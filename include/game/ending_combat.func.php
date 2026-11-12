<?php
if(!defined('IN_GAME')) exit('Access Denied');
include_once GAME_ROOT.'./include/game/severe_status.func.php';

function erc_config($key,$default=0)
{
	static $values;
	if($values === NULL) {
		include config('dissociation',$GLOBALS['gamecfg'] ?? 1);
		$values = isset($dissociation) ? $dissociation : array();
	}
	return $values[$key] ?? $default;
}

function erc_has($data,$skill)
{
	if(!severe_enabled() || !empty($data['erc_retro'])) return false;
	$p = severe_para($data);
	if($skill==='npc_weak_analysis' && empty($data['type']) && isset($p['skillpara'][$skill]['active']) && !$p['skillpara'][$skill]['active']) return false;
	return in_array($skill,$p['skill'] ?? array(),true) || !empty($p['talent_skills'][$skill]);
}

function erc_pay(&$data,$sp,$chance=100)
{
	if(!empty($data['type'])) return $chance>=100 || diceroll(99)<$chance;
	if($data['sp'] < $sp || diceroll(99) >= $chance) return false;
	$data['sp'] -= $sp;
	return true;
}

function erc_animal_skills()
{
	return array('npc_honor_fist','npc_endurance','npc_joint_lock','npc_fighting_spirit','npc_foxfire','npc_gnaw','npc_luck_charge');
}

// 湾城中的技能传送仍能使用，但目的地必须留在当前投影。
function erc_teleport_places($data,$mode=0)
{
	if(severe_enabled() && (int)($data['pgroup'] ?? 0)==3) return array_keys($GLOBALS['hplsinfo'][3] ?? array_fill_keys(range(120,129),''));
	return get_safe_plslist($mode);
}

function erc_place_name($data,$place)
{
	return $GLOBALS['hplsinfo'][(int)($data['pgroup'] ?? 0)][$place] ?? $GLOBALS['plsinfo'][$place] ?? '未知地点';
}

function erc_bone_immune(&$data)
{
	return erc_has($data,'npc_bonearmor') && erc_pay($data,30,!empty($data['type']) ? 100 : 50);
}

function erc_retro_begin(&$data)
{
	if(!empty($data['erc_retro'])) return;
	$save = array();
	foreach(array('att','def','mhp','msp','wp','wk','wc','wg','wd','wf','wb','wepe','arbe','arhe','arae','arfe','arte') as $key) {
		if(isset($data[$key]) && $data[$key] > 65535) {
			$save['numbers'][$key] = $data[$key]-65535;
			$data[$key] = 65535;
		}
	}
	foreach(array('wepsk','arbsk','arhsk','arask','arfsk','artsk') as $key) {
		$save['strings'][$key] = $data[$key] ?? '';
		$slot=substr($key,0,-2); $save['names'][$slot]=$data[$slot] ?? '';
		$data[$key] = implode('',array_slice(preg_split('//u',$save['strings'][$key],-1,PREG_SPLIT_NO_EMPTY),0,5));
	}
	$save['skills'] = $data['clbpara']['skill'] ?? array();
	$save['talents'] = $data['clbpara']['talent_skills'] ?? array();
	$data['clbpara']['skill'] = $data['clbpara']['talent_skills'] = array();
	foreach(array_keys($data) as $key) if(strpos($key,'skill_') === 0 || strpos($key,'bskill_') === 0 || strpos($key,'askill_') === 0) { $save['flags'][$key]=$data[$key]; unset($data[$key]); }
	unset($data['bskill'],$data['bskilllog']);
	$data['erc_retro'] = $save;
}

function erc_retro_end(&$data)
{
	if(empty($data['erc_retro'])) return;
	$save = $data['erc_retro'];
	foreach($save['numbers'] ?? array() as $key=>$extra) {
		$slot=substr($key,0,-1);
		if(isset($save['names'][$slot]) && ($data[$slot] ?? '')!=$save['names'][$slot]) continue;
		$data[$key] += $extra;
	}
	foreach($save['strings'] as $key=>$value) {
		// 武器或防具已被打碎时，不能把它的属性复原成幽灵装备。
		$slot = substr($key,0,-2);
		if(!empty($data[$slot.'s']) && ($data[$slot] ?? '')==($save['names'][$slot] ?? '')) $data[$key] = $value;
	}
	$data['clbpara']['skill'] = $save['skills'];
	$data['clbpara']['talent_skills'] = $save['talents'];
	foreach($save['flags'] ?? array() as $key=>$value) $data[$key] = $value;
	unset($data['erc_retro']);
}

function erc_prepare(&$pa,&$pd)
{
	if(!severe_enabled()) return;
	$pa['clbpara'] = severe_para($pa); $pd['clbpara'] = severe_para($pd);
	if(erc_has($pa,'npc_retro_barrier')) erc_retro_begin($pd);
	if(erc_has($pd,'npc_retro_barrier')) erc_retro_begin($pa);
	erc_prepare_one($pa,$pd); erc_prepare_one($pd,$pa);
}

function erc_prepare_one(&$source,&$target)
{
	global $now;
	unset($source['erc_damage_applied'],$source['erc_skip'],$source['erc_retreat']);
	unset($source['clbpara']['severe_rest_elapsed']);
	if(!empty($source['clbpara']['blood_poison']) && strpos($source['inf'] ?? '','p')===false) unset($source['clbpara']['blood_poison']);
	if(isset($source['clbpara']['rook_lock_turns'])) {
		$source['clbpara']['rook_lock_turns']--;
		if($source['clbpara']['rook_lock_turns']<=0) { $source['inf']=str_replace('l','',$source['inf'] ?? ''); unset($source['clbpara']['rook_lock_turns'],$source['clbpara']['rook_lock_until']); }
	}
	if(severe_curse($source,'illluck')) $source['clbpara']['illluck'] = rand(60,90)/100;
	if(erc_has($source,'npc_steam_guard') && !empty($source['type']) && $source['hp'] > 0) $source['hp']=$source['mhp'];
	if(erc_has($source,'npc_talisman') && !empty($source['type'])) {
		if(!isset($source['clbpara']['talisman_layers']) || (!empty($source['clbpara']['talisman_ready']) && $source['clbpara']['talisman_ready'] <= $now)) {
			$source['clbpara']['talisman_layers']=4;
			unset($source['clbpara']['talisman_ready']);
		}
	}
	if(erc_has($source,'npc_luck_charge')) {
		$source['clbpara']['luck_charge'] = min(erc_config('luck_max',80),(int)($source['clbpara']['luck_charge'] ?? 0)+(!empty($source['type']) ? erc_config('luck_charge',20) : (erc_has($source,'npc_lucky_escape') ? 15 : 10)));
	}
	if(erc_has($source,'npc_afterimage') && !empty($source['type'])) {
		$position=(int)($source['pgroup'] ?? 0).':'.(int)$source['pls'];
		if(isset($source['clbpara']['afterimage_position']) && $source['clbpara']['afterimage_position']!==$position) $source['clbpara']['afterimage']=min(3,($source['clbpara']['afterimage'] ?? 0)+1);
		$source['clbpara']['afterimage_position']=$position;
	}
	if(erc_has($source,'npc_nightmare_domain') && !empty($source['type'])) {
		$target['clbpara']['nightmare_layers']=min(10,($target['clbpara']['nightmare_layers'] ?? 0)+1);
		if($target['clbpara']['nightmare_layers'] >= 10) { $target['erc_selfstrike']=1; $target['clbpara']['nightmare_layers']=0; }
	}
	if(empty($target['type']) && !empty($source['type'])) {
		foreach(erc_animal_skills() as $i=>$skill) {
			if(!erc_has($source,$skill)) continue;
			if(!isset($target['clbpara']['animal_debuff'][$i])) $target['clbpara']['animal_debuff'][$i]=0;
			$target['clbpara']['animal_debuff'][$i] = min(20,$target['clbpara']['animal_debuff'][$i]+1);
		}
		if(erc_has($source,'npc_fighting_spirit') && !isset($target['clbpara']['animal_spirit'])) $target['clbpara']['animal_spirit']=100;
		if(erc_has($source,'npc_foxfire')) $target['clbpara']['foxfire_step']=(int)$source['type'] == 35 ? erc_config('foxfire_inherited',5) : erc_config('foxfire',8);
		if(($target['clbpara']['animal_debuff'][2] ?? 0)>=3) $target['sp']=max(0,$target['sp']-(int)ceil($target['msp']*.05));
	}
	erc_cap_sp($target);
}

