<?php
if(!defined('IN_GAME')) exit('Access Denied');

function vncreation_ensure_table()
{
	global $db,$gtablepre;
	static $ready = false;
	if($ready) return;
	$sql = file_get_contents(GAME_ROOT.'./gamedata/sql/vncreation.sql');
	$db->query(str_replace(' bra_', ' '.$gtablepre, substr($sql, strpos($sql, 'CREATE TABLE'))));
	$ready = true;
}

// 投稿始终面向大房间，不能随投稿者所在的私人房间切换类别、地图或价格。
function vncreation_config()
{
	global $groomid,$gamecfg;
	$previous_room = $groomid;
	$groomid = 0;
	try {
		$file = config('vncreation',$gamecfg);
		include $file;
		include config('vnworld',$gamecfg);
		include config('resources',$gamecfg);
		$vncreation_cfg['file'] = $file;
		$vncreation_cfg['npc_count'] = $vncreation_npc_count;
		$vncreation_cfg['kinds'] = $vn_iteminfo;
		unset($vn_itemspkinfo['none']);
		$vncreation_cfg['attributes'] = $vn_itemspkinfo;
		$vncreation_cfg['max_sk'] = min(7, $vnmix_max_sk);
		$vncreation_cfg['place_count'] = count($plsinfo);
		unset($plsinfo[0], $plsinfo[34]);
		$vncreation_cfg['places'] = $plsinfo;
		return $vncreation_cfg;
	} finally {
		$groomid = $previous_room;
	}
}

function vncreation_html($value)
{
	return htmlspecialchars(is_scalar($value) ? (string)$value : '', ENT_QUOTES, 'UTF-8');
}

function vncreation_describe_item($part, $cfg)
{
	$attrs = array();
	foreach($part['sk'] as $sk) $attrs[] = $cfg['attributes'][$sk] ?? $sk;
	return $part['name'].' / '.($cfg['kinds'][$part['k']] ?? $part['k']).' / 效果 '.$part['e'].' / 耐久 '.($part['s'] ? $part['s'] : '∞').' / '.($attrs ? implode('、',$attrs) : '无属性');
}

function vncreation_describe($kind, $data, $cfg)
{
	if($kind === 'item') return array(vncreation_describe_item($data,$cfg), '每局 '.$data['count'].' 个，地点：'.($data['place'] === -1 ? '全图随机' : ($cfg['places'][$data['place']] ?? '当前地图不可用')));
	$level = $cfg['npc_levels'][$data['level']];
	$lines = array($data['name'].' / '.$level['name'].' / Lv.'.$level['lvl'].' / 生命 '.$level['mhp'].' / 体力 '.$level['msp'].' / 攻防 '.$level['att'].' / 熟练 '.$level['skill'].' / 金钱 '.$level['money']);
	foreach($data['equip'] as $slot=>$part) {
		$item = array_merge($part,$part['roll']);
		$lines[] = $cfg['equip_slots'][$slot].'（'.$cfg['equip_levels'][$part['tier']]['name'].'）：'.vncreation_describe_item($item,$cfg);
	}
	foreach($data['bag'] as $slot=>$part) $lines[] = '背包 '.$slot.'：'.vncreation_describe_item($part,$cfg);
	return $lines;
}

function vncreation_form_data($kind, $input, $cfg)
{
	$blank = array('name'=>'', 'k'=>'HH', 'e'=>1, 's'=>1, 'sk'=>array());
	if(!is_array($input)) $input = array();
	if($kind === 'item') return array_replace($blank,array('count'=>1,'place'=>-1),$input);
	$data = array('name'=>$input['name'] ?? '', 'level'=>$input['level'] ?? 1, 'equip'=>array(), 'bag'=>array());
	foreach($cfg['equip_slots'] as $slot=>$label) {
		$part = $input['equip'][$slot] ?? array();
		$data['equip'][$slot] = array_replace(array('name'=>'','tier'=>0,'k'=>$cfg['equip_kinds'][$slot][0]),is_array($part) ? $part : array());
	}
	for($i=1;$i<=6;$i++) {
		$part = $input['bag'][$i] ?? array();
		$data['bag'][$i] = array_replace($blank,is_array($part) ? $part : array());
	}
	return $data;
}

