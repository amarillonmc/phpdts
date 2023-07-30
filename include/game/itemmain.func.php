<?php

//
//	date:2012-02-02
///include/game/itemmain.func.php
//	Issue：
//	preg_replace
//	
//*/

if(!defined('IN_GAME')) {
	exit('Access Denied');
}



# 计算发现陷阱后的“触发率”
function calc_real_trap_obbs($pa,$trpnum)
{
	global $gamestate,$trap_min_obbs;
	# 最小值
	$real_trap_obbs = $trap_min_obbs;
	# 地图上每有1个雷+0.25%
	$real_trap_obbs += $trpnum/4;
	# 奇怪的加成值:
	# rp雷修正：
	$real_trap_obbs = $gamestate >= 50 ? $real_trap_obbs + $pa['rp']/30 : $real_trap_obbs + $pa['rp'] / 177;
	# 连斗修正
	if($gamestate >= 40) $real_trap_obbs += 3;
	# 姿态修正：
	if($pa['pose'] == 3) $real_trap_obbs += 1;
	if($pa['pose'] == 1) $real_trap_obbs += 3; //攻击和探索姿势略容易踩陷阱
	# 地点修正：
	if($pa['pls'] == 0) $real_trap_obbs += 15; //无月之影太恐怖啦
	# 社团修正
	if($pa['club'] == 6) $real_trap_obbs *= 0.85; //宛如疾风陷阱触发率*0.85
	return $real_trap_obbs;
}

# 计算触发陷阱后的“回避率”
function calc_trap_escape_rate(&$pa,$playerflag=0,$selflag=0)
{
	# 奇迹雷回避率-1
	if($pa['itmk0'] == 'TOc') return -1;

	# 最大陷阱回避率
	$max_escrate = 90;
	# 基础回避率：8 + 等级/3
	$escrate = 8 + $pa['lvl']/3;
	# 宛如疾风社团加成
	if($pa['club'] == 6) $escrate *= 1.1;
	# 躲避策略加成
	if($pa['tactic'] == 4) $escrate *= 1.2;
	# 自雷回避加成
	if($selflag) $escrate *= 1.5;
	# 陷阱探测属性加成（锡安陷阱探测属性效果+10）
	include_once GAME_ROOT.'./include/game/revattr.func.php';
	if(empty($pa['ex_keys'])) $pa['ex_keys'] = array_merge(\revattr\get_equip_ex_array($pa),\revattr\get_wep_ex_array($pa));
	if(!empty($pa['ex_keys']) && in_array('M',$pa['ex_keys']))
	{
		$pa['minedetect'] = 1;
		$escrate *= $pa['club'] == 7 ? 1.45 : 1.35;
	}

	# 社团技能修正（旧）
	// include_once GAME_ROOT.'./include/game/clubskills.func.php';
	// $escrate *= get_clubskill_bonus_escrate($pa['club'],$pa['skills']);

	# 社团技能修正（新）
	# 「谨慎」效果判定：
	if(!empty($pa['clbpara']['skill']) && !check_skill_unlock('c5_caution',$pa))
	{
		$sk_lvl = get_skilllvl('c5_caution',$pa);
		$escrate *= 1+(get_skillvars('c5_caution','evgain',$sk_lvl)/100);
	}
	return min($escrate,$max_escrate);
}

# 计算触发陷阱后的陷阱伤害
function calc_trap_damage(&$pa,$pd=NULL,$playerflag=0,$selflag=0)
{
	global $log;
	// 奇迹陷阱
	if($pa['itmk0'] == 'TOc')
	{
		$damage = $pa['hp'];
		return $damage;
	}
	// 随机数大神的陷阱
	if($pa['itmk0'] == 'TO8')
	{ 
		$damage = $pa['hp'] / 8;
		return $damage;
	}

	$damage = round(rand(0,$pa['itme0']/2)+($pa['itme0']/2));

	# 防御姿态可以降低陷阱伤害
	$damage = $pa['tactic'] == 2 ? round($damage * 0.75) : $damage;

	# 技能「宗师」效果判定
	if(!check_skill_unlock('c13_master',$pa))
	{
		$pa['skilllog'] = "大祸临头，你却呵呵笑道：“雕虫小技，不足为惧！”<br>";
		$damage_p = get_skillvars('c13_master','trapdmgloss');
		$pa['skilllog'] .= "已经跳到你腿上的<span class='yellow'>{$pa['itm0']}</span>显然被你非凡的气魄震慑到了！你仅";
		$damage = round($damage * (1 - ($damage_p/100)));
	}
	
	return $damage;
}

# 计算触发陷阱后的伤害减免事件
function check_trap_def_event(&$pa,$damage,$playerflag=0,$selflag=0)
{
	# 奇迹雷、神力雷不能迎击
	if($pa['itmk0'] == 'TOc' || $pa['itmk0'] == 'TO8') return $damage;
	# 检查是否有迎击属性
	include_once GAME_ROOT.'./include/game/revattr.func.php';
	if(empty($pa['ex_keys'])) $pa['ex_keys'] = array_merge(\revattr\get_equip_ex_array($pa),\revattr\get_wep_ex_array($pa));
	# 计算迎击概率（锡安迎击率+20）
	if(!empty($pa['ex_keys']) && in_array('m',$pa['ex_keys'])) 
	{
		$pa['minedetect'] = 1;
		$def_obbs = $pa['club'] == 7 ? 60 : 40;
		$dice = diceroll(99);
		if($dice < $def_obbs)
		{
			$damage = 0;
		}
	}

	# 「天佑」技能判定
	if($damage && !check_skill_unlock('buff_godbless',$pa))
	{
		$damage = 0;
		$log .= "<span class=\"yellow\">「天佑」使你免疫了陷阱伤害！</span><br>";
	}

	return $damage;
}

# 计算回避陷阱后的“陷阱回收率”
function calc_trap_reuse_rate($pa,$playerflag=0,$selflag=0)
{
	# 基础回收率
	$fdrate = 5 + $pa['lvl']/3;
	# 拆弹专家社团加成
	if($pa['club'] == 5) $fdrate += 35;
	# 自雷回收加成
	if($selflag) $fdrate += 50;

	# 社团技能修正（旧）
	//include_once GAME_ROOT.'./include/game/clubskills.func.php';
	//$fdrate *= get_clubskill_bonus_reuse($club,$skills);

	# 社团技能修正（新）
	# 「谨慎」效果判定：
	if(!empty($pa['clbpara']['skill']) && !check_skill_unlock('c5_caution',$pa))
	{
		$sk_lvl = get_skilllvl('c5_caution',$pa);
		$fdrate += get_skillvars('c5_caution','reugain',$sk_lvl);
	}
	return $fdrate;
}

