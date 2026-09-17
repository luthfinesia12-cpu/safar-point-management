@extends('layouts.app', ['title'=>'Penerimaan Barang'])
@section('content')
<div class="page-header"><div><div class="breadcrumb">Inventory / Penerimaan</div><h1>Penerimaan Barang</h1><p>Catat barang yang diterima dari Purchase Order.</p></div><button data-modal-open="create-gr"><x-icon name="plus"/> Posting Penerimaan</button></div>
<section class="panel"><div class="panel-header"><div><h2>Riwayat Penerimaan</h2><p>Penerimaan parsial maupun lengkap tercatat di sini.</p></div></div><div class="table-wrap"><table><thead><tr><th>Nomor</th><th>Purchase Order</th><th>Status</th><th>Waktu Posting</th></tr></thead><tbody>
@forelse($receipts as $receipt)<tr><td><strong>{{ $receipt->document_number }}</strong></td><td>{{ $receipt->purchaseOrder->document_number }}</td><td><span class="status-badge {{ $receipt->status->value }}">{{ str_replace('_',' ',$receipt->status->value) }}</span></td><td>{{ $receipt->posted_at?->format('d/m/Y H:i')??'—' }}</td></tr>
@empty<tr><td colspan="4"><div class="empty-state"><div class="empty-icon"><x-icon name="box"/></div><h3>Belum ada penerimaan</h3><p>Posting penerimaan saat barang datang.</p></div></td></tr>@endforelse
</tbody></table></div>{{ $receipts->links() }}</section>
<div class="modal" id="create-gr"><div class="modal-backdrop" data-modal-close></div><section class="drawer">
    <div class="drawer-header"><div><h2>Posting Penerimaan</h2><p>Stok akan bertambah setelah transaksi diposting.</p></div><button class="icon-button" type="button" data-modal-close>×</button></div>
    <form method="post" action="{{ route('goods-receipts.store') }}">@csrf
        <label>Purchase Order<div class="field-with-action"><select id="gr-order" name="purchase_order_id" required data-po-select><option value="">Pilih PO</option>@foreach($orders as $order)<option value="{{ $order->id }}" data-product="{{ $order->items->first()?->product_id }}" data-products="{{ $order->items->pluck('product_id')->implode(',') }}">{{ $order->document_number }} — {{ $order->items->first()?->product?->name }}</option>@endforeach</select><button type="button" class="secondary scan-button" data-scan-trigger data-scan-mode="purchase-order" data-scan-target="#gr-order"><x-icon name="search"/> Scan SKU</button></div><small>Scan akan memilih PO aktif yang memuat produk tersebut.</small></label>
        <label>Gudang Tujuan<select name="warehouse_id" required><option value="">Pilih gudang</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->code }} — {{ $warehouse->name }}</option>@endforeach</select></label>
        <input type="hidden" name="items[0][product_id]" value="" data-po-product>
        <label>Jumlah Diterima<input name="items[0][quantity]" type="number" step="0.001" min="0.001" required></label>
        <div class="drawer-actions"><button class="outline" type="button" data-modal-close>Batal</button><button>Posting Penerimaan</button></div>
    </form>
</section></div>
@endsection
