<?php

define('CURSCRIPT', 'vn_postitem');

require './include/common.inc.php';
require config('vnworld',$gamecfg);


/*** 登陆检测 ***/
if(!$cuser||!$cpass) { gexit($_ERROR['no_login'],__file__,__line__); }

$result = $db->query("SELECT * FROM {$tablepre}users WHERE username='$cuser'");
if(!$db->num_rows($result)) { gexit($_ERROR['login_check'],__file__,__line__); }
$udata = $db->fetch_array($result);
if($udata['password'] != $cpass) { gexit($_ERROR['wrong_pw'], __file__, __line__); }
if($udata['groupid'] <= 0) { gexit($_ERROR['user_ban'], __file__, __line__); }
/*** 登陆检测结束 ***/

//可能有隐患
global $gamefounder;
$gmflag = ($udata['groupid']>=$vnmix_editor_group || $udata['username']==$gamefounder) ? 1 : 0;

/*** 页面判定部分 ***/
if(!isset($vnmode)){$vnmode = 'none';}
$vmixlog = '';

//初始化显示用参数
for($i=0;$i<=4;$i++)
{
	${'uvs'.$i} = '';
	${'uvrsk'.$i} = 0;
}
$uvrn = ''; $uvrk = 0; $uvre = 1; $uvrs = 0;

//初始化允许选择的道具类别、属性
$temp_vn_iteminfo = $gmflag ? $vn_iteminfo+$vn_gm_iteminfo : $vn_iteminfo;
$temp_vn_itemspkinfo = $gmflag ? $vn_itemspkinfo+$vn_gm_itemspkinfo : $vn_itemspkinfo;

//是否道具名开启联想功能 需要使用sp_ilist.php先生成道具名词库
if($vnmix_name_assoc)
{
	$in_file = config('itmlist',$gamecfg);
	if(!file_exists($in_file))
	{
		require 'sp_ilist.php';
		get_itm_namelist();
	}
	include_once($in_file);
	$temp_item_namelist = $item_namelist;
}

