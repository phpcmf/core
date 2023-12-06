<?php

namespace Cmf\User\Exception;

use Exception;
use Cmf\Foundation\KnownError;

class NotAuthenticatedException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'not_authenticated';
    }
}
