<?php

namespace App\Tools;

use App\Services\QdrantService;
use Illuminate\Support\Facades\Log;

class TasteQuery extends CriterionTool
{
    public function __construct(
        private readonly QdrantService $qdrant,
    ) {}

    public function name(): string
    {
        return 'taste_query';
    }

    public function description(): string
    {
        return 'Search taste preferences and current vibe from Criterion and Lexi shared memory.';
    }

    public function execute(array $parameters): array
    {
        $query = $parameters['query'] ?? 'Jordan current mood and media preferences';
        $limit = $parameters['limit'] ?? 5;

        $criterionCollection = config('criterion.qdrant.collection');
        $lexiCollection = config('criterion.qdrant.lexi_collection');

        $taste = $this->searchSafe($criterionCollection, $query, $limit);
        $vibe = $this->searchSafe($lexiCollection, $query, $limit);

        return [
            'taste' => $taste,
            'vibe' => $vibe,
            'summary' => $this->buildSummary($taste, $vibe),
        ];
    }

    private function searchSafe(string $collection, string $query, int $limit): array
    {
        try {
            return $this->qdrant->searchCollection($collection, $query, $limit);
        } catch (\Throwable $e) {
            Log::warning("TasteQuery: failed to search {$collection}", [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function buildSummary(array $taste, array $vibe): string
    {
        $parts = [];

        if ($vibe !== []) {
            $vibeSnippets = array_map(
                fn (array $hit) => $hit['content'],
                array_slice($vibe, 0, 3),
            );
            $parts[] = 'Lexi context: '.implode(' | ', $vibeSnippets);
        }

        if ($taste !== []) {
            $tasteSnippets = array_map(
                fn (array $hit) => $hit['content'],
                array_slice($taste, 0, 3),
            );
            $parts[] = 'Taste memory: '.implode(' | ', $tasteSnippets);
        }

        return $parts !== [] ? implode("\n", $parts) : 'No shared memory available.';
    }
}
