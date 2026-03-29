<?php

namespace App\Tools;

use Illuminate\Support\Facades\Log;

abstract class CriterionTool
{
    abstract public function name(): string;

    abstract public function description(): string;

    abstract public function execute(array $parameters): mixed;

    public function run(array $parameters = []): mixed
    {
        $start = microtime(true);
        $tool = $this->name();

        Log::info("Tool started: {$tool}", ['parameters' => $parameters]);

        try {
            $result = $this->execute($parameters);

            $duration = round((microtime(true) - $start) * 1000);

            Log::info("Tool completed: {$tool}", [
                'duration_ms' => $duration,
            ]);

            return $result;
        } catch (\Throwable $e) {
            $duration = round((microtime(true) - $start) * 1000);

            Log::error("Tool failed: {$tool}", [
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
            ]);

            throw $e;
        }
    }
}
