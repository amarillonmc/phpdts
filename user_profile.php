<?php

define('CURSCRIPT', 'user_profile');

require './include/common.inc.php';
require './include/user.func.php';

$_REQUEST = gstrfilter($_REQUEST);
if ($_REQUEST["playerID"]=="")
{
	if(!$cuser||!$cpass) { gexit($_ERROR['no_login'],__file__,__line__); }

	$result = $db->query("SELECT * FROM {$tablepre}users WHERE username='$cuser'");
	if(!$db->num_rows($result)) { gexit($_ERROR['login_check'],__file__,__line__); }
	$udata = $db->fetch_array($result);
	if($udata['password'] != $cpass) { gexit($_ERROR['wrong_pw'], __file__, __line__); }
	if($udata['groupid'] <= 0) { gexit($_ERROR['user_ban'], __file__, __line__); }

	extract($udata);
	$curuser=true;
}
else
{
	$uname=$_REQUEST["playerID"];
	$result = $db->query("SELECT * FROM {$tablepre}users WHERE username='$uname'");
	if(!$db->num_rows($result)) { gexit($_ERROR['user_not_exists'],__file__,__line__); }
	$udata = $db->fetch_array($result);
	extract($udata);
	$curuser=false;
	if ($uname==$cuser) $curuser=true;
}

$iconarray = get_iconlist($icon);
$select_icon = $icon;
$winning_rate=$validgames?round($wingames/$validgames*100)."%":'0%';

include_once GAME_ROOT.'./include/game/achievement.func.php';
$ach=$udata['achievement'];
$n=$udata['username'];
if (!valid_achievement($ach)) {
	$ach=init_achievement($ach);
	$db->query("UPDATE {$tablepre}users SET achievement='$ach' WHERE username='$n'" );	
}
//解析成就的完成情况
global $gamecfg;
require config("gamecfg",$gamecfg);
for ($i=0; $i<$achievement_count; $i++)
{
	$cpl[$i]=check_achievement($i,$n);
	$prc[$i]=fetch_achievement($i,$n);
}
include template('user_profile');

