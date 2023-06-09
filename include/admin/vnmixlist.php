<?php
if(!defined('IN_ADMIN')) {
	exit('Access Denied');
}
include config('vnworld',$gamecfg);
include_once GAME_ROOT.'./include/vnworld/vnmix.func.php';

if(!isset($vncmd)){$vncmd = '';}

global $gamecfg;
$cache_file = config('vnmixitem',$gamecfg);
if(file_exists($cache_file))
{
	include $cache_file;
	$temp_vniteminfo = $vn_iteminfo + $vn_gm_iteminfo;
	if(isset($vn_mixinfo))
	{
		$temp_mixinfo = $vn_mixinfo;
		foreach($temp_mixinfo as $vn_key=>$vn_arr)
		{
			foreach($vn_arr as $vn_type => $vn_info)
			{
				if($vn_type == 'stuff')
				{
					for($i=0;$i<5;$i++)
					{
						if(empty($vn_info[$i])) $temp_mixinfo[$vn_key][$vn_type][$i] = '-';
					}
				}
				if($vn_type == 'result')
				{
					$temp_mixinfo[$vn_key][$vn_type][1] = $temp_vniteminfo[$vn_info[1]];
					$sk_arr = Array();
					if(!empty($vn_info[4]))
					{
						$temp_mixinfo[$vn_key][$vn_type][4] = parse_skinfo_desc($vn_info[4],$vn_info[1]);
					}
				}
			}
		}
	}
}

if(strpos($vncmd ,'del')===0)
{
	$vnid = substr($vncmd,4);
	if(!isset($vnid) || !isset($vn_mixinfo[$vnid]))
	{
		$cmd_info = "配方{$vnid}不存在，请重新输入！";
		//草草草 我已经变成goto的形状了 怎么会这样！
		return;
	}
	//先把配方从当前文件里取出来
	$flag = edit_vn_mixilst('del',$vnid);
	if(!is_array($flag))
	{
		$cmd_info = $flag;
		return;
	}
	else 
	{
		// 将配方重新格式化
		$earr = Array();
		// 格式化合成素材
		for($i=0;$i<5;$i++)
		{
			$earr['stf'.$i] = $flag['stuff'][$i];
		}
		// 格式化合成结果
		$earr['creator'] = $flag['name'];
		$earr['class'] = $flag['class'];
		$earr['itm'] = $flag['result'][0];
		$earr['itmk'] = $flag['result'][1];
		$earr['itme'] = $flag['result'][2];
		$earr['itms'] = $flag['result'][3];
		$earr['itmsk'] = $flag['result'][4];
		$earr['istatus'] = 0;
		// 保存至数据库
		$db->array_insert("{$gtablepre}vnmixitem", $earr);
	}
	adminlog('回退了配方',$vresult);
	$cmd_info = "已回退配方{$earr['itm']}！";
	return;
}
include template('admin_vnmixlist');


?>

