<?php

use App\Jobs\ProcessRadarrEvent;
use App\Jobs\ProcessSonarrEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

describe('criterion:listen', function () {
    it('subscribes to bifrost Redis channels and dispatches radarr jobs', function () {
        Queue::fake();

        $radarrPayload = json_encode([
            'source' => 'radarr',
            'event_type' => 'Download',
            'payload' => ['movie' => ['title' => 'The Godfather']],
        ]);

        Redis::shouldReceive('subscribe')
            ->once()
            ->with(['bifrost.radarr', 'bifrost.sonarr'], Mockery::on(function ($callback) use ($radarrPayload) {
                $callback($radarrPayload, 'bifrost.radarr');

                return true;
            }));

        $this->artisan('criterion:listen')->assertSuccessful();

        Queue::assertPushedOn('media', ProcessRadarrEvent::class);
    });

    it('subscribes to bifrost Redis channels and dispatches sonarr jobs', function () {
        Queue::fake();

        $sonarrPayload = json_encode([
            'source' => 'sonarr',
            'event_type' => 'Download',
            'payload' => ['series' => ['title' => 'Breaking Bad'], 'episodes' => []],
        ]);

        Redis::shouldReceive('subscribe')
            ->once()
            ->with(['bifrost.radarr', 'bifrost.sonarr'], Mockery::on(function ($callback) use ($sonarrPayload) {
                $callback($sonarrPayload, 'bifrost.sonarr');

                return true;
            }));

        $this->artisan('criterion:listen')->assertSuccessful();

        Queue::assertPushedOn('media', ProcessSonarrEvent::class);
    });

    it('logs a warning for invalid JSON messages', function () {
        Log::shouldReceive('info')->andReturnNull();
        Log::shouldReceive('warning')
            ->once()
            ->with('Bifrost: invalid JSON received', Mockery::any());

        Redis::shouldReceive('subscribe')
            ->once()
            ->with(['bifrost.radarr', 'bifrost.sonarr'], Mockery::on(function ($callback) {
                $callback('not-valid-json', 'bifrost.radarr');

                return true;
            }));

        $this->artisan('criterion:listen')->assertSuccessful();
    });

    it('logs a warning for unknown channels', function () {
        Log::shouldReceive('info')->andReturnNull();
        Log::shouldReceive('warning')
            ->once()
            ->with('Bifrost: unknown channel', ['channel' => 'bifrost.unknown']);

        Redis::shouldReceive('subscribe')
            ->once()
            ->with(['bifrost.radarr', 'bifrost.sonarr'], Mockery::on(function ($callback) {
                $callback('{"event_type":"Test"}', 'bifrost.unknown');

                return true;
            }));

        $this->artisan('criterion:listen')->assertSuccessful();
    });
});
