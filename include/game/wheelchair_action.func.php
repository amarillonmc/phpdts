<?php
if(!defined('IN_GAME')) exit('Access Denied');

include_once GAME_ROOT.'./include/game/wheelchair_route.func.php';
include_once GAME_ROOT.'./include/game/search.func.php';

function wheelchair_safe_places($data)
{
	global $plsinfo,$hplsinfo,$arealist,$areanum,$areaadd,$areatime,$now,$hack;
	$places=isset($plsinfo[$data['pls']]) ? $plsinfo : ($hplsinfo[$data['pgroup']] ?? array());
	$result=array();
	foreach($places as $id=>$name) {
		if(!is_numeric($id)) continue;
		$index=array_search($id,$arealist);
		if(!$hack && $index!==false && $index<=$areanum) continue;
		if(!$hack && $areatime-$now<=90 && $index!==false && $index<=$areanum+$areaadd) continue;
		$result[]=(int)$id;
	}
	return $result;
}

function wheelchair_public_deaths()
{
	global $db,$tablepre,$now;
	static $deaths;
	if($deaths!==null) return $deaths;
	$deaths=array(); $chat=array(); $since=(int)$now-120;
	// 广播遗言中已公开死亡地点；新闻中已公开凶手和死者种类。
	$r=$db->query("SELECT time,send,recv FROM {$tablepre}chat WHERE type=3 AND time>=$since ORDER BY cid DESC LIMIT 100");
	while($row=$db->fetch_array($r)) $chat[$row['time'].'|'.$row['send']]=(int)$row['recv'];
	$r=$db->query("SELECT time,news,a,b,c FROM {$tablepre}newsinfo WHERE time>=$since AND news LIKE 'death%' ORDER BY nid DESC LIMIT 100");
	while($row=$db->fetch_array($r)) {
		$victim=explode('|',$row['a']); $victim=end($victim);
		$key=$row['time'].'|'.$victim;
		if(!isset($chat[$key]) || !in_array($row['news'],array('death20','death21','death22','death23','death24','death25','death29','death60'),true)) continue;
		$killer=explode('|',$row['c']); $killer=end($killer);
		$deaths[]=array('time'=>(int)$row['time'],'victim'=>$victim,'type'=>(int)$row['b'],'killer'=>$killer,'pls'=>$chat[$key]);
	}
	return $deaths;
}

function wheelchair_danger_places($data)
{
	$counts=array(); $danger=array();
	foreach(wheelchair_public_deaths() as $death) {
		if(!$death['type'] || $death['killer']===$data['name']) continue;
		$key=$death['killer'].'|'.$death['pls'];
		$counts[$key]=1+($counts[$key] ?? 0);
		if($counts[$key]>=4) $danger[$death['pls']]=true;
	}
	return array_keys($danger);
}

function wheelchair_move_choice(&$data,$places,$skill,$reason)
{
	global $plsinfo,$hplsinfo,$cmdcdtime,$movecoldtime;
	$safe=array_diff(wheelchair_safe_places($data),wheelchair_has($data,'wc_prepare') ? wheelchair_danger_places($data) : array());
	foreach($places as $dest) if($dest!=$data['pls'] && in_array((int)$dest,$safe,true)) {
		$old=$data['pls'];
		move((int)$dest,$data);
		$cmdcdtime=$movecoldtime;
		if($data['pls']!=$old) wheelchair_log($skill,$reason.'，已移动至 '.($plsinfo[$data['pls']] ?? $hplsinfo[$data['pgroup']][$data['pls']] ?? $data['pls']).'。');
		else wheelchair_log($skill,$reason.'，移动未完成，原因见上方记录。');
		return true;
	}
	return false;
}

function wheelchair_protected($data,$item)
{
	return strpos($item['itmsk'],'v')!==false || strpos($item['itmsk'],'V')!==false || !empty(get_clbpara($item['itmpara'])) || wheelchair_material($data,$item);
}

