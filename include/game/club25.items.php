<?php
if(!defined('IN_GAME')) exit('Access Denied');

// 机关和补给的其他参数并存；清除机关时保留物品本身的参数及原存储形态。
function club25_item_write_para(&$field,$para)
{
	$field = is_array($field) ? $para : (empty($para) ? '' : json_encode($para,JSON_UNESCAPED_UNICODE));
}

function club25_item_marked($para)
{
	$para = get_itmpara($para);
	return isset($para['c25_hide']) || isset($para['c25_trip']) || isset($para['c25_mine']) || isset($para['c25_poison_done']);
}

function club25_item_merge_allowed($first,$second)
{
	if(!club25_item_marked($first) && !club25_item_marked($second)) return true;
	return get_itmpara($first) == get_itmpara($second);
}

// 调用方持有来源玩家数组时传入同一引用，避免收网期间旧战斗数组覆盖击杀和成长。
function club25_item_damage(&$victim,$owner,$damage,$kind,$item,$cause='trap',&$owner_data=NULL)
{
	global $log,$now,$pdata;
	$owner = max(0,intval($owner));
	$damage = max(0,intval(round($damage)));
	$external_owner = false;
	if($owner && $owner == intval($victim['pid'])) {
		$owner_data = &$victim;
	} elseif(!is_array($owner_data) || intval($owner_data['pid'] ?? 0) != $owner) {
		if($owner && isset($pdata['pid']) && intval($pdata['pid']) == $owner) $owner_data = &$pdata;
		elseif($owner) {
			$owner_data = fetch_playerdata_by_pid($owner);
			$external_owner = is_array($owner_data);
		}
	}
	$skills = $victim['clbpara']['skill'] ?? Array();
	if(in_array('fireseed4',$skills) || (in_array($kind,Array('trap','trip')) && in_array('npc_perfectspy',$skills))) $damage = 0;
	if(intval($victim['club'] ?? 0) == 25) {
		$cfg = club25_config();
		$damage = intval(round($damage * $cfg['hazard_percent'] / 100));
	}
	if(function_exists('ruleset_damage_immunity_hook')) {
		$adjusted = ruleset_damage_immunity_hook($victim,$kind == 'food' ? 'poison' : 'trap',$damage);
		if($adjusted !== NULL) $damage = max(0,intval($adjusted));
	}
	if(function_exists('severe_enabled') && severe_enabled()) {
		$damage=severe_negative_damage($victim,$damage);
		if(!empty($victim['c25_net_active']) && is_array($owner_data)) $damage=erc_damage($owner_data,$victim,$damage);
	}
	$victim['hp'] -= $damage;
	$maker = is_array($owner_data) ? $owner_data['name'] : '不知躲在哪里的可怜猫';
	$safe_maker = htmlspecialchars($maker,ENT_QUOTES,'UTF-8');
	$safe_item = htmlspecialchars($item,ENT_QUOTES,'UTF-8');
	if($cause == 'c25_hide') {
		$log .= "你刚准备咬下<span class='yellow'>{$safe_item}</span>，一个<span class='yellow'>{$safe_maker}</span>的幻影突然窜了出来！<br>果然，力是相互的！幻影狠狠地咬了你一口，你疼得龇牙咧嘴，受到了<span class='dmg'>{$damage}</span>点伤害！<br>";
		addnews($now,'c25_hide',$victim['name'],$maker,$item,$damage);
	} elseif($cause == 'poison') {
		$log .= "现场的<span class='yellow'>{$safe_item}</span>里掺着毒药，令对方受到了<span class='dmg'>{$damage}</span>点伤害！<br>";
		addnews($now,'poison',$victim['name'],$maker,$item,$victim['nick']);
	} elseif(!empty($victim['c25_net_active'])) {
		$log .= "现场的<span class='yellow'>{$safe_item}</span>被引爆，令对方受到了<span class='dmg'>{$damage}</span>点伤害！<br>";
	} else {
		$log .= "刚把<span class='yellow'>{$safe_item}</span>拿稳，一声娇滴滴的“抓到你啦”就从里面传来——诡雷炸开了！你受到了<span class='dmg'>{$damage}</span>点伤害！<br>";
		addnews($now,'c25_trip',$victim['name'],$maker,$item,$damage);
	}
	club25_effect_reward($victim,$owner,$kind,$damage,false,$item,$owner_data);
	if($victim['hp'] <= 0) {
		if(is_array($owner_data)) $owner_data['final_damage'] = $damage;
		club25_item_kill($victim,$owner,$item,$cause,$owner_data);
	}
	if($external_owner) player_save($owner_data);
	return $damage;
}

