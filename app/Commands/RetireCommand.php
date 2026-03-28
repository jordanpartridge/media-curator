<?php

namespace App\Commands;

use App\Services\JellyfinService;
use App\Services\RadarrService;
use Illuminate\Console\Command;

class RetireCommand extends Command
{
    protected $signature = 'curator:retire {--days=90 : Minimum age in days before considering retirement} {--type=Movie : Content type (Movie or Series)}';

    protected $description = 'Identify stale unwatched content for retirement based on Jellyfin watch data';

    public function handle(JellyfinService $jellyfin, RadarrService $radarr): int
    {
        $days = (int) $this->option('days');
        $type = $this->option('type');

        $this->info("Scanning for unwatched {$type} content older than {$days} days...");

        try {
            $stale = $jellyfin->getStaleItems($type, $days);
        } catch (\Throwable $e) {
            $this->error("Failed to query Jellyfin: {$e->getMessage()}");

            return self::FAILURE;
        }

        if (empty($stale)) {
            $this->info('No stale content found. Your library is well-curated!');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line('<fg=yellow>═══ Retirement Candidates ═══</>');
        $this->newLine();

        $rows = [];
        foreach (array_values($stale) as $item) {
            $name = $item['Name'] ?? 'Unknown';
            $added = isset($item['DateCreated']) ? substr($item['DateCreated'], 0, 10) : '?';
            $age = isset($item['DateCreated']) ? now()->diffInDays($item['DateCreated']) . 'd' : '?';

            $rows[] = [$name, $added, $age, 'Never'];
        }

        $this->table(['Title', 'Added', 'Age', 'Watched'], $rows);

        $this->newLine();
        $this->comment(count($stale) . " item(s) identified. Review and remove via Radarr/Sonarr if desired.");
        $this->comment("To auto-remove, future versions will support: curator:retire --execute");

        return self::SUCCESS;
    }
}
