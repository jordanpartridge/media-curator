<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FilmRating extends Model
{
    protected $fillable = [
        'title',
        'year',
        'tmdb_id',
        'rating',
        'notes',
        'watched_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'tmdb_id' => 'integer',
            'rating' => 'integer',
            'watched_at' => 'datetime',
        ];
    }
}
