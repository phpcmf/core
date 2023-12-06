<?php

namespace Cmf\Foundation;

interface SiteInterface
{
    /**
     * Create and boot a PHPCmf application instance.
     *
     * @return AppInterface
     */
    public function bootApp(): AppInterface;
}
