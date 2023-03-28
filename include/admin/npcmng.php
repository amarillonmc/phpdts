<?php
if(!defined('IN_ADMIN')) {
	exit('Access Denied');
}
if(!isset($command)){$command = 'list';}
if(!isset($start)){$start = 0;}
if(!isset($checkmode)){$checkmode = '';}
if(!isset($checkinfo)){$checkinfo = '';}
if(!isset($pagemode)){$pagemode = '';}

$cmd_info = '';
$start = getstart($start,$pagemode);
$resultinfo = '';
if($command != 'submitedit'){	
	$npcdata = dbsearch($start,$checkmode,$checkinfo);
}

$in_file = config('itmlist',$gamecfg);
if(!file_exists($in_file))
{
	require 'sp_ilist.php';
	get_itm_namelist();
}
include_once($in_file);
$temp_item_namelist = $item_namelist;

if($command == 'kill' || $command == 'live' || $command == 'del') {
	$operlist = $operlist2 = $dfaillist = $gfaillist = array();
	for($i=0;$i<$showlimit;$i++){
		if(isset(${'npc_'.$i})) {
			if(isset($npcdata[$i]) && $npcdata[$i]['pid'] == ${'npc_'.$i}){
				if($command == 'kill'){
					if($npcdata[$i]['hp'] > 0){
						$operlist[${'npc_'.$i}] = $npcdata[$i]['name'].'(PID:'.$npcdata[$i]['pid'].')';
						$npcdata[$i]['hp'] = 0;
						$npcdata[$i]['state'] = 15;
						$deathnum ++;$alivenum--;
						adminlog('killnpc',$npcdata[$i]['name']);
					}else{
						$gfaillist[] = $npcdata[$i]['name'].'(PID:'.$npcdata[$i]['pid'].')';
					}					
				}elseif($command == 'live'){
					if($npcdata[$i]['hp'] <= 0){
						$operlist[${'npc_'.$i}] = $npcdata[$i]['name'].'(PID:'.$npcdata[$i]['pid'].')';
						$npcdata[$i]['hp'] = $npcdata[$i]['mhp'];
						$npcdata[$i]['state'] = 0;
						$deathnum --; //$alivenum++;
						adminlog('livenpc',$npcdata[$i]['name']);
					}else{
						$gfaillist[] = $npcdata[$i]['name'].'(PID:'.$npcdata[$i]['pid'].')';
					}					
				}elseif($command == 'del'){
					
					if($npcdata[$i]['hp'] > 0){					
						$operlist[${'npc_'.$i}] = $npcdata[$i]['name'].'(PID:'.$npcdata[$i]['pid'].')';	
						$npcdata[$i]['hp'] = 0;
						$npcdata[$i]['state'] = 16;
						$deathnum ++;//$alivenum++;
						adminlog('delnpc',$npcdata[$i]['name']);
					}else{
						$operlist2[${'npc_'.$i}] = $npcdata[$i]['name'].'(PID:'.$npcdata[$i]['pid'].')';
						adminlog('delncp',$npcdata[$i]['name']);
					}
				}
			}else{
				$dfaillist[] = ${'npc_'.$i};
			}			
		}
	}
	if($operlist || $operlist2 || $dfaillist || $gfaillist){
		if($command == 'kill'){
			$operword = '被杀死';
			$qryword = "UPDATE {$tablepre}players SET hp='0',state='15',bid='0' ";
		}elseif($command == 'live'){
			$operword = '被复活';
			$qryword = "UPDATE {$tablepre}players SET hp=mhp,state='0' ";
		}elseif($command == 'del'){
			$operword = '被清除';
			$qryword = "UPDATE {$tablepre}players SET hp='0',state='16',bid='0',weps='0',arbs='0',arhs='0',aras='0',arfs='0',arts='0',itms0='0',itms1='0',itms2='0',itms3='0',itms4='0',itms5='0',itms6='0',money='0' ";
			$operword2 = '的尸体被清除';
			$qryword2 = "UPDATE {$tablepre}players SET bid='0',weps='0',arbs='0',arhs='0',aras='0',arfs='0',arts='0',itms0='0',itms1='0',itms2='0',itms3='0',itms4='0',itms5='0',itms6='0',money='0' ";
		}
		if($operlist){
			$qrywhere = '('.implode(',',array_keys($operlist)).')';
			$opernames = implode(',',($operlist));
			$db->query("$qryword WHERE pid IN $qrywhere");
			//echo "$qryword WHERE pid IN $qrywhere";
			$cmd_info .= " NPC $opernames $operword 。<br>";
		}
		if($operlist2){
			$qrywhere2 = '('.implode(',',array_keys($operlist2)).')';
			$opernames = implode(',',($operlist2));
			$db->query("$qryword2 WHERE pid IN $qrywhere2");
			//echo "$qryword2 WHERE pid IN $qrywhere2";
			$cmd_info .= " NPC $opernames $operword2 。<br>";
		}
		if($gfaillist){
			$gfailnames = implode(',',($gfaillist));
			$cmd_info .= " NPC $gfailnames 已经处于该状态，无法 $operword  。<br>";
		}
		if($dfaillist){
			$dfailnames = implode(',',($dfaillist));
			$cmd_info .= " PID为 $dfailnames 的NPC不存在或位于查询范围外  。<br>";
		}
		save_gameinfo();
	}else{
		$cmd_info = "指定的帐户超出查询范围或指令错误。";
	}
	$command = 'list';
} elseif(strpos($command ,'edit')===0) {
	$pid = explode('_',$command);
	$no = (int)$pid[1];
	$pid = (int)$pid[2];
	if(!$pid){
		$cmd_info = "帐户UID错误。";
	}elseif(!isset($npcdata[$no]) || $npcdata[$no]['pid'] != $pid){
		$cmd_info = "该帐户不存在或超出查询范围。";
	}else{
		$result = $db->query("SELECT * FROM {$tablepre}players WHERE pid='$pid' AND type>'0'");
		$npc = $db->fetch_array($result);

		if(!$npc) {
			$cmd_info = "找不到角色 ".$npcdata[$no]['name']." 。";
		}else{
			$npc['str_clbpara'] = '';
			if(isset($npc['clbpara'])) $npc['str_clbpara'] = $npc['clbpara'];
			$command = 'check';
		}
	}
} elseif($command == 'submitedit') {
	if(isset($str_clbpara))
	{
		$clbpara = Array();
		$tmp_clbpara = json_decode(htmlspecialchars_decode($str_clbpara),1);
		foreach($tmp_clbpara as $key=>$res) $clbpara[$key] = $res;
		$clbpara = json_encode($clbpara,JSON_UNESCAPED_UNICODE);
	}
	$effect_flag = 0;
	$ndata = update_db_player_structure();
	foreach($ndata as $key)
	{
		if($key != 'pid' && isset($$key))
		{
			$key_value = $$key;
			$db->query("UPDATE {$tablepre}players SET $key='$key_value' where pid='$pid'");
			if($db->affected_rows()) $effect_flag = 1;
		}
	}
	if(!$effect_flag){
		$cmd_info = "没有检测到对角色 $name 的修改";
	} else {
		adminlog('editnpc',$name);
		$cmd_info = "角色 $name 的属性被修改了";
	}
	$npcdata = dbsearch($start,$checkmode,$checkinfo);
}
include template('admin_npcmng');


