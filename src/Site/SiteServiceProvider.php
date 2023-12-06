<?php

namespace Cmf\Site;

use Cmf\Extension\Event\Disabled;
use Cmf\Extension\Event\Enabled;
use Cmf\Formatter\Formatter;
use Cmf\Foundation\AbstractServiceProvider;
use Cmf\Foundation\ErrorHandling\Registry;
use Cmf\Foundation\ErrorHandling\Reporter;
use Cmf\Foundation\ErrorHandling\ViewFormatter;
use Cmf\Foundation\ErrorHandling\WhoopsFormatter;
use Cmf\Foundation\Event\ClearingCache;
use Cmf\Frontend\AddLocaleAssets;
use Cmf\Frontend\AddTranslations;
use Cmf\Frontend\Assets;
use Cmf\Frontend\Compiler\Source\SourceCollector;
use Cmf\Frontend\RecompileFrontendAssets;
use Cmf\Http\Middleware as HttpMiddleware;
use Cmf\Http\RouteCollection;
use Cmf\Http\RouteHandlerFactory;
use Cmf\Http\UrlGenerator;
use Cmf\Locale\LocaleManager;
use Cmf\Settings\Event\Saved;
use Cmf\Settings\Event\Saving;
use Cmf\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Laminas\Stratigility\MiddlewarePipe;
use Symfony\Contracts\Translation\TranslatorInterface;

class SiteServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->extend(UrlGenerator::class, function (UrlGenerator $url, Container $container) {
            return $url->addCollection('site', $container->make('cmf.site.routes'));
        });

        $this->container->singleton('cmf.site.routes', function (Container $container) {
            $routes = new RouteCollection;
            $this->populateRoutes($routes, $container);

            return $routes;
        });

        $this->container->afterResolving('cmf.site.routes', function (RouteCollection $routes, Container $container) {
            $this->setDefaultRoute($routes, $container);
        });

        $this->container->singleton('cmf.site.middleware', function () {
            return [
                HttpMiddleware\InjectActorReference::class,
                'cmf.site.error_handler',
                HttpMiddleware\ParseJsonBody::class,
                HttpMiddleware\CollectGarbage::class,
                HttpMiddleware\StartSession::class,
                HttpMiddleware\RememberFromCookie::class,
                HttpMiddleware\AuthenticateWithSession::class,
                HttpMiddleware\SetLocale::class,
                'cmf.site.route_resolver',
                HttpMiddleware\CheckCsrfToken::class,
                HttpMiddleware\ShareErrorsFromSession::class,
                HttpMiddleware\CmfPromotionHeader::class,
                HttpMiddleware\ReferrerPolicyHeader::class,
                HttpMiddleware\ContentTypeOptionsHeader::class
            ];
        });

        $this->container->bind('cmf.site.error_handler', function (Container $container) {
            return new HttpMiddleware\HandleErrors(
                $container->make(Registry::class),
                $container['cmf.config']->inDebugMode() ? $container->make(WhoopsFormatter::class) : $container->make(ViewFormatter::class),
                $container->tagged(Reporter::class)
            );
        });

        $this->container->bind('cmf.site.route_resolver', function (Container $container) {
            return new HttpMiddleware\ResolveRoute($container->make('cmf.site.routes'));
        });

        $this->container->singleton('cmf.site.handler', function (Container $container) {
            $pipe = new MiddlewarePipe;

            foreach ($container->make('cmf.site.middleware') as $middleware) {
                $pipe->pipe($container->make($middleware));
            }

            $pipe->pipe(new HttpMiddleware\ExecuteRoute());

            return $pipe;
        });

        $this->container->bind('cmf.assets.site', function (Container $container) {
            /** @var Assets $assets */
            $assets = $container->make('cmf.assets.factory')('site');

            $assets->js(function (SourceCollector $sources) use ($container) {
                $sources->addFile(__DIR__.'/../../js/dist/site.js');
                $sources->addString(function () use ($container) {
                    return $container->make(Formatter::class)->getJs();
                });
            });

            $assets->css(function (SourceCollector $sources) use ($container) {
                $sources->addFile(__DIR__.'/../../less/site.less');
                $sources->addString(function () use ($container) {
                    return $container->make(SettingsRepositoryInterface::class)->get('custom_less', '');
                });
            });

            $container->make(AddTranslations::class)->forFrontend('site')->to($assets);
            $container->make(AddLocaleAssets::class)->to($assets);

            return $assets;
        });

        $this->container->bind('cmf.frontend.site', function (Container $container) {
            return $container->make('cmf.frontend.factory')('site');
        });

        $this->container->singleton('cmf.site.discussions.sortmap', function () {
            return [
                'latest' => '-lastPostedAt',
                'top' => '-commentCount',
                'newest' => '-createdAt',
                'oldest' => 'createdAt'
            ];
        });
    }

    public function boot(Container $container, Dispatcher $events, Factory $view)
    {
        $this->loadViewsFrom(__DIR__.'/../../views', 'cmf.site');

        $view->share([
            'translator' => $container->make(TranslatorInterface::class),
            'settings' => $container->make(SettingsRepositoryInterface::class)
        ]);

        $events->listen(
            [Enabled::class, Disabled::class, ClearingCache::class],
            function () use ($container) {
                $recompile = new RecompileFrontendAssets(
                    $container->make('cmf.assets.site'),
                    $container->make(LocaleManager::class)
                );
                $recompile->flush();
            }
        );

        $events->listen(
            Saved::class,
            function (Saved $event) use ($container) {
                $recompile = new RecompileFrontendAssets(
                    $container->make('cmf.assets.site'),
                    $container->make(LocaleManager::class)
                );
                $recompile->whenSettingsSaved($event);

                $validator = new ValidateCustomLess(
                    $container->make('cmf.assets.site'),
                    $container->make('cmf.locales'),
                    $container,
                    $container->make('cmf.less.config')
                );
                $validator->whenSettingsSaved($event);
            }
        );

        $events->listen(
            Saving::class,
            function (Saving $event) use ($container) {
                $validator = new ValidateCustomLess(
                    $container->make('cmf.assets.site'),
                    $container->make('cmf.locales'),
                    $container,
                    $container->make('cmf.less.config')
                );
                $validator->whenSettingsSaving($event);
            }
        );
    }

    /**
     * Populate the site client routes.
     *
     * @param RouteCollection $routes
     * @param Container       $container
     */
    protected function populateRoutes(RouteCollection $routes, Container $container)
    {
        $factory = $container->make(RouteHandlerFactory::class);

        $callback = include __DIR__.'/routes.php';
        $callback($routes, $factory);
    }

    /**
     * Determine the default route.
     *
     * @param RouteCollection $routes
     * @param Container       $container
     */
    protected function setDefaultRoute(RouteCollection $routes, Container $container)
    {
        $factory = $container->make(RouteHandlerFactory::class);
        $defaultRoute = $container->make('cmf.settings')->get('default_route');

        if (isset($routes->getRouteData()[0]['GET'][$defaultRoute]['handler'])) {
            $toDefaultController = $routes->getRouteData()[0]['GET'][$defaultRoute]['handler'];
        } else {
            $toDefaultController = $factory->toSite(Content\Index::class);
        }

        $routes->get(
            '/',
            'default',
            $toDefaultController
        );
    }
}
