<?php

namespace Cmf\Http\Event;

use Cmf\Http\AccessToken;

class DeveloperTokenCreated
{
    /**
     * @var AccessToken
     */
    public $token;

    public function __construct(AccessToken $token)
    {
        $this->token = $token;
    }
}
