<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}

// gruleset决定后续应加载哪套配置，因此必须在第一次config()查询之前存在。
// gruleset selects the configuration set, so it must exist before the first config() lookup.
function roommng_ensure_ruleset_game_structure()
{
	global $db,$gtablepre;

	$result = $db->query("SHOW COLUMNS FROM {$gtablepre}game LIKE 'gruleset'");
	if($db->num_rows($result)) return;

	// FTP覆盖后的首批请求可能同时发现旧结构；锁内二次确认可避免重复ALTER。
	// The first requests after an FTP overwrite may all see the old schema; recheck under the lock before ALTER.
	$structure_lock = @fopen(GAME_ROOT.'./gamedata/process.lock', 'ab');
	if(!$structure_lock || !flock($structure_lock, LOCK_EX))
	{
		if($structure_lock) fclose($structure_lock);
		gexit('Unable to lock the RuleSet schema migration.', __FILE__, __LINE__);
	}

	try
	{
		$result = $db->query("SHOW COLUMNS FROM {$gtablepre}game LIKE 'gruleset'");
		if(!$db->num_rows($result))
		{
			$db->query("ALTER TABLE {$gtablepre}game ADD gruleset varchar(50) NOT NULL DEFAULT '' AFTER groomid");
		}
	}
	finally
	{
		flock($structure_lock, LOCK_UN);
		fclose($structure_lock);
	}
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

	$result = $db->query("DESCRIBE {$gtablepre}game gruleset");
	if(!$db->num_rows($result))
	{
		$db->query("ALTER TABLE {$gtablepre}game ADD gruleset varchar(50) NOT NULL DEFAULT '' AFTER groomid");
		echo "向game表中添加了字段gruleset<br>";
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

	$result = $db->query("DESCRIBE {$gtablepre}users u_templateid");
	if(!$db->num_rows($result))
	{
		$db->query("ALTER TABLE {$gtablepre}users ADD u_templateid tinyint(3) unsigned NOT NULL DEFAULT '0' AFTER lastword");
		echo "向users表中添加了字段u_templateid<br>";
	}

	$result = $db->query("DESCRIBE {$gtablepre}users nicksrev");
	if(!$db->num_rows($result))
	{
		$db->query("ALTER TABLE {$gtablepre}users ADD nicksrev text NOT NULL default '' AFTER nicks");
		echo "向users表中添加了字段nicksrev<br>";
	}

	$result = $db->query("SHOW INDEX FROM {$gtablepre}game");
	$gr = $db->fetch_array($result);
	if($gr['Column_name'] != 'groomid')
	{
		if(!empty($gr['Key_name']))
		{
			$db->query("ALTER TABLE`{$gtablepre}game` DROP PRIMARY KEY");
			echo "取消了game表的主键{$gr['Key_name']}<br>";
		}
		$db->query("ALTER TABLE`{$gtablepre}game` ADD PRIMARY KEY (`groomid`)");
		echo "将game表的主键变更为groomid<br>";
	}
	return;
}

// 串行化建房流程，避免并发请求复用同一房间号或重复扣费。
// Serialize room creation so concurrent requests cannot reuse a room id or charge twice.
function roommng_acquire_create_lock()
{
	global $plock;

	// common.inc.php 的初始化路径可能已经持有同一把全局锁。
	// The common.inc.php initialization path may already hold the same global lock.
	if (isset($plock) && is_resource($plock)) return NULL;

	$lock_handle = @fopen(GAME_ROOT.'./gamedata/process.lock', 'ab');
	if (!$lock_handle || !flock($lock_handle, LOCK_EX))
	{
		if ($lock_handle) fclose($lock_handle);
		return false;
	}
	return $lock_handle;
}

function roommng_release_create_lock($lock_handle)
{
	if (!is_resource($lock_handle)) return;
	flock($lock_handle, LOCK_UN);
	fclose($lock_handle);
}

# 创建一个新房间
function roommng_create_new_room(&$udata, $ruleset_id = '')
{
	global $db,$gtablepre,$now;
	global $startmin,$max_rooms,$ip_max_rooms,$rerror;

	$room_lock = roommng_acquire_create_lock();
	if ($room_lock === false)
	{
		$rerror = 'room_num_limit';
		return;
	}

	try
	{
		// 等待锁期间账号状态可能已被另一个请求更新，因此必须在锁内刷新。
		// Account state may change while waiting for the lock, so refresh it inside the critical section.
		$username_literal = "X'".bin2hex($udata['username'])."'";
		$user_result = $db->query("SELECT roomid,credits2,ip,groupid FROM {$gtablepre}users WHERE username=$username_literal");
		if(!$db->num_rows($user_result))
		{
			$rerror = 'login_check';
			return;
		}
		$current_user = $db->fetch_array($user_result);
		foreach(array('roomid','credits2','ip','groupid') as $user_field) $udata[$user_field] = $current_user[$user_field];

		if(!empty($udata['roomid']))
		{
			$rerror = 'alreay_in_room';
			return;
		}

		$ruleset_cost = 0;
		# 检查RuleSet权限和费用
		if(!empty($ruleset_id))
		{
			// 包含配置文件
			include_once GAME_ROOT.'./gamedata/ruleset/ruleset_config.php';

			// 调试信息：记录权限检查过程
			$debug_info = array(
				'ruleset_id' => $ruleset_id,
				'user_groupid' => $udata['groupid'],
				'user_credits2' => $udata['credits2'],
				'ruleset_enabled' => isset($ruleset_enabled) ? $ruleset_enabled : 'undefined',
				'config_exists' => isset($ruleset_config[$ruleset_id]) ? 'yes' : 'no'
			);

			if(isset($ruleset_config[$ruleset_id])) {
				$config = $ruleset_config[$ruleset_id];
				$debug_info['admin_free'] = $config['admin_free'];
				$debug_info['credits_cost'] = $config['credits_cost'];
				$debug_info['admin_check'] = ($config['admin_free'] && $udata['groupid'] >= 2) ? 'pass' : 'fail';
				$debug_info['credits_check'] = ($udata['credits2'] >= $config['credits_cost']) ? 'pass' : 'fail';
			}

			// 临时调试：将调试信息写入文件
			file_put_contents(GAME_ROOT.'./doc/etc/ruleset_debug_'.date('Y-m-d_H-i-s').'.txt',
				"RuleSet权限检查调试信息:\n" . print_r($debug_info, true));

			if(!can_create_ruleset_room($ruleset_id, $udata))
			{
				$rerror = 'ruleset_no_permission';
				return;
			}

			$config = get_ruleset_config($ruleset_id);
			if($config && !($config['admin_free'] && $udata['groupid'] >= 2))
			{
				$ruleset_cost = max(0, intval($config['credits_cost']));
			}
		}

		# 根据IP判断是否可新建房间
		$ip_literal = "X'".bin2hex($udata['ip'])."'";
		$ipresult = $db->query("SELECT roomid FROM {$gtablepre}users WHERE roomid>0 AND ip=$ip_literal");
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
			$now_room_ids = array();
			while($room_data = $db->fetch_array($result)) $now_room_ids[] = $room_data['groomid'];
			$available_room_ids = array_diff($room_ids,$now_room_ids);
			$new_room_id = array_shift($available_room_ids);
		}
		else
		{
			$new_room_id = 1;
		}

		# 获取当前游戏回数
		$result = $db->query("SELECT max(gamenum) AS max_value FROM {$gtablepre}game WHERE groomid>=0 ");
		$new_gamenum = $db->fetch_array($result)['max_value'];

		# 先成功建立房间，再原子扣费；余额变化时删除新房并返回，不让失败请求吞掉切糕。
		# Create the room first, then charge atomically; remove it if the balance changed meanwhile.
		$starttime = $now + $startmin*5;
		$ruleset_sql = !empty($ruleset_id) ? ",'$ruleset_id'" : ",''";
		$db->query("INSERT INTO {$gtablepre}game (gamenum,groomid,groomownid,gamestate,starttime,gruleset) VALUES ('$new_gamenum','$new_room_id','{$udata['username']}','0','$starttime'$ruleset_sql)");

		if($ruleset_cost > 0)
		{
			$db->query("UPDATE {$gtablepre}users SET credits2=credits2-'$ruleset_cost' WHERE username=$username_literal AND credits2>='$ruleset_cost'");
			if($db->affected_rows() != 1)
			{
				$db->query("DELETE FROM {$gtablepre}game WHERE groomid='$new_room_id'");
				$rerror = 'insufficient_credits';
				return;
			}
			$udata['credits2'] -= $ruleset_cost;
		}

		# 加入房间
		roommng_join_room($new_room_id,$udata);
	}
	finally
	{
		roommng_release_create_lock($room_lock);
	}

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
		$db->query("UPDATE {$gtablepre}game SET groomnums='{$gdata['groomnums']}' WHERE groomid='{$rkey}'");
		# 加入房间
		$db->query("UPDATE {$gtablepre}users SET roomid='{$rkey}' WHERE username='{$udata['username']}'");
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
	$result = $db->query("SELECT * FROM {$gtablepre}game WHERE groomid='{$udata['roomid']}'");
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
				$result2 = $db->query("SELECT * FROM {$gtablepre}users WHERE roomid='{$udata['roomid']}' AND username!='{$udata['username']}'");
				if($db->num_rows($result2))
				{
					$udata2 = $db->fetch_array($result2);
					$new_ownid = $udata2['username'];
					echo "将房主权限移交给了{$udata2['username']}<br>";
				}
			}
			if(isset($new_ownid))
			{
				$db->query("UPDATE {$gtablepre}game SET groomnums='{$gdata['groomnums']}',groomownid='{$new_ownid}' WHERE groomid='{$udata['roomid']}'");
			}
			else 
			{
				$db->query("UPDATE {$gtablepre}game SET groomnums='{$gdata['groomnums']}' WHERE groomid='{$udata['roomid']}'");
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

	$result = $db->query("SELECT * FROM {$gtablepre}game WHERE groomid='{$udata['roomid']}'");
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
function roommng_close_room($rkey,$adminlog = 0,$check_in_game = 0)
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
		# 检查是否为闲置房间
		if($check_in_game)
		{
			# 不能解散正在游戏中的房间
			if($gdata['gamestate'] > 10 && $gdata['alivenum'])
			{
				$cmd_info .= "房间 {$rkey} 内仍有存活玩家，无法关闭。<br>";
				return;
			}
		}
		# 清空房间内玩家
		if($gdata['groomnums']) $db->query("UPDATE {$gtablepre}users SET roomid=0 WHERE roomid='{$rkey}'");
		# 关闭房间
		$db->query("DELETE FROM {$gtablepre}game WHERE groomid='{$rkey}'");
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
