<?php

namespace Cmf\Http\Exception;

use Exception;
use Cmf\Foundation\KnownError;

class MethodNotAllowedException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'method_not_allowed';
    }
}
