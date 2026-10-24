<?php
if(!defined('IN_GAME')) exit('Access Denied');

function fcg_cards()
{
	static $cards;
	if($cards === null) $cards = require GAME_ROOT.'./gamedata/ruleset/FCG/cards.php';
	return $cards;
}

function fcg_colors() { return array('W','R','Y','G','B'); }

function fcg_starter_deck()
{
	return array(0,0,4,4,8,8,12,12,16,16,20,20,2,6,10,14,18,22,24,24,26,28,30,32,34,36,38,40,42,42);
}

function fcg_validate_deck($deck, $collection)
{
	if(!is_array($deck) || count($deck) < 25 || count($deck) > 100) throw new InvalidArgumentException('卡组须有 25～100 张卡。');
	$cards = fcg_cards(); $counts = array();
	foreach($deck as $id) {
		if(!is_int($id) || !isset($cards[$id])) throw new InvalidArgumentException('卡组包含未知卡片。');
		$counts[$id] = ($counts[$id] ?? 0) + 1;
		if($counts[$id] > ($collection[$id] ?? 0)) throw new InvalidArgumentException('持有的「'.$cards[$id]['name'].'」数量不足。');
		if(!$cards[$id]['unlimited'] && $counts[$id] > 7) throw new InvalidArgumentException('非能量卡每个卡号最多放入 7 张。');
	}
	return array_values($deck);
}

function fcg_side($deck, $hp = 80)
{
	shuffle($deck);
	return array('hp'=>$hp,'atk'=>0,'def'=>0,'regen'=>0,'energy'=>array_fill_keys(fcg_colors(),1),
		'gen'=>array_fill_keys(fcg_colors(),1),'hand'=>array_splice($deck,0,3),'deck'=>$deck,'monster'=>null,
		'bonus'=>0,'pierce'=>0,'thorns'=>array(0,0),'evade'=>0,'free'=>array());
}

function fcg_opponents()
{
	static $opponents;
	if($opponents !== null) return $opponents;
	$names = array('覆唱的篝火','爱恋的埋火','怜悯的永火','执念的残火','希望的焰火','羞怯的萤火','守望的炉火','缄默的暗火','漫游的流火','憧憬的蜃火','倾听的灯火','戏谑的燐火','早慧的薪火','倦怠的灰火','渴求的鬼火','傲慢的炫火','孤绝的冥火');
	$cards = fcg_cards(); $opponents = array();
	foreach($names as $i=>$name) {
		$level = $i < 5 ? ($i < 3 ? 1 : 2) : 3 + intdiv($i-5,3);
		$pairs = $i < 5 ? array(13+$i,18+$i,23+$i) : range(28+4*($i-5),31+4*($i-5));
		$colors = array(); $deck = array();
		foreach($pairs as $pair) {
			$colors[$cards[2*($pair-1)]['color']] = true;
			for($n=0;$n<4;$n++) $deck[] = 2*($pair-1);
			if($i>=5) for($n=0;$n<2;$n++) $deck[] = 2*($pair-1)+1;
		}
		foreach(array_keys($colors) as $color) {
			$base = 4*array_search($color,fcg_colors(),true);
			for($n=0;$n<5;$n++) $deck[] = $base;
			for($n=0;$n<3;$n++) $deck[] = $base+2;
		}
		while(count($deck)<30) $deck[] = 20;
		$opponents[$i] = array('id'=>$i,'name'=>'✦'.$name,'level'=>$level,'deck'=>$deck,
			'portrait'=>$i<5 ? 'img/n_'.(34+$i).'.gif' : 'img/fcg/fireseeds/'.sprintf('%02d',$i-4).'.png',
			'hp'=>80+10*max(0,$level-2));
	}
	return $opponents;
}

function fcg_new_match($deck, $opponent, $now)
{
	$all = fcg_opponents();
	if(!isset($all[$opponent])) throw new InvalidArgumentException('找不到这位牌手。');
	$o = $all[$opponent];
	return array('id'=>bin2hex(random_bytes(12)),'opponent'=>$opponent,'turn'=>1,'deadline'=>$now+10,
		'ai_at'=>$now+2,'sides'=>array(fcg_side($deck),fcg_side($o['deck'],$o['hp'])),
		'drop_pool'=>$o['deck'],'last_spell'=>null,'result'=>null,'rewarded'=>false,'log'=>array('小种火把牌堆推到了你的面前。'));
}

