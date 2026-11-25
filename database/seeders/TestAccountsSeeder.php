<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\MembershipSubscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use RuntimeException;

class TestAccountsSeeder extends Seeder
{
    private const PASSWORD = 'RuangBaca2026!';

    public function run(): void
    {
        if (! App::environment(['local', 'testing'])) {
            throw new RuntimeException('Sample accounts can only be seeded in local or testing environments.');
        }

        foreach ($this->accounts() as $account) {
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => self::PASSWORD,
                    'role' => $account['role'],
                ],
            );

            if ($account['role'] === 'admin') {
                continue;
            }

            Member::where('email', $user->email)
                ->whereNull('user_id')
                ->update(['user_id' => $user->id]);

            $user->member()->updateOrCreate(
                [],
                [
                    'name' => $user->name,
                    'email' => $user->email,
                    'member_number' => $account['member_number'],
                    'status' => 'active',
                    'joined_at' => $account['joined_at'],
                ],
            );

            if ($account['payment_order_id'] === null) {
                continue;
            }

            $startsAt = today();

            MembershipSubscription::updateOrCreate(
                ['payment_order_id' => $account['payment_order_id']],
                [
                    'user_id' => $user->id,
                    'plan' => 'premium',
                    'billing_period' => 'monthly',
                    'amount' => config('membership.plans.premium.prices.monthly'),
                    'status' => 'active',
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->copy()->addMonthNoOverflow(),
                    'merchandise_status' => 'not_eligible',
                    'payment_status' => 'paid',
                    'payment_paid_at' => now(),
                ],
            );
        }
    }

    /**
     * @return list<
     *     array{name: string, email: string, role: 'admin', member_number: null, joined_at: null, payment_order_id: null}
     *     | array{name: string, email: string, role: 'member', member_number: string, joined_at: string, payment_order_id: ?string}
     * >
     */
    private function accounts(): array
    {
        return [
            ['name' => 'Admin Ruang Baca 1', 'email' => 'admin1@ruangbaca.test', 'role' => 'admin', 'member_number' => null, 'joined_at' => null, 'payment_order_id' => null],
            ['name' => 'Admin Ruang Baca 2', 'email' => 'admin2@ruangbaca.test', 'role' => 'admin', 'member_number' => null, 'joined_at' => null, 'payment_order_id' => null],
            ['name' => 'Admin Ruang Baca 3', 'email' => 'admin3@ruangbaca.test', 'role' => 'admin', 'member_number' => null, 'joined_at' => null, 'payment_order_id' => null],
            ['name' => 'Member Basic 1', 'email' => 'basic1@ruangbaca.test', 'role' => 'member', 'member_number' => 'LIB-2026-0001', 'joined_at' => '2026-01-10', 'payment_order_id' => null],
            ['name' => 'Member Basic 2', 'email' => 'basic2@ruangbaca.test', 'role' => 'member', 'member_number' => 'LIB-2026-0002', 'joined_at' => '2026-01-11', 'payment_order_id' => null],
            ['name' => 'Member Basic 3', 'email' => 'basic3@ruangbaca.test', 'role' => 'member', 'member_number' => 'LIB-2026-0003', 'joined_at' => '2026-01-12', 'payment_order_id' => null],
            ['name' => 'Alya Putri', 'email' => 'alya@ruangbaca.id', 'role' => 'member', 'member_number' => 'LIB-2024-0042', 'joined_at' => '2024-05-01', 'payment_order_id' => 'DEMO-READER-PREMIUM'],
            ['name' => 'Member Premium 2', 'email' => 'premium2@ruangbaca.test', 'role' => 'member', 'member_number' => 'LIB-2026-0004', 'joined_at' => '2026-01-13', 'payment_order_id' => 'SAMPLE-PREMIUM-2'],
            ['name' => 'Member Premium 3', 'email' => 'premium3@ruangbaca.test', 'role' => 'member', 'member_number' => 'LIB-2026-0005', 'joined_at' => '2026-01-14', 'payment_order_id' => 'SAMPLE-PREMIUM-3'],
        ];
    }
}
