<?php

namespace App\Tools;

use App\Services\JellyfinService;
use Carbon\Carbon;

class RetireList extends CriterionTool
{
    public function __construct(
        private readonly JellyfinService $jellyfin,
    ) {}

    public function name(): string
    {
        return 'retire_list';
    }

    public function description(): string
    {
        return 'List unwatched movies older than N days, sorted by age. Helps identify stale content for retirement.';
    }

    public function execute(array $parameters): array
    {
        $days = $parameters['days'] ?? 90;

        $stale = $this->jellyfin->getStaleItems('Movie', (int) $days);

        $results = array_map(function (array $item) {
            $added = $item['DateCreated'] ?? now()->toIso8601String();
            $daysOld = (int) Carbon::parse($added)->diffInDays(now());

            return [
                'title' => $item['Name'] ?? '',
                'date_added' => $added,
                'days_in_library' => $daysOld,
            ];
        }, $stale);

        usort($results, fn (array $a, array $b) => $b['days_in_library'] <=> $a['days_in_library']);

        return $results;
    }
}
