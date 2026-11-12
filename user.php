<?php

define('CURSCRIPT', 'user');

require './include/common.inc.php';
require './include/masterslave.func.php';
require_once GAME_ROOT.'./include/paperdoll.func.php';
require_once GAME_ROOT.'./include/activation.func.php';
require_once GAME_ROOT.'./include/talent.func.php';
//require './include/user.func.php';

// 形象编辑使用独立 JSON 请求，登录失效时也返回可读的 JSON 错误。
if(($mode ?? '') === 'paperdoll' && (!is_string($cuser) || $cuser === '' || !is_string($cpass) || $cpass === '' || empty($udata) || !hash_equals((string)$udata['password'], $cpass) || (int)$udata['groupid'] <= 0)) {
	ob_clean();
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store');
	http_response_code(401);
	echo paperdoll_json(array('error' => '登录状态已失效，请重新登录。'));
	exit;
}

if(!$cuser||!$cpass) { gexit($_ERROR['no_login'],__file__,__line__); }
if(!$udata) { gexit($_ERROR['login_check'],__file__,__line__); }
if($udata['password'] != $cpass) { gexit($_ERROR['wrong_pw'], __file__, __line__); }
if($udata['groupid'] <= 0) { gexit($_ERROR['user_ban'], __file__, __line__); }

if(!isset($mode)){
	$mode = 'show';
}

$talent_notice = '';
if($mode === 'talent' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
	try {
		$expected = hash_hmac('sha256','talent:'.$udata['uid'],(string)$cpass);
		if(!isset($_POST['talent_csrf']) || !is_string($_POST['talent_csrf']) || !hash_equals($expected,$_POST['talent_csrf'])) throw new InvalidArgumentException('页面校验已失效，请刷新后重试。');
		$talent_notice = talent_save_action($udata['uid'],(string)$cpass,$_POST);
	} catch(Throwable $e) {
		$talent_notice = $e instanceof InvalidArgumentException ? $e->getMessage() : '天赋暂时无法保存，原有记录已保留。';
		if(!($e instanceof InvalidArgumentException)) error_log('Talent: '.$e->getMessage());
	}
	$udata = fetch_userdata_by_username($cuser);
}

