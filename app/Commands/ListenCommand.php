<?php

namespace App\Commands;

use App\Jobs\ProcessRadarrEvent;
use App\Jobs\ProcessSonarrEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ListenCommand extends Command
{
    protected $signature = 'criterion:listen';

    protected $description = 'Subscribe to Bifrost Redis channels and react to Radarr/Sonarr events';

    /** @var array<string, class-string> */
    private array $channelMap = [
        'bifrost.radarr' => ProcessRadarrEvent::class,
        'bifrost.sonarr' => ProcessSonarrEvent::class,
    ];

    public function handle(): int
    {
        $channels = array_keys($this->channelMap);

        $this->info('Criterion listening on: '.implode(', ', $channels));

        Log::info('Bifrost listener started', ['channels' => $channels]);

        Redis::subscribe($channels, function (string $message, string $channel) {
            $this->processMessage($message, $channel);
        });

        return self::SUCCESS;
    }

    private function processMessage(string $message, string $channel): void
    {
        $data = json_decode($message, true);

        if (! is_array($data)) {
            Log::warning('Bifrost: invalid JSON received', [
                'channel' => $channel,
                'raw' => $message,
            ]);

            return;
        }

        $jobClass = $this->channelMap[$channel] ?? null;

        if (! $jobClass) {
            Log::warning('Bifrost: unknown channel', ['channel' => $channel]);

            return;
        }

        Log::info('Bifrost: dispatching event', [
            'channel' => $channel,
            'event_type' => $data['event_type'] ?? 'unknown',
        ]);

        $jobClass::dispatch($data)->onQueue('media');
    }
}
