<?php
if(!defined('IN_GAME')) exit('Access Denied');

function club25_config()
{
	return include config('club25cfg',1);
}

function club25_slot_item($data,$slot)
{
	$prefix = $slot === 'wep' ? 'wep' : 'itm'.$slot;
	$row = Array();
	foreach(Array('itm'=>'','itmk'=>'k','itme'=>'e','itms'=>'s','itmsk'=>'sk','itmpara'=>'para') as $key=>$suffix)
		$row[$key] = $data[$slot === 'wep' ? $prefix.$suffix : $key.$slot] ?? '';
	return $row;
}

function club25_consume_slot(&$data,$slot,$amount)
{
	global $nosta;
	$key = $slot === 'wep' ? 'weps' : 'itms'.$slot;
	if($data[$key] !== $nosta && $data[$key] > $amount) { $data[$key] -= $amount; return; }
	foreach(Array('itm'=>'','itmk'=>'k','itme'=>'e','itms'=>'s','itmsk'=>'sk','itmpara'=>'para') as $field=>$suffix)
		$data[$slot === 'wep' ? 'wep'.$suffix : $field.$slot] = in_array($field,Array('itme','itms')) ? 0 : '';
	if($slot === 'wep') reload_equip_items($data);
}

function club25_owned($item,$pid)
{
	$para = get_itmpara($item['itmpara'] ?? '');
	// 撤回归最后一次布场的玩家；下毒和机关制作者仍用于原有伤害结算。
	if(isset($para['c25_ground_owner'])) return (int)$para['c25_ground_owner'] === (int)$pid;
	foreach(Array('c25_mine','c25_hide','c25_trip') as $key)
		if(isset($para[$key]['owner']) && (int)$para[$key]['owner'] === (int)$pid) return true;
	return preg_match('/^(TO|P)/',$item['itmk']) && is_numeric($item['itmsk']) && (int)$item['itmsk'] === (int)$pid;
}

function club25_food($item)
{
	$para = get_itmpara($item['itmpara'] ?? '');
	return preg_match('/^P[HSB][12]?$/',$item['itmk']) || (in_array($item['itmk'],Array('HH','HS','HB')) && !empty($para['c25_hide']));
}

// 只有常规数值雷进入复线；特殊死法雷保留旧规则。
function club25_place_trap(&$data,$item)
{
	global $db,$tablepre,$log;
	$cfg = club25_config();
	$para = get_itmpara($item['itmpara'] ?? '');
	$item['itmk'] = str_replace('TN','TO',$item['itmk']);
	$item['itms'] = 1;
	$item['itmsk'] = (int)$data['pid'];
	$item['pls'] = (int)$data['pls'];
	$copies = 0;
	if($item['itmk'] === 'TO') {
		$old = $para['c25_mine'] ?? Array();
		$para['c25_mine'] = Array('owner'=>(int)$data['pid'],
			'bonus'=>isset($old['bonus']) ? (int)$old['bonus'] : max(0,(int)floor($data['wd']*$cfg['trap_wd_rate'])),
			'copy'=>!empty($old['copy']) ? 1 : 0,'pgroup'=>(int)$data['pgroup']);
		// 原雷回收再布也不能再次增殖。
		if(empty($old['spread']) && empty($old['copy'])) $copies = $cfg['copy_count'];
		$para['c25_mine']['spread'] = 1;
	}
	$item['itmpara'] = json_encode($para,JSON_UNESCAPED_UNICODE);
	$db->array_insert($tablepre.'maptrap',$item);
	for($i=0;$i<$copies;$i++) {
		$child = $item;
		$child['itm'] = '可怜猫的小阔剑';
		$child['itme'] = max(1,(int)floor($item['itme']*$cfg['copy_rate']));
		$para['c25_mine']['copy'] = 1;
		$para['c25_mine']['bonus'] = (int)floor(get_itmpara($item['itmpara'])['c25_mine']['bonus']*$cfg['copy_rate']);
		$child['itmpara'] = json_encode($para,JSON_UNESCAPED_UNICODE);
		$db->array_insert($tablepre.'maptrap',$child);
	}
	$data['clbpara']['c25_catstep'] = 1;
	$log .= '你把'.htmlspecialchars($item['itm'],ENT_QUOTES,'UTF-8').'藏好了。'.($copies ? '旁边又探出'.$copies.'只小可怜猫。' : '').'下一步可以踮着脚溜走。<br>';
	if(function_exists('ai93_trigger')) ai93_trigger(3,$data);
}

