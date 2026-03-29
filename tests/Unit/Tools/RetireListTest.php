<?php

use App\Services\JellyfinService;
use App\Tools\RetireList;

beforeEach(function () {
    $this->jellyfin = Mockery::mock(JellyfinService::class);
    $this->tool = new RetireList($this->jellyfin);
});

describe('RetireList', function () {
    it('returns its name', function () {
        expect($this->tool->name())->toBe('retire_list');
    });

    it('returns a description', function () {
        expect($this->tool->description())->toBeString()->not->toBeEmpty();
    });

    it('returns stale movies sorted by age descending', function () {
        $this->jellyfin->shouldReceive('getStaleItems')
            ->with('Movie', 90)
            ->once()
            ->andReturn([
                ['Name' => 'Recent Stale', 'DateCreated' => now()->subDays(100)->toIso8601String()],
                ['Name' => 'Very Old', 'DateCreated' => now()->subDays(500)->toIso8601String()],
            ]);

        $results = $this->tool->execute([]);

        expect($results)->toHaveCount(2)
            ->and($results[0]['title'])->toBe('Very Old')
            ->and($results[0]['days_in_library'])->toBeGreaterThanOrEqual(499)
            ->and($results[1]['title'])->toBe('Recent Stale')
            ->and($results[1]['days_in_library'])->toBeGreaterThanOrEqual(99);
    });

    it('uses custom days parameter', function () {
        $this->jellyfin->shouldReceive('getStaleItems')
            ->with('Movie', 30)
            ->once()
            ->andReturn([]);

        $results = $this->tool->execute(['days' => 30]);

        expect($results)->toBe([]);
    });

    it('defaults to 90 days', function () {
        $this->jellyfin->shouldReceive('getStaleItems')
            ->with('Movie', 90)
            ->once()
            ->andReturn([]);

        $this->tool->execute([]);
    });
});
