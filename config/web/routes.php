<?php

declare(strict_types=1);

use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

use Yiisoft\DataResponse\Middleware\FormatDataResponseAsJson;

use NMForce\PHPDTS\Controller\HomeController;

return [
    Group::create()
        ->middleware(FormatDataResponseAsJson::class)
        ->routes(
            Route::get('[/]')
                ->action([HomeController::class, 'index'])
                ->name('default'),
            Route::get('/home')
                ->action([HomeController::class, 'index'])
                ->name('home'),
        ),
];
