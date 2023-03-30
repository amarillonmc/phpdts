<?php

declare(strict_types=1);

namespace NMForce\PHPDTS\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

use Yiisoft\DataResponse\DataResponseFactoryInterface;

final class HomeController
{
    public function __construct(
        private StreamFactoryInterface $streamFactory,
        private DataResponseFactoryInterface $responseFactory,
    ) {
    }

    public function index(): ResponseInterface
    {
        return $this->responseFactory->createResponse([
            'message' => 'hello',
        ]);
    }
}
