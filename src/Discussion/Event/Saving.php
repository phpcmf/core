<?php

namespace Cmf\Discussion\Event;

use Cmf\Discussion\Discussion;
use Cmf\User\User;

class Saving
{
    /**
     * The discussion that will be saved.
     *
     * @var \Cmf\Discussion\Discussion
     */
    public $discussion;

    /**
     * The user who is performing the action.
     *
     * @var User
     */
    public $actor;

    /**
     * Any user input associated with the command.
     *
     * @var array
     */
    public $data;

    /**
     * @param \Cmf\Discussion\Discussion $discussion
     * @param User $actor
     * @param array $data
     */
    public function __construct(Discussion $discussion, User $actor, array $data = [])
    {
        $this->discussion = $discussion;
        $this->actor = $actor;
        $this->data = $data;
    }
}
