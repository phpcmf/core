<?php

namespace Cmf\User\Job;

use Cmf\Http\UrlGenerator;
use Cmf\Mail\Job\SendRawEmailJob;
use Cmf\Queue\AbstractJob;
use Cmf\Settings\SettingsRepositoryInterface;
use Cmf\User\PasswordToken;
use Cmf\User\UserRepository;
use Illuminate\Contracts\Queue\Queue;
use Symfony\Contracts\Translation\TranslatorInterface;

class RequestPasswordResetJob extends AbstractJob
{
    /**
     * @var string
     */
    protected $email;

    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function handle(
        SettingsRepositoryInterface $settings,
        UrlGenerator $url,
        TranslatorInterface $translator,
        UserRepository $users,
        Queue $queue
    ) {
        $user = $users->findByEmail($this->email);

        if (! $user) {
            return;
        }

        $token = PasswordToken::generate($user->id);
        $token->save();

        $data = [
            'username' => $user->display_name,
            'url' => $url->to('site')->route('resetPassword', ['token' => $token->token]),
            'site' => $settings->get('site_title'),
        ];

        $body = $translator->trans('core.email.reset_password.body', $data);
        $subject = $translator->trans('core.email.reset_password.subject');

        $queue->push(new SendRawEmailJob($user->email, $subject, $body));
    }
}
