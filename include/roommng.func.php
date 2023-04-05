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
		$db->query("ALTER TABLE {$gtablepre}game ADD groomid tinyint(3) unsigned NOT NULL DEFAULT '0' AFTER gamestate");
		echo "向game表中添加了字段groomid<br>";
	}
	$result = $db->query("DESCRIBE {$gtablepre}game groomnums");
	if(!$db->num_rows($result))
	{
		$db->query("ALTER TABLE {$gtablepre}game ADD groomnums tinyint(3) unsigned NOT NULL DEFAULT '0' AFTER groomid");
		echo "向game表中添加了字段groomnums<br>";
	}
	$result = $db->query("DESCRIBE {$gtablepre}game groomownid");
	if(!$db->num_rows($result))
	{
		$db->query("ALTER TABLE {$gtablepre}game ADD groomownid char(15) NOT NULL default '' AFTER groomnums");
		echo "向game表中添加了字段groomownid<br>";
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

# 创建一个新房间
function roommng_create_new_room(&$udata)
{
	global $db,$gtablepre,$now;
	global $startmin,$max_rooms,$ip_max_rooms,$rerror;

	if(!empty($udata['roomid']))
	{
		$rerror = 'alreay_in_room';
		return;
	}

	# 根据IP判断是否可新建房间
	$ipresult = $db->query("SELECT roomid FROM {$gtablepre}users WHERE roomid>0 AND ip='{$udata['ip']}'");
	if($db->num_rows($ipresult) >= $ip_max_rooms)
	{
		$rerror = 'room_ip_limit';
		return;
	}

	# 统计当前已新建房间数量
	$result = $db->query("SELECT groomid FROM {$gtablepre}game WHERE groomid>0 ");
	$now_room_nums = $db->num_rows($result);
	if($now_room_nums >= $max_rooms)
	{
		$rerror = 'room_num_limit';
		return;
	}
	
	if($now_room_nums)
	{
		$room_ids = range(1,$max_rooms);
		$now_room_ids = $db->fetch_array($result);
		$new_room_id = array_shift(array_diff($room_ids,$now_room_ids));
	}
	else 
	{
		$new_room_id = 1;
	}

	# 新建并初始化房间状态
	$starttime = $now + $startmin*5;
	$db->query("INSERT INTO {$gtablepre}game (gamenum,groomid,groomownid,gamestate,starttime) VALUES ('1','$new_room_id','{$udata['username']}','0','$starttime')");

	# 加入房间
	roommng_join_room($new_room_id,$udata);

	return;
}

# 加入一个房间
function roommng_join_room($rkey,&$udata)
{
	global $db,$gtablepre,$rerror;

	if(!empty($udata['roomid']))
	{
		$rerror = 'alreay_in_room';
		return;
	}

	$result = $db->query("SELECT * FROM {$gtablepre}game WHERE groomid='$rkey'");
	if($db->num_rows($result))
	{
		$gdata = $db->fetch_array($result);
		$gdata['groomnums']++;
		# 更新房间内玩家数量
		$db->query("UPDATE {$gtablepre}game SET groomnums={$gdata['groomnums']} WHERE groomid={$rkey}");
		# 加入房间
		$db->query("UPDATE {$gtablepre}users SET roomid={$rkey} WHERE username='{$udata['username']}'");
	}
	else 
	{
		# 要加入的房间号不存在时，尝试新建一个
		roommng_create_new_room($udata);
	}
	return;
}

# 离开当前房间
function roommng_exit_room(&$udata)
{
	global $db,$gtablepre,$rerror;

	if(empty($udata['roomid']))
	{
		$rerror = 'not_in_room';
		return;
	}

	echo "已退出房间{$udata['roomid']}<br>";

	# 退出房间时更新房间状态
	$result = $db->query("SELECT * FROM {$gtablepre}game WHERE groomid={$udata['roomid']}");
	if($db->num_rows($result))
	{
		$gdata = $db->fetch_array($result);
		$gdata['groomnums']--;
		# 检查解散房间还是更新房间状态
		if($gdata['groomnums'] > 0)
		{
			# 房主退出房间时，将房主权限移交给房间内其他人
			if(!empty($gdata['groomownid']) && $gdata['groomownid'] == $udata['username'])
			{
				$result2 = $db->query("SELECT * FROM {$gtablepre}users WHERE roomid={$udata['roomid']} AND username!='{$udata['username']}'");
				if($db->num_rows($result2))
				{
					$udata2 = $db->fetch_array($result2);
					$new_ownid = $udata2['uid'];
					echo "将房主权限移交给了{$udata2['username']}<br>";
				}
			}
			if(isset($new_ownid))
			{
				$db->query("UPDATE {$gtablepre}game SET groomnums={$gdata['groomnums']},groomownid={$new_ownid} WHERE groomid={$udata['roomid']}");
			}
			else 
			{
				$db->query("UPDATE {$gtablepre}game SET groomnums={$gdata['groomnums']} WHERE groomid={$udata['roomid']}");
			}
		}
		else 
		{
			roommng_close_room($udata['roomid']);
		}
	}
	# 更新用户状态
	$db->query("UPDATE {$gtablepre}users SET roomid = 0 WHERE username='{$udata['username']}'");
	return;
}

# 房主解散自己所在的房间
function roommng_close_own_room(&$udata)
{
	global $db,$gtablepre,$rerror;

	if(empty($udata['roomid']))
	{
		$rerror = 'not_in_room';
		return;
	}

	$result = $db->query("SELECT * FROM {$gtablepre}game WHERE groomid={$udata['roomid']}");
	if($db->num_rows($result))
	{
		$gdata = $db->fetch_array($result);
		# 不能解散没有房主的房间
		if(empty($gdata['groomownid']) || (!empty($gdata['groomownid']) && $gdata['groomownid'] != $udata['username']))
		{
			$rerror = 'room_close_limit';
			return;
		}
		# 不能解散正在游戏中的房间
		if($gdata['gamestate'] > 10 && $gdata['alivenum'])
		{
			$rerror = 'room_close_limit2';
			return;
		}
		# 解散房间
		roommng_close_room($udata['roomid']);
	}
	# 更新用户状态
	$db->query("UPDATE {$gtablepre}users SET roomid = 0 WHERE username='{$udata['username']}'");
	return;
}

# 强制解散指定房间
function roommng_close_room($rkey,$adminlog = 0)
{
	global $db,$gtablepre,$rerror,$cmd_info;

	if(!$rkey)
	{
		$cmd_info .=  "不能关闭大房间！<br>";
		return;
	}

	$result = $db->query("SELECT * FROM {$gtablepre}game WHERE groomid='$rkey'");
	if($db->num_rows($result))
	{
		$gdata = $db->fetch_array($result);
		# 清空房间内玩家
		if($gdata['groomnums']) $db->query("UPDATE {$gtablepre}users SET roomid=0 WHERE roomid={$rkey}");
		# 关闭房间
		$db->query("DELETE FROM {$gtablepre}game WHERE groomid={$rkey}");
		$cmd_info .= "已关闭房间 {$rkey} 号<br>";
		if($adminlog) adminlog('closeroom',$rkey);
	}
	else 
	{
		$cmd_info .= "房间 {$rkey} 未开启，或房间不存在！<br>";
	}
	return;
}



?>
