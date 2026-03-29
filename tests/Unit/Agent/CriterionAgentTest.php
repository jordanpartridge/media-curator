<?php

use App\Agent\BaseAgent;
use App\Agent\CriterionAgent;
use App\Tools\MemorySearch;
use App\Tools\MemoryStore;
use App\Tools\SlackReply;

beforeEach(function () {
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

it('has domain context covering media services', function () {
    $context = $this->agent->domainContext();

    expect($context)
        ->toContain('Radarr')
        ->toContain('Sonarr')
        ->toContain('Jellyfin')
        ->toContain('Qdrant');
});

it('declares memory and slack tools', function () {
    $tools = $this->agent->domainTools();

    expect($tools)
        ->toContain(MemorySearch::class)
        ->toContain(MemoryStore::class)
        ->toContain(SlackReply::class);
});

it('extends BaseAgent', function () {
    expect($this->agent)->toBeInstanceOf(BaseAgent::class);
});
