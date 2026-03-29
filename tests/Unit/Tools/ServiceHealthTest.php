<?php

use App\Tools\ServiceHealth;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

describe('ServiceHealth', function () {
    beforeEach(function () {
        $this->tool = app(ServiceHealth::class);
    });

    it('has the correct tool name', function () {
        expect($this->tool->name())->toBe('service_health');
    });

    it('has a description', function () {
        expect($this->tool->description())->toBeString()->not->toBeEmpty();
    });

    it('returns healthy status for reachable services', function () {
        Http::fake([
            '*/api/v3/health' => Http::response([], 200),
            '*/health' => Http::response('Healthy', 200),
            '*/api/v1/health' => Http::response([], 200),
            '*/transmission/rpc' => Http::response('', 409),
        ]);

        $results = $this->tool->execute([]);

        expect($results)
            ->toHaveKeys(['radarr', 'sonarr', 'jellyfin', 'prowlarr', 'transmission']);

        foreach ($results as $service) {
            expect($service['healthy'])->toBeTrue();
        }
    });

    it('returns unhealthy status for unreachable services', function () {
        Http::fake([
            '*' => Http::response('Server Error', 500),
        ]);

        $results = $this->tool->execute([]);

        foreach ($results as $service) {
            expect($service['healthy'])->toBeFalse();
        }
    });

    it('handles connection timeouts gracefully', function () {
        Http::fake([
            '*' => fn () => throw new ConnectionException('Connection timed out'),
        ]);

        $results = $this->tool->execute([]);

        foreach ($results as $service) {
            expect($service['healthy'])->toBeFalse();
            expect($service['status'])->toBe(0);
        }
    });

    it('treats HTTP 409 as healthy for Transmission RPC', function () {
        Http::fake([
            '*/api/v3/health' => Http::response([], 200),
            '*/health' => Http::response('Healthy', 200),
            '*/api/v1/health' => Http::response([], 200),
            '*/transmission/rpc' => Http::response('', 409),
        ]);

        $results = $this->tool->execute([]);

        expect($results['transmission']['healthy'])->toBeTrue();
    });

    it('includes container name in each result', function () {
        Http::fake(['*' => Http::response([], 200)]);

        $results = $this->tool->execute([]);

        expect($results['radarr']['container'])->toBe('radarr');
        expect($results['sonarr']['container'])->toBe('sonarr');
        expect($results['jellyfin']['container'])->toBe('jellyfin');
        expect($results['prowlarr']['container'])->toBe('prowlarr');
        expect($results['transmission']['container'])->toBe('transmission');
    });

    it('defines all five monitored services', function () {
        $services = $this->tool->services();

        expect($services)->toHaveCount(5)
            ->toHaveKeys(['radarr', 'sonarr', 'jellyfin', 'prowlarr', 'transmission']);
    });
});
