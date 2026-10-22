<?php
if(!defined('IN_GAME')) exit('Access Denied');
require GAME_ROOT.'./gamedata/cache/vncreation_1.php';
// 本模式使用独立的角色、物品与合成目录，不能投放原生战斗系统的付费投稿。
$vncreation_cfg['enabled'] = false;
