<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use App\Models\MembershipSubscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

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
            ['title' => 'Koper Merah di Halte Utara', 'author' => 'Ruang Baca', 'publisher' => 'Ruang Baca', 'isbn' => 'DEMO-ORIGINAL-2026-01', 'genre' => 'Fiksi', 'language' => 'Indonesia', 'published_year' => 2026, 'synopsis' => 'Sebuah koper tertinggal di halte mempertemukan Raka dengan kisah lama yang belum sempat berpamitan.', 'cover' => 'https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=700&q=85', 'shelf' => 'Rak Cerita A1', 'dewey_code' => '813.8', 'stock' => 2, 'ebook' => true, 'ebook_path' => 'ebooks/ruang-baca-original-01.pdf', 'popular' => true],
            ['title' => 'Peta untuk Hari Hujan', 'author' => 'Ruang Baca', 'publisher' => 'Ruang Baca', 'isbn' => 'DEMO-ORIGINAL-2026-02', 'genre' => 'Fiksi', 'language' => 'Indonesia', 'published_year' => 2026, 'synopsis' => 'Nala mengikuti peta misterius yang menunjukkan bahwa kebaikan kecil dapat menjadi penunjuk arah.', 'cover' => 'https://images.unsplash.com/photo-1506485338023-6ce5f36692df?auto=format&fit=crop&w=700&q=85', 'shelf' => 'Rak Cerita A1', 'dewey_code' => '813.8', 'stock' => 2, 'ebook' => true, 'ebook_path' => 'ebooks/ruang-baca-original-02.pdf', 'popular' => true],
            ['title' => 'Kebun di Atap Gedung Tujuh', 'author' => 'Ruang Baca', 'publisher' => 'Ruang Baca', 'isbn' => 'DEMO-ORIGINAL-2026-03', 'genre' => 'Inspirasi', 'language' => 'Indonesia', 'published_year' => 2026, 'synopsis' => 'Satu pot kecil mengubah atap berdebu menjadi kebun dan menghubungkan para tetangga.', 'cover' => 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?auto=format&fit=crop&w=700&q=85', 'shelf' => 'Rak Cerita A1', 'dewey_code' => '813.8', 'stock' => 2, 'ebook' => true, 'ebook_path' => 'ebooks/ruang-baca-original-03.pdf', 'popular' => true],
            ['title' => 'Surat yang Tidak Dikirim', 'author' => 'Ruang Baca', 'publisher' => 'Ruang Baca', 'isbn' => 'DEMO-ORIGINAL-2026-04', 'genre' => 'Drama', 'language' => 'Indonesia', 'published_year' => 2026, 'synopsis' => 'Laras menemukan kotak surat lama dan membantu dua sahabat lama saling menyapa kembali.', 'cover' => 'https://images.unsplash.com/photo-1455390582262-044cdead277a?auto=format&fit=crop&w=700&q=85', 'shelf' => 'Rak Cerita A2', 'dewey_code' => '813.8', 'stock' => 2, 'ebook' => true, 'ebook_path' => 'ebooks/ruang-baca-original-04.pdf', 'popular' => false],
            ['title' => 'Toko Jam Pukul Enam', 'author' => 'Ruang Baca', 'publisher' => 'Ruang Baca', 'isbn' => 'DEMO-ORIGINAL-2026-05', 'genre' => 'Drama', 'language' => 'Indonesia', 'published_year' => 2026, 'synopsis' => 'Sebuah jam saku yang berhenti menyimpan kenangan yang membantu Hana mengenang ayahnya.', 'cover' => 'https://images.unsplash.com/photo-1509048191080-d2e267d7b1f?auto=format&fit=crop&w=700&q=85', 'shelf' => 'Rak Cerita A2', 'dewey_code' => '813.8', 'stock' => 2, 'ebook' => true, 'ebook_path' => 'ebooks/ruang-baca-original-05.pdf', 'popular' => false],
            ['title' => 'Radio dari Kamar Belakang', 'author' => 'Ruang Baca', 'publisher' => 'Ruang Baca', 'isbn' => 'DEMO-ORIGINAL-2026-06', 'genre' => 'Fiksi', 'language' => 'Indonesia', 'published_year' => 2026, 'synopsis' => 'Siaran radio sederhana menghidupkan kembali kebiasaan warga untuk saling menjaga.', 'cover' => 'https://images.unsplash.com/photo-1511379938547-c1f69419868d?auto=format&fit=crop&w=700&q=85', 'shelf' => 'Rak Cerita A2', 'dewey_code' => '813.8', 'stock' => 2, 'ebook' => true, 'ebook_path' => 'ebooks/ruang-baca-original-06.pdf', 'popular' => false],
            ['title' => 'Perahu Kertas untuk Nara', 'author' => 'Ruang Baca', 'publisher' => 'Ruang Baca', 'isbn' => 'DEMO-ORIGINAL-2026-07', 'genre' => 'Keluarga', 'language' => 'Indonesia', 'published_year' => 2026, 'synopsis' => 'Nara dan kakeknya mengubah harapan kecil menjadi kerja bersama untuk lingkungan mereka.', 'cover' => 'https://images.unsplash.com/photo-1500375592092-40eb2168fd21?auto=format&fit=crop&w=700&q=85', 'shelf' => 'Rak Cerita A3', 'dewey_code' => '813.8', 'stock' => 2, 'ebook' => true, 'ebook_path' => 'ebooks/ruang-baca-original-07.pdf', 'popular' => false],
            ['title' => 'Rumah dengan Jendela Biru', 'author' => 'Ruang Baca', 'publisher' => 'Ruang Baca', 'isbn' => 'DEMO-ORIGINAL-2026-08', 'genre' => 'Drama', 'language' => 'Indonesia', 'published_year' => 2026, 'synopsis' => 'Sebuah buku resep menjadi awal persahabatan baru di sebuah gang yang sunyi.', 'cover' => 'https://images.unsplash.com/photo-1449844908441-8829872d2607?auto=format&fit=crop&w=700&q=85', 'shelf' => 'Rak Cerita A3', 'dewey_code' => '813.8', 'stock' => 2, 'ebook' => true, 'ebook_path' => 'ebooks/ruang-baca-original-08.pdf', 'popular' => false],
            ['title' => 'Resep Sup Hari Minggu', 'author' => 'Ruang Baca', 'publisher' => 'Ruang Baca', 'isbn' => 'DEMO-ORIGINAL-2026-09', 'genre' => 'Keluarga', 'language' => 'Indonesia', 'published_year' => 2026, 'synopsis' => 'Keluarga Bima memasak dari resep yang hilang satu baris dan menemukan rasa kebersamaan.', 'cover' => 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=700&q=85', 'shelf' => 'Rak Cerita A3', 'dewey_code' => '813.8', 'stock' => 2, 'ebook' => true, 'ebook_path' => 'ebooks/ruang-baca-original-09.pdf', 'popular' => false],
            ['title' => 'Cahaya dari Mercusuar Kecil', 'author' => 'Ruang Baca', 'publisher' => 'Ruang Baca', 'isbn' => 'DEMO-ORIGINAL-2026-10', 'genre' => 'Inspirasi', 'language' => 'Indonesia', 'published_year' => 2026, 'synopsis' => 'Saka dan warga desa menemukan bahwa cahaya kecil dapat menuntun banyak orang pulang.', 'cover' => 'https://images.unsplash.com/photo-1518837695005-2083093ee35b?auto=format&fit=crop&w=700&q=85', 'shelf' => 'Rak Cerita A4', 'dewey_code' => '813.8', 'stock' => 2, 'ebook' => true, 'ebook_path' => 'ebooks/ruang-baca-original-10.pdf', 'popular' => false],
        ];

        foreach (range(1, 10) as $number) {
            $filename = sprintf('ruang-baca-original-%02d.pdf', $number);
            $assetPath = database_path('seeders/assets/'.$filename);

            if (! File::exists($assetPath)) {
                throw new \RuntimeException('An original sample e-book asset is missing: '.$filename);
            }

            if (! Storage::disk('local')->put('ebooks/ruang-baca-original-'.sprintf('%02d', $number).'.pdf', File::get($assetPath))) {
                throw new \RuntimeException('An original sample e-book could not be stored: '.$filename);
            }
        }

        foreach ($books as $book) {
            Book::updateOrCreate(['isbn' => $book['isbn']], $book);
        }

        if (App::environment(['local', 'testing'])) {
            $this->call(TestAccountsSeeder::class);

            $demoEbookAsset = database_path('seeders/assets/ruang-baca-demo.pdf');

            if (! File::exists($demoEbookAsset)) {
                throw new \RuntimeException('The demo e-book PDF asset is missing.');
            }

            $demoEbookPath = 'ebooks/ruang-baca-demo.pdf';
            if (! Storage::disk('local')->put($demoEbookPath, File::get($demoEbookAsset))) {
                throw new \RuntimeException('The demo e-book PDF could not be stored on the private disk.');
            }

            Book::updateOrCreate(
                ['isbn' => 'DEMO-READER-2026'],
                [
                    'title' => 'Senja di Peron Terakhir',
                    'author' => 'Ruang Baca Demo',
                    'publisher' => 'Ruang Baca',
                    'genre' => 'Fiksi',
                    'language' => 'Indonesia',
                    'published_year' => 2026,
                    'synopsis' => 'Cerita pendek orisinal untuk menguji tampilan dan kontrol reader e-book.',
                    'cover' => 'https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=700&q=85',
                    'shelf' => 'E-book demo',
                    'dewey_code' => '813.8',
                    'stock' => 0,
                    'ebook' => true,
                    'ebook_path' => $demoEbookPath,
                    'popular' => false,
                ],
            );

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

            MembershipSubscription::updateOrCreate(
                ['payment_order_id' => 'DEMO-READER-PREMIUM'],
                [
                    'user_id' => $demoUser->id,
                    'plan' => 'premium',
                    'billing_period' => 'monthly',
                    'amount' => config('membership.plans.premium.prices.monthly'),
                    'status' => 'active',
                    'starts_at' => today(),
                    'ends_at' => today()->addMonthNoOverflow(),
                    'merchandise_status' => 'not_eligible',
                    'payment_status' => 'paid',
                    'payment_paid_at' => now(),
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
