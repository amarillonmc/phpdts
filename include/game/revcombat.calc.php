<?php

	if(!defined('IN_GAME')) {
		exit('Access Denied');
	}

	# 发现率、躲避率、先制率、反击率计算方法

	// 判断追击/鏖战/协战机制下双方的先制顺序、并注册对应状态
	// *追击先制率补正：原始先制率+(战场距离x10)%
	function check_revcombat_status(&$pa,&$pd,$active)
	{
		# 初始化先攻参数
		$active_r = 0;
		$active_dice = diceroll(99);
		# 计算战场距离
		$range = get_battle_range($pa,$pd,$active);
		# pa或pd身上存在鏖战标记、或战场距离为0
		if(strpos($pa['action'],'dfight')===0 || strpos($pd['action'],'dfight')===0 || !$range)
		{
			# 添加鏖战状态
			$pa['is_dfight'] = $pd['is_dfight'] = 1;
			# 获取鏖战状态下pa对pd的先制率
			$active_r = get_active_r_rev($pa,$pd,1);
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
			$active_r = get_active_r_rev($pa,$pd,2);
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
			change_battle_turns($pa,$pd,$active);
		}
		else 
		{
			# 不满足追击/鏖战条件，重置战斗轮次
			rs_battle_turns($pa,$pd);
		}
		return;
	}
?>