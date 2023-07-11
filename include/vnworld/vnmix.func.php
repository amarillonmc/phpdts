<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}

/*** 模块函数部分 ***/

# 格式化显示从数据库中读出的待审核的配方文件
function parse_queue_vnmix_info($carr)
{
	include_once GAME_ROOT.'./include/game/itemplace.func.php';
	// 格式化素材来源
	for($i=0;$i<5;$i++)
	{
		if(!empty($carr['stf'.$i]))
		{
			$snm = $carr['stf'.$i];
			$tooltipinfo = get_item_place($snm);
			if(!empty($tooltipinfo)) $carr['stf'.$i] = "<span tooltip=\"".$tooltipinfo."\">".$snm."</span>";
		}
	}
	// 格式化名称
	$carr['itm_desc'] = parse_nameinfo_desc($carr['itm']);
	// 格式化类别
	$carr['itmk_desc'] = parse_kinfo_desc($carr['itmk'],$carr['itmsk']);
	// 合并显示类
	$carr['result'] = $carr['itmk_desc'].'/'.$carr['itme'].'/'.$carr['itms'];
	// 格式化属性
	if(!empty($carr['itmsk']))
	{
		$carr['itmsk_desc'] = parse_skinfo_desc($carr['itmsk'],$carr['itmk']);
		$carr['result'] .= '/'.$carr['itmsk_desc'];
	}
	return $carr;
}

# 从数据库中读取全部或指定待审核配方文件
function get_queue_vnmix_list($id=NULL)
{
	global $db,$tablepre,$gtablepre;
	if(isset($id))
	{
		$id = (int)$id;
		$result = $db->query("SELECT * FROM {$gtablepre}vnmixitem WHERE iid={$id}");
		if($db->num_rows($result))
		{
			return $db->fetch_array($result);
		}
	}
	else 
	{
		$result = $db->query("SELECT * FROM {$gtablepre}vnmixitem ");
		if($db->num_rows($result))
		{ 
			while($t = $db->fetch_array($result,MYSQLI_ASSOC))
			{	
				$carr[$t['iid']] = $t;
				unset($carr[$t['iid']]['iid']);
			}
			return $carr;
		}
	}
	return;
}

# 检查指定配方id是否存在
function check_exists_queue_vnmix($id,$name,$gmflag=0)
{
	$varr = get_queue_vnmix_list($id);
	if(empty($varr)) return '<span class="red">错误：该配方不存在！<br>';
	if(empty($gmflag) && $name != $varr['creator']) '<span class="red">错误：你没有权限编辑别人的配方！<br>';
	return $varr;
}

# 检查配方内容合法性
function check_post_queue_vmix(&$arr,$gmflag=0)
{
	include config('vnworld',$gamecfg);
	# 检查合成素材
	$snums = 0;
	for($i=0;$i<5;$i++)
	{
		if(!empty($arr['stf'.$i]))
		{
			$flag = check_post_queue_vitm($arr['stf'.$i],1);
			if($flag) return $flag;
			$snums ++;
		}
	}
	if($snums<2 || $snums>5) return "合成素材数量非法，需要2-5种合成素材。<br>";
	# 检查配方道具名
	$flag = check_post_queue_vitm($arr['itm']);
	if($flag) return $flag;
	# 检查道具用途
	$flag = check_post_queue_vitmk($arr['itmk'],$gmflag);
	if($flag) return $flag;
	# 检查道具效果
	$flag = check_post_queue_vitme($arr['itme']);
	if($flag) return $flag;
	# 检查道具耐久
	$flag = check_post_queue_vitms($arr['itms']);
	if($flag) return $flag;
	# 检查道具属性
	if(!empty($arr['itmsk']))
	{
		$tmp_sk = get_itmsk_array($arr['itmsk']);
		if(count($tmp_sk)>$vnmix_max_sk) return "合成结果最多只能拥有{$vnmix_max_sk}种属性。<br>";
		foreach($tmp_sk as $sk)
		{
			$flag = check_post_queue_vitmsk($sk,$gmflag);
			if($flag) return $flag;
		}
	}
	return;
}

