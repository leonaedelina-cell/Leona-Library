@extends('layouts.app')
@section('title', 'Atur ulang password')
@section('content')
<section class="auth-page shell">
    <div class="auth-art"><span>✳</span><b>A FRESH<br>START FOR<br>YOUR NEXT<br>CHAPTER.</b><small>RUANG BACA · BANDUNG</small></div>
    <div class="auth-copy">
        <span class="eyebrow">HAMPIR SELESAI</span>
        <h1>Atur password<br><em>yang baru.</em></h1>
        <form action="{{ route('password.update') }}" method="POST" class="modal-form">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label>Email akun<input type="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" required></label>
            <label>Password baru<input type="password" name="password" autocomplete="new-password" required minlength="10"></label>
            <label>Ulangi password<input type="password" name="password_confirmation" autocomplete="new-password" required minlength="10"></label>
            <button class="button button-dark button-wide" type="submit">Simpan password baru <span>↗</span></button>
        </form>
    </div>
</section>
@endsection
