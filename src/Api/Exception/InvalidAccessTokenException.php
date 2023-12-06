<?php

namespace Cmf\Api\Exception;

use Exception;
use Cmf\Foundation\KnownError;

class InvalidAccessTokenException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'invalid_access_token';
    }
}
