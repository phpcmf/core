<?php

namespace Cmf\Api\Controller;

use Cmf\Api\Serializer\NotificationSerializer;
use Cmf\Http\RequestUtil;
use Cmf\Notification\Command\ReadNotification;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class UpdateNotificationController extends AbstractShowController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = NotificationSerializer::class;

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
        $id = Arr::get($request->getQueryParams(), 'id');
        $actor = RequestUtil::getActor($request);

        return $this->bus->dispatch(
            new ReadNotification($id, $actor)
        );
    }
}
