<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function store(Request $request, Book $book): RedirectResponse
    {
        $user = $request->user();

        if (! $user->member || $user->member->status === 'blocked') {
            return back()->withErrors(['reservation' => 'Reservasi tidak tersedia untuk akun anggota yang diblokir.']);
        }

        $result = DB::transaction(function () use ($book, $user): ?string {
            $lockedBook = Book::query()->lockForUpdate()->findOrFail($book->id);

            if ($lockedBook->availableCopies() > 0) {
                return 'Buku masih tersedia dan bisa langsung dipinjam.';
            }

            if ($user->reservations()->where('book_id', $lockedBook->id)->whereIn('status', ['pending', 'ready'])->exists()) {
                return 'Kamu sudah berada dalam antrean reservasi buku ini.';
            }

            $membership = $user->currentMembership();
            $priority = $lockedBook->popular && $membership?->plan === 'premium' ? 1 : 0;

            Reservation::create([
                'user_id' => $user->id,
                'book_id' => $lockedBook->id,
                'priority' => $priority,
                'status' => 'pending',
            ]);

            return null;
        });

        if ($result !== null) {
            return back()->withErrors(['reservation' => $result]);
        }

        return back()->with('success', 'Reservasi berhasil dicatat. Antrean buku populer memprioritaskan anggota Premium.');
    }
}
