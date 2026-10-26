<?php
if (!defined('IN_GAME')) exit('Access Denied');
require GAME_ROOT.'gamedata/cache/resources_1.php';
$iteminfo['R'] = '配方';
$iteminfo['ER'] = '夹层遗物';
$typeinfo[96] = '夹层住民';
$typeinfo[97] = '入侵者';
$typeinfo[98] = '车厢行商';
$killmsginfo[96] = $killmsginfo[97] = $killmsginfo[98] = '电梯的灯还亮着。';
$lwinfo[96] = $lwinfo[97] = $lwinfo[98] = '下一站……';
$shops = $depots = $hospitals = array();
$plsinfo = array(0=>'帧间候梯厅');
$hplsinfo = array(250=>array(9000=>'共享电梯厢'));
$areainfo = array(0=>'门上的指示灯忽明忽暗。',9000=>'门外的世界不停更换，车厢里的灯仍为每一位乘客亮着。');

