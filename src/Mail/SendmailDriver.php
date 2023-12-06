<?php

namespace Cmf\Mail;

use Cmf\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\MessageBag;
use Swift_SendmailTransport;
use Swift_Transport;

class SendmailDriver implements DriverInterface
{
    public function availableSettings(): array
    {
        return [];
    }

    public function validate(SettingsRepositoryInterface $settings, Factory $validator): MessageBag
    {
        return new MessageBag;
    }

    public function canSend(): bool
    {
        return true;
    }

    public function buildTransport(SettingsRepositoryInterface $settings): Swift_Transport
    {
        return new Swift_SendmailTransport;
    }
}
