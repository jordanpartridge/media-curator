<?php

use App\Models\FilmRating;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

describe('FilmRating', function () {
    it('can be created with valid attributes', function () {
        $rating = FilmRating::create([
            'title' => 'The Princess Bride',
            'year' => 1987,
            'tmdb_id' => 2493,
            'rating' => 10,
            'notes' => 'As you wish, sir.',
            'watched_at' => now(),
        ]);

        expect($rating)->toBeInstanceOf(FilmRating::class);
        expect($rating->id)->toBeInt();
    });

    it('casts year and rating as integers', function () {
        $rating = FilmRating::create([
            'title' => 'Inception',
            'year' => 2010,
            'rating' => 9,
            'watched_at' => now(),
        ]);

        $rating->refresh();

        expect($rating->year)->toBeInt();
        expect($rating->rating)->toBeInt();
    });

    it('casts watched_at as datetime', function () {
        $rating = FilmRating::create([
            'title' => 'Interstellar',
            'year' => 2014,
            'rating' => 9,
            'watched_at' => '2025-01-15 20:00:00',
        ]);

        $rating->refresh();

        expect($rating->watched_at)->toBeInstanceOf(Carbon::class);
    });

    it('allows nullable fields', function () {
        $rating = FilmRating::create([
            'title' => 'Unknown Film',
            'rating' => 5,
        ]);

        expect($rating->year)->toBeNull();
        expect($rating->tmdb_id)->toBeNull();
        expect($rating->notes)->toBeNull();
        expect($rating->watched_at)->toBeNull();
    });
});
