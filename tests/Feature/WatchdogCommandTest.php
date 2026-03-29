<?php

use App\Tools\ServiceHealth;
use App\Tools\ServiceRestart;
use App\Tools\SlackReply;
use Illuminate\Support\Facades\Http;

describe('criterion:watchdog', function () {
    it('reports success when all services are healthy', function () {
        Http::fake([
            '*/api/v3/health' => Http::response([], 200),
            '*/health' => Http::response('Healthy', 200),
            '*/transmission/rpc' => Http::response('', 409),
            '*/api/v1/health' => Http::response([], 200),
        ]);

        $this->artisan('criterion:watchdog')
            ->assertExitCode(0);
    });

    it('does not attempt restart when all services are healthy', function () {
        Http::fake([
            '*/api/v3/health' => Http::response([], 200),
            '*/health' => Http::response('Healthy', 200),
            '*/transmission/rpc' => Http::response('', 409),
            '*/api/v1/health' => Http::response([], 200),
        ]);

        $this->mock(ServiceRestart::class, function ($mock) {
            $mock->shouldNotReceive('run');
        });

        $this->artisan('criterion:watchdog')
            ->assertExitCode(0);
    });

    it('escalates to Slack when restart fails to recover a service', function () {
        $this->mock(ServiceHealth::class, function ($mock) {
            $mock->shouldReceive('run')
                ->once()
                ->andReturn([
                    'radarr' => ['healthy' => false, 'status' => 500, 'container' => 'radarr'],
                    'sonarr' => ['healthy' => true, 'status' => 200, 'container' => 'sonarr'],
                    'jellyfin' => ['healthy' => true, 'status' => 200, 'container' => 'jellyfin'],
                    'prowlarr' => ['healthy' => true, 'status' => 200, 'container' => 'prowlarr'],
                    'transmission' => ['healthy' => true, 'status' => 409, 'container' => 'transmission'],
                ]);
        });

        $this->mock(ServiceRestart::class, function ($mock) {
            $mock->shouldReceive('run')
                ->once()
                ->with(Mockery::on(fn ($params) => $params['container'] === 'radarr'))
                ->andReturn(['restarted' => true, 'recovered' => false]);
        });

        $this->mock(SlackReply::class, function ($mock) {
            $mock->shouldReceive('run')
                ->once()
                ->with(Mockery::on(fn ($params) => str_contains($params['message'], 'Radarr is unresponsive, sir')))
                ->andReturn(true);
        });

        $this->artisan('criterion:watchdog')
            ->assertExitCode(1);
    });

    it('uses Criterion voice in escalation messages', function () {
        $this->mock(ServiceHealth::class, function ($mock) {
            $mock->shouldReceive('run')
                ->once()
                ->andReturn([
                    'radarr' => ['healthy' => false, 'status' => 0, 'container' => 'radarr'],
                    'sonarr' => ['healthy' => false, 'status' => 0, 'container' => 'sonarr'],
                    'jellyfin' => ['healthy' => true, 'status' => 200, 'container' => 'jellyfin'],
                    'prowlarr' => ['healthy' => true, 'status' => 200, 'container' => 'prowlarr'],
                    'transmission' => ['healthy' => true, 'status' => 409, 'container' => 'transmission'],
                ]);
        });

        $this->mock(ServiceRestart::class, function ($mock) {
            $mock->shouldReceive('run')
                ->twice()
                ->andReturn(['restarted' => true, 'recovered' => false]);
        });

        $messages = [];
        $this->mock(SlackReply::class, function ($mock) use (&$messages) {
            $mock->shouldReceive('run')
                ->twice()
                ->andReturnUsing(function ($params) use (&$messages) {
                    $messages[] = $params['message'];

                    return true;
                });
        });

        $this->artisan('criterion:watchdog')
            ->assertExitCode(1);

        expect($messages[0])->toContain('Radarr is unresponsive, sir. I was unable to revive it.');
        expect($messages[1])->toContain('Sonarr is unresponsive, sir. I was unable to revive it.');
    });

    it('does not escalate when a service recovers after restart', function () {
        $this->mock(ServiceHealth::class, function ($mock) {
            $mock->shouldReceive('run')
                ->once()
                ->andReturn([
                    'radarr' => ['healthy' => false, 'status' => 500, 'container' => 'radarr'],
                    'sonarr' => ['healthy' => true, 'status' => 200, 'container' => 'sonarr'],
                    'jellyfin' => ['healthy' => true, 'status' => 200, 'container' => 'jellyfin'],
                    'prowlarr' => ['healthy' => true, 'status' => 200, 'container' => 'prowlarr'],
                    'transmission' => ['healthy' => true, 'status' => 409, 'container' => 'transmission'],
                ]);
        });

        $this->mock(ServiceRestart::class, function ($mock) {
            $mock->shouldReceive('run')
                ->once()
                ->andReturn(['restarted' => true, 'recovered' => true]);
        });

        $this->mock(SlackReply::class, function ($mock) {
            $mock->shouldNotReceive('run');
        });

        $this->artisan('criterion:watchdog')
            ->assertExitCode(0);
    });

    it('returns failure exit code when any service remains down', function () {
        $this->mock(ServiceHealth::class, function ($mock) {
            $mock->shouldReceive('run')
                ->once()
                ->andReturn([
                    'radarr' => ['healthy' => false, 'status' => 0, 'container' => 'radarr'],
                    'sonarr' => ['healthy' => true, 'status' => 200, 'container' => 'sonarr'],
                    'jellyfin' => ['healthy' => true, 'status' => 200, 'container' => 'jellyfin'],
                    'prowlarr' => ['healthy' => true, 'status' => 200, 'container' => 'prowlarr'],
                    'transmission' => ['healthy' => true, 'status' => 409, 'container' => 'transmission'],
                ]);
        });

        $this->mock(ServiceRestart::class, function ($mock) {
            $mock->shouldReceive('run')
                ->once()
                ->andReturn(['restarted' => true, 'recovered' => false]);
        });

        $this->mock(SlackReply::class, function ($mock) {
            $mock->shouldReceive('run')
                ->once()
                ->andReturn(true);
        });

        $this->artisan('criterion:watchdog')
            ->assertExitCode(1);
    });
});
