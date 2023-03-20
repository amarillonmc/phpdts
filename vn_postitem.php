<?php

define('CURSCRIPT', 'vn_postitem');

require './include/common.inc.php';
include config('vnworld',$gamecfg);
include_once GAME_ROOT.'./include/vnworld/vnmix.func.php';

/*** 登陆检测 ***/
if(!$cuser||!$cpass) { gexit($_ERROR['no_login'],__file__,__line__); }

$result = $db->query("SELECT * FROM {$tablepre}users WHERE username='$cuser'");
if(!$db->num_rows($result)) { gexit($_ERROR['login_check'],__file__,__line__); }
$udata = $db->fetch_array($result);
if($udata['password'] != $cpass) { gexit($_ERROR['wrong_pw'], __file__, __line__); }
if($udata['groupid'] <= 0) { gexit($_ERROR['user_ban'], __file__, __line__); }
$gmflag = ($udata['groupid']>=$vnmix_editor_group || $udata['username']==$gamefounder) ? 1 : 0;
/*** 登陆检测结束 ***/

$vmixlog = ''; $flag = NULL; 
$carr = Array(
	'class' => 'item',
	'itm' => '请填写道具名',
	'itmk' => 'Y',
	'itme' => 0,
	'itms' => 0,
	'itmsk' => '',
);
if(!isset($vnmode)) $vnmode = '';

# 编辑指定配方，参数初始化 
if(isset($_POST['editid']) || isset($_POST['editor']))
{
	if($_POST['editor'] != $cuser) gexit("编辑者参数非法！", __file__, __line__);
	$carr = check_exists_queue_vnmix($_POST['editid'],$_POST['editor'],$gmflag);
	if(!is_array($carr)) gexit($flag, __file__, __line__);
	if($carr['itms'] == $nosta) $carr['itms'] = 0;
	if(!empty($carr['itmsk']))
	{
		$carr['itmsk_desc'] = parse_info_desc(get_itmsk_array($carr['itmsk']),'sk',$carr['itmk']);
	}
	else 
	{
		$carr['itmsk'] = '';
		$carr['itmsk_desc'] = '-';
	}
	$self_edit_flag = 1;
}

# 传入了增删查改指令，初始化对应ID
if(isset($exmode))
{
	$exarr = explode('+',$exmode);
	$exmode = $exarr[0];
	$edit_id = (int)$exarr[1];
	// 检查是否存在ID
	if(!isset($edit_id)) gexit("关键参数缺失，无法对配方进行编辑。", __file__, __line__);
	// 再检查一次要删除的配方是否存在
	$earr = check_exists_queue_vnmix($edit_id,$udata['username'],$gmflag);
	if(!is_array($earr)) gexit($earr, __file__, __line__);
	// 检查审核配方的状态合法性
	if($exmode == 'cs')
	{
		if(!$gmflag) gexit("你没有权限编辑配方状态！", __file__, __line__);
		if(empty($exarr[2])) gexit("关键参数缺失，无法对配方进行审核。", __file__, __line__);
		$edit_st = (int)$exarr[2];
	}
}

# 初始化允许选择的道具类别、属性
$temp_vn_iteminfo = $gmflag ? $vn_iteminfo+$vn_gm_iteminfo : $vn_iteminfo;
$temp_vn_itemspkinfo = $gmflag ? $vn_itemspkinfo+$vn_gm_itemspkinfo : $vn_itemspkinfo;

# 是否开启道具名联想功能 需要使用sp_ilist.php先生成道具名词库
if($vnmix_name_assoc)
{
	$in_file = config('itmlist',$gamecfg);
	include($in_file);
	$temp_item_namelist = $item_namelist;
}

