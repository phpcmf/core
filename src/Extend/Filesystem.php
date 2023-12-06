<?php

namespace Cmf\Extend;

use Cmf\Extension\Extension;
use Cmf\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;

class Filesystem implements ExtenderInterface
{
    private $disks = [];
    private $drivers = [];

    /**
     * Declare a new filesystem disk.
     * Disks represent storage locations, and are backed by storage drivers.
     * PHPCmf core uses disks for storing assets and avatars.
     *
     * By default, the "local" driver will be used for disks.
     * The "local" driver represents the filesystem where your PHPCmf installation is running.
     *
     * To declare a new disk, you must provide default configuration a "local" driver.
     *
     * @param string $name: The name of the disk.
     * @param string|callable $callback
     *
     * The callback can be a closure or an invokable class, and should accept:
     *  - \Cmf\Foundation\Paths $paths
     *  - \Cmf\Http\UrlGenerator $url
     *
     * The callable should return:
     * - A Laravel disk config array,
     *   The `driver` key is not necessary for this array, and will be ignored.
     *
     * @example
     * ```
     * ->disk('cmf-uploads', function (Paths $paths, UrlGenerator $url) {
     *       return [
     *          'root'   => "$paths->public/assets/uploads",
     *          'url'    => $url->to('site')->path('assets/uploads')
     *       ];
     *   });
     * ```
     *
     * @see https://laravel.com/docs/8.x/filesystem#configuration
     *
     * @return self
     */
    public function disk(string $name, $callback): self
    {
        $this->disks[$name] = $callback;

        return $this;
    }

    /**
     * Register a new filesystem driver.
     *
     * @param string $name: The name of the driver.
     * @param string $driverClass: The ::class attribute of the driver.
     *                             Driver must implement `\Cmf\Filesystem\DriverInterface`.
     * @return self
     */
    public function driver(string $name, string $driverClass): self
    {
        $this->drivers[$name] = $driverClass;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('cmf.filesystem.disks', function ($existingDisks) use ($container) {
            foreach ($this->disks as $name => $disk) {
                $existingDisks[$name] = ContainerUtil::wrapCallback($disk, $container);
            }

            return $existingDisks;
        });

        $container->extend('cmf.filesystem.drivers', function ($existingDrivers) {
            return array_merge($existingDrivers, $this->drivers);
        });
    }
}
