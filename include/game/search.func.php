<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}

include_once GAME_ROOT.'./include/state.func.php';
include_once GAME_ROOT.'./include/game/itemmain.func.php';
include_once GAME_ROOT.'./include/game/revbattle.func.php';
include_once GAME_ROOT.'./include/game/revbattle.calc.php';
include_once GAME_ROOT.'./include/game/revcombat.func.php';

function check_can_move($pls,$pgroup,$moveto)
{
	global $log,$plsinfo,$hplsinfo,$arealist,$areanum,$hack;

	if($pls == $moveto)
	{
		$log .= "不能重复移动。<br>";
		return 0;
	}
	$plsnum = sizeof($plsinfo);
	if(!isset($plsinfo[$pls]) && isset($hplsinfo[$pgroup]))
	{
		//玩家位于隐藏地点组内，不能通过常规移动方式回到标准地点，也不能移动到其他隐藏地点组
		if(!array_key_exists($moveto,$hplsinfo[$pgroup]))
		{
			$log .= "地图上没有{$hplsinfo[$pgroup][$moveto]}啊？<br>";
			return 0;
		}
	}
	else
	{
		//玩家位于标准地点组内
		if((!array_key_exists($moveto,$plsinfo))||($moveto == 'main')||($moveto < 0 )||($moveto >= $plsnum))
		{
			$log .= '请选择正确的移动地点。<br>';
			return 0;
		} 
		elseif(array_search($moveto,$arealist) <= $areanum && !$hack)
		{
			$log .= $plsinfo[$moveto].'是禁区，还是离远点吧！<br>';
			return 0;
		}
	}
	return 1;
}

