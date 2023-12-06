<?php

namespace Cmf\Site\Controller;

use Cmf\Http\Controller\AbstractHtmlController;
use Cmf\User\EmailToken;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

class ConfirmEmailViewController extends AbstractHtmlController
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
     */
    public function render(Request $request)
    {
        $token = Arr::get($request->getQueryParams(), 'token');

        $token = EmailToken::validOrFail($token);

        return $this->view->make('cmf.site::confirm-email')
            ->with('csrfToken', $request->getAttribute('session')->token());
    }
}
