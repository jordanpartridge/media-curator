<?php

namespace App\Tools;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackReply extends CriterionTool
{
    public function name(): string
    {
        return 'slack_reply';
    }

    public function description(): string
    {
        return 'Send a message to the configured Slack channel.';
    }

    public function execute(array $parameters): bool
    {
        $message = $parameters['message'] ?? '';
        $channel = $parameters['channel'] ?? config('criterion.slack.channel');
        $token = config('criterion.slack.bot_token');

        if ($message === '' || ! $token) {
            Log::warning('SlackReply: missing message or bot token');

            return false;
        }

        $response = Http::withToken($token)
            ->post('https://slack.com/api/chat.postMessage', [
                'channel' => $channel,
                'text' => $message,
            ]);

        if (! $response->json('ok')) {
            Log::error('SlackReply failed', [
                'error' => $response->json('error'),
            ]);

            return false;
        }

        return true;
    }
}
