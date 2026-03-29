<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QdrantService
{
    private string $baseUrl;

    private string $collection;

    private int $dimension;

    public function __construct()
    {
        $host = config('criterion.qdrant.host');
        $port = config('criterion.qdrant.port');
        $this->baseUrl = "http://{$host}:{$port}";
        $this->collection = config('criterion.qdrant.collection');
        $this->dimension = config('criterion.qdrant.dimension');
    }

    public function ensureCollection(): void
    {
        $response = Http::get("{$this->baseUrl}/collections/{$this->collection}");

        if ($response->status() === 404) {
            Http::put("{$this->baseUrl}/collections/{$this->collection}", [
                'vectors' => [
                    'size' => $this->dimension,
                    'distance' => 'Cosine',
                ],
            ]);

            Log::info("Qdrant collection created: {$this->collection}");
        }
    }

    public function store(string $content, array $metadata = []): bool
    {
        $vector = $this->embed($content);

        if (! $vector) {
            return false;
        }

        $point = [
            'id' => crc32($content.microtime()),
            'vector' => $vector,
            'payload' => array_merge($metadata, [
                'content' => $content,
                'stored_at' => now()->toIso8601String(),
            ]),
        ];

        $response = Http::put(
            "{$this->baseUrl}/collections/{$this->collection}/points",
            ['points' => [$point]],
        );

        return $response->successful();
    }

    public function search(string $query, int $limit = 5): array
    {
        return $this->searchCollection($this->collection, $query, $limit);
    }

    public function searchCollection(string $collection, string $query, int $limit = 5): array
    {
        $vector = $this->embed($query);

        if (! $vector) {
            return [];
        }

        $response = Http::post(
            "{$this->baseUrl}/collections/{$collection}/points/search",
            [
                'vector' => $vector,
                'limit' => $limit,
                'with_payload' => true,
            ],
        );

        if (! $response->successful()) {
            Log::warning('Qdrant search failed', [
                'collection' => $collection,
                'status' => $response->status(),
            ]);

            return [];
        }

        return collect($response->json('result', []))
            ->map(fn (array $hit) => [
                'content' => $hit['payload']['content'] ?? '',
                'score' => $hit['score'] ?? 0,
                'metadata' => collect($hit['payload'] ?? [])->except('content')->all(),
            ])
            ->all();
    }

    private function embed(string $text): ?array
    {
        $ollamaUrl = config('services.ollama.url');

        try {
            $response = Http::timeout(30)->post("{$ollamaUrl}/api/embeddings", [
                'model' => config('services.ollama.model'),
                'prompt' => $text,
            ]);

            return $response->json('embedding');
        } catch (\Throwable $e) {
            Log::error('Embedding generation failed', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
