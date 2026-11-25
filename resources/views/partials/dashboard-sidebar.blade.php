@php
    $currentUser = auth()->user();
    $isPremiumMember = ! $isAdminDashboard && $membership?->plan === 'premium';
    $sidebarStatus = $isAdminDashboard
        ? 'Panel admin'
        : ($member?->status === 'blocked'
            ? 'Akun dibatasi'
            : ($membership
                ? ucfirst($membership->plan).' aktif'
                : ($pendingMembership ? 'Pembayaran diproses' : 'Anggota aktif')));
@endphp

<aside class="dashboard-sidebar" id="dashboard-sidebar" aria-label="{{ $isAdminDashboard ? 'Navigasi panel pengelola' : 'Navigasi dashboard anggota' }}">
    <div class="dashboard-sidebar-head">
        <a class="dashboard-sidebar-brand" href="{{ $isAdminDashboard ? route('admin.index') : route('dashboard') }}">
            <span class="dashboard-sidebar-mark">r.</span>
            <span>ruang<span>baca</span><small>{{ $isAdminDashboard ? 'PANEL PENGELOLA' : 'RUANG ANGGOTA' }}</small></span>
        </a>
        <button class="dashboard-sidebar-close" type="button" data-sidebar-close aria-label="Tutup menu dashboard">×</button>
    </div>

    <div class="dashboard-sidebar-user">
        <span class="dashboard-sidebar-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($currentUser->name, 0, 1)) }}</span>
        <span><strong>{{ $currentUser->name }}</strong><small>{{ $isAdminDashboard ? 'Pengelola' : $currentUser->email }}</small></span>
    </div>

    <div class="dashboard-sidebar-plan {{ $isAdminDashboard ? 'dashboard-sidebar-plan-admin' : ($isPremiumMember ? 'dashboard-sidebar-plan-premium' : '') }}" @if (! $isAdminDashboard && $membership) data-membership-tier="{{ $membership->plan }}" @endif>
        <span class="dashboard-sidebar-plan-mark" aria-hidden="true">{{ $isAdminDashboard ? '✳' : '✦' }}</span>
        <span>
            <small>{{ $isAdminDashboard ? 'AKSES' : 'STATUS AKUN' }}</small>
            <strong>{{ $isAdminDashboard ? 'Panel admin' : $sidebarStatus }}</strong>
        </span>
    </div>

    <span class="dashboard-sidebar-label">MENU</span>
    <nav class="dashboard-sidebar-nav">
        @if ($isAdminDashboard)
            <a class="dashboard-sidebar-link" href="#admin-overview" aria-label="Ringkasan"><span>01</span><span class="dashboard-sidebar-link-label">Ringkasan</span></a>
            <a class="dashboard-sidebar-link" href="#admin-users" aria-label="Pengguna"><span>02</span><span class="dashboard-sidebar-link-label">Pengguna</span></a>
            <a class="dashboard-sidebar-link" href="#admin-members" aria-label="Keanggotaan"><span>03</span><span class="dashboard-sidebar-link-label">Keanggotaan</span></a>
            <a class="dashboard-sidebar-link" href="#admin-memberships" aria-label="Membership"><span>04</span><span class="dashboard-sidebar-link-label">Membership</span></a>
            <a class="dashboard-sidebar-link" href="#admin-books" aria-label="Koleksi buku"><span>05</span><span class="dashboard-sidebar-link-label">Koleksi buku</span></a>
            <a class="dashboard-sidebar-link" href="#admin-loan-form" aria-label="Catat pinjaman"><span>06</span><span class="dashboard-sidebar-link-label">Catat pinjaman</span></a>
            <a class="dashboard-sidebar-link" href="#admin-guest-loan-form" aria-label="Catat pinjaman tamu"><span>07</span><span class="dashboard-sidebar-link-label">Pinjaman tamu</span></a>
            <a class="dashboard-sidebar-link" href="#admin-loans" aria-label="Aktivitas pinjaman"><span>08</span><span class="dashboard-sidebar-link-label">Aktivitas pinjaman</span></a>
            <a class="dashboard-sidebar-link" href="#admin-inbox" aria-label="Pesan masuk"><span>09</span><span class="dashboard-sidebar-link-label">Pesan masuk</span></a>
            <a class="dashboard-sidebar-link" href="#admin-reservations" aria-label="Reservasi"><span>10</span><span class="dashboard-sidebar-link-label">Reservasi</span></a>
            <a class="dashboard-sidebar-link" href="#admin-book-requests" aria-label="Usulan buku"><span>11</span><span class="dashboard-sidebar-link-label">Usulan buku</span></a>
            <a class="dashboard-sidebar-link" href="#admin-book-reviews" aria-label="Moderasi ulasan"><span>12</span><span class="dashboard-sidebar-link-label">Moderasi ulasan</span></a>
        @else
            <a class="dashboard-sidebar-link" href="#member-overview" aria-label="Ringkasan"><span>01</span><span class="dashboard-sidebar-link-label">Ringkasan</span></a>
            <a class="dashboard-sidebar-link" href="#member-membership" aria-label="Membership"><span>02</span><span class="dashboard-sidebar-link-label">Membership</span></a>
            <a class="dashboard-sidebar-link" href="#member-loans" aria-label="Sedang dibaca"><span>03</span><span class="dashboard-sidebar-link-label">Sedang dibaca</span></a>
            <a class="dashboard-sidebar-link" href="#member-wishlist" aria-label="Daftar bacaan"><span>04</span><span class="dashboard-sidebar-link-label">Daftar bacaan</span></a>
            <a class="dashboard-sidebar-link" href="#member-requests" aria-label="Usulan buku"><span>05</span><span class="dashboard-sidebar-link-label">Usulan buku</span></a>
            <a class="dashboard-sidebar-link" href="#member-history" aria-label="Riwayat pinjaman"><span>06</span><span class="dashboard-sidebar-link-label">Riwayat pinjaman</span></a>
        @endif
    </nav>

    @if (! $isAdminDashboard)
        <a class="dashboard-sidebar-membership-link" href="{{ route('membership.index') }}">
            {{ $isPremiumMember ? 'Lihat benefit Premium' : 'Jelajahi paket Premium' }} <span aria-hidden="true">↗</span>
        </a>
    @endif

    <div class="dashboard-sidebar-shortcuts">
        <a href="{{ route('catalog') }}">Jelajahi katalog <span aria-hidden="true">↗</span></a>
        <a href="{{ route('profile.edit') }}">Pengaturan profil <span aria-hidden="true">↗</span></a>
    </div>

    <form class="dashboard-sidebar-logout" action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit"><span class="dashboard-sidebar-logout-label">Keluar</span><span aria-hidden="true">↗</span></button>
    </form>
</aside>
<button class="dashboard-sidebar-backdrop" type="button" data-sidebar-close tabindex="-1" aria-label="Tutup menu dashboard"></button>
