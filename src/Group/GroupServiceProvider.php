<?php

namespace Cmf\Group;

use Cmf\Foundation\AbstractServiceProvider;
use Cmf\Group\Access\ScopeGroupVisibility;

class GroupServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        Group::registerVisibilityScoper(new ScopeGroupVisibility(), 'view');
    }
}
