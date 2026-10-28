<?php
if (!defined('IN_GAME')) exit('Access Denied');
require GAME_ROOT.'gamedata/cache/npc_1.php';
// 由 FLASH 的世界刷新例程从现有 NPC 池取样，保留持久角色。
$npcinfo = array();
