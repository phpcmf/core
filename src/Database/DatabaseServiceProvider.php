<?php

namespace Cmf\Database;

use Cmf\Foundation\AbstractServiceProvider;
use Illuminate\Container\Container as ContainerImplementation;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;

class DatabaseServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->singleton(Manager::class, function (ContainerImplementation $container) {
            $manager = new Manager($container);

            $config = $container['cmf']->config('database');
            $config['engine'] = 'InnoDB';
            $config['prefix_indexes'] = true;

            $manager->addConnection($config, 'cmf');

            return $manager;
        });

        $this->container->singleton(ConnectionResolverInterface::class, function (Container $container) {
            $manager = $container->make(Manager::class);
            $manager->setAsGlobal();
            $manager->bootEloquent();

            $dbManager = $manager->getDatabaseManager();
            $dbManager->setDefaultConnection('cmf');

            return $dbManager;
        });

        $this->container->alias(ConnectionResolverInterface::class, 'db');

        $this->container->singleton(ConnectionInterface::class, function (Container $container) {
            $resolver = $container->make(ConnectionResolverInterface::class);

            return $resolver->connection();
        });

        $this->container->alias(ConnectionInterface::class, 'db.connection');
        $this->container->alias(ConnectionInterface::class, 'cmf.db');

        $this->container->singleton(MigrationRepositoryInterface::class, function (Container $container) {
            return new DatabaseMigrationRepository($container['cmf.db'], 'migrations');
        });

        $this->container->singleton('cmf.database.model_private_checkers', function () {
            return [];
        });
    }

    public function boot(Container $container)
    {
        AbstractModel::setConnectionResolver($container->make(ConnectionResolverInterface::class));
        AbstractModel::setEventDispatcher($container->make('events'));

        foreach ($container->make('cmf.database.model_private_checkers') as $modelClass => $checkers) {
            $modelClass::saving(function ($instance) use ($checkers) {
                foreach ($checkers as $checker) {
                    if ($checker($instance) === true) {
                        $instance->is_private = true;

                        return;
                    }
                }

                $instance->is_private = false;
            });
        }
    }
}
