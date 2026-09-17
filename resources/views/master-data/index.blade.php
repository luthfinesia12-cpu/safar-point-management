@extends('layouts.app', ['title' => 'Master Data'])
@php
    $labels = ['products'=>'Produk','categories'=>'Kategori','brands'=>'Brand','units'=>'Satuan','suppliers'=>'Supplier','warehouses'=>'Gudang','bank-accounts'=>'Rekening Bank'];
    $fieldLabels = ['name'=>'Nama','barcode'=>'Barcode / Kode Scan','minimum_stock'=>'Minimum Stok','specification'=>'Spesifikasi','category_id'=>'Kategori','brand_id'=>'Brand','unit_id'=>'Satuan','is_active'=>'Status Aktif','contact'=>'Kontak','address'=>'Alamat','bank_account'=>'Rekening Bank','pic'=>'PIC','bank_name'=>'Nama Bank','account_number'=>'Nomor Rekening','account_holder'=>'Nama Pemilik'];
    $currentLabel = $labels[$type] ?? ucfirst(str_replace('-', ' ', $type));
@endphp
@section('content')
<div class="page-header">
    <div><div class="breadcrumb">Master Data / {{ $currentLabel }}</div><h1>Daftar {{ $currentLabel }}</h1><p>Kelola {{ strtolower($currentLabel) }} yang digunakan pada seluruh transaksi.</p></div>
    <div class="actions">@if($type==='products') @can('reports.view')<a class="button outline" href="{{ route('exports.xlsx', $type) }}"><x-icon name="download"/> Ekspor</a>@endcan @endif <button type="button" data-modal-open="create-master"><x-icon name="plus"/> Tambah {{ $currentLabel }}</button></div>
</div>
<div class="subnav">@foreach($labels as $key=>$label) @if($key!=='bank-accounts')<a href="{{ route('master-data.index', $key) }}" class="{{ $type===$key?'active':'' }}">{{ $label }}</a>@endif @endforeach</div>
@if($type==='products')
<div class="metrics-grid">
    <section class="metric-card"><div class="metric-icon"><x-icon name="box"/></div><div><div class="metric-label">Total Produk</div><div class="metric-value">{{ $items->total() }}</div><div class="metric-help">Produk terdaftar</div></div></section>
    <section class="metric-card"><div class="metric-icon green"><x-icon name="check"/></div><div><div class="metric-label">Produk Ditampilkan</div><div class="metric-value">{{ $items->count() }}</div><div class="metric-help">Pada halaman ini</div></div></section>
    <section class="metric-card"><div class="metric-icon gold"><x-icon name="warning"/></div><div><div class="metric-label">Stok Minimum</div><div class="metric-value">{{ $items->sum('minimum_stock') }}</div><div class="metric-help">Total batas minimum</div></div></section>
    <section class="metric-card"><div class="metric-icon blue"><x-icon name="database"/></div><div><div class="metric-label">Kategori Aktif</div><div class="metric-value">{{ $categories->count() }}</div><div class="metric-help">Kategori tersedia</div></div></section>
</div>
@endif
<section class="panel">
    <div class="panel-header"><div><h2>Data {{ $currentLabel }}</h2><p>Cari dan kelola data yang telah tersimpan.</p></div></div>
    <form class="toolbar" method="get">
        <label class="search-field">Pencarian<input id="master-search" name="search" value="{{ request('search') }}" type="search" placeholder="Cari kode, nama, SKU, atau barcode..."></label>
        @if($type==='products')<button type="button" class="secondary scan-button" data-scan-trigger data-scan-mode="input" data-scan-target="#master-search"><x-icon name="search"/> Scan Cari</button>@endif
        <button class="secondary">Cari</button>@if(request('search'))<a class="button outline" href="{{ route('master-data.index', $type) }}">Reset</a>@endif
    </form>
    <div class="table-wrap" id="master-table"><table><thead><tr><th>Kode</th><th>Nama/Identitas</th>@if($type==='products')<th>Kategori</th><th>Brand</th><th>Satuan</th><th>Minimum Stok</th>@endif<th>Status</th><th>Aksi</th></tr></thead><tbody>
        @forelse($items as $item)
        <tr><td><strong>{{ $item->code ?? '-' }}</strong>@if($item->sku)<br><small>SKU: {{ $item->sku }}</small>@endif</td><td><strong>{{ $item->name ?? $item->bank_name.' / '.$item->account_number }}</strong>@if($type==='products' && $item->barcode)<br><small>Barcode: {{ $item->barcode }}</small>@endif</td>@if($type==='products')<td>{{ $item->category?->name ?? '—' }}</td><td>{{ $item->brand?->name ?? '—' }}</td><td>{{ $item->unit?->name ?? '—' }}</td><td>{{ number_format($item->minimum_stock,0,',','.') }}</td>@endif<td><span class="status-badge {{ $item->is_active===false?'inactive':'' }}">{{ $item->is_active===null||$item->is_active?'Aktif':'Nonaktif' }}</span></td><td><button type="button" class="secondary" data-modal-open="edit-{{ $item->id }}"><x-icon name="edit"/> Edit</button></td></tr>
        @empty
        <tr><td colspan="{{ $type==='products'?8:4 }}"><div class="empty-state"><div class="empty-icon"><x-icon name="database"/></div><h3>Belum ada {{ strtolower($currentLabel) }}</h3><p>Klik “Tambah {{ $currentLabel }}” untuk membuat data pertama.</p></div></td></tr>
        @endforelse
    </tbody></table></div>{{ $items->links() }}
