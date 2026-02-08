<?php

namespace App\Commands;

use Illuminate\Support\Facades\Http;
use LaravelZero\Framework\Commands\Command;

class StatusCommand extends Command
{
    protected $signature = 'curator:status';

    protected $description = 'Check connectivity to Radarr, Sonarr, Jellyfin, and Ollama';

    public function handle(): int
    {
        $this->line('<fg=cyan>═══ Media Curator Status ═══</>');
        $this->newLine();

        $services = [
            'Radarr' => config('services.radarr.url') . '/api/v3/system/status?apikey=' . config('services.radarr.api_key'),
            'Sonarr' => config('services.sonarr.url') . '/api/v3/system/status?apikey=' . config('services.sonarr.api_key'),
            'Jellyfin' => config('services.jellyfin.url') . '/System/Info?api_key=' . config('services.jellyfin.api_key'),
            'Ollama' => config('services.ollama.url') . '/api/tags',
        ];

        $rows = [];
        foreach ($services as $name => $url) {
            try {
                $response = Http::timeout(3)->get($url);
                $status = $response->successful() ? '<fg=green>Connected</>' : '<fg=red>Error (' . $response->status() . ')</>';
            } catch (\Throwable) {
                $status = '<fg=red>Unreachable</>';
            }
            $rows[] = [$name, $status];
        }

        $this->table(['Service', 'Status'], $rows);

        return self::SUCCESS;
    }
}
