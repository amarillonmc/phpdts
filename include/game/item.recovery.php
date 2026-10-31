<?php

if (! defined ( 'IN_GAME' )) {
	exit ( 'Access Denied' );
}

// Handle stamina recovery items
function item_recovery_stamina($itmn, &$data) {
	global $log, $nosta;
	extract($data, EXTR_REFS);

	$itm = & ${'itm' . $itmn};
	$itmk = & ${'itmk' . $itmn};
	$itme = & ${'itme' . $itmn};
	$itms = & ${'itms' . $itmn};
	$itmsk = & ${'itmsk' . $itmn};

	if ($sp < $msp) {
		$oldsp = $sp;
		if($club == 12){
			$spup = round($itme*1.25);
		}else{
			$spup = $itme;
		}
		/*$sp += $spup;
		$sp = $sp > $msp ? $msp : $sp;
		$oldsp = $sp - $oldsp;*/
		$addsp = $msp - $sp < $spup ? $msp - $sp : $spup;
		//ADD: Process Luck Battle Mode random SP/HP gains.
		if ($clbpara['BGMBrand'] == 'rixolamal'){
			$addsp = diceroll($itme);
			$log .= "随机数大神不喜欢给定值，你回复的体力被骰子改动了！<br>";
		}
		if($addsp > 0) $sp += $addsp;
		else $addsp = 0;
		$log .= "你使用了<span class=\"red\">$itm</span>，恢复了<span class=\"yellow\">$addsp</span>点体力。<br>";
		//吃了无毒果酱
		if($itm == '桔黄色的果酱') $clbpara['achvars']['eat_jelly'] = 1;
		if ($itms != $nosta) {
			$itms --;
			if ($itms <= 0) {
				$log .= "<span class=\"red\">$itm</span>用光了。<br>";
				$itm = $itmk = $itmsk = '';
				$itme = $itms = 0;
			}
		}
	} else {
		$log .= '你的体力不需要恢复。<br>';
	}
}

// Handle health recovery items
function item_recovery_health($itmn, &$data) {
	global $log, $nosta;
	extract($data, EXTR_REFS);

	if (!empty($clbpara['kimi_no_hp'])) {
		$log .= "<span class=\"grey\">你的HP槽已经废除，无法恢复生命。</span><br>";
		return;
	}

	$itm = & ${'itm' . $itmn};
	$itmk = & ${'itmk' . $itmn};
	$itme = & ${'itme' . $itmn};
	$itms = & ${'itms' . $itmn};
	$itmsk = & ${'itmsk' . $itmn};

	if ($hp < $mhp) {
		$oldhp = $hp;
		if($club == 12){
			$hpup = round($itme*1.25);
		}else{
			$hpup = $itme;
		}
		/*$hp += $hpup;
		$hp = $hp > $mhp ? $mhp : $hp;
		$oldhp = $hp - $oldhp;*/
		$addhp = $mhp - $hp < $hpup ? $mhp - $hp : $hpup;
		if ($clbpara['BGMBrand'] == 'rixolamal'){
			$addhp = diceroll($itme);
			$log .= "随机数大神不喜欢给定值，你回复的生命被骰子改动了！<br>";
		}
		if($addhp > 0) {
			$hp += $addhp;

			# 「起迹」标记清除：
			if(isset($clbpara['tl_oncemore_used'])) {
				unset($clbpara['tl_oncemore_used']);
				$log .= "<span class='yellow'>「起迹」技能恢复了效果！</span><br>";
			}
		}
		else $addhp = 0;
		$log .= "你使用了<span class=\"red\">$itm</span>，恢复了<span class=\"yellow\">$addhp</span>点生命。<br>";
		if ($itms != $nosta) {
			$itms --;
			if ($itms <= 0) {
				$log .= "<span class=\"red\">$itm</span>用光了。<br>";
				$itm = $itmk = $itmsk = '';
				$itme = $itms = 0;
			}

		}
	} else {
		$log .= '你的生命不需要恢复。<br>';
	}
}

