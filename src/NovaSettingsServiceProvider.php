<?php

namespace OptimistDigital\NovaSettings;

use Laravel\Nova\Nova;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OptimistDigital\NovaSettings\Http\Middleware\Authorize;
use OptimistDigital\NovaTranslationsLoader\LoadsNovaTranslations;
use OptimistDigital\NovaSettings\Http\Middleware\SettingsPathExists;

class NovaSettingsServiceProvider extends ServiceProvider
{
    use LoadsNovaTranslations;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'nova-settings');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadTranslations(__DIR__ . '/../lang', 'nova-settings', true);

        if ($this->app->runningInConsole()) {
            // Publish migrations
            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'migrations');

            // Publish config
            $this->publishes([
                __DIR__ . '/../config/' => config_path(),
            ], 'config');
        }
    }

    public function register()
    {
        $this->registerRoutes();

        $this->mergeConfigFrom(
            __DIR__ . '/../config/nova-settings.php',
            'nova-settings'
        );

        $this->app->singleton(NovaSettingsStore::class, function () {
            return new NovaSettingsStore();
        });
    }

    protected function registerRoutes()
    {
        // Register nova routes
        Nova::router()->group(function ($router) {
            $path = config('nova-settings.base_path', 'nova-settings');
            $router->get("{$path}/{pageId?}", fn ($pageId = 'general') => inertia('NovaSettings', ['basePath' => $path, 'pageId' => $pageId]));
        });

        if ($this->app->routesAreCached()) return;

        Route::middleware(['nova', Authorize::class, SettingsPathExists::class])
            ->group(__DIR__ . '/../routes/api.php');
    }
}
