<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $books = [
            ['title' => 'Laut Bercerita', 'author' => 'Leila S. Chudori', 'publisher' => 'Kepustakaan Populer Gramedia', 'isbn' => '9786024246945', 'genre' => 'Fiksi', 'language' => 'Indonesia', 'published_year' => 2017, 'synopsis' => 'Kisah Biru Laut dan kawan-kawannya, sekelompok aktivis mahasiswa yang memperjuangkan keadilan di tengah masa yang penuh ketidakpastian.', 'cover' => 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=700&q=85', 'shelf' => 'Rak A3 · Lantai 2', 'dewey_code' => '813.3', 'stock' => 4, 'ebook' => false, 'popular' => true],
            ['title' => 'Filosofi Teras', 'author' => 'Henry Manampiring', 'publisher' => 'Kompas', 'isbn' => '9786024125189', 'genre' => 'Pengembangan Diri', 'language' => 'Indonesia', 'published_year' => 2018, 'synopsis' => 'Belajar menjalani hidup dengan lebih tenang melalui filsafat Stoisisme yang praktis dan relevan untuk keseharian.', 'cover' => 'https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=700&q=85', 'shelf' => 'Rak C1 · Lantai 1', 'dewey_code' => '188', 'stock' => 3, 'ebook' => false, 'popular' => true],
            ['title' => 'Sebuah Seni untuk Bersikap Bodo Amat', 'author' => 'Mark Manson', 'publisher' => 'Gramedia Pustaka Utama', 'isbn' => '9786024526986', 'genre' => 'Pengembangan Diri', 'language' => 'Indonesia', 'published_year' => 2018, 'synopsis' => 'Panduan yang jujur dan jenaka untuk memilih hal-hal yang benar-benar penting dan menjalani hidup dengan lebih bermakna.', 'cover' => 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?auto=format&fit=crop&w=700&q=85', 'shelf' => 'Rak C2 · Lantai 1', 'dewey_code' => '158.1', 'stock' => 2, 'ebook' => false, 'popular' => true],
            ['title' => 'Laut dan Langit', 'author' => 'Nadia Ristivani', 'publisher' => 'Bentang Pustaka', 'isbn' => '9786022917350', 'genre' => 'Romansa', 'language' => 'Indonesia', 'published_year' => 2025, 'synopsis' => 'Sebuah kisah hangat tentang keberanian untuk memulai lagi dan menemukan rumah di antara orang-orang terkasih.', 'cover' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=700&q=85', 'shelf' => 'Rak B1 · Lantai 2', 'dewey_code' => '813.4', 'stock' => 5, 'ebook' => false, 'popular' => false],
            ['title' => 'Perempuan yang Menangis kepada Bulan Hitam', 'author' => 'Dian Purnomo', 'publisher' => 'Gramedia Pustaka Utama', 'isbn' => '9786020626190', 'genre' => 'Fiksi', 'language' => 'Indonesia', 'published_year' => 2020, 'synopsis' => 'Perjalanan Magi Diela dalam memperjuangkan hak, harapan, dan masa depan di tanah Sumba.', 'cover' => 'https://images.unsplash.com/photo-1516979187457-637abb4f9353?auto=format&fit=crop&w=700&q=85', 'shelf' => 'Rak A2 · Lantai 2', 'dewey_code' => '813.3', 'stock' => 3, 'ebook' => false, 'popular' => true],
            ['title' => 'Atomic Habits', 'author' => 'James Clear', 'publisher' => 'Gramedia Pustaka Utama', 'isbn' => '9786020633176', 'genre' => 'Pengembangan Diri', 'language' => 'Indonesia', 'published_year' => 2019, 'synopsis' => 'Cara sederhana dan terbukti untuk membentuk kebiasaan baik serta meninggalkan kebiasaan buruk setahap demi setahap.', 'cover' => 'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?auto=format&fit=crop&w=700&q=85', 'shelf' => 'Rak C3 · Lantai 1', 'dewey_code' => '158.1', 'stock' => 6, 'ebook' => false, 'popular' => false],
            ['title' => 'Pulang', 'author' => 'Tere Liye', 'publisher' => 'Republika', 'isbn' => '9786020822342', 'genre' => 'Fiksi', 'language' => 'Indonesia', 'published_year' => 2015, 'synopsis' => 'Petualangan Bujang yang menghadapi pertanyaan tentang keluarga, kehilangan, dan arti pulang.', 'cover' => 'https://images.unsplash.com/photo-1519682337058-a94d519337bc?auto=format&fit=crop&w=700&q=85', 'shelf' => 'Rak A1 · Lantai 2', 'dewey_code' => '813.3', 'stock' => 2, 'ebook' => false, 'popular' => false],
            ['title' => 'The Midnight Library', 'author' => 'Matt Haig', 'publisher' => 'Canongate Books', 'isbn' => '9781786892737', 'genre' => 'Fiksi', 'language' => 'Inggris', 'published_year' => 2020, 'synopsis' => 'Di antara hidup dan mati terdapat sebuah perpustakaan dengan tak terhingga buku yang berisi kesempatan mencoba kehidupan lain.', 'cover' => 'https://images.unsplash.com/photo-1513001900722-370f803f498d?auto=format&fit=crop&w=700&q=85', 'shelf' => 'Rak D1 · Lantai 1', 'dewey_code' => '823.92', 'stock' => 2, 'ebook' => false, 'popular' => false],
        ];

        foreach ($books as $book) {
            Book::updateOrCreate(['isbn' => $book['isbn']], $book);
        }

        if (App::environment(['local', 'testing'])) {
            $demoUser = User::firstOrCreate(
                ['email' => 'alya@ruangbaca.id'],
                ['name' => 'Alya Putri', 'password' => 'RuangBaca2026!', 'role' => 'member'],
            );

            Member::where('email', $demoUser->email)->whereNull('user_id')->update(['user_id' => $demoUser->id]);
            $demoUser->member()->firstOrCreate(
                [],
                [
                    'name' => $demoUser->name,
                    'email' => $demoUser->email,
                    'member_number' => 'LIB-2024-0042',
                    'status' => 'active',
                    'joined_at' => '2024-05-01',
                ],
            );
        }

        $member = [
            'member_name' => 'Alya Putri',
            'member_number' => 'LIB-2024-0042',
            'method' => 'pickup',
        ];

        if (App::environment(['local', 'testing'])) {
            Member::updateOrCreate(
                ['member_number' => $member['member_number']],
                [
                    'name' => 'Alya Putri',
                    'email' => 'alya@ruangbaca.id',
                    'user_id' => User::where('email', 'alya@ruangbaca.id')->value('id'),
                    'status' => 'active',
                    'joined_at' => '2024-05-01',
                ],
            );

            Loan::firstOrCreate(
                [
                    'book_id' => Book::where('isbn', '9786024125189')->value('id'),
                    'member_number' => $member['member_number'],
                    'status' => 'active',
                ],
                [...$member, 'due_at' => now()->addDays(8)->toDateString()],
            );

            Loan::firstOrCreate(
                [
                    'book_id' => Book::where('isbn', '9786024526986')->value('id'),
                    'member_number' => $member['member_number'],
                    'status' => 'returned',
                ],
                [...$member, 'due_at' => now()->subDays(12)->toDateString()],
            );
        }
    }
}
