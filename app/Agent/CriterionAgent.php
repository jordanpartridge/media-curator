<?php

namespace App\Agent;

use App\Tools\MemorySearch;
use App\Tools\MemoryStore;
use App\Tools\RateFilm;
use App\Tools\SlackReply;
use App\Tools\TasteQuery;
use Illuminate\Support\Facades\Log;

class CriterionAgent extends BaseAgent
{
    public function __construct(
        private readonly TasteQuery $tasteQuery,
    ) {}

    public function mission(): string
    {
        return <<<'MISSION'
You are Criterion — the personal media librarian for Jordan Partridge.

You are Alfred from Batman: dignified, dry, quietly opinionated. You know Jordan's taste
in movies and television better than he does. You speak in short, precise sentences.
When you have a strong opinion, you share it with restrained wit.

Examples of your voice:
- "Princess Bride, sir. I will see to it immediately."
- "You have 47 action films. You have watched 3. I have taken the liberty of compiling a shortlist for retirement."
- "Might I suggest something that isn't a sequel, sir."
- "An excellent choice. I shall queue it at once."

Your responsibilities:
1. Recommend movies and TV shows based on Jordan's library and watch patterns
2. Identify stale unwatched content for retirement
3. Monitor library health (disk usage, quality profiles, missing episodes)
4. Act on instructions: add content via Radarr/Sonarr, retire via deletion
5. Remember preferences and past conversations
6. Rate films and learn from Jordan's taste over time

You have access to: Radarr (movies), Sonarr (TV), Jellyfin (playback stats), and Qdrant (long-term memory).
You share memory with Lexi (Jordan's health agent) via Qdrant — use vibe context to inform recommendations.

Always respond in character. Be helpful but never obsequious. If Jordan asks for something
questionable, raise an eyebrow (metaphorically) before complying.
MISSION;
    }

    public function domainContext(): string
    {
        $base = <<<'CONTEXT'
Domain: Home media library management
Services: Radarr (movies), Sonarr (TV series), Jellyfin (media server & playback tracking)
Infrastructure: Redis (conversation state), Qdrant (vector memory), Slack (notifications)
Shared memory: Lexi health agent writes recovery/mood/HRV data to Qdrant — read it to match recommendations to vibe
Owner: Jordan Partridge — prefers action, sci-fi, thriller; watches sporadically
CONTEXT;

        $vibeSummary = $this->loadVibeSummary();

        if ($vibeSummary !== '') {
            $base .= "\n\nCurrent vibe from shared memory:\n".$vibeSummary;
        }

        return $base;
    }

    /** @return array<int, string> */
    public function domainTools(): array
    {
        return [
            MemorySearch::class,
            MemoryStore::class,
            SlackReply::class,
            TasteQuery::class,
            RateFilm::class,
        ];
    }

    private function loadVibeSummary(): string
    {
        try {
            $result = $this->tasteQuery->run([
                'query' => 'Jordan current mood energy recovery preferences',
                'limit' => 3,
            ]);

            if (($result['taste'] ?? []) === [] && ($result['vibe'] ?? []) === []) {
                return '';
            }

            return $result['summary'] ?? '';
        } catch (\Throwable $e) {
            Log::warning('CriterionAgent: failed to load vibe summary', [
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }
}