function erc_cap_sp(&$data)
{
	if(!severe_enabled()) return;
	$p=severe_para($data); $layers=(int)($p['animal_debuff'][1] ?? 0);
	if($layers>0) $data['sp']=min($data['sp'],(int)round($data['msp']*max(.2,1-erc_config('endurance_maxsp',.04)*$layers)));
}

function erc_jam_strength($data)
{
	$p=severe_para($data);
	return ($p['jam_until'] ?? 0) > ($GLOBALS['now'] ?? 0) ? min(1,(float)($p['jam_strength'] ?? 1)) : 0;
}

function erc_can_electronic($data,$kind,$name)
{
	if(!severe_enabled() || !(preg_match('/^(R|EE|ER)/',$kind) || $name=='移动PC')) return true;
	if(diceroll(99)<70*erc_jam_strength($data)) { $GLOBALS['log'].='嗡——设备的屏幕只剩下一片雪花。<br>'; return false; }
	return true;
}

function erc_interfere(&$source,&$target)
{
	global $now,$db,$tablepre;
	$power=1;
	if((int)($source['type'] ?? 0) == 34 && ($source['clbpara']['er_sub'] ?? -1) == 4) {
		$result=$db->query("SELECT COUNT(*) FROM {$tablepre}players WHERE type=42 AND hp>0");
		$power=min(1,(int)$db->result($result,0)/2);
	}
	if($power <= 0 || !erc_pay($source,40,!empty($source['type']) ? 100 : 30)) return;
	$target['clbpara']['jam_until']=$now+(!empty($source['type']) ? 90 : 30);
	$target['clbpara']['jam_strength']=$power;
}

function erc_before_attack(&$pa,&$pd)
{
	global $log;
	if(!severe_enabled()) return true;
	if(!empty($pa['clbpara']['sendo_subdued'])) return false;
	if(!empty($pa['clbpara']['erc_skip_attack'])) {
		unset($pa['clbpara']['erc_skip_attack']);
		$log .= '锁链缠住了手腕，这一次没能出手。<br>'; return false;
	}
	$joint=(int)($pa['clbpara']['animal_debuff'][2] ?? 0);
	if($joint >= 3) { $log .= '腕、腿与颈部同时被锁住了，必须先挣脱关节锁。<br>'; return false; }
	$cost = (int)ceil(($pa['msp'] ?? 0)*.01*($pa['clbpara']['animal_debuff'][1] ?? 0));
	if($cost > 0) {
		if($pa['sp'] < $cost) { $log .= '消耗战已经耗尽了出手的体力。<br>'; return false; }
		$pa['sp'] -= $cost;
	}
	if(!empty($pd['clbpara']['afterimage']) && (!empty($pd['type']) || erc_pay($pd,30,35))) {
		$pd['clbpara']['afterimage']--;
		$pa['erc_afterimage_miss']=1;
		$log .= '攻击只穿过了一道残影。<br>'; return false;
	}
	if(!empty($pd['type']) && erc_has($pd,'npc_prefetch') && erc_pay($pd,80,erc_config('omicron_prefetch',20))) {
		$pa['erc_prefetched_by']=$pd['pid'];
		$log .= '维度的缝隙已经张开，她正在预取你的攻势。<br>';
	}
	if(erc_has($pa,'npc_interference') && !empty($pa['type'])) erc_interfere($pa,$pd);
	if(erc_has($pa,'npc_sequence')) erc_interfere($pa,$pd);
	if(!empty($pa['type']) && erc_has($pa,'npc_afterimage')) $pa['clbpara']['afterimage']=min(3,($pa['clbpara']['afterimage'] ?? 0)+1);
	if(erc_has($pa,'npc_arsenal') && empty($pa['type']) && erc_pay($pa,30)) $pa['wep_range']=($pa['wep_range'] ?? 0)+2;
	return true;
}

function erc_hitrate(&$pa,&$pd,$rate)
{
	if(!severe_enabled()) return $rate;
	if(severe_curse($pa,'confusion')) $rate*=.67;
	if(severe_curse($pa,'illluck')) $rate*=($pa['clbpara']['illluck'] ?? .75);
	$rate-=20*erc_jam_strength($pa);
	$rate-=min(80,($pa['clbpara']['animal_debuff'][4] ?? 0)*($pa['clbpara']['foxfire_step'] ?? 8));
	$rate*=max(.5,1-.05*($pa['clbpara']['nightmare_layers'] ?? 0));
	if(!empty($pa['erc_snipe'])) $rate+=30;
	if(erc_has($pa,'npc_snow_sight') && erc_pay($pa,20)) $rate+=10;
	if(empty($pa['type']) && erc_has($pa,'npc_nightmare_domain') && ($pd['clbpara']['nightmare_layers'] ?? 0)>=5) $rate=100;
	return !empty($pa['clbpara']['animal_debuff'][4]) ? max(1,min(100,$rate)) : $rate;
}

function erc_base_att($data,$value)
{
	if(!severe_enabled()) return $value;
	if(severe_has($data,'frenzy')) $value*=1.25;
	if(!empty($data['clbpara']['animal_debuff'][2])) $value*=.7;
	if(erc_has($data,'npc_talisman') && !empty($data['clbpara']['talisman_layers']) && ($data['wep_kind'] ?? '') == 'F') $value*=1.2;
	return max(1,(int)round($value));
}

function erc_base_def(&$attacker,$defender,$value)
{
	if(!severe_enabled()) return $value;
	if(severe_has($defender,'frenzy')) $value*=1.25;
	if(erc_has($attacker,'npc_weak_analysis') && erc_pay($attacker,20)) {
		$p=&$attacker['clbpara']['analysis'];
		$stacks=(($p['target'] ?? 0) == $defender['pid']) ? (int)($p['stacks'] ?? 0)+1 : 1;
		$p=array('target'=>$defender['pid'],'stacks'=>min(5,$stacks));
		$value*=($stacks >= 5 && erc_has($attacker,'npc_weak_break')) || ($stacks >= 5 && !empty($attacker['type'])) ? 0 : 1-.08*min(5,$stacks);
	}
	if(!empty($attacker['erc_snipe'])) $value*=.7;
	if(empty($attacker['type']) && erc_has($attacker,'npc_hydraulic') && ($defender['clbpara']['hydraulic_soft'] ?? 0)>=3) $value=0;
	if(empty($attacker['type']) && erc_has($attacker,'npc_deathomen') && ($defender['clbpara']['deathomen'] ?? 0)>=3) $value*=.5;
	// 现役物理公式直接除以防御值，贯穿后仍保留分母的最小正值。
	return max(.01,$value);
}

function erc_battle_skill(&$pa,&$pd)
{
	$skill=$pa['bskill'] ?? '';
	if(!in_array($skill,array('npc_rook_lock','npc_headshot'),true)) return false;
	if(!erc_has($pa,$skill)) { unset($pa['bskill']); return true; }
	$cost=$skill=='npc_rook_lock' ? 250 : 300;
	if(!erc_pay($pa,$cost)) { $GLOBALS['log'].='发动这一招所需的体力不足。<br>'; unset($pa['bskill']); return true; }
	if($skill=='npc_rook_lock') $pa['erc_lock']=1;
	else $pa['erc_snipe']=1;
	$pa['bskilllog']=$skill=='npc_rook_lock' ? '你伸出双臂，准备将对手锁住。<br>' : '你放弃掩护，专注于这一次狙击。<br>';
	return true;
}

