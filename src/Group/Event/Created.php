<?php

namespace Cmf\Group\Event;

use Cmf\Group\Group;
use Cmf\User\User;

class Created
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
     * @param Group $group
     * @param User $actor
     */
    public function __construct(Group $group, User $actor = null)
    {
        $this->group = $group;
        $this->actor = $actor;
    }
}