function fcg_log(&$m, $line)
{
	$m['log'][] = $line;
	if(count($m['log']) > 40) $m['log'] = array_slice($m['log'],-40);
}

function fcg_draw(&$s, $n = 1)
{
	for($i=0;$i<$n;$i++) {
		if(count($s['hand']) >= 6 || !$s['deck']) $s['regen']--;
		else $s['hand'][] = array_shift($s['deck']);
	}
}

function fcg_energy(&$s, $color, $n, $kind = 'energy')
{
	foreach($color === 'ALL' ? fcg_colors() : array($color) as $c) {
		$s[$kind][$c] = max($kind === 'gen' ? 1 : 0, min(99,$s[$kind][$c]+$n));
	}
}

function fcg_steal(&$a, &$b, $stat, $n)
{
	if($stat === 'energy' || $stat === 'gen_max') {
		$key = $stat === 'energy' ? 'energy' : 'gen';
		$c = array_search(max($b[$key]),$b[$key],true);
		$n = min($n,max(0,$b[$key][$c]-($key==='gen' ? 1 : 0)));
		fcg_energy($b,$c,-$n,$key); fcg_energy($a,$c,$n,$key);
	} else {
		$n = min($n,max(0,$b[$stat])); $b[$stat]-=$n; $a[$stat]+=$n;
	}
}

// Card effects are a fixed vocabulary from the approved card table, never executable PHP.
function fcg_tokens($ops)
{
	return array_values(array_filter(array_map('trim',explode(';',$ops)),'strlen'));
}

function fcg_ability($monster, $key, $default = 0)
{
	if(!$monster) return $default;
	foreach($monster['abilities'] as $op) {
		if($op === $key) return 1;
		if(preg_match('/^'.preg_quote($key,'/').'\(([^)]*)\)$/',$op,$m)) {
			$values = explode(',',$m[1]); return count($values) === 1 ? (float)$values[0] : array_map('floatval',$values);
		}
	}
	return $default;
}

function fcg_monster_death(&$a, &$b)
{
	if(!$a['monster'] || $a['monster']['hp'] > 0) return;
	$b['hp'] -= fcg_ability($a['monster'],'rebirth_dmg_enemy');
	if(fcg_ability($a['monster'],'rebirth_once') && empty($a['monster']['reborn'])) {
		$a['monster']['reborn'] = true;
		$a['monster']['hp'] = $a['monster']['maxhp'];
	} else $a['monster'] = null;
}