function trap(&$data=NULL){
	global $log,$cmd,$mode,$iteminfo;
	global $now,$db,$tablepre;

	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);
	
	$playerflag = $itmsk0 ? true : false;
	$selflag = $itmsk0 == $pid ? true : false;
	$dice=diceroll(99);

	if($playerflag && !$selflag)
	{
		$result = $db->query("SELECT * FROM {$tablepre}players WHERE pid='$itmsk0'");
		$wdata = $db->fetch_array($result);
		if(!empty($wdata))
		{
			$trname = $wdata['name'];$trtype = $wdata['type'];$trperfix = '<span class="yellow">'.$trname.'</span>设置的';
		}
		else 
		{
			$trname = $trtype = $trperfix = '';
		}
	}
	elseif($selflag)
	{
		$trname = $name;$trtype = 0;$trperfix = '你自己设置的';
	}
	else
	{
		$trname = $trtype = $trperfix = '';
	}

	// 计算陷阱回避率
	$escrate = calc_trap_escape_rate($data,$playerflag,$selflag);
	//echo '回避率 = '.$escrate.'%';

	if($dice >= $escrate)
	{
		$bid = $itmsk0;

		# 计算陷阱伤害
		$damage = calc_trap_damage($data,NULL,$playerflag,$selflag);
		# 检查陷阱是否被迎击
		$damage = check_trap_def_event($data,$damage,$playerflag,$selflag);

		if($damage)
		{
			$tmp_club=$club;
			$hp -= $damage; 
			$trapkill = false;

			if($playerflag)
			{
				addnews($now,'trap',$name,$trname,$itm0,$nick);
			}
			$log .= "糟糕，你触发了{$trperfix}陷阱<span class=\"yellow\">$itm0</span>！<br>";
			if(!empty($data['skilllog'])) $log.= $data['skilllog'];
			$log .= "受到<span class=\"dmg\">$damage</span>点伤害！<br>";

			# 踩雷rp结算
			$rp_up = -1 * $rp / 2; 
			include_once GAME_ROOT.'./include/state.func.php';
			if($rp_up) rpup_rev($data,$rp_up);

			# 陷阱击杀
			if($hp <= 0) 
			{
				if(!empty($wdata))
				{
					$wdata['wep_name'] = $itm0;
					// 陷阱有主 走击杀判定
					$last = pre_kill_events($wdata,$data,0,'trap');
					// 检查是否复活
					$revival_flag = revive_process($wdata,$data,0);
					// 没有复活 走完击杀流程
					if(!$revival_flag) final_kill_events($wdata,$data,0,$last);
					player_save($wdata);
				}
				else
				{
					include_once GAME_ROOT.'./include/state.func.php';
					$killmsg = death('trap',$trname,$trtype,$itm0,$data);
					$log .= "你被{$trperfix}陷阱杀死了！";
					if($killmsg && !$selflag){
						$log .= "<span class=\"yellow\">{$trname}对你说：“{$killmsg}”</span><br>";
					}				
					if ($tmp_club==99) $log.="<span class=\"lime\">但由于你及时按下了BOMB键，你原地满血复活了！</span><br>";
				}
				$trapkill = true;
				# 检查成就
				// include_once GAME_ROOT.'./include/game/achievement.func.php';
				// check_trap_death_achievement($name,$trname,$selflag,$itm0,$itme0);
			}
			# 陷阱存活
			else
			{
				# 「天佑」技能判定
				if(!check_skill_unlock('c6_godbless',$data) && check_skill_unlock('buff_godbless',$data))
				{
					$actmhp = get_skillvars('c6_godbless','actmhp');
					if($damage >= $data['mhp']*($actmhp/100))
					{
						getclubskill('buff_godbless',$data['clbpara']);
						$log .= "<span class=\"yellow\">你的技能「天佑」被触发，暂时进入了无敌状态！</span><br>";
					}
				}
				# 检查成就
				// include_once GAME_ROOT.'./include/game/achievement.func.php';
				// check_trap_survive_achievement($achievement,$selflag,$itm0,$itme0);
			}
			# logsave
			if($playerflag && !$selflag && $trapkill)
			{
				$w_log = "<span class=\"red\">{$name}触发了你设置的陷阱{$itm0}并被杀死了！</span>";
				if ($tmp_club==99) $w_log.="<span class=\"lime\">但由于{$name}及时按下了BOMB键，{$name}原地满血复活了！</span>";
				$w_log.="<br>";
				logsave ( $itmsk0, $now, $w_log ,'b');
			}
			elseif($playerflag && !$selflag)
			{
				$w_log = "<span class=\"yellow\">{$name}触发了你设置的陷阱{$itm0}！</span><br>";
				logsave ( $itmsk0, $now, $w_log ,'b');
			}
		}
		# 陷阱迎击
		else
		{
			# logsave
			if($playerflag)
			{
				addnews($now,'trapdef',$name,$trname,$itm0,$nick);
				if(!$selflag)
				{
					$w_log = "<span class=\"yellow\">{$name}触发了你设置的陷阱{$itm0}，但是没有受到任何伤害！</span><br>";
					logsave ( $itmsk0, $now, $w_log ,'b');
				}				
			}
			$log .= "糟糕，你触发了{$trperfix}陷阱<span class=\"yellow\">$itm0</span>！";
			if(!empty($data['minedetect']))
			{
				unset($data['minedetect']);
				$log .= "<br>不过，身上装备着的自动迎击系统启动了！<span class=\"yellow\">在迎击功能的保护下你毫发无伤。</span><br>";
			}
			else
			{
				$log .= "但是没有受到任何伤害！<br>";
			}
			# 检查成就
			// include_once GAME_ROOT.'./include/game/achievement.func.php';
			// check_trap_fail_achievement($achievement,$selflag,$itm0,$itme0);
		}
		$itm0 = $itmk0 = $itmsk0 = '';
		$itme0 = $itms0 = 0;
		return;
	}
	# 陷阱回避
	else 
	{
		# 检查成就
		// include_once GAME_ROOT.'./include/game/achievement.func.php';
		// check_trap_miss_achievement($achievement,$selflag,$itm0,$itme0);
		
		# logsave
		if($playerflag && !$selflag)
		{
			addnews($now,'trapmiss',$name,$trname,$itm0,$nick);
			$w_log = "<span class=\"yellow\">{$name}回避了你设置的陷阱{$itm0}！</span><br>";
			logsave ( $itmsk0, $now, $w_log ,'b');
		}

		# 计算陷阱重复利用率
		$fdrate = calc_trap_reuse_rate($data,$playerflag,$selflag);

		if($dice < $fdrate)
		{
			if(!empty($data['minedetect']))
			{
				unset($data['minedetect']);
				$log .= "在探雷装备的辅助下，你发现了{$trperfix}陷阱<span class=\"yellow\">$itm0</span>并且拆除了它。陷阱看上去还可以重复使用。<br>";
			}
			else
			{
				$log .= "你发现了{$trperfix}陷阱<span class=\"yellow\">$itm0</span>，不过你并没有触发它。陷阱看上去还可以重复使用。<br>";
			}				
			$itmsk0 = '';$itmk0 = str_replace('TO','TN',$itmk0);
			$mode = 'itemfind';
			return;
		}
		else
		{
			if(isset($data['minedetect']))
			{
				unset($data['minedetect']);
				$log .= "在探雷装备的辅助下，你发现了{$trperfix}陷阱<span class=\"yellow\">$itm0</span>并且拆除了它。不过陷阱好像被你搞坏了。<br>";
			}
			else
			{
				$log .= "你触发了{$trperfix}陷阱<span class=\"yellow\">$itm0</span>，不过你成功地回避了陷阱。<br>";
			}		
			$itm0 = $itmk0 = $itmsk0 = '';
			$itme0 = $itms0 = 0;
			$mode = 'command';
			return;
		}
	}
}

function itemfind(&$data=NULL) {
	//global $mode,$log,$itm0,$itmk0,$itms0,$itmsk0;
	//global $club;
	global $mode,$log;

	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);

	if(!$itm0||!$itmk0||!$itms0){
		$log .= '获取物品信息错误！';
		$mode = 'command';
		return;
	}
	if(strpos($itmk0,'TO')===0) {
		trap($data);
	}else{
		if(CURSCRIPT == 'botservice')
		{
			echo "mode=itemfind\n";
			echo "itm0=$itm0\n";
			echo "itms0=$itms0\n";
			echo "itmsk0=$itmsk0\n";
		}
		$mode = 'itemfind';
		return;
	}
}

function itemget(&$data=NULL) 
{
	global $log,$nosta,$mode,$cmd;
	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);
	$log .= "获得了物品<span class=\"yellow\">$itm0</span>。<br>";

	# 拾取诅咒物品时，触发霉运
	if(!empty($itmsk0) && in_array('V',get_itmsk_array($itmsk0)))
	{
		$log .= "<span class=\"grey\">你感觉自己要倒大霉了……</span><br>";
		getclubskill('inf_cursed',$clbpara);
	}

	//PORT
	if(strpos($itmsk0,'^')!==false){
		$keep_flag = false;
		include_once GAME_ROOT . './include/game/itembag.func.php';
		replace_itembag($keep_flag);
		if(!$keep_flag){
			return;
		}
	}
	if(preg_match('/^(WC|WD|WF|Y|B|C|TN|GA|GB|M|V)/',$itmk0) && $itms0 !== $nosta){
		//global $wep,$wepk,$wepe,$weps,$wepsk;
		if($wep == $itm0 && $wepk == $itmk0 && $wepe == $itme0 && $wepsk == $itmsk0){
			$weps += $itms0;
			$log .= "与装备着的武器<span class=\"yellow\">$wep</span>合并了。";
			$itm0 = $itmk0 = $itmsk0 = '';
			$itme0 = $itms0 = 0;
			$mode = 'command';
			return;
		}else{
			for($i = 1;$i <= 6;$i++){
				//global ${'itm'.$i},${'itmk'.$i},${'itme'.$i},${'itms'.$i},${'itmsk'.$i};
				if((${'itms'.$i})&&($itm0 == ${'itm'.$i})&&($itmk0 == ${'itmk'.$i})&&($itme0 == ${'itme'.$i})&&($itmsk0 == ${'itmsk'.$i})){
					${'itms'.$i} += $itms0;
					$log .= "与包裹里的<span class=\"yellow\">$itm0</span>合并了。";
					$itm0 = $itmk0 = $itmsk0 = '';
					$itme0 = $itms0 = 0;
					$mode = 'command';
					return;
				}
			}
		}
	} elseif(preg_match('/^H|^P/',$itmk0) && $itms0 !== $nosta){
		$sameitem = array(); $scnt=0;
		for($i = 1;$i <= 6;$i++){
			global ${'itm'.$i},${'itmk'.$i},${'itme'.$i},${'itms'.$i};
			if(${'itms'.$i}&&($itm0 == ${'itm'.$i})&&($itme0 == ${'itme'.$i})&&(preg_match('/^(H|P)/',${'itmk'.$i}))){
				$sameitem[] = $i; $scnt++;
			}
		}
		if(isset($sameitem[0])){
			if ($data['pass'] == 'bot')
			{
				include_once GAME_ROOT.'./bot/revbot.func.php';
				if(bot_check_getitem($data)) itemadd($data);
				else itemdrop($data);
			}
			else  
			{
				include template('itemmerge0');
				$cmd = ob_get_contents();
				ob_clean();
			}
			return;
		}
	}

	itemadd($data);
	return;
}


