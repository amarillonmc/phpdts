<?php

namespace revcombat
{
	if(!defined('IN_GAME')) {
		exit('Access Denied');
	}

	# 计算pa是否在pd的反击射程内
	function calc_counter_range(&$pa,&$pd,$active)
	{
		if(!empty($pa['wep_range']) && $pd['wep_range'] >= $pa['wep_range']) return 1;
		# 鏖战状态下无视射程反击（爆系武器除外）
		if((isset($pd['is_dfight']) || isset($pa['is_dfight'])) && !empty($pd['wep_range'])) return 1;
		#「直感」触发后可以超射程反击（爆系武器除外）
		if(isset($pd['skill_c2_intuit']) && !empty($pa['wep_range']))
		{
			$sk_dice = diceroll(99);
			$sk_lvl = get_skilllvl('c2_intuit',$pd);
			$sk_obbs = get_skillvars('c2_intuit','rangerate',$sk_lvl);
			if($sk_dice < $sk_obbs) 
			{
				return 1;
			}
		}
		return 0;
	}

	# 计算pd成功对pa发起反击的概率
	function calc_counter_rate(&$pa,&$pd,$active)
	{
		global $counter_obbs,$inf_counter_p,$pose_counter_modifier,$tactic_counter_modifier;

		# 获取攻击方式的基础反击率：
		$counter = $counter_obbs[$pd['wep_kind']];

		# 获取姿态、策略对反击率的修正：
		$counter += $pose_counter_modifier[$pd['pose']];
		$counter += $tactic_counter_modifier[$pd['tactic']];

		# 计算双方射程差对反击率的影响：（高射程武器受低射程武器攻击时，反击率下降(双方射程差x10)%，最低不会低于8%）
		if($pd['wep_range'] > $pa['wep_range'] && $counter > 8)
		{
			$counter = $counter - (($pd['wep_range'] - $pa['wep_range'])*10);
			$counter = max(8,$counter);
		}

		# 鏖战状态下，将基础反击率修正为100
		if(isset($pd['is_dfight']) || isset($pa['is_dfight'])) $counter = 100;

		# 计算社团技能对反击率的修正
		$counter = calc_clbskill_counter_rate($pd,$pa,$active,$counter);

		//echo "{$pd['nm']}对{$pa['nm']}的反击率是{$counter}%<br>";
		return $counter;
	}

	# 计算社团技能对基础反击率的修正
	# 这里传入的第一个参数分别是实际上的pd和pa，所以下面的判定主体都是pa
	function calc_clbskill_counter_rate(&$pa,&$pd,$active,$counterate)
	{
		global $inf_counter_p;
		#「直感」效果判定：
		if(isset($pa['skill_c2_intuit']))
		{
			$sk_lvl = get_skilllvl('c2_intuit',$pa);
			//获取反击倍率加成
			$sk_r = 1 + (get_skillvars('c2_intuit','countergain',$sk_lvl) / 100);
			$counterate *= $sk_r;
		}
		#「臂力」效果判定：
		if(isset($pa['skill_c3_pitchpow']))
		{
			$sk_lvl = get_skilllvl('c3_pitchpow',$pa);
			//获取反击倍率加成
			$sk_r = 1 + (get_skillvars('c3_pitchpow','countergain',$sk_lvl) / 100);
			$counterate *= $sk_r;
		}
		#「天助」效果判定：
		if(isset($pa['skill_c6_godsend']))
		{
			$sk_r = get_skillpara('c6_godsend','countergain',$pa['clbpara']);
			if(!empty($sk_r))
			{
				$sk_r = 1 + ($sk_r / 100);
				$counterate *= $sk_r;
			}
		}

		# 异常状态对反击率的影响
		if(!empty($pa['inf']))
		{
			foreach ($inf_counter_p as $inf_ky => $value) 
			{
				if(strpos($pa['inf'], $inf_ky)!==false) $counterate *= $value;
			}	
		}

		return $counterate;
	}