function move($moveto = 99,&$data=NULL) {
	//global $lvl,$log,$pls,$pgroup,$plsinfo,$hplsinfo,$inf,$hp,$mhp,$sp,$def,$club,$arealist,$areanum,$hack,$areainfo,$gamestate,$pose,$weather;
	//global $gamestate,$gamecfg,$pdata;

	global $log,$weather,$plsinfo,$hplsinfo,$arealist,$areanum,$hack,$areainfo,$gamestate,$gamecfg;
	global $inf_move_sp,$infwords,$inf_move_hp;

	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);

	$plsnum = sizeof($plsinfo);

	if($pls == $moveto)
	{
		$log .= '相同地点，不需要移动。<br>';
		return;
	}

	if(!isset($plsinfo[$pls]) && isset($hplsinfo[$pgroup]))
	{
		//玩家位于隐藏地点组内，不能通过常规移动方式回到标准地点，也不能移动到其他隐藏地点组
		if(!array_key_exists($moveto,$hplsinfo[$pgroup]))
		{
			$log .= "地图上没有{$hplsinfo[$pgroup][$moveto]}啊？<br>";
			return;
		}
		$hpls_flag = true;
	}
	else
	{
		//玩家位于标准地点组内
		if((!array_key_exists($moveto,$plsinfo))||($moveto == 'main')||($moveto < 0 )||($moveto >= $plsnum))
		{
			$log .= '请选择正确的移动地点。<br>';
			return;
		} 
		elseif(array_search($moveto,$arealist) <= $areanum && !$hack)
		{
			$log .= $plsinfo[$moveto].'是禁区，还是离远点吧！<br>';
			return;
		}
		$hpls_flag = false;
	}

	//足部受伤，20；足球社，12；冻伤，30；正常，15；去gamecfg里改吧
	$movesp = 15;
	if ($inf) {
		
		foreach ($inf_move_sp as $inf_ky => $sp_down) {
			if(strpos($inf,$inf_ky)!==false){$movesp+=$sp_down;}
		}
	}
	//if(strpos($inf, 'f') !== false){ $movesp += 5; }
	//if(strpos($inf, 'i') !== false){ $movesp += 15; }
	if($club == 6){
		if($lvl>=20){
			$movesp -= 14;
		}else{
			$movesp -= 10+floor($lvl/5);
		}
	}

	
	if($sp <= $movesp){
		$log .= "体力不足，不能移动！<br>还是先睡会儿吧！<br>";
		return;
	}

	$sp -= $movesp;
	$moved = false;
	if($weather == 11) {//龙卷风
		if($hpls_flag)
		{
			$pls = array_rand($hplsinfo[$pgroup]);
			$moveto_info = $hplsinfo[$pgroup][$pls];
		}
		else 
		{
			if($hack){$pls = rand(0,sizeof($plsinfo)-1);}
			else {$pls = rand($areanum+1,sizeof($plsinfo)-1);$pls=$arealist[$pls];}
			$moveto_info = $plsinfo[$pls];
		}
		$log = ($log . "龙卷风把你吹到了<span class=\"yellow\">$moveto_info</span>！<br>");
		$moved = true;
	} elseif($weather == 13) {//冰雹
		$damage = round($mhp/12) + rand(0,20);
		$hp -= $damage;
		$log .= "被<span class=\"blue\">冰雹</span>击中，生命减少了<span class=\"red\">$damage</span>点！<br>";
		if($hp <= 0 ) {
			death('hsmove','',0,'',$data);
			return;
//		} else {
//			$pls = $moveto;
//			$log .= "消耗<span class=\"yellow\">{$movesp}</span>点体力，移动到了<span class=\"yellow\">$plsinfo[$pls]</span>。<br>";
		}
	} elseif($weather == 14){//离子暴
		$dice = rand(0,8);
		if($dice ==0 && strpos($inf,'e')===false){
			$log .= "空气中充斥着的<span class=\"linen\">狂暴电磁波</span>导致你<span class=\"yellow\">身体麻痹</span>了！<br>";
			$inf = str_replace('e','',$inf);
			$inf .= 'e';
		}elseif($dice ==1 && strpos($inf,'w')===false){
			$log .= "空气中充斥着的<span class=\"linen\">狂暴电磁波</span>导致你<span class=\"grey\">混乱</span>了！<br>";
			$inf = str_replace('w','',$inf);
			$inf .= 'w';
		}elseif($dice ==2 && (strpos($inf,'w')===false || strpos($inf,'e')===false)){
			if (strpos($inf,'w')===false)
			{
				$log .= "空气中充斥着的<span class=\"linen\">狂暴电磁波</span>导致你<span class=\"grey\">混乱</span>了！<br>";
				$inf = str_replace('w','',$inf);
				$inf .= 'w';
			}
			if (strpos($inf,'e')===false)
			{
				$log .= "空气中充斥着的<span class=\"linen\">狂暴电磁波</span>导致你<span class=\"yellow\">身体麻痹</span>了！<br>";
				$inf = str_replace('e','',$inf);
				$inf .= 'e';
			}
		}else{
			$log .= "空气中充斥着狂暴的电磁波……<br>";
		}
	} elseif($weather == 15){//辐射尘
		$dice = rand(0,3);
		if($dice == 0){
			$mhpdown = rand(4,8);
			if($mhp > $mhpdown){
				$log .= "空气中弥漫着的<span class=\"green\">放射性尘埃</span>导致你的生命上限减少了<span class=\"red\">{$mhpdown}</span>点！<br>";
				$mhp -= $mhpdown;
				if($hp > $mhp){$hp = $mhp;}
			}
		}elseif ($dice==1 && strpos($inf,'p')===false){
			$log .= "空气中弥漫着的<span class=\"green\">放射性尘埃</span>导致你<span class=\"purple\">中毒</span>了！<br>";
			$inf = str_replace('p','',$inf);
			$inf .= 'p';
		}else{
			$log .= "空气中弥漫着放射性尘埃……<br>";
		}
	} elseif($weather == 16){//臭氧洞
		$dice = rand(0,7);
		if($dice <= 3){
			$defdown = rand(4,8);
			if($def > $defdown){
				$log .= "高强度的<span class=\"purple\">紫外线照射</span>导致你的防御力减少了<span class=\"red\">{$defdown}</span>点！<br>";
				$def -= $defdown;
			}
		}elseif($dice <=5 && strpos($inf,'u')===false){
			$log .= "高强度的<span class=\"purple\">紫外线照射</span>导致你<span class=\"red\">烧伤</span>了！<br>";
			$inf = str_replace('u','',$inf);
			$inf .= 'u';
		}else{
			$log .= "高强度的紫外线灼烧着大地……<br>";
		}
	} 

	if(!$moved) {
		if(!$hpls_flag) $pgroup = 0;
		$pls = $moveto;
		$moveto_info = $hpls_flag ? $hplsinfo[$pgroup][$pls] : $plsinfo[$pls];
		$log .= "消耗<span class=\"yellow\">{$movesp}</span>点体力，移动到了<span class=\"yellow\">{$moveto_info}</span>。<br>";
	}else{$f=false;}
	
	$log .= $areainfo[$pls].'<br>';	

	# 移动到指定地点，结算移动探索事件
	move_search_events($data,'move');

	$enemyrate =  \revbattle\calc_meetman_rate($data);
	//echo "enemyrate = {$enemyrate}%";
	discover($enemyrate,$data);
	return;
}

