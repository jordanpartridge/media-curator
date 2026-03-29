<?php

namespace App\Commands;

use App\Tools\ServiceHealth;
use App\Tools\ServiceRestart;
use App\Tools\SlackReply;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WatchdogCommand extends Command
{
    protected $signature = 'criterion:watchdog';

    protected $description = 'Health-check all media services, self-heal with Podman restart, escalate to Slack on failure';

    public function handle(ServiceHealth $health, ServiceRestart $restart, SlackReply $slack): int
    {
        $this->line('<fg=cyan>═══ Criterion Watchdog ═══</>');
        $this->newLine();

        $results = $health->run();
        $failures = [];

        foreach ($results as $name => $status) {
            $label = ucfirst($name);

            if ($status['healthy']) {
                $this->line("  <fg=green>✓</> {$label}");

                continue;
            }

            $this->line("  <fg=red>✗</> {$label} — attempting restart…");

            $restartResult = $restart->run([
                'container' => $status['container'],
                'wait' => 10,
            ]);

            if ($restartResult['recovered']) {
                Log::info("{$label} has been restored after restart");
                $this->line("  <fg=yellow>↻</> {$label} — restored");
            } else {
                $failures[] = $name;
                $this->line("  <fg=red>✗</> {$label} — still down after restart");
            }
        }

        if ($failures !== []) {
            $this->escalate($slack, $failures);
        }

        $this->newLine();
        $this->line(count($failures) === 0
            ? '<fg=green>All services operational.</>'
            : '<fg=red>'.count($failures).' service(s) require attention.</>');

        return count($failures) === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function escalate(SlackReply $slack, array $failures): void
    {
        foreach ($failures as $name) {
            $label = ucfirst($name);
            $message = "{$label} is unresponsive, sir. I was unable to revive it.";

            $slack->run(['message' => $message]);

            Log::critical("Watchdog escalation: {$name} is unresponsive after restart attempt");
        }
    }
}
