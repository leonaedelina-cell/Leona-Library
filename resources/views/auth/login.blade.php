@extends('layouts.app')
@section('title', 'Masuk')
@section('content')
<section class="auth-page shell">
    <div class="auth-art"><span>✳</span><b>TAKE A<br>BREATH.<br>FIND A<br>STORY.</b><small>RUANG BACA · BANDUNG</small></div>
    <div class="auth-copy">
        <span class="eyebrow">SELAMAT DATANG KEMBALI</span>
        <h1>Ruang ceritamu<br><em>menunggu.</em></h1>
        <p>Masuk untuk melihat buku-buku yang sedang menemanimu.</p>
        <form action="{{ route('login.store') }}" method="POST" class="modal-form">
            @csrf
            <label>Email<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus placeholder="nama@email.com"></label>
            <label>Password<input type="password" name="password" autocomplete="current-password" required placeholder="Password akunmu"></label>
            <label class="auth-checkbox"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
            <button class="button button-dark button-wide" type="submit">Masuk ke ruang saya <span>↗</span></button>
        </form>
        <a class="auth-forgot" href="{{ route('password.request') }}">Lupa password?</a>
        <p class="auth-switch">Belum menjadi anggota? <a href="{{ route('register') }}">Daftar gratis ↗</a></p>
    </div>
</section>
@endsection
