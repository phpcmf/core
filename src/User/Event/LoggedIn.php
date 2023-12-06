<?php

namespace Cmf\User\Event;

use Cmf\Http\AccessToken;
use Cmf\User\User;

class LoggedIn
{
    public $user;

    public $token;

    public function __construct(User $user, AccessToken $token)
    {
        $this->user = $user;
        $this->token = $token;
    }
}
