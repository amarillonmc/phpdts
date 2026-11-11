<?php
// php -d short_open_tag=1 gamedata/tests/account_rewards_test.php --local-db
// 只操作随机前缀的临时测试表，不载入 common.inc.php，不修改站点账户或对局。
if(PHP_SAPI !== 'cli' || !in_array($argv[1] ?? '', array('--local-db','--worker'), true)) exit("Use --local-db to test the configured local database.\n");
define('IN_GAME', true);
define('GAME_ROOT', dirname(__DIR__, 2).'/');
require getenv('PHPDTS_REWARD_TEST_CONFIG') ?: GAME_ROOT.'config.inc.php';
if(!preg_match('/\A(localhost|127\.0\.0\.1|::1)(:\d+)?\z/', $dbhost)) exit("Only a local test database is supported.\n");
require GAME_ROOT.'include/db_mysqli.class.php';
require GAME_ROOT.'include/global.func.php';
require GAME_ROOT.'include/user.func.php';
require GAME_ROOT.'include/messages.func.php';
require GAME_ROOT.'include/activation.func.php';
function roommng_acquire_create_lock() { return fopen('php://temp', 'r+'); }
function roommng_release_create_lock($lock) { if(is_resource($lock)) fclose($lock); }
class RewardTestDb extends dbstuff {
	public $fail_next = '';
	function query($sql, $type='') {
		if($this->fail_next !== '' && strpos($sql,$this->fail_next) === 0) { $this->fail_next = ''; return false; }
		return parent::query($sql,$type);
	}
}
function reward_assert($ok, $label) { if(!$ok) throw new RuntimeException('FAIL: '.$label); echo "PASS: $label\n"; }
function reward_reject($callback, $label) {
	try { $callback(); } catch(InvalidArgumentException $e) { reward_assert(true, $label); return; }
	throw new RuntimeException('FAIL: '.$label);
}
function reward_user($uid=1) {
	global $db,$gtablepre;
	return $db->fetch_array($db->query("SELECT * FROM {$gtablepre}users WHERE uid=".(int)$uid));
}
function reward_count($table) {
	global $db,$gtablepre;
	return (int)$db->fetch_array($db->query("SELECT COUNT(*) AS n FROM {$gtablepre}$table"))['n'];
}
function reward_code($more=array()) {
	return activation_generate(array_merge(array('seed'=>bin2hex(random_bytes(16)), 'count'=>1, 'stitle'=>'测试奖励', 'scontent'=>"引号 ' 与 \\ 路径 <标签>", 'reward_credits'=>10), $more))[0];
}
function reward_claim($ids, $uid=1) {
	global $udata,$info;
	$udata = format_userdata(reward_user($uid)); $info = array();
	return message_check($ids, array());
}
function reward_player() {
	global $db,$tablepre;
	$db->query("INSERT INTO {$tablepre}players (name,type,hp,mhp,sp,msp,att,def,money,clbpara) VALUES ('RewardOne',0,400,400,400,400,100,100,20,'{}')");
	return $db->fetch_array($db->query("SELECT * FROM {$tablepre}players WHERE pid=".(int)$db->insert_id()));
}
function reward_parallel($action, $args) {
	global $gtablepre;
	$workers = array();
	for($i=0;$i<2;$i++) {
		$command = array_merge(array(PHP_BINARY,'-d','short_open_tag=1',__FILE__,'--worker',$gtablepre,$action), $args[$i]);
		$proc = proc_open($command, array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')), $pipes);
		if(!is_resource($proc)) throw new RuntimeException('Cannot start test worker.');
		fclose($pipes[0]); $workers[] = array($proc,$pipes);
	}
	$out = array();
	foreach($workers as $worker) {
		$out[] = trim(stream_get_contents($worker[1][1])); $error = stream_get_contents($worker[1][2]);
		fclose($worker[1][1]); fclose($worker[1][2]);
		if(proc_close($worker[0]) !== 0 || $error !== '') throw new RuntimeException('Worker failed: '.$error);
	}
	sort($out); return $out;
}
function reward_endpoint($entry, $post) {
	global $gtablepre;
	$fixture = sys_get_temp_dir().'/phpdts-reward-endpoint-'.bin2hex(random_bytes(6));
	mkdir($fixture); mkdir($fixture.'/include');
	$cache_id = random_int(800000,899999);
	$bootstrap = '<?php define("IN_GAME",true); define("GAME_ROOT",'.var_export(GAME_ROOT,true).'); ob_start();' . "\n";
	$bootstrap .= 'require '.var_export(getenv('PHPDTS_REWARD_TEST_CONFIG') ?: GAME_ROOT.'config.inc.php',true).';' . "\n";
	$bootstrap .= '$gtablepre=$tablepre='.var_export($gtablepre,true).'; define("TEMPLATEID",'.$cache_id.'); define("TPLDIR","./templates/default");' . "\n";
	$bootstrap .= <<<'PHP'
require GAME_ROOT.'include/db_mysqli.class.php';
require GAME_ROOT.'include/global.func.php';
require GAME_ROOT.'include/user.func.php';
require GAME_ROOT.'include/messages.func.php';
set_error_handler(function($severity,$message,$file,$line){throw new ErrorException($message,0,$severity,$file,$line);});
$db=new dbstuff(); $db->connect($dbhost,$dbuser,$dbpw,$dbname,0);
$now=1800000000; $groomid=0; $tplrefresh=1; $magic_quotes_gpc=false;
$cuser='RewardOne'; $cpass='test-pass';
$udata=format_userdata($db->fetch_array($db->query("SELECT * FROM {$gtablepre}users WHERE uid=1")));
$_SERVER['REQUEST_METHOD']='POST';
require './request.php';
extract(gstrfilter($_POST));
PHP;
	file_put_contents($fixture.'/include/common.inc.php',$bootstrap);
	file_put_contents($fixture.'/request.php','<?php $_POST='.var_export($post,true).';');
	foreach(array('masterslave.func.php','messages.func.php') as $file) file_put_contents($fixture.'/include/'.$file,'<?php require_once '.var_export(GAME_ROOT.'include/'.$file,true).';');
	try {
		$proc=proc_open(array(PHP_BINARY,'-d','short_open_tag=1',GAME_ROOT.$entry),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes,$fixture);
		if(!is_resource($proc)) throw new RuntimeException('Cannot start endpoint fixture.');
		fclose($pipes[0]); $output=stream_get_contents($pipes[1]); $error=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
		$status=proc_close($proc);
		if($status !== 0 || $error !== '') throw new RuntimeException('Endpoint failed: '.$error);
		$response=json_decode($output,true);
		if(!is_array($response)) throw new RuntimeException('Endpoint did not return JSON: '.$output);
		return $response;
	} finally {
		foreach(array('include/common.inc.php','include/masterslave.func.php','include/messages.func.php','request.php') as $file) unlink($fixture.'/'.$file);
		rmdir($fixture.'/include'); rmdir($fixture);
		$cache=GAME_ROOT.'gamedata/templates/'.$cache_id.'_messages.tpl.php'; if(file_exists($cache)) unlink($cache);
	}
}
$connection_check = @mysqli_connect($dbhost,$dbuser,$dbpw,$dbname);
if(!$connection_check) { fwrite(STDERR, "Cannot connect to the configured local test database.\n"); exit(1); }
mysqli_close($connection_check);
$db = new RewardTestDb(); $db->connect($dbhost,$dbuser,$dbpw,$dbname,0);
$now = 1800000000; $groomid = 0;
$gtablepre = ($argv[1] === '--worker') ? ($argv[2] ?? '') : 'rewardtest_'.bin2hex(random_bytes(6)).'_';
if(!preg_match('/\Arewardtest_[0-9a-f]{12}_\z/', $gtablepre)) exit("Invalid test prefix.\n");
$tablepre = $gtablepre;
if($argv[1] === '--worker') {
	try {
		if($argv[3] === 'redeem') { activation_redeem((int)$argv[4], 'test-pass', $argv[5], $argv[6]); echo 'ok'; }
		elseif($argv[3] === 'claim') echo reward_claim(array((int)$argv[4]));
		elseif($argv[3] === 'boon') {
			$p = $db->fetch_array($db->query("SELECT * FROM {$tablepre}players WHERE pid=".(int)$argv[4]));
			echo account_boon_enter(1, $p) !== '' ? '1' : '0';
		}
	} catch(InvalidArgumentException $e) { echo 'reject'; }
	exit;
}
$created = array('users','messages','del_messages','activation_codes','activation_uses','players');
try {
	$db->query("CREATE TABLE {$gtablepre}users (uid INT PRIMARY KEY, username VARCHAR(60), password VARCHAR(64), groupid INT, credits INT DEFAULT 0, credits2 MEDIUMINT DEFAULT 0, nicksrev TEXT, achrev TEXT) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4");
	$db->query("INSERT INTO {$gtablepre}users (uid,username,password,groupid,nicksrev,achrev) VALUES (1,'RewardOne','test-pass',1,'{\"nicks\":[0]}','{}'),(2,'RewardTwo','test-pass',1,'{\"nicks\":[0]}','{}')");
	create_messages_db();
	account_boons_ensure_storage(); activation_ensure_storage(); activation_ensure_storage();
	$db->query("CREATE TABLE {$tablepre}players (pid INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(60),type INT,hp INT UNSIGNED,mhp INT UNSIGNED,sp INT UNSIGNED,msp INT UNSIGNED,att INT UNSIGNED,def INT UNSIGNED,money INT UNSIGNED,clbpara TEXT) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4");
	$fields = account_reward_from_form(array('reward_credits'=>'100','reward_credits2'=>'20','reward_titles'=>'1,2','reward_achievements'=>'0:1','boon_name'=>'初行','boon_mhp'=>'50','boon_att'=>'10'));
	reward_assert($fields['credits'] === 100 && $fields['boon']['stats']['mhp'] === 50, '后台奖励表单可组合五种奖励');
	reward_reject(function(){ account_reward_from_form(array('reward_titles'=>'999999')); }, '未知头衔被拒绝');
	reward_reject(function(){ account_reward_from_form(array('reward_achievements'=>'0:999')); }, '非法成就阶段被拒绝');
	reward_reject(function(){ account_reward_from_form(array('boon_name'=>'空赐福')); }, '没有属性的赐福被拒绝');
	reward_reject(function(){ account_reward_from_form(array('reward_credits'=>'-1')); }, '负数奖励被拒绝');
	reward_reject(function(){ account_reward_mail_text(str_repeat('"',100),''); }, '转义后过长的标题不会被数据库截断');
	$code = reward_code(); $request = bin2hex(random_bytes(16));
	activation_redeem(1,'test-pass',$code,$request);
	activation_redeem(1,'test-pass',$code,$request);
	reward_assert(reward_count('messages') === 1, '相同请求重放只产生一封邮件');
	create_messages_db();
	reward_assert(reward_count('messages') === 1 && (int)$db->fetch_array($db->query("SELECT timestamp FROM {$gtablepre}messages LIMIT 1"))['timestamp'] === $now, '邮件建表可重入且时间正确保存');
	reward_reject(function()use($code){ activation_redeem(1,'test-pass',$code,bin2hex(random_bytes(16))); }, '每账户一次禁止再次兑换');
	activation_redeem(2,'test-pass',$code,bin2hex(random_bytes(16)));
	reward_assert(reward_count('messages') === 2, '非限量通用码可供另一账户使用');
	$db->query("DELETE FROM {$gtablepre}messages WHERE receiver='RewardOne'");
	reward_reject(function()use($code){ activation_redeem(1,'test-pass',$code,bin2hex(random_bytes(16))); }, '永久删除邮件也不能重领一次码');
	$bound = reward_code(array('bound_username'=>'RewardOne','per_account_once'=>0));
	reward_reject(function()use($bound){ activation_redeem(2,'test-pass',$bound,bin2hex(random_bytes(16))); }, '绑定码拒绝其他登录账户');
	activation_redeem(1,'test-pass',$bound,bin2hex(random_bytes(16)));
	activation_redeem(1,'test-pass',$bound,bin2hex(random_bytes(16)));
	reward_assert(reward_count('activation_uses') === 4, '绑定账户与可重复使用可组合');
	$limited = reward_code(array('limited'=>1,'per_account_once'=>0));
	$result = reward_parallel('redeem', array(array('1',$limited,bin2hex(random_bytes(16))),array('2',$limited,bin2hex(random_bytes(16)))));
	reward_assert($result === array('ok','reject'), '两个账户并发兑换限量码，仅一方成功');
	$seed = array('seed'=>str_repeat('a',32),'count'=>2,'stitle'=>'重复生成','scontent'=>'','reward_credits'=>1);
	$first = activation_generate($seed); $before = reward_count('activation_codes');
	reward_assert(activation_generate($seed) === $first && reward_count('activation_codes') === $before, '相同种子重复提交重现代码且不重复建码');
	reward_reject(function()use($seed){ activation_generate(array_merge($seed,array('reward_credits'=>2))); }, '已有代码不能换奖励覆盖');
	$db->query("UPDATE {$gtablepre}activation_codes SET enabled=0 WHERE code='{$first[0]}'");
	reward_reject(function()use($first){ activation_redeem(1,'test-pass',$first[0],bin2hex(random_bytes(16))); }, '停用码不能兑换');
	$mid = message_create('RewardOne','组合奖励','',account_reward_encode($fields));
	reward_assert(reward_claim(array($mid,$mid)) === 1, '一封混合附件只领取一次');
	$u = reward_user(); $ach = json_decode($u['achrev'],true); $titles = json_decode($u['nicksrev'],true);
	reward_assert((int)$u['credits'] === 100 && (int)$u['credits2'] === 20 && $ach[0]['l'] === 1 && in_array(1,$titles['nicks']) && in_array(2,$titles['nicks']) && count(account_boons_load($u['boons'])) === 1, '重新读库确认积分、切糕、成就、多个头衔和赐福均持久化');
	reward_assert(reward_claim(array($mid)) === 0, '已领取邮件再次提交不发奖');
	$m1 = message_create('RewardOne','','','gettitle_3'); $m2 = message_create('RewardOne','','','getqiegao_7');
	reward_claim(array($m1,$m2));
	reward_assert(in_array(3,json_decode(reward_user()['nicksrev'],true)['nicks']), '头衔邮件后接无头衔邮件，头衔仍正确保存');
	$m1 = message_create('RewardOne','','','gettitle_4'); $m2 = message_create('RewardOne','','','gettitle_4');
	reward_claim(array($m1,$m2));
	reward_assert((int)reward_user()['credits2'] === 77, '批量领取相同头衔，第二份转为 50 切糕');
	$other = message_create('RewardTwo','','','getqiegao_99');
	reward_assert(reward_claim(array($other)) === 0, '伪造他人的邮件编号不能领取');
	$race = message_create('RewardOne','','','getcredits_13');
	reward_assert(reward_parallel('claim',array(array((string)$race),array((string)$race))) === array('0','1') && (int)reward_user()['credits'] === 113, '并发领取同一邮件只增加一次积分');
	$second = $fields; $second['boon']['name'] = '再行'; $second['boon']['stats'] = array('def'=>15); $second['credits'] = $second['credits2'] = 0; $second['titles'] = $second['achievements'] = array();
	$mid = message_create('RewardOne','','',account_reward_encode($second)); reward_claim(array($mid));
	$p = reward_player(); $groomid = 1;
	reward_assert(account_boon_enter(1,$p) === '' && count(account_boons_load(reward_user()['boons'])) === 2, '小房间不使用待用赐福');
	$groomid = 0; $bad = array('pid'=>999999,'type'=>0);
	reward_assert(account_boon_enter(1,$bad) === '' && count(account_boons_load(reward_user()['boons'])) === 2, '尚未写入角色的失败入场不消耗赐福');
	reward_assert(reward_parallel('boon',array(array((string)$p['pid']),array((string)$p['pid']))) === array('0','1'), '并发入场奖励调用仅使用队首一份');
	$row = $db->fetch_array($db->query("SELECT * FROM {$tablepre}players WHERE pid=".(int)$p['pid']));
	reward_assert((int)$row['hp'] === 450 && (int)$row['mhp'] === 450 && (int)$row['att'] === 110 && count(account_boons_load(reward_user()['boons'])) === 1, '队首赐福属性与消耗一起保存');
	$p = reward_player(); account_boon_enter(1,$p);
	reward_assert((int)$p['def'] === 115 && (int)$p['hp'] === 400 && !account_boons_load(reward_user()['boons']), '下次入场使用第二份，第一份不再生效');
	$db->query("UPDATE {$gtablepre}users SET credits2=8388607 WHERE uid=1");
	$mid = message_create('RewardOne','','','getqiegao_1');
	reward_assert(reward_claim(array($mid)) === 0 && (int)$db->fetch_array($db->query("SELECT checked FROM {$gtablepre}messages WHERE mid=$mid"))['checked'] === 0, '余额溢出时保留未领取邮件');
	// 验证同一实现也适用于 InnoDB 部署。
	foreach(array('users','messages','players') as $table) $db->query("ALTER TABLE {$gtablepre}$table ENGINE=InnoDB");
	$mid = message_create('RewardOne','','','getcredits_1');
	reward_assert(reward_claim(array($mid)) === 1 && (int)reward_user()['credits'] === 114, 'InnoDB 同样正确领取并保存');
	foreach(array(0,1) as $bound_flag) foreach(array(0,1) as $once_flag) foreach(array(0,1) as $limited_flag) {
		$combo = reward_code(array('bound_username'=>$bound_flag ? 'RewardOne' : '', 'per_account_once'=>$once_flag, 'limited'=>$limited_flag));
		activation_redeem(1,'test-pass',$combo,bin2hex(random_bytes(16)));
		foreach(array(1,2) as $uid) {
			$blocked = $limited_flag || ($uid === 1 ? $once_flag : $bound_flag);
			try { activation_redeem($uid,'test-pass',$combo,bin2hex(random_bytes(16))); $accepted = true; }
			catch(InvalidArgumentException $e) { $accepted = false; }
			if($accepted === (bool)$blocked) throw new RuntimeException('Activation combination failed.');
		}
	}
	reward_assert(true, '绑定、每账户次数、全服限量的全部八种组合');
	foreach(array(0,1) as $once_flag) foreach(array(0,1) as $limited_flag) {
		$custom_input = array('custom_code'=>'新手福利-'.$once_flag.$limited_flag, 'seed'=>'', 'count'=>200, 'bound_username'=>'不存在的账户', 'per_account_once'=>$once_flag, 'limited'=>$limited_flag);
		$before_codes = reward_count('activation_codes');
		$custom = reward_code($custom_input);
		$row = $db->fetch_array($db->query("SELECT * FROM {$gtablepre}activation_codes WHERE code=".account_reward_sql($custom)));
		reward_assert($custom === $custom_input['custom_code'] && (int)$row['bound_uid'] === 0 && reward_count('activation_codes') === $before_codes+1, '中文指定码只生成一个且不绑定账户 '.$once_flag.$limited_flag);
		$token = bin2hex(random_bytes(16)); $before_mail = reward_count('messages');
		activation_redeem(1,'test-pass',$custom,$token); activation_redeem(1,'test-pass',$custom,$token);
		reward_assert(reward_count('messages') === $before_mail+1, '指定码重复提交只发一封邮件 '.$once_flag.$limited_flag);
		reward_assert(reward_code($custom_input) === $custom && reward_count('activation_codes') === $before_codes+1, '指定码重复生成不重置使用记录 '.$once_flag.$limited_flag);
		foreach(array(1,2) as $uid) {
			$blocked = $limited_flag || ($uid === 1 && $once_flag);
			try { activation_redeem($uid,'test-pass',$custom,bin2hex(random_bytes(16))); $accepted = true; }
			catch(InvalidArgumentException $e) { $accepted = false; }
			reward_assert($accepted !== (bool)$blocked, '指定码次数规则 '.$once_flag.$limited_flag.' 账户 '.$uid);
		}
	}
	$custom_input = array('custom_code'=>'vip777', 'stitle'=>'字母指定码', 'scontent'=>'', 'reward_credits'=>1);
	$vip = activation_generate($custom_input);
	reward_assert($vip === array('VIP777'), '指定码无需种子且英文字母统一大写');
	activation_redeem(1,'test-pass','  vIp777  ',bin2hex(random_bytes(16)));
	reward_reject(function(){ activation_redeem(1,'test-pass','VIP777',bin2hex(random_bytes(16))); }, '改变字母大小写不能绕过单次限制');
	$before_codes = reward_count('activation_codes');
	reward_assert(activation_generate(array_merge($custom_input,array('custom_code'=>'VIP777'))) === $vip && reward_count('activation_codes') === $before_codes, '大小写不同的指定码不会重复生成');
	foreach(array('reward_credits'=>2,'per_account_once'=>0,'limited'=>1) as $key=>$value) {
		reward_reject(function()use($custom_input,$key,$value){ activation_generate(array_merge($custom_input,array($key=>$value))); }, '指定码冲突不覆盖 '.$key);
	}
	$db->query("UPDATE {$gtablepre}activation_codes SET enabled=0 WHERE code=".account_reward_sql($vip[0]));
	activation_generate($custom_input);
	reward_reject(function()use($vip){ activation_redeem(2,'test-pass',$vip[0],bin2hex(random_bytes(16))); }, '重新生成已停用指定码不会恢复兑换');
	reward_reject(function()use($bound){ reward_code(array('custom_code'=>$bound)); }, '指定码不能覆盖已有绑定码');
	$hyphen = reward_code(array('custom_code'=>'vip-777'));
	activation_redeem(1,'test-pass',$hyphen,bin2hex(random_bytes(16)));
	reward_assert($hyphen === 'VIP-777', '短码中的连字符保留，不会指向另一个代码');
	$long_code = str_repeat('福',32);
	reward_assert(reward_code(array('custom_code'=>$long_code)) === $long_code, '32 个中文字完整保存');
	activation_redeem(1,'test-pass',$long_code,bin2hex(random_bytes(16)));
	foreach(array(' ',str_repeat('福',33),"vip' OR 1=1",'<script>',"vip\0",'vip 777',array('vip777'),"\xff") as $bad_code) {
		reward_reject(function()use($bad_code){ reward_code(array('custom_code'=>$bad_code)); }, '非法指定码被拒绝');
		reward_reject(function()use($bad_code){ activation_redeem(1,'test-pass',$bad_code,bin2hex(random_bytes(16))); }, '非法兑换码被拒绝');
	}
	$legacy = reward_code();
	activation_redeem(1,'test-pass',implode(' - ',str_split(strtolower($legacy),4)),bin2hex(random_bytes(16)));
	reward_assert(true, '原随机码的小写及带分隔符输入继续可用');
	$custom_limited = reward_code(array('custom_code'=>'shared-once', 'limited'=>1, 'per_account_once'=>0));
	reward_assert(reward_parallel('redeem',array(array('1',$custom_limited,bin2hex(random_bytes(16))),array('2',$custom_limited,bin2hex(random_bytes(16))))) === array('ok','reject'), '指定码跨账户并发限量只发送一封奖励邮件');
	$failure_code = reward_code(); $failure_token = bin2hex(random_bytes(16));
	$old_mail_count = reward_count('messages'); $old_use_count = reward_count('activation_uses');
	foreach(array('messages','activation_uses') as $table) {
		$db->fail_next = "INSERT INTO {$gtablepre}$table";
		try { activation_redeem(1,'test-pass',$failure_code,$failure_token); throw new LogicException('Failure was ignored.'); }
		catch(RuntimeException $e) {}
		reward_assert(reward_count('messages') === $old_mail_count && reward_count('activation_uses') === $old_use_count, $table.' 写入失败时不发奖、不消耗兑换次数');
	}
	activation_redeem(1,'test-pass',$failure_code,$failure_token);
	reward_assert(reward_count('activation_uses') === $old_use_count+1, '失败后原请求可以重试成功');
	$fields['credits'] = $fields['credits2'] = 0; $fields['titles'] = $fields['achievements'] = array();
	$mid = message_create('RewardOne','','',account_reward_encode($fields)); reward_claim(array($mid));
	$p = reward_player();
	$db->fail_next = "UPDATE {$gtablepre}users, {$tablepre}players";
	try { account_boon_enter(1,$p); throw new LogicException('Failure was ignored.'); } catch(RuntimeException $e) {}
	reward_assert(count(account_boons_load(reward_user()['boons'])) === 1, '赐福保存失败不消耗队列');
	account_boon_enter(1,$p);
	reward_assert(!account_boons_load(reward_user()['boons']), '同一角色赐福保存重试仅消耗一份');
	$endpoint_code = reward_code(array('custom_code'=>'账户入口福利', 'reward_credits'=>17));
	$endpoint_post = array('mode'=>'redeem','activation_code'=>$endpoint_code,'activation_request'=>bin2hex(random_bytes(16)),'reward_csrf'=>hash_hmac('sha256','activation:1','test-pass'));
	$response = reward_endpoint('user.php',$endpoint_post);
	reward_assert(strpos($response['innerHTML']['activation_info'],'兑换成功') !== false && strlen($response['value']['activation_request']) === 32, '实际账户入口返回兑换结果并刷新请求凭据');
	$count_before = reward_count('messages'); reward_endpoint('user.php',$endpoint_post);
	reward_assert(reward_count('messages') === $count_before, '实际账户入口重放 POST 不重复发邮件');
	$mid = (int)$db->fetch_array($db->query("SELECT mid FROM {$gtablepre}activation_uses WHERE request_key='{$endpoint_post['activation_request']}'"))['mid'];
	$post = array('mode'=>'check','sl'.$mid=>'1','reward_csrf'=>'wrong');
	$before = (int)reward_user()['credits']; reward_endpoint('messages.php',$post);
	reward_assert((int)reward_user()['credits'] === $before, '实际邮件入口拒绝伪造的页面凭据');
	$post['reward_csrf'] = hash_hmac('sha256','messages:1','test-pass');
	$response = reward_endpoint('messages.php',$post);
	reward_assert((int)reward_user()['credits'] === $before+17 && strpos($response['innerHTML']['messages'],'附件已收') !== false, '实际邮件入口领取后返回更新过的附件页面');
	$post['mode']='del'; reward_endpoint('messages.php',$post);
	$deleted_mid=(int)$db->fetch_array($db->query("SELECT MAX(mid) AS mid FROM {$gtablepre}del_messages"))['mid'];
	reward_endpoint('messages.php',array('mode'=>'recover','sl'.$deleted_mid=>'1','reward_csrf'=>$post['reward_csrf']));
	$restored=(int)$db->fetch_array($db->query("SELECT MAX(mid) AS mid FROM {$gtablepre}messages"))['mid'];
	reward_endpoint('messages.php',array('mode'=>'check','sl'.$restored=>'1','reward_csrf'=>$post['reward_csrf']));
	reward_assert((int)reward_user()['credits'] === $before+17, '实际删除、恢复、重领流程保留已领取状态');
	echo "All account reward database tests passed.\n";
} finally {
	$db->query('UNLOCK TABLES');
	foreach($created as $table) $db->query("DROP TABLE IF EXISTS {$gtablepre}$table");
}
