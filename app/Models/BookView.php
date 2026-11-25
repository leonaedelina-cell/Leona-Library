<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookView extends Model
{
    protected $fillable = [
        'book_id',
        'user_id',
        'session_hash',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
