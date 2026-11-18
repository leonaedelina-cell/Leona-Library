<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookRequest;
use App\Models\BookReview;
use App\Models\ContactMessage;
use App\Models\Loan;
use App\Models\Member;
use App\Models\MembershipSubscription;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationQueue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        return view('admin', [
            'books' => Book::withCount(['loans as active_loans_count' => fn ($query) => $query->where('status', 'active')])->latest()->get(),
            'loans' => Loan::with('book')->latest()->take(12)->get(),
            'activeLoansCount' => Loan::where('status', 'active')->count(),
            'totalLoans' => Loan::count(),
            'members' => Member::with('user')->latest()->get(),
            'users' => User::with('member')->latest()->get(),
            'messages' => ContactMessage::latest()->take(8)->get(),
            'membershipRequests' => MembershipSubscription::with('user')
                ->where('status', 'pending')
                ->where('payment_status', 'pending')
                ->latest()
                ->get(),
            'merchandiseRequests' => MembershipSubscription::with('user')->where('merchandise_status', 'pending')->latest()->get(),
            'bookRequests' => BookRequest::with('user')->latest()->take(30)->get(),
            'bookReviews' => BookReview::with(['book', 'user'])->where('status', 'pending')->latest()->take(50)->get(),
            'reservations' => Reservation::with(['user', 'book'])
                ->whereIn('status', ['pending', 'ready'])
                ->orderByDesc('priority')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $email = $request->input('email');
        if (is_string($email)) {
            $request->merge(['email' => Str::lower($email)]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:members,email'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
            'role' => ['required', 'in:member,admin'],
        ]);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => Str::lower($data['email']),
                'password' => $data['password'],
                'role' => $data['role'],
            ]);

            if ($data['role'] === 'member') {
                Member::create([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'member_number' => 'LIB-'.now()->format('Y').'-'.Str::upper(Str::random(8)),
                    'status' => 'active',
                    'joined_at' => today(),
                ]);
            }
        });

        return back()->with('success', 'Akun berhasil dibuat.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $email = $request->input('email');
        if (is_string($email)) {
            $request->merge(['email' => Str::lower($email)]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id), Rule::unique('members')->ignore($user->member?->id)],
            'role' => ['required', 'in:member,admin'],
            'password' => ['nullable', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);

        if ($user->is($request->user()) && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'Anda tidak dapat mengubah peran admin akun yang sedang digunakan.']);
        }

        if ($user->isAdmin() && $data['role'] !== 'admin' && User::where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['role' => 'Aplikasi harus memiliki setidaknya satu admin.']);
        }

        DB::transaction(function () use ($user, $data) {
            $user->update([
                'name' => $data['name'],
                'email' => Str::lower($data['email']),
                'role' => $data['role'],
                ...filled($data['password'] ?? null) ? ['password' => $data['password']] : [],
            ]);

            if ($data['role'] === 'member') {
                $user->member()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'name' => $user->name,
                        'email' => $user->email,
                        'member_number' => $user->member?->member_number ?? 'LIB-'.now()->format('Y').'-'.Str::upper(Str::random(8)),
                        'status' => $user->member?->status ?? 'active',
                        'joined_at' => $user->member?->joined_at ?? today(),
                    ],
                );
            } else {
                $user->member?->update(['name' => $user->name, 'email' => $user->email]);
            }
        });

        return back()->with('success', 'Akun berhasil diperbarui.');
    }

    public function deleteUser(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Anda tidak dapat menghapus akun yang sedang digunakan.']);
        }

        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['user' => 'Aplikasi harus memiliki setidaknya satu admin.']);
        }

        DB::transaction(function () use ($user) {
            $user->member?->delete();
            $user->delete();
        });

        return back()->with('success', 'Akun berhasil dihapus.');
    }

    public function storeBook(Request $request): RedirectResponse
    {
        $data = $this->validatedBook($request);
        $data['ebook_path'] = $request->file('ebook_file')?->store('ebooks', 'local');
        $data['ebook'] = $data['ebook_path'] !== null;

        Book::create($data);

        return back()->with('success', 'Buku berhasil ditambahkan ke katalog.');
    }

    public function updateBook(Request $request, Book $book, ReservationQueue $reservationQueue): RedirectResponse
    {
        $data = $this->validatedBook($request, $book);

        if ($request->boolean('remove_ebook')) {
            if ($book->ebook_path) {
                Storage::disk('local')->delete($book->ebook_path);
            }

            $data['ebook_path'] = null;
        }

        if ($request->hasFile('ebook_file')) {
            $newEbookPath = $request->file('ebook_file')->store('ebooks', 'local');

            if ($book->ebook_path && $book->ebook_path !== $newEbookPath) {
                Storage::disk('local')->delete($book->ebook_path);
            }

            $data['ebook_path'] = $newEbookPath;
        }

        $data['ebook'] = $data['ebook_path'] !== null;
        $book->update($data);
        $reservationQueue->prepare($book);

        return back()->with('success', 'Data buku berhasil diperbarui.');
    }

    public function deleteBook(Book $book): RedirectResponse
    {
        if ($book->loans()->exists()) {
            return back()->withErrors(['book' => 'Buku dengan riwayat peminjaman tidak dapat dihapus.']);
        }

        if (Reservation::where('book_id', $book->id)->exists()) {
            return back()->withErrors(['book' => 'Buku dengan riwayat reservasi tidak dapat dihapus.']);
        }

        if ($book->ebook_path) {
            Storage::disk('local')->delete($book->ebook_path);
        }
        $book->delete();

        return back()->with('success', 'Buku berhasil dihapus.');
    }

    public function updateMemberStatus(Request $request, Member $member): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:active,blocked']]);

        $member->update($data);

        return back()->with('success', $data['status'] === 'active'
            ? 'Akses anggota '.$member->name.' berhasil dibuka.'
            : 'Keanggotaan '.$member->name.' berhasil diblokir.');
    }

    public function storeLoan(Request $request, ReservationQueue $reservationQueue): RedirectResponse
    {
        $data = $request->validate([
            'member_number' => ['required', 'string', 'max:40', 'exists:members,member_number'],
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'method' => ['required', 'in:pickup,onsite'],
        ]);

        $message = DB::transaction(function () use ($data, $reservationQueue) {
            $member = Member::where('member_number', $data['member_number'])->lockForUpdate()->firstOrFail();

            if ($member->status === 'blocked') {
                return 'Anggota yang diblokir tidak dapat meminjam buku.';
            }

            $book = Book::query()->lockForUpdate()->findOrFail($data['book_id']);

            if ($book->availableCopies() < 1) {
                return 'Tidak ada eksemplar tersedia untuk buku ini.';
            }

            if (! $reservationQueue->mayBorrow($member->user, $book)) {
                return 'Eksemplar tersedia untuk anggota berikutnya dalam antrean reservasi.';
            }

            $membership = $member->user?->currentMembership();
            $plan = $membership ? config('membership.plans.'.$membership->plan) : null;
            $loanLimit = $plan['loan_limit'] ?? 3;
            $loanDays = $plan['loan_days'] ?? 14;

            if ($member->loans()->where('status', 'active')->count() >= $loanLimit) {
                return 'Batas peminjaman adalah '.$loanLimit.' buku aktif untuk paket anggota.';
            }

            Loan::create([
                'book_id' => $book->id,
                'member_name' => $member->name,
                'member_number' => $member->member_number,
                'due_at' => today()->addDays($loanDays),
                'status' => 'active',
                'method' => $data['method'],
                'late_fee_free_days' => $membership?->billing_period === 'yearly' ? 3 : 0,
            ]);

            if ($member->user) {
                $reservationQueue->fulfill($member->user, $book);
            }

            return null;
        });

        return $message
            ? back()->withErrors(['loan' => $message])
            : back()->with('success', 'Peminjaman anggota berhasil dicatat.');
    }

    public function returnBook(Loan $loan, ReservationQueue $reservationQueue): RedirectResponse
    {
        if ($loan->status !== 'active') {
            return back()->withErrors(['loan' => 'Peminjaman ini sudah tercatat selesai.']);
        }

        $lateDays = $loan->lateDays();
        $fineAmount = $loan->estimatedFine();

        DB::transaction(function () use ($loan, $fineAmount, $reservationQueue): void {
            $loan->update(['status' => 'returned', 'fine' => $fineAmount]);
            $reservationQueue->prepare($loan->book);
        });

        return back()->with('success', $fineAmount > 0
            ? 'Pengembalian dicatat. Denda keterlambatan Rp'.number_format($fineAmount, 0, ',', '.').'.'
            : ($lateDays > 0
                ? 'Pengembalian dicatat tanpa denda sesuai benefit membership tahunan.'
                : 'Pengembalian buku berhasil dicatat.'));
    }

    public function fulfillMembershipMerchandise(MembershipSubscription $membership): RedirectResponse
    {
        if ($membership->merchandise_status !== 'pending') {
            return back()->withErrors(['merchandise' => 'Merchandise tidak sedang menunggu penyerahan.']);
        }

        $membership->update(['merchandise_status' => 'fulfilled']);

        return back()->with('success', 'Penyerahan merchandise berhasil dicatat.');
    }

    public function updateBookRequest(Request $request, BookRequest $bookRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected,acquired'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $bookRequest->update($data);

        return back()->with('success', 'Status usulan buku berhasil diperbarui.');
    }

    public function approveBookReview(BookReview $bookReview): RedirectResponse
    {
        if ($bookReview->status !== 'pending') {
            return back()->withErrors(['review' => 'Ulasan ini sudah dimoderasi.']);
        }

        $bookReview->update(['status' => 'approved']);

        return back()->with('success', 'Ulasan berhasil disetujui dan ditampilkan.');
    }

    public function updateReservation(Request $request, Reservation $reservation, ReservationQueue $reservationQueue): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:fulfilled,cancelled']]);

        if (! in_array($reservation->status, ['pending', 'ready'], true)) {
            return back()->withErrors(['reservation' => 'Reservasi ini sudah selesai dan tidak dapat diubah lagi.']);
        }

        $reservation->update($data);
        $reservationQueue->prepare($reservation->book);

        return back()->with('success', 'Status reservasi berhasil diperbarui.');
    }

    private function validatedBook(Request $request, ?Book $book = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:180'],
            'publisher' => ['nullable', 'string', 'max:180'],
            'isbn' => ['nullable', 'string', 'max:30', Rule::unique('books', 'isbn')->ignore($book?->id)],
            'genre' => ['required', 'string', 'max:80'],
            'language' => ['required', 'string', 'max:50'],
            'published_year' => ['required', 'integer', 'between:1000,2100'],
            'synopsis' => ['nullable', 'string', 'max:3000'],
            'cover' => ['nullable', 'url', 'max:1000'],
            'shelf' => ['nullable', 'string', 'max:100'],
            'dewey_code' => ['nullable', 'string', 'max:30'],
            'stock' => ['required', 'integer', 'min:0'],
            'ebook_file' => ['nullable', 'file', 'mimes:pdf', 'extensions:pdf', 'max:20480'],
            'remove_ebook' => ['sometimes', 'boolean'],
            'popular' => ['sometimes', 'boolean'],
        ]);

        unset($data['ebook_file'], $data['remove_ebook']);

        $data['ebook_path'] = $book?->ebook_path;
        $data['popular'] = $request->boolean('popular');

        return $data;
    }
}
