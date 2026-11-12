@extends('layouts.app')
@section('title', 'Pulang ke cerita yang kamu suka')
@section('content')
<section class="hero shell">
    <div class="hero-copy">
        <span class="eyebrow"><span class="eyebrow-dot"></span> TEMPAT KECIL UNTUK JEDA SEJENAK</span>
        <h1>Setiap halaman,<br>membawa <em>kemungkinan.</em></h1>
        <p>Temukan kisah yang sudah lama kamu cari, atau cerita baru yang tak pernah kamu duga. Ada ruang untuk semuanya di sini.</p>
        <form class="hero-search" action="{{ route('catalog') }}" method="GET"><span>⌕</span><input type="search" name="q" placeholder="Cari judul, penulis, atau cerita..." aria-label="Cari buku"><button type="submit">Temukan buku <span>↗</span></button></form>
        <div class="hero-social"><div class="avatar-stack"><span>R</span><span>A</span><span>D</span><span>+</span></div><span>Ruang nyaman untuk <b>2.400+</b> pembaca</span></div>
    </div>
    <div class="hero-art">
        <div class="sun-disc"></div><div class="art-caption"><span>01 / CERITA MINGGU INI</span><b>Temukan tempat<br>untuk bertumbuh.</b></div>
        <div class="hero-book book-one"><span>RUANG BACA<br><b>Small things,<br>big stories.</b></span><i>01</i></div>
        <div class="hero-book book-two"><span>THE ART OF<br><b>Slowing down</b></span><i>02</i></div>
        <div class="hero-book book-three"><span>HARI INI<br><b>kita membaca</b></span><i>03</i></div>
        <span class="art-sparkle sparkle-one">✳</span><span class="art-sparkle sparkle-two">✳</span>
        <div class="art-note"><span>✦</span> waktu terbaik<br>adalah sekarang</div>
    </div>
</section>
<div class="shell trust-strip"><span>SEDIKIT ANGKA, BANYAK CERITA</span><div><b>{{ number_format($bookCount) }}<small> buku di rak kami</small></b><b>{{ number_format($activeMemberCount) }}<small> anggota aktif</small></b><b>{{ number_format($loanCount) }}<small> cerita dipinjam</small></b></div></div>

<section class="section shell">
    <div class="section-head"><div><span class="eyebrow">MUNGKIN KAMU SUKA</span><h2>Yang sedang <em>dibicarakan.</em></h2><p>Cerita favorit yang berkali-kali dibawa pulang.</p></div><a class="text-link" href="{{ route('catalog') }}">Lihat semua buku <span>↗</span></a></div>
    <div class="book-grid">@foreach ($popularBooks as $book) @include('partials.book-card', ['book' => $book]) @endforeach</div>
</section>

<section class="reading-note"><div class="shell note-content"><span class="note-ornament">“</span><div><span class="eyebrow">PENGINGAT KECIL HARI INI</span><blockquote>“Satu buku, satu cangkir teh, dan sore yang pelan. Kadang itu saja sudah cukup.”</blockquote><span class="note-credit">— Catatan dari ruang baca</span></div><span class="note-flower">✳</span></div></section>

<section class="membership-promo shell"><div><span class="eyebrow">BACA LEBIH BANYAK</span><h2>Cerita favoritmu,<br>tanpa <em>batas ruang.</em></h2><p>Pilih Basic atau Premium untuk kuota pinjam lebih besar, baca e-book, dan benefit tahunan.</p></div><a class="button button-dark" href="{{ route('membership.index') }}">Kenali membership <span>↗</span></a></section>

<section class="section shell">
    <div class="section-head"><div><span class="eyebrow">BARU MENDARAT DI RAK</span><h2>Segar dari <em>percetakan.</em></h2><p>Teman baru untuk menemani harimu.</p></div><a class="text-link" href="{{ route('catalog') }}">Jelajahi katalog <span>↗</span></a></div>
    <div class="book-grid">@foreach ($latestBooks as $book) @include('partials.book-card', ['book' => $book]) @endforeach</div>
</section>

<section class="visit-banner shell"><div class="visit-copy"><span class="eyebrow">SUDUT FAVORITMU BERIKUTNYA</span><h2>Ada kursi kosong<br>di dekat jendela.</h2><p>Mampir untuk membaca, bekerja, atau sekadar bernapas sedikit lebih lega.</p><a class="button button-dark" href="{{ route('services') }}">Kenali ruang kami <span>↗</span></a></div><div class="visit-art"><span>☼</span><i></i><b>READ<br>GROW<br>REPEAT</b></div></section>
@endsection