function wheelchair_junk($data,$item)
{
	if(wheelchair_protected($data,$item)) return false;
	$slot=wheelchair_equip_slot($item['itmk']);
	if($slot && strpos($item['itmsk'],'^')===false) return wheelchair_replacement_power($data,$item)<=wheelchair_power($data,true);
	if($item['itmk']==='X' && $item['itmsk']==='' && !empty($data['clbpara']['wheelchair']['recipe'])) return true;
	// 未知用途的任务物、书、强化道具不当作垃圾自动销毁。
	return strpos($item['itmk'],'P')===0;
}

function wheelchair_free_slot(&$data)
{
	for($i=1;$i<=6;$i++) if(empty($data['itms'.$i])) return true;
	for($i=1;$i<=6;$i++) {
		$item=wheelchair_item($data,'itm'.$i);
		if(wheelchair_junk($data,$item)) {
			itemdrop('itm'.$i,$data);
			wheelchair_log('整备','背包已满，丢下无战力提升或不在目标配方中的 '.$item['itm'].'；仍按普通丢弃规则保留视野。');
			return empty($data['itms'.$i]);
		}
	}
	return false;
}

function wheelchair_should_equip($data,$item)
{
	if(empty($item['itms']) || !wheelchair_equip_slot($item['itmk']) || in_array($item['itmk'],array('XX','XY'),true)
		|| strpbrk($item['itmsk'],'vV^')!==false || !empty(get_clbpara($item['itmpara']))) return false;
	if(wheelchair_material($data,$item)) {
		// 中间产物可以用于发育；材料齐全准备合成时，不把刚卸下的素材重新装备。
		$next=wheelchair_plan_next(wheelchair_route_plan($data),$data);
		if($next['kind']==='mix') return false;
	}
	return wheelchair_replacement_power($data,$item)>wheelchair_power($data,true)*1.03;
}

function wheelchair_handle_item(&$data)
{
	global $cmd,$mode,$coldtimeon;
	if(empty($data['itms0'])) return true;
	$item=wheelchair_item($data,'itm0');
	$prepare=wheelchair_has($data,'wc_prepare');
	if($prepare && wheelchair_should_equip($data,$item)) {
		$before=wheelchair_power($data,true);
		itemuse(0,$data);
		$after=wheelchair_power($data,true);
		if($after>$before) wheelchair_log('整备','换装 '.$item['itm'].'，完好战力 '.$before.' → '.$after.'。');
		return empty($data['itms0']);
	}
	if($prepare && wheelchair_junk($data,$item)) {
		itemdrop('itm0',$data);
		wheelchair_log('整备','丢下无提升或不宜食用的 '.$item['itm'].'，可从视野中重新拾取。');
		return empty($data['itms0']);
	}
	// 相同补给按普通合并操作处理，不能将有毒／不同 para 的补给混入。
	if($prepare && in_array($item['itmk'],array('HH','HS','HB'),true)) for($i=1;$i<=6;$i++) {
		$other=wheelchair_item($data,'itm'.$i);
		if($item['itm']===$other['itm'] && $item['itmk']===$other['itmk'] && $item['itme']==$other['itme'] && $item['itmsk']===$other['itmsk'] && $item['itmpara']==$other['itmpara']) {
			itemmerge(0,$i);
			if(empty($data['itms0'])) { $cmd=''; $mode='command'; wheelchair_log('整备','合并同名同效果补给 '.$item['itm'].'。'); return true; }
		}
	}
	if(($prepare || wheelchair_material($data,$item)) && wheelchair_free_slot($data)) {
		itemget($data);
		if(!empty($data['itms0'])) itemadd($data);
		if(empty($data['itms0'])) { $cmd=''; $mode='command'; wheelchair_log('收拾','将 '.$item['itm'].' 放入背包，保留补给、特殊物品及目标素材。'); return true; }
	}
	return false;
}

