<?php

namespace Cmf\Api\Controller;

use Cmf\Api\Serializer\ExtensionReadmeSerializer;
use Cmf\Extension\ExtensionManager;
use Cmf\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowExtensionReadmeController extends AbstractShowController
{
    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * {@inheritdoc}
     */
    public $serializer = ExtensionReadmeSerializer::class;

    public function __construct(ExtensionManager $extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $extensionName = Arr::get($request->getQueryParams(), 'name');

        RequestUtil::getActor($request)->assertAdmin();

        return $this->extensions->getExtension($extensionName);
    }
}
