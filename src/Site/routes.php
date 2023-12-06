<?php

use Cmf\Site\Content;
use Cmf\Site\Controller;
use Cmf\Http\RouteCollection;
use Cmf\Http\RouteHandlerFactory;

return function (RouteCollection $map, RouteHandlerFactory $route) {
    $map->get(
        '/all',
        'index',
        $route->toSite(Content\Index::class)
    );

    $map->get(
        '/discussion/{id:\d+(?:-[^/]*)?}[/{near:[^/]*}]',
        'discussion',
        $route->toSite(Content\Discussion::class)
    );

    $map->get(
        '/user/{username}[/{filter:[^/]*}]',
        'user',
        $route->toSite(Content\User::class)
    );

    $map->get(
        '/settings',
        'settings',
        $route->toSite(Content\AssertRegistered::class)
    );

    $map->get(
        '/notifications',
        'notifications',
        $route->toSite(Content\AssertRegistered::class)
    );

    $map->get(
        '/logout',
        'logout',
        $route->toController(Controller\LogOutController::class)
    );

    $map->post(
        '/global-logout',
        'globalLogout',
        $route->toController(Controller\GlobalLogOutController::class)
    );

    $map->post(
        '/login',
        'login',
        $route->toController(Controller\LogInController::class)
    );

    $map->post(
        '/register',
        'register',
        $route->toController(Controller\RegisterController::class)
    );

    $map->get(
        '/confirm/{token}',
        'confirmEmail',
        $route->toController(Controller\ConfirmEmailViewController::class),
    );

    $map->post(
        '/confirm/{token}',
        'confirmEmail.submit',
        $route->toController(Controller\ConfirmEmailController::class),
    );

    $map->get(
        '/reset/{token}',
        'resetPassword',
        $route->toController(Controller\ResetPasswordController::class)
    );

    $map->post(
        '/reset',
        'savePassword',
        $route->toController(Controller\SavePasswordController::class)
    );
};
