<?php

namespace Cmf\Discussion\Command;

use Cmf\User\User;

class EditDiscussion
{
    /**
     * The ID of the discussion to edit.
     *
     * @var int
     */
    public $discussionId;

    /**
     * The user performing the action.
     *
     * @var \Cmf\User\User
     */
    public $actor;

    /**
     * The attributes to update on the discussion.
     *
     * @var array
     */
    public $data;

    /**
     * @param int $discussionId The ID of the discussion to edit.
     * @param \Cmf\User\User $actor The user performing the action.
     * @param array $data The attributes to update on the discussion.
     */
    public function __construct($discussionId, User $actor, array $data)
    {
        $this->discussionId = $discussionId;
        $this->actor = $actor;
        $this->data = $data;
    }
}
