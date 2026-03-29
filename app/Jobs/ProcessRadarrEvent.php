<?php

namespace App\Jobs;

use App\Tools\SlackReply;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessRadarrEvent implements ShouldQueue
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
            'Grab' => $this->handleGrab(),
            'HealthIssue' => $this->handleHealthIssue($slack),
            default => Log::info('Radarr: unhandled event type', ['event_type' => $eventType]),
        };
    }

    private function handleDownload(SlackReply $slack): void
    {
        $title = $this->data['payload']['movie']['title'] ?? 'Unknown Title';

        Log::info('Radarr: download complete', ['title' => $title]);

        $slack->run([
            'message' => "Your copy of {$title} has arrived, sir.",
        ]);
    }

    private function handleGrab(): void
    {
        $title = $this->data['payload']['movie']['title'] ?? 'Unknown Title';

        Log::info('Radarr: grab initiated', ['title' => $title]);
    }

    private function handleHealthIssue(SlackReply $slack): void
    {
        Log::warning('Radarr: health issue detected', ['payload' => $this->data['payload'] ?? []]);

        if ($this->attemptRestart('radarr')) {
            Log::info('Radarr: service restart successful');

            return;
        }

        Log::error('Radarr: service restart failed, escalating');

        $slack->run([
            'message' => 'Sir, Radarr is reporting a health issue and my restart attempt has failed. Your attention is required.',
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