function search(&$data=NULL){
	//global $pdata,$lvl,$log,$pls,$pgroup,$arealist,$areanum,$hack,$plsinfo,$hplsinfo,$club,$sp,$gamestate,$pose,$weather,$hp,$mhp,$def,$inf;
	
	global $log,$weather,$arealist,$areanum,$hack,$plsinfo,$hplsinfo,$gamestate;
	global $inf_search_sp,$infwords,$inf_search_hp;

	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);

	if(!isset($plsinfo[$pls]) && isset($hplsinfo[$pgroup]))
	{
		$hpls_flag = true;
	}
	else 
	{
		if(array_search($pls,$arealist) <= $areanum && !$hack)
		{
			$log .= $plsinfo[$pls].'是禁区，还是赶快逃跑吧！<br>';
			return;
		}
		$hpls_flag = false;
	}

	//腕部受伤，20；冻伤：30；侦探社，12；正常，15；改到gamecfg
	$schsp =15;
	if ($inf) {
		foreach ($inf_search_sp as $inf_ky => $sp_down) {
			if(strpos($inf,$inf_ky)!==false){$schsp+=$sp_down;}
		}
	}
	//if(strpos($inf, 'a') !== false){ $schsp += 5; }
	//if(strpos($inf, 'i') !== false){ $schsp += 15; }
	if($club == 6){
		if($lvl>=20){
			$schsp -= 14;
		}else{
			$schsp -= 10+floor($lvl/5);
		}
	}


	if($sp <= $schsp){
		$log .= "体力不足，不能探索！<br>还是先睡会儿吧！<br>";
		return;	
	}

	if($weather == 11) {//龙卷风
		if($hpls_flag)
		{
			$pls = array_rand($hplsinfo[$pgroup]);
			$moveto_info = $hplsinfo[$pgroup][$pls];
		}
		else 
		{
			if($hack){$pls = rand(0,sizeof($plsinfo)-1);}
			else {$pls = rand($areanum+1,sizeof($plsinfo)-1);$pls=$arealist[$pls];}
			$moveto_info = $plsinfo[$pls];
		}
		$log = ($log . "龙卷风把你吹到了<span class=\"yellow\">$moveto_info</span>！<br>");
		$moved = true;
	} elseif($weather == 13) {//冰雹
		$damage = round($mhp/12) + rand(0,20);
		$hp -= $damage;
		$log .= "被<span class=\"blue\">冰雹</span>击中，生命减少了<span class=\"red\">$damage</span>点！<br>";
		if($hp <= 0 ) {
			death('hsmove','',0,'',$data);
			return;
//		} else {
//			$pls = $moveto;
//			$log .= "消耗<span class=\"yellow\">{$movesp}</span>点体力，移动到了<span class=\"yellow\">$plsinfo[$pls]</span>。<br>";
		}
	} elseif($weather == 14){//离子暴
		$dice = rand(0,8);
		if($dice ==0 && strpos($inf,'e')===false){
			$log .= "空气中充斥着的<span class=\"linen\">狂暴电磁波</span>导致你<span class=\"yellow\">身体麻痹</span>了！<br>";
			$inf = str_replace('e','',$inf);
			$inf .= 'e';
		}elseif($dice ==1 && strpos($inf,'w')===false){
			$log .= "空气中充斥着的<span class=\"linen\">狂暴电磁波</span>导致你<span class=\"grey\">混乱</span>了！<br>";
			$inf = str_replace('w','',$inf);
			$inf .= 'w';
		}elseif($dice ==2 && (strpos($inf,'w')===false || strpos($inf,'e')===false)){
			if (strpos($inf,'w')===false)
			{
				$log .= "空气中充斥着的<span class=\"linen\">狂暴电磁波</span>导致你<span class=\"grey\">混乱</span>了！<br>";
				$inf = str_replace('w','',$inf);
				$inf .= 'w';
			}
			if (strpos($inf,'e')===false)
			{
				$log .= "空气中充斥着的<span class=\"linen\">狂暴电磁波</span>导致你<span class=\"yellow\">身体麻痹</span>了！<br>";
				$inf = str_replace('e','',$inf);
				$inf .= 'e';
			}
		}else{
			$log .= "空气中充斥着狂暴的电磁波……<br>";
		}
	} elseif($weather == 15){//辐射尘
		$dice = rand(0,3);
		if($dice == 0){
			$mhpdown = rand(4,8);
			if($mhp > $mhpdown){
				$log .= "空气中弥漫着的<span class=\"green\">放射性尘埃</span>导致你的生命上限减少了<span class=\"red\">{$mhpdown}</span>点！<br>";
				$mhp -= $mhpdown;
				if($hp > $mhp){$hp = $mhp;}
			}
		}elseif ($dice==1 && strpos($inf,'p')===false){
			$log .= "空气中弥漫着的<span class=\"green\">放射性尘埃</span>导致你<span class=\"purple\">中毒</span>了！<br>";
			$inf = str_replace('p','',$inf);
			$inf .= 'p';
		}else{
			$log .= "空气中弥漫着放射性尘埃……<br>";
		}
	} elseif($weather == 16){//臭氧洞
		$dice = rand(0,7);
		if($dice <= 3){
			$defdown = rand(4,8);
			if($def > $defdown){
				$log .= "高强度的<span class=\"purple\">紫外线照射</span>导致你的防御力减少了<span class=\"red\">{$defdown}</span>点！<br>";
				$def -= $defdown;
			}
		}elseif($dice <=5 && strpos($inf,'u')===false){
			$log .= "高强度的<span class=\"purple\">紫外线照射</span>导致你<span class=\"red\">烧伤</span>了！<br>";
			$inf = str_replace('u','',$inf);
			$inf .= 'u';
		}else{
			$log .= "高强度的紫外线灼烧着大地……<br>";
		}
	} 
	
	
	$sp -= $schsp;
	$log .= "消耗<span class=\"yellow\">{$schsp}</span>点体力，你搜索着周围的一切。。。<br>";
	
	# 探索指定地点，结算探索事件
	move_search_events($data,'search');
	
	$enemyrate = \revbattle\calc_meetman_rate($data);
	//echo "enemyrate = {$enemyrate}%";
	discover($enemyrate,$data);
	return;

}

