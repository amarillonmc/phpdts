<?php
if(!defined('IN_GAME')) exit('Access Denied');

function severe_enabled()
{
	return function_exists('ending_routes_enabled') ? ending_routes_enabled() : (!empty($GLOBALS['ending_routes_v2']) && !empty($GLOBALS['club_skills']));
}

function severe_para($data)
{
	return isset($data['clbpara']) ? get_clbpara($data['clbpara']) : array();
}

function severe_has($data,$key)
{
	if(!severe_enabled()) return false;
	$p = severe_para($data);
	return !empty($p['severe_inf'][$key]);
}

function severe_curse($data,$key)
{
	if(!severe_enabled()) return false;
	$p = severe_para($data);
	return !empty($p['curses'][$key]);
}

function severe_apply(&$data,$key)
{
	if(!severe_enabled()) return false;
	$race = (int)($data['race'] ?? 0);
	if($key == 'head' && in_array($race,array(2,5),true)) return false;
	if(in_array($key,array('upper','lower'),true) && $race == 4) return false;
	$data['clbpara'] = severe_para($data);
	unset($data['clbpara']['severe_rest_elapsed']);
	if($key == 'head') {
		// 只登记致死原因，交给原有复活链；不能把永久生命上限写成零。
		$data['clbpara']['severe_head'] = 1;
		$data['gg_flag'] = 61;
		return true;
	}
	$data['clbpara']['severe_inf'][$key] = 1;
	return true;
}

function severe_apply_curse(&$data,$key)
{
	if(!severe_enabled()) return;
	$data['clbpara'] = severe_para($data);
	unset($data['clbpara']['severe_rest_elapsed']);
	$data['clbpara']['curses'][$key] = 1;
	if($key == 'beast') severe_apply($data,'frenzy');
}

function severe_effective($data,$key)
{
	if(!severe_has($data,$key)) return false;
	if($key == 'lower' && !empty($data['arfs']) && strpos($data['arf'] ?? '','义肢') !== false) return false;
	if($key == 'upper' && !empty($data['arbs']) && strpos($data['arb'] ?? '','义肢') !== false) return false;
	return true;
}

function severe_cure(&$data,$keys)
{
	$data['clbpara'] = severe_para($data);
	foreach((array)$keys as $key) {
		if($key == 'frenzy' && severe_curse($data,'beast')) continue;
		if($key == 'bleeding' && !empty($data['clbpara']['bleeding_mhp'])) {
			$data['mhp'] += $data['clbpara']['bleeding_mhp'];
			unset($data['clbpara']['bleeding_mhp']);
		}
		unset($data['clbpara']['severe_inf'][$key]);
	}
}

function severe_cleanse(&$data)
{
	if(!severe_enabled()) return;
	$data['clbpara'] = severe_para($data);
	unset($data['clbpara']['curses'],$data['clbpara']['illluck']);
	severe_cure($data,array('bleeding','rupture','frenzy'));
	if((int)($data['club'] ?? 0) == 8) severe_cure($data,array('upper','lower'));
}

function severe_rest(&$data,$elapsed)
{
	if(!severe_enabled()) return;
	$data['clbpara']=severe_para($data);
	$data['clbpara']['severe_rest_elapsed']=min(3600,($data['clbpara']['severe_rest_elapsed'] ?? 0)+max(0,$elapsed));
	severe_cure($data,array('frenzy'));
	$needed=((int)($data['club'] ?? 0) == 8 ? 60 : 180);
	if(function_exists('talent_rest_required')) $needed=talent_rest_required($data,$needed);
	else $needed*=max(.5,1-(float)($data['clbpara']['talent_bonus']['rest_time'] ?? 0)/100);
	if($data['clbpara']['severe_rest_elapsed'] >= $needed) severe_cleanse($data);
}

function severe_can_horizon($data)
{
	return !severe_has($data,'nightmare');
}

function severe_can_use_item($data,$itmk,$itm='')
{
	global $log;
	if(!severe_enabled()) return true;
	$equip = preg_match('/^(W|D|A)/',$itmk);
	if(severe_effective($data,'upper') && !$equip) {
		$log .= '双臂受到严重损伤，暂时无法使用道具；仍可以穿戴装备。<br>';
		return false;
	}
	if(severe_has($data,'frenzy') && preg_match('/^H[HSB]/',$itmk) && strpos($itm,'镇静') === false) {
		$log .= '狂热让你无法服用回复补给。<br>';
		return false;
	}
	return true;
}

