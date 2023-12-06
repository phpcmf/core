<?php

namespace Cmf\Discussion\Command;

use Cmf\Discussion\DiscussionRepository;
use Cmf\Discussion\Event\Deleting;
use Cmf\Foundation\DispatchEventsTrait;
use Cmf\User\Exception\PermissionDeniedException;
use Illuminate\Contracts\Events\Dispatcher;

class DeleteDiscussionHandler
{
    use DispatchEventsTrait;

    /**
     * @var \Cmf\Discussion\DiscussionRepository
     */
    protected $discussions;

    /**
     * @param Dispatcher $events
     * @param DiscussionRepository $discussions
     */
    public function __construct(Dispatcher $events, DiscussionRepository $discussions)
    {
        $this->events = $events;
        $this->discussions = $discussions;
    }

    /**
     * @param DeleteDiscussion $command
     * @return \Cmf\Discussion\Discussion
     * @throws PermissionDeniedException
     */
    public function handle(DeleteDiscussion $command)
    {
        $actor = $command->actor;

        $discussion = $this->discussions->findOrFail($command->discussionId, $actor);

        $actor->assertCan('delete', $discussion);

        $this->events->dispatch(
            new Deleting($discussion, $actor, $command->data)
        );

        $discussion->delete();

        $this->dispatchEventsFor($discussion, $actor);

        return $discussion;
    }
}