function itemdrop($item,&$data=NULL) {
	global $db,$tablepre,$log,$mode;

	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);

	if(strpos($item,'itm')===false)
	{
		$log .= '只能丢弃包裹内的道具！<br>';
		$mode = 'command';
		return;
	}
	/*if($item == 'wep'){
		global $wep,$wepk,$wepe,$weps,$wepsk;
		$itm = & $wep;
		$itmk = & $wepk;
		$itme = & $wepe;
		$itms = & $weps;
		$itmsk = & $wepsk;
	} elseif(strpos($item,'ar') === 0) {
		$itmn = substr($item,2,1);
		global ${'ar'.$itmn},${'ar'.$itmn.'k'},${'ar'.$itmn.'e'},${'ar'.$itmn.'s'},${'ar'.$itmn.'sk'};
		$itm = & ${'ar'.$itmn};
		$itmk = & ${'ar'.$itmn.'k'};
		$itme = & ${'ar'.$itmn.'e'};
		$itms = & ${'ar'.$itmn.'s'};
		$itmsk = & ${'ar'.$itmn.'sk'};

	} else*/
	
	if(strpos($item,'itm') === 0) {
		$itmn = substr($item,3,1);
		//global ${'itm'.$itmn},${'itmk'.$itmn},${'itme'.$itmn},${'itms'.$itmn},${'itmsk'.$itmn};
		$itm = & ${'itm'.$itmn};
		$itmk = & ${'itmk'.$itmn};
		$itme = & ${'itme'.$itmn};
		$itms = & ${'itms'.$itmn};
		$itmsk = & ${'itmsk'.$itmn};
	}
	//PORT
	if(strpos($itmsk,'^')!==false){
		$dflag=true;
		for($i=1;$i<=6;$i++){
			//global ${'itm'.$i},${'itmk'.$i},${'itme'.$i},${'itms'.$i},${'itmsk'.$i};
			if(strpos(${'itmsk'.$i},'^')!==false && ${'itms'.$i} && ${'itme'.$i}){
				$dflag=false;
				break;
			}
		}
		//global $arbsk,$arbs,$arbe;
		if(strpos($arbsk,'^')!==false  && $arbs && $arbe){
			$dflag=false;
		}
		if ($dflag){
			include_once GAME_ROOT . './include/game/itembag.func.php';
			drop_itembag();
		}
	}
	if(($itmk=='XX')||(($itmk=='XY'))){
		$log .= '该物品不能丢弃。<br>';
		$mode = 'command';
		return;
	}
	# 诅咒装备不能被丢弃
	if(in_array('V',get_itmsk_array($itmsk)))
	{
		$log .= "你丢弃了……<br>你忽然忘记自己原本想干什么了。<br>";
		$mode = 'command';
		return;
	}
	if(!$itms||!$itmk||$itmk=='WN'||$itmk=='DN'){
		$log .= '该物品不存在！<br>';
		$mode = 'command';
		return;
	}
	if(strpos($itmsk,'v')!==false)
	{
		$log .= "{$itm}在地上化作点点碎片，随风消逝了。<br>";
		$log .= "你摧毁了<span class=\"red\">$itm</span>。<br>";
	}
	else
	{
		$db->query("INSERT INTO {$tablepre}mapitem (itm, itmk, itme, itms, itmsk ,pls) VALUES ('$itm', '$itmk', '$itme', '$itms', '$itmsk', '$pls')");
		$drop_iid = $db->insert_id();
		$log .= "你丢弃了<span class=\"red\">$itm</span>。<br>";
		check_add_searchmemory($drop_iid,'itm',$itm,$data);
	}
	if($item == 'wep'){
		$itm = '拳头';
		$itmsk = '';
		$itmk = 'WN';
		$itme = 0;
		$itms = $nosta;
	} else {
		$itm = $itmk = $itmsk = '';
		$itme = $itms = 0;
	}
	$mode = 'command';
	return;
}

function itemoff($item){
	global $log,$mode,$cmd,$itm0,$itmk0,$itme0,$itms0,$itmsk0,$nosta,$pdata;

	if($item == 'wep'){
		global $wep,$wepk,$wepe,$weps,$wepsk;
		$itm = & $wep;
		$itmk = & $wepk;
		$itme = & $wepe;
		$itms = & $weps;
		$itmsk = & $wepsk;
	} elseif(strpos($item,'ar') === 0) {
		$itmn = substr($item,2,1);
		global ${'ar'.$itmn},${'ar'.$itmn.'k'},${'ar'.$itmn.'e'},${'ar'.$itmn.'s'},${'ar'.$itmn.'sk'};
		$itm = & ${'ar'.$itmn};
		$itmk = & ${'ar'.$itmn.'k'};
		$itme = & ${'ar'.$itmn.'e'};
		$itms = & ${'ar'.$itmn.'s'};
		$itmsk = & ${'ar'.$itmn.'sk'};
	}
	if(!$itms||!$itmk||$itmk=='WN'||$itmk=='DN'){
		$log .= '该物品不存在！<br>';
		$mode = 'command';
		return;
	}
	if(($itmk=='XX')||(($itmk=='XY'))){
		$log .= '该物品不能卸下。<br>';
		$mode = 'command';
		return;
	}
	# 诅咒装备不能主动卸下
	if(in_array('V',get_itmsk_array($itmsk)))
	{
		$log .= "你尝试着卸下{$itm}……但它就像长在了你身上一样，纹丝不动！<br>";
		$mode = 'command';
		return;
	}

	//卸下装备时，进行单次套装检测
	reload_single_set_item($pdata,$item,$itm);

	$log .= "你卸下了装备<span class=\"yellow\">$itm</span>。<br>";

	$itm0 = $itm;
	$itmk0 = $itmk;
	$itme0 = $itme;
	$itms0 = $itms;
	$itmsk0 = $itmsk;
	
	if($item == 'wep'){
	$itm = '拳头';
	$itmsk = '';
	$itmk = 'WN';
	$itme = 0;
	$itms = $nosta;
	} else {
	$itm = $itmk = $itmsk = '';
	$itme = $itms = 0;
	}
	itemget();
	return;
}

function itemadd(&$data=NULL)
{
	global $log,$mode,$cmd;

	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);

	if(!$itms0){
		$log .= '你没有捡取物品。<br>';
		$mode = 'command';
		return;
	}
	for($i = 1;$i <= 6;$i++){
		//global ${'itm'.$i},${'itmk'.$i},${'itme'.$i},${'itms'.$i},${'itmsk'.$i};
		if(!${'itms'.$i}){
			$log .= "将<span class=\"yellow\">$itm0</span>放入包裹。<br>";
			${'itm'.$i} = $itm0;
			${'itmk'.$i} = $itmk0;
			${'itme'.$i} = $itme0;
			${'itms'.$i} = $itms0;
			${'itmsk'.$i} = $itmsk0;
			$itm0 = $itmk0 = $itmsk = '';
			$itme0 = $itms0 = 0;
			$mode = 'command';
			return;
		}
	}
	if (CURSCRIPT != 'botservice')
	{
		//$log .= '你的包裹已经满了。想要丢掉哪个物品？<br>';
		include template('itemdrop0');
		$cmd = ob_get_contents();
		ob_clean();
	}
	else  echo "mode=itemdrop0\n";
//	$cmd .= '<input type="hidden" name="mode" value="itemmain"><br><input type="radio" name="command" id="dropitm0" value="dropitm0" checked><a onclick=sl("dropitm0"); href="javascript:void(0);" >'."$itm0/$itme0/$itms0".'</a><br><br>';
//
//	for($i = 1;$i <= 6;$i++){
//		$cmd .= '<input type="radio" name="command" id="swapitm'.$i.'" value="swapitm'.$i.'"><a onclick=sl("swapitm'.$i.'"); href="javascript:void(0);" >'."${'itm'.$i}/${'itme'.$i}/${'itms'.$i}".'</a><br>';
//	}
	return;
}

