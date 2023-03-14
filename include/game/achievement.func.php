<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}

# 初始化单个成就页面
function init_achtabledata($ach)
{
	$ach_dir = 'achievement_'.$ach;
	# 本地存在对应的页面模板，返回模板
	if(file_exists(GAME_ROOT."./templates/default/".$ach_dir.".htm"))
	{
		return Array($ach_dir);
	}
	# 本地不存在对应的页面模板，尝试新建一个
	else 
	{
		//暂时使用一个通用模板动态生成
		//未来如果发现存在性能问题，再在这里补完自动创建模板文件的功能
		return $ach_dir;
	}
}

function print_achievement_rev($ach)
{
	if(!empty($ach))
	{
		$ach = json_decode($ach,true);
		return $ach;
	}
	return Array();
}

function check_achievement_rev($which,$who)
{
	global $db,$tablepre;
	$result = $db->query("SELECT achrev FROM {$tablepre}users WHERE username = '$who'");
	$ach = $db->result($result,0);
	$value = 0;
	if(!empty($ach)) 
	{
		$ach = print_achievement_rev($ach);
		$achlist = get_achlist($which);
		// 保存的成就阶段 = 成就完成阶段时 返回999 这是为了兼容旧版成就
		if(isset($ach[$which]['l'])) $value = $ach[$which]['l'] == $achlist['lvl'] ? 999 : $ach[$which]['l'];
	}
	//echo "成就值等级检索阶段： 成就{$which} 的等级 = {$value}<br>";
	return $value;
}

function fetch_achievement_rev($which,$who)
{
	global $db,$tablepre;
	$result = $db->query("SELECT achrev FROM {$tablepre}users WHERE username = '$who'");
	$ach = $db->result($result,0);
	$value = 0;
	if(!empty($ach)) 
	{
		$ach = print_achievement_rev($ach);
		// 保存的成就进度 >= 9999999时 返回9999999 这是为了兼容旧版成就
		if(isset($ach[$which]['v']) && !is_array($ach[$which]['v'])) $value = $ach[$which]['v'] >= 9999999 ? 9999999 : $ach[$which]['v'];
		if(is_array($ach[$which]['v'])) $value = $ach[$which]['v'];
	}
	//echo "成就值检索阶段： 成就{$which} 的值 = {$value}<br>";
	return $value;
}

function update_achievement_rev($which,$who,$value)
{
	global $db,$tablepre;
	$result = $db->query("SELECT achrev FROM {$tablepre}users WHERE username = '$who'");
	$ach = $db->result($result,0);
	$ach = print_achievement_rev($ach);
	$ach[$which]['v'] = $value;
	//echo "成就值变更阶段： 成就{$which} 的值被修正为 {$value}<br>";
	$ach = json_encode($ach);
	$db->query("UPDATE {$tablepre}users SET achrev='$ach' WHERE username='".$who."'" );
}

function done_achievement_rev($which,$ch,$who)
{
	global $db,$tablepre;
	$result = $db->query("SELECT achrev FROM {$tablepre}users WHERE username = '$who'");
	$ach = $db->result($result,0);
	$ach = print_achievement_rev($ach);
	// 要将成就进度改为999时 修正为当前成就对应完成阶段
	if($ch == 999)
	{
		$achlist = get_achlist($which);
		$ch = $achlist['lvl'];
	}
	$ach[$which]['l'] = $ch;
	//echo "成就完成阶段： 成就{$which} 阶段被修正为 {$ch}<br>";
	$ach = json_encode($ach);
	$db->query("UPDATE {$tablepre}users SET achrev='$ach' WHERE username='".$who."'" );
}

function reset_achievement_rev($which,$who)
{
	global $db,$tablepre,$log;
	$result = $db->query("SELECT achrev FROM {$tablepre}users WHERE username = '$who'");
	$ach = $db->result($result,0);
	$ach = print_achievement_rev($ach);
	if(array_key_exists($which,$ach))
	{
		unset($ach[$which]);
		//echo "【DEBUG】已重置{$who} 成就编号：{$which}的进度。<br>";
		$ach = json_encode($ach);
		$db->query("UPDATE {$tablepre}users SET achrev='$ach' WHERE username='".$who."'" );
	}
}

function check_daily_achievement($who,$only_id=0)
{
	global $db,$tablepre,$now,$reset_daily_cd;
	$result = $db->query("SELECT daily FROM {$tablepre}users WHERE username = '$who'");
	$daily = $db->result($result,0);
	# 存在每日记录时检查是否可以刷新每日
	if(!empty($daily))
	{
		$daily = json_decode($daily,true);
		$now_daily = $daily['ach'];
		if($only_id) return $daily['ach'];
		$reset_time = $daily['st'] + $reset_daily_cd;
		# 每日刷新尚在CD中
		if($now < $reset_time) return Array($reset_time,$daily['ach']);
		return Array(0,$daily['ach']);
	}
	return Array(0,0);
}

function reset_daily_achievement($who)
{
	global $db,$tablepre,$now,$reset_daily_cd;
	$result = $db->query("SELECT daily FROM {$tablepre}users WHERE username = '$who'");
	$daily = $db->result($result,0);
	if(!empty($daily))
	{
		$daily = json_decode($daily,true);
		// 清空旧每日数据
		foreach($daily['ach'] as $aid) reset_achievement_rev($aid,$who);
	}
	else 
	{
		$daily = Array();
	}
	//echo "开始为{$who}获取新的每日任务<br>";
	$daily['st'] = $now;
	$ach_type = get_achtype();
	$daily_list = $ach_type['daily']['ach'];
	//至少有3个每日任务才能发每日
	if(!empty($daily_list) && count($daily_list)>=3)
	{
		$d1 = 0; $d2 = 0; $d3 = 0;
		$d1 = $daily_list[array_rand($daily_list)];
		while(!$d2 || $d2 == $d1) $d2 = $daily_list[array_rand($daily_list)];
		while(!$d3 || $d3 == $d2 || $d3 == $d1) $d3 = $daily_list[array_rand($daily_list)];
		$daily['ach'] = Array($d1,$d2,$d3);
	}
	$n_daily = json_encode($daily);
	$db->query("UPDATE {$tablepre}users SET daily='$n_daily' WHERE username='".$who."'" );
	return Array($daily['st']+$reset_daily_cd,$daily['ach']);
}