function erc_physical_ratio($pa,$pd)
{
	if(!severe_enabled()) return 1;
	$r=1;
	if((int)($pd['race'] ?? 0)==1 && ($pa['wep_kind'] ?? '')!='F') $r*=erc_config('beast_physical',.35);
	if((int)($pa['race'] ?? 0)==2 && ($pa['wep_kind'] ?? '')=='F') $r*=erc_config('fairy_spirit',1.25);
	return $r;
}

function erc_element_ratio($pa,$pd,$key)
{
	if(!severe_enabled()) return 1;
	$r=1;
	if((int)($pd['race'] ?? 0)==1) $r*=erc_config($key=='u' ? 'beast_fire' : 'beast_element',$key=='u' ? 2 : 1.3);
	$names=array('u'=>'炎','i'=>'霜','e'=>'雷','w'=>'鸣','p'=>'毒');
	if(isset($names[$key])) foreach(array($pa,$pd) as $person) if((int)($person['race'] ?? 0)==2 && ($person['arb'] ?? '')=='妖精羽翼·'.$names[$key]) $r*=2;
	return $r;
}

function erc_snapshot($data)
{
	$out=array();
	foreach(array('att','def','mhp','msp','wp','wk','wc','wg','wd','wf','wb','lvl','rage') as $key) $out[$key]=(int)($data[$key] ?? 0);
	foreach(array('wep','arb','arh','ara','arf','art') as $slot) foreach(array('','k','e','s','sk','para') as $suffix) $out[$slot.$suffix]=$data[$slot.$suffix] ?? '';
	for($i=1;$i<=6;$i++) foreach(array('itm','itmk','itme','itms','itmsk','itmpara') as $key) $out[$key.$i]=$data[$key.$i] ?? '';
	return $out;
}

function erc_similar($a,$b)
{
	if(!$a || !$b) return false;
	// 换武器是明确的破解方式：武器身份与其余面板各占一半权重。
	$weapon=0; foreach(array('wep','wepk','wepe','wepsk') as $key) if(($a[$key] ?? NULL)===($b[$key] ?? NULL)) $weapon+=.125;
	$matches=0; foreach($a as $key=>$value) if(($b[$key] ?? NULL)===$value) $matches++;
	return $weapon+.5*$matches/count($a) >= erc_config('diamond_similarity',.9);
}

