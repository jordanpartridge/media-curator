<?php

use App\Services\JellyfinService;
use App\Services\RadarrService;
use App\Tools\LibraryQuery;

beforeEach(function () {
    $this->radarr = Mockery::mock(RadarrService::class);
    $this->jellyfin = Mockery::mock(JellyfinService::class);
    $this->tool = new LibraryQuery($this->radarr, $this->jellyfin);
});

describe('LibraryQuery', function () {
    it('returns its name', function () {
        expect($this->tool->name())->toBe('library_query');
    });

    it('returns a description', function () {
        expect($this->tool->description())->toBeString()->not->toBeEmpty();
    });

    it('combines radarr and jellyfin data', function () {
        $this->radarr->shouldReceive('getMovies')
            ->once()
            ->andReturn([
                ['title' => 'The Princess Bride', 'year' => 1987, 'added' => '2024-01-15'],
                ['title' => 'Die Hard', 'year' => 1988, 'added' => '2024-02-01'],
            ]);

        $this->jellyfin->shouldReceive('getItems')
            ->once()
            ->andReturn([
                [
                    'Name' => 'The Princess Bride',
                    'UserData' => ['PlayCount' => 3, 'LastPlayedDate' => '2024-03-01'],
                ],
            ]);

        $results = $this->tool->execute([]);

        expect($results)->toHaveCount(2)
            ->and($results[0]['title'])->toBe('The Princess Bride')
            ->and($results[0]['play_count'])->toBe(3)
            ->and($results[0]['last_watched'])->toBe('2024-03-01')
            ->and($results[1]['title'])->toBe('Die Hard')
            ->and($results[1]['play_count'])->toBe(0)
            ->and($results[1]['last_watched'])->toBeNull();
    });

    it('returns empty array when library is empty', function () {
        $this->radarr->shouldReceive('getMovies')->once()->andReturn([]);
        $this->jellyfin->shouldReceive('getItems')->once()->andReturn([]);

        expect($this->tool->execute([]))->toBe([]);
    });
});
