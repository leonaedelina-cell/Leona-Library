<?php

namespace App\Http\Controllers;

use App\Models\MembershipSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MidtransWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'string', 'max:80'],
            'status_code' => ['required', 'string', 'max:3', 'regex:/^\d+$/'],
            'gross_amount' => ['required', 'string', 'max:32'],
            'signature_key' => ['required', 'string', 'size:128'],
            'transaction_status' => ['required', 'in:settlement,capture,pending,deny,cancel,expire,failure,refund,partial_refund,chargeback'],
            'fraud_status' => ['nullable', 'string', 'max:32'],
            'transaction_id' => ['nullable', 'string', 'max:100'],
        ]);

        $serverKey = config('services.midtrans.server_key');
        abort_unless(is_string($serverKey) && $serverKey !== '', 503, 'Payment notifications are not configured.');

        $expectedSignature = hash('sha512', $data['order_id'].$data['status_code'].$data['gross_amount'].$serverKey);
        abort_unless(hash_equals($expectedSignature, $data['signature_key']), 403, 'Invalid payment signature.');

        abort_unless(preg_match('/^\d+(?:\.0{1,2})?$/D', $data['gross_amount']) === 1, 403, 'Invalid payment amount.');

        $amount = (int) explode('.', $data['gross_amount'])[0];
        $membership = MembershipSubscription::query()
            ->where('payment_order_id', $data['order_id'])
            ->firstOrFail();

        abort_unless($amount === $membership->amount, 403, 'Payment amount does not match the invoice.');

        DB::transaction(function () use ($data, $membership): void {
            $user = $membership->user()->lockForUpdate()->firstOrFail();
            $lockedMembership = MembershipSubscription::query()
                ->lockForUpdate()
                ->findOrFail($membership->id);

            if ($lockedMembership->status !== 'pending') {
                $reversalStatuses = [
                    'refund' => 'refunded',
                    'partial_refund' => 'partially_refunded',
                    'chargeback' => 'chargeback',
                ];

                if ($lockedMembership->status === 'active'
                    && isset($reversalStatuses[$data['transaction_status']])) {
                    $lockedMembership->update([
                        'status' => 'rejected',
                        'payment_status' => $reversalStatuses[$data['transaction_status']],
                        'payment_transaction_id' => $data['transaction_id'] ?? null,
                    ]);
                }

                return;
            }

            $transactionId = $data['transaction_id'] ?? null;

            if ($data['transaction_status'] === 'settlement'
                || ($data['transaction_status'] === 'capture' && ($data['fraud_status'] ?? null) === 'accept')) {
                $hasPriorAnnualMembership = $user->memberships()
                    ->where('status', 'active')
                    ->where('billing_period', 'yearly')
                    ->whereNotNull('starts_at')
                    ->exists();
                $startsAt = today();
                $endsAt = $lockedMembership->billing_period === 'yearly'
                    ? $startsAt->copy()->addYearNoOverflow()
                    : $startsAt->copy()->addMonthNoOverflow();

                $lockedMembership->update([
                    'status' => 'active',
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'payment_status' => 'paid',
                    'payment_transaction_id' => $transactionId,
                    'payment_paid_at' => now(),
                    'merchandise_status' => $lockedMembership->billing_period === 'yearly' && ! $hasPriorAnnualMembership
                        ? 'pending'
                        : 'not_eligible',
                ]);

                return;
            }

            $terminalStatuses = [
                'deny' => 'denied',
                'cancel' => 'cancelled',
                'expire' => 'expired',
                'failure' => 'failed',
                'refund' => 'refunded',
                'partial_refund' => 'partially_refunded',
                'chargeback' => 'chargeback',
            ];

            if (isset($terminalStatuses[$data['transaction_status']])
                || ($data['transaction_status'] === 'capture' && ($data['fraud_status'] ?? null) === 'deny')) {
                $lockedMembership->update([
                    'status' => 'rejected',
                    'payment_status' => $terminalStatuses[$data['transaction_status']] ?? 'denied',
                    'payment_transaction_id' => $transactionId,
                ]);

                return;
            }

            $lockedMembership->update([
                'payment_status' => 'pending',
                'payment_transaction_id' => $transactionId,
            ]);
        });

        return response()->json(['message' => 'Notification processed.']);
    }
}
