<?php

namespace Cmf\Discussion\Event;

use Cmf\Discussion\Discussion;
use Cmf\User\User;

class Started
{
    /**
     * @var \Cmf\Discussion\Discussion
     */
    public $discussion;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param \Cmf\Discussion\Discussion $discussion
     * @param User $actor
     */
    public function __construct(Discussion $discussion, User $actor = null)
    {
        $this->discussion = $discussion;
        $this->actor = $actor;
    }
}
