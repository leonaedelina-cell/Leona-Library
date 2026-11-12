@extends('layouts.app')
@section('title', 'Jelajahi katalog')
@section('content')
<section class="page-hero shell"><span class="eyebrow">KATALOG PERPUSTAKAAN</span><h1>Temukan cerita<br>yang <em>memanggilmu.</em></h1><p>Pelan-pelan saja. Rak kami penuh dengan tempat-tempat baru untuk dikunjungi.</p></section>
<section class="shell catalog-section">
    <form class="filter-bar" action="{{ route('catalog') }}" method="GET">
        <label class="filter-search"><span>⌕</span><input type="search" name="q" value="{{ request('q') }}" placeholder="Judul buku atau nama penulis..."></label>
        <select name="genre" aria-label="Filter genre"><option value="">Semua genre</option>@foreach ($genres as $genre)<option value="{{ $genre }}" @selected(request('genre') === $genre)>{{ $genre }}</option>@endforeach</select>
        <select name="language" aria-label="Filter bahasa"><option value="">Semua bahasa</option>@foreach ($languages as $language)<option value="{{ $language }}" @selected(request('language') === $language)>{{ $language }}</option>@endforeach</select>
        <input class="year-filter" type="number" name="year" value="{{ request('year') }}" min="1000" max="2100" placeholder="Tahun" aria-label="Tahun terbit">
        <select name="availability" aria-label="Filter ketersediaan"><option value="">Semua status</option><option value="available" @selected(request('availability') === 'available')>Tersedia</option></select>
        <button class="button button-dark filter-submit" type="submit">Terapkan</button>
    </form>
    <div class="catalog-heading"><span><b>{{ $books->total() }}</b> cerita menanti</span><span>Diurutkan dari yang terbaru ↘</span></div>
    @if ($books->isNotEmpty())
        <div class="book-grid catalog-grid">@foreach ($books as $book) @include('partials.book-card', ['book' => $book]) @endforeach</div>
        <div class="pagination-wrap">{{ $books->links() }}</div>
    @else
        <div class="empty-state"><span>⌕</span><h2>Belum ketemu ceritanya.</h2><p>Coba ubah kata pencarian atau filter yang kamu pilih.</p><a class="button button-dark" href="{{ route('catalog') }}">Lihat semua buku</a></div>
    @endif
</section>
@endsection
