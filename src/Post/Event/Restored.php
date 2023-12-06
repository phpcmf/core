<?php

namespace Cmf\Post\Event;

use Cmf\Post\CommentPost;
use Cmf\User\User;

class Restored
{
    /**
     * @var \Cmf\Post\CommentPost
     */
    public $post;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param \Cmf\Post\CommentPost $post
     */
    public function __construct(CommentPost $post, User $actor = null)
    {
        $this->post = $post;
        $this->actor = $actor;
    }
}
