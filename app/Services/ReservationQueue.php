<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Reservation;
use App\Models\User;

class ReservationQueue
{
    public function prepare(Book $book): ?Reservation
    {
        $availableCopies = max(0, $book->stock - $book->loans()->where('status', 'active')->count());
        $readyCount = Reservation::query()
            ->where('book_id', $book->id)
            ->where('status', 'ready')
            ->count();
        $slots = max(0, $availableCopies - $readyCount);

        if ($slots > 0) {
            Reservation::query()
                ->where('book_id', $book->id)
                ->where('status', 'pending')
                ->orderByDesc('priority')
                ->orderBy('created_at')
                ->orderBy('id')
                ->limit($slots)
                ->get()
                ->each(fn (Reservation $reservation) => $reservation->update(['status' => 'ready']));
        }

        return Reservation::query()
            ->where('book_id', $book->id)
            ->where('status', 'ready')
            ->orderByDesc('priority')
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();
    }

    public function mayBorrow(?User $user, Book $book): bool
    {
        $this->prepare($book);

        $readyReservations = Reservation::query()
            ->where('book_id', $book->id)
            ->where('status', 'ready');

        if (! $readyReservations->exists()) {
            return true;
        }

        return $user !== null && Reservation::query()
            ->where('book_id', $book->id)
            ->where('status', 'ready')
            ->where('user_id', $user->id)
            ->exists();
    }

    public function fulfill(User $user, Book $book): void
    {
        Reservation::query()
            ->where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->where('status', 'ready')
            ->update(['status' => 'fulfilled']);

        $this->prepare($book);
    }
}
