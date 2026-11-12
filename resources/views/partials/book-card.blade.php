<article class="book-card">
    <a class="cover-link" href="{{ route('books.show', $book) }}">
        <div class="book-cover"><img src="{{ $book->cover }}" alt="Sampul {{ $book->title }}" loading="lazy"><span class="cover-badge">{{ $book->ebook_path ? 'e-book' : ($book->popular ? 'pilihan pembaca' : 'tersedia di rak') }}</span></div>
    </a>
    <div class="book-info">
        <span class="book-genre">{{ $book->genre }}</span>
        <h3><a href="{{ route('books.show', $book) }}">{{ $book->title }}</a></h3>
        <p>{{ $book->author }}</p>
        <div class="book-meta"><span>◷ {{ $book->published_year }}</span><span class="{{ $book->availableCopies() ? 'available' : 'unavailable' }}">● {{ $book->availableCopies() ? $book->availableCopies().' tersedia' : 'Sedang dipinjam' }}</span></div>
    </div>
</article>
