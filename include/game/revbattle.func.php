<?php

	if(!defined('IN_GAME')) {
		exit('Access Denied');
	}

	//发现敌人
	function findenemy_rev($edata) 
	{
		global $db,$tablepre;
		global $fog,$pid,$log,$mode,$main,$cmd,$battle_title,$attinfo,$skillinfo,$nosta;

		$battle_title = '发现敌人';

		//获取并保存当前玩家数据
		$sdata = current_player_save();

		//格式化双方clbpara
		$sdata['clbpara'] = get_clbpara($sdata['clbpara']); $edata['clbpara'] = get_clbpara($edata['clbpara']);

		//格式化对战双方数据
		$init_data = update_db_player_structure();
		foreach(Array('w_','s_') as $p)
		{
			foreach ($init_data as $i) global ${$p.$i};
		}
		extract($edata,EXTR_PREFIX_ALL,'w'); extract($sdata,EXTR_PREFIX_ALL,'s');
		init_rev_battle();

		$log .= "你发现了敌人<span class=\"red\">{$w_name}</span>！<br>对方好像完全没有注意到你！<br>";

		//初始化玩家攻击方式信息
		$w1 = substr($s_wepk,1,1);
		$w2 = substr($s_wepk,2,1);
		if ($w2=='0'||$w2=='1') $w2='';
		if (($w1 == 'G'||$w1=='J')&&($s_weps==$nosta)) $w1 = 'P';

		include template('battlecmd_rev');
		$cmd = ob_get_contents();
		ob_clean();
		$main = 'battle_rev';
		return;
	}

	//发现中立NPC $kind 0=中立单位 1=友军
	function findneut(&$edata,$kind=0)
	{
		global $db,$tablepre;
		global $fog,$log,$mode,$main,$cmd,$battle_title,$attinfo,$skillinfo;
		
		$battle_title = $kind ? '发现朋友' : '发现敌人？';

		//获取并保存当前玩家数据
		$sdata = current_player_save();

		//格式化双方clbpara
		$sdata['clbpara'] = get_clbpara($sdata['clbpara']); $edata['clbpara'] = get_clbpara($edata['clbpara']);

		//格式化双方数据
		$init_data = update_db_player_structure();
		foreach(Array('w_','s_','') as $p)
		{
			foreach ($init_data as $i) global ${$p.$i};
		}
		extract($edata,EXTR_PREFIX_ALL,'w'); extract($sdata,EXTR_PREFIX_ALL,'s');
		init_rev_battle(1);

		$log .= "你发现了<span class=\"yellow\">$w_name</span>！<br>";
		if(!$kind) $log .= "对方看起来没有敌意。<br>";

		//TODO：把这一段挪到一个独立函数里
		if($edata['clbpara']['post'] == $sdata['pid']) 
		{	
			$log.="对方一看见你，便猛地朝你扑了过来！<br>
			<br><span class='sienna'>“老板！有你的快递喔！”</span><br>
			<br>你被这突然袭击吓了一跳！<br>
			但对方只是从身上摸出了一个包裹样的东西扔给了你。然后又急匆匆地转身离开了。<br>
			<br>……这是在搞啥……？<br><br>";
			$action='';
			global $itm0,$itmk0,$itme0,$itms0,$itmsk0;
			$iid=$edata['clbpara']['postid'];
			$itm0=$edata['itm'.$iid];$itmk0=$edata['itmk'.$iid];$itmsk0=$edata['itmsk'.$iid];
			$itme0=$edata['itme'.$iid];$itms0=$edata['itms'.$iid];
			//发一条news 表示快递已送达
			$sponsorid = $edata['clbpara']['sponsor'];
			$result = $db->query("SELECT * FROM {$tablepre}gambling WHERE uid = '$sponsorid'");
			$sordata = $db->fetch_array($result);
			addnews($now,'gpost_success',$sordata['uname'],$itm0,$name);
			//再见了~快递员！
			unset($edata['clbpara']['post']);unset($edata['clbpara']['postid']);unset($edata['clbpara']['sponsor']);
			destory_corpse($edata);
			//解除快递锁
			$db->query("UPDATE {$tablepre}gambling SET bnid=0 WHERE uid='$sponsorid'");
		}

		include template('findneut');
		$cmd = ob_get_contents();
		ob_clean();
		$main = 'battle_rev';
		return;
	}
?>