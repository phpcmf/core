<?php

namespace Cmf\User\Query;

use Cmf\Filter\FilterInterface;
use Cmf\Filter\FilterState;
use Cmf\Filter\ValidateFilterTrait;
use Cmf\Search\AbstractRegexGambit;
use Cmf\Search\SearchState;
use Illuminate\Database\Query\Builder;

class EmailFilterGambit extends AbstractRegexGambit implements FilterInterface
{
    use ValidateFilterTrait;

    /**
     * {@inheritdoc}
     */
    public function apply(SearchState $search, $bit)
    {
        if (! $search->getActor()->hasPermission('user.edit')) {
            return false;
        }

        return parent::apply($search, $bit);
    }

    /**
     * {@inheritdoc}
     */
    public function getGambitPattern()
    {
        return 'email:(.+)';
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
        return 'email';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        if (! $filterState->getActor()->hasPermission('user.edit')) {
            return;
        }

        $this->constrain($filterState->getQuery(), $filterValue, $negate);
    }

    protected function constrain(Builder $query, $rawEmail, bool $negate)
    {
        $email = $this->asString($rawEmail);

        $query->where('email', $negate ? '!=' : '=', $email);
    }
}
