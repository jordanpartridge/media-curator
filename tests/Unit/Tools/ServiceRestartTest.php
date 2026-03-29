<?php

use App\Tools\ServiceHealth;
use App\Tools\ServiceRestart;
use Illuminate\Support\Facades\Process;

describe('ServiceRestart', function () {
    beforeEach(function () {
        $this->tool = app(ServiceRestart::class);
    });

    it('has the correct tool name', function () {
        expect($this->tool->name())->toBe('service_restart');
    });

    it('has a description', function () {
        expect($this->tool->description())->toBeString()->not->toBeEmpty();
    });

    it('rejects empty container name', function () {
        $result = $this->tool->execute([]);

        expect($result['restarted'])->toBeFalse();
        expect($result['error'])->toBe('No container specified');
    });

    it('rejects invalid container names', function () {
        $result = $this->tool->execute(['container' => 'malicious-container']);

        expect($result['restarted'])->toBeFalse();
        expect($result['error'])->toBe('Invalid container name');
    });

    it('accepts all five valid container names', function () {
        $valid = ['radarr', 'sonarr', 'jellyfin', 'prowlarr', 'transmission'];

        foreach ($valid as $container) {
            Process::fake([
                "podman restart {$container}" => Process::result(output: $container),
            ]);

            $mock = Mockery::mock(ServiceHealth::class);
            $mock->shouldReceive('services')->andReturn([
                $container => [
                    'url' => "http://localhost/{$container}",
                    'headers' => [],
                    'container' => $container,
                ],
            ]);
            $mock->shouldReceive('run')->andReturn([
                $container => ['healthy' => true, 'status' => 200, 'container' => $container],
            ]);

            $this->app->instance(ServiceHealth::class, $mock);

            $result = $this->tool->execute(['container' => $container, 'wait' => 0]);

            expect($result['restarted'])->toBeTrue();
        }
    });

    it('reports failure when podman restart fails', function () {
        Process::fake([
            'podman restart radarr' => Process::result(exitCode: 1, errorOutput: 'no such container'),
        ]);

        $result = $this->tool->execute(['container' => 'radarr', 'wait' => 0]);

        expect($result['restarted'])->toBeFalse();
        expect($result['recovered'])->toBeFalse();
        expect($result['error'])->toContain('no such container');
    });

    it('reports recovered true when service comes back after restart', function () {
        Process::fake([
            '*' => Process::result(output: 'radarr'),
        ]);

        $mock = Mockery::mock(ServiceHealth::class);
        $mock->shouldReceive('services')->andReturn([
            'radarr' => [
                'url' => 'http://localhost/api/v3/health',
                'headers' => [],
                'container' => 'radarr',
            ],
        ]);
        $mock->shouldReceive('run')->andReturn([
            'radarr' => ['healthy' => true, 'status' => 200, 'container' => 'radarr'],
        ]);

        $this->app->instance(ServiceHealth::class, $mock);

        $result = $this->tool->execute(['container' => 'radarr', 'wait' => 0]);

        expect($result['restarted'])->toBeTrue();
        expect($result['recovered'])->toBeTrue();
    });

    it('reports recovered false when service stays down after restart', function () {
        Process::fake([
            '*' => Process::result(output: 'radarr'),
        ]);

        $mock = Mockery::mock(ServiceHealth::class);
        $mock->shouldReceive('services')->andReturn([
            'radarr' => [
                'url' => 'http://localhost/api/v3/health',
                'headers' => [],
                'container' => 'radarr',
            ],
        ]);
        $mock->shouldReceive('run')->andReturn([
            'radarr' => ['healthy' => false, 'status' => 500, 'container' => 'radarr'],
        ]);

        $this->app->instance(ServiceHealth::class, $mock);

        $result = $this->tool->execute(['container' => 'radarr', 'wait' => 0]);

        expect($result['restarted'])->toBeTrue();
        expect($result['recovered'])->toBeFalse();
    });
});
