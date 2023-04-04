<?php

declare(strict_types=1);

use yiisoft\Definitions\DynamicReference;
use yiisoft\Definitions\Reference;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mysql\ConnectionPDO;

use Yiisoft\Db\Driver\PDO\PDODriverInterface;
use Yiisoft\Db\Mysql\PDODriver;

use Yiisoft\Db\Mysql\Dsn;

/** @var array $params */

return [
    ConnectionInterface::class => ConnectionPDO::class,
    PDODriverInterface::class => PDODriver::class,
    PDODriver::class => [
        '__construct()' => [
            'dsn' => DynamicReference::to(static fn (Dsn $dsn) => $dsn->asString()),
            'username' => $params['db']['username'],
            'password' => $params['db']['password'],
        ]
    ],
    Dsn::class => [
        '__construct()' => [
            'driver' => $params['db']['driver'],
            'host' => $params['db']['host'],
            'databaseName' => $params['db']['databaseName'],
            'port' => $params['db']['port'],
            'options' => $params['db']['options']
        ]
    ]
];
