<?php
if(!defined('IN_GAME')) exit('Access Denied');

function club24_config()
{
	return include config('club24cfg',1);
}

// 地图组以地点表为准；战斗显示会把隐藏地点并入 plsinfo，须先排除它们。
function club24_places($data,$move=false)
{
	global $plsinfo,$hplsinfo,$arealist,$areanum,$hack;
	$group = (int)$data['pgroup'];
	$places = $group && isset($hplsinfo[$group]) ? array_keys($hplsinfo[$group]) : array_keys($plsinfo);
	if(!$group) foreach($hplsinfo as $hidden) $places = array_diff($places,array_keys($hidden));
	$allowed = Array();
	foreach($places as $place) {
		if(!is_numeric($place) || $place < 0 || $place == 254) continue;
		if($move) {
			if($place == $data['pls']) continue;
			if(!$group && !$hack && array_search($place,$arealist) <= $areanum) continue;
			if(function_exists('ruleset_move_allowed_hook') && !ruleset_move_allowed_hook($data,$place)) continue;
		}
		$allowed[] = (int)$place;
	}
	return $allowed;
}

function club24_remote_npc($data,$target,$corpse=false)
{
	$available = $target['hp'] > 0 || ($corpse && in_array($data['action'],Array('corpse','focus'),true)
		&& $data['bid'] == $target['pid'] && $target['bid'] == $data['pid']);
	return $data['club'] == 24 && $target['type'] > 0 && $available
		&& (int)$data['pgroup'] == (int)$target['pgroup']
		&& in_array((int)$target['pls'],club24_places($data),true);
}

function club24_item_exempt($name,$kind)
{
	$cfg = club24_config();
	return $kind == 'ZB' || $kind == 'VS' || strpos($name,'【歌单】') === 0
		|| in_array($name,$cfg['exempt_items'],true);
}

// 拾取、普通合成和重铸共用。锁定项为 E/D/S；合成只改效果、耐久。
function club24_roll_item(&$data,$slot,$lock='',$attributes=true)
{
	global $nosta;
	$kind = $data['itmk'.$slot];
	if(empty($data['itms'.$slot]) || !preg_match('/^[WDAHP]/',$kind)
		|| club24_item_exempt($data['itm'.$slot],$kind)) return false;
	$cfg = club24_config();
	if($lock != 'E') $data['itme'.$slot] = rand(1,max(1,(int)min(4294967295,$data['itme'.$slot]*$cfg['item_effect_mult'])));
	if($lock != 'D' && $data['itms'.$slot] !== $nosta)
		$data['itms'.$slot] = rand(1,max(1,(int)min(9999999999,$data['itms'.$slot]*$cfg['item_durability_mult'])));
	if($attributes && $lock != 'S' && preg_match('/^[WDA]/',$kind)) {
		$old = get_itmsk_array($data['itmsk'.$slot]);
		$bound = in_array('v',$old,true) ? 'v' : '';
		$key = $kind[0] == 'W' ? 'weapon_attrs' : ($kind[0] == 'D' ? 'armor_attrs' : 'accessory_attrs');
		$pool = array_values(array_diff(array_unique(str_split($cfg[$key])),Array('X','v')));
		shuffle($pool);
		$count = max(1,count(array_diff($old,Array('v'))));
		$data['itmsk'.$slot] = implode('',array_slice($pool,0,$count)).$bound;
	}
	return true;
}

function club24_item_signature($data)
{
	$item = Array();
	foreach(Array('itm','itmk','itme','itms','itmsk') as $key) $item[] = (string)$data[$key.'0'];
	$item[] = get_itmpara($data['itmpara0']);
	return sha1(json_encode($item,JSON_UNESCAPED_UNICODE));
}

function club24_pickup(&$data,$source)
{
	global $log;
	if(($data['club'] ?? 0) != 24 || empty($data['itms0'])) return;
	// 满包或补给合并界面会跨请求保留 itm0，不允许重复提交免费重投。
	if(($data['clbpara']['c24_pickup'] ?? '') === club24_item_signature($data)) return;
	if(club24_roll_item($data,0,'',$source != 'mix')) $log .= '骰子改写了这件物品！<br>';
	$data['clbpara']['c24_pickup'] = club24_item_signature($data);
}