function fcg_effect(&$m, $side, $card, $mirroring = false)
{
	$a = &$m['sides'][$side]; $b = &$m['sides'][1-$side];
	$dead_monster = null;
	foreach(fcg_tokens($card['ops']) as $token) {
		if(strpos($token,'scale_by(') === 0) {
			switch($card['pair']) {
				case 27:
					$n = count(array_filter($a['energy'],function($e) { return $e > 0; }));
					$a['atk'] += $n*($card['upgraded'] ? 3 : 2); $a['def'] += $n*($card['upgraded'] ? 3 : 2);
					$a['hp'] += $n*($card['upgraded'] ? 6 : 5); if($card['upgraded']) fcg_draw($a,2); break;
				case 46: $b['hp'] -= $b['atk']; break;
				case 50: $n = max($b['atk'],$b['def']); $a['atk'] += $n; $a['def'] += $n; break;
				case 70: $b['hp'] -= (int)min($card['upgraded'] ? 24 : 18,array_sum($b['energy'])*($card['upgraded'] ? 2/3 : 1/2)); break;
				case 75: $b['hp'] -= array_sum($a['gen'])+array_sum($b['gen'])+($card['upgraded'] ? 8 : 0); break;
				default: throw new LogicException('Unsupported FCG scale: '.$card['id']);
			}
			continue;
		}
		if(!preg_match('/^(\w+)(?:\((.*)\))?$/',$token,$parts)) throw new LogicException('Unsupported FCG effect: '.$token);
		$op = $parts[1]; $v = isset($parts[2]) ? array_map('trim',explode(',',$parts[2])) : array();
		$n = isset($v[0]) ? (float)$v[0] : 0;
		switch($op) {
			case 'add_gen': case 'add_energy':
				if($v[0] === 'enemy_random') fcg_energy($b,fcg_colors()[random_int(0,4)],(int)$v[1]);
				else fcg_energy($a,$v[0],(int)$v[1],$op==='add_gen' ? 'gen' : 'energy'); break;
			case 'gain_atk': case 'gain_def': case 'gain_hp': case 'lose_hp': case 'add_regen': case 'add_poison':
				$key = array('gain_atk'=>'atk','gain_def'=>'def','gain_hp'=>'hp','lose_hp'=>'hp','add_regen'=>'regen','add_poison'=>'regen')[$op];
				$target = $v[0] === 'enemy' ? 1-$side : $side;
				$m['sides'][$target][$key] += (float)$v[1]*(in_array($op,array('lose_hp','add_poison'),true) ? -1 : 1); break;
			case 'gain_atk_def': $a['atk']+=(float)$v[1]; $a['def']+=(float)$v[2]; break;
			case 'turn_bonus_dmg': $a['bonus'] += $n; break;
			case 'draw': fcg_draw($a,(int)$n); break;
			case 'weaken_enemy': $b['atk']=max(0,$b['atk']-$n); $b['def']=max(0,$b['def']-(float)$v[1]); break;
			case 'sunder_enemy': $b['def']=max(0,$b['def']-$n); if($b['monster']) $b['monster']['def']=max(0,$b['monster']['def']-$n); break;
			case 'pierce_enemy': $b['hp']-=$n; break;
			case 'freeze_enemy_monster': if($b['monster']) $b['monster']['frozen']=max($b['monster']['frozen'],(int)$n); break;
			case 'gain_monster_def': case 'gain_monster_hp':
				if($a['monster']) $a['monster'][$op==='gain_monster_def' ? 'def' : 'hp']+=(float)$v[1]; break;
			case 'lose_monster_hp': if($b['monster']) $b['monster']['hp']-=(float)$v[1]; break;
			case 'steal_energy': fcg_steal($a,$b,'energy',$n); break;
			case 'steal_stat': fcg_steal($a,$b,$v[0],(float)$v[1]); break;
			case 'set_thorns': $a['thorns']=array((float)substr($v[1],6),(float)substr($v[2],4)); break;
			case 'set_evade': $a['evade']=(int)$v[1]; break;
			case 'cleanse_flip_self': $a['regen']=abs($a['regen']); if($a['monster']) $a['monster']['frozen']=0; break;
			case 'clean_self_poison': $a['regen']=max(0,$a['regen']); break;
			case 'clean_self_poison_to_enemy': if($a['regen']<0) { $b['regen']+=$a['regen']; $a['regen']=0; } break;
			case 'invert_enemy_regen': $b['regen']=-$b['regen']; break;
			case 'invert_or_burst_poison': if($b['regen']>0) $b['regen']=-$b['regen']; else $b['hp']-=(float)$v[1]; break;
			case 'mirror_balance_stats':
				$a['atk']=$b['atk']=max($a['atk'],$b['atk']); $a['def']=$b['def']=max($a['def'],$b['def']); break;
			case 'mirror_last_spell':
				if(!$mirroring && $m['last_spell'] !== null) fcg_effect($m,$side,fcg_cards()[$m['last_spell']],true); break;
			case 'mystery_upgraded_zero_cost':
				$pool = array_values(array_filter(fcg_cards(),function($c) { return $c['upgraded']; }));
				if(count($a['hand'])>=6) $a['regen']--; else { $a['hand'][]=$pool[random_int(0,count($pool)-1)]['id']; $a['free'][count($a['hand'])-1]=true; } break;
			case 'code_fu_search':
				$best=null; $cards=fcg_cards();
				foreach($a['deck'] as $i=>$id) if($cards[$id]['type']==='monster' && ($best===null || $cards[$id]['cost']>$cards[$a['deck'][$best]]['cost'])) $best=$i;
				if(count($a['hand'])>=6) $a['regen']--;
				elseif($best!==null) { $a['hand'][]=$a['deck'][$best]; $a['free'][count($a['hand'])-1]=true; array_splice($a['deck'],$best,1); } break;
			case 'kill_enemy_monster': case 'kill_monster_absorb_hp':
				$dead_monster=$b['monster'];
				if($dead_monster) { if($op==='kill_monster_absorb_hp') $a['hp']+=max(0,$dead_monster['hp']); $b['monster']['hp']=0; fcg_monster_death($b,$a); } break;
			case 'steal_monster_atk_to_self': if($dead_monster) $a['atk']+=$dead_monster['atk']; break;
			case 'buff_or_summon_guardian':
				$stats=array(); foreach($v as $kv) { list($k,$value)=explode('=',$kv); $stats[trim($k)]=(int)$value; }
				if(!$a['monster']) $a['monster']=array('id'=>$card['id'],'atk'=>0,'def'=>0,'hp'=>0,'maxhp'=>0,'frozen'=>0,'abilities'=>array('lure'));
				foreach(array('atk','def','hp') as $k) $a['monster'][$k]+=$stats[$k];
				$a['monster']['maxhp']=max($a['monster']['maxhp'],$a['monster']['hp']);
				$a['monster']['abilities'][]='growth('.$stats['growth'].','.$stats['growth'].',0)'; break;
			default: throw new LogicException('Unsupported FCG spell: '.$op);
		}
	}
	$a['hp']=min(200,$a['hp']); $b['hp']=min(200,$b['hp']);
}

