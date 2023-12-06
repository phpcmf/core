<?php

namespace Cmf\Api;

use Cmf\Api\Controller\AbstractSerializeController;
use Cmf\Api\Serializer\AbstractSerializer;
use Cmf\Api\Serializer\BasicDiscussionSerializer;
use Cmf\Api\Serializer\NotificationSerializer;
use Cmf\Foundation\AbstractServiceProvider;
use Cmf\Foundation\ErrorHandling\JsonApiFormatter;
use Cmf\Foundation\ErrorHandling\Registry;
use Cmf\Foundation\ErrorHandling\Reporter;
use Cmf\Http\Middleware as HttpMiddleware;
use Cmf\Http\RouteCollection;
use Cmf\Http\RouteHandlerFactory;
use Cmf\Http\UrlGenerator;
use Illuminate\Contracts\Container\Container;
use Laminas\Stratigility\MiddlewarePipe;

class ApiServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->container->extend(UrlGenerator::class, function (UrlGenerator $url, Container $container) {
            return $url->addCollection('api', $container->make('cmf.api.routes'), 'api');
        });

        $this->container->singleton('cmf.api.routes', function () {
            $routes = new RouteCollection;
            $this->populateRoutes($routes);

            return $routes;
        });

        $this->container->singleton('cmf.api.throttlers', function () {
            return [
                'bypassThrottlingAttribute' => function ($request) {
                    if ($request->getAttribute('bypassThrottling')) {
                        return false;
                    }
                }
            ];
        });

        $this->container->bind(Middleware\ThrottleApi::class, function (Container $container) {
            return new Middleware\ThrottleApi($container->make('cmf.api.throttlers'));
        });

        $this->container->singleton('cmf.api.middleware', function () {
            return [
                HttpMiddleware\InjectActorReference::class,
                'cmf.api.error_handler',
                HttpMiddleware\ParseJsonBody::class,
                Middleware\FakeHttpMethods::class,
                HttpMiddleware\StartSession::class,
                HttpMiddleware\RememberFromCookie::class,
                HttpMiddleware\AuthenticateWithSession::class,
                HttpMiddleware\AuthenticateWithHeader::class,
                HttpMiddleware\SetLocale::class,
                'cmf.api.route_resolver',
                HttpMiddleware\CheckCsrfToken::class,
                Middleware\ThrottleApi::class
            ];
        });

        $this->container->bind('cmf.api.error_handler', function (Container $container) {
            return new HttpMiddleware\HandleErrors(
                $container->make(Registry::class),
                new JsonApiFormatter($container['cmf.config']->inDebugMode()),
                $container->tagged(Reporter::class)
            );
        });

        $this->container->bind('cmf.api.route_resolver', function (Container $container) {
            return new HttpMiddleware\ResolveRoute($container->make('cmf.api.routes'));
        });

        $this->container->singleton('cmf.api.handler', function (Container $container) {
            $pipe = new MiddlewarePipe;

            foreach ($this->container->make('cmf.api.middleware') as $middleware) {
                $pipe->pipe($container->make($middleware));
            }

            $pipe->pipe(new HttpMiddleware\ExecuteRoute());

            return $pipe;
        });

        $this->container->singleton('cmf.api.notification_serializers', function () {
            return [
                'discussionRenamed' => BasicDiscussionSerializer::class
            ];
        });

        $this->container->singleton('cmf.api_client.exclude_middleware', function () {
            return [
                HttpMiddleware\InjectActorReference::class,
                HttpMiddleware\ParseJsonBody::class,
                Middleware\FakeHttpMethods::class,
                HttpMiddleware\StartSession::class,
                HttpMiddleware\AuthenticateWithSession::class,
                HttpMiddleware\AuthenticateWithHeader::class,
                HttpMiddleware\CheckCsrfToken::class,
                HttpMiddleware\RememberFromCookie::class,
            ];
        });

        $this->container->singleton(Client::class, function ($container) {
            $pipe = new MiddlewarePipe;

            $exclude = $container->make('cmf.api_client.exclude_middleware');

            $middlewareStack = array_filter($container->make('cmf.api.middleware'), function ($middlewareClass) use ($exclude) {
                return ! in_array($middlewareClass, $exclude);
            });

            foreach ($middlewareStack as $middleware) {
                $pipe->pipe($container->make($middleware));
            }

            $pipe->pipe(new HttpMiddleware\ExecuteRoute());

            return new Client($pipe);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container)
    {
        $this->setNotificationSerializers();

        AbstractSerializeController::setContainer($container);

        AbstractSerializer::setContainer($container);
    }

    /**
     * Register notification serializers.
     */
    protected function setNotificationSerializers()
    {
        $serializers = $this->container->make('cmf.api.notification_serializers');

        foreach ($serializers as $type => $serializer) {
            NotificationSerializer::setSubjectSerializer($type, $serializer);
        }
    }

    /**
     * Populate the API routes.
     *
     * @param RouteCollection $routes
     */
    protected function populateRoutes(RouteCollection $routes)
    {
        $factory = $this->container->make(RouteHandlerFactory::class);

        $callback = include __DIR__.'/routes.php';
        $callback($routes, $factory);
    }
}
