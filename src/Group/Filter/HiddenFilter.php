<?php

namespace Cmf\Group\Filter;

use Cmf\Filter\FilterInterface;
use Cmf\Filter\FilterState;
use Cmf\Filter\ValidateFilterTrait;

class HiddenFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'hidden';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $hidden = $this->asBool($filterValue);

        $filterState->getQuery()->where('is_hidden', $negate ? '!=' : '=', $hidden);
    }
}