function erc_damage(&$pa,&$pd,$damage)
{
	global $now,$log;
	if(!severe_enabled() || $damage <= 0) return $damage;
	if((int)($pa['horizon'] ?? 0)==2 && (int)($pd['horizon'] ?? 0)==2) $damage*=erc_config('ex_damage',1.5);
	if(severe_curse($pa,'weakness')) $damage*=.67;
	if(severe_curse($pa,'illluck')) $damage*=($pa['clbpara']['illluck'] ?? .75);
	$damage*=max(.5,1-.05*($pa['clbpara']['nightmare_layers'] ?? 0));
	if(erc_has($pd,'npc_weak_analysis') && empty($pd['type'])) $damage*=1.15;
	if(erc_has($pa,'npc_hydraulic')) $damage*=1+.25*(int)($pd['clbpara'][!empty($pa['type']) ? 'hydraulic_mark' : 'hydraulic_soft'] ?? 0);
	if(erc_has($pa,'npc_blood_draw')) $damage*=1+max(0,1-$pd['hp']/max(1,$pd['mhp']));
	if(erc_has($pa,'npc_spark') && erc_pay($pa,20,30)) $damage+=min(1000,max(25,(int)($pa['wf'] ?? 0)/5));
	if(!empty($pa['type']) && erc_has($pa,'npc_white_phosphorus') && strpos($pd['inf'] ?? '','u')!==false) $damage*=2;
	if(!empty($pa['clbpara']['prefetch_damage'])) { $damage+=$pa['clbpara']['prefetch_damage']; unset($pa['clbpara']['prefetch_damage']); }
	if(erc_has($pa,'npc_rook_lock') && !empty($pa['type']) && !empty($pa['is_counter']) && diceroll(99)<65) {
		$damage+=rand(800,1200); \revattr\get_inf_rev($pd,'a');
		if(($pd['tactic'] ?? 0)==2 || !empty($pd['skill_buff_shield']) || ($pd['artk'] ?? '')==='AA') erc_damage_equipment($pd,8,false);
		$log.='铁城的重拳挟着风声撞来。<br>';
	}
	if(!empty($pa['clbpara']['rook_wall_charge'])) { $damage*=1.3; unset($pa['clbpara']['rook_wall_charge']); }
	if(erc_has($pd,'npc_sequence') && !empty($pd['type'])) {
		$key=($pa['wep_kind'] ?? '').':'.($pa['bskill'] ?? 'normal');
		$count=(int)($pd['clbpara']['sequence'][$pa['pid']][$key] ?? 0)+1;
		$pd['clbpara']['sequence'][$pa['pid']][$key]=min(3,$count);
		if(count($pd['clbpara']['sequence'])>32) unset($pd['clbpara']['sequence'][array_key_first($pd['clbpara']['sequence'])]);
		$damage*=$count>=3 ? 0 : ($count==2 ? .5 : 1);
		if($count>1) $log.='断序已经识别了这一招的轨迹。<br>';
	}
	if(erc_has($pd,'npc_fate_record') && !empty($pd['type'])) {
		$snapshot=erc_snapshot($pa);
		$record=$pd['clbpara']['fate'][$pa['pid']] ?? array();
		if(erc_similar($record['snapshot'] ?? array(),$snapshot)) { $damage=0; $log.='演算结果已被否定，这一击没有抵达现实。<br>'; }
		$pd['clbpara']['fate'][$pa['pid']]=array('snapshot'=>$snapshot,'hits'=>min(100,(int)($record['hits'] ?? 0)+1));
		if(count($pd['clbpara']['fate'])>32) unset($pd['clbpara']['fate'][array_key_first($pd['clbpara']['fate'])]);
	}
	if(erc_has($pa,'npc_fate_record') && !empty($pa['type'])) {
		$record=$pa['clbpara']['fate'][$pd['pid']] ?? array();
		if(erc_similar($record['snapshot'] ?? array(),erc_snapshot($pd))) $damage+=erc_config('diamond_true_damage',150)*(int)($record['hits'] ?? 0);
	}
	if(empty($pa['type']) && (erc_has($pa,'npc_sequence') || erc_has($pa,'npc_fate_record')) && erc_pay($pa,30)) {
		$s=&$pa['clbpara']['fate_player']; $count=($s['target'] ?? 0)==$pd['pid'] ? min(10,($s['count'] ?? 0)+1) : 1;
		$s=array('target'=>$pd['pid'],'count'=>$count);
		if(erc_has($pa,'npc_sequence')) $damage*=1+.08*$count;
		if(erc_has($pa,'npc_fate_record')) $damage+=20*$count;
	}
	if(erc_has($pa,'npc_shadow_mark')) {
		if(!empty($pa['type'])) {
			$mark=$pd['clbpara']['sanbu_shadow'] ?? array();
			$n=($mark['until'] ?? 0)>$now ? ($mark['stacks'] ?? 0)+1 : 1;
			$pd['clbpara']['sanbu_shadow']=array('stacks'=>$n,'until'=>$now+300);
			severe_apply_curse($pd,'trouble');
			if($n>=3) { $damage=max($damage,$pd['hp']); $log.='三道灯影终于重合，落影的第三步已经走完。<br>'; }
		} elseif(empty($pa['is_counter']) && erc_pay($pa,30)) {
			$s=&$pa['clbpara']['shadow_chain'];
			$n=($s['target'] ?? 0)==$pd['pid'] ? ($s['stacks'] ?? 0)+1 : 1;
			$s=array('target'=>$pd['pid'],'stacks'=>$n%3);
			if($n>=3) $damage*=2;
		}
	}
	if(erc_has($pa,'npc_headshot') && $damage >= $pd['hp']*(!empty($pa['type']) ? .85 : .5) && (!empty($pa['type']) || (!empty($pa['erc_snipe']) && diceroll(99)<15))) {
		if(severe_apply($pd,'head')) $damage=max($damage,$pd['hp']);
	}
	if(erc_has($pa,'npc_lockhead') && ((!empty($pa['type']) && $damage >= $pd['hp']*.4) || (empty($pa['type']) && $pd['hp'] <= $pd['mhp']*.4 && ($pa['rage'] ?? 0)>=150 && erc_pay($pa,150,15))) && severe_apply($pd,'head')) $damage=max($damage,$pd['hp']);
	if(erc_has($pa,'npc_hydraulic')) {
		if(!empty($pa['type']) && ($pd['clbpara']['hydraulic_mark'] ?? 0)>=3) $damage=max($damage,$pd['hp']);
		elseif(empty($pa['type']) && ($pd['clbpara']['hydraulic_soft'] ?? 0)>=3) { $damage*=2; $pd['clbpara']['hydraulic_soft']=0; }
	}
	if(erc_has($pa,'npc_deathomen') && ($pd['clbpara']['deathomen'] ?? 0)>=3) {
		$damage*=3;
		if(!empty($pa['type']) && diceroll(99)<35 && severe_apply($pd,'head')) $damage=max($damage,$pd['hp']);
		$pd['clbpara']['deathomen']=0;
	}
	if(!empty($pa['type']) && erc_has($pa,'npc_wire') && ($pd['clbpara']['wire'] ?? 0)>=2) $damage=max($damage,$pd['hp']);
	if(erc_has($pa,'npc_blood_poison') && !empty($pa['type']) && (int)$pa['type']==39 && ($pd['clbpara']['blood_poison'] ?? 0)>=3) $damage=max($damage,$pd['hp']);
	if(erc_has($pa,'npc_blood_poison') && empty($pa['type']) && ($pd['clbpara']['blood_poison_soft'] ?? 0)>=3) { $damage*=2; $pd['clbpara']['blood_poison_soft']=0; }
	if(erc_has($pa,'npc_nightmare_domain') && empty($pa['type']) && ($pd['clbpara']['nightmare_layers'] ?? 0)>=5) { $damage*=2; $pd['clbpara']['nightmare_layers']=0; }
	if(erc_has($pa,'npc_honor_fist')) {
		$floor=min(erc_config('honor_max',.85),erc_config('honor_start',.2)+erc_config('honor_step',.05)*($pd['clbpara']['animal_debuff'][0] ?? 0));
		if(!empty($pa['type'])) {
			if($pd['hp']>$pd['mhp']*$floor) $damage=min($damage,max(1,$pd['hp']-(int)ceil($pd['mhp']*$floor)));
			elseif(diceroll(99)<70) $damage=max($damage,$pd['hp']);
		} elseif(erc_pay($pa,30,30)) $damage*=1.25;
	}
	if(erc_has($pd,'npc_honor_guard') && empty($pd['type']) && erc_pay($pd,50,25)) $damage*=.8;
	if(erc_has($pd,'npc_stalwart') && erc_pay($pd,30)) $damage*=.9;
	if(erc_has($pd,'npc_bookworm') && erc_pay($pd,20)) $damage*=.85;
	if(erc_has($pd,'npc_rook_lock') && !empty($pd['type']) && diceroll(99)<15) { $damage*=.5; $pd['clbpara']['rook_wall_charge']=1; $log.='铁城架起双臂，如铁壁般顶住了攻击。<br>'; }
	if(erc_has($pd,'npc_talisman') && !empty($pd['clbpara']['talisman_layers'])) {
		$pd['clbpara']['talisman_layers']--;
		if($pd['clbpara']['talisman_layers']<=0) $pd['clbpara']['talisman_ready']=$now+erc_config('shield_cooldown',120);
		$log.='护符燃尽了一层，挡住了这一击。<br>'; $damage=0;
	}
	if(!empty($pd['type']) && erc_has($pd,'npc_nightmare_domain') && diceroll(99)<25) { $damage=0; $log.='精神屏障将攻击抹去了。<br>'; }
	if(!empty($pd['erc_snipe'])) $damage*=1.3;
	if(($pa['erc_prefetched_by'] ?? 0)==$pd['pid']) {
		$pd['clbpara']['prefetch_damage']=(int)round($damage);
		unset($pa['erc_prefetched_by']); $damage=0;
		$log.='这一击被移入了另一片空间，将成为她反击的力量。<br>';
	}
	// 红蓝的分段保护在所有固定伤害、即死和附加伤害之后执行。
	if((int)($pd['type'] ?? 0)==19 && ($pd['name'] ?? '')=='红暮') $damage=min(10000,$damage);
	if((int)($pd['type'] ?? 0)==19 && ($pd['name'] ?? '')=='蓝凝') $damage=min((int)ceil($pd['mhp']*.5),$damage);
	if((int)($pd['type'] ?? 0)==38 && ($pd['clbpara']['sendo_qid'] ?? '')=='QD3' && empty($pd['clbpara']['sendo_subdued']) && $pd['hp']-$damage <= $pd['mhp']*.15) {
		$damage=max(0,$pd['hp']-1); $pd['clbpara']['sendo_subdued']=1;
		unset($pd['gg_flag'],$pd['clbpara']['severe_head']);
		$log.='你在最后一刻收住了力道，恶霸已经无力反抗，可以使用捕绳生擒。<br>';
	}
	if(!empty($pa['erc_selfstrike'])) {
		unset($pa['erc_selfstrike']);
		$pa['c24_self_damage']=(int)round($damage);
		$log.='梦魇扭转了攻势，攻击反而落向了你自己。<br>';
		$damage=0;
	}
	if($damage<$pd['hp'] && ($pd['gg_flag'] ?? 0)==61) unset($pd['gg_flag'],$pd['clbpara']['severe_head']);
	return max(0,(int)round($damage));
}

