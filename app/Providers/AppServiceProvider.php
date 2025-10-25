<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\LotteryResultScraper;
use App\Services\ResultProviders\Az24DailyProvider;
use App\Services\ResultProviders\DailyResultProviderInterface;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Provider “theo ngày/miền” cho AZ24
        $this->app->singleton(
            \App\Services\ResultProviders\DailyResultProviderInterface::class,
            \App\Services\ResultProviders\Az24DailyProvider::class
        );

        // Scraper dùng DailyResultProviderInterface
        $this->app->singleton(\App\Services\LotteryResultScraper::class, function ($app) {
            return new \App\Services\LotteryResultScraper(
                $app->make(\App\Services\ResultProviders\DailyResultProviderInterface::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Paginator::useTailwind();
    }
}
