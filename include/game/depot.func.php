<?php

	if(!defined('IN_GAME')) {
		exit('Access Denied');
	}
	global $can_lootdepot_type;
	//个人仓库最多可以储存的道具数量 0=不限制
	$max_saveitem_num = 0;
	//储存每件道具的手续费
	$saveitem_cost = 20;
	//取出道具的手续费
	$loaditem_cost = 220;
	//游戏开场时NPC存在仓库里的道具
	$npc_depot = Array();
	//可以从哪些人的尸体上获取其存放在仓库里的道具权限？ 默认：仅玩家
	$can_lootdepot_type = Array(0);

	function depot_getlist($n,$t)
	{
		global $db,$tablepre;
		$iarr = Array();		
		$depot = $db->query("SELECT * FROM {$tablepre}itemdepot WHERE itmowner='$n' AND itmpw='$t'");
		while($i = $db->fetch_array($depot)) 
		{
			$iarr[] = $i;
		}
		return $iarr;
	}	

	function depot_changeowner($n,$t,$tn,$tt)
	{
		global $db,$tablepre;
		$db->query("UPDATE {$tablepre}itemdepot SET itmowner='$tn',itmpw='$tt' WHERE itmowner='$n' AND itmpw=$t");
	}

	function loot_depot($n,$t,$tn,$tt)
	{
		global $log,$can_lootdepot_type,$rp;
		if(!in_array($tt,$can_lootdepot_type))
		{
			$log.="无法转移安全箱权限！可能是对方的权限等级比你高。<br>";
			return;
		}
		if(count(depot_getlist($n,$t))<=0) 
		{
			$log.="对方没有在安全箱内存过东西！<br>";
			return;
		}
		depot_changeowner($tn,$tt,$n,$t);	
		$rp+=233;
		$log .= "你将{$tn}生前存放在安全箱里的东西转移到了自己的名下！哇，这可真是……<br>";
		addnews ( 0, 'loot_depot', $n, $tn );
		return;
	}

	function depot_save($i)
	{
		global $db,$tablepre,$arealist,$areanum,$hack;
		global $log,$pls,$name,$type,$money;
		global $max_saveitem_num,$saveitem_cost,$loaditem_cost,$depots;	
		global ${'itm'.$i},${'itmk'.$i},${'itme'.$i},${'itms'.$i},${'itmsk'.$i};

		$i = (int)$i;
		if(!in_array($pls,$depots))
		{
			$log.="<span class='red'>你所在的位置没有安全箱！建议你不要胡思乱想！</span><br>";
			return;
		}
		if(array_search($pls,$arealist) <= $areanum && !$hack)
		{
			$log.="<span class='red'>你所在的位置是禁区！还想着存东西，命不要啦！</span><br>";
			return;
		}
		if(!$i || $i>6 || $i<1 || (${'itms'.$i}<=0 && ${'itms'.$i}!=='∞'))
		{
			$log.="<span class='red'>要储存的道具信息错误，请返回重新输入。</span><br>";
			return;
		}
		if($money < $saveitem_cost)
		{
			$log.="<span class='red'>你身上的钱不足以支付储存道具的手续费！</span><br>";
			return;
		}
		if(strpos(${'itmsk'.$i},'v')!==false)
		{
			$log.="<span class='red'>你尝试着把灵魂绑定的道具扔进安全箱，但安全箱又立刻将它吐了出来！</span><br>";
			return;
		}
			
		$idpt = depot_getlist($name,$type);
		$idpt_num = sizeof($idpt);

		if($idpt_num+1>$max_saveitem_num && $max_saveitem_num>0)
		{
			$log.="<span class='red'>安全箱已满，无法再储存更多道具！</span><br>";
			return;
		}

		$money -= $saveitem_cost;
		$log.="你成功将道具<span class='yellow'>{${'itm'.$i}}</span>存进了安全箱内！<br>同时被迫支付了手续费<span class='yellow'>{$saveitem_cost}</span>元。<br>";
		$itm=&${'itm'.$i};$itmk=&${'itmk'.$i};$itmsk=&${'itmsk'.$i};
		$itme=&${'itme'.$i};$itms=&${'itms'.$i};
		addnews($now,'depot_save',$name,${'itm'.$i});
		$db->query("INSERT INTO {$tablepre}itemdepot (itm, itmk, itme, itms, itmsk ,itmowner, itmpw) VALUES ('$itm', '$itmk', '$itme', '$itms', '$itmsk', '$name', '$type')");
		$itm='';$itmk='';$itmsk='';
		$itme=0;$itms=0;
	}

	function depot_load($i)
	{
		global $db,$tablepre,$arealist,$areanum,$hack;
		global $log,$pls,$name,$type,$money;
		global $max_saveitem_num,$saveitem_cost,$loaditem_cost,$depots;	
		global $itm0,$itmk0,$itme0,$itms0,$itmsk0;

		$i = (int)$i;
		if(!in_array($pls,$depots))
		{
			$log.="<span class='red'>你所在的位置没有安全箱！建议你不要胡思乱想！</span><br>";
			return;
		}
		if(array_search($pls,$arealist) <= $areanum && !$hack)
		{
			$log.="<span class='red'>你所在的位置是禁区！还想着取东西，命不要啦！</span><br>";
			return;
		}
		if($money < $loaditem_cost)
		{
			$log.="<span class='red'>你身上的钱不足以支付取出道具的保管费……卧槽竟然二次收费，太黑了吧！</span><br>";
			return;
		}

		$idpt = depot_getlist($name,$type);
		$idpt_num = sizeof($idpt);	

		if(($max_saveitem_num>0 && $i>$max_saveitem_num) || $i<0 || ($idpt[$i]['itms']<=0 && $idpt[$i]['itms']!=='∞'))
		{
			$log.="<span class='red'>要取出的道具信息错误，请返回重新输入。</span><br>";
			return;
		}
		$itm0= $idpt[$i]['itm'];
		$itmk0= $idpt[$i]['itmk'];
		$itme0= $idpt[$i]['itme'];
		$itms0= $idpt[$i]['itms'];
		$itmsk0= $idpt[$i]['itmsk'];
		$iid = $idpt[$i]['iid'];
		addnews($now,'depot_load',$name,$itm0);
		$log.="你成功将道具<span class='yellow'>{$itm0}</span>从安全箱中取了出来！<br>同时被迫支付了保管费<span class='yellow'>{$loaditem_cost}</span>元……你感觉自己的心在滴血。<br>";
		$money -= $loaditem_cost;
		$db->query("DELETE FROM {$tablepre}itemdepot WHERE iid='$iid'");
		include_once GAME_ROOT.'./include/game/itemmain.func.php';
		itemget();
	}

?>