function wheelchair_prepare(&$data)
{
	global $coldtimeon,$opendialog;
	$changed=false;
	// 最多六次补给，避免一条请求无界消耗；冷却模式每次只代用一件。
	for($step=0;$step<6;$step++) {
		$lowhp=$data['hp']<$data['mhp']*.7; $lowsp=$data['sp']<$data['msp']*.35;
		if(!$lowhp && !$lowsp) break;
		$use=-1; $best=INF;
		for($i=0;$i<=6;$i++) {
			$item=wheelchair_item($data,'itm'.$i);
			if(empty($item['itms']) || wheelchair_material($data,$item)) continue;
			if(($lowhp && in_array($item['itmk'],array('HH','HB'),true)) || (!$lowhp && $lowsp && in_array($item['itmk'],array('HS','HB'),true))) {
				if($item['itme']>0 && $item['itme']<$best) { $best=$item['itme']; $use=$i; }
			}
		}
		$hp=$data['hp']; $sp=$data['sp'];
		if($use>=0) {
			$name=$data['itm'.$use]; itemuse($use,$data);
			wheelchair_log('整备','因'.($lowhp ? '生命' : '体力').'不足使用 '.$name.'；生命 '.$hp.' → '.$data['hp'].'，体力 '.$sp.' → '.$data['sp'].'。');
		} elseif($data['skillpoint']>0 && in_array('f_heal',$data['clbpara']['skill'] ?? array(),true)) {
			upgclbskills('f_heal');
			if(($opendialog ?? '')==='skillpage') unset($opendialog);
			wheelchair_log('整备','补给不足，使用自愈；生命 '.$hp.' → '.$data['hp'].'，体力 '.$sp.' → '.$data['sp'].'。');
		} else break;
		if($hp==$data['hp'] && $sp==$data['sp']) break;
		$changed=true;
		if($coldtimeon) return true;
	}
	for($i=1;$i<=6;$i++) {
		$item=wheelchair_item($data,'itm'.$i);
		if(wheelchair_should_equip($data,$item)) {
			$before=wheelchair_power($data,true); itemuse($i,$data);
			if(wheelchair_power($data,true)>$before) { wheelchair_log('整备','换用 '.$item['itm'].'，完好战力 '.$before.' → '.wheelchair_power($data,true).'。'); $changed=true; }
			if($coldtimeon && $changed) return true;
		}
	}
	return $changed;
}

function wheelchair_grow(&$data)
{
	global $cskills,$opendialog,$skillinfo;
	$priorities=array(1=>array('c1_crit'),2=>array('c2_butcher','c2_master'),3=>array('c3_pitchpow','c3_enchant'),4=>array('c4_stable','c4_break'),5=>array('c5_focus','c5_caution'),6=>array('c6_godluck'),7=>array('c7_shield'),8=>array('c8_expert'),9=>array('c9_spirit'),10=>array('c10_inspire'),11=>array('c11_stock'),12=>array('c12_huge'),13=>array('c13_kungfu'),19=>array('c19_purity'),20=>array('c20_fertile'),21=>array('c21_stormedge'));
	$choices=$priorities[(int)$data['club']] ?? array();
	$choices[]=$data['mhp']<600 ? 's_hp' : 's_ad';
	foreach($choices as $skill) {
		if(!isset($cskills[$skill]['cost']) || !in_array($skill,$data['clbpara']['skill'] ?? array(),true) || check_skill_unlock($skill,$data)) continue;
		$level=(int)($data['clbpara']['skillpara'][$skill]['lvl'] ?? 0);
		if(isset($cskills[$skill]['maxlvl']) && $level>=$cskills[$skill]['maxlvl']) continue;
		$cost=is_array($cskills[$skill]['cost']) ? ($cskills[$skill]['cost'][$level] ?? -1) : $cskills[$skill]['cost'];
		if($cost<1 || $data['skillpoint']<$cost+2) continue;
		$before=$data['skillpoint']; upgclbskills($skill);
		if(($opendialog ?? '')==='skillpage') unset($opendialog);
		if($data['skillpoint']<$before) { wheelchair_log('精进','按当前社团学习 '.$cskills[$skill]['name'].'，消耗 '.($before-$data['skillpoint']).' 点，至少留下两点用于自愈。'); return true; }
	}
	if(!wheelchair_at_shop($data) || !empty($data['itms0'])) return false;
	$reserve=max(200,(int)($data['clbpara']['wheelchair']['funding'] ?? 0));
	if(!empty($data['clbpara']['wheelchair']['recipe'])) {
		// 导航期间不抢购成长物品，全部余款留给目标链。
		return false;
	}
	$kind=strtoupper(substr($skillinfo[substr($data['wepk'],1,1)] ?? 'wp',1));
	foreach(wheelchair_shop_items() as $row) {
		$price=wheelchair_price($data,$row);
		if($price<1 || $data['money']<$price+$reserve) continue;
		$book=$row['itmk']==='V'.$kind && ($data['w'.strtolower($kind)] ?? 0)<150;
		$item=array('itm'=>$row['item'],'itmk'=>$row['itmk'],'itme'=>$row['itme'],'itms'=>$row['itms'],'itmsk'=>$row['itmsk'],'itmpara'=>$row['itmpara']);
		$score=wheelchair_replacement_power($data,$item);
		$equip=$score!==null && $score>wheelchair_power($data,true)*1.15 && !wheelchair_protected($data,$item);
		if(!$book && !$equip) continue;
		if(!wheelchair_free_slot($data) || !wheelchair_buy($data,$row,$book ? '当前武器熟练度不足 150' : '装备可明显提升完好战力')) return false;
		for($i=0;$i<=6;$i++) if(($data['itm'.$i] ?? '')===$row['item']) { itemuse($i,$data); break; }
		return true;
	}
	return false;
}

