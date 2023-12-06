<?php

namespace Cmf\Post\Event;

use Cmf\Post\CommentPost;
use Cmf\User\User;

class Posted
{
    /**
     * @var CommentPost
     */
    public $post;

    /**
     * @var User|null
     */
    public $actor;

    /**
     * @param CommentPost $post
     * @param User|null $actor
     */
    public function __construct(CommentPost $post, User $actor = null)
    {
        $this->post = $post;
        $this->actor = $actor;
    }
}
