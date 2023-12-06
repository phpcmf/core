<?php

namespace Cmf\User\Exception;

use Exception;
use Cmf\Foundation\KnownError;

class PermissionDeniedException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'permission_denied';
    }
}
