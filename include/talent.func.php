<?php
if(!defined('IN_GAME')) exit('Access Denied');

function talent_enabled()
{
	global $club_skills;
	return function_exists('ending_routes_enabled') && ending_routes_enabled() && (!isset($club_skills) || $club_skills != 0);
}

function talent_bonus($data,$key)
{
	if(!talent_enabled() || !empty($data['type'])) return 0;
	$para = get_clbpara($data['clbpara'] ?? '');
	return max(0,(int)($para['talent_bonus'][$key] ?? 0));
}

function talent_catalog()
{
	static $trees;
	if($trees === null) { include GAME_ROOT.'./gamedata/cache/talent_1.php'; $trees = $talent_trees; }
	return $trees;
}

function talent_ensure_storage()
{
	global $db,$gtablepre;
	static $ready = false;
	if($ready) return;
	$check = $db->query("SHOW COLUMNS FROM {$gtablepre}users LIKE 'talentdata'",'SILENT');
	if(!$check) throw new RuntimeException('无法读取天赋账户字段。');
	if($db->num_rows($check)) { $ready = true; return; }
	$lock = roommng_acquire_create_lock();
	if($lock === false) throw new RuntimeException('无法准备天赋账户字段。');
	try {
		$check = $db->query("SHOW COLUMNS FROM {$gtablepre}users LIKE 'talentdata'",'SILENT');
		if(!$check || (!$db->num_rows($check) && !$db->query("ALTER TABLE {$gtablepre}users ADD talentdata TEXT NULL",'SILENT'))) throw new RuntimeException('无法添加天赋账户字段。');
		$ready = true;
	} finally { roommng_release_create_lock($lock); }
}

function talent_load($raw)
{
	$empty = array('earned_points'=>0,'bought_points'=>0,'alloc'=>array(),'unlocked_titles'=>array(),'unlocked_skills'=>array(),'unlocked_races'=>array(0),'resets'=>0);
	if($raw === '' || $raw === null) return $empty;
	$data = is_array($raw) ? $raw : json_decode($raw,true);
	if(!is_array($data)) throw new RuntimeException('天赋记录无法读取，原有记录已保留。');
	$data += $empty;
	foreach(array('earned_points','bought_points','resets') as $key) if(!is_numeric($data[$key]) || $data[$key]<0) throw new RuntimeException('天赋点记录无效。');
	foreach(array('alloc','unlocked_titles','unlocked_skills','unlocked_races') as $key) if(!is_array($data[$key])) throw new RuntimeException('天赋记录无效。');
	return $data;
}

function talent_sync_earned(&$data,$achrev)
{
	$achrev = is_array($achrev) ? $achrev : json_decode((string)$achrev,true);
	$data['earned_points'] = 0;
	for($id=630;$id<=657;$id++) if(!empty($achrev[$id]['l'])) $data['earned_points']++;
}

function talent_cost($data)
{
	$trees = talent_catalog(); $used = 0;
	foreach($data['alloc'] as $tree=>$nodes) if(isset($trees[$tree]) && is_array($nodes)) foreach(array_unique($nodes) as $node) if(isset($trees[$tree]['nodes'][$node])) $used += $trees[$tree]['nodes'][$node]['cost'];
	return $used;
}

function talent_cap()
{
	$total = 0;
	foreach(talent_catalog() as $tree) foreach($tree['nodes'] as $node) $total += $node['cost'];
	return (int)floor($total/2);
}

function talent_price($data,$reset=false)
{
	global $talent_point_prices,$talent_price_step,$talent_reset_prices;
	$prices = $reset ? ($talent_reset_prices ?? array(100,300,600,1000)) : ($talent_point_prices ?? array(100,200,400,800,1200));
	$index = $reset ? (int)$data['resets'] : (int)floor($data['bought_points']/max(1,(int)($talent_price_step ?? 10)));
	return max(0,(int)$prices[min(count($prices)-1,$index)]);
}