function dbsearch($start,$checkmode,$checkinfo){
	global $showlimit,$db,$tablepre,$resultinfo,$cmd_info,$plsinfo,$hplsinfo;
	//登记非功能性地点信息时合并隐藏地点
	foreach($hplsinfo as $hgroup=>$hpls) $plsinfo += $hpls;
	$limitstr = " LIMIT $start,$showlimit";
	if(($checkmode == 'name')&&($checkinfo)) {
		$result = $db->query("SELECT * FROM {$tablepre}players WHERE name LIKE '%{$checkinfo}%' AND type>'0'".$limitstr);
	} elseif($checkmode == 'teamID') {
		if($checkinfo){
			$result = $db->query("SELECT * FROM {$tablepre}players WHERE teamID LIKE '%".$checkinfo."%' AND type>'0' ORDER BY teamID".$limitstr);
		} else {
			$result = $db->query("SELECT * FROM {$tablepre}players WHERE type>'0' ORDER BY teamID DESC".$limitstr);
		}
	} elseif($checkmode == 'pls') {
		if($checkinfo){
			$plslist = array();
			foreach($plsinfo as $key => $val){
				if(strpos($val,$checkinfo)!==false){
					$plslist[] = $key;
				}
			}
			if(is_numeric($checkinfo)){$plslist[] = (int)$checkinfo;}
			if(!empty($plslist)){
				$plsstr = implode(',',$plslist);
				$result = $db->query("SELECT * FROM {$tablepre}players WHERE pls IN (".$plsstr.") AND type>'0' ORDER BY pls".$limitstr);
			}else{$result = false;}
		} else {
			$result = $db->query("SELECT * FROM {$tablepre}players WHERE type>'0' ORDER BY pls".$limitstr);
		}
	} else {
		$result = $db->query("SELECT * FROM {$tablepre}players WHERE type>'0'".$limitstr);
	}
	if(!$result || !$db->num_rows($result)) {
		$cmd_info = '没有符合条件的角色。';
		$startno = $start + 1;
		$resultinfo = '位置：第'.$startno.'条记录';
		$npcdata = Array();
	} else {
		while($npc = $db->fetch_array($result)) {
			$npcdata[] = $npc;
		}
		$startno = $start + 1;
		$endno = $start + count($npcdata);
		$resultinfo = '第'.$startno.'条-第'.$endno.'条记录';
	}
	return $npcdata;
}
?>