<?php

declare(strict_types=1);

namespace NMForce\PHPDTS\Controller;

use Psr\Http\Message\ResponseInterface;

use Yiisoft\Yii\View\ViewRenderer;

final class HomeController
{
    public function __construct(
        private ViewRenderer $viewRenderer,
    ) {
    }

    public function index(): ResponseInterface
    {
        $now = time() + 8 * 3600 + 0 * 60;
        list($sec, $min, $hour, $day, $month, $year, $wday) = explode(',', date("s,i,H,j,n,Y,w", $now));
        return $this->viewRenderer->render('index', [
            'sec' => $sec,
            'min' => $min,
            'hour' => $hour,
            'day' => $day,
            'month' => $month,
            'year' => $year,
            'wday' => $wday,
            'now' => $now,
        ]);
    }
}
