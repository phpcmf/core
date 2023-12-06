<?php

namespace Cmf\Site\Controller;

use Cmf\Http\Exception\TokenMismatchException;
use Cmf\Http\Rememberer;
use Cmf\Http\RequestUtil;
use Cmf\Http\SessionAuthenticator;
use Cmf\Http\UrlGenerator;
use Cmf\User\Event\LoggedOut;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class LogOutController implements RequestHandlerInterface
{
    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var SessionAuthenticator
     */
    protected $authenticator;

    /**
     * @var Rememberer
     */
    protected $rememberer;

    /**
     * @var Factory
     */
    protected $view;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @param Dispatcher $events
     * @param SessionAuthenticator $authenticator
     * @param Rememberer $rememberer
     * @param Factory $view
     * @param UrlGenerator $url
     */
    public function __construct(
        Dispatcher $events,
        SessionAuthenticator $authenticator,
        Rememberer $rememberer,
        Factory $view,
        UrlGenerator $url
    ) {
        $this->events = $events;
        $this->authenticator = $authenticator;
        $this->rememberer = $rememberer;
        $this->view = $view;
        $this->url = $url;
    }

    /**
     * @param Request $request
     * @return ResponseInterface
     * @throws TokenMismatchException
     */
    public function handle(Request $request): ResponseInterface
    {
        $session = $request->getAttribute('session');
        $actor = RequestUtil::getActor($request);

        $url = Arr::get($request->getQueryParams(), 'return', $this->url->to('site')->base());

        // 如果没有用户登录，请返回到索引。
        if ($actor->isGuest()) {
            return new RedirectResponse($url);
        }

        // 如果未提供有效的 CSRF 令牌，请显示一个视图，该视图将允许用户按下按钮以完成注销过程。
        $csrfToken = $session->token();

        if (Arr::get($request->getQueryParams(), 'token') !== $csrfToken) {
            $return = Arr::get($request->getQueryParams(), 'return');

            $view = $this->view->make('cmf.site::log-out')
                ->with('url', $this->url->to('site')->route('logout').'?token='.$csrfToken.($return ? '&return='.urlencode($return) : ''));

            return new HtmlResponse($view->render());
        }

        $accessToken = $session->get('access_token');
        $response = new RedirectResponse($url);

        $this->authenticator->logOut($session);

        $actor->accessTokens()->where('token', $accessToken)->delete();

        $this->events->dispatch(new LoggedOut($actor, false));

        return $this->rememberer->forget($response);
    }
}
