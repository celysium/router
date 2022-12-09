<?php

namespace Celysium\Router;

use Illuminate\Routing\Router as BaseRouter;
use Illuminate\Support\ServiceProvider;

class RouterProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('celysium-router', function ($app) {
            return new Router($app->make(BaseRouter::class));
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