# 检查配方道具名
function check_post_queue_vitm(&$itm,$stf=0)
{
	//检查道具名
	$itm = preg_replace('/[,\#;\p{Cc}]+|锋利的|电气|毒性|[\r\n]|-改|<|>|\"/u','',$itm);
	$itm = preg_replace('/^\s+|\s+$/m', '',$itm);
	if(!$stf && !$itm)
	{
		$vlog .= '<span class="red">错误：道具名不能为空。</span><br>';
		return $vlog;
	}
	if(mb_strlen($itm,'utf-8')>40)
	{
		$vlog .= '<span class="red">错误：道具名长度超过了40个字符。</span><br>';
		return $vlog;
	}
	return;
}

# 检查道具用途
function check_post_queue_vitmk(&$itmk,$gmflag=0)
{
	include config('vnworld',$gamecfg);
	$temp_vn_iteminfo = $gmflag ? $vn_iteminfo+$vn_gm_iteminfo : $vn_iteminfo;
	if(!isset($itmk) || !isset($temp_vn_iteminfo[$itmk]))
	{
		$vlog = '<span class="red">错误：输入了无效的道具用途。</span><br>';
		return $vlog;
	}
	return;
}

# 检查道具效果
function check_post_queue_vitme(&$itme)
{
	//检查道具效果、耐久
	if($itme<1 || $itme>16777214)
	{
		$vlog = '<span class="red">错误：输入了无效的道具效果（道具效果范围：1~16777214）。</span><br>';
		return $vlog;
	}
	return;
}

# 检查道具耐久
function check_post_queue_vitms(&$itms)
{
	global $nosta;
	//检查道具耐久
	if($itms<0 || $itms>65535)
	{
		$vlog = '<span class="red">错误：输入了无效的道具耐久（道具耐久范围：0~65535）。</span><br>';
		return $vlog;
	}
	if($itms == 0) $itms = $nosta;
	return;
}

# 检查道具属性
function check_post_queue_vitmsk($sk,$gmflag=0)
{
	include config('vnworld',$gamecfg);
	$temp_vn_itemspkinfo = $gmflag ? $vn_itemspkinfo+$vn_gm_itemspkinfo : $vn_itemspkinfo;
	if(!isset($temp_vn_itemspkinfo[$sk]))
	{
		$vlog = "<span class='red'>错误：选择了无效的道具属性{$sk}。</span><br>";
		return $vlog;
	}
	return;
}

# 审核通过，将配方写入配方文件
function writeover_vn_mixilst($varr=Array())
{
	global $checkstr,$gamecfg;
	$cache_file = config('vnmixitem',$gamecfg);
	if(file_exists($cache_file)) include $cache_file;
	else $vn_mixinfo = Array();

	# 将未审批配方格式化
	$narr = Array();
	$narr['class'] = isset($varr['class']) ? $varr['class'] : 'item';
	foreach($varr as $key => $arr)
	{
		if(strpos($key,'stf')!==false)
		{
			$id = str_replace("stf","",$key);
			if(!empty($arr)) $narr['stuff'][$id] = $arr;
		}
		elseif(strpos($key,'itm')!==false)
		{
			if($key == 'itm') $narr['result'][0] = $arr;
			if($key == 'itmk') $narr['result'][1] = $arr;
			if($key == 'itme') $narr['result'][2] = $arr;
			if($key == 'itms') $narr['result'][3] = $arr;
			if($key == 'itmsk') $narr['result'][4] = $arr;
		}
		else 
		{
			$narr[$key] = $arr;
		}
	}
	$narr['name'] = $varr['creator'];
	unset($narr['iid']); unset($narr['creator']); unset($narr['istatus']);

	# 向本地配方表中加入配方
	$vn_mixinfo[]=$narr;
	sort($vn_mixinfo);
	global $checkstr;
	$cont = str_replace('?>','',str_replace('<?','<?php',$checkstr));
	$cont .= '$vn_mixinfo = ' . var_export($vn_mixinfo,1).";\r\n?>";
	file_put_contents($cache_file,$cont,LOCK_EX);
	return;
}


# 修改本地配方文件
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
	file_put_contents($cache_file,$cont,LOCK_EX);
	//writeover($cache_file, $cont);
	//chmod($cache_file,0777);
	unlink($lock_file);
	return $varr;
}

function vn_adminlog($op,$an1='',$an2='',$an3=''){
	global $now,$cuser;
	$alfile = GAME_ROOT.'./gamedata/adminlog.php';
	if($op){
		$aldata = "$now,$cuser,$op,$an1,$an2,$an3,\n";
		writeover($alfile,$aldata,'ab+');
	}
	return;
}


?>
