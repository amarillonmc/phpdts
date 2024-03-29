<?php

declare(strict_types=1);

return [
    'config-plugin' => [
        'params' => 'common/params.php',
        'di' => 'common/di/*.php',
        'events' => [],
        'bootstrap' => [],

        'params-console' => [
            '$params',
            'console/params.php',
        ],
        'di-console' => [
            '$di',
            'console/di/*.php',
        ],
        'events-console' => '$events',
        'bootstrap-console' => '$bootstrap',

        'params-web' => [
            '$params',
            'web/params.php',
        ],
        'di-web' => [
            '$di',
            'web/di/*.php',
        ],
        'events-web' => '$events',
        'routes' => 'web/routes.php',
        'bootstrap-web' => '$bootstrap',
    ],
    'config-plugin-environments' => [
        'dev' => [
            'params' => [
                'environments/dev/params.php',
            ],
        ],
        'prod' => [
            'params' => [
                'environments/prod/params.php',
            ],
        ],
        'test' => [
            'params' => [
                'environments/test/params.php',
            ],
        ],
    ],
    'config-plugin-options' => [
        'source-directory' => 'config',
    ],
];
