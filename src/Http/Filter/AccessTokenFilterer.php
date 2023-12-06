<?php

namespace Cmf\Http\Filter;

use Cmf\Filter\AbstractFilterer;
use Cmf\Http\AccessToken;
use Cmf\User\User;
use Illuminate\Database\Eloquent\Builder;

class AccessTokenFilterer extends AbstractFilterer
{
    protected function getQuery(User $actor): Builder
    {
        return AccessToken::query()->whereVisibleTo($actor);
    }
}