//提交编辑
if(isset($exmode) && strpos($exmode,'ep')===0)
{	
	$edit_id = substr($exmode,2); 
	if(!isset($edit_id))
	{
		$edit_id = NULL;
		$vlog = "<span class='red'>错误：关键参数缺失，无法对配方进行编辑。</span><br>";
		goto error_edit2;
	}
	//重复检查一次要编辑的配方是否存在
	$flag = 0;
	$flag = check_keys_in_vn_cache_file($edit_id,$udata['username']);
	if($flag && !is_array($flag))
	{
		$vlog = $flag;
		goto error_edit2;
	}
	$edit_name = $flag['name']; $edit_result = $flag['result'][0];
	unset($flag);
	//通过检查，打包。
	$flag = filter_post_mixlist($vsname0,$vsname1,$vsname2,$vsname3,$vsname4,$vrname,$vrk,$vre,$vrs,$vrsk0,$vrsk1,$vrsk2,$vrsk3,$vrsk4);
	if($flag && !is_array($flag) || ((!isset($flag['result'])) || !isset($flag['stuff'])))
	{	//有非法参数，报错
		$vlog = $flag;
		goto error_edit2;
	}
	else 
	{	
		//通过检查，保存回文件
		if($edit_name !== $udata['username'])
		{
			$result = $db->query("SELECT * FROM {$tablepre}users WHERE username='$edit_name'");
			if(!$db->num_rows($result))
			{
				$vlog = "<span class='red'>错误：输入了错误的配方作者{$edit_name}。'</span><br>";
				goto error_edit2;
			}
			$odata = $db->fetch_array($result);
			$nm = $odata['username'];
			$flag = post_in_vn_cache_file($odata,$flag,$edit_id);
			unset($odata);
		}
		else 
		{
			$flag = post_in_vn_cache_file($udata,$flag,$edit_id);
		}
		if($flag)
		{	//返回占线信息
			$vlog = $flag;
		}
		else 
		{	
			if($gmflag) vn_adminlog('编辑了配方',$edit_result);
			$vlog .= '<span class="yellow">成功编辑了配方！</span><br>';
			$vdata['url'] = 'vnworld.php?vtips=1';
		}
	}
	error_edit2:
	$vdata['innerHTML']['vmixprint'] = $vlog;
	ob_clean();
	$jgamedata = compatible_json_encode($vdata);
	echo $jgamedata;
	ob_end_flush();	
}
//提交删除
elseif(isset($exmode) && strpos($exmode,'dp')===0)
{	
	$edit_id = substr($exmode,2); 
	if(!isset($edit_id))
	{
		$edit_id = NULL;
		$vlog = "<span class='red'>错误：关键参数缺失，无法删除配方。</span><br>";
		goto error_del;
	}
	$flag = 0;
	//先检查要删除的配方合法性
	$flag = check_keys_in_vn_cache_file($edit_id,$udata['username']);
	if($flag && !is_array($flag)) 
	{
		$vmixlog = $flag;
		goto error_del;
	}
	else 
	{
		$edit_name = $flag['name']; $edit_result = $flag['result'][0];
		unset($flag);
		if($edit_name !== $udata['username'])
		{
			$result = $db->query("SELECT * FROM {$tablepre}users WHERE username='$edit_name'");
			if(!$db->num_rows($result))
			{
				$vlog = "<span class='red'>错误：输入了错误的配方作者{$edit_name}。'</span><br>";
				goto error_edit2;
			}
			$odata = $db->fetch_array($result);
			$flag = post_in_vn_cache_file($odata,'del',$edit_id);
			unset($odata);
		}
		else 
		{
			$flag = post_in_vn_cache_file($udata,'del',$edit_id);
		}
		if($flag)
		{
			$vlog = $flag;
		}
		else 
		{
			if($gmflag) vn_adminlog('删除了配方',$edit_result);
			$vlog = '删除了配方。<br>';
			$vdata['url'] = 'vnworld.php?vtips=2';
		}
	}
	error_del:
	$vdata['innerHTML']['vmixprint'] = $vlog;
	ob_clean();
	$jgamedata = compatible_json_encode($vdata);
	echo $jgamedata;
	ob_end_flush();	
}
//提交审核
elseif(isset($exmode) && strpos($exmode,'cs')===0)
{	
	$edit = explode('+',substr($exmode,2));
	$edit_id = $edit[0]; $change_status = $edit[1];
	if(!isset($edit_id) || !isset($change_status))
	{
		$edit = $edit_id = $change_status = NULL;
		$vlog = "<span class='red'>错误：关键参数缺失，无法改变配方的审核状态。</span><br>";
		goto error_s;
	}
	if(!$gmflag)
	{
		$edit = $edit_id = $change_status = NULL;
		$vlog = "<span class='red'>错误：你没有权限审核配方。'</span><br>";
		goto error_s;
	}
	$flag = 0;
	//先检查要改变状态的配方合法性
	$flag = check_keys_in_vn_cache_file($edit_id,$udata['username']);
	if($flag && !is_array($flag)) 
	{
		$vmixlog = $flag;
		goto error_s;
	}
	else 
	{
		$edit_name = $flag['name']; $edit_result = $flag['result'][0];
		unset($flag);
		$result = $db->query("SELECT * FROM {$tablepre}users WHERE username='$edit_name'");
		if(!$db->num_rows($result))
		{
			$vlog = "<span class='red'>错误：输入了错误的配方作者。'</span><br>";
			goto error_s;
		}
		$odata = $db->fetch_array($result);
		$flag = post_in_vn_cache_file($odata,'chs'.$change_status,$edit_id);
		unset($odata);
		if($flag)
		{
			$vlog = $flag;
		}
		else 
		{
			if($gmflag) vn_adminlog('改变了以下配方状态',$edit_result,$change_status);
			$vlog = '成功变更了配方状态。<br>';
			$vdata['url'] = 'vnworld.php?vtips=3&vcs='.$change_status.'';
		}
	}
	error_s:
	$vdata['innerHTML']['vmixprint'] = $vlog;
	ob_clean();
	$jgamedata = compatible_json_encode($vdata);
	echo $jgamedata;
	ob_end_flush();	
}
elseif($vnmode=='编辑') //哇靠！从总览界面传回来的编辑申请。感觉怪怪的
{
	if(!isset($editid) || (!isset($editor)&&!$gmflag))
	{
		$editid = $editor = NULL;
		$vmixlog = "<span class='red'>错误：输入了错误的参数，无法进入编辑模式。</span><br>";
		goto error_edit1;
	}
	if($editor!==$udata['username'] && !$gmflag)
	{
		$editid = $editor = NULL;
		$vmixlog = "<span class='red'>错误：你没有权限编辑别人提交的配方。</span><br>";
		goto error_edit1;
	}
	$flag = check_keys_in_vn_cache_file($editid,$editor);
	if($flag && !is_array($flag)) 
	{
		$vmixlog = $flag;
	}
	else 
	{
		//向界面传递要编辑的内容
		$edit_arr = $flag;
		for($i=0;$i<=4;$i++)
		{
			if(isset($edit_arr['stuff'][$i])) ${'uvs'.$i} = $edit_arr['stuff'][$i];
			if(isset($edit_arr['result'][4][$i])) ${'uvrsk'.$i} = $edit_arr['result'][4][$i];
		}
		$uvrn = $edit_arr['result'][0]; $uvrk = $edit_arr['result'][1]; $uvre = $edit_arr['result'][2]; $uvrs = $edit_arr['result'][3]=='∞' ? 0 : $edit_arr['result'][3];
	}
	error_edit1:
	include template('vn_postitem');
}
//提交保存
elseif($vnmode=='postmode')
{
	$flag = false; $c2 = $udata['credits2']; $cid = $udata['uid']; $vrlist = Array();
	//检查钱够不够
	if($c2<$vnmix_c2_cost)
	{
		$vlog = '<span class="red">错误：切糕不足。提交一次合成需要消耗：'.$vnmix_c2_cost.'。</span><br>';
		goto errorlog;
	}
	//检查参数合法性并打包
	$flag = filter_post_mixlist($vsname0,$vsname1,$vsname2,$vsname3,$vsname4,$vrname,$vrk,$vre,$vrs,$vrsk0,$vrsk1,$vrsk2,$vrsk3,$vrsk4);
	if($flag && !is_array($flag) || ((!isset($flag['result'])) || !isset($flag['stuff'])))
	{	//参数非法，返回log
		$vlog = $flag;
		errorlog:
	}
	else 
	{
		//参数合法，将配方导入缓存文件
		$flag = post_in_vn_cache_file($udata,$flag);
		if($flag)
		{	//文件有锁，返回一个占线提示
			$vlog = $flag;
		}
		else 
		{	//成功提交，结算切糕
			$c2 -= $vnmix_c2_cost;
			$db->query("UPDATE {$tablepre}users SET credits2='$c2' WHERE uid='$cid'");
			$vlog .= '<span class="yellow">成功保存了配方！当前切糕数：'.$c2.'</span><br>';
			$vdata['url'] = 'vnworld.php?vtips=0';
		}
	}
	$vdata['innerHTML']['vmixprint'] = $vlog;
	ob_clean();
	$jgamedata = compatible_json_encode($vdata);
	echo $jgamedata;
	ob_end_flush();	
}
//显示主界面
elseif($vnmode=='none')
{
	include template('vn_postitem');
}

