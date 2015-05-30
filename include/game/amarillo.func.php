<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}

function move($moveto = 99) {
	global $log,$pls,$plsinfo,$inf,$hp,$sp,$club,$arealist,$areanum,$hack,$areainfo,$gamestate,$pose,$weather;

	$plsnum = sizeof($plsinfo);
	if(($moveto == 'main')||($moveto < 0 )||($moveto >= $plsnum)){
		$log .= '请选择正确的移动地点。<br>';
		return;
	} elseif($pls == $moveto){
		$log .= '相同地点，不需要移动。<br>';
		return;
	} elseif(array_search($moveto,$arealist) <= $areanum && !$hack){
		$log .= $plsinfo[$moveto].'是禁区，还是离远点吧！';
		return;
	}
	
	//足部受伤，20；足球社，12；正常，15；

	if(strpos($inf, 'f') !== false){ $movesp = 20; }
	elseif($club == 6){ $movesp = 12; }
	else{ $movesp = 15; }
	
	if($sp <= $movesp){
		$log .= '体力不足，不能移动，还是先睡会儿吧！';
		return;
	}

	$sp -= $movesp;
	if($weather == 11) {
		if($hack){$pls = rand(0,sizeof($plsinfo)-1);}
		else {$pls = rand($areanum+1,sizeof($plsinfo)-1);}
		$log = ($log . "你刚迈出脚步，就被一阵龙卷风吹到了 <span class=\"yellow\">$plsinfo[$pls]</span>  ！<br>");
	} elseif($weather == 13) {
		$damage = rand(1,3);
		$hp -= $damage;
		$log .= "被 <span class=\"blue\">冰雹</span> 击中，导致生命减少了 <span class=\"red\">$damage</span> 点！<br>";
		if($hp <= 0 ) {
			include_once GAME_ROOT.'./include/state.func.php';
			death('hsmove');
			return;
		} else {
			$pls = $moveto;
			$log .= "移动到了 <span class=\"yellow\">$plsinfo[$pls]</span> 。<br>";
		}
	} else {
		$pls = $moveto;
		$log .= "移动到了 <span class=\"yellow\">$plsinfo[$pls]</span> 。<br>";
	}

	if(strpos($inf, 'p') !== false){
		$damage = rand(4,77);
		$hp -= $damage;
		$log .= "毒发导致生命减少了 <span class=\"red\">$damage</span> 点！<br>";
		if($hp <= 0 ){
			include_once GAME_ROOT.'./include/state.func.php';
			death('pmove');
			return;
		}
	}
	$log .= $areainfo[$pls];
	if(($gamestate>=40)&&($pose!=3)){
		discover(100);
	} else {
		discover(70);
	}
	return;

}

function search(){
	global $log,$pls,$arealist,$areanum,$hack,$plsinfo,$club,$sp,$gamestate,$pose,$weather,$hp;
	
	if(array_search($pls,$arealist) <= $areanum && !$hack){
		$log .= $plsinfo[$pls].'是禁区，还是赶快逃跑吧！';
		return;
	}

	//腕部受伤，20；侦探社，12；正常，15；

	if(strpos($inf, 'a') !== false){ $schsp = 20; }
	elseif($club == 10){ $schsp = 12; }
	else{ $schsp = 15; }

	if($sp <= $schsp){
		$log .= '体力不足，不能探索，还是先睡会儿吧！';
		return;	
	}

	if($weather == 13) {
		$hp --;
		$log .= "被 <span class=\"blue\">冰雹</span> 击中，导致生命减少了 <span class=\"red\">1</span> 点！<br>";
		if($hp <= 0 ) {
			include_once GAME_ROOT.'./include/state.func.php';
			death('hsmove');
			return;
		}
	}
	$sp -= $schsp;
	$log .= '你仔细搜索着周围的一切。。。<br>';
	if(($gamestate>=40)&&($pose!=3)) {
		discover(100);
	} else {
		discover(30);
	}
	return;

}

