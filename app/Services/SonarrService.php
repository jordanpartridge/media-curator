<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SonarrService
{
    private string $baseUrl;

    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.sonarr.url'), '/');
        $this->apiKey = config('services.sonarr.api_key');
    }

    public function getSeries(): array
    {
        return $this->get('/api/v3/series');
    }

    public function addSeries(int $tvdbId, string $title, int $qualityProfileId = 1, string $rootFolderPath = '/tv'): array
    {
        return $this->post('/api/v3/series', [
            'tvdbId' => $tvdbId,
            'title' => $title,
            'qualityProfileId' => $qualityProfileId,
            'rootFolderPath' => $rootFolderPath,
            'monitored' => true,
            'addOptions' => ['searchForMissingEpisodes' => true],
        ]);
    }

    public function deleteSeries(int $id, bool $deleteFiles = true): void
    {
        Http::timeout(10)
            ->delete("{$this->baseUrl}/api/v3/series/{$id}", [
                'apikey' => $this->apiKey,
                'deleteFiles' => $deleteFiles,
            ]);
    }

    private function get(string $path): array
    {
        $response = Http::timeout(10)
            ->get("{$this->baseUrl}{$path}", ['apikey' => $this->apiKey]);

        $response->throw();

        return $response->json() ?? [];
    }

    private function post(string $path, array $data): array
    {
        $response = Http::timeout(10)
            ->withQueryParameters(['apikey' => $this->apiKey])
            ->post("{$this->baseUrl}{$path}", $data);

        $response->throw();

        return $response->json() ?? [];
    }
}