function vncreation_int($value, $min, $max)
{
	if((!is_string($value) && !is_int($value)) || !preg_match('/^-?[0-9]+$/D', (string)$value) || $value < $min || $value > $max) {
		throw new InvalidArgumentException('数值须为 '.$min.'～'.$max.' 之间的整数。');
	}
	return (int)$value;
}

function vncreation_name($value, $max = 30)
{
	// 名称还会进入旧战斗日志、物品名规则和 SQL，沿用幻想世界的纯文本边界。
	if(!is_string($value) || !preg_match('//u', $value)) throw new InvalidArgumentException('名称编码无效。');
	$value = trim($value);
	if($value === '' || $value === '0' || mb_strlen($value,'UTF-8') > $max || preg_match('/[<>\x22\x27\\\\&,;#|\p{C}]/u', $value)) {
		throw new InvalidArgumentException('名称须为 1～'.$max.' 个字符，不能包含引号、反斜杠、HTML 或分隔符。');
	}
	return $value;
}

function vncreation_item($input, $cfg)
{
	if(!is_array($input)) throw new InvalidArgumentException('物品参数无效。');
	$item = array('name'=>vncreation_name($input['name'] ?? ''), 'k'=>$input['k'] ?? '');
	if(!is_string($item['k']) || !isset($cfg['kinds'][$item['k']])) throw new InvalidArgumentException('请选择允许的物品种类。');
	$item['e'] = vncreation_int($input['e'] ?? '', 1, 16777214);
	$item['s'] = vncreation_int($input['s'] ?? '', 0, 65535);
	$sk = $input['sk'] ?? array();
	if(!is_array($sk) || count($sk) > $cfg['max_sk']) throw new InvalidArgumentException('额外属性最多选择 '.$cfg['max_sk'].' 个。');
	foreach($sk as $key) {
		if(!is_string($key) || !isset($cfg['attributes'][$key])) throw new InvalidArgumentException('选择了无效的额外属性。');
	}
	$item['sk'] = array_values(array_unique($sk));
	sort($item['sk']);
	return $item;
}

function vncreation_normalize($kind, $input, $cfg)
{
	if(!is_array($input)) throw new InvalidArgumentException('投稿参数无效。');
	if($kind === 'item') {
		$data = vncreation_item($input, $cfg);
		$data['count'] = vncreation_int($input['count'] ?? '', 1, $cfg['max_count']);
		$data['place'] = vncreation_int($input['place'] ?? '', -1, 255);
		if($data['place'] !== -1 && !isset($cfg['places'][$data['place']])) throw new InvalidArgumentException('请选择大房间中可投放的地点。');
		return $data;
	}
	if($kind !== 'npc') throw new InvalidArgumentException('投稿类别无效。');
	$data = array('name'=>vncreation_name($input['name'] ?? '',40));
	$data['level'] = vncreation_int($input['level'] ?? '', 1, count($cfg['npc_levels']));
	$data['equip'] = $data['bag'] = array();
	foreach($cfg['equip_slots'] as $slot=>$label) {
		$part = $input['equip'][$slot] ?? array();
		if(!is_array($part)) throw new InvalidArgumentException('装备参数无效。');
		$tier = vncreation_int($part['tier'] ?? 0, 0, count($cfg['equip_levels']));
		if(!$tier) continue;
		$k = $part['k'] ?? '';
		if(!is_string($k) || !in_array($k,$cfg['equip_kinds'][$slot],true)) throw new InvalidArgumentException($label.'的装备种类无效。');
		$data['equip'][$slot] = array('name'=>vncreation_name($part['name'] ?? ''), 'k'=>$k, 'tier'=>$tier);
	}
	for($i=1;$i<=6;$i++) {
		$part = $input['bag'][$i] ?? array();
		if(!is_array($part)) throw new InvalidArgumentException('背包参数无效。');
		if(isset($part['name']) && $part['name'] !== '') $data['bag'][$i] = vncreation_item($part,$cfg);
	}
	return $data;
}

