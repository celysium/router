<?php

namespace Celysium\Router;

use Illuminate\Support\Collection;

interface RouterInterface
{
    public function get(): Collection;
}
