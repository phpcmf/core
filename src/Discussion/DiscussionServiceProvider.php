<?php

namespace Cmf\Discussion;

use Cmf\Discussion\Access\ScopeDiscussionVisibility;
use Cmf\Discussion\Event\Renamed;
use Cmf\Foundation\AbstractServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;

class DiscussionServiceProvider extends AbstractServiceProvider
{
    public function boot(Dispatcher $events)
    {
        $events->subscribe(DiscussionMetadataUpdater::class);
        $events->subscribe(UserStateUpdater::class);

        $events->listen(
            Renamed::class,
            DiscussionRenamedLogger::class
        );

        Discussion::registerVisibilityScoper(new ScopeDiscussionVisibility(), 'view');
    }
}
