<?php

namespace App\Tools;

use App\Services\QdrantService;

class MemoryStore extends CriterionTool
{
    public function __construct(
        private readonly QdrantService $qdrant,
    ) {}

    public function name(): string
    {
        return 'memory_store';
    }

    public function description(): string
    {
        return 'Store a fact, preference, or decision in long-term vector memory for future retrieval.';
    }

    public function execute(array $parameters): bool
    {
        $content = $parameters['content'] ?? '';
        $metadata = $parameters['metadata'] ?? [];

        if ($content === '') {
            return false;
        }

        return $this->qdrant->store($content, $metadata);
    }
}
