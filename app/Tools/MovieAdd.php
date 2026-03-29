<?php

namespace App\Tools;

use App\Services\RadarrService;

class MovieAdd extends CriterionTool
{
    public function __construct(
        private readonly RadarrService $radarr,
    ) {}

    public function name(): string
    {
        return 'movie_add';
    }

    public function description(): string
    {
        return 'Add a movie to the library by TMDB ID. Criterion adds directly — no approval needed.';
    }

    public function execute(array $parameters): string
    {
        $tmdbId = $parameters['tmdb_id'] ?? null;
        $title = $parameters['title'] ?? '';

        if ($tmdbId === null || $title === '') {
            return 'I require both a TMDB ID and title to add a film, sir.';
        }

        $this->radarr->addMovie((int) $tmdbId, $title);

        return "Added {$title} to your collection, sir.";
    }
}
