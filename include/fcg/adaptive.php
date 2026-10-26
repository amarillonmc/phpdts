<?php
if(!defined('IN_GAME')) exit('Access Denied');

function fcg_evo_initial_state()
{
	$stats=array('matches'=>0,'total_matches'=>0,'wins'=>0,'total_turns'=>0,'energy_starved'=>0,'hand_starved'=>0,'last_change'=>'初始平衡构筑。');
	return array('gen'=>array_fill_keys(fcg_colors(),1),'decks'=>fcg_npc_decks()['adaptive'],
		'deck_stats'=>array_fill_keys(fcg_colors(),$stats),'card_stats'=>array());
}

function fcg_evo_observe_hand(&$m, $side)
{
	if(!isset($m['telemetry'][$side])) return;
	$t=&$m['telemetry'][$side];
	foreach(array_unique($m['sides'][$side]['hand']) as $id) if(empty($t['seen_this_turn'][$id])) {
		$t['seen_turns'][$id]=($t['seen_turns'][$id] ?? 0)+1;
		$t['seen_this_turn'][$id]=true;
	}
}

function fcg_evo_end_turn(&$m)
{
	if(!isset($m['telemetry'])) return;
	$cards=fcg_cards();
	foreach(array(0,1) as $side) {
		fcg_evo_observe_hand($m,$side); $s=$m['sides'][$side]; $t=&$m['telemetry'][$side];
		$t['turns']++; if(count($s['hand'])<=1) $t['hand_starved']++;
		$affordable=false;
		foreach($s['hand'] as $slot=>$id) {
			$c=$cards[$id];
			// 帮手占位不属于缺能量；免费检索牌也不能被误算成卡费。
			if($s['energy'][$c['color']]>=(!empty($s['free'][$slot])?0:$c['cost'])) $affordable=true;
		}
		foreach(array_unique($s['hand']) as $id) $t['stalled_turns'][$id]=($t['stalled_turns'][$id] ?? 0)+1;
		if($s['hand'] && !$affordable) $t['energy_starved']++;
		unset($t);
	}
}

function fcg_evo_cross_resonance($card, $previous)
{
	if($previous===null || $previous===$card['color']) return false;
	return preg_match('/resonance\('.preg_quote($previous,'/').'\s*,/',$card['ops'])===1;
}

function fcg_evo_tier($card)
{
	// #20/#21 虽不受玩家同名上限约束，仍是 7～8 费牌，不能充当开局能量基底。
	return $card['unlimited'] && $card['cost']<=1 ? 0 : ($card['cost']<=4 ? 1 : ($card['cost']<=6 ? 2 : 3));
}

