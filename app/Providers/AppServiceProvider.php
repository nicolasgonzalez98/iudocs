<?php

namespace App\Providers;

use App\Services\Documind\DocumindClient;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DocumindClient::class, function () {
            return new DocumindClient(
                url: config('services.documind.url'),
                key: config('services.documind.service_key'),
                timeout: (int) config('services.documind.timeout', 30),
                enabled: (bool) config('services.documind.enabled', false),
                chatMode: (string) config('services.documind.chat_mode', 'off'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
