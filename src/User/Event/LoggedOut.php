<?php

namespace Cmf\User\Event;

use Cmf\User\User;

class LoggedOut
{
    /**
     * @var User
     */
    public $user;

    /**
     * @var bool
     */
    public $isGlobal;

    public function __construct(User $user, bool $isGlobal = false)
    {
        $this->user = $user;
        $this->isGlobal = $isGlobal;
    }
}
