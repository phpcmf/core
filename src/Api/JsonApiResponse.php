<?php

namespace Cmf\Api;

use Laminas\Diactoros\Response\JsonResponse;
use Tobscure\JsonApi\Document;

class JsonApiResponse extends JsonResponse
{
    /**
     * {@inheritdoc}
     */
    public function __construct(Document $document, $status = 200, array $headers = [], $encodingOptions = 15)
    {
        $headers['content-type'] = 'application/vnd.api+json';

        // The call to jsonSerialize prevents rare issues with json_encode() failing with a
        // syntax error even though Document implements the JsonSerializable interface.
        parent::__construct($document->jsonSerialize(), $status, $headers, $encodingOptions);
    }
}
