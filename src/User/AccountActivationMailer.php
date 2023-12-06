<?php

namespace Cmf\User;

use Cmf\Http\UrlGenerator;
use Cmf\Settings\SettingsRepositoryInterface;
use Cmf\User\Event\Registered;
use Illuminate\Contracts\Queue\Queue;
use Symfony\Contracts\Translation\TranslatorInterface;

class AccountActivationMailer
{
    use AccountActivationMailerTrait;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param \Cmf\Settings\SettingsRepositoryInterface $settings
     * @param Queue $queue
     * @param UrlGenerator $url
     * @param TranslatorInterface $translator
     */
    public function __construct(SettingsRepositoryInterface $settings, Queue $queue, UrlGenerator $url, TranslatorInterface $translator)
    {
        $this->settings = $settings;
        $this->queue = $queue;
        $this->url = $url;
        $this->translator = $translator;
    }

    public function handle(Registered $event)
    {
        $user = $event->user;

        if ($user->is_email_confirmed) {
            return;
        }

        $token = $this->generateToken($user, $user->email);
        $data = $this->getEmailData($user, $token);

        $this->sendConfirmationEmail($user, $data);
    }
}
