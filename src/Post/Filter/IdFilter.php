<?php

namespace Cmf\Post\Filter;

use Cmf\Filter\FilterInterface;
use Cmf\Filter\FilterState;
use Cmf\Filter\ValidateFilterTrait;

class IdFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'id';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $ids = $this->asIntArray($filterValue);

        $filterState->getQuery()->whereIn('posts.id', $ids, 'and', $negate);
    }
}
