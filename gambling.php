<?php

define('CURSCRIPT', 'gambling');

require './include/common.inc.php';

$gbinfo = '';
if(!$cuser||!$cpass) { $gbinfo .= $_ERROR['no_login']; }
elseif($gamestate < 20) { $gbinfo .= $_ERROR['no_start']; }
elseif($gamestate < 30) { $gbinfo .= '游戏还未停止激活，不可进行下注！'; }
else{
	$result = $db->query("SELECT * FROM {$tablepre}users WHERE username='$cuser'");
	if(!$db->num_rows($result)) { $gbinfo .= $_ERROR['login_check']; }
	$udata = $db->fetch_array($result);
	if($udata['password'] != $cpass) { $gbinfo .= $_ERROR['wrong_pw']; }
	if($udata['groupid'] <= 0) { $gbinfo .= $_ERROR['user_ban']; }
}



include template('alivelist');
	$alivedata['innerHTML']['alivelist'] = ob_get_contents();
	if(isset($error)){$alivedata['innerHTML']['error'] = $error;}
	ob_clean();
	$jgamedata = compatible_json_encode($alivedata);
	echo $jgamedata;
	ob_end_flush();


//include template('alive');

?>