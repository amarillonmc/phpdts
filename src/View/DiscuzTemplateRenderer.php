<?php

declare(strict_types=1);

namespace NMForce\PHPDTS\View;

use Throwable;

use function extract;
use function func_get_arg;
use function ob_end_clean;
use function ob_get_clean;
use function ob_get_level;
use function ob_implicit_flush;
use function ob_start;

use Yiisoft\Aliases\Aliases;

use Yiisoft\View\ViewInterface;
use Yiisoft\View\TemplateRendererInterface;

final class DiscuzTemplateRenderer implements TemplateRendererInterface
{
    public function __construct(private Aliases $aliases)
    {
    }

    public function render(ViewInterface $view, string $template, array $parameters): string
    {
        defined('IN_GAME') or define('IN_GAME', TRUE);
        defined('GAME_ROOT') or define('GAME_ROOT', "{$this->aliases->get('@root')}/");
        defined('TPLDIR') or define('TPLDIR', './templates/default');
        defined('TEMPLATEID') or define('TEMPLATEID', 1);
        defined('CURSCRIPT') or define('CURSCRIPT', 'index');

        $tplrefresh = 1;
        require_once GAME_ROOT . './include/global.func.php';

        $renderer = function (): void {
            extract(func_get_arg(1), EXTR_OVERWRITE);
            require template(basename(func_get_arg(0), '.htm'));
        };

        $obInitialLevel = ob_get_level();
        ob_start();
        ob_implicit_flush(false);
        try {
            $renderer->bindTo($view)($template, $parameters);
            return ob_get_clean();
        } catch (Throwable $e) {
            while (ob_get_level() > $obInitialLevel) {
                ob_end_clean();
            }
            throw $e;
        }
    }
}
