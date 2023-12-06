<?php

namespace Cmf\Post\Command;

use Cmf\Foundation\DispatchEventsTrait;
use Cmf\Post\CommentPost;
use Cmf\Post\Event\Saving;
use Cmf\Post\PostRepository;
use Cmf\Post\PostValidator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;

class EditPostHandler
{
    use DispatchEventsTrait;

    /**
     * @var \Cmf\Post\PostRepository
     */
    protected $posts;

    /**
     * @var \Cmf\Post\PostValidator
     */
    protected $validator;

    /**
     * @param Dispatcher $events
     * @param PostRepository $posts
     * @param \Cmf\Post\PostValidator $validator
     */
    public function __construct(Dispatcher $events, PostRepository $posts, PostValidator $validator)
    {
        $this->events = $events;
        $this->posts = $posts;
        $this->validator = $validator;
    }

    /**
     * @param EditPost $command
     * @return \Cmf\Post\Post
     * @throws \Cmf\User\Exception\PermissionDeniedException
     */
    public function handle(EditPost $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $post = $this->posts->findOrFail($command->postId, $actor);

        if ($post instanceof CommentPost) {
            $attributes = Arr::get($data, 'attributes', []);

            if (isset($attributes['content'])) {
                $actor->assertCan('edit', $post);

                $post->revise($attributes['content'], $actor);
            }

            if (isset($attributes['isHidden'])) {
                $actor->assertCan('hide', $post);

                if ($attributes['isHidden']) {
                    $post->hide($actor);
                } else {
                    $post->restore();
                }
            }
        }

        $this->events->dispatch(
            new Saving($post, $actor, $data)
        );

        $this->validator->assertValid($post->getDirty());

        $post->save();

        $this->dispatchEventsFor($post, $actor);

        return $post;
    }
}
