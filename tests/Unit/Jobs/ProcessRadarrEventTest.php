<?php

use App\Jobs\ProcessRadarrEvent;
use App\Tools\SlackReply;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->slack = Mockery::mock(SlackReply::class);
    $this->app->instance(SlackReply::class, $this->slack);
});

describe('ProcessRadarrEvent', function () {
    describe('Download event', function () {
        it('sends a Slack notification with the movie title', function () {
            $this->slack->shouldReceive('run')
                ->once()
                ->with(Mockery::on(fn (array $params) => str_contains($params['message'], 'The Godfather')))
                ->andReturn(true);

            $job = new ProcessRadarrEvent([
                'source' => 'radarr',
                'event_type' => 'Download',
                'payload' => ['movie' => ['title' => 'The Godfather']],
            ]);

            $job->handle($this->slack);
        });

        it('uses "Unknown Title" when movie title is missing', function () {
            $this->slack->shouldReceive('run')
                ->once()
                ->with(Mockery::on(fn (array $params) => str_contains($params['message'], 'Unknown Title')))
                ->andReturn(true);

            $job = new ProcessRadarrEvent([
                'source' => 'radarr',
                'event_type' => 'Download',
                'payload' => ['movie' => []],
            ]);

            $job->handle($this->slack);
        });
    });

    describe('Grab event', function () {
        it('logs the grab without sending a Slack message', function () {
            Log::shouldReceive('info')
                ->atLeast()
                ->once()
                ->with('Radarr: grab initiated', Mockery::any());

            $this->slack->shouldNotReceive('run');

            $job = new ProcessRadarrEvent([
                'source' => 'radarr',
                'event_type' => 'Grab',
                'payload' => ['movie' => ['title' => 'Inception']],
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

            $job = new ProcessRadarrEvent([
                'source' => 'radarr',
                'event_type' => 'HealthIssue',
                'payload' => ['message' => 'Disk space low'],
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

            $job = new ProcessRadarrEvent([
                'source' => 'radarr',
                'event_type' => 'HealthIssue',
                'payload' => ['message' => 'Disk space low'],
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

            $job = new ProcessRadarrEvent([
                'source' => 'radarr',
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
                ->with('Radarr: unhandled event type', ['event_type' => 'Rename']);

            $job = new ProcessRadarrEvent([
                'source' => 'radarr',
                'event_type' => 'Rename',
                'payload' => [],
            ]);

            $job->handle($this->slack);
        });
    });

    it('is dispatched on the media queue', function () {
        $job = new ProcessRadarrEvent(['event_type' => 'Download', 'payload' => ['movie' => ['title' => 'Test']]]);

        expect($job)->toBeInstanceOf(ShouldQueue::class);
    });
});
