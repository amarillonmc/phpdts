<?php
if (!defined('IN_GAME')) exit('Access Denied');
require GAME_ROOT.'gamedata/cache/gamecfg_1.php';
$hplimit = $splimit = 400;
$validlimit = 8;
$rsgame_bots = 0;
$combolimit = $deathlimit = 0;
$event_obbs = $chase_obbs = $dfight_obbs = 0;
$gamblingon = $npcchaton = 0;
$gshoplist = array();
$coldtimeon = 0;
$corpseprotect = 0;
$elevator_budget_base = 150;
$elevator_budget_min = 100;
$elevator_door_open = 8;
$elevator_door_close = 5;
$elevator_travel_unit = 3;
$elevator_vote_window = 120;
$elevator_limit_finite = 3600;
$elevator_limit_endless = 5400;
$elevator_join_window = 600;

