<?php

namespace Celysium\Router\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static get():array
 */
class Router extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'router';
    }
}
