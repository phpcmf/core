<?php

namespace Cmf\User;

use Cmf\Foundation\AbstractServiceProvider;
use Cmf\Foundation\Config;
use Cmf\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use SessionHandlerInterface;

class SessionServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->singleton('cmf.session.drivers', function () {
            return [];
        });

        $this->container->singleton('session', function (Container $container) {
            $manager = new SessionManager($container);
            $drivers = $container->make('cmf.session.drivers');
            $settings = $container->make(SettingsRepositoryInterface::class);
            $config = $container->make(Config::class);

            /**
             * 默认为 Laravel 已经定义的文件驱动程序
             *
             * @see \Illuminate\Session\SessionManager::createFileDriver()
             */
            $manager->setDefaultDriver('file');

            foreach ($drivers as $driver => $className) {
                /** @var SessionDriverInterface $driverInstance */
                $driverInstance = $container->make($className);

                $manager->extend($driver, function () use ($settings, $config, $driverInstance) {
                    return $driverInstance->build($settings, $config);
                });
            }

            return $manager;
        });

        $this->container->alias('session', SessionManager::class);

        $this->container->singleton('session.handler', function (Container $container): SessionHandlerInterface {
            /** @var SessionManager $manager */
            $manager = $container->make('session');

            return $manager->handler();
        });

        $this->container->alias('session.handler', SessionHandlerInterface::class);
    }
}