function club25_deploy(&$data,$slot,$count=1)
{
	global $db,$tablepre,$nosta,$log;
	if(!preg_match('/^[0-6]$/',(string)$slot) || empty($data['itms'.$slot])) return false;
	$item = club25_slot_item($data,$slot);
	$para = get_itmpara($item['itmpara']);
	if(in_array('v',get_itmsk_array($item['itmsk'])) || in_array('V',get_itmsk_array($item['itmsk']))) {
		$log .= '它黏住你了，不能拿去布场。<br>'; return false;
	}
	$count = max(1,min(3,(int)$count));
	if(strpos($item['itmk'],'TN') === 0) {
		if($item['itms'] === $nosta) $count = 1;
		else $count = min($count,(int)$item['itms']);
		for($i=0;$i<$count;$i++) club25_place_trap($data,$item);
		if($item['itms'] !== $nosta) club25_consume_slot($data,$slot,$count);
	} else {
		if(!club25_food($item) && empty($para['c25_trip'])) { $log .= '先藏好幻影或装上诡雷，再把礼物留下吧。<br>'; return false; }
		// 一堆只藏一次机关，投放时整堆搬运，不拆出多个同样的幻影。
		$para['c25_ground_group'] = (int)$data['pgroup'];
		$para['c25_ground_owner'] = (int)$data['pid'];
		$item['itmpara'] = json_encode($para,JSON_UNESCAPED_UNICODE);
		$item['pls'] = (int)$data['pls'];
		$db->array_insert($tablepre.'mapitem',$item);
		club25_consume_slot($data,$slot,$item['itms'] === $nosta ? 1 : $item['itms']);
		$data['clbpara']['c25_catstep'] = 1;
		$log .= '你留下了'.htmlspecialchars($item['itm'],ENT_QUOTES,'UTF-8').'，摆出一副「我什么都没做哦」的表情。<br>';
	}
	return true;
}

function club25_board($data)
{
	global $db,$tablepre,$plsinfo,$hplsinfo;
	if(($data['club'] ?? 0) != 25) return Array();
	$rows = Array();
	foreach(Array('trap'=>'maptrap','item'=>'mapitem') as $source=>$table) {
		$result = $db->query("SELECT * FROM {$tablepre}{$table} WHERE itms != '0'");
		while($item = $db->fetch_array($result)) {
			$para = get_itmpara($item['itmpara'] ?? '');
			$owned = club25_owned($item,$data['pid']);
			$group = $para['c25_mine']['pgroup'] ?? ($para['c25_ground_group'] ?? null);
			$local = (int)$item['pls'] === (int)$data['pls'] && ($group === null || (int)$group === (int)$data['pgroup']);
			$food = $source === 'item' && club25_food($item);
			$net = $local && (($source === 'trap' && $item['itmk'] === 'TO') || $food);
			if(!$owned && !$net) continue;
			$labels = Array();
			if($source === 'trap') $labels[] = !empty($para['c25_mine']['copy']) ? '副雷' : '地雷';
			if(strpos($item['itmk'],'P') === 0) $labels[] = '有毒补给';
			if(!empty($para['c25_hide'])) $labels[] = '捉迷藏';
			if(!empty($para['c25_trip'])) $labels[] = '诡雷';
			$place = $plsinfo[$item['pls']] ?? '未知地点';
			if(isset($group,$hplsinfo[$group][$item['pls']])) $place = $hplsinfo[$group][$item['pls']];
			$rows[] = Array('key'=>($source === 'trap' ? 't'.$item['tid'] : 'i'.$item['iid']),
				'kind'=>implode(' / ',$labels),'source'=>$source === 'trap' ? 'trap' : ($food ? 'food' : 'item'),
				'name'=>$item['itm'],'effect'=>$item['itme'],'count'=>$item['itms'],
				'place'=>$place,'pls'=>(int)$item['pls'],'local'=>$local,'owned'=>$owned,'can_net'=>$net);
		}
	}
	return $rows;
}