if($mode == 'redeem') {
	$gamedata = array('innerHTML'=>array('activation_info'=>''));
	try {
		account_reward_post_check('activation', $_POST);
		$gamedata['innerHTML']['activation_info'] = account_reward_html(activation_redeem($udata['uid'], $cpass, $_POST['activation_code'] ?? '', $_POST['activation_request'] ?? ''));
		$gamedata['value']['activation_request'] = bin2hex(random_bytes(16));
		$gamedata['value']['activation_code'] = '';
	} catch(Throwable $e) {
		$gamedata['innerHTML']['activation_info'] = account_reward_html($e->getMessage());
		if(!($e instanceof InvalidArgumentException)) error_log('Activation: '.$e->getMessage());
	}
	ob_clean();
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store');
	echo compatible_json_encode($gamedata);
	exit;
} elseif($mode == 'paperdoll') {
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store');
	ob_clean();
	try {
		if(($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') throw new InvalidArgumentException('请从账户页面保存形象。');
		$raw = file_get_contents('php://input', false, null, 0, 8193);
		if(strlen($raw) > 8192) throw new InvalidArgumentException('形象设置过长。');
		$input = json_decode($raw, true);
		$csrf = hash_hmac('sha256', 'paperdoll:'.$udata['uid'], (string)$cpass);
		if(!is_array($input) || !isset($input['csrf']) || !is_string($input['csrf']) || !hash_equals($csrf, $input['csrf'])) throw new InvalidArgumentException('页面校验已失效，请刷新。');
		paperdoll_ensure_storage();
		$paperdoll_user = paperdoll_current_user($udata['uid'], $cpass);
		echo paperdoll_json(paperdoll_save($paperdoll_user, $input));
	} catch(InvalidArgumentException $e) {
		http_response_code(409);
		echo paperdoll_json(array('error' => $e->getMessage()));
	} catch(Throwable $e) {
		error_log('Paperdoll: '.$e->getMessage());
		http_response_code(503);
		echo paperdoll_json(array('error' => '形象暂时无法保存，请稍后重试。'));
	}
	exit;
} elseif($mode == 'sync_master') {
	// 处理主从数据同步
	$gamedata=Array();$gamedata['innerHTML']['info'] = '';

	if($slave_level >= 1 && !empty($master_server_name)) {
		if(!empty($master_username) && !empty($master_password)) {
			$sync_result = sync_user_from_master($master_username, md5($master_password), $cuser);
			$gamedata['innerHTML']['info'] .= $sync_result['message'] . '<br>';
		} else {
			$gamedata['innerHTML']['info'] .= '请输入主服务器的用户名和密码<br>';
		}
	} else {
		$gamedata['innerHTML']['info'] .= '当前服务器不是从服务器或未配置主服务器信息<br>';
	}

	ob_clean();
	$jgamedata = compatible_json_encode($gamedata);
	echo $jgamedata;
	ob_end_flush();

} elseif($mode == 'reverse_migrate') {
	// 处理反向迁移
	$gamedata=Array();$gamedata['innerHTML']['info'] = '';

	if(is_reverse_migration_mode() && !empty($master_server_name)) {
		if(!empty($remote_username) && !empty($remote_password)) {
			$migrate_result = reverse_migrate_user($cuser, $remote_username, md5($remote_password));
			$gamedata['innerHTML']['info'] .= $migrate_result['message'] . '<br>';
		} else {
			$gamedata['innerHTML']['info'] .= '请输入远端从服务器的用户名和密码<br>';
		}
	} else {
		$gamedata['innerHTML']['info'] .= '当前服务器不是反向迁移模式或未配置目标服务器信息<br>';
	}

	ob_clean();
	$jgamedata = compatible_json_encode($gamedata);
	echo $jgamedata;
	ob_end_flush();

} elseif($mode == 'edit') {
	$gamedata=Array();$gamedata['innerHTML']['info'] = '';
	if($opass && $npass && $rnpass){
		$pass_right = true;
		$pass_check = pass_check($npass,$rnpass);
		if($pass_check!='pass_ok'){
			$gamedata['innerHTML']['info'] .= $_ERROR[$pass_check].'<br />';
			$pass_right = false;
		}
		$opass = md5($opass);
		$npass = md5($npass);
		if($opass != $udata['password']){
			$gamedata['innerHTML']['info'] .= $_ERROR['wrong_pw'].'<br />';
			$pass_right = false;
		}
		if($pass_right){
			gsetcookie('pass',$npass);
			$passqry = "`password` ='$npass',";
			$gamedata['innerHTML']['info'] .= $_INFO['pass_success'].'<br />';
		}else{
			$passqry = '';
			$gamedata['innerHTML']['info'] .= $_INFO['pass_failure'].'<br />';
		}
	}else{
		$passqry = '';
		$gamedata['innerHTML']['info'] .= $_INFO['pass_failure'].'<br />';
	}
	$credits = $udata['credits'];$credits2 = $udata['credits2'];
	/*if($exchg12||$exchg21){
		//if(!is_numeric($exchg12)||$exchg12<0){$gamedata['innerHTML']['info'] .= $_INFO['credits_failure'];}
		if(!is_numeric($exchg12)||!is_numeric($exchg21)||$exchg12<0||$exchg21<0){$gamedata['innerHTML']['info'] .= $_INFO['credits_failure'];}
		elseif($exchg12 && $exchg21){$gamedata['innerHTML']['info'] .= $_INFO['credits_conflicts'];}
		else{
			if($exchg12){
				$exchg12 = ceil($exchg12);
				if($exchg12>$udata['credits']){$gamedata['innerHTML']['info'] .= $_INFO['credits_failure2'];}
				elseif($exchg12 % 100){$gamedata['innerHTML']['info'] .= $_INFO['credits_failure3'];}
				elseif($exchg12 > $credits/5){$gamedata['innerHTML']['info'] .= '不允许一次转换超过20%的积分！';}
				else{
					$credits -= $exchg12;
					$credits2 += $exchg12/100;
					$gamedata['innerHTML']['info'] .= $_INFO['credits_success'];
				}
			}elseif($exchg21){
				$exchg21 = ceil($exchg21);
				if($exchg21 > $credits2){$gamedata['innerHTML']['info'] .= $_INFO['credits_failure2'];}
				else{
					$credits2 -= $exchg21;
					$credits += $exchg21*75;
					$gamedata['innerHTML']['info'] .= $_INFO['credits_success'];
				}
			}
		}
	}*/
	# 头像编辑
	if ($icon>$iconlimit) $icon=0;
	# 入场音量编辑
	$volume = round($volume/100,2); $volume = round(min(1,max(0,$volume)),2);
	gsetcookie('volume',$volume,86400*30,0);
	# 切换用户界面
	if(!empty($templateid))
	{
		// 支持的模板ID: 0=默认, 1=LULUXIA(未开放), 2=NOUVEAU
		if($templateid == 1) {
			# LULUXIA模板暂时只允许管理员账户切换
			if($udata['groupid'] < 9)
			{
				$templateid = 0;
				$gamedata['innerHTML']['info'] .= '界面切换失败，LULUXIA界面暂未实装。<br>';
			}
		} elseif($templateid == 2) {
			# NOUVEAU模板对所有用户开放
			$gamedata['innerHTML']['info'] .= 'NOUVEAU界面尚在施工中。有问题欢迎去官方论坛报告。<br>';
			$templateid = 2;
		} else {
			# 其他值默认为经典界面
			$templateid = 0;
		}
	}
	$db->query("UPDATE {$gtablepre}users SET gender='$gender', icon='$icon',{$passqry}motto='$motto',  killmsg='$killmsg', lastword='$lastword', nick='$nick', u_templateid='$templateid' WHERE username='$cuser'");
	if($db->affected_rows()){
		$gamedata['innerHTML']['info'] .= $_INFO['data_success'];
	}else{
		$gamedata['innerHTML']['info'] .= $_INFO['data_failure'];
	}
	$gamedata['innerHTML']['credits'] = $credits;$gamedata['innerHTML']['credits2'] = $credits2;
	$gamedata['value']['opass'] = $gamedata['value']['npass'] = $gamedata['value']['rnpass'] = '';$gamedata['value']['exchg12'] = $gamedata['value']['exchg21'] = 0;
	if(isset($error)){$gamedata['innerHTML']['error'] = $error;}
	ob_clean();
	$jgamedata = compatible_json_encode($gamedata);
	echo $jgamedata;
	ob_end_flush();
	
} else {
	//$ustate = 'edit';
	header('Cache-Control: no-store');
	extract($udata);
	$talent_available = false;
	try {
		talent_ensure_storage();
		$talent_user = talent_current_user($udata['uid'],(string)$cpass);
		$talent_data = talent_load($talent_user['talentdata']);
		talent_sync_earned($talent_data,$talent_user['achrev']);
		$talent_base = hash('sha256',(string)$talent_user['talentdata']);
		$talent_csrf = hash_hmac('sha256','talent:'.$udata['uid'],(string)$cpass);
		$talent_used = talent_cost($talent_data); $talent_limit = talent_cap();
		$talent_free = max(0,min($talent_limit,$talent_data['earned_points']+$talent_data['bought_points'])-$talent_used);
		$talent_buy_price = talent_price($talent_data); $talent_reset_price = talent_price($talent_data,true);
		$talent_trees = talent_catalog(); $talent_available = true;
	} catch(Throwable $e) { $talent_notice = '天赋暂时无法读取，原有记录已保留。'; error_log('Talent: '.$e->getMessage()); }
	$talent_notice = htmlspecialchars($talent_notice,ENT_QUOTES,'UTF-8');
	$nickinfo = titles_get_desc($nick);
	$iconarray = get_iconlist($icon);
	$select_icon = $icon;
	$activation_csrf = hash_hmac('sha256', 'activation:'.$udata['uid'], (string)$cpass);
	$activation_request = bin2hex(random_bytes(16));
	$activation_queue = array(); $activation_info = '';
	try {
		account_boons_ensure_storage();
		$boon_user = $db->fetch_array($db->query("SELECT boons FROM {$gtablepre}users WHERE uid=".(int)$udata['uid']));
		foreach(account_boons_load($boon_user['boons']) as $boon) $activation_queue[] = account_reward_html(account_boon_describe($boon));
	} catch(Throwable $e) {
		$activation_info = '赐福队列暂时无法读取，请稍后重试。';
		error_log('Account boons: '.$e->getMessage());
	}
	try {
		paperdoll_ensure_storage();
		$paperdoll_user = paperdoll_current_user($udata['uid'], $cpass);
		$paperdoll_boot = paperdoll_json(paperdoll_account_state($paperdoll_user) + array(
			'catalog' => paperdoll_catalog(), 'csrf' => hash_hmac('sha256', 'paperdoll:'.$udata['uid'], (string)$cpass),
			'endpoint' => 'user.php?mode=paperdoll'
		));
	} catch(Throwable $e) {
		error_log('Paperdoll: '.$e->getMessage());
		$paperdoll_boot = paperdoll_json(array('error' => '形象编辑暂时不可用，请稍后重试。'));
	}
	//这里假定player表里有usertitle字段而且储存方式是这样蛋疼的。具体程序虚子你写。
	$utlist = get_utitlelist();//然后去接收用户传来的$

	// 主从同步相关变量
	$show_sync_button = ($slave_level >= 1 && !empty($master_server_name));
	$user_sync_status = get_user_sync_status($cuser);
	if($user_sync_status) {
		$user_sync_status['sync_time_formatted'] = date('Y-m-d H:i:s', $user_sync_status['sync_time']);
	}
	$sync_button_text = $user_sync_status ? "从{$master_server_name}同步已绑定的账号数据" : "从{$master_server_name}迁移用户和成就数据";

	// 反向迁移相关变量
	$show_reverse_migrate_button = (is_reverse_migration_mode() && !empty($master_server_name));
	$user_reverse_migrate_status = get_reverse_migration_status($cuser);
	if($user_reverse_migrate_status) {
		$user_reverse_migrate_status['sync_time_formatted'] = date('Y-m-d H:i:s', $user_reverse_migrate_status['sync_time']);
	}
	$reverse_migrate_button_text = $user_reverse_migrate_status ? "重新推送到{$master_server_name}" : "推送到{$master_server_name}";

	include template('user');
}

?>
