<?php

namespace Cmf\Notification\Event;

use DateTime;
use Cmf\User\User;

class ReadAll
{
    /**
     * @var User
     */
    public $actor;

    /**
     * @var DateTime
     */
    public $timestamp;

    public function __construct(User $user, DateTime $timestamp)
    {
        $this->actor = $user;
        $this->timestamp = $timestamp;
    }
}
