<?php

	if(!defined('IN_GAME')) {
		exit('Access Denied');
	}

	//战斗中切换武器
	function change_wep_in_battle($s=2)
	{
		global $log,$nosta;
		global $wep,$wepk,$wepe,$weps,$wepsk;
		global $wep2,$wep2k,$wep2e,$wep2s,$wep2sk;
		# 初始化主武器名
		$eqp = 'wep';
		# 初始化副武器名
		$seqp = 'wep'.$s;
		$seqpk = $seqp.'k';
		$seqpe = $seqp.'e';
		$seqps = $seqp.'s';
		$seqpsk = $seqp.'sk';
		# 保存副武器数据
		$swep=${$seqp}; $swepk=${$seqpk};
		$swepe=${$seqpe}; $sweps=${$seqps}; $swepsk=${$seqpsk};
		# 主武器为空、副武器不为空的情况下，直接替换为副武器
		if(($wepk == 'WN' || !$weps) && ($swepk != 'WN'))
		{
			${$eqp} = $swep; ${$seqp} = '拳头';
			${$eqp.'k'} = $swepk; ${$seqpk} = 'WN';
			${$eqp.'e'} = $swepe; ${$seqpe} = 0;
			${$eqp.'s'} = $sweps; ${$seqps} = $nosta;
			${$eqp.'sk'} = $swepsk; ${$seqpsk} = '';
			$log.="你将{$wep}拿在了手上。<br>";
		}
		# 主武器不为空的情况下，副武器替换为主武器
		elseif($wepk != 'WN')
		{
			${$seqp} = ${$eqp}; ${$eqp} = $swep; 
			${$seqpk} = ${$eqp.'k'}; ${$eqp.'k'} = $swepk;
			${$seqpe} = ${$eqp.'e'}; ${$eqp.'e'} = $swepe; 
			${$seqps} = ${$eqp.'s'}; ${$eqp.'s'} = $sweps; 
			${$seqpsk} = ${$eqp.'sk'}; ${$eqp.'sk'} = $swepsk; 
			$log.="你将{$wep2}收了起来";
			if($wepk != 'WN') $log .="，将{$wep}拿在了手上";
			$log.="。<br>";
		}
		else 
		{
			$log.="你没有装备副武器！去给自己找一个吧！<br>";
		}
		return;
	}

	//战斗中逃跑
	function escape_from_enemy(&$pa,&$pd)
	{
		global $action,$clbpara,$chase_escape_obbs,$log;
		include_once GAME_ROOT.'./include/game/dice.func.php';
		# 在受追击/鏖战状态下逃跑有概率失败
		if(strpos($action,'pchase')===0 || strpos($action,'dfight')===0)
		{
			$escape_dice = diceroll(99);
			if($escape_dice < $chase_escape_obbs)
			{
				$log .= "你尝试逃跑，但是敌人在你身后紧追不舍！<br>";
				$pa['fail_escape'] = 1;
				return 0;
			}
		}
		$log .= "你逃跑了。";
		$action = '';
		unset($clbpara['battle_turns']);
		return 1;
	}

	//发现敌人
	function findenemy_rev($edata) 
	{
		global $db,$tablepre;
		global $fog,$pid,$log,$mode,$main,$cmd,$battle_title,$attinfo,$skillinfo,$nosta,$cskills;

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
		//初始化界面与log
		$battle_title = init_battle_title($sdata,$edata);
		$log .= init_battle_log($sdata,$edata);
		if(strpos($sdata['action'],'chase')!==false || strpos($sdata['action'],'dfight')!==false) init_rev_battle(1);
		else init_rev_battle();

		//检查是敌对或中立单位
		$neut_flag = $edata['pose'] == 7 ? 1 : 0;

		//初始化玩家战斗技列表
		if(!empty($sdata['clbpara']['skill']))
		{
			include_once GAME_ROOT.'./include/game/revclubskills.func.php';
			$battle_skills = Array();
			$sk_nums = 0;
			foreach($sdata['clbpara']['skill'] as $sk)
			{
				//遍历玩家技能，寻找带有战斗技标签的技能
				if(get_skilltags($sk,'battle'))
				{
					$sk_desc = '';
					//先检查技能是否满足解锁条件
					$unlock = check_skill_unlock($sk,$sdata);
					if($unlock)
					{
						$sk_desc .= is_array($cskills[$sk]['lockdesc']) ? $cskills[$sk]['lockdesc'][$unlock] : $cskills[$sk]['lockdesc'];
					}
					//再检查技能是否满足激活条件
					else
					{
						$unlock = check_skill_active($sk,$sdata);
						if($unlock) $sk_desc .= $unlock;
					}
					//技能可以使用，输出介绍文本
					if(empty($sk_desc)) $sk_desc = parse_skilldesc($sk,$sdata,1);
					//存入可使用战斗技队列，顺序：是否可使用、技能名、技能介绍文本
					$battle_skills[$sk_nums] = Array($unlock,$sk,$sk_desc);
					$sk_nums++;
				}
			}
		}

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

	// 初始化战斗界面标题
	function init_battle_title($pa,$pd)
	{
		if(strpos($pa['action'],'chase')!==false)
		{
			if(strpos($pa['action'],'pchase')!==false) $title = '遭到追击';
			else $title = '乘胜追击';
		}
		if(strpos($pa['action'],'dfight')!==false)
		{
			$title = '陷入鏖战';
		}
		else 
		{
			$title = '发现敌人';
		}
		return $title;
	}

	// 初始化战斗界面log
	function init_battle_log($pa,$pd)
	{
		if(strpos($pa['action'],'chase')!==false)
		{
			if(strpos($pa['action'],'pchase')!==false)
			{
				$battle_log = "但是<span class=\"red\">{$pd['name']}</span>在你身后紧追不舍！<br>";
			}
			else 
			{
				$battle_log = "你再度锁定了<span class=\"red\">{$pd['name']}</span>！<br>";
			}
		}
		elseif(strpos($pa['action'],'dfight')!==false)
		{
			$battle_log = "你与<span class=\"red\">{$pd['name']}</span>相互对峙着！<br>";
		}
		else 
		{
			if($pd['pose'] == 7)
			{
				$battle_log ="你发现了<span class=\"lime\">{$pd['name']}</span>！<br>对方看起来对你没有敌意。<br>";
			}
			else
			{
				$battle_log ="你发现敌人了<span class=\"red\">{$pd['name']}</span>！<br>对方好像完全没有注意到你！<br>";
			}
		}
		return $battle_log;
	}
?>