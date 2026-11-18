<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookReviewController extends Controller
{
    public function store(Request $request, Loan $loan): RedirectResponse
    {
        abort_unless(
            $request->user()->member?->member_number === $loan->member_number,
            403,
        );

        $data = $request->validate([
            'mood' => ['required', 'in:terharu,hangat,terkejut,terinspirasi,merenung'],
            'content' => [
                'required',
                'string',
                'min:20',
                'max:2000',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }

                    $prohibitedWords = ['anjing', 'bangsat', 'bajingan', 'brengsek', 'kontol', 'memek', 'tolol', 'goblok'];
                    $pattern = '/(?<![\p{L}\p{N}])(?:'.implode('|', $prohibitedWords).')(?![\p{L}\p{N}])/iu';

                    if (preg_match($pattern, $value) === 1) {
                        $fail('Gunakan bahasa yang sopan agar ulasan dapat dikirim.');
                    }
                },
            ],
            'is_spoiler' => ['sometimes', 'boolean'],
        ]);

        $error = DB::transaction(function () use ($data, $loan, $request): ?string {
            $lockedLoan = Loan::query()
                ->with('book')
                ->lockForUpdate()
                ->findOrFail($loan->id);

            if ($lockedLoan->status !== 'returned') {
                return 'Kesan dan pesan hanya dapat dikirim setelah peminjaman selesai dibaca.';
            }

            if (! $lockedLoan->book->ebook_path) {
                return 'Kesan dan pesan hanya tersedia untuk e-book yang telah selesai dibaca.';
            }

            if ($lockedLoan->review()->exists()) {
                return 'Kamu sudah mengirim kesan dan pesan untuk e-book ini.';
            }

            $lockedLoan->review()->create([
                'user_id' => $request->user()->id,
                'book_id' => $lockedLoan->book_id,
                'mood' => $data['mood'],
                'content' => $data['content'],
                'is_spoiler' => $request->boolean('is_spoiler'),
                'status' => 'pending',
            ]);

            return null;
        });

        if ($error !== null) {
            return back()->withErrors(['review' => $error]);
        }

        return back()->with('success', 'Terima kasih sudah berbagi cerita. Ulasanmu menunggu persetujuan pengelola.');
    }
}
