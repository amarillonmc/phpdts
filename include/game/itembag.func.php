<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}
/* ————————————————设计思路———————————————— */
//关于背包的设定：
//依然是使用json_encode将背包内的道具信息的数组变成一个字符串，储存在$itembag内，以实现尽量多的扩展背包大小的功能
//然后还是使用json_encode将需要放入背包的道具信息数组变成一个字符串，储存在$getitem里
/* ————————————————获得道具示例———————————————— */
//$getitem = Array(
//	1 => Array(
//		'itm' => $itm1,
//		'itmk' => $itmk1,
//		'itme' => $itme1,
//		'itms' => $itms1,
//		'itmsk' => $itmsk1,
//	),
//	2 => Array(
//		'itm' => $itm2,
//		'itmk' => $itmk2,
//		'itme' => $itme2,
//		'itms' => $itms2,
//		'itmsk' => $itmsk2,
//	)
//);
/* ————————————————设计需求———————————————— */
//需要增加的字段：
//$itembag - 记录背包内道具 $getitem - 记录放入背包内道具 $itmnum - 背包内道具数量 $itmnumlimit - 背包内道具数量限制 $weight - 背包内道具重量 $weightlimit - 背包内道具重量限制
//需要在resource内添加的array：
//$itmstkinfo - 记录可堆叠道具类别，以及最大堆叠数量 $itmwtinfo - 道具的重量设定
//重量设定未实装，但预留了位置
/* ————————————————基础部分———————————————— */
//还原
function decode_item($i){
	if(!$i){
		$i_list = Array();
	}else{
		$i_list = json_decode($i,true);
	}
	return $i_list;
}
//兼容5.3以下php的json_encode()
function json_encode_comp($par){
	if(version_compare(PHP_VERSION,'5.4.0')>=0){ //可以使用json_encode()的JSON_UNESCAPED_UNICODE常量
		return json_encode($par,JSON_UNESCAPED_UNICODE);
	}else{ //不可以使用JSON_UNESCAPED_UNICODE，用url_encode()处理
		return urldecode(json_encode(url_encode($par)));
	}
}
function url_encode($str) {  
	if(is_array($str)) {  
		foreach($str as $key=>$value) {  
			$str[urlencode($key)] = url_encode($value);  
		}  
	} else {  
		$str = urlencode($str);  
	}  
      
	return $str;  
} 
/* ————————————————计算部分———————————————— */
//计算背包内的道具数量（按照类别来区分）
function count_item(){
	global $itmnum,$itmnumlimit;
	global $itembag;
	$item_list = decode_item($itembag);
	$itmnum = sizeof(array_keys($item_list));
}
//计算负重
//这里不考虑当前负重超过负重限制的情况（考虑了在这里也没法处理【摊手）
function item_weight(){
	global $itembag,$weight,$wep,$arb,$arh,$ara,$arf,$art;
	global $itmwtinfo;
	$item_list = decode_item($itembag);
	$weight = 0;
	foreach(array_keys($item_list) as $iid){
		$itm =	$item_list[$iid]['itm'];
		$itms = $item_list[$iid]['itms'];
		$weight += $itmwtinfo[$itm]*$itms; 
	}
	foreach(array_keys(array($wep=>$wep,$arb=>$arb,$arh=>$arh,$ara=>$ara,$arf=>$arf,$art=>$art)) as $ar){
	//不要吐槽，我想静静
		$weight += $itmwtinfo[$ar]; 
	}
	return $weight;
}
//计算获得道具的数量（按照类别来区分）
function count_getitem(){
	global $getitem;
	$item_list = decode_item($getitem);
	$getitmnum = sizeof(array_keys($item_list));
	return $getitmnum;
}
//单独计算获得道具是否超重
function getitem_weight($git,$gitnum){
	global $weight,$weightlimit;
	global $itmwtinfo;
	$rest_wt = $weightlimit - $weight;
	$true_gitnum = floor($rest_wt/($itmwtinfo[$git]*$gitnum));
	$over_wt_num = $gitnum <= $true_gitnum ? 0 : $gitnum - $true_gitnum; 
	return $over_wt_num;
}
/* ————————————————处理部分———————————————— */
//数组
function item_arr(){
	global $itembag;
	$item_list = decode_item($itembag);
	return $item_list;
}
//显示背包内道具信息
function item_info(){
	global $itembag,$itmnum,$itmnumlimit;
	global $iteminfo,$itemspkinfo; 
	global $log,$mode;
	$item_list = decode_item($itembag);
	$log.="当前背包内装有如下道具：<br><br>";
	foreach($item_list as $item){
		$log.="<span class='yellow'>{$item['itm']}</span>/{$item['itme']}/{$item['itms']}/{$itemspkinfo[$item['itmsk']]}<br>";
	}
	$log.="<br>背包剩余空间 <span class='lime'>{$itmnum}/{$itmnumlimit}</span><br>";
	$mode = 'command';
}
//选择要放入的道具
function item_encase($ilist){
	global $log,$getitem,$itmnum,$itmnumlimit;
	foreach($ilist as $i){
		global ${'itm'.$i},${'itmk'.$i},${'itme'.$i},${'itms'.$i},${'itmsk'.$i};
		$git_list = decode_item($getitem);
		$itm = &${'itm'.$i};
		$itmk = &${'itmk'.$i};
		$itme = &${'itme'.$i};
		$itms = &${'itms'.$i};
		$itmsk = &${'itmsk'.$i};
		if(strpos($itmsk,'V')!==false || strpos($itmsk,'v')!==false){
			$log.="诅咒和灵魂绑定的装备无法存放在背包内。<br>";
		}elseif(round(sizeof($git_list)+1+$itmnum) > $itmnumlimit){
			$log.="背包已满，无法继续放入道具。<br>";
		}else{
			item_find($itm,$itmk,$itme,$itms,$itmsk);
			$itm = $itmk = $itmsk = '';
			$itme = $itms = 0;
		}
	}
	item_get();
}
//发现道具
function item_find($itm,$itmk,$itme,$itms,$itmsk){
	global $getitem;
	$git_list = decode_item($getitem);
	$gitarr = Array(
		'itm' => $itm,
		'itmk' => $itmk,
		'itme' => $itme,
		'itms' => $itms,
		'itmsk' => $itmsk,
	);
	array_push($git_list,$gitarr);
	$getitem = json_encode_comp($git_list);
}
//处理获得道具
function item_get(){
	global $itmstkinfo,$itmwtinfo;
	global $getitem,$itembag,$weight,$weightlimit,$itmnum,$itmnumlimit;
	global $log;
	$git_list = decode_item($getitem);
	$item_list = decode_item($itembag);
	foreach(array_keys($git_list) as $gid){
		$git = $git_list[$gid]['itm'];
		$gitk = $git_list[$gid]['itmk'];
		$gite = $git_list[$gid]['itme'];
		$gits = $git_list[$gid]['itms'];
		$gitsk = $git_list[$gid]['itmsk'];
		if(!$gits || !$gitk || !$git){
			$log.="获取道具的相关信息失败。<br>";
		}else{
		/*	//判断是否超重
			$overwt = getitem_weight($git,$gits);
			if($overwt > 0){
				//根据“如果道具可堆叠，那么道具就可拆分原理”，将道具超重的部分作为一坨新道具，加入$git_list中，剩下的部分，自然就是不超重的
				if(in_array($git,array_keys($itmstkinfo))){
					$git_list[$gid]['itms'] -= $overwt;
					$gits = $git_list[$gid]['itms'];
					$overgitarr = Array(
						'itm' => $git,
						'itmk' => $gitk,
						'itme' => $gite,
						'itms' => $overwt,
						'itmsk' => $gitsk,
					);
					array_push($git_list,$overgitarr);
					$overwt = false;
				}else{
					$overwt = true;
				}
			}else{
				$overwt = false;
			}
			if($overwt){
				$log.="负重超过上限，无法继续获得道具。<br>";
				item_full_drop();
				return;
			}*/
			//判断是否堆叠
			$over = false;
			$full_stk = false;
			for($i=0;$i<=sizeof(array_keys($item_list));$i++){
				//四个判定 道具名相同/道具类相同/属于可堆叠道具/背包内的相同道具数未超过堆叠限制
				if(($git == $item_list[$i]['itm']) && ($gitk == $item_list[$i]['itmk']) && (in_array($gitk,array_keys($itmstkinfo))) && (($item_list[$i]['itms'])<$itmstkinfo[$gitk]) && !$over){
					if(($item_list[$i]['itms']+$gits)>$itmstkinfo[$gitk]){
						//获得道具数+原有道具数超过堆叠限制时，多出来的部分将作为新一组道具继续走完item_get()
						$add_git = $itmstkinfo[$gitk] - $item_list[$i]['itms'];
						$item_list[$i]['itms'] += $add_git;
						$git_list[$gid]['itms'] -= $add_git;
						$log.="合并了道具<span class=\"yellow\">{$git}</span>的一部分。<br>";
					}else{
						//获得道具数+原有道具数未超过堆叠限制时
						$weight += $itmwtinfo[$git]*$git_list[$gid]['itms'];
						$item_list[$i]['itms'] += $git_list[$gid]['itms'];
						$git_list[$gid]['itms'] = 0;
						$log.="合并了道具<span class=\"yellow\">{$git}</span>。<br>";
						$full_stk = true;
					}
					$gits = $git_list[$gid]['itms'];
					$over = true;
				}
			}
			//判断是否过量
			$overnum = ($itmnum+1) > $itmnumlimit ? true : false;
			if($overnum && !$full_stk){
				$log.="背包已满，无法继续放入道具。<br>";
				return;
			}else{
				$weight = item_weight();
				if(!$full_stk){
					$itmnum++;
					$weight += $itmwtinfo[$git]*$gits;
					$gitarr = Array(
						'itm' => $git,
						'itmk' => $gitk,
						'itme' => $gite,
						'itms' => $gits,
						'itmsk' => $gitsk,
					);
					array_push($item_list,$gitarr);
					$log.="你向背包中存入了<span class=\"yellow\">{$git}</span>。<br>";
				}
				unset($git_list[$gid]);
				$getitem = json_encode_comp($git_list);
				$itembag = json_encode_comp($item_list);
			}
		}
	}
}
//处理取出道具
function item_out($iid){
	global $itembag,$itmnum;
	global $log,$itm0,$itmk0,$itme0,$itms0,$itmsk0;
	$item_list = decode_item($itembag);
	if(!in_array($iid,array_keys($item_list))){
		$log .= '此道具不存在，请重新选择。<br>';
		return;
	}
	if(!$item_list[$iid]['itm'] || !$item_list[$iid]['itms'] || !$item_list[$iid]['itmk']){
		$log .= '此道具不存在，请重新选择。<br>';
		return;
	}
	$itm0 = $item_list[$iid]['itm'];
	$itmk0 = $item_list[$iid]['itmk'];
	$itme0 = $item_list[$iid]['itme'];
	$itms0 = $item_list[$iid]['itms'];
	$itmsk0 = $item_list[$iid]['itmsk'];
	unset($item_list[$iid]);
	$itmnum = sizeof(array_keys($item_list));
	$itembag = json_encode_comp($item_list);
	include_once GAME_ROOT.'./include/game/itemmain.func.php';
	itemget();	
}
//背包内道具数量超过可携带道具数量限制时的处理
function overnumlimit(){
	global $itembag,$log;
	global $itmnum,$itmnumlimit;
	global $pls,$db,$tablepre;
	$item_list = decode_item($itembag);
	if($itmnum > $itmnumlimit){
		$p = $itmnum - $itmnumlimit;
		for($a=1;$a<=$p;$a++){		
			array_pop($item_list);
			$itembag = json_encode_comp($item_list);
		}
		$log.="由于背包空间不足，你背包里的一些道具在行动中损坏了！<br>";
		$itmnum = $itmnumlimit;//青蛙你漏了这句导致背包会不断漏直到漏完为止
	}
}
//丢弃背包时对背包内的道具进行处理
function drop_itembag(){
	global $itembag,$log,$itmnum,$itmnumlimit;
	global $pls,$db,$tablepre;
	$item_list = decode_item($itembag);
	foreach(array_keys($item_list) as $iid){
		$itm = $item_list[$iid]['itm'];
		$itmk = $item_list[$iid]['itmk'];
		$itme = $item_list[$iid]['itme'];
		$itms = $item_list[$iid]['itms'];
		$itmsk = $item_list[$iid]['itmsk'];
		$db->query("INSERT INTO {$tablepre}mapitem (itm, itmk, itme, itms, itmsk ,pls) VALUES ('$itm', '$itmk', '$itme', '$itms', '$itmsk', '$pls')");
	}
	$log.="你将背包连同里面的道具一同丢掉了。<br>";
	$itmnum = $itmnumlimit = 0;
	$item_list = Array();
	$itembag = json_encode_comp($item_list);
}
//拾取背包时对是否替换进行判断
function replace_itembag(&$keep){
	global $itm0,$itmk0,$itme0,$itms0,$itmsk0;
	global $arb,$arbk,$arbe,$arbs,$arbsk;
	global $itmnumlimit,$log,$mode;
	global $pls,$db,$tablepre;
	if(strpos($itmsk0,'^')!==false){
		$r_flag = false;
		for($i=1;$i<=6;$i++){
		global ${'itm'.$i},${'itmk'.$i},${'itme'.$i},${'itms'.$i},${'itmsk'.$i};
			if(strpos($arbsk,'^')!==false  && $arbs && $arbe){
				$r_flag = 'arb';
			}elseif(strpos(${'itmsk'.$i},'^')!==false && ${'itms'.$i} && ${'itme'.$i}){
				$r_flag = $i;
			}
		}
		if($r_flag && ($itms0>$itmnumlimit)){
			if($r_flag == 'arb'){
				$db->query("INSERT INTO {$tablepre}mapitem (itm, itmk, itme, itms, itmsk ,pls) VALUES ('$arb', '$arbk', '$arbe', '$arbs', '$arbsk', '$pls')");
				$arb = $itm0;
				$arbk = $itmk0;
				$arbe = $itme0;
				$arbs = $itms0;
				$arbsk = $itmsk0;
				$itm0 = $itmk0 = $itmsk0 = '';
				$itme0 = $itms0 = 0;
			}else{
				$i = $r_flag;
				global ${'itm'.$i},${'itmk'.$i},${'itme'.$i},${'itms'.$i},${'itmsk'.$i};
				$db->query("INSERT INTO {$tablepre}mapitem (itm, itmk, itme, itms, itmsk ,pls) VALUES ('${'itm'.$i}', '${'itmk'.$i}', '${'itme'.$i}', '${'itms'.$i}', '${'itmsk'.$i}', '$pls')");
				${'itm'.$i} = $itm0;
				${'itmk'.$i} = $itmk0;
				${'itme'.$i} = $itme0;
				${'itms'.$i} = $itms0;
				${'itmsk'.$i} = $itmsk0;
				$itm0 = $itmk0 = $itmsk0 = '';
				$itme0 = $itms0 = 0;
			}
			$log.="由于只能携带一个背包，你用拾到的品质较高的背包替换掉了身上的背包。<br>";
		}elseif($r_flag){
			$db->query("INSERT INTO {$tablepre}mapitem (itm, itmk, itme, itms, itmsk ,pls) VALUES ('$itm0', '$itmk0', '$itme0', '$itms0', '$itmsk0', '$pls')");
			$itm0 = $itmk0 = $itmsk0 = '';
			$itme0 = $itms0 = 0;
			$log.="由于只能携带一个背包，你扔掉了这个品质较差的背包。<br>";
		}else{
			$keep = true;
		}
		$mode = 'command';
	}
}


?>