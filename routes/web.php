<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookRequestController;
use App\Http\Controllers\BookReviewController;
use App\Http\Controllers\EbookController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\MidtransWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LibraryController::class, 'home'])->name('home');
Route::get('/katalog', [LibraryController::class, 'catalog'])->name('catalog');
Route::get('/buku/{book}', [LibraryController::class, 'show'])->name('books.show');
Route::get('/buku/{book}/reader', [EbookController::class, 'show'])->middleware('auth')->name('books.ebook');
Route::get('/buku/{book}/reader/content', [EbookController::class, 'file'])->middleware('auth')->name('books.ebook.file');
Route::post('/peminjaman/{loan}/kesan-pesan', [BookReviewController::class, 'store'])->middleware(['auth', 'throttle:5,1'])->name('book-reviews.store');
Route::get('/layanan', [LibraryController::class, 'services'])->name('services');
Route::get('/membership', [MembershipController::class, 'index'])->name('membership.index');
Route::post('/payments/midtrans/notification', MidtransWebhookController::class)->name('payments.midtrans.notification');
Route::post('/kontak', [LibraryController::class, 'contact'])->middleware('throttle:10,1')->name('contact');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegistration'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.store');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');
    Route::redirect('/daftar-anggota', '/register')->name('members.register');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [LibraryController::class, 'dashboard'])->name('dashboard');
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/buku/{book}/pinjam', [LibraryController::class, 'borrow'])->name('books.borrow');
    Route::post('/buku/{book}/wishlist', [LibraryController::class, 'wishlist'])->name('books.wishlist');
    Route::post('/buku/{book}/reservasi', [ReservationController::class, 'store'])->name('reservations.store');
    Route::post('/peminjaman/{loan}/perpanjang', [LibraryController::class, 'renew'])->name('loans.renew');
    Route::post('/membership', [MembershipController::class, 'store'])->name('membership.store');
    Route::post('/usulan-buku', [BookRequestController::class, 'store'])->name('book-requests.store');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::post('/pengguna', [AdminController::class, 'storeUser'])->name('users.store');
        Route::put('/pengguna/{user}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/pengguna/{user}', [AdminController::class, 'deleteUser'])->name('users.delete');
        Route::post('/anggota/{member}/status', [AdminController::class, 'updateMemberStatus'])->name('members.status');
        Route::post('/buku', [AdminController::class, 'storeBook'])->name('books.store');
        Route::put('/buku/{book}', [AdminController::class, 'updateBook'])->name('books.update');
        Route::delete('/buku/{book}', [AdminController::class, 'deleteBook'])->name('books.delete');
        Route::post('/peminjaman', [AdminController::class, 'storeLoan'])->name('loans.store');
        Route::post('/peminjaman/tamu', [AdminController::class, 'storeGuestLoan'])->name('guest-loans.store');
        Route::post('/peminjaman/{loan}/kembali', [AdminController::class, 'returnBook'])->name('loans.return');
        Route::post('/membership/{membership}/merchandise', [AdminController::class, 'fulfillMembershipMerchandise'])->name('memberships.merchandise');
        Route::put('/usulan-buku/{bookRequest}', [AdminController::class, 'updateBookRequest'])->name('book-requests.update');
        Route::post('/ulasan/{bookReview}/setujui', [AdminController::class, 'approveBookReview'])->name('book-reviews.approve');
        Route::put('/reservasi/{reservation}', [AdminController::class, 'updateReservation'])->name('reservations.update');
    });
});
