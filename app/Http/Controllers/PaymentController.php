<?php

namespace App\Http\Controllers;

use App\Events\WorkflowStatusChanged;
use App\Http\Requests\PaymentRequest;
use App\Models\BankAccount;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\AuditService;
use App\Services\ProcurementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        return view('procurement.payments.index', ['payments' => Payment::with(['purchaseOrder', 'pic', 'verifier'])->latest()->paginate(20), 'orders' => PurchaseOrder::whereNotIn('status', ['cancelled'])->orderByDesc('id')->get(), 'accounts' => BankAccount::where('is_active', true)->orderBy('bank_name')->get()]);
    }

    public function store(PaymentRequest $request, ProcurementService $service, AuditService $audit): RedirectResponse
    {
        $data = $request->validated();
        foreach (['proof_path', 'receipt_path'] as $field) {
            if ($request->hasFile($field)) {
                $data[$field] = $request->file($field)->store('payments');
            }
        }
        $payment = $service->createPayment($data, $request->user()->id);
        event(new WorkflowStatusChanged(User::role('Owner/Direksi')->pluck('id')->all(), 'Pembayaran menunggu verifikasi', 'Pembayaran PO '.$payment->purchaseOrder->document_number.' menunggu verifikasi Owner/Direksi.', ['payment_id' => $payment->id]));
        $audit->record($request, 'payment_submitted', 'finance', 'Pembayaran diajukan untuk verifikasi', null, $payment->toArray());

        return back()->with('status', 'Pembayaran menunggu verifikasi Owner/Direksi.');
    }

    public function verify(Payment $payment, Request $request, ProcurementService $service, AuditService $audit): RedirectResponse
    {
        abort_unless($request->user()->hasRole('Owner/Direksi'), 403);
        $data = $request->validate(['decision' => ['required', 'in:verified,rejected'], 'notes' => ['nullable', 'string']]);
        $payment = $service->verifyPayment($payment, $request->user()->id, $data['decision'], $data['notes'] ?? null);
        event(new WorkflowStatusChanged([$payment->pic_id], 'Verifikasi pembayaran', 'Pembayaran telah '.$data['decision'].'.', ['payment_id' => $payment->id]));
        $audit->record($request, 'payment_'.$data['decision'], 'finance', 'Verifikasi pembayaran dicatat', null, $payment->toArray());

        return back()->with('status', 'Verifikasi pembayaran berhasil dicatat.');
    }
}
