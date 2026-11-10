<?php
if(!defined('IN_GAME')) exit('Access Denied');

// 账户奖励不随收件人所在的小房间切换资源表。
function account_reward_catalog()
{
	static $catalog;
	if($catalog === null) {
		include GAME_ROOT.'./gamedata/cache/titles_1.php';
		include GAME_ROOT.'./gamedata/cache/fcg_titles.php';
		include GAME_ROOT.'./gamedata/cache/achievement_1.php';
		// 合成日记等没有完成阶段的条目不是可授予的成就。
		foreach($ach_list as $id=>$achievement) if(empty($achievement['lvl']) || empty($achievement['name'])) unset($ach_list[$id]);
		$catalog = array('titles'=>$titles_list, 'title_desc'=>$title_desc, 'achievements'=>$ach_list);
	}
	return $catalog;
}

function account_reward_html($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// 各数据库封装的 array_insert 转义行为不同；这里使用 MySQL 字符串字面量。
function account_reward_sql($value)
{
	if(is_int($value)) return (string)$value;
	return "X'".bin2hex((string)$value)."'";
}

function account_reward_int($value, $min, $max)
{
	if((!is_int($value) && !is_string($value)) || !preg_match('/\A[0-9]+\z/', (string)$value) || $value < $min || $value > $max) {
		throw new InvalidArgumentException('数值须为 '.$min.'～'.$max.' 之间的整数。');
	}
	return (int)$value;
}

function account_reward_post_check($scope, $input)
{
	global $udata, $cpass;
	$expected = hash_hmac('sha256', $scope.':'.$udata['uid'], (string)$cpass);
	if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !is_string($input['reward_csrf'] ?? null) || !hash_equals($expected, $input['reward_csrf'])) {
		throw new InvalidArgumentException('页面校验已失效，请刷新后重试。');
	}
}

function account_reward_mail_text($title, $content)
{
	if(!is_string($title) || !preg_match('//u', $title) || trim($title) === '' || mb_strlen($title, 'UTF-8') > 100 || !is_string($content) || !preg_match('//u', $content) || strlen($content) > 10000) {
		throw new InvalidArgumentException('请填写 1～100 字的邮件标题，正文最多 10000 字节。');
	}
	$title = account_reward_html($title);
	if(mb_strlen($title, 'UTF-8') > 255) throw new InvalidArgumentException('标题中的特殊符号过多，请缩短标题。');
	$content = nl2br(account_reward_html($content));
	if(strlen($content) > 65535) throw new InvalidArgumentException('正文中的换行或特殊符号过多，请缩短正文。');
	return array($title, $content);
}

function account_boon_stats()
{
	return array('mhp'=>'生命上限', 'msp'=>'体力上限', 'att'=>'攻击', 'def'=>'防御', 'wp'=>'殴熟', 'wk'=>'斩熟', 'wg'=>'射熟', 'wc'=>'投熟', 'wd'=>'爆熟', 'wf'=>'灵熟', 'money'=>'金钱');
}

function account_boon_validate($boon)
{
	if(!is_array($boon) || !is_string($boon['name'] ?? null) || !preg_match('//u', $boon['name']) || trim($boon['name']) === '' || mb_strlen($boon['name'], 'UTF-8') > 40 || !is_array($boon['stats'] ?? null) || !$boon['stats']) {
		throw new InvalidArgumentException('赐福需要名称和至少一项属性加成。');
	}
	$stats = array();
	foreach($boon['stats'] as $key=>$value) {
		if(!isset(account_boon_stats()[$key])) throw new InvalidArgumentException('赐福包含不支持的属性。');
		$stats[$key] = account_reward_int($value, 1, in_array($key, array('wp','wk','wg','wc','wd','wf'), true) ? 65535 : 1000000000);
	}
	return array('name'=>trim($boon['name']), 'stats'=>$stats);
}

function account_reward_validate($reward)
{
	if(!is_array($reward) || array_diff(array_keys($reward), array('credits','credits2','titles','achievements','boon'))) throw new InvalidArgumentException('邮件附件格式无效。');
	$catalog = account_reward_catalog();
	$out = array('credits'=>account_reward_int($reward['credits'] ?? 0, 0, 2147483647), 'credits2'=>account_reward_int($reward['credits2'] ?? 0, 0, 8388607), 'titles'=>array(), 'achievements'=>array());
	if(!is_array($reward['titles'] ?? array()) || !is_array($reward['achievements'] ?? array())) throw new InvalidArgumentException('头衔或成就列表格式无效。');
	foreach($reward['titles'] ?? array() as $id) {
		$id = account_reward_int($id, 0, 2147483647);
		if(!isset($catalog['titles'][$id])) throw new InvalidArgumentException('头衔编号 '.$id.' 不存在。');
		$out['titles'][] = $id;
	}
	$out['titles'] = array_values(array_unique($out['titles']));
	foreach($reward['achievements'] ?? array() as $id=>$level) {
		$id = account_reward_int($id, 0, 2147483647);
		if(!isset($catalog['achievements'][$id])) throw new InvalidArgumentException('成就编号 '.$id.' 不存在。');
		$out['achievements'][$id] = account_reward_int($level, 1, $catalog['achievements'][$id]['lvl']);
	}
	if(isset($reward['boon'])) $out['boon'] = account_boon_validate($reward['boon']);
	return $out;
}

