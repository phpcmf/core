<?php

namespace Cmf\Frontend\Driver;

use Cmf\Frontend\Document;
use Psr\Http\Message\ServerRequestInterface;

interface TitleDriverInterface
{
    public function makeTitle(Document $document, ServerRequestInterface $request, array $siteApiDocument): string;
}