function wheelchair_hunt_match($enemy,$hunt)
{
	if(!$hunt) return false;
	if($hunt['kind']==='player') return empty($enemy['type']) && $enemy['name']===$hunt['target'];
	return (int)$enemy['type']===(int)$hunt['target'];
}

function wheelchair_hunt_step(&$data,$hunt)
{
	global $now,$apose,$poseinfo;
	$data['clbpara']['wheelchair']['working_hunt']=$hunt;
	if(in_array((int)$data['pose'],array(3,5),true) && in_array(1,$apose)) {
		$data['pose']=1;
		wheelchair_log('追猎','开始寻找战斗目标，将探索姿态切换为'.$poseinfo[1].'，减少遭遇战中的劣势。');
	}
	$seen=$data['clbpara']['wheelchair']['seen'] ?? array();
	if($seen && $now-$seen['time']<=120 && wheelchair_hunt_match($seen,$hunt) && $seen['pls']!=$data['pls']) {
		if(wheelchair_move_choice($data,array($seen['pls']),'追猎','前往最近两分钟内见过目标的地点')) return true;
	}
	if($hunt['kind']==='player') foreach(wheelchair_public_deaths() as $death) if($death['killer']===$hunt['target']) {
		if(wheelchair_move_choice($data,array($death['pls']),'追猎','公开死亡消息显示目标最近在此战斗')) return true;
		break;
	}
	foreach($data['clbpara']['smeo'] ?? array() as $key=>$memory) if($memory[1]==='enemy') {
		// 只读取当前视野里已有的对象，重新遇敌仍走原有先制率判定。
		$enemy=fetch_playerdata_by_pid((int)$memory[0]);
		if($enemy && $enemy['pls']==$data['pls'] && $enemy['hp']>0 && wheelchair_hunt_match($enemy,$hunt)) {
			$data['action']='enemy'; $data['bid']=$enemy['pid'];
			lost_searchmemory($key,$data);
			\revbattle\revbattle_prepare('focus');
			wheelchair_log('追猎','再次关注视野中的目标，按普通重遇规则判定先手。');
			return true;
		}
	}
	$n=1+($data['clbpara']['wheelchair']['hunt_tries'] ?? 0);
	$data['clbpara']['wheelchair']['hunt_tries']=$n;
	if($n%10===0) {
		$places=wheelchair_safe_places($data); sort($places);
		$offset=array_search((int)$data['pls'],$places,true);
		if($offset!==false) $places=array_merge(array_slice($places,$offset+1),array_slice($places,0,$offset));
		return wheelchair_move_choice($data,$places,'追猎','连续探索未遇到合适目标，换一个可进入的地点');
	}
	return false;
}

