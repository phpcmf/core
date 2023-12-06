<?php

namespace Cmf\Notification\Job;

use Cmf\Notification\Blueprint\BlueprintInterface;
use Cmf\Notification\Notification;
use Cmf\Queue\AbstractJob;
use Cmf\User\User;

class SendNotificationsJob extends AbstractJob
{
    /**
     * @var BlueprintInterface
     */
    private $blueprint;

    /**
     * @var User[]
     */
    private $recipients;

    public function __construct(BlueprintInterface $blueprint, array $recipients = [])
    {
        $this->blueprint = $blueprint;
        $this->recipients = $recipients;
    }

    public function handle()
    {
        Notification::notify($this->recipients, $this->blueprint);
    }
}
