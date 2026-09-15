<?php

namespace App\Http\Controllers;

use App\Models\DocumentWorkflowRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Services\AuditService;
use App\Services\ProcurementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DocumentWorkflowController extends Controller
{
    public function cancelPurchaseRequest(PurchaseRequest $purchaseRequest, Request $request, ProcurementService $service, AuditService $audit): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        abort_unless($purchaseRequest->requester_id === $request->user()->id || $request->user()->hasRole('Owner/Direksi'), 403);
        $pr = $purchaseRequest->status->value === 'draft'
            ? $service->cancelPurchaseRequest($purchaseRequest, $request->user()->id, $data['reason'])
            : $service->requestCancellation($purchaseRequest, $request->user()->id, $data['reason']);
        $audit->record($request, 'purchase_request_cancellation_requested', 'procurement', 'Pembatalan PR dicatat', null, $pr->toArray());

        return back()->with('status', 'Pembatalan PR berhasil dicatat.');
    }

    public function cancelPurchaseOrder(PurchaseOrder $purchaseOrder, Request $request, ProcurementService $service, AuditService $audit): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000'], 'finance_confirmed' => ['sometimes', 'boolean']]);
        abort_unless($request->user()->hasRole('Owner/Direksi'), 403);
        $workflow = $service->requestCancellation($purchaseOrder, $request->user()->id, $data['reason']);
        $audit->record($request, 'purchase_order_cancellation_requested', 'procurement', 'Pembatalan PO diajukan', null, $workflow->toArray());

        return back()->with('status', 'Pembatalan PO menunggu keputusan Owner/Direksi.');
    }

    public function closeShort(PurchaseOrder $purchaseOrder, Request $request, ProcurementService $service, AuditService $audit): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        abort_unless($request->user()->hasRole('Purchasing'), 403);
        $workflow = $service->requestCloseShort($purchaseOrder, $request->user()->id, $data['reason']);
        $audit->record($request, 'purchase_order_close_short_requested', 'procurement', 'TUTUP KURANG diajukan', null, $workflow->toArray());

        return back()->with('status', 'Pengajuan TUTUP KURANG menunggu persetujuan Owner/Direksi.');
    }

    public function decide(DocumentWorkflowRequest $workflow, Request $request, ProcurementService $service, AuditService $audit): RedirectResponse
    {
        abort_unless($request->user()->hasRole('Owner/Direksi'), 403);
        $data = $request->validate(['decision' => ['required', 'in:approved,rejected'], 'notes' => ['nullable', 'string', 'max:2000'], 'finance_confirmed' => ['sometimes', 'boolean']]);
        $result = $service->decideWorkflowRequest($workflow, $request->user()->id, $data['decision'], $data['notes'] ?? null, (bool) ($data['finance_confirmed'] ?? false));
        $audit->record($request, 'document_workflow_'.$data['decision'], 'procurement', 'Keputusan workflow dokumen dicatat', null, $result->toArray());

        return back()->with('status', 'Keputusan workflow berhasil dicatat.');
    }
}