function itemmerge($itn1,$itn2){
	global $log,$mode;
	
	if($itn1 == $itn2) {
		$log .= '需要选择两个物品才能进行合并！';
		$mode = 'itemmerge';
		return;
	}
	
	global $nosta,${'itm'.$itn1},${'itmk'.$itn1},${'itme'.$itn1},${'itms'.$itn1},${'itmsk'.$itn1},${'itm'.$itn2},${'itmk'.$itn2},${'itme'.$itn2},${'itms'.$itn2},${'itmsk'.$itn2};
	
	$it1 = & ${'itm'.$itn1};
	$itk1 = & ${'itmk'.$itn1};
	$ite1 = & ${'itme'.$itn1};
	$its1 = & ${'itms'.$itn1};
	$itsk1 = & ${'itmsk'.$itn1};
	$it2 = & ${'itm'.$itn2};
	$itk2 = & ${'itmk'.$itn2};
	$ite2 = & ${'itme'.$itn2};
	$its2 = & ${'itms'.$itn2};
	$itsk2 = & ${'itmsk'.$itn2};
	
	if(!$its1 || !$its2) {
		$log .= '请选择正确的物品进行合并！';
		$mode = 'itemmerge';
		return;
	}
	
	if($its1==$nosta || $its2==$nosta) {
		$log .= '耐久是无限的物品不能合并！';
		$mode = 'itemmerge';
		return;
	}

	if(($it1 == $it2)&&($ite1 == $ite2)) {
		if(($itk1==$itk2)&&($itsk1==$itsk2)&&preg_match('/^(WC|WD|WF|Y|B|C|TN|GA|GB|V|M)/',$itk1)) {
			$its2 += $its1;
			$it1 = $itk1 = $itsk1 = '';
			$ite1 = $its1 = 0;
			$log .= "你合并了<span class=\"yellow\">$it2</span>。";
			$mode = 'command';
			return;
		} elseif(preg_match('/^(H|P)/',$itk1)&&preg_match('/^(H|P)/',$itk2)) {
			if((strpos($itk1,'P') === 0)||(strpos($itk1,'P') === 0)){
				$p1 = substr($itk1,2);
				$p2 = substr($itk2,2);
				$k = substr($itk1,1,1);
				if($p2 < $p1){ $p2 = $p1;};
				$itk2 = "P$k$p2";
				if($itsk1 !== ''){
					$itsk2=$itsk1;
					}
			}
			$its2 += $its1;
			$it1 = $itk1 = $itsk1 = '';
			$ite1 = $its1 = 0;
			
			$log .= "你合并了 <span class=\"yellow\">$it2</span>。";
			$mode = 'command';
			return;
		} elseif($itk1!=$itk2||$itsk1!=$itsk2) {
			$log .= "<span class=\"yellow\">$it1</span>与<span class=\"yellow\">$it2</span>不是同类型同属性物品，不能合并！";
			$mode = 'itemmerge';
		} else{
			$log .= "<span class=\"yellow\">$it1</span>与<span class=\"yellow\">$it2</span>完全是两个东西，想合并也不可能啊……";
			$mode = 'itemmerge';
		}
	} else {
		$log .= "<span class=\"yellow\">$it1</span>与<span class=\"yellow\">$it2</span>不是同名同效果物品，不能合并！";
		$mode = 'itemmerge';
	}

	if(!$itn1 || !$itn2) {
		itemadd();
	}

	//$mode = 'command';
	return;
}
/*$syncn=$synck=$synce=$syncs=$syncsk=Array();
function itemmix($mlist, $itemselect=-1) {
	global $log,$mode,$gamecfg,$name,$nosta,$gd,$name,$nick;
	global $itm1,$itm2,$itm3,$itm4,$itm5,$itm6,$itms1,$itms2,$itms3,$itms4,$itms5,$itms6,$itme1,$itme2,$itme3,$itme4,$itme5,$itme6,$club,$clbpara,$wd;
	global $itmk1,$itmk2,$itmk3,$itmk4,$itmk5,$itmk6,$itmsk1,$itmsk2,$itmsk3,$itmsk4,$itmsk5,$itmsk6;
	global $syncn,$synck,$synce,$syncs,$syncsk,$sync,$reqname,$star;
	global $cmd;
	$mlist2 = array_unique($mlist);	
	if(count($mlist) != count($mlist2)) {
		$log .= '相同道具不能进行合成！<br>';
		$mode = 'itemmix';
		return;
	}
	if(count($mlist) < 2){
		$log .= '至少需要2个道具才能进行合成！';
		$mode = 'itemmix';
		return;
	}

	//尝试合成时 合成操作计数+1
	if(empty($clbpara['achvars']['immix'])) $clbpara['achvars']['immix'] = 1;

	$issyncro=false;
	$isntsyn=false;
	$isoverlay=false;
	$isntove=false;
	$star=0;
	$reqname='';
	$tzname='';
	$ostar=0;
	$mixitem = array();
	foreach($mlist as $val){
		if ((strlen(${'itmk'.$val})>=4)&&(strpos(${'itmsk'.$val},'J')!==false)){
				$isoverlay=true;
				break;
			}
	}
	foreach($mlist as $val){
		if(!${'itm'.$val}){
			$log .= '所选择的道具不存在！';
			$mode = 'itemmix';
			return;
		}
		$mitm = ${'itm'.$val};
		foreach(Array('/锋利的/','/电气/','/毒性/','/-改$/') as $value){
			$mitm = preg_replace($value,'',$mitm);
		}
		$mixitem[] = $mitm;
		if (strlen(${'itmk'.$val})<4){
			$isntove=true;
			if ($isoverlay==true){
				$log.="<span class=\"red\">超量失败！所有素材消失！说明写这段代码的人还是一个有良知，明是非的中国人！</span><br>";
				addnews($now,'mixfail',titles_get_desc($nick).' '.$name,$itm0);
				foreach($mlist as $val){
					${'itm'.$val} = ${'itmk'.$val} = ${'itmsk'.$val} = '';
					${'itme'.$val} = ${'itms'.$val} = 0;
					}
				return;
			}
		}else{
			if ($isoverlay==false){
				$ostar=substr(${'itmk'.$val},2,2);
			}
		}
		if ($isoverlay==true){
			if ((strlen(${'itmk'.$val})<4)||((substr(${'itmk'.$val},2,2)!=$ostar)&&($ostar!=0))){
				$log.="<span class=\"red\">超量失败！所有素材消失！说明写这段代码的人还是一个有良知，明是非的中国人！</span><br>";
				addnews($now,'mixfail',titles_get_desc($nick).' '.$name,$itm0);
				foreach($mlist as $val){
					${'itm'.$val} = ${'itmk'.$val} = ${'itmsk'.$val} = '';
					${'itme'.$val} = ${'itms'.$val} = 0;
					}
				return;
			}
			$ostar=substr(${'itmk'.$val},2,2);
			continue;
		}else{
			if ((strlen(${'itmk'.$val})>=4)&&(strpos(${'itmsk'.$val},'J')!==false)){
				if (substr(${'itmk'.$val},2,2)!=$ostar){
					$log.="<span class=\"red\">超量失败！所有素材消失！说明写这段代码的人还是一个有良知，明是非的中国人！</span><br>";
					addnews($now,'mixfail',titles_get_desc($nick).' '.$name,$itm0);
					foreach($mlist as $val){
						${'itm'.$val} = ${'itmk'.$val} = ${'itmsk'.$val} = '';
						${'itme'.$val} = ${'itms'.$val} = 0;
					}
				return;
				}
				$isoverlay=true;
				$ostar=substr(${'itmk'.$val},2,2);
			}
		}
		if ($issyncro==true){
			if ((strlen(${'itmk'.$val})<4)&&($isntsyn==false)){
				$log.="<span class=\"red\">同调失败！所有素材消失！真是大快人心啊！</span><br>";
				addnews($now,'mixfail',titles_get_desc($nick).' '.$name,$itm0);
				foreach($mlist as $val){
					${'itm'.$val} = ${'itmk'.$val} = ${'itmsk'.$val} = '';
					${'itme'.$val} = ${'itms'.$val} = 0;
					}
				return;
			}
			if (strpos(${'itmsk'.$val},'s')!==false){
				$log.="<span class=\"red\">同调失败！所有素材消失！真是大快人心啊！</span><br>";
				addnews($now,'mixfail',titles_get_desc($nick).' '.$name,$itm0);
				foreach($mlist as $val){
					${'itm'.$val} = ${'itmk'.$val} = ${'itmsk'.$val} = '';
					${'itme'.$val} = ${'itms'.$val} = 0;
					}
				return;
			}
			$star+=substr(${'itmk'.$val},2,2);
			$reqname.=${'itm'.$val}.'_';
		}else{
			if (strpos(${'itmsk'.$val},'s')!==false){
				if ($isntsyn==false){
					$issyncro=true;
					$star+=substr(${'itmk'.$val},2,2);
					$tzname=${'itm'.$val};
					continue;
				}else{
					$log.="<span class=\"red\">同调失败！所有素材消失！真是大快人心啊！</span><br>";
					addnews($now,'mixfail',titles_get_desc($nick).' '.$name,$itm0);
					foreach($mlist as $val){
						${'itm'.$val} = ${'itmk'.$val} = ${'itmsk'.$val} = '';
						${'itme'.$val} = ${'itms'.$val} = 0;
					}
					return;
				}
			}
			if (strlen(${'itmk'.$val})>=4){
				$star+=substr(${'itmk'.$val},2,2);
				$reqname.=${'itm'.$val}.'_';
			}else{
				$isntsyn=true;
			}
		}
	}
	//overlay
	if ($isoverlay==true){
		$file1 = config('overlay',$gamecfg);
		$olist = openfile($file1);
		$num = count($olist)-1;
		$nnum = sizeof($mixitem);
		$sync=-1;
		$syncn=$synck=$synce=$syncs=$syncsk=Array();
		for ($i=0;$i<=$num;$i++){
			$t = explode(',',$olist[$i]);
			if (($t[5]!=$ostar)||($t[6]!=$nnum)) {continue;}
			$sync++;
			$syncn[$sync]=$t[0];
			$synck[$sync]=$t[1];
			$synce[$sync]=$t[2];
			$syncs[$sync]=$t[3];
			$syncsk[$sync]=$t[4];
		}
		if ($sync==-1){
			$log.="<span class=\"red\">超量失败！所有素材消失！说明写这段代码的人还是一个有良知，明是非的中国人！</span><br>";
			foreach($mlist as $val){
				${'itm'.$val} = ${'itmk'.$val} = ${'itmsk'.$val} = '';
				${'itme'.$val} = ${'itms'.$val} = 0;
			}
			addnews($now,'mixfail',titles_get_desc($nick).' '.$name,$itm0);
			return;
		}
		if ($itemselect==-1)
		{
			$mask=0;
			foreach($mlist as $k)
				if (1<=$k && $k<=6)
					$mask|=(1<<((int)$k-1));
					
			$cmd.='<input type="hidden" id="mode" name="mode" value="itemmain">';
			$cmd.='<input type="hidden" id="command" name="command" value="itemmix">';
			$cmd.='<input type="hidden" id="mixmask" name="mixmask" value="'.$mask.'">';
			$cmd.='<input type="hidden" id="itemselect" name="itemselect" value="999">';
			$cmd.= "请选择超量结果<br><br>";
			for($i=0;$i<=$sync;$i++){
				$tn=$syncn[$i];
				$tk=$syncn[$i].'_'.$synck[$i].'_'.$synce[$i].'_'.$syncs[$i].'_'.$syncsk[$i].'_-1_';
				$cmd.="<input type=\"button\" class=\"cmdbutton\"  style=\"width:200\" value=\"".$tn."\" onclick=\"$('itemselect').value='".$i."';postCmd('gamecmd','command.php');this.disabled=true;\">";
			}
			$cmd.="<input type=\"button\" class=\"cmdbutton\"  style=\"width:200\" value=\"返回\" onclick=\"postCmd('gamecmd','command.php');this.disabled=true;\">";
		}
		else
		{
			$i=(int)$itemselect;
			if ($i<0 || $i>$sync)
			{
				$mode='command'; return; 
			}
			foreach($mlist as $val)
			{
				${'itm'.$val} = ${'itmk'.$val} = ${'itmsk'.$val} = '';
				${'itme'.$val} = ${'itms'.$val} = 0;
			}
			$tk=$syncn[$i].'_'.$synck[$i].'_'.$synce[$i].'_'.$syncs[$i].'_'.$syncsk[$i].'_-1_';
			include_once GAME_ROOT.'./include/game/special.func.php';
			syncro($tk);
			$mode='command';
		}
		return;
	}
	//syncro
	if (($issyncro==true)&&($isntsyn==false)){
		$sync=-1;
		$syncn=$synck=$synce=$syncs=$syncsk=Array();
		$file = config('synitem',$gamecfg);
		$slist = openfile($file);
		$num = count($slist)-1;
		for ($i=0;$i<=$num;$i++){
			$t = explode(',',$slist[$i]);
			$rnum = count($t)-8;
			$tn=$t[0];$tk=$t[1];$te=$t[2];$ts=$t[3];$tsk=$t[4];$tstar=$t[5];
			if ($star!=$tstar) {continue;}
			if (($t[6]!='-1')&&(strpos($tzname,$t[6])===false)) {continue;}
			$isok=true;
			for ($j=1;$j<=$rnum;$j++){
				if (($t[7+$j-1]!='-1')&&(strpos($reqname,$t[7+$j-1])===false)) {$isok=false;break;}
			}
			if ($isok==false) {continue;}
			$sync++;
			$syncn[$sync]=$tn;$synck[$sync]=$tk;$synce[$sync]=$te;$syncs[$sync]=$ts;$syncsk[$sync]=$tsk;
		}
		if ($sync==-1){
			$log.="<span class=\"red\">同调失败！所有素材消失！真是大快人心啊！</span><br>";
			foreach($mlist as $val){
				${'itm'.$val} = ${'itmk'.$val} = ${'itmsk'.$val} = '';
				${'itme'.$val} = ${'itms'.$val} = 0;
			}
			addnews($now,'mixfail',titles_get_desc($nick).' '.$name,$itm0);
			return;
		}
		if ($itemselect==-1)
		{
			$mask=0;
			foreach($mlist as $k)
				if (1<=$k && $k<=6)
					$mask|=(1<<((int)$k-1));
			
			$cmd.='<input type="hidden" id="mode" name="mode" value="itemmain">';
			$cmd.='<input type="hidden" id="command" name="command" value="itemmix">';
			$cmd.='<input type="hidden" id="mixmask" name="mixmask" value="'.$mask.'">';
			$cmd.='<input type="hidden" id="itemselect" name="itemselect" value="999">';
			$cmd.= "请选择同调结果<br><br>";
			for($i=0;$i<=$sync;$i++){
				$tn=$syncn[$i];
				$tk=$syncn[$i].'_'.$synck[$i].'_'.$synce[$i].'_'.$syncs[$i].'_'.$syncsk[$i].'_'.$star.'_';
				$cmd.="<input type=\"button\" class=\"cmdbutton\"  style=\"width:200\" value=\"".$tn."\" onclick=\"$('itemselect').value='".$i."';postCmd('gamecmd','command.php');this.disabled=true;\">";
			}
			$cmd.="<input type=\"button\" class=\"cmdbutton\"  style=\"width:200\" value=\"返回\" onclick=\"postCmd('gamecmd','command.php');this.disabled=true;\">";
		}
		else
		{
			$i=(int)$itemselect;
			if ($i<0 || $i>$sync)
			{
				$mode='command'; return; 
			}
			foreach($mlist as $val)
			{
				${'itm'.$val} = ${'itmk'.$val} = ${'itmsk'.$val} = '';
				${'itme'.$val} = ${'itms'.$val} = 0;
			}
			$tk=$syncn[$i].'_'.$synck[$i].'_'.$synce[$i].'_'.$syncs[$i].'_'.$syncsk[$i].'_1_';
			include_once GAME_ROOT.'./include/game/special.func.php';
			syncro($tk);
			$mode='command';
		}
		return;
	}

	//include_once config('mixitem',$gamecfg);
	//include_once config('vnmixitem',$gamecfg);
	$mixflag = false;
	$mixinfo = get_mixinfo();
	//if(!empty($vn_mixinfo)) $mixinfo = array_merge($mixinfo,$vn_mixinfo);
	foreach($mixinfo as $minfo) {
		if(!array_diff($mixitem,$minfo['stuff']) && !array_diff($minfo['stuff'],$mixitem) && count($mixitem) == count($minfo['stuff'])){ 
			$mixflag = true;
			break;			
		}
	}

	$itmstr = '';
	foreach($mixitem as $val){
		$itmstr .= $val.' ';
	}
	$itmstr = substr($itmstr,0,-1);
		
	if(!$mixflag || $club == 20) {
		//Added an additional check here so even Club20 somehow entered itemmix, nothing can be made.
		$log .= "<span class=\"yellow\">$itmstr</span>不能合成！<br>";
		$mode = 'itemmix';
	} else {
		foreach($mlist as $val){
			itemreduce('itm'.$val);
		}

		global $itm0,$itmk0,$itme0,$itms0,$itmsk0,$now;

		list($itm0,$itmk0,$itme0,$itms0,$itmsk0) = $minfo['result'];
		$log .= "<span class=\"yellow\">$itmstr</span>合成了<span class=\"yellow\">{$minfo['result'][0]}</span><br>";
		//var_dump($minfo['result'][0]);
		addnews($now,'itemmix',titles_get_desc($nick).' '.$name,$itm0);
		//if($club == 5) { $wd += 2; }
		//else { $wd+=1; }
		$wd+=1;
		if((strpos($itmk0,'WD') === 0)&&($club == 5)&&($itms0 !== $nosta)){ $itms0 = ceil($itms0*1.5); }
		elseif((strpos($itmk0,'H') === 0)&&($club == 16)&&($itms0 !== $nosta)){ $itms0 = ceil($itms0*2); }
		elseif(($itmk0 == 'EE' || $itmk0 == 'ER') && ($club == 7)){ $itme0 *= 5; }
		//elseif(($itm0 == '移动PC' || $itm0 == '广域生命探测器') && ($club == 7)){ $itme0 *= 3; }
		
		//检查成就
		include_once GAME_ROOT.'./include/game/achievement.func.php';
		check_mixitem_achievement_rev($name,$itm0);
		
		itemget();
	}
	return;
}*/
function itemreduce($item,$mode=0){ //只限合成使用！！
	global $log;
	if(strpos($item,'itm') === 0) {
		$itmn = substr($item,3,1);
		global ${'itm'.$itmn},${'itmk'.$itmn},${'itme'.$itmn},${'itms'.$itmn},${'itmsk'.$itmn};
		$itm = & ${'itm'.$itmn};
		$itmk = & ${'itmk'.$itmn};
		$itme = & ${'itme'.$itmn};
		$itms = & ${'itms'.$itmn};
		$itmsk = & ${'itmsk'.$itmn};
	} else {
		return;
	}

	if(!$itms) { return; }
	# 素材类道具作合成素材时只消耗耐久
	if(preg_match('/^(Y|B|C|X|TN|GB|H|P|V|M)/',$itmk))
	{
		# Added one additional check to deal with infinite stamina item - destroy it when used in mix.
		if($itms == '∞'){
			$itms = 0;
			$log .= "<span class=\"red\">$itm</span>消失了……它已被";
			$itm = $itmk = $itmsk = '';
			$itme = $itms = 0;
		}else{
		$itms--;}
	}
	# 带有工具属性的道具作合成素材时，不会消耗
	elseif(in_array('🧰',get_itmsk_array($itmsk)))
	{
		$itms = $itms;
	}
	else{$itms=0;}
	if($itms <= 0) {
		$itms = 0;
		$log .= "<span class=\"red\">$itm</span>用光了。<br>";
		$itm = $itmk = $itmsk = '';
		$itme = $itms = 0;
	}
	return;
}

