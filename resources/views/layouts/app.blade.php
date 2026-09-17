<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Safar Point Management' }} | Safar Point</title>
    @vite(['resources/css/app.css', 'resources/css/scanner.css', 'resources/js/app.js'])
</head>
<body>
@auth
@php
    $masterActive = request()->routeIs('master-data.*');
    $purchasingActive = request()->routeIs('purchase-requests.*', 'purchase-orders.*', 'purchase-order-revisions.*', 'document-workflow-requests.*');
    $inventoryActive = request()->routeIs('goods-receipts.*', 'adjustments.*');
@endphp
<div class="app-shell" data-app-shell>
    <aside class="sidebar" id="app-sidebar">
        <div class="brand"><div class="brand-mark">SP</div><div><strong><h3>SAFAR POINT</h3></strong><span>Management System</span></div></div>
        <nav class="side-nav" aria-label="Navigasi utama">
            @can('dashboard.view')<a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"><x-icon name="dashboard"/><span>Dashboard</span></a>@endcan
            @can('master-data.manage')
            <div class="nav-group {{ $masterActive ? 'open' : '' }}" data-nav-group>
                <button type="button" class="nav-item nav-toggle {{ $masterActive ? 'active' : '' }}" data-nav-toggle aria-expanded="{{ $masterActive ? 'true' : 'false' }}"><x-icon name="database"/><span>Master Data</span><x-icon name="chevron"/></button>
                <div class="nav-submenu">
                    @foreach(['products' => 'Produk', 'categories' => 'Kategori', 'brands' => 'Brand', 'units' => 'Satuan', 'suppliers' => 'Supplier', 'warehouses' => 'Gudang'] as $type => $label)
                    <a href="{{ route('master-data.index', $type) }}" class="{{ request()->route('type') === $type ? 'active' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>
            @endcan
            <div class="nav-label">Operasional</div>
            @can('purchase-requests.create')
            <div class="nav-group {{ $purchasingActive ? 'open' : '' }}" data-nav-group>
                <button type="button" class="nav-item nav-toggle {{ $purchasingActive ? 'active' : '' }}" data-nav-toggle aria-expanded="{{ $purchasingActive ? 'true' : 'false' }}"><x-icon name="cart"/><span>Purchasing</span><x-icon name="chevron"/></button>
                <div class="nav-submenu"><a href="{{ route('purchase-requests.index') }}" class="{{ request()->routeIs('purchase-requests.*') ? 'active' : '' }}">Purchase Request</a>@can('purchase-orders.create')<a href="{{ route('purchase-orders.index') }}" class="{{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}">Purchase Order</a>@endcan</div>
            </div>
            @endcan
            @can('goods-receipts.create')
            <div class="nav-group {{ $inventoryActive ? 'open' : '' }}" data-nav-group>
                <button type="button" class="nav-item nav-toggle {{ $inventoryActive ? 'active' : '' }}" data-nav-toggle aria-expanded="{{ $inventoryActive ? 'true' : 'false' }}"><x-icon name="box"/><span>Inventory</span><x-icon name="chevron"/></button>
                <div class="nav-submenu"><a href="{{ route('goods-receipts.index') }}" class="{{ request()->routeIs('goods-receipts.*') ? 'active' : '' }}">Penerimaan</a>@can('adjustments.create')<a href="{{ route('adjustments.index') }}" class="{{ request()->routeIs('adjustments.*') ? 'active' : '' }}">Penyesuaian Stok</a>@endcan</div>
            </div>
            @endcan
            @can('payments.create')<a href="{{ route('payments.index') }}" class="nav-item {{ request()->routeIs('payments.*') ? 'active' : '' }}"><x-icon name="card"/><span>Pembayaran</span></a>@endcan
            <div class="nav-label">Administrasi</div>
            @can('users.view')<a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}"><x-icon name="users"/><span>Pengguna</span></a>@endcan
            @can('roles.manage')<a href="{{ route('roles.index') }}" class="nav-item {{ request()->routeIs('roles.*') ? 'active' : '' }}"><x-icon name="shield"/><span>Role & Permission</span></a>@endcan
            @can('audit.view')<a href="{{ route('audit.index') }}" class="nav-item {{ request()->routeIs('audit.*') ? 'active' : '' }}"><x-icon name="file"/><span>Audit Aktivitas</span></a>@endcan
            @can('settings.manage')<a href="{{ route('settings.company') }}" class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}"><x-icon name="settings"/><span>Pengaturan</span></a>@endcan
        </nav>
        <div class="sidebar-footer"><div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div><div><strong>{{ auth()->user()->name }}</strong><span>{{ auth()->user()->roles->pluck('name')->first() ?? 'Pengguna' }}</span></div></div>
    </aside>
    <div class="page-shell">
        <header class="topbar">
            <button class="icon-button mobile-menu" type="button" data-sidebar-toggle aria-label="Buka navigasi"><x-icon name="menu"/></button>
            <div class="global-search"><x-icon name="search"/><input type="search" placeholder="Cari menu atau data..." data-global-search><kbd>⌘ K</kbd></div>
            <div class="topbar-actions"><span class="date-chip">{{ now()->translatedFormat('d M Y') }}</span><div class="top-user"><div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div><div><strong>{{ auth()->user()->name }}</strong><span>{{ auth()->user()->roles->pluck('name')->first() ?? 'Pengguna' }}</span></div></div><form method="post" action="{{ route('logout') }}">@csrf<button class="icon-button" title="Keluar" aria-label="Keluar"><x-icon name="logout"/></button></form></div>
        </header>
        <main class="page-content">
            @if(session('status'))<div class="alert alert-success" role="status"><x-icon name="check"/><span>{{ session('status') }}</span><button type="button" data-dismiss-alert>×</button></div>@endif
            @if($errors->any())<div class="alert alert-error" role="alert"><x-icon name="warning"/><div><strong>Periksa kembali data berikut:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div><button type="button" data-dismiss-alert>×</button></div>@endif
            @yield('content')
        </main>
    </div>
    <div class="sidebar-backdrop" data-sidebar-toggle></div>
    <div class="modal scanner-modal" id="sku-scanner" data-scan-lookup-url="{{ route('products.scan-lookup') }}" aria-hidden="true">
        <div class="modal-backdrop" data-scan-close></div>
        <section class="scanner-dialog" role="dialog" aria-modal="true" aria-labelledby="scanner-title">
            <div class="drawer-header"><div><h2 id="scanner-title">Scan SKU / Barcode</h2><p>Arahkan kamera ke kode, atau gunakan scanner USB/Bluetooth.</p></div><button type="button" class="icon-button" data-scan-close aria-label="Tutup scanner">×</button></div>
            <div class="scanner-viewport"><video data-scan-video playsinline muted></video><div class="scanner-frame"><span></span></div><div class="scanner-placeholder" data-scan-placeholder><x-icon name="box"/><strong>Kamera belum aktif</strong><span>Tekan “Aktifkan Kamera” untuk mulai memindai.</span></div></div>
            <div class="scanner-status" data-scan-status role="status">Siap menerima kode.</div>
            <label>Scanner fisik atau input manual<div class="scan-input-row"><input type="text" data-scan-manual autocomplete="off" inputmode="text" placeholder="Scan atau ketik SKU/barcode lalu Enter"><button type="button" data-scan-submit>Gunakan</button></div><small>Scanner USB/Bluetooth biasanya mengirim Enter secara otomatis.</small></label>
            <div class="drawer-actions"><button type="button" class="outline" data-scan-close>Batal</button><button type="button" class="secondary" data-scan-camera>Aktifkan Kamera</button></div>
        </section>
    </div>
</div>
@else @yield('content') @endauth
</body>
</html>
