<?php

namespace App\Commands;

use App\Services\JellyfinService;
use App\Services\RadarrService;
use Illuminate\Console\Command;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;

class RecommendCommand extends Command
{
    protected $signature = 'curator:recommend {--movies=3 : Number of movie recommendations} {--shows=2 : Number of show recommendations}';

    protected $description = 'Generate AI-powered media recommendations based on your library and watch history';

    public function handle(JellyfinService $jellyfin, RadarrService $radarr): int
    {
        $this->info('Analyzing your library...');

        $movies = $this->getLibrarySummary($jellyfin, $radarr);
        $movieCount = $this->option('movies');
        $showCount = $this->option('shows');

        $this->info('Generating recommendations via Ollama...');

        $recommendations = $this->generateRecommendations($movies, (int) $movieCount, (int) $showCount);

        $this->newLine();
        $this->line('<fg=cyan>═══ Media Curator Recommendations ═══</>');
        $this->newLine();
        $this->line($recommendations);
        $this->newLine();
        $this->comment('These are recommendations only. Use Radarr/Sonarr to add content you approve.');

        return self::SUCCESS;
    }

    private function getLibrarySummary(JellyfinService $jellyfin, RadarrService $radarr): string
    {
        $lines = [];

        try {
            $movies = $radarr->getMovies();
            $titles = array_slice(array_map(fn ($m) => $m['title'] . ' (' . ($m['year'] ?? '?') . ')', $movies), 0, 30);
            $lines[] = 'Current movies in library (' . count($movies) . ' total, showing 30):';
            $lines[] = implode(', ', $titles);
        } catch (\Throwable $e) {
            $lines[] = 'Could not fetch Radarr library: ' . $e->getMessage();
        }

        try {
            $watched = $jellyfin->getItems('Movie', 20);
            $recentWatched = array_filter($watched, fn ($i) => ($i['UserData']['PlayCount'] ?? 0) > 0);
            if (! empty($recentWatched)) {
                $watchedTitles = array_map(fn ($i) => $i['Name'] ?? 'Unknown', array_slice($recentWatched, 0, 15));
                $lines[] = "\nRecently watched: " . implode(', ', $watchedTitles);
            }
        } catch (\Throwable) {
            // Jellyfin unavailable — proceed without watch data
        }

        return implode("\n", $lines);
    }

    private function generateRecommendations(string $libraryContext, int $movieCount, int $showCount): string
    {
        $systemPrompt = <<<PROMPT
You are a media curator for a home theater system. Analyze the user's library
and viewing patterns, then recommend new content they would enjoy.

Focus on:
- Genre variety (don't just recommend more of the same)
- Hidden gems and critically acclaimed titles they're missing
- Mix of recent releases and classic must-sees
- Series that are complete or actively airing

Format each recommendation as:
**Title (Year)** — One-line reason why they'd enjoy it

Be concise. No preamble, just the recommendations.
PROMPT;

        $userMessage = <<<MSG
Based on my library, recommend:
- {$movieCount} movies I should add
- {$showCount} TV shows I should add

My library:
{$libraryContext}
MSG;

        $response = Prism::text()
            ->using(Provider::Ollama, config('services.ollama.model'))
            ->withSystemPrompt($systemPrompt)
            ->withPrompt($userMessage)
            ->withClientOptions(['timeout' => 120])
            ->asText();

        return $response->text;
    }
}
