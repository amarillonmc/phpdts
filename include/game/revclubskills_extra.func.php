<?php

	if (! defined ( 'IN_GAME' )) {
		exit ( 'Access Denied' );
	}

	# 新社团技能 - 特殊社团技能处理：

	//include_once GAME_ROOT.'./include/game/dice.func.php';

	/*# 「百战」技能判定
	function skill_c1_veteran_act($choice)
	{
		global $log,$pdata,$cskills,$club,$clbpara,$itemspkinfo;
		if(!check_skill_unlock('c1_veteran',$pdata))
		{
			$c_arr = get_skillvars('c1_veteran','defkind');
			$cvar = &$clbpara['skillpara']['c1_veteran']['choice'];
			if(empty($choice) || !in_array($choice,$c_arr))
			{
				$log .= "选定的防御属性不存在！<br>";
				return;
			}
			if($choice == $cvar)
			{
				$log .= "不能重复选择属性。<br>";
				return;
			}
			$cvar = $choice;
			$log .= "防御属性已变更为<span class='yellow'>{$itemspkinfo[$choice]}</span>！<br>";
		}
		else 
		{
			$log .= "「百战」技能未解锁！<br>";
		}
		return;
	}*/

	# 「穿杨」与「咆哮」解锁
	function skill_c4_unlock($csk)
	{
		global $log,$pdata,$cskills;
		if(($csk != 'c4_roar' && $csk != 'c4_sniper') || !in_array($csk,$pdata['clbpara']['skill']))
		{
			$log .= "要解锁的技能{$csk}不存在。<br>";
			return;
		}
		if(!check_skill_unlock('c4_roar',$pdata) || !check_skill_unlock('c4_sniper',$pdata))
		{
			$log .= "无法重复解锁。<br>";
			return;
		}
		//include_once GAME_ROOT.'./include/game/revclubskills.func.php';
		set_skillpara($csk,'active',1,$pdata['clbpara']);
		set_skillpara(get_skillvars($csk,'disableskill'),'disable',1,$pdata['clbpara']);
		$log .= "<span class='yellow'>已解锁技能「{$cskills[$csk]['name']}」！</span><br>";
		return;
	}

	# 尸体发火！
	function skill_tl_cstick_act(&$edata)
	{
		global $log,$pdata,$cskills;
		//include_once GAME_ROOT.'./include/game/revclubskills.func.php';
		$lock = check_skill_unlock('tl_cstick',$pdata);
		if(!$lock)
		{
			# 扣除怒气
			$pdata['rage'] -= get_skillvars('tl_cstick','ragecost');
			addnews($now,'bsk_tl_cstick',$pdata['name'],$edata['name'].'的尸体');
			addnews($now,'cstick',$pdata['name'],$edata['name'].'的尸体');
			# 炼到了不该炼的尸体
			if(in_array($edata['type'],get_skillvars('tl_cstick','notype')))
			{
				$log .= "仿佛觉察到了你那邪恶的念头，你刚一伸出手，{$edata['name']}的尸体便化作尘埃随风散去了……<br>不知为何，你感到有些惭愧。<br>";
				destory_corpse($edata);
				$pdata['rp'] += 333;
				return;
			}
			# 开抡！
			$log .= "你干脆利落地把<span class='red'>{$edata['name']}</span>从地上拽了起来！然后卯足力气，在空中挥舞了两下。<br>……<br>";
			$pdata['itm0'] = "{$edata['name']}尸体模样的棍棒";
			$pdata['itmk0'] = 'WP'; 
			$pdata['itme0'] = round($edata['msp']); 
			$pdata['itms0'] = round($edata['mhp']); 
			$pdata['itmsk0'] = '';
			$dice = diceroll(99);
			$N_obbs = pow($edata['lvl'],1.3);
			$z_obbs = !$edata['type'] ? pow($edata['lvl'],1.3) : pow($edata['lvl'],1.15);
			if($dice < $N_obbs)
			{
				$pdata['itmsk0'] .= 'N'; 
				$log .= "不错！份量不轻不重刚刚好！<br>";
			}
			if($dice < $z_obbs)
			{
				$pdata['itmsk0'] .= 'Z'; 
				$log .= "越是挥舞，越觉趁手！这尸体仿佛死来就是为你准备的！<br>哇，这下真正捡到宝了！<br>";
			}
			if(empty($pdata['itmsk0']))
			{
				$log .= "哎呀……好像这具尸体和你的相性不是很好。但是无所谓啦！<br>";
			}
			# 出生啊！
			$max_rp_dice = $pdata['itme0']+$pdata['itms0'] > 300 ? $pdata['itme0']+$pdata['itms0'] : 300;
			$rp_dice = rand(300,$max_rp_dice);
			$pdata['rp'] += $rp_dice;
			# 做成棍了就没有尸体了
			destory_corpse($edata);
			include_once GAME_ROOT.'./include/game/itemmain.func.php';
			itemget();
		}
		else 
		{
			$log .= isset($cskills['tl_cstick']['lockdesc'][$lock]) ? $cskills['tl_cstick']['lockdesc'][$lock] : $lock;
		}
		return;
	}

?>