// 这是单色构筑的静态估值，不改实际卡牌效果；条件收益和需要配合的词条打折。
function fcg_evo_effect_value($ops, $card)
{
	$value=0;
	$weights=array('lure'=>4,'escort'=>5,'stealth'=>5,'kill_monsters'=>5,'rebirth_once'=>8,'aura_artillery'=>5,
		'aura_burn_monster_heal'=>6,'clean_self_poison'=>4,'cleanse_flip_self'=>7,'clean_self_poison_to_enemy'=>6,
		'invert_enemy_regen'=>5,'endure_lethal'=>7,'kill_enemy_monster'=>12,'kill_monster_absorb_hp'=>16,
		'code_fu_search'=>12,'mystery_upgraded_zero_cost'=>10,'mirror_balance_stats'=>8,'steal_monster_atk_to_self'=>5,
		'shuffle_stats'=>1,'clean_self_poison_on_death'=>3,'ephemeral'=>-5,'weak_to_red'=>-4);
	foreach(fcg_tokens($ops) as $token) {
		if(preg_match('/^(combo|resonance)\(([^,]+),\s*(.*)\)$/',$token,$p)) {
			if($p[1]==='resonance' && !in_array(trim($p[2]),array($card['color'],'ANY'),true)) continue;
			$value+=fcg_evo_effect_value($p[3],$card)*($p[1]==='combo'?0.6:0.8); continue;
		}
		if(preg_match('/^combo_(\d+)_(.*)$/',$token,$p)) { $value+=fcg_evo_effect_value($p[2],$card)*0.6; continue; }
		if(strpos($token,'scale_by(')===0) { $value+=12; continue; }
		if(!preg_match('/^(\w+)(?:\((.*)\))?$/',$token,$p)) continue;
		$op=$p[1]; $v=isset($p[2])?array_map('trim',explode(',',$p[2])):array();
		$n=isset($v[0])?(float)$v[0]:0; $b=isset($v[1])?(float)$v[1]:0;
		if(isset($weights[$op])) { $value+=$weights[$op]; continue; }
		switch($op) {
			case 'add_energy': case 'add_gen': case 'gen':
				if(in_array($v[0],array($card['color'],'ALL'),true)) $value+=$b*($op==='add_energy'?1.2:3); break;
			case 'gain_atk': case 'gain_def': $value+=($v[0]==='enemy'?-1:1)*$b*1.6; break;
			case 'gain_hp': $value+=($v[0]==='enemy'?-1:1)*$b*0.6; break;
			case 'lose_hp': $value+=($v[0]==='enemy'?1:-1)*$b; break;
			case 'gain_atk_def': $value+=($b+(float)$v[2])*1.6; break;
			case 'add_regen': case 'add_poison': $value+=$b*3; break;
			case 'draw': $value+=$n*4; break;
			case 'desperate_draw': $value+=$b*2; break;
			case 'growth': $value+=($n*1.8+$b*1.6+(float)$v[2]*0.7)*1.5; break;
			case 'promote': $value+=($n+$b+(float)$v[2]+(float)$v[3])*0.6; break;
			case 'pierce': case 'poison_enemy': case 'aura_venom': case 'aura_spell_zap': $value+=$n*2; break;
			case 'pierce_enemy': case 'rebirth_dmg_enemy': case 'on_summon_dmg_enemy': $value+=$n; break;
			case 'turn_bonus_dmg': case 'on_summon_barrier': $value+=$n*0.6; break;
			case 'gain_barrier': $value+=$b*0.6; break;
			case 'gain_monster_def': case 'gain_monster_hp': case 'lose_monster_hp': $value+=$b*0.5; break;
			case 'freeze_enemy_monster': $value+=min(5,$n)*1.4; break;
			case 'weaken_enemy': $value+=($n+$b)*0.9; break;
			case 'sunder_enemy': case 'sunder': case 'disarm_enemy': case 'disarm_attack': case 'vuln_enemy': case 'mark_vuln_on_hit': $value+=$n*0.9; break;
			case 'steal_energy': $value+=$n*1.8; break;
			case 'steal_stat': case 'on_summon_steal_stat': $value+=$b*($v[0]==='gen_max'?4:2); break;
			case 'set_evade': $value+=4*$b; break;
			case 'set_thorns': $value+=min(12,(float)substr($v[2],4)*0.5); break;
			case 'invert_or_burst_poison': $value+=$b; break;
			case 'burst_poison': $value+=$n*4; break;
			case 'mirror_last_spell': $value+=8; break;
			case 'on_summon_gain_hp': case 'on_summon_eat_poison': $value+=$n*0.6; break;
			case 'on_summon_gain_def': $value+=$n*1.6; break;
			case 'on_summon_flatten_def': $value+=8; break;
			case 'on_summon_chaos_blessing': $value+=1; break;
			case 'enrage_atk': $value+=$n*2; break;
			case 'headshot': case 'combo_attack_bonus': $value+=$b*0.6; break;
			case 'clone_monster_stats': $value+=$n*12; break;
			case 'aura_bug_draw': $value+=5; break;
			case 'buff_or_summon_guardian': $value+=35; break;
		}
	}
	return $value;
}

function fcg_card_prior_score($card)
{
	if($card['unlimited']) return $card['upgraded']?75:62;
	$raw=$card['atk']*1.8+$card['def']*1.6+$card['hp']*0.7+fcg_evo_effect_value($card['ops'],$card);
	$score=40+6*$raw/max(1.5,$card['cost']+1)+($card['upgraded']?6:0);
	if(strpos($card['ops'],'mirror_last_spell')!==false) $score-=5;
	return max(40,min(85,$score));
}

function fcg_evo_card_score($state, $id, $draw_bonus = false)
{
	$card=fcg_cards()[$id]; $s=$state['card_stats'][$id] ?? array(); $d=$state['deck_stats'][$card['color']];
	$base=($d['wins']+1)/($d['total_matches']+2);
	$npc=(($s['npc_played_wins'] ?? 0)+2*$base)/(($s['npc_played_games'] ?? 0)+2)*100;
	$plr=(($s['plr_played_wins'] ?? 0)+1)/(($s['plr_played_games'] ?? 0)+2)*100;
	$stall=(($s['npc_stalled'] ?? 0)+0.5*($s['plr_stalled'] ?? 0))/max(1,($s['npc_seen'] ?? 0)+0.5*($s['plr_seen'] ?? 0));
	$score=0.60*$npc+0.25*$plr+0.15*fcg_card_prior_score($card)-min(1,$stall)*35;
	// 只有单色可触发的抽牌收益享受缺牌补正。
	$ops=$card['ops'];
	foreach(fcg_tokens($ops) as $token) if(preg_match('/^resonance\(([WRYGB]),/',$token,$p) && $p[1]!==$card['color']) $ops=str_replace($token,'',$ops);
	if($draw_bonus && strpos($ops,'draw(')!==false) $score+=10;
	return $score;
}

function fcg_evo_candidate($state, $color, $counts, $tiers, $exclude, $draw_bonus, $explore)
{
	$candidates=array(); $cards=fcg_cards();
	foreach($cards as $id=>$c) {
		if($c['color']!==$color || $id===$exclude || !in_array(fcg_evo_tier($c),$tiers,true)) continue;
		if(!$c['unlimited'] && ($counts[$id] ?? 0)>=($c['rare']?2:4)) continue;
		$candidates[$id]=fcg_evo_card_score($state,$id,$draw_bonus);
	}
	if(!$candidates) return null;
	if($explore && random_int(1,100)<=15) {
		// 冷门候选优先：只在实战样本最少的一组里随机探索。
		$least=INF; $pool=array();
		foreach($candidates as $id=>$score) {
			$n=$state['card_stats'][$id]['npc_played_games'] ?? 0;
			if($n<$least) { $least=$n; $pool=array(); }
			if($n==$least) $pool[]=$id;
		}
		return $pool[random_int(0,count($pool)-1)];
	}
	arsort($candidates,SORT_NUMERIC); $best=key($candidates);
	return $exclude!==null && $candidates[$best]<=fcg_evo_card_score($state,$exclude,$draw_bonus) ? null : $best;
}

