<?php

namespace Cmf\Extension\Exception;

use Cmf\Extension\ExtensionManager;
use Cmf\Foundation\ErrorHandling\HandledError;

class DependentExtensionsExceptionHandler
{
    public function handle(DependentExtensionsException $e): HandledError
    {
        return (new HandledError(
            $e,
            'dependent_extensions',
            409
        ))->withDetails($this->errorDetails($e));
    }

    protected function errorDetails(DependentExtensionsException $e): array
    {
        return [
            [
                'extension' => $e->extension->getTitle(),
                'extensions' => ExtensionManager::pluckTitles($e->dependent_extensions),
            ]
        ];
    }
}
