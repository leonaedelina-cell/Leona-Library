@extends('layouts.reader')
@section('title', 'Membaca '.$book->title)
@section('content')
<main class="ebook-reader" data-pdf-reader data-pdf-url="{{ $pdfUrl }}" data-book-id="{{ $book->id }}">
    <header class="ebook-reader-header">
        <a class="reader-back-link" href="{{ route('books.show', $book) }}" aria-label="Kembali ke detail buku">← <span>Detail buku</span></a>
        <div class="reader-book-title">
            <span>SEDANG MEMBACA</span>
            <h1>{{ $book->title }}</h1>
            <p>{{ $book->author }}</p>
        </div>
        <span class="reader-premium-badge">✦ Premium</span>
    </header>

    <section class="reader-stage" data-reader-stage tabindex="0" aria-label="Halaman e-book; gunakan tombol panah kiri dan kanan untuk berpindah halaman">
        <div class="reader-loading" data-reader-loading role="status">
            <span class="reader-spinner" aria-hidden="true"></span>
            <p>Menyiapkan ceritamu...</p>
        </div>
        <div class="reader-error" data-reader-error role="alert" hidden>
            Buku belum dapat dimuat. Muat ulang halaman atau hubungi pengelola perpustakaan.
        </div>
        <div class="reader-page-surface" data-reader-surface hidden>
            <canvas class="reader-canvas" data-reader-canvas aria-label="Halaman PDF"></canvas>
            <span class="reader-watermark" aria-hidden="true">{{ $watermark }} · RUANG BACA</span>
        </div>
    </section>

    <nav class="reader-controls" aria-label="Kontrol pembaca e-book">
        <button class="reader-page-button" type="button" data-reader-prev disabled aria-label="Halaman sebelumnya">← <span>Sebelumnya</span></button>
        <div class="reader-page-status" aria-live="polite">
            <span>HALAMAN</span>
            <strong><span data-reader-current>1</span><i>/</i><span data-reader-total>--</span></strong>
        </div>
        <button class="reader-page-button" type="button" data-reader-next disabled aria-label="Halaman berikutnya"><span>Berikutnya</span> →</button>
        <div class="reader-progress" role="progressbar" aria-label="Progres membaca" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-reader-progress>
            <span data-reader-progress-bar></span>
        </div>
        <div class="reader-tools">
            <div class="reader-zoom" aria-label="Ukuran halaman">
                <button type="button" data-reader-zoom-out aria-label="Perkecil halaman">−</button>
                <output data-reader-zoom>100%</output>
                <button type="button" data-reader-zoom-in aria-label="Perbesar halaman">+</button>
            </div>
            <div class="reader-themes" aria-label="Tema pembaca">
                <button class="active" type="button" data-reader-theme="paper" aria-label="Tema kertas" aria-pressed="true">Aa</button>
                <button type="button" data-reader-theme="light" aria-label="Tema terang" aria-pressed="false">☼</button>
                <button type="button" data-reader-theme="dark" aria-label="Tema gelap" aria-pressed="false">◐</button>
            </div>
        </div>
    </nav>

    <p class="reader-protection-note">Reader tidak menyediakan unduh atau cetak langsung. Screenshot dan perekaman layar tidak dapat dicegah sepenuhnya oleh aplikasi web.</p>
</main>
@endsection
