<?php

use App\Jobs\ProcessSonarrEvent;
use App\Tools\SlackReply;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->slack = Mockery::mock(SlackReply::class);
    $this->app->instance(SlackReply::class, $this->slack);
});

describe('ProcessSonarrEvent', function () {
    describe('Download event', function () {
        it('sends a Slack notification with series and episode title', function () {
            $this->slack->shouldReceive('run')
                ->once()
                ->with(Mockery::on(fn (array $params) => str_contains($params['message'], 'Breaking Bad')
                    && str_contains($params['message'], 'Ozymandias'),
                ))
                ->andReturn(true);

            $job = new ProcessSonarrEvent([
                'source' => 'sonarr',
                'event_type' => 'Download',
                'payload' => [
                    'series' => ['title' => 'Breaking Bad'],
                    'episodes' => [['title' => 'Ozymandias']],
                ],
            ]);

            $job->handle($this->slack);
        });

        it('sends notification without episode title when missing', function () {
            $this->slack->shouldReceive('run')
                ->once()
                ->with(Mockery::on(fn (array $params) => str_contains($params['message'], 'The Mandalorian')
                    && ! str_contains($params['message'], '""'),
                ))
                ->andReturn(true);

            $job = new ProcessSonarrEvent([
                'source' => 'sonarr',
                'event_type' => 'Download',
                'payload' => [
                    'series' => ['title' => 'The Mandalorian'],
                    'episodes' => [],
                ],
            ]);

            $job->handle($this->slack);
        });
    });

    describe('HealthIssue event', function () {
        it('attempts a service restart and does not escalate on success', function () {
            Http::fake([
                '*/api/v3/system/restart*' => Http::response([], 200),
            ]);

            $this->slack->shouldNotReceive('run');

            $job = new ProcessSonarrEvent([
                'source' => 'sonarr',
                'event_type' => 'HealthIssue',
                'payload' => ['message' => 'Missing episodes'],
            ]);

            $job->handle($this->slack);
        });

        it('escalates to Slack when restart fails', function () {
            Http::fake([
                '*/api/v3/system/restart*' => Http::response([], 500),
            ]);

            $this->slack->shouldReceive('run')
                ->once()
                ->with(Mockery::on(fn (array $params) => str_contains($params['message'], 'health issue')))
                ->andReturn(true);

            $job = new ProcessSonarrEvent([
                'source' => 'sonarr',
                'event_type' => 'HealthIssue',
                'payload' => ['message' => 'Missing episodes'],
            ]);

            $job->handle($this->slack);
        });

        it('escalates to Slack when restart throws an exception', function () {
            Http::fake([
                '*/api/v3/system/restart*' => fn () => throw new RuntimeException('Connection refused'),
            ]);

            $this->slack->shouldReceive('run')
                ->once()
                ->with(Mockery::on(fn (array $params) => str_contains($params['message'], 'health issue')))
                ->andReturn(true);

            $job = new ProcessSonarrEvent([
                'source' => 'sonarr',
                'event_type' => 'HealthIssue',
                'payload' => ['message' => 'Connection lost'],
            ]);

            $job->handle($this->slack);
        });
    });

    describe('unknown event', function () {
        it('logs the unhandled event type', function () {
            Log::shouldReceive('info')
                ->atLeast()
                ->once()
                ->with('Sonarr: unhandled event type', ['event_type' => 'Rename']);

            $job = new ProcessSonarrEvent([
                'source' => 'sonarr',
                'event_type' => 'Rename',
                'payload' => [],
            ]);

            $job->handle($this->slack);
        });
    });

    it('is dispatched on the media queue', function () {
        $job = new ProcessSonarrEvent(['event_type' => 'Download', 'payload' => ['series' => ['title' => 'Test']]]);

        expect($job)->toBeInstanceOf(ShouldQueue::class);
    });
});
