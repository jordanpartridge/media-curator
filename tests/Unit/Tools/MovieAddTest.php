<?php

use App\Services\RadarrService;
use App\Tools\MovieAdd;

beforeEach(function () {
    $this->radarr = Mockery::mock(RadarrService::class);
    $this->tool = new MovieAdd($this->radarr);
});

describe('MovieAdd', function () {
    it('returns its name', function () {
        expect($this->tool->name())->toBe('movie_add');
    });

    it('returns a description', function () {
        expect($this->tool->description())->toBeString()->not->toBeEmpty();
    });

    it('adds a movie via radarr and confirms', function () {
        $this->radarr->shouldReceive('addMovie')
            ->with(2493, 'The Princess Bride')
            ->once()
            ->andReturn(['id' => 1]);

        $result = $this->tool->execute([
            'tmdb_id' => 2493,
            'title' => 'The Princess Bride',
        ]);

        expect($result)->toContain('The Princess Bride')
            ->and($result)->toContain('collection, sir');
    });

    it('rejects missing tmdb_id', function () {
        $this->radarr->shouldNotReceive('addMovie');

        $result = $this->tool->execute(['title' => 'Some Movie']);

        expect($result)->toContain('require');
    });

    it('rejects missing title', function () {
        $this->radarr->shouldNotReceive('addMovie');

        $result = $this->tool->execute(['tmdb_id' => 123]);

        expect($result)->toContain('require');
    });

    it('rejects empty parameters', function () {
        $this->radarr->shouldNotReceive('addMovie');

        $result = $this->tool->execute([]);

        expect($result)->toContain('require');
    });
});