function wheelchair_after_explore(&$data)
{
	global $fog,$coldtimeon,$cmd,$main,$mode;
	$hunt=$data['clbpara']['wheelchair']['working_hunt'] ?? array();
	if(!$hunt || $fog || $coldtimeon || $data['hp']<=0 || ($data['action'] ?? '')!=='enemy' || empty($data['bid'])) return;
	$enemy=fetch_playerdata_by_pid((int)$data['bid']);
	if(!$enemy || $enemy['pls']!=$data['pls'] || !wheelchair_hunt_match($enemy,$hunt)) return;
	$data['clbpara']['wheelchair']['hunt_tries']=0;
	if(!empty($enemy['teamID']) && $enemy['teamID']==$data['teamID']) return;
	$margin=empty($enemy['type']) ? 1.6 : 1.35;
	if($data['hp']<$data['mhp']*.6 || wheelchair_power($data)<wheelchair_power($enemy)*$margin) {
		wheelchair_log('追猎','遇到目标，但当前生命或战力优势不足；保留遭遇界面，请自行选择战斗或逃跑。'); return;
	}
	$kind=substr($data['wepk'],1,1);
	if(in_array($kind,array('G','J'),true) && $data['weps']==='∞') $kind='P';
	$cmd=''; $main='';
	wheelchair_log('追猎','已遭遇所选目标且战力占优，尝试一次普通攻击；伤害、反击和追击均按原规则结算。');
	\revbattle\revbattle_prepare($kind);
}

function wheelchair_battle_command(&$data)
{
	global $fog;
	$hunt=wheelchair_has($data,'wc_hunt') ? ($data['clbpara']['wheelchair']['hunt'] ?? array()) : array();
	if(wheelchair_has($data,'wc_route') && !empty($data['clbpara']['wheelchair']['recipe'])) $hunt=$data['clbpara']['wheelchair']['working_hunt'] ?? $hunt;
	if($fog || !$hunt || !in_array($data['action'] ?? '',array('enemy','chase','pchase','dfight'),true) || empty($data['bid'])) {
		wheelchair_log('追猎','当前没有可按追猎处理的遭遇，请使用普通战斗指令。');
		if(!empty($data['bid'])) \revbattle\revbattle_prepare('chase');
		return;
	}
	$enemy=fetch_playerdata_by_pid((int)$data['bid']);
	$margin=!empty($enemy['type']) ? 1.35 : 1.6;
	if(!$enemy || !wheelchair_hunt_match($enemy,$hunt) || $data['hp']<$data['mhp']*.6 || wheelchair_power($data)<wheelchair_power($enemy)*$margin) {
		wheelchair_log('追猎','目标不符、生命偏低或优势不足，按普通规则尝试撤离。');
		\revbattle\revbattle_prepare('back'); return;
	}
	$kind=substr($data['wepk'],1,1);
	if(in_array($kind,array('G','J'),true) && $data['weps']==='∞') $kind='P';
	wheelchair_log('追猎','目标仍在场且战力占优，继续一次普通攻击。');
	\revbattle\revbattle_prepare($kind);
}

