@extends('layouts.app', ['title'=>'Penyesuaian Stok'])
@section('content')
<div class="page-header"><div><div class="breadcrumb">Inventory / Penyesuaian Stok</div><h1>Penyesuaian Stok</h1><p>Ajukan koreksi stok dengan alasan dan persetujuan resmi.</p></div><button data-modal-open="create-adj"><x-icon name="plus"/> Ajukan ADJ</button></div>
<section class="panel"><div class="panel-header"><div><h2>Daftar Penyesuaian</h2><p>Stok tidak dapat menjadi negatif dan setiap perubahan tercatat.</p></div></div><div class="table-wrap"><table><thead><tr><th>Nomor</th><th>Gudang</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
@forelse($adjustments as $adjustment)<tr><td><strong>{{ $adjustment->document_number }}</strong></td><td>{{ $adjustment->warehouse->name }}</td><td><span class="status-badge {{ $adjustment->status->value }}">{{ str_replace('_',' ',$adjustment->status->value) }}</span></td><td>@if($adjustment->status->value==='submitted')<form method="post" action="{{ route('adjustments.decide',$adjustment) }}">@csrf<input type="hidden" name="decision" value="approved"><button>Setujui & Posting</button></form>@else—@endif</td></tr>
@empty<tr><td colspan="4"><div class="empty-state"><div class="empty-icon"><x-icon name="box"/></div><h3>Belum ada penyesuaian</h3><p>Gunakan ADJ hanya untuk koreksi stok yang dapat dipertanggungjawabkan.</p></div></td></tr>@endforelse
</tbody></table></div>{{ $adjustments->links() }}</section>
<div class="modal" id="create-adj"><div class="modal-backdrop" data-modal-close></div><section class="drawer">
    <div class="drawer-header"><div><h2>Ajukan Penyesuaian Stok</h2><p>Persetujuan Owner/Direksi diperlukan.</p></div><button class="icon-button" type="button" data-modal-close>×</button></div>
    <form method="post" action="{{ route('adjustments.store') }}">@csrf
        <label>Gudang<select name="warehouse_id" required><option value="">Pilih gudang</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->code }} — {{ $warehouse->name }}</option>@endforeach</select></label>
        <label>Produk<div class="field-with-action"><select id="adj-product" name="items[0][product_id]" required><option value="">Pilih produk</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->sku }} — {{ $product->name }}{{ $product->barcode ? ' · '.$product->barcode : '' }}</option>@endforeach</select><button type="button" class="secondary scan-button" data-scan-trigger data-scan-mode="product" data-scan-target="#adj-product"><x-icon name="search"/> Scan SKU</button></div></label>
        <label>Jenis Penyesuaian<select name="adjustment_type"><option value="increase">Tambah Stok</option><option value="decrease">Kurangi Stok</option><option value="correction">Koreksi</option></select></label>
        <label>Alasan<textarea name="reason" required placeholder="Jelaskan penyebab dan dasar penyesuaian"></textarea></label>
        <label>Perubahan Kuantitas<input name="items[0][quantity_change]" type="number" step="0.001" required></label>
        <div class="drawer-actions"><button class="outline" type="button" data-modal-close>Batal</button><button>Ajukan Penyesuaian</button></div>
    </form>
</section></div>
@endsection
