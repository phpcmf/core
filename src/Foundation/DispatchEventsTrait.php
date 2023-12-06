<?php

namespace Cmf\Foundation;

use Cmf\User\User;
use Illuminate\Contracts\Events\Dispatcher;

trait DispatchEventsTrait
{
    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * Dispatch all events for an entity.
     *
     * @param object $entity
     * @param User $actor
     */
    public function dispatchEventsFor($entity, User $actor = null)
    {
        foreach ($entity->releaseEvents() as $event) {
            $event->actor = $actor;

            $this->events->dispatch($event);
        }
    }
}
