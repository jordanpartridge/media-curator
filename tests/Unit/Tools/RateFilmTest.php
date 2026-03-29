<?php

use App\Models\FilmRating;
use App\Services\QdrantService;
use App\Tools\RateFilm;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('RateFilm', function () {
    beforeEach(function () {
        $this->qdrant = Mockery::mock(QdrantService::class);
        $this->tool = new RateFilm($this->qdrant);
    });

    it('stores a film rating in the database', function () {
        $this->qdrant->shouldReceive('store')->once()->andReturn(true);

        $result = $this->tool->execute([
            'title' => 'Blade Runner 2049',
            'year' => 2017,
            'rating' => 9,
            'notes' => 'Visually stunning sequel',
        ]);

        expect($result['stored'])->toBeTrue();
        expect($result['title'])->toBe('Blade Runner 2049');
        expect($result['rating'])->toBe(9);

        $this->assertDatabaseHas('film_ratings', [
            'title' => 'Blade Runner 2049',
            'year' => 2017,
            'rating' => 9,
        ]);
    });

    it('stores the rating in Qdrant vector memory', function () {
        $this->qdrant->shouldReceive('store')
            ->once()
            ->with(
                Mockery::on(fn ($content) => str_contains($content, 'Blade Runner 2049') && str_contains($content, '9/10')),
                Mockery::on(fn ($meta) => $meta['type'] === 'film_rating' && in_array('film', $meta['tags'])),
            )
            ->andReturn(true);

        $this->tool->execute([
            'title' => 'Blade Runner 2049',
            'year' => 2017,
            'rating' => 9,
        ]);
    });

    it('returns error when title is missing', function () {
        $this->qdrant->shouldNotReceive('store');

        $result = $this->tool->execute(['rating' => 7]);

        expect($result['stored'])->toBeFalse();
        expect($result['error'])->toBeString();
    });

    it('returns error when rating is missing', function () {
        $this->qdrant->shouldNotReceive('store');

        $result = $this->tool->execute(['title' => 'Inception']);

        expect($result['stored'])->toBeFalse();
        expect($result['error'])->toBeString();
    });

    it('sets watched_at to current time', function () {
        $this->qdrant->shouldReceive('store')->andReturn(true);

        $this->tool->execute([
            'title' => 'The Matrix',
            'year' => 1999,
            'rating' => 10,
        ]);

        $rating = FilmRating::first();
        expect($rating->watched_at)->not->toBeNull();
    });

    it('stores optional tmdb_id', function () {
        $this->qdrant->shouldReceive('store')->andReturn(true);

        $this->tool->execute([
            'title' => 'Dune',
            'year' => 2021,
            'rating' => 8,
            'tmdb_id' => 438631,
        ]);

        $this->assertDatabaseHas('film_ratings', [
            'title' => 'Dune',
            'tmdb_id' => 438631,
        ]);
    });

    it('has the correct tool name', function () {
        expect($this->tool->name())->toBe('rate_film');
    });

    it('has a description mentioning rating and memory', function () {
        expect($this->tool->description())
            ->toContain('rating')
            ->toContain('memory');
    });
});
