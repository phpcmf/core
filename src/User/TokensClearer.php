<?php

namespace Cmf\User;

use Cmf\User\Event\EmailChanged;
use Cmf\User\Event\PasswordChanged;
use Illuminate\Contracts\Events\Dispatcher;

class TokensClearer
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen([PasswordChanged::class, EmailChanged::class], [$this, 'clearPasswordTokens']);
        $events->listen(PasswordChanged::class, [$this, 'clearEmailTokens']);
    }

    /**
     * @param PasswordChanged|EmailChanged $event
     */
    public function clearPasswordTokens($event): void
    {
        $event->user->passwordTokens()->delete();
    }

    /**
     * @param PasswordChanged $event
     */
    public function clearEmailTokens($event): void
    {
        $event->user->emailTokens()->delete();
    }
}
