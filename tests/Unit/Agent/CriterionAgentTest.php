<?php

use App\Agent\BaseAgent;
use App\Agent\CriterionAgent;
use App\Tools\LibraryQuery;
use App\Tools\MemorySearch;
use App\Tools\MemoryStore;
use App\Tools\MovieAdd;
use App\Tools\MovieSearch;
use App\Tools\RetireList;
use App\Tools\SlackReply;
use App\Tools\WatchHistory;

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

it('declares all domain tools', function () {
    $tools = $this->agent->domainTools();

    expect($tools)
        ->toContain(MovieSearch::class)
        ->toContain(MovieAdd::class)
        ->toContain(LibraryQuery::class)
        ->toContain(RetireList::class)
        ->toContain(WatchHistory::class)
        ->toContain(MemorySearch::class)
        ->toContain(MemoryStore::class)
        ->toContain(SlackReply::class);
});

it('includes tool routing rules in mission', function () {
    $mission = $this->agent->mission();

    expect($mission)
        ->toContain('movie_search')
        ->toContain('movie_add')
        ->toContain('library_query')
        ->toContain('retire_list')
        ->toContain('watch_history');
});

it('extends BaseAgent', function () {
    expect($this->agent)->toBeInstanceOf(BaseAgent::class);
});
