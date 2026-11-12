<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan',
        'billing_period',
        'amount',
        'status',
        'starts_at',
        'ends_at',
        'approved_by',
        'approved_at',
        'merchandise_status',
        'payment_order_id',
        'payment_status',
        'payment_url',
        'payment_transaction_id',
        'payment_paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'approved_at' => 'datetime',
            'payment_paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isCurrent(): bool
    {
        return $this->status === 'active'
            && $this->ends_at !== null
            && $this->ends_at->greaterThanOrEqualTo(today());
    }
}
