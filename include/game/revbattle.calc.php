<?php

namespace revbattle
{
	
	if(!defined('IN_GAME')) {
		exit('Access Denied');
	}

	# 计算pa在探索/移动时会遭遇敌人的概率（遇敌率越低，道具发现率越高）
	function calc_meetman_rate(&$pa)
	{
		global $gamestate,$weather,$pose_find_modifier,$pls_find_modifier,$weather_find_r;
		
		# 基础遇敌率
		$enemyrate = 40;
		# 连斗阶段遇敌率+20
		if($gamestate == 40){$enemyrate += 20;}
		# 死斗阶段遇敌率+40
		elseif($gamestate == 50){$enemyrate += 40;}
		# 姿态对遇敌率的修正
		if(!empty($pose_find_modifier[$pa['pose']])) $enemyrate += $pose_find_modifier[$pa['pose']];
		# 天气对遇敌率的修正
		if(!empty($weather_find_r[$weather])) $enemyrate += $weather_find_r[$weather];
		# 地图场景对遇敌率的修正
		if(!empty($pls_find_modifier[$pa['pls']])) $enemyrate += $pls_find_modifier[$pa['pls']];
		
		# 社团技能对遇敌率的修正
		$enemyrate = calc_clbskill_meetman_rate($pa,$enemyrate);

		//echo "enemyrate = {$enemyrate}";

		return $enemyrate;
	}

	# pa发现pd时，计算pd被pa发现的概率
	function calc_hide_rate($pa,$pd,$mode=0)
	{
		global $weather,$weather_hide_r,$pls_hide_modifier,$pose_hide_modifier,$tactic_hide_modifier;
		
		# 获取基础躲避率
		$hide_r = 0;
		# 计算天气对躲避率的修正
		$wth_r = $weather_hide_r[$weather] ?: 0 ;
		# 计算地点对躲避率的修正
		$pls_r = $pls_hide_modifier[$pd['pls']] ?: 0 ;
		# 计算pd姿态对于躲避率的修正：
		$pose_r = $pose_hide_modifier[$pd['pose']] ?: 0;
		# 计算pd策略对于躲避率的修正：
		$tac_r = $tactic_hide_modifier[$pd['tactic']] ?: 0;
		# 基础汇总：
		$hide_r += $wth_r + $pose_r + $tac_r;

		# 社团技能对躲避率的修正
		$hide_r = calc_clbskill_hide_rate($pa,$pd,$hide_r); 
		
		return $hide_r;
	}

	# pd没能躲避pa时，双方进入战斗；计算pa对pd作出先制攻击的概率；
	# $mode 0-标准战斗 1-鏖战 2-追击（追击&鏖战基础先制率不受天气姿态影响）
	function calc_active_rate(&$pa,&$pd,$mode=0)
	{
		global $log,$now,$weather,$gamevars,$gamecfg;
		global $weather_active_r,$pose_active_modifier,$pose_active_modifier,$active_obbs,$chase_active_obbs;

		# 获取基础先攻率：
		if(!$mode)
		{
			$active_r = $active_obbs;
			# 计算天气对先攻率的修正：
			$wth_ar = $weather_active_r[$weather] ?: 0;
			# 计算pa姿态对于先攻率的修正：
			$a_pose_ar = $pose_active_modifier[$pa['pose']] ?: 0;
			# 计算pd姿态对于先攻率的修正：
			$d_pose_ar = $pose_active_modifier[$pd['pose']] ?: 0;
			# 基础汇总：
			$active_r += $wth_ar + $a_pose_ar - $d_pose_ar;
		}
		else
		{
			$active_r = $chase_active_obbs;
			# 计算追击状态下pa对pd的先攻加成。默认：战场距离*10%
			if($mode == 2) $range_ar += \revattr\get_battle_range($pa,$pd,1) * 10;
		}

		# 社团技能对先攻率的修正
		$active_r = calc_clbskill_active_rate($pa,$pd,$active_r);
		# 社团技能对先攻率的修正（定值）
		$active_r = calc_clbskill_active_rate_fix($pa,$pd,$active_r);

		# 计算先攻率上下限：
		$active_r = max(min($active_r,96),4);
		
		return $active_r;
	}

	# 计算社团技能对pa遇敌率的修正
	function calc_clbskill_meetman_rate(&$pa,$enemyrate)
	{
		# 社团技能修正（新）
		# 「专注」效果判定
		if(!empty($pa['clbpara']['skill']) && !check_skill_unlock('c5_focus',$pa)) 
		{
			# 探人模式遇敌率提升
			if(get_skillpara('c5_focus','choice',$pa['clbpara']) == 1)
			{
				$sk_var = get_skillvars('c5_focus','meetgain');
				$enemyrate += $sk_var;
			}
			# 探物模式遇敌率降低
			elseif(get_skillpara('c5_focus','choice',$pa['clbpara']) == 2)
			{
				$sk_var = get_skillvars('c5_focus','itmgain');
				$enemyrate -= $sk_var;
			}
		}

		return $enemyrate;
	}

