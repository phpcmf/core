<?php

use Cmf\Foundation\Paths;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;

if (! function_exists('resolve')) {
    /**
     * 从容器解析服务
     *
     * @template T
     * @param string|class-string<T> $name
     * @param array $parameters
     * @return T|mixed
     */
    function resolve(string $name, array $parameters = [])
    {
        return Container::getInstance()->make($name, $parameters);
    }
}

// 以下内容都已永久弃用。我们使用的一些 laravel 组件（例如任务调度）需要它们，它们不应该在扩展代码中使用。

if (! function_exists('app')) {
    /**
     * @deprecated perpetually.
     *
     * @param  string  $make
     * @param  array   $parameters
     * @return mixed|\Illuminate\Container\Container
     */
    function app($make = null, $parameters = [])
    {
        if (is_null($make)) {
            return Container::getInstance();
        }

        return resolve($make, $parameters);
    }
}

if (! function_exists('base_path')) {
    /**
     * @deprecated perpetually.
     *
     * 获取安装基础的路径
     *
     * @param  string  $path
     * @return string
     */
    function base_path($path = '')
    {
        return resolve(Paths::class)->base.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('public_path')) {
    /**
     * @deprecated perpetually.
     *
     * 获取公用文件夹的路径
     *
     * @param  string  $path
     * @return string
     */
    function public_path($path = '')
    {
        return resolve(Paths::class)->public.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('storage_path')) {
    /**
     * @deprecated perpetually.
     *
     * 获取存储文件夹的路径
     *
     * @param  string  $path
     * @return string
     */
    function storage_path($path = '')
    {
        return resolve(Paths::class)->storage.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('event')) {
    /**
     * @deprecated perpetually.
     *
     * 触发事件并调用侦听器
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array|null
     */
    function event($event, $payload = [], $halt = false)
    {
        return resolve('events')->dispatch($event, $payload, $halt);
    }
}

if (! function_exists('config')) {
    /**
     * @deprecated 不使用，将转移到 cmf/laravel-helpers.
     */
    function config(string $key, $default = null)
    {
        return resolve(Repository::class)->get($key, $default);
    }
}