// 主请求持有现有process.lock；条件删除仍避免重复提交领取同一份物品。
function club25_ground_take($key,$data,$owned=false,$one=false)
{
	global $db,$tablepre,$nosta;
	if(!is_string($key) || !preg_match('/^([ti])([1-9][0-9]*)$/',$key,$m)) return false;
	$table = $m[1] === 't' ? 'maptrap' : 'mapitem'; $field = $m[1] === 't' ? 'tid' : 'iid'; $id = (int)$m[2];
	$result = $db->query("SELECT * FROM {$tablepre}{$table} WHERE {$field}='{$id}'");
	$item = $db->fetch_array($result);
	if(!$item || empty($item['itms']) || (int)$item['pls'] !== (int)$data['pls']) return false;
	$para = get_itmpara($item['itmpara'] ?? '');
	$group = $para['c25_mine']['pgroup'] ?? ($para['c25_ground_group'] ?? null);
	if($group !== null && (int)$group !== (int)$data['pgroup']) return false;
	if($owned && !club25_owned($item,$data['pid'])) return false;
	$db->query("DELETE FROM {$tablepre}{$table} WHERE {$field}='{$id}' AND pls='".(int)$data['pls']."'");
	if(!$db->affected_rows()) return false;
	unset($item[$field]);
	if($one && $table === 'mapitem' && ($item['itms'] === $nosta || (int)$item['itms'] > 1)) {
		$rest = $item;
		if($rest['itms'] !== $nosta) $rest['itms']--;
		// 被调用的幻影属于这一整堆；剩下的补给不会再生出同一只猫。
		unset($para['c25_hide']);
		$para['c25_poison_done'] = 1;
		$rest['itmpara'] = json_encode($para,JSON_UNESCAPED_UNICODE);
		$db->array_insert($tablepre.$table,$rest);
		$item['itms'] = 1;
	}
	$item['source'] = $table === 'maptrap' ? 'trap' : 'food';
	return $item;
}

function club25_net_take(&$actor,&$target,$input='')
{
	global $db,$tablepre,$log;
	if(($actor['club'] ?? 0) != 25 || $actor['hp'] <= 0 || $target['hp'] <= 0
		|| (int)$actor['pls'] !== (int)$target['pls'] || (int)$actor['pgroup'] !== (int)$target['pgroup']
		|| (int)$actor['pid'] === (int)$target['pid'] || (!empty($actor['teamID']) && $actor['teamID'] === $target['teamID'])) return false;
	$chosen = null;
	foreach(club25_board($actor) as $row) {
		if(!$row['can_net'] || (!$target['type'] && $row['source'] !== 'trap')) continue;
		if($input !== '' && $input !== $row['key']) continue;
		$chosen = $row; break;
	}
	if(!$chosen) { $log .= '这里没有能借来收网的普通机关。<br>'; return false; }
	return club25_ground_take($chosen['key'],$actor,false,true);
}

