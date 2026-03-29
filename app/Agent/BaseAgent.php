<?php

namespace App\Agent;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;

abstract class BaseAgent
{
    protected array $history = [];

    protected array $bootContext = [];

    abstract public function mission(): string;

    abstract public function domainContext(): string;

    /** @return array<int, string> */
    abstract public function domainTools(): array;

    public function withHistory(array $history): static
    {
        $this->history = $history;

        return $this;
    }

    public function withBootContext(array $context): static
    {
        $this->bootContext = $context;

        return $this;
    }

    public function respond(string $message, ?string $conversationId = null): string
    {
        $conversationId ??= uniqid('conv_');

        $this->loadHistory($conversationId);

        $systemPrompt = $this->buildSystemPrompt();

        $start = microtime(true);

        try {
            $response = Prism::text()
                ->using(Provider::Ollama, config('services.ollama.model'))
                ->withSystemPrompt($systemPrompt)
                ->withPrompt($message)
                ->withClientOptions(['timeout' => 120])
                ->asText();

            $text = $response->text;
        } catch (\Throwable $e) {
            Log::error('Agent response failed', [
                'agent' => static::class,
                'error' => $e->getMessage(),
            ]);

            $text = 'I appear to be experiencing difficulties, sir. Please try again momentarily.';
        }

        $duration = round((microtime(true) - $start) * 1000);

        Log::info('Agent responded', [
            'agent' => static::class,
            'conversation' => $conversationId,
            'duration_ms' => $duration,
        ]);

        $this->appendHistory($conversationId, $message, $text);

        return $text;
    }

    protected function buildSystemPrompt(): string
    {
        $parts = [
            $this->mission(),
            $this->domainContext(),
        ];

        if ($this->bootContext) {
            $parts[] = "Current context:\n".implode("\n", array_map(
                fn (string $key, mixed $value) => "- {$key}: {$value}",
                array_keys($this->bootContext),
                array_values($this->bootContext),
            ));
        }

        if ($this->history) {
            $recent = array_slice($this->history, -10);
            $parts[] = "Recent conversation:\n".implode("\n", array_map(
                fn (array $turn) => "{$turn['role']}: {$turn['content']}",
                $recent,
            ));
        }

        return implode("\n\n", $parts);
    }

    protected function loadHistory(string $conversationId): void
    {
        $maxHistory = config('criterion.memory.max_history', 20);

        try {
            $stored = Redis::get("criterion:history:{$conversationId}");

            if ($stored) {
                $decoded = json_decode($stored, true);
                $this->history = array_slice($decoded ?? [], -$maxHistory);
            }
        } catch (\Throwable) {
            // Redis unavailable — continue without history
        }
    }

    protected function appendHistory(string $conversationId, string $userMessage, string $assistantMessage): void
    {
        $this->history[] = ['role' => 'user', 'content' => $userMessage];
        $this->history[] = ['role' => 'assistant', 'content' => $assistantMessage];

        $maxHistory = config('criterion.memory.max_history', 20);
        $this->history = array_slice($this->history, -$maxHistory);

        $ttl = config('criterion.memory.ttl', 86400);

        try {
            Redis::setex(
                "criterion:history:{$conversationId}",
                $ttl,
                json_encode($this->history),
            );
        } catch (\Throwable) {
            // Redis unavailable — history won't persist
        }
    }
}