// 纯状态转移，账户余额与旧记录在调用处用一条条件 UPDATE 保存。
function talent_change(&$data,$action,$tree='',$node=-1)
{
	if($action === 'buy') {
		if($data['earned_points']+$data['bought_points'] >= talent_cap()) throw new InvalidArgumentException('已有点数足够投入半数天赋树，无需继续购买。');
		$cost = talent_price($data); $data['bought_points']++; return $cost;
	}
	if($action === 'reset') {
		if(!talent_cost($data)) throw new InvalidArgumentException('当前没有已分配的天赋点。');
		$cost = talent_price($data,true); $data['alloc'] = array(); $data['resets']++; return $cost;
	}
	$trees = talent_catalog();
	if($action !== 'learn' || !is_string($tree) || !isset($trees[$tree]['nodes'][$node])) throw new InvalidArgumentException('请选择天赋树中的有效节点。');
	$learned = $data['alloc'][$tree] ?? array();
	if(in_array($node,$learned,true)) throw new InvalidArgumentException('这个节点已经点亮。');
	if($node>0 && !in_array($node-1,$learned,true)) throw new InvalidArgumentException('请先点亮前一个节点。');
	$def = $trees[$tree]['nodes'][$node];
	if(talent_cost($data)+$def['cost'] > min(talent_cap(),$data['earned_points']+$data['bought_points'])) throw new InvalidArgumentException('可分配天赋点不足。');
	$data['alloc'][$tree][] = $node;
	if($def['kind'] === 'title') $data['unlocked_titles'][] = $trees[$tree]['title_id'];
	if($def['kind'] === 'skill') $data['unlocked_skills'][$tree] = array_values(array_unique(array_merge($data['unlocked_skills'][$tree] ?? array(),$def['value'])));
	if($def['kind'] === 'race') $data['unlocked_races'][] = $def['value'];
	$data['unlocked_titles'] = array_values(array_unique($data['unlocked_titles']));
	$data['unlocked_races'] = array_values(array_unique($data['unlocked_races']));
	return 0;
}

function talent_current_user($uid,$password)
{
	global $db,$gtablepre;
	$user = $db->fetch_array($db->query("SELECT uid,username,password,groupid,credits2,talentdata,achrev,nicksrev FROM {$gtablepre}users WHERE uid=".(int)$uid));
	if(!$user || (int)$user['groupid']<=0 || !hash_equals((string)$user['password'],(string)$password)) throw new InvalidArgumentException('登录状态已失效。');
	return $user;
}

function talent_save_action($uid,$password,$input)
{
	global $db,$gtablepre,$tablepre;
	if(!is_array($input) || !isset($input['base']) || !is_string($input['base'])) throw new InvalidArgumentException('请刷新天赋页面。');
	talent_ensure_storage();
	// 与入场共用 process.lock，避免洗点检查和角色入场交错。
	$lock = roommng_acquire_create_lock();
	if($lock === false) throw new RuntimeException('天赋暂时无法保存。');
	try {
		$user = talent_current_user($uid,$password); $raw = (string)($user['talentdata'] ?? '');
		if(!hash_equals(hash('sha256',$raw),$input['base'])) throw new InvalidArgumentException('天赋已在其他页面改变，请刷新后重试；本次未扣费。');
		$data = talent_load($raw); talent_sync_earned($data,$user['achrev']);
		$action = $input['talent_action'] ?? '';
		if($action === 'reset') {
			$alive = $db->query("SELECT pid FROM {$tablepre}players WHERE type=0 AND hp>0 AND name=X'".bin2hex($user['username'])."' LIMIT 1");
			if($db->num_rows($alive)) throw new InvalidArgumentException('你在当前房间还有存活角色，不能洗点。');
		}
		$node = isset($input['node']) && is_scalar($input['node']) && preg_match('/\A[0-5]\z/',(string)$input['node']) ? (int)$input['node'] : -1;
		$cost = talent_change($data,$action,$input['tree'] ?? '',$node);
		if((int)$user['credits2'] < $cost) throw new InvalidArgumentException('切糕不足，本次没有改变天赋。');
		$nicks = is_array($user['nicksrev']) ? $user['nicksrev'] : json_decode((string)$user['nicksrev'],true);
		if(!is_array($nicks)) $nicks = array('nicks'=>array(0));
		$nicks['nicks'] = array_values(array_unique(array_merge($nicks['nicks'] ?? array(0),$data['unlocked_titles'])));
		$encoded = json_encode($data,JSON_UNESCAPED_UNICODE); $encoded_titles = json_encode($nicks,JSON_UNESCAPED_UNICODE);
		$sql = "UPDATE {$gtablepre}users SET talentdata=X'".bin2hex($encoded)."',nicksrev=X'".bin2hex($encoded_titles)."',credits2=credits2-".$cost." WHERE uid=".(int)$uid." AND password=X'".bin2hex($password)."' AND groupid>0 AND credits2>=".$cost." AND BINARY COALESCE(talentdata,'')=X'".bin2hex($raw)."' AND BINARY COALESCE(nicksrev,'')=X'".bin2hex((string)$user['nicksrev'])."'";
		if(!$db->query($sql,'SILENT')) throw new RuntimeException('无法保存天赋。');
		if($db->affected_rows()!==1) throw new InvalidArgumentException('账户记录或余额已变化，请刷新重试；本次未扣费。');
		return '天赋已保存。'.($cost ? '消耗 '.$cost.' 切糕。' : '');
	} finally { roommng_release_create_lock($lock); }
}

