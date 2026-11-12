<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    protected $fillable = [
        'title',
        'author',
        'publisher',
        'isbn',
        'genre',
        'language',
        'published_year',
        'synopsis',
        'cover',
        'shelf',
        'dewey_code',
        'stock',
        'ebook',
        'ebook_path',
        'popular',
    ];

    protected function casts(): array
    {
        return [
            'ebook' => 'boolean',
            'popular' => 'boolean',
        ];
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function availableCopies(): int
    {
        $activeLoans = $this->active_loans_count
            ?? $this->loans()->where('status', 'active')->count();

        return max(0, $this->stock - $activeLoans);
    }
}
