<?php

namespace Cmf\Extension\Command;

use Cmf\Extension\ExtensionManager;

class ToggleExtensionHandler
{
    /**
     * @var ExtensionManager
     */
    protected $extensions;

    public function __construct(ExtensionManager $extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * @throws \Cmf\User\Exception\PermissionDeniedException
     * @throws \Cmf\Extension\Exception\MissingDependenciesException
     * @throws \Cmf\Extension\Exception\DependentExtensionsException
     */
    public function handle(ToggleExtension $command)
    {
        $command->actor->assertAdmin();

        if ($command->enabled) {
            $this->extensions->enable($command->name);
        } else {
            $this->extensions->disable($command->name);
        }
    }
}