function account_reward_decode($enclosure)
{
	if(!is_string($enclosure)) throw new InvalidArgumentException('邮件附件格式无效。');
	$enclosure = trim($enclosure);
	if(substr($enclosure, 0, 1) === '{') return account_reward_validate(json_decode($enclosure, true));
	// 保留旧切糕、头衔附件；也允许手工填写积分和成就附件。
	$reward = array('titles'=>array(), 'achievements'=>array());
	foreach(explode(';', $enclosure) as $part) {
		$part = trim($part);
		if($part === '') continue;
		if(preg_match('/\A(getqiegao|getcredits|gettitle)_([0-9]+)\z/', $part, $m)) {
			if($m[1] === 'gettitle') { if((int)$m[2]) $reward['titles'][] = $m[2]; }
			else {
				$key = $m[1] === 'getqiegao' ? 'credits2' : 'credits';
				$reward[$key] = ($reward[$key] ?? 0) + account_reward_int($m[2], 0, $key === 'credits2' ? 8388607 : 2147483647);
			}
		} elseif(preg_match('/\Agetachievement_([0-9]+)_([0-9]+)\z/', $part, $m)) {
			$reward['achievements'][$m[1]] = $m[2];
		} else throw new InvalidArgumentException('无法识别的邮件附件：'.$part);
	}
	return account_reward_validate($reward);
}

function account_reward_from_form($input)
{
	$reward = account_reward_decode($input['senclosure'] ?? '');
	foreach(array('credits','credits2') as $key) $reward[$key] += account_reward_int($input['reward_'.$key] ?? 0, 0, $key === 'credits2' ? 8388607 : 2147483647);
	foreach(array('titles','achievements') as $key) {
		$raw = $input['reward_'.$key] ?? '';
		if(!is_string($raw)) throw new InvalidArgumentException('编号列表格式无效。');
		foreach(preg_split('/[\s,，]+/u', trim($raw), -1, PREG_SPLIT_NO_EMPTY) as $part) {
			if($key === 'titles') $reward['titles'][] = account_reward_int($part, 0, 2147483647);
			else {
				$pieces = explode(':', $part);
				$id = account_reward_int($pieces[0], 0, 2147483647);
				$catalog = account_reward_catalog();
				if(count($pieces) > 2 || !isset($catalog['achievements'][$id])) throw new InvalidArgumentException('成就编号或阶段格式无效。');
				$reward['achievements'][$id] = $pieces[1] ?? $catalog['achievements'][$id]['lvl'];
			}
		}
	}
	$stats = array();
	foreach(account_boon_stats() as $key=>$label) {
		$value = $input['boon_'.$key] ?? '0';
		if($value === '') $value = '0';
		$value = account_reward_int($value, 0, in_array($key, array('wp','wk','wg','wc','wd','wf'), true) ? 65535 : 1000000000);
		if($value) $stats[$key] = $value;
	}
	if($stats || !empty($input['boon_name'])) $reward['boon'] = array('name'=>$input['boon_name'] ?? '', 'stats'=>$stats);
	return account_reward_validate($reward);
}

