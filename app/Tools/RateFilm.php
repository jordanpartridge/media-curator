<?php

namespace App\Tools;

use App\Models\FilmRating;
use App\Services\QdrantService;

class RateFilm extends CriterionTool
{
    public function __construct(
        private readonly QdrantService $qdrant,
    ) {}

    public function name(): string
    {
        return 'rate_film';
    }

    public function description(): string
    {
        return 'Log a film rating to the database and store it in vector memory for taste learning.';
    }

    public function execute(array $parameters): array
    {
        $title = $parameters['title'] ?? '';
        $year = $parameters['year'] ?? null;
        $rating = $parameters['rating'] ?? null;
        $notes = $parameters['notes'] ?? null;

        if ($title === '' || $rating === null) {
            return ['stored' => false, 'error' => 'Title and rating are required.'];
        }

        $filmRating = FilmRating::create([
            'title' => $title,
            'year' => $year,
            'tmdb_id' => $parameters['tmdb_id'] ?? null,
            'rating' => $rating,
            'notes' => $notes,
            'watched_at' => now(),
        ]);

        $memoryContent = "Rated {$title} ({$year}) {$rating}/10".
            ($notes ? ". {$notes}" : '');

        $this->qdrant->store($memoryContent, [
            'type' => 'film_rating',
            'tags' => ['film', 'rating'],
            'title' => $title,
            'year' => $year,
            'rating' => $rating,
        ]);

        return [
            'stored' => true,
            'id' => $filmRating->id,
            'title' => $title,
            'rating' => $rating,
        ];
    }
}
