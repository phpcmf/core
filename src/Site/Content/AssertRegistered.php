<?php

namespace Cmf\Site\Content;

use Cmf\Frontend\Document;
use Cmf\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface as Request;

class AssertRegistered
{
    public function __invoke(Document $document, Request $request)
    {
        RequestUtil::getActor($request)->assertRegistered();
    }
}