function severe_heal_ratio($data)
{
	if(!severe_enabled()) return 1;
	$p = severe_para($data);
	$r = severe_has($data,'rupture') ? 0.35 : 1;
	if(!empty($p['hydraulic_mark']) || ($p['coagulate_until'] ?? 0) > ($GLOBALS['now'] ?? 0)) $r *= .5;
	if(severe_curse($data,'illluck')) $r *= $p['illluck'] ?? .75;
	return $r;
}

// 校验与实际行动分开：无效指令和刷新不得造成出血或累积报应。
function severe_can_move($data)
{
	global $log;
	if(severe_effective($data,'lower') && $data['sp'] < ceil($data['msp']*.8)) {
		$log .= '双腿受到严重损伤，需要至少八成体力才能移动。<br>';
		return false;
	}
	return true;
}

function severe_action_cost($data,$cost,$is_move=false)
{
	if(!severe_enabled()) return $cost;
	if(severe_has($data,'rupture')) $cost *= 2;
	if($is_move && severe_effective($data,'lower')) $cost = max($cost,ceil($data['msp']*.8));
	return (int)ceil($cost);
}

function severe_action(&$data,$is_move=false)
{
	if(!severe_enabled()) return;
	$data['clbpara'] = severe_para($data);
	unset($data['clbpara']['severe_rest_elapsed']);
	if(severe_has($data,'bleeding')) {
		$lost = (int)($data['clbpara']['bleeding_mhp'] ?? 0);
		$base = $data['mhp']+$lost;
		$reduce = min(max(0,(int)floor($base*.5)-$lost),(int)ceil($base*rand(1,3)/100));
		$data['mhp'] = max(1,$data['mhp']-$reduce);
		$data['clbpara']['bleeding_mhp'] = $lost+$reduce;
		$data['hp'] = max(1,min($data['mhp'],$data['hp']-(int)ceil($base*rand(3,6)/100)));
	}
	if(severe_curse($data,'trouble')) $data['rp'] += 2;
	if(severe_curse($data,'illluck')) $data['clbpara']['illluck'] = rand(60,90)/100;
	if(($data['clbpara']['rook_lock_until'] ?? 0) <= ($GLOBALS['now'] ?? 0)) {
		$data['inf'] = str_replace('l','',$data['inf'] ?? '');
		unset($data['clbpara']['rook_lock_until']);
	}
}

function severe_negative_damage(&$data,$damage)
{
	if(!severe_enabled()) return $damage;
	$data['clbpara'] = severe_para($data);
	$stacks = (int)($data['clbpara']['betrayal_stacks'] ?? 0);
	if($damage > 0 && $stacks > 0) {
		$damage = (int)ceil($damage*(1+.2*min(10,$stacks)));
		unset($data['clbpara']['betrayal_stacks']);
	}
	return $damage;
}

function severe_revival(&$data)
{
	global $log;
	$data['clbpara'] = severe_para($data);
	if(!empty($data['clbpara']['severe_head'])) {
		unset($data['clbpara']['severe_head'],$data['gg_flag']);
		$log .= '<span class="lime">致命的头部损伤被复苏的力量修复，你重新看见了眼前的世界。</span><br>';
	}
}

function severe_visible_status($data)
{
	if(!severe_enabled()) return array();
	$out = array();
	foreach(array('lower'=>'下肢破坏','upper'=>'上肢破坏','frenzy'=>'狂热','nightmare'=>'噩梦') as $k=>$v) if(severe_has($data,$k)) $out[]=$v;
	foreach(array('darkness'=>'黑暗','confusion'=>'迷惑','weakness'=>'虚弱','revenge'=>'复仇','betrayal'=>'背德','legbreak'=>'断腿','beast'=>'野兽','trouble'=>'麻烦','illluck'=>'运灭') as $k=>$v) if(severe_curse($data,$k)) $out[]=$v.'诅咒';
	return $out;
}