function club25_skill($sk,&$data,$input='')
{
	global $log,$db,$tablepre,$nosta,$c25_count,$c25_kind,$c25_target;
	$actions = Array('c25_modify'=>'modify','c25_hide'=>'hide','c25_feed'=>'feed','c25_field'=>'board','c25_trip'=>'trip');
	$action = $actions[$sk] ?? $sk;
	if($input === '') $input = isset($c25_target) && is_scalar($c25_target) ? (string)$c25_target : '';
	if(($data['club'] ?? 0) != 25 || $data['type'] || $data['hp'] <= 0) { $log .= '你现在还不能装作可怜猫。<br>'; return 0; }
	if($action === 'board') return 1;
	if(!empty($data['action']) && !in_array($data['action'],Array('corpse','pacorpse','focus'),true)) {
		$log .= '先应付眼前的人，再偷偷做手脚吧。<br>'; return 0;
	}
	if(($data['action'] ?? '') === 'focus' && !empty($data['bid'])) {
		$focus = fetch_playerdata_by_pid((int)$data['bid']);
		if($focus && $focus['hp'] > 0) { $log .= '先应付眼前的人，再偷偷做手脚吧。<br>'; return 0; }
	}
	$cfg = club25_config();
	if($action === 'feed') {
		if($data['rage'] < $cfg['feed_rage']) { $log .= '还没有气到想拿零食砸人，需要'.$cfg['feed_rage'].'点怒气。<br>'; return 0; }
		$slot = 0;
		for($i=1;$i<=6;$i++) if(empty($data['itms'.$i])) { $slot=$i; break; }
		if(!$slot) { $log .= '先腾出一个背包格，零食不会凭空挤进去。<br>'; return 0; }
		$data['itm'.$slot] = $cfg['feed_names'][array_rand($cfg['feed_names'])];
		$data['itmk'.$slot] = 'HB'; $data['itme'.$slot] = $cfg['feed_effect']; $data['itms'.$slot] = $cfg['feed_count'];
		$data['itmsk'.$slot] = $data['itmpara'.$slot] = '';
		$data['rage'] -= $cfg['feed_rage'];
		$log .= '可怜猫从蝴蝶结后面掏出了'.$cfg['feed_count'].'份'.htmlspecialchars($data['itm'.$slot],ENT_QUOTES,'UTF-8').'。这次真的能吃，暂时。<br>';
		return 1;
	}
	if($action === 'withdraw') {
		$slot=0; for($i=1;$i<=6;$i++) if(empty($data['itms'.$i])) { $slot=$i; break; }
		if(!$slot) { $log .= '背包满了，先给自己的小心思留个位置。<br>'; return 0; }
		$item = club25_ground_take($input,$data,true);
		if(!$item) { $log .= '它已经不在这里，或者不是你留下的东西。<br>'; return 0; }
		$item['itmk'] = str_replace('TO','TN',$item['itmk']);
		foreach(Array('itm','itmk','itme','itms','itmsk','itmpara') as $key) $data[$key.$slot] = $item[$key];
		$log .= '你把自己的布置悄悄收了回来。机关还在，别手滑吃掉哦。<br>';
		return 1;
	}
	if(!is_string($input) || !preg_match('/^(wep|[0-6])$/',$input) || ($input === 'wep' && $action !== 'modify')) {
		$log .= '请选择背包0～6格，改装也可选择当前武器。<br>'; return 0;
	}
	$item = club25_slot_item($data,$input);
	if(empty($item['itms'])) { $log .= '那个位置什么都没有。<br>'; return 0; }
	$para = get_itmpara($item['itmpara']);
	if($action === 'modify') {
		if(strpos($item['itmk'],'W') !== 0 || $item['itmk'] === 'WN' || $item['itme'] <= 0
			|| in_array('v',get_itmsk_array($item['itmsk'])) || in_array('V',get_itmsk_array($item['itmsk']))
			|| (function_exists('fcg_phantom') && fcg_phantom($item['itmpara']))) {
			$log .= '这件东西不能拆来做小阔剑。<br>'; return 0;
		}
		$cost = $cfg['modify_durability'];
		if($item['itms'] !== $nosta && $item['itms'] < $cost) { $log .= '这件武器已经不够拆了。<br>'; return 0; }
		// 混合武器只按第一个类别计算，枪械补弹成本单独由倍率控制。
		$kind = substr($item['itmk'],1,1);
		$rate = $cfg['weapon_rates'][$kind] ?? 1;
		$trap = Array('itm'=>'可怜猫的便携小阔剑','itmk'=>'TN','itme'=>max(1,min(65535,(int)round($item['itme']*$cost*$rate))),
			'itms'=>1,'itmsk'=>'','itmpara'=>'');
		club25_consume_slot($data,$input,$cost);
		club25_place_trap($data,$trap);
		return 1;
	}
	if(in_array($action,Array('hide','trip','hide_deploy','trip_deploy'),true)) {
		$hide = strpos($action,'hide') === 0; $key = $hide ? 'c25_hide' : 'c25_trip';
		if(isset($para[$key])) { $log .= '这里已经藏着一份小心思，不用再塞一份。<br>'; return 0; }
		if($hide && !preg_match('/^[HP][HSB][12]?$/',$item['itmk'])) { $log .= '幻影只愿意躲进普通生命、体力或命体补给里。<br>'; return 0; }
		$cost = $cfg[$hide ? 'hide_sp' : 'trip_sp'];
		if($data['sp'] < $cost) { $log .= '体力不足，连蝴蝶结都系不动了。<br>'; return 0; }
		if(!$hide && !in_array($c25_kind ?? 'damage',Array('damage','poison'),true)) return 0;
		if(strpos($action,'_deploy') !== false && (in_array('v',get_itmsk_array($item['itmsk'])) || in_array('V',get_itmsk_array($item['itmsk'])))) {
			$log .= '它黏住你了，不能拿去布场。<br>'; return 0;
		}
		$damage = max(1,(int)floor(max(0,$data['att'])*$cfg['hide_att_rate']+max(0,$data['wd'])*$cfg['hide_wd_rate']));
		$para[$key] = Array('owner'=>(int)$data['pid'],'name'=>$data['name'],'damage'=>$hide ? $damage : max(1,(int)floor($damage*$cfg['trip_damage_rate'])));
		if(!$hide) $para[$key]['mode'] = $c25_kind ?? 'damage';
		$data['itmpara'.$input] = json_encode($para,JSON_UNESCAPED_UNICODE);
		$data['sp'] -= $cost;
		$log .= $hide ? '你把自己的幻影塞进了补给。它保证只咬第一口。<br>' : '你系好了一根细线。真的不是在等谁来碰它哦？<br>';
		if(strpos($action,'_deploy') !== false) club25_deploy($data,$input,1);
		return 1;
	}
	if($action === 'deploy') return club25_deploy($data,$input,$c25_count ?? 1) ? 1 : 0;
	return 0;
}

