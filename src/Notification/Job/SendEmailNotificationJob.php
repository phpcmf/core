<?php

namespace Cmf\Notification\Job;

use Cmf\Notification\MailableInterface;
use Cmf\Notification\NotificationMailer;
use Cmf\Queue\AbstractJob;
use Cmf\User\User;

class SendEmailNotificationJob extends AbstractJob
{
    /**
     * @var MailableInterface
     */
    private $blueprint;

    /**
     * @var User
     */
    private $recipient;

    public function __construct(MailableInterface $blueprint, User $recipient)
    {
        $this->blueprint = $blueprint;
        $this->recipient = $recipient;
    }

    public function handle(NotificationMailer $mailer)
    {
        $mailer->send($this->blueprint, $this->recipient);
    }
}
