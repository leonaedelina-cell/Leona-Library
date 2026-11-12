# Ruang Baca

Aplikasi perpustakaan berbasis Laravel dengan katalog buku, pendaftaran anggota otomatis, peminjaman, panel admin, serta pembaca PDF privat.

## Persyaratan

- PHP 8.3 atau lebih baru
- Composer
- Node.js dan npm
- Ekstensi PHP SQLite untuk konfigurasi database bawaan

## Menjalankan aplikasi

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item -ItemType File -Force database\database.sqlite
php artisan migrate --seed
npm.cmd install
npm.cmd run build
php artisan serve
```

Buka `http://127.0.0.1:8000`. Jika file `.env` sudah tersedia, lewati `Copy-Item` dan jangan menimpa konfigurasi lokal.

Untuk pengembangan frontend, jalankan `npm.cmd run dev` di terminal terpisah. Email reset password menggunakan mailer `log` secara bawaan; tautannya dapat diperiksa di `storage\logs\laravel.log`. Atur `MAIL_MAILER` dan variabel mail terkait pada `.env` untuk mengirim email sungguhan.

## Akun dan keanggotaan

- Buat akun melalui `/register` atau `/daftar-anggota`. Password wajib sedikitnya 10 karakter serta mengandung huruf dan angka.
- Pendaftaran langsung membuat akun anggota aktif. Admin tetap dapat memblokir akses anggota.
- Pengguna terdaftar dapat masuk melalui `/login`, mengelola profil dan password di `/profil`, serta melihat kartu QR anggota digital.
- Anggota dapat meminjam gratis hingga 3 buku bersamaan selama 14 hari. Paket di `/membership`: Basic Rp29.000/bulan atau Rp290.000/tahun (3 buku/14 hari); Premium Rp49.000/bulan atau Rp490.000/tahun (7 buku/21 hari).
- Pembayaran membership menggunakan Midtrans Snap. Paket aktif otomatis setelah webhook pembayaran yang valid diterima; pilihan metode bayar dikonfigurasi melalui `MIDTRANS_ENABLED_PAYMENTS`. Paket tahunan memperoleh toleransi denda 3 hari, hak mengusulkan buku, dan merchandise sederhana untuk membership tahunan pertama.
- Untuk sandbox, isi `MIDTRANS_SERVER_KEY` di `.env` dengan Server Key sandbox dari dashboard Midtrans. Jangan membagikan atau menyimpan key di source code. Gunakan `MIDTRANS_IS_PRODUCTION=true` beserta Server Key production saat siap produksi.
- Atur URL notifikasi pada dashboard Midtrans menjadi `https://alamat-aplikasi-anda/payments/midtrans/notification`. Endpoint ini harus dapat diakses dari internet melalui HTTPS agar pembayaran otomatis terkonfirmasi.
- E-book PDF privat memerlukan membership Premium aktif. Reservasi buku populer memprioritaskan Premium; anggota dengan paket tahunan dapat mengirim usulan pengadaan buku.
- Untuk pengujian lokal, seeder membuat anggota aktif `alya@ruangbaca.id` dengan password `RuangBaca2026!`. Akun ini hanya dibuat pada environment `local` dan `testing`; jangan gunakan untuk production.
- Tidak ada akun admin bawaan. Daftarkan akun, lalu promosikan melalui terminal:

```powershell
php artisan library:make-admin email-anda@example.com
```

Setelah itu buka `/admin`. Hanya akun dengan peran admin yang dapat mengakses halaman maupun aksi pengelolaan.

## Fitur pengelola

- Kelola akun, peran, blokir akses anggota, dan koleksi buku.
- Pantau status pembayaran membership, tandai merchandise tahunan telah diserahkan, dan tindaklanjuti usulan buku serta reservasi dalam urutan prioritas.
- Unggah PDF hingga 20 MB. File disimpan di disk privat dan hanya bisa dibaca oleh anggota dengan membership Premium aktif.
- Catat peminjaman menggunakan nomor anggota yang dipindai atau dimasukkan manual; kamera memerlukan izin browser dan HTTPS atau localhost. Jika pemindaian kamera tidak didukung, gunakan input manual.
- Catat pengembalian dan hitung denda keterlambatan otomatis.

## Pengujian

```powershell
php artisan test
vendor\bin\pint --test
npm.cmd run build
```

Tes menggunakan database terisolasi dan tidak mengubah koleksi pada database aplikasi.
