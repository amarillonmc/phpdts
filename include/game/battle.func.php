<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}

function findteam(&$w_pdata)
{
	global $log,$mode,$main,$cmd,$battle_title,$gamestate;
	global $pdata;

	if($gamestate>=40)
	{
		$log .= '<span class="yellow">连斗阶段所有队伍取消！</span><br>';
		$mode = 'command';
		return;
	}

	$battle_title = '发现队友';
	extract($pdata,EXTR_REFS);
	extract($w_pdata,EXTR_PREFIX_ALL,'w');
	init_battle_rev($pdata,$w_pdata);
	
	$main = 'battle_rev';
	$log .= "你发现了队友<span class=\"yellow\">$w_name</span>！<br>";

	include template('findteam');
	$cmd = ob_get_contents();
	ob_clean();

	return;
}

function findcorpse(&$w_pdata)
{
	global $log,$mode,$main,$battle_title,$cmd,$iteminfo,$itemspkinfo;
	global $allow_destory_corpse,$no_destory_corpse_type;
	global $pdata;

	$battle_title = '发现尸体';
	extract($pdata,EXTR_REFS);
	extract($w_pdata,EXTR_PREFIX_ALL,'w');

	init_battle_rev($pdata,$w_pdata,1);
	
	$main = 'battle_rev';
	$log .= '你发现了<span class="red">'.$w_name.'</span>的尸体！<br>';

	# 初始化尸体tooltip
	foreach (Array('wep','wep2','arb','arh','ara','arf','art','itm0','itm1','itm2','itm3','itm4','itm5','itm6') as $value) 
	{
		$value = 'w_'.$value;
		if(strpos($value,'itm')!==false)
		{
			$k_value = str_replace('itm','itmk',$value);
			$s_value = str_replace('itm','itms',$value);
			$sk_value = str_replace('itm','itmsk',$value);
		}
		else 
		{
			$k_value = $value.'k';
			$s_value = $value.'s';
			$sk_value = $value.'sk';
		}

		global ${$value.'_words'},${$k_value.'_words'},${$s_value.'_words'},${$sk_value.'_words'};

		# 初始化名称样式
		${$value.'_words'} = parse_nameinfo_desc($$value,$w_horizon);
		# 初始化类别样式
		${$k_value.'_words'} = parse_kinfo_desc($$k_value,$$sk_value);
		# 初始化属性样式
		${$sk_value.'_words'} = empty($$sk_value) ? '' : parse_skinfo_desc($$sk_value,$$k_value,1);
	}

	// 初始化仓库数据
	include_once GAME_ROOT.'./include/game/depot.func.php';
	$loot_depot_flag = 0;
	if(in_array($w_type,$can_lootdepot_type)) $loot_depot_flag = depot_getlist($w_name,$w_type) ? 1 : 0;

	// 初始化抡尸数据
	$cstick_flag = 0;
	if(!check_skill_unlock('tl_cstick',$pdata) && !check_skill_cost('tl_cstick',$pdata)) $cstick_flag = in_array($w_type,get_skillvars('tl_cstick','notype')) ? 0 : 1;
	
	// 保存发现过女主尸体的记录
	if($w_pdata['type'] == 14) $clbpara['achvars']['corpse_n14'] += 1;

	include template('corpse');
	$cmd = ob_get_contents();
	ob_clean();

	return;
}


function senditem()
{
	global $db,$tablepre,$log,$mode,$main,$command,$cmd,$battle_title,$message,$plsinfo,$hplsinfo,$now,$gamestate;
	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);

	$mateid = $bid;
	if(!$mateid || $action != 'team'){
		$log .= '<span class="yellow">你没有遇到队友，或已经离开现场！</span><br>';
		$action = ''; $bid = 0;
		$mode = 'command';
		return;
	}
	if($gamestate>=40){
		$log .= '<span class="yellow">连斗阶段无法赠送物品！</span><br>';
		$action = ''; $bid = 0;
		$mode = 'command';
		return;
	}
	$result = $db->query("SELECT * FROM {$tablepre}players WHERE pid='$mateid'");
	if(!$db->num_rows($result)){
		$log .= "对方不存在！<br>";
		$action = ''; $bid = 0;
		$mode = 'command';
		return;
	}

	$edata = $db->fetch_array($result);
	if($edata['pls'] != $pls) 
	{
		//登记非功能性地点信息时合并隐藏地点
		foreach($hplsinfo as $hgroup=>$hpls) $plsinfo += $hpls;
		$log .= '<span class="yellow">'.$edata['name'].'</span>已经离开了<span class="yellow">'.$plsinfo[$pls].'</span>。<br>';
		$mode = 'command';
		$action = ''; $bid = 0;
		return;
	} elseif($edata['hp'] <= 0) {
		$log .= '<span class="yellow">'.$edata['name'].'</span>已经死亡，不能接受物品。<br>';
		$mode = 'command';
		$action = ''; $bid = 0;
		return;
	} elseif(!$teamID || $edata['teamID']!=$teamID){
		$log .= '<span class="yellow">'.$edata['name'].'</span>并非你的队友，不能接受物品。<br>';
		$mode = 'command';
		$action = ''; $bid = 0;
		return;
	}

	if(!empty($message))
	{
		$log .= "<span class=\"lime\">你对{$edata['name']}说：“{$message}”</span><br>";
		$w_log = "<span class=\"lime\">{$name}对你说：“{$message}”</span><br>";
		if(!$edata['type']){logsave($edata['pid'],$now,$w_log,'c');}
	}
	
	if($command != 'back')
	{
		$itmn = substr($command, 3);

		if (!${'itms'.$itmn}) {
			$log .= '此道具不存在！';
			$action = ''; $bid = 0;
			$mode = 'command';
			return;
		}
		$itm = & ${'itm'.$itmn};
		$itmk = & ${'itmk'.$itmn};
		$itme = & ${'itme'.$itmn};
		$itms = & ${'itms'.$itmn};
		$itmsk = & ${'itmsk'.$itmn};

		# 诅咒道具不能赠予，不准祸水东引！
		if(in_array('V',get_itmsk_array($itmsk)))
		{
			$log .= "你伸出手，挠了挠自己的头。<br>你本来打算干什么来着？<br>";
			$action = ''; $bid = 0;
			$mode = 'command';
			return;
		}

		extract($edata,EXTR_PREFIX_ALL,'w');

		for($i = 1;$i <= 6; $i++)
		{
			if(!$edata['itms'.$i]) 
			{
				$edata['itm'.$i] = $itm;
				$edata['itmk'.$i] = $itmk;
				$edata['itme'.$i] = $itme;
				$edata['itms'.$i] = $itms;
				$edata['itmsk'.$i] = $itmsk;
				$log .= "你将<span class=\"yellow\">{$edata['itm'.$i]}</span>送给了<span class=\"yellow\">$w_name</span>。<br>";
				$w_log = "<span class=\"yellow\">$name</span>将<span class=\"yellow\">{$edata['itm'.$i]}</span>送给了你。";
				if(!$w_type){logsave($w_pid,$now,$w_log,'t');}
				
				addnews($now,'senditem',$name,$w_name,$itm,$nick);
				//w_save($w_pid);
				player_save($edata);
				$itm = $itmk = $itmsk = '';
				$itme = $itms = 0;
				$action = ''; $bid = 0;
				return;
			}
		}
		$log .= "<span class=\"yellow\">$w_name</span> 的包裹已经满了，不能赠送物品。<br>";
	}
	$action = ''; $bid = 0;
	$mode = 'command';
	return;
}

?>
