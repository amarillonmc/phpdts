<?php
if(!defined('IN_ADMIN')) {
	exit('Access Denied');
}

// 引入主从同步功能
include_once './include/masterslave.func.php';
require_once GAME_ROOT.'./include/account_rewards.func.php';
$reward_csrf = hash_hmac('sha256', 'admin-rewards:'.$udata['uid'], (string)$cpass);
$reward_form = account_reward_form_values($_POST);
$reward_boon_stats = account_boon_stats();
$reward_catalog = account_reward_catalog();
if(!isset($urcmd)){$urcmd = '';}
if($urcmd){
	if(!isset($start)){$start = 0;}
	if(!isset($pagemode)){$pagemode = '';}
	$start = getstart($start,$pagemode);
	if($pagecmd == 'check'){
		if(empty($urorder) || !in_array($urorder,Array('uid','groupid','lastgame'))){
			$urorder = 'uid';
		}
		$urorder2 = $urorder2 == 'ASC' ? 'ASC' : 'DESC';
		$result = $db->query("SELECT * FROM {$gtablepre}users ORDER BY $urorder $urorder2, uid DESC LIMIT $start,$showlimit");	
	}elseif($pagecmd == 'find'){
		if($checkmode == 'ip') {
			$result = $db->query("SELECT * FROM {$gtablepre}users WHERE ip LIKE '%{$checkinfo}%' ORDER BY uid DESC LIMIT $start,$showlimit");
		} else {
			$result = $db->query("SELECT * FROM {$gtablepre}users WHERE username LIKE '%{$checkinfo}%' ORDER BY uid DESC LIMIT $start,$showlimit");
		}
	}
	if(!$db->num_rows($result)) {
		$cmd_info = '没有符合条件的帐户！';
		$startno = $start + 1;
		$resultinfo = '位置：第'.$startno.'条记录';
	} else {
		while($ur = $db->fetch_array($result)) {
			if(!$ur['gender']){$ur['gender']='0';}
			$ur = format_userdata($ur);
			$urdata[] = $ur;
		}
		$startno = $start + 1;
		$endno = $start + count($urdata);
		$resultinfo = '第'.$startno.'条-第'.$endno.'条记录';
	}
}
if($urcmd == 'ban' || $urcmd == 'unban' || $urcmd == 'del' || $urcmd == 'sendmessage' || $urcmd == 'reverse_migrate') {
	$operlist = $gfaillist = $ffaillist = array();
	for($i=0;$i<$showlimit;$i++){
		if(isset(${'user_'.$i})) {
			if(isset($urdata[$i]) && $urdata[$i]['uid'] == ${'user_'.$i} && ($urdata[$i]['groupid'] < $mygroup || $urcmd == 'sendmessage')){
				$operlist[${'user_'.$i}] = $urdata[$i]['username'];
				if($urcmd == 'ban'){
					$urdata[$i]['groupid'] = 0;
				}elseif($urcmd == 'unban'){
					$urdata[$i]['groupid'] = 1;
				}elseif($urcmd == 'del'){
					unset($urdata[$i]);
				}
//				adminlog('banur',$urdata[$i]['username']);
			}elseif(isset($urdata[$i]) && $urdata[$i]['uid'] == ${'user_'.$i}){
				$gfaillist[${'user_'.$i}] = $urdata[$i]['username'];
			}else{
				$ffaillist[] = ${'user_'.$i};
			}			
		}
	}
	if($operlist || $gfaillist || $ffaillist){
		$cmd_info = '';
		if($urcmd == 'sendmessage'){
			$operword = '发送邮件给';
		}elseif($urcmd == 'ban'){
			$operword = '封停';
			$qryword = "UPDATE {$gtablepre}users SET groupid='0' ";
		}elseif($urcmd == 'unban'){
			$operword = '解封';
			$qryword = "UPDATE {$gtablepre}users SET groupid='1' ";
		}elseif($urcmd == 'del'){
			$operword = '删除';
			$qryword = "DELETE FROM {$gtablepre}users ";
		}elseif($urcmd == 'reverse_migrate'){
			$operword = '反向迁移';
		}
		if($operlist){
			if($urcmd == 'sendmessage'){
				include_once './include/messages.func.php';
				$sent = array();
				try {
					account_reward_post_check('admin-rewards', $_POST);
					$stitle = $_POST['stitle'] ?? ''; $scontent = $_POST['scontent'] ?? '';
					list($mail_title, $mail_content) = account_reward_mail_text($stitle, $scontent);
					$senclosure = account_reward_encode(account_reward_from_form($_POST));
					foreach($operlist as $receiver) {
						message_create($receiver, $mail_title, $mail_content, $senclosure);
						$sent[] = $receiver;
					}
				} catch(Throwable $e) {
					$cmd_info .= account_reward_html($e->getMessage()).'<br>';
					if(!($e instanceof InvalidArgumentException)) error_log('Admin messages: '.$e->getMessage());
				}
				if($sent) {
					$opernames = implode(',', $sent);
					$cmd_info .= '已给账户 '.account_reward_html($opernames).' 发送邮件。<br>';
					adminlog('sendmessageur', $opernames, json_encode(array($stitle,$scontent,$senclosure), JSON_UNESCAPED_UNICODE));
				}
			}elseif($urcmd == 'reverse_migrate'){
				// 处理反向迁移
				if(!is_reverse_migration_mode()) {
					$cmd_info .= " 错误：当前不是反向迁移模式 (slave_level != -1)。<br>";
				} elseif(empty($master_password)) {
					$cmd_info .= " 错误：请输入远端从服务器密码。<br>";
				} elseif(empty($remote_username)) {
					$cmd_info .= " 错误：请输入远端从服务器用户名。<br>";
				} else {
					$opernames = array();
					$success_count = 0;
					$fail_count = 0;

					foreach($operlist as $uid => $username) {
						// 使用本地用户名、远端用户名和密码进行反向迁移
						$migrate_result = reverse_migrate_user($username, $remote_username, md5($master_password));
						if($migrate_result['success']) {
							$success_count++;
							$opernames[] = $username . '(成功)';
						} else {
							$fail_count++;
							$opernames[] = $username . '(失败: ' . $migrate_result['message'] . ')';
						}
					}

					$opernames_str = implode(', ', $opernames);
					$cmd_info .= " 反向迁移结果：成功 $success_count 个，失败 $fail_count 个。详情：$opernames_str<br>";
					adminlog($urcmd.'ur', implode(',', array_values($operlist)), "成功:$success_count,失败:$fail_count");
				}
			}else{
				$qrywhere = '('.implode(',',array_keys($operlist)).')';
				$opernames = implode(',',($operlist));
				$db->query("$qryword WHERE uid IN $qrywhere");
				$cmd_info .= " 帐户 $opernames 被 $operword 。<br>";
			}

		}
		if($gfaillist){
			$gfailnames = implode(',',($gfaillist));
			$cmd_info .= " 权限不够，无法 $operword 帐户 $gfailnames 。<br>";
		}
		if($ffaillist){
			$ffailnames = implode(',',($ffaillist));
			$cmd_info .= " UID为 $ffailnames 的帐户不在当前查询范围。<br>";
		}
	}else{
		$cmd_info = "指定的帐户超出查询范围或指令错误。";
	}
	$urcmd = 'list';
}  elseif($urcmd == 'del2') {
	$result = $db->query("SELECT username,uid FROM {$gtablepre}users WHERE lastgame = 0 AND groupid<='$mygroup' LIMIT 1000");
	while($ddata = $db->fetch_array($result)){
		$n = $ddata['username'];$u = $ddata['uid'];
		adminlog('delur',$n);
		echo " 帐户 $n 被删除。<br>";
		$db->query("DELETE FROM {$gtablepre}users WHERE uid='$u'");
	}
}elseif(strpos($urcmd ,'edit')===0) {
	$uid = explode('_',$urcmd);
	$no = (int)$uid[1];
	$uid = (int)$uid[2];
	if(!$uid){
		$cmd_info = "帐户UID错误。";
	}elseif(!isset($urdata[$no]) || $urdata[$no]['uid'] != $uid){
		$cmd_info = "该帐户不存在或超出查询范围。";
	}elseif($urdata[$no]['groupid'] > $mygroup){
		$cmd_info = "权限不够，不能修改此帐户信息！";
	}else{
		$urdata[$no]['motto'] = $urmotto = astrfilter(${'motto_'.$no});
		$urdata[$no]['killmsg'] = $urkillmsg = astrfilter(${'killmsg_'.$no});
		$urdata[$no]['lastword'] = $urlastword = astrfilter(${'lastword_'.$no});
		//$urdata[$no]['nicks'] = $urnicks = ${'nicks_'.$no};
		//$urdata[$no]['achievement'] = $urach = ${'achievement_'.$no};
		$urdata[$no]['icon'] = $uricon = (int)(${'icon_'.$no});
		$urdata[$no]['credits'] = $urcredits = (int)(${'credits_'.$no});
		$urdata[$no]['credits2'] = $urcredits2 = (int)(${'credits2_'.$no});
		if(!in_array(${'gender_'.$no},array('0','m','f'))){
			$urdata[$no]['gender'] = $urgender = '0';
		}else{
			$urdata[$no]['gender'] = $urgender = ${'gender_'.$no};
		}
		if(!empty(${'addtitles_'.$no}) && isset($titles_list[${'addtitles_'.$no}]))
		{
			$nkey = ${'addtitles_'.$no};
			$flag = titles_get_new($urdata[$no],$nkey);
			if($flag)
			{
				$cmd_info .= "".$urdata[$no]['username']." 获得了头衔 {$titles_list[$nkey]} <br>";
				$db->query("UPDATE {$gtablepre}users SET nicksrev='{$urdata[$no]['nicksrev']}' WHERE uid='$uid'");
			}
			else 
			{
				$cmd_info .= "".$urdata[$no]['username']." 已拥有头衔 {$titles_list[$nkey]} ，不能重复获取<br>";
			}
		}
		if(!empty(${'deltitles_'.$no}) && isset($titles_list[${'deltitles_'.$no}]))
		{
			$nkey = ${'deltitles_'.$no};
			$flag = titles_delete($urdata[$no],$nkey);
			if($flag)
			{
				$cmd_info .= "从".$urdata[$no]['username']." 的头衔列表中删去了 {$titles_list[$nkey]} <br>";
				$db->query("UPDATE {$gtablepre}users SET nicksrev='{$urdata[$no]['nicksrev']}' WHERE uid='$uid'");
			}
			else 
			{
				$cmd_info .= "".$urdata[$no]['username']." 未持有头衔 {$titles_list[$nkey]}<br>";
			}
		}
		if(!empty(${'pass_'.$no})){
			$urpass = md5(${'pass_'.$no});
			$db->query("UPDATE {$gtablepre}users SET motto='$urmotto',killmsg='$urkillmsg',lastword='$urlastword',icon='$uricon',gender='$urgender',password='$urpass',credits='$urcredits',credits2='$urcredits2' WHERE uid='$uid'");
			$cmd_info .= "帐户 ".$urdata[$no]['username']." 的密码及其他信息已修改！";
		}else{
			$db->query("UPDATE {$gtablepre}users SET motto='$urmotto',killmsg='$urkillmsg',lastword='$urlastword',icon='$uricon',gender='$urgender',credits='$urcredits',credits2='$urcredits2' WHERE uid='$uid'");
			$cmd_info .= "帐户 ".$urdata[$no]['username']." 的信息已修改！";
		}
		$urdata[$no] = fetch_userdata_by_username($urdata[$no]['username']);
	}
	$urcmd = 'list';
}

// 设置反向迁移显示变量
$show_reverse_migration = is_reverse_migration_mode();

include template('admin_urlist');



function urlist($htm,$cmd='',$start=0) {
}

?>

