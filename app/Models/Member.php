<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    protected $fillable = [
        'name',
        'email',
        'user_id',
        'member_number',
        'status',
        'joined_at',
    ];

    protected function casts(): array
    {
        return ['joined_at' => 'date'];
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'member_number', 'member_number');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