function discover($schmode = 0) {
	global $log,$mode,$command,$cmd,$event_obbs,$weather,$pls,$club,$pose,$tactic,$inf,$item_obbs,$enemy_obbs,$active_obbs;

	$event_dice = rand(0,99);
	if($event_dice < $event_obbs){
		include_once GAME_ROOT.'./include/game/event.func.php';
		event();
		$mode = 'command';
		return;
	}
	
	include_once GAME_ROOT.'./include/game/attr.func.php';

	$mode_dice = rand(0,99);
	if($mode_dice < $schmode ) {
		global $db,$tablepre,$pid,$corpse_obbs,$teamID,$fog,$gamestate,$bid;
		if($gamestate < 40) {
			$result = $db->query("SELECT * FROM {$tablepre}players WHERE pls='$pls' AND pid!='$pid' AND pid!='$bid'");
		} else {
			$result = $db->query("SELECT * FROM {$tablepre}players WHERE pls='$pls' AND pid!='$pid'");
		}
		if(!$db->num_rows($result)){
			$log .= '这里似乎已经没有人在了。<br>';
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
			if($edata['hp'] > 0) {
				$hide_r = get_hide_r($weather,$pls,$edata['pose'],$edata['tactic'],$edata['club'],$edata['inf']);
				$enemy_dice = rand(0,99);
				if($enemy_dice < ($find_obbs - $hide_r)) {
					if($teamID&&(!$fog)&&($teamID == $edata['teamID'])){
						include_once GAME_ROOT.'./include/game/battle.func.php';
						findteam($edata);
						return;
					} else {
						$active_r = get_active_r($weather,$pls,$pose,$tactic,$club,$inf);
						$active_dice = rand(0,99);
						if($active_dice < $active_obbs + $active_r) {
							include_once GAME_ROOT.'./include/game/battle.func.php';
							findenemy($edata);
							return;
						} else {
							include_once GAME_ROOT.'./include/game/combat.func.php';
							combat($edata,0);
							return;
						}
					}
				}
			} else {
				$corpse_dice = rand(0,99);
				if($corpse_dice < $corpse_obbs) {
					if($gamestate <40 &&(($edata['weps'] && $edata['wepe'])||($edata['arbs'] && $edata['arbe'])||$edata['arhs']||$edata['aras']||$edata['arfs']||$edata['arts']||$edata['itms0']||$edata['itms1']||$edata['itms2']||$edata['itms3']||$edata['itms4']||$edata['itms5']||$edata['money'])){
						include_once GAME_ROOT.'./include/game/battle.func.php';
						findcorpse($edata);
						return;
					} else {
						discover(50);
						return;
					}
				}
			}
		}
		$log .= '似乎有什么人潜藏着┅┅士兵吗？<br>';
		$mode = 'command';
		return;
	} else {
		$find_r = get_find_r($weather,$pls,$pose,$tactic,$club,$inf);
		$find_obbs = $item_obbs + $find_r;
		$item_dice = rand(0,99);
		if($item_dice < $find_obbs) {
			$mapfile = GAME_ROOT."./gamedata/mapitem/{$pls}mapitem.php";
			$mapitem = openfile($mapfile);
			$itemnum = sizeof($mapitem) - 1;
			if($itemnum <= 0){
				$log .= "这里似乎什么都没有了。<br>";
				$mode = 'command';
				return;
			}
			$itemno = rand(1,$itemnum);
			global $itm0,$itmk0,$itme0,$itms0,$itmsk0;
			list($itm0,$itmk0,$itme0,$itms0,$itmsk0) = explode(',', $mapitem[$itemno]);
			array_splice($mapitem,$itemno,1);
			writeover($mapfile,implode('', $mapitem),'wb');
			unset($mapitem);

			if($itms0){
				include_once GAME_ROOT.'./include/game/itemmain.func.php';
				itemfind();
				return;
			} else {
				$log .= "但是什么都没有发现。<br>";
			}
		} else {
			$log .= "但是什么都没有发现。<br>";
		}
	}
	$mode = 'command';
	return;

}



?>