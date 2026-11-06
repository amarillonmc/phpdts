<?php
if(!defined('IN_GAME')) exit('Access Denied');

function paperdoll_json($value)
{
	return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
}

function paperdoll_catalog()
{
	static $catalog = null;
	if($catalog === null) {
		$raw = file_get_contents(GAME_ROOT.'./gamedata/paperdoll/catalog.json');
		$catalog = json_decode($raw, true);
		if(!is_array($catalog) || !isset($catalog['default'], $catalog['unit_price']) || !is_int($catalog['unit_price']) || $catalog['unit_price'] < 0) {
			throw new RuntimeException('纸娃娃资源配置无效。');
		}
	}
	return $catalog;
}

function paperdoll_normalize_appearance($input, $catalog = null)
{
	if($catalog === null) $catalog = paperdoll_catalog();
	if(!is_array($input) || !isset($input['classic']) || !is_bool($input['classic'])) throw new InvalidArgumentException('请选择经典界面的形象显示方式。');
	$indexes = array();
	foreach(array('hair', 'top', 'bottom', 'accessories') as $category) {
		$indexes[$category] = array();
		foreach($catalog[$category] as $part) $indexes[$category][$part['id']] = $part;
	}
	$state = array('classic' => $input['classic']);
	foreach(array('hair', 'top', 'bottom') as $category) {
		$part = $input[$category] ?? null;
		if(!is_array($part) || !isset($part['id'], $part['color']) || !is_string($part['id']) || !isset($indexes[$category][$part['id']])) throw new InvalidArgumentException('请选择列表中的发型、上装和下装。');
		if(!is_string($part['color']) || !preg_match('/\A#[0-9a-fA-F]{6}\z/', $part['color'])) throw new InvalidArgumentException('部件颜色无效，请重新选择。');
		$state[$category] = array('id' => $part['id'], 'color' => strtolower($part['color']));
	}
	if(!isset($input['accessories']) || !is_array($input['accessories']) || array_values($input['accessories']) !== $input['accessories'] || count($input['accessories']) > count($indexes['accessories'])) throw new InvalidArgumentException('饰品选择无效。');
	$accessories = array();
	foreach($input['accessories'] as $part) {
		if(!is_array($part) || !isset($part['id'], $part['color']) || !is_string($part['id']) || !isset($indexes['accessories'][$part['id']])) throw new InvalidArgumentException('请选择列表中的饰品。');
		if(!is_string($part['color']) || !preg_match('/\A#[0-9a-fA-F]{6}\z/', $part['color'])) throw new InvalidArgumentException('饰品颜色无效，请重新选择。');
		$slot = $indexes['accessories'][$part['id']]['slot'];
		if(isset($accessories[$slot])) throw new InvalidArgumentException('同一位置只能佩戴一件饰品。');
		$accessories[$slot] = array('id' => $part['id'], 'color' => strtolower($part['color']));
	}
	ksort($accessories);
	$state['accessories'] = array_values($accessories);
	return $state;
}

function paperdoll_default($catalog = null)
{
	if($catalog === null) $catalog = paperdoll_catalog();
	$state = paperdoll_normalize_appearance(array('classic' => false) + $catalog['default'], $catalog);
	return array('free_used' => false, 'revision' => 0) + $state;
}

function paperdoll_load($raw, $catalog = null)
{
	if($catalog === null) $catalog = paperdoll_catalog();
	if($raw === null || $raw === '') return paperdoll_default($catalog);
	if(!is_string($raw) || strlen($raw) > 8192) throw new RuntimeException('形象读取失败，请联系管理员；原有形象已保留。');
	$state = json_decode($raw, true);
	if(!is_array($state) || !isset($state['free_used']) || !is_bool($state['free_used'])) throw new RuntimeException('形象读取失败，请联系管理员；原有形象已保留。');
	$revision = $state['revision'] ?? 0;
	if(!is_int($revision) || $revision < 0 || $revision >= PHP_INT_MAX) throw new RuntimeException('形象读取失败，请联系管理员；原有形象已保留。');
	try {
		return array('free_used' => $state['free_used'], 'revision' => $revision) + paperdoll_normalize_appearance($state, $catalog);
	} catch(InvalidArgumentException $e) {
		throw new RuntimeException('形象读取失败，请联系管理员；原有形象已保留。');
	}
}

function paperdoll_display_state($raw)
{
	$catalog = paperdoll_catalog();
	try { return paperdoll_load($raw, $catalog); }
	catch(RuntimeException $e) {
		// 损坏的账户外观不阻断对局，也不能借默认显示重领首次免费。
		$state = paperdoll_default($catalog);
		$state['free_used'] = true;
		return $state;
	}
}

function paperdoll_changed_count($old, $next, $catalog = null)
{
	if($catalog === null) $catalog = paperdoll_catalog();
	$count = 0;
	foreach(array('hair', 'top', 'bottom') as $category) if($old[$category] !== $next[$category]) $count++;
	$slots = array();
	foreach($catalog['accessories'] as $part) $slots[$part['id']] = $part['slot'];
	$old_parts = $new_parts = array();
	foreach($old['accessories'] as $part) $old_parts[$slots[$part['id']]] = $part;
	foreach($next['accessories'] as $part) $new_parts[$slots[$part['id']]] = $part;
	foreach(array_unique(array_merge(array_keys($old_parts), array_keys($new_parts))) as $slot) {
		if(($old_parts[$slot] ?? null) !== ($new_parts[$slot] ?? null)) $count++;
	}
	return $count;
}

