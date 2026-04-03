<?php

namespace Modules\Ecommerce\Providers;

use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Modules\Ecommerce\Http\Middleware\AuthenticateCustomer;
use Modules\Ecommerce\Http\Middleware\RedirectIfAuthenticatedCustomer;

class EcommerceServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $router = $this->app['router'];
        $router->aliasMiddleware('ecom.auth', AuthenticateCustomer::class);
        $router->aliasMiddleware('ecom.guest', RedirectIfAuthenticatedCustomer::class);

        Config::set('auth.guards.ecom_customer', [
            'driver' => 'session',
            'provider' => 'ecom_customers',
        ]);

        Config::set('auth.providers.ecom_customers', [
            'driver' => 'eloquent',
            'model' => \Modules\Ecommerce\Entities\EcomCustomer::class,
        ]);
    }

    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('ecommerce.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'ecommerce');
    }

    public function registerViews()
    {
        $viewPath = resource_path('views/modules/ecommerce');
        $sourcePath = __DIR__.'/../Resources/views';
        $moduleViewPaths = array_values(array_filter(array_map(function ($path) {
            return $path.'/modules/ecommerce';
        }, config('view.paths')), function ($path) {
            return is_dir($path);
        }));

        $this->publishes([
            $sourcePath => $viewPath,
        ], 'views');

        $this->loadViewsFrom(array_merge($moduleViewPaths, [$sourcePath]), 'ecommerce');
    }

    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/ecommerce');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'ecommerce');
        } else {
            $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'ecommerce');
        }
    }

    public function registerFactories()
    {
        if (! app()->environment('production') && $this->app->runningInConsole()) {
            app(Factory::class)->load(__DIR__.'/../Database/factories');
        }
    }
}
