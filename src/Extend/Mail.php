<?php

namespace Cmf\Extend;

use Cmf\Extension\Extension;
use Illuminate\Contracts\Container\Container;

class Mail implements ExtenderInterface
{
    private $drivers = [];

    /**
     * Add a mail driver.
     *
     * @param string $identifier: Identifier for mail driver. E.g. 'smtp' for SmtpDriver.
     * @param string $driver: ::class attribute of driver class, which must implement Cmf\Mail\DriverInterface.
     * @return self
     */
    public function driver(string $identifier, string $driver): self
    {
        $this->drivers[$identifier] = $driver;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('mail.supported_drivers', function ($existingDrivers) {
            return array_merge($existingDrivers, $this->drivers);
        });
    }
}
