<?php

namespace Cmf\Discussion\Query;

use Cmf\Discussion\DiscussionRepository;
use Cmf\Filter\FilterInterface;
use Cmf\Filter\FilterState;
use Cmf\Search\AbstractRegexGambit;
use Cmf\Search\SearchState;
use Cmf\User\User;
use Illuminate\Database\Query\Builder;

class UnreadFilterGambit extends AbstractRegexGambit implements FilterInterface
{
    /**
     * @var \Cmf\Discussion\DiscussionRepository
     */
    protected $discussions;

    /**
     * @param \Cmf\Discussion\DiscussionRepository $discussions
     */
    public function __construct(DiscussionRepository $discussions)
    {
        $this->discussions = $discussions;
    }

    /**
     * {@inheritdoc}
     */
    public function getGambitPattern()
    {
        return 'is:unread';
    }

    /**
     * {@inheritdoc}
     */
    protected function conditions(SearchState $search, array $matches, $negate)
    {
        $this->constrain($search->getQuery(), $search->getActor(), $negate);
    }

    public function getFilterKey(): string
    {
        return 'unread';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $this->constrain($filterState->getQuery(), $filterState->getActor(), $negate);
    }

    protected function constrain(Builder $query, User $actor, bool $negate)
    {
        if ($actor->exists) {
            $readIds = $this->discussions->getReadIdsQuery($actor);

            $query->where(function ($query) use ($readIds, $negate, $actor) {
                if (! $negate) {
                    $query->whereNotIn('id', $readIds)->where('last_posted_at', '>', $actor->marked_all_as_read_at ?: 0);
                } else {
                    $query->whereIn('id', $readIds)->orWhere('last_posted_at', '<=', $actor->marked_all_as_read_at ?: 0);
                }
            });
        }
    }
}