function check_mixitem_achievement_rev($nn,$item)
{
	global $now,$validtime,$starttime,$gamecfg,$name,$db,$tablepre;
	include_once GAME_ROOT.'./include/game/titles.func.php';
	$done = 0;
	$atotal = Array();
	//1. 快速KEY弹成就
	if ($item=="【KEY系催泪弹】")
	{
		$timeused=$now-$starttime; $besttime=(int)fetch_achievement_rev(1,$nn);
		if ($timeused<$besttime || $besttime==0) update_achievement_rev(1,$nn,$timeused);
		if (!check_achievement_rev(1,$nn) && $timeused<=300) {
		done_achievement_rev(1,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+30 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+16 WHERE username='".$nn."'" );
		get_title("KEY男",$nn);
		}
		//609.日常 合成一次KEY弹
		if(in_array(609,check_daily_achievement($nn,1)))
		{
			$aid = 609;
			$alvl = check_achievement_rev($aid,$nn);
			$achlist = get_achlist($aid);
			$avars = fetch_achievement_rev($aid,$nn)+1;
			update_achievement_rev($aid,$nn,$avars);
			if(!$alvl)
			{
				$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
				$alvl ++;
				done_achievement_rev($aid,$alvl,$nn);
				//日常任务统计成就 600 计数+1
				$atotal[600] += 1;
			}
		}
	}
	//200.快速贤者成就
	if ($item=="火水木金土符『贤者之石』")
	{
		$aid = 200;
		$alvl = check_achievement_rev($aid,$nn);$achlist = get_achlist($aid);
		// 检查最快时长
		$timeused=$now-$starttime; $avars = fetch_achievement_rev($aid,$nn);
		if(empty($avars) || $timeused < $avars) update_achievement_rev($aid,$nn,$timeused);
		// 检查是否满足条件进入下一阶段
		while(!$alvl && $timeused <= 900) 
		{
			if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$nn);
			$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
			$alvl ++;
			done_achievement_rev($aid,$alvl,$nn);
		}
	}
	//201.快速✦烈埋火成就
	if ($item=="✦烈埋火")
	{
		$aid = 201;
		$alvl = check_achievement_rev($aid,$nn);$achlist = get_achlist($aid);
		// 检查最快时长
		$timeused=$now-$starttime; $avars = fetch_achievement_rev($aid,$nn);
		if(empty($avars) || $timeused < $avars) update_achievement_rev($aid,$nn,$timeused);
		// 检查是否满足条件进入下一阶段
		while(!$alvl && $timeused <= 420) 
		{
			if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$nn);
			$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
			$alvl ++;
			done_achievement_rev($aid,$alvl,$nn);
		}
	}

	//0. KEY弹成就
	if ($item=="【KEY系催泪弹】") 
	{
		update_achievement_rev(0,$nn,((int)fetch_achievement_rev(0,$nn))+1);
		if ((int)fetch_achievement_rev(0,$nn)>=30 && (check_achievement_rev(0,$nn)<999)) {
		done_achievement_rev(0,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+700 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("流星",$nn);
		}
		elseif ((int)fetch_achievement_rev(0,$nn)>=5 && (check_achievement_rev(0,$nn)<2)) {
		done_achievement_rev(0,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+200 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("幻想",$nn);
		}
		elseif ((int)fetch_achievement_rev(0,$nn)>=1 && (check_achievement_rev(0,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement_rev(0,1,$nn);
		}
	}
	//14. 燃烧弹成就
	if ($item=="【KEY系燃烧弹】") 
	{
		update_achievement_rev(14,$nn,((int)fetch_achievement_rev(14,$nn))+1);
		if ((int)fetch_achievement_rev(14,$nn)>=30 && (check_achievement_rev(14,$nn)<999)) {
		done_achievement_rev(14,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+700 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("TERRA",$nn);
		}
		elseif ((int)fetch_achievement_rev(14,$nn)>=5 && (check_achievement_rev(14,$nn)<2)) {
		done_achievement_rev(14,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+200 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("树形图",$nn);
		}
		elseif ((int)fetch_achievement_rev(14,$nn)>=1 && (check_achievement_rev(14,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement_rev(14,1,$nn);
		}
	}
	//15. 生命弹成就
	if ($item=="【KEY系生命弹】") 
	{
		update_achievement_rev(15,$nn,((int)fetch_achievement_rev(15,$nn))+1);
		if ((int)fetch_achievement_rev(15,$nn)>=30 && (check_achievement_rev(15,$nn)<999)) {
		done_achievement_rev(15,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+700 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("未来战士",$nn);
		}
		elseif ((int)fetch_achievement_rev(15,$nn)>=5 && (check_achievement_rev(15,$nn)<2)) {
		done_achievement_rev(15,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+200 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("素描本",$nn);
		}
		elseif ((int)fetch_achievement_rev(15,$nn)>=1 && (check_achievement_rev(15,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement_rev(15,1,$nn);
		}
	}
	//33. 诅咒刀成就
	if ($item=="影刀【秋岚】") 
	{
		update_achievement_rev(33,$nn,((int)fetch_achievement_rev(33,$nn))+1);
		if ((int)fetch_achievement_rev(33,$nn)>=1 && (check_achievement_rev(33,$nn)<999)) {
		done_achievement_rev(33,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+522 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("剑圣",$nn);
		}
	}
	//35. 『T-LINK念动冲拳』成就
	if ($item=="『T-LINK念动冲拳』") 
	{
		update_achievement_rev(35,$nn,((int)fetch_achievement_rev(35,$nn))+1);
		if ((int)fetch_achievement_rev(35,$nn)>=111 && (check_achievement_rev(35,$nn)<999)) {
		done_achievement_rev(35,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("殴系爱好者",$nn);
		}
		elseif ((int)fetch_achievement_rev(35,$nn)>=51 && (check_achievement_rev(35,$nn)<2)) {
		done_achievement_rev(35,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("热血机师",$nn);
		}
		elseif ((int)fetch_achievement_rev(35,$nn)>=1 && (check_achievement_rev(35,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement_rev(35,1,$nn);
		}
	}
	//36. Azurewrath成就
	if ($item=="Azurewrath") 
	{
		update_achievement_rev(36,$nn,((int)fetch_achievement_rev(36,$nn))+1);
		if ((int)fetch_achievement_rev(36,$nn)>=111 && (check_achievement_rev(36,$nn)<999)) {
		done_achievement_rev(36,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("斩系爱好者",$nn);
		}
		elseif ((int)fetch_achievement_rev(36,$nn)>=51 && (check_achievement_rev(36,$nn)<2)) {
		done_achievement_rev(36,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("苍蓝之光",$nn);
		}
		elseif ((int)fetch_achievement_rev(36,$nn)>=1 && (check_achievement_rev(36,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement_rev(36,1,$nn);
		}
	}
	//37. 『Two Become One』成就
	if ($item=="『Two Become One』") 
	{
		update_achievement_rev(37,$nn,((int)fetch_achievement_rev(37,$nn))+1);
		if ((int)fetch_achievement_rev(37,$nn)>=111 && (check_achievement_rev(37,$nn)<999)) {
		done_achievement_rev(37,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("钥刃大师",$nn);
		}
		elseif ((int)fetch_achievement_rev(37,$nn)>=51 && (check_achievement_rev(37,$nn)<2)) {
		done_achievement_rev(37,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("合二为一",$nn);
		}
		elseif ((int)fetch_achievement_rev(37,$nn)>=1 && (check_achievement_rev(37,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement_rev(37,1,$nn);
		}
	}
	//38. 『迷你鲨』成就
	if ($item=="『迷你鲨』") 
	{
		update_achievement_rev(38,$nn,((int)fetch_achievement_rev(38,$nn))+1);
		if ((int)fetch_achievement_rev(38,$nn)>=111 && (check_achievement_rev(38,$nn)<999)) {
		done_achievement_rev(38,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("射系爱好者",$nn);
		}
		elseif ((int)fetch_achievement_rev(38,$nn)>=51 && (check_achievement_rev(38,$nn)<2)) {
		done_achievement_rev(37,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("勇闯仙境",$nn);
		}
		elseif ((int)fetch_achievement_rev(38,$nn)>=1 && (check_achievement_rev(38,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement_rev(38,1,$nn);
		}
	}
	//39. ☆金色闪光☆成就
	if ($item=="☆金色闪光☆") 
	{
		update_achievement_rev(39,$nn,((int)fetch_achievement_rev(39,$nn))+1);
		if ((int)fetch_achievement_rev(39,$nn)>=111 && (check_achievement_rev(39,$nn)<999)) {
		done_achievement_rev(39,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("重枪爱好者",$nn);
		}
		elseif ((int)fetch_achievement_rev(39,$nn)>=51 && (check_achievement_rev(39,$nn)<2)) {
		done_achievement_rev(39,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("黑洞边缘",$nn);
		}
		elseif ((int)fetch_achievement_rev(39,$nn)>=1 && (check_achievement_rev(39,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement_rev(39,1,$nn);
		}
	}
	//40. 星尘龙 ★8成就
	if ($item=="星尘龙 ★8") 
	{
		update_achievement_rev(40,$nn,((int)fetch_achievement_rev(40,$nn))+1);
		if ((int)fetch_achievement_rev(40,$nn)>=111 && (check_achievement_rev(40,$nn)<999)) {
		done_achievement_rev(40,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("决斗者",$nn);
		}
		elseif ((int)fetch_achievement_rev(40,$nn)>=51 && (check_achievement_rev(40,$nn)<2)) {
		done_achievement_rev(40,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("光的道路",$nn);
		}
		elseif ((int)fetch_achievement_rev(40,$nn)>=1 && (check_achievement_rev(40,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement_rev(40,1,$nn);
		}
	}
	//41. 流星龙 ★10成就
	if ($item=="流星龙 ★10") 
	{
		update_achievement_rev(41,$nn,((int)fetch_achievement_rev(41,$nn))+1);
		if ((int)fetch_achievement_rev(41,$nn)>=111 && (check_achievement_rev(41,$nn)<999)) {
		done_achievement_rev(41,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("聚集的祈愿",$nn);
		}
		elseif ((int)fetch_achievement_rev(41,$nn)>=51 && (check_achievement_rev(41,$nn)<2)) {
		done_achievement_rev(41,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("加速同调",$nn);
		}
		elseif ((int)fetch_achievement_rev(41,$nn)>=1 && (check_achievement_rev(41,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement_rev(41,1,$nn);
		}
	}
	//42. 《小黄的超级球》成就
	if ($item=="《小黄的超级球》") 
	{
		update_achievement_rev(42,$nn,((int)fetch_achievement_rev(42,$nn))+1);
		if ((int)fetch_achievement_rev(42,$nn)>=111 && (check_achievement_rev(42,$nn)<999)) {
		done_achievement_rev(42,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("投系爱好者",$nn);
		}
		elseif ((int)fetch_achievement_rev(42,$nn)>=51 && (check_achievement_rev(42,$nn)<2)) {
		done_achievement_rev(42,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("平和之心",$nn);
		}
		elseif ((int)fetch_achievement_rev(42,$nn)>=1 && (check_achievement_rev(42,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement_rev(42,1,$nn);
		}
	}
	//43. 莫洛托夫鸡尾酒成就
	if ($item=="莫洛托夫鸡尾酒") 
	{
		update_achievement_rev(43,$nn,((int)fetch_achievement_rev(43,$nn))+1);
		if ((int)fetch_achievement_rev(43,$nn)>=111 && (check_achievement_rev(43,$nn)<999)) {
		done_achievement_rev(43,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("爆系爱好者",$nn);
		}
		elseif ((int)fetch_achievement_rev(43,$nn)>=51 && (check_achievement_rev(43,$nn)<2)) {
		done_achievement_rev(43,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("红烧天堂",$nn);
		}
		elseif ((int)fetch_achievement_rev(43,$nn)>=1 && (check_achievement_rev(43,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement_rev(43,1,$nn);
		}
	}
	//44. ★BIUBIUBIU★成就
	if ($item=="★BIUBIUBIU★") 
	{
		update_achievement_rev(44,$nn,((int)fetch_achievement_rev(44,$nn))+1);
		if ((int)fetch_achievement_rev(44,$nn)>=111 && (check_achievement_rev(44,$nn)<999)) {
		done_achievement_rev(44,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("★啪啪啪★",$nn);
		}
		elseif ((int)fetch_achievement_rev(44,$nn)>=51 && (check_achievement_rev(44,$nn)<2)) {
		done_achievement_rev(44,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("★刷刷刷★",$nn);
		}
		elseif ((int)fetch_achievement_rev(44,$nn)>=1 && (check_achievement_rev(44,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement_rev(44,1,$nn);
		}
	}
	//45. 日符「Royal Flare」成就
	if ($item=="日符「Royal Flare」") 
	{
		update_achievement_rev(45,$nn,((int)fetch_achievement_rev(45,$nn))+1);
		if ((int)fetch_achievement_rev(45,$nn)>=111 && (check_achievement_rev(45,$nn)<999)) {
		done_achievement_rev(45,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("灵系爱好者",$nn);
		}
		elseif ((int)fetch_achievement_rev(45,$nn)>=51 && (check_achievement_rev(45,$nn)<2)) {
		done_achievement_rev(45,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("皇家烈焰",$nn);
		}
		elseif ((int)fetch_achievement_rev(45,$nn)>=1 && (check_achievement_rev(45,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement_rev(45,1,$nn);
		}
	}
	//46. 火水木金土符『贤者之石』成就
	if ($item=="火水木金土符『贤者之石』") 
	{
		update_achievement_rev(46,$nn,((int)fetch_achievement_rev(46,$nn))+1);
		if ((int)fetch_achievement_rev(46,$nn)>=111 && (check_achievement_rev(46,$nn)<999)) {
		done_achievement_rev(46,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("贤者之石",$nn);
		}
		elseif ((int)fetch_achievement_rev(46,$nn)>=51 && (check_achievement_rev(46,$nn)<2)) {
		done_achievement_rev(46,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("五行大师",$nn);
		}
		elseif ((int)fetch_achievement_rev(46,$nn)>=1 && (check_achievement_rev(46,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement_rev(46,1,$nn);
		}
	}
	//47. 广域生命探测器成就
	if ($item=="广域生命探测器") 
	{
		update_achievement_rev(47,$nn,((int)fetch_achievement_rev(47,$nn))+1);
		if ((int)fetch_achievement_rev(47,$nn)>=111 && (check_achievement_rev(47,$nn)<999)) {
		done_achievement_rev(47,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("知人和",$nn);
		}
		elseif ((int)fetch_achievement_rev(47,$nn)>=51 && (check_achievement_rev(47,$nn)<2)) {
		done_achievement_rev(47,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("知地利",$nn);
		}
		elseif ((int)fetch_achievement_rev(47,$nn)>=1 && (check_achievement_rev(47,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement_rev(47,1,$nn);
		}
	}
	//48. 法式面包棍棒成就
	if ($item=="法式面包棍棒") 
	{
		update_achievement_rev(48,$nn,((int)fetch_achievement_rev(48,$nn))+1);
		if ((int)fetch_achievement_rev(48,$nn)>=111 && (check_achievement_rev(48,$nn)<999)) {
		done_achievement_rev(48,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("混沌的深渊",$nn);
		}
		elseif ((int)fetch_achievement_rev(48,$nn)>=51 && (check_achievement_rev(48,$nn)<2)) {
		done_achievement_rev(48,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("混沌爱好者",$nn);
		}
		elseif ((int)fetch_achievement_rev(48,$nn)>=1 && (check_achievement_rev(48,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement_rev(48,1,$nn);
		}
	}
	//49. 【春雨夏海，秋叶冬雪】挑战成就
	if ($item=="【春雨夏海，秋叶冬雪】") 
	{
		update_achievement_rev(49,$nn,((int)fetch_achievement_rev(49,$nn))+1);
		if ((int)fetch_achievement_rev(49,$nn)>=7 && (check_achievement_rev(49,$nn)<999)) {
		done_achievement_rev(49,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+700 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("键·四季赞歌",$nn);
		}
		elseif ((int)fetch_achievement_rev(49,$nn)>=1 && (check_achievement_rev(49,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+100 WHERE username='".$nn."'" );
		done_achievement_rev(49,1,$nn);
		}
	}
	//50. ★一发逆转神话★挑战成就
	if ($item=="★一发逆转神话★") 
	{
		update_achievement_rev(50,$nn,((int)fetch_achievement_rev(50,$nn))+1);
		if ((int)fetch_achievement_rev(50,$nn)>=7 && (check_achievement_rev(50,$nn)<999)) {
		done_achievement_rev(50,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+700 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("★一发逆转！★",$nn);
		}
		elseif ((int)fetch_achievement_rev(50,$nn)>=1 && (check_achievement_rev(50,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+100 WHERE username='".$nn."'" );
		done_achievement_rev(50,1,$nn);
		}
	}
	//51. 模式『EX』挑战成就
	if ($item=="模式『EX』") 
	{
		update_achievement_rev(51,$nn,((int)fetch_achievement_rev(51,$nn))+1);
		if ((int)fetch_achievement_rev(51,$nn)>=7 && (check_achievement_rev(51,$nn)<999)) {
		done_achievement_rev(51,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+700 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("『ＥＸ』",$nn);
		}
		elseif ((int)fetch_achievement_rev(51,$nn)>=1 && (check_achievement_rev(51,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+100 WHERE username='".$nn."'" );
		done_achievement_rev(51,1,$nn);
		}
	}
	//52. ◎光之创造神◎挑战成就
	if ($item=="◎光之创造神◎") 
	{
		update_achievement_rev(52,$nn,((int)fetch_achievement_rev(52,$nn))+1);
		if ((int)fetch_achievement_rev(52,$nn)>=7 && (check_achievement_rev(52,$nn)<999)) {
		done_achievement_rev(52,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+700 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("◎胜利之光◎",$nn);
		}
		elseif ((int)fetch_achievement_rev(52,$nn)>=1 && (check_achievement_rev(52,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+100 WHERE username='".$nn."'" );
		done_achievement_rev(52,1,$nn);
		}
	}
	//新版成就切糕、积分结算汇总到此
	if(!empty($c1)) $db->query("UPDATE {$tablepre}users SET credits=credits+$c1 WHERE username='".$nn."'" );
	if(!empty($c2)) $db->query("UPDATE {$tablepre}users SET credits2=credits2+$c2 WHERE username='".$nn."'" );
	if(!empty($atotal))
	{
		foreach($atotal as $aid => $anums) 
			check_nums_achievement($nn,$aid,$anums);
	}
	return;
}

//新版结局成就检测机制：加入团队胜利兼容
//function check_end_achievement_rev($w,$m)
function check_end_achievement_rev($w,$m,$data='')
{
	global $now,$validtime,$starttime,$gamecfg,$name,$db,$tablepre;
	include_once GAME_ROOT.'./include/game/titles.func.php';

	$done = 0;
	$atotal = Array();
	$data['clbpara'] = get_clbpara($data['clbpara']);

	//16. 最后幸存成就
	if ($m==2)
	{
		// 初始化
		$aid = 16;
		$alvl = check_achievement_rev($aid,$w);
		$achlist = get_achlist($aid);
		// 增加一次完成次数
		$avars = fetch_achievement_rev($aid,$w)+1;
		update_achievement_rev($aid,$w,$avars);
		// 检查是否满足条件进入下一阶段（如果累计的次数足够一次性完成多个阶段，会依次完成）
		while((!$alvl && $avars) || ($alvl == 1 && $avars >= 17) || ($alvl == 2 && $avars >= 177)) 
		{
			$done = 1;
			// alvl代表的是当前阶段 所以先获取当前阶段的奖励 之后提升alvl
			if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$w);
			$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
			// 阶段步进
			$alvl ++;
		}
		// 阶段有所变化时，增加阶段次数
		if($done) done_achievement_rev($aid,$alvl,$w);
		$done = 0;
	}
	//17. 核爆全灭成就
	if ($m==5)
	{
		// 初始化
		$aid = 17;
		$alvl = check_achievement_rev($aid,$w);
		$achlist = get_achlist($aid);
		// 增加一次完成次数
		$avars = fetch_achievement_rev($aid,$w)+1;
		update_achievement_rev($aid,$w,$avars);
		// 检查是否满足条件进入下一阶段
		while((!$alvl && $avars) || ($alvl == 1 && $avars >= 7)) 
		{
			$done = 1;
			if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$w);
			$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
			$alvl ++;
		}
		if($done) done_achievement_rev($aid,$alvl,$w);
		$done = 0;
	}
	//18. 锁定解除成就
	if ($m==3)
	{
		// 初始化
		$aid = 18;
		$alvl = check_achievement_rev($aid,$w);
		$achlist = get_achlist($aid);
		// 增加一次完成次数
		$avars = fetch_achievement_rev($aid,$w)+1;
		update_achievement_rev($aid,$w,$avars);
		// 检查是否满足条件进入下一阶段
		while((!$alvl && $avars) || ($alvl == 1 && $avars >= 17) || ($alvl == 2 && $avars >= 77)) 
		{
			$done = 1;
			if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$w);
			$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
			$alvl ++;
		}
		if($done) done_achievement_rev($aid,$alvl,$w);
		$done = 0;
	}
	//202. 25分钟内解禁挑战（仅个人可完成）
	if ($m==3 && !empty($data))
	{
		$aid = 202;
		$alvl = check_achievement_rev($aid,$w);
		$achlist = get_achlist($aid);
		// 检查最快时长
		$timeused=$now-$starttime; 
		$avars = fetch_achievement_rev($aid,$w);
		if(empty($avars) || $timeused < $avars) update_achievement_rev($aid,$w,$timeused);
		// 检查是否满足条件进入下一阶段
		while(!$alvl && $timeused <= 1500) 
		{
			$done = 1;
			if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$w);
			$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
			$alvl ++;
		}
		if($done) done_achievement_rev($aid,$alvl,$w);
		$done = 0;
	}
	//206. 不使用合成/元素合成达成锁定解除/幻境解离结局
	if (!empty($data) && empty($data['clbpara']['achvars']['immix']) && empty($data['clbpara']['achvars']['team']) && ($m==3 || $m==7))
	{
		$aid = 206;
		$alvl = check_achievement_rev($aid,$w);
		$achlist = get_achlist($aid);
		// 增加一次完成次数
		$avars = fetch_achievement_rev($aid,$w)+1;
		update_achievement_rev($aid,$w,$avars);
		// 检查是否满足条件进入下一阶段
		while(!$alvl)
		{
			$done = 1;
			if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$w);
			$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
			$alvl ++;
		}
		if($done) done_achievement_rev($aid,$alvl,$w);
		$done = 0;
	}
	//207. 不击杀小兵/种火达成锁定解除结局
	if (!empty($data) && empty($data['clbpara']['achvars']['kill_minion']) && ($m==3 || $m==7))
	{
		$aid = 207;
		$alvl = check_achievement_rev($aid,$w);
		$achlist = get_achlist($aid);
		// 增加一次完成次数
		$avars = fetch_achievement_rev($aid,$w)+1;
		update_achievement_rev($aid,$w,$avars);
		// 检查是否满足条件进入下一阶段
		while(!$alvl)
		{
			$done = 1;
			if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$w);
			$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
			$alvl ++;
		}
		if($done) done_achievement_rev($aid,$alvl,$w);
		$done = 0;
	}
	//19. 幻境解离成就
	if ($m==7)
	{
		// 初始化
		$aid = 19;
		$alvl = check_achievement_rev($aid,$w);
		$achlist = get_achlist($aid);
		// 增加一次完成次数
		$avars = fetch_achievement_rev($aid,$w)+1;
		update_achievement_rev($aid,$w,$avars);
		// 检查是否满足条件进入下一阶段
		while((!$alvl && $avars) || ($alvl == 1 && $avars >= 17) || ($alvl == 2 && $avars >= 77)) 
		{
			$done = 1;
			if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$w);
			$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
			$alvl ++;
		}
		if($done) done_achievement_rev($aid,$alvl,$w);
		$done = 0;
	}
	//203. 55分钟内解离挑战（仅个人可完成）
	if ($m==7 && !empty($data))
	{
		$aid = 203;
		$alvl = check_achievement_rev($aid,$w);
		$achlist = get_achlist($aid);
		// 检查最快时长
		$timeused=$now-$starttime; 
		$avars = fetch_achievement_rev($aid,$w);
		if(empty($avars) || $timeused < $avars) update_achievement_rev($aid,$w,$timeused);
		// 检查是否满足条件进入下一阶段
		while(!$alvl && $timeused <= 3300) 
		{
			$done = 1;
			if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$w);
			$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
			$alvl ++;
		}
		if($done) done_achievement_rev($aid,$alvl,$w);
		$done = 0;
	}
	// 603.日常 达成一次解禁/解离结局
	if(in_array(603,check_daily_achievement($w,1)) && !empty($data) && ($m==3 || $m==7))
	{
		$aid = 603;
		$alvl = check_achievement_rev($aid,$w);
		$achlist = get_achlist($aid);
		$avars = fetch_achievement_rev($aid,$w)+1;
		update_achievement_rev($aid,$w,$avars);
		if(!$alvl)
		{
			$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
			$alvl ++;
			done_achievement_rev($aid,$alvl,$w);
			//日常任务统计成就 600 计数+1
			$atotal[600] += 1;
		}
	}
	//新版成就切糕、积分结算汇总到此
	if(!empty($c1)) $db->query("UPDATE {$tablepre}users SET credits=credits+$c1 WHERE username='".$w."'" );
	if(!empty($c2)) $db->query("UPDATE {$tablepre}users SET credits2=credits2+$c2 WHERE username='".$w."'" );
	if(!empty($atotal))
	{
		foreach($atotal as $aid => $anums) 
			check_nums_achievement($nn,$aid,$anums);
	}
	return;
}

//新版击杀成就检测：pa击杀pd
//function check_battle_achievement_rev($n,$is_npc,$killname,$wp)
function check_battle_achievement_rev($pa,$pd)
{
	global $gamestate,$gamecfg,$db,$tablepre;
	include_once GAME_ROOT.'./include/game/titles.func.php';

	// 旧版成就参数兼容
	$is_npc = $pd['type'];
	$nn = $pa['name'];
	$killname = $pd['name'];
	$wp = isset($pa['wep_name']) ? $pa['wep_name'] : $pa['wep'];
	// 判断是否为活跃玩家：暂时只要IP不一样就算活跃玩家
	$is_tplayer = $pa['ip'] == $pd['ip'] ? 0 : 1;
	// 获取pa当前的每日任务列表
	$daily = check_daily_achievement($nn,1);
	//是否需要将完成过的成就统计到另一个成就里，如果有，将成就编号和完成次数汇总到下面这个数组里，在函数尾部一同处理
	$atotal = Array();

	# 击杀玩家成就
	if (!$is_npc && $pd['name'] != $nn)
	{
		$done = 0;
		// 2.无条件击杀玩家成就
		$aid = 2;
		$alvl = check_achievement_rev($aid,$nn);
		$achlist = get_achlist($aid);
		// 增加一次完成次数
		$avars = fetch_achievement_rev($aid,$nn)+1;
		update_achievement_rev($aid,$nn,$avars);
		// 检查是否满足条件进入下一阶段
		while((!$alvl && $avars >= 10) || ($alvl == 1 && $avars >= 100) || ($alvl == 2 && $avars >= 1000)) 
		{
			$done = 1;
			if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$nn);
			$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
			$alvl ++;
		}
		if($done) done_achievement_rev($aid,$alvl,$nn);
		$done = 0;

		// 602.日常 击杀一名活跃玩家
		if(in_array(602,$daily) && $is_tplayer)
		{
			$aid = 602;
			$alvl = check_achievement_rev($aid,$nn);
			$achlist = get_achlist($aid);
			$avars = fetch_achievement_rev($aid,$nn)+1;
			update_achievement_rev($aid,$nn,$avars);
			if(!$alvl)
			{
				$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
				$alvl ++;
				done_achievement_rev($aid,$alvl,$nn);
				//日常任务统计成就 600 计数+1
				$atotal[600] += 1;
			}
		}

		// 60.击杀存在击杀数的其他玩家
		if(!empty($pd['killnum']))
		{
			$aid = 60;
			$alvl = check_achievement_rev($aid,$nn);
			$achlist = get_achlist($aid);
			// 增加一次完成次数
			$avars = fetch_achievement_rev($aid,$nn)+1;
			update_achievement_rev($aid,$nn,$avars);
			// 检查是否满足条件进入下一阶段
			while((!$alvl && $avars >= 1) || ($alvl == 1 && $avars >= 10) || ($alvl == 2 && $avars >= 100)) 
			{
				$done = 1;
				if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$nn);
				$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
				$alvl ++;
			}
			if($done) done_achievement_rev($aid,$alvl,$nn);
			$done = 0;
		}

		// 61.在死斗模式下击杀玩家
		if($gamestate == 50)
		{
			$aid = 61;
			$alvl = check_achievement_rev($aid,$nn);
			$achlist = get_achlist($aid);
			// 增加一次完成次数
			$avars = fetch_achievement_rev($aid,$nn)+1;
			update_achievement_rev($aid,$nn,$avars);
			// 检查是否满足条件进入下一阶段
			while((!$alvl && $avars >= 1) || ($alvl == 1 && $avars >= 10) || ($alvl == 2 && $avars >= 100)) 
			{
				$done = 1;
				if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$nn);
				$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
				$alvl ++;
			}
			if($done) done_achievement_rev($aid,$alvl,$nn);
			$done = 0;
		}

		// 62.使用毒补给毒死玩家
		if($pd['state'] == 26)
		{
			$aid = 62;
			$alvl = check_achievement_rev($aid,$nn);
			$achlist = get_achlist($aid);
			// 增加一次完成次数
			$avars = fetch_achievement_rev($aid,$nn)+1;
			update_achievement_rev($aid,$nn,$avars);
			// 检查是否满足条件进入下一阶段
			while((!$alvl && $avars >= 1) || ($alvl == 1 && $avars >= 10) || ($alvl == 2 && $avars >= 100)) 
			{
				$done = 1;
				if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$nn);
				$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
				$alvl ++;
			}
			if($done) done_achievement_rev($aid,$alvl,$nn);
			$done = 0;
		}

		// 63.埋设陷阱炸死玩家
		if($pd['state'] == 27)
		{
			$aid = 63;
			$alvl = check_achievement_rev($aid,$nn);
			$achlist = get_achlist($aid);
			// 增加一次完成次数
			$avars = fetch_achievement_rev($aid,$nn)+1;
			update_achievement_rev($aid,$nn,$avars);
			// 检查是否满足条件进入下一阶段
			while((!$alvl && $avars >= 1) || ($alvl == 1 && $avars >= 10) || ($alvl == 2 && $avars >= 100)) 
			{
				$done = 1;
				if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$nn);
				$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
				$alvl ++;
			}
			if($done) done_achievement_rev($aid,$alvl,$nn);
			$done = 0;
		}

		// 606.用陷阱或毒药击杀一名活跃玩家
		if(in_array(606,$daily) && $is_tplayer && ($pd['state'] == 26 || $pd['state'] == 27))
		{
			$aid = 606;
			$alvl = check_achievement_rev($aid,$nn);
			$achlist = get_achlist($aid);
			$avars = fetch_achievement_rev($aid,$nn)+1;
			update_achievement_rev($aid,$nn,$avars);
			if(!$alvl)
			{
				$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
				$alvl ++;
				done_achievement_rev($aid,$alvl,$nn);
				//日常任务统计成就 600 计数+1
				$atotal[600] += 1;
			}
		}

		// 64.使用DN杀死玩家
		if($pd['state'] == 28)
		{
			$aid = 64;
			$alvl = check_achievement_rev($aid,$nn);
			$achlist = get_achlist($aid);
			// 增加一次完成次数
			$avars = fetch_achievement_rev($aid,$nn)+1;
			update_achievement_rev($aid,$nn,$avars);
			// 检查是否满足条件进入下一阶段
			while(!$alvl && $avars >= 1) 
			{
				$done = 1;
				if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$nn);
				$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
				$alvl ++;
			}
			if($done) done_achievement_rev($aid,$alvl,$nn);
			$done = 0;
		}

		// 65.击杀使用过移动PC的玩家
		if(!empty($pd['clbpara']['achvars']['hack']))
		{
			$aid = 65;
			$alvl = check_achievement_rev($aid,$nn);
			$achlist = get_achlist($aid);
			// 增加一次完成次数
			$avars = fetch_achievement_rev($aid,$nn)+1;
			update_achievement_rev($aid,$nn,$avars);
			// 检查是否满足条件进入下一阶段
			while((!$alvl && $avars >= 1) || ($alvl == 1 && $avars >= 10) || ($alvl == 2 && $avars >= 100)) 
			{
				$done = 1;
				if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$nn);
				$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
				$alvl ++;
			}
			if($done) done_achievement_rev($aid,$alvl,$nn);
			$done = 0;
		}

		// 66.击杀改变过天气的玩家
		if(!empty($pd['clbpara']['achvars']['wthchange']))
		{
			$aid = 66;
			$alvl = check_achievement_rev($aid,$nn);
			$achlist = get_achlist($aid);
			// 增加一次完成次数
			$avars = fetch_achievement_rev($aid,$nn)+1;
			update_achievement_rev($aid,$nn,$avars);
			// 检查是否满足条件进入下一阶段
			while((!$alvl && $avars >= 1) || ($alvl == 1 && $avars >= 10) || ($alvl == 2 && $avars >= 100)) 
			{
				$done = 1;
				if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$nn);
				$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
				$alvl ++;
			}
			if($done) done_achievement_rev($aid,$alvl,$nn);
			$done = 0;
		}

		// 67.击杀使用过破灭之诗的活跃玩家
		if(!empty($pd['clbpara']['achvars']['thiphase']) && $is_tplayer)
		{
			$aid = 67;
			$alvl = check_achievement_rev($aid,$nn);
			$achlist = get_achlist($aid);
			// 阶段0：只提升阶段，不改变次数
			if(!$alvl && $avars >= 1)
			{
				$done = 1;
				if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$nn);
				//特判：阶段0时清空进度
				$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
				$alvl ++;
			}
			// 阶段1：入场更晚时击杀1名；阶段2：入场更晚时击杀13名；
			else
			{
				// 入场时间更晚时 增加次数
				if($pa['validtime'] >= $pd['validtime'])
				{
					$avars = fetch_achievement_rev($aid,$nn)+1;
					update_achievement_rev($aid,$nn,$avars);
				}
				while(($alvl == 1 && $avars >= 1) || ($alvl == 2 && $avars >= 13))
				{
					$done = 1;
					if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$nn);
					$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
					$alvl ++;
				}
			}
			if($done) done_achievement_rev($aid,$alvl,$nn);
			$done = 0;
		}

		// 68.击杀女主后 击杀其他摸过女主尸体的活跃玩家
		if(!empty($pa['clbpara']['achvars']['kill_n14']) && !empty($pd['clbpara']['achvars']['corpse_n14']) && $is_tplayer)
		{
			$aid = 68;
			$alvl = check_achievement_rev($aid,$nn);
			$achlist = get_achlist($aid);
			// 增加一次完成次数
			$avars = fetch_achievement_rev($aid,$nn)+1;
			update_achievement_rev($aid,$nn,$avars);
			// 检查是否满足条件进入下一阶段
			while(!$alvl && $avars >= 1) 
			{
				$done = 1;
				if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$nn);
				$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
				$alvl ++;
			}
			if($done) done_achievement_rev($aid,$alvl,$nn);
			$done = 0;
		}

		// 69.打海豹
		if((!empty($pd['clbpara']['achvars']['gacha_sr']) || !empty($pd['clbpara']['achvars']['gacha_ssr'])) && $is_tplayer)
		{
			$aid = 69;
			$alvl = check_achievement_rev($aid,$nn);
			$achlist = get_achlist($aid);
			// 打海豹阶段0、1：只提升阶段，不改变次数
			if(!$alvl || ($alvl == 1 && !empty($pd['clbpara']['achvars']['gacha_ssr'])))
			{
				$done = 1;
				if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$nn);
				$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
				$alvl ++;
			}
			// 打海豹阶段2
			elseif(!empty($pd['clbpara']['achvars']['gacha_ssr']) && $pa['validtime'] >= $pd['validtime'])
			{
				$avars = fetch_achievement_rev($aid,$nn)+1;
				update_achievement_rev($aid,$nn,$avars);
				$done = 1;
				if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$nn);
				$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
				$alvl ++;
			}
			if($done) done_achievement_rev($aid,$alvl,$nn);
			$done = 0;
		}
	}
	//31. ReturnToSender成就
	if (!$is_npc)
	{
		$result = $db->query("SELECT nicks FROM {$tablepre}users WHERE username = '$killname'");
		$ns = $db->result($result, 0);
		if ((strpos($ns,"KEY男")!==false)&&($wp=='【KEY系催泪弹】')){
			update_achievement_rev(31,$nn,((int)fetch_achievement_rev(31,$nn))+1);
			if ((int)fetch_achievement_rev(31,$nn)>=1 && (check_achievement_rev(31,$nn)<999)) {
				done_achievement_rev(31,999,$nn);
				include_once GAME_ROOT.'./include/game/titles.func.php';
				get_title("R.T.S",$nn);
				get_title("善有善报",$killname);
				}
		}
	}
	//32. 呵呵
	if (!$is_npc)
	{
		$result = $db->query("SELECT nicks FROM {$tablepre}users WHERE username = '$killname'");
		$ns1 = $db->result($result, 0);
		$result = $db->query("SELECT nicks FROM {$tablepre}users WHERE username = '$nn'");
		$ns2 = $db->result($result, 0);
		if ((strpos($ns2,"LOOP")!==false)||(strpos($ns1,"LOOP")!==false)){
			if (check_achievement_rev(32,$nn)<999) done_achievement_rev(32,999,$nn);
			if (check_achievement_rev(32,$killname)<999) done_achievement_rev(32,999,$killname);
			include_once GAME_ROOT.'./include/game/titles.func.php';
			get_title("LOOP",$nn);
			get_title("LOOP",$killname);
		}
	}
	//3. 击杀NPC成就 
	if ($is_npc)
	{
		update_achievement_rev(3,$nn,((int)fetch_achievement_rev(3,$nn))+1);
		if ((int)fetch_achievement_rev(3,$nn)>=10000 && (check_achievement_rev(3,$nn)<999)) {
		done_achievement_rev(3,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+500 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+15 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("最后一步",$nn);
		}
		elseif ((int)fetch_achievement_rev(3,$nn)>=500 && (check_achievement_rev(3,$nn)<2)) {
		done_achievement_rev(3,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+200 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("黑客",$nn);
		}
		elseif ((int)fetch_achievement_rev(3,$nn)>=100 && (check_achievement_rev(3,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+5 WHERE username='".$nn."'" );
		done_achievement_rev(3,1,$nn);
		}
	}
	// 601.日常 击杀10名NPC
	if(in_array(601,$daily) && $is_npc)
	{
		$aid = 601;
		$alvl = check_achievement_rev($aid,$nn);
		$achlist = get_achlist($aid);
		$avars = fetch_achievement_rev($aid,$nn)+1;
		update_achievement_rev($aid,$nn,$avars);
		if(!$alvl && $avars>=10)
		{
			$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
			$alvl ++;
			done_achievement_rev($aid,$alvl,$nn);
			//日常任务统计成就 600 计数+1
			$atotal[600] += 1;
		}
	}
	//4. 推倒红暮成就
	if ($is_npc && ($killname=="红暮" || $killname=="红杀将军 红暮")) 
	{
		update_achievement_rev(4,$nn,((int)fetch_achievement_rev(4,$nn))+1);
		if ((int)fetch_achievement_rev(4,$nn)>=9 && (check_achievement_rev(4,$nn)<999)) {
		done_achievement_rev(4,999,$nn);
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("越红者",$nn);
		}
		elseif ((int)fetch_achievement_rev(4,$nn)>=1 && (check_achievement_rev(4,$nn)<1)) {
		done_achievement_rev(4,1,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+50 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+75 WHERE username='".$nn."'" );
		}
	}
	//13. 推倒蓝凝成就
	if ($is_npc && ($killname=="蓝凝" || $killname=="红杀菁英 蓝凝")) 
	{
		update_achievement_rev(13,$nn,((int)fetch_achievement_rev(13,$nn))+1);
		if ((int)fetch_achievement_rev(13,$nn)>=3 && (check_achievement_rev(13,$nn)<999)) {
		done_achievement_rev(13,999,$nn);
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("跨过彩虹",$nn);
		}
		elseif ((int)fetch_achievement_rev(13,$nn)>=1 && (check_achievement_rev(13,$nn)<1)) {
		done_achievement_rev(13,1,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+50 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+75 WHERE username='".$nn."'" );
		}
	}
	//20. 击破虚子成就
	if ($is_npc && ($killname=="虚子" || $killname=="武神 虚子")) 
	{
		update_achievement_rev(20,$nn,((int)fetch_achievement_rev(20,$nn))+1);
		if ((int)fetch_achievement_rev(20,$nn)>=1 && (check_achievement_rev(20,$nn)<999)) {
		done_achievement_rev(20,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+268 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+263 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("寻星者",$nn);
		}
	}
	//21. 击破水月成就
	if ($is_npc && ($killname=="水月" || $killname=="武神 水月")) 
	{
		update_achievement_rev(21,$nn,((int)fetch_achievement_rev(21,$nn))+1);
		if ((int)fetch_achievement_rev(21,$nn)>=1 && (check_achievement_rev(21,$nn)<999)) {
		done_achievement_rev(21,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+233 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+233 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("寂静洪流",$nn);
		}
	}
	//22. 击破冴冴成就
	if ($is_npc && ($killname=="冴月麟MK-II" || $killname=="天神 冴月麟MK-II")) 
	{
		update_achievement_rev(22,$nn,((int)fetch_achievement_rev(22,$nn))+1);
		if ((int)fetch_achievement_rev(22,$nn)>=1 && (check_achievement_rev(22,$nn)<999)) {
		done_achievement_rev(22,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+2333 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("l33t",$nn);
		}
	}
	//23. 击破四面成就
	if ($is_npc && ($killname=="星莲船四面BOSS" || $killname=="天神 星莲船四面BOSS")) 
	{
		update_achievement_rev(23,$nn,((int)fetch_achievement_rev(23,$nn))+1);
		if ((int)fetch_achievement_rev(23,$nn)>=1 && (check_achievement_rev(23,$nn)<999)) {
		done_achievement_rev(23,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+888 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("赌玉狂魔",$nn);
		}
	}
	//24. 击破北京成就
	if ($is_npc && ($killname=="北京推倒你" || $killname=="武神 北京推倒你")) 
	{
		update_achievement_rev(24,$nn,((int)fetch_achievement_rev(24,$nn))+1);
		if ((int)fetch_achievement_rev(24,$nn)>=1 && (check_achievement_rev(24,$nn)<999)) {
		done_achievement_rev(24,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+211 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+299 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("时代眼泪",$nn);
		}
	}
	//25. 击破yoshiko成就
	if ($is_npc && ($killname=="Yoshiko-G" || $killname=="武神 Yoshiko-G")) 
	{
		update_achievement_rev(25,$nn,((int)fetch_achievement_rev(25,$nn))+1);
		if ((int)fetch_achievement_rev(25,$nn)>=1 && (check_achievement_rev(25,$nn)<999)) {
		done_achievement_rev(25,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+111 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+333 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("卸腿者",$nn);
		}
	}
	//26. 击破吉祥物成就
	if ($is_npc && ($killname=="便当盒" || $killname=="真职人 便当盒")) 
	{
		update_achievement_rev(26,$nn,((int)fetch_achievement_rev(26,$nn))+1);
		if ((int)fetch_achievement_rev(26,$nn)>=1 && (check_achievement_rev(26,$nn)<999)) {
		done_achievement_rev(26,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+1 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+111 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("吉祥物",$nn);
		}
	}
	//27. 英灵殿成就 
	if (($is_npc>=20)&&($is_npc<=22))
	{
		update_achievement_rev(27,$nn,((int)fetch_achievement_rev(27,$nn))+1);
		if ((int)fetch_achievement_rev(27,$nn)>=100 && (check_achievement_rev(27,$nn)<999)) {
		done_achievement_rev(27,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+500 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("替天行道",$nn);
		}
		elseif ((int)fetch_achievement_rev(27,$nn)>=30 && (check_achievement_rev(27,$nn)<2)) {
		done_achievement_rev(27,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+300 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		}
		elseif ((int)fetch_achievement_rev(27,$nn)>=1 && (check_achievement_rev(27,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement_rev(27,1,$nn);
		}
	}
	//56. 击杀种火成就 
	if ($is_npc==92)
	{
		update_achievement_rev(56,$nn,((int)fetch_achievement_rev(56,$nn))+1);
		if ((int)fetch_achievement_rev(56,$nn)>=366 && (check_achievement_rev(56,$nn)<999)) {
		done_achievement_rev(56,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+250 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("除错大师",$nn);
		}
		elseif ((int)fetch_achievement_rev(56,$nn)>=180 && (check_achievement_rev(56,$nn)<2)) {
		done_achievement_rev(56,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("都市传说",$nn);
		}
		elseif ((int)fetch_achievement_rev(56,$nn)>=1 && (check_achievement_rev(56,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement_rev(56,1,$nn);
		}
	}
	// 605.日常击杀10名种火
	if(in_array(605,$daily) && $is_npc == 89)
	{
		$aid = 605;
		$alvl = check_achievement_rev($aid,$nn);
		$achlist = get_achlist($aid);
		$avars = fetch_achievement_rev($aid,$nn)+1;
		update_achievement_rev($aid,$nn,$avars);
		if(!$alvl && $avars>=10)
		{
			$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
			$alvl ++;
			done_achievement_rev($aid,$alvl,$nn);
			//日常任务统计成就 600 计数+1
			$atotal[600] += 1;
		}
	}
	//57. 击杀回声成就 
	if ($is_npc==89)
	{
		update_achievement_rev(57,$nn,((int)fetch_achievement_rev(57,$nn))+1);
		if ((int)fetch_achievement_rev(57,$nn)>=103 && (check_achievement_rev(57,$nn)<999)) {
		done_achievement_rev(57,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+250 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("暴雷骤雨",$nn);
		}
		elseif ((int)fetch_achievement_rev(57,$nn)>=52 && (check_achievement_rev(57,$nn)<2)) {
		done_achievement_rev(57,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("风驰电掣",$nn);
		}
		elseif ((int)fetch_achievement_rev(57,$nn)>=1 && (check_achievement_rev(57,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement_rev(57,1,$nn);
		}
	}
	
	//切糕、积分结算汇总
	if(!empty($c1)) $db->query("UPDATE {$tablepre}users SET credits=credits+$c1 WHERE username='".$nn."'" );
	if(!empty($c2)) $db->query("UPDATE {$tablepre}users SET credits2=credits2+$c2 WHERE username='".$nn."'" );
	//成就计数结算
	if(!empty($atotal))
	{
		foreach($atotal as $aid => $anums) 
			check_nums_achievement($nn,$aid,$anums);
	}
}


function check_item_achievement_rev($nn,$i,$ie,$is,$ik,$isk)
{
	global $gamecfg,$name,$db,$tablepre,$now,$starttime,$gamestate;
	include_once GAME_ROOT.'./include/game/titles.func.php';
	$atotal = Array();

	//解禁相关
	if ($i == "游戏解除钥匙")
	{
		// 初始化
		$done = 0;
		// 101.使用参战者红暮&蓝凝掉落的钥匙达成锁定解除结局
		if($isk == 'Z') $aid = 101;
		// 102.使用DF掉落的钥匙达成锁定解除结局
		elseif($isk == 'x') $aid = 102;
		// 100.使用执行官掉落的钥匙达成锁定解除结局
		else $aid = 100;

		$alvl = check_achievement_rev($aid,$nn);
		$achlist = get_achlist($aid);
		// 增加一次完成次数
		$avars = fetch_achievement_rev($aid,$nn)+1;
		update_achievement_rev($aid,$nn,$avars);
		// 检查是否满足条件进入下一阶段（如果累计的次数足够一次性完成多个阶段，会依次完成）
		while(!$alvl && $avars) 
		{
			$done = 1;
			// alvl代表的是当前阶段 所以先获取当前阶段的奖励 之后提升alvl
			if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$nn);
			$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
			// 阶段步进
			$alvl ++;
		}
		// 阶段有所变化时，增加阶段次数
		if($done) done_achievement_rev($aid,$alvl,$nn);
	}

	if($i == "凸眼鱼")
	{
		// 607.日常 使用一次凸眼鱼吸收20具尸体
		if(in_array(607,check_daily_achievement($nn,1)) && $isk>=20)
		{
			$aid = 607;
			$alvl = check_achievement_rev($aid,$nn);
			$achlist = get_achlist($aid);
			$avars = fetch_achievement_rev($aid,$nn)+1;
			update_achievement_rev($aid,$nn,$avars);
			if(!$alvl)
			{
				$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
				$alvl ++;
				done_achievement_rev($aid,$alvl,$nn);
				//日常任务统计成就 600 计数+1
				$atotal[600] += 1;
			}
		}
	}


	//28. 死斗成就
	if (($gamestate==50)&&($i=="杏仁豆腐的ID卡")) 
	{
		$t=$now-$starttime;
		$besttime=(int)fetch_achievement_rev(28,$nn);
		if ($t<$besttime || $besttime==0) update_achievement_rev(28,$nn,$t);
		if (!check_achievement_rev(28,$nn) && $t<=1800) {
		done_achievement_rev(28,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+250 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("神触",$nn);
		}
		//604. 日常开启一次死斗
		if(in_array(604,check_daily_achievement($nn,1)))
		{
			$aid = 604;
			$alvl = check_achievement_rev($aid,$nn);
			$achlist = get_achlist($aid);
			$avars = fetch_achievement_rev($aid,$nn)+1;
			update_achievement_rev($aid,$nn,$avars);
			if(!$alvl)
			{
				$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
				$alvl ++;
				done_achievement_rev($aid,$alvl,$nn);
				//日常任务统计成就 600 计数+1
				$atotal[600] += 1;
			}
		}
	}
	//29. 美食成就
	if (($ik=='HS')||($ik=='HH')||($ik=='HB'))
	{
		$heal=$ie;
		if ($ik=='HB') $heal+=$ie;
		$uu=((int)fetch_achievement_rev(29,$nn))+$heal;
		if ($uu>9999999) $uu=9999999;
		update_achievement_rev(29,$nn,$uu);
		if (((int)fetch_achievement_rev(29,$nn)>=999983) && (check_achievement_rev(29,$nn))<999) {
		done_achievement_rev(29,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+200 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("补给掠夺者",$nn);
		}
		elseif ((int)fetch_achievement_rev(29,$nn)>=142857 && (check_achievement_rev(29,$nn)<2)) {
		done_achievement_rev(29,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+50 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("美食家",$nn);
		}
		elseif ((int)fetch_achievement_rev(29,$nn)>=32767 && (check_achievement_rev(29,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+5 WHERE username='".$nn."'" );
		done_achievement_rev(29,1,$nn);
		}
	}
	//30. 贝爷成就
	$kk=substr($ik,0,1);
	if (($kk=='P')&&($ie>=30))
	{
		update_achievement_rev(30,$nn,((int)fetch_achievement_rev(30,$nn))+1);
		if (((int)fetch_achievement_rev(30,$nn)>=365) && (check_achievement_rev(30,$nn))<999) {
		done_achievement_rev(30,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+200 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("贝爷",$nn);
		}
		elseif ((int)fetch_achievement_rev(30,$nn)>=133 && (check_achievement_rev(30,$nn)<2)) {
		done_achievement_rev(30,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+50 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("神农",$nn);
		}
		elseif ((int)fetch_achievement_rev(30,$nn)>=5 && (check_achievement_rev(30,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+5 WHERE username='".$nn."'" );
		done_achievement_rev(30,1,$nn);
		}
	}
	//34. 独自逃脱成就
	if ($i=="【E.S.C.A.P.E】"){
		update_achievement_rev(34,$nn,((int)fetch_achievement_rev(34,$nn))+1);
		// +20 切糕与1胜场
		$db->query("UPDATE {$tablepre}users SET credits=credits+20 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET wingames=wingames+1 WHERE username='".$nn."'" );
		if (((int)fetch_achievement_rev(34,$nn)>=101) && (check_achievement_rev(34,$nn))<999) {
			done_achievement_rev(34,999,$nn);
			$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
			$db->query("UPDATE {$tablepre}users SET credits2=credits2+200 WHERE username='".$nn."'" );
			include_once GAME_ROOT.'./include/game/titles.func.php';
			get_title("脚底抹油",$nn);
			}
			elseif ((int)fetch_achievement_rev(34,$nn)>=36 && (check_achievement_rev(34,$nn)<2)) {
			done_achievement_rev(34,2,$nn);
			$db->query("UPDATE {$tablepre}users SET credits=credits+50 WHERE username='".$nn."'" );
			$db->query("UPDATE {$tablepre}users SET credits2=credits2+50 WHERE username='".$nn."'" );
			include_once GAME_ROOT.'./include/game/titles.func.php';
			get_title("现实主义者",$nn);
			}
			elseif ((int)fetch_achievement_rev(34,$nn)>=1 && (check_achievement_rev(34,$nn)<1)) {
			done_achievement_rev(34,1,$nn);
			$db->query("UPDATE {$tablepre}users SET credits=credits+10 WHERE username='".$nn."'" );
			$db->query("UPDATE {$tablepre}users SET credits2=credits2+5 WHERE username='".$nn."'" );
			include_once GAME_ROOT.'./include/game/titles.func.php';
			get_title("实用主义者",$nn);
			}
	}
	//53. 打钉子成就
	if (preg_match ( "/钉$/", $i ) || preg_match ( "/钉\[/", $i )){
		$enhance=$ie;
		$uu=((int)fetch_achievement_rev(53,$nn))+$enhance;
		if ($uu>9999999) $uu=9999999;
		update_achievement_rev(53,$nn,$uu);
		if (((int)fetch_achievement_rev(53,$nn)>=17777) && (check_achievement_rev(53,$nn))<999) {
			done_achievement_rev(53,999,$nn);
			$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
			$db->query("UPDATE {$tablepre}users SET credits2=credits2+200 WHERE username='".$nn."'" );
			include_once GAME_ROOT.'./include/game/titles.func.php';
			get_title("无情打钉者",$nn);
			}
			elseif ((int)fetch_achievement_rev(53,$nn)>=1777 && (check_achievement_rev(53,$nn)<2)) {
			done_achievement_rev(53,2,$nn);
			$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
			$db->query("UPDATE {$tablepre}users SET credits2=credits2+50 WHERE username='".$nn."'" );
			include_once GAME_ROOT.'./include/game/titles.func.php';
			get_title("棍棒爱好者",$nn);
			}
			elseif ((int)fetch_achievement_rev(53,$nn)>=777 && (check_achievement_rev(53,$nn)<1)) {
			$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
			$db->query("UPDATE {$tablepre}users SET credits2=credits2+5 WHERE username='".$nn."'" );
			done_achievement_rev(53,1,$nn);
			}
	}
	//54. 磨刀石成就
	if (strpos ( $i, '磨刀石' ) !== false){
		$enhance=$ie;
		$uu=((int)fetch_achievement_rev(54,$nn))+$enhance;
		if ($uu>9999999) $uu=9999999;
		update_achievement_rev(54,$nn,$uu);
		if (((int)fetch_achievement_rev(54,$nn)>=17777) && (check_achievement_rev(54,$nn))<999) {
			done_achievement_rev(54,999,$nn);
			$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
			$db->query("UPDATE {$tablepre}users SET credits2=credits2+200 WHERE username='".$nn."'" );
			include_once GAME_ROOT.'./include/game/titles.func.php';
			get_title("无情磨刀者",$nn);
			}
			elseif ((int)fetch_achievement_rev(54,$nn)>=1777 && (check_achievement_rev(54,$nn)<2)) {
			done_achievement_rev(54,2,$nn);
			$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
			$db->query("UPDATE {$tablepre}users SET credits2=credits2+50 WHERE username='".$nn."'" );
			include_once GAME_ROOT.'./include/game/titles.func.php';
			get_title("磨刀爱好者",$nn);
			}
			elseif ((int)fetch_achievement_rev(54,$nn)>=777 && (check_achievement_rev(54,$nn)<1)) {
			$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
			$db->query("UPDATE {$tablepre}users SET credits2=credits2+5 WHERE username='".$nn."'" );
			done_achievement_rev(54,1,$nn);
			}
	}
	//55. 针线包成就
	if ($i == "针线包"){
		$enhance=$ie;
		$uu=((int)fetch_achievement_rev(55,$nn))+$enhance;
		if ($uu>9999999) $uu=9999999;
		update_achievement_rev(55,$nn,$uu);
		if (((int)fetch_achievement_rev(55,$nn)>=17777) && (check_achievement_rev(55,$nn))<999) {
		done_achievement_rev(55,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+200 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("无情补丁",$nn);
		}
		elseif ((int)fetch_achievement_rev(55,$nn)>=1777 && (check_achievement_rev(55,$nn)<2)) {
		done_achievement_rev(55,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+50 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("补丁爱好者",$nn);
		}
		elseif ((int)fetch_achievement_rev(55,$nn)>=777 && (check_achievement_rev(55,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+5 WHERE username='".$nn."'" );
		done_achievement_rev(55,1,$nn);
		}
	}

	//新版成就切糕、积分结算汇总到此
	if(!empty($c1)) $db->query("UPDATE {$tablepre}users SET credits=credits+$c1 WHERE username='".$nn."'" );
	if(!empty($c2)) $db->query("UPDATE {$tablepre}users SET credits2=credits2+$c2 WHERE username='".$nn."'" );
	//成就计数结算
	if(!empty($atotal))
	{
		foreach($atotal as $aid => $anums) 
			check_nums_achievement($nn,$aid,$anums);
	}
	return;
}

//杂项成就，在将数据保存回数据库时统一检查
function check_misc_achievement_rev(&$pa)
{
	global $gamestate,$gamecfg,$db,$tablepre;
	include_once GAME_ROOT.'./include/game/titles.func.php';

	$done = 0;
	$atotal = Array();
	// 旧版成就参数兼容
	$is_player = $pa['type'] ? 0 : 1;
	$nn = $pa['name'];
	// 判断是否为活跃玩家：暂时只要IP不一样就算活跃玩家
	$is_tplayer = $pa['ip'] == $pd['ip'] ? 0 : 1;

	# 防呆：只会检查玩家成就完成情况
	if ($is_player)
	{
		// 204.混沌伤害打满成就
		if(!empty($pa['clbpara']['achvars']['full_chaosdmg']))
		{
			unset($pa['clbpara']['achvars']['full_chaosdmg']);
			$aid = 204;
			$alvl = check_achievement_rev($aid,$nn);
			$achlist = get_achlist($aid);
			// 增加一次完成次数
			$avars = fetch_achievement_rev($aid,$nn)+1;
			update_achievement_rev($aid,$nn,$avars);
			// 检查是否满足条件进入下一阶段
			while(!$alvl && $avars)
			{
				if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$nn);
				$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
				$alvl ++;
				done_achievement_rev($aid,$alvl,$nn);
			}
		}
		// 205.一击承受百万伤害成就
		if(!empty($pa['clbpara']['achvars']['takedmg']) && $pa['clbpara']['achvars']['takedmg'] >= 1000000)
		{
			unset($pa['clbpara']['achvars']['takedmg']);
			$aid = 205;
			$alvl = check_achievement_rev($aid,$nn);
			$achlist = get_achlist($aid);
			// 检查历史最高伤害
			$nowvars = $pa['clbpara']['achvars']['takedmg']; $avars = fetch_achievement_rev($aid,$nn);
			if($nowvars > $avars) update_achievement_rev($aid,$nn,$nowvars);
			// 检查是否完成成就
			while(!$alvl)
			{
				if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$nn);
				$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
				$alvl ++;
				done_achievement_rev($aid,$alvl,$nn);
			}
		}
		// 208.套装收集成就
		if(!empty($pa['clbpara']['setitems']))
		{
			$aid = 208;
			$alvl = check_achievement_rev($aid,$nn);
			$achlist = get_achlist($aid);
			$avars = fetch_achievement_rev($aid,$nn);
			if($alvl < 3)
			{
				// 成就未完成的情况下，检查是否有新集齐的套装需要加入成就记录
				if(!is_array($avars)) $avars = Array();
				$set_items_info = get_set_items_info();
				foreach($pa['clbpara']['setitems'] as $sid => $snums)
				{
					// 检查凑齐完整效果的套装
					if($snums == $set_items_info[$sid]['active'][1] && !in_array($sid,$avars))
					{
						//echo "成就 {$aid} 变动：将套装 {$sid}保存入成就进度。";
						$avars[] = $sid;
					}
				}
				update_achievement_rev($aid,$nn,$avars);
				$anums = count($avars);
				while((!$alvl && $anums) || ($alvl == 1 && $anums >= 3) || ($alvl == 2 && $anums >=5))
				{
					if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$nn);
					$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
					$alvl ++;
					done_achievement_rev($aid,$alvl,$nn);
				}
			}
		}
		// 501.吃下【像围棋子一样的饼干】【桔黄色的果酱】并且活下来
		if(!empty($pa['clbpara']['achvars']['eat_weiqi']) && !empty($pa['clbpara']['achvars']['eat_jelly']))
		{
			unset($pa['clbpara']['achvars']['eat_weiqi']);
			unset($pa['clbpara']['achvars']['eat_jelly']);
			$aid = 501;
			$alvl = check_achievement_rev($aid,$nn);
			$achlist = get_achlist($aid);
			// 增加一次完成次数
			$avars = fetch_achievement_rev($aid,$nn)+1;
			update_achievement_rev($aid,$nn,$avars);
			// 检查是否满足条件进入下一阶段
			while(!$alvl && $avars)
			{
				if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$nn);
				$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
				$alvl ++;
				done_achievement_rev($aid,$alvl,$nn);
			}
		}
		// 502.使用【翼人的羽毛】打出7230点以上伤害
		if(!empty($pa['clbpara']['achvars']['ach502']))
		{
			$aid = 502;
			$alvl = check_achievement_rev($aid,$nn);
			$achlist = get_achlist($aid);
			// 检查伤害
			$new_vars = $pa['clbpara']['achvars']['ach502'];
			$old_vars = fetch_achievement_rev($aid,$nn);
			if($new_vars > $old_vars) 
			{
				update_achievement_rev($aid,$nn,$new_vars);
				// 检查是否满足条件进入下一阶段
				while(!$alvl && $new_vars>=7230)
				{
					if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$nn);
					$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
					$alvl ++;
					done_achievement_rev($aid,$alvl,$nn);
				}
			}
		}
		// 503.穿着【智代专用熊装】连续攻击同一个玩家/NPC64次以上
		if(!empty($pa['clbpara']['achvars']['ach503']))
		{
			$aid = 503;
			$alvl = check_achievement_rev($aid,$nn);
			$achlist = get_achlist($aid);
			// 检查攻击次数
			$new_vars = $pa['clbpara']['achvars']['ach503']['t'];
			$old_vars = fetch_achievement_rev($aid,$nn);
			if($new_vars > $old_vars) 
			{
				update_achievement_rev($aid,$nn,$new_vars);
				// 检查是否满足条件进入下一阶段
				while(!$alvl && $new_vars>=64)
				{
					if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$nn);
					$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
					$alvl ++;
					done_achievement_rev($aid,$alvl,$nn);
				}
			}
		}
		// 504.在【RF高校】使用每一种系的武器各杀死一个目标
		if(!empty($pa['clbpara']['achvars']['ach504']))
		{
			$aid = 504;
			$alvl = check_achievement_rev($aid,$nn);
			$achlist = get_achlist($aid);
			// 检查击杀过的武器种类
			$new_vars = count($pa['clbpara']['achvars']['ach504']);
			$old_vars = fetch_achievement_rev($aid,$nn);
			if($new_vars > $old_vars) 
			{
				update_achievement_rev($aid,$nn,$new_vars);
				// 检查是否满足条件进入下一阶段
				while(!$alvl && $new_vars>=6)
				{
					if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$nn);
					$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
					$alvl ++;
					done_achievement_rev($aid,$alvl,$nn);
				}
			}
		}
		// 505.一击秒杀【守卫者 静流】
		if(!empty($pa['clbpara']['achvars']['ach505']))
		{
			unset($pa['clbpara']['achvars']['ach505']);
			$aid = 505;
			$alvl = check_achievement_rev($aid,$nn);
			$achlist = get_achlist($aid);
			// 增加一次完成次数
			$avars = fetch_achievement_rev($aid,$nn)+1;
			update_achievement_rev($aid,$nn,$avars);
			// 检查是否满足条件进入下一阶段
			while(!$alvl && $avars)
			{
				if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$nn);
				$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
				$alvl ++;
				done_achievement_rev($aid,$alvl,$nn);
			}
		}
		// 608.日常 成功使用一次移动PC 只会记录一次
		if(in_array(608,check_daily_achievement($nn,1)))
		{
			if(!empty($pa['clbpara']['achvars']['hack']))
			{
				$aid = 608;
				$alvl = check_achievement_rev($aid,$nn);
				$achlist = get_achlist($aid);
				$avars = fetch_achievement_rev($aid,$nn);
				if(empty($avars)) update_achievement_rev($aid,$nn,1);
				if(!$alvl)
				{
					$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
					$alvl ++;
					done_achievement_rev($aid,$alvl,$nn);
					//日常任务统计成就 600 计数+1
					$atotal[600] += 1;
				}
			}
		}
		// 610.日常 唱过一首歌
		if(in_array(610,check_daily_achievement($nn,1)))
		{
			if(!empty($pa['clbpara']['achvars']['sing']))
			{
				$aid = 610;
				$alvl = check_achievement_rev($aid,$nn);
				$achlist = get_achlist($aid);
				$avars = fetch_achievement_rev($aid,$nn);
				if(empty($avars)) update_achievement_rev($aid,$nn,1);
				if(!$alvl)
				{
					$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
					$alvl ++;
					done_achievement_rev($aid,$alvl,$nn);
					//日常任务统计成就 600 计数+1
					$atotal[600] += 1;
				}
			}
		}
	}
	//新版成就切糕、积分结算汇总到此
	if(!empty($c1)) $db->query("UPDATE {$tablepre}users SET credits=credits+$c1 WHERE username='".$nn."'" );
	if(!empty($c2)) $db->query("UPDATE {$tablepre}users SET credits2=credits2+$c2 WHERE username='".$nn."'" );
	//成就计数结算
	if(!empty($atotal))
	{
		foreach($atotal as $aid => $anums) 
			check_nums_achievement($nn,$aid,$anums);
	}
	return;
}

//为其他成就计数的成就
function check_nums_achievement($nn,$aid,$anums=1)
{
	global $gamestate,$gamecfg,$db,$tablepre;
	include_once GAME_ROOT.'./include/game/titles.func.php';

	//每日任务计数成就
	if($aid == 600)
	{
		$alvl = check_achievement_rev($aid,$nn);
		$achlist = get_achlist($aid);
		$avars = fetch_achievement_rev($aid,$nn)+$anums;
		update_achievement_rev($aid,$nn,$avars);
		// 检查是否完成成就
		while((!$alvl && $avars) || ($alvl == 1 && $avars >= 10) || ($alvl == 2 && $avars >= 100) || ($alvl == 3 && $avars >= 1001))
		{
			$done = 1;
			if(!empty($achlist['title'][$alvl])) get_title($achlist['title'][$alvl],$nn);
			$c1 += $achlist['c1'][$alvl]; $c2 += $achlist['c2'][$alvl];
			$alvl ++;
		}
		if($done) done_achievement_rev($aid,$alvl,$nn);
		$done = 0;
	}

	//新版成就切糕、积分结算汇总到此
	if(!empty($c1)) $db->query("UPDATE {$tablepre}users SET credits=credits+$c1 WHERE username='".$nn."'" );
	if(!empty($c2)) $db->query("UPDATE {$tablepre}users SET credits2=credits2+$c2 WHERE username='".$nn."'" );
	return;
}

/**** 以下为旧版成就相关函数 ****/

function valid_achievement($s)
{
	global $gamecfg;
	require config("gamecfg",$gamecfg);
	global $achievement_count;
	$k=-1;
	//if (strlen($s)<=($achievement_count*4)) return false;
	for ($i=1; $i<=$achievement_count*2; $i++)
	{
		if (strpos($s,"/",$k+1)===false) return false;
		$k=strpos($s,"/",$k+1);
	}
	return true;
}

function init_achievement($t)
{
	global $gamecfg;
	require config("gamecfg",$gamecfg);
	global $achievement_count;
	$k=-1; $cnt=0;
	$f=0;
	while (1) 
	{
		if (strpos($t,"/",$k+1)===false) {$f=1;break;}
		$k=strpos($t,"/",$k+1); $cnt++;
	}
	$s=$t;
	if (($f==1)&&($cnt==0)) {$s='';}
	for ($i=1; $i<=$achievement_count*2-$cnt; $i++) $s.="0/";
	return $s;
}

function check_achievement($which, $who)
{
	global $gamecfg,$name,$db,$tablepre;
	$result = $db->query("SELECT achievement FROM {$tablepre}users WHERE username = '$who'");
	$ach = $db->result($result, 0);
	$bg=-1;
	for ($i=1; $i<=$which*2; $i++) $bg=strpos($ach,"/",$bg+1);
	$bg++; $ed=strpos($ach,"/",$bg);
	$db->query("UPDATE {$tablepre}users SET achievement='$ach' WHERE username='".$who."'" );
	$a=(int)substr($ach,$bg,$ed-$bg);
	return $a;
}

function fetch_achievement($which,$who)
{
	global $gamecfg,$name,$db,$tablepre;
	$result = $db->query("SELECT achievement FROM {$tablepre}users WHERE username = '$who'");
	$ach = $db->result($result, 0);
	$bg=-1;
	for ($i=1; $i<=$which*2+1; $i++) $bg=strpos($ach,"/",$bg+1);
	$bg++; $ed=strpos($ach,"/",$bg);
	$db->query("UPDATE {$tablepre}users SET achievement='$ach' WHERE username='".$who."'" );
	$a=(int)substr($ach,$bg,$ed-$bg);
	return $a;
}

function done_achievement($which,$ch,$who)
{
	global $gamecfg,$name,$db,$tablepre;
	$result = $db->query("SELECT achievement FROM {$tablepre}users WHERE username = '$who'");
	$ach = $db->result($result, 0);
	$bg=-1;
	for ($i=1; $i<=$which*2; $i++) $bg=strpos($ach,"/",$bg+1);
	$bg++; $ed=strpos($ach,"/",$bg);
	$ach=substr($ach,0,$bg).$ch.substr($ach,$ed);
	$db->query("UPDATE {$tablepre}users SET achievement='$ach' WHERE username='".$who."'" );
}

function update_achievement($which,$who,$value)
{
	global $cpl,$prc,$name,$db,$tablepre;
	$result = $db->query("SELECT achievement FROM {$tablepre}users WHERE username = '$who'");
	$ach = $db->result($result, 0);
	$bg=-1;
	for ($i=1; $i<=$which*2+1; $i++) $bg=strpos($ach,"/",$bg+1);
	$bg++; $ed=strpos($ach,"/",$bg);
	$ach=substr($ach,0,$bg).$value.substr($ach,$ed);
	$bg=-1;
	for ($i=1; $i<=$which*2+1; $i++) $bg=strpos($ach,"/",$bg+1);
	$bg++; $ed=strpos($ach,"/",$bg);
	$prc[$which]=substr($ach,$bg,$ed-$bg);
	$db->query("UPDATE {$tablepre}users SET achievement='$ach' WHERE username='".$who."'" );
}

function normalize_achievement($ach, &$crd1, &$crd2)
{
	global $gamecfg;
	require config("gamecfg",$gamecfg);
	require config("resources",$gamecfg);
	$crd1=0; $crd2=0;
	
}

function check_mixitem_achievement($nn,$item)
{
	global $now,$validtime,$starttime,$gamecfg,$name,$db,$tablepre;
	//0. KEY弹成就
	if ($item=="【KEY系催泪弹】") 
	{
		update_achievement(0,$nn,((int)fetch_achievement(0,$nn))+1);
		if ((int)fetch_achievement(0,$nn)>=30 && (check_achievement(0,$nn)<999)) {
		done_achievement(0,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+700 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("流星",$nn);
		}
		elseif ((int)fetch_achievement(0,$nn)>=5 && (check_achievement(0,$nn)<2)) {
		done_achievement(0,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+200 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("幻想",$nn);
		}
		elseif ((int)fetch_achievement(0,$nn)>=1 && (check_achievement(0,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement(0,1,$nn);
		}
	}
	//1. 快速KEY弹成就
	if ($item=="【KEY系催泪弹】")
	{
		$timeused=$now-$starttime; $besttime=(int)fetch_achievement(1,$nn);
		if ($timeused<$besttime || $besttime==0) update_achievement(1,$nn,$timeused);
		if (!check_achievement(1,$nn) && $timeused<=300) {
		done_achievement(1,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+30 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+16 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("KEY男",$nn);
		}
	}
	//14. 燃烧弹成就
	if ($item=="【KEY系燃烧弹】") 
	{
		update_achievement(14,$nn,((int)fetch_achievement(14,$nn))+1);
		if ((int)fetch_achievement(14,$nn)>=30 && (check_achievement(14,$nn)<999)) {
		done_achievement(14,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+700 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("TERRA",$nn);
		}
		elseif ((int)fetch_achievement(14,$nn)>=5 && (check_achievement(14,$nn)<2)) {
		done_achievement(14,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+200 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("树形图",$nn);
		}
		elseif ((int)fetch_achievement(14,$nn)>=1 && (check_achievement(14,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement(14,1,$nn);
		}
	}
	//15. 生命弹成就
	if ($item=="【KEY系生命弹】") 
	{
		update_achievement(15,$nn,((int)fetch_achievement(15,$nn))+1);
		if ((int)fetch_achievement(15,$nn)>=30 && (check_achievement(15,$nn)<999)) {
		done_achievement(15,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+700 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("未来战士",$nn);
		}
		elseif ((int)fetch_achievement(15,$nn)>=5 && (check_achievement(15,$nn)<2)) {
		done_achievement(15,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+200 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("素描本",$nn);
		}
		elseif ((int)fetch_achievement(15,$nn)>=1 && (check_achievement(15,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement(15,1,$nn);
		}
	}
	//33. 诅咒刀成就
	if ($item=="影刀【秋岚】") 
	{
		update_achievement(33,$nn,((int)fetch_achievement(33,$nn))+1);
		if ((int)fetch_achievement(33,$nn)>=1 && (check_achievement(33,$nn)<999)) {
		done_achievement(33,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+522 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("剑圣",$nn);
		}
	}
	//35. 『T-LINK念动冲拳』成就
	if ($item=="『T-LINK念动冲拳』") 
	{
		update_achievement(35,$nn,((int)fetch_achievement(35,$nn))+1);
		if ((int)fetch_achievement(35,$nn)>=111 && (check_achievement(35,$nn)<999)) {
		done_achievement(35,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("殴系爱好者",$nn);
		}
		elseif ((int)fetch_achievement(35,$nn)>=51 && (check_achievement(35,$nn)<2)) {
		done_achievement(35,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("热血机师",$nn);
		}
		elseif ((int)fetch_achievement(35,$nn)>=1 && (check_achievement(35,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement(35,1,$nn);
		}
	}
	//36. Azurewrath成就
	if ($item=="Azurewrath") 
	{
		update_achievement(36,$nn,((int)fetch_achievement(36,$nn))+1);
		if ((int)fetch_achievement(36,$nn)>=111 && (check_achievement(36,$nn)<999)) {
		done_achievement(36,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("斩系爱好者",$nn);
		}
		elseif ((int)fetch_achievement(36,$nn)>=51 && (check_achievement(36,$nn)<2)) {
		done_achievement(36,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("苍蓝之光",$nn);
		}
		elseif ((int)fetch_achievement(36,$nn)>=1 && (check_achievement(36,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement(36,1,$nn);
		}
	}
	//37. 『Two Become One』成就
	if ($item=="『Two Become One』") 
	{
		update_achievement(37,$nn,((int)fetch_achievement(37,$nn))+1);
		if ((int)fetch_achievement(37,$nn)>=111 && (check_achievement(37,$nn)<999)) {
		done_achievement(37,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("钥刃大师",$nn);
		}
		elseif ((int)fetch_achievement(37,$nn)>=51 && (check_achievement(37,$nn)<2)) {
		done_achievement(37,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("合二为一",$nn);
		}
		elseif ((int)fetch_achievement(37,$nn)>=1 && (check_achievement(37,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement(37,1,$nn);
		}
	}
	//38. 『迷你鲨』成就
	if ($item=="『迷你鲨』") 
	{
		update_achievement(38,$nn,((int)fetch_achievement(38,$nn))+1);
		if ((int)fetch_achievement(38,$nn)>=111 && (check_achievement(38,$nn)<999)) {
		done_achievement(38,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("射系爱好者",$nn);
		}
		elseif ((int)fetch_achievement(38,$nn)>=51 && (check_achievement(38,$nn)<2)) {
		done_achievement(37,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("勇闯仙境",$nn);
		}
		elseif ((int)fetch_achievement(38,$nn)>=1 && (check_achievement(38,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement(38,1,$nn);
		}
	}
	//39. ☆金色闪光☆成就
	if ($item=="☆金色闪光☆") 
	{
		update_achievement(39,$nn,((int)fetch_achievement(39,$nn))+1);
		if ((int)fetch_achievement(39,$nn)>=111 && (check_achievement(39,$nn)<999)) {
		done_achievement(39,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("重枪爱好者",$nn);
		}
		elseif ((int)fetch_achievement(39,$nn)>=51 && (check_achievement(39,$nn)<2)) {
		done_achievement(39,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("黑洞边缘",$nn);
		}
		elseif ((int)fetch_achievement(39,$nn)>=1 && (check_achievement(39,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement(39,1,$nn);
		}
	}
	//40. 星尘龙 ★8成就
	if ($item=="星尘龙 ★8") 
	{
		update_achievement(40,$nn,((int)fetch_achievement(40,$nn))+1);
		if ((int)fetch_achievement(40,$nn)>=111 && (check_achievement(40,$nn)<999)) {
		done_achievement(40,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("决斗者",$nn);
		}
		elseif ((int)fetch_achievement(40,$nn)>=51 && (check_achievement(40,$nn)<2)) {
		done_achievement(40,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("光的道路",$nn);
		}
		elseif ((int)fetch_achievement(40,$nn)>=1 && (check_achievement(40,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement(40,1,$nn);
		}
	}
	//41. 流星龙 ★10成就
	if ($item=="流星龙 ★10") 
	{
		update_achievement(41,$nn,((int)fetch_achievement(41,$nn))+1);
		if ((int)fetch_achievement(41,$nn)>=111 && (check_achievement(41,$nn)<999)) {
		done_achievement(41,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("聚集的祈愿",$nn);
		}
		elseif ((int)fetch_achievement(41,$nn)>=51 && (check_achievement(41,$nn)<2)) {
		done_achievement(41,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("加速同调",$nn);
		}
		elseif ((int)fetch_achievement(41,$nn)>=1 && (check_achievement(41,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement(41,1,$nn);
		}
	}
	//42. 《小黄的超级球》成就
	if ($item=="《小黄的超级球》") 
	{
		update_achievement(42,$nn,((int)fetch_achievement(42,$nn))+1);
		if ((int)fetch_achievement(42,$nn)>=111 && (check_achievement(42,$nn)<999)) {
		done_achievement(42,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("投系爱好者",$nn);
		}
		elseif ((int)fetch_achievement(42,$nn)>=51 && (check_achievement(42,$nn)<2)) {
		done_achievement(42,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("平和之心",$nn);
		}
		elseif ((int)fetch_achievement(42,$nn)>=1 && (check_achievement(42,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement(42,1,$nn);
		}
	}
	//43. 莫洛托夫鸡尾酒成就
	if ($item=="莫洛托夫鸡尾酒") 
	{
		update_achievement(43,$nn,((int)fetch_achievement(43,$nn))+1);
		if ((int)fetch_achievement(43,$nn)>=111 && (check_achievement(43,$nn)<999)) {
		done_achievement(43,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("爆系爱好者",$nn);
		}
		elseif ((int)fetch_achievement(43,$nn)>=51 && (check_achievement(43,$nn)<2)) {
		done_achievement(43,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("红烧天堂",$nn);
		}
		elseif ((int)fetch_achievement(43,$nn)>=1 && (check_achievement(43,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement(43,1,$nn);
		}
	}
	//44. ★BIUBIUBIU★成就
	if ($item=="★BIUBIUBIU★") 
	{
		update_achievement(44,$nn,((int)fetch_achievement(44,$nn))+1);
		if ((int)fetch_achievement(44,$nn)>=111 && (check_achievement(44,$nn)<999)) {
		done_achievement(44,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("★啪啪啪★",$nn);
		}
		elseif ((int)fetch_achievement(44,$nn)>=51 && (check_achievement(44,$nn)<2)) {
		done_achievement(44,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("★刷刷刷★",$nn);
		}
		elseif ((int)fetch_achievement(44,$nn)>=1 && (check_achievement(44,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement(44,1,$nn);
		}
	}
	//45. 日符「Royal Flare」成就
	if ($item=="日符「Royal Flare」") 
	{
		update_achievement(45,$nn,((int)fetch_achievement(45,$nn))+1);
		if ((int)fetch_achievement(45,$nn)>=111 && (check_achievement(45,$nn)<999)) {
		done_achievement(45,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("灵系爱好者",$nn);
		}
		elseif ((int)fetch_achievement(45,$nn)>=51 && (check_achievement(45,$nn)<2)) {
		done_achievement(45,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("皇家烈焰",$nn);
		}
		elseif ((int)fetch_achievement(45,$nn)>=1 && (check_achievement(45,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement(45,1,$nn);
		}
	}
	//46. 火水木金土符『贤者之石』成就
	if ($item=="火水木金土符『贤者之石』") 
	{
		update_achievement(46,$nn,((int)fetch_achievement(46,$nn))+1);
		if ((int)fetch_achievement(46,$nn)>=111 && (check_achievement(46,$nn)<999)) {
		done_achievement(46,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("贤者之石",$nn);
		}
		elseif ((int)fetch_achievement(46,$nn)>=51 && (check_achievement(46,$nn)<2)) {
		done_achievement(46,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("五行大师",$nn);
		}
		elseif ((int)fetch_achievement(46,$nn)>=1 && (check_achievement(46,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement(46,1,$nn);
		}
	}
	//47. 广域生命探测器成就
	if ($item=="广域生命探测器") 
	{
		update_achievement(47,$nn,((int)fetch_achievement(47,$nn))+1);
		if ((int)fetch_achievement(47,$nn)>=111 && (check_achievement(47,$nn)<999)) {
		done_achievement(47,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("知人和",$nn);
		}
		elseif ((int)fetch_achievement(47,$nn)>=51 && (check_achievement(47,$nn)<2)) {
		done_achievement(47,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("知地利",$nn);
		}
		elseif ((int)fetch_achievement(47,$nn)>=1 && (check_achievement(47,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement(47,1,$nn);
		}
	}
	//48. 法式面包棍棒成就
	if ($item=="法式面包棍棒") 
	{
		update_achievement(48,$nn,((int)fetch_achievement(48,$nn))+1);
		if ((int)fetch_achievement(48,$nn)>=111 && (check_achievement(48,$nn)<999)) {
		done_achievement(48,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+350 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("混沌的深渊",$nn);
		}
		elseif ((int)fetch_achievement(48,$nn)>=51 && (check_achievement(48,$nn)<2)) {
		done_achievement(48,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("混沌爱好者",$nn);
		}
		elseif ((int)fetch_achievement(48,$nn)>=1 && (check_achievement(48,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement(48,1,$nn);
		}
	}
	//49. 【春雨夏海，秋叶冬雪】挑战成就
	if ($item=="【春雨夏海，秋叶冬雪】") 
	{
		update_achievement(49,$nn,((int)fetch_achievement(49,$nn))+1);
		if ((int)fetch_achievement(49,$nn)>=7 && (check_achievement(49,$nn)<999)) {
		done_achievement(49,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+700 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("键·四季赞歌",$nn);
		}
		elseif ((int)fetch_achievement(49,$nn)>=1 && (check_achievement(49,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+100 WHERE username='".$nn."'" );
		done_achievement(49,1,$nn);
		}
	}
	//50. ★一发逆转神话★挑战成就
	if ($item=="★一发逆转神话★") 
	{
		update_achievement(50,$nn,((int)fetch_achievement(50,$nn))+1);
		if ((int)fetch_achievement(50,$nn)>=7 && (check_achievement(50,$nn)<999)) {
		done_achievement(50,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+700 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("★一发逆转！★",$nn);
		}
		elseif ((int)fetch_achievement(50,$nn)>=1 && (check_achievement(50,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+100 WHERE username='".$nn."'" );
		done_achievement(50,1,$nn);
		}
	}
	//51. 模式『EX』挑战成就
	if ($item=="模式『EX』") 
	{
		update_achievement(51,$nn,((int)fetch_achievement(51,$nn))+1);
		if ((int)fetch_achievement(51,$nn)>=7 && (check_achievement(51,$nn)<999)) {
		done_achievement(51,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+700 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("『ＥＸ』",$nn);
		}
		elseif ((int)fetch_achievement(51,$nn)>=1 && (check_achievement(51,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+100 WHERE username='".$nn."'" );
		done_achievement(51,1,$nn);
		}
	}
	//52. ◎光之创造神◎挑战成就
	if ($item=="◎光之创造神◎") 
	{
		update_achievement(52,$nn,((int)fetch_achievement(52,$nn))+1);
		if ((int)fetch_achievement(52,$nn)>=7 && (check_achievement(52,$nn)<999)) {
		done_achievement(52,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+700 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("◎胜利之光◎",$nn);
		}
		elseif ((int)fetch_achievement(52,$nn)>=1 && (check_achievement(52,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+100 WHERE username='".$nn."'" );
		done_achievement(52,1,$nn);
		}
	}
}
function check_end_achievement($w,$m)
{
	global $now,$validtime,$starttime,$gamecfg,$name,$db,$tablepre;
	//16. 最后幸存成就
	//$result = $db->query("SELECT achievement FROM {$tablepre}users WHERE username = '$w' AND type = 0");
	//$ach = $db->result($result, 0);
	if ($m==2)
	{
		update_achievement(16,$w,((int)fetch_achievement(16,$w))+1,$w);
		if (!check_achievement(16,$w)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits+150 WHERE username='".$w."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$w."'" );
		done_achievement(16,999,$w);
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("生存者",$w);
		}
	}
	//17. 核爆全灭成就
	if ($m==5)
	{
		update_achievement(17,$w,((int)fetch_achievement(17,$w))+1,$w);
		if (!check_achievement(17,$w)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$w."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+100 WHERE username='".$w."'" );
		done_achievement(17,999,$w);
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("叶子钦定！",$w);
		}
	}
	//18. 锁定解除成就
	if ($m==3)
	{
		update_achievement(18,$w,((int)fetch_achievement(18,$w))+1);
		if (!check_achievement(18,$w)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits+500 WHERE username='".$w."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$w."'" );
		done_achievement(18,999,$w);
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("最后的荣光",$w);
		}
	}
	//19. 幻境解离成就
	if ($m==7)
	{
		update_achievement(19,$w,((int)fetch_achievement(19,$w))+1);
		if (!check_achievement(19,$w)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits+1000 WHERE username='".$w."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+1000 WHERE username='".$w."'" );
		done_achievement(19,999,$w);
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("奇迹的篝火",$w);
		}
	}
}

function check_battle_achievement($n,$is_npc,$killname,$wp)
{
	global $gamecfg,$w_name,$name,$db,$tablepre;
	$nn=$n;
	if ($nn==$killname){$nn=$w_name;}
	//2. 击杀玩家成就
	if (!$is_npc)
	{
		update_achievement(2,$nn,((int)fetch_achievement(2,$nn))+1);
		if ((int)fetch_achievement(2,$nn)>=1000 && (check_achievement(2,$nn)<999)) {
		done_achievement(2,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+200 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("G.D.M",$nn);
		}
		elseif ((int)fetch_achievement(2,$nn)>=100 && (check_achievement(2,$nn)<2)) {
		done_achievement(2,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+500 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("二度打",$nn);
		}
		elseif ((int)fetch_achievement(2,$nn)>=10 && (check_achievement(2,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		done_achievement(2,1,$nn);
		}
	}
	//31. ReturnToSender成就
	if (!$is_npc)
	{
		$result = $db->query("SELECT nicks FROM {$tablepre}users WHERE username = '$killname'");
		$ns = $db->result($result, 0);
		if ((strpos($ns,"KEY男")!==false)&&($wp=='【KEY系催泪弹】')){
			update_achievement(31,$nn,((int)fetch_achievement(31,$nn))+1);
			if ((int)fetch_achievement(31,$nn)>=1 && (check_achievement(31,$nn)<999)) {
				done_achievement(31,999,$nn);
				include_once GAME_ROOT.'./include/game/titles.func.php';
				get_title("R.T.S",$nn);
				get_title("善有善报",$killname);
				}
		}
	}
	//32. 呵呵
	if (!$is_npc)
	{
		$result = $db->query("SELECT nicks FROM {$tablepre}users WHERE username = '$killname'");
		$ns1 = $db->result($result, 0);
		$result = $db->query("SELECT nicks FROM {$tablepre}users WHERE username = '$nn'");
		$ns2 = $db->result($result, 0);
		if ((strpos($ns2,"LOOP")!==false)||(strpos($ns1,"LOOP")!==false)){
			if (check_achievement(32,$nn)<999) done_achievement(32,999,$nn);
			if (check_achievement(32,$killname)<999) done_achievement(32,999,$killname);
			include_once GAME_ROOT.'./include/game/titles.func.php';
			get_title("LOOP",$nn);
			get_title("LOOP",$killname);
		}
	}
	//3. 击杀NPC成就 
	if ($is_npc)
	{
		update_achievement(3,$nn,((int)fetch_achievement(3,$nn))+1);
		if ((int)fetch_achievement(3,$nn)>=10000 && (check_achievement(3,$nn)<999)) {
		done_achievement(3,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+500 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+15 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("最后一步",$nn);
		}
		elseif ((int)fetch_achievement(3,$nn)>=500 && (check_achievement(3,$nn)<2)) {
		done_achievement(3,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+200 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("黑客",$nn);
		}
		elseif ((int)fetch_achievement(3,$nn)>=100 && (check_achievement(3,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+5 WHERE username='".$nn."'" );
		done_achievement(3,1,$nn);
		}
	}
	//4. 推倒红暮成就
	if ($is_npc && ($killname=="红暮" || $killname=="红杀将军 红暮")) 
	{
		update_achievement(4,$nn,((int)fetch_achievement(4,$nn))+1);
		if ((int)fetch_achievement(4,$nn)>=9 && (check_achievement(4,$nn)<999)) {
		done_achievement(4,999,$nn);
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("越红者",$nn);
		}
		elseif ((int)fetch_achievement(4,$nn)>=1 && (check_achievement(4,$nn)<1)) {
		done_achievement(4,1,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+50 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+75 WHERE username='".$nn."'" );
		}
	}
	//13. 推倒蓝凝成就
	if ($is_npc && ($killname=="蓝凝" || $killname=="红杀菁英 蓝凝")) 
	{
		update_achievement(13,$nn,((int)fetch_achievement(13,$nn))+1);
		if ((int)fetch_achievement(13,$nn)>=3 && (check_achievement(13,$nn)<999)) {
		done_achievement(13,999,$nn);
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("跨过彩虹",$nn);
		}
		elseif ((int)fetch_achievement(13,$nn)>=1 && (check_achievement(13,$nn)<1)) {
		done_achievement(13,1,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+50 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+75 WHERE username='".$nn."'" );
		}
	}
	//20. 击破虚子成就
	if ($is_npc && ($killname=="虚子" || $killname=="武神 虚子")) 
	{
		update_achievement(20,$nn,((int)fetch_achievement(20,$nn))+1);
		if ((int)fetch_achievement(20,$nn)>=1 && (check_achievement(20,$nn)<999)) {
		done_achievement(20,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+268 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+263 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("寻星者",$nn);
		}
	}
	//21. 击破水月成就
	if ($is_npc && ($killname=="水月" || $killname=="武神 水月")) 
	{
		update_achievement(21,$nn,((int)fetch_achievement(21,$nn))+1);
		if ((int)fetch_achievement(21,$nn)>=1 && (check_achievement(21,$nn)<999)) {
		done_achievement(21,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+233 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+233 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("寂静洪流",$nn);
		}
	}
	//22. 击破冴冴成就
	if ($is_npc && ($killname=="冴月麟MK-II" || $killname=="天神 冴月麟MK-II")) 
	{
		update_achievement(22,$nn,((int)fetch_achievement(22,$nn))+1);
		if ((int)fetch_achievement(22,$nn)>=1 && (check_achievement(22,$nn)<999)) {
		done_achievement(22,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+2333 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("l33t",$nn);
		}
	}
	//23. 击破四面成就
	if ($is_npc && ($killname=="星莲船四面BOSS" || $killname=="天神 星莲船四面BOSS")) 
	{
		update_achievement(23,$nn,((int)fetch_achievement(23,$nn))+1);
		if ((int)fetch_achievement(23,$nn)>=1 && (check_achievement(23,$nn)<999)) {
		done_achievement(23,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+888 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("赌玉狂魔",$nn);
		}
	}
	//24. 击破北京成就
	if ($is_npc && ($killname=="北京推倒你" || $killname=="武神 北京推倒你")) 
	{
		update_achievement(24,$nn,((int)fetch_achievement(24,$nn))+1);
		if ((int)fetch_achievement(24,$nn)>=1 && (check_achievement(24,$nn)<999)) {
		done_achievement(24,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+211 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+299 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("时代眼泪",$nn);
		}
	}
	//25. 击破yoshiko成就
	if ($is_npc && ($killname=="Yoshiko-G" || $killname=="武神 Yoshiko-G")) 
	{
		update_achievement(25,$nn,((int)fetch_achievement(25,$nn))+1);
		if ((int)fetch_achievement(25,$nn)>=1 && (check_achievement(25,$nn)<999)) {
		done_achievement(25,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+111 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+333 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("卸腿者",$nn);
		}
	}
	//26. 击破吉祥物成就
	if ($is_npc && ($killname=="便当盒" || $killname=="真职人 便当盒")) 
	{
		update_achievement(26,$nn,((int)fetch_achievement(26,$nn))+1);
		if ((int)fetch_achievement(26,$nn)>=1 && (check_achievement(26,$nn)<999)) {
		done_achievement(26,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+1 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+111 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("吉祥物",$nn);
		}
	}
	//27. 英灵殿成就 
	if (($is_npc>=20)&&($is_npc<=22))
	{
		update_achievement(27,$nn,((int)fetch_achievement(27,$nn))+1);
		if ((int)fetch_achievement(27,$nn)>=100 && (check_achievement(27,$nn)<999)) {
		done_achievement(27,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+500 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("替天行道",$nn);
		}
		elseif ((int)fetch_achievement(27,$nn)>=30 && (check_achievement(27,$nn)<2)) {
		done_achievement(27,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+300 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		}
		elseif ((int)fetch_achievement(27,$nn)>=1 && (check_achievement(27,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement(27,1,$nn);
		}
	}
	//56. 击杀种火成就 
	if ($is_npc==92)
	{
		update_achievement(56,$nn,((int)fetch_achievement(56,$nn))+1);
		if ((int)fetch_achievement(56,$nn)>=366 && (check_achievement(56,$nn)<999)) {
		done_achievement(56,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+250 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("除错大师",$nn);
		}
		elseif ((int)fetch_achievement(56,$nn)>=180 && (check_achievement(56,$nn)<2)) {
		done_achievement(56,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("都市传说",$nn);
		}
		elseif ((int)fetch_achievement(56,$nn)>=1 && (check_achievement(56,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement(56,1,$nn);
		}
	}
	//57. 击杀回声成就 
	if ($is_npc==89)
	{
		update_achievement(57,$nn,((int)fetch_achievement(57,$nn))+1);
		if ((int)fetch_achievement(57,$nn)>=103 && (check_achievement(57,$nn)<999)) {
		done_achievement(57,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+250 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("暴雷骤雨",$nn);
		}
		elseif ((int)fetch_achievement(57,$nn)>=52 && (check_achievement(57,$nn)<2)) {
		done_achievement(57,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("风驰电掣",$nn);
		}
		elseif ((int)fetch_achievement(57,$nn)>=1 && (check_achievement(57,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+10 WHERE username='".$nn."'" );
		done_achievement(57,1,$nn);
		}
	}
}


function check_item_achievement($nn,$i,$ie,$is,$ik,$isk)
{
	global $gamecfg,$name,$db,$tablepre,$now,$starttime,$gamestate;
	//28. 死斗成就
	if (($gamestate==50)&&($i=="杏仁豆腐的ID卡")) 
	{
		$t=$now-$starttime;
		$besttime=(int)fetch_achievement(28,$nn);
		if ($t<$besttime || $besttime==0) update_achievement(28,$nn,$t);
		if (!check_achievement(28,$nn) && $t<=1800) {
		done_achievement(28,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits+250 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("神触",$nn);
		}
	}
	//29. 美食成就
	if (($ik=='HS')||($ik=='HH')||($ik=='HB'))
	{
		$heal=$ie;
		if ($ik=='HB') $heal+=$ie;
		$uu=((int)fetch_achievement(29,$nn))+$heal;
		if ($uu>9999999) $uu=9999999;
		update_achievement(29,$nn,$uu);
		if (((int)fetch_achievement(29,$nn)>=999983) && (check_achievement(29,$nn))<999) {
		done_achievement(29,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+200 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("补给掠夺者",$nn);
		}
		elseif ((int)fetch_achievement(29,$nn)>=142857 && (check_achievement(29,$nn)<2)) {
		done_achievement(29,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+50 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("美食家",$nn);
		}
		elseif ((int)fetch_achievement(29,$nn)>=32767 && (check_achievement(29,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+5 WHERE username='".$nn."'" );
		done_achievement(29,1,$nn);
		}
	}
	//30. 贝爷成就
	$kk=substr($ik,0,1);
	if (($kk=='P')&&($ie>=30))
	{
		update_achievement(30,$nn,((int)fetch_achievement(30,$nn))+1);
		if (((int)fetch_achievement(30,$nn)>=365) && (check_achievement(30,$nn))<999) {
		done_achievement(30,999,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+200 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("贝爷",$nn);
		}
		elseif ((int)fetch_achievement(30,$nn)>=133 && (check_achievement(30,$nn)<2)) {
		done_achievement(30,2,$nn);
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+50 WHERE username='".$nn."'" );
		include_once GAME_ROOT.'./include/game/titles.func.php';
		get_title("神农",$nn);
		}
		elseif ((int)fetch_achievement(30,$nn)>=5 && (check_achievement(30,$nn)<1)) {
		$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET credits2=credits2+5 WHERE username='".$nn."'" );
		done_achievement(30,1,$nn);
		}
	}
	//34. 独自逃脱成就
	if ($i=="【E.S.C.A.P.E】"){
		update_achievement(34,$nn,((int)fetch_achievement(34,$nn))+1);
		// +20 切糕与1胜场
		$db->query("UPDATE {$tablepre}users SET credits=credits+20 WHERE username='".$nn."'" );
		$db->query("UPDATE {$tablepre}users SET wingames=wingames+1 WHERE username='".$nn."'" );
		if (((int)fetch_achievement(34,$nn)>=101) && (check_achievement(34,$nn))<999) {
			done_achievement(34,999,$nn);
			$db->query("UPDATE {$tablepre}users SET credits=credits+100 WHERE username='".$nn."'" );
			$db->query("UPDATE {$tablepre}users SET credits2=credits2+200 WHERE username='".$nn."'" );
			include_once GAME_ROOT.'./include/game/titles.func.php';
			get_title("脚底抹油",$nn);
			}
			elseif ((int)fetch_achievement(34,$nn)>=36 && (check_achievement(34,$nn)<2)) {
			done_achievement(34,2,$nn);
			$db->query("UPDATE {$tablepre}users SET credits=credits+50 WHERE username='".$nn."'" );
			$db->query("UPDATE {$tablepre}users SET credits2=credits2+50 WHERE username='".$nn."'" );
			include_once GAME_ROOT.'./include/game/titles.func.php';
			get_title("现实主义者",$nn);
			}
			elseif ((int)fetch_achievement(34,$nn)>=1 && (check_achievement(34,$nn)<1)) {
			done_achievement(34,1,$nn);
			$db->query("UPDATE {$tablepre}users SET credits=credits+10 WHERE username='".$nn."'" );
			$db->query("UPDATE {$tablepre}users SET credits2=credits2+5 WHERE username='".$nn."'" );
			include_once GAME_ROOT.'./include/game/titles.func.php';
			get_title("实用主义者",$nn);
			}
	}
	//53. 打钉子成就
	if (preg_match ( "/钉$/", $i ) || preg_match ( "/钉\[/", $i )){
		$enhance=$ie;
		$uu=((int)fetch_achievement(53,$nn))+$enhance;
		if ($uu>9999999) $uu=9999999;
		update_achievement(53,$nn,$uu);
		if (((int)fetch_achievement(53,$nn)>=17777) && (check_achievement(53,$nn))<999) {
			done_achievement(53,999,$nn);
			$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
			$db->query("UPDATE {$tablepre}users SET credits2=credits2+200 WHERE username='".$nn."'" );
			include_once GAME_ROOT.'./include/game/titles.func.php';
			get_title("无情打钉者",$nn);
			}
			elseif ((int)fetch_achievement(53,$nn)>=1777 && (check_achievement(53,$nn)<2)) {
			done_achievement(53,2,$nn);
			$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
			$db->query("UPDATE {$tablepre}users SET credits2=credits2+50 WHERE username='".$nn."'" );
			include_once GAME_ROOT.'./include/game/titles.func.php';
			get_title("棍棒爱好者",$nn);
			}
			elseif ((int)fetch_achievement(53,$nn)>=777 && (check_achievement(53,$nn)<1)) {
			$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
			$db->query("UPDATE {$tablepre}users SET credits2=credits2+5 WHERE username='".$nn."'" );
			done_achievement(53,1,$nn);
			}
	}
	//54. 磨刀石成就
	if (strpos ( $i, '磨刀石' ) !== false){
		$enhance=$ie;
		$uu=((int)fetch_achievement(54,$nn))+$enhance;
		if ($uu>9999999) $uu=9999999;
		update_achievement(54,$nn,$uu);
		if (((int)fetch_achievement(54,$nn)>=17777) && (check_achievement(54,$nn))<999) {
			done_achievement(54,999,$nn);
			$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
			$db->query("UPDATE {$tablepre}users SET credits2=credits2+200 WHERE username='".$nn."'" );
			include_once GAME_ROOT.'./include/game/titles.func.php';
			get_title("无情磨刀者",$nn);
			}
			elseif ((int)fetch_achievement(54,$nn)>=1777 && (check_achievement(54,$nn)<2)) {
			done_achievement(54,2,$nn);
			$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
			$db->query("UPDATE {$tablepre}users SET credits2=credits2+50 WHERE username='".$nn."'" );
			include_once GAME_ROOT.'./include/game/titles.func.php';
			get_title("磨刀爱好者",$nn);
			}
			elseif ((int)fetch_achievement(54,$nn)>=777 && (check_achievement(54,$nn)<1)) {
			$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
			$db->query("UPDATE {$tablepre}users SET credits2=credits2+5 WHERE username='".$nn."'" );
			done_achievement(54,1,$nn);
			}
	}
	//55. 针线包成就
	if ($i == "针线包"){
		$enhance=$ie;
		$uu=((int)fetch_achievement(55,$nn))+$enhance;
		if ($uu>9999999) $uu=9999999;
		update_achievement(55,$nn,$uu);
		if (((int)fetch_achievement(55,$nn)>=17777) && (check_achievement(55,$nn))<999) {
			done_achievement(55,999,$nn);
			$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
			$db->query("UPDATE {$tablepre}users SET credits2=credits2+200 WHERE username='".$nn."'" );
			include_once GAME_ROOT.'./include/game/titles.func.php';
			get_title("无情补丁",$nn);
			}
			elseif ((int)fetch_achievement(55,$nn)>=1777 && (check_achievement(55,$nn)<2)) {
			done_achievement(55,2,$nn);
			$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
			$db->query("UPDATE {$tablepre}users SET credits2=credits2+50 WHERE username='".$nn."'" );
			include_once GAME_ROOT.'./include/game/titles.func.php';
			get_title("补丁爱好者",$nn);
			}
			elseif ((int)fetch_achievement(55,$nn)>=777 && (check_achievement(55,$nn)<1)) {
			$db->query("UPDATE {$tablepre}users SET credits=credits WHERE username='".$nn."'" );
			$db->query("UPDATE {$tablepre}users SET credits2=credits2+5 WHERE username='".$nn."'" );
			done_achievement(55,1,$nn);
			}
	}
}


?>