function itemmove($from,$to){
	global $log;
	if(!$from || !is_numeric($from) || !$to || !is_numeric($to) || $from < 1 || $to < 1 || $from > 6 || $to > 6){
		$log .= '错误的包裹位置参数。<br>';
		return;
	}	elseif($from == $to){
		$log .= '同一物品无法互换。<br>';
		return;
	}
	global ${'itm'.$from},${'itmk'.$from},${'itme'.$from},${'itms'.$from},${'itmsk'.$from},${'itm'.$to},${'itmk'.$to},${'itme'.$to},${'itms'.$to},${'itmsk'.$to};
	$f = & ${'itm'.$from};
	$fk = & ${'itmk'.$from};
	$fe = & ${'itme'.$from};
	$fs = & ${'itms'.$from};
	$fsk = & ${'itmsk'.$from};
	$t = & ${'itm'.$to};
	$tk = & ${'itmk'.$to};
	$te = & ${'itme'.$to};
	$ts = & ${'itms'.$to};
	$tsk = & ${'itmsk'.$to};
	if(!$fs){
		$log .= '错误的道具参数。<br>';
		return;
	}
	if(!$ts){
		$log .= "将<span class=\"yellow\">{$f}</span>移动到了<span class=\"yellow\">包裹{$to}</span>。<br>";
		$t = $f;
		$tk = $fk;
		$te = $fe;
		$ts = $fs;
		$tsk = $fsk;
		$f = $fk = $fsk = '';
		$fe = $fs = 0;
		
	}else {
		$log .= "将<span class=\"yellow\">{$f}</span>与<span class=\"yellow\">{$t}</span>互换了位置。<br>";
		$temp = $t;
		$tempk = $tk;
		$tempe = $te;
		$temps = $ts;
		$tempsk = $tsk;
		$t = $f;
		$tk = $fk;
		$te = $fe;
		$ts = $fs;
		$tsk = $fsk;
		$f = $temp;
		$fk = $tempk;
		$fe = $tempe;
		$fs = $temps;
		$fsk = $tempsk;
		
	}
	return;
}


