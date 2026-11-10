<?php

define('CURSCRIPT', 'messages');

require './include/common.inc.php';
require_once './include/messages.func.php';

if(!$cuser||!$cpass) { gexit($_ERROR['no_login'],__file__,__line__); }
if(!$udata) { gexit($_ERROR['login_check'],__file__,__line__); }
if($udata['password'] != $cpass) { gexit($_ERROR['wrong_pw'], __file__, __line__); }
if($udata['groupid'] <= 0) { gexit($_ERROR['user_ban'], __file__, __line__); }

$username = $udata['username'];

$message_rec_cost = 100;//恢复邮件价格

if(!isset($mode)){
	$mode = 'show';
}

$info = array();
$message_csrf = hash_hmac('sha256', 'messages:'.$udata['uid'], (string)$cpass);
$message_locked = false;
try {
	account_boons_ensure_storage();
	if(in_array($mode, array('check','del','del2','recover'), true)) {
		account_reward_post_check('messages', $_POST);
		if(!$db->query("LOCK TABLES {$gtablepre}users WRITE, {$gtablepre}messages WRITE, {$gtablepre}del_messages WRITE", 'SILENT')) throw new RuntimeException('邮箱繁忙，请稍后重试。');
		$message_locked = true;
		$message_user = $db->fetch_array($db->query("SELECT * FROM {$gtablepre}users WHERE uid=".(int)$udata['uid']));
		if(!$message_user || (int)$message_user['groupid'] <= 0 || !hash_equals((string)$message_user['password'], (string)$cpass)) throw new InvalidArgumentException('登录状态已失效，请重新登录。');
		$udata = format_userdata($message_user);
	}
$messages = init_messages($mode);
$editflag = 0;
if($mode == 'del' || $mode == 'del2') {//删除
	$dellist = array();
	foreach(array_keys($messages) as $mi){
		if(!empty($_POST['sl'.$mi])) $dellist[] = $mi;
	}
	if(!empty($dellist)) $editflag = 1;
}elseif($mode == 'check') {//查看并收取附件
	$checklist = array();
	foreach(array_keys($messages) as $mi){
		if(!empty($_POST['sl'.$mi]) && !$messages[$mi]['checked'] && !empty($messages[$mi]['enclosure'])) $checklist[] = $mi;
	}
	if(!empty($checklist)) $editflag = 1;
}elseif($mode == 'recover') {//恢复删除邮件
	$reclist = array();
	foreach(array_keys($messages) as $mi){
		if(!empty($_POST['sl'.$mi])) $reclist[] = $mi;
	}
	if(!empty($reclist)) {
		$cost = $message_rec_cost * sizeof($reclist);
		if($udata['credits2'] < $cost) {
			$info[] = '切糕不足，无法恢复邮件！';
		}else {
			$udata['credits2'] -= $cost;
			$info[] = '支付了'.$cost.'切糕';
			$db->array_update("{$gtablepre}users", Array('credits2' => $udata['credits2']), "username='".$udata['username']."'");
			$editflag = 1;
		}
	}
}

if($editflag) {
	if(!empty($checklist)){
		$checked_count = message_check($checklist, $messages);
		if($checked_count) $info[] = '已查收'.$checked_count.'条消息！';
	}
	if(!empty($dellist)){
		$ins_arr = array();
		if('del'==$mode) {//正常删除会添加到垃圾箱
			foreach($dellist as $di){
				$tmp = $messages[$di];
				$tmp['dtimestamp'] = $now;
				unset($tmp['mid']);
				$ins_arr[] = $tmp;
			}
			if(!empty($ins_arr)) $db->array_insert("{$gtablepre}del_messages", $ins_arr);
		}
		$delc = implode(',',$dellist);
		if('del' == $mode){
			$db->query("DELETE FROM {$gtablepre}messages WHERE mid IN ($delc) AND receiver='$username'");
			$dnum = $db->affected_rows();
			$info[] = '已删除'.$dnum.'条消息！';
		}elseif('del2' == $mode){
			$db->query("DELETE FROM {$gtablepre}del_messages WHERE mid IN ($delc) AND receiver='$username'");
			$dnum = $db->affected_rows();
			$info[] = '已彻底删除'.$dnum.'条消息！';
		}
		
	}
	if(!empty($reclist)){
		$ins_arr = array();
		foreach($reclist as $ri){
			$tmp = $messages[$ri];
			unset($tmp['mid'],$tmp['dtimestamp']);
			$ins_arr[] = $tmp;
		}
		if(!empty($ins_arr)) $db->array_insert("{$gtablepre}messages", $ins_arr);
		$recc = implode(',',$reclist);
		$db->query("DELETE FROM {$gtablepre}del_messages WHERE mid IN ($recc) AND receiver='$username'");
		$rnum = $db->affected_rows();
		$info[] = '已恢复'.$rnum.'条消息！';
	}
	if('recover' == $mode || 'del2' == $mode){//删除或者恢复命令，显示的是垃圾桶页面
		$mode = 'showdel';
	}
	//重载一次信息
	$messages = init_messages($mode);
}elseif(strpos($mode,'show') !== 0 && empty($info)){
	$info[] = '没有做任何更改';
}
//全部设为已读
if('show' == $mode){
	foreach($messages as $mv){
		if(!$mv['rd']) {
			$db->query("UPDATE {$gtablepre}messages SET rd='1' WHERE receiver='$username' AND rd='0'");
			break;
		}
	}
}

} catch(Throwable $e) {
	$info[] = account_reward_html($e->getMessage());
	if(!($e instanceof InvalidArgumentException)) error_log('Messages: '.$e->getMessage());
	if($mode !== 'show') $mode = 'showmail';
	$messages = init_messages($mode);
} finally {
	if($message_locked) $db->query('UNLOCK TABLES');
}

$messages = message_disp($messages);
if('show'==$mode){//生成整个页面，不用ajax
	include template('message_page');
}else{//有指令，需要ajax
	$gamedata=Array();
	$gamedata['innerHTML']['info'] = implode('<br>', $info);
	if(isset($error)) $gamedata['innerHTML']['error'] = $error;
	include template('messages');
	$gamedata['innerHTML']['messages'] = ob_get_contents();
	$jgamedata = compatible_json_encode($gamedata);
	ob_clean();
	echo $jgamedata;
}

/* End of file messages.php */
/* Location: /messages.php */
