<?php

namespace App\Http\Controllers;

use App\Events\WorkflowStatusChanged;
use App\Http\Requests\PurchaseRequestRequest;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\AuditService;
use App\Services\ProcurementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseRequestController extends Controller
{
    public function index(): View
    {
        return view('procurement.requests.index', ['requests' => PurchaseRequest::with('requester')->latest()->paginate(20), 'products' => Product::where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(PurchaseRequestRequest $request, ProcurementService $service, AuditService $audit): RedirectResponse
    {
        $pr = $service->createPurchaseRequest($request->validated(), $request->user()->id);
        $audit->record($request, 'purchase_request_created', 'procurement', 'PR dibuat', null, $pr->toArray());

        return back()->with('status', 'PR berhasil disimpan sebagai draft.');
    }

    public function submit(PurchaseRequest $purchaseRequest, Request $request, ProcurementService $service, AuditService $audit): RedirectResponse
    {
        abort_unless($purchaseRequest->requester_id === $request->user()->id && $request->user()->can('purchase-requests.submit'), 403);
        $pr = $service->submitPurchaseRequest($purchaseRequest);
        event(new WorkflowStatusChanged(User::role('Owner/Direksi')->pluck('id')->all(), 'PR menunggu persetujuan', 'PR '.$pr->document_number.' menunggu persetujuan Owner/Direksi.', ['purchase_request_id' => $pr->id]));
        $audit->record($request, 'purchase_request_submitted', 'procurement', 'PR diajukan', null, $pr->toArray());

        return back()->with('status', 'PR diajukan untuk persetujuan Owner/Direksi.');
    }

    public function decide(PurchaseRequest $purchaseRequest, Request $request, ProcurementService $service, AuditService $audit): RedirectResponse
    {
        abort_unless($request->user()->hasRole('Owner/Direksi'), 403);
        $data = $request->validate(['decision' => ['required', 'in:approved,rejected'], 'notes' => ['nullable', 'string']]);
        $pr = $service->decidePurchaseRequest($purchaseRequest, $request->user()->id, $data['decision'], $data['notes'] ?? null);
        event(new WorkflowStatusChanged([$pr->requester_id], 'Keputusan PR', 'PR '.$pr->document_number.' telah '.$data['decision'].'.', ['purchase_request_id' => $pr->id]));
        $audit->record($request, 'purchase_request_'.$data['decision'], 'procurement', 'Keputusan PR dicatat', null, $pr->toArray());

        return back()->with('status', 'Keputusan PR berhasil dicatat.');
    }
}
