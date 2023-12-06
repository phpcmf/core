<?php

namespace Cmf\Api\Controller;

use Cmf\Api\Serializer\CurrentUserSerializer;
use Cmf\Http\RequestUtil;
use Cmf\User\Command\RegisterUser;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class CreateUserController extends AbstractCreateController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = CurrentUserSerializer::class;

    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        return $this->bus->dispatch(
            new RegisterUser(RequestUtil::getActor($request), Arr::get($request->getParsedBody(), 'data', []))
        );
    }
}
