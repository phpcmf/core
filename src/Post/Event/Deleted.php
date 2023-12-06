<?php

namespace Cmf\Post\Event;

use Cmf\Post\Post;
use Cmf\User\User;

class Deleted
{
    /**
     * @var \Cmf\Post\Post
     */
    public $post;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param \Cmf\Post\Post $post
     */
    public function __construct(Post $post, User $actor = null)
    {
        $this->post = $post;
        $this->actor = $actor;
    }
}
