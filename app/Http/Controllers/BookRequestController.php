<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if (! $request->user()->member || $request->user()->member->status === 'blocked'
            || $request->user()->currentMembership()?->billing_period !== 'yearly') {
            return back()->withErrors(['book_request' => 'Pengajuan buku tersedia untuk anggota dengan membership tahunan yang aktif.']);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $request->user()->bookRequests()->create($data);

        return back()->with('success', 'Usulan buku berhasil dikirim ke pengelola.');
    }
}