function club24_shop_price($price,&$data,$reroll=false)
{
	if($data['club'] != 24) return $price;
	if($reroll || !isset($data['clbpara']['c24_shopmult'])) {
		$cfg = club24_config();
		$data['clbpara']['c24_shopmult'] = rand((int)round($cfg['shop_mult_min']*100),(int)round($cfg['shop_mult_max']*100))/100;
	}
	return max(1,(int)ceil($price*$data['clbpara']['c24_shopmult']));
}

function club24_allin_factor($data)
{
	if($data['club'] != 24 || !get_skilllvl('c24_allin',$data)
		|| !get_skillpara('c24_allin','active',$data['clbpara'])) return 1;
	$cfg = club24_config();
	$factor = rand((int)round($cfg['allin_fluc_min']*100),(int)round($cfg['allin_fluc_max']*100))/100;
	return rand(1,100) <= $cfg['allin_neg_pct'] ? -$factor : $factor;
}

// 升级的收益存于现有 skillpara；等级、锁定权限、开关本身不参加赌博。
function club24_upgrade($sk,&$data)
{
	$cfg = club24_config();
	$level = get_skilllvl($sk,$data)+1;
	$gains = Array(
		'c24_verdict' => Array(1=>Array('faces'=>1),2=>Array('faces'=>1),3=>Array('rageback'=>10)),
		'c24_roulette' => Array(2=>Array('chambers'=>1),3=>Array('chambers'=>1)),
		'c24_gamble' => Array(2=>Array('rerolls'=>1),3=>Array('ragecut'=>$cfg['gamble_rage']-$cfg['gamble_rage_lv3'])),
		'c24_reforge' => Array(3=>Array('ragecut'=>$cfg['reforge_rage']-$cfg['reforge_rage_lv3'])),
	);
	if(isset($gains[$sk][$level])) {
		$factor = club24_allin_factor($data);
		foreach($gains[$sk][$level] as $key=>$gain) {
			$data['clbpara']['skillpara'][$sk][$key] = get_skillpara($sk,$key,$data['clbpara'])+(int)round($gain*$factor);
		}
	}
	return 1;
}

function club24_skill_rage($sk,$data)
{
	$cfg = club24_config();
	if($sk == 'c24_roulette') return $cfg['roulette_rage'];
	if($sk == 'c24_coin' || $sk == 'c24_dice') return 10;
	$base = $sk == 'c24_gamble' ? $cfg['gamble_rage'] : $cfg['reforge_rage'];
	return max(0,$base-get_skillpara($sk,'ragecut',$data['clbpara']));
}

function club24_skill($sk,&$data,$input='')
{
	global $log,$db,$tablepre,$now;
	if($data['club'] != 24 || $data['type'] || $data['hp'] <= 0 || check_skill_unlock($sk,$data)) {
		$log .= '你现在无法使用这个技能。<br>'; return 0;
	}
	if($sk == 'c24_allin') {
		$active = !get_skillpara($sk,'active',$data['clbpara']);
		set_skillpara($sk,'active',(int)$active,$data['clbpara']);
		$log .= $active ? '你决定梭哈，把此后的升级收益交给骰子！<br>' : '你收起了赌注，关闭了梭哈。<br>';
		return 1;
	}
	if(!in_array($sk,Array('c24_reforge','c24_coin','c24_dice'),true)) return 0;
	$cost = club24_skill_rage($sk,$data);
	if($data['rage'] < $cost) { $log .= "怒气不足，需要{$cost}点怒气！<br>"; return 0; }
	if($sk == 'c24_reforge') {
		if(!is_string($input) || !preg_match('/^([0-6])(?:\s*([EDS]))?$/i',trim($input),$match)) {
			$log .= '请填写背包槽位0～6；二级起可附加一个锁定项E、D或S，例如3E。<br>'; return 0;
		}
		$slot = (int)$match[1]; $lock = isset($match[2]) ? strtoupper($match[2]) : '';
		if($lock && get_skilllvl($sk,$data) < 2) { $log .= '重铸达到二级后才能锁定一项。<br>'; return 0; }
		if(empty($data['itms'.$slot]) || !preg_match('/^[WDA]/',$data['itmk'.$slot])
			|| club24_item_exempt($data['itm'.$slot],$data['itmk'.$slot])) {
			$log .= '请选择可以重铸的武器、防具或饰品；剧情必需品不能重铸。<br>'; return 0;
		}
		club24_roll_item($data,$slot,$lock);
		if($slot == 0) $data['clbpara']['c24_pickup'] = club24_item_signature($data);
		$log .= "你启动了装备轮盘！{$data['itm'.$slot]}的效果与耐久被骰子改写！<br>";
	} else {
		if($sk == 'c24_coin') {
			$face = rand(0,1) ? '【正面】！天意如此！' : '【反面】！随机数大神发话了！';
			$message = "【掷币】{$data['name']}掷出一枚硬币——{$face}";
		} else {
			$dice = rand(1,20);
			$message = "【掷骰】{$data['name']}掷出了 D20——【{$dice}】！";
			if($dice == 20) $message .= '自然20！这局我最大！';
			if($dice == 1) $message .= '……1也是叛军的一页！';
		}
		$db->array_insert($tablepre.'chat',Array('type'=>'0','time'=>$now,'send'=>$data['name'],'recv'=>'','msg'=>$message));
		$log .= htmlspecialchars($message,ENT_QUOTES,'UTF-8').'<br>';
	}
	$data['rage'] -= $cost;
	return 1;
}

