<?php

namespace Cmf\Extend;

use Cmf\Extension\Extension;
use Illuminate\Contracts\Container\Container;

interface ExtenderInterface
{
    public function extend(Container $container, Extension $extension = null);
}
