<?php

declare(strict_types=1);

use Yiisoft\Definitions\Reference;

use NMForce\PHPDTS\ViewInjection\ApplicationViewInjection;

return [
    'yiisoft/aliases' => [
        'aliases' => [
            '@public' => '@root/public',
            '@assets' => '@public/assets',
            '@layout' => '@root/templates/default',
            '@views' => '@root/templates/default',
        ],
    ],
    'yiisoft/yii-view' => [
        'layout' => null,
        'injections' => [
            Reference::to(ApplicationViewInjection::class),
        ],
    ],
];
