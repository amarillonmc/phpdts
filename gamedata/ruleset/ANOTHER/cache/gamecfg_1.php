<?php
if(!defined('IN_GAME')) exit('Access Denied');

require GAME_ROOT.'./gamedata/cache/gamecfg_1.php';
// 原有升级经验曲线和属性增量保留；本模式由自己的计时钩子推进禁区。
$baseexp=9;
$hplimit=$splimit=400;
$areahour=10;
$areaadd=3;
$areawarntime=120;
$arealimit=999;
$areaesc=0;
$isduel=0;
$antiAFKertime=999;
$validlimit=300;
$combolimit=0;
$deathlimit=0;
$coldtimeon=0;
$event_obbs=0;
$chase_obbs=$dfight_obbs=0;
$rsgame_bots=0;
$gamblingon=0;
$gshoplist=array();
$allow_destory_corpse=0;
$npcchaton=0;
$team_sp=$teamj_sp=0;
