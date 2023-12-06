<?php

namespace Cmf\Install;

use Cmf\Foundation\AppInterface;
use Cmf\Foundation\ErrorHandling\Registry;
use Cmf\Foundation\ErrorHandling\Reporter;
use Cmf\Foundation\ErrorHandling\WhoopsFormatter;
use Cmf\Http\Middleware as HttpMiddleware;
use Cmf\Install\Console\InstallCommand;
use Illuminate\Contracts\Container\Container;
use Laminas\Stratigility\MiddlewarePipe;

class Installer implements AppInterface
{
    /**
     * @var Container
     */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return \Psr\Http\Server\RequestHandlerInterface
     */
    public function getRequestHandler()
    {
        $pipe = new MiddlewarePipe;
        $pipe->pipe(new HttpMiddleware\HandleErrors(
            $this->container->make(Registry::class),
            $this->container->make(WhoopsFormatter::class),
            $this->container->tagged(Reporter::class)
        ));
        $pipe->pipe($this->container->make(HttpMiddleware\StartSession::class));
        $pipe->pipe(
            new HttpMiddleware\ResolveRoute($this->container->make('cmf.install.routes'))
        );
        $pipe->pipe(new HttpMiddleware\ExecuteRoute());

        return $pipe;
    }

    /**
     * @return \Symfony\Component\Console\Command\Command[]
     */
    public function getConsoleCommands()
    {
        return [
            new InstallCommand(
                $this->container->make(Installation::class)
            ),
        ];
    }
}
