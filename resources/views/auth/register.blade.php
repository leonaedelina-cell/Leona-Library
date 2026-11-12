@extends('layouts.app')
@section('title', 'Daftar anggota')
@section('content')
<section class="auth-page shell">
    <div class="auth-art registration-art"><span>✳</span><b>YOUR NEXT<br>CHAPTER<br>STARTS HERE.</b><small>RUANG BACA · BANDUNG</small></div>
    <div class="auth-copy">
        <span class="eyebrow">JADI BAGIAN DARI RUANG BACA</span>
        <h1>Satu akun,<br><em>banyak cerita.</em></h1>
        <p>Daftar untuk mendapatkan kartu anggota digital dan langsung mulai meminjam buku.</p>
        <form action="{{ route('register.store') }}" method="POST" class="modal-form">
            @csrf
            <label>Nama lengkap<input name="name" value="{{ old('name') }}" autocomplete="name" required maxlength="120" placeholder="Namamu"></label>
            <label>Alamat email<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required maxlength="255" placeholder="nama@email.com"></label>
            <label>Password<input type="password" name="password" autocomplete="new-password" required minlength="10" placeholder="Minimal 10 karakter, huruf dan angka"></label>
            <label>Ulangi password<input type="password" name="password_confirmation" autocomplete="new-password" required minlength="10" placeholder="Ketik ulang password"></label>
            <button class="button button-dark button-wide" type="submit">Daftar gratis <span>↗</span></button>
        </form>
        <p class="auth-switch">Sudah terdaftar? <a href="{{ route('login') }}">Masuk ke akun ↗</a></p>
    </div>
</section>
@endsection
