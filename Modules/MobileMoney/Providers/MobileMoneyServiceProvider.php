<?php

namespace Modules\MobileMoney\Providers;

use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\ServiceProvider;

class MobileMoneyServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }

    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('mobilemoney.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'mobilemoney');
    }

    public function registerViews()
    {
        $viewPath = resource_path('views/modules/mobilemoney');
        $sourcePath = __DIR__.'/../Resources/views';
        $moduleViewPaths = array_values(array_filter(array_map(function ($path) {
            return $path.'/modules/mobilemoney';
        }, config('view.paths')), function ($path) {
            return is_dir($path);
        }));

        $this->publishes([
            $sourcePath => $viewPath,
        ], 'views');

        $this->loadViewsFrom(array_merge($moduleViewPaths, [$sourcePath]), 'mobilemoney');
    }

    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/mobilemoney');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'mobilemoney');
        } else {
            $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'mobilemoney');
        }
    }

    public function registerFactories()
    {
        if (! app()->environment('production') && $this->app->runningInConsole()) {
            app(Factory::class)->load(__DIR__.'/../Database/factories');
        }
    }
}
