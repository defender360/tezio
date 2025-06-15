<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;
use App\Search\ElasticsearchEngine;
use App\Services\NotificationService;
use App\Services\TemplateService;
use App\Services\ChannelService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register notification services
        $this->app->singleton(TemplateService::class, function ($app) {
            return new TemplateService();
        });

        $this->app->singleton(ChannelService::class, function ($app) {
            return new ChannelService();
        });

        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService(
                $app->make(TemplateService::class),
                $app->make(ChannelService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register the custom Elasticsearch engine for Laravel Scout
        resolve(EngineManager::class)->extend('elasticsearch', function () {
            return new ElasticsearchEngine(
                config('scout.elasticsearch', [])
            );
        });
    }
}