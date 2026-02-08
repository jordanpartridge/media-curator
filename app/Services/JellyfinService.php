<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class JellyfinService
{
    private string $baseUrl;

    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.jellyfin.url'), '/');
        $this->apiKey = config('services.jellyfin.api_key');
    }

    /**
     * Get all items (movies and series) with play statistics.
     */
    public function getItems(string $type = 'Movie', int $limit = 200): array
    {
        $response = Http::timeout(10)
            ->get("{$this->baseUrl}/Items", [
                'api_key' => $this->apiKey,
                'IncludeItemTypes' => $type,
                'Recursive' => 'true',
                'Fields' => 'DateCreated,UserData',
                'Limit' => $limit,
                'SortBy' => 'DateCreated',
                'SortOrder' => 'Descending',
            ]);

        $response->throw();

        return $response->json('Items') ?? [];
    }

    /**
     * Get items that have never been played.
     */
    public function getUnwatched(string $type = 'Movie'): array
    {
        $items = $this->getItems($type);

        return array_filter($items, function (array $item): bool {
            return ($item['UserData']['PlayCount'] ?? 0) === 0;
        });
    }

    /**
     * Get items added before a given date that remain unwatched.
     */
    public function getStaleItems(string $type = 'Movie', int $olderThanDays = 90): array
    {
        $cutoff = now()->subDays($olderThanDays)->toIso8601String();
        $unwatched = $this->getUnwatched($type);

        return array_filter($unwatched, function (array $item) use ($cutoff): bool {
            $added = $item['DateCreated'] ?? '';

            return $added !== '' && $added < $cutoff;
        });
    }
}