function itembuy($item,$shop,$bnum=1,&$data=NULL) 
{
	global $log,$mode,$now,$areanum,$areaadd,$shops;
	global $db,$tablepre;

	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);

	$result=$db->query("SELECT * FROM {$tablepre}shopitem WHERE sid = '$item'");
	$iteminfo = $db->fetch_array($result);
	$price = $club == 11 ? round($iteminfo['price']*0.75) : $iteminfo['price'];
	//$file = GAME_ROOT."./gamedata/shopitem/{$shop}shopitem.php";
	//$itemlist = openfile($file);
	//$iteminfo = $itemlist[$item];
	if(!$iteminfo) {
		$log .= '要购买的道具不存在！<br><br>';
		$mode = 'command';
		return;
	}

//	if(!in_array($pls,$shops)) {
//		$log .= '你所在的位置没有商店。<br>';
//		return;
//	}
	$bnum = (int)$bnum;
	//list($num,$price,$iname,$ikind,$ieff,$ista,$isk) = explode(',',$iteminfo);
	if($iteminfo['num'] <= 0) {
		$log .= '此物品已经售空！<br><br>';
		$mode = 'command';
		return;
	} elseif($bnum<=0) {
		$log .= '购买数量必须为大于0的整数。<br><br>';
		$mode = 'command';
		return;
	} elseif($bnum>$iteminfo['num']) {
		$log .= '购买数量必须小于存货数量。<br><br>';
		$mode = 'command';
		return;
	} elseif($money < $price*$bnum) {
		$log .= '你的钱不够，不能购买此物品！<br><br>';
		$mode = 'command';
		return;
	} elseif(!preg_match('/^(WC|WD|WF|Y|B|C|TN|GB|H|V|M)/',$iteminfo['itmk'])&&$bnum>1) {
		$log .= '此物品一次只能购买一个。<br><br>';
		$mode = 'command';
		return;
	}elseif($iteminfo['area']> $areanum/$areaadd){
		$log .= '此物品尚未开放出售！<br><br>';
		$mode = 'command';
		return;
	}
//	if (strpos($ikind,'_') !== false) {
//		list($ik,$it) = explode('_',$ikind);
//		if($areanum < $it*$areaadd) {
//			$log .= '此物品尚未开放出售！<br>';
//			return;
//		}
//	} else {
//		$ik = $ikind;
//	}
	$inum = $iteminfo['num']-$bnum;
	$sid = $iteminfo['sid'];
	$db->query("UPDATE {$tablepre}shopitem SET num = '$inum' WHERE sid = '$sid'");
//	$num-=$bnum;
	$money -= $price*$bnum;
//	$itemlist[$item] = "$num,$price,$iname,$ikind,$ieff,$ista,$isk,\n";
//	writeover($file,implode('',$itemlist));
	addnews($now,'itembuy',$name,$iteminfo['item']);
	$log .= "购买成功。";
	$itm0 = $iteminfo['item'];
	$itmk0 = $iteminfo['itmk'];
	$itme0 = $iteminfo['itme'];
	$itms0 = $iteminfo['itms']*$bnum;
	$itmsk0 = $iteminfo['itmsk'];

	itemget($data);	
	return;
}





function getcorpse($item,&$data=NULL)
{
	global $db,$tablepre,$log,$mode,$now;
	//global $itm0,$itmk0,$itme0,$itms0,$itmsk0,$money,$pls,$action,$rp,$name;
	//global $club,$allow_destory_corpse,$no_destory_corpse_type,$rpup_destory_corpse;
	global $allow_destory_corpse,$no_destory_corpse_type,$rpup_destory_corpse;

	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);

	//$corpseid = strpos($action,'corpse')===0 ? str_replace('corpse','',$action) : str_replace('pacorpse','',$action);
	$corpseid = $bid;
	if(!$corpseid || ($action != 'corpse' && $action != 'pacorpse')){
		$log .= '<span class="yellow">你没有遇到尸体，或已经离开现场！</span><br>';
		$action = ''; $bid = 0;
		$mode = 'command';
		return;
	}

	//$result = $db->query("SELECT * FROM {$tablepre}players WHERE pid='$corpseid'");
	$edata = fetch_playerdata_by_pid($corpseid);

	if(!$edata){
		$log .= '对方不存在！<br>';
		$action = ''; $bid = 0;
		$mode = 'command';
		return;
	}

	//$edata = $db->fetch_array($result);
	
	if($edata['hp']>0) {
		$log .= '对方尚未死亡！<br>';
		$action = ''; $bid = 0;
		$mode = 'command';
		return;
	} elseif($edata['pls'] != $pls) {
		$log .= '对方跟你不在同一个地图！<br>';
		$action = ''; $bid = 0;
		$mode = 'command';
		return;
	}

	if($item == 'destory')
	{
		if(!$allow_destory_corpse || in_array($edata['type'],$no_destory_corpse_type))
		{
			$log.="你还想对这具可怜的尸体干什么？麻烦给死者一点基本的尊重！<br>";
			$action = ''; $bid = 0;
			$mode = 'command';
			return;
		}

		$log.="你销毁了{$edata['name']}的尸体。<br>但这一切值得吗……？<br>";
		# 销毁尸体rp结算
		$rp_up = diceroll($rpup_destory_corpse);
		include_once GAME_ROOT.'./include/state.func.php';
		rpup_rev($data,$rp_up);

		addnews($now,'cdestroy',$name,$edata['name']);
		destory_corpse($edata);
		$action = ''; $bid = 0;
		$mode = 'command';
		return;
	}
	elseif($item == 'element_split' || $item == 'c20_zombie')
	{
		if($club != 20)
		{
			$log.="你还想对这具可怜的尸体干什么？麻烦给死者一点基本的尊重！<br>";
			$action = ''; $bid = 0;
			$mode = 'command';
			return;
		}
		include_once GAME_ROOT.'./include/game/elementmix.func.php';
		split_corpse_to_elements($edata,$item);
		$action = ''; $bid = 0;
		$mode = 'command';
		return;
	}
	elseif($item == 'back')
	{
		//没有从尸体上捡取道具时，保留视野
		check_add_searchmemory($edata['pid'],'corpse',$edata['name'],$data);
		$action = ''; $bid = 0;
		$mode = 'command';
		return;
	}

	if($item == 'cstick')
	{
		include_once GAME_ROOT.'./include/game/revclubskills_extra.func.php';
		skill_tl_cstick_act($edata);
		$action = ''; $bid = 0;
		$mode = 'command';
		return;
	}
	
	if($item == 'pickpocket')
	{
		//用视野保存一下，可以吗？
		check_add_searchmemory($edata['pid'],'corpse',$edata['name'],$data);
		$log = '';
		$mode = 'sp_pickpocket';
		return;
	}

	if($item == 'loot_depot')
	{
		//global $name,$type;
		include_once GAME_ROOT.'./include/game/depot.func.php';
		loot_depot($name,$type,$edata['name'],$edata['type']);
		$action = ''; $bid = 0;
		$mode = 'command';
		return;
	}
	
	if($item == 'wep' || $item == 'wep2') {
		$itm0 = $edata[$item];
		$itmk0 = $edata[$item.'k'];
		$itme0 = $edata[$item.'e'];
		$itms0 = $edata[$item.'s'];
		$itmsk0 = $edata[$item.'sk'];
		$edata[$item] = $edata[$item.'k'] = $edata[$item.'sk'] = '';
		$edata[$item.'e'] = $edata[$item.'s'] = 0;  
	} elseif(strpos($item,'ar') === 0) {
		$itm0 = $edata[$item];
		$itmk0 = $edata[$item.'k'];
		$itme0 = $edata[$item.'e'];
		$itms0 = $edata[$item.'s'];
		$itmsk0 = $edata[$item.'sk'];
		$edata[$item] = $edata[$item.'k'] = $edata[$item.'sk'] = '';
		$edata[$item.'e'] = $edata[$item.'s'] = 0;  
	} elseif(strpos($item,'itm') === 0) {
		$itmn = substr($item,3,1);
		$itm0 = $edata['itm'.$itmn];
		$itmk0 = $edata['itmk'.$itmn];
		$itme0 = $edata['itme'.$itmn];
		$itms0 = $edata['itms'.$itmn];
		$itmsk0 = $edata['itmsk'.$itmn];
		$edata['itm'.$itmn] = $edata['itmk'.$itmn] = $edata['itmsk'.$itmn] = '';
		$edata['itme'.$itmn] = $edata['itms'.$itmn] = 0;  
	} elseif($item == 'money') {
		$money += $edata['money'];
		$log .= '获得了金钱 <span class="yellow">'.$edata['money'].'</span>。<br>';
		$edata['money'] = 0;
		player_save($edata);
		$action = ''; $bid = 0;
		$mode = 'command';
		return;
	} else {
		$action = ''; $bid = 0;
		return;
	}

	player_save($edata);

	if(!$itms0||!$itmk0||$itmk0=='WN'||$itmk0=='DN') {
		$log .= '该物品不存在！';
	} else {
		itemget($data);
	}
	$action = ''; $bid = 0;
	$mode = 'command';
	return;
}

