<?php

namespace Cmf\Site\Controller;

use DateTime;
use Cmf\Http\Controller\AbstractHtmlController;
use Cmf\User\Exception\InvalidConfirmationTokenException;
use Cmf\User\PasswordToken;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

class ResetPasswordController extends AbstractHtmlController
{
    /**
     * @var Factory
     */
    protected $view;

    /**
     * @param Factory $view
     */
    public function __construct(Factory $view)
    {
        $this->view = $view;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     * @throws \Cmf\User\Exception\InvalidConfirmationTokenException
     */
    public function render(Request $request)
    {
        $token = Arr::get($request->getQueryParams(), 'token');

        $token = PasswordToken::findOrFail($token);

        if ($token->created_at < new DateTime('-1 day')) {
            throw new InvalidConfirmationTokenException;
        }

        return $this->view->make('cmf.site::reset-password')
            ->with('passwordToken', $token->token)
            ->with('csrfToken', $request->getAttribute('session')->token());
    }
}
