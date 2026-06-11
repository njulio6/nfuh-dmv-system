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
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                $settings = \App\Models\Setting::first();
                if ($settings) {
                    \Illuminate\Support\Facades\View::share('appSettings', $settings);
                    \Illuminate\Support\Facades\Config::set('app.name', $settings->app_name);
                }
            }
        } catch (\Exception $e) {
            // Prevent migration and command locks before table exists
        }
    }
}