/*** 模块函数部分 ***/
//调用2个函数将提交的合成数据保存进本地文件。
function post_in_vn_cache_file($data,$arr,$t=NULL)
{
	$carr = load_vn_cache_file();
	if($carr<0)
	{
		return '<span class="red">有其他人正在提交合成，请等一会儿再试！</span><br>';
	}
	//根据uid输入提交的内容
	if($arr === 'del')
	{ 
		if(isset($carr[$t]))
		{
			unset($carr[$t]);
		}
		else 
		{
			unlock_vn_cache_file();
			return '<span class="red">错误：要删除的配方不存在！</span><br>';
		}
	}
	elseif(strpos($arr,'chs')===0)
	{
		$arr = substr($arr,3);
		if(isset($carr[$t]))
		{
			$carr[$t]['status'] = $arr;
			if($arr == 1)
			{
				writeover_vn_mixilst($carr[$t]);
				unset($carr[$t]);
			}
		}
		else 
		{
			unlock_vn_cache_file();
			return '<span class="red">错误：要审核的配方不存在！'.$t.count($carr[$data['uid']][$t]).'</span><br>';
		}
	}
	else 
	{
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
	}
	sort($carr);
	writeover_vn_cache_file($carr);
	return 0;
}
//检查某条配方是否存在于某人的提交中
function check_keys_in_vn_cache_file($num,$name)
{
	global $gamecfg,$gmflag;
	$cache_file = config('queue_vnmixitem',$gamecfg);
	if(!file_exists($cache_file)) 
	{
		return '<span class="red">严重错误：缓存文件不存在，请联系管理员！<br>';
	}
	include_once($cache_file);
	if(!isset($carr[$num])) 
	{
		return '<span class="red">错误：该配方不存在！<br>';
	}
	if(!$gmflag && $carr[$num]['name'] !== $name) 
	{
		return '<span class="red">错误：你没有权限编辑别人的配方！<br>';
	}
	return $carr[$num];
}
//打开本地缓存文件
function load_vn_cache_file()
{
	global $gamecfg;
	//加锁，文件被打开时其他玩家不能提交合成，防止冲突……但是不一定有用就是了。
	$lock_file = GAME_ROOT.'./gamedata/bak/vnmix.lock';
	if(file_exists($lock_file)) 
	{
		//锁还在，返回一个报错信息
		return -1;
	}
	else 
	{
		//加锁
		writeover($lock_file,' ');
		//返回获取到的本地缓存数组
		$cache_file = config('queue_vnmixitem',$gamecfg);
		if(!file_exists($cache_file)) writeover_vn_cache_file();
		require($cache_file);
		return $carr;
	}
}

