<?php

namespace Cmf\Post\Filter;

use Cmf\Filter\FilterInterface;
use Cmf\Filter\FilterState;
use Cmf\Filter\ValidateFilterTrait;

class NumberFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'number';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $number = $this->asInt($filterValue);

        $filterState->getQuery()->where('posts.number', $negate ? '!=' : '=', $number);
    }
}
