<?php

namespace Cmf\Admin;

use Cmf\Extension\Event\Disabled;
use Cmf\Extension\Event\Enabled;
use Cmf\Foundation\AbstractServiceProvider;
use Cmf\Foundation\ErrorHandling\Registry;
use Cmf\Foundation\ErrorHandling\Reporter;
use Cmf\Foundation\ErrorHandling\ViewFormatter;
use Cmf\Foundation\ErrorHandling\WhoopsFormatter;
use Cmf\Foundation\Event\ClearingCache;
use Cmf\Frontend\AddLocaleAssets;
use Cmf\Frontend\AddTranslations;
use Cmf\Frontend\Compiler\Source\SourceCollector;
use Cmf\Frontend\RecompileFrontendAssets;
use Cmf\Http\Middleware as HttpMiddleware;
use Cmf\Http\RouteCollection;
use Cmf\Http\RouteHandlerFactory;
use Cmf\Http\UrlGenerator;
use Cmf\Locale\LocaleManager;
use Cmf\Settings\Event\Saved;
use Illuminate\Contracts\Container\Container;
use Laminas\Stratigility\MiddlewarePipe;

class AdminServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->extend(UrlGenerator::class, function (UrlGenerator $url, Container $container) {
            return $url->addCollection('admin', $container->make('cmf.admin.routes'), 'admin');
        });

        $this->container->singleton('cmf.admin.routes', function () {
            $routes = new RouteCollection;
            $this->populateRoutes($routes);

            return $routes;
        });

        $this->container->singleton('cmf.admin.middleware', function () {
            return [
                HttpMiddleware\InjectActorReference::class,
                'cmf.admin.error_handler',
                HttpMiddleware\ParseJsonBody::class,
                HttpMiddleware\StartSession::class,
                HttpMiddleware\RememberFromCookie::class,
                HttpMiddleware\AuthenticateWithSession::class,
                HttpMiddleware\SetLocale::class,
                'cmf.admin.route_resolver',
                HttpMiddleware\CheckCsrfToken::class,
                Middleware\RequireAdministrateAbility::class,
                HttpMiddleware\ReferrerPolicyHeader::class,
                HttpMiddleware\ContentTypeOptionsHeader::class,
                Middleware\DisableBrowserCache::class,
            ];
        });

        $this->container->bind('cmf.admin.error_handler', function (Container $container) {
            return new HttpMiddleware\HandleErrors(
                $container->make(Registry::class),
                $container['cmf.config']->inDebugMode() ? $container->make(WhoopsFormatter::class) : $container->make(ViewFormatter::class),
                $container->tagged(Reporter::class)
            );
        });

        $this->container->bind('cmf.admin.route_resolver', function (Container $container) {
            return new HttpMiddleware\ResolveRoute($container->make('cmf.admin.routes'));
        });

        $this->container->singleton('cmf.admin.handler', function (Container $container) {
            $pipe = new MiddlewarePipe;

            foreach ($container->make('cmf.admin.middleware') as $middleware) {
                $pipe->pipe($container->make($middleware));
            }

            $pipe->pipe(new HttpMiddleware\ExecuteRoute());

            return $pipe;
        });

        $this->container->bind('cmf.assets.admin', function (Container $container) {
            /** @var \Cmf\Frontend\Assets $assets */
            $assets = $container->make('cmf.assets.factory')('admin');

            $assets->js(function (SourceCollector $sources) {
                $sources->addFile(__DIR__.'/../../js/dist/admin.js');
            });

            $assets->css(function (SourceCollector $sources) {
                $sources->addFile(__DIR__.'/../../less/admin.less');
            });

            $container->make(AddTranslations::class)->forFrontend('admin')->to($assets);
            $container->make(AddLocaleAssets::class)->to($assets);

            return $assets;
        });

        $this->container->bind('cmf.frontend.admin', function (Container $container) {
            /** @var \Cmf\Frontend\Frontend $frontend */
            $frontend = $container->make('cmf.frontend.factory')('admin');

            $frontend->content($container->make(Content\AdminPayload::class));

            return $frontend;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../../views', 'cmf.admin');

        $events = $this->container->make('events');

        $events->listen(
            [Enabled::class, Disabled::class, ClearingCache::class],
            function () {
                $recompile = new RecompileFrontendAssets(
                    $this->container->make('cmf.assets.admin'),
                    $this->container->make(LocaleManager::class)
                );
                $recompile->flush();
            }
        );

        $events->listen(
            Saved::class,
            function (Saved $event) {
                $recompile = new RecompileFrontendAssets(
                    $this->container->make('cmf.assets.admin'),
                    $this->container->make(LocaleManager::class)
                );
                $recompile->whenSettingsSaved($event);
            }
        );
    }

    /**
     * @param RouteCollection $routes
     */
    protected function populateRoutes(RouteCollection $routes)
    {
        $factory = $this->container->make(RouteHandlerFactory::class);

        $callback = include __DIR__.'/routes.php';
        $callback($routes, $factory);
    }
}