//写入本地缓存文件
function writeover_vn_cache_file($carr=Array())
{
	global $checkstr,$gamecfg;
	$cache_file = config('queue_vnmixitem',$gamecfg);
	$cont = str_replace('?>','',str_replace('<?','<?php',$checkstr));
	$cont .= '$carr = ' . var_export($carr,1).";\r\n?>";
	writeover($cache_file, $cont);
	chmod($cache_file,0777);
	unlock_vn_cache_file();
	return;
}

//写入配方文件
function writeover_vn_mixilst($varr=Array())
{
	global $checkstr,$gamecfg;
	$cache_file = config('vnmixitem',$gamecfg);
	if(file_exists($cache_file))
	{
		include_once($cache_file);
	}
	else 
	{
		$vn_mixinfo = Array();
	}
	$narr = Array();
	$narr['class'] = 'VN';
	foreach($varr as $key=>$arr)
	{
		if($key == 'stuff')
		{
			foreach($arr as $s_key => $s_name)
			{
				$narr['stuff'][] = $s_name;
			}
		}
		elseif($key == 'result')
		{
			foreach($arr as $r_key => $r_value)
			{
				if($r_key == 4)
				{
					foreach($r_value as $sk_value) if(isset($sk_value)) $narr['result'][4] .= $sk_value;
				}
				else 
				{
					$narr['result'][$r_key] = $r_value;
				}
			}
		}
	}
	$narr['name'] = $varr['name'];
	$vn_mixinfo[]=$narr;
	sort($vn_mixinfo);
	global $checkstr;
	$cont = str_replace('?>','',str_replace('<?','<?php',$checkstr));
	$cont .= '$vn_mixinfo = ' . var_export($vn_mixinfo,1).";\r\n?>";
	writeover($cache_file, $cont);
	chmod($cache_file,0777);
	//unlock_vn_cache_file();
	return;
}

function unlock_vn_cache_file()
{
	//完成流程后解锁文件
	$lock_file = GAME_ROOT.'./gamedata/bak/vnmix.lock';
	unlink($lock_file);
}

