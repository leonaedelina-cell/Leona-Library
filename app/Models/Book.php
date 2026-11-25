<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    public function reviews(): HasMany
    {
        return $this->hasMany(BookReview::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(BookView::class);
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'book_favorites')->withTimestamps();
    }

    public function availableCopies(): int
    {
        $activeLoans = $this->active_loans_count
            ?? $this->loans()->where('status', 'active')->count();

        return max(0, $this->stock - $activeLoans);
    }
}
