<?php

if (! defined ( 'IN_GAME' )) {
	exit ( 'Access Denied' );
}

// Handle poison items
function item_poison($itmn, &$data) {
	global $log, $nosta, $db, $tablepre, $now;
	extract($data, EXTR_REFS);

	$itm = & ${'itm' . $itmn};
	$itmk = & ${'itmk' . $itmn};
	$itme = & ${'itme' . $itmn};
	$itms = & ${'itms' . $itmn};
	$itmsk = & ${'itmsk' . $itmn};

	if (strpos($itmk, '2') === 2) {
		$damage = round($itme * 2);
	} elseif (strpos($itmk, '1') === 2) {
		$damage = round($itme * 1.5);
	} else {
		$damage = round($itme);
	}
	if (strpos($inf, 'p') === false) {
		$inf .= 'p';
	}

	# 「种火IV」效果判定：
	if(!empty($data['clbpara']['skill']) && in_array('fireseed4', $data['clbpara']['skill'])) {
		$log .= "<span class='yellow'>「种火IV」使{$name}受到的所有伤害变为0！</span><br>";
		$damage = 0;
	}
	# RuleSet钩子：模式技能可复用种火IV式的非战斗伤害免疫。
	# RuleSet hook: mode skills may reuse Fireseed-IV-style non-combat immunity.
	if(function_exists('ruleset_damage_immunity_hook')) {
		$ruleset_damage = ruleset_damage_immunity_hook($data, 'poison', $damage);
		if($ruleset_damage !== NULL) $damage = max(0, intval($ruleset_damage));
	}

	$hp -= $damage;
	if ($itmsk && is_numeric($itmsk)) {
		$result = $db->query("SELECT * FROM {$tablepre}players WHERE pid='$itmsk'");
		$wdata = $db->fetch_array($result);
		$log .= "糟糕，<span class=\"yellow\">$itm</span>中被<span class=\"yellow\">{$wdata['name']}</span>掺入了毒药！你受到了<span class=\"dmg\">$damage</span>点伤害！<br>";
		addnews($now, 'poison', $name, $wdata['name'], $itm, $nick);
	} else {
		$log .= "糟糕，<span class=\"yellow\">$itm</span>有毒！你受到了<span class=\"dmg\">$damage</span>点伤害！<br>";
	}
	if ($hp <= 0) {
		if ($itmsk && is_numeric($itmsk)) {
			$bid = $itmsk;
			$result = $db->query("SELECT * FROM {$tablepre}players WHERE pid='$itmsk'");
			$edata = $db->fetch_array($result);
			//include_once GAME_ROOT . './include/state.func.php';
			//$killmsg = death('poison', $wdata['name'], $wdata['type'], $itm);
			//$log .= "你被<span class=\"red\">" . $wdata['name'] . "</span>毒死了！";
			//if($killmsg){$log .= "<span class=\"yellow\">{$wdata['name']}对你说："{$killmsg}"</span><br>";}
			if(!$edata['type'])
			{
				$w_log = "<span class=\"yellow\">{$name}误食了你下毒的补给<span class=\"red\">{$itm}</span>被毒死！</span><br>";
				logsave($itmsk, $now, $w_log, 'b');
			}
			$edata['wep_name'] = $itm;
			include_once GAME_ROOT.'./include/state.func.php';
			$last = pre_kill_events($edata, $data, 0, 'poison');
			if($itmsk == $data['pid']) $last = 0;
			final_kill_events($edata, $data, 0, $last);
			player_save($edata); //current_player_save();
		} else {
			//$bid = 0;
			include_once GAME_ROOT . './include/state.func.php';
			death('poison', '', 0, $itm);
			$log .= "你被毒死了！";
		}
	}
	else
	{
		//吃了像围棋子一样的饼干但是活下来了……怎么做到的！
		if($itm == '像围棋子一样的饼干') $clbpara['achvars']['eat_weiqi'] = 1;
		if($itm == '桔黄色的果酱') $clbpara['achvars']['eat_jelly'] = 1;
	}
	if ($itms != $nosta) {
		$itms --;
		if ($itms <= 0) {
			$log .= "<span class=\"red\">$itm</span>用光了。<br>";
			$itm = $itmk = $itmsk = '';
			$itme = $itms = 0;
		}
	}
}
