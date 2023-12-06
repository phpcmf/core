<?php

namespace Cmf\Http\Exception;

use Exception;
use Cmf\Foundation\KnownError;

class TokenMismatchException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'csrf_token_mismatch';
    }
}
