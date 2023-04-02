<?php
if(!defined('IN_ADMIN')) {
	exit('Access Denied');
}

if($gamestate >= 20){
	require_once GAME_ROOT.'./include/system.func.php';
	
	$result = $db->query("SELECT pid FROM {$tablepre}players WHERE type=0");
	$validnum = $db->num_rows($result);
	
	$result = $db->query("SELECT pid FROM {$tablepre}players WHERE hp>0 AND type=0");
	$alivenum = $db->num_rows($result);
	
	$result = $db->query("SELECT pid FROM {$tablepre}players WHERE hp<=0 OR state>=10");
	$deathnum = $db->num_rows($result);
	
	movehtm();
	
	save_gameinfo();
	
	adminlog('infomng');
	
	$cmd_info = "状态更新：激活人数 {$validnum},生存人数 {$alivenum},死亡人数 {$deathnum}<br>";
	$cmd_info .= "已重置移动地点缓存数据<br>";
}else{
	$cmd_info = "当前游戏未开始！<br>";
}

# 暂时把房间人数自检放在这里
if(!empty($roomlist))
{
	foreach($roomlist as $rkey => $rinfo)
	{
		$result = $db->query("SELECT uid FROM {$gtablepre}users WHERE roomid = {$rkey}");
		if($db->num_rows($result)) 
		{
			$join_nums = $db->num_rows($result);
			$db->query("UPDATE {$gtablepre}game SET groomnums = {$join_nums} WHERE groomid = {$rkey}");
			$cmd_info .= "房间 {$rkey} 状态更新：房间内人数 {$join_nums}<br>";
		}
		else 
		{
			$db->query("DELETE FROM {$gtablepre}game WHERE groomid = {$rkey}");
			$cmd_info .= "房间 {$rkey} 无人参与：已关闭<br>";
		}
	}
}

include template('admin_menu');

?>