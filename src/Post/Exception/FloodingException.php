<?php

namespace Cmf\Post\Exception;

use Exception;
use Cmf\Foundation\KnownError;

class FloodingException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'too_many_requests';
    }
}
