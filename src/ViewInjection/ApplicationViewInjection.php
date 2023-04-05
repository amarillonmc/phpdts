<?php

declare(strict_types=1);

namespace NMForce\PHPDTS\ViewInjection;

use Yiisoft\Yii\View\CommonParametersInjectionInterface;

use Yiisoft\Aliases\Aliases;

final class ApplicationViewInjection implements CommonParametersInjectionInterface
{

    public function __construct(
        private Aliases $aliases
    ) {
    }

    public function getCommonParameters(): array
    {
        defined('IN_GAME') or define('IN_GAME', TRUE);
        defined('GAME_ROOT') or define('GAME_ROOT', "{$this->aliases->get('@root')}/");
        require GAME_ROOT . './include/global.func.php';

        $vars = get_defined_vars();
        require GAME_ROOT . './config.inc.php';
        require GAME_ROOT . './gamedata/system.php';
        require GAME_ROOT . './gamedata/gameinfo.php';
        require GAME_ROOT . './gamedata/combatinfo.php';

        $adminmsg = file_get_contents(GAME_ROOT . './gamedata/adminmsg.htm');
        $systemmsg = file_get_contents(GAME_ROOT . './gamedata/systemmsg.htm');
        $cuser = '苹果';
        $error = '';
        require config('resources');
        require config('gamecfg');
        $vars = array_diff(get_defined_vars(), $vars);
        return $vars;
        // return [
        //     // 'charset' => $this->charset,
        //     // 'allowcsscache' => 1,
        //     // 'homepage' => 'http://www.amarilloviridian.com/',
        //     // 'gameversion'=> 'GE942 ～TORONTO',

        // ];
    }
}
