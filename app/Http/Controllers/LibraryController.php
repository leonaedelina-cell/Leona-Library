<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookView;
use App\Models\ContactMessage;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Reservation;
use App\Services\ReservationQueue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LibraryController extends Controller
{
    public function home(): View
    {
        return view('home', [
            'popularBooks' => Book::withCount([
                'views',
                'favorites',
                'loans as active_loans_count' => fn ($query) => $query->where('status', 'active'),
            ])->where('popular', true)->take(4)->get(),
            'latestBooks' => Book::withCount([
                'views',
                'favorites',
                'loans as active_loans_count' => fn ($query) => $query->where('status', 'active'),
            ])->latest()->take(4)->get(),
            'bookCount' => Book::count(),
            'loanCount' => Loan::count(),
            'activeMemberCount' => Member::where('status', 'active')->count(),
        ]);
    }

    public function catalog(Request $request): View
    {
        $sortOptions = [
            'popular' => 'Terpopuler',
            'favorite' => 'Terfavorit',
            'latest' => 'Terbaru',
            'title' => 'Judul (A-Z)',
        ];
        $sort = $request->input('sort', 'latest');

        if (! is_string($sort) || ! array_key_exists($sort, $sortOptions)) {
            $sort = 'latest';
        }

        $booksQuery = Book::query()
            ->when($request->filled('q'), fn ($query) => $query->where(function ($query) use ($request) {
                $query->where('title', 'like', '%'.$request->input('q').'%')
                    ->orWhere('author', 'like', '%'.$request->input('q').'%');
            }))
            ->when($request->filled('genre'), fn ($query) => $query->where('genre', $request->input('genre')))
            ->when($request->filled('language'), fn ($query) => $query->where('language', $request->input('language')))
            ->when($request->input('availability') === 'available', fn ($query) => $query->whereRaw('stock > (SELECT COUNT(*) FROM loans WHERE loans.book_id = books.id AND loans.status = ?)', ['active']))
            ->when($request->filled('year'), fn ($query) => $query->where('published_year', $request->integer('year')))
            ->withCount([
                'views',
                'favorites',
                'loans',
                'loans as active_loans_count' => fn ($query) => $query->where('status', 'active'),
                'reviews as approved_reviews_count' => fn ($query) => $query->where('status', 'approved'),
                'reviews as positive_reviews_count' => fn ($query) => $query
                    ->where('status', 'approved')
                    ->whereIn('mood', ['hangat', 'terinspirasi']),
            ]);

        $books = match ($sort) {
            'popular' => $booksQuery->orderByDesc('views_count')->orderByDesc('loans_count')->latest(),
            'favorite' => $booksQuery
                ->orderByDesc('favorites_count')
                ->orderByDesc('positive_reviews_count')
                ->orderByDesc('approved_reviews_count')
                ->latest(),
            'title' => $booksQuery->orderBy('title')->orderBy('author')->orderBy('id'),
            default => $booksQuery->latest(),
        };

        return view('catalog', [
            'books' => $books->paginate(12)->withQueryString(),
            'genres' => Book::query()->distinct()->orderBy('genre')->pluck('genre'),
            'languages' => Book::query()->distinct()->orderBy('language')->pluck('language'),
            'sort' => $sort,
            'sortOptions' => $sortOptions,
        ]);
    }

    public function show(Book $book, Request $request): View
    {
        if ($request->user()) {
            $this->syncLegacyWishlist($request);
        }

        $userId = $request->user()?->getAuthIdentifier();
        $viewedAt = now();

        BookView::query()->insertOrIgnore([
            'book_id' => $book->id,
            'user_id' => $userId,
            'session_hash' => $userId === null ? hash('sha256', $request->session()->getId()) : null,
            'created_at' => $viewedAt,
            'updated_at' => $viewedAt,
        ]);

        $book->loadCount([
            'loans as active_loans_count' => fn ($query) => $query->where('status', 'active'),
            'views',
            'favorites',
        ]);

        return view('book', [
            'book' => $book,
            'isWishlisted' => $request->user()?->favoriteBooks()->whereKey($book->id)->exists() ?? false,
            'approvedReviews' => $book->reviews()
                ->with('user')
                ->where('status', 'approved')
                ->latest()
                ->get(),
        ]);
    }

    public function borrow(Request $request, Book $book, ReservationQueue $reservationQueue): RedirectResponse
    {
        $data = $request->validate([
            'method' => ['required', 'in:pickup,onsite'],
        ]);

        $result = DB::transaction(function () use ($book, $data, $request, $reservationQueue): array {
            $member = Member::where('user_id', $request->user()->id)->lockForUpdate()->first();

            if (! $member || $member->status === 'blocked') {
                return ['error' => 'Akun anggota yang diblokir tidak dapat meminjam buku.', 'days' => 14];
            }

            $lockedBook = Book::query()->lockForUpdate()->findOrFail($book->id);

            if ($lockedBook->availableCopies() < 1) {
                return ['error' => 'Maaf, semua eksemplar sedang dipinjam.', 'days' => 14];
            }

            if (! $reservationQueue->mayBorrow($request->user(), $lockedBook)) {
                return ['error' => 'Eksemplar tersedia untuk anggota berikutnya dalam antrean reservasi.', 'days' => 14];
            }

            $membership = $request->user()->currentMembership();
            $plan = $membership ? config('membership.plans.'.$membership->plan) : null;
            $loanLimit = $plan['loan_limit'] ?? 3;
            $loanDays = $plan['loan_days'] ?? 14;
            $dueDate = today()->addDays($loanDays);

            if ($member->loans()->where('status', 'active')->count() >= $loanLimit) {
                return ['error' => 'Batas peminjaman adalah '.$loanLimit.' buku aktif untuk paket Anda.', 'days' => $loanDays];
            }

            Loan::create([
                'member_name' => $member->name,
                'member_number' => $member->member_number,
                'borrower_type' => 'member',
                'checked_out_at' => now(),
                'book_id' => $lockedBook->id,
                'due_at' => $dueDate->toDateString(),
                'return_by' => $dueDate->copy()->endOfDay(),
                'status' => 'active',
                'method' => $data['method'],
                'late_fee_free_days' => $membership?->billing_period === 'yearly' ? 3 : 0,
            ]);

            $reservationQueue->fulfill($request->user(), $lockedBook);

            $request->session()->put([
                'member_number' => $member->member_number,
                'member_name' => $member->name,
            ]);

            return ['error' => null, 'days' => $loanDays];
        });

        if ($result['error'] !== null) {
            return back()->withErrors(['borrow' => $result['error']]);
        }

        return redirect()->route('dashboard')->with('success', 'Permintaan peminjaman berhasil dicatat. Tenggat pengembalian '.$result['days'].' hari dari hari ini.');
    }

    public function wishlist(Request $request, Book $book): RedirectResponse
    {
        $this->syncLegacyWishlist($request);
        $user = $request->user();

        if ($user->favoriteBooks()->whereKey($book->id)->exists()) {
            $user->favoriteBooks()->detach($book->id);
        } else {
            $user->favoriteBooks()->syncWithoutDetaching([$book->id]);
        }

        return back()->with('success', 'Daftar bacaan berhasil diperbarui.');
    }

    public function services(): View
    {
        return view('services');
    }

    public function contact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        ContactMessage::create($data);

        return back()->with('success', 'Terima kasih! Pesan Anda sudah kami terima.');
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.index');
        }

        $this->syncLegacyWishlist($request);
        $member = $user->member;

        return view('dashboard', [
            'member' => $member,
            'membership' => $user->currentMembership(),
            'pendingMembership' => $user->memberships()
                ->where('status', 'pending')
                ->where('payment_status', 'pending')
                ->latest()
                ->first(),
            'loans' => $member
                ? Loan::with(['book', 'review'])->where('member_number', $member->member_number)->latest()->get()
                : collect(),
            'wishlist' => $user->favoriteBooks()->withCount([
                'views',
                'favorites',
                'loans as active_loans_count' => fn ($query) => $query->where('status', 'active'),
            ])->get(),
            'reservations' => $user->reservations()->with('book')->whereIn('status', ['pending', 'ready'])->latest()->get(),
            'bookRequests' => $user->bookRequests()->latest()->take(5)->get(),
        ]);
    }

    public function renew(Request $request, Loan $loan): RedirectResponse
    {
        abort_unless($loan->member_number === $request->user()->member?->member_number, 403);

        if ($loan->status !== 'active') {
            return back()->withErrors(['loan' => 'Hanya peminjaman aktif yang dapat diperpanjang.']);
        }

        if ($loan->renewals >= 1) {
            return back()->withErrors(['loan' => 'Masa pinjam hanya dapat diperpanjang satu kali.']);
        }

        if (Reservation::query()
            ->where('book_id', $loan->book_id)
            ->whereIn('status', ['pending', 'ready'])
            ->where('user_id', '!=', $request->user()->id)
            ->exists()) {
            return back()->withErrors(['loan' => 'Buku sudah masuk antrean reservasi anggota lain dan tidak dapat diperpanjang.']);
        }

        if ($loan->due_at->isBefore(today())) {
            return back()->withErrors(['loan' => 'Peminjaman yang melewati tenggat tidak dapat diperpanjang secara otomatis. Silakan hubungi petugas.']);
        }

        $dueDate = $loan->due_at->copy()->addDays(7);

        $loan->update([
            'due_at' => $dueDate,
            'return_by' => $dueDate->copy()->endOfDay(),
            'renewals' => $loan->renewals + 1,
        ]);

        return back()->with('success', 'Masa pinjam berhasil diperpanjang selama 7 hari.');
    }

    public function returnBook(Loan $loan, ReservationQueue $reservationQueue): RedirectResponse
    {
        if ($loan->status !== 'active') {
            return back()->withErrors(['loan' => 'Peminjaman ini sudah tercatat selesai.']);
        }

        $lateDays = $loan->lateDays();
        $fineAmount = $loan->estimatedFine();

        DB::transaction(function () use ($loan, $fineAmount, $reservationQueue): void {
            $loan->update(['status' => 'returned', 'fine' => $fineAmount, 'returned_at' => now()]);
            $reservationQueue->prepare($loan->book);
        });

        return back()->with('success', $fineAmount > 0
            ? 'Pengembalian dicatat. Denda keterlambatan Rp'.number_format($fineAmount, 0, ',', '.').'.'
            : ($lateDays > 0
                ? 'Pengembalian dicatat tanpa denda sesuai benefit membership tahunan.'
                : 'Pengembalian buku berhasil dicatat.'));
    }

    private function syncLegacyWishlist(Request $request): void
    {
        $user = $request->user();
        $legacyWishlist = collect($request->session()->get('wishlist', []))
            ->filter(fn ($id): bool => is_int($id) || (is_string($id) && ctype_digit($id)))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($legacyWishlist->isNotEmpty()) {
            $bookIds = Book::query()->whereKey($legacyWishlist)->pluck('id')->all();

            if ($bookIds !== []) {
                $user->favoriteBooks()->syncWithoutDetaching($bookIds);
            }
        }

        $request->session()->forget('wishlist');
    }
}
