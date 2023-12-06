<?php

namespace Cmf\Http\Exception;

use Exception;
use Cmf\Foundation\KnownError;

class RouteNotFoundException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'not_found';
    }
}