function fcg_remove_hand(&$s, $slot)
{
	array_splice($s['hand'],$slot,1); $free=array();
	foreach($s['free'] as $i=>$value) if($i!==$slot) $free[$i>$slot ? $i-1 : $i]=$value;
	$s['free']=$free;
}

function fcg_play(&$m, $side, $slot)
{
	if($m['result'] !== null) throw new InvalidArgumentException('牌局已经结束。');
	$a=&$m['sides'][$side]; $b=&$m['sides'][1-$side];
	if(!is_int($slot) || !isset($a['hand'][$slot])) throw new InvalidArgumentException('这张牌已经不在手中了。');
	$card=fcg_cards()[$a['hand'][$slot]]; $cost=!empty($a['free'][$slot]) ? 0 : $card['cost'];
	if($a['energy'][$card['color']]<$cost) throw new InvalidArgumentException('对应颜色的能量不足。');
	if($card['type']==='monster' && $a['monster']) throw new InvalidArgumentException('驻场位置已被占据，可以先解散帮手。');
	$a['energy'][$card['color']]-=$cost; fcg_remove_hand($a,$slot);
	if($card['type']==='monster') {
		$a['monster']=array('id'=>$card['id'],'atk'=>$card['atk'],'def'=>$card['def'],'hp'=>$card['hp'],'maxhp'=>$card['hp'],'frozen'=>0,'abilities'=>fcg_tokens($card['ops']));
		foreach($a['monster']['abilities'] as $op) {
			if(preg_match('/^on_summon_gain_hp\((\d+)\)$/',$op,$p)) $a['hp']+=(int)$p[1];
			if(preg_match('/^on_summon_dmg_enemy\((\d+)\)$/',$op,$p)) $b['hp']-=(int)$p[1];
			if(preg_match('/^on_summon_steal_stat\((\w+),\s*(\d+)\)$/',$op,$p)) fcg_steal($a,$b,$p[1],(int)$p[2]);
		}
	} else {
		fcg_effect($m,$side,$card);
		// Keep the preceding real spell when a mirror is played: mirrors cannot form a recursive chain.
		if(strpos($card['ops'],'mirror_last_spell')===false) $m['last_spell']=$card['id'];
	}
	$m['deadline']++;
	fcg_log($m,($side ? '种火' : '你').'打出了「'.$card['name'].'」。'); fcg_finish($m);
}

function fcg_packet(&$attacker, &$defender, $damage, $pierce, $hunt)
{
	if($damage<=0) return 0;
	$monster_target=$defender['monster'] && $defender['monster']['hp']>0 && ($hunt || fcg_ability($defender['monster'],'lure'));
	$armor=$monster_target ? $defender['monster']['def'] : $defender['def'];
	$dealt=max(0,(int)round($damage-max(0,$armor-$pierce)));
	if(!$monster_target && $dealt>0 && $defender['evade']) {
		if($defender['evade']===2) $attacker['hp']-=$dealt;
		$defender['evade']=0; $dealt=1;
	}
	if($monster_target) {
		$overflow=max(0,$dealt-$defender['monster']['hp']); $defender['monster']['hp']-=$dealt;
		$defender['hp']-=$overflow;
	} else $defender['hp']-=$dealt;
	if($dealt>0 && $defender['thorns'][0]>0) $attacker['hp']-=min($defender['thorns'][1],(int)round($dealt*$defender['thorns'][0]));
	return $dealt;
}

