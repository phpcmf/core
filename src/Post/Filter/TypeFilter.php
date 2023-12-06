<?php

namespace Cmf\Post\Filter;

use Cmf\Filter\FilterInterface;
use Cmf\Filter\FilterState;
use Cmf\Filter\ValidateFilterTrait;

class TypeFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'type';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $type = $this->asString($filterValue);

        $filterState->getQuery()->where('posts.type', $negate ? '!=' : '=', $type);
    }
}
