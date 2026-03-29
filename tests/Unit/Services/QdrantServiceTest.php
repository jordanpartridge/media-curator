<?php

use App\Services\QdrantService;
use Illuminate\Support\Facades\Http;

describe('QdrantService::searchCollection', function () {
    it('searches a specified collection', function () {
        Http::fake([
            '*/api/embeddings' => Http::response(['embedding' => array_fill(0, 1536, 0.1)]),
            '*/collections/custom_collection/points/search' => Http::response([
                'result' => [
                    [
                        'payload' => ['content' => 'test result'],
                        'score' => 0.95,
                    ],
                ],
            ]),
        ]);

        $service = app(QdrantService::class);
        $results = $service->searchCollection('custom_collection', 'test query', 5);

        expect($results)->toHaveCount(1);
        expect($results[0]['content'])->toBe('test result');
        expect($results[0]['score'])->toBe(0.95);
    });

    it('returns empty array when embedding fails', function () {
        Http::fake([
            '*/api/embeddings' => Http::response([], 500),
        ]);

        $service = app(QdrantService::class);
        $results = $service->searchCollection('any_collection', 'test');

        expect($results)->toBeEmpty();
    });

    it('returns empty array when search request fails', function () {
        Http::fake([
            '*/api/embeddings' => Http::response(['embedding' => array_fill(0, 1536, 0.1)]),
            '*/collections/*/points/search' => Http::response([], 500),
        ]);

        $service = app(QdrantService::class);
        $results = $service->searchCollection('broken_collection', 'test');

        expect($results)->toBeEmpty();
    });

    it('delegates search to searchCollection with default collection', function () {
        Http::fake([
            '*/api/embeddings' => Http::response(['embedding' => array_fill(0, 1536, 0.1)]),
            '*/collections/criterion_memory/points/search' => Http::response([
                'result' => [
                    [
                        'payload' => ['content' => 'default collection hit'],
                        'score' => 0.8,
                    ],
                ],
            ]),
        ]);

        $service = app(QdrantService::class);
        $results = $service->search('query');

        expect($results)->toHaveCount(1);
        expect($results[0]['content'])->toBe('default collection hit');
    });

    it('strips content from metadata in results', function () {
        Http::fake([
            '*/api/embeddings' => Http::response(['embedding' => array_fill(0, 1536, 0.1)]),
            '*/collections/*/points/search' => Http::response([
                'result' => [
                    [
                        'payload' => [
                            'content' => 'the content',
                            'type' => 'film_rating',
                            'stored_at' => '2025-01-01T00:00:00Z',
                        ],
                        'score' => 0.9,
                    ],
                ],
            ]),
        ]);

        $service = app(QdrantService::class);
        $results = $service->searchCollection('test', 'query');

        expect($results[0]['metadata'])->toHaveKey('type');
        expect($results[0]['metadata'])->toHaveKey('stored_at');
        expect($results[0]['metadata'])->not->toHaveKey('content');
    });
});