# 探索或移动行为会触发的事件
function move_search_events(&$data,$act)
{
	global $log,$inf_move_hp,$inf_move_sp,$infwords,$weather,$gamevars,$now,$elements_info;

	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);
	
	if($act == 'move')
	{
		//移动后丢失探索视野
		lost_searchmemory('all',$data);
	}

	if($inf)
	{
		foreach ($inf_move_hp as $inf_ky => $o_dmg) {
			if(strpos($inf,$inf_ky)!==false)
			{
				$damage = round($mhp * $o_dmg) + rand(0,10);
				# 「死疗」效果判定： TODO：之后要把异常状态扣血效果单独做一个函数
				if($inf_ky == 'p' && !check_skill_unlock('c8_deadheal',$data))
				{
					$sk_p = get_skillvars('c8_deadheal','exdmgr');
					$damage = min($mhp-$hp,ceil($damage*($sk_p/100)));
					$damage *= -1;
				}
				$hp -= $damage;
				if($damage > 0) $log .= "{$infwords[$inf_ky]}减少了<span class=\"red\">$damage</span>点生命！<br>";
				elseif($damage < 0) $log .= "{$infwords[$inf_ky]}恢复了<span class=\"lime\">".abs($damage)."</span>点生命！<br>";
				if($hp <= 0 ){
					death($inf_ky.'move','',0,'',$data);
					return;
				}
			}			
		}
	}

	# 光玉雨天气效果判定：
	if($weather == 18)
	{
		# 雨停了
		if($now > $gamevars['wth18etime'])
		{
			unset($gamevars['wth18stime']);
			unset($gamevars['wth18etime']);
			unset($gamevars['wth18pid']);
			$weather = 1;
			save_gameinfo();
			addnews($now, 'wth18end');
			if(!empty($clbpara['event_bgmbook'])) unset($clbpara['event_bgmbook']);
		}
		# 雨势
		else 
		{
			if(empty($clbpara['event_bgmbook'])) $clbpara['event_bgmbook'] = Array('wth18');
			$wthlastime = $now - $gamevars['wth18stime'];
			# 雨势在前7分钟递增，后3分钟递减
			$wthlastime = $wthlastime <= 420 ? $wthlastime : 600 - $wthlastime;
			$wthpow = min(7,max(1,round($wthlastime / 60)));
			$hp_up = diceroll($wthpow) * diceroll($wthpow); 
			$sp_up = diceroll($wthpow) * diceroll($wthpow);
			if($hp_up || $sp_up)
			{
				$log .= "<span class='minirainbow'>不知从哪飘来的光球落在了你的肩上……</span><br>超量恢复了";
				if($hp_up)
				{
					$hp += $hp_up;
					$log .= "<span class='minirainbow'>{$hp_up}</span>点生命！";
				}
				if($sp_up)
				{
					$hp += $sp_up;
					$log .= "<span class='minirainbow'>{$sp_up}</span>点体力！";
				}
				$log .= "<br>";
			}
		}
	}

	# 「理财」效果判定：
	if(!check_skill_unlock('c11_stock',$data))
	{
		$sk = 'c11_stock';
		$sk_mst = get_skillvars($sk,'mst');
		$ms = get_skillpara($sk,'ms',$data['clbpara']) + 1;
		# 赚钱！
		if($ms >= $sk_mst)
		{
			$sk_var = get_skillvars($sk,'earn');
			$earn = min(max(ceil($money*$sk_var/100),get_skillvars($sk,'minmoney')),get_skillvars($sk,'maxmoney'));
			$money += $earn;
			$log .= "<span class='yellow'>「理财」使你赚到了{$earn}元！</span><br>";
			$ms = 0;
		}
		set_skillpara($sk,'ms',$ms,$data['clbpara']);
	}
	# 「佣兵」效果判定：召唤过佣兵的情况下，每行动一次+1计数
	if(!empty(get_skillpara('c11_merc','id',$clbpara)))
	{
		include_once GAME_ROOT.'./include/game/revclubskills_extra.func.php';
		$sk = 'c11_merc'; 
		# 检查是否有需要付工资的佣兵
		$mids = get_skillpara($sk,'id',$clbpara);
		foreach($mids as $mkey => $mid)
		{
			$mdata = fetch_playerdata_by_pid($mid);
			skill_merc_paid($sk,$mkey,$mdata);
			# 每次探索/移动时都重新检查佣兵是否可协战
			$clbpara['skillpara'][$sk]['cancover'][$mkey] = $mdata['pls'] == $pls ? 1 : 0;
			# 移动到其他地图时，佣兵丢失追击焦点
			if(!empty($mdata['clbpara']['mercchase']) && $act == 'move') $mdata['clbpara']['mercchase'] = 0;
			player_save($mdata);
		}
	}

	# 「沃土」效果判定：
	if(!check_skill_unlock('c20_fertile',$data))
	{
		$sk = 'c20_fertile';
		$sk_lvl = get_skilllvl($sk,$data);
		$sk_mst = get_skillvars($sk,'mst',$sk_lvl);
		$ms = get_skillpara($sk,'ms',$data['clbpara']) + 1;
		# 赚钱！
		if($ms >= $sk_mst)
		{
			$temp_enums = 0; $temp_ekey = 0;
			foreach($elements_info as $key => $info)
			{
				if(!empty($data['element'.$key]))
				{
					if(empty($temp_enums) || $data['element'.$key] < $temp_enums)
					{
						$temp_enums = $data['element'.$key];
						$temp_ekey = $key;
					}
				}
			}
			if($temp_enums)
			{
				$sk_var = get_skillvars($sk,'minemsgain',$sk_lvl);
				$add_ev = $temp_enums * (1 + ($sk_var/100));
				$data['element'.$temp_ekey] = $add_ev;
				$ms = 0;
				$log .= "<span class='yellow'>你的口袋里冒出了新的元素！</span><br>";
			}
		}
		set_skillpara($sk,'ms',$ms,$data['clbpara']);
	}

	return;
}

