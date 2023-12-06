<?php

namespace Cmf\User;

use Cmf\Database\AbstractModel;
use Cmf\Http\SlugDriverInterface;

/**
 * @implements SlugDriverInterface<User>
 */
class UsernameSlugDriver implements SlugDriverInterface
{
    /**
     * @var UserRepository
     */
    protected $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    /**
     * @param User $instance
     */
    public function toSlug(AbstractModel $instance): string
    {
        return $instance->username;
    }

    /**
     * @return User
     */
    public function fromSlug(string $slug, User $actor): AbstractModel
    {
        return $this->users->findOrFailByUsername($slug, $actor);
    }
}
