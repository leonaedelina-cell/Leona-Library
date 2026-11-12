<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8f6f1">
    <title>@yield('title', 'Ruang Baca') · Ruang Baca</title>
    <meta name="description" content="Perpustakaan Ruang Baca — temukan cerita dan ruang untuk bertumbuh.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/library.css') }}">
    @stack('head')
</head>
<body>
    <header class="site-header">
        <div class="shell nav-wrap">
            <a class="brand" href="{{ route('home') }}"><span class="brand-mark">r.</span><span>ruang<span class="brand-light">baca</span><small>PERPUSTAKAAN KOTA</small></span></a>
            <button class="menu-toggle" type="button" aria-label="Buka menu" aria-expanded="false">☰</button>
            <nav class="main-nav" aria-label="Navigasi utama">
                <a class="{{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Beranda</a>
                <a class="{{ request()->routeIs('catalog', 'books.show') ? 'active' : '' }}" href="{{ route('catalog') }}">Jelajahi buku</a>
                <a class="{{ request()->routeIs('services') ? 'active' : '' }}" href="{{ route('services') }}">Tentang kami</a>
                <a class="{{ request()->routeIs('membership.*') ? 'active' : '' }}" href="{{ route('membership.index') }}">Membership</a>
                <a class="{{ request()->routeIs('register') ? 'active' : '' }}" href="{{ route('register') }}">Gabung</a>
            </nav>
            @auth
                <div class="account-nav">
                    <a class="nav-cta" href="{{ route('profile.edit') }}">Profil <span>↗</span></a>
                    @php($activeMembership = auth()->user()->currentMembership())
                    @if ($activeMembership)
                        <a class="membership-tier-badge membership-tier-{{ $activeMembership->plan }}" href="{{ route('membership.index') }}" data-membership-tier="{{ $activeMembership->plan }}" aria-label="Membership {{ $activeMembership->plan }} aktif" title="Membership {{ $activeMembership->plan }} aktif">✦ {{ ucfirst($activeMembership->plan) }}</a>
                    @endif
                    @if (auth()->user()->isAdmin())
                        <a class="nav-cta admin-nav-link" href="{{ route('admin.index') }}">Admin</a>
                    @endif
                    <form action="{{ route('logout') }}" method="POST">@csrf<button class="nav-logout" type="submit">Keluar</button></form>
                </div>
            @else
                <a class="nav-cta" href="{{ route('login') }}">Masuk <span>↗</span></a>
            @endauth
        </div>
    </header>

    @if (session('success'))
        <div class="flash success shell" role="status">{{ session('success') }}</div>
    @endif
    @if (session('status'))
        <div class="flash success shell" role="status">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="flash error shell" role="alert">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="flash error shell" role="alert">{{ $errors->first() }}</div>
    @endif

    <main>@yield('content')</main>

    <footer class="footer">
        <div class="shell footer-main">
            <div><a class="brand footer-brand" href="{{ route('home') }}"><span class="brand-mark">r.</span><span>ruang<span class="brand-light">baca</span><small>PERPUSTAKAAN KOTA</small></span></a><p>Tempat cerita bertemu, dan jeda menemukan maknanya.</p></div>
            <div><strong>Jelajahi</strong><a href="{{ route('catalog') }}">Katalog buku</a><a href="{{ route('services') }}">Layanan</a><a href="{{ route('dashboard') }}">Ruang saya</a><a href="{{ route('register') }}">Daftar anggota</a></div>
            <div><strong>Temukan kami</strong><span>Jl. Merdeka No. 28, Bandung</span><span>Selasa–Minggu, 09.00–18.00</span><a href="mailto:sapa@ruangbaca.id">sapa@ruangbaca.id</a></div>
        </div>
        <div class="shell footer-bottom"><span>© {{ date('Y') }} Ruang Baca. Tumbuh bersama cerita.</span>@auth @if (auth()->user()->isAdmin())<a href="{{ route('admin.index') }}">Panel pengelola ↗</a>@endif @else<a href="{{ route('login') }}">Masuk anggota ↗</a>@endauth</div>
    </footer>
    @vite('resources/js/app.js')
    @stack('scripts')
</body>
</html>
