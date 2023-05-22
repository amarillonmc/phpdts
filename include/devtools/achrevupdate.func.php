<?php

@ob_end_clean();
header('Content-Type: text/HTML; charset=utf-8'); // 以事件流的形式告知浏览器进行显示
header('Cache-Control: no-cache');         // 告知浏览器不进行缓存
header('X-Accel-Buffering: no');           // 关闭加速缓冲
@ini_set('implicit_flush',1);
ob_implicit_flush(1);
set_time_limit(0);
@ini_set('zlib.output_compression',0);

function achrev_update()
{
	global $db,$gtablepre,$exit;

	$result = $db->query("SELECT * FROM {$gtablepre}users");	
	while($ur = $db->fetch_array($result)) 
	{
		$udatas[] = $ur;			
	}

	$alist = get_achlist();

	$nums = 0;

	foreach($udatas as $ukey => $udata)
	{
		# 存在旧成就数据，且新成就数据为空时，将旧字段数据迁移至新字段内
		if(!empty($udata['achievement']) && empty($udata['achrev']))
		{
			include_once GAME_ROOT.'./include/game/achievement.func.php';
			$n = $udata['username'];
			$new_ach = Array(); $cpl = Array(); $prc = Array();
			foreach($alist as $i => $iarr)
			{
				if($i <= 57)
				{
					$cpl[$i]=check_achievement($i,$n);
					$prc[$i]=fetch_achievement($i,$n);
					//新成就储存结构内，只会保存有进度的成就
					if(!empty($cpl[$i]) || !empty($prc[$i]))
					{
						// 到达999阶段的成就 替换为配置中预设的达成等级
						if($cpl[$i] == 999) 
						{
							if($i == 16 || $i == 17 || $i == 18 || $i == 19) 
							{
								//特判：四个结局成就阶段会变更为1...就这样了！
								$cpl[$i] = 1;
							}
							else
							{
								$cpl[$i] = $iarr['lvl'] ?: count($iarr['name']);
							}
						}
						$new_ach[$i]['l'] = $cpl[$i] ?: 0;
						$new_ach[$i]['v'] = $prc[$i] ?: 0;
					}
				}
			}
			$new_ach = json_encode($new_ach);
			$db->query("UPDATE {$gtablepre}users SET achievement='',achrev='$new_ach' WHERE username='".$udata['username']."'" );
			echo "更新了用户 {$udata['username']} 的成就状态<br>";
			$nums++;
			unset($cpl); unset($prc);
			unset($alist); unset($new_ach); unset($n);
		}
		elseif(!empty($udata['achievement']))
		{
			$db->query("UPDATE {$gtablepre}users SET achievement='' WHERE username='".$udata['username']."'" );
			$nums++;
			echo "删除了用户 {$udata['username']} 的旧成就数据<br>";
		}
		unset($udatas[$ukey]);
	}

	if(!$nums) echo "没有需要迁移的成就数据。<br>";
	else echo "<br>累计更新了{$nums}位用户的成就数据。<br>";

	echo $exit;

	return;
}

function nicksrev_update()
{
	global $db,$gtablepre,$gamecfg,$exit,$titles_list;

	$result = $db->query("SELECT * FROM {$gtablepre}users");	
	while($ur = $db->fetch_array($result)) 
	{
		$udatas[] = $ur;	
		unset($ur);		
	}

	$tlist = $titles_list;

	$nums = 0;

	foreach($udatas as $ukey => $udata)
	{
		# 更新旧头衔数据
		if(empty($udata['nicksrev']) || !is_numeric($udata['nick']))
		{
			# 先更新当前头衔
			$nkey = !empty($udata['nick']) && !is_numeric($udata['nick']) ? array_search($udata['nick'],$tlist) : 0;
			$db->query("UPDATE {$gtablepre}users SET nick='$nkey' WHERE username='{$udata['username']}'" );
			# 然后更新头衔列表
			$nicks = explode("/",$udata['nicks']);
			foreach($nicks as $nick)
			{
				$nkey = array_search($nick,$tlist);
				titles_get_new($udata,$nkey);
				unset($nkey);
			}
			$db->query("UPDATE {$gtablepre}users SET nicksrev='{$udata['nicksrev']}' WHERE username='{$udata['username']}'" );
			unset($nicks); 
			echo "更新了用户 {$udata['username']} 的头衔数据<br>";
			$nums++;
		}
		/*elseif(!empty($udata['nicks']))
		{
			$db->query("UPDATE {$gtablepre}users SET nicks='' WHERE username='".$udata['username']."'" );
			$nums++;
			echo "删除了用户 {$udata['username']} 的旧头衔数据<br>";
		}*/
		unset($udatas[$ukey]);
	}

	if(!$nums) echo "没有需要迁移的头衔数据。<br>";
	else echo "<br>累计更新了{$nums}位用户的头衔数据。<br>";

	echo $exit;

	return;
}

?>
