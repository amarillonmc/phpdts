<?php

if (! defined ( 'IN_GAME' )) {
	exit ( 'Access Denied' );
}

// Handle bullet items
function item_ammo_bullets($itmn, &$data) {
	global $log, $mode, $nosta;
	extract($data, EXTR_REFS);
	
	$itm = & ${'itm' . $itmn};
	$itmk = & ${'itmk' . $itmn};
	$itme = & ${'itme' . $itmn};
	$itms = & ${'itms' . $itmn};
	$itmsk = & ${'itmsk' . $itmn};
	
	if ((strpos($wepk, 'WG') !== 0)&&(strpos($wepk, 'WJ') !== 0)) {
		$log .= "<span class=\"red\">你没有装备枪械，不能使用子弹。</span><br>";
		$mode = 'command';
		return;
	}
	if (strpos($wepk,'WG')===false){
		if ($itmk=='GBh'){
		$bulletnum = 3;	
		}else{
		$log .= "<span class=\"red\">枪械类型和弹药类型不匹配。</span><br>";
		$mode = 'command';
		return;
		}
	}
	elseif (strpos($wepsk, 'o') !== false) {
		$log .= "<span class=\"red\">{$wep}不能装填弹药。</span><br>";
		$mode = 'command';
		return;
	} elseif (strpos($wepsk, 'e') !== false || strpos($wepsk, 'w') !== false) {
		if ($itmk == 'GBe') {
			$bulletnum = 18;
		} else {
			$log .= "<span class=\"red\">枪械类型和弹药类型不匹配。</span><br>";
			$mode = 'command';
			return;
		}
	} elseif (strpos($wepsk, 'i') !== false || strpos($wepsk, 'u') !== false) {
		if ($itmk == 'GBi') {
			$bulletnum = 18;
		} else {
			$log .= "<span class=\"red\">枪械类型和弹药类型不匹配。</span><br>";
			$mode = 'command';
			return;
		}
	} else {
		if (strpos($wepsk, 'r') !== false) {
			if ($itmk == 'GBr') {
				$bulletnum = 24;
			} else {
				$log .= "<span class=\"red\">枪械类型和弹药类型不匹配。</span><br>";
				$mode = 'command';
				return;
			}
		} else {
			if ($itmk == 'GB') {
				$bulletnum = 12;
			} else {
				$log .= "<span class=\"red\">枪械类型和弹药类型不匹配。</span><br>";
				$mode = 'command';
				return;
			}
		}
	}
	if (function_exists('elevator_has_skill') && elevator_has_skill($data,723)) $bulletnum*=elevator_skill_value(723,'capacity');
	if ($weps == $nosta) {
		$weps = 0;
	}
	$bullet = $bulletnum - $weps;
	if ($bullet <= 0) {
		$log .= "<span class=\"red\">{$wep}的弹匣是满的，不能装弹。</span>";
		return;
	} elseif ($bullet >= $itms) {
		$bullet = $itms;
	}
	if ($itms != $nosta) {
		$itms -= $bullet;
		$weps += $bullet;
		$log .= "为<span class=\"red\">$wep</span>装填了<span class=\"red\">$itm</span>，<span class=\"red\">$wep</span>残弹数增加<span class=\"yellow\">$bullet</span>。<br>";
		if ($itms <= 0) {
			$log .= "<span class=\"red\">$itm</span>用光了。<br>";
			$itm = $itmk = $itmsk = '';
			$itme = $itms = 0;
		}
	} else {
		$weps += $bullet;
		$log .= "为<span class=\"red\">$wep</span>装填了<span class=\"red\">$itm</span>，<span class=\"red\">$wep</span>残弹数增加<span class=\"yellow\">$bullet</span>。<br>";
	}
}

// Handle arrow items
function item_ammo_arrows($itmn, &$data) {
	global $log, $mode;
	extract($data, EXTR_REFS);
	
	$itm = & ${'itm' . $itmn};
	$itmk = & ${'itmk' . $itmn};
	$itme = & ${'itme' . $itmn};
	$itms = & ${'itms' . $itmn};
	$itmsk = & ${'itmsk' . $itmn};
	
	if (strpos($wepk, 'WB') !== 0) {
		$log .= "<span class=\"red b\">你没有装备弓，不能给武器上箭。</span><br>";
		$mode = 'command';
		return;
	} elseif(0 === $itmn && !empty($weps)) {//捡到的箭矢不能马上拉弓，避免换箭覆盖itm0的问题
		$log .= "你一只手捏着弓箭，一只手抓着刚捡到的箭矢，没法马上弯弓搭箭。<span class=\"red b\">还是先把箭矢收进包裹里吧。</span><br>";
		$mode = 'command';
		return;
	} else {
		//$theitem = Array('itm' => &$itm, 'itmk' => &$itmk, 'itme' => &$itme, 'itms' => &$itms, 'itmsk' => &$itmsk);
		include_once GAME_ROOT . './include/game/item2.func.php';
		itemuse_ugb($pdata, $itmn);
	}
}
