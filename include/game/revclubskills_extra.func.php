<?php

	if (! defined ( 'IN_GAME' )) {
		exit ( 'Access Denied' );
	}

	# 新社团技能 - 特殊社团技能处理：

	include_once GAME_ROOT.'./include/game/dice.func.php';

	# 「百战」技能判定
	function skill_c1_veteran_act($choice)
	{
		global $log,$pdata,$cskills,$club,$clbpara,$itemspkinfo;
		if(!check_skill_unlock($sk,$pdata))
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
			$log .= "「百战」的防御属性变更为{$itemspkinfo[$choice]}。<br>";
		}
		else 
		{
			$log .= "「百战」技能未解锁！<br>";
		}
		return;
	}

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
		include_once GAME_ROOT.'./include/game/revclubskills.func.php';
		set_skillpara($csk,'active',1,$pdata['clbpara']);
		set_skillpara(get_skillvars($csk,'disableskill'),'disable',1,$pdata['clbpara']);
		$log .= "<span class='yellow'>已解锁技能「{$cskills[$csk]['name']}」！</span><br>";
		return;
	}

?>
