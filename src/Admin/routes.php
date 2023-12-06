<?php

use Cmf\Admin\Content\Index;
use Cmf\Admin\Controller\UpdateExtensionController;
use Cmf\Http\RouteCollection;
use Cmf\Http\RouteHandlerFactory;

return function (RouteCollection $map, RouteHandlerFactory $route) {
    $map->get(
        '/',
        'index',
        $route->toAdmin(Index::class)
    );

    $map->post(
        '/extensions/{name}',
        'extensions.update',
        $route->toController(UpdateExtensionController::class)
    );
};
