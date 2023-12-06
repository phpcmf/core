<?php

namespace Cmf\User\Search;

use Cmf\Search\AbstractSearcher;
use Cmf\Search\GambitManager;
use Cmf\User\User;
use Cmf\User\UserRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;

class UserSearcher extends AbstractSearcher
{
    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @param UserRepository $users
     * @param Dispatcher $events
     * @param GambitManager $gambits
     * @param array $searchMutators
     */
    public function __construct(UserRepository $users, Dispatcher $events, GambitManager $gambits, array $searchMutators)
    {
        parent::__construct($gambits, $searchMutators);

        $this->events = $events;
        $this->users = $users;
    }

    protected function getQuery(User $actor): Builder
    {
        return $this->users->query()->whereVisibleTo($actor);
    }
}