function paperdoll_ensure_storage()
{
	global $db, $gtablepre;
	static $ready = false;
	if($ready) return;
	$result = $db->query("SHOW COLUMNS FROM {$gtablepre}users LIKE 'paperdoll'", 'SILENT');
	if(!$result) throw new RuntimeException('无法检查纸娃娃账户字段。');
	if($db->num_rows($result)) { $ready = true; return; }
	$lock = roommng_acquire_create_lock();
	if($lock === false) throw new RuntimeException('无法准备纸娃娃账户字段。');
	try {
		$result = $db->query("SHOW COLUMNS FROM {$gtablepre}users LIKE 'paperdoll'", 'SILENT');
		if(!$result) throw new RuntimeException('无法检查纸娃娃账户字段。');
		if(!$db->num_rows($result) && !$db->query("ALTER TABLE {$gtablepre}users ADD paperdoll TEXT NULL", 'SILENT')) throw new RuntimeException('无法添加纸娃娃账户字段。');
		$ready = true;
	} finally { roommng_release_create_lock($lock); }
}

function paperdoll_current_user($uid, $password)
{
	global $db, $gtablepre;
	$result = $db->query("SELECT uid,password,groupid,credits2,paperdoll FROM {$gtablepre}users WHERE uid=".(int)$uid, 'SILENT');
	if(!$result) throw new RuntimeException('无法读取纸娃娃账户。');
	$user = $db->fetch_array($result);
	if(!$user || (int)$user['groupid'] <= 0 || !hash_equals((string)$user['password'], (string)$password)) throw new InvalidArgumentException('登录状态已失效，请重新登录。');
	return $user;
}

function paperdoll_account_state($user, $catalog = null)
{
	return array('state' => paperdoll_load($user['paperdoll'] ?? '', $catalog), 'base' => hash('sha256', (string)($user['paperdoll'] ?? '')), 'credits2' => (int)$user['credits2']);
}

function paperdoll_save($user, $input, $catalog = null)
{
	global $db, $gtablepre;
	if($catalog === null) $catalog = paperdoll_catalog();
	if(!is_array($input) || !isset($input['base']) || !is_string($input['base']) || !preg_match('/\A[0-9a-f]{64}\z/', $input['base'])) throw new InvalidArgumentException('页面已失效，请刷新后重试。');
	$old = paperdoll_load($user['paperdoll'] ?? '', $catalog);
	$next = array('free_used' => $old['free_used'], 'revision' => $old['revision']) + paperdoll_normalize_appearance($input['appearance'] ?? null, $catalog);
	$changed = paperdoll_changed_count($old, $next, $catalog);
	if($changed === 0 && $next['classic'] === $old['classic']) {
		// 相同配置的重试不再次扣费，旧页面也不能覆盖另一个已经保存的形象。
		return paperdoll_account_state($user, $catalog) + array('cost' => 0, 'changed' => 0, 'message' => '形象没有变化，未消耗切糕。');
	}
	$raw = (string)($user['paperdoll'] ?? '');
	if(!hash_equals(hash('sha256', $raw), $input['base'])) throw new InvalidArgumentException('形象已在其他页面更新，请刷新后重新选择。');
	$cost = $old['free_used'] ? $changed * $catalog['unit_price'] : 0;
	if((int)$user['credits2'] < $cost) throw new InvalidArgumentException('切糕不足，形象未保存。');
	if($changed > 0) $next['free_used'] = true;
	// 换回曾用过的形象也产生新状态，避免旧页面的保存凭据重新生效。
	$next['revision']++;
	$encoded = paperdoll_json($next);
	// 外观、首次免费标记和扣费在同一账户行内条件更新，兼容既有 MyISAM 部署。
	$query = "UPDATE {$gtablepre}users SET paperdoll=X'".bin2hex($encoded)."',credits2=credits2-".$cost.
		" WHERE uid=".(int)$user['uid']." AND password=X'".bin2hex((string)$user['password'])."' AND groupid>0 AND BINARY COALESCE(paperdoll,'')=X'".bin2hex($raw)."' AND credits2>=".$cost;
	if(!$db->query($query, 'SILENT')) throw new RuntimeException('无法保存纸娃娃账户。');
	if($db->affected_rows() !== 1) throw new InvalidArgumentException('形象或切糕余额已变化，请刷新后重试；本次未扣费。');
	$user = paperdoll_current_user($user['uid'], $user['password']);
	$message = $changed === 0 ? '显示方式已保存，未消耗切糕。' : (!$old['free_used'] ? '形象已保存，本次首次自定义免费。' : '形象已保存，消耗 '.$cost.' 切糕。');
	return paperdoll_account_state($user, $catalog) + array('cost' => $cost, 'changed' => $changed, 'message' => $message);
}
