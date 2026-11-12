<?php
if(!defined('IN_GAME')) exit('Access Denied');

function wheelchair_supported()
{
	global $current_ruleset_config;
	return !isset($current_ruleset_config['club_skills']) || $current_ruleset_config['club_skills'] != 0;
}

function wheelchair_has($data,$skill,$active=true)
{
	$para = get_clbpara($data['clbpara'] ?? array());
	return wheelchair_supported() && in_array($skill,$para['skill'] ?? array(),true)
		&& (!$active || empty($para['wheelchair']['paused'][$skill]));
}

function wheelchair_learned($skill,&$para)
{
	global $wheelchair_skills;
	if(isset($wheelchair_skills[$skill]) && $skill !== 'wc_review') {
		// 只保存五个获得标记：技能丢失、暂停和转社团均不能退还本局折扣。
		$para['wheelchair']['learned'][$skill] = 1;
	}
}

function wheelchair_penalty_count($data)
{
	$para = get_clbpara($data['clbpara'] ?? array());
	$skills = array_unique(array_merge($para['skill'] ?? array(),array_keys($para['wheelchair']['learned'] ?? array())));
	// 同一ＡＩ账户不能通过切到未学辅助技能的旧分体绕过本局分流和奖励折算。
	if(function_exists('race_ai_is') && race_ai_is($data) && !empty($data['name'])) {
		foreach(race_ai_family($data) as $body) {
			$body_para=get_clbpara($body['clbpara']);
			$skills=array_merge($skills,$body_para['skill'] ?? array(),array_keys($body_para['wheelchair']['learned'] ?? array()));
		}
		$skills=array_unique($skills);
	}
	return count(array_intersect($skills,array('wc_appraise','wc_prepare','wc_route','wc_hunt','wc_grow')));
}

function wheelchair_credit_gain($amount,$name='', $data=null)
{
	global $pdata,$db,$tablepre;
	$amount = max(0,(int)$amount);
	if($data === null) {
		if(!empty($pdata) && empty($pdata['type']) && (($pdata['name'] ?? '') === $name || (function_exists('race_account_name') && race_account_name($pdata)===$name))) $data = $pdata;
		elseif($name !== '' && isset($db,$tablepre)) {
			$result = $db->query("SELECT name,type,race,clbpara FROM {$tablepre}players WHERE name=X'".bin2hex($name)."' AND type=0 LIMIT 1");
			$data = $db->fetch_array($result);
		}
	}
	return (int)floor($amount / (1 << wheelchair_penalty_count($data ?: array())));
}

function wheelchair_award_credits2($name,$amount)
{
	global $db,$gtablepre,$pdata,$log;
	if(function_exists('race_account_name')) $name=race_account_name($name);
	$gain = wheelchair_credit_gain($amount,$name);
	if($gain) $db->query("UPDATE {$gtablepre}users SET credits2=credits2+$gain WHERE username='".addslashes($name)."'");
	if($gain != $amount && ($pdata['name'] ?? '') === $name) wheelchair_log('切糕','本次奖励 '.$amount.' 切糕，经本局轮椅折扣实际获得 '.$gain.'。');
	return $gain;
}

function wheelchair_log($skill,$message)
{
	global $log;
	$log .= '<span class="lime">【'.$skill.'】'.htmlspecialchars($message,ENT_QUOTES,'UTF-8').'</span><br>';
}

