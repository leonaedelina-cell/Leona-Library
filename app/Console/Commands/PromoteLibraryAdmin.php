<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class PromoteLibraryAdmin extends Command
{
    protected $signature = 'library:make-admin {email : Email akun yang akan diberi akses admin}';

    protected $description = 'Promosikan akun perpustakaan terdaftar menjadi admin';

    public function handle(): int
    {
        $user = User::where('email', Str::lower($this->argument('email')))->first();

        if (! $user) {
            $this->components->error('Akun tidak ditemukan. Daftarkan akun tersebut terlebih dahulu.');

            return self::FAILURE;
        }

        $user->update(['role' => 'admin']);
        $this->components->info("{$user->email} sekarang memiliki akses admin.");

        return self::SUCCESS;
    }
}