function vncreation_item_cost($item, $cfg, $base)
{
	// 无限耐久比允许的任意有限耐久更贵。
	$durability = $item['s'] ? ($item['s']-1)*$cfg['durability_cost'] : 65535*$cfg['durability_cost']+$cfg['infinite_cost'];
	return $base + ($item['e']-1)*$cfg['effect_cost'] + $durability + count($item['sk'])*$cfg['attribute_cost'];
}

function vncreation_price($kind, $data, $cfg)
{
	if($kind === 'item') return vncreation_item_cost($data,$cfg,$cfg['item_base'])*$data['count'];
	$cost = $cfg['npc_levels'][$data['level']]['cost'];
	foreach($data['equip'] as $part) $cost += $cfg['equip_levels'][$part['tier']]['cost'];
	foreach($data['bag'] as $part) $cost += vncreation_item_cost($part,$cfg,$cfg['bag_base']);
	return $cost;
}

function vncreation_edit_cost($kind, $data, $old, $cfg)
{
	if($old === null) return vncreation_price($kind,$data,$cfg);
	if($kind === 'item') return max(0,$data['count']-$old['count'])*vncreation_item_cost($data,$cfg,$cfg['item_base']) + min($data['count'],$old['count'])*vncreation_item_upgrade_cost($data,$old,$cfg);
	$cost = max(0,$cfg['npc_levels'][$data['level']]['cost']-$cfg['npc_levels'][$old['level']]['cost']);
	foreach($data['equip'] as $slot=>$part) {
		$previous = $old['equip'][$slot] ?? null;
		$cost += max(0,$cfg['equip_levels'][$part['tier']]['cost']-($previous ? $cfg['equip_levels'][$previous['tier']]['cost'] : 0));
		if($previous && ($previous['tier'] !== $part['tier'] || $previous['k'] !== $part['k'])) {
			$cost += $part['tier']*$cfg['reroll_cost'];
		}
	}
	foreach($data['bag'] as $slot=>$part) $cost += isset($old['bag'][$slot]) ? vncreation_item_upgrade_cost($part,$old['bag'][$slot],$cfg) : vncreation_item_cost($part,$cfg,$cfg['bag_base']);
	return $cost;
}

function vncreation_item_upgrade_cost($item, $old, $cfg)
{
	$cost = max(0,$item['e']-$old['e'])*$cfg['effect_cost'];
	$new_s = $item['s'] ? ($item['s']-1)*$cfg['durability_cost'] : 65535*$cfg['durability_cost']+$cfg['infinite_cost'];
	$old_s = $old['s'] ? ($old['s']-1)*$cfg['durability_cost'] : 65535*$cfg['durability_cost']+$cfg['infinite_cost'];
	return $cost + max(0,$new_s-$old_s) + max(0,count($item['sk'])-count($old['sk']))*$cfg['attribute_cost'];
}

function vncreation_roll($slot, $tier, $cfg)
{
	$level = $cfg['equip_levels'][$tier];
	$pools = $cfg['rolls'][$slot === 'wep' ? 'weapon' : 'armor'];
	$sk = array();
	foreach($pools as $rarity=>$pool) {
		shuffle($pool);
		$sk = array_merge($sk,array_slice($pool,0,$level[$rarity]));
	}
	sort($sk);
	return array('e'=>$level['e'], 's'=>$level['s'], 'sk'=>$sk);
}

function vncreation_get($id)
{
	global $db,$gtablepre;
	$id = (int)$id;
	$result = $db->query("SELECT * FROM {$gtablepre}vncreation WHERE id=$id");
	return $db->fetch_array($result);
}

function vncreation_stamp($row)
{
	return $row ? hash('sha256',$row['content'].'|'.$row['status'].'|'.$row['spent'].'|'.$row['note'].'|'.$row['updated']) : '';
}