function wheelchair_entry(&$data,$llm=false)
{
	global $wheelchair_skills,$current_ruleset_config;
	$stored = array();
	if(!wheelchair_supported()) return $stored;
	$data['clbpara'] = get_clbpara($data['clbpara']);
	if($llm) {
		foreach($wheelchair_skills as $skill=>$info) getclubskill($skill,$data['clbpara']);
		$data['clbpara']['wheelchair']['entry_notice'] = '已通过 LLM 入场接口学会六项辅助技能。复盘免费，其余五项使本局此后切糕收益为 1/32。导航与追猎需要先选择目标。';
	} elseif((int)$data['nick'] === 9301 && ($current_ruleset_config['title_system'] ?? 2) >= 2) {
		for($i=1;$i<=6;$i++) {
			if(!empty($data['itms'.$i])) {
				$item = wheelchair_item($data,'itm'.$i);
				if(in_array($item['itm'],array('面包','矿泉水'),true)) {
					$item['itme'] = max(180,(int)$item['itme']);
					$item['itms'] = max(20,(int)$item['itms']);
				}
				$item['itmpara'] = get_clbpara($item['itmpara']);
				$item['itmpara']['wheelchair_free_depot'] = 1;
				$stored[] = $item;
			}
		}
		$i=1;
		foreach($wheelchair_skills as $skill=>$info) {
			wheelchair_set_item($data,'itm'.$i++,array('itm'=>'《'.$info[0].'》','itmk'=>'VS','itme'=>1,'itms'=>1,'itmsk'=>'v','itmpara'=>array('wheelchair_skill'=>$skill)));
		}
		$data['clbpara']['wheelchair']['entry_notice'] = '六本灵魂绑定技能书已放入背包。面包、矿泉水及原入场物品已寄存安全箱，首次取出免费；面包和水至少为效果 180、数量 20。到有安全箱的地点，点击「安全箱」→「取出」领取。先使用或丢弃技能书腾出位置；只学复盘不受惩罚。';
	}
	return $stored;
}

function wheelchair_entry_notice(&$data)
{
	if(!empty($data['clbpara']['wheelchair']['entry_notice'])) {
		wheelchair_log('轮椅',$data['clbpara']['wheelchair']['entry_notice']);
		unset($data['clbpara']['wheelchair']['entry_notice']);
		return true;
	}
	return false;
}

function wheelchair_item($data,$slot)
{
	$result = array();
	foreach(array('','k','e','s','sk','para') as $suffix) {
		$key = strpos($slot,'itm')===0 ? 'itm'.$suffix.substr($slot,3) : $slot.$suffix;
		$result['itm'.$suffix] = $data[$key] ?? (($suffix==='e'||$suffix==='s') ? 0 : '');
	}
	return $result;
}

function wheelchair_set_item(&$data,$slot,$item)
{
	foreach(array('','k','e','s','sk','para') as $suffix) {
		$key = strpos($slot,'itm')===0 ? 'itm'.$suffix.substr($slot,3) : $slot.$suffix;
		$data[$key] = $item['itm'.$suffix] ?? (($suffix==='e'||$suffix==='s') ? 0 : '');
	}
}

function wheelchair_equip_slot($kind)
{
	foreach(array('W'=>'wep','DB'=>'arb','DH'=>'arh','DA'=>'ara','DF'=>'arf','A'=>'art','ss'=>'art','XX'=>'art','XY'=>'art') as $prefix=>$slot) {
		if(strpos($kind,$prefix)===0) return $slot;
	}
	return '';
}

