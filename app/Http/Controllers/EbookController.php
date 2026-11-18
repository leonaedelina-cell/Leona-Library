<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EbookController extends Controller
{
    public function show(Book $book, Request $request): View
    {
        $user = $request->user();
        $this->authorizeReader($book, $user);

        return view('ebook.reader', [
            'book' => $book,
            'pdfUrl' => route('books.ebook.file', $book),
            'watermark' => $user->email,
        ]);
    }

    public function file(Book $book, Request $request): BinaryFileResponse
    {
        $this->authorizeReader($book, $request->user());

        $response = response()->file(Storage::disk('local')->path($book->ebook_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="ruang-baca-'.$book->id.'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    private function authorizeReader(Book $book, ?User $user): void
    {
        $member = $user?->member;

        abort_unless($member && $member->status !== 'blocked', 403);
        abort_unless($user?->currentMembership()?->plan === 'premium', 403);
        abort_unless($book->ebook_path && Storage::disk('local')->exists($book->ebook_path), 404);
    }
}
