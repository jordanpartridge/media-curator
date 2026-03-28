<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RadarrService
{
    private string $baseUrl;

    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.radarr.url'), '/');
        $this->apiKey = config('services.radarr.api_key');
    }

    public function getMovies(): array
    {
        return $this->get('/api/v3/movie');
    }

    public function addMovie(int $tmdbId, string $title, int $qualityProfileId = 1, string $rootFolderPath = '/movies'): array
    {
        return $this->post('/api/v3/movie', [
            'tmdbId' => $tmdbId,
            'title' => $title,
            'qualityProfileId' => $qualityProfileId,
            'rootFolderPath' => $rootFolderPath,
            'monitored' => true,
            'addOptions' => ['searchForMovie' => true],
        ]);
    }

    public function deleteMovie(int $id, bool $deleteFiles = true): void
    {
        Http::timeout(10)
            ->get("{$this->baseUrl}/api/v3/movie/{$id}?apikey={$this->apiKey}&deleteFiles=".($deleteFiles ? 'true' : 'false'));
    }

    public function lookupByTmdb(int $tmdbId): array
    {
        return $this->get("/api/v3/movie/lookup/tmdb?tmdbId={$tmdbId}");
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
