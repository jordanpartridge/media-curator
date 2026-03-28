<?php

namespace App\Commands;

use Illuminate\Console\Command;

class InspireCommand extends Command
{
    protected $signature = 'inspire {name=Artisan}';

    protected $description = 'Display an inspiring quote';

    public function handle(): void
    {
        $this->info('Simplicity is the ultimate sophistication.');
    }
}
