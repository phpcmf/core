<?php

namespace Cmf\Post\Filter;

use Cmf\Filter\FilterInterface;
use Cmf\Filter\FilterState;
use Cmf\Filter\ValidateFilterTrait;
use Cmf\User\UserRepository;

class AuthorFilter implements FilterInterface
{
    use ValidateFilterTrait;

    /**
     * @var \Cmf\User\UserRepository
     */
    protected $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function getFilterKey(): string
    {
        return 'author';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $usernames = $this->asStringArray($filterValue);

        $ids = $this->users->query()->whereIn('username', $usernames)->pluck('id');

        $filterState->getQuery()->whereIn('posts.user_id', $ids, 'and', $negate);
    }
}
