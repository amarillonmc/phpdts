<?php

declare(strict_types=1);

use Yiisoft\Definitions\DynamicReference;
use Yiisoft\Definitions\Reference;

use Yiisoft\Aliases\Aliases;

use Yiisoft\View\WebView;

use NMForce\PHPDTS\View\DiscuzTemplateRenderer;

/** @var array $params */

return [
    WebView::class => [
        '__construct()' => [
            'basePath' => DynamicReference::to(
                static fn (Aliases $aliases) => $aliases->get($params['yiisoft/view']['basePath'])
            ),
        ],
        'withDefaultExtension()' => [
            'htm',
        ],
        'withRenderers()' => [['htm' => Reference::to(DiscuzTemplateRenderer::class)]],
        'setParameters()' => [
            $params['yiisoft/view']['parameters'],
        ],
        'reset' => function () use ($params) {
            /** @var WebView $this */
            $this->clear();
            $this->setParameters($params['yiisoft/view']['parameters']);
        },
    ],
];
