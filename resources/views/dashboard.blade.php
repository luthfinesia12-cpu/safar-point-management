@extends('layouts.app', ['title' => 'Dashboard'])
@section('content')
<div class="page-header">
    <div><div class="breadcrumb">Beranda / Dashboard</div><h1>Dashboard</h1><p>Ringkasan aktivitas operasional Safar Point hari ini.</p></div>
    <div class="actions">@can('audit.view')<a class="button outline" href="{{ route('audit.index') }}"><x-icon name="file"/> Lihat Aktivitas</a>@endcan @can('purchase-requests.create')<a class="button" href="{{ route('purchase-requests.index') }}"><x-icon name="plus"/> Buat PR</a>@endcan</div>
</div>
<div class="metrics-grid">
@php($cards = [
 ['submitted_requests','PR Menunggu Approval','file','gold','Perlu keputusan Owner/Direksi'],
 ['open_orders','PO Aktif','cart','green','Pesanan sedang berjalan'],
 ['pending_payments','Menunggu Verifikasi','card','blue','Pembayaran perlu diperiksa'],
 ['stock_movements','Mutasi Stok Hari Ini','box','','Pergerakan stok tercatat'],
])
@foreach($cards as [$key,$label,$icon,$tone,$help])
<section class="metric-card"><div class="metric-icon {{ $tone }}"><x-icon :name="$icon"/></div><div><div class="metric-label">{{ $label }}</div><div class="metric-value">{{ $metrics[$key] }}</div><div class="metric-help">{{ $help }}</div></div></section>
@endforeach
</div>
<div class="grid">
    <section class="panel"><div class="panel-header"><div><h2>Selamat datang, {{ auth()->user()->name }}</h2><p>Semoga aktivitas hari ini berjalan lancar.</p></div><div class="user-avatar">{{ strtoupper(substr(auth()->user()->name,0,2)) }}</div></div><p>Anda masuk sebagai <strong>{{ auth()->user()->roles->pluck('name')->join(', ') ?: 'Pengguna' }}</strong>. Seluruh aktivitas penting Anda tercatat otomatis pada Audit Aktivitas.</p></section>
    <section class="panel"><div class="panel-header"><div><h2>Profil Perusahaan</h2><p>Identitas aktif pada dokumen dan laporan.</p></div>@if($settings?->logo_path)<img src="{{ asset('storage/'.$settings->logo_path) }}" alt="Logo {{ $settings->name }}" class="user-avatar">@endif</div><p><strong>{{ $settings?->name ?? 'Belum diatur' }}</strong></p>@if($settings?->address)<small>{{ $settings->address }}</small>@endif @can('settings.manage')<div style="margin-top:14px"><a href="{{ route('settings.company') }}">Kelola profil →</a></div>@endcan</section>
</div>
<section class="panel"><div class="panel-header"><div><h2>Aktivitas Terbaru</h2><p>Jejak aktivitas terbaru di dalam sistem.</p></div>@can('audit.view')<a href="{{ route('audit.index') }}">Lihat semua →</a>@endcan</div><div class="table-wrap"><table><thead><tr><th>Waktu</th><th>Aksi</th><th>Deskripsi</th></tr></thead><tbody>@forelse($recentAudits as $log)<tr><td>{{ $log->created_at->format('d/m/Y H:i') }}</td><td><span class="status-badge info">{{ str_replace('_',' ',$log->action) }}</span></td><td>{{ $log->description }}</td></tr>@empty<tr><td colspan="3"><div class="empty-state"><h3>Belum ada aktivitas</h3><p>Aktivitas pengguna akan muncul di sini.</p></div></td></tr>@endforelse</tbody></table></div></section>
@endsection