function erc_hit(&$pa,&$pd,$damage)
{
	global $now;
	if(!severe_enabled() || $damage<=0) return;
	if(erc_has($pa,'npc_interference') && empty($pa['type'])) erc_interfere($pa,$pd);
	if(erc_has($pa,'npc_rook_lock') && (!empty($pa['type']) || !empty($pa['erc_lock']))) {
		if((!empty($pa['is_counter']) || !empty($pa['erc_lock'])) && diceroll(99)<(!empty($pa['type']) ? 55 : 100)) {
			if(strpos($pd['inf'] ?? '','l')===false) $pd['inf']=($pd['inf'] ?? '').'l';
			$pd['clbpara']['rook_lock_until']=$now+90;
			$pd['clbpara']['rook_lock_turns']=3;
		}
		if(diceroll(99)<25) severe_apply($pd,'bleeding');
		if(empty($pa['type'])) unset($pa['erc_lock']);
	}
	if(erc_has($pa,'npc_hydraulic')) {
		if(!empty($pa['type'])) {
			$pd['clbpara']['hydraulic_mark']=min(3,($pd['clbpara']['hydraulic_mark'] ?? 0)+1);
			severe_apply($pd,diceroll(99)<50 ? 'rupture' : 'upper');
			erc_damage_equipment($pd,6,true);
		} elseif(erc_pay($pa,80)) $pd['clbpara']['hydraulic_soft']=min(3,($pd['clbpara']['hydraulic_soft'] ?? 0)+1);
	}
	if(erc_has($pa,'npc_ammo_roulette') && erc_pay($pa,60)) {
		$roll=diceroll(3);
		if($roll==0) erc_damage_equipment($pd,8,false);
		elseif($roll==1) { $pd['clbpara']['jam_until']=$now+30; $pd['clbpara']['jam_strength']=1; }
		elseif($roll==2) \revattr\get_inf_rev($pd,'u');
		else $pd['clbpara']['coagulate_until']=$now+60;
	}
	if(erc_has($pa,'npc_deathomen') && erc_pay($pa,50)) {
		$pd['clbpara']['deathomen']=min(3,($pd['clbpara']['deathomen'] ?? 0)+1);
		$pd['clbpara']['searing_until']=$now+120;
		\revattr\get_inf_rev($pd,'u');
	}
	if(erc_has($pa,'npc_wire') && erc_pay($pa,50,!empty($pa['type']) ? 100 : 40)) {
		$pd['clbpara']['wire']=min(!empty($pa['type']) ? 3 : 2,($pd['clbpara']['wire'] ?? 0)+1);
		if(!empty($pa['type']) && diceroll(99)<30) severe_apply($pd,'lower');
	}
	if(erc_has($pa,'npc_nightmare_domain')) {
		if(!empty($pa['type'])) {
			if(diceroll(99)<30) $pd['clbpara']['erc_skip_attack']=1;
			severe_apply_curse($pd,diceroll(99)<50 ? 'weakness' : 'beast');
		} elseif(erc_pay($pa,50)) $pd['clbpara']['nightmare_layers']=min(5,($pd['clbpara']['nightmare_layers'] ?? 0)+1);
	}
	if(erc_has($pa,'npc_blood_poison') && erc_pay($pa,20,!empty($pa['type']) ? 100 : 35)) {
		\revattr\get_inf_rev($pd,'p');
		if(!empty($pa['type'])) {
			if((int)$pa['type']==39) $pd['clbpara']['blood_poison']=min(4,($pd['clbpara']['blood_poison'] ?? 0)+1);
			if(diceroll(99)<30) severe_apply($pd,'bleeding');
		} else $pd['clbpara']['blood_poison_soft']=min(3,($pd['clbpara']['blood_poison_soft'] ?? 0)+1);
	}
	if(erc_has($pa,'npc_stealth')) $pa['clbpara']['stealth_reveal']=$now+((int)($pa['type'] ?? 0)==39 ? 45 : 120);
	if(erc_has($pa,'npc_nightmare') && erc_pay($pa,40,30)) {
		severe_apply_curse($pd,diceroll(99)<50 ? 'darkness' : 'confusion');
		if(diceroll(99)<30) severe_apply($pd,'nightmare');
	}
	if(!empty($pa['type']) && erc_has($pa,'npc_fate_record')) severe_apply_curse($pd,'illluck');
	if(!empty($pa['type']) && erc_has($pa,'npc_dimension_theft')) erc_steal($pa,$pd);
	if(erc_has($pa,'npc_fighting_spirit') && erc_pay($pa,30)) $pd['clbpara']['animal_spirit']=max(0,($pd['clbpara']['animal_spirit'] ?? 100)-erc_config('spirit_loss',8));
	if(erc_has($pd,'npc_fighting_spirit') && isset($pa['clbpara']['animal_spirit'])) $pa['clbpara']['animal_spirit']=min(100,$pa['clbpara']['animal_spirit']+$damage*erc_config('spirit_recover',.003));
	if(erc_has($pa,'npc_joint_lock') && empty($pa['type']) && erc_pay($pa,80,25)) $pd['clbpara']['animal_debuff'][2]=min(2,($pd['clbpara']['animal_debuff'][2] ?? 0)+1);
	if(erc_has($pa,'npc_endurance') && empty($pa['type']) && erc_pay($pa,40,30)) $pd['clbpara']['animal_debuff'][1]=min(5,($pd['clbpara']['animal_debuff'][1] ?? 0)+1);
	if(erc_has($pa,'npc_magicbee') && erc_pay($pa,20,25)) \revattr\get_inf_rev($pd,'p');
	if(erc_has($pa,'npc_foxfire') && empty($pa['type']) && erc_pay($pa,30,30)) { $pd['clbpara']['animal_debuff'][4]=min(4,($pd['clbpara']['animal_debuff'][4] ?? 0)+1); $pd['clbpara']['foxfire_step']=3; }
	if(erc_has($pa,'npc_gnaw') && ($pd['clbpara']['gnaw_hits'] ?? 0)<erc_config('gnaw_limit',6) && erc_pay($pa,50,!empty($pa['type']) ? 100 : 25)) { erc_damage_equipment($pd,!empty($pa['type']) ? 5 : 2,false); $pd['clbpara']['gnaw_hits']=($pd['clbpara']['gnaw_hits'] ?? 0)+1; }
	if(severe_curse($pd,'betrayal')) $pa['clbpara']['betrayal_stacks']=min(10,($pa['clbpara']['betrayal_stacks'] ?? 0)+1);
	if(severe_curse($pa,'beast') && diceroll(99)<25) severe_apply($pd,'frenzy');
	if(erc_has($pd,'npc_weak_analysis') && ($pd['clbpara']['analysis']['target'] ?? 0)!=$pa['pid']) $pd['clbpara']['analysis']['stacks']=(int)floor(($pd['clbpara']['analysis']['stacks'] ?? 0)/2);
	if(!empty($pd['clbpara']['shadow_chain'])) unset($pd['clbpara']['shadow_chain']);
	if((erc_has($pa,'npc_stage_mine') || ((int)($pa['type'] ?? 0)==19 && ($pa['name'] ?? '')==='蓝凝') || erc_has($pa,'npc_white_phosphorus')) && diceroll(99)<25) severe_apply($pd,'lower');
}

function erc_damage_equipment(&$data,$amount,$weapon)
{
	if($weapon && !empty($data['weps'])) weapon_loss($data,$amount,1);
	$slots=array(); foreach(array('arb','arh','ara','arf') as $slot) if(!empty($data[$slot.'s'])) $slots[]=$slot;
	if($slots) armor_hurt($data,$slots[array_rand($slots)],$amount);
}

function erc_after_damage(&$attacker,&$victim)
{
	global $now,$log,$db,$tablepre;
	if(!severe_enabled() || $victim['hp']<=0 || ($attacker['final_damage'] ?? 0)<=0) return false;
	if(erc_has($victim,'npc_pawn_evolve') && !empty($victim['type']) && empty($victim['clbpara']['pawn_evolved']) && $victim['hp']<=$victim['mhp']/2) {
		$victim['clbpara']['pawn_evolved']=1;
		$victim['mhp']=32000; $victim['hp']=min(32000,$victim['hp']+12000);
		$victim['att']=7000; $victim['def']=2600;
		$log.='银锤笑了：「那么，谢幕演出开始——！」<br>';
	}
	if((erc_has($victim,'npc_stage_mine') || erc_has($victim,'npc_white_phosphorus')) && !empty($victim['type'])) erc_mine($victim,$attacker);
	if((int)($victim['type'] ?? 0)===34 && ($victim['clbpara']['er_sub'] ?? -1)==6 && diceroll(99)<25) {
		$count=(int)$db->result($db->query("SELECT COUNT(*) FROM {$tablepre}players WHERE type=34 AND name='白凰的符灵幻象' AND hp>0"),0);
		if($count<3) {
			addnpc(34,8,1,0,array('pls'=>$victim['pls'],'pgroup'=>$victim['pgroup'],'horizon'=>$victim['horizon'],'clbpara'=>array('er_sub'=>8,'er_role'=>'illusion','er_summoner'=>(int)$victim['pid'])));
			$log.='燃烧的符纸散作光羽，一只符灵幻象在白凰身边凝聚。<br>';
		}
	}
	if((int)($victim['type'] ?? 0)==19 && ($victim['name'] ?? '')=='红暮') {
		$places=get_safe_plslist(); $places=array_values(array_diff($places,array($victim['pls'])));
		if($places) { $victim['pls']=$places[array_rand($places)]; $victim['erc_retreat']=1; addnews($now,'hongmu_retreat',$victim['name'],$victim['pls']); return true; }
	}
	return false;
}