# 切换副武器
function change_subwep($s=2,&$data=NULL)
{
    global $log,$nosta,$nowep;

    if(!isset($data))
    {
        global $pdata;
        $data = &$pdata;
    }
    extract($data,EXTR_REFS);

    # 初始化主武器名
    $eqp = 'wep';
    # 初始化副武器名
    $seqp = 'wep'.$s;
    $seqpk = $seqp.'k';
    $seqpe = $seqp.'e';
    $seqps = $seqp.'s';
    $seqpsk = $seqp.'sk';
    # 保存副武器数据
    $swep=${$seqp}; $swepk=${$seqpk};
    $swepe=${$seqpe}; $sweps=${$seqps}; $swepsk=${$seqpsk};

    # 切换时，检查主手是否为空
    $no_wepflag = 0;
    if($wepk == 'WN' && $wep == $nowep && empty($wepe) && $weps == $nosta && empty($wepsk))
    {
        $no_wepflag = 1;
    }

    # 切换时，检查副武器槽是否为空
    $no_swepflag = 0;
    if(empty($swep) || ($swepk == 'WN' && $swep == $nowep && empty($swepe) && $sweps == $nosta && empty($swepsk)))
    {
        $swep = $nowep; $swepk = 'WN';
        $swepe = 0; $sweps = $nosta; $swepsk = '';
        $no_swepflag = 1;
    }

    ${$seqp} = ${$eqp}; ${$eqp} = $swep; 
    ${$seqpk} = ${$eqp.'k'}; ${$eqp.'k'} = $swepk;
    ${$seqpe} = ${$eqp.'e'}; ${$eqp.'e'} = $swepe; 
    ${$seqps} = ${$eqp.'s'}; ${$eqp.'s'} = $sweps; 
    ${$seqpsk} = ${$eqp.'sk'}; ${$eqp.'sk'} = $swepsk; 

    $sweplog = '';
    if(!$no_wepflag) $sweplog.="收起了<span class='yellow'>{$wep2}</span>";
    if(!$no_swepflag) 
    {
        if(!empty($sweplog)) $sweplog .= '，';
        $sweplog .="拿出了<span class='yellow'>{$wep}</span>";
    }

    if(!$sweplog) $log .= '你左手交叠右手，大喝一声：“我特殊召唤两个拳头！”<br>';
    else $log .= $sweplog."。<br>";

    return;
}

# 销毁指定装备
function destory_single_equip(&$pa,$equip)
{
	global $log;
	
	$equip_list = get_equip_list();

	if(in_array($equip,$equip_list))
	{
		$pa[$equip] = $pa[$equip.'k'] = $pa[$equip.'sk'] = '';
		$pa[$equip.'e'] = $pa[$equip.'s'] = 0;
		reload_equip_items($pa);
	}
	else 
	{
		$log .= "传入了非法的道具位名。";
		return;
	}
	return;
}

# 销毁指定道具（传入的i必须为1~6范围内的数字）
function destory_single_item(&$pa,$i,$costlog=0)
{
	global $log;

	$item_list = range(0,6);

	if(in_array($i,$item_list))
	{
		if($costlog)
		{
			$log .= "<span class=\"red\">{$pa['itm'.$i]}</span>用光了。<br>";
		}
		$pa['itm'.$i] = $pa['itmk'.$i] = $pa['itmsk'.$i] = '';
		$pa['itme'.$i] = $pa['itms'.$i] = 0;
	}
	else 
	{
		$log .= "传入了非法的道具位名。";
		return;
	}
	return;
}

# 初始化玩家/NPC数据时，检查对应部位是否可装备道具、或是否缺少基础装备
function reload_equip_items(&$pa)
{
	global $nowep,$noarb,$nosta;

	if(empty($pa['wep']) || empty($pa['weps']))
	//if(empty($pa['weps']) && $pa['wep'] !== $nowep)
	{
		$pa['wep'] = $nowep;
		$pa['wepk'] = 'WN';
		$pa['wepe'] = 0;
		$pa['weps'] = $nosta;
		$pa['wepsk'] = '';
	}

	if(empty($pa['arb']) || empty($pa['arbs']))
	{
		$pa['arb'] = $noarb;
		$pa['arbk'] = 'DN';
		$pa['arbe'] = 0;
		$pa['arbs'] = $nosta;
		$pa['arbsk'] = '';
	}
	return;
}

# 初始化玩家/NPC数据时，重载套装效果
function reload_set_items(&$pa)
{
	# 身上登记过套装效果，先重置
	if(!empty($pa['clbpara']['setitems']))
	{
		# TODO：失去对应的套装效果
		$pa['clbpara']['setitems'] = Array();
	}
	$set_items = get_set_items();
	$equip_list = get_equip_list();
	# 遍历身上的装备信息 检查是否为套装的组成部分
	foreach($equip_list as $eqp)
	{
		if(!empty($pa[$eqp.'s']) && isset($set_items[$eqp][$pa[$eqp]]))
		{
			$sid = $set_items[$eqp][$pa[$eqp]];
			$pa['clbpara']['setitems'][$sid] += 1;
		}
		# 身上存在诅咒装备时，触发霉运效果
		if(!empty($pa[$eqp.'sk']) && in_array('V',get_itmsk_array($pa[$eqp.'sk'])))
		{
			$cursed_flag = 1;
		}
	}
	# 身上存在诅咒效果
	if(isset($cursed_flag))
	{
		if(check_skill_unlock('inf_cursed',$pa)) getclubskill('inf_cursed',$pa['clbpara']);
	}
	else 
	{
		if(!check_skill_unlock('inf_cursed',$pa)) lostclubskill('inf_cursed',$pa['clbpara']);
	}
	# 身上存在套装效果
	if(!empty($pa['clbpara']['setitems']))
	{
		//获得对应的套装效果

	}
	return;
}

# 装备/替换/破坏装备时，进行单件套装效果变更
# eqp → 装备部位；eqm → 装备名；active 1 → 装备；active 0 → 卸下/损坏
function reload_single_set_item(&$pa,$eqp,$enm,$active=0)
{
	global $log;
	$set_items = get_set_items();
	# 检查装备是否为套装组成部分
	if(isset($set_items[$eqp][$enm]))
	{
		$sid = $set_items[$eqp][$enm];
		$set_items_info = get_set_items_info();
		if($active)
		{
			$pa['clbpara']['setitems'][$sid] += 1;
			$nownums = $pa['clbpara']['setitems'][$sid];
			//获得对应的套装效果
			//$log .= "激活了套装{$set_items_info[$sid]['name']}{$nownums}件套的效果。<br>";
			//检查是否解锁对应套装成就
		}
		else
		{
			$pa['clbpara']['setitems'][$sid] -= 1;
			$nownums = $pa['clbpara']['setitems'][$sid];
			//失去对应的套装效果
			//$log .= "套装{$set_items_info[$sid]['name']}组件数-1，重新激活{$nownums}件套的效果。<br>";
		}
	}
	return;
}


