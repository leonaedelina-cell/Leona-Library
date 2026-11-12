@extends('layouts.app')
@section('title', 'Ruang bacaku')
@section('content')
<section class="dashboard-hero"><div class="shell dashboard-welcome"><div><span class="eyebrow">SELAMAT DATANG KEMBALI, {{ strtoupper($member?->name ?? auth()->user()->name) }} ✿</span><h1>Ruang kecilmu<br>untuk <em>cerita besar.</em></h1><p>Semoga ada cerita yang membuat harimu sedikit lebih hangat.</p></div>@if ($member)<div class="member-pass"><span>RUANG BACA <i>✳</i></span><b>{{ $member->name }}</b><small>ANGGOTA SEJAK {{ $member->joined_at->translatedFormat('F Y') }}</small><div class="member-code"><strong>{{ $member->member_number }}</strong><canvas class="member-qr" data-qr="{{ $member->member_number }}" aria-label="QR kartu anggota"></canvas></div><span class="member-status {{ $member->status }}">{{ $member->status === 'blocked' ? 'Keanggotaan diblokir' : 'Anggota aktif' }}</span></div>@endif</div></section>
@if ($member && $member->status === 'blocked')<div class="shell member-notice blocked-notice">Keanggotaan sedang diblokir. Silakan hubungi petugas untuk informasi lebih lanjut.</div>@endif
<section class="shell dashboard-content">
    <div class="membership-dashboard-card">
        <div>
            <span class="eyebrow">PAKET MEMBACA</span>
            @if ($membership)
                <h2>{{ config('membership.plans.'.$membership->plan.'.name') }} <em>aktif.</em></h2>
                <p>Berlaku sampai {{ $membership->ends_at->translatedFormat('d F Y') }} · Maksimal {{ config('membership.plans.'.$membership->plan.'.loan_limit') }} buku selama {{ config('membership.plans.'.$membership->plan.'.loan_days') }} hari.</p>
                @if ($membership->billing_period === 'yearly')
                    <small>Benefit tahunan: toleransi denda 3 hari, usulan buku, dan merchandise perdana.</small>
                @endif
            @elseif ($pendingMembership)
                <h2>Pengajuan <em>menunggu.</em></h2>
                <p>Pembayaran sedang diproses. Membership akan aktif otomatis setelah Midtrans mengonfirmasi pembayaran.</p>
                @if ($pendingMembership->payment_url)<a class="text-link" href="{{ $pendingMembership->payment_url }}">Lanjutkan pembayaran ↗</a>@endif
            @else
                <h2>Terdaftar <em>gratis.</em></h2>
                <p>Pinjam sampai 3 buku bersamaan selama 14 hari. Upgrade paket untuk kuota dan benefit tambahan.</p>
            @endif
        </div>
        <a class="button button-outline" href="{{ route('membership.index') }}">Lihat paket <span>↗</span></a>
    </div>
    @if ($membership?->merchandise_status === 'pending')
        <p class="membership-notice">Merchandise tahunanmu menunggu diambil atau dikirim oleh petugas.</p>
    @elseif ($membership?->merchandise_status === 'fulfilled')
        <p class="membership-notice">Merchandise tahunan sudah diserahkan. Terima kasih sudah menjadi bagian dari Ruang Baca!</p>
    @endif
    <div class="dashboard-section-head"><div><span class="eyebrow">CERITA YANG SEDANG MENEMANIMU</span><h2>Sedang <em>dibaca.</em></h2></div><a class="text-link" href="{{ route('catalog') }}">Cari bacaan baru ↗</a></div>
    @php($activeLoans = $loans->where('status', 'active'))
    @if ($activeLoans->isNotEmpty())
        <div class="loan-list">@foreach ($activeLoans as $loan)<article class="loan-card"><img src="{{ $loan->book->cover }}" alt="Sampul {{ $loan->book->title }}"><div class="loan-info"><span class="book-genre">{{ $loan->book->genre }}</span><h3>{{ $loan->book->title }}</h3><p>{{ $loan->book->author }}</p><div class="loan-due"><span>↩ Kembalikan sebelum</span><b>{{ $loan->due_at->translatedFormat('d F Y') }}</b></div></div><div class="loan-actions"><span class="loan-status">● Sedang dipinjam</span><form action="{{ route('loans.renew', $loan) }}" method="POST">@csrf<button class="button button-outline" type="submit">Perpanjang 7 hari ↗</button></form></div></article>@endforeach</div>
    @else
        <div class="empty-state dashboard-empty"><span>✿</span><h2>Belum ada cerita yang dibawa pulang.</h2><p>Rak kami punya banyak teman baru untukmu.</p><a class="button button-dark" href="{{ route('catalog') }}">Jelajahi katalog <span>↗</span></a></div>
    @endif
    <div class="dashboard-section-head history-heading"><div><span class="eyebrow">DISIMPAN UNTUK NANTI</span><h2>Daftar <em>bacaanmu.</em></h2></div></div>
    @if ($wishlist->isNotEmpty())<div class="book-grid wishlist-grid">@foreach ($wishlist as $book) @include('partials.book-card', ['book' => $book]) @endforeach</div>@else<p class="muted-copy wishlist-empty">Buku yang kamu simpan akan tinggal di sini. <a href="{{ route('catalog') }}">Mari lihat raknya ↗</a></p>@endif
    <div class="dashboard-section-head history-heading"><div><span class="eyebrow">USULAN UNTUK PERPUSTAKAAN</span><h2>Buku yang ingin <em>kamu baca.</em></h2></div></div>
    @if ($member && $member->status !== 'blocked' && $membership?->billing_period === 'yearly')
        <form action="{{ route('book-requests.store') }}" method="POST" class="book-request-form">
            @csrf
            <label>Judul buku<input name="title" required maxlength="255" placeholder="Judul yang kamu cari"></label>
            <label>Penulis (opsional)<input name="author" maxlength="180" placeholder="Nama penulis"></label>
            <label>Catatan (opsional)<input name="notes" maxlength="1000" placeholder="Alasan atau detail edisi"></label>
            <button class="button button-dark" type="submit">Kirim usulan ↗</button>
        </form>
    @elseif ($member && $member->status !== 'blocked')
        <p class="muted-copy">Fitur usulan buku tersedia untuk member dengan paket tahunan. <a href="{{ route('membership.index') }}">Lihat paket membership ↗</a></p>
    @endif
    @if ($bookRequests->isNotEmpty())
        <div class="member-request-list">
            @foreach ($bookRequests as $bookRequest)
                <div><b>{{ $bookRequest->title }}</b><span>{{ $bookRequest->author ?: 'Penulis belum diisi' }}</span><small>{{ ucfirst($bookRequest->status) }}{{ $bookRequest->admin_note ? ' · '.$bookRequest->admin_note : '' }}</small></div>
            @endforeach
        </div>
    @endif
    @if ($reservations->isNotEmpty())
        <div class="dashboard-section-head history-heading"><div><span class="eyebrow">ANTREAN BUKU</span><h2>Reservasi <em>kamu.</em></h2></div></div>
        <div class="member-request-list">
            @foreach ($reservations as $reservation)
                <div><b>{{ $reservation->book->title }}</b><span>{{ $reservation->status === 'ready' ? 'Giliranmu sudah tiba.' : 'Menunggu eksemplar tersedia.' }} <a href="{{ route('books.show', $reservation->book) }}">Lihat buku ↗</a></span><small>{{ $reservation->priority ? 'Prioritas Premium' : 'Antrean reguler' }}</small></div>
            @endforeach
        </div>
    @endif
    <div class="dashboard-section-head history-heading"><div><span class="eyebrow">JEJAK CERITA</span><h2>Riwayat <em>pinjaman.</em></h2></div></div>
    @php($pastLoans = $loans->where('status', 'returned'))
    @if ($pastLoans->isNotEmpty())<div class="history-list">@foreach ($pastLoans as $loan)<div><span>✓</span><b>{{ $loan->book->title }}</b><span>{{ $loan->created_at->translatedFormat('d M Y') }}</span><small>Selesai dibaca</small></div>@endforeach</div>@else<p class="muted-copy">Cerita yang telah kamu kembalikan akan muncul di sini.</p>@endif
</section>
@endsection