function talent_entry(&$data,$account,$race)
{
	if(!talent_enabled()) return;
	$talent = talent_load($account['talentdata'] ?? '');
	if(!in_array($race,$talent['unlocked_races'],true)) throw new InvalidArgumentException('这个种族尚未解锁。');
	$data['race'] = $race;
	$data['clbpara'] = get_clbpara($data['clbpara'] ?? '');
	$bonus = array();
	foreach(talent_catalog() as $id=>$tree) {
		foreach($talent['alloc'][$id] ?? array() as $number) {
			$node = $tree['nodes'][$number] ?? array();
			if(($node['kind'] ?? '') === 'bonus') foreach($node['value'] as $key=>$amount) $bonus[$key] = ($bonus[$key] ?? 0)+$amount;
		}
		if((int)$data['nick'] === $tree['title_id'] && in_array($tree['title_id'],$talent['unlocked_titles'],true)) {
			foreach($talent['unlocked_skills'][$id] ?? array() as $skill) {
				$data['clbpara']['skill'][] = $skill;
				$data['clbpara']['talent_skills'][$skill] = 1;
			}
			$data['clbpara']['talent_tree'] = $id;
		}
	}
	// 天赋技能不在 wc_* 列表中，不能纳入轮椅分流。
	$data['clbpara']['skill'] = array_values(array_unique($data['clbpara']['skill'] ?? array()));
	$data['clbpara']['talent_bonus'] = $bonus;
	foreach(array('mhp','msp','wp','wk','wg','wc','wd','wf') as $stat) if(isset($bonus[$stat])) $data[$stat] = ($data[$stat] ?? 0)+$bonus[$stat];
	// 只补本次增加的容量，保留其他头衔刻意设置的残血入场状态。
	$data['hp'] = min($data['mhp'],$data['hp']+($bonus['mhp'] ?? 0));
	$data['sp'] = min($data['msp'],$data['sp']+($bonus['msp'] ?? 0));
	// 先叠加入场已有的食物、饮料，保留其效果与附加信息；没有对应补给时补普通面包或水。
	foreach(array('supply_food'=>'HH','supply_drink'=>'HS') as $key=>$kind) {
		$amount = (int)($bonus[$key] ?? 0);
		if($amount<=0) continue;
		$found = false;
		for($i=1;$i<=6;$i++) if(($data['itmk'.$i] ?? '')===$kind && !empty($data['itms'.$i])) {
			if($data['itms'.$i] !== $GLOBALS['nosta']) $data['itms'.$i] += $amount;
			$found = true; break;
		}
		if(!$found) ending_routes_give($data,array('itm'=>$kind==='HH' ? '面包' : '矿泉水','itmk'=>$kind,'itme'=>$kind==='HH' ? 120 : 140,'itms'=>$amount));
	}
	if($race === 2) {
		$elements = array('炎','霜','雷','鸣','毒');
		$data['arb'] = '妖精羽翼·'.$elements[array_rand($elements)]; $data['arbk'] = 'DB'; $data['arbe'] = 50; $data['arbs'] = '∞'; $data['arbsk'] = ''; $data['arbpara'] = '';
	}
}

function talent_rest_required($data,$seconds)
{
	if(!talent_enabled()) return $seconds;
	$para = get_clbpara($data['clbpara'] ?? '');
	return max(1,(int)ceil($seconds*(1-min(0.5,max(0,(int)($para['talent_bonus']['rest_time'] ?? 0))/100))));
}
