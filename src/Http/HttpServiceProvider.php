<?php

namespace Cmf\Http;

use Cmf\Discussion\Discussion;
use Cmf\Discussion\IdWithTransliteratedSlugDriver;
use Cmf\Discussion\Utf8SlugDriver;
use Cmf\Foundation\AbstractServiceProvider;
use Cmf\Http\Access\ScopeAccessTokenVisibility;
use Cmf\Settings\SettingsRepositoryInterface;
use Cmf\User\IdSlugDriver;
use Cmf\User\User;
use Cmf\User\UsernameSlugDriver;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;

class HttpServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->singleton('cmf.http.csrfExemptPaths', function () {
            return ['token'];
        });

        $this->container->bind(Middleware\CheckCsrfToken::class, function (Container $container) {
            return new Middleware\CheckCsrfToken($container->make('cmf.http.csrfExemptPaths'));
        });

        $this->container->singleton('cmf.http.slugDrivers', function () {
            return [
                Discussion::class => [
                    'default' => IdWithTransliteratedSlugDriver::class,
                    'utf8' => Utf8SlugDriver::class,
                ],
                User::class => [
                    'default' => UsernameSlugDriver::class,
                    'id' => IdSlugDriver::class
                ],
            ];
        });

        $this->container->singleton('cmf.http.selectedSlugDrivers', function (Container $container) {
            $settings = $container->make(SettingsRepositoryInterface::class);

            $compiledDrivers = [];

            foreach ($container->make('cmf.http.slugDrivers') as $resourceClass => $resourceDrivers) {
                $driverKey = $settings->get("slug_driver_$resourceClass", 'default');

                $driverClass = Arr::get($resourceDrivers, $driverKey, $resourceDrivers['default']);

                $compiledDrivers[$resourceClass] = $container->make($driverClass);
            }

            return $compiledDrivers;
        });
        $this->container->bind(SlugManager::class, function (Container $container) {
            return new SlugManager($container->make('cmf.http.selectedSlugDrivers'));
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->setAccessTokenTypes();

        AccessToken::registerVisibilityScoper(new ScopeAccessTokenVisibility(), 'view');
    }

    protected function setAccessTokenTypes()
    {
        $models = [
            DeveloperAccessToken::class,
            RememberAccessToken::class,
            SessionAccessToken::class
        ];

        foreach ($models as $model) {
            AccessToken::setModel($model::$type, $model);
        }
    }
}