function erc_mine(&$source,$target,$random_only=false)
{
	global $db,$tablepre,$now;
	$white=erc_has($source,'npc_white_phosphorus');
	$places=(int)($source['pgroup'] ?? 0)==3 ? range(120,129) : get_safe_plslist();
	$location=$source['pls'];
	if($random_only || (!$white && !empty($source['clbpara']['pawn_evolved']))) $location=$places[array_rand($places)];
	$damage=max(500,(int)round($target['mhp']*.12));
	$db->array_insert($tablepre.'maptrap',array('itm'=>$white ? '白磷高热雷' : '银锤的压轴烟花','itmk'=>'TO','itme'=>$damage,'itms'=>1,'itmsk'=>(string)$source['pid'],'pls'=>$location,'itmpara'=>$white ? json_encode(array('ai93_inf'=>'u','phosphorus'=>1)) : ''));
	addnews($now,'yinchui_bomb',$source['name'],$location);
}

function erc_revive(&$data)
{
	global $log;
	if(!severe_enabled() || $data['hp']>0) return false;
	if(erc_has($data,'npc_lifecat') && empty($data['clbpara']['lifecat_used'])) {
		$data['clbpara']['lifecat_used']=1; $data['hp']=1; $data['state']=0;
		$log.='你把最后的一条命攥回了手心。<br>'; severe_revival($data); return true;
	}
	$charge=(int)($data['clbpara']['luck_charge'] ?? 0);
	if((erc_has($data,'npc_luck_charge') || erc_has($data,'npc_lucky_escape')) && $charge>0 && diceroll(99)<$charge) {
		$data['clbpara']['luck_charge']=max(0,$charge-erc_config('luck_consume',60));
		$data['hp']=max(1,(int)round($data['mhp']*.05)); $data['state']=0;
		$log.='幸运替她挡住了致命的一击，积蓄的运气也随之消散。<br>'; severe_revival($data); return true;
	}
	return false;
}

function erc_slot_item($data,$slot)
{
	$i=substr($slot,0,3)=='itm' ? substr($slot,3) : NULL;
	$out=array();
	foreach(array('itm'=>'','itmk'=>'k','itme'=>'e','itms'=>'s','itmsk'=>'sk','itmpara'=>'para') as $key=>$suffix) $out[$key]=$data[$i!==NULL ? $key.$i : $slot.$suffix] ?? ($key=='itme' || $key=='itms' ? 0 : '');
	return $out;
}

function erc_arsenal_swap(&$data,&$enemy)
{
	global $ex_dmg_def;
	if(diceroll(99)<=50) return;
	$defenses=array_merge(\revattr\get_equip_ex_array($enemy),\revattr\get_wep_ex_array($enemy));
	$choices=array(); $best=-999;
	for($i=1;$i<=6;$i++) {
		$item=erc_slot_item($data,'itm'.$i);
		if(empty($item['itms']) || strpos($item['itmk'],'W')!==0) continue;
		$kind=substr($item['itmk'],1,1); $score=in_array($kind,$defenses,true) ? -2 : 1;
		foreach($ex_dmg_def as $key=>$resist) if(strpos($item['itmsk'],$key)!==false) $score+=in_array($resist,$defenses,true) ? -1 : 2;
		if($score>$best) { $best=$score; $choices=array($i); } elseif($score==$best) $choices[]=$i;
	}
	if(!$choices) return;
	$slot='itm'.$choices[array_rand($choices)]; $old=erc_slot_item($data,'wep'); $next=erc_slot_item($data,$slot);
	if(function_exists('reload_single_set_item')) { reload_single_set_item($data,'wep',$data['wep']); reload_single_set_item($data,'wep',$next['itm'],1); }
	erc_write_slot($data,'wep',$next); erc_write_slot($data,$slot,$old);
	$data['weppara']=get_itmpara($data['weppara']);
	\revattr\get_wep_kind($data); $data['wep_range']=\revattr\get_wep_range($data); $data['wep_skill']=\revattr\get_wep_skill($data); $data['wep_name']=$data['wep'];
	$data['change_wep_log']='灵翼调整了弹种，将手中的武器切换为<span class="yellow">'.$data['wep'].'</span>。<br>';
}

function erc_write_slot(&$data,$slot,$item)
{
	$i=substr($slot,0,3)=='itm' ? substr($slot,3) : NULL;
	foreach(array('itm'=>'','itmk'=>'k','itme'=>'e','itms'=>'s','itmsk'=>'sk','itmpara'=>'para') as $key=>$suffix) $data[$i!==NULL ? $key.$i : $slot.$suffix]=$item[$key] ?? ($key=='itme' || $key=='itms' ? 0 : '');
}

function erc_steal(&$thief,&$target)
{
	global $log;
	if(!empty($target['type'])) return;
	$store=$thief['clbpara']['dimension_store'] ?? array();
	$owned=0; foreach($store as $entry) if($entry['owner']==$target['pid']) $owned++;
	if(count($store)>=6 || $owned>=3) return;
	$slots=array();
	foreach(array('wep','arb','arh','ara','arf','art','itm1','itm2','itm3','itm4','itm5','itm6') as $slot) {
		$item=erc_slot_item($target,$slot);
		if(empty($item['itms']) || empty($item['itm'])) continue;
		if($slot=='arb' && (int)($target['race'] ?? 0)==2) continue;
		if(strpos($item['itmk'],'Q')===0 || !empty(get_itmpara($item['itmpara'])['quest'])) continue;
		$slots[]=$slot;
	}
	if(!$slots) return;
	$slot=$slots[array_rand($slots)]; $item=erc_slot_item($target,$slot);
	$store[]=array('owner'=>$target['pid'],'item'=>$item);
	$thief['clbpara']['dimension_store']=$store;
	erc_write_slot($target,$slot,array());
	if($slot=='wep') { $target['wep']='拳头'; $target['wepk']='WN'; $target['weps']=$GLOBALS['nosta']; }
	$target['clbpara']['dimension_mark']=$thief['pid'];
	// 仓库保存原物，战斗内只投影使用；不往尸体背包复制一份可重复拾取的物品。
	if(strpos($item['itmk'],'W')===0) { erc_write_slot($thief,'wep',$item); $thief['clbpara']['dimension_equipped']='wep'; }
	elseif(preg_match('/^D([BHAF])/',$item['itmk'],$m)) { $dest=array('B'=>'arb','H'=>'arh','A'=>'ara','F'=>'arf')[$m[1]]; erc_write_slot($thief,$dest,$item); $thief['clbpara']['dimension_equipped_'.$dest]=1; }
	elseif(preg_match('/^H[HB]/',$item['itmk'])) $thief['hp']=min($thief['mhp'],$thief['hp']+(int)$item['itme']);
	if(function_exists('revattr\\get_wep_kind')) {
		\revattr\get_wep_kind($thief);
		$thief['wep_range']=\revattr\get_wep_range($thief);
		$thief['wep_skill']=\revattr\get_wep_skill($thief);
		$thief['wep_name']=$thief['wep'];
	}
	$log.='她张开手，刚才还在你身上的装备已经落进了维度裂隙。<br>';
}

function erc_return_stolen(&$victim,&$killer)
{
	global $db,$tablepre;
	$store=$victim['clbpara']['dimension_store'] ?? array();
	if(!$store) return;
	$owners=array();
	foreach($store as $entry) {
		$pid=(int)$entry['owner'];
		if($pid==(int)$killer['pid']) $owner=&$killer;
		else { if(!isset($owners[$pid])) $owners[$pid]=fetch_playerdata_by_pid($pid); $owner=&$owners[$pid]; }
		if(!$owner) { unset($owner); continue; }
		$slot=''; if($owner['hp']>0) for($i=1;$i<=6;$i++) if(empty($owner['itms'.$i])) { $slot='itm'.$i; break; }
		if($slot) erc_write_slot($owner,$slot,$entry['item']);
		else { $item=$entry['item']; $item['pls']=(int)$owner['pls']; if(is_array($item['itmpara'])) $item['itmpara']=json_encode($item['itmpara']); $db->array_insert($tablepre.'mapitem',$item); }
		unset($owner);
	}
	foreach($owners as $owner) if($owner) player_save($owner);
	foreach(array('wep','arb','arh','ara','arf') as $slot) if(($victim['clbpara']['dimension_equipped'] ?? '')==$slot || !empty($victim['clbpara']['dimension_equipped_'.$slot])) erc_write_slot($victim,$slot,array());
	unset($victim['clbpara']['dimension_store']);
}

