<?php

namespace Cmf\Api\Controller;

use Cmf\Api\Serializer\UserSerializer;
use Cmf\Http\RequestUtil;
use Cmf\User\Command\DeleteAvatar;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class DeleteAvatarController extends AbstractShowController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = UserSerializer::class;

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
            new DeleteAvatar(Arr::get($request->getQueryParams(), 'id'), RequestUtil::getActor($request))
        );
    }
}
