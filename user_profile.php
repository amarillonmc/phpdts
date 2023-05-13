<?php

define('CURSCRIPT', 'user_profile');

require './include/common.inc.php';
require './include/user.func.php';
include_once GAME_ROOT.'./include/game/titles.func.php';

$_REQUEST = gstrfilter($_REQUEST);
if (empty($_REQUEST["playerID"]))
{
	if(!$cuser||!$cpass) { gexit($_ERROR['no_login'],__file__,__line__); }

	$result = $db->query("SELECT * FROM {$gtablepre}users WHERE username='$cuser'");
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
	$result = $db->query("SELECT * FROM {$gtablepre}users WHERE username='$uname'");
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
//本人访问账户页面时，初始化每日任务相关参数
if($curuser)
{
	$dailyarr = check_daily_achievement($n);
	if(isset($_REQUEST["action"]) && $_REQUEST["action"]=="refdaily" && !$dailyarr[0])
	{
		$dailyarr = reset_daily_achievement($n);
	}
	if($dailyarr[0])
	{
		list($min,$hour,$day,$month,$year)=explode(',',date("i,H,j,n,Y",$dailyarr[0]));
		$reset_daily_flag = $year."年".$month."月".$day."日".$hour."时".$min."分";
		$reset_daily_flag = "<span class=\"yellow\">下次可获取每日挑战时间：".$reset_daily_flag."</span>";
	}
	$dailyarr = $dailyarr[1];
}
//访问它人账户页面时，只显示获取过的每日任务
else 
{
	$dailyarr = check_daily_achievement($n,1);
}
if(!empty($udata['achrev'])) $udata['achrev'] = json_decode($udata['achrev'],true);
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
				if($cpl[$i] == 999) 
				{
					if($i == 16 || $i == 17 || $i == 18 || $i == 19) 
					{
						//特判：四个结局成就阶段会变更为1...就这样了！
						$cpl[$i] = 1;
					}
					else
					{
						$cpl[$i] = $iarr['lvl'] ?: count($iarr['name']);
					}
				}
				$new_ach[$i]['l'] = $cpl[$i] ?: 0;
				$new_ach[$i]['v'] = $prc[$i] ?: 0;
			}
		}
	}
	$new_ach = json_encode($new_ach);
	$db->query("UPDATE {$gtablepre}users SET achrev='$new_ach' WHERE username='".$udata['username']."'" );
	$cpl = Array(); $prc = Array();
}
//解析成就的完成情况
$alist = get_achlist();
$atype = get_achtype();
$dtype = get_achtype(1);
$h_atype = get_hidden_achtype();
//判断是否存在每日任务
$atype['daily']['ach'] = empty($dailyarr[0]) ? Array() : $dailyarr;
foreach($alist as $aid => $arr)
{
	$cpl[$aid] = isset($udata['achrev'][$aid]['l']) ? $udata['achrev'][$aid]['l'] : 0;
	//这一条是临时为了兼容旧版本数据 之后把旧成就完全整理好后，就可以把这条注释掉了
	if(isset($alist[$aid]['lvl']) && $cpl[$aid] == $alist[$aid]['lvl']) $cpl[$aid] = 999;
	$prc[$aid] = isset($udata['achrev'][$aid]['v']) ? $udata['achrev'][$aid]['v'] : 0;
}
//判断是否存在完成的隐藏成就
foreach($h_atype as $hid => $htype)
{
	if($cpl[$hid] == 999) $atype[$htype]['ach'][] = $hid;
}
include template('user_profile');



