@extends('layouts.app')
@section('title', 'Paket membership')
@section('content')
<section class="page-hero shell membership-hero">
    <span class="eyebrow">RUANG BACA, LEBIH DEKAT</span>
    <h1>Pilih ruang untuk<br><em>cerita berikutnya.</em></h1>
    <p>Membership berbayar memberi kuota pinjam lebih besar dan benefit tambahan. Semua akun anggota yang tidak diblokir dapat langsung meminjam gratis hingga 3 buku selama 14 hari.</p>
</section>

@if ($currentMembership)
    <div class="shell membership-notice">
        Paket aktif: <b>{{ $plans[$currentMembership->plan]['name'] }}</b>,
        berlaku sampai {{ $currentMembership->ends_at->translatedFormat('d F Y') }}.
    </div>
@elseif ($pendingMembership)
    <div class="shell membership-notice">
        Pembayaran {{ $plans[$pendingMembership->plan]['name'] }} sedang menunggu konfirmasi otomatis dari Midtrans.
        @if ($pendingMembership->payment_url)
            <a class="text-link" href="{{ $pendingMembership->payment_url }}">Lanjutkan pembayaran ↗</a>
        @endif
    </div>
@endif

<section class="shell membership-plans">
    @foreach ($plans as $planKey => $plan)
        <article class="membership-card {{ $planKey === 'premium' ? 'membership-card-featured' : '' }}">
            <span class="eyebrow">{{ $planKey === 'premium' ? 'UNTUK PEMBACA SETIA' : 'MULAI DARI SINI' }}</span>
            <h2>{{ $plan['name'] }}</h2>
            <p class="membership-card-intro">{{ $planKey === 'premium' ? 'Lebih banyak waktu, lebih banyak cerita.' : 'Temani hari-hari membaca dengan nyaman.' }}</p>
            <div class="membership-price">
                <b>Rp{{ number_format($plan['prices']['monthly'], 0, ',', '.') }}</b><span>/ bulan</span>
            </div>
            <div class="membership-yearly-price">
                <span>atau Rp{{ number_format($plan['prices']['yearly'], 0, ',', '.') }} / tahun</span>
            </div>
            <ul class="membership-benefits">
                <li>Maksimal {{ $plan['loan_limit'] }} buku dipinjam bersamaan</li>
                <li>Masa pinjam {{ $plan['loan_days'] }} hari per buku</li>
                <li>Perpanjangan gratis satu kali</li>
                @if ($plan['ebook_access'])
                    <li>Akses baca online PDF/e-book</li>
                    <li>Prioritas antrean reservasi buku populer</li>
                @endif
                @if (in_array($planKey, ['basic', 'premium'], true))
                    <li>Benefit paket tahunan: toleransi denda 3 hari, usulan buku, dan merchandise sederhana untuk pendaftaran tahunan pertama</li>
                @endif
            </ul>

            @auth
                @if (! $member || $member->status === 'blocked')
                    <p class="membership-action-note">Akun yang tidak terdaftar sebagai anggota atau sedang diblokir tidak dapat membeli paket.</p>
                @elseif ($currentMembership)
                    <button class="button button-outline button-wide" type="button" disabled>
                        Paket berbayar aktif
                    </button>
                @elseif ($pendingMembership)
                    @if ($pendingMembership->payment_url)
                        <a class="button button-dark button-wide" href="{{ $pendingMembership->payment_url }}">Lanjutkan pembayaran <span>↗</span></a>
                    @else
                        <button class="button button-outline button-wide" type="button" disabled>Pembayaran sedang disiapkan</button>
                    @endif
                @else
                    <div class="membership-actions">
                        <form action="{{ route('membership.store') }}" method="POST">
                            @csrf<input type="hidden" name="plan" value="{{ $planKey }}"><input type="hidden" name="billing_period" value="monthly">
                            <button class="button {{ $planKey === 'premium' ? 'button-dark' : 'button-outline' }} button-wide" type="submit">Pilih bulanan</button>
                        </form>
                        <form action="{{ route('membership.store') }}" method="POST">
                            @csrf<input type="hidden" name="plan" value="{{ $planKey }}"><input type="hidden" name="billing_period" value="yearly">
                            <button class="button button-quiet button-wide" type="submit">Pilih tahunan</button>
                        </form>
                    </div>
                @endif
            @else
                <a class="button {{ $planKey === 'premium' ? 'button-dark' : 'button-outline' }} button-wide" href="{{ route('login') }}">Masuk untuk memilih</a>
            @endauth
        </article>
    @endforeach
</section>
<section class="shell membership-payment-note">
    <span class="eyebrow">PEMBAYARAN AMAN</span>
    <p>Pembayaran diproses melalui Midtrans Snap. Pilihan yang tersedia meliputi QRIS, e-wallet, transfer bank, dan kartu kredit. Membership aktif otomatis setelah pembayaran berhasil.</p>
</section>
@auth
    @if ($memberships->isNotEmpty())
        <section class="shell membership-history">
            <div class="dashboard-section-head"><div><span class="eyebrow">RIWAYAT PAKET</span><h2>Jejak <em>membership.</em></h2></div></div>
            <div class="member-request-list">
                @foreach ($memberships as $membership)
                    @php($paymentLabel = match ($membership->payment_status) {
                        'paid' => 'Lunas',
                        'pending' => 'Menunggu pembayaran',
                        'denied' => 'Ditolak',
                        'cancelled' => 'Dibatalkan',
                        'expired' => 'Kedaluwarsa',
                        'failed' => 'Gagal',
                        'refunded' => 'Dikembalikan',
                        'partially_refunded' => 'Dikembalikan sebagian',
                        'chargeback' => 'Disengketakan',
                        default => ucfirst($membership->status),
                    })
                    <div><b>{{ $plans[$membership->plan]['name'] }} · {{ $membership->billing_period === 'yearly' ? 'Tahunan' : 'Bulanan' }}</b><span>Rp{{ number_format($membership->amount, 0, ',', '.') }}</span><small>{{ $paymentLabel }}{{ $membership->ends_at ? ' · sampai '.$membership->ends_at->translatedFormat('d M Y') : '' }}</small></div>
                @endforeach
            </div>
        </section>
    @endif
@endauth
@endsection
