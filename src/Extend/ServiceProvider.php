<?php

namespace Cmf\Extend;

use Cmf\Extension\Extension;
use Illuminate\Contracts\Container\Container;

class ServiceProvider implements ExtenderInterface
{
    private $providers = [];

    /**
     * Register a service provider.
     *
     * Service providers are an advanced feature and might give access to PHPCmf internals that do not come with backward compatibility.
     * Please read our documentation about service providers for recommendations.
     *
     * @param string $serviceProviderClass The ::class attribute of the service provider class.
     * @return self
     */
    public function register(string $serviceProviderClass): self
    {
        $this->providers[] = $serviceProviderClass;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $app = $container->make('cmf');

        foreach ($this->providers as $provider) {
            $app->register($provider);
        }
    }
}