function vncreation_start_session()
{
	if(session_status() === PHP_SESSION_ACTIVE) return;
	if(!session_start(array('use_strict_mode'=>1,'cookie_httponly'=>true,'cookie_samesite'=>'Lax'))) {
		gexit('暂时无法保存操作记录，请联系管理员检查会话存储目录。',__FILE__,__LINE__);
	}
}

// 每个表单单独使用一次性令牌；PHP session 锁覆盖校验、保存及令牌消费。
function vncreation_form_token($context, $row = null)
{
	if(!isset($_SESSION['vncreation_forms'])) $_SESSION['vncreation_forms'] = array();
	$token = bin2hex(random_bytes(24));
	$_SESSION['vncreation_forms'][$token] = array($context,vncreation_stamp($row));
	if(count($_SESSION['vncreation_forms']) > 80) array_shift($_SESSION['vncreation_forms']);
	return $token;
}

function vncreation_check_token($token, $context)
{
	if(!is_string($token) || !isset($_SESSION['vncreation_forms'][$token]) || $_SESSION['vncreation_forms'][$token][0] !== $context) {
		throw new InvalidArgumentException('表单已提交或已过期，请刷新页面后重试。');
	}
	return $_SESSION['vncreation_forms'][$token][1];
}

function vncreation_save($user, $kind, $id, $action, $input, $stamp, $cfg)
{
	global $db,$gtablepre,$now;
	if(!$cfg['enabled']) throw new InvalidArgumentException('当前大房间规则集使用独立的角色和物品系统，暂不接收此类投稿。');
	$id = vncreation_int($id,0,2147483647);
	$uid = (int)$user['uid'];
	if(!in_array($action,array('save','reroll'),true)) throw new InvalidArgumentException('操作无效。');
	// users 仍可能是 MyISAM，不能以事务代替互斥；锁内重读余额和作品。
	$db->query("LOCK TABLES {$gtablepre}users WRITE, {$gtablepre}vncreation WRITE");
	try {
		$result = $db->query("SELECT * FROM {$gtablepre}users WHERE uid=$uid");
		$current = $db->fetch_array($result);
		if(!$current || $current['password'] !== $user['password'] || $current['groupid'] <= 0) throw new InvalidArgumentException('账号状态已改变，请重新登录。');
		$row = $id ? vncreation_get($id) : null;
		if($id && (!$row || (int)$row['uid'] !== $uid || $row['kind'] !== $kind)) throw new InvalidArgumentException('只能编辑自己的投稿。');
		if(vncreation_stamp($row) !== $stamp) throw new InvalidArgumentException('投稿已被修改或审核，请刷新页面后再操作。');
		$old = $row ? json_decode($row['content'],true) : null;
		if($action === 'reroll') {
			$slot = $input['slot'] ?? '';
			if($kind !== 'npc' || !$old || !is_string($slot) || !isset($old['equip'][$slot])) throw new InvalidArgumentException('没有可重掷的装备。');
			$data = $old;
			$tier = $data['equip'][$slot]['tier'];
			if($tier < 2) throw new InvalidArgumentException('普通装备没有随机属性，无需重掷。');
			$data['equip'][$slot]['roll'] = vncreation_roll($slot,$tier,$cfg);
			$cost = $tier*$cfg['reroll_cost'];
		} else {
			$data = vncreation_normalize($kind,$input,$cfg);
			$cost = vncreation_edit_cost($kind,$data,$old,$cfg);
			if($kind === 'npc') {
				foreach($data['equip'] as $slot=>&$part) {
					$previous = $old['equip'][$slot] ?? null;
					$part['roll'] = $previous && $previous['tier'] === $part['tier'] && $previous['k'] === $part['k'] ? $previous['roll'] : vncreation_roll($slot,$part['tier'],$cfg);
				}
				unset($part);
			}
		}
		$content = json_encode($data,JSON_UNESCAPED_UNICODE);
		if($content === false) throw new InvalidArgumentException('投稿内容无法保存。');
		if($cost > (int)$current['credits2']) throw new InvalidArgumentException('切糕不足，本次需要 '.$cost.' 切糕。');
		if($cost && (!$db->query("UPDATE {$gtablepre}users SET credits2=credits2-$cost WHERE uid=$uid AND credits2>=$cost",'SILENT') || $db->affected_rows() !== 1)) {
			throw new RuntimeException('扣费失败，投稿未保存。');
		}
		$content_sql = "X'".bin2hex($content)."'";
		$spent = ($row ? (int)$row['spent'] : 0)+$cost;
		if($row) {
			$sql = "UPDATE {$gtablepre}vncreation SET content=$content_sql,status=0,note='',spent=$spent,updated=$now WHERE id=$id";
		} else {
			$creator_sql = "X'".bin2hex($current['username'])."'";
			$sql = "INSERT INTO {$gtablepre}vncreation (uid,creator,kind,status,content,spent,note,updated) VALUES ($uid,$creator_sql,'$kind',0,$content_sql,$spent,'',$now)";
		}
		try {
			$saved = $db->query($sql,'SILENT');
		} catch(Exception $e) {
			// PHP 8.1 的 mysqli 默认以异常报告 SQL 失败，仍须走退款分支。
			$saved = false;
		}
		if(!$saved) {
			if($cost) $db->query("UPDATE {$gtablepre}users SET credits2=credits2+$cost WHERE uid=$uid");
			throw new RuntimeException('保存失败，本次切糕已退回，请稍后重试。');
		}
		if(!$id) $id = $db->insert_id();
		return array('id'=>$id, 'cost'=>$cost, 'balance'=>(int)$current['credits2']-$cost);
	} finally {
		$db->query('UNLOCK TABLES');
	}
}

