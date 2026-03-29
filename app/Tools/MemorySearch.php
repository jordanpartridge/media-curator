<?php

namespace App\Tools;

use App\Services\QdrantService;

class MemorySearch extends CriterionTool
{
    public function __construct(
        private readonly QdrantService $qdrant,
    ) {}

    public function name(): string
    {
        return 'memory_search';
    }

    public function description(): string
    {
        return 'Search long-term vector memory for relevant context about past conversations, preferences, and decisions.';
    }

    public function execute(array $parameters): array
    {
        $query = $parameters['query'] ?? '';
        $limit = $parameters['limit'] ?? 5;

        if ($query === '') {
            return [];
        }

        return $this->qdrant->search($query, $limit);
    }
}