// 外部来源传引用时由调用者统一保存，避免死亡结算用旧数组盖回成长。
function club25_effect_reward(&$victim,$owner,$kind,$damage,$new_poison=false,$source_item='',&$owner_data=NULL)
{
	global $now;
	if(!$owner || (int)$owner === (int)$victim['pid'] || ($damage <= 0 && !$new_poison)) return;
	$save = $owner_data === NULL;
	if($save) $owner_data = fetch_playerdata_by_pid((int)$owner);
	if(!$owner_data || $owner_data['type'] || (!empty($owner_data['teamID']) && $owner_data['teamID'] === $victim['teamID'])) return;
	$owner_data['clbpara'] = get_clbpara($owner_data['clbpara']);
	if(!$victim['type'] && $damage > 0 && in_array($kind,Array('food','trap'),true))
		$owner_data['clbpara']['achvars']['c25_'.$kind.'_hit'] = 1;
	if($owner_data['club'] == 25 && $owner_data['hp'] > 0 && empty($victim['c25_net_active'])) {
		$cfg = club25_config();
		$owner_data['exp'] += max(1,(int)round(($victim['lvl']-$owner_data['lvl'])/3));
		$owner_data['wd'] += $cfg['reward_wd'];
		include_once GAME_ROOT.'./include/state.func.php';
		lvlup_rev($owner_data,$victim,0);
	}
	$notice = htmlspecialchars($victim['name'],ENT_QUOTES,'UTF-8').'碰到了你留下的'.htmlspecialchars($source_item,ENT_QUOTES,'UTF-8').'，';
	$notice .= $damage > 0 ? '受到了'.$damage.'点伤害。' : '陷入了中毒。';
	logsave((int)$owner,$now,$notice.'<br>','b');
	if($save) player_save($owner_data);
}

include_once GAME_ROOT.'./include/game/club25.items.php';
