@extends('layouts.app')
@section('title', 'Lupa password')
@section('content')
<section class="auth-page shell">
    <div class="auth-art"><span>✳</span><b>EVERY STORY<br>HAS A WAY<br>BACK HOME.</b><small>RUANG BACA · BANDUNG</small></div>
    <div class="auth-copy">
        <span class="eyebrow">KAMI BANTU KAMU KEMBALI</span>
        <h1>Lupa password<br><em>tidak apa-apa.</em></h1>
        <p>Kami akan mengirim tautan untuk mengatur ulang password ke email akunmu.</p>
        <form action="{{ route('password.email') }}" method="POST" class="modal-form">
            @csrf
            <label>Email akun<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus placeholder="nama@email.com"></label>
            <button class="button button-dark button-wide" type="submit">Kirim tautan reset <span>↗</span></button>
        </form>
        <p class="auth-switch"><a href="{{ route('login') }}">← Kembali ke halaman masuk</a></p>
    </div>
</section>
@endsection
