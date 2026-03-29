<?php

use App\Services\JellyfinService;
use App\Tools\WatchHistory;

beforeEach(function () {
    $this->jellyfin = Mockery::mock(JellyfinService::class);
    $this->tool = new WatchHistory($this->jellyfin);
});

describe('WatchHistory', function () {
    it('returns its name', function () {
        expect($this->tool->name())->toBe('watch_history');
    });

    it('returns a description', function () {
        expect($this->tool->description())->toBeString()->not->toBeEmpty();
    });

    it('returns recently watched movies', function () {
        $this->jellyfin->shouldReceive('getWatchHistory')
            ->with('Movie', 7)
            ->once()
            ->andReturn([
                [
                    'Name' => 'The Princess Bride',
                    'UserData' => ['LastPlayedDate' => '2024-03-25'],
                ],
                [
                    'Name' => 'Die Hard',
                    'UserData' => ['LastPlayedDate' => '2024-03-24'],
                ],
            ]);

        $results = $this->tool->execute([]);

        expect($results)->toHaveCount(2)
            ->and($results[0]['title'])->toBe('The Princess Bride')
            ->and($results[0]['watched_at'])->toBe('2024-03-25')
            ->and($results[1]['title'])->toBe('Die Hard');
    });

    it('uses custom days parameter', function () {
        $this->jellyfin->shouldReceive('getWatchHistory')
            ->with('Movie', 30)
            ->once()
            ->andReturn([]);

        $results = $this->tool->execute(['days' => 30]);

        expect($results)->toBe([]);
    });

    it('defaults to 7 days', function () {
        $this->jellyfin->shouldReceive('getWatchHistory')
            ->with('Movie', 7)
            ->once()
            ->andReturn([]);

        $this->tool->execute([]);
    });
});