// 行动毒伤也使用同一来源，但不能再走机关减伤或再次领取一次触发奖励。
function club25_item_kill(&$victim,$owner,$item,$cause,&$owner_data=NULL)
{
	global $pdata;
	if($victim['hp'] > 0) return;
	$owner = max(0,intval($owner));
	$external_owner = false;
	if($owner && $owner == intval($victim['pid'])) $owner_data = &$victim;
	elseif(!is_array($owner_data) || intval($owner_data['pid'] ?? 0) != $owner) {
		if($owner && isset($pdata['pid']) && intval($pdata['pid']) == $owner) $owner_data = &$pdata;
		elseif($owner) {
			$owner_data = fetch_playerdata_by_pid($owner);
			$external_owner = is_array($owner_data);
		}
	}
	include_once GAME_ROOT.'./include/state.func.php';
	if($cause == 'c25_net') $cause = 'trap';
	if(is_array($owner_data) && $owner != intval($victim['pid'])) {
		$owner_data['wep_name'] = $item;
		if(!empty($victim['c25_net_active'])) {
			include_once GAME_ROOT.'./include/game/revcombat.func.php';
			$old_kind = $owner_data['wep_kind'] ?? NULL;
			$owner_data['wep_kind'] = $cause;
			\revcombat\rev_combat_result($owner_data,$victim,0);
			if($old_kind === NULL) unset($owner_data['wep_kind']);
			else $owner_data['wep_kind'] = $old_kind;
			if($victim['hp'] > 0) $victim['c25_item_revived'] = 1;
		} else {
			$last = pre_kill_events($owner_data,$victim,0,$cause);
			if(!revive_process($owner_data,$victim,0)) final_kill_events($owner_data,$victim,0,$last);
		}
	} else {
		// 自己的机关或无主毒物也保留复活判定，但不把自伤记成击杀。
		$natural_source = is_array($owner_data) ? $owner_data : create_dummy_playerdata();
		if(!revive_process($natural_source,$victim,0)) death($cause,$owner == intval($victim['pid']) ? $victim['name'] : '',0,$item,$victim);
		if($victim['hp'] > 0 && !empty($victim['c25_net_active'])) $victim['c25_item_revived'] = 1;
	}
	if($external_owner) player_save($owner_data);
}

// 只在补给已通过“可以使用”的检查后调用；致死时也确实消耗这一份。
function club25_item_hide($itmn,&$data)
{
	global $nosta,$log;
	$field = 'itmpara'.$itmn;
	$para = get_itmpara($data[$field] ?? '');
	if(empty($para['c25_hide']) || !preg_match('/^[HP][HSB]/',$data['itmk'.$itmn] ?? '')) return true;
	$mark = $para['c25_hide'];
	unset($para['c25_hide']);
	club25_item_write_para($data[$field],$para);
	$damage = club25_item_damage($data,intval($mark['owner'] ?? 0),$mark['damage'] ?? 0,'food',$data['itm'.$itmn],'c25_hide');
	if($data['hp'] <= 0) {
		if($data['itms'.$itmn] !== $nosta) {
			$data['itms'.$itmn]--;
			if($data['itms'.$itmn] <= 0) {
				$data['itm'.$itmn] = $data['itmk'.$itmn] = $data['itmsk'.$itmn] = $data[$field] = '';
				$data['itme'.$itmn] = $data['itms'.$itmn] = 0;
			}
		}
		player_save($data);
		return false;
	}
	if($damage > 0) {
		include_once GAME_ROOT.'./include/game/revattr.func.php';
		if(\revattr\get_inf_rev($data,'f')) $log .= "幻影还顺口叼住了你的脚腕，导致你<span class='red'>足部受伤</span>了！<br>";
	}
	$log .= "幻影一边嘲笑着你，一边钻进了空气里。可怜的到底是谁呀？<br>";
	return true;
}

// slot 为已成功取得的 itm1..itm6 或装备位；发现于 itm0 本身不会调用此函数。
function club25_item_trip(&$data,$slot)
{
	global $log,$now;
	if(is_numeric($slot)) $slot = 'itm'.intval($slot);
	if(!preg_match('/^(itm[1-6]|wep|arb|arh|ara|arf|art)$/',$slot)) return;
	$field = strpos($slot,'itm') === 0 ? 'itmpara'.substr($slot,3) : $slot.'para';
	$para = get_itmpara($data[$field] ?? '');
	if(empty($para['c25_trip'])) return;
	$mark = $para['c25_trip'];
	unset($para['c25_trip']);
	club25_item_write_para($data[$field],$para);
	$item = $data[$slot];
	$owner = intval($mark['owner'] ?? 0);
	if(($mark['mode'] ?? 'damage') != 'poison') {
		club25_item_damage($data,$owner,$mark['damage'] ?? 0,'trip',$item);
		return;
	}
	include_once GAME_ROOT.'./include/game/revattr.func.php';
	$attrs = array_merge(\revattr\get_equip_ex_array($data),\revattr\get_wep_ex_array($data));
	$blocked = !check_skill_unlock('buff_shield',$data);
	if((in_array('q',$attrs) || in_array('a',$attrs)) && diceroll(99) < 90) $blocked = true;
	$new_poison = !$blocked && \revattr\get_inf_rev($data,'p');
	if($new_poison) {
		unset($data['clbpara']['c25_poison_owner']);
		if($owner) $data['clbpara']['c25_poison_owner'] = $owner;
	}
	$log .= '物品里的细线一绷，毒雾伴着一声窃笑扑了出来！'.($new_poison ? "你<span class='purple'>中毒</span>了！" : '不过，你没有受到新的毒性影响。').'<br>';
	addnews($now,'c25_trip',$data['name'],$mark['name'] ?? '可怜猫',$item,0);
	club25_effect_reward($data,$owner,'trip',0,(bool)$new_poison,$item);
}