function club24_verdict(&$pa,&$pd,$damage)
{
	global $log;
	if($pa['club'] != 24 || $pa['type'] || !empty($pa['is_counter']) || !empty($pa['c24_verdict_done'])
		|| check_skill_unlock('c24_verdict',$pa)) return $damage;
	$pa['c24_verdict_done'] = true;
	$dice = rand(1,6);
	$text = "裁决的骰子滚动了——【{$dice}】！";
	if($dice == 1) {
		$damage = (int)floor($damage/2);
		$pa['rage'] = max(0,min(255,$pa['rage']+get_skillpara('c24_verdict','rageback',$pa['clbpara'])));
		$text .= '攻击软弱无力，伤害减半！';
	} elseif($dice > 6-get_skillpara('c24_verdict','faces',$pa['clbpara'])) {
		$pa['c24_rapid'] = true;
		$text .= '连续攻击！';
	} else $text .= '无事发生。';
	$log .= '<span class="yellow">'.$text.'</span><br>';
	if(!$pd['type']) $pd['logsave'] = ($pd['logsave'] ?? '').$text.'<br>';
	return $damage;
}

// 战斗技的原始纯粹伤害。返回 NULL 时继续普通武器攻击。
function club24_battle_damage(&$pa,&$pd,$active)
{
	global $log;
	if($pa['club'] != 24) return NULL;
	if(isset($pa['bskill_c24_roulette'])) {
		$cfg = club24_config();
		$sides = max(2,6-get_skillpara('c24_roulette','chambers',$pa['clbpara']));
		$dice = rand(1,$sides);
		if($pa['wep_kind'] == 'N') {
			$cap = $cfg['roulette_unarmed_cap'];
			$damage = rand(min($cap,max(0,(int)$pa['att'])),$cap);
		} else {
			$damage = rand(max(0,(int)round($pa['wepe']*$cfg['roulette_mult_low'])),max(0,(int)round($pa['wepe']*$cfg['roulette_mult_high'])));
		}
		$pa['c24_selfshot'] = $dice != $sides;
		$log .= "左轮枪在你手中旋转——【{$dice}】！".($pa['c24_selfshot'] ? '子弹没有飞向敌人——它打中了你！' : "子弹贯穿了{$pd['nm']}！").'<br>';
		return $damage;
	}
	if(isset($pa['bskill_c24_gamble'])) {
		// 沿用物理原始伤害公式，基础防御按1计算，不读取敌人的防御或减伤。
		$pa['base_att'] = \revattr\get_base_att($pa,$pd,$active);
		$target = $pd; $target['base_def'] = 1;
		$base = max(0,(int)(\revattr\get_original_dmg_rev($pa,$target,$active)+\revattr\get_original_fix_dmg_rev($pa,$target,$active)));
		$rolled = diceroll($base);
		$rerolls = get_skillpara('c24_gamble','rerolls',$pa['clbpara']);
		for($i=0;$i<abs($rerolls);$i++) {
			$next = diceroll($base);
			$rolled = $rerolls > 0 ? max($rolled,$next) : min($rolled,$next);
		}
		$log .= "你豪赌一把！本次伤害被骰子重掷：{$base} → {$rolled}！<br>";
		return $rolled;
	}
	return NULL;
}
