<?php

declare(strict_types=1);

use Yiisoft\Router\Group;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollectionInterface;
use Yiisoft\Router\RouteCollectorInterface;

return [
    RouteCollectionInterface::class => static function (RouteCollectorInterface $collector) use ($config) {
        $collector->addGroup(Group::create()->routes(...$config->get('routes')));;

        return new RouteCollection($collector);
    }
];
