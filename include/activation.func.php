<?php
if(!defined('IN_GAME')) exit('Access Denied');
require_once GAME_ROOT.'./include/account_rewards.func.php';

function activation_ensure_storage()
{
	global $db, $gtablepre, $dbcharset;
	static $ready = false;
	if($ready) return;
	$sql = file_get_contents(GAME_ROOT.'./gamedata/sql/activation.sql');
	$charset = preg_match('/\A[a-zA-Z0-9_]+\z/', (string)$dbcharset) ? $dbcharset : 'utf8';
	foreach(explode(';', str_replace('bra_', $gtablepre, $sql)) as $statement) {
		if(trim($statement) !== '' && !$db->query(trim($statement).' DEFAULT CHARSET='.$charset, 'SILENT')) throw new RuntimeException('无法准备激活码数据表。');
	}
	$ready = true;
}

function activation_generate($input)
{
	global $db, $gtablepre, $now;
	activation_ensure_storage();
	$custom_code = $input['custom_code'] ?? '';
	if(!is_string($custom_code)) throw new InvalidArgumentException('指定激活码格式无效。');
	$once = account_reward_int($input['per_account_once'] ?? 1, 0, 1);
	$limited = account_reward_int($input['limited'] ?? 0, 0, 1);
	$uid = 0;
	if($custom_code !== '') {
		// 指定码始终不限账户，种子、绑定账户及批量数量不参与生成。
		$codes = array(activation_normalize_code($custom_code));
	} else {
		$seed = $input['seed'] ?? '';
		if(!is_string($seed) || strlen($seed) < 16 || strlen($seed) > 200) throw new InvalidArgumentException('种子需要 16～200 字节，建议使用页面随机生成的种子。');
		$count = account_reward_int($input['count'] ?? 1, 1, 200);
		$bound = $input['bound_username'] ?? '';
		if(!is_string($bound)) throw new InvalidArgumentException('绑定账户格式无效。');
		if(trim($bound) !== '') {
			$user = $db->fetch_array($db->query("SELECT uid,username FROM {$gtablepre}users WHERE username=".account_reward_sql(trim($bound))));
			if(!$user) throw new InvalidArgumentException('绑定的登录账户不存在。');
			$uid = (int)$user['uid']; $bound = $user['username'];
		} else $bound = '';
		$codes = array();
		for($i=1; $i<=$count; $i++) $codes[] = strtoupper(substr(hash_hmac('sha256', 'activation:'.$uid.':'.$bound.':'.$i, $seed), 0, 32));
	}
	list($title, $content) = account_reward_mail_text($input['stitle'] ?? '', $input['scontent'] ?? '');
	$enclosure = account_reward_encode(account_reward_from_form($input));
	if($enclosure === '') throw new InvalidArgumentException('激活码至少需要一项奖励。');
	if(!$db->query("LOCK TABLES {$gtablepre}activation_codes WRITE", 'SILENT')) throw new RuntimeException('激活码生成繁忙，请重试。');
	try {
		$values = array();
		foreach($codes as $code) {
			$old = $db->fetch_array($db->query("SELECT * FROM {$gtablepre}activation_codes WHERE code=".account_reward_sql($code)));
			if($old) {
				if((int)$old['bound_uid'] !== $uid || (int)$old['per_account_once'] !== $once || (int)$old['limited'] !== $limited || $old['title'] !== $title || $old['content'] !== $content || $old['enclosure'] !== $enclosure) throw new InvalidArgumentException($custom_code !== '' ? '此激活码已存在，不能覆盖奖励或使用规则，请更换指定码。' : '这个种子已用于其他奖励，请更换种子。');
				continue;
			}
			$values[] = '('.account_reward_sql($code).",$uid,$once,$limited,".account_reward_sql($title).','.account_reward_sql($content).','.account_reward_sql($enclosure).','.(int)$now.')';
		}
		if($values && !$db->query("INSERT INTO {$gtablepre}activation_codes (code,bound_uid,per_account_once,limited,title,content,enclosure,created_at) VALUES ".implode(',', $values), 'SILENT')) throw new RuntimeException('激活码未能保存。');
	} finally { $db->query('UNLOCK TABLES'); }
	return $codes;
}

