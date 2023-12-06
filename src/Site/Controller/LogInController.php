<?php

namespace Cmf\Site\Controller;

use Cmf\Api\Client;
use Cmf\Site\LogInValidator;
use Cmf\Http\AccessToken;
use Cmf\Http\RememberAccessToken;
use Cmf\Http\Rememberer;
use Cmf\Http\SessionAuthenticator;
use Cmf\User\Event\LoggedIn;
use Cmf\User\UserRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class LogInController implements RequestHandlerInterface
{
    /**
     * @var \Cmf\User\UserRepository
     */
    protected $users;

    /**
     * @var Client
     */
    protected $apiClient;

    /**
     * @var SessionAuthenticator
     */
    protected $authenticator;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var Rememberer
     */
    protected $rememberer;

    /**
     * @var LogInValidator
     */
    protected $validator;

    /**
     * @param \Cmf\User\UserRepository $users
     * @param Client $apiClient
     * @param SessionAuthenticator $authenticator
     * @param Rememberer $rememberer
     * @param LogInValidator $validator
     */
    public function __construct(UserRepository $users, Client $apiClient, SessionAuthenticator $authenticator, Dispatcher $events, Rememberer $rememberer, LogInValidator $validator)
    {
        $this->users = $users;
        $this->apiClient = $apiClient;
        $this->authenticator = $authenticator;
        $this->events = $events;
        $this->rememberer = $rememberer;
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $params = Arr::only($body, ['identification', 'password', 'remember']);

        $this->validator->assertValid($body);

        $response = $this->apiClient->withParentRequest($request)->withBody($params)->post('/token');

        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody());

            $token = AccessToken::findValid($data->token);

            $session = $request->getAttribute('session');
            $this->authenticator->logIn($session, $token);

            $this->events->dispatch(new LoggedIn($this->users->findOrFail($data->userId), $token));

            if ($token instanceof RememberAccessToken) {
                $response = $this->rememberer->remember($response, $token);
            }
        }

        return $response;
    }
}
