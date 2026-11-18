@extends('layouts.app')
@section('title', $book->title)
@section('content')
<section class="shell detail-page">
    <a class="back-link" href="{{ route('catalog') }}">← Kembali ke katalog</a>
    <div class="detail-grid">
        <div class="detail-cover-wrap"><div class="detail-cover"><img src="{{ $book->cover }}" alt="Sampul {{ $book->title }}"></div><span class="cover-caption">SEBUAH CERITA UNTUKMU</span></div>
        <div class="detail-copy">
            <span class="eyebrow">{{ strtoupper($book->genre) }} <span class="detail-divider">·</span> {{ $book->published_year }}</span>
            <h1>{{ $book->title }}</h1><p class="detail-author">oleh <b>{{ $book->author }}</b></p>
            <div class="availability-panel"><span class="availability-dot {{ $book->availableCopies() ? '' : 'busy' }}"></span><div><b>{{ $book->availableCopies() ? 'Ada di rak, siap menemanimu' : 'Sedang dibaca teman lain' }}</b><span>{{ $book->availableCopies() }} dari {{ $book->stock }} eksemplar tersedia <i>·</i> {{ $book->shelf }}</span></div><span class="dewey">DDC {{ $book->dewey_code }}</span></div>
            <div class="detail-actions">
                @auth
                    @if (auth()->user()->member && auth()->user()->member->status !== 'blocked')
                        <button class="button button-dark" type="button" data-open-modal="borrow-modal" @disabled(!$book->availableCopies())>Pinjam cerita ini <span>↗</span></button>
                        @if (! $book->availableCopies())
                            <form action="{{ route('reservations.store', $book) }}" method="POST">@csrf<button class="button button-outline" type="submit">Antre reservasi <span>↗</span></button></form>
                        @endif
                        <form action="{{ route('books.wishlist', $book) }}" method="POST">@csrf<button class="button button-outline" type="submit">♡ Simpan ke daftar</button></form>
                    @elseif (auth()->user()->member?->status === 'blocked')
                        <span class="member-pending-note">Akses akun anggota sedang diblokir.</span>
                    @endif
                    @if ($book->ebook_path && auth()->user()->member && auth()->user()->member->status !== 'blocked')
                        @if (auth()->user()->currentMembership()?->plan === 'premium')
                            <a class="button button-quiet" href="{{ route('books.ebook', $book) }}">▣ Baca e-book</a>
                        @else
                            <a class="button button-quiet" href="{{ route('membership.index') }}">▣ Baca e-book Premium</a>
                        @endif
                    @endif
                @else
                    <a class="button button-dark" href="{{ route('login') }}">Masuk untuk meminjam <span>↗</span></a>
                    <a class="button button-outline" href="{{ route('register') }}">Daftar anggota</a>
                    @if ($book->ebook_path)<a class="button button-quiet" href="{{ route('login') }}">Masuk untuk membaca e-book</a>@endif
                @endauth
            </div>
            <div class="detail-tabs" role="tablist"><button class="tab active" type="button" data-tab="synopsis">Sinopsis</button><button class="tab" type="button" data-tab="specification">Spesifikasi</button><button class="tab" type="button" data-tab="reviews">Ulasan ({{ $approvedReviews->count() }})</button></div>
            <div class="tab-panel active" data-panel="synopsis"><p>{{ $book->synopsis ?: 'Sebuah cerita menarik yang menunggu untuk ditemukan. Pinjam dan mulai petualangan membacamu.' }}</p></div>
            <div class="tab-panel specification-panel" data-panel="specification"><div><span>PENERBIT</span><b>{{ $book->publisher ?: '—' }}</b></div><div><span>ISBN</span><b>{{ $book->isbn ?: '—' }}</b></div><div><span>BAHASA</span><b>{{ $book->language }}</b></div><div><span>TAHUN TERBIT</span><b>{{ $book->published_year }}</b></div><div><span>KLASIFIKASI DEWEY</span><b>{{ $book->dewey_code ?: '—' }}</b></div><div><span>LOKASI RAK</span><b>{{ $book->shelf ?: '—' }}</b></div></div>
            <div class="tab-panel reviews-panel" data-panel="reviews">
                @forelse ($approvedReviews as $review)
                    <article class="published-review">
                        <header><b>{{ $review->user->name }}</b><span>{{ ucfirst($review->mood) }}</span></header>
                        @if ($review->is_spoiler)
                            <details class="spoiler-review"><summary>Spoiler alert · buka jika siap</summary><p>{{ $review->content }}</p></details>
                        @else
                            <p>{{ $review->content }}</p>
                        @endif
                    </article>
                @empty
                    <p class="review-empty">Belum ada ulasan. Jadilah pembaca pertama yang membagikan kesan setelah membacanya. ✦</p>
                @endforelse
            </div>
        </div>
    </div>
</section>

@auth
    @if (auth()->user()->member && auth()->user()->member->status !== 'blocked')
        <div class="modal-backdrop" id="borrow-modal" hidden><div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="borrow-title"><button class="modal-close" type="button" aria-label="Tutup" data-close-modal>×</button><span class="eyebrow">MULAI PETUALANGAN BARU</span><h2 id="borrow-title">Bawa pulang ceritanya.</h2><p class="modal-intro">Masa pinjam mengikuti paket aktifmu. Kamu bisa memperpanjang satu kali dari ruang anggota.</p><form action="{{ route('books.borrow', $book) }}" method="POST" class="modal-form">@csrf<label>Anggota<input value="{{ auth()->user()->member->name }} · {{ auth()->user()->member->member_number }}" disabled></label><label>Cara menerima buku<select name="method" required><option value="pickup">Ambil langsung di perpustakaan</option><option value="onsite">Baca di ruang baca</option></select></label><button class="button button-dark button-wide" type="submit">Konfirmasi peminjaman <span>↗</span></button></form></div></div>
    @endif
@endauth
@endsection
