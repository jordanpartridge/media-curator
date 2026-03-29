<?php

namespace App\Tools;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class ServiceRestart extends CriterionTool
{
    public function name(): string
    {
        return 'service_restart';
    }

    public function description(): string
    {
        return 'Restart a media service container via Podman and verify recovery.';
    }

    public function execute(array $parameters): array
    {
        $container = $parameters['container'] ?? '';

        if ($container === '') {
            return ['restarted' => false, 'recovered' => false, 'error' => 'No container specified'];
        }

        $allowed = ['radarr', 'sonarr', 'jellyfin', 'prowlarr', 'transmission'];

        if (! in_array($container, $allowed, true)) {
            return ['restarted' => false, 'recovered' => false, 'error' => 'Invalid container name'];
        }

        Log::info("Attempting podman restart for {$container}");

        $result = Process::timeout(30)->run("podman restart {$container}");

        if (! $result->successful()) {
            Log::error("Podman restart failed for {$container}", [
                'output' => $result->errorOutput(),
            ]);

            return ['restarted' => false, 'recovered' => false, 'error' => $result->errorOutput()];
        }

        sleep($parameters['wait'] ?? 10);

        $health = app(ServiceHealth::class);
        $services = $health->services();
        $serviceConfig = $services[$container] ?? null;

        if (! $serviceConfig) {
            return ['restarted' => true, 'recovered' => false, 'error' => 'No health config for container'];
        }

        $recheck = $health->run(['service' => $container]);
        $recovered = ($recheck[$container]['healthy'] ?? false);

        Log::info("Restart result for {$container}", ['recovered' => $recovered]);

        return ['restarted' => true, 'recovered' => $recovered];
    }
}
