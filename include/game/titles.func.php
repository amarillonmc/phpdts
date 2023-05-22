<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}

//反正应该不会重复获得头衔……偷懒
//还是会重复的 反正没什么工作量
function get_title($t,$n)
{
	/*global $gamecfg,$name,$db,$gtablepre;
	require config("gamecfg",$gamecfg);
	$result = $db->query("SELECT nicks FROM {$gtablepre}users WHERE username = '$n'");
	$k = $db->result($result, 0);
	if (strpos($k,$t)===false){
		$cf = GAME_ROOT.'./gamedata/clearlog.php';
		$d = "$n".','."$t\n";
		writeover($cf,$d,'ab+');
		$k=$k.'/'.$t;
	}*/
	global $db,$gtablepre,$titles_list;

	# 使用旧函数获取头衔时，转为新格式

	if(in_array($t,$titles_list))
	{
		$udata = fetch_userdata_by_username($n);
		$tkey = array_search($t,$titles_list);
		titles_get_new($udata,$tkey);
		$db->query("UPDATE {$gtablepre}users SET nicksrev='{$udata['nicksrev']}' WHERE username='".$n."'" );
	}
	else 
	{
		global $log;
		$log .= "要获取的头衔{$t}不存在，请在\$titles_list内为其添加编号！<br>";
		return;
	}
}

function titles_get_new(&$udata,$tkey,$mode=0)
{
	global $titles_list;

	$flag = 0;
	$tkey = (int)$tkey;

	if(empty($udata['nicksrev'])) $udata['nicksrev'] = Array('nicks' => Array(0));
	$nicksrev = &$udata['nicksrev'];
	$nicksrev = is_array($nicksrev) ? $nicksrev : json_decode($nicksrev,true);

	# 要获得的头衔编号必须存在于头衔列表内
	if(isset($titles_list[$tkey]) && !in_array($tkey,$nicksrev['nicks']))
	{
		$nicksrev['nicks'][] = $tkey;
		$flag = 1;
	}

	$nicksrev = json_encode($nicksrev);

	return $flag;
}

function titles_delete(&$udata,$tkey,$mode=0)
{
	global $titles_list;

	$flag = 0;
	$tkey = (int)$tkey;

	$nicksrev = &$udata['nicksrev'];
	$nicksrev = is_array($nicksrev) ? $nicksrev : json_decode($nicksrev,true);

	# 要获得的头衔编号必须存在于头衔列表内
	if(isset($titles_list[$tkey]) && in_array($tkey,$nicksrev['nicks']))
	{
		unset($nicksrev['nicks'][array_search($tkey,$nicksrev['nicks'])]);
		$flag = 1;
	}

	$nicksrev = json_encode($nicksrev);

	return $flag;
}

function titles_get_desc($tkey,$mode=0)
{
	global $db,$gtablepre,$gamecfg;
	include config("titles",$gamecfg);

	# 兼容旧显示方式
	if($mode && !is_numeric($tkey)) $tkey = array_search($tkey,$titles_list);

	$tkey = (int)$tkey;
	$n = $titles_list[$tkey] ?: $tkey;

	if(isset($title_desc[$tkey]))
	{
		# 存在图片格式，直接返回；
		if(isset($title_desc[$tkey]['img']))
		{
			$n_desc = "<img src=\" ".$title_desc[$tkey]['img']."\">";
			return $n_desc;
		}
		else
		{
			$n_desc = "<span ";
		}
		# 存在样式，赋予一个样式：
		if(isset($title_desc[$tkey]['class']))
		{
			$n_class = "class=\"{$title_desc[$tkey]['class']}\" ";
			$n_desc .= $n_class;
		}
		# 存在tooltip，赋予一个tooltip：
		if(isset($title_desc[$tkey]['title']))
		{
			$n_title = "tooltip=\"{$title_desc[$tkey]['title']}\" ";
			$n_desc .= $n_title;
		}
		$n_desc .= ">".$n."</span>";
		return $n_desc;
	}
	return $n;
}

//格式化头衔tooltip
function get_titles_desc($n)
{
	global $title_desc,$titles_list;

	if(isset($title_desc[$n]))
	{
		# 存在图片格式，直接返回；
		if(isset($title_desc[$n]['img']))
		{
			$n_desc = "<img src=\" ".$title_desc[$n]['img']."\">";
			return $n_desc;
		}
		else
		{
			$n_desc = "<span ";
		}
		# 存在样式，赋予一个样式：
		if(isset($title_desc[$n]['class']))
		{
			$n_class = "class=\"{$title_desc[$n]['class']}\" ";
			$n_desc .= $n_class;
		}
		# 存在tooltip，赋予一个tooltip：
		if(isset($title_desc[$n]['title']))
		{
			$n_title = "tooltip=\"{$title_desc[$n]['title']}\" ";
			$n_desc .= $n_title;
		}
		$n_desc .= ">".$n."</span>";
		return $n_desc;
	}
	return $n;
}

//格式化头衔奖励
function get_title_valid($n)
{
	global $title_valid;

	if(!empty($title_valid[$n]))
	{
		return $title_valid[$n];
	}
	return;
}

//应用头衔奖励中的加减乘除变化 $value=原值 $change=变动值
function parse_title_valid_operators($value,$change)
{
	if(strpos($change,'[:')===false || strpos($change,':]')===false) return $change;
	
	//先用这种比较搞的方式来实现吧，如果未来有更多需求出现再换一个智能一点的办法
	if(strpos($change,'[:+=:]')!==false)
	{
		$value += str_replace('[:+=:]','',$change);
	}
	elseif(strpos($change,'[:-=:]')!==false)
	{
		$value -= str_replace('[:-=:]','',$change);
	}
	elseif(strpos($change,'[:*=:]')!==false)
	{
		$value *= str_replace('[:*=:]','',$change);
	}
	elseif(strpos($change,'[:/=:]')!==false)
	{
		$value /= str_replace('[:/=:]','',$change);
	}
	return round($value);
}

?>
