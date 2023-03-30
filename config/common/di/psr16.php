<?php

declare(strict_types=1);

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\ArrayCache;

return [
    CacheInterface::class => ArrayCache::class
];