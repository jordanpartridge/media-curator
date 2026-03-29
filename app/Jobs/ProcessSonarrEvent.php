<?php

namespace App\Jobs;

use App\Tools\SlackReply;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessSonarrEvent implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly array $data,
    ) {}

    public function handle(SlackReply $slack): void
    {
        $eventType = $this->data['event_type'] ?? 'unknown';

        match ($eventType) {
            'Download' => $this->handleDownload($slack),
            'HealthIssue' => $this->handleHealthIssue($slack),
            default => Log::info('Sonarr: unhandled event type', ['event_type' => $eventType]),
        };
    }

    private function handleDownload(SlackReply $slack): void
    {
        $series = $this->data['payload']['series']['title'] ?? 'Unknown Series';
        $episode = $this->data['payload']['episodes'][0]['title'] ?? null;

        $message = $episode
            ? "A new episode of {$series} is ready — \"{$episode}\", sir."
            : "A new episode of {$series} is ready, sir.";

        Log::info('Sonarr: download complete', ['series' => $series, 'episode' => $episode]);

        $slack->run(['message' => $message]);
    }

    private function handleHealthIssue(SlackReply $slack): void
    {
        Log::warning('Sonarr: health issue detected', ['payload' => $this->data['payload'] ?? []]);

        if ($this->attemptRestart('sonarr')) {
            Log::info('Sonarr: service restart successful');

            return;
        }

        Log::error('Sonarr: service restart failed, escalating');

        $slack->run([
            'message' => 'Sir, Sonarr is reporting a health issue and my restart attempt has failed. Your attention is required.',
        ]);
    }

    private function attemptRestart(string $service): bool
    {
        try {
            $url = config("services.{$service}.url").'/api/v3/system/restart?apikey='.config("services.{$service}.api_key");

            $response = Http::timeout(10)->post($url);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("Service restart failed: {$service}", ['error' => $e->getMessage()]);

            return false;
        }
    }
}