// Handle soul increase items
function item_recovery_soul_increase($itmn, &$data) {
	global $log, $nosta;
	extract($data, EXTR_REFS);

	if (!empty($clbpara['kimi_no_ss'])) {
		$log .= "<span class=\"grey\">你的歌魂槽已经废除，无法增加歌魂。</span><br>";
		return;
	}

	$itm = & ${'itm' . $itmn};
	$itmk = & ${'itmk' . $itmn};
	$itme = & ${'itme' . $itmn};
	$itms = & ${'itms' . $itmn};
	$itmsk = & ${'itmsk' . $itmn};

	$mss+=$itme;
	$ss+=$itme;
	$log .= "你使用了<span class=\"red\">$itm</span>，增加了<span class=\"yellow\">$itme</span>点歌魂。<br>";
	if ($clbpara['BGMBrand'] == 'lila'){
		$check = diceroll(20);
		if ($check > 17){
			$log .= "<span class=\"clan\">突然，一位纯洁的女初中生形象出现在你的脑海中，<br>你觉醒了额外的歌魂！<br></span>";
			$mss += $check * 2;
			$ss += $check * 2;
		}
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

// Handle soul recovery items
function item_recovery_soul($itmn, &$data) {
	global $log, $nosta;
	extract($data, EXTR_REFS);

	if (!empty($clbpara['kimi_no_ss'])) {
		$log .= "<span class=\"grey\">你的歌魂槽已经废除，无法恢复歌魂。</span><br>";
		return;
	}

	$itm = & ${'itm' . $itmn};
	$itmk = & ${'itmk' . $itmn};
	$itme = & ${'itme' . $itmn};
	$itms = & ${'itms' . $itmn};
	$itmsk = & ${'itmsk' . $itmn};

	$ssup=$itme;
	if ($ss < $mss) {
		$oldss = $ss;
		$ss += $ssup;
		$ss = $ss > $mss ? $mss : $ss;
		$oldss = $ss - $oldss;
		$log .= "你使用了<span class=\"red\">$itm</span>，恢复了<span class=\"yellow\">$oldss</span>点歌魂。<br>";
		if ($itms != $nosta) {
			$itms --;
			if ($itms <= 0) {
				$log .= "<span class=\"red\">$itm</span>用光了。<br>";
				$itm = $itmk = $itmsk = '';
				$itme = $itms = 0;
			}

		}
	} else {
		$log .= '你的歌魂不需要恢复。<br>';
	}
}

// Handle rage recovery items
function item_recovery_rage($itmn, &$data) {
	global $log, $nosta, $gamecfg;
	extract($data, EXTR_REFS);

	$itm = & ${'itm' . $itmn};
	$itmk = & ${'itmk' . $itmn};
	$itme = & ${'itme' . $itmn};
	$itms = & ${'itms' . $itmn};
	$itmsk = & ${'itmsk' . $itmn};

	$rageup=$itme;
	require config('gamecfg',$gamecfg);
	if ($rage < $mrage) {
		$oldrage = $rage;
		$rage += $rageup;
		$rage = $rage > $mrage ? $mrage : $rage;
		$oldrage = $rage - $oldrage;
		$log .= "你吃了一口<span class=\"red\">$itm</span>，顿时感觉心中充满了愤怒。你的怒气值增加了<span class=\"yellow b\">$oldrage</span>点！<br>";
		if ($itms != $nosta) {
			$itms --;
			if ($itms <= 0) {
				$log .= "<span class=\"red\">$itm</span>用光了。<br>";
				$itm = $itmk = $itmsk = '';
				$itme = $itms = 0;
			}

		}
	} else {
		$log .= '你已经出离愤怒了，动怒伤肝，还是歇歇吧！<br>';
	}
}

// Handle health and stamina recovery items
function item_recovery_both($itmn, &$data) {
	global $log, $nosta;
	extract($data, EXTR_REFS);

	$itm = & ${'itm' . $itmn};
	$itmk = & ${'itmk' . $itmn};
	$itme = & ${'itme' . $itmn};
	$itms = & ${'itms' . $itmn};
	$itmsk = & ${'itmsk' . $itmn};

	if (($hp < $mhp) || ($sp < $msp)) {
		if($club == 12){
			$bpup = round($itme*1.25);
		}else{
			$bpup = $itme;
		}
		//$oldsp = $sp;
		//$sp += $bpup;
		//$sp = $sp > $msp ? $msp : $sp;
		//$oldsp = $sp - $oldsp;
		$addsp = $msp - $sp < $bpup ? $msp - $sp : $bpup;
		if ($clbpara['BGMBrand'] == 'rixolamal'){
			$addsp = diceroll($itme);
			$log .= "随机数大神不喜欢给定值，你回复的体力被骰子改动了！<br>";
		}
		if($addsp > 0) $sp += $addsp;
		else $addsp = 0;
		//$oldhp = $hp;
		//$hp += $bpup;
		//$hp = $hp > $mhp ? $mhp : $hp;
		//$oldhp = $hp - $oldhp;
		$addhp = $mhp - $hp < $bpup ? $mhp - $hp : $bpup;
		if ($clbpara['BGMBrand'] == 'rixolamal'){
			$addhp = diceroll($itme);
			$log .= "随机数大神不喜欢给定值，你回复的生命被骰子改动了！<br>";
		}
		if($addhp > 0) {
			$hp += $addhp;

			# 「起迹」标记清除：
			if(isset($clbpara['tl_oncemore_used'])) {
				unset($clbpara['tl_oncemore_used']);
				$log .= "<span class='yellow'>「起迹」技能恢复了效果！</span><br>";
			}
		}
		else $addhp = 0;
		$log .= "你使用了<span class=\"red\">$itm</span>，恢复了<span class=\"yellow\">$addhp</span>点生命和<span class=\"yellow\">$addsp</span>点体力。<br>";
		//吃了无毒的围棋子饼干 真勇啊！
		if($itm == '像围棋子一样的饼干') $clbpara['achvars']['eat_weiqi'] = 1;
		if ($itms != $nosta) {
			$itms --;
			if ($itms <= 0) {
				$log .= "<span class=\"red\">$itm</span>用光了。<br>";
				$itm = $itmk = $itmsk = '';
				$itme = $itms = 0;
			}
		}
	} else {
		$log .= '你的生命和体力都不需要恢复。<br>';
	}
}