function wheelchair_power($data,$full=false)
{
	global $rangeinfo,$skillinfo;
	// 这是供比较的期望强度，不调用战斗结算，也不掷骰或消耗技能。
	$hp = max(0,(float)($data[$full ? 'mhp' : 'hp'] ?? 0));
	if(!$hp) return 0;
	$kind = substr($data['wepk'] ?? 'WN',1,1);
	$unloaded = in_array($kind,array('G','J','B'),true) && ($data['weps'] ?? '')==='∞';
	if($unloaded) $kind=$kind==='B' ? 'K' : 'P';
	$prof = max(0,(float)($data[$skillinfo[$kind] ?? 'wp'] ?? 0));
	$we = !empty($data['weps']) ? max(0,(float)($data['wepe'] ?? 0)) : 0;
	if($kind==='N') $we=$prof*2/3;
	elseif($unloaded) $we*=.2;
	elseif(!in_array($kind,array('G','J'),true)) $we*=2;
	if(is_numeric($data['weps'] ?? '') && $data['weps']<3) $we*=.7;
	$attack = max(1,(float)($data['att'] ?? 0)+$we) * (1+sqrt($prof)/12);
	$attack *= 1+.025*max(0,(float)($rangeinfo[$kind] ?? 0));
	$defence = max(0,(float)($data['def'] ?? 0));
	$attrs = '';
	foreach(array('arb','arh','ara','arf','art') as $slot) if(!empty($data[$slot.'s'])) {
		$defence += max(0,(float)($data[$slot.'e'] ?? 0));
		$attrs .= $data[$slot.'sk'] ?? '';
	}
	$weaponattrs = !empty($data['weps']) && !$unloaded ? ($data['wepsk'] ?? '') : '';
	// 连击、贯穿、必中和属性伤害；防属性、防物理、护盾等给保守加权。
	foreach(array('r'=>.35,'n'=>.25,'N'=>.15,'c'=>.20,'u'=>.12,'i'=>.12,'e'=>.12,'p'=>.10,'d'=>.12,'Z'=>.20) as $key=>$gain) {
		if(in_array($key,get_itmsk_array($weaponattrs),true)) $attack *= 1+$gain;
	}
	$protection = 1;
	foreach(array('a','b','B','z','A','U','I','E','P','D') as $key) if(in_array($key,get_itmsk_array($attrs),true)) $protection += .12;
	$pose = (int)($data['pose'] ?? 0); $tactic = (int)($data['tactic'] ?? 0);
	if($pose===1) { $attack*=1.3; $defence*=1.3; }
	if($pose===3) { $attack*=.8; $defence*=.8; }
	if($pose===4) { $attack*=1.2; $defence*=.6; }
	if($pose===5) { $attack*=.6; $defence*=.6; }
	if($pose===6) $defence*=.5;
	if($tactic===2) $defence*=1.3;
	if($tactic===3) { $attack*=1.1; $defence*=.8; }
	if($tactic===4) { $attack*=.8; $defence*=.8; }
	$status = $full ? 1 : max(.45,1-.07*strlen($data['inf'] ?? ''));
	$para = get_clbpara($data['clbpara'] ?? array());
	// 关键被动的等级也反映在比较中，避免学了技能却显示完全没有成长。
	$skillweights = array('P'=>array('c1_crit','c13_kungfu'),'K'=>array('c2_butcher','c2_master'),'C'=>array('c3_pitchpow','c3_enchant'),'G'=>array('c4_stable','c4_break'),'D'=>array('c5_focus'),'F'=>array('c9_spirit'));
	foreach($skillweights[$kind==='J' ? 'G' : ($kind==='N' ? 'P' : $kind)] ?? array() as $skill) {
		if(in_array($skill,$para['skill'] ?? array(),true)) $attack*=1+.06*min(10,max(0,(int)($para['skillpara'][$skill]['lvl'] ?? 0)));
	}
	$stamina = $full ? 1 : .65+.35*min(1,max(0,($data['sp'] ?? 0)/max(1,$data['msp'] ?? 1)));
	return (int)round(sqrt($attack * $hp * (1+$defence/300) * $protection) * $status * $stamina);
}

function wheelchair_replacement_power($data,$item)
{
	$slot = wheelchair_equip_slot($item['itmk']);
	if(!$slot) return null;
	// pdata 的顶层字段可能被 extract(EXTR_REFS) 引用；普通数组复制仍会保留这些引用。
	$comparison=array();
	foreach($data as $key=>$value) $comparison[$key]=$value;
	wheelchair_set_item($comparison,$slot,$item);
	return wheelchair_power($comparison,true);
}

function wheelchair_item_rating($item)
{
	if(!wheelchair_equip_slot($item['itmk'])) return null;
	$base=array('hp'=>400,'mhp'=>400,'att'=>100,'def'=>100,'wepk'=>'WN','sp'=>400,'msp'=>400,
		'wp'=>100,'wk'=>100,'wc'=>100,'wg'=>100,'wd'=>100,'wf'=>100,'wn'=>100);
	return max(0,wheelchair_replacement_power($base,$item)-wheelchair_power($base,true));
}

