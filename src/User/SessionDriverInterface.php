<?php

namespace Cmf\User;

use Cmf\Foundation\Config;
use Cmf\Settings\SettingsRepositoryInterface;
use SessionHandlerInterface;

interface SessionDriverInterface
{
    /**
     * Build a session handler to handle sessions.
     * Settings and configuration can either be pulled from the PHPCmf settings repository
     * or the config.php file.
     *
     * @param SettingsRepositoryInterface $settings: An instance of the PHPCmf settings repository.
     * @param Config $config: An instance of the wrapper class around `config.php`.
     */
    public function build(SettingsRepositoryInterface $settings, Config $config): SessionHandlerInterface;
}
