<?php

namespace App\Http\Controllers;

use App\Http\Requests\GoodsReceiptRequest;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Services\ProcurementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GoodsReceiptController extends Controller
{
    public function index(): View
    {
        return view('procurement.receipts.index', ['receipts' => GoodsReceipt::with('purchaseOrder')->latest()->paginate(20), 'orders' => PurchaseOrder::whereIn('status', ['approved', 'completed'])->with('items.product')->get(), 'warehouses' => Warehouse::where('is_active', true)->get()]);
    }

    public function store(GoodsReceiptRequest $request, ProcurementService $service, AuditService $audit): RedirectResponse
    {
        $gr = $service->postGoodsReceipt($request->validated(), $request->user()->id);
        $audit->record($request, 'goods_receipt_posted', 'inventory', 'GR diposting dan stok diperbarui', null, $gr->toArray());

        return back()->with('status', 'GR berhasil diposting.');
    }
}
