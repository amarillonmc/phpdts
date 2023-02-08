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

function get_title_desc($n){
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
?>
