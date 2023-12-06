<?php

namespace Cmf\Foundation;

use Exception;

class IOException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'io_error';
    }
}
