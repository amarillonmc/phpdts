<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}

function move($moveto = 99) {
	global $lvl,$log,$pls,$plsinfo,$inf,$hp,$mhp,$sp,$def,$club,$arealist,$areanum,$hack,$areainfo,$gamestate,$pose,$weather;
	global $gamestate;
	$f=false;
	if ($pls==34 && $gamestate<50) $f=true;
	if ($moveto==34 && $gamestate<50) $f=true;
	$plsnum = sizeof($plsinfo);
	if(($moveto == 'main')||($moveto < 0 )||($moveto >= $plsnum)){
		$log .= 'Please select correct place to move.<br>';
		return;
	} elseif($pls == $moveto){
		$log .= 'Same location, not moving<br>';
		return;
	} elseif(array_search($moveto,$arealist) <= $areanum && !$hack){
		$log .= $plsinfo[$moveto].'is a forbidden area!<br>';
		return;
	}


	//足部受伤，20；足球社，12；冻伤，30；正常，15；去gamecfg里改吧
	$movesp = 15;
	if ($inf) {
		global $inf_move_sp;
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
		$log .= "Cannot Move<br>Not enough SP!<br>";
		return;
	}

	$sp -= $movesp;
	$moved = false;
	if($weather == 11) {//龙卷风
		if($hack){$pls = rand(0,sizeof($plsinfo)-1);}
		else {$pls = rand($areanum+1,sizeof($plsinfo)-1);$pls=$arealist[$pls];}
		$log = ($log . "Tornado blow you to <span class=\"yellow\">$plsinfo[$pls]</span>！<br>");
		$moved = true;
	} elseif($weather == 13) {//冰雹
		$damage = round($mhp/12) + rand(0,20);
		$hp -= $damage;
		$log .= "You got pelted by <span class=\"blue\">Hail</span>, your HP is decreased by <span class=\"red\">$damage</span>！<br>";
		if($hp <= 0 ) {
			include_once GAME_ROOT.'./include/state.func.php';
			death('hsmove');
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
		$pls = $moveto;
		$log .= "You spend <span class=\"yellow\">{$movesp}</span> SP， and moved to<span class=\"yellow\">$plsinfo[$pls]</span>。<br>";
	}else{$f=false;}
	
	
	if($inf){
		global $infwords,$inf_move_hp;
		foreach ($inf_move_hp as $inf_ky => $o_dmg) {
			if(strpos($inf,$inf_ky)!==false){
				$damage = round($mhp * $o_dmg) + rand(0,15);
				$hp -= $damage;
				$log .= "{$infwords[$inf_ky]} lowed <span class=\"red\">$damage</span> HP！<br>";
				if($hp <= 0 ){
					include_once GAME_ROOT.'./include/state.func.php';
					death($inf_ky.'move');
					return;
				}
			}			
		}
	}
	
	$log .= $areainfo[$pls].'<br>';	
	if ($f) {
		if (CURSCRIPT !== 'botservice') $log.="<span id=\"HsUipfcGhU\"></span>";	//刷新页面标记
		return;
	}
	$enemyrate = 40;
	if($gamestate == 40){$enemyrate += 20;}
	elseif($gamestate == 50){$enemyrate += 40;}
	if($pose==3){$enemyrate -= 20;}
	elseif($pose==4){$enemyrate += 10;}
	discover($enemyrate);
	/*
	$enemyrate = 70;
	if($gamestate == 40){$enemyrate += 10;}
	elseif($gamestate == 50){$enemyrate += 15;}
	if($pose==3){$enemyrate -= 20;}
	elseif($pose==4){$enemyrate += 10;}
	discover($enemyrate);
	*/
	return;

}

function search(){
	global $lvl,$log,$pls,$arealist,$areanum,$hack,$plsinfo,$club,$sp,$gamestate,$pose,$weather,$hp,$mhp,$def,$inf;
	
	
	if(array_search($pls,$arealist) <= $areanum && !$hack){
		$log .= $plsinfo[$pls].'是禁区，还是赶快逃跑吧！<br>';
		return;
	}

	//腕部受伤，20；冻伤：30；侦探社，12；正常，15；改到gamecfg
	$schsp =15;
	if ($inf) {
		global $inf_search_sp;
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
		$log .= "Cannot Search!<br>Not enough SP! (Try sleeping)<br>";
		return;	
	}

	if($weather == 11) {//龙卷风
		if($hack){$pls = rand(0,sizeof($plsinfo)-1);}
		else {$pls = rand($areanum+1,sizeof($plsinfo)-1);$pls=$arealist[$pls];}
		$log = ($log . "Tornado blown you to <span class=\"yellow\">$plsinfo[$pls]</span>！<br>");
		$moved = true;
	} elseif($weather == 13) {//冰雹
		$damage = round($mhp/12) + rand(0,20);
		$hp -= $damage;
		$log .= "被<span class=\"blue\">冰雹</span>击中，生命减少了<span class=\"red\">$damage</span>点！<br>";
		if($hp <= 0 ) {
			include_once GAME_ROOT.'./include/state.func.php';
			death('hsmove');
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
	$log .= "You spend <span class=\"yellow\">{$schsp}</span> SP， started to search the area... ...<br>";
	if($inf){
		global $infwords,$inf_search_hp;
		foreach ($inf_search_hp as $inf_ky => $o_dmg) {
			if(strpos($inf,$inf_ky)!==false){
				$damage = round($mhp * $o_dmg) + rand(0,10);
				$hp -= $damage;
				$log .= "{$infwords[$inf_ky]}lowered <span class=\"red\">$damage</span> HP！<br>";
				if($hp <= 0 ){
					include_once GAME_ROOT.'./include/state.func.php';
					death($inf_ky.'move');
					return;
				}
			}			
		}
	}
	
	/*if(strpos($inf, 'p') !== false){
		$damage = round($mhp/32) + rand(0,5);
		$hp -= $damage;
		$log .= "<span class=\"purple\">毒发</span>减少了<span class=\"red\">$damage</span>点生命！<br>";
		if($hp <= 0 ){
			include_once GAME_ROOT.'./include/state.func.php';
			death('pmove');
			return;
		}
	}
	if(strpos($inf, 'u') !== false){
		$damage = round($mhp/32) + rand(0,15);
		$hp -= $damage;
		$log .= "<span class=\"yellow\">烧伤发作</span>减少了<span class=\"red\">$damage</span>点生命！<br>";
		if($hp <= 0 ){
			include_once GAME_ROOT.'./include/state.func.php';
			death('umove');
			return;
		}
	}*/
	$enemyrate = 40;
	if($gamestate == 40){$enemyrate += 20;}
	elseif($gamestate == 50){$enemyrate += 30;}
	if($pose==3){$enemyrate -= 20;}
	elseif($pose==4){$enemyrate += 10;}
	discover($enemyrate);
//	$log .= '遇敌率'.$enemyrate.'%<br>';
//	if(($gamestate>=40)&&($pose!=3)) {
//		discover(75);
//	} else {
//		discover(30);
//	}
	return;

}

function discover($schmode = 0) {
	global $art,$pls,$now,$log,$mode,$command,$cmd,$event_obbs,$weather,$pls,$club,$pose,$tactic,$inf,$item_obbs,$enemy_obbs,$trap_min_obbs,$trap_max_obbs,$bid,$db,$tablepre,$gamestate,$corpseprotect,$action,$skills,$rp,$aidata;
	$event_dice = rand(0,99);
	if(($event_dice < $event_obbs)||(($art!="Untainted Glory")&&($pls==34)&&($gamestate != 50))){
		include_once GAME_ROOT.'./include/game/event.func.php';
		event();
		$mode = 'command';
		return;
	}
	
	include_once GAME_ROOT. './include/game/aievent.func.php';//AI事件
	$aidata = false;//用于判断天然呆AI（冴冴这样的）是否已经来到你身后并且很生气
	aievent(20);//触发AI事件的概率
	if(is_array($aidata)){
		include_once GAME_ROOT.'./include/game/attr.func.php';
		$active_r = get_active_r($weather,$pls,$pose,$tactic,$club,$inf,$aidata['pose']);
		include_once GAME_ROOT.'./include/game/clubskills.func.php';
		$active_r *= get_clubskill_bonus_active($club,$skills,$aidata['club'],$aidata['skills']);
		if ($active_r>96) $active_r=96;
		$bid = $aidata['pid'];
		$active_dice = rand(0,99);
		if($active_dice <  $active_r) {
			$action = 'enemy'.$aidata['pid'];
			include_once GAME_ROOT.'./include/game/battle.func.php';
			findenemy($aidata);
			return;
		} else {
			include_once GAME_ROOT.'./include/game/combat.func.php';
			combat(0);
			return;
		}
		
		
	}
	
	$trap_dice=rand(0,99);//随机数，开始判断是否踩陷阱
	if($trap_dice < $trap_max_obbs){ //踩陷阱概率最大值
		$trapresult = $db->query("SELECT * FROM {$tablepre}maptrap WHERE pls = '$pls' ORDER BY itmk DESC");
//		$traplist = Array();
//		while($trap0 = $db->fetch_array($result)){
//			$traplist[$trap0['tid']] = $trap0;
//			if($trap0['itmk'] == 'TOc'){
//				$xtrap = true;
//				$xtrapid = $
//			}
//		}
		$xtrp = $db->fetch_array($trapresult);
		$xtrpflag = false;
		//echo $xtrp['itm'];
		if($xtrp['itmk'] == 'TOc'){
			$xtrpflag = true;
		}
		$trpnum = $db->num_rows($trapresult);
		if($trpnum){//看地图上有没有陷阱	
			//echo "踩陷阱概率：{$real_trap_obbs}%";
			if($xtrpflag){
				global $itm0,$itmk0,$itme0,$itms0,$itmsk0;
				$itm0=$xtrp['itm'];
				$itmk0=$xtrp['itmk'];
				$itme0=$xtrp['itme'];
				$itms0=$xtrp['itms'];
				$itmsk0=$xtrp['itmsk'];
				$tid = $xtrp['tid'];
				$db->query("DELETE FROM {$tablepre}maptrap WHERE tid='$tid'");
				include_once GAME_ROOT.'./include/game/itemmain.func.php';
				itemfind();
				return;
			}else{
				$real_trap_obbs = $trap_min_obbs + $trpnum/4;
				//Anti-Meta RP System Version 2.00 ~ Nemo
				//冴冴我喜欢你！
				//17rp/177rp+1%
				if($gamestate >= 50) {$real_trap_obbs = $real_trap_obbs + $rp / 177; }
				else{ $real_trap_obbs = $real_trap_obbs + $rp/30; }
				if($pose==1){$real_trap_obbs+=1;}
				elseif($pose==3){$real_trap_obbs+=3;}//攻击和探索姿势略容易踩陷阱
				if($gamestate >= 40){$real_trap_obbs+=3;}//连斗以后略容易踩陷阱
				if($pls == 0){$real_trap_obbs+=15;}//在后台非常容易踩陷阱
				if($club == 6){$real_trap_obbs-=5;}//人肉搜索称号遭遇陷阱概率减少
				if($trap_dice < $real_trap_obbs){//踩陷阱判断
					$itemno = rand(0,$trpnum-1);
					$db->data_seek($trapresult,$itemno);
					$mi=$db->fetch_array($trapresult);
					global $itm0,$itmk0,$itme0,$itms0,$itmsk0;
					$itm0=$mi['itm'];
					$itmk0=$mi['itmk'];
					$itme0=$mi['itme'];
					$itms0=$mi['itms'];
					$itmsk0=$mi['itmsk'];
					$tid=$mi['tid'];
					$db->query("DELETE FROM {$tablepre}maptrap WHERE tid='$tid'");
					if($itms0){
						include_once GAME_ROOT.'./include/game/itemmain.func.php';
						itemfind();
						return;
					}
				}
			}
		}
	}
//	$trap_dice =  rand(0,99);
//	if($pose==1){$trap_dice-=5;}
//	elseif($pose==3){$trap_dice-=8;}//攻击和探索姿势略容易踩陷阱
//	if($gamestate >= 40){$trap_dice-=5;}//连斗以后略容易踩陷阱
//	if($trap_dice < $trap_obbs){
//		$result = $db->query("SELECT * FROM {$tablepre}{$pls}mapitem WHERE itmk = 'TO'");
//		$trpnum = $db->num_rows($result);
//		if($trpnum){
//			$itemno = rand(0,$trpnum-1);
//			$db->data_seek($result,$itemno);
//			$mi=$db->fetch_array($result);
//			global $itm0,$itmk0,$itme0,$itms0,$itmsk0;
//			$itm0=$mi['itm'];
//			$itmk0=$mi['itmk'];
//			$itme0=$mi['itme'];
//			$itms0=$mi['itms'];
//			$itmsk0=$mi['itmsk'];
//			$iid=$mi['iid'];
//			$db->query("DELETE FROM {$tablepre}{$pls}mapitem WHERE iid='$iid'");
//			if($itms0){
//				include_once GAME_ROOT.'./include/game/itemmain.func.php';
//				itemfind();
//				return;
//			}
//		}
//	}
	include_once GAME_ROOT.'./include/game/attr.func.php';

	$mode_dice = rand(0,99);
	if($mode_dice < $schmode) {
		global $pid,$corpse_obbs,$teamID,$fog,$bid,$gamestate;
//		if($gamestate < 40) {
//			$result = $db->query("SELECT * FROM {$tablepre}players WHERE pls='$pls' AND pid!='$pid' AND pid!='$bid'");
//		} else {
//			$result = $db->query("SELECT * FROM {$tablepre}players WHERE pls='$pls' AND pid!='$pid'");
//		}
		$result = $db->query("SELECT * FROM {$tablepre}players WHERE pls='$pls' AND pid!='$pid'");
		if(!$db->num_rows($result)){
			$log .= '<span class="yellow">No one is here.</span><br>';
			if(CURSCRIPT == 'botservice') echo "noenemy=1\n";
			$mode = 'command';
			return;
		}

		$enemynum = $db->num_rows($result);
		$enemyarray = range(0, $enemynum - 1);
		shuffle($enemyarray);
		$find_r = get_find_r($weather,$pls,$pose,$tactic,$club,$inf);
		$find_obbs = $enemy_obbs + $find_r;
		
		foreach($enemyarray as $enum){
			$db->data_seek($result, $enum);
			$edata = $db->fetch_array($result);
			if(!$edata['type'] || $gamestate < 50){
				if($edata['hp'] > 0) {
					global $art,$artk,$name; 
					if ((!$edata['type'])&&($artk=='XX')&&(($edata['artk']!='XX')||($edata['art']!=$name))&&($gamestate<50)){
						continue;
					}
					if (($artk!='XX')&&($edata['artk']=='XX')&&($gamestate<50)){
						continue;
					}
					$hide_r = get_hide_r($weather,$pls,$edata['pose'],$edata['tactic'],$edata['club'],$edata['inf']);
					include_once GAME_ROOT.'./include/game/clubskills.func.php';
					$hide_r *= get_clubskill_bonus_hide($edata['club'],$edata['skills']);
					$enemy_dice = rand(0,99);
					if($enemy_dice < ($find_obbs - $hide_r)) {
						if($teamID&&(!$fog)&&($gamestate<40)&&($teamID == $edata['teamID'])){
							$bid = $edata['pid'];
							$action = 'team'.$edata['pid'];
							include_once GAME_ROOT.'./include/game/battle.func.php';
							findteam($edata);
							return;
						} else {
							$active_r = get_active_r($weather,$pls,$pose,$tactic,$club,$inf,$edata['pose']);
							include_once GAME_ROOT.'./include/game/clubskills.func.php';
							$active_r *= get_clubskill_bonus_active($club,$skills,$edata['club'],$edata['skills']);
							if ($active_r>96) $active_r=96;
							$bid = $edata['pid'];
							$active_dice = rand(0,99);
							if($active_dice <  $active_r) {
								$action = 'enemy'.$edata['pid'];
								include_once GAME_ROOT.'./include/game/battle.func.php';
								findenemy($edata);
								return;
							} else {
								if (CURSCRIPT == 'botservice') 
								{
									echo "passive_battle=1\n";
									echo "passive_w_name={$edata['name']}\n";
									echo "passive_w_type={$edata['type']}\n";
									echo "passive_w_sNo={$edata['sNo']}\n";
								}
								include_once GAME_ROOT.'./include/game/combat.func.php';
								combat(0);
								return;
							}
						}
					}else{
						$hideflag = true;
					}
				} else {
					$corpse_dice = rand(0,99);
					if($corpse_dice < $corpse_obbs) {
						
						if($gamestate <40 && $edata['endtime'] < $now - $corpseprotect && (($edata['weps'] && $edata['wepe'])||($edata['arbs'] && $edata['arbe'])||$edata['arhs']||$edata['aras']||$edata['arfs']||$edata['arts']||$edata['itms0']||$edata['itms1']||$edata['itms2']||$edata['itms3']||$edata['itms4']||$edata['itms5']||$edata['money'])){
							
							$bid = $edata['pid'];
							$action = 'corpse'.$edata['pid'];
							include_once GAME_ROOT.'./include/game/battle.func.php';
							findcorpse($edata);
							return;
						} else {
							//这看上去是个bug…… 会导致地图上最后一个兵很难摸到…… 
							//改成discover(100)应该就能解决问题…… 但修复了可能导致平衡性问题…… 所以暂时留在这……
							discover(50);	
							return;
						}
					}
				}
			}
		}
		if($hideflag == true){
			$log .= 'Someone is hiding...<br>';
		}else{
			$log .= '<span class="yellow">No one is nearby...</span><br>';
		}
		$mode = 'command';
		return;
	} else {
		$find_r = get_find_r($weather,$pls,$pose,$tactic,$club,$inf);
		$find_obbs = $item_obbs + $find_r;
		$item_dice = rand(0,99);
		if($item_dice < $find_obbs) {
			//$mapfile = GAME_ROOT."./gamedata/mapitem/{$pls}mapitem.php";
			//$mapitem = openfile($mapfile);
			//$itemnum = sizeof($mapitem) - 1;
//			$result = $db->query("SELECT * FROM {$tablepre}mapitem WHERE map='$pls'");
//			$itemnum = $db->num_rows($result);
			$result = $db->query("SELECT * FROM {$tablepre}mapitem WHERE pls = '$pls'");
			$itemnum = $db->num_rows($result);
			if($itemnum <= 0){
				$log .= '<span class="yellow">There are no items</span><br>';
				$mode = 'command';
				return;
			}
			$itemno = rand(0,$itemnum-1);
			$db->data_seek($result,$itemno);
			$mi=$db->fetch_array($result);
			global $itm0,$itmk0,$itme0,$itms0,$itmsk0;
			$itm0=$mi['itm'];
			$itmk0=$mi['itmk'];
			$itme0=$mi['itme'];
			$itms0=$mi['itms'];
			$itmsk0=$mi['itmsk'];
			$iid=$mi['iid'];
			$db->query("DELETE FROM {$tablepre}mapitem WHERE iid='$iid'");
			//list($itm0,$itmk0,$itme0,$itms0,$itmsk0) = explode(',', $mapitem[$itemno]);
			//array_splice($mapitem,$itemno,1);
			//writeover($mapfile,implode('', $mapitem),'wb');
			//unset($mapitem);

			if($itms0){
				include_once GAME_ROOT.'./include/game/itemmain.func.php';
				itemfind();
				return;
			} else {
				$log .= "Discovered nothing.<br>";
			}
		} else {
			$log .= "Discovered nothing. <br>";
		}
	}
	$mode = 'command';
	return;

}



?>