function wheelchair_observe($self,$enemy)
{
	global $wheelchair_enemy,$now,$fog,$pdata;
	if(($self['pid'] ?? 0)!==($pdata['pid'] ?? 0) || (!wheelchair_has($self,'wc_appraise') && !wheelchair_has($self,'wc_hunt'))) return;
	// 仅在已经遭遇时计算，界面只提供粗范围，不暴露对手背包或精确属性。
	$power = wheelchair_power($enemy);
	$step = max(20,(int)pow(10,max(1,strlen((string)$power)-2)));
	$wheelchair_enemy = array('low'=>max(0,(int)(floor($power*.7/$step)*$step)), 'high'=>(int)(ceil($power*1.4/$step)*$step));
	if(wheelchair_has($self,'wc_hunt') && !$fog) {
		$GLOBALS['pdata']['clbpara']['wheelchair']['seen'] = array('name'=>$enemy['name'],'type'=>(int)$enemy['type'],'pls'=>(int)$enemy['pls'],'time'=>$now);
	}
}

function wheelchair_review_before($data)
{
	global $log;
	return array('hp'=>$data['hp'],'sp'=>$data['sp'],'money'=>$data['money'],'killnum'=>$data['killnum'],'power'=>wheelchair_power($data,true),'loglen'=>strlen($log));
}

function wheelchair_review_after(&$data,$before,$command)
{
	global $log,$now;
	if(!$before || !wheelchair_has($data,'wc_review',false)) return;
	$delta = array();
	foreach(array('hp'=>'生命','sp'=>'体力','money'=>'金钱','killnum'=>'击杀') as $key=>$label) {
		$d = $data[$key]-$before[$key];
		if($d) $delta[] = $label.($d>0 ? '+' : '').$d;
	}
	$power = wheelchair_power($data,true)-$before['power'];
	if($power) $delta[] = '完好战力'.($power>0 ? '+' : '').$power;
	$details = html_entity_decode(strip_tags(str_replace(array('<br>','<br />'), ' ',substr($log,$before['loglen']))),ENT_QUOTES,'UTF-8');
	// 保留实际日志的简短摘录，不把所有扣血都猜成敌人攻击。
	$details = preg_replace('/\s+/u',' ', $details);
	$details = function_exists('mb_substr') ? mb_substr($details,0,240,'UTF-8') : substr($details,0,720);
	$row = date('H:i:s',$now).' '.(implode('，',$delta) ?: '数值无变化').'。'.$details;
	$data['clbpara']['wheelchair']['review'][] = $row;
	$data['clbpara']['wheelchair']['review'] = array_slice($data['clbpara']['wheelchair']['review'],-12);
	if($command==='search') {
		$stalled = $power<=0 && $data['killnum']==$before['killnum'] && $data['money']<=$before['money'];
		$data['clbpara']['wheelchair']['stall'] = $stalled ? 1+($data['clbpara']['wheelchair']['stall'] ?? 0) : 0;
		if($data['clbpara']['wheelchair']['stall']===12) wheelchair_log('复盘','连续十二次探索没有击杀、金钱或完好战力增长。检查当前目标、素材来源及所在地点；灵子视界探索会消耗生命。');
	}
}

