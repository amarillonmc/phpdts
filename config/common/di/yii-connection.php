<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mysql\ConnectionPDO;

use Yiisoft\Db\Driver\PDO\PDODriverInterface;
use Yiisoft\Db\Mysql\PDODriver;

return [
    ConnectionInterface::class => ConnectionPDO::class,
    PDODriverInterface::class => PDODriver::class,
    PDODriver::class => [
        '__construct()' => [
            'dsn' => $params['db']['dsn'],
            'username' => $params['db']['username'],
            'password' => $params['db']['password'],
        ]
    ]
];