//在包裹内寻找道具进行编辑
function check_item_edit_event($pa,&$pd,$event)
{
	$flag = 0;
	for($i=0;$i<=6;$i++)
	{
		if(!empty($pd['itms'.$i]))
		{
			# 「渗透」效果判定
			if($event == 'c8_infilt')
			{
				if(strpos($pd['itmk'.$i],'H')===0)
				{
					$pd['itmk'.$i] = str_replace("H",'P',$pd['itmk'.$i]);
					$pd['itmsk'.$i] = $pa['pid'];
					$flag = 1;
				}
			}
		}
	}
	return $flag;
}

//武器损耗&消耗计算：force_imp：强制扣除武器效果；check_sk：是否在武器毁坏时重新检查属性数组$pa['ex_keys']
function weapon_loss(&$pa,$hurtvalue,$force_imp=0,$check_sk=0)
{
	global $log,$wepimprate,$nosta;

	//小开不算开 以后再做弹药相关吧
	if($pa['pass'] == 'bot' && ($pa['wep_kind'] == 'G' || $pa['wep_kind'] == 'J')) $hurtvalue = 0;

	if($hurtvalue && $pa['wep_kind'] != 'N')
	{
		$wep_loss_flag = 0;
		//获取武器损耗类型
		$wep_imp = $wepimprate[$pa['wep_kind']];
		//损耗型武器
		if($wep_imp > 0 || $force_imp)
		{
			if($pa['weps'] == $nosta || $force_imp)
			{
				$pa['wepe'] = max(0,$pa['wepe']-$hurtvalue);
				if(!$pa['type'])
				{
					if($hurtvalue > 0) $log.= "<span class='grey'>{$pa['nm']}的{$pa['wep']}的攻击力下降了{$hurtvalue}。</span><br>";
					else $log.= "<span class='grey'>{$pa['nm']}的{$pa['wep']}的攻击力上升了".abs($hurtvalue)."！……为什么啊？</span><br>";
				}
			}
			else 
			{
				$pa['weps'] = max(0,$pa['weps']-$hurtvalue);
				if(!$pa['type'])
				{
					if($hurtvalue > 0) $log.= "<span class='grey'>{$pa['nm']}的{$pa['wep']}的耐久度下降了{$hurtvalue}。</span><br>";
					else $log.= "<span class='grey'>{$pa['nm']}的{$pa['wep']}的耐久度上升了".abs($hurtvalue)."！……为什么啊？</span><br>";
				}
			}
			if(empty($pa['weps']) || empty($pa['wepe']))
			{
				$log .= "{$pa['nm']}的<span class=\"red\">{$pa['wep']}</span>使用过度，已经损坏，无法再装备了！<br>";
				$wep_loss_flag = 1;
			}
		}
		//消耗型武器
		else 
		{
			if($pa['weps'] != $nosta)
			{
				$pa['weps'] = max(0,$pa['weps']-$hurtvalue);
				if($pa['wep_kind'] == 'C' || $pa['wep_kind'] == 'D' || $pa['wep_kind'] == 'F')
				{
					if(!$pa['type'])
					{
						if($hurtvalue > 0) $log .= "<span class='grey'>{$pa['nm']}用掉了{$hurtvalue}个{$pa['wep']}。</span><br>";
						else $log .= "<span class='grey'>{$pa['wep']}凭空增殖出了".abs($hurtvalue)."个……啊？？</span><br>";
					}
					if(empty($pa['weps']))
					{
						$log .= "{$pa['nm']}的<span class=\"red\">{$pa['wep']}</span>用光了！<br>";
						$wep_loss_flag = 1;
					}
				} 
				elseif($pa['wep_kind'] == 'G' || $pa['wep_kind'] == 'J') 
				{
					if(!$pa['type'])
					{
						if($hurtvalue > 0) $log .= "<span class='grey'>{$pa['nm']}的{$pa['wep']}的弹药数减少了{$hurtvalue}。</span><br>";
						else $log .= "<span class='grey'>{$pa['wep']}的弹药数凭空多出了".abs($hurtvalue)."……啊？？</span><br>";
					}
					if(empty($pa['weps']))
					{
						$log .= "{$pa['nm']}的<span class=\"red\">{$pa['wep']}</span>弹药用光了！<br>";
						$pa['weps'] = $nosta;
					}
				}
				elseif($pa['wep_kind'] == 'B')
				{
					if(!$pa['type'])
					{
						if($hurtvalue > 0) $log .= "<span class='grey'>{$pa['nm']}的{$pa['wep']}用掉了{$hurtvalue}支箭。</span><br>";
						else $log .= "<span class='grey'>{$pa['wep']}的箭矢数凭空多出了".abs($hurtvalue)."……啊？？</span><br>";
					}
					if(empty($pa['weps']))
					{
						$log .= "{$pa['nm']}的<span class=\"red\">{$pa['wep']}</span>的箭矢用光了！<br>";
						$pa['weps'] = $nosta;
						//弓系武器用光箭后刷新一次属性，剔除箭矢带来的属性
						//箭矢用光时抹掉箭矢名
						wep_b_clean_arrow_name($pa['wepk']);
						//箭矢用光时抹掉箭矢带来的属性
						wep_b_clean_arrow_sk($pa['wepsk']);
					}
				}
			}
		}
		if($wep_loss_flag)
		{
			//剔除武器属性
			if($check_sk && !empty($pa['wepsk'])) unset_ex_from_array($pa,get_itmsk_array($pa['wepsk']));

			$pa['wep'] = '拳头'; $pa['wep_kind'] = 'N'; $pa['wepk'] = 'WN';
			$pa['wepe'] = 0; $pa['weps'] = $nosta; $pa['wepsk'] = '';
			return -1;
		}
	}
	return;
}


//扣除指定装备的耐久。check_sk：是否在武器毁坏时重新检查属性数组$pa['ex_keys']
function armor_hurt(&$pa,$which,$hurtvalue,$check_sk=0)
{
	global $log,$nosta;

	if(!empty($pa[$which.'s']) && !empty($hurtvalue))
	{
		//无限耐久的防具可以抵挡1次任意点损耗
		if ($pa[$which.'s'] == $nosta)
		{
			$pa[$which.'s'] = $hurtvalue;
		}
		//扣除耐久
		$x = min($pa[$which.'s'], $hurtvalue);
		$pa[$which.'s'] = $pa[$which.'s']-$x;
		if(!$pa['type']) $log .= "<span class=\"grey\">{$pa['nm']}的".$pa[$which]."的耐久度下降了{$x}！</span><br>";
		//耐久为0 装备损坏
		if($pa[$which.'s'] <= 0)
		{
			$log .= "{$pa['nm']}的<span class=\"red\">".$pa[$which]."</span>受损过重，无法再装备了！<br>";

			//剔除防具属性
			if($check_sk && !empty($pa[$which.'sk'])) unset_ex_from_array($pa,get_itmsk_array($pa[$which.'sk']));

			//装备损坏后 重新检查套装属性
			reload_single_set_item($pa,$which,$pa[$which]);

			if($which == 'arb')
			{
				$pa[$which] = '内衣'; $pa[$which.'k'] = 'DN';
				$pa[$which.'e'] = 0; $pa[$which.'s'] = $nosta; $pa[$which.'sk'] = '';
			}
			else 
			{
				$pa[$which] = $pa[$which.'k'] = $pa[$which.'sk'] = '';
				$pa[$which.'e'] = $pa[$which.'s'] = 0; 
			}
			return -1;
		}
	}
	return 0;
}

//从属性数组中剔除指定属性
function unset_ex_from_array(&$pa,$exarr)
{
	if(!empty($pa['ex_keys']) && !empty($exarr))
	{
		foreach($exarr as $ex)
		{
			if(in_array($ex,$pa['ex_keys'])) unset($pa['ex_keys'][array_search($ex,$pa['ex_keys'])]);
		}
	}
	return;
}

//把箭矢名字抹掉
	//认为武器类别|后的都是箭矢名，返回抹掉的名字
function wep_b_clean_arrow_name(&$itmk){
	if(strpos($itmk,'|')===false) return '';
	$ofs = strpos($itmk,'|');
	$ret = substr($itmk, $ofs+1);
	$itmk = substr($itmk, 0, $ofs);
	return $ret;
}

//把引用的参数中的箭矢带来的属性抹掉，返回抹掉的属性
//认为|之间的属性都是箭矢属性
function wep_b_clean_arrow_sk(&$itmsk){
	if(strpos($itmsk,'|')===false) return '';
	//如果奇数个，则结尾补一个|，嘿嘿
	if(substr_count($itmsk, '|') % 2) $itmsk .= '|';
	preg_match('/\|.*\|/s',$itmsk,$matches);
	$ret = '';
	if(!empty($matches)) {
		$itmsk = preg_replace('/\|.*?\|/s','',$itmsk);
		$ret = substr($matches[0], 1, -1);
	}
	return $ret;
}


?>