function wheelchair_panel($data)
{
	global $wheelchair_skills,$wheelchair_enemy;
	$owned = array();
	foreach($wheelchair_skills as $key=>$info) if(wheelchair_has($data,$key,false)) $owned[$key]=$info;
	if(!$owned) return '';
	$wc = $data['clbpara']['wheelchair'] ?? array();
	$html = '<div class="wheelchair-panel" style="border-bottom:1px solid #888;padding:6px;line-height:1.5">';
	if(wheelchair_has($data,'wc_appraise')) {
		$html .= '<b class="lime">战力 '.wheelchair_power($data).' / 完好 '.wheelchair_power($data,true).'</b><br>';
		if(!empty($data['itms0'])) {
			$p = wheelchair_replacement_power($data,wheelchair_item($data,'itm0'));
			if($p!==null) $html .= '换装后完好战力 '.$p.'（'.sprintf('%+d',$p-wheelchair_power($data,true)).'）<br>';
		}
		if(!empty($wheelchair_enemy)) $html .= '对手估计战力 '.$wheelchair_enemy['low'].'～'.$wheelchair_enemy['high'].'<br>';
		if($data['hp']<$data['mhp']*.4) $html .= '<b class="red">生命偏低，先补给。</b><br>';
		$html .= '<details><summary>物品评分／换装收益</summary>';
		foreach(array('wep','wep2','arb','arh','ara','arf','art','itm0','itm1','itm2','itm3','itm4','itm5','itm6') as $slot) {
			$item=wheelchair_item($data,$slot); $rating=wheelchair_item_rating($item);
			if(empty($item['itms']) || $rating===null) continue;
			$html .= htmlspecialchars($item['itm'],ENT_QUOTES,'UTF-8').'：'.$rating;
			if(strpos($slot,'itm')===0) $html .= '；换上后 '.wheelchair_replacement_power($data,$item).'（'.sprintf('%+d',wheelchair_replacement_power($data,$item)-wheelchair_power($data,true)).'）';
			$html .= '<br>';
		}
		$html .= '物品分以统一基础属性估算；换装收益按你的角色计算。</details>';
	}
	$n = wheelchair_penalty_count($data);
	$html .= '本局此后切糕：1/'.(1<<$n).'（单笔取整）<br>';
	if($data['hp']>0) {
		if(in_array($data['action'] ?? '',array('enemy','chase','pchase','dfight'),true) && (isset($owned['wc_hunt']) || isset($owned['wc_route']))) $html .= wheelchair_button('wc_hunt_act','按追猎决策');
		foreach($owned as $key=>$info) if($key!=='wc_review') $html .= wheelchair_button('wc_toggle_'.$key,$info[0].(empty($wc['paused'][$key]) ? '：开' : '：停'));
		if(isset($owned['wc_route'])) {
			$html .= '<br>导航目标 <input name="wc_target" id="wc_target" maxlength="100" size="14" value="'.htmlspecialchars($wc['target'] ?? '',ENT_QUOTES,'UTF-8').'">'.wheelchair_button('wc_route_find','查找').wheelchair_button('wc_route_stop','取消');
			if(!empty($wc['recipe'])) {
				include_once GAME_ROOT.'./include/game/wheelchair_route.func.php';
				$plan=wheelchair_route_plan($data); $next=wheelchair_plan_next($plan,$data);
				$html .= '<br>下一步：'.htmlspecialchars(($next['kind']==='blocked' ? $next['reason'] : ($next['kind']==='mix' ? '合成 ' : ($next['kind']==='have' ? '已获得 ' : '寻找 ')).$next['name']),ENT_QUOTES,'UTF-8');
				$html .= '<details><summary>当前配方链</summary>'.htmlspecialchars(implode('、',wheelchair_plan_names($plan)),ENT_QUOTES,'UTF-8').'</details>';
			}
		}
		if(isset($owned['wc_hunt'])) {
			$html .= '<br>追猎 <select name="wc_hunt_kind"><option value="minion">小兵</option><option value="npc">NPC种类</option><option value="player">玩家姓名</option></select><input name="wc_hunt_target" maxlength="60" size="10" placeholder="种类编号／姓名">'.wheelchair_button('wc_hunt_set','设定').wheelchair_button('wc_hunt_stop','取消');
			if(!empty($wc['hunt'])) $html .= '<br>追猎目标：'.htmlspecialchars($wc['hunt']['label'],ENT_QUOTES,'UTF-8');
		}
		if((wheelchair_has($data,'wc_prepare') || wheelchair_has($data,'wc_grow') || (wheelchair_has($data,'wc_route') && !empty($wc['recipe'])) || (wheelchair_has($data,'wc_hunt') && !empty($wc['hunt'])))
			&& (!empty($data['itms0']) || in_array($data['action'] ?? '',array('corpse','pacorpse'),true))) $html .= '<br>'.wheelchair_button('wc_continue','整理并继续探索');
	}
	if(isset($owned['wc_review'])) {
		$html .= '<details><summary>复盘（最近十二步）</summary>';
		foreach($wc['review'] ?? array() as $row) $html .= htmlspecialchars($row,ENT_QUOTES,'UTF-8').'<br>';
		$html .= '</details>';
	}
	return $html.'</div>';
}

function wheelchair_button($command,$label)
{
	return '<button type="button" onclick="wheelchairCmd(\''.htmlspecialchars($command,ENT_QUOTES,'UTF-8').'\')">'.htmlspecialchars($label,ENT_QUOTES,'UTF-8').'</button>';
}
