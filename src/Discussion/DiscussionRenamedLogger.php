<?php

namespace Cmf\Discussion;

use Cmf\Discussion\Event\Renamed;
use Cmf\Notification\Blueprint\DiscussionRenamedBlueprint;
use Cmf\Notification\NotificationSyncer;
use Cmf\Post\DiscussionRenamedPost;

class DiscussionRenamedLogger
{
    /**
     * @var NotificationSyncer
     */
    protected $notifications;

    public function __construct(NotificationSyncer $notifications)
    {
        $this->notifications = $notifications;
    }

    public function handle(Renamed $event)
    {
        $post = DiscussionRenamedPost::reply(
            $event->discussion->id,
            $event->actor->id,
            $event->oldTitle,
            $event->discussion->title
        );

        $post = $event->discussion->mergePost($post);

        if ($event->discussion->user_id !== $event->actor->id) {
            $blueprint = new DiscussionRenamedBlueprint($post);

            if ($post->exists) {
                $this->notifications->sync($blueprint, [$event->discussion->user]);
            } else {
                $this->notifications->delete($blueprint);
            }
        }
    }
}