if(isset($exmode))
{	
	# 提交编辑
	if($exmode == 'ep')
	{
		// 打包
		for($i=0;$i<5;$i++) $earr['stf'.$i] = ${'vsname'.$i};
		$earr['itm'] = $vrname; $earr['itmk'] = $vrk; $earr['itme'] = $vre; $earr['itms'] = $vrs; $earr['itmsk'] = $vrsk; $earr['class'] = $vrck; 
		// 执行各项参数合法性检查
		$flag = check_post_queue_vmix($earr,$gmflag);
		if($flag)
		{
			$vlog = $flag;
			goto error_flag;
		}
		// 通过检查，更新对应配方
		$db->array_update("{$tablepre}vnmixitem",$earr,"iid = '$edit_id'");
		// 保存对应log
		if($gmflag) vn_adminlog('编辑了配方',$edit_result);
		$vlog .= '<span class="yellow">成功编辑了配方！</span><br>';
		$vdata['url'] = 'vnworld.php?vtips=1';
		goto error_flag;
	}
	# 提交删除
	elseif($exmode == 'dp')
	{
		$db->query("DELETE FROM {$tablepre}vnmixitem WHERE iid = '$edit_id'");
		if($gmflag) vn_adminlog('删除了配方',$edit_result);
		$vlog = '删除了配方。<br>';
		$vdata['url'] = 'vnworld.php?vtips=2';
		goto error_flag;
	}
	# 提交审核
	elseif($exmode == 'cs')
	{
		# 通过审核、保存配方至本地文件
		if($edit_st == 1)
		{
			# 提交前再进行一遍合法性校验
			$flag = check_post_queue_vmix($earr,$gmflag);
			if($flag)
			{
				$vlog = $flag;
				goto error_flag;
			}
			writeover_vn_mixilst($earr);
			$db->query("DELETE FROM {$tablepre}vnmixitem WHERE iid = '$edit_id'");
			$vdata['url'] = 'vnworld.php?vtips=3&vcs='.$edit_st.'';
		}
		# 审核不通过
		else 
		{
			$db->query("UPDATE {$tablepre}vnmixitem SET istatus = '$edit_st' WHERE iid = '$edit_id'");
			$vdata['url'] = 'vnworld.php?vtips=4&vcs='.$edit_st.'';
		}
		if($gmflag) vn_adminlog('改变了以下配方状态',$edit_result,$edit_st);
		$vlog = '成功变更了配方状态。<br>';
		goto error_flag;
	}
}
# 新建配方
elseif($vnmode=='postmode')
{
	$c2 = $udata['credits2']; $earr = Array();
	//检查钱够不够
	if($c2<$vnmix_c2_cost)
	{
		$vlog = '<span class="red">错误：切糕不足。提交一次合成需要消耗：'.$vnmix_c2_cost.'。</span><br>';
		goto error_flag;
	}
	// 打包
	for($i=0;$i<5;$i++) $earr['stf'.$i] = ${'vsname'.$i};
	$earr['itm'] = $vrname; $earr['itmk'] = $vrk; $earr['itme'] = $vre; $earr['itms'] = $vrs; $earr['itmsk'] = $vrsk; $earr['class'] = $vrck; 
	// 执行各项参数合法性检查
	$flag = check_post_queue_vmix($earr,$gmflag);
	if($flag)
	{
		$vlog = $flag;
		goto error_flag;
	}
	// 参数合法，补全剩余参数
	$earr['creator'] = $udata['username']; $earr['istatus'] = 0; 
	// 保存至数据库
	$db->array_insert("{$tablepre}vnmixitem", $earr);
	// 结算切糕
	$c2 -= $vnmix_c2_cost;
	$db->query("UPDATE {$tablepre}users SET credits2='$c2' WHERE uid='$cid'");
	$vlog .= '<span class="yellow">成功保存了配方！当前切糕数：'.$c2.'</span><br>';
	$vdata['url'] = 'vnworld.php?vtips=0';
	goto error_flag;
}
# ajax
elseif(!empty($vdata))
{
	error_flag:
	$vdata['innerHTML']['vmixprint'] = $vlog;
	ob_clean();
	$jgamedata = compatible_json_encode($vdata);
	echo $jgamedata;
	ob_end_flush();	
}
# 主页面
else
{
	include template('vn_postitem');
}

?>
