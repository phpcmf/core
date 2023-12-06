<?php

namespace Cmf\Post\Command;

use Cmf\Foundation\DispatchEventsTrait;
use Cmf\Post\Event\Deleting;
use Cmf\Post\PostRepository;
use Illuminate\Contracts\Events\Dispatcher;

class DeletePostHandler
{
    use DispatchEventsTrait;

    /**
     * @var \Cmf\Post\PostRepository
     */
    protected $posts;

    /**
     * @param Dispatcher $events
     * @param \Cmf\Post\PostRepository $posts
     */
    public function __construct(Dispatcher $events, PostRepository $posts)
    {
        $this->events = $events;
        $this->posts = $posts;
    }

    /**
     * @param DeletePost $command
     * @return \Cmf\Post\Post
     * @throws \Cmf\User\Exception\PermissionDeniedException
     */
    public function handle(DeletePost $command)
    {
        $actor = $command->actor;

        $post = $this->posts->findOrFail($command->postId, $actor);

        $actor->assertCan('delete', $post);

        $this->events->dispatch(
            new Deleting($post, $actor, $command->data)
        );

        $post->delete();

        $this->dispatchEventsFor($post, $actor);

        return $post;
    }
}