function discover($schmode = 0,&$data=NULL) 
{
	//global $pdata;
	//global $art,$pls,$now,$log,$mode,$command,$cmd,$event_obbs,$weather,$pls,$club,$pose,$tactic,$inf,$item_obbs,$enemy_obbs,$trap_min_obbs,$trap_max_obbs,$bid,$db,$tablepre,$gamestate,$corpseprotect,$action,$skills,$rp,$aidata;
	//global $clbpara,$gamecfg;

	global $now,$log,$mode,$command,$cmd;
	global $db,$tablepre,$gamestate,$aidata,$pls_bgm,$weather;
	global $event_obbs,$item_obbs,$enemy_obbs,$trap_min_obbs,$trap_max_obbs,$corpse_obbs,$corpseprotect;

	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);

	$event_dice = rand(0,99);
	if($data['pass'] == 'bot') $event_obbs = -1;
	if(($event_dice < $event_obbs)||(($art!="Untainted Glory")&&($pls==34)&&($gamestate != 50))){
		//echo "进入事件判定<br>";
		include_once GAME_ROOT.'./include/game/event.func.php';
		$event_flag = event();
		//触发了事件，中止探索推进
		if($event_flag)
		{
			$mode = 'command';
			return;
		}
	}

	# 判定移动、探索、事件后的BGM变化
	if(array_key_exists($pls,$pls_bgm))
	{
		$clbpara['pls_bgmbook'] = $pls_bgm[$pls];
	}
	else
	{
		if(isset($clbpara['pls_bgmbook'])) 
			unset($clbpara['pls_bgmbook']);
	}
	
	include_once GAME_ROOT. './include/game/aievent.func.php';//AI事件
	$aidata = false;//用于判断天然呆AI（冴冴这样的）是否已经来到你身后并且很生气
	aievent(20);//触发AI事件的概率
	if(is_array($aidata))
	{
		//触发了AI追击事件
		$edata = $aidata;
		goto battle_flag;
	}
	
	$trap_dice=diceroll(99);
	// 计算陷阱“发现率”
	if($trap_dice < $trap_max_obbs)
	{ 
		//echo "进入踩陷阱判定<br>";
		$trapresult = $db->query("SELECT * FROM {$tablepre}maptrap WHERE pls = '$pls' ORDER BY itmk DESC");
		$trpnum = $db->num_rows($trapresult);
		//看地图上有没有陷阱	
		if($trpnum)
		{
			$fstrp = $db->fetch_array($trapresult);
			//奇迹雷
			$xtrpflag = $fstrp['itmk'] == 'TOc' ? true : false;
			//计算 或不计算陷阱“触发率”：
			$real_trap_obbs = $xtrpflag ? 100 : calc_real_trap_obbs($data,$trpnum);
			//echo "realtrapobbs = {$real_trap_obbs}<br>";
			if($trap_dice < $real_trap_obbs)
			{
				if(!$xtrpflag)
				{
					$itemno = rand(0,$trpnum-1);
					$db->data_seek($trapresult,$itemno);
					$fstrp = $db->fetch_array($trapresult);
				}
				//global $itm0,$itmk0,$itme0,$itms0,$itmsk0;
				$itm0=$fstrp['itm'];
				$itmk0=$fstrp['itmk'];
				$itme0=$fstrp['itme'];
				$itms0=$fstrp['itms'];
				$itmsk0=$fstrp['itmsk'];
				$tid = $fstrp['tid'];
				$db->query("DELETE FROM {$tablepre}maptrap WHERE tid='$tid'");
				itemfind($data);
				return;
			}
		}
	}
	$mode_dice = rand(0,99);
	if($mode_dice < $schmode) 
	{
		global $fog,$gamestate;

		$result = $db->query("SELECT * FROM {$tablepre}players WHERE pls='$pls' AND pid!='$pid'");
		if(!$db->num_rows($result)){
			$log .= '<span class="yellow">周围一个人都没有。</span><br>';
			if(CURSCRIPT == 'botservice') echo "noenemy=1\n";
			$mode = 'command';
			return;
		}

		$enemynum = $db->num_rows($result);
		$enemyarray = range(0, $enemynum - 1);
		shuffle($enemyarray);
		
		//移除了重复调用discover()的设定，尝试用一种正常一点的办法确保敌人/尸体发现率符合基础设定值，不然现在的尸体确实太难摸了。
		//现在触发遇敌事件只会返回三种结果：1、发现尸体；2、发现敌人、3、敌人隐藏起来；所以实际的尸体发现率=$enemyrate*$corpse_obbs
		$meetman_flag = 0;
		foreach($enemyarray as $enum)
		{
			$db->data_seek($result, $enum);
			$edata = $db->fetch_array($result);
			# 使用fetch_playerdata_by_pid重新获取敌人数据，以应用各种在载入玩家数据时进行的判定
			$edata = fetch_playerdata_by_pid($edata['pid']);
			if(!$edata['type'] || $gamestate < 50)
			{
				if($edata['hp'] <= 0)
				{
					//直接略过无效尸体
					if($gamestate>=40) continue;
					$ret = false;
					foreach(array('money','arhs','aras','arfs','arts','itms1','itms2','itms3','itms4','itms5','itms6') as $chkval)
					{
						if($edata[$chkval]) 
						{
							$ret = true;
							break;
						}
					}
					if(!$ret) continue;
					//计算尸体发现率
					$corpse_dice = rand(0,99);
					//击杀女主后，对女主尸体发现率大幅提升
					if($edata['type'] == 14 && isset($data['clbpara']['achvars']['kill_n14'])) $corpse_dice = 100;
					if($corpse_dice > $corpse_obbs)
					{
						$meetman_flag = 1;
						break;
					}
				}
				else 
				{
					# 略过决斗者
					if ((!$edata['type'])&&($artk=='XX')&&(($edata['artk']!='XX')||($edata['art']!=$name))&&($gamestate<50)) continue;
					if (($artk!='XX')&&($edata['artk']=='XX')&&($gamestate<50)) continue;
					# 暂时直接略过盟友单位
					if(!empty($edata['clbpara']['mate']) && in_array($pid,$edata['clbpara']['mate'])) continue;
					# 灵子状态只能遭遇同为灵子状态的对象，非灵子状态对象无法发现灵子状态下的对象……但是尸体就没有这种考量了
					if(($edata['pose'] == 8 || $data['pose'] == 8) && $data['pose'] != $edata['pose']) continue;
					# 「量心」技能效果判定（不会遭遇HP为1的敌人）：
					if(!check_skill_unlock('c19_dispel',$data) && !empty(get_skillpara('c19_dispel','active',$clbpara)) && $edata['hp'] == 1) continue;
					
					# 计算活人发现率
					$hide_r = \revbattle\calc_hide_rate($data,$edata);
					$enemy_dice = diceroll(99);
					# 把find_r杀了，现在技能都是用躲避率去判断的了，躲避率为负就等于发现率增幅了
					$meetman_flag = $enemy_dice < ($enemy_obbs - $hide_r) ? 1 : -1;
					break;
				}
			}
		}
		if($meetman_flag>0)
		{
			if($edata['hp'] > 0) 
			{
				if(isset($edata['clbpara'])) $edata['clbpara']=get_clbpara($edata['clbpara']);
				//发现队友
				if($teamID&&(!$fog)&&($gamestate<40)&&($teamID == $edata['teamID']))
				{
					$bid = $edata['pid'];
					$action = 'team';
					findteam($edata);
					return;
				} 
				//发现中立NPC或友军 TODO：把这里条件判断挪到一个函数里
				elseif(isset($edata['clbpara']['post']) && $edata['clbpara']['post'] == $pid)
				{
					$bid = $edata['pid'];
					$action = 'neut';
					findneut($edata,1);
					return;
				}
				//发现敌人
				else 
				{
					battle_flag:
					//计算先攻概率
					$active_r = \revbattle\calc_active_rate($data,$edata);
					$bid = $edata['pid'];
					$active_dice = diceroll(99);
					//先制
					if($active_dice < $active_r)
					{
						$action = 'enemy'; $bid = $edata['pid'];
						if($data['pass'] != 'bot')
						{
							
							\revbattle\findenemy_rev($edata);
						}
						else 
						{
							echo "进入战斗！<br>";
							\revcombat\rev_combat_prepare($data,$edata,1,'',0);
						}
						return;
					}
					//挨打
					else 
					{
						if($data['pass'] != 'bot')
						{
							
							\revcombat\rev_combat_prepare($edata,$data,0);
						}
						else 
						{
							\revcombat\rev_combat_prepare($edata,$data,0,'',0);
						}
						return;
					}
				}
			}
			else 
			{
				$action = 'corpse'; $bid = $edata['pid'];
				include_once GAME_ROOT.'./include/game/battle.func.php';
				findcorpse($edata);
				return;
			}
		}
		elseif($meetman_flag < 0)
		{
			$log .= '似乎有人隐藏着……<br>';
		}
		else 
		{
			if($data['pose'] == 8) $log .= '<span class="yellow">周围没有同处于灵子状态的对象。</span><br>';
			else $log .= '<span class="yellow">周围一个人都没有。</span><br>';
		}
		$mode = 'command';
		return;
	} 
	else 
	{
		//echo "进入道具判定<br>";
		//$find_r = get_find_r($weather,$pls,$pose,$tactic,$club,$inf);
		$find_obbs = $item_obbs;
		$item_dice = rand(0,99);
		if($item_dice < $find_obbs) 
		{
			$flag = focus_item($data);
			if(!$flag)
			{
				$log .= '<span class="yellow">周围找不到任何物品。</span><br>';
				$mode = 'command';
				return;
			}
		} 
		else 
		{
			$log .= "但是什么都没有发现。<br>";
		}
	}
	$mode = 'command';
	return;

}

