<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}

function roommng_verify_db_game_structure()
{
	global $db,$gtablepre;

	$result = $db->query("DESCRIBE {$gtablepre}users roomid");
	if(!$db->num_rows($result))
	{
		$db->query("ALTER TABLE {$gtablepre}users ADD roomid tinyint(3) unsigned NOT NULL DEFAULT '0' AFTER groupid");
		echo "向users表中添加了字段roomid<br>";
	}

	$result = $db->query("DESCRIBE {$gtablepre}game groomid");
	if(!$db->num_rows($result))
	{
		$db->query("ALTER TABLE {$gtablepre}game ADD groomid tinyint(3) unsigned NOT NULL DEFAULT '0' '' AFTER gamestate");
		echo "向game表中添加了字段groomid<br>";
	}
	$result = $db->query("DESCRIBE {$gtablepre}game groomnums");
	if(!$db->num_rows($result))
	{
		$db->query("ALTER TABLE {$gtablepre}game ADD groomnums tinyint(3) unsigned NOT NULL DEFAULT '0' AFTER groomid");
		echo "向game表中添加了字段groomnums<br>";
	}

	$result = $db->query("SHOW INDEX FROM {$gtablepre}game");
	$gr = $db->fetch_array($result);
	if($gr['Column_name'] != 'groomid')
	{
		$db->query("ALTER TABLE`{$gtablepre}game` DROP PRIMARY KEY");
		$db->query("ALTER TABLE`{$gtablepre}game` ADD PRIMARY KEY (`groomid`)");
		echo "将game表的索引变更为groomid<br>";
	}
	return;
}

function roommng_close_room($rkey,$log_print=0)
{
	global $db,$gtablepre;

	if(!$rkey)
	{
		echo "不能关闭大房间！<br>";
		return;
	}

	$result = $db->query("SELECT * FROM {$gtablepre}game WHERE groomid='$rkey'");
	if($db->num_rows($result))
	{
		$gdata = $db->fetch_array($result);
		# 清空房间内玩家
		if($gdata['groomnums']) $db->query("UPDATE {$gtablepre}users SET roomid = 0 WHERE roomid= {$rkey}");
		# 关闭房间
		$db->query("DELETE FROM {$gtablepre}game WHERE groomid = {$rkey}");
		if($log_print) echo "已关闭房间 {$rkey} 号<br>";
	}
	else 
	{
		if($log_print) echo "房间 {$rkey} 未开启，或房间不存在！<br>";
	}
}



?>
