<?php

namespace VanToanTG\AntiCrawler;

use Illuminate\Support\ServiceProvider;
use VanToanTG\AntiCrawler\Services\BotDetectionService;
use VanToanTG\AntiCrawler\Services\RateLimitService;
use VanToanTG\AntiCrawler\Services\CaptchaService;

class AntiCrawlerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/Config/anti-crawler.php',
            'anti-crawler'
        );

        // Register services
        $this->app->singleton(BotDetectionService::class, function ($app) {
            return new BotDetectionService();
        });

        $this->app->singleton(RateLimitService::class, function ($app) {
            return new RateLimitService();
        });

        $this->app->singleton(CaptchaService::class, function ($app) {
            return new CaptchaService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'anti-crawler');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Publish configuration
        $this->publishes([
            __DIR__ . '/Config/anti-crawler.php' => config_path('anti-crawler.php'),
        ], 'anti-crawler-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/Database/Migrations' => database_path('migrations'),
        ], 'anti-crawler-migrations');

        // Publish views
        $this->publishes([
            __DIR__ . '/Resources/views' => resource_path('views/vendor/anti-crawler'),
        ], 'anti-crawler-views');

        // Register middleware
        $router = $this->app['router'];
        $router->aliasMiddleware('anti-crawler', \VanToanTG\AntiCrawler\Middleware\AntiCrawler::class);
        $router->aliasMiddleware('bot-detect', \VanToanTG\AntiCrawler\Middleware\DetectBot::class);
    }
}
