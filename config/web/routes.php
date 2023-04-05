<?php

declare(strict_types=1);

use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

use Yiisoft\DataResponse\Middleware\FormatDataResponseAsJson;
use Yiisoft\DataResponse\Middleware\FormatDataResponseAsHtml;

use NMForce\PHPDTS\Controller\HomeController;

return [
    Group::create()
        ->middleware(FormatDataResponseAsHtml::class)
        ->routes(
            Route::get('[/]')
                ->action([HomeController::class, 'index'])
                ->name('/acdts/index'),
        ),
];
