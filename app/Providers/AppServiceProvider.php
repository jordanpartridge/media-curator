<?php

namespace App\Providers;

use App\Agent\CriterionAgent;
use App\Services\QdrantService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(QdrantService::class);
        $this->app->singleton(CriterionAgent::class);
    }

    public function boot(): void
    {
        //
    }
}
