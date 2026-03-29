<?php

use App\Agent\BaseAgent;
use App\Agent\CriterionAgent;
use App\Services\QdrantService;
use App\Tools\MemorySearch;
use App\Tools\MemoryStore;
use App\Tools\RateFilm;
use App\Tools\SlackReply;
use App\Tools\TasteQuery;

beforeEach(function () {
    $this->qdrant = Mockery::mock(QdrantService::class);
    $this->qdrant->shouldReceive('searchCollection')->andReturn([]);
    $this->app->instance(QdrantService::class, $this->qdrant);

    $this->agent = app(CriterionAgent::class);
});

it('has a mission prompt with the Criterion persona', function () {
    $mission = $this->agent->mission();

    expect($mission)
        ->toContain('Criterion')
        ->toContain('Alfred')
        ->toContain('Jordan Partridge')
        ->toContain('Princess Bride');
});

it('mentions shared Lexi memory in mission', function () {
    $mission = $this->agent->mission();

    expect($mission)->toContain('Lexi');
});

it('has domain context covering media services', function () {
    $context = $this->agent->domainContext();

    expect($context)
        ->toContain('Radarr')
        ->toContain('Sonarr')
        ->toContain('Jellyfin')
        ->toContain('Qdrant');
});

it('includes shared memory reference in domain context', function () {
    $context = $this->agent->domainContext();

    expect($context)->toContain('Lexi');
});

it('declares memory, slack, taste, and rating tools', function () {
    $tools = $this->agent->domainTools();

    expect($tools)
        ->toContain(MemorySearch::class)
        ->toContain(MemoryStore::class)
        ->toContain(SlackReply::class)
        ->toContain(TasteQuery::class)
        ->toContain(RateFilm::class);
});

it('extends BaseAgent', function () {
    expect($this->agent)->toBeInstanceOf(BaseAgent::class);
});

it('injects vibe summary into domain context when memory available', function () {
    $qdrant = Mockery::mock(QdrantService::class);
    $qdrant->shouldReceive('searchCollection')
        ->andReturn([
            ['content' => 'Jordan feeling energized after morning run', 'score' => 0.9, 'metadata' => []],
        ]);

    $this->app->instance(QdrantService::class, $qdrant);
    $this->app->forgetInstance(CriterionAgent::class);

    $agent = app(CriterionAgent::class);
    $context = $agent->domainContext();

    expect($context)->toContain('Current vibe from shared memory');
});

it('handles vibe loading failure gracefully', function () {
    $qdrant = Mockery::mock(QdrantService::class);
    $qdrant->shouldReceive('searchCollection')
        ->andThrow(new RuntimeException('Qdrant unreachable'));

    $this->app->instance(QdrantService::class, $qdrant);
    $this->app->forgetInstance(CriterionAgent::class);

    $agent = app(CriterionAgent::class);
    $context = $agent->domainContext();

    expect($context)
        ->toContain('Radarr')
        ->not->toContain('Current vibe from shared memory');
});
