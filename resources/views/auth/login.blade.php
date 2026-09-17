@extends('layouts.app', ['title' => 'Masuk'])
@section('content')
<div class="auth-page">
    <section class="auth-showcase">
        <div class="auth-logo"><div class="brand-mark">SP</div><div><strong>SAFAR POINT</strong><span>Management System</span></div></div>
        <h1>Operasional lebih rapi, keputusan lebih cepat.</h1>
        <p>Kelola master data, purchasing, persediaan, pembayaran, dan laporan Safar Point dalam satu sistem terpadu.</p>
    </section>
    <section class="auth-card-wrap">
        <main class="auth-card">
            <div class="mobile-only auth-logo"><div class="brand-mark">SP</div><div><strong>SAFAR POINT</strong><span>Management System</span></div></div>
            <h2>Selamat datang</h2><p>Masuk menggunakan akun Safar Point Anda.</p>
            @if($errors->any())<div class="alert alert-error"><x-icon name="warning"/><span>{{ $errors->first() }}</span></div>@endif
            <form method="post" action="{{ route('login.store') }}">@csrf
                <label>Email<input type="email" name="email" value="{{ old('email') }}" placeholder="nama@safarpoint.local" required autofocus autocomplete="username"></label>
                <label>Password<input type="password" name="password" placeholder="Masukkan password" required autocomplete="current-password"></label>
                <label class="login-check"><span><input type="checkbox" name="remember"> Ingat saya di perangkat ini</span></label>
                <button>Masuk ke Sistem</button>
            </form>
            <p style="text-align:center;margin:20px 0 0;font-size:12px">Akses khusus pengguna resmi Safar Point.</p>
        </main>
    </section>
</div>
@endsection
