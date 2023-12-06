<?php

namespace Cmf\Discussion\Event;

use Cmf\Discussion\UserState;

class UserDataSaving
{
    /**
     * @var \Cmf\Discussion\UserState
     */
    public $state;

    /**
     * @param \Cmf\Discussion\UserState $state
     */
    public function __construct(UserState $state)
    {
        $this->state = $state;
    }
}
