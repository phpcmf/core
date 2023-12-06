<?php

namespace Cmf\User\Command;

use Cmf\Foundation\DispatchEventsTrait;
use Cmf\User\EmailToken;
use Cmf\User\UserRepository;
use Illuminate\Contracts\Events\Dispatcher;

class ConfirmEmailHandler
{
    use DispatchEventsTrait;

    /**
     * @var \Cmf\User\UserRepository
     */
    protected $users;

    /**
     * @param \Cmf\User\UserRepository $users
     */
    public function __construct(Dispatcher $events, UserRepository $users)
    {
        $this->events = $events;
        $this->users = $users;
    }

    /**
     * @param ConfirmEmail $command
     * @return \Cmf\User\User
     */
    public function handle(ConfirmEmail $command)
    {
        /** @var EmailToken $token */
        $token = EmailToken::validOrFail($command->token);

        $user = $token->user;
        $user->changeEmail($token->email);

        $user->activate();

        $user->save();
        $this->dispatchEventsFor($user);

        // Delete *all* tokens for the user, in case other ones were sent first
        $user->emailTokens()->delete();

        return $user;
    }
}
