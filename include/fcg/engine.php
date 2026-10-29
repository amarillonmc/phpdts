<?php
if(!defined('IN_GAME')) exit('Access Denied');
require_once GAME_ROOT.'./include/fcg/adaptive.php';

function fcg_cards()
{
	static $cards;
	if($cards === null) $cards = require GAME_ROOT.'./gamedata/ruleset/FCG/cards.php';
	return $cards;
}

function fcg_colors() { return array('W','R','Y','G','B'); }

function fcg_npc_decks()
{
	static $decks;
	if($decks===null) {
		$decks=array(); $counts=require GAME_ROOT.'./gamedata/ruleset/FCG/npc_decks.php';
		foreach($counts as $kind=>$group) foreach($group as $key=>$list) {
			$decks[$kind][$key]=array();
			foreach($list as $id=>$n) $decks[$kind][$key]=array_merge($decks[$kind][$key],array_fill(0,$n,$id));
		}
	}
	return $decks;
}

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
		'bonus'=>0,'pierce'=>0,'thorns'=>array(0,0),'evade'=>0,'free'=>array(),
		'played_this_turn'=>0,'played_cards'=>array(),'last_color'=>null,'disarm'=>0,'vuln'=>0,'endure'=>0,'barrier'=>0,
		'field'=>null,'surge'=>array(),'discarded_match'=>0,'sql_inject'=>0,'last_card'=>null);
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
	$new=array(
		array('攻坚的烈火',5,110,array(76,77,80,81,99,103,113)),
		array('瞬息的惊火',6,115,array(86,87,90,91,94,108,109)),
		array('喧嚣的爆火',6,115,array(82,83,88,89,97,102,107)),
		array('统御的枢火',7,120,array(78,79,84,85,92,93,95,105))
	);
	foreach($new as $i=>$row) {
		list($name,$level,$hp,$pairs)=$row; $deck=array(); $colors=array();
		foreach($pairs as $pair) {
			$id=2*($pair-1); $colors[$cards[$id]['color']]=true;
			$deck=array_merge($deck,array($id,$id,$id+1));
		}
		foreach(array_keys($colors) as $color) {
			$id=4*array_search($color,fcg_colors(),true);
			$deck=array_merge($deck,array($id,$id,$id,$id+2,$id+2));
		}
		$opponents[17+$i]=array('id'=>17+$i,'name'=>'✦'.$name,'level'=>$level,'hp'=>$hp,'deck'=>$deck,'portrait'=>'img/fcg/fireseeds/'.(13+$i).'.png');
	}
	foreach(range(5,20) as $id) {
		$opponents[$id]['has_hard']=true;
		$opponents[$id]['hard_hp']=$opponents[$id]['hp']+25;
		$opponents[$id]['hard_level']=$opponents[$id]['level']+2;
	}
	$opponents[21]=array('id'=>21,'name'=>'✦推演的炉火','level'=>7,'hp'=>120,
		'deck'=>fcg_npc_decks()['adaptive']['W'],'portrait'=>'img/fcg/fireseeds/17.png','adaptive'=>true);
	$vol3=array(
		array('屏息的潜火',6,115,array(118,120,121,122,124,125,126)),
		array('孤注的炽火',6,115,array(128,129,130,131,132,133,134,135)),
		array('登峰的罡火',7,120,array(116,117,136,138,139,140,142,144,145)),
		array('弄巧的游火',7,120,array(119,123,127,137,141,143,146,147,148,149,150,151,152,153,154,155))
	);
	foreach($vol3 as $i=>$row) {
		list($name,$level,$hp,$pairs)=$row; $deck=array(); $colors=array();
		foreach($pairs as $pair) {
			$id=2*($pair-1); $colors[$cards[$id]['color']]=true;
			$deck=array_merge($deck,array($id,$id,$id+1));
		}
		foreach(array_keys($colors) as $color) {
			$id=4*array_search($color,fcg_colors(),true);
			$deck=array_merge($deck,array($id,$id,$id,$id+2,$id+2));
		}
		$opponents[22+$i]=array('id'=>22+$i,'name'=>'✦'.$name,'level'=>$level,'hp'=>$hp,'deck'=>$deck,
			'portrait'=>'img/fcg/fireseeds/'.(18+$i).'.png','has_hard'=>true,'hard_hp'=>$hp+25,'hard_level'=>$level+2);
	}
	return $opponents;
}

function fcg_hard_drop_pool($opponent)
{
	$cards=fcg_cards(); $pool=array();
	$signature=array_filter(array_unique(fcg_opponents()[$opponent]['deck']),function($id)use($cards){return !$cards[$id]['unlimited'];});
	foreach($signature as $id) {
		$pool[]=$id;
		if($cards[$id]['upgraded']) $pool[]=$id;
	}
	foreach(array_diff(array_unique(fcg_npc_decks()['hard'][$opponent]),$signature) as $id) {
		if(!$cards[$id]['unlimited'] && ($cards[$id]['upgraded'] || $cards[$id]['rare'])) $pool[]=$id;
	}
	return $pool;
}

function fcg_new_match($deck, $opponent, $now, $hard = false, $evo = null)
{
	$all = fcg_opponents();
	if(!is_int($opponent) || !isset($all[$opponent])) throw new InvalidArgumentException('找不到这位牌手。');
	if(!is_bool($hard)) throw new InvalidArgumentException('请选择有效的挑战难度。');
	$o = $all[$opponent];
	if($hard && empty($o['has_hard'])) throw new InvalidArgumentException('这位牌手没有认真模式。');
	if($hard) { $o['deck']=fcg_npc_decks()['hard'][$opponent]; $o['hp']=$o['hard_hp']; }
	if($opponent===21) {
		$evo=$evo ?? fcg_evo_initial_state(); $color=fcg_colors()[random_int(0,4)];
		$o['deck']=$evo['decks'][$color];
	}
	$m=array('id'=>bin2hex(random_bytes(12)),'opponent'=>$opponent,'hard'=>$hard,'turn'=>1,'deadline'=>$now+10,
		'ai_at'=>$now+($hard?1:2),'sides'=>array(fcg_side($deck),fcg_side($o['deck'],$o['hp'])),
		'drop_pool'=>$hard ? fcg_hard_drop_pool($opponent) : $o['deck'],'last_spell'=>null,'result'=>null,'rewarded'=>false,'stickers'=>array(1=>array('id'=>1,'at'=>$now)),
		'log'=>array('小种火把牌堆推到了你的面前。'));
	if($hard) fcg_log($m,'种火认真起来了：使用 32 张精选构筑，生命增加 25，出牌更快。');
	if($opponent===21) {
		$m['ai_color']=$color; $m['ai_gen']=$evo['gen'][$color]; $m['ai_change']=$evo['deck_stats'][$color]['last_change'];
		$m['telemetry']=array_fill(0,2,array('played'=>array(),'solo_played'=>array(),'seen_turns'=>array(),'stalled_turns'=>array(),'seen_this_turn'=>array(),'turns'=>0,'energy_starved'=>0,'hand_starved'=>0));
		foreach(array(0,1) as $side) fcg_evo_observe_hand($m,$side);
		$label=array('W'=>'白','R'=>'红','Y'=>'黄','G'=>'绿','B'=>'蓝')[$color];
		fcg_log($m,'推演的炉火选用了'.$label.'色构筑，第 '.$m['ai_gen'].' 世代。'.$m['ai_change']);
	}
	return $m;
}

