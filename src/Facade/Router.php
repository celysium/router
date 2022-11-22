<?php

namespace CCelysium\Router\Facade;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Collection get();
 */
class Router extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'router';
    }
}
