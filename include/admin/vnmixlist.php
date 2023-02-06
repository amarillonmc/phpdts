<?php
if(!defined('IN_ADMIN')) {
	exit('Access Denied');
}
require config('vnworld',$gamecfg);
//require './include/game/itemplace.func.php';

if(!isset($vncmd)){$vncmd = 'none';}

global $gamecfg;
$cache_file = config('vnmixitem',$gamecfg);
if(file_exists($cache_file))
{
	$temp_vniteminfo = $vn_iteminfo + $vn_gm_iteminfo;
	include_once($cache_file);
	$temp_mixinfo = $vn_mixinfo;
	if(isset($vn_mixinfo))
	{
		foreach($temp_mixinfo as $vn_key=>$vn_arr)
		{
			foreach($vn_arr as $vn_type => $vn_info)
			{
				/*if($vn_type == 'stuff')
				{
					// 格式化素材来源
					foreach($vn_info as $sid => $snm)
					{
						$temp_mixinfo[$vn_key][$vn_type][$sid] = "<span tooltip=\"".get_item_place($snm)."\">".$snm."</span>";
					}
				}*/
				if($vn_type == 'result')
				{
					$temp_mixinfo[$vn_key][$vn_type][1] = $temp_vniteminfo[$vn_info[1]];
					$sk_arr = Array();
					if(!empty($vn_info[4]))
					{
						$sk_arr = get_itmsk_array($vn_info[4]);
						$temp_mixinfo[$vn_key][$vn_type][4] = '';
						foreach($sk_arr as $sk_value)
						{
							if(!empty($temp_mixinfo[$vn_key][$vn_type][4])) $temp_mixinfo[$vn_key][$vn_type][4] .= '+'.parse_itm_desc($sk_value,'sk');
							else $temp_mixinfo[$vn_key][$vn_type][4] = parse_itm_desc($sk_value,'sk');
						}
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
		$vname = $flag['name']; $vresult = $flag['result'][0];
		$result = $db->query("SELECT * FROM {$tablepre}users WHERE username='$vname'");
		if(!$db->num_rows($result))
		{
			$cmd_info = '配方的作者信息无效！请检查缓存文件。';
			return;
		}
		$vdata = $db->fetch_array($result);
		post_back_vn_cache_file($vdata,$flag);
	}
	adminlog('回退了配方',$vresult);
	$cmd_info = "已回退配方{$vnid}！";
	return;
}
include template('admin_vnmixlist');


//修改配方文件
function edit_vn_mixilst($varr,$t)
{
	global $checkstr,$gamecfg;
	//先加锁
	$lock_file = GAME_ROOT.'./gamedata/bak/vnmix2.lock';
	if(file_exists($lock_file)) 
	{
		return '有其他管理员正在进行编辑操作，请稍等一会儿再试！';
	}
	else 
	{
		$cache_file = config('vnmixitem',$gamecfg);
		if(file_exists($cache_file))
		{
			//加锁
			writeover($lock_file,' ');
			include_once($cache_file);
			global $vn_mixinfo;
		}
		else 
		{
			return '合成配方文件不存在！不能进行编辑操作。';
		}
	}

	if($varr==='del' && isset($vn_mixinfo[$t]))
	{
		$varr = $vn_mixinfo[$t];
		$varr['status'] = 0;
		unset($vn_mixinfo[$t]);
	}
	sort($vn_mixinfo);
	global $checkstr;
	$cont = str_replace('?>','',str_replace('<?','<?php',$checkstr));
	$cont .= '$vn_mixinfo = ' . var_export($vn_mixinfo,1).";\r\n?>";
	writeover($cache_file, $cont);
	chmod($cache_file,0777);
	unlink($lock_file);
	return $varr;
}

//为什么要把这个函数在这里又重写一遍？……因为引用已经乱套了……呃啊，杀了我吧！
function post_back_vn_cache_file($data,$arr,$t=NULL)
{
	global $checkstr,$gamecfg;
	$lock_file = GAME_ROOT.'./gamedata/bak/vnmix.lock';
	if(file_exists($lock_file)) 
	{
		//锁还在，返回一个报错信息
		return '有其他人正在提交或编辑合成，请稍后再试！<br>';
	}
	else 
	{
		writeover($lock_file,' ');
	}
	$file = config('queue_vnmixitem',$gamecfg);
	if(!file_exists($file))
	{
		$carr = Array();
	}	
	else 
	{
		include_once($file);
	}

	//把字符串还原为数组
	if(!empty($arr['result'][4]))
	{
		$arr['result'][4] = get_itmsk_array($arr['result'][4]);
	}
	
	if(isset($t))
	{
		$arr['name'] = $carr[$t]['name'];
		$arr['status'] = $carr[$t]['status'];
		$carr[$t] = $arr;
	}
	else 
	{
		$arr['name'] = $data['username'];
		$carr[] = $arr;
	}
	sort($carr);
	$cont = str_replace('?>','',str_replace('<?','<?php',$checkstr));
	$cont .= '$carr = ' . var_export($carr,1).";\r\n?>";
	writeover($file, $cont);
	chmod($file,0777);
	unlink($lock_file);
	return 0;
}


?>

