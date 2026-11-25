<article class="book-card">
    <a class="cover-link" href="{{ route('books.show', $book) }}">
        <div class="book-cover"><img src="{{ $book->cover }}" alt="Sampul {{ $book->title }}" loading="lazy"><span class="cover-badge">{{ $book->ebook_path ? 'e-book' : ($book->popular ? 'pilihan pembaca' : 'tersedia di rak') }}</span></div>
    </a>
    <div class="book-info">
        <span class="book-genre">{{ $book->genre }}</span>
        <h3><a href="{{ route('books.show', $book) }}">{{ $book->title }}</a></h3>
        <p>{{ $book->author }}</p>
        <div class="book-stats" aria-label="Statistik buku">
            <span class="book-stat book-stat-views" aria-label="Dilihat oleh {{ number_format($book->views_count, 0, ',', '.') }} orang" title="Dilihat oleh {{ number_format($book->views_count, 0, ',', '.') }} orang">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.25 12s3.5-6.75 9.75-6.75S21.75 12 21.75 12 18.25 18.75 12 18.75 2.25 12 2.25 12Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
                <span>{{ number_format($book->views_count, 0, ',', '.') }}</span>
            </span>
            <span class="book-stat book-stat-favorites" aria-label="Disimpan oleh {{ number_format($book->favorites_count, 0, ',', '.') }} anggota" title="Disimpan oleh {{ number_format($book->favorites_count, 0, ',', '.') }} anggota">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.6a5.4 5.4 0 0 0-7.6 0L12 5.8l-1.2-1.2a5.4 5.4 0 0 0-7.6 7.6l1.2 1.2L12 21l7.6-7.6 1.2-1.2a5.4 5.4 0 0 0 0-7.6Z"></path></svg>
                <span>{{ number_format($book->favorites_count, 0, ',', '.') }}</span>
            </span>
        </div>
        <time class="book-added-at" datetime="{{ $book->created_at->toDateString() }}">Dipublikasi {{ $book->created_at->format('d/m/Y') }}</time>
        <div class="book-meta"><span>◷ {{ $book->published_year }}</span><span class="{{ $book->availableCopies() ? 'available' : 'unavailable' }}">● {{ $book->availableCopies() ? $book->availableCopies().' tersedia' : 'Sedang dipinjam' }}</span></div>
    </div>
</article>
