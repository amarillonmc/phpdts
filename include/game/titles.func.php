<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}


//反正应该不会重复获得头衔……偷懒
//还是会重复的 反正没什么工作量
function get_title($t,$n){
	global $gamecfg,$name,$db,$gtablepre;
	require config("gamecfg",$gamecfg);
	$result = $db->query("SELECT nicks FROM {$gtablepre}users WHERE username = '$n'");
	$k = $db->result($result, 0);
	if (strpos($k,$t)===false){
		$cf = GAME_ROOT.'./gamedata/clearlog.php';
		$d = "$n".','."$t\n";
		writeover($cf,$d,'ab+');
		$k=$k.'/'.$t;
	}
	$db->query("UPDATE {$gtablepre}users SET nicks='$k' WHERE username='".$n."'" );
}

//格式化头衔tooltip
function get_title_desc($n)
{
	global $title_desc;

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
