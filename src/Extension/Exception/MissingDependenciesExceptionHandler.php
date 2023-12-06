<?php

namespace Cmf\Extension\Exception;

use Cmf\Extension\ExtensionManager;
use Cmf\Foundation\ErrorHandling\HandledError;

class MissingDependenciesExceptionHandler
{
    public function handle(MissingDependenciesException $e): HandledError
    {
        return (new HandledError(
            $e,
            'missing_dependencies',
            409
        ))->withDetails($this->errorDetails($e));
    }

    protected function errorDetails(MissingDependenciesException $e): array
    {
        return [
            [
                'extension' => $e->extension->getTitle(),
                'extensions' => ExtensionManager::pluckTitles($e->missing_dependencies),
            ]
        ];
    }
}