//检查输入的素材合法性。非法返回log，合法返回一个打包好的数组。
function filter_post_mixlist($vsname0,$vsname1,$vsname2,$vsname3,$vsname4,$vrname,$vrk,$vre,$vrs,$vrsk0,$vrsk1,$vrsk2,$vrsk3,$vrsk4)
{
	global $gmflag,$temp_vn_iteminfo,$temp_vn_itemspkinfo,$result_tips;
	$vlog = ''; $slist = Array(); $sklist = Array();
	//检查道具用途
	if(!isset($vrk) || !isset($temp_vn_iteminfo[$vrk]))
	{
		$vlog = '<span class="red">错误：输入了无效的'.$result_tips[1].'。</span><br>';
		return $vlog;
	}
	//检查道具效果、耐久
	if($vre<1 || $vre>16777214)
	{
		$vlog = '<span class="red">错误：输入了无效的'.$result_tips[2].'（'.$result_tips[2].'范围：1~16777214）。</span><br>';
		return $vlog;
	}
	if($vrs<0 || $vrs>65535)
	{
		$vlog = '<span class="red">错误：输入了无效的'.$result_tips[3].'（'.$result_tips[3].'范围：0~65535）。</span><br>';
		return $vlog;
	}
	//检查道具名
	$vrname = preg_replace('/[,\#;\p{Cc}]+|锋利的|电气|毒性|钉|\[.*\]|[\r\n]|-改|<|>|\"/u','', $vrname);
	$vrname = preg_replace('/^\s+|\s+$/m', '', $vrname);
	if(empty($vrname) || mb_strlen($vrname,'utf-8')>30)
	{
		$vlog .= '<span class="red">错误：'.$result_tips[0].'的名称为空或长度超过了30个字符。</span><br>';
		return $vlog;
	}
	//检查合成素材、属性
	for($s=0;$s<=4;$s++)
	{
		//检查属性合法性
		if(!isset($temp_vn_itemspkinfo[${'vrsk'.$s}]))
		{
			$vlog = '<span class="red">错误：输入了无效的'.$result_tips[($s+4)].'。</span><br>';
			return $vlog;
		}
		else
		{
			if(${'vrsk'.$s} != 'none' && !in_array(${'vrsk'.$s},$sklist)) $sklist[] = ${'vrsk'.$s};
		}
		//检查素材名
		${'vsname'.$s} = preg_replace('/[,\#;\p{Cc}]+|锋利的|电气|毒性|钉|\[.*\]|[\r\n]|-改|<|>|\"/u','',${'vsname'.$s});
		//只过滤以首格空格开头或以尾部空格结尾的部分，不改变后面内容里的空格
		${'vsname'.$s} = preg_replace('/^\s+|\s+$/m', '', ${'vsname'.$s});
		if(empty(${'vsname'.$s}) || mb_strlen(${'vsname'.$s},'utf-8')>30)
		{
			//$vlog .= '<span class="red">错误：'.$stuff_tips[$s].'的名称为空或长度超过了30个字符。</span><br>';
		}
		else
		{
			if(count($slist)<5) $slist[] = ${'vsname'.$s};
		}
	}
	if(count($slist)<2)
	{
		$vlog .= '<span class="red">错误：至少要添加2种符合条件的合成素材。（素材名称不能为空，且长度不能超过30个字符）</span><br>';
		return $vlog;
	}
	//通过合法性检测 导入新配方
	$newarr = Array();
	//导入合成素材
	foreach($slist as $st) $newarr['stuff'][] = $st;
	//导入合成结果
	$newarr['result'][0] = $vrname;
	$newarr['result'][1] = $vrk;
	$newarr['result'][2] = (int)$vre;
	$newarr['result'][3] = (int)$vrs == 0 ? '∞' : (int)$vrs;
	//导入合成属性
	foreach($sklist as $sk) $newarr['result'][4][] = $sk;
	//打上检疫标签
	$newarr['status'] = 0;
	return $newarr;
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
