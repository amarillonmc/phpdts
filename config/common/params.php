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
            '@runtime' => '@root/runtime',
            '@vendor' => '@root/vendor',
        ],
    ],
    'db' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'databaseName' => 'acdts3',
        'port' => '3306',
        'options' => ['charset' => 'utf8mb4'],
        'username' => 'root',
        'password' => 'mylittlepony',
    ]
];
