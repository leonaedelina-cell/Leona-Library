<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EbookController extends Controller
{
    public function show(Book $book, Request $request): BinaryFileResponse
    {
        $user = $request->user();
        $member = $user?->member;

        abort_unless($member && $member->status !== 'blocked', 403);
        abort_unless($user?->currentMembership()?->plan === 'premium', 403);
        abort_unless($book->ebook_path && Storage::disk('local')->exists($book->ebook_path), 404);

        return response()->file(Storage::disk('local')->path($book->ebook_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="ruang-baca-'.$book->id.'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
