<?php

use Yiisoft\Definitions\DynamicReference;
use Yiisoft\Definitions\Reference;
use Yiisoft\Definitions\ReferencesArray;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Yii\Http\Handler\NotFoundHandler;



use Yiisoft\Session\SessionMiddleware;
use Yiisoft\Csrf\CsrfMiddleware;
use Yiisoft\Router\Middleware\Router;

return [
    Yiisoft\Yii\Http\Application::class => [
        '__construct()' => [
            'dispatcher' => DynamicReference::to([
                'class' => MiddlewareDispatcher::class,
                'withMiddlewares()' => [
                    [
                        SessionMiddleware::class,
                        CsrfMiddleware::class,
                        Router::class,
                    ]
                ],
            ]),
            'fallbackHandler' => Reference::to(NotFoundHandler::class),
        ],
    ]
];
