<?php

namespace Cmf\User\Exception;

use Exception;
use Cmf\Foundation\KnownError;

class InvalidConfirmationTokenException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'invalid_confirmation_token';
    }
}
