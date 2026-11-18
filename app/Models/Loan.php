<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Loan extends Model
{
    protected $fillable = [
        'book_id',
        'member_name',
        'member_number',
        'due_at',
        'status',
        'method',
        'fine',
        'renewals',
        'late_fee_free_days',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'date',
            'fine' => 'integer',
            'renewals' => 'integer',
            'late_fee_free_days' => 'integer',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(BookReview::class);
    }

    public function lateDays(): int
    {
        return $this->due_at->isBefore(today())
            ? $this->due_at->diffInDays(today())
            : 0;
    }

    public function estimatedFine(): int
    {
        return max(0, $this->lateDays() - $this->late_fee_free_days) * 1000;
    }
}
