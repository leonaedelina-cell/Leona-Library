@extends('layouts.app')
@section('title', 'Pengelola')
@section('content')
<section class="admin-page shell">
    <div class="admin-heading"><div><span class="eyebrow">RUANG PENGELOLA</span><h1>Merawat <em>cerita.</em></h1><p>Kelola koleksi, anggota, akun, dan peminjaman.</p></div><button class="button button-dark" type="button" data-new-book>+ Tambah buku</button></div>
    <div class="admin-stats">
        <div><span>KOLEKSI BUKU</span><b>{{ $books->count() }}</b><small>judul di katalog</small></div>
        <div><span>PINJAMAN AKTIF</span><b>{{ $activeLoansCount }}</b><small>perlu dipantau</small></div>
        <div><span>ANGGOTA AKTIF</span><b>{{ $members->where('status', 'active')->count() }}</b><small>{{ $members->where('status', 'blocked')->count() }} diblokir</small></div>
        <div><span>TOTAL PINJAMAN</span><b>{{ $totalLoans }}</b><small>seluruh transaksi</small></div>
    </div>

    <section class="admin-panel">
        <div class="admin-panel-head"><div><span class="eyebrow">AKUN & AKSES</span><h2>Kelola <em>pengguna.</em></h2></div><button class="button button-outline" type="button" data-new-user>+ Buat akun</button></div>
        <div class="table-wrap"><table class="admin-table">
            <thead><tr><th>NAMA & EMAIL</th><th>NOMOR ANGGOTA</th><th>PERAN</th><th>KEANGGOTAAN</th><th>AKSI</th></tr></thead>
            <tbody>
                @forelse ($users as $user)
                    @php($memberStatus = $user->member?->status ?? '—')
                    <tr>
                        <td><b>{{ $user->name }}</b><small class="table-sub">{{ $user->email }}</small></td>
                        <td>{{ $user->member?->member_number ?? '—' }}</td>
                        <td>{{ $user->role === 'admin' ? 'Admin' : 'Anggota' }}</td>
                        <td>{{ $memberStatus === 'active' ? 'Aktif' : ($memberStatus === 'blocked' ? 'Diblokir' : '—') }}</td>
                        <td class="table-actions">
                            <button type="button" data-edit-user="{{ json_encode(['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role]) }}">Edit</button>
                            @if ($user->id !== auth()->id())
                                <form action="{{ route('admin.users.delete', $user) }}" method="POST" onsubmit="return confirm('Hapus akun {{ $user->name }}?')">@csrf @method('DELETE')<button class="delete-link" type="submit">Hapus</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="table-empty">Belum ada akun.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>

    <section class="admin-panel">
        <div class="admin-panel-head"><div><span class="eyebrow">STATUS AKSES</span><h2>Keanggotaan <em>perpustakaan.</em></h2></div><span>{{ $members->count() }} anggota</span></div>
        <div class="table-wrap"><table class="admin-table">
            <thead><tr><th>NAMA & NOMOR ANGGOTA</th><th>EMAIL</th><th>BERGABUNG</th><th>STATUS</th><th>AKSI</th></tr></thead>
            <tbody>
                @forelse ($members as $member)
                    <tr>
                        <td><b>{{ $member->name }}</b><small class="table-sub">{{ $member->member_number }}</small></td><td>{{ $member->email }}</td><td>{{ $member->joined_at->translatedFormat('d M Y') }}</td>
                        <td><span class="status-pill {{ $member->status }}">{{ $member->status === 'blocked' ? 'Diblokir' : 'Aktif' }}</span></td>
                        <td class="table-actions">
                            @if ($member->status === 'blocked')
                                <form action="{{ route('admin.members.status', $member) }}" method="POST">@csrf<input type="hidden" name="status" value="active"><button class="table-link" type="submit">Buka blokir</button></form>
                            @else
                                <form action="{{ route('admin.members.status', $member) }}" method="POST">@csrf<input type="hidden" name="status" value="blocked"><button class="delete-link" type="submit">Blokir</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="table-empty">Belum ada anggota terdaftar.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>

    <section class="admin-panel">
        <div class="admin-panel-head"><div><span class="eyebrow">PEMBAYARAN OTOMATIS</span><h2>Transaksi <em>membership.</em></h2></div><span>{{ $membershipRequests->count() }} menunggu</span></div>
        <div class="table-wrap"><table class="admin-table">
            <thead><tr><th>ANGGOTA</th><th>PAKET</th><th>PERIODE</th><th>TAGIHAN</th><th>ORDER ID</th><th>STATUS</th></tr></thead>
            <tbody>
                @forelse ($membershipRequests as $membership)
                    <tr>
                        <td><b>{{ $membership->user->name }}</b><small class="table-sub">{{ $membership->user->email }}</small></td>
                        <td>{{ config('membership.plans.'.$membership->plan.'.name') }}</td>
                        <td>{{ $membership->billing_period === 'yearly' ? 'Tahunan' : 'Bulanan' }}</td>
                        <td>Rp{{ number_format($membership->amount, 0, ',', '.') }}</td>
                        <td>{{ $membership->payment_order_id }}</td>
                        <td>{{ $membership->payment_url ? 'Menunggu pembayaran' : 'Menyiapkan pembayaran' }} · {{ $membership->created_at->translatedFormat('d M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-empty">Belum ada pengajuan membership.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>

    @if ($merchandiseRequests->isNotEmpty())
        <section class="admin-panel">
            <div class="admin-panel-head"><div><span class="eyebrow">BENEFIT TAHUNAN</span><h2>Penyerahan <em>merchandise.</em></h2></div></div>
            <div class="table-wrap"><table class="admin-table">
                <thead><tr><th>ANGGOTA</th><th>PAKET</th><th>AKTIF SEJAK</th><th>AKSI</th></tr></thead>
                <tbody>
                    @foreach ($merchandiseRequests as $membership)
                        <tr>
                            <td><b>{{ $membership->user->name }}</b><small class="table-sub">{{ $membership->user->email }}</small></td>
                            <td>{{ config('membership.plans.'.$membership->plan.'.name') }} tahunan</td>
                            <td>{{ $membership->starts_at?->translatedFormat('d M Y') }}</td>
                            <td><form action="{{ route('admin.memberships.merchandise', $membership) }}" method="POST">@csrf<button class="table-link" type="submit">Tandai diserahkan</button></form></td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
        </section>
    @endif

    <section class="admin-panel">
        <div class="admin-panel-head"><div><span class="eyebrow">KOLEKSI PERPUSTAKAAN</span><h2>Semua <em>buku.</em></h2></div><span>{{ $books->count() }} koleksi</span></div>
        <div class="table-wrap"><table class="admin-table">
            <thead><tr><th>JUDUL & PENULIS</th><th>GENRE</th><th>TAHUN</th><th>STOK</th><th>LOKASI</th><th>E-BOOK</th><th>AKSI</th></tr></thead>
            <tbody>
                @foreach ($books as $book)
                    <tr>
                        <td class="table-book"><img src="{{ $book->cover }}" alt=""><span><b>{{ $book->title }}</b><small>{{ $book->author }}</small></span></td>
                        <td>{{ $book->genre }}</td><td>{{ $book->published_year }}</td><td>{{ $book->availableCopies() }}/{{ $book->stock }}</td><td>{{ $book->shelf }}</td><td>{{ $book->ebook_path ? 'PDF aktif' : '—' }}</td>
                        <td class="table-actions">
                            <button type="button" data-edit-book="{{ json_encode([...$book->only(['id', 'title', 'author', 'publisher', 'isbn', 'genre', 'language', 'published_year', 'synopsis', 'cover', 'shelf', 'dewey_code', 'stock', 'popular']), 'has_ebook' => filled($book->ebook_path)]) }}">Edit</button>
                            <form action="{{ route('admin.books.delete', $book) }}" method="POST" onsubmit="return confirm('Hapus buku ini dari katalog?')">@csrf @method('DELETE')<button class="delete-link" type="submit">Hapus</button></form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table></div>
    </section>

    <section class="admin-panel">
        <div class="admin-panel-head"><div><span class="eyebrow">PINDAI KARTU ANGGOTA</span><h2>Catat <em>peminjaman.</em></h2></div></div>
        <form action="{{ route('admin.loans.store') }}" method="POST" class="admin-loan-form" data-scanner-form>
            @csrf
            <label>Nomor anggota / QR
                <div class="scanner-input-row"><input name="member_number" data-member-number required autocomplete="off" placeholder="Pindai QR atau isi nomor anggota"><button class="button button-outline" type="button" data-start-scanner>▦ Pindai QR</button></div>
            </label>
            <div class="scanner-panel" data-scanner-panel hidden><video data-scanner-video autoplay muted playsinline></video><p data-scanner-status>Arahkan QR kartu anggota ke kamera.</p><button class="button button-outline" type="button" data-stop-scanner>Hentikan kamera</button></div>
            <label>Buku<select name="book_id" required><option value="">Pilih buku</option>            @foreach ($books as $book)<option value="{{ $book->id }}" @disabled($book->availableCopies() < 1)>{{ $book->title }} — {{ $book->availableCopies() }} tersedia</option>@endforeach</select></label>
            <label>Metode<select name="method" required><option value="pickup">Ambil langsung</option><option value="onsite">Baca di ruang baca</option></select></label>
            <button class="button button-dark" type="submit">Simpan peminjaman</button>
        </form>
    </section>

    <section class="admin-panel">
        <div class="admin-panel-head"><div><span class="eyebrow">AKTIVITAS TERKINI</span><h2>Pinjam & <em>kembali.</em></h2></div></div>
        <div class="table-wrap"><table class="admin-table">
            <thead><tr><th>ANGGOTA</th><th>BUKU</th><th>TENGGAT</th><th>STATUS</th><th>DENDA</th><th>AKSI</th></tr></thead>
            <tbody>
                @forelse ($loans as $loan)
                    <tr>
                        <td><b>{{ $loan->member_name }}</b><small class="table-sub">{{ $loan->member_number }}</small></td><td>{{ $loan->book->title }}</td><td>{{ $loan->due_at->translatedFormat('d M Y') }}</td>
                        <td><span class="loan-status">{{ $loan->status === 'active' ? '● Dipinjam' : '✓ Kembali' }}</span></td>
                        <td>Rp{{ number_format($loan->status === 'returned' ? $loan->fine : $loan->estimatedFine(), 0, ',', '.') }}</td>
                        <td>
                            @if ($loan->status === 'active')
                                <form action="{{ route('admin.loans.return', $loan) }}" method="POST">@csrf<button class="table-link" type="submit">Catat kembali ↗</button></form>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-empty">Belum ada transaksi peminjaman.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>

    <section class="admin-panel">
        <div class="admin-panel-head"><div><span class="eyebrow">MASUK DARI PENGUNJUNG</span><h2>Pesan & <em>usulan buku.</em></h2></div></div>
        <div class="table-wrap"><table class="admin-table">
            <thead><tr><th>NAMA</th><th>EMAIL</th><th>PESAN</th><th>DITERIMA</th></tr></thead>
            <tbody>
                @forelse ($messages as $message)
                    <tr><td>{{ $message->name }}</td><td>{{ $message->email }}</td><td>{{ $message->message }}</td><td>{{ $message->created_at->translatedFormat('d M Y') }}</td></tr>
                @empty
                    <tr><td colspan="4" class="table-empty">Belum ada pesan masuk.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>

    <section class="admin-panel">
        <div class="admin-panel-head"><div><span class="eyebrow">ANTREAN PRIORITAS</span><h2>Reservasi <em>buku.</em></h2></div><span>{{ $reservations->count() }} antrean</span></div>
        <div class="table-wrap"><table class="admin-table">
            <thead><tr><th>URUTAN</th><th>BUKU</th><th>ANGGOTA</th><th>STATUS</th><th>AKSI</th></tr></thead>
            <tbody>
                @forelse ($reservations as $reservation)
                    <tr>
                        <td>{{ $loop->iteration }}{{ $reservation->priority ? ' · Premium' : '' }}</td>
                        <td>{{ $reservation->book->title }}</td>
                        <td><b>{{ $reservation->user->name }}</b><small class="table-sub">{{ $reservation->user->email }}</small></td>
                        <td>{{ $reservation->status === 'ready' ? 'Giliran anggota' : 'Menunggu buku' }}</td>
                        <td class="table-actions">
                            @if ($reservation->status === 'ready')
                                <form action="{{ route('admin.reservations.update', $reservation) }}" method="POST">@csrf @method('PUT')<input type="hidden" name="status" value="fulfilled"><button class="table-link" type="submit">Selesai dilayani</button></form>
                            @endif
                            <form action="{{ route('admin.reservations.update', $reservation) }}" method="POST">@csrf @method('PUT')<input type="hidden" name="status" value="cancelled"><button class="delete-link" type="submit">Batalkan</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="table-empty">Belum ada antrean reservasi.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>

    <section class="admin-panel">
        <div class="admin-panel-head"><div><span class="eyebrow">USULAN ANGGOTA</span><h2>Permintaan <em>buku baru.</em></h2></div><span>{{ $bookRequests->count() }} terbaru</span></div>
        <div class="table-wrap"><table class="admin-table">
            <thead><tr><th>ANGGOTA</th><th>BUKU</th><th>CATATAN</th><th>STATUS</th><th>PERBARUI</th></tr></thead>
            <tbody>
                @forelse ($bookRequests as $bookRequest)
                    <tr>
                        <td><b>{{ $bookRequest->user->name }}</b><small class="table-sub">{{ $bookRequest->user->email }}</small></td>
                        <td>{{ $bookRequest->title }}<small class="table-sub">{{ $bookRequest->author }}</small></td>
                        <td>{{ $bookRequest->notes ?: '—' }}</td>
                        <td>{{ ucfirst($bookRequest->status) }}</td>
                        <td>
                            <form action="{{ route('admin.book-requests.update', $bookRequest) }}" method="POST" class="request-status-form">
                                @csrf @method('PUT')
                                <select name="status" aria-label="Status usulan {{ $bookRequest->title }}">
                                    <option value="approved" @selected($bookRequest->status === 'approved')>Disetujui</option>
                                    <option value="rejected" @selected($bookRequest->status === 'rejected')>Ditolak</option>
                                    <option value="acquired" @selected($bookRequest->status === 'acquired')>Sudah tersedia</option>
                                </select>
                                <input name="admin_note" maxlength="1000" placeholder="Catatan untuk anggota" value="{{ $bookRequest->admin_note }}">
                                <button class="table-link" type="submit">Simpan</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="table-empty">Belum ada usulan buku dari anggota.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>
</section>

<div class="modal-backdrop" id="book-modal" hidden>
    <div class="modal-card admin-book-modal" role="dialog" aria-modal="true" aria-labelledby="book-modal-title">
        <button class="modal-close" type="button" aria-label="Tutup" data-close-modal>×</button>
        <span class="eyebrow">KATALOG PERPUSTAKAAN</span><h2 id="book-modal-title">Tambahkan cerita baru.</h2>
        <form action="{{ route('admin.books.store') }}" data-store-url="{{ route('admin.books.store') }}" method="POST" enctype="multipart/form-data" class="admin-book-form">
            @csrf
            <label>Judul buku<input name="title" required maxlength="255"></label><label>Pengarang<input name="author" required maxlength="180"></label>
            <label>Penerbit<input name="publisher" maxlength="180"></label><label>ISBN<input name="isbn" maxlength="30"></label>
            <label>Genre<input name="genre" required maxlength="80"></label><label>Bahasa<input name="language" required value="Indonesia" maxlength="50"></label>
            <label>Tahun terbit<input name="published_year" type="number" required min="1000" max="2100" value="{{ date('Y') }}"></label><label>Stok<input name="stock" type="number" required min="0" value="1"></label>
            <label>Lokasi rak<input name="shelf" maxlength="100"></label><label>Kode Dewey<input name="dewey_code" maxlength="30"></label>
            <label class="full-field">URL sampul<input name="cover" type="url" maxlength="1000" placeholder="https://..."></label>
            <label class="full-field">Sinopsis<textarea name="synopsis" rows="3" maxlength="3000"></textarea></label>
            <label class="full-field">Unggah e-book PDF (maks. 20 MB)<input type="file" name="ebook_file" accept="application/pdf,.pdf"><small data-current-ebook></small></label>
            <label class="check-label full-field" data-remove-ebook-wrap hidden><input name="remove_ebook" type="checkbox" value="1"> Hapus PDF yang saat ini tersimpan</label>
            <label class="check-label"><input name="popular" type="checkbox" value="1"> Buku populer</label>
            <button class="button button-dark button-wide full-field" type="submit">Simpan koleksi <span>↗</span></button>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="user-modal" hidden>
    <div class="modal-card admin-book-modal" role="dialog" aria-modal="true" aria-labelledby="user-modal-title">
        <button class="modal-close" type="button" aria-label="Tutup" data-close-modal>×</button>
        <span class="eyebrow">AKUN RUANG BACA</span><h2 id="user-modal-title">Buat akun baru.</h2>
        <form action="{{ route('admin.users.store') }}" data-store-url="{{ route('admin.users.store') }}" method="POST" class="admin-book-form">
            @csrf
            <label>Nama lengkap<input name="name" required maxlength="120"></label><label>Email<input type="email" name="email" required maxlength="255"></label>
            <label>Password<input type="password" name="password" required minlength="10" autocomplete="new-password"></label><label>Ulangi password<input type="password" name="password_confirmation" required minlength="10" autocomplete="new-password"></label>
            <label>Peran<select name="role" required><option value="member">Anggota</option><option value="admin">Admin</option></select></label>
            <p class="full-field admin-form-hint">Password minimal 10 karakter, mengandung huruf dan angka. Password akun hanya dapat diubah pemilik atau admin.</p>
            <button class="button button-dark button-wide full-field" type="submit">Simpan akun <span>↗</span></button>
        </form>
    </div>
</div>
@endsection