function account_reward_encode($reward)
{
	$reward = account_reward_validate($reward);
	if(!$reward['credits'] && !$reward['credits2'] && !$reward['titles'] && !$reward['achievements'] && empty($reward['boon'])) return '';
	return json_encode($reward, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

function account_reward_form_values($input)
{
	$defaults = array('stitle'=>'发放奖励', 'scontent'=>'您获得了奖励，请查收！', 'senclosure'=>'', 'reward_credits'=>0, 'reward_credits2'=>0, 'reward_titles'=>'', 'reward_achievements'=>'', 'boon_name'=>'');
	foreach(account_boon_stats() as $key=>$label) $defaults['boon_'.$key] = 0;
	foreach($defaults as $key=>$value) $defaults[$key] = account_reward_html(is_scalar($input[$key] ?? null) ? $input[$key] : $value);
	return $defaults;
}

function account_reward_describe($reward)
{
	$catalog = account_reward_catalog();
	$lines = array();
	if($reward['credits']) $lines['credits'] = $reward['credits'].' 积分';
	if($reward['credits2']) $lines['credits2'] = $reward['credits2'].' 切糕';
	foreach($reward['titles'] as $id) $lines['title_'.$id] = '头衔：'.$catalog['titles'][$id];
	foreach($reward['achievements'] as $id=>$level) $lines['achievement_'.$id] = '成就：'.($catalog['achievements'][$id]['name'][$level] ?? $catalog['achievements'][$id]['name'][$level-1]).'（阶段 '.$level.'）';
	if(isset($reward['boon'])) $lines['boon'] = '一次性赐福：'.account_boon_describe($reward['boon']);
	return $lines;
}

function account_boon_describe($boon)
{
	$parts = array();
	foreach($boon['stats'] as $key=>$value) $parts[] = account_boon_stats()[$key].' +'.$value;
	return $boon['name'].'（'.implode('、', $parts).'）';
}

function account_boons_ensure_storage()
{
	global $db, $gtablepre;
	static $ready = false;
	if($ready) return;
	$result = $db->query("SHOW COLUMNS FROM {$gtablepre}users LIKE 'boons'", 'SILENT');
	if(!$result) throw new RuntimeException('无法检查赐福字段。');
	if(!$db->num_rows($result)) {
		$lock = roommng_acquire_create_lock();
		if($lock === false) throw new RuntimeException('无法准备赐福字段。');
		try {
			$result = $db->query("SHOW COLUMNS FROM {$gtablepre}users LIKE 'boons'", 'SILENT');
			if(!$result || (!$db->num_rows($result) && !$db->query("ALTER TABLE {$gtablepre}users ADD boons MEDIUMTEXT NULL", 'SILENT'))) throw new RuntimeException('无法添加赐福字段。');
		} finally { roommng_release_create_lock($lock); }
	}
	$ready = true;
}

function account_boons_load($raw)
{
	if($raw === null || $raw === '') return array();
	$queue = json_decode($raw, true);
	if(!is_array($queue) || ($queue && array_keys($queue) !== range(0, count($queue)-1))) throw new RuntimeException('赐福队列格式无效。');
	foreach($queue as $boon) account_boon_validate($boon);
	return $queue;
}

// 已成功写入的角色与账户队首在同一条 UPDATE 中变更。重复调用不会再用下一份。
function account_boon_enter($uid, &$player)
{
	global $db, $gtablepre, $tablepre, $groomid;
	if((int)$groomid !== 0 || function_exists('ruleset_valid_page_hook') || !empty($player['type']) || empty($player['pid'])) return '';
	account_boons_ensure_storage();
	if(!$db->query("LOCK TABLES {$gtablepre}users WRITE, {$tablepre}players WRITE", 'SILENT')) throw new RuntimeException('无法读取入场赐福。');
	try {
		$user = $db->fetch_array($db->query("SELECT uid,username,boons FROM {$gtablepre}users WHERE uid=".(int)$uid));
		$row = $db->fetch_array($db->query("SELECT * FROM {$tablepre}players WHERE pid=".(int)$player['pid']));
		if(!$user || !$row || $row['name'] !== $user['username'] || (int)$row['type'] !== 0) return '';
		$para = get_clbpara($row['clbpara']);
		if(!empty($para['entry_boon'])) return '';
		$queue = account_boons_load($user['boons']);
		if(!$queue) return '';
		$boon = array_shift($queue);
		$changes = array();
		foreach($boon['stats'] as $key=>$amount) {
			$max = in_array($key, array('wp','wk','wg','wc','wd','wf'), true) ? 65535 : 4294967295;
			if($row[$key] > $max-$amount) throw new RuntimeException('赐福加成超过角色属性上限，赐福已保留。');
			$changes[$key] = (int)$row[$key] + $amount;
			if($key === 'mhp') $changes['hp'] = min($changes[$key], (int)$row['hp']+$amount);
			if($key === 'msp') $changes['sp'] = min($changes[$key], (int)$row['sp']+$amount);
		}
		$para['entry_boon'] = $boon;
		$changes['clbpara'] = json_encode($para, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
		$set = array();
		foreach($changes as $key=>$value) $set[] = "{$tablepre}players.`$key`=".account_reward_sql($value);
		$set[] = "{$gtablepre}users.boons=".account_reward_sql(json_encode($queue, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
		$sql = "UPDATE {$gtablepre}users, {$tablepre}players SET ".implode(',', $set)." WHERE {$gtablepre}users.uid=".(int)$uid." AND {$tablepre}players.pid=".(int)$row['pid'];
		if(!$db->query($sql, 'SILENT')) throw new RuntimeException('赐福未能生效，已保留至下次入场。');
		foreach($changes as $key=>$value) $player[$key] = $key === 'clbpara' && is_array($player[$key]) ? $para : $value;
		return account_boon_describe($boon);
	} finally { $db->query('UNLOCK TABLES'); }
}