function focus_item(&$data=NULL,$id=NULL)
{
	global $db,$tablepre,$log;
	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);

	if(isset($id))
	{
		$result = $db->query("SELECT * FROM {$tablepre}mapitem WHERE pls = '$pls' AND iid = '$id'");
		if(!$db->num_rows($result)) 
		{
			$log .= "但是你想找的东西已经不见了！<br>";
			return 0;
		}
		$mi=$db->fetch_array($result);
	}
	else 
	{
		$result = $db->query("SELECT * FROM {$tablepre}mapitem WHERE pls = '$pls'");
		$itemnum = $db->num_rows($result);
		if($itemnum <= 0) return 0;
		$itemno = rand(0,$itemnum-1);
		$db->data_seek($result,$itemno);
		$mi=$db->fetch_array($result);
	}
	$itm0=$mi['itm'];
	$itmk0=$mi['itmk'];
	$itme0=$mi['itme'];
	$itms0=$mi['itms'];
	$itmsk0=$mi['itmsk'];
	$iid=$mi['iid'];
	$db->query("DELETE FROM {$tablepre}mapitem WHERE iid='$iid'");
	if($itms0)
	{
		if($data['pass'] == 'bot') 
		{
			itemget($data);
		}
		else 
		{
			itemfind();
			return 1;
		}
	} 
	else 
	{
		$log .= "但是什么都没有发现。可能是因为道具有天然呆属性。<br>";
	}
	return;
}



?>
