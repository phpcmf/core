<?php

namespace Cmf\Discussion\Event;

use Cmf\Discussion\Discussion;
use Cmf\User\User;

class Renamed
{
    /**
     * @var Discussion
     */
    public $discussion;

    /**
     * @var string
     */
    public $oldTitle;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param \Cmf\Discussion\Discussion $discussion
     * @param User $actor
     * @param string $oldTitle
     */
    public function __construct(Discussion $discussion, $oldTitle, User $actor = null)
    {
        $this->discussion = $discussion;
        $this->oldTitle = $oldTitle;
        $this->actor = $actor;
    }
}
