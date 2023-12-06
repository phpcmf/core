<?php

namespace Cmf\User\Command;

use Cmf\Foundation\DispatchEventsTrait;
use Cmf\User\Event\Deleting;
use Cmf\User\Exception\PermissionDeniedException;
use Cmf\User\UserRepository;
use Illuminate\Contracts\Events\Dispatcher;

class DeleteUserHandler
{
    use DispatchEventsTrait;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @param Dispatcher $events
     * @param UserRepository $users
     */
    public function __construct(Dispatcher $events, UserRepository $users)
    {
        $this->events = $events;
        $this->users = $users;
    }

    /**
     * @param DeleteUser $command
     * @return \Cmf\User\User
     * @throws PermissionDeniedException
     */
    public function handle(DeleteUser $command)
    {
        $actor = $command->actor;
        $user = $this->users->findOrFail($command->userId, $actor);

        $actor->assertCan('delete', $user);

        $this->events->dispatch(
            new Deleting($user, $actor, $command->data)
        );

        $user->delete();

        $this->dispatchEventsFor($user, $actor);

        return $user;
    }
}