</section>
<div class="modal" id="create-master"><div class="modal-backdrop" data-modal-close></div><section class="drawer"><div class="drawer-header"><div><h2>Tambah {{ $currentLabel }}</h2><p>Lengkapi informasi berikut dengan benar.</p></div><button type="button" class="icon-button" data-modal-close>×</button></div>
    <form method="post" action="{{ route('master-data.store', $type) }}">@csrf<div class="grid">
        @foreach($fields as $field)
        <label>{{ $fieldLabels[$field]??ucfirst(str_replace('_',' ',$field)) }}
            @if(in_array($field,['category_id','brand_id','unit_id']))
                <select name="{{ $field }}" @required($field==='unit_id')><option value="">Pilih {{ $fieldLabels[$field] }}</option>@php($options=$field==='category_id'?$categories:($field==='brand_id'?$brands:$units)) @foreach($options as $option)<option value="{{ $option->id }}">{{ $option->code }} — {{ $option->name }}</option>@endforeach</select>
            @elseif($field==='barcode')
                <div class="field-with-action"><input id="create-barcode" name="barcode" value="{{ old('barcode') }}" autocomplete="off" placeholder="Scan atau ketik barcode"><button type="button" class="secondary scan-button" data-scan-trigger data-scan-mode="input" data-scan-target="#create-barcode"><x-icon name="search"/> Scan</button></div><small>SKU internal dibuat otomatis. Barcode harus unik jika diisi.</small>
            @elseif($field==='is_active')
                <select name="is_active"><option value="1">Aktif</option><option value="0">Nonaktif</option></select>
            @elseif(in_array($field,['specification','address']))
                <textarea name="{{ $field }}" placeholder="Masukkan {{ strtolower($fieldLabels[$field]??$field) }}">{{ old($field) }}</textarea>
            @else
                <input name="{{ $field }}" value="{{ old($field) }}" type="{{ $field==='minimum_stock'?'number':'text' }}" @if($field==='minimum_stock')step="0.001" min="0"@endif @required(in_array($field,['name','bank_name','account_number','account_holder','minimum_stock']))>
            @endif
        </label>
        @endforeach
    </div><div class="drawer-actions"><button type="button" class="outline" data-modal-close>Batal</button><button>Simpan {{ $currentLabel }}</button></div></form>
</section></div>
@foreach($items as $item)
<div class="modal" id="edit-{{ $item->id }}"><div class="modal-backdrop" data-modal-close></div><section class="drawer"><div class="drawer-header"><div><h2>Edit {{ $currentLabel }}</h2><p>{{ $item->code??'Perbarui data' }}</p></div><button type="button" class="icon-button" data-modal-close>×</button></div>
    <form method="post" action="{{ route('master-data.update',[$type,$item->id]) }}">@csrf @method('put')<div class="grid">
        @foreach($fields as $field)
        <label>{{ $fieldLabels[$field]??ucfirst(str_replace('_',' ',$field)) }}
            @if(in_array($field,['category_id','brand_id','unit_id']))
                <select name="{{ $field }}"><option value="">Pilih {{ $fieldLabels[$field] }}</option>@php($options=$field==='category_id'?$categories:($field==='brand_id'?$brands:$units)) @foreach($options as $option)<option value="{{ $option->id }}" @selected($item->{$field}==$option->id)>{{ $option->code }} — {{ $option->name }}</option>@endforeach</select>
            @elseif($field==='barcode')
                <div class="field-with-action"><input id="edit-barcode-{{ $item->id }}" name="barcode" value="{{ $item->barcode }}" autocomplete="off" placeholder="Scan atau ketik barcode"><button type="button" class="secondary scan-button" data-scan-trigger data-scan-mode="input" data-scan-target="#edit-barcode-{{ $item->id }}"><x-icon name="search"/> Scan</button></div>
            @elseif($field==='is_active')
                <select name="is_active"><option value="1" @selected($item->is_active)>Aktif</option><option value="0" @selected(!$item->is_active)>Nonaktif</option></select>
            @elseif(in_array($field,['specification','address']))
                <textarea name="{{ $field }}">{{ $item->{$field} }}</textarea>
            @else
                <input name="{{ $field }}" value="{{ $item->{$field} }}" @if($field==='minimum_stock')type="number" step="0.001" min="0"@endif>
            @endif
        </label>
        @endforeach
    </div><div class="drawer-actions"><button type="button" class="outline" data-modal-close>Batal</button><button>Simpan Perubahan</button></div></form>
</section></div>
@endforeach
@endsection