function vncreation_review($user, $id, $status, $note, $stamp)
{
	global $db,$gtablepre,$now,$gamefounder;
	$id = vncreation_int($id,1,2147483647);
	$status = vncreation_int($status,0,2);
	if(!is_string($note) || !preg_match('//u',$note) || mb_strlen($note,'UTF-8') > 255) throw new InvalidArgumentException('审核说明最多 255 字。');
	$uid = (int)$user['uid'];
	$db->query("LOCK TABLES {$gtablepre}users READ, {$gtablepre}vncreation WRITE");
	try {
		$result = $db->query("SELECT * FROM {$gtablepre}users WHERE uid=$uid");
		$current = $db->fetch_array($result);
		if(!$current || $current['password'] !== $user['password'] || ($current['groupid'] < 5 && $current['username'] !== $gamefounder)) throw new InvalidArgumentException('没有审核权限。');
		$row = vncreation_get($id);
		if(!$row || vncreation_stamp($row) !== $stamp) throw new InvalidArgumentException('投稿已改变，请重新查看内容后审核。');
		$note_sql = "X'".bin2hex($note)."'";
		$db->query("UPDATE {$gtablepre}vncreation SET status=$status,note=$note_sql,updated=$now WHERE id=$id");
	} finally {
		$db->query('UNLOCK TABLES');
	}
}

function vncreation_set_count($value, $cfg)
{
	if(!$cfg['enabled']) throw new InvalidArgumentException('当前大房间规则集不投放此类投稿，无需设置数量。');
	$value = vncreation_int($value,0,100);
	// 与开局读取互斥，避免读到只写了一部分的配置。
	$lock = fopen(GAME_ROOT.'./gamedata/process.lock','ab');
	if(!$lock || !flock($lock,LOCK_EX)) throw new RuntimeException('暂时无法保存配置，请稍后重试。');
	try {
		$text = file_get_contents($cfg['file']);
		$text = preg_replace('/\$vncreation_npc_count\s*=\s*\d+;/', '\$vncreation_npc_count = '.$value.';', $text, 1, $changed);
		if(!$changed || file_put_contents($cfg['file'],$text,LOCK_EX) !== strlen($text)) throw new RuntimeException('配置写入失败，请检查文件权限。');
		if(function_exists('opcache_invalidate')) opcache_invalidate($cfg['file'],true);
	} finally {
		flock($lock,LOCK_UN);
		fclose($lock);
	}
}