function fcg_turn(&$m)
{
	// Snapshot offense before either side is hit; a simultaneous lethal attack still resolves.
	$before=$m['sides']; $damage=array(0,0);
	foreach(array(0,1) as $i) {
		$a=&$m['sides'][$i]; $b=&$m['sides'][1-$i]; $s=$before[$i]; $monster=$s['monster'];
		$damage[$i]+=fcg_packet($a,$b,$s['atk']+$s['bonus'],$s['pierce'],false);
		if($monster && !$monster['frozen']) {
			$n=fcg_ability($monster,'sunder'); $b['def']=max(0,$b['def']-$n);
			if($b['monster']) $b['monster']['def']=max(0,$b['monster']['def']-$n);
			$damage[$i]+=fcg_packet($a,$b,$monster['atk'],fcg_ability($monster,'pierce'),fcg_ability($monster,'kill_monsters'));
			$b['regen']-=fcg_ability($monster,'poison_enemy');
			fcg_steal($a,$b,'energy',fcg_ability($monster,'steal_energy'));
		}
	}
	foreach(array(0,1) as $i) {
		$s=&$m['sides'][$i]; $s['hp']=min(200,$s['hp']+$s['regen']);
		$s['bonus']=$s['pierce']=$s['evade']=0; $s['thorns']=array(0,0);
		if($s['monster'] && $s['monster']['hp']>0) {
			if($s['monster']['frozen']) $s['monster']['frozen']--;
			else {
				foreach($s['monster']['abilities'] as $op) {
					if(preg_match('/^gen\(([WRYGB]),\s*(-?\d+)\)$/',$op,$p)) fcg_energy($s,$p[1],(int)$p[2]);
					if(preg_match('/^growth\((\d+),\s*(\d+),\s*(\d+)\)$/',$op,$p)) { $s['monster']['atk']+=(int)$p[1]; $s['monster']['def']+=(int)$p[2]; $s['monster']['hp']+=(int)$p[3]; }
				}
			}
		}
	}
	fcg_log($m,'第 '.$m['turn'].' 回合：你造成 '.$damage[0].' 点伤害，种火造成 '.$damage[1].' 点伤害。');
	fcg_finish($m);
	if($m['result']!==null) return;
	$m['turn']++; $m['deadline']+=5;
	foreach($m['sides'] as &$s) { foreach(fcg_colors() as $c) fcg_energy($s,$c,$s['gen'][$c]); fcg_draw($s); } unset($s);
}

function fcg_ai(&$m)
{
	$s=$m['sides'][1]; $cards=fcg_cards(); $best=null; $score=-INF;
	foreach($s['hand'] as $slot=>$id) {
		$c=$cards[$id]; if($s['energy'][$c['color']]<(!empty($s['free'][$slot]) ? 0 : $c['cost']) || ($c['type']==='monster' && $s['monster'])) continue;
		$value=$c['cost']+random_int(0,3);
		if($c['type']==='monster') $value+=8;
		if(strpos($c['ops'],'add_gen')!==false) $value+=$m['turn']<10 ? 12 : 1;
		if($s['hp']<45 && strpos($c['ops'],'gain_hp')!==false) $value+=8;
		if($value>$score) { $best=$slot; $score=$value; }
	}
	if($best!==null) fcg_play($m,1,$best);
	elseif(count($s['hand'])===6) {
		// Do not remain permanently blocked behind a full hand of unaffordable/duplicate units.
		$worst=0; foreach($s['hand'] as $slot=>$id) if($cards[$id]['cost']>$cards[$s['hand'][$worst]]['cost']) $worst=$slot;
		fcg_remove_hand($m['sides'][1],$worst); $m['sides'][1]['regen']--;
		fcg_log($m,'种火弃掉了一张手牌。');
	}
}

function fcg_advance(&$m, $now)
{
	// Both AI decisions and turn boundaries use the persisted clock, independent of polling frequency.
	$steps=0;
	while($m['result']===null && min($m['deadline'],$m['ai_at'])<=$now && $steps++<1200) {
		if($m['ai_at']<$m['deadline']) { $at=$m['ai_at']; fcg_ai($m); $m['ai_at']=$at+2; }
		else fcg_turn($m);
	}
}

function fcg_finish(&$m)
{
	foreach($m['sides'] as &$s) { $s['hp'] = min(200,$s['hp']); $s['atk'] = max(0,$s['atk']); $s['def'] = max(0,$s['def']); } unset($s);
	fcg_monster_death($m['sides'][0],$m['sides'][1]);
	fcg_monster_death($m['sides'][1],$m['sides'][0]);
	if($m['sides'][0]['hp'] <= 0 || $m['sides'][1]['hp'] <= 0) {
		$m['result'] = $m['sides'][0]['hp'] <= 0 ? ($m['sides'][1]['hp'] <= 0 ? 'draw' : 'loss') : 'win';
	}
}