	# 计算社团技能对躲避率（pd是否躲避pa）的修正（加算）
	function calc_clbskill_hide_rate(&$pa,&$pd,$r)
	{
		# pd持有「潜行」时的效果判定：
		if(!check_skill_unlock('c5_sneak',$pd))
		{
			$sk_lvl = get_skilllvl('c5_sneak',$pd);
			$sk_r = get_skillvars('c5_sneak','hidegain',$sk_lvl);
			$r += $sk_r;
		}
		# pd持有「天助」时的效果判定：
		if(!check_skill_unlock('c6_godsend',$pd))
		{
			$sk_r = get_skillpara('c6_godsend','hidegain',$pd['clbpara']);
			if(!empty($sk_r)) $r += $sk_r;
		}
		# pd处于「暗杀」状态下的效果判定：
		if(!check_skill_unlock('buff_assassin',$pd))
		{
			$sk_r = get_skillvars('buff_assassin','hidegain');
			$r += $sk_r;
		}
		# pa持有「瞩目」时的效果判定：
		if(!check_skill_unlock('c12_huge',$pa))
		{
			$sk_r = get_skillvars('c12_huge','hideloss');
			$r -= $sk_r;
		}
		# pd持有「瞩目」时的效果判定：
		if(!check_skill_unlock('c12_huge',$pd))
		{
			$sk_r = get_skillvars('c12_huge','hidegain');
			$r -= $sk_r;
		}
		return $r;
	}

	# 计算社团技能对先攻率（pa是否先攻pd）的修正
	function calc_clbskill_active_rate(&$pa,&$pd,$r)
	{
		global $log,$weather,$gamevars,$inf_active_p;

		# pa持有「枭眼」时的效果判定：
		if(!check_skill_unlock('c3_hawkeye',$pa))
		{
			//计算双方射程差
			$pa['wep_range'] = \revattr\get_wep_range($pa); 
			$pd['wep_range'] = \revattr\get_wep_range($pd); 
			if($pa['wep_range'] >= $pd['wep_range'])
			{
				$sk_r = get_skillvars('c3_hawkeye','actgain');
				$r += $sk_r;
			}
		}
		# pd持有「枭眼」时的效果判定：
		if(!check_skill_unlock('c3_hawkeye',$pd))
		{
			//计算双方射程差
			$pa['wep_range'] = \revattr\get_wep_range($pa); 
			$pd['wep_range'] = \revattr\get_wep_range($pd); 
			if($pa['wep_range'] < $pd['wep_range'])
			{
				$sk_r = get_skillvars('c3_hawkeye','actgain');
				$r -= $sk_r;
			}
		}
		# pa持有「潜行」时的效果判定：（只在主动发现敌人时应用）
		if(!check_skill_unlock('c5_sneak',$pa))
		{
			$sk_lvl = get_skilllvl('c5_sneak',$pa);
			$sk_r = get_skillvars('c5_sneak','actgain',$sk_lvl);
			$r += $sk_r;
		}
		# pa持有「天助」时的效果判定：（只在主动发现敌人时应用）
		if(!check_skill_unlock('c6_godsend',$pa))
		{
			$sk_r = get_skillpara('c6_godsend','actgain',$pa['clbpara']);
			if(!empty($sk_r)) $r += $sk_r;
		}
		# pa处于「暗杀」状态下的效果判定：
		if(!check_skill_unlock('buff_assassin',$pa))
		{
			$sk_r = get_skillvars('buff_assassin','actgain');
			$r += $sk_r;
		}
		# pa持有「洞察」时的效果判定：（只在主动发现敌人时应用）
		if(!check_skill_unlock('c10_insight',$pa) && \revattr\get_wep_skill($pa) > \revattr\get_wep_skill($pd))
		{
			$sk_lvl = get_skilllvl('c10_insight',$pa);
			$sk_r = get_skillvars('c10_insight','actgain',$sk_lvl);
			$r += $sk_r;
		}

		# 光玉雨特殊效果判定：
		if($weather == 18 && $gamevars['wth18pid'] == $pa['pid'])
		{
			# 计算雨势
			$wthlastime = $now - $gamevars['wth18stime'];
			# 雨势在前7分钟递增，后3分钟递减
			$wthlastime = $wthlastime <= 420 ? $wthlastime : 600 - $wthlastime;
			$wthpow = min(7,max(1,round($wthlastime / 60)));
			# 效力加成
			$r += diceroll($wthpow) + diceroll($wthpow);
		}

		# pa存在异常状态时：
		#（pd身上的异常状态不会影响pa的先制率，这个机制以后考虑改掉）
		if(!empty($pa['inf']))
		{
			$inf_ar = 1;
			foreach ($inf_active_p as $inf_ky => $value) 
			{
				if(strpos($pa['inf'], $inf_ky)!==false){$inf_ar *= $value;}
			}
			$r *= $inf_ar;
		}

		return $r;
	}

	# 计算社团技能对先攻率（pa是否先攻pd）的修正（定值）
	function calc_clbskill_active_rate_fix(&$pa,&$pd,$r)
	{
		global $log;

		# pd处于「眩晕」状态下的效果判定：
		if(!check_skill_unlock('inf_dizzy',$pd))
		{
			$log.="{$pd['name']}正处于眩晕状态！<br>";
			$r = 100;
		}

		return $r;
	}
}

?>