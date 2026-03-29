<?php

use App\Services\RadarrService;
use App\Tools\MovieSearch;

beforeEach(function () {
    $this->radarr = Mockery::mock(RadarrService::class);
    $this->tool = new MovieSearch($this->radarr);
});

describe('MovieSearch', function () {
    it('returns its name', function () {
        expect($this->tool->name())->toBe('movie_search');
    });

    it('returns a description', function () {
        expect($this->tool->description())->toBeString()->not->toBeEmpty();
    });

    it('searches radarr and returns formatted results', function () {
        $this->radarr->shouldReceive('lookup')
            ->with('Princess Bride')
            ->once()
            ->andReturn([
                [
                    'title' => 'The Princess Bride',
                    'year' => 1987,
                    'tmdbId' => 2493,
                    'overview' => 'A classic fairy tale.',
                    'genres' => ['Adventure', 'Comedy'],
                ],
            ]);

        $results = $this->tool->execute(['query' => 'Princess Bride']);

        expect($results)->toHaveCount(1)
            ->and($results[0]['title'])->toBe('The Princess Bride')
            ->and($results[0]['year'])->toBe(1987)
            ->and($results[0]['tmdb_id'])->toBe(2493)
            ->and($results[0]['overview'])->toBe('A classic fairy tale.')
            ->and($results[0]['genres'])->toBe(['Adventure', 'Comedy']);
    });

    it('returns empty array for empty query', function () {
        $this->radarr->shouldNotReceive('lookup');

        expect($this->tool->execute(['query' => '']))->toBe([]);
    });

    it('limits results to 10', function () {
        $movies = array_fill(0, 15, [
            'title' => 'Movie',
            'year' => 2024,
            'tmdbId' => 1,
            'overview' => 'A movie.',
            'genres' => [],
        ]);

        $this->radarr->shouldReceive('lookup')
            ->once()
            ->andReturn($movies);

        $results = $this->tool->execute(['query' => 'Movie']);

        expect($results)->toHaveCount(10);
    });

    it('handles missing fields gracefully', function () {
        $this->radarr->shouldReceive('lookup')
            ->once()
            ->andReturn([['title' => 'Partial Movie']]);

        $results = $this->tool->execute(['query' => 'partial']);

        expect($results[0]['year'])->toBeNull()
            ->and($results[0]['tmdb_id'])->toBeNull()
            ->and($results[0]['overview'])->toBe('')
            ->and($results[0]['genres'])->toBe([]);
    });
});
