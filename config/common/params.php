<?php

declare(strict_types=1);

return [
    'app' => [
        'charset' => 'UTF-8',
        'locale' => 'zh',
        'name' => 'phpdts',
    ],
    'locale' => [
        'locales' => ['en' => 'en-US', 'zh' => 'zh-CN']
    ],
    'yiisoft/aliases' => [
        'aliases' => [
            '@root' => dirname(__DIR__, 2),
            '@assets' => '@root/public/assets',
            '@public' => '@root/public',
            '@runtime' => '@root/runtime',
            '@vendor' => '@root/vendor',
        ],
    ],
];
