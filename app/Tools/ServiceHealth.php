<?php

namespace App\Tools;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ServiceHealth extends CriterionTool
{
    public function name(): string
    {
        return 'service_health';
    }

    public function description(): string
    {
        return 'Check health of all monitored media services.';
    }

    public function execute(array $parameters): array
    {
        $services = $this->services();
        $results = [];

        foreach ($services as $name => $config) {
            $results[$name] = $this->check($name, $config);
        }

        return $results;
    }

    public function services(): array
    {
        return [
            'radarr' => [
                'url' => config('services.radarr.url').'/api/v3/health',
                'headers' => ['X-Api-Key' => config('services.radarr.api_key')],
                'container' => 'radarr',
            ],
            'sonarr' => [
                'url' => config('services.sonarr.url').'/api/v3/health',
                'headers' => ['X-Api-Key' => config('services.sonarr.api_key')],
                'container' => 'sonarr',
            ],
            'jellyfin' => [
                'url' => config('services.jellyfin.url').'/health',
                'headers' => [],
                'container' => 'jellyfin',
            ],
            'prowlarr' => [
                'url' => config('services.prowlarr.url').'/api/v1/health',
                'headers' => ['X-Api-Key' => config('services.prowlarr.api_key')],
                'container' => 'prowlarr',
            ],
            'transmission' => [
                'url' => config('services.transmission.url').'/transmission/rpc',
                'headers' => [],
                'container' => 'transmission',
            ],
        ];
    }

    private function check(string $name, array $config): array
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders($config['headers'])
                ->get($config['url']);

            $healthy = $response->successful() || $response->status() === 409;

            return [
                'healthy' => $healthy,
                'status' => $response->status(),
                'container' => $config['container'],
            ];
        } catch (\Throwable $e) {
            Log::warning("Health check failed for {$name}", [
                'error' => $e->getMessage(),
            ]);

            return [
                'healthy' => false,
                'status' => 0,
                'container' => $config['container'],
            ];
        }
    }
}
