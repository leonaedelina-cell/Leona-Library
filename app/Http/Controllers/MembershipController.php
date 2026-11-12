<?php

namespace App\Http\Controllers;

use App\Models\MembershipSubscription;
use App\Models\User;
use App\Services\MidtransSnapClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use LogicException;
use UnexpectedValueException;

class MembershipController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('membership.index', [
            'plans' => config('membership.plans'),
            'member' => $user?->member,
            'currentMembership' => $user?->currentMembership(),
            'pendingMembership' => $user?->memberships()
                ->where('status', 'pending')
                ->where('payment_status', 'pending')
                ->latest()
                ->first(),
            'memberships' => $user?->memberships()->latest()->take(5)->get() ?? collect(),
        ]);
    }

    public function store(Request $request, MidtransSnapClient $midtrans): RedirectResponse
    {
        $data = $request->validate([
            'plan' => ['required', 'in:basic,premium'],
            'billing_period' => ['required', 'in:monthly,yearly'],
        ]);

        $membership = DB::transaction(function () use ($data, $request): MembershipSubscription|string {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->id);
            $member = $user->member()->lockForUpdate()->first();

            if (! $member || $member->status === 'blocked') {
                return 'Akun anggota yang diblokir tidak dapat membeli membership.';
            }

            if ($user->currentMembership()) {
                return 'Paket berbayar yang aktif masih berlaku. Kamu dapat membeli paket baru setelah masa aktif berakhir.';
            }

            $pendingMembership = $user->memberships()
                ->where('status', 'pending')
                ->where('payment_status', 'pending')
                ->lockForUpdate()
                ->first();

            if ($pendingMembership) {
                return $pendingMembership->payment_url
                    ? $pendingMembership
                    : 'Pembayaran sebelumnya sedang disiapkan. Muat ulang halaman beberapa saat lagi.';
            }

            $membership = MembershipSubscription::create([
                'user_id' => $user->id,
                'plan' => $data['plan'],
                'billing_period' => $data['billing_period'],
                'amount' => config('membership.plans.'.$data['plan'].'.prices.'.$data['billing_period']),
                'status' => 'pending',
                'merchandise_status' => 'not_eligible',
                'payment_status' => 'pending',
                'payment_order_id' => 'RB-'.Str::uuid(),
            ]);

            return $membership;
        });

        if (is_string($membership)) {
            return back()->withErrors(['membership' => $membership]);
        }

        if ($membership->payment_url) {
            return redirect()->away($membership->payment_url);
        }

        try {
            $paymentUrl = $midtrans->createCheckout($membership);
        } catch (ConnectionException|RequestException|LogicException|UnexpectedValueException $exception) {
            Log::warning('Midtrans Snap checkout could not be created.', [
                'membership_id' => $membership->id,
                'order_id' => $membership->payment_order_id,
                'exception' => $exception::class,
            ]);

            $membership->update([
                'status' => 'rejected',
                'payment_status' => 'failed',
            ]);

            return back()->withErrors([
                'membership' => 'Pembayaran belum dapat disiapkan. Silakan coba lagi beberapa saat lagi.',
            ]);
        }

        $membership->update(['payment_url' => $paymentUrl]);

        return redirect()->away($paymentUrl);
    }
}
