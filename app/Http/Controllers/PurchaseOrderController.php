<?php

namespace App\Http\Controllers;

use App\Events\WorkflowStatusChanged;
use App\Http\Requests\PurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderRevision;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\User;
use App\Services\AuditService;
use App\Services\ProcurementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function index(): View
    {
        return view('procurement.orders.index', ['orders' => PurchaseOrder::with('supplier')->latest()->paginate(20), 'requests' => PurchaseRequest::where('status', 'approved')->with('items.product')->get(), 'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(PurchaseOrderRequest $request, ProcurementService $service, AuditService $audit): RedirectResponse
    {
        $po = $service->createPurchaseOrder($request->validated(), $request->user()->id);
        $audit->record($request, 'purchase_order_created', 'procurement', 'PO diterbitkan', null, $po->toArray());

        return back()->with('status', 'PO berhasil diterbitkan.');
    }

    public function revise(PurchaseOrder $purchaseOrder, Request $request, ProcurementService $service, AuditService $audit): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000'], 'supplier_id' => ['nullable', 'exists:suppliers,id'], 'expected_arrival' => ['nullable', 'date'], 'shipping_address' => ['nullable', 'string'], 'notes' => ['nullable', 'string'], 'discount' => ['nullable', 'numeric', 'gte:0'], 'tax' => ['nullable', 'numeric', 'gte:0'], 'shipping_cost' => ['nullable', 'numeric', 'gte:0'], 'items' => ['nullable', 'array'], 'items.*.product_id' => ['required_with:items', 'exists:products,id'], 'items.*.quantity' => ['required_with:items', 'numeric', 'gt:0'], 'items.*.unit_price' => ['required_with:items', 'numeric', 'gte:0']]);
        $revision = $service->revisePurchaseOrder($purchaseOrder, $data, $request->user()->id);
        if ($revision instanceof PurchaseOrderRevision) {
            event(new WorkflowStatusChanged(User::role('Owner/Direksi')->pluck('id')->all(), 'Revisi PO menunggu persetujuan', 'Revisi PO '.$purchaseOrder->document_number.' menunggu persetujuan Owner/Direksi.', ['purchase_order_revision_id' => $revision->id]));
        }
        $audit->record($request, $revision instanceof PurchaseOrderRevision ? 'purchase_order_revision_submitted' : 'purchase_order_updated', 'procurement', 'Perubahan PO dicatat', null, $revision->toArray());

        return back()->with('status', $revision instanceof PurchaseOrderRevision ? 'Revisi PO menunggu persetujuan Owner/Direksi.' : 'Perubahan non-material PO berhasil dicatat.');
    }

    public function decideRevision(PurchaseOrderRevision $purchaseOrderRevision, Request $request, ProcurementService $service, AuditService $audit): RedirectResponse
    {
        abort_unless($request->user()->hasRole('Owner/Direksi'), 403);
        $data = $request->validate(['decision' => ['required', 'in:approved,rejected'], 'notes' => ['nullable', 'string']]);
        $revision = $service->decidePurchaseOrderRevision($purchaseOrderRevision, $request->user()->id, $data['decision'], $data['notes'] ?? null);
        event(new WorkflowStatusChanged([$revision->changed_by], 'Keputusan revisi PO', 'Revisi PO telah '.$data['decision'].'.', ['purchase_order_revision_id' => $revision->id]));
        $audit->record($request, 'purchase_order_revision_'.$data['decision'], 'procurement', 'Keputusan revisi PO dicatat', null, $revision->toArray());

        return back()->with('status', 'Keputusan revisi PO berhasil dicatat.');
    }
}
