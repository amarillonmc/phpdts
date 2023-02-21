<?php

define('CURSCRIPT', 'chat');

require './include/common.inc.php';
//require_once GAME_ROOT.'./include/JSON.php';

if(!$cuser || !defined('IN_GAME')) {
	exit('Not in game.');
}

if(($sendmode == 'send')&&$chatmsg) {
	$result = $db->query("SELECT pid FROM {$tablepre}players WHERE name='$cuser' AND type='0'");
	if(!$db->num_rows($result)) exit('Not in game.');
	if(strpos($chatmsg,'/') === 0) {
		$result = $db->query("SELECT groupid FROM {$tablepre}users WHERE username='$cuser'");
		$groupid = $db->result($result);
		if($groupid > 1) {
			if(strpos($chatmsg,'/post') === 0) {
				$chatmsg = substr($chatmsg,6);
				if($chatmsg){
					$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,msg) VALUES ('4','$now','$cuser','$chatmsg')");
				}
			} else {
				$chatdata = array('lastcid' => $lastcid, 'msg' => Array('<span class="red">指令错误。</span><br>'));
			}
		} else {
			$chatdata = array('lastcid' => $lastcid, 'msg' => Array('<span class="red">聊天信息不能用 / 开头。</span><br>'));
		}
	} else { 
		if($chattype == 0) {
			$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,msg) VALUES ('0','$now','$cuser','$chatmsg')");
		} elseif($chattype == 1) {
			$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('1','$now','$cuser','$teamID','$chatmsg')");
		}
	}
}
if(!$chatdata) {
	$chatdata = getchat($lastcid,$teamID);
}
if(!$emdata) {	
	$emdata = get_emdata();
}
ob_clean();
//$json = new Services_JSON();
//$jgamedata = $json->encode($chatdata);
$jgamedata = compatible_json_encode($chatdata);
echo $jgamedata;
ob_end_flush();


?>