function vncreation_npc_data($data, $cfg, $number)
{
	global $now,$baseexp,$nosta;
	$npc = get_npcinit();
	$stats = $cfg['npc_levels'][$data['level']];
	foreach(array('lvl','mhp','msp','att','def','money') as $key) $npc[$key] = $stats[$key];
	$npc['name'] = $data['name'];
	$npc['type'] = 95;
	$npc['sNo'] = $number;
	$npc['endtime'] = $now;
	$npc['hp'] = $npc['mhp'];
	$npc['sp'] = $npc['msp'];
	$npc['state'] = 0;
	$npc['exp'] = min(65535,round(2*$npc['lvl']*$baseexp));
	$npc['pls'] = array_rand($cfg['places']);
	foreach(array('p','k','g','c','d','f') as $key) $npc['w'.$key] = $stats['skill'];
	foreach($data['equip'] as $slot=>$part) {
		$npc[$slot] = $part['name'];
		$npc[$slot.'k'] = $part['k'];
		$npc[$slot.'e'] = $part['roll']['e'];
		$npc[$slot.'s'] = $part['roll']['s'];
		$npc[$slot.'sk'] = implode('',$part['roll']['sk']);
		$npc[$slot.'para'] = '';
	}
	foreach($data['bag'] as $slot=>$part) {
		$npc['itm'.$slot] = $part['name'];
		$npc['itmk'.$slot] = $part['k'];
		$npc['itme'.$slot] = $part['e'];
		$npc['itms'.$slot] = $part['s'] ? $part['s'] : $nosta;
		$npc['itmsk'.$slot] = implode('',$part['sk']);
		$npc['itmpara'.$slot] = '';
	}
	return $npc;
}

// rs_game 已在开局锁内；禁区刷新不再投放，私房也不读取公共投稿池。
function vncreation_spawn($kind)
{
	global $db,$gtablepre,$tablepre,$groomid,$nosta,$gamevars,$gamecfg;
	if((int)$groomid !== 0) return;
	if($kind === 'item' && !empty($gamevars['vncreation_items'])) return;
	include config('vncreation',$gamecfg);
	if(!$vncreation_cfg['enabled']) return;
	$cfg = vncreation_config();
	if(!$cfg['places'] || ($kind === 'npc' && !$cfg['npc_count'])) return;
	vncreation_ensure_table();
	$limit = $kind === 'npc' ? ' ORDER BY RAND() LIMIT '.max(0,min(100,(int)$cfg['npc_count'])) : '';
	$result = $db->query("SELECT * FROM {$gtablepre}vncreation WHERE kind='$kind' AND status=1".$limit);
	$number = 0;
	while($row = $db->fetch_array($result)) {
		$data = json_decode($row['content'],true);
		if($kind === 'npc') {
			$npc = vncreation_npc_data($data,$cfg,++$number);
			if(function_exists('ruleset_randomize_npc_stats')) ruleset_randomize_npc_stats($npc);
			if(function_exists('ruleset_should_randomize_npc') && ruleset_should_randomize_npc(99) && function_exists('ruleset_get_random_npc_location')) $npc['pls'] = ruleset_get_random_npc_location($cfg['place_count']);
			$db->array_insert("{$tablepre}players",player_format_with_db_structure($npc));
		} else {
			// 大房间更换地图后，原定点不可用的投稿暂不投放。
			if($data['place'] !== -1 && !isset($cfg['places'][$data['place']])) continue;
			$random_place = $data['place'] === -1 || (function_exists('ruleset_should_randomize_item') && ruleset_should_randomize_item($data['place'],0,0));
			for($i=0;$i<$data['count'];$i++) {
				$place = $random_place ? array_rand($cfg['places']) : $data['place'];
				$db->array_insert("{$tablepre}mapitem",array('itm'=>$data['name'], 'itmk'=>$data['k'], 'itme'=>$data['e'], 'itms'=>$data['s'] ? $data['s'] : $nosta, 'itmsk'=>implode('',$data['sk']), 'pls'=>$place, 'itmpara'=>''));
			}
		}
	}
	if($kind === 'item') {
		$gamevars['vncreation_items'] = 1;
		save_gameinfo();
	}
}
