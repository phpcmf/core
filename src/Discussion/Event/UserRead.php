<?php

namespace Cmf\Discussion\Event;

use Cmf\Discussion\UserState;
use Cmf\User\User;

class UserRead
{
    /**
     * @var UserState
     */
    public $state;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param UserState $state
     */
    public function __construct(UserState $state)
    {
        $this->state = $state;
    }
}