function fcg_log(&$m, $line)
{
	$m['log'][] = $line;
	if(count($m['log']) > 40) $m['log'] = array_slice($m['log'],-40);
}

function fcg_draw(&$s, $n = 1, $safe = false)
{
	for($i=0;$i<$n;$i++) {
		if(count($s['hand']) >= 6 || !$s['deck']) { if(!$safe || count($s['hand'])<6) $s['regen']--; }
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
	$out=array(); $buf=''; $depth=0; $len=strlen($ops);
	for($i=0;$i<$len;$i++) {
		$ch=$ops[$i];
		if($ch==='(') $depth++;
		elseif($ch===')' && $depth>0) $depth--;
		if($ch===';' && $depth===0) { if(trim($buf)!=='') $out[]=trim($buf); $buf=''; }
		else $buf.=$ch;
	}
	if(trim($buf)!=='') $out[]=trim($buf);
	return $out;
}

function fcg_has_mastery($side, $color, $n)
{
	return !empty($side['sql_inject']) || ($side['gen'][$color] ?? 1)>=$n;
}

function fcg_card_cost($side, $slot)
{
	if(!isset($side['hand'][$slot])) return 99;
	if(!empty($side['free'][$slot])) return 0;
	$card=fcg_cards()[$side['hand'][$slot]]; $discount=(int)($side['surge'][$slot] ?? 0);
	if(in_array('surge_match',fcg_tokens($card['ops']),true)) $discount+=(int)($side['discarded_match'] ?? 0);
	return max(0,$card['cost']-$discount);
}

function fcg_trigger_hand_surge(&$side, $played_card)
{
	foreach($side['hand'] as $slot=>$id) {
		$card=fcg_cards()[$id];
		if(in_array('surge_hand',fcg_tokens($card['ops']),true) && ($card['pair']!==134 || $played_card['type']==='spell'))
			$side['surge'][$slot]=($side['surge'][$slot] ?? 0)+1;
	}
}

function fcg_discard_slot(&$m, $side, $slot, $by_gamble = false)
{
	$a=&$m['sides'][$side];
	if(!is_int($slot) || !isset($a['hand'][$slot])) throw new InvalidArgumentException('请选择一张手牌。');
	$card=fcg_cards()[$a['hand'][$slot]];
	fcg_evo_observe_hand($m,$side); fcg_remove_hand($a,$slot);
	$a['discarded_match']=($a['discarded_match'] ?? 0)+1; $a['played_cards'][]=$card['id'];
	foreach(fcg_tokens($card['ops']) as $token) if(preg_match('/^refactor\((.*)\)$/',$token,$p)) {
		$sub=$card; $sub['ops']=$p[1]; fcg_effect($m,$side,$sub);
		fcg_log($m,fcg_side_name($m,$side).'弃掉「'.$card['name'].'」，触发了【重构】！');
		// 孤注的代价与主效果一起结算，不能在锁血等主效果执行前提前结束牌局。
		if(!$by_gamble) fcg_finish($m);
		return;
	}
	if(!$by_gamble) $a['regen']--;
	fcg_log($m,fcg_side_name($m,$side).'弃掉了一张牌。'.($by_gamble?'':'回合回血降低了 1 点。'));
}

function fcg_set_field(&$side, $card, $kind, $op, $turns = 0, $trigger = '')
{
	if(!empty($side['field'])) $side['played_cards'][]=$side['field']['id'];
	$side['field']=array('id'=>$card['id'],'kind'=>$kind,'op'=>$op,'turns'=>$turns);
	if($trigger!=='') $side['field']['trigger']=$trigger;
}

function fcg_check_trap(&$m, $defender_side, $trigger, $context = 0)
{
	$d=&$m['sides'][$defender_side]; $a=&$m['sides'][1-$defender_side]; $f=$d['field'] ?? null;
	if(!$f || $f['kind']!=='trap' || $f['trigger']!==$trigger) return false;
	$d['field']=null; $d['played_cards'][]=$f['id']; $m['last_card']=$f['id'];
	fcg_log($m,fcg_side_name($m,$defender_side).'翻开了伏击卡「'.fcg_cards()[$f['id']]['name'].'」！');
	$v=explode('_',$f['op']);
	switch($trigger) {
		case 'on_enemy_summon':
			$n=(int)$v[2]; $a['def']=max(0,$a['def']-(int)$v[3]);
			if($a['monster']) { fcg_hurt($a,max(0,$n-$a['monster']['hp'])); $a['monster']['hp']-=$n; fcg_monster_death($a,$d); }
			break;
		case 'on_enemy_combo2':
			fcg_hurt($a,(int)$v[1]); $a['def']=max(0,$a['def']-(int)$v[2]);
			if($a['monster']) $a['monster']['def']=max(0,$a['monster']['def']-(int)$v[2]); break;
		case 'on_enemy_big_card':
			fcg_steal($d,$a,'energy',(int)$v[2]);
			if($a['monster']) $a['monster']['frozen']=max($a['monster']['frozen'],(int)$v[3]);
			$a['disarm']=($a['disarm'] ?? 0)+(int)$v[4]; break;
		case 'on_enemy_heal_or_energy': $a['regen']-=(int)$v[2]; fcg_hurt($a,(int)$v[3]); break;
		case 'on_enemy_heavy_hit': fcg_hurt($a,$context+($v[2]==='2'?6:0)); break;
	}
	return true;
}

function fcg_ability($monster, $key, $default = 0)
{
	if(!$monster) return $default;
	$total=null;
	foreach($monster['abilities'] as $op) {
		if($op === $key) return 1;
		if(preg_match('/^'.preg_quote($key,'/').'\(([^)]*)\)$/',$op,$m)) {
			$values = explode(',',$m[1]);
			if(count($values)>1) return array_map('floatval',$values);
			$total=($total ?? 0)+(float)$values[0];
		}
	}
	return $total ?? $default;
}

function fcg_aura($side, $key, $default = 0)
{
	return $side['monster'] && !$side['monster']['frozen'] && $side['monster']['hp']>0 ? fcg_ability($side['monster'],$key,$default) : $default;
}

function fcg_hurt(&$side, $damage)
{
	$damage=max(0,$damage); $shield=min($side['barrier'] ?? 0,$damage);
	$side['barrier']=($side['barrier'] ?? 0)-$shield; $damage-=$shield;
	$side['hp']-=$damage;
	if(!empty($side['endure'])) $side['hp']=max(1,$side['hp']);
	return $damage;
}

function fcg_monster_death(&$a, &$b)
{
	if(!$a['monster'] || $a['monster']['hp'] > 0) return;
	fcg_hurt($b,fcg_ability($a['monster'],'rebirth_dmg_enemy'));
	if(fcg_ability($a['monster'],'clean_self_poison_on_death')) $a['regen']=max(0,$a['regen']);
	if(fcg_ability($a['monster'],'rebirth_once') && empty($a['monster']['reborn'])) {
		$a['monster']['reborn'] = true;
		$a['monster']['hp'] = $a['monster']['maxhp'];
	} else {
		if(preg_match('/^valhalla_(2|3)$/',$a['field']['op'] ?? '',$p)) {
			$a['atk']+=(int)$p[1]; fcg_energy($a,'W',(int)$p[1]);
		}
		$tier=fcg_ability($a['monster'],'upload_on_death');
		if($tier) fcg_set_field($a,$a['monster'],'domain','digital_life_'.(int)$tier);
		$a['played_cards'][]=$a['monster']['id'];
		$a['monster'] = null;
	}
}

function fcg_effect(&$m, $side, $card, $mirroring = false)
{
	$a = &$m['sides'][$side]; $b = &$m['sides'][1-$side];
	$dead_monster = null;
	foreach(fcg_tokens($card['ops']) as $token) {
		if(preg_match('/^refactor\((.*)\)$/',$token)) continue;
		if(preg_match('/^gamble\((.*)\)$/',$token,$p)) {
			$min=null;
			foreach($a['hand'] as $slot=>$id) if($min===null || fcg_card_cost($a,$slot)<=fcg_card_cost($a,$min)) $min=$slot;
			if($min!==null) fcg_discard_slot($m,$side,$min,true);
			$sub=$card; $sub['ops']=$p[1]; fcg_effect($m,$side,$sub,$mirroring); continue;
		}
		if(preg_match('/^mastery\(([WRYGB]),\s*(\d+),\s*(.*)\)$/',$token,$p)) {
			if(fcg_has_mastery($a,$p[1],(int)$p[2])) { $sub=$card; $sub['ops']=$p[3]; fcg_effect($m,$side,$sub,$mirroring); }
			continue;
		}
		if(preg_match('/^(combo|resonance)\(([^,]+),\s*(.*)\)$/',$token,$condition)) {
			if(!empty($a['sql_inject']) || ($condition[1]==='combo' ? ($a['played_this_turn'] ?? 0)>=(int)$condition[2] : (($a['last_color'] ?? null)!==null && ($condition[2]==='ANY' || $a['last_color']===$condition[2])))) {
				$sub=$card; $sub['ops']=$condition[3]; fcg_effect($m,$side,$sub,$mirroring);
			}
			continue;
		}
		if(preg_match('/^combo_(\d+)_(.*)$/',$token,$condition)) {
			if(!empty($a['sql_inject']) || ($a['played_this_turn'] ?? 0)>=(int)$condition[1]) {
				$sub=$card; $sub['ops']=preg_replace('/^pierce\(/','pierce_enemy(',$condition[2]); fcg_effect($m,$side,$sub,$mirroring);
			}
			continue;
		}
		if(strpos($token,'scale_by(') === 0) {
			switch($card['pair']) {
				case 27:
					$n = count(array_filter($a['energy'],function($e) { return $e > 0; }));
					$a['atk'] += $n*($card['upgraded'] ? 3 : 2); $a['def'] += $n*($card['upgraded'] ? 3 : 2);
					$a['hp'] += $n*($card['upgraded'] ? 6 : 5); if($card['upgraded']) fcg_draw($a,2); break;
				case 46: case 107: fcg_hurt($b,$b['atk']); break;
				case 50: $n = max($b['atk'],$b['def']); $a['atk'] += $n; $a['def'] += $n; break;
				case 70: fcg_hurt($b,(int)min($card['upgraded'] ? 24 : 18,array_sum($b['energy'])*($card['upgraded'] ? 2/3 : 1/2))); break;
				case 75: fcg_hurt($b,array_sum($a['gen'])+array_sum($b['gen'])+($card['upgraded'] ? 8 : 0)); break;
				default: throw new LogicException('Unsupported FCG scale: '.$card['id']);
			}
			continue;
		}
		if(!preg_match('/^(\w+)(?:\((.*)\))?$/',$token,$parts)) throw new LogicException('Unsupported FCG effect: '.$token);
		$op = $parts[1]; $v = isset($parts[2]) ? array_map('trim',explode(',',$parts[2])) : array();
		$n = isset($v[0]) ? (float)$v[0] : 0;
		switch($op) {
			case 'surge_hand': case 'surge_match': break;
			case 'field_domain':
				fcg_set_field($a,$card,'domain',$v[0]);
				if(preg_match('/^valhalla_(2|3)$/',$v[0],$p)) { $a['atk']+=(int)$p[1]; $a['def']+=(int)$p[1]; } break;
			case 'field_countdown': fcg_set_field($a,$card,'countdown',$v[1],(int)$n); break;
			case 'trap_set': fcg_set_field($a,$card,'trap',$v[1],0,$v[0]); break;
			case 'clear_enemy_field':
				if(!empty($b['field'])) { $b['played_cards'][]=$b['field']['id']; $b['field']=null; fcg_energy($a,'Y',(int)$n); $a['def']+=(int)$v[1]; } break;
			case 'surge_boost_hand':
				foreach($a['hand'] as $slot=>$id) $a['surge'][$slot]=($a['surge'][$slot] ?? 0)+(int)$n; break;
			case 'summon_self_or_heal':
				if(!$a['monster']) fcg_summon($m,$side,fcg_cards()[$card['id']]);
				else { $a['hp']+=$n; $a['def']+=4; } break;
			case 'desperate_burst': if($a['hp']<=$n) fcg_hurt($b,(int)$v[1]); break;
			case 'steal_valkyrie_soul':
				if($b['monster']) {
					foreach(array('atk','def') as $stat) { $gain=(int)floor($b['monster'][$stat]/2); $b['monster'][$stat]-=$gain; $a[$stat]+=$gain; }
					if($a['monster']) $a['monster']['abilities']=array_values(array_unique(array_merge($a['monster']['abilities'],array_diff($b['monster']['abilities'],array('escort','ephemeral')))));
				} else { fcg_steal($a,$b,'energy',$n==2?6:4); fcg_hurt($b,$n==2?14:10); } break;
			case 'gain_monster_stats':
				foreach(array('atk','def','hp') as $i=>$stat) {
					if($a['monster']) $a['monster'][$stat]+=(int)$v[$i]; else $a[$stat]+=(int)$v[$i];
				}
				if($a['monster']) $a['monster']['maxhp']+=(int)$v[2]; break;
			case 'miracle_box': case 'miracle_gem':
				$cheat=fcg_aura($a,'aura_cheat_miracle');
				$jackpot=!empty($a['sql_inject']) || $cheat>0 || $a['hp']<$b['hp'] || random_int(1,100)<=50;
				if($op==='miracle_box') {
					fcg_energy($a,'ALL',$n==2?2:1); $a['hp']+=$n==2?14:10;
					if($jackpot) { fcg_energy($a,'ALL',1); fcg_hurt($b,$n==2?20:14); fcg_draw($a,2); }
				} else {
					$a['atk']+=$jackpot?(int)$v[1]:$n; $a['def']+=$jackpot?(int)$v[1]:$n;
					if($jackpot && $a['monster']) {
						$a['monster']['atk']+=4; $a['monster']['def']+=4;
						if(!in_array('rebirth_once',$a['monster']['abilities'],true)) $a['monster']['abilities'][]='rebirth_once';
					}
				}
				if($cheat) { fcg_draw($a); fcg_energy($a,'Y',$cheat); }
				fcg_log($m,fcg_side_name($m,$side).'的奇迹'.($jackpot?'大成功！':'获得了保底收益。')); break;
			case 'sql_inject': $a['sql_inject']=1; break;
			case 'draw_safe': fcg_draw($a,(int)$n,true); break;
			case 'hoard_feast':
				$cnt=!empty($a['sql_inject'])?6:count($a['hand']); $a['hp']+=$cnt*$n; fcg_energy($a,'G',$cnt*(int)$v[1]); break;
			case 'programmer_husband':
				$b[$b['atk']>=$b['def']?'atk':'def']=1;
				if($b['monster']) $b['monster']['atk']=1;
				if($n==2) $b['energy'][array_search(max($b['energy']),$b['energy'],true)]=1; break;
			case 'copy_enemy_last_card': case 'rewind_last_played':
				// 记住各自上一张公开出牌，跨回合可回收，盖伏卡不会进入这个记录。
				$id=$op==='copy_enemy_last_card'?($b['last_card'] ?? null):($a['last_card'] ?? null);
				if($op==='rewind_last_played' && ($id===null || $id===$card['id'])) {
					$id=null;
					foreach(array_reverse($a['played_cards'] ?? array()) as $previous) if($previous!==$card['id']) { $id=$previous; break; }
				}
				if($id!==null && ($op!=='rewind_last_played' || $id!==$card['id']) && count($a['hand'])<6) {
					$a['hand'][]=$id; $a['surge'][count($a['hand'])-1]=(int)$n;
				} break;
			case 'add_gen': case 'add_energy':
				if($v[0] === 'enemy_random') fcg_energy($b,fcg_colors()[random_int(0,4)],(int)$v[1]);
				else fcg_energy($a,$v[0],(int)$v[1],$op==='add_gen' ? 'gen' : 'energy'); break;
			case 'gain_atk': case 'gain_def': case 'gain_hp': case 'lose_hp': case 'add_regen': case 'add_poison':
				$key = array('gain_atk'=>'atk','gain_def'=>'def','gain_hp'=>'hp','lose_hp'=>'hp','add_regen'=>'regen','add_poison'=>'regen')[$op];
				$target = $v[0] === 'enemy' ? 1-$side : $side;
				if($op==='lose_hp') fcg_hurt($m['sides'][$target],(float)$v[1]);
				else $m['sides'][$target][$key] += (float)$v[1]*($op==='add_poison' ? -1 : 1); break;
			case 'gain_atk_def': $a['atk']+=(float)$v[1]; $a['def']+=(float)$v[2]; break;
			case 'turn_bonus_dmg': $a['bonus'] += $n; break;
			case 'draw': fcg_draw($a,(int)$n); break;
			case 'weaken_enemy': $b['atk']=max(0,$b['atk']-$n); $b['def']=max(0,$b['def']-(float)$v[1]); break;
			case 'sunder_enemy': $b['def']=max(0,$b['def']-$n); if($b['monster']) $b['monster']['def']=max(0,$b['monster']['def']-$n); break;
			case 'pierce_enemy': fcg_hurt($b,$n); break;
			case 'disarm_enemy': $b['bonus']=0; $b['disarm']=($b['disarm'] ?? 0)+$n; break;
			case 'vuln_enemy': $b['vuln']=($b['vuln'] ?? 0)+$n; break;
			case 'gain_barrier': $a['barrier']=($a['barrier'] ?? 0)+(float)$v[1]; break;
			case 'endure_lethal': $a['endure']=1; break;
			case 'desperate_draw': if($a['hp']<=$n) fcg_draw($a,(int)$v[1]); break;
			case 'burst_poison': fcg_hurt($b,max(0,-$b['regen'])*$n); break;
			case 'shuffle_stats':
				$stats=array($a['atk'],$a['def'],$b['atk'],$b['def']); shuffle($stats);
				list($a['atk'],$a['def'],$b['atk'],$b['def'])=$stats; break;
			case 'freeze_enemy_monster': if($b['monster']) $b['monster']['frozen']=max($b['monster']['frozen'],(int)$n); break;
			case 'gain_monster_def': case 'gain_monster_hp':
				if($a['monster']) $a['monster'][$op==='gain_monster_def' ? 'def' : 'hp']+=(float)$v[1]; break;
			case 'lose_monster_hp': if($b['monster'] && empty($b['monster']['stealth'])) $b['monster']['hp']-=(float)$v[1]*($card['color']==='R' && fcg_ability($b['monster'],'weak_to_red') ? 2 : 1); break;
			case 'steal_energy': fcg_steal($a,$b,'energy',$n); break;
			case 'steal_stat': fcg_steal($a,$b,$v[0],(float)$v[1]); break;
			case 'set_thorns': $a['thorns']=array((float)substr($v[1],6),(float)substr($v[2],4)); break;
			case 'set_evade': $a['evade']=(int)$v[1]; break;
			case 'cleanse_flip_self': $a['regen']=abs($a['regen']); $a['vuln']=0; if($a['monster']) $a['monster']['frozen']=0; break;
			case 'clean_self_poison': $a['regen']=max(0,$a['regen']); break;
			case 'clean_self_poison_to_enemy': if($a['regen']<0) { $b['regen']+=$a['regen']; $a['regen']=0; } break;
			case 'invert_enemy_regen': $b['regen']=-$b['regen']; break;
			case 'invert_or_burst_poison': if($b['regen']>0) $b['regen']=-$b['regen']; else fcg_hurt($b,(float)$v[1]); break;
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
				$dead_monster=empty($b['monster']['stealth']) ? $b['monster'] : null;
				if($dead_monster) { if($op==='kill_monster_absorb_hp') $a['hp']+=max(0,$dead_monster['hp']); $b['monster']['hp']=0; fcg_monster_death($b,$a); } break;
			case 'steal_monster_atk_to_self': if($dead_monster) $a['atk']+=$dead_monster['atk']; break;
			case 'buff_or_summon_guardian':
				$stats=array(); foreach($v as $kv) { list($k,$value)=explode('=',$kv); $stats[trim($k)]=(int)$value; }
				if(!$a['monster']) $a['monster']=array('id'=>$card['id'],'atk'=>0,'def'=>0,'hp'=>0,'maxhp'=>0,'frozen'=>0,'abilities'=>array('lure'));
				foreach(array('atk','def','hp') as $k) $a['monster'][$k]+=$stats[$k];
				$a['monster']['maxhp']=max($a['monster']['maxhp'],$a['monster']['hp']);
				$a['monster']['abilities'][]='growth('.$stats['growth'].','.$stats['growth'].',0)';
				fcg_check_trap($m,1-$side,'on_enemy_summon'); break;
			default: throw new LogicException('Unsupported FCG spell: '.$op);
		}
	}
	$a['hp']=min($a['mhp'] ?? 200,$a['hp']); $b['hp']=min($b['mhp'] ?? 200,$b['hp']);
}

function fcg_remove_hand(&$s, $slot)
{
	array_splice($s['hand'],$slot,1);
	foreach(array('free','surge') as $key) {
		$next=array();
		foreach($s[$key] ?? array() as $i=>$value) if($i!==$slot) $next[$i>$slot ? $i-1 : $i]=$value;
		$s[$key]=$next;
	}
}

function fcg_can_summon($side, $card)
{
	return !$side['monster'] || in_array('escort',fcg_tokens($card['ops']),true) || fcg_aura($side,'aura_burn_monster_heal');
}

function fcg_summon(&$m, $side, $card)
{
	$a=&$m['sides'][$side]; $b=&$m['sides'][1-$side]; $old=$a['monster'];
	$abilities=fcg_tokens($card['ops']);
	$escort=in_array('escort',$abilities,true);
	if($old && !$escort && fcg_aura($a,'aura_burn_monster_heal')) {
		$a['hp']+=$card['hp']; $a['monster']['atk']+=$card['atk']; $a['monster']['maxhp']+=2;
		return;
	}
	$unit=array('id'=>$card['id'],'atk'=>$card['atk'],'def'=>$card['def'],'hp'=>$card['hp'],'maxhp'=>$card['hp'],
		'frozen'=>0,'age'=>0,'stealth'=>in_array('stealth',$abilities,true)?1:0,'abilities'=>$abilities);
	foreach($abilities as $op) if(preg_match('/^mastery_mon\(([WRYGB]),\s*(\d+),\s*(\d+),\s*(\d+),\s*(\d+)\)$/',$op,$p) && fcg_has_mastery($a,$p[1],(int)$p[2])) {
		$unit['atk']+=(int)$p[3]; $unit['def']+=(int)$p[4]; $unit['hp']+=(int)$p[5]; $unit['maxhp']+=(int)$p[5];
	}
	if($old && $escort) {
		foreach(array('atk','def','hp','maxhp') as $key) $a['monster'][$key]+=$unit[$key];
		$a['monster']['abilities']=array_merge($old['abilities'],array_values(array_diff($abilities,array('escort'))));
	} else $a['monster']=$unit;
	foreach($abilities as $op) {
		if(preg_match('/^on_summon_draw\((\d+)\)$/',$op,$p)) fcg_draw($a,(int)$p[1]);
		if(preg_match('/^mastery_deathnote\((\d+),\s*(\d+)\)$/',$op,$p) && fcg_has_mastery($a,'B',(int)$p[1])) {
			if($b['monster']) { $b['monster']['hp']=0; fcg_monster_death($b,$a); }
			fcg_hurt($b,(int)$p[2]);
		}
		if(preg_match('/^on_summon_jinx\((\d+),\s*(\d+),\s*(\d+)\)$/',$op,$p)) {
			$b['atk']+=(int)$p[1]; fcg_set_field($a,$card,'countdown','jinx_'.$p[2].'_'.$p[3],1);
		}
		if(preg_match('/^hoard_slides\((\d+),\s*(\d+)\)$/',$op,$p)) {
			$n=!empty($a['sql_inject'])?6:count($a['hand']); $a['def']+=$n*(int)$p[1]; $a['hp']+=$n*(int)$p[2];
			if($n>=4) fcg_energy($a,'W',$card['upgraded']?2:1,'gen');
		}
		if(preg_match('/^on_summon_(gain_hp|gain_def|dmg_enemy|barrier|eat_poison|chaos_blessing|flatten_def)\((\d+)\)$/',$op,$p)) {
			$n=(int)$p[2];
			switch($p[1]) {
				case 'gain_hp': $a['hp']+=$n; break;
				case 'gain_def': $a['def']+=$n; break;
				case 'dmg_enemy': fcg_hurt($b,$n); break;
				case 'barrier': $a['barrier']=($a['barrier'] ?? 0)+$n; break;
				case 'eat_poison': $a['hp']+=max(0,-$a['regen'])*$n; $a['regen']=max(0,$a['regen']); break;
				case 'chaos_blessing': $a['atk']+=$n; $b['atk']+=$n; $a['regen']-=$n; $b['regen']-=$n; break;
				case 'flatten_def': $b['def']=$n; if($b['monster']) $b['monster']['def']=$n; break;
			}
		}
		if(preg_match('/^on_summon_steal_stat\((\w+),\s*(\d+)\)$/',$op,$p)) fcg_steal($a,$b,$p[1],(int)$p[2]);
		if(preg_match('/^clone_monster_stats\(([\d.]+)\)$/',$op,$p) && $old) {
			foreach(array('atk','def','hp','maxhp') as $key) $a['monster'][$key]+=(int)floor($old[$key]*(float)$p[1]);
		}
		if(strpos($op,'add_regen(')===0) { $sub=$card; $sub['ops']=$op; fcg_effect($m,$side,$sub); }
	}
	fcg_check_trap($m,1-$side,'on_enemy_summon');
}

function fcg_play(&$m, $side, $slot)
{
	if($m['result'] !== null) throw new InvalidArgumentException('牌局已经结束。');
	$a=&$m['sides'][$side]; $b=&$m['sides'][1-$side];
	if(!is_int($slot) || !isset($a['hand'][$slot])) throw new InvalidArgumentException('这张牌已经不在手中了。');
	$card=fcg_cards()[$a['hand'][$slot]]; $cost=fcg_card_cost($a,$slot);
	$trap=strpos($card['ops'],'trap_set(')===0;
	if($a['energy'][$card['color']]<$cost) throw new InvalidArgumentException('对应颜色的能量不足。');
	if($card['type']==='monster' && !fcg_can_summon($a,$card)) throw new InvalidArgumentException('驻场位置已被占据，可以先解散帮手。');
	fcg_evo_observe_hand($m,$side);
	$a['energy'][$card['color']]-=$cost; fcg_remove_hand($a,$slot);
	fcg_trigger_hand_surge($a,$card); $a['discarded_match']=($a['discarded_match'] ?? 0)+1;
	if(isset($m['telemetry'][$side])) {
		$t=&$m['telemetry'][$side]; $id=$card['id'];
		$t['played'][$id]=($t['played'][$id] ?? 0)+1;
		// 异色余韵实际触发的出牌仍计数，但不把其混色胜率当作单色构筑的证据。
		if(!fcg_evo_cross_resonance($card,$a['last_color'] ?? null)) $t['solo_played'][$id]=($t['solo_played'][$id] ?? 0)+1;
		unset($t);
	}
	$a['played_this_turn']=($a['played_this_turn'] ?? 0)+1;
	$bug=fcg_aura($b,'aura_bug_draw',null);
	if(!$card['unlimited'] && $bug!==null) { if($bug==0) fcg_draw($b); else fcg_energy($b,'Y',$bug); }
	if(!$card['unlimited'] && in_array($b['field']['op'] ?? '',array('peep_room_1','peep_room_2'),true)) {
		$max=null;
		foreach($b['hand'] as $slot=>$id) if($max===null || fcg_card_cost($b,$slot)>fcg_card_cost($b,$max)) $max=$slot;
		if($max!==null) $b['surge'][$max]=($b['surge'][$max] ?? 0)+1;
	}
	// 先记录已公开的出牌，之后翻开的伏击卡才是最新公开牌。
	fcg_log($m,fcg_side_name($m,$side).($trap?'在场地区盖放了一张伏击卡。':'打出了「'.$card['name'].'」。'));
	if(!$trap) $m['last_card']=$card['id'];
	if($card['type']==='monster') {
		fcg_summon($m,$side,$card);
	} else {
		fcg_effect($m,$side,$card);
		fcg_hurt($b,fcg_aura($a,'aura_spell_zap'));
		// Keep the preceding real spell when a mirror is played: mirrors cannot form a recursive chain.
		if(!$trap && strpos($card['ops'],'mirror_last_spell')===false) $m['last_spell']=$card['id'];
	}
	$a['last_color']=$card['color'];
	if(!$trap) $a['last_card']=$card['id'];
	if($card['type']!=='monster' && !$trap && strpos($card['ops'],'field_domain(')!==0 && strpos($card['ops'],'field_countdown(')!==0) $a['played_cards'][]=$card['id'];
	if(!$card['unlimited'] && $a['played_this_turn']>=2) fcg_check_trap($m,1-$side,'on_enemy_combo2');
	if($card['cost']>=4) fcg_check_trap($m,1-$side,'on_enemy_big_card');
	if(!$card['unlimited'] && preg_match('/(?:gain_hp|add_regen|add_energy|add_gen)\(/',$card['ops'])) fcg_check_trap($m,1-$side,'on_enemy_heal_or_energy');
	foreach(array(0,1) as $i) fcg_evo_observe_hand($m,$i);
	if($side===1 && empty($m['live']) && $card['rare'] && $m['ai_at']-($m['stickers'][1]['at'] ?? 0)>=3) $m['stickers'][1]=array('id'=>22,'at'=>$m['ai_at']);
	if(empty($m['live']) || ($m['extension'] ?? 0)<4) { $m['deadline']++; $m['extension']=($m['extension'] ?? 0)+1; }
	fcg_finish($m);
}

function fcg_side_name($m, $side)
{
	return $m['names'][$side] ?? ($side ? '种火' : '你');
}

function fcg_packet(&$attacker, &$defender, $damage, $pierce, $hunt, $color = null, $artillery = false, &$m = null, $defender_side = null)
{
	if($damage<=0) return 0;
	$monster_target=!$artillery && $defender['monster'] && $defender['monster']['hp']>0 && empty($defender['monster']['stealth']) && ($hunt || fcg_ability($defender['monster'],'lure'));
	$armor=$monster_target ? $defender['monster']['def'] : $defender['def'];
	$dealt=max(0,(int)round($damage-max(0,$armor-$pierce)));
	if($monster_target && $color==='R' && fcg_ability($defender['monster'],'weak_to_red')) $dealt*=2;
	if(!$monster_target && !empty($defender['vuln'])) { $dealt+=$defender['vuln']; $defender['vuln']=0; }
	if(!$monster_target) {
		foreach(array($attacker,$defender) as $s) if(strpos($s['field']['op'] ?? '','stall_eight_zones_')===0) { $dealt=(int)ceil($dealt/2); break; }
		if($dealt>=10 && $m!==null && fcg_check_trap($m,$defender_side,'on_enemy_heavy_hit',$dealt)) $dealt=1;
	}
	if(!$monster_target && $dealt>0 && $defender['evade']) {
		if($defender['evade']===2) fcg_hurt($attacker,$dealt);
		$defender['evade']=0; $dealt=1;
	}
	if($monster_target) {
		$overflow=max(0,$dealt-$defender['monster']['hp']); $defender['monster']['hp']-=$dealt;
		fcg_hurt($defender,$overflow);
		if($dealt>0 && $defender['monster']['hp']>0) {
			$defender['monster']['atk']=($defender['monster']['atk'] ?? 0)+fcg_ability($defender['monster'],'enrage_atk');
			if(fcg_ability($defender['monster'],'ephemeral')) $defender['monster']['hp']=0;
		}
	} else $dealt=fcg_hurt($defender,$dealt);
	if($dealt>0 && $defender['thorns'][0]>0) fcg_hurt($attacker,min($defender['thorns'][1],(int)round($dealt*$defender['thorns'][0])));
	return $dealt;
}

function fcg_field_turn(&$m, $side)
{
	$s=&$m['sides'][$side]; $enemy=&$m['sides'][1-$side]; $f=$s['field'] ?? null;
	if(!$f || $f['kind']==='trap') return;
	$op=$f['op'];
	if($f['kind']==='countdown') {
		if(--$s['field']['turns']>0) return;
		$s['field']=null; $s['played_cards'][]=$f['id']; $m['last_card']=$f['id'];
		fcg_log($m,'「'.fcg_cards()[$f['id']]['name'].'」的倒数归零！');
		if(preg_match('/^nuke_(\d+)_(\d+)$/',$op,$p)) {
			fcg_hurt($enemy,(int)$p[1]); fcg_hurt($s,(int)$p[2]);
			if($enemy['monster']) { $enemy['monster']['hp']-=(int)$p[1]; fcg_monster_death($enemy,$s); }
		} elseif(strpos($op,'stall_eight_zones_')===0) {
			fcg_hurt($enemy,array_sum($s['gen'])+array_sum($enemy['gen'])+($op==='stall_eight_zones_2'?12:6)); fcg_draw($s,2);
		} elseif(preg_match('/^jinx_(\d+)_(\d+)$/',$op,$p)) {
			$enemy['atk']=(int)floor($enemy['atk']/2); fcg_hurt($enemy,(int)$p[1]); $enemy['regen']-=(int)$p[2];
		}
		return;
	}
	switch($op) {
		case 'heal_both_4_clean_freeze': case 'heal_both_6_def_1_clean_freeze':
			$n=$op==='heal_both_4_clean_freeze'?4:6; $s['hp']+=$n; if($n===6) $s['def']++;
			if($s['monster']) { $s['monster']['hp']=min($s['monster']['maxhp'],$s['monster']['hp']+$n); $s['monster']['frozen']=0; } break;
		case 'valhalla_2': case 'valhalla_3':
			// 交接稿的回合收益二选一采用回血，避免临时攻击在同次回合末被清空。
			$up=$op==='valhalla_3'; $s['hp']+=$up?5:3;
			if($s['monster']) { $s['monster']['atk']+=$up?2:1; $s['monster']['def']++; } break;
		case 'peep_room_1': case 'peep_room_2':
			fcg_energy($s,'Y',$op==='peep_room_2'?2:1); if($op==='peep_room_2') fcg_draw($s); break;
		case 'viridian_1': case 'viridian_2':
			$up=$op==='viridian_2'; $s['regen']++; $enemy['regen']-=$up?2:1;
			if($s['monster'] && fcg_has_mastery($s,'G',4)) {
				$s['monster']['atk']+=$up?3:2; $s['monster']['def']+=$up?2:1;
				$s['monster']['hp']+=$up?3:2; $s['monster']['maxhp']+=$up?3:2;
			} break;
		case 'digital_life_1': case 'digital_life_2': fcg_energy($s,'ALL',1); fcg_hurt($enemy,$op==='digital_life_2'?6:4); break;
	}
}

function fcg_turn(&$m)
{
	fcg_evo_end_turn($m);
	// 先收起上一回合的牌；本次结算阵亡的帮手留在新回合的牌堆中。
	foreach($m['sides'] as &$s) $s['played_cards']=array(); unset($s);
	// Snapshot offense before either side is hit; a simultaneous lethal attack still resolves.
	$before=$m['sides']; $damage=array(0,0);
	foreach(array(0,1) as $i) {
		$n=fcg_aura($before[1-$i],'disarm_attack');
		if($n) { $before[$i]['bonus']=0; $before[$i]['disarm']=($before[$i]['disarm'] ?? 0)+$n; }
	}
	foreach(array(0,1) as $i) {
		$a=&$m['sides'][$i]; $b=&$m['sides'][1-$i]; $s=$before[$i]; $monster=$s['monster'];
		$artillery=!empty($s['barrier']) || fcg_aura($s,'aura_artillery');
		$direct=fcg_packet($a,$b,max(0,$s['atk']+$s['bonus']-($s['disarm'] ?? 0)),$s['pierce'],false,$s['last_color'] ?? null,$artillery,$m,1-$i);
		$damage[$i]+=$direct;
		if($direct>0) $b['regen']-=fcg_aura($s,'aura_venom');
		if($monster && !$monster['frozen']) {
			$n=fcg_ability($monster,'sunder'); $b['def']=max(0,$b['def']-$n);
			if($b['monster']) $b['monster']['def']=max(0,$b['monster']['def']-$n);
			$attack=$monster['atk']; $combo=fcg_ability($monster,'combo_attack_bonus'); $headshot=fcg_ability($monster,'headshot');
			if($combo && (!empty($s['sql_inject']) || ($s['played_this_turn'] ?? 0)>=$combo[0])) $attack+=$combo[1];
			if(!empty($monster['stealth'])) { $attack*=2; if($a['monster']) $a['monster']['stealth']=0; }
			if($headshot && $before[1-$i]['hp']<=$headshot[0]) $attack+=$headshot[1];
			$hit=fcg_packet($a,$b,$attack,fcg_ability($monster,'pierce'),fcg_ability($monster,'kill_monsters'),fcg_cards()[$monster['id']]['color'],$artillery,$m,1-$i);
			if(fcg_ability($monster,'armor_press')) fcg_hurt($b,$monster['def']);
			$damage[$i]+=$hit;
			if($hit>0) $b['vuln']=($b['vuln'] ?? 0)+fcg_ability($monster,'mark_vuln_on_hit');
			$b['regen']-=fcg_ability($monster,'poison_enemy');
			fcg_steal($a,$b,'energy',fcg_ability($monster,'steal_energy'));
		}
	}
	foreach(array(0,1) as $i) {
		$s=&$m['sides'][$i];
		if($s['regen']<0) fcg_hurt($s,-$s['regen']);
		else $s['hp']=min($s['mhp'] ?? 200,$s['hp']+$s['regen']);
		$s['bonus']=$s['pierce']=$s['evade']=0; $s['thorns']=array(0,0);
		if($s['monster'] && $s['monster']['hp']>0) {
			if($s['monster']['frozen']) $s['monster']['frozen']--;
			else {
				foreach($s['monster']['abilities'] as $op) {
					if(preg_match('/^gen\((ALL|[WRYGB]),\s*(-?\d+)\)$/',$op,$p)) fcg_energy($s,$p[1],(int)$p[2]);
					if(preg_match('/^growth\((\d+),\s*(\d+),\s*(\d+)\)$/',$op,$p)) { $s['monster']['atk']+=(int)$p[1]; $s['monster']['def']+=(int)$p[2]; $s['monster']['hp']+=(int)$p[3]; $s['monster']['maxhp']+=(int)$p[3]; }
				}
				$s['monster']['age']=($s['monster']['age'] ?? 0)+1;
				$promote=fcg_ability($s['monster'],'promote');
				if($promote && empty($s['monster']['promoted']) && ($s['monster']['age']>=2 || $s['hp']<=45)) {
					$s['monster']['promoted']=true;
					foreach(array('atk','def','hp') as $k=>$stat) $s['monster'][$stat]+=$promote[$k];
					$s['monster']['maxhp']+=$promote[2];
				}
				if($promote && !empty($s['monster']['promoted'])) fcg_hurt($m['sides'][1-$i],$promote[3]);
			}
			if(fcg_ability($s['monster'],'ephemeral')) { $s['monster']['hp']=0; fcg_monster_death($s,$m['sides'][1-$i]); }
		}
	}
	foreach(array(0,1) as $i) fcg_monster_death($m['sides'][$i],$m['sides'][1-$i]);
	foreach(array(0,1) as $i) fcg_field_turn($m,$i);
	fcg_log($m,'第 '.$m['turn'].' 回合：'.fcg_side_name($m,0).'造成 '.$damage[0].' 点伤害，'.fcg_side_name($m,1).'造成 '.$damage[1].' 点伤害。');
	fcg_finish($m);
	foreach($m['sides'] as &$s) {
		if(empty($s['played_this_turn'])) $s['vuln']=0;
		$s['played_this_turn']=$s['disarm']=$s['endure']=$s['sql_inject']=0;
	} unset($s);
	if($m['result']!==null) return;
	$m['turn']++; $m['deadline']+=5; $m['extension']=0;
	foreach($m['sides'] as &$s) { foreach(fcg_colors() as $c) fcg_energy($s,$c,$s['gen'][$c]); fcg_draw($s); } unset($s);
	if(isset($m['telemetry'])) foreach(array(0,1) as $i) { $m['telemetry'][$i]['seen_this_turn']=array(); fcg_evo_observe_hand($m,$i); }
}

function fcg_ai(&$m, $side = 1)
{
	$s=$m['sides'][$side]; $cards=fcg_cards(); $best=null; $score=-INF;
	foreach($s['hand'] as $slot=>$id) {
		$c=$cards[$id]; if($s['energy'][$c['color']]<fcg_card_cost($s,$slot) || ($c['type']==='monster' && !fcg_can_summon($s,$c))) continue;
		$value=$c['cost']+random_int(0,3);
		if($c['type']==='monster') $value+=8;
		if(strpos($c['ops'],'aura_')!==false) $value+=6;
		if(strpos($c['ops'],'combo')!==false) $value+=($s['played_this_turn'] ?? 0)>=1 ? 6 : -4;
		if(strpos($c['ops'],'resonance('.($s['last_color'] ?? '-'))!==false) $value+=5;
		if($c['cost']<=2 && ($s['played_this_turn'] ?? 0)<2) $value+=3;
		if(strpos($c['ops'],'add_gen')!==false) $value+=$m['turn']<10 ? 12 : 1;
		if($s['hp']<45 && strpos($c['ops'],'gain_hp')!==false) $value+=8;
		if(strpos($c['ops'],'field_')===0 || strpos($c['ops'],'trap_set(')===0) $value+=empty($s['field'])?6:-8;
		if(strpos($c['ops'],'sql_inject')!==false && count($s['hand'])>=3) $value+=7;
		if($value>$score) { $best=$slot; $score=$value; }
	}
	if($best!==null) fcg_play($m,$side,$best);
	elseif(count($s['hand'])===6) {
		// Do not remain permanently blocked behind a full hand of unaffordable/duplicate units.
		$worst=0; foreach($s['hand'] as $slot=>$id) if(fcg_card_cost($s,$slot)>fcg_card_cost($s,$worst)) $worst=$slot;
		foreach($s['hand'] as $slot=>$id) if(strpos($cards[$id]['ops'],'refactor(')!==false) { $worst=$slot; break; }
		fcg_discard_slot($m,$side,$worst);
	}
}

function fcg_advance(&$m, $now)
{
	// Both AI decisions and turn boundaries use the persisted clock, independent of polling frequency.
	$steps=0;
	while($m['result']===null && min($m['deadline'],$m['ai_at'])<=$now && $steps++<1200) {
		if($m['ai_at']<$m['deadline']) { $at=$m['ai_at']; fcg_ai($m); $m['ai_at']=$at+(!empty($m['hard'])?1:2); }
		else { $at=$m['deadline']; fcg_turn($m); }
		if($m['result']!==null) $m['stickers'][1]=array('id'=>$m['result']==='loss'?28:12,'at'=>$at);
	}
}

function fcg_finish(&$m)
{
	foreach($m['sides'] as &$s) { $s['hp'] = min($s['mhp'] ?? 200,$s['hp']); $s['atk'] = max(0,$s['atk']); $s['def'] = max(0,$s['def']); } unset($s);
	fcg_monster_death($m['sides'][0],$m['sides'][1]);
	fcg_monster_death($m['sides'][1],$m['sides'][0]);
	foreach($m['sides'] as &$s) if(!empty($s['endure'])) $s['hp']=max(1,$s['hp']); unset($s);
	if($m['sides'][0]['hp'] <= 0 || $m['sides'][1]['hp'] <= 0) {
		$m['result'] = $m['sides'][0]['hp'] <= 0 ? ($m['sides'][1]['hp'] <= 0 ? 'draw' : 'loss') : 'win';
	}
}
