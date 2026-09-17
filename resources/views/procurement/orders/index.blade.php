@extends('layouts.app', ['title'=>'Purchase Order'])
@section('content')
<div class="page-header"><div><div class="breadcrumb">Purchasing / Purchase Order</div><h1>Purchase Order</h1><p>Terbitkan dan pantau pesanan kepada supplier.</p></div><div class="actions">@can('reports.view')<a class="button outline" href="{{ route('exports.xlsx','purchase-orders') }}"><x-icon name="download"/> Ekspor</a>@endcan<button data-modal-open="create-po"><x-icon name="plus"/> Terbitkan PO</button></div></div>
<section class="panel"><div class="panel-header"><div><h2>Daftar Purchase Order</h2><p>PO yang dibuat dari PR yang telah disetujui.</p></div></div><div class="toolbar"><label class="search-field">Cari PO<input type="search" placeholder="Cari nomor atau supplier..." data-table-search="#po-table"></label></div><div class="table-wrap" id="po-table"><table><thead><tr><th>Nomor</th><th>Supplier</th><th>Total</th><th>Status Pembayaran</th></tr></thead><tbody>
@forelse($orders as $order)<tr><td><strong>{{ $order->document_number }}</strong></td><td>{{ $order->supplier->name }}</td><td><strong>Rp {{ number_format($order->total,0,',','.') }}</strong></td><td><span class="status-badge {{ $order->payment_status->value }}">{{ str_replace('_',' ',$order->payment_status->value) }}</span></td></tr>
@empty<tr><td colspan="4"><div class="empty-state"><div class="empty-icon"><x-icon name="cart"/></div><h3>Belum ada Purchase Order</h3><p>PO dapat dibuat setelah PR disetujui.</p></div></td></tr>@endforelse
</tbody></table></div>{{ $orders->links() }}</section>
<div class="modal" id="create-po"><div class="modal-backdrop" data-modal-close></div><section class="drawer">
    <div class="drawer-header"><div><h2>Terbitkan Purchase Order</h2><p>Pilih PR yang telah disetujui.</p></div><button class="icon-button" type="button" data-modal-close>×</button></div>
    <form method="post" action="{{ route('purchase-orders.store') }}">@csrf
        <label>PR Disetujui<div class="field-with-action"><select id="po-request" name="purchase_request_id" required data-pr-select><option value="">Pilih PR</option>@foreach($requests as $request)<option value="{{ $request->id }}" data-product="{{ $request->items->first()?->product_id }}" data-products="{{ $request->items->pluck('product_id')->implode(',') }}">{{ $request->document_number }} — {{ $request->reason }}</option>@endforeach</select><button type="button" class="secondary scan-button" data-scan-trigger data-scan-mode="purchase-request" data-scan-target="#po-request"><x-icon name="search"/> Scan SKU</button></div><small>Scan akan memilih PR disetujui yang memuat produk tersebut.</small></label>
        <label>Supplier<select name="supplier_id" required><option value="">Pilih supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->code }} — {{ $supplier->name }}</option>@endforeach</select></label>
        <input type="hidden" name="items[0][product_id]" value="" data-pr-product>
        <div class="grid"><label>Jumlah<input name="items[0][quantity]" type="number" step="0.001" min="0.001" required></label><label>Harga Satuan<input name="items[0][unit_price]" type="number" step="0.01" min="0" required placeholder="Rp"></label></div>
        <div class="drawer-actions"><button class="outline" type="button" data-modal-close>Batal</button><button>Terbitkan PO</button></div>
    </form>
</section></div>
@endsection