function fcg_evolve_npc_deck(&$state, $color)
{
	$deck=&$state['decks'][$color]; $d=&$state['deck_stats'][$color]; $cards=fcg_cards();
	$counts=array_count_values($deck); $tiers=array_fill(0,4,0); $rank=array(); $changes=array(); $curve_slot=null;
	$starve=$d['energy_starved']/max(1,$d['total_turns']);
	$draw_bonus=$starve<0.08 && $d['hand_starved']/max(1,$d['total_turns'])>0.30;
	foreach($deck as $slot=>$id) { $tiers[fcg_evo_tier($cards[$id])]++; $rank[$slot]=fcg_evo_card_score($state,$id,$draw_bonus); }
	asort($rank,SORT_NUMERIC);
	$from=null; $to=array();
	if($starve>0.22 && $tiers[0]<12 && $tiers[3]>2) { $from=3; $to=array(0); }
	elseif($draw_bonus && $tiers[0]>9) {
		$from=0; foreach(array(1=>12,2=>8,3=>4) as $tier=>$cap) if($tiers[$tier]<$cap) $to[]=$tier;
	}
	if($from!==null && $to) foreach($rank as $slot=>$score) if(fcg_evo_tier($cards[$deck[$slot]])===$from) {
		$in=fcg_evo_candidate($state,$color,$counts,$to,null,$draw_bonus,false);
		if($in!==null) { $out=$deck[$slot]; $deck[$slot]=$in; $counts[$out]--; $counts[$in]=($counts[$in] ?? 0)+1; $changes[]=array($out,$in); $curve_slot=$slot; }
		break;
	}
	$n=0;
	foreach($rank as $slot=>$score) {
		$out=$deck[$slot]; if($slot===$curve_slot || $cards[$out]['unlimited']) continue;
		if($n++>=2) break;
		$in=fcg_evo_candidate($state,$color,$counts,array(fcg_evo_tier($cards[$out])),$out,$draw_bonus,true);
		if($in===null) continue;
		$deck[$slot]=$in; $counts[$out]--; $counts[$in]=($counts[$in] ?? 0)+1; $changes[]=array($out,$in);
	}
	$state['gen'][$color]++;
	$lines=array(); foreach($changes as $pair) $lines[]='「'.$cards[$pair[0]]['name'].'」换为「'.$cards[$pair[1]]['name'].'」';
	$d['last_change']=$lines ? implode('；',$lines).'。' : '本轮保留原构筑，继续观察。';
	$d['matches']=$d['total_turns']=$d['energy_starved']=$d['hand_starved']=0;
	foreach($state['card_stats'] as $id=>&$s) if($cards[$id]['color']===$color) foreach($s as &$value) $value*=0.85;
	unset($s,$value);
}

function fcg_evo_record_and_optimize(&$state, $m)
{
	if(($m['opponent'] ?? null)!==21 || !empty($m['pvp']) || $m['result']===null || $m['turn']<3 || !isset($m['telemetry'],$m['ai_color'])) return false;
	$color=$m['ai_color']; $d=&$state['deck_stats'][$color]; $d['matches']++; $d['total_matches']++;
	if($m['result']==='loss') $d['wins']++;
	$t=$m['telemetry'][1]; $d['total_turns']+=$t['turns']; $d['energy_starved']+=$t['energy_starved']; $d['hand_starved']+=$t['hand_starved'];
	foreach(array(0=>'plr',1=>'npc') as $side=>$prefix) {
		$t=$m['telemetry'][$side]; $won=$m['result']===($side===0?'win':'loss');
		foreach(array_unique(array_merge(array_keys($t['seen_turns']),array_keys($t['played']))) as $id) {
			if(!isset($state['card_stats'][$id])) $state['card_stats'][$id]=array();
			$s=&$state['card_stats'][$id];
			foreach(array('plays'=>'played','stalled'=>'stalled_turns','seen'=>'seen_turns') as $key=>$source) $s[$prefix.'_'.$key]=($s[$prefix.'_'.$key] ?? 0)+($t[$source][$id] ?? 0);
			// 所有颜色均入库；仅剔除依赖异色余韵的玩家出牌胜率样本。
			if(!empty($t[$side===0?'solo_played':'played'][$id])) {
				$s[$prefix.'_played_games']=($s[$prefix.'_played_games'] ?? 0)+1;
				$s[$prefix.'_played_wins']=($s[$prefix.'_played_wins'] ?? 0)+($won?1:0);
			}
			unset($s);
		}
	}
	if($d['matches']>=12) fcg_evolve_npc_deck($state,$color);
	return true;
}
