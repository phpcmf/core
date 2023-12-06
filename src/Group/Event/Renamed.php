<?php

namespace Cmf\Group\Event;

use Cmf\Group\Group;
use Cmf\User\User;

class Renamed
{
    /**
     * @var \Cmf\Group\Group
     */
    public $group;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param \Cmf\Group\Group $group
     * @param User $actor
     */
    public function __construct(Group $group, User $actor = null)
    {
        $this->group = $group;
        $this->actor = $actor;
    }
}