function wheelchair_search(&$data)
{
	global $mode,$cmd,$main,$coldtimeon,$cmdcdtime,$searchcoldtime,$itemusecoldtime;
	$prepare=wheelchair_has($data,'wc_prepare');
	$route=wheelchair_has($data,'wc_route') && !empty($data['clbpara']['wheelchair']['recipe']);
	$hunt=wheelchair_has($data,'wc_hunt') ? ($data['clbpara']['wheelchair']['hunt'] ?? array()) : array();
	$grow=wheelchair_has($data,'wc_grow');
	if(!$prepare && !$route && !$hunt && !$grow) return false;
	unset($data['clbpara']['wheelchair']['working_hunt']);
	if(in_array($data['action'] ?? '',array('corpse','pacorpse'),true)) {
		$corpse=fetch_playerdata_by_pid((int)$data['bid']);
		if($corpse && $corpse['hp']<=0 && $corpse['pls']==$data['pls']) {
			if($corpse['money']>0) {
				getcorpse('money',$data);
				check_add_searchmemory($corpse['pid'],'corpse',$corpse['name'],$data);
				wheelchair_log('收拾','领取当前尸体上的金钱，并保留尸体视野。'); return true;
			}
			if($route) foreach(array('wep','wep2','arb','arh','ara','arf','art','itm1','itm2','itm3','itm4','itm5','itm6') as $slot) {
				$item=wheelchair_item($corpse,$slot);
				if(!empty($item['itms']) && wheelchair_material($data,$item) && empty($data['itms0'])) { getcorpse($slot,$data); wheelchair_log('导航','尝试从当前尸体拾取素材 '.$item['itm'].'。'); return true; }
			}
		}
		getcorpse('back',$data);
	}
	if(!empty($data['action'])) return false;
	if($route && empty($data['itms0'])) {
		// 领取钱后原生流程会退出尸体页；只回看仍在视野内且确实有目标素材的尸体。
		foreach($data['clbpara']['smeo'] ?? array() as $key=>$memory) if($memory[1]==='corpse') {
			$corpse=fetch_playerdata_by_pid((int)$memory[0]);
			if(!$corpse || $corpse['hp']>0 || $corpse['pls']!=$data['pls']) continue;
			foreach(array('wep','wep2','arb','arh','ara','arf','art','itm1','itm2','itm3','itm4','itm5','itm6') as $slot) {
				$item=wheelchair_item($corpse,$slot);
				if(!empty($item['itms']) && wheelchair_material($data,$item) && strpos($item['itmsk'],'v')===false) {
					$data['action']='corpse'; $data['bid']=$corpse['pid'];
					getcorpse($slot,$data);
					wheelchair_log('导航','从仍在视野内的尸体拾取素材 '.$item['itm'].'。'); return true;
				}
			}
		}
	}
	$cmdcdtime=$itemusecoldtime;
	if($prepare && wheelchair_prepare($data) && $coldtimeon) return true;
	if(!empty($data['itms0'])) {
		if($route) {
			// 手持槽也能参加原生合成；先检查，避免两个同名素材入包时自动合并成一格。
			$next=wheelchair_plan_next(wheelchair_route_plan($data),$data);
			if($next['kind']==='mix') {
				$slots=array_column($next['children'],'slot');
				if(in_array('itm0',$slots,true) && count(preg_grep('/^itm[0-6]$/',$slots))===count($slots) && wheelchair_route_step($data)) return true;
			}
		}
		wheelchair_handle_item($data);
		if(!empty($data['itms0'])) {
			$held_key=sha1(json_encode(wheelchair_item($data,'itm0')));
			if(($data['clbpara']['wheelchair']['held_warning'] ?? '')!==$held_key) {
				$data['clbpara']['wheelchair']['held_warning']=$held_key;
				wheelchair_log('整备','手持物品无法安全收纳。可先手动整理；再次点探索会丢弃手持物品后继续，灵魂绑定物品将销毁。'); return true;
			}
			itemdrop('itm0',$data);
			if(!empty($data['itms0'])) { wheelchair_log('整备','这件物品受原本的不可丢弃规则限制，仍需手动处理。'); return true; }
			wheelchair_log('整备','再次探索，已按提示放下手持物品。');
		}
		unset($data['clbpara']['wheelchair']['held_warning']);
		if($coldtimeon) return true;
	}
	if($prepare && ($data['hp']<$data['mhp']*.4 || $data['sp']<20)) {
		if(empty($data['clbpara']['wheelchair']['health_warning'])) {
			$data['clbpara']['wheelchair']['health_warning']=1;
			wheelchair_log('整备','生命或体力仍偏低，且准备尚未完成。建议补给或休息；再次点探索仍会执行。灵子视界探索优先消耗生命。'); return true;
		}
	} else unset($data['clbpara']['wheelchair']['health_warning']);
	$cmd=''; $main=''; $mode='command';
	if($prepare) {
		$safe=wheelchair_safe_places($data); $danger=wheelchair_danger_places($data);
		if(!in_array((int)$data['pls'],$safe,true) || in_array((int)$data['pls'],$danger,true)) {
			if(wheelchair_move_choice($data,$safe,'整备','禁区预告或近期密集NPC死亡消息提示此地危险')) return true;
		}
	}
	if($grow && wheelchair_grow($data)) return true;
	if($route && wheelchair_route_step($data)) { wheelchair_after_explore($data); return true; }
	if(!$route && $hunt && wheelchair_hunt_step($data,$hunt)) { wheelchair_after_explore($data); return true; }
	search($data); $cmdcdtime=$searchcoldtime;
	wheelchair_after_explore($data);
	return true;
}