function erc_final_death(&$killer,&$victim)
{
	global $db,$tablepre;
	$victim['clbpara']=severe_para($victim); $killer['clbpara']=severe_para($killer);
	if(!severe_enabled() || !empty($victim['clbpara']['erc_death_done'])) return;
	$victim['clbpara']['erc_death_done']=1;
	if((int)($victim['type'] ?? 0)===34 && ($victim['clbpara']['er_sub'] ?? -1)==8) {
		foreach(array('wep','arb','arh','ara','arf','art','itm1','itm2','itm3','itm4','itm5','itm6') as $slot) erc_write_slot($victim,$slot,array());
		$victim['money']=0;
	}
	if(severe_curse($victim,'revenge')) foreach(array('att','def','wp','wk','wc','wg','wd','wf','wb') as $key) if(isset($killer[$key])) $killer[$key]=max(0,(int)floor($killer[$key]*(1-rand(1,10)/100)));
	if(severe_curse($victim,'legbreak') && empty($killer['type'])) severe_apply($killer,'lower');
	if((int)($victim['type'] ?? 0)==37) {
		$skills=erc_animal_skills(); $skill=$skills[$victim['clbpara']['er_sub'] ?? -1] ?? '';
		if($skill) {
			$result=$db->query("SELECT * FROM {$tablepre}players WHERE type=35 AND hp>0");
			while($boss=$db->fetch_array($result)) { $boss['clbpara']=severe_para($boss); $boss['clbpara']['skill']=array_values(array_diff($boss['clbpara']['skill'] ?? array(),array($skill))); player_save($boss); }
		}
	}
	if(erc_has($victim,'npc_dimension_theft')) erc_return_stolen($victim,$killer);
	if(empty($killer['type']) && erc_has($killer,'npc_dimension_theft') && erc_pay($killer,40,35)) {
		$empty=''; for($i=1;$i<=6;$i++) if(empty($killer['itms'.$i])) { $empty='itm'.$i; break; }
		$slots=array();
		foreach(array('wep','arb','arh','ara','arf','art','itm1','itm2','itm3','itm4','itm5','itm6') as $slot) {
			$item=erc_slot_item($victim,$slot);
			if(!empty($item['itms']) && !empty($item['itm']) && !preg_match('/^(Q|ZB)/',$item['itmk']) && empty(get_itmpara($item['itmpara'])['quest']) && !($slot=='arb' && (int)($victim['race'] ?? 0)==2)) $slots[]=$slot;
		}
		if($empty && $slots) {
			$slot=$slots[array_rand($slots)]; erc_write_slot($killer,$empty,erc_slot_item($victim,$slot)); erc_write_slot($victim,$slot,array());
			$GLOBALS['log'].='你从散落的战利品中顺手收起了一件物品。<br>';
		}
	}
	if(function_exists('ending_routes_npc_dead') && !empty($victim['type'])) ending_routes_npc_dead($victim,$killer);
}

function erc_finish(&$data,&$enemy)
{
	global $now,$db,$tablepre;
	if(!severe_enabled()) return;
	erc_retro_end($data);
	if(!empty($data['type']) && $data['hp']>0 && erc_has($data,'npc_dimension_theft') && diceroll(99)<erc_config('omicron_swap',25)) {
		$group=(int)($data['pgroup'] ?? 0); $marked=array();
		$result=$db->query("SELECT * FROM {$tablepre}players WHERE type=0 AND hp>0 AND pgroup=$group");
		while($person=$db->fetch_array($result)) {
			if((int)$person['pid']==(int)$enemy['pid']) $person=$enemy;
			$p=severe_para($person);
			if(($p['dimension_mark'] ?? 0)==$data['pid'] && $person['hp']>0 && $person['pls']!=$data['pls']) $marked[]=$person;
		}
		if($marked) {
			$person=$marked[array_rand($marked)]; $old=$data['pls']; $data['pls']=$person['pls']; $person['pls']=$old;
			if((int)$person['pid']==(int)$enemy['pid']) $enemy=$person; else player_save($person);
			$GLOBALS['log'].='维度的裂隙一闪而过，她与被标记者交换了位置。<br>';
		}
	}
	if($data['hp']>0 && erc_has($data,'npc_shadow_step') && !empty($data['type'])) {
		$places=(int)($data['pgroup'] ?? 0)==3 ? range(120,129) : get_safe_plslist();
		if($places) $data['pls']=$places[array_rand($places)];
	}
	if($data['hp']>0 && erc_has($data,'npc_steam_guard') && empty($data['type']) && erc_pay($data,30)) $data['hp']=min($data['mhp'],$data['hp']+(int)ceil($data['mhp']*.03));
	if(!empty($data['type']) && erc_has($data,'npc_bonearmor')) $data['inf']='';
	if(!empty($data['type']) && $data['hp']>0 && erc_has($data,'npc_white_phosphorus')) erc_mine($data,$enemy,true);
	erc_cap_sp($data);
	unset($data['clbpara']['shadow_moved']);
	unset($data['erc_lock'],$data['erc_snipe'],$data['erc_selfstrike'],$data['erc_prefetched_by']);
}

function erc_chase($pa,$pd)
{
	return erc_has($pa,'npc_rook_chase') || erc_has($pd,'npc_rook_chase') || erc_has($pa,'npc_hydraulic') || erc_has($pd,'npc_hydraulic');
}

function erc_force_chase(&$pa,&$pd,$active)
{
	global $log;
	if(!erc_chase($pa,$pd) || $pa['hp']<=0 || $pd['hp']<=0 || !empty($pa['erc_retreat']) || !empty($pd['erc_retreat'])) return false;
	$source=erc_has($pa,'npc_rook_chase') || erc_has($pa,'npc_hydraulic') ? 'a' : 'd';
	if($source=='a') $chaser=&$pa; else $chaser=&$pd;
	if(!erc_pay($chaser,40,!empty($chaser['type']) ? 100 : 35)) return false;
	if($active) { $pa['action']=$source=='a' ? 'chase' : 'pchase'; $pa['bid']=$pd['pid']; }
	else { $pd['action']=$source=='d' ? 'chase' : 'pchase'; $pd['bid']=$pa['pid']; }
	$log.='沉重的脚步紧紧追来，对手没有给出脱离的空隙。<br>';
	return true;
}

function erc_hide($viewer,$target,$rate)
{
	if(!severe_enabled()) return $rate;
	if(erc_has($target,'npc_stealth') && ($target['clbpara']['stealth_reveal'] ?? 0)<=($GLOBALS['now'] ?? 0)) $rate=max($rate,!empty($target['type']) ? 75 : 30);
	if(erc_has($target,'npc_fighting_spirit') && isset($viewer['clbpara']['animal_spirit']) && $viewer['clbpara']['animal_spirit']<=0) return 100;
	if(erc_has($viewer,'npc_snow_sight')) $rate-=20;
	return $rate;
}

