<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookReview;
use App\Models\ContactMessage;
use App\Models\Loan;
use App\Models\Member;
use App\Models\MembershipSubscription;
use App\Models\Reservation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_and_catalog_render_seeded_books(): void
    {
        $book = $this->createBook();

        $this->get('/')
            ->assertOk()
            ->assertSee('Setiap halaman')
            ->assertSee($book->title);

        $this->get('/katalog?q=laut')
            ->assertOk()
            ->assertSee($book->title);

        $this->get('/katalog?q=judul-yang-tidak-ada')
            ->assertOk()
            ->assertSee('Belum ketemu ceritanya.');
    }

    public function test_guest_can_view_login_registration_and_password_recovery_pages(): void
    {
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
        $this->get(route('password.request'))->assertOk();
    }

    public function test_member_can_borrow_a_book_and_extend_the_due_date(): void
    {
        $book = $this->createBook();
        $member = $this->createActiveMember();
        $this->actingAs($member->user);

        $this->post(route('books.borrow', $book), [
            'method' => 'pickup',
        ])->assertRedirect(route('dashboard'));

        $loan = Loan::firstOrFail();
        $originalDueDate = $loan->due_at->copy();

        $this->assertSame(now()->addDays(14)->toDateString(), $originalDueDate->toDateString());
        $this->get(route('dashboard'))->assertOk()->assertSee($book->title);

        $this->post(route('loans.renew', $loan))->assertRedirect();
        $this->assertSame($originalDueDate->addDays(7)->toDateString(), $loan->fresh()->due_at->toDateString());
        $this->assertSame(1, $loan->fresh()->renewals);

        $this->from(route('dashboard'))
            ->post(route('loans.renew', $loan))
            ->assertSessionHasErrors('loan');

        $this->assertSame(1, $loan->fresh()->renewals);
        $this->assertSame(0, $book->fresh()->availableCopies());
    }

    public function test_catalog_filters_out_books_with_no_physical_copies(): void
    {
        $book = $this->createBook(['stock' => 1]);
        Loan::create([
            'book_id' => $book->id,
            'member_name' => 'Alya Putri',
            'member_number' => 'LIB-2024-0042',
            'due_at' => today()->addDays(14),
            'status' => 'active',
            'method' => 'pickup',
        ]);

        $this->get('/katalog?availability=available')
            ->assertOk()
            ->assertDontSee($book->title);
    }

    public function test_member_cannot_borrow_more_than_three_books_at_once(): void
    {
        $book = $this->createBook(['stock' => 4]);
        $member = $this->createActiveMember();
        $this->actingAs($member->user);

        foreach (range(1, 3) as $number) {
            Loan::create([
                'book_id' => $book->id,
                'member_name' => 'Alya Putri',
                'member_number' => 'LIB-2024-0042',
                'due_at' => today()->addDays(14),
                'status' => 'active',
                'method' => 'pickup',
            ]);
        }

        $this->from(route('books.show', $book))
            ->post(route('books.borrow', $book), [
                'method' => 'pickup',
            ])
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHasErrors('borrow');

        $this->assertSame(3, Loan::count());
    }

    public function test_member_can_submit_one_pending_review_for_a_returned_ebook_loan(): void
    {
        $book = $this->createBook(['ebook' => true, 'ebook_path' => 'ebooks/laut-bercerita.pdf']);
        $member = $this->createActiveMember();
        $loan = $this->createLoan($member, $book, 'returned');

        $this->actingAs($member->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('kesanmu.')
            ->assertSee('Bagian mana yang paling lama tinggal di pikiranmu?');

        $this->post(route('book-reviews.store', $loan), [
            'mood' => 'terharu',
            'content' => 'Kisah ini meninggalkan rasa hangat dan terus teringat setelah halaman terakhir.',
            'is_spoiler' => '1',
            'status' => 'approved',
        ])->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('book_reviews', [
            'loan_id' => $loan->id,
            'user_id' => $member->user->id,
            'book_id' => $book->id,
            'mood' => 'terharu',
            'is_spoiler' => true,
            'status' => 'pending',
        ]);

        $this->post(route('book-reviews.store', $loan), [
            'mood' => 'hangat',
            'content' => 'Ada banyak perasaan yang masih ingin saya ceritakan tentang buku ini.',
        ])->assertSessionHasErrors('review');

        $this->assertDatabaseCount('book_reviews', 1);
    }

    public function test_member_cannot_review_an_active_loan(): void
    {
        $book = $this->createBook(['ebook' => true, 'ebook_path' => 'ebooks/laut-bercerita.pdf']);
        $member = $this->createActiveMember();
        $loan = $this->createLoan($member, $book, 'active');

        $this->actingAs($member->user)
            ->from(route('dashboard'))
            ->post(route('book-reviews.store', $loan), [
                'mood' => 'hangat',
                'content' => 'Ada banyak perasaan yang ingin saya ceritakan tentang buku ini.',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors([
                'review' => 'Kesan dan pesan hanya dapat dikirim setelah peminjaman selesai dibaca.',
            ]);

        $this->assertDatabaseCount('book_reviews', 0);
    }

    public function test_member_cannot_review_a_returned_physical_loan(): void
    {
        $book = $this->createBook();
        $member = $this->createActiveMember();
        $loan = $this->createLoan($member, $book, 'returned');

        $this->actingAs($member->user)
            ->from(route('dashboard'))
            ->post(route('book-reviews.store', $loan), [
                'mood' => 'hangat',
                'content' => 'Ada banyak perasaan yang ingin saya ceritakan tentang buku ini.',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors([
                'review' => 'Kesan dan pesan hanya tersedia untuk e-book yang telah selesai dibaca.',
            ]);

        $this->assertDatabaseCount('book_reviews', 0);
    }

    public function test_member_cannot_submit_a_review_for_another_members_loan(): void
    {
        $book = $this->createBook(['ebook' => true, 'ebook_path' => 'ebooks/laut-bercerita.pdf']);
        $owner = $this->createActiveMember();
        $loan = $this->createLoan($owner, $book, 'returned');
        $otherUser = User::create([
            'name' => 'Pembaca Lain',
            'email' => 'pembaca-lain@example.com',
            'password' => 'SecretPassword2026',
            'role' => 'member',
        ]);
        Member::create([
            'user_id' => $otherUser->id,
            'member_number' => 'LIB-2024-0043',
            'name' => $otherUser->name,
            'email' => $otherUser->email,
            'status' => 'active',
            'joined_at' => '2024-05-01',
        ]);

        $this->actingAs($otherUser)
            ->post(route('book-reviews.store', $loan), [
                'mood' => 'hangat',
                'content' => 'Ada banyak perasaan yang ingin saya ceritakan tentang buku ini.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('book_reviews', 0);
    }

    public function test_member_cannot_submit_a_review_containing_profanity(): void
    {
        $book = $this->createBook(['ebook' => true, 'ebook_path' => 'ebooks/laut-bercerita.pdf']);
        $member = $this->createActiveMember();
        $loan = $this->createLoan($member, $book, 'returned');

        $this->actingAs($member->user)
            ->from(route('dashboard'))
            ->post(route('book-reviews.store', $loan), [
                'mood' => 'hangat',
                'content' => 'Buku ini goblok dan penuh cerita yang tidak menarik.',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors([
                'content' => 'Gunakan bahasa yang sopan agar ulasan dapat dikirim.',
            ]);

        $this->assertDatabaseCount('book_reviews', 0);
    }

    public function test_admin_can_approve_spoiler_review_and_only_approved_reviews_are_public(): void
    {
        $book = $this->createBook(['ebook' => true, 'ebook_path' => 'ebooks/laut-bercerita.pdf']);
        $member = $this->createActiveMember();
        $loan = $this->createLoan($member, $book, 'returned');
        $review = BookReview::create([
            'loan_id' => $loan->id,
            'user_id' => $member->user->id,
            'book_id' => $book->id,
            'mood' => 'terharu',
            'content' => "Kisah ini tinggal di pikiranku. <script>alert('x')</script>",
            'is_spoiler' => true,
            'status' => 'pending',
        ]);

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertDontSee('Kisah ini tinggal di pikiranku.');

        $this->actingAs($this->createAdmin());

        $this->get(route('admin.index'))
            ->assertOk()
            ->assertSee('MODERASI PEMBACA')
            ->assertSee('Kisah ini tinggal di pikiranku.');

        $this->post(route('admin.book-reviews.approve', $review))
            ->assertRedirect(route('admin.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('book_reviews', [
            'id' => $review->id,
            'status' => 'approved',
        ]);

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('Spoiler alert · buka jika siap')
            ->assertSee('Kisah ini tinggal di pikiranku.')
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee("<script>alert('x')</script>", false);
    }

    public function test_member_cannot_approve_pending_reviews(): void
    {
        $book = $this->createBook(['ebook' => true, 'ebook_path' => 'ebooks/laut-bercerita.pdf']);
        $member = $this->createActiveMember();
        $loan = $this->createLoan($member, $book, 'returned');
        $review = BookReview::create([
            'loan_id' => $loan->id,
            'user_id' => $member->user->id,
            'book_id' => $book->id,
            'mood' => 'hangat',
            'content' => 'Ada banyak perasaan yang ingin saya ceritakan tentang buku ini.',
            'status' => 'pending',
        ]);

        $this->actingAs($member->user)
            ->post(route('admin.book-reviews.approve', $review))
            ->assertForbidden();

        $this->assertDatabaseHas('book_reviews', [
            'id' => $review->id,
            'status' => 'pending',
        ]);
    }

    public function test_new_members_are_active_immediately_and_blocked_members_cannot_borrow(): void
    {
        $book = $this->createBook(['stock' => 2]);

        $this->post(route('register.store'), [
            'name' => 'Nadia Ristivani',
            'email' => 'nadia@example.com',
            'password' => 'BukuHangat2026',
            'password_confirmation' => 'BukuHangat2026',
        ])->assertRedirect(route('dashboard'));

        $member = Member::where('email', 'nadia@example.com')->firstOrFail();
        $this->assertSame('active', $member->status);
        $this->assertSame('member', $member->user->role);
        $this->assertNotSame('BukuHangat2026', $member->user->password);

        $borrowData = [
            'method' => 'pickup',
        ];

        $this->post(route('books.borrow', $book), $borrowData)
            ->assertRedirect(route('dashboard'));

        $this->actingAs($this->createAdmin());

        $this->actingAs(User::where('role', 'admin')->firstOrFail());
        $this->post(route('admin.members.status', $member), ['status' => 'blocked'])
            ->assertSessionHas('success');

        $book->update(['stock' => 3]);
        $this->actingAs($member->user);
        $this->from(route('books.show', $book))
            ->post(route('books.borrow', $book), $borrowData)
            ->assertSessionHasErrors('borrow');

        $this->from(route('membership.index'))
            ->post(route('membership.store'), ['plan' => 'basic', 'billing_period' => 'monthly'])
            ->assertSessionHasErrors('membership');
    }

    public function test_contact_form_is_saved_and_wishlist_is_available_to_the_member(): void
    {
        $book = $this->createBook();
        $member = $this->createActiveMember();
        $this->actingAs($member->user);

        $this->post(route('books.wishlist', $book))->assertRedirect();
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee($book->title);

        $this->post(route('contact'), [
            'name' => 'Alya Putri',
            'email' => 'alya@example.com',
            'message' => 'Tolong tambahkan lebih banyak buku fiksi.',
        ])->assertRedirect();

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'alya@example.com',
            'message' => 'Tolong tambahkan lebih banyak buku fiksi.',
        ]);
        $this->assertInstanceOf(ContactMessage::class, ContactMessage::first());
    }

    public function test_administrator_can_create_a_book_and_record_its_return(): void
    {
        Storage::fake('local');
        $this->actingAs($this->createAdmin());

        $this->post(route('admin.books.store'), [
            'title' => 'Cerita Baru',
            'author' => 'Penulis Lokal',
            'genre' => 'Fiksi',
            'language' => 'Indonesia',
            'published_year' => 2025,
            'stock' => 2,
            'ebook_file' => UploadedFile::fake()->createWithContent('sample.pdf', '%PDF-1.4 sample content'),
            'popular' => '1',
        ])->assertRedirect();

        $book = Book::where('title', 'Cerita Baru')->firstOrFail();
        $this->assertTrue($book->ebook);
        $this->assertTrue($book->popular);
        Storage::disk('local')->assertExists($book->ebook_path);

        $loan = Loan::create([
            'book_id' => $book->id,
            'member_name' => 'Alya Putri',
            'member_number' => 'LIB-2024-0042',
            'due_at' => today()->subDays(2),
            'status' => 'active',
            'method' => 'pickup',
        ]);

        $this->post(route('admin.loans.return', $loan))
            ->assertRedirect()
            ->assertSessionHas('success', 'Pengembalian dicatat. Denda keterlambatan Rp2.000.');

        $this->assertSame('returned', $loan->fresh()->status);
        $this->assertSame(2000, $loan->fresh()->fine);
    }

    public function test_admin_panel_and_write_routes_reject_guests_and_members(): void
    {
        $this->get(route('admin.index'))->assertRedirect(route('login'));
        $this->post(route('admin.books.store'))->assertRedirect(route('login'));

        $member = $this->createActiveMember();
        $this->actingAs($member->user);
        $this->get(route('admin.index'))->assertForbidden();
        $this->post(route('admin.books.store'))->assertForbidden();
    }

    public function test_registered_member_can_log_in_view_and_update_profile_and_password(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Sari Puspita',
            'email' => 'sari@example.com',
            'password' => 'CeritaIndah2026',
            'password_confirmation' => 'CeritaIndah2026',
        ])->assertRedirect(route('dashboard'));

        $user = User::where('email', 'sari@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->get(route('profile.edit'))->assertOk()->assertSee($user->member->member_number);
        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();

        $this->post(route('login.store'), [
            'email' => 'SARI@EXAMPLE.COM',
            'password' => 'CeritaIndah2026',
        ])->assertRedirect(route('dashboard'));

        $this->put(route('profile.update'), [
            'name' => 'Sari Puspita Baru',
            'email' => 'SARI.BARU@EXAMPLE.COM',
        ])->assertSessionHas('success');
        $this->assertSame('sari.baru@example.com', $user->fresh()->email);

        $this->put(route('profile.password'), [
            'current_password' => 'CeritaIndah2026',
            'password' => 'PasswordBaru2026',
            'password_confirmation' => 'PasswordBaru2026',
        ])->assertSessionHas('success');

        $this->post(route('logout'));
        $this->post(route('login.store'), [
            'email' => 'sari.baru@example.com',
            'password' => 'PasswordBaru2026',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_user_cannot_extend_another_members_loan(): void
    {
        $book = $this->createBook();
        $member = $this->createActiveMember();
        $otherMember = Member::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'member_number' => 'LIB-2025-0002',
            'status' => 'active',
            'joined_at' => '2025-01-01',
        ]);
        $loan = Loan::create([
            'book_id' => $book->id,
            'member_name' => $otherMember->name,
            'member_number' => $otherMember->member_number,
            'due_at' => today()->addDays(7),
            'status' => 'active',
            'method' => 'pickup',
        ]);

        $this->actingAs($member->user)
            ->post(route('loans.renew', $loan))
            ->assertForbidden();

        $this->assertSame(0, $loan->fresh()->renewals);
    }

    public function test_member_cannot_extend_a_loan_reserved_by_another_member(): void
    {
        $book = $this->createBook();
        $member = $this->createActiveMember();
        $otherMember = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'SecretPassword2026',
            'role' => 'member',
        ]);
        Reservation::create([
            'user_id' => $otherMember->id,
            'book_id' => $book->id,
            'priority' => 0,
            'status' => 'pending',
        ]);
        $loan = Loan::create([
            'book_id' => $book->id,
            'member_name' => $member->name,
            'member_number' => $member->member_number,
            'due_at' => today()->addDays(3),
            'status' => 'active',
            'method' => 'pickup',
        ]);

        $this->actingAs($member->user)
            ->from(route('dashboard'))
            ->post(route('loans.renew', $loan))
            ->assertSessionHasErrors('loan');

        $this->assertSame(0, $loan->fresh()->renewals);
    }

    public function test_admin_can_create_update_and_delete_accounts_and_prevent_self_deletion(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);
        $this->get(route('admin.index'))->assertOk();

        $this->post(route('admin.users.store'), [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'KoleksiBuku2026',
            'password_confirmation' => 'KoleksiBuku2026',
            'role' => 'member',
        ])->assertSessionHas('success');

        $user = User::where('email', 'budi@example.com')->firstOrFail();
        $this->assertSame('active', $user->member->status);
        $this->assertNotSame('KoleksiBuku2026', $user->password);

        $this->put(route('admin.users.update', $user), [
            'name' => 'Budi Santoso Baru',
            'email' => 'budi.baru@example.com',
            'role' => 'member',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('members', ['user_id' => $user->id, 'status' => 'active']);
        $this->post(route('admin.members.status', $user->member), ['status' => 'blocked'])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('members', ['user_id' => $user->id, 'status' => 'blocked']);

        $this->delete(route('admin.users.delete', $admin))->assertSessionHasErrors('user');
        $this->delete(route('admin.users.delete', $user))->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_registration_rejects_an_existing_email_regardless_of_case(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Sari Puspita',
            'email' => 'sari@example.com',
            'password' => 'CeritaIndah2026',
            'password_confirmation' => 'CeritaIndah2026',
        ])->assertRedirect(route('dashboard'));

        $this->post(route('logout'));

        $this->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Sari Puspita Lain',
                'email' => 'Sari@Example.com',
                'password' => 'CeritaIndah2026',
                'password_confirmation' => 'CeritaIndah2026',
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame(1, User::where('email', 'sari@example.com')->count());
    }

    public function test_admin_can_record_a_loan_using_a_member_number_from_the_scanner(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createActiveMember();
        $book = $this->createBook(['stock' => 2]);

        $this->actingAs($admin)
            ->post(route('admin.loans.store'), [
                'member_number' => $member->member_number,
                'book_id' => $book->id,
                'method' => 'pickup',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('loans', [
            'member_number' => $member->member_number,
            'book_id' => $book->id,
            'status' => 'active',
        ]);
    }

    public function test_uploaded_ebook_requires_a_premium_membership_and_non_blocked_member(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('ebooks/sample.pdf', '%PDF-1.4 test document');
        $book = $this->createBook(['ebook_path' => 'ebooks/sample.pdf']);

        $this->get(route('books.ebook.file', $book))->assertRedirect(route('login'));

        $member = $this->createActiveMember();
        $this->actingAs($member->user)
            ->get(route('books.ebook.file', $book))
            ->assertForbidden();

        $this->createMembership($member->user, 'premium');
        $this->actingAs($member->user->fresh())
            ->get(route('books.ebook', $book))
            ->assertOk()
            ->assertViewIs('ebook.reader')
            ->assertSee('data-pdf-reader', false)
            ->assertSee('data-reader-next', false)
            ->assertSee('data-reader-theme="dark"', false)
            ->assertSee(route('books.ebook.file', $book), false)
            ->assertDontSee('<iframe', false)
            ->assertDontSee('download', false);

        $this->get(route('books.ebook.file', $book))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename="ruang-baca-'.$book->id.'.pdf"')
            ->assertHeaderContains('cache-control', 'private')
            ->assertHeaderContains('cache-control', 'no-store');

        $member->update(['status' => 'blocked']);
        $this->actingAs($member->user->fresh());
        $this->get(route('books.ebook.file', $book))->assertForbidden();
    }

    public function test_demo_ebook_is_seeded_on_private_storage_for_the_premium_demo_member(): void
    {
        Storage::fake('local');

        $this->seed(DatabaseSeeder::class);

        $book = Book::where('isbn', 'DEMO-READER-2026')->firstOrFail();
        $demoUser = User::where('email', 'alya@ruangbaca.id')->firstOrFail();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'ebook' => true,
            'ebook_path' => 'ebooks/ruang-baca-demo.pdf',
        ]);
        Storage::disk('local')->assertExists($book->ebook_path);
        $this->assertSame('premium', $demoUser->currentMembership()?->plan);

        $this->actingAs($demoUser)
            ->get(route('books.ebook', $book))
            ->assertOk()
            ->assertSee($book->title)
            ->assertSee($demoUser->email);
    }

    public function test_member_can_start_midtrans_checkout_but_only_paid_notification_activates_membership(): void
    {
        $member = $this->createActiveMember();
        $admin = $this->createAdmin();
        $serverKey = 'SB-Mid-server-test-key';
        $paymentUrl = 'https://app.sandbox.midtrans.com/snap/v2/vtweb/fake-token';

        config([
            'services.midtrans.server_key' => $serverKey,
            'services.midtrans.snap_base_url' => 'https://app.sandbox.midtrans.com',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'fake-token',
                'redirect_url' => $paymentUrl,
            ], 201),
        ]);

        $this->get(route('membership.index'))
            ->assertOk()
            ->assertSee('Rp29.000')
            ->assertSee('Rp290.000')
            ->assertSee('Rp49.000')
            ->assertSee('Rp490.000');

        $this->actingAs($member->user)
            ->post(route('membership.store'), ['plan' => 'basic', 'billing_period' => 'yearly'])
            ->assertRedirect($paymentUrl);

        $membership = MembershipSubscription::firstOrFail();
        $this->assertDatabaseHas('membership_subscriptions', [
            'id' => $membership->id,
            'user_id' => $member->user_id,
            'plan' => 'basic',
            'billing_period' => 'yearly',
            'amount' => 290000,
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_url' => $paymentUrl,
        ]);
        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'https://app.sandbox.midtrans.com/snap/v1/transactions'
            && $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Basic '.base64_encode($serverKey.':'))
            && $request->data()['transaction_details']['gross_amount'] === 290000
            && $request->data()['enabled_payments'] === ['qris', 'gopay', 'shopeepay', 'bank_transfer', 'credit_card']);

        $this->actingAs($member->user)
            ->from(route('membership.index'))
            ->post(route('membership.store'), ['plan' => 'premium', 'billing_period' => 'monthly'])
            ->assertRedirect($paymentUrl);

        $this->assertSame(1, MembershipSubscription::count());

        $this->actingAs($admin);
        $this->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Menunggu pembayaran')
            ->assertDontSee('Konfirmasi & aktifkan');

        $this->postJson(route('payments.midtrans.notification'), $this->midtransNotification($membership, [
            'transaction_status' => 'capture',
            'fraud_status' => 'challenge',
        ]))->assertOk();
        $this->assertSame('pending', $membership->fresh()->status);

        $this->postJson(route('payments.midtrans.notification'), $this->midtransNotification($membership))
            ->assertOk();

        $this->assertDatabaseHas('membership_subscriptions', [
            'id' => $membership->id,
            'status' => 'active',
            'payment_status' => 'paid',
            'payment_transaction_id' => 'midtrans-transaction-1',
            'merchandise_status' => 'pending',
        ]);
        $this->assertSame(today()->toDateString(), $membership->fresh()->starts_at->toDateString());
        $this->assertSame(today()->addYearNoOverflow()->toDateString(), $membership->fresh()->ends_at->toDateString());

        $this->actingAs($admin);
        $this->post(route('admin.memberships.merchandise', $membership))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('membership_subscriptions', [
            'id' => $membership->id,
            'merchandise_status' => 'fulfilled',
        ]);
    }

    public function test_returns_403_for_invalid_midtrans_signature_or_invoice_amount(): void
    {
        $member = $this->createActiveMember();
        config(['services.midtrans.server_key' => 'SB-Mid-server-test-key']);
        $membership = MembershipSubscription::create([
            'user_id' => $member->user_id,
            'plan' => 'basic',
            'billing_period' => 'monthly',
            'amount' => 29000,
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_order_id' => 'RB-test-order',
        ]);
        $invalidSignature = $this->midtransNotification($membership);
        $invalidSignature['signature_key'] = str_repeat('0', 128);

        $this->postJson(route('payments.midtrans.notification'), $invalidSignature)
            ->assertForbidden();

        $this->postJson(route('payments.midtrans.notification'), $this->midtransNotification($membership, [
            'gross_amount' => '29001.00',
        ]))->assertForbidden();

        $this->assertDatabaseHas('membership_subscriptions', [
            'id' => $membership->id,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }

    public function test_midtrans_failed_payment_does_not_activate_membership_and_can_be_retried(): void
    {
        $member = $this->createActiveMember();
        config(['services.midtrans.server_key' => 'SB-Mid-server-test-key']);
        Http::fake([
            'app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/fake-token',
            ], 201),
        ]);

        $this->actingAs($member->user)
            ->post(route('membership.store'), ['plan' => 'premium', 'billing_period' => 'monthly'])
            ->assertRedirect();
        $membership = MembershipSubscription::firstOrFail();

        $this->postJson(route('payments.midtrans.notification'), $this->midtransNotification($membership, [
            'status_code' => '407',
            'transaction_status' => 'expire',
        ]))->assertOk();

        $this->assertDatabaseHas('membership_subscriptions', [
            'id' => $membership->id,
            'status' => 'rejected',
            'payment_status' => 'expired',
        ]);
        $this->assertNull($member->user->currentMembership());

        $this->actingAs($member->user)
            ->post(route('membership.store'), ['plan' => 'premium', 'billing_period' => 'monthly'])
            ->assertRedirect();
        $this->assertSame(2, MembershipSubscription::count());
    }

    public function test_refund_notification_deactivates_a_paid_membership(): void
    {
        $member = $this->createActiveMember();
        config(['services.midtrans.server_key' => 'SB-Mid-server-test-key']);
        $membership = $this->createMembership($member->user, 'premium');
        $membership->update([
            'payment_order_id' => 'RB-refund-test',
            'payment_status' => 'paid',
        ]);

        $this->postJson(route('payments.midtrans.notification'), $this->midtransNotification($membership, [
            'transaction_status' => 'refund',
        ]))->assertOk();

        $this->assertDatabaseHas('membership_subscriptions', [
            'id' => $membership->id,
            'status' => 'rejected',
            'payment_status' => 'refunded',
        ]);
        $this->assertNull($member->user->currentMembership());
    }

    public function test_checkout_failure_is_reported_without_leaving_a_pending_invoice(): void
    {
        $member = $this->createActiveMember();
        config(['services.midtrans.server_key' => null]);

        $this->actingAs($member->user)
            ->from(route('membership.index'))
            ->post(route('membership.store'), ['plan' => 'basic', 'billing_period' => 'monthly'])
            ->assertRedirect(route('membership.index'))
            ->assertSessionHasErrors('membership');

        $this->assertDatabaseHas('membership_subscriptions', [
            'user_id' => $member->user_id,
            'status' => 'rejected',
            'payment_status' => 'failed',
        ]);
    }

    public function test_rejects_checkout_redirects_outside_midtrans(): void
    {
        $member = $this->createActiveMember();
        config(['services.midtrans.server_key' => 'SB-Mid-server-test-key']);
        Http::preventStrayRequests();
        Http::fake([
            'app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'redirect_url' => 'https://attacker.example/fake-payment',
            ], 201),
        ]);

        $this->actingAs($member->user)
            ->from(route('membership.index'))
            ->post(route('membership.store'), ['plan' => 'basic', 'billing_period' => 'monthly'])
            ->assertRedirect(route('membership.index'))
            ->assertSessionHasErrors('membership');

        $this->assertDatabaseHas('membership_subscriptions', [
            'user_id' => $member->user_id,
            'status' => 'rejected',
            'payment_status' => 'failed',
            'payment_url' => null,
        ]);
    }

    public function test_active_membership_tier_is_shown_by_the_profile_link_only_while_active(): void
    {
        $member = $this->createActiveMember();
        $membership = $this->createMembership($member->user, 'premium');
        $this->actingAs($member->user);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-membership-tier="premium"', false)
            ->assertSee('✦ Premium');

        $membership->update(['ends_at' => today()->subDay()]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('data-membership-tier', false);
    }

    public function test_premium_membership_increases_concurrent_loan_limit_and_duration(): void
    {
        $book = $this->createBook(['stock' => 8]);
        $member = $this->createActiveMember();
        $this->createMembership($member->user, 'premium');
        $this->actingAs($member->user);

        foreach (range(1, 6) as $number) {
            Loan::create([
                'book_id' => $book->id,
                'member_name' => $member->name,
                'member_number' => $member->member_number,
                'due_at' => today()->addDays(21),
                'status' => 'active',
                'method' => 'pickup',
            ]);
        }

        $this->post(route('books.borrow', $book), ['method' => 'pickup'])
            ->assertRedirect(route('dashboard'));

        $newestLoan = Loan::where('member_number', $member->member_number)->latest()->firstOrFail();
        $this->assertSame(today()->addDays(21)->toDateString(), $newestLoan->due_at->toDateString());

        $this->from(route('books.show', $book))
            ->post(route('books.borrow', $book), ['method' => 'pickup'])
            ->assertSessionHasErrors('borrow');

        $this->assertSame(7, Loan::where('member_number', $member->member_number)->where('status', 'active')->count());
    }

    public function test_annual_membership_applies_three_free_late_days(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createActiveMember();
        $this->createMembership($member->user, 'basic', 'yearly');
        $book = $this->createBook();
        $loan = Loan::create([
            'book_id' => $book->id,
            'member_name' => $member->name,
            'member_number' => $member->member_number,
            'due_at' => today()->subDays(5),
            'status' => 'active',
            'method' => 'pickup',
            'late_fee_free_days' => 3,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.loans.return', $loan))
            ->assertSessionHas('success', 'Pengembalian dicatat. Denda keterlambatan Rp2.000.');

        $this->assertSame(2000, $loan->fresh()->fine);
    }

    public function test_annual_member_can_submit_a_book_request_and_admin_can_update_it(): void
    {
        $member = $this->createActiveMember();
        $admin = $this->createAdmin();

        $this->actingAs($member->user)
            ->post(route('book-requests.store'), [
                'title' => 'Bumi Manusia',
                'author' => 'Pramoedya Ananta Toer',
            ])
            ->assertSessionHasErrors('book_request');

        $this->assertDatabaseMissing('book_requests', ['user_id' => $member->user_id]);

        $this->createMembership($member->user, 'basic', 'yearly');

        $this->actingAs($member->user)
            ->post(route('book-requests.store'), [
                'title' => 'Bumi Manusia',
                'author' => 'Pramoedya Ananta Toer',
                'notes' => 'Edisi terbaru jika tersedia.',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('book_requests', [
            'user_id' => $member->user_id,
            'title' => 'Bumi Manusia',
            'status' => 'pending',
        ]);

        $bookRequest = $member->user->bookRequests()->firstOrFail();
        $this->actingAs($admin)
            ->put(route('admin.book-requests.update', $bookRequest), [
                'status' => 'acquired',
                'admin_note' => 'Buku tersedia mulai pekan ini.',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('book_requests', [
            'id' => $bookRequest->id,
            'status' => 'acquired',
            'admin_note' => 'Buku tersedia mulai pekan ini.',
        ]);
    }

    public function test_premium_members_are_prioritized_in_popular_book_reservations(): void
    {
        $book = $this->createBook(['stock' => 1, 'popular' => true]);
        $regularMember = $this->createActiveMember();
        $premiumUser = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'SecretPassword2026',
            'role' => 'member',
        ]);
        $premiumMember = Member::create([
            'user_id' => $premiumUser->id,
            'name' => $premiumUser->name,
            'email' => $premiumUser->email,
            'member_number' => 'LIB-2025-0002',
            'status' => 'active',
            'joined_at' => today(),
        ]);
        $this->createMembership($premiumUser, 'premium');
        Loan::create([
            'book_id' => $book->id,
            'member_name' => 'Peminjam Lama',
            'member_number' => 'LIB-OLD-0001',
            'due_at' => today()->addDays(14),
            'status' => 'active',
            'method' => 'pickup',
        ]);

        $this->actingAs($regularMember->user)
            ->post(route('reservations.store', $book))
            ->assertSessionHas('success');
        $this->actingAs($premiumUser)
            ->post(route('reservations.store', $book))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reservations', [
            'user_id' => $regularMember->user_id,
            'book_id' => $book->id,
            'priority' => 0,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('reservations', [
            'user_id' => $premiumUser->id,
            'book_id' => $book->id,
            'priority' => 1,
            'status' => 'pending',
        ]);

        $admin = $this->createAdmin();
        $this->actingAs($admin)->post(route('admin.loans.return', Loan::firstOrFail()))
            ->assertSessionHas('success');

        $premiumReservation = Reservation::where('user_id', $premiumUser->id)->firstOrFail();
        $this->assertSame('ready', $premiumReservation->status);

        $this->actingAs($regularMember->user)
            ->from(route('books.show', $book))
            ->post(route('books.borrow', $book), ['method' => 'pickup'])
            ->assertSessionHasErrors('borrow');

        $this->actingAs($premiumUser)
            ->post(route('books.borrow', $book), ['method' => 'pickup'])
            ->assertRedirect(route('dashboard'));

        $this->assertSame('fulfilled', $premiumReservation->fresh()->status);
    }

    public function test_admin_can_upload_a_real_pdf_and_invalid_files_are_rejected(): void
    {
        Storage::fake('local');
        $this->actingAs($this->createAdmin());

        $this->post(route('admin.books.store'), [
            'title' => 'Buku Digital',
            'author' => 'Penulis Lokal',
            'genre' => 'Fiksi',
            'language' => 'Indonesia',
            'published_year' => 2025,
            'stock' => 1,
            'ebook_file' => UploadedFile::fake()->createWithContent('sample.pdf', '%PDF-1.4 sample content'),
        ])->assertSessionHas('success');

        $book = Book::where('title', 'Buku Digital')->firstOrFail();
        $this->assertNotEmpty($book->ebook_path);
        Storage::disk('local')->assertExists($book->ebook_path);

        $this->from(route('admin.index'))
            ->post(route('admin.books.store'), [
                'title' => 'File Tidak Valid',
                'author' => 'Penulis Lokal',
                'genre' => 'Fiksi',
                'language' => 'Indonesia',
                'published_year' => 2025,
                'stock' => 1,
                'ebook_file' => UploadedFile::fake()->createWithContent('sample.txt', 'not a PDF'),
            ])
            ->assertSessionHasErrors('ebook_file');
    }

    public function test_admin_can_replace_and_remove_a_private_ebook(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('ebooks/old.pdf', '%PDF-1.4 old document');
        $book = $this->createBook(['ebook' => true, 'ebook_path' => 'ebooks/old.pdf']);
        $this->actingAs($this->createAdmin());

        $bookData = [
            'title' => 'Laut Bercerita',
            'author' => 'Leila S. Chudori',
            'publisher' => 'Penerbit Cerita',
            'isbn' => '9786024246945',
            'genre' => 'Fiksi',
            'language' => 'Indonesia',
            'published_year' => 2020,
            'synopsis' => 'Sebuah kisah hangat.',
            'cover' => 'https://example.com/book.jpg',
            'shelf' => 'Rak A1',
            'dewey_code' => '813.3',
            'stock' => 1,
        ];

        $this->put(route('admin.books.update', $book), [
            ...$bookData,
            'ebook_file' => UploadedFile::fake()->createWithContent('replacement.pdf', '%PDF-1.4 replacement'),
        ])->assertSessionHas('success');

        $book->refresh();
        $this->assertNotSame('ebooks/old.pdf', $book->ebook_path);
        Storage::disk('local')->assertMissing('ebooks/old.pdf');
        Storage::disk('local')->assertExists($book->ebook_path);

        $this->put(route('admin.books.update', $book), [
            ...$bookData,
            'remove_ebook' => '1',
        ])->assertSessionHas('success');

        $book->refresh();
        $this->assertNull($book->ebook_path);
        $this->assertFalse($book->ebook);
    }

    public function test_password_reset_link_is_sent_without_disclosing_unknown_accounts(): void
    {
        Notification::fake();
        $user = User::create([
            'name' => 'Alya Putri',
            'email' => 'alya@example.com',
            'password' => 'SecretPassword2026',
            'role' => 'member',
        ]);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);

        $this->post(route('password.email'), ['email' => 'unknown@example.com'])
            ->assertSessionHas('status', 'Jika email tersebut terdaftar, tautan reset password akan dikirim ke alamat tersebut.');
    }

    private function createBook(array $attributes = []): Book
    {
        return Book::create(array_merge([
            'title' => 'Laut Bercerita',
            'author' => 'Leila S. Chudori',
            'publisher' => 'Penerbit Cerita',
            'isbn' => '9786024246945',
            'genre' => 'Fiksi',
            'language' => 'Indonesia',
            'published_year' => 2020,
            'synopsis' => 'Sebuah kisah hangat.',
            'cover' => 'https://example.com/book.jpg',
            'shelf' => 'Rak A1',
            'dewey_code' => '813.3',
            'stock' => 1,
            'ebook' => false,
            'popular' => true,
        ], $attributes));
    }

    private function createLoan(Member $member, Book $book, string $status): Loan
    {
        return Loan::create([
            'book_id' => $book->id,
            'member_name' => $member->name,
            'member_number' => $member->member_number,
            'due_at' => today(),
            'status' => $status,
            'method' => 'pickup',
        ]);
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function midtransNotification(MembershipSubscription $membership, array $overrides = []): array
    {
        $payload = array_merge([
            'order_id' => $membership->payment_order_id,
            'status_code' => '200',
            'gross_amount' => number_format($membership->amount, 2, '.', ''),
            'transaction_status' => 'settlement',
            'transaction_id' => 'midtrans-transaction-1',
        ], $overrides);
        $payload['signature_key'] = hash(
            'sha512',
            $payload['order_id'].$payload['status_code'].$payload['gross_amount'].config('services.midtrans.server_key'),
        );

        return $payload;
    }

    private function createActiveMember(): Member
    {
        $user = User::create([
            'name' => 'Alya Putri',
            'email' => 'alya@example.com',
            'password' => 'SecretPassword2026',
            'role' => 'member',
        ]);

        return Member::create([
            'user_id' => $user->id,
            'member_number' => 'LIB-2024-0042',
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'active',
            'joined_at' => '2024-05-01',
        ]);
    }

    private function createMembership(User $user, string $plan = 'basic', string $billingPeriod = 'monthly'): MembershipSubscription
    {
        $startsAt = today();

        return MembershipSubscription::create([
            'user_id' => $user->id,
            'plan' => $plan,
            'billing_period' => $billingPeriod,
            'amount' => config('membership.plans.'.$plan.'.prices.'.$billingPeriod),
            'status' => 'active',
            'starts_at' => $startsAt,
            'ends_at' => $billingPeriod === 'yearly'
                ? $startsAt->copy()->addYearNoOverflow()
                : $startsAt->copy()->addMonthNoOverflow(),
            'merchandise_status' => 'not_eligible',
        ]);
    }

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Library Admin',
            'email' => 'admin@example.com',
            'password' => 'SecretPassword2026',
            'role' => 'admin',
        ]);
    }
}
