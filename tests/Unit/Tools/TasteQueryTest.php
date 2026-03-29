<?php

use App\Services\QdrantService;
use App\Tools\TasteQuery;

describe('TasteQuery', function () {
    beforeEach(function () {
        $this->qdrant = Mockery::mock(QdrantService::class);
        $this->tool = new TasteQuery($this->qdrant);
    });

    it('returns taste and vibe arrays with a summary', function () {
        $this->qdrant->shouldReceive('searchCollection')
            ->with('criterion_memory', Mockery::type('string'), 5)
            ->andReturn([
                ['content' => 'Loves Blade Runner', 'score' => 0.95, 'metadata' => []],
            ]);

        $this->qdrant->shouldReceive('searchCollection')
            ->with('lexi_memory', Mockery::type('string'), 5)
            ->andReturn([
                ['content' => 'Recovery score 85, mood: upbeat', 'score' => 0.88, 'metadata' => []],
            ]);

        $result = $this->tool->execute([]);

        expect($result)
            ->toHaveKey('taste')
            ->toHaveKey('vibe')
            ->toHaveKey('summary');

        expect($result['taste'])->toHaveCount(1);
        expect($result['vibe'])->toHaveCount(1);
    });

    it('builds summary from both collections', function () {
        $this->qdrant->shouldReceive('searchCollection')
            ->with('criterion_memory', Mockery::type('string'), 5)
            ->andReturn([
                ['content' => 'Rated Inception 9/10', 'score' => 0.9, 'metadata' => []],
            ]);

        $this->qdrant->shouldReceive('searchCollection')
            ->with('lexi_memory', Mockery::type('string'), 5)
            ->andReturn([
                ['content' => 'HRV trending up, good recovery', 'score' => 0.85, 'metadata' => []],
            ]);

        $result = $this->tool->execute([]);

        expect($result['summary'])
            ->toContain('Lexi context')
            ->toContain('Taste memory');
    });

    it('returns fallback summary when both collections are empty', function () {
        $this->qdrant->shouldReceive('searchCollection')->andReturn([]);

        $result = $this->tool->execute([]);

        expect($result['summary'])->toBe('No shared memory available.');
    });

    it('handles lexi collection failure gracefully', function () {
        $this->qdrant->shouldReceive('searchCollection')
            ->with('criterion_memory', Mockery::type('string'), Mockery::type('int'))
            ->andReturn([
                ['content' => 'Likes thriller genre', 'score' => 0.8, 'metadata' => []],
            ]);

        $this->qdrant->shouldReceive('searchCollection')
            ->with('lexi_memory', Mockery::type('string'), Mockery::type('int'))
            ->andThrow(new RuntimeException('Connection refused'));

        $result = $this->tool->execute([]);

        expect($result['taste'])->toHaveCount(1);
        expect($result['vibe'])->toBeEmpty();
    });

    it('accepts custom query and limit parameters', function () {
        $this->qdrant->shouldReceive('searchCollection')
            ->with('criterion_memory', 'sci-fi preferences', 3)
            ->once()
            ->andReturn([]);

        $this->qdrant->shouldReceive('searchCollection')
            ->with('lexi_memory', 'sci-fi preferences', 3)
            ->once()
            ->andReturn([]);

        $this->tool->execute(['query' => 'sci-fi preferences', 'limit' => 3]);
    });

    it('has the correct tool name', function () {
        expect($this->tool->name())->toBe('taste_query');
    });

    it('has a description mentioning taste and vibe', function () {
        expect($this->tool->description())
            ->toContain('taste')
            ->toContain('vibe');
    });
});