function activation_normalize_code($code)
{
	if(!is_string($code) || strlen($code) > 256) throw new InvalidArgumentException('激活码格式无效。');
	// 仅转换 ASCII 字母，避免旧版 PHP 的系统语言设置改变中文字节。
	$code = strtr(trim($code, " \t\n\r"), 'abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
	// 旧的随机码仍可使用带空格或连字符的分组写法；短码中的连字符保留。
	$compact = preg_replace('/[\s-]+/', '', $code);
	if(preg_match('/\A[0-9A-F]{32}\z/', $compact)) return $compact;
	if(!preg_match('/\A[\x{4E00}-\x{9FFF}A-Z0-9_-]{1,32}\z/u', $code)) throw new InvalidArgumentException('激活码须为 1～32 个中文字、英文字母、数字、下划线或连字符。');
	return $code;
}

function activation_redeem($uid, $password, $code, $request_key)
{
	global $db, $gtablepre, $now;
	if(!is_string($request_key) || !preg_match('/\A[0-9a-f]{32}\z/', $request_key)) throw new InvalidArgumentException('兑换请求无效，请刷新页面。');
	$code = activation_normalize_code($code);
	$uid = (int)$uid;
	activation_ensure_storage();
	// 限量码必须覆盖跨账户的检查、发信和登记；现有部署可能使用 MyISAM。
	if(!$db->query("LOCK TABLES {$gtablepre}activation_codes WRITE, {$gtablepre}activation_uses WRITE, {$gtablepre}messages WRITE, {$gtablepre}users READ", 'SILENT')) throw new RuntimeException('兑换繁忙，请稍后重试。');
	try {
		$user = $db->fetch_array($db->query("SELECT uid,username,password,groupid FROM {$gtablepre}users WHERE uid=$uid"));
		if(!$user || (int)$user['groupid'] <= 0 || !hash_equals((string)$user['password'], (string)$password)) throw new InvalidArgumentException('登录状态已失效，请重新登录。');
		$row = $db->fetch_array($db->query("SELECT * FROM {$gtablepre}activation_codes WHERE code=".account_reward_sql($code)));
		if(!$row || ((int)$row['bound_uid'] && (int)$row['bound_uid'] !== $uid)) throw new InvalidArgumentException('激活码无效或不属于当前账户。');
		$id = (int)$row['id'];
		$previous = $db->fetch_array($db->query("SELECT code_id,mid FROM {$gtablepre}activation_uses WHERE uid=$uid AND request_key='$request_key'"));
		if($previous) {
			if((int)$previous['code_id'] !== $id) throw new InvalidArgumentException('此请求已处理，请刷新页面后输入其他激活码。');
			return '本次兑换已经处理，奖励邮件已发送，请前往邮箱查收。';
		}
		if(!$row['enabled']) throw new InvalidArgumentException('激活码已停用。');
		if($row['limited'] || $row['per_account_once']) {
			$where = $row['limited'] ? '' : " AND uid=$uid";
			$used = $db->query("SELECT id FROM {$gtablepre}activation_uses WHERE code_id=$id$where LIMIT 1");
			if($db->num_rows($used)) throw new InvalidArgumentException($row['limited'] ? '激活码已被使用并失效。' : '当前账户已经使用过这个激活码。');
		}
		account_reward_decode($row['enclosure']);
		$mid = message_create($user['username'], $row['title'], $row['content'], $row['enclosure']);
		$sql = "INSERT INTO {$gtablepre}activation_uses (code_id,uid,request_key,mid,used_at) VALUES ($id,$uid,'$request_key',".(int)$mid.','.(int)$now.')';
		if(!$db->query($sql, 'SILENT')) {
			// 发信失败不登记次数；登记失败撤回尚未对收件人可见的新邮件。
			$db->query("DELETE FROM {$gtablepre}messages WHERE mid=".(int)$mid);
			throw new RuntimeException('兑换未能保存，请重试。');
		}
		return '兑换成功，奖励已通过邮件发送，请前往邮箱查收。';
	} finally { $db->query('UNLOCK TABLES'); }
}
