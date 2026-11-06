<?php
if(!defined('IN_GAME')) exit('Access Denied');

include_once GAME_ROOT.'./include/game/club25.func.php';

function club25_weapon_damage($data,$damage)
{
	if((int)$data['club'] !== 25) return $damage;
	$cfg = club25_config();
	return max(0,round($damage * $cfg['weapon_percent'] / 100));
}

// 只在成功移动的探索阶段领取；失败移动和原地探索都不消耗猫步。
function club25_take_catstep(&$data)
{
	if((int)$data['club'] !== 25 || empty($data['clbpara']['c25_catstep'])) return false;
	global $log;
	unset($data['clbpara']['c25_catstep']);
	$log .= '<span class="lime">你踮起脚尖溜走了。「猫步」保护这次移动，不会主动遇敌或踩雷。</span><br>';
	return true;
}

// 保留普通陷阱的同骰判定；新增的概率区间只会选中可怜猫的普通雷。
function club25_pick_trap($data,$rows,$dice,$maximum)
{
	if($dice >= $maximum || !$rows) return false;
	$all = $cats = array();
	foreach($rows as $row) {
		$para = get_itmpara($row['itmpara'] ?? '');
		if(isset($para['c25_mine']['pgroup']) && (int)$para['c25_mine']['pgroup'] !== (int)$data['pgroup']) continue;
		$all[] = $row;
		if($row['itmk'] === 'TO' && !empty($para['c25_mine']['owner'])) $cats[] = $row;
	}
	if(!$all) return false;
	if($all[0]['itmk'] === 'TOc') return $all[0];
	if($dice < calc_real_trap_obbs($data,count($all))) return $all[array_rand($all)];
	$cfg = club25_config();
	if($cats && $dice < min($maximum,$cfg['trap_floor'])) return $cats[array_rand($cats)];
	return false;
}

// 收网消耗一次攻击动作。机关自己结算扣血与归属，战斗外层只继续反击和保存。
function club25_net_attack(&$actor,&$target)
{
	global $log,$now;
	include_once GAME_ROOT.'./include/game/club25.items.php';
	$item = club25_net_take($actor,$target,$actor['c25_net_input'] ?? '');
	$actor['c25_net_settled'] = true;
	$actor['c25_net_stop'] = false;
	$actor['hitrate_times'] = 0;
	$actor['final_damage'] = 0;
	if(!$item) return 0;

	$para = get_itmpara($item['itmpara'] ?? '');
	$kind = $item['source'];
	$owner = is_numeric($item['itmsk'] ?? '') ? (int)$item['itmsk'] : 0;
	$trap_playerflag = $owner > 0;
	$trap_selflag = $owner === (int)$target['pid'];
	if($kind === 'food' && !empty($para['c25_hide']['owner'])) $owner = (int)$para['c25_hide']['owner'];
	if(!$owner) $owner = (int)$actor['pid'];
	$owner_data = NULL;
	if($owner === (int)$actor['pid']) $owner_data = &$actor;
	elseif($owner === (int)$target['pid']) $owner_data = &$target;
	$target['c25_net_active'] = 1;
	$damage = 0;
	$new_poison = false;
	$log .= '<span class="yellow">你拨动了现场的机关「'.htmlspecialchars($item['itm'],ENT_QUOTES,'UTF-8').'」。这东西是谁放的并不重要，重要的是现在有人要倒霉了。</span><br>';

	if($kind === 'trap') {
		$slot = array();
		foreach(array('itm','itmk','itme','itms','itmsk','itmpara') as $field) {
			$slot[$field] = $target[$field.'0'] ?? '';
			$target[$field.'0'] = $item[$field] ?? '';
		}
		if(diceroll(99) >= calc_trap_escape_rate($target,$trap_playerflag,$trap_selflag)) {
			$damage = calc_trap_damage($target,NULL,$trap_playerflag,$trap_selflag);
			$damage = check_trap_def_event($target,$damage,$trap_playerflag,$trap_selflag);
			$damage = club25_item_damage($target,$owner,$damage,'trap',$item['itm'],'c25_net',$owner_data);
			if($damage > 0 && $target['hp'] > 0 && empty($target['c25_item_revived']) && !empty($para['ai93_inf'])) {
				$before = $target['inf'];
				\revattr\get_inf_rev($target,$para['ai93_inf']);
				if(strpos($before,'p') === false && strpos($target['inf'],'p') !== false) $target['clbpara']['c25_poison_owner'] = $owner;
			}
		} else $log .= '<span class="yellow">对方察觉了引线，躲开了这次爆炸。</span><br>';
		foreach($slot as $field=>$value) $target[$field.'0'] = $value;
	} else {
		if(!empty($para['c25_hide'])) {
			$damage = club25_item_damage($target,$owner,max(0,(int)$para['c25_hide']['damage']),'food',$item['itm'],'c25_hide',$owner_data);
			if($damage > 0 && $target['hp'] > 0 && empty($target['c25_item_revived'])) \revattr\get_inf_rev($target,'f');
		}
		if($target['hp'] > 0 && empty($target['c25_item_revived']) && strpos($item['itmk'],'P') === 0) {
			$poison_owner = is_numeric($item['itmsk'] ?? '') ? (int)$item['itmsk'] : 0;
			if(!$poison_owner) $poison_owner = (int)$actor['pid'];
			$poison_data = NULL;
			if($poison_owner === (int)$actor['pid']) $poison_data = &$actor;
			elseif($poison_owner === (int)$target['pid']) $poison_data = &$target;
			$attrs = array_merge(\revattr\get_equip_ex_array($target),\revattr\get_wep_ex_array($target));
			$blocked = !check_skill_unlock('buff_shield',$target);
			if((in_array('q',$attrs) || in_array('a',$attrs)) && diceroll(99) < 90) $blocked = true;
			$new_poison = !$blocked && \revattr\get_inf_rev($target,'p');
			if($new_poison) {
				$target['clbpara']['c25_poison_owner'] = $poison_owner;
			}
			$multiplier = substr($item['itmk'],2,1) === '2' ? 2 : (substr($item['itmk'],2,1) === '1' ? 1.5 : 1);
			$damage += club25_item_damage($target,$poison_owner,round($item['itme']*$multiplier),'food',$item['itm'],'poison',$poison_data);
		} elseif($target['hp'] > 0 && empty($target['c25_item_revived']) && strpos($item['itmk'],'H') === 0) {
			// 幻影先咬，活下来的 NPC 仍然吃到了真正的补给。
			if(in_array(substr($item['itmk'],1,1),array('H','B'))) $target['hp'] = min($target['mhp'],$target['hp']+$item['itme']);
			if(in_array(substr($item['itmk'],1,1),array('S','B'))) $target['sp'] = min($target['msp'],$target['sp']+$item['itme']);
		}
	}
	unset($target['c25_net_active']);
	$actor['hitrate_times'] = ($damage > 0 || $new_poison) ? 1 : 0;
	$actor['final_damage'] = $damage;
	$actor['c25_net_stop'] = $target['hp'] <= 0 || !empty($target['c25_item_revived']);
	unset($target['c25_item_revived']);
	if($actor['hitrate_times']) {
		expup_rev($actor,$target,1);
		$actor['wd'] += club25_config()['reward_wd'];
	}
	rgup_rev($actor,$target,1);
	return $damage;
}
