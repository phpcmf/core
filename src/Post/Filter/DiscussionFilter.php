<?php

namespace Cmf\Post\Filter;

use Cmf\Filter\FilterInterface;
use Cmf\Filter\FilterState;
use Cmf\Filter\ValidateFilterTrait;

class DiscussionFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'discussion';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $discussionId = $this->asInt($filterValue);

        $filterState->getQuery()->where('posts.discussion_id', $negate ? '!=' : '=', $discussionId);
    }
}