function erc_active(&$pa,&$pd,$rate)
{
	if(!severe_enabled()) return $rate;
	$rate-=min(70,($pa['clbpara']['animal_debuff'][4] ?? 0)*($pa['clbpara']['foxfire_step'] ?? 8));
	$rate+=min(70,($pd['clbpara']['animal_debuff'][4] ?? 0)*($pd['clbpara']['foxfire_step'] ?? 8));
	if(erc_has($pa,'npc_lockhead')) $rate+=!empty($pa['type']) ? 50 : 15;
	if(erc_has($pd,'npc_lockhead')) $rate-=!empty($pd['type']) ? 50 : 15;
	if(!empty($pa['type']) && erc_has($pa,'npc_hydraulic')) $rate+=20;
	if(!empty($pd['type']) && erc_has($pd,'npc_hydraulic')) $rate-=20;
	if(empty($pa['type']) && erc_has($pa,'npc_prefetch') && empty($pa['clbpara']['prefetch_seen'][$pd['pid']]) && erc_pay($pa,20)) {
		$rate+=10; $pa['clbpara']['prefetch_seen'][$pd['pid']]=1;
		if(count($pa['clbpara']['prefetch_seen'])>32) unset($pa['clbpara']['prefetch_seen'][array_key_first($pa['clbpara']['prefetch_seen'])]);
	}
	if(!empty($pa['clbpara']['shadow_moved'])) $rate+=10;
	if(erc_has($pa,'npc_foxstep')) $rate+=8;
	if(erc_has($pd,'npc_foxstep')) $rate-=8;
	if(erc_has($pa,'npc_electrofox') && erc_pay($pa,20)) $rate+=10;
	if(erc_has($pa,'npc_rook_chase') && (!empty($pa['is_chase']) || !empty($pa['is_dfight']))) $rate+=10;
	if(erc_has($pa,'npc_blood_draw')) $rate+=30*(1-$pd['hp']/max(1,$pd['mhp']));
	if(erc_has($pd,'npc_blood_draw')) $rate-=30*(1-$pa['hp']/max(1,$pa['mhp']));
	return max(4,min(96,$rate));
}

function erc_action_cost($data,$cost,$is_move=false)
{
	if(!severe_enabled()) return $cost;
	$p=severe_para($data);
	$cost*=1+erc_config('endurance_cost',.1)*($p['animal_debuff'][1] ?? 0);
	if($is_move && ($p['animal_debuff'][2] ?? 0)>=2) $cost*=1.5;
	if($is_move && !empty($p['wire'])) $cost*=1+.3*$p['wire'];
	if($is_move && erc_has($data,'npc_groundsheep')) $cost*=.8;
	if($is_move && erc_has($data,'npc_shadow_step')) $cost*=.5;
	return (int)ceil($cost);
}

function erc_can_action($data,$is_move=false)
{
	global $log;
	if(!severe_enabled()) return true;
	$p=severe_para($data);
	if(!$is_move && isset($p['animal_spirit']) && $p['animal_spirit']<=0) { $log.='战意已经耗尽，无法继续探索；先转移地点，寻找恢复的机会。<br>'; return false; }
	if(($p['animal_debuff'][2] ?? 0)>=3) { $log.='三处关节都被锁住了，请先挣脱。<br>'; return false; }
	return true;
}

function erc_action(&$data,$is_move=false)
{
	global $gamevars,$db,$tablepre,$now;
	if(!severe_enabled()) return;
	$data['clbpara']=severe_para($data);
	$p=&$data['clbpara'];
	erc_cap_sp($data);
	if($is_move && erc_has($data,'npc_shadow_step')) $p['shadow_moved']=1;
	if($is_move && empty($data['type']) && erc_has($data,'npc_afterimage')) $p['afterimage']=1;
	if(!empty($p['blood_poison']) && strpos($data['inf'] ?? '','p')===false) unset($p['blood_poison']);
	if(erc_has($data,'npc_cagedbird') && erc_pay($data,10,20)) $data['hp']=min($data['mhp'],$data['hp']+max(1,(int)round($data['mhp']*.01)));
	if(erc_has($data,'npc_toolcare') && erc_pay($data,10,10)) {
		$slots=array(); foreach(array('wep','arb','arh','ara','arf') as $slot) if(!empty($data[$slot.'s']) && $data[$slot.'s']!=$GLOBALS['nosta']) $slots[]=$slot;
		if($slots) $data[$slots[array_rand($slots)].'s']++;
	}
	if(!empty($gamevars['jiaotou_challenge']) || (int)($data['pgroup'] ?? 0)==3) {
		$place=(int)$data['pls']; $group=(int)($data['pgroup'] ?? 0);
		$result=$db->query("SELECT * FROM {$tablepre}players WHERE hp>0 AND pls=$place AND pgroup=$group AND type IN (34,39)");
		while($npc=$db->fetch_array($result)) {
			$npc['clbpara']=severe_para($npc);
			if(erc_has($npc,'npc_interference') || erc_has($npc,'npc_sequence')) erc_interfere($npc,$data);
			if((int)$npc['type']==39 && ($npc['clbpara']['er_sub'] ?? -1)==7 && diceroll(99)<25) {
				$data['clbpara']['blood_poison']=min(4,($data['clbpara']['blood_poison'] ?? 0)+1);
				\revattr\get_inf_rev($data,'p');
				$data['hp']=max(1,$data['hp']-(int)ceil($data['mhp']*.03));
				$GLOBALS['log'].='暗处飞来一支袖箭，血毒顺着伤口蔓延。<br>';
				if($data['clbpara']['blood_poison']>=4) { death('poison',$npc['name'],39,'',$data); return; }
			}
		}
	}
}

function erc_escape_rate($data,$rate)
{
	if(!severe_enabled()) return $rate;
	$p=severe_para($data);
	if(!empty($p['hydraulic_mark'])) return 0;
	if(strpos($data['inf'] ?? '','l')!==false) $rate-=30;
	$rate-=15*($p['wire'] ?? 0);
	if(erc_has($data,'npc_joint_escape')) $rate+=15;
	if(erc_has($data,'npc_lucky_escape')) $rate+=10;
	return max(0,min(100,$rate));
}

function erc_escape(&$data,&$enemy)
{
	global $log,$cmdcdtime,$searchcoldtime;
	if(erc_has($data,'npc_foxstep')) $data['sp']=min($data['msp'],$data['sp']+(int)ceil($data['msp']*.05));
	if(!severe_enabled() || (int)($enemy['type'] ?? 0)!=35) return;
	$cmdcdtime=max((int)($cmdcdtime ?? 0),(int)($searchcoldtime ?? 1000),1000);
	$data['clbpara']=severe_para($data);
	$data['hp']=min($data['mhp'],$data['hp']+(int)ceil($data['mhp']*.25));
	$data['sp']=min($data['msp'],$data['sp']+(int)ceil($data['msp']*.25));
	foreach($data['clbpara']['animal_debuff'] ?? array() as $k=>$v) $data['clbpara']['animal_debuff'][$k]=max(0,$v-1);
	$data['clbpara']['animal_spirit']=min(100,($data['clbpara']['animal_spirit'] ?? 100)+10);
	$log.='你终于退到了安全距离，喘息间恢复了四分之一生命与体力，纠缠的压力也稍稍减轻。<br>';
}

function erc_active_skill(&$data,$skill)
{
	global $log,$now;
	if(!erc_has($data,$skill)) return false;
	if($skill=='npc_talisman') {
		if(($data['clbpara']['talisman_ready'] ?? 0)>$now || !empty($data['clbpara']['talisman_layers']) || !erc_pay($data,400)) { $log.='贴符需要400体力，且旧符已经耗尽并完成冷却。<br>'; return false; }
		$data['clbpara']['talisman_layers']=2; $log.='两层护符在你身边展开。<br>'; return true;
	}
	return false;
}

function erc_struggle(&$data)
{
	global $log;
	if(!severe_enabled()) return false;
	$data['clbpara']=severe_para($data);
	if(empty($data['clbpara']['animal_debuff'][2])) return false;
	$cost=erc_has($data,'npc_joint_escape') ? 20 : 50;
	if($data['sp']<$cost) { $log.='没有足够的体力挣脱关节锁。<br>'; return false; }
	$data['sp']-=$cost; $data['clbpara']['animal_debuff'][2]--;
	$log.='你忍着疼痛解开了一处关节锁。<br>'; return true;
}
