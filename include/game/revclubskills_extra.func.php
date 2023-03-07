<?php

	if (! defined ( 'IN_GAME' )) {
		exit ( 'Access Denied' );
	}

	# 新社团技能 - 特殊社团技能处理：

	include_once GAME_ROOT.'./include/game/dice.func.php';

	# 「百战」技能判定
	function skill_c1_veteran_act($choice)
	{
		global $log,$cskills,$club,$clbpara,$itemspkinfo;
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
		return;
	}

?>
