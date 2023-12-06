<?php

namespace Cmf\Discussion\Query;

use Cmf\Filter\FilterInterface;
use Cmf\Filter\FilterState;
use Cmf\Filter\ValidateFilterTrait;
use Cmf\Search\AbstractRegexGambit;
use Cmf\Search\SearchState;
use Cmf\User\UserRepository;
use Illuminate\Database\Query\Builder;

class AuthorFilterGambit extends AbstractRegexGambit implements FilterInterface
{
    use ValidateFilterTrait;

    /**
     * @var \Cmf\User\UserRepository
     */
    protected $users;

    /**
     * @param \Cmf\User\UserRepository $users
     */
    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    /**
     * {@inheritdoc}
     */
    public function getGambitPattern()
    {
        return 'author:(.+)';
    }

    /**
     * {@inheritdoc}
     */
    protected function conditions(SearchState $search, array $matches, $negate)
    {
        $this->constrain($search->getQuery(), $matches[1], $negate);
    }

    public function getFilterKey(): string
    {
        return 'author';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $this->constrain($filterState->getQuery(), $filterValue, $negate);
    }

    protected function constrain(Builder $query, $rawUsernames, $negate)
    {
        $usernames = $this->asStringArray($rawUsernames);

        $ids = $this->users->getIdsForUsernames($usernames);

        $query->whereIn('discussions.user_id', $ids, 'and', $negate);
    }
}
