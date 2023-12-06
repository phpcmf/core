<?php

namespace Cmf\Post\Event;

use Cmf\Post\CommentPost;
use Cmf\User\User;

class Hidden
{
    /**
     * @var CommentPost
     */
    public $post;

    /**
     * @var User
     */
    public $actor;

    /**
     * @param CommentPost $post
     */
    public function __construct(CommentPost $post, User $actor = null)
    {
        $this->post = $post;
        $this->actor = $actor;
    }
}
