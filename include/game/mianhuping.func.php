<?php
if(!defined('IN_GAME')) exit('Access Denied');

// 按名称和剩余数量统计，排除待拾取物品；无限耐久足够本次最多三份的用量。
function binglai_inventory($data)
{
	global $nosta;
	$items = Array('锅'=>0,'面包'=>0,'矿泉水'=>0,'曾经的荣光-橙黄'=>0);
	for($i=1;$i<=6;$i++) {
		$name = $data['itm'.$i] ?? '';
		if(!isset($items[$name])) continue;
		$count = $data['itms'.$i] ?? 0;
		$items[$name] += $count === $nosta ? 3 : max(0,(int)$count);
	}
	if(($data['wep'] ?? '') === '锅' && (($data['weps'] ?? 0) === $nosta || (int)($data['weps'] ?? 0)>0)) $items['锅']++;
	return $items;
}

function binglai_roll($data)
{
	global $binglai_recipes;
	$items = binglai_inventory($data);
	$available = Array();
	foreach($binglai_recipes as $id=>$recipe) {
		foreach($recipe['cost'] as $name=>$count) if($items[$name] < $count) continue 2;
		$available[] = $id;
	}
	if(!$available) return false;
	$id = $available[rand(0,count($available)-1)];
	$roll = $binglai_recipes[$id];
	if(isset($roll['random'])) {
		$name = $roll['random'];
		$count = rand(1,min(3,$items[$name]));
		$roll['cost'][$name] = $count;
		$bread = Array(1,2,4,7);
		$water = Array(1,2,3,4,6);
		if($id == 1) $roll['shield_pct'] = 10*$bread[$count-1];
		elseif($id == 2) $roll['mult'] = 1+0.1*$water[$count-1];
		elseif($id == 8) $roll['shield_pct'] = 10*Array(4,5,7)[$count-1];
		elseif($id == 9) {
			$points = 3*(3+$water[$count-1])/4;
			$roll['mult'] = 1+0.1*ceil($points);
			$roll['shield_pct'] = 10*floor($points);
		}
	}
	return $roll;
}

// 只在引擎校验技能与怒气成功后调用；耗尽最后一份食材也不会使本次技能失效。
function binglai_prepare(&$pa)
{
	global $log,$nosta;
	$roll = binglai_roll($pa);
	if(!$roll) return false;
	$spent = Array();
	foreach($roll['cost'] as $name=>$count) {
		$spent[] = $name.'×'.$count;
		for($i=1;$i<=6 && $count>0;$i++) {
			if($pa['itm'.$i] !== $name) continue;
			if($pa['itms'.$i] === $nosta) break;
			$used = min($count,max(0,(int)$pa['itms'.$i]));
			$pa['itms'.$i] -= $used;
			$count -= $used;
			if($pa['itms'.$i] <= 0) destory_single_item($pa,$i);
		}
	}
	$pa['binglai_roll'] = $roll;
	$pa['binglai_hits'] = $roll['hits']-1;
	$log .= '<span class="lime">'.$pa['nm'].'消耗了'.implode('、',$spent).'，制作了「'.$roll['name'].'」！</span><br>';
	if($roll['shield_pct']) {
		$pa['clbpara'] = get_clbpara($pa['clbpara']);
		$before = $pa['clbpara']['binglai_shield'] ?? 0;
		$pa['clbpara']['binglai_shield'] = min(2*$pa['mhp'],$before+round($pa['mhp']*$roll['shield_pct']/100));
		$gain = $pa['clbpara']['binglai_shield']-$before;
		$log .= '<span class="lime">'.$pa['nm'].'获得了'.$gain.'点临时生命，本场交战中先抵消伤害。</span><br>';
	}
	return true;
}

function binglai_absorb(&$target,$damage)
{
	global $log;
	if($damage <= 0) return $damage;
	$target['clbpara'] = get_clbpara($target['clbpara']);
	$shield = $target['clbpara']['binglai_shield'] ?? 0;
	if($shield > 0) {
		$absorbed = min($shield,$damage);
		$target['clbpara']['binglai_shield'] -= $absorbed;
		$damage -= $absorbed;
		$name = htmlspecialchars($target['nm'] ?? $target['name'],ENT_QUOTES,'UTF-8');
		$log .= '<span class="lime">'.$name.'的临时生命抵消了'.$absorbed.'点伤害。</span><br>';
	}
	return $damage;
}

// 主目标的份额交回标准战斗流程扣血；自己和旁观者在此扣血，不再追加完整主攻。
function binglai_splash(&$pa,&$pd,$pool)
{
	global $db,$tablepre,$log;
	if($pool <= 0) return 0;
	$place = (int)$pa['pls']; $group = (int)$pa['pgroup'];
	$aid = (int)$pa['pid']; $did = (int)$pd['pid'];
	$result = $db->query("SELECT * FROM {$tablepre}players WHERE pls='$place' AND pgroup='$group' AND hp>0 AND pid<>'$aid' AND pid<>'$did'");
	$players = Array();
	while($player = $db->fetch_array($result)) $players[] = $player;
	if($pa['hp'] > 0) $players[] = $pa;
	if($pd['hp'] > 0 && (int)$pd['pls'] === $place && (int)$pd['pgroup'] === $group) $players[] = $pd;
	if(!$players) return 0;
	shuffle($players);
	$players = array_slice($players,0,min(rand(2,5),count($players)));
	$share = ceil($pool/count($players));
	$main_damage = 0;
	$log .= '<span class="yellow">'.$pa['nm'].'将「'.$pa['binglai_roll']['name'].'」泼向四周，波及了'.count($players).'人！</span><br>';
	foreach($players as $player) {
		$player['clbpara'] = get_clbpara($player['clbpara']);
		$skills = $player['clbpara']['skill'] ?? Array();
		$damage = $player['def'] > 10000 || array_intersect(Array('fireseed3','fireseed4'),$skills) ? 1 : $share;
		$damage = min($player['hp']-1,binglai_absorb($player,$damage));
		$name = htmlspecialchars($player['name'],ENT_QUOTES,'UTF-8');
		$log .= $name.'受到了<span class="yellow">'.$damage.'</span>点伤害！<br>';
		if((int)$player['pid'] === $did) {
			$pd['clbpara'] = $player['clbpara'];
			$main_damage = $damage;
		} elseif((int)$player['pid'] === $aid) {
			$pa['hp'] -= $damage;
			$pa['clbpara'] = $player['clbpara'];
		} else {
			$hp = (int)($player['hp']-$damage);
			$pid = (int)$player['pid'];
			$para = bin2hex(json_encode($player['clbpara'],JSON_UNESCAPED_UNICODE));
			$db->query("UPDATE {$tablepre}players SET hp='$hp',clbpara=X'$para' WHERE pid='$pid'");
		}
	}
	return $main_damage;
}
