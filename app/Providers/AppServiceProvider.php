<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $isInstalled = config('app.installed') ?? file_exists(storage_path('installed'));
        if (!$isInstalled) {
            config([
                'session.driver' => 'file',
                'cache.default' => 'file',
            ]);
        }
    }

    public function boot(): void
    {
        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            try {
                $view->with('appSettings', \App\Models\Setting::first());
            } catch (\Exception $e) {
                // Prevent exception during migrations
            }
        });

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                $settings = \App\Models\Setting::first();
                if ($settings) {
                    \Illuminate\Support\Facades\Config::set('app.name', $settings->app_name);
                }
            }
        } catch (\Exception $e) {
            // Prevent migration and command locks before table exists
        }
    }
}
