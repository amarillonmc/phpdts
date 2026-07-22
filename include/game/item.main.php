<?php

if (! defined ( 'IN_GAME' )) {
	exit ( 'Access Denied' );
}

include_once GAME_ROOT.'./include/game/clubslct.func.php';

// Main item use function that delegates to specific item type handlers
function itemuse($itmn,&$data=NULL) {
	//global $mode, $log, $nosta, $pid, $name, $state, $now,$nick,$achievement,$club,$clbpara,$pdata;

	global $url,$cmd,$mode,$db,$tablepre,$log,$nosta,$noarb,$gamevars,$corpseprotect,$now,$gamecfg,$hack,$gamevars;
	global $exdmginf,$ex_inf,$cskills,$elements_info,$sparkle,$event_bgm;
	global $upexp,$baseexp,$elec_cap;
	//Some globals seems to be still needed... ...
	global $itemspkinfo,$plsinfo;
	global $pid;

	if(!isset($data))
	{
		global $pdata;
		$data = &$pdata;
	}
	extract($data,EXTR_REFS);

	if (($itmn < 1 || $itmn > 6) && $itmn != 0 ){
		$log .= '此道具不存在，请重新选择。';
		$mode = 'command';
		return;
	}

	////global ${'itm' . $itmn}, ${'itmk' . $itmn}, ${'itme' . $itmn}, ${'itms' . $itmn}, ${'itmsk' . $itmn};
	//2024-07-19: I'm mad enough to add $itmpara, with me luck.
	$itm = & ${'itm' . $itmn};
	$itmk = & ${'itmk' . $itmn};
	$itme = & ${'itme' . $itmn};
	$itms = & ${'itms' . $itmn};
	$itmsk = & ${'itmsk' . $itmn};
	//$itmpara = & ${'itmpara' . $itmn};
	$itmpara = & get_itmpara(${'itmpara' . $itmn});
	$i=$itm;$ik=$itmk;$ie=$itme;$is=$itms;$isk=$itmsk;$ipara=$itmpara;

	if (($itms <= 0) && ($itms != $nosta)) {
		$itm = $itmk = $itmsk = '';
		$itme = $itms = 0;
		$log .= '此道具不存在，请重新选择。<br>';
		$mode = 'command';
		return;
	}

	//If you are dead, you can't use items!
	if ($hp <= 0) {
		$log .= '你的大脑看起来仍旧想挣扎一下，但你的手已经动不了了，挣扎似乎也没有什么意义。<br>';
		$log .= '你已经死亡，无法使用道具。<br>';
		$mode = 'command';
		return;
	}

	// RuleSet钩子：在具体道具逻辑执行前记录剧情物品的使用。
	// RuleSet hook: observe story-item use before the concrete handler runs.
	if(function_exists('ruleset_itemuse_hook')) {
		$item_snapshot = Array(
			'itm' => $i,
			'itmk' => $ik,
			'itme' => $ie,
			'itms' => $is,
			'itmsk' => $isk,
			'itmpara' => $ipara,
		);
		// 严格返回true表示道具已由规则集完整处理，不再进入通用分派。
		// A strict true means the ruleset fully handled the item; skip generic dispatch.
		$ruleset_item_handled = ruleset_itemuse_hook($data, $itmn, $item_snapshot);
		extract($data, EXTR_REFS);
		if($ruleset_item_handled === true) return;
	}

	// Include specific item type handlers
	include_once GAME_ROOT.'./include/game/item.weapon.php';
	include_once GAME_ROOT.'./include/game/item.recovery.php';
	include_once GAME_ROOT.'./include/game/item.poison.php';
	include_once GAME_ROOT.'./include/game/item.trap.php';
	include_once GAME_ROOT.'./include/game/item.ammo.php';
	include_once GAME_ROOT.'./include/game/item.radar.php';
	include_once GAME_ROOT.'./include/game/item.cure.php';
	include_once GAME_ROOT.'./include/game/item.skillbook.php';
	include_once GAME_ROOT.'./include/game/item.enhance.php';
	include_once GAME_ROOT.'./include/game/item.weather.php';
	include_once GAME_ROOT.'./include/game/item.electronic.php';
	include_once GAME_ROOT.'./include/game/item.giftbox.php';
	include_once GAME_ROOT.'./include/game/item.dice.php';
	include_once GAME_ROOT.'./include/game/item.platform.php';
	include_once GAME_ROOT.'./include/game/item.tool.php';
	include_once GAME_ROOT.'./include/game/item.weapon_mod.php';
	include_once GAME_ROOT.'./include/game/item.ending.php';
	include_once GAME_ROOT.'./include/game/item.special_effect.php';
	include_once GAME_ROOT.'./include/game/item.npc.php';
	include_once GAME_ROOT.'./include/game/item.quest.php';
	include_once GAME_ROOT.'./include/game/item.synthesis.php';
	include_once GAME_ROOT.'./include/game/item.club_card.php';
	include_once GAME_ROOT.'./include/game/item.nachster_booster.php';
	include_once GAME_ROOT.'./include/game/item.nouveau_booster1.php';
	include_once GAME_ROOT.'./include/game/item.test.php';
	include_once GAME_ROOT.'./include/game/item.other.php';

	// Try to handle with nouveau booster1 items first
	if(item_nouveau_booster1($itmn, $data)) {
		return;
	}

	// Delegate to specific item type handlers based on item type code
	if(platform_is_recorder_item($itmpara)) {
		// Projection recorder items are routed here, while their logic stays in item.platform.php.
		item_platform($itmn, $data);
	} elseif(strpos($itmk, 'W') === 0 || strpos($itmk, 'D') === 0 || strpos($itmk, 'A') === 0 || strpos($itmk, 'ss') === 0) {
		// Weapons and equipment
		item_weapon($itmn, $data);
	} elseif(strpos($itmk, 'HS') === 0) {
		// Stamina recovery
		item_recovery_stamina($itmn, $data);
	} elseif(strpos($itmk, 'HH') === 0) {
		// Health recovery
		item_recovery_health($itmn, $data);
	} elseif(strpos($itmk, 'HM') === 0) {
		// Soul increase
		item_recovery_soul_increase($itmn, $data);
	} elseif(strpos($itmk, 'HT') === 0) {
		// Soul recovery
		item_recovery_soul($itmn, $data);
	} elseif(strpos($itmk, 'HR') === 0) {
		// Rage recovery
		item_recovery_rage($itmn, $data);
	} elseif(strpos($itmk, 'HB') === 0) {
		// Health and stamina recovery
		item_recovery_both($itmn, $data);
	} elseif(strpos($itmk, 'P') === 0) {
		// Poison
		item_poison($itmn, $data);
	} elseif(strpos($itmk, 'T') === 0) {
		// Trap
		item_trap($itmn, $data);
	} elseif(strpos($itmk, 'GB') === 0) {
		// Bullets
		item_ammo_bullets($itmn, $data);
	} elseif(strpos($itmk, 'GA') === 0) {
		// Arrows
		item_ammo_arrows($itmn, $data);
	} elseif(strpos($itmk, 'R') === 0) {
		// Radar (old)
		item_radar_old($itmn, $data);
	} elseif(strpos($itmk, 'C') === 0) {
		// Cure/Medicine
		item_cure($itmn, $data);
	} elseif(strpos($itmk, 'V') === 0) {
		// Skill books
		item_skillbook($itmn, $data);
	} elseif(strpos($itmk, 'M') === 0) {
		// Enhancement
		item_enhance($itmn, $data);
	} elseif(strpos($itmk, 'EW') === 0) {
		// Weather control
		item_weather($itmn, $data);
	} elseif(strpos($itmk, 'EE') === 0 || $itm == '移动PC') {
		// Electronic devices
		item_electronic($itmn, $data);
	} elseif(strpos($itmk, 'ER') === 0) {
		// Radar (new)
		item_radar_new($itmn, $data);
	} elseif(strpos($itmk, 'B') === 0) {
		// Battery
		item_battery($itmn, $data);
	} elseif(strpos($itmk, 'p') === 0) {
		// Gift boxes
		item_giftbox($itmn, $data);
	} elseif(strpos($itmk, 'ygo') === 0) {
		// YGO boxes
		item_ygo_box($itmn, $data);
	} elseif(strpos($itmk, 'fy') === 0) {
		// FY boxes
		item_fy_box($itmn, $data);
	} elseif(strpos($itmk, 'f99') === 0) {
		// Debug boxes
		item_debug_box($itmn, $data);
	} elseif($itmk == 'U') {
		// Trap detector
		item_trap_detector($itmn, $data);
	} elseif(strpos($itmk, '🎲') === 0) {
		// Dice items
		item_dice($itmn, $data);
	} elseif(strpos($itmk, '💝') === 0) {
		// NPC Platform
		item_platform($itmn, $data);
	} elseif(strpos($itmk, 'Y') === 0 || strpos($itmk, 'Z') === 0) {
		// Y/Z type items
		// Check if it's a tool item
		item_tool($itmn, $data);
		// Check if it's a weapon modification item
		item_weapon_mod($itmn, $data);
		// Check if it's an ending item
		item_ending($itmn, $data);
		// Check if it's a special effect item
		item_special_effect($itmn, $data);
		// Check if it's an NPC-related item
		item_npc($itmn, $data);
		// Check if it's a quest item
		item_quest($itmn, $data);
		// Check if it's a synthesis item
		item_synthesis($itmn, $data);
		// Check if it's a club card
		item_club_card($itmn, $data);
		// Check if it's a nachster booster item
		item_nachster_booster($itmn, $data);
		// Check if it's a test item
		item_test($itmn, $data);
	} elseif(strpos($itmk, '🎆') === 0) {
		// Fireworks
		item_fireworks($itmn, $data);
	} else {
		// Other items
		item_other($itmn, $data);
	}

	//元素大师使用提示纸条的特殊效果：
	if(strpos($ik,'💝')!==0 && !platform_is_recorder_item($ipara) && $club == 20 && strpos($itmk,'Y')===0 && strpos($itm,'提示纸条')!==false)
	{
		$log.="<br>就在你读完内容打算把纸条收起来时，你愕然发现纸条背面竟然还有字！<br><br>";
		include config('elementmix',$gamecfg);
		$log.= $emix_slip[array_rand($emix_slip)];
		//除商店纸条外：提供一条元素特征（TODO）、或一条固定配方、或一条随机属性组合
		$log .= "<br><span class='yellow'>附：见面有缘，再送你一条提示吧：<br>“将带有";
		if(!preg_match('/(A|B|C|D)/',$itm))
		{
				//野生纸条：给随机属性组合提示
			$submix_list = array_merge_recursive($submix_list,$gamevars['rand_emixsubres']);
		}
		$s_id = array_rand($submix_list);
		$s_result = $itemspkinfo[$submix_list[$s_id]['result']];
		foreach($submix_list[$s_id]['stuff'] as $skey) $log .= "【$itemspkinfo[$skey]】";
		$log .= "特征的元素组合起来，就有机会组合出【{$s_result}】属性。”</span><br>";
		//阅后即焚
		$log .="<br>……说这么多鬼记得住啊！<br>你思考了一下，决定把{$itm}吃进肚子里，以便慢慢消化其中的知识。<br>";
		if ($itms != $nosta) {
			$itms--;
		}
		# 将提示给到的次要特征组合加入笔记内
		if(empty($clbpara['elements']['info']['sd']['sd'.$s_id]))
			$clbpara['elements']['info']['sd']['sd'.$s_id] = 1;
	}

	// 消耗物品
	if ($itms <= 0 && $itm && $itms !== $nosta) {
		$log .= "<span class=\"red\">$itm</span>用光了。<br>";
		$itm = $itmk = $itmsk = '';
		$itme = $itms = 0;
	}

	// 检查成就
	include_once GAME_ROOT.'./include/game/achievement.func.php';
	check_item_achievement_rev($name,$i,$ie,$is,$ik,$isk);

	// 只有在没有设置二级菜单时才设置为command模式
	global $cmd;
	if (empty($cmd) && $mode != 'quest') {
		$mode = 'command';
	}
	return;
}
