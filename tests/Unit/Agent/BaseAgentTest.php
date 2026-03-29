<?php

use App\Agent\BaseAgent;

beforeEach(function () {
    $this->agent = new class extends BaseAgent
    {
        public function mission(): string
        {
            return 'Test mission prompt.';
        }

        public function domainContext(): string
        {
            return 'Test domain context.';
        }

        public function domainTools(): array
        {
            return [];
        }

        public function exposeBuildSystemPrompt(): string
        {
            return $this->buildSystemPrompt();
        }
    };
});

it('builds a system prompt from mission and domain context', function () {
    $prompt = $this->agent->exposeBuildSystemPrompt();

    expect($prompt)
        ->toContain('Test mission prompt.')
        ->toContain('Test domain context.');
});

it('includes boot context in system prompt when provided', function () {
    $this->agent->withBootContext(['movies' => 42, 'status' => 'healthy']);

    $prompt = $this->agent->exposeBuildSystemPrompt();

    expect($prompt)
        ->toContain('movies: 42')
        ->toContain('status: healthy');
});

it('includes conversation history in system prompt', function () {
    $this->agent->withHistory([
        ['role' => 'user', 'content' => 'Recommend a movie'],
        ['role' => 'assistant', 'content' => 'The Princess Bride, sir.'],
    ]);

    $prompt = $this->agent->exposeBuildSystemPrompt();

    expect($prompt)
        ->toContain('user: Recommend a movie')
        ->toContain('assistant: The Princess Bride, sir.');
});

it('returns domain tools as an array', function () {
    expect($this->agent->domainTools())->toBeArray()->toBeEmpty();
});

it('returns withHistory fluently', function () {
    $result = $this->agent->withHistory([]);

    expect($result)->toBe($this->agent);
});

it('returns withBootContext fluently', function () {
    $result = $this->agent->withBootContext([]);

    expect($result)->toBe($this->agent);
});
