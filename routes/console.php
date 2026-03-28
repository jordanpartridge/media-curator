<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('criterion:watchdog', function () {
    $this->info('Criterion watchdog — checking library health...');
})->purpose('Periodic library health check');

Artisan::command('criterion:audit', function () {
    $this->info('Criterion weekly audit — analyzing library trends...');
})->purpose('Weekly library audit and recommendations');
