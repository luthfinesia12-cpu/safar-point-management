<?php

namespace App\Http\Controllers;

use App\Events\WorkflowStatusChanged;
use App\Http\Requests\AdjustmentRequest;
use App\Models\Adjustment;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Services\ProcurementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdjustmentController extends Controller
{
    public function index(): View
    {
        return view('procurement.adjustments.index', ['adjustments' => Adjustment::with(['warehouse', 'requester'])->latest()->paginate(20), 'products' => Product::where('is_active', true)->orderBy('name')->get(), 'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(AdjustmentRequest $request, ProcurementService $service, AuditService $audit): RedirectResponse
    {
        $adjustment = $service->createAdjustment($request->validated(), $request->user()->id);
        $service->submitAdjustment($adjustment);
        event(new WorkflowStatusChanged(User::role('Owner/Direksi')->pluck('id')->all(), 'ADJ menunggu persetujuan', 'ADJ '.$adjustment->fresh()->document_number.' menunggu persetujuan Owner/Direksi.', ['adjustment_id' => $adjustment->id]));
        $audit->record($request, 'adjustment_submitted', 'inventory', 'ADJ diajukan untuk persetujuan', null, $adjustment->fresh()->toArray());

        return back()->with('status', 'ADJ diajukan untuk persetujuan Owner/Direksi.');
    }

    public function decide(Adjustment $adjustment, Request $request, ProcurementService $service, AuditService $audit): RedirectResponse
    {
        abort_unless($request->user()->hasRole('Owner/Direksi'), 403);
        $data = $request->validate(['decision' => ['required', 'in:approved,rejected'], 'notes' => ['nullable', 'string']]);
        $adjustment = $service->decideAdjustment($adjustment, $request->user()->id, $data['decision'], $data['notes'] ?? null);
        event(new WorkflowStatusChanged([$adjustment->requester_id], 'Keputusan ADJ', 'ADJ '.$adjustment->document_number.' telah '.$data['decision'].'.', ['adjustment_id' => $adjustment->id]));
        $audit->record($request, 'adjustment_'.$data['decision'], 'inventory', 'Keputusan ADJ dicatat', null, $adjustment->toArray());

        return back()->with('status', 'Keputusan ADJ berhasil dicatat.');
    }
}
