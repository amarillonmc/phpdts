<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}
//反正应该不会重复获得头衔……偷懒
//还是会重复的 反正没什么工作量
function get_title($t,$n){
	global $gamecfg,$name,$db,$tablepre;
	require config("gamecfg",$gamecfg);
	$result = $db->query("SELECT nicks FROM {$tablepre}users WHERE username = '$n'");
	$k = $db->result($result, 0);
	if (strpos($k,$t)===false){
		$cf = GAME_ROOT.'./gamedata/clearlog.php';
		$d = "$n".','."$t\n";
		writeover($cf,$d,'ab+');
		$k=$k.'/'.$t;
	}
	$db->query("UPDATE {$tablepre}users SET nicks='$k' WHERE username='".$n."'" );
}
?>
