<?php

namespace App\Services;

use App\Models\MembershipSubscription;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use LogicException;
use UnexpectedValueException;

class MidtransSnapClient
{
    /**
     * Create a Snap checkout and return its hosted payment URL.
     *
     * @throws ConnectionException
     * @throws RequestException
     * @throws LogicException
     * @throws UnexpectedValueException
     */
    public function createCheckout(MembershipSubscription $membership): string
    {
        $serverKey = config('services.midtrans.server_key');
        $baseUrl = config('services.midtrans.snap_base_url');

        $baseHost = is_string($baseUrl) ? parse_url($baseUrl, PHP_URL_HOST) : null;

        if (! is_string($serverKey) || $serverKey === ''
            || ! is_string($baseUrl)
            || parse_url($baseUrl, PHP_URL_SCHEME) !== 'https'
            || ! in_array($baseHost, ['app.sandbox.midtrans.com', 'app.midtrans.com'], true)) {
            throw new LogicException('Midtrans Snap is not configured.');
        }

        $membership->loadMissing('user');

        $response = Http::acceptJson()
            ->asJson()
            ->withBasicAuth($serverKey, '')
            ->connectTimeout(3)
            ->timeout(15)
            ->post(rtrim($baseUrl, '/').'/snap/v1/transactions', [
                'transaction_details' => [
                    'order_id' => $membership->payment_order_id,
                    'gross_amount' => $membership->amount,
                ],
                'item_details' => [[
                    'id' => 'membership-'.$membership->plan.'-'.$membership->billing_period,
                    'price' => $membership->amount,
                    'quantity' => 1,
                    'name' => 'Membership '.config('membership.plans.'.$membership->plan.'.name').' '.($membership->billing_period === 'yearly' ? 'Tahunan' : 'Bulanan'),
                ]],
                'customer_details' => [
                    'first_name' => $membership->user->name,
                    'email' => $membership->user->email,
                ],
                'enabled_payments' => config('services.midtrans.enabled_payments'),
            ]);

        $paymentUrl = $response->throw()->json('redirect_url');
        $paymentHost = is_string($paymentUrl) ? parse_url($paymentUrl, PHP_URL_HOST) : null;

        if (! is_string($paymentUrl)
            || filter_var($paymentUrl, FILTER_VALIDATE_URL) === false
            || parse_url($paymentUrl, PHP_URL_SCHEME) !== 'https'
            || $paymentHost !== $baseHost) {
            throw new UnexpectedValueException('Midtrans returned an invalid checkout URL.');
        }

        return $paymentUrl;
    }
}