function wheelchair_command(&$data,$command,$post)
{
	global $typeinfo,$mode,$wheelchair_skills;
	if($command==='search' || $command==='wc_continue') return wheelchair_search($data);
	if(strpos($command,'wc_')!==0) return false;
	if(strpos($command,'wc_toggle_')===0) {
		$skill=substr($command,10);
		if($skill==='wc_review' || !isset($wheelchair_skills[$skill]) || !wheelchair_has($data,$skill,false)) { wheelchair_log('轮椅','尚未学会该辅助技能。'); return true; }
		$data['clbpara']['wheelchair']['paused'][$skill]=empty($data['clbpara']['wheelchair']['paused'][$skill]) ? 1 : 0;
		wheelchair_log($wheelchair_skills[$skill][0],empty($data['clbpara']['wheelchair']['paused'][$skill]) ? '辅助已开启。' : '辅助已暂停；本局已经生效的切糕折扣不变。');
	} elseif(strpos($command,'wc_route_')===0 && wheelchair_has($data,'wc_route',false)) {
		if($command==='wc_route_find') wheelchair_route_find($data,$post['wc_target'] ?? '');
		elseif(strpos($command,'wc_route_pick_')===0) wheelchair_route_pick($data,substr($command,14));
		elseif($command==='wc_route_stop') { unset($data['clbpara']['wheelchair']['recipe'],$data['clbpara']['wheelchair']['funding']); wheelchair_log('导航','已取消目标。'); }
	} elseif(strpos($command,'wc_hunt_')===0 && wheelchair_has($data,'wc_hunt',false)) {
		if($command==='wc_hunt_stop') { unset($data['clbpara']['wheelchair']['hunt']); wheelchair_log('追猎','已取消目标。'); }
		elseif($command==='wc_hunt_set') {
			$kind=$post['wc_hunt_kind'] ?? ''; $target=$post['wc_hunt_target'] ?? '';
			if(!is_string($kind) || !is_string($target) || strlen($target)>180) { wheelchair_log('追猎','目标格式不正确。'); return true; }
			$target=trim($target); $label='';
			if($kind==='minion') { $target='90'; $label='小兵'; }
			elseif($kind==='npc' && ctype_digit($target) && (int)$target>0 && isset($typeinfo[(int)$target])) $label=$typeinfo[(int)$target];
			elseif($kind==='player' && $target!=='' && $target!==$data['name']) $label=$target;
			if($label==='') { wheelchair_log('追猎','请选择小兵、有效NPC种类编号，或其他玩家的完整姓名。'); return true; }
			$data['clbpara']['wheelchair']['hunt']=array('kind'=>$kind,'target'=>$target,'label'=>$label);
			unset($data['clbpara']['wheelchair']['paused']['wc_hunt']);
			$data['clbpara']['wheelchair']['hunt_tries']=0;
			wheelchair_log('追猎','目标设为 '.$label.'。继续探索会依据公开线索寻找目标。');
		}
	} else wheelchair_log('轮椅','尚未学会对应技能，或指令无效。');
	$mode='command';
	return true;
}