	// 判断追击/鏖战/协战机制下双方的先制顺序、并注册对应状态
	// *追击先制率补正：原始先制率+(战场距离x10)%
	function check_revcombat_status(&$pa,&$pd,$active)
	{
		# 初始化先攻参数
		$active_r = 0;
		$active_dice = diceroll(99);
		# 计算战场距离
		$range = \revattr\get_battle_range($pa,$pd,$active);
		# pa或pd身上存在鏖战标记、或战场距离为0
		if(strpos($pa['action'],'dfight')===0 || strpos($pd['action'],'dfight')===0 || !$range)
		{
			# 添加鏖战状态
			$pa['is_dfight'] = $pd['is_dfight'] = 1;
			# 获取鏖战状态下pa对pd的先制率
			$active_r = \revbattle\calc_active_rate($pa,$pd,1);
			# 如果pa身上存在逃跑失败的标记，则pa先制率降低50……这是偷懒行为，未来的你记得改掉
			if(isset($pa['fail_escape'])) $active_r -= 50;
			# 判断是否先制
			$active = $active_dice < $active_r ? 1 : 0 ;
		}
		# pa为玩家，身上存在追击标记 或 pd为玩家，身上存在受追击标记
		elseif(strpos($pa['action'],'chase')===0 || strpos($pd['action'],'pchase')===0)
		{
			# 添加追击状态
			$pa['is_chase'] = 1; $pd['is_pchase'] = 1;
			# 获取追击状态下pa对pd的先制率
			$active_r = \revbattle\calc_active_rate($pa,$pd,2);
			# 如果pd身上存在逃跑失败的标记，则pa先制率提升50
			if(isset($pd['fail_escape'])) $active_r += 50;
			# 判断是否先制
			$active = $active_dice < $active_r ? 1 : 0 ;
			# pa先制失败，双方转入鏖战状态
			if(!$active)
			{
				unset($pa['is_chase']);unset($pd['is_pchase']);
				$pa['is_dfight'] = $pd['is_dfight'] = 1;
			}
		}
		# 清除双方标记
		$pa['action'] = $pd['action'] = '';
		$pa['bid'] = $pd['bid'] = 0;
		# 返回先制值
		return $active;
	}

	// 判断是否转入追击/鏖战流程
	function check_can_chase(&$pa,&$pd,$active)
	{
		global $chase_obbs,$dfight_obbs,$log;
		$chase_flag = 0;
		$dice = diceroll(99);
		# 进攻方(pa)或防守方(pd)已存在鏖战标记、或防守方(pd)成功反击了进攻方(pa)的攻击，检查是否维持&转入鏖战状态
		if((isset($pa['is_dfight']) || isset($pd['is_dfight']) || isset($pd['is_counter'])))
		{
			if($dice < $dfight_obbs)
			{
				# 满足鏖战条件，检查pa是玩家还是NPC，并赋予对应标记
				if($active) $pa['action'] = 'dfight'.$pd['pid'];
				else $pd['action'] = 'dfight'.$pa['pid'];
				$chase_flag = 1;
				$log.= "<span class='red'>{$pa['nm']}与{$pd['nm']}相互对峙着！</span><br>";
			}
			else 
			{
				$log.= "<span class='grey'>{$pd['nm']}从{$pa['nm']}的视野里消失了。</span><br>";
			}
		}
		# 进攻方(pa)持有非爆武器，且防守方(pd)未能及时反击，检查是否触发追击
		if(!$chase_flag && !empty($pa['wep_range']) && isset($pd['cannot_counter']))
		{
			if($dice < $dfight_obbs)
			{
				# 满足追击条件，检查pa是玩家还是NPC，并赋予对应标记
				if($active) $pa['action'] = 'chase'.$pd['pid'];
				else $pd['action'] = 'pchase'.$pa['pid'];
				$chase_flag = 1;
				$log.= "<span class='red'>但是{$pa['nm']}紧追着{$pd['nm']}不放！</span><br>";
			}
			else 
			{
				$log.= "<span class='grey'>{$pd['nm']}从{$pa['nm']}的视野里消失了。</span><br>";
			}
		}
		if($chase_flag)
		{
			# 满足追击/鏖战条件，判定战斗轮次步进
			\revattr\change_battle_turns($pa,$pd,$active);
		}
		else 
		{
			# 不满足追击/鏖战条件，重置战斗轮次
			\revattr\rs_battle_turns($pa,$pd);
		}
		return;
	}
	
}
?>