<?php

namespace Cmf\Post\Event;

use Cmf\Post\Post;
use Cmf\User\User;

class Deleting
{
    /**
     * The post that is going to be deleted.
     *
     * @var \Cmf\Post\Post
     */
    public $post;

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
     * @param \Cmf\Post\Post $post
     * @param User $actor
     * @param array $data
     */
    public function __construct(Post $post, User $actor, array $data)
    {
        $this->post = $post;
        $this->actor = $actor;
        $this->data = $data;
    }
}
