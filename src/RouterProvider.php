<?php

namespace Celysium\Router;

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
        $this->app->bind('router', function ($app) {
            return new Router();
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
