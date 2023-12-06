<?php

namespace Cmf\User\Filter;

use Cmf\Filter\AbstractFilterer;
use Cmf\User\User;
use Cmf\User\UserRepository;
use Illuminate\Database\Eloquent\Builder;

class UserFilterer extends AbstractFilterer
{
    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @param UserRepository $users
     * @param array $filters
     * @param array $filterMutators
     */
    public function __construct(UserRepository $users, array $filters, array $filterMutators)
    {
        parent::__construct($filters, $filterMutators);

        $this->users = $users;
    }

    protected function getQuery(User $actor): Builder
    {
        return $this->users->query()->whereVisibleTo($actor);
    }
}
