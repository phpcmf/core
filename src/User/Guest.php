<?php

namespace Cmf\User;

use Cmf\Group\Group;

class Guest extends User
{
    /**
     * Override the ID of this user, as a guest does not have an ID.
     *
     * @var int
     */
    public $id = 0;

    /**
     * Get the guest's group, containing only the 'guests' group model.
     *
     * @return \Cmf\Group\Group
     */
    public function getGroupsAttribute()
    {
        if (! isset($this->attributes['groups'])) {
            $this->attributes['groups'] = $this->relations['groups'] = Group::where('id', Group::GUEST_ID)->get();
        }

        return $this->attributes['groups'];
    }

    /**
     * {@inheritdoc}
     */
    public function isGuest()
    {
        return true;
    }
}
