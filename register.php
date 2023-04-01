<?php

define('CURSCRIPT', 'register');

require './include/common.inc.php';

include './include/user.func.php';
include './gamedata/banlist.list';

if(isset($cuser) && isset($cpass)){
	gexit($_ERROR['logged_in'],__file__,__line__);
}
if(!isset($cmd)){
	//$ustate = 'register';
	$icon = 0;
	$gender = 'm';
	$iconarray = get_iconlist();
	$select_icon = 0;
	$motto = $criticalmsg = $killmsg = $lastword = '';
	include template('register');
}elseif($cmd = 'post_register'){
	//$ustate = 'register';
	$gamedata = Array();
	$name_check = name_check($username);
	$pass_check = pass_check($npass,$rnpass);
	$onlineip = real_ip();
	
	if($name_check!='name_ok'){
		$gamedata['innerHTML']['info'] = $_ERROR[$name_check];
	}elseif($pass_check!='pass_ok'){
		$gamedata['innerHTML']['info'] = $_ERROR[$pass_check];
	}elseif(preg_match($iplimit,$onlineip)){
		$gamedata['innerHTML']['info'] = $_ERROR['ip_banned'];
	}else{
		$result = $db->query("SELECT * FROM {$gtablepre}users WHERE username = '$username'");
		if($db->num_rows($result) > 0) {
			$gamedata['innerHTML']['info'] = $_ERROR['name_exists'];
		}else{//现在开始注册
			$groupid = 1;
			$credits = 0;
			$password = md5($npass);
			$nick='参展者';
			$nicks='参展者';
			$result = $db->query("INSERT INTO {$gtablepre}users (username,password,groupid,ip,credits,gender,icon,motto,killmsg,lastword) VALUES ('$username', '$password', '$groupid', '$onlineip', '$credits', '$gender', '$icon', '$motto', '$killmsg', '$lastword')");
			$db->query("UPDATE {$gtablepre}users SET nick='$nick', nicks='$nicks' WHERE username='".$username."'" );
			if($result){
				$gamedata['innerHTML']['info'] = $_INFO['reg_success'];
				$ustate = 'check';
				gsetcookie('user',$username);
				gsetcookie('pass',$password);
				gsetcookie('volume',0.2,86400*30,0);
			}else{
				$gamedata['innerHTML']['info'] = $_ERROR['db_failure'];
				$gamedata['innerHTML']['info'] .= ob_get_contents();
			}
		}
	}
	if($ustate == 'check'){
		$gamedata['innerHTML']['postreg'] = '<input type="button" name="back" value="返回游戏首页" onclick="window.location.href=\'index.php\'">';
		if(isset($error)){$gamedata['innerHTML']['error'] = $error;}
		ob_clean();
		$jgamedata = compatible_json_encode($gamedata);
		echo $jgamedata;
		ob_end_flush();
	}else{
		ob_clean();
		if(isset($error)){$gamedata['innerHTML']['error'] = $error;}
		$jgamedata = compatible_json_encode($gamedata);
		echo $jgamedata;
		ob_end_flush();
	}
}

?>