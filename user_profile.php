<?php

define('CURSCRIPT', 'user_profile');

require './include/common.inc.php';
require './include/user.func.php';
include_once GAME_ROOT.'./include/game/titles.func.php';

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
$nickinfo = get_title_desc($nick);
$select_icon = $icon;
$winning_rate=$validgames?round($wingames/$validgames*100)."%":'0%';

include_once GAME_ROOT.'./include/game/achievement.func.php';
$ach=$udata['achievement'];
$n=$udata['username'];
if(!empty($udata['achrev'])) $udata['achrev'] = json_decode($udata['achrev'],true);
/*if (!valid_achievement($ach)) {
	$ach=init_achievement($ach);
	$db->query("UPDATE {$tablepre}users SET achievement='$ach' WHERE username='$n'" );	
}*/ //已废弃

// 访问账户页面时，检查是否需要转化新版成就数据结构
if(!empty($udata['achievement']) && empty($udata['achrev']))
{
	global $achievement_count;
	include_once GAME_ROOT.'./include/game/achievement.func.php';
	$alist = get_achlist();
	$new_ach = Array(); $cpl = Array(); $prc = Array();
	foreach($alist as $i => $iarr)
	{
		if($i <= 57)
		{
			$cpl[$i]=check_achievement($i,$n);
			$prc[$i]=fetch_achievement($i,$n);
			//新成就储存结构内，只会保存有进度的成就
			if(!empty($cpl[$i]) || !empty($prc[$i]))
			{
				// 到达999阶段的成就 替换为配置中预设的达成等级
				if($cpl[$i] == 999) $cpl[$i] = $iarr['lvl'] ?: count($iarr['name']);
				$new_ach[$i]['l'] = $cpl[$i] ?: 0;
				$new_ach[$i]['v'] = $prc[$i] ?: 0;
			}
		}
	}
	$new_ach = json_encode($new_ach);
	$db->query("UPDATE {$tablepre}users SET achrev='$new_ach' WHERE username='".$udata['username']."'" );
	$cpl = Array(); $prc = Array();
}
//解析成就的完成情况//已废弃
/*global $achievement_count;
require config("gamecfg",$gamecfg);
for ($i=0; $i<$achievement_count; $i++)
{
	$cpl[$i]=check_achievement($i,$n);
	$prc[$i]=fetch_achievement($i,$n);
	//$ncp[$i]['s'] = $cpl[$i];
	//$ncp[$i]['v'] = $prc[$i];
}*/
//$ncp = json_encode($ncp);
//$db->query("UPDATE {$tablepre}users SET achrev='$ncp' WHERE username='$n'" );	
//解析成就的完成情况
$alist = get_achlist();
$atype = get_achtype();
foreach($alist as $aid => $arr)
{
	$cpl[$aid] = isset($udata['achrev'][$aid]['l']) ? $udata['achrev'][$aid]['l'] : 0;
	//这一条是临时为了兼容旧版本数据 之后把旧成就完全整理好后，就可以把这条注释掉了
	if($cpl[$aid] == $alist[$aid]['lvl']) $cpl[$aid] = 999;
	$prc[$aid] = isset($udata['achrev'][$aid]['v']) ? $udata['achrev'][$aid]['v'] : 0;
}
include template('user_profile');

