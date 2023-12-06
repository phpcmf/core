<?php

namespace Cmf\Group\Access;

use Cmf\User\Access\AbstractPolicy;
use Cmf\User\User;

class GroupPolicy extends AbstractPolicy
{
    /**
     * @param User $actor
     * @param string $ability
     * @return bool|null
     */
    public function can(User $actor, $ability)
    {
        if ($actor->hasPermission('group.'.$ability)) {
            return $this->allow();
        }
    }
}
