<?php

	if(!defined('IN_GAME')) {
		exit('Access Denied');
	}
	//include_once GAME_ROOT.'./include/game/dice.func.php';
	include_once GAME_ROOT.'./include/game/attr.func.php';
	include_once GAME_ROOT.'./include/game/revattr.func.php';
	include_once GAME_ROOT.'./include/game/combat.func.php';
	include_once GAME_ROOT.'./include/game/titles.func.php';

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

	# 战斗准备流程：
	# pa、pd分别代表先制发现者与被先制发现者；
	# active用于判断当前的主视角(玩家、战斗界面下方那一栏)对应pa还是pd；
	# actvie=1代表主视角是pa，否则主视角是pd；
	function rev_combat_prepare($pa,$pd,$active,$wep_kind='',$log_print=1) 
	{
		global $db,$tablepre,$log,$mode,$main,$cmd,$battle_title;

		# actvie=0但没有传入pd时，读取当前玩家数据。
		# pa无论怎样都会有的，因为玩家主动发现还要过一遍findenemy()
		if(!isset($pd) && !$active) $pd = current_player_save();

		# 格式化双方clbpara：最后保存角色数据的时候会自动转写clbpara，所以想改什么直接改就行了
		$pa['clbpara'] = get_clbpara($pa['clbpara']); $pd['clbpara'] = get_clbpara($pd['clbpara']);

		# 是否显示战斗界面
		if($log_print)
		{
			// 格式化交战双方信息
			// 主视角（战斗界面下方那一栏）前缀是$s_；敌对视角前缀是$w_；
			// 为什么要这么搞……？都是为了兼容 NPC vs NPC 的战斗场景……
			$init_data = update_db_player_structure();
			foreach(Array('w_','s_') as $p)
			{
				foreach ($init_data as $i) global ${$p.$i};
			}
			if($active)
			{	// 先制攻击，主视角是pa，给pa一个s_前缀；敌对视角是pd，给pd一个w_前缀；
				extract($pa,EXTR_PREFIX_ALL,'s');extract($pd,EXTR_PREFIX_ALL,'w');
			}
			else 
			{	//被先制攻击，主视角是pd，敌对视角是pa
				extract($pd,EXTR_PREFIX_ALL,'s');extract($pa,EXTR_PREFIX_ALL,'w');
			}
			init_rev_battle(1);
			$battle_title = '战斗发生';
			$main = 'battle_rev';
		}

		# 如果传入了主动技参数，在这里登记
		if(strpos($wep_kind,'bskill_') === 0)
		{
			$bskill = substr($wep_kind,7);
			$pa['bskill'] = $bskill;
		}

		# 初始化双方的真实攻击方式wep_kind，传入了攻击方式/主动技的情况下，在这里判断传入参数的合法性。
		get_wep_kind($pa,$wep_kind); 
		$pa['wep_range'] = get_wep_range($pa);
		get_wep_kind($pd); 
		$pd['wep_range'] = get_wep_range($pd);

		# 传入pa为玩家、pd为NPC，且存在鏖战/追击标志时，判断战斗流程类型（标准/追击/鏖战/协战）
		if(!$pa['type'] && $pd['type'] && (strpos($pa['action'],'dfight')!==false || strpos($pa['action'],'chase')!==false))
		{
			# 玩家正在追击NPC，或两人进入鏖战状态，判定先攻
			if(strpos($pa['action'],'chase')===0 || strpos($pa['action'],'dfight')!==false)
			{
				# 传入参数，第一位为pa(玩家)，第二位为pd(NPC)，传出active
				$active = check_revcombat_status($pa,$pd,$active);
				if($active)
					rev_combat($pa,$pd,$active,$log_print);
				else
					rev_combat($pd,$pa,$active,$log_print);
			}
			# NPC正在追击玩家，判定NPC是否为先攻
			else
			{
				# 传入参数，第一位为pd(NPC)，第二位为pa(玩家)，传出1-active
				$active = check_revcombat_status($pd,$pa,$active);
				if($active)
					rev_combat($pd,$pa,1-$active,$log_print);
				else
					rev_combat($pa,$pd,1-$active,$log_print);
			}
		}
		# 进入标准战斗流程
		else 
		{	
			rev_combat($pa,$pd,$active,$log_print);
		}
	}

	# 战斗流程：
	# 注意：无论任何情况，都不要在rev_combat()执行完之前插入return！如果想要跳过战斗阶段，请使用goto battle_finish_flag;
	# 如果无论如何都要提前return，请完整执行一遍 #保存双方状态 这一块的内容；
	function rev_combat(&$pa,&$pd,$active,$log_print=1) 
	{
		global $db,$tablepre,$now,$mode,$main,$cmd,$log;
		global $hdamage,$hplayer;
		global $infinfo,$plsinfo,$hplsinfo,$nosta,$chase_obbs,$dfight_obbs;

		# 登记非功能性地点信息时合并隐藏地点
		foreach($hplsinfo as $hgroup=>$hpls) $plsinfo += $hpls;

		# 登记称谓：之后的流程里已经用不到active了（大概）
		$pa['nm'] = (!$pa['type'] && $active) ? '你' : $pa['name']; 
		$pd['nm'] = (!$pd['type'] && !$active && $pa['nm']!=='你') ? '你' : $pd['name']; 

		# 在初始化战斗阶段触发的事件。即：无论是否反击都只会触发1次的事件。如果返回值小于0，则中断战斗。
		$cp_flag = combat_prepare_events($pa,$pd,$active);
		if($cp_flag < 0) goto battle_finish_flag;

		# 遇敌log
		if($active)
		{
			if(!$pa['type'] && isset($pa['message']))
			{
				$log.="<span class=\"lime\">{$pa['nm']}大喊着：{$pa['message']}！向<span class=\"red\">{$pd['nm']}</span>发起了攻击！</span><br>";
				if (!$pd['type']) 
				{
					$w_log = "<span class=\"lime\">{$pa['name']}对你大喊：{$pa['message']}</span><br>";
					logsave ($pd['pid'],$now,$w_log,'c');
				}
			}
			else 
			{
				if(isset($pa['is_chase']))
				{
					$log .= "{$pa['nm']}再度向<span class=\"red\">{$pd['nm']}</span>发起攻击！<br>";
				}
				elseif(isset($pa['is_dfight']))
				{
					$log .= "{$pa['nm']}抓住机会抢先向<span class=\"red\">{$pd['nm']}</span>发起攻击！<br>";
				}
				else 
				{
					$log .= "{$pa['nm']}向<span class=\"red\">{$pd['nm']}</span>发起攻击！<br>";
				}
			}
		}
		else
		{
			if(isset($pa['is_chase']))
			{
				$log .= "<span class=\"red\">{$pa['nm']}</span>再度向{$pd['nm']}袭来！<br>";
			}
			elseif(isset($pa['is_dfight']))
			{
				$log .= "但是<span class=\"red\">{$pa['nm']}</span>抢先对{$pd['nm']}发起攻击！<br>";
			}
			else
			{
				$log .= "<span class=\"red\">{$pa['nm']}</span>突然向{$pd['nm']}袭来！<br>";
			}
		}
	
		# 战斗发起者是NPC时
		if($pa['type'])
		{
			$log .= npc_chat_rev ($pa,$pd,'attack');
			//换装判定
			npc_changewep_rev($pa,$pd,$active);
		}

		# 打击流程
		# 这里的第一个参数指的是进攻方(造成伤害的一方)；第二个参数指的是防守方(承受伤害的一方)。active已经没用了。
		# 传参的时候只用考虑参数位置，不用管pa、pd具体是谁。
		$att_dmg = rev_attack($pa,$pd,$active);

		# 存在暴毙标识：进攻方(pa)在进攻过程中未造成伤害就暴毙，可能是因为触发了武器直死。
		if(isset($pa['gg_flag']))
		{
			$pa['hp'] = 0;
			//提前进行战斗结果判断。注意：这里的pa、pd顺序。因为暴毙的是pa，所以pa放在第二个参数位置。
			//在rev_combat_result()中：传入的第一个参数代表击杀者(敌人)、第二个参数代表死者。不用管是pa还是pd，只要按照这个规则传就行了。
			$att_result = rev_combat_result($pd,$pa,$active);
		}		
		# 没有暴毙，结算打击伤害
		elseif(isset($att_dmg))
		{
			//扣血
			$pd['hp'] = max(0,$pd['hp']-$att_dmg);
			//判断是否触发击杀或复活：1-继续战斗；0-中止战斗
			$att_result = rev_combat_result($pa,$pd,$active);
		} 

		# 反击流程判断：$att_result>0，且敌人非治疗姿态或重视躲藏才会触发反击。 TODO：为反击条件新建一个函数
		if ($pd['hp']>0 && $att_result>0 && check_can_counter($pa,$pd,$active)) 
		{
			# 反击者是NPC时，进行换装判断
			if($pd['type']) npc_changewep_rev($pd,$pa,$active);
			if (check_in_counter_range($pa,$pd,$active)) 
			{
				# 计算反击率
				$counter = get_counter_rev($pa,$pd,$active);
				# 掷骰
				$counter_dice = diceroll(99);
				if ($counter_dice < $counter) 
				{
					$log .= "<span class=\"red\">{$pd['nm']}的反击！</span><br>";
					if($pd['type']) $log .= npc_chat_rev ($pd,$pa, 'defend' );
					# 反击打击实行
					# 因为这时候进攻方(造成伤害)的一方是pd，所以向第一个位置传入pd，向第二个位置(防守方)传入pa。
					$pd['is_counter'] = 1; //给pd一个反击标记，代表这是反击造成的伤害
					$def_dmg = rev_attack($pd,$pa,1);
				} 
				else 
				{
					$pd['cannot_counter'] = 1;
					$log .= npc_chat_rev ($pd,$pa, 'escape' );
					$log .= "<span class=\"red\">{$pd['nm']}没能抓住机会反击，逃跑了！</span><br>";
				}
			} 
			# 不满足射程
			else 
			{
				$pd['cannot_counter'] = 1;
				$log .= npc_chat_rev($pd,$pa, 'cannot' );
				$log .= "<span class=\"red\">{$pd['nm']}攻击范围不足，不能反击，逃跑了！</span><br>";
			}
		}
		# 不满足基础反击条件
		elseif($pd['hp']>0  && $att_result>0) 
		{
			$pd['cannot_counter'] = 1;
			if(isset($pd['cannot_counter_log'])) $log .= "<span class=\"red\">".$pd['cannot_counter_log']."</span><br>";
			else $log .= "<span class=\"red\">{$pd['nm']}转身逃开了！</span><br>";
		}

		# 存在暴毙标识：反击方(pd)在反击过程中未造成伤害就暴毙，可能是因为触发了武器直死。
		if(isset($pd['gg_flag']))
		{
			$pd['hp'] = 0;
			//提前进行战斗结果判断。注意：这里的pa、pd顺序。
			$def_result = rev_combat_result($pa,$pd,$active);
		}
		# 没有暴毙，结算反击伤害
		elseif(isset($def_dmg))
		{
			//扣血
			$pa['hp'] = max(0,$pa['hp']-$def_dmg);
			//判断是否触发击杀或复活。增补：$active已经没有用了，不用管它。
			$def_result = rev_combat_result($pd,$pa,1-$active);
		}

		# 攻击、反击的战斗结果判断均非0时：检查是否触发追击/鏖战事件
		if($att_result && (!isset($def_result)||!empty($def_result)) && $chase_obbs && $dfight_obbs)
		{
			check_can_chase($pa,$pd,$active);
		}

		# 检查是否更新最高伤害情报
		$att_dmg = !empty($att_dmg) ? $att_dmg : 0;
		$def_dmg = !empty($def_dmg) ? $def_dmg : 0;
		if (($att_dmg > $hdamage) && ($att_dmg >= $def_dmg) && (!$pa['type'])) {
			$hdamage = $att_dmg;
			$hplayer = $pa['name'];
			save_combatinfo ();
		} elseif (($def_dmg > $hdamage) && (!$pd['type'])) {
			$hdamage = $def_dmg;
			$hplayer = $pd['name'];
			save_combatinfo ();
		}

		# 敌人是玩家，更新logsave
		if (($active && !$pd['type']) || (!$active && !$pa['type']))
		{
			if($active)
			{
				$w_log = "手持<span class=\"red\">{$pa['wep_name']}</span>的<span class=\"yellow\">{$pa['name']}</span>向你袭击！<br>";
				if(isset($pd['logsave'])) $w_log .= $pd['logsave'];
				if(isset($pd['lvlup_log'])) $w_log .= $pd['lvlup_log'];
				$w_log .= "你受到其<span class=\"yellow\">$att_dmg</span>点攻击，对其做出了<span class=\"yellow\">$def_dmg</span>点反击。<br>";
				logsave ($pd['pid'],$now,$w_log,'c');
			}
			else
			{
				$w_log = "你发现了手持<span class=\"red\">{$pd['wep_name']}</span>的<span class=\"yellow\">{$pd['name']}</span>并且先发制人！<br>你对其做出<span class=\"yellow\">$att_dmg</span>点攻击，受到其<span class=\"yellow\">$def_dmg</span>点反击。<br>";
				if(isset($pa['logsave'])) $w_log .= $pa['logsave'];
				if(isset($pa['lvlup_log'])) $w_log .= $pa['lvlup_log'];
				$w_log .= "你受到其<span class=\"yellow\">$att_dmg</span>点攻击，对其做出了<span class=\"yellow\">$def_dmg</span>点反击。<br>";
				logsave ($pa['pid'],$now,$w_log,'c');
			}
		}

		# 战斗准备事件中触发了跳过战斗标记，直接goto跳转到这个位置。
		battle_finish_flag:

		# 如果战斗中出现了死者 更新action标记
		if ($active) 
		{ 
			if ($pd['hp']<=0 && $pa['hp']>0)
			{
				$pa['action']='corpse'.$pd['pid'];
			}
			if ($pa['hp']<=0 && $pd['hp']>0 && $pd['action']=='' && $pd['type']==0)
			{
				$pd['action'] = 'pacorpse'.$pa['pid']; 
			}		
		}
		else
		{
			if ($pd['hp']<=0 && $pa['hp']>0 && $pa['action']=='' && $pa['type']==0)
			{
				$pa['action']='pacorpse'.$pd['pid'];
			}
			if ($pa['hp']<=0 && $pd['hp']>0)
			{
				$pd['action'] = 'corpse'.$pa['pid']; 
			}
		}

		# 保存双方状态
		if ($active)
		{
			//pa是玩家/主视角NPC的情况下 把edata（$w_*）发给$pd 把sdata($s_*) 发给$pa
			$edata=$pd; $sdata=$pa;
			player_save($pa); player_save($pd);
		}
		else
		{
			//pd是玩家/主视角NPC的情况下 把edata（$w_*）发给$pa 把sdata($s_*) 发给$pd
			$edata=$pa; $sdata=$pd;
			player_save($pa); player_save($pd);
		}

		# 刷新玩家状态
		if(!$sdata['type']) player_load($sdata);
		# 主视角不是玩家，可能是玩家召唤的NPC帮手。将身上的印记传给玩家。
		elseif($sdata['type'] && $active) $action = $sdata['action'];

		# 刷新界面显示 蛋疼度+233
		$init_data = update_db_player_structure();
		foreach(Array('w_','s_','') as $p)
		{
			foreach ($init_data as $i) global ${$p.$i};
		}
		extract($sdata,EXTR_PREFIX_ALL,'s'); extract($edata,EXTR_PREFIX_ALL,'w');
		init_rev_battle (1);
		$main = 'battle_rev';
		
		# 根据玩家身上的标记($action) 判断接下来要跳转的页面
		if(substr($action,0,6)=='corpse')
		{
			// 清除战斗轮记录
			unset($clbpara['battle_turns']);
			// 发现尸体
			include_once GAME_ROOT . './include/game/battle.func.php';
			findcorpse($edata);
		}
		else 
		{
			// 转入追击状态
			if(strpos($action,'chase')!==false || strpos($action,'dfight')!==false)
			{
				$chase_flag = 1;
			}
			// 否则脱离战斗状态 清空标记
			else
			{
				unset($clbpara['battle_turns']);
				$action = '';
			}
			include template('battleresult');
			$cmd = ob_get_contents();
			ob_clean();
		}
		return;
	}

	# 打击流程：
	# 这里的第一个参数pa指的是进攻方(造成伤害的一方)；第二个参数pd指的是防守方(承受伤害的一方)。active已经没用了。
	# 传参的时候只用考虑位置，不用管pa、pd都是谁。
	function rev_attack(&$pa,&$pd,$active = 1) 
	{
		global $now,$nosta,$log,$infobbs,$infinfo,$attinfo,$skillinfo,$wepimprate,$specialrate;
		global $db,$tablepre;
		
		# 获取属性
		$pa['ex_equip_keys'] = $pa['ex_wep_keys'] = Array();
		$pa['ex_equip_keys'] = get_equip_ex_array($pa); //获取pa防具上的所有属性
		$pa['ex_wep_keys'] = get_wep_ex_array($pa); //获取pa武器、饰品上的所有属性

		$pd['ex_equip_keys'] = $pd['ex_wep_keys'] = Array();
		$pd['ex_equip_keys'] = get_equip_ex_array($pd);//获取pd防具上的所有属性
		$pd['ex_wep_keys'] = get_wep_ex_array($pd);//获取pd武器、饰品上的所有属性

		# 技能抽取判定
		if(in_array('+',array_merge($pa['ex_wep_keys'],$pa['ex_equip_keys'])) || in_array('+',array_merge($pd['ex_wep_keys'],$pd['ex_equip_keys'])))
		{
			$log .= "<span class=\"yellow\">技能抽取使双方的武器熟练度在战斗中大幅下降！</span><br>";
			$pa['skdr_flag'] = $pd['skdr_flag'] = 1;
		}
		# 灵魂抽取判定
		if(in_array('*',array_merge($pa['ex_wep_keys'],$pa['ex_equip_keys'])) || in_array('*',array_merge($pd['ex_wep_keys'],$pd['ex_equip_keys'])))
		{
			$log .= "<span class=\"yellow\">灵魂抽取使双方的武器和饰物属性全部失效！</span><br>";
			$pa['sldr_flag'] = $pd['sldr_flag'] = 1;
		}
		# 精神抽取判定
		if(in_array('-',array_merge($pa['ex_wep_keys'],$pa['ex_equip_keys'])) || in_array('-',array_merge($pd['ex_wep_keys'],$pd['ex_equip_keys'])))
		{
			$log .= "<span class=\"yellow\">精神抽取使双方的防具属性全部失效！</span><br>";
			$pa['mdr_flag'] = $pd['mdr_flag'] = 1;
		}
		# 灵、精抽应用
		if(isset($pa['sldr_flag']) || isset($pd['sldr_flag'])) $pa['ex_wep_keys'] = $pd['ex_wep_keys'] = Array();
		if(isset($pa['mdr_flag']) || isset($pd['mdr_flag'])) $pa['ex_equip_keys'] = $pd['ex_equip_keys'] = Array();
		# 三抽检定过后把2个属性数组合并，不然每次都要拖着一长串
		$pa['ex_keys'] = array_merge($pa['ex_wep_keys'],$pa['ex_equip_keys']); unset($pa['ex_wep_keys']); unset($pa['ex_equip_keys']);
		$pd['ex_keys'] = array_merge($pd['ex_wep_keys'],$pd['ex_equip_keys']); unset($pd['ex_wep_keys']); unset($pd['ex_equip_keys']);
		
		# 检查是否存在额外属性（可能来源于技能）
		get_extra_ex_array($pa);  get_extra_ex_array($pd); 
		
		# 在计算命中流程开始前判定的事件，分两种：（以后看情况要不要分成两个函数）
		# 第一种：进攻方(pa)在进攻前因为某种缘故受到伤害、甚至暴毙（直死、DOT结算等） 。判断是否继续进攻流程；
		# 如果需要登记特殊的死法，请给死者(比如pa)赋一个$pa['gg_flag'] = '死法编号'; 这样在后续的流程里会自动判定特殊死亡事件，如果没有登记特殊死法，会按照正常的战死流程登记；
		# 第二种：pa对pd的装备、武器产生影响的特殊攻击（临摹装置、一些特殊的直接伤害等）。
		# 如果这个阶段让pa或pd的武器发生了改变，记得使用get_wep_kind()刷新真实攻击方式。对武器和装备的破坏请使用weapon_loss()与armor_hurt()函数，以使装备在被破坏后从ex_keys中剔除对应的属性。
		# 返回值小于0时中止流程，否则继续。
		$flag = hitrate_prepare_events($pa,$pd,$active);
		if($flag < 0) return $flag;

		# 获取pa真实熟练度 保存在$pa['wep_skill']内
		$pa['wep_skill'] = get_wep_skill($pa);

		# 应用技抽效果
		if(isset($pa['skdr_flag']) || isset($pd['skdr_flag']))
		{
			$pa['wep_skill']=sqrt($pa['wep_skill']);
		}

		# 计算武器基础命中率 保存在$pa['hitrate']内
		$pa['hitrate'] = get_hitrate_rev($pa,$pd,$active);
		# 计算命中次数 保存在$pa['hitrate_times']内
		get_hit_time_rev($pa,$pd,$active);
		# 在执行伤害计算前登记武器名 防止武器被消耗掉后不能正常登记击杀信息
		$pa['wep_name'] = $pa['wep'];

		$log .= "{$pa['nm']}使用{$pa['wep']}<span class=\"yellow\">{$attinfo[$pa['wep_kind']]}</span>{$pd['nm']}！<br>";

		# 命中次数大于0时 执行伤害判断
		if ($pa['hitrate_times'] > 0) 
		{
			//检查是否存在造成不受其他因素影响的固定伤害（例：混沌伤害、直死）
			$fix_dmg = get_fix_damage($pa,$pd,$active);
			if(isset($fix_dmg))
			{
				$damage = $fix_dmg;
				if($damage <= 0)  $log .= "<span class=\"yellow\">造成的总伤害：<span class=\"red\">$damage</span>。</span><br>";
			}
			//如无，则正常计算伤害
			else
			{
				# 物理伤害计算部分：
				//获取攻击方(pa)的基础攻击力与修正
				$pa['base_att'] = get_base_att($pa,$pd,$active);
				//获取防守方(pd)的基础防御与修正
				$pd['base_def']  = get_base_def($pa,$pd,$active);
				//获取攻击方(pa)的原始伤害
				$damage = get_original_dmg_rev ($pa,$pd,$active);
				//获取攻击方(pa)在原始伤害基础上附加的固定伤害（重枪、灵武固伤）
				$damage +=  get_original_fix_dmg_rev($pa,$pd,$active);
				//获取攻击方(pa)对伤害倍率施加的变化（连击、必杀、灵力武器发挥了x%的威力） 返回的是一个数组 每个值是一个单独的系数
				$damage_p = get_damage_p_rev ($pa,$pd,$active);
				//获取攻击方(pa)在造成伤害前触发的事件（检查pd身上是否有防御属性，pa是否触发了贯穿、冲击）
				deal_damage_prepare_events($pa,$pd,$active);
				//获取防守方(pd)对伤害倍率施加的变化（防御属性、持有重枪受伤增加、热恋、同志）	系数保存在同一个数组里，分开2个函数只是为了调整log顺序
				$damage_p = array_merge($damage_p,get_damage_def_p_rev($pa,$pd,$active));
				//计算物理伤害：
				$log.="造成了";
				//存在伤害系数队列
				if(is_array($damage_p) && count($damage_p)>0 && $damage>1)
				{
					if(in_array('0',$damage_p))
					{
						//队列内系数有0 直接归零
						$damage = 0;
					}
					else
					{
						//否则输出一段 A×B×C=D 样式的文本
						$log .= "{$damage}";
						foreach($damage_p as $p)
						{
							$damage = round($damage*$p);
							$log .= "×{$p}";
						}
						$log .= "＝";
					}
				}
				$damage = $damage > 1 ? round ( $damage ) : 1; //命中了至少会保留1点伤害 此所谓雁过拔毛
				$log.="<span class=\"red\">$damage</span>点伤害！<br>";
				//最终物理伤害
				$pdamage = $damage;

				# 物理伤害计算结束后、加载预受伤事件……：
				get_hurt_prepare_events($pa,$pd,$active);
				
				# 属性伤害计算部分：
				//获取攻击方(pa)能造成的属性伤害类型
				$pa['ex_attack_keys'] = get_base_ex_att_array($pa,$pd,$active);
				//攻击方(pa)存在属性伤害：
				if(!empty($pa['ex_attack_keys']))
				{	
					//获取攻击方(pa)在造成属性伤害前触发的事件（检查pd身上是否有防御属性，pa是否触发了属穿）
					deal_ex_damage_prepare_events($pa,$pd,$active);
					//获取攻击方(pa)能造成的属性伤害
					$ex_damage = get_original_ex_dmg($pa,$pd,$active);
					//攻击方(pa)能造成了多次属性伤害的情况下，进行后续判断
					if(is_array($ex_damage) || $ex_damage > 1)
					{
						//获取攻击方(pa)能造成的属性伤害加成
						$ex_damage_p = get_ex_dmg_p($pa,$pd,$active);
						//存在大于1种属性伤害，输出一段 A+B+C=D 样式的文本，并将所有属性伤害保存在 $total_ex_damage 内
						if(is_array($ex_damage))
						{
							$total_ex_damage = 0;
							if(count($ex_damage)>1)
							{
								$elog = '';
								foreach($ex_damage as $edmg)
								{
									$total_ex_damage += $edmg;
									if(!empty($elog)) $elog .= "＋".$edmg;
									else $elog .= $edmg;
								}
								$elog = '造成了'.$elog;
							}
							else 
							{
								$total_ex_damage = $ex_damage[0];
							}
							//将 $total_ex_damage 存回 $ex_damage
							$ex_damage = $total_ex_damage;
						}
						//存在对最终属性伤害的修正，输出一段 A×BxC=D 或 (A+B+C)×A×B=D 样式的文本
						if(!empty($ex_damage_p))
						{
							if(isset($elog))
							{
								$elog = str_replace("造成了","造成了(",$elog);
								$elog = $elog.')';
							}
							else 
							{
								$elog = "造成了{$ex_damage}";
							}
							foreach($ex_damage_p as $edmg_p)
							{
								$ex_damage *= $edmg_p;
								$elog .= "×{$edmg_p}";
							}
						}
						$ex_damage = round($ex_damage);
						//存在额外的属性伤害文本，输出
						if(isset($elog)) $log .= $elog."＝<span class=\"red\">{$ex_damage}</span>点属性伤害！<br>";
					}
					//并入最终伤害
					$damage += $ex_damage;
				}

				#最终伤害计算部分：
				//获取最终伤害的系数变化（晶莹、书中虫减伤）
				$fin_damage_p = get_final_dmg_p($pa,$pd,$active);
				//最终伤害存在系数队列的情况下，输出一段 AxBxC=D 格式的文本，但是如果最终伤害发生了定值变化，则不会使用这一段文本。
				if(!empty($fin_damage_p))
				{
					$fd_log = $damage;
					foreach($fin_damage_p as $fin_p)
					{
						$damage = round($damage * $fin_p);
						$fd_log .= "×{$fin_p}";
					}
				}
				//获取最终伤害的定值变化（伤害制御、剔透）
				$fin_damage_fix = get_final_dmg_fix($pa,$pd,$active,$damage);
				if($fin_damage_fix != $damage) 
				{
					$o_damage = $damage;
					$damage = $fin_damage_fix;
				}
				//存在物理伤害以外的其他伤害 输出一段最终伤害log：
				if($pdamage != $damage)
				{
					$log .= "<span class=\"yellow\">造成的总伤害：";
					if(isset($fd_log)) $log .= $fd_log.'＝';
					$log .= "<span class=\"red\">{$damage}";
					if(isset($o_damage) && $o_damage != $damage) $log .= "（{$o_damage}）";
					$log .= "</span>。</span><br>";
				}
			}
			//将造成的最终伤害登记在$pa['final_damage']内
			$pa['final_damage'] = $damage;
			//将伤害发送至进行状况
			checkdmg ($pa['name'],$pd['name'],$damage);
			//攻击方(pa)造成伤害后的事件（计算反噬伤害）
			attack_finish_events($pa,$pd,$active);
			//防守方(pd)受到伤害后的事件（防具耐久下降、受伤）
			get_hurt_events($pa,$pd,$active);
			//经验结算
			expup_rev($pa,$pd,$active);
			//怒气结算
			rgup_rev($pa,$pd,$active);
		}
		else 
		{
			$damage = 0;
			$log .= "但是没有击中！<br>";
		}
		//计算武器损耗
		if(!empty($pa['wep_imp_times'])) weapon_loss($pa,$pa['wep_imp_times']);
		//发出声音
		addnoise ( $pa['wep_kind'], $pa['wepsk'], $now, $pa['pls'], $pa['pid'], $pd['pid'], $pa['wep_kind'] );
		//增加熟练度
		$pa[$skillinfo[$pa['wep_kind']]] += $pa['club'] == 10 ? 2 : 1;
		//print_r($pa);
		return $damage;
	}

	# 战斗结算流程：返回1=继续战斗；返回0=中止战斗；
	# 这里第一个参数pa指的是杀人者(敌对方)视角，第二个参数pd指的是死者(受到伤害者)视角。不需要考虑papd具体是谁，只要按照这个规则传参就行
	# active已经没用了……大概吧
	function rev_combat_result(&$pa,&$pd,$active)
	{
		global $log;

		# 死者(受伤者)pd血量低于0时 结算击杀/复活事件
		if($pd['hp']<= 0)
		{
			# NPC二阶段处理：
			if($pd['club'] == 99 && $pd['type'])
			{
				$log .= npc_chat_rev ($pd,$pa, 'death' );
				include_once GAME_ROOT . './include/system.func.php';
				$npcdata = evonpc ($pd['type'],$pd['name']);
				$log .= '<span class="yellow">'.$pd['name'].'却没死去，反而爆发出真正的实力！</span><br>';
				if($npcdata)
				{
					addnews($now , 'evonpc',$pd['name'], $npcdata['name'], $pa['name']);
					foreach($npcdata as $key => $val)
					{
						$pd[$key] = $val;
					}
					return 0;
				}
			}
			# 击杀、复活判定检查
			else
			{
				# 如果存在暴毙标记，证明pd是在攻击过程中死掉的，发送一个暴毙log
				if(isset($pd['gg_flag']))
				{
					$death_flag = $pd['gg_flag'];
					unset($pd['gg_flag']);
					$log .= "<span class=\"red\">{$pd['nm']}在与{$pa['nm']}的战斗中意外身亡！</span><br>";
				}
				# 否则，pd作为防守方被打死，发送正常击杀log
				else 
				{
					$death_flag = $pa['wep_kind'];
					$log .= "<span class=\"red\">{$pd['nm']}被{$pa['nm']}杀死了！</span><br>";
				}
				# 执行不需要考虑复活问题的击杀事件：
				$lastword = pre_kill_events($pa,$pd,$active,$death_flag);
				# 执行复活判定：
				$revival_flag = revive_process($pa,$pd,$active);
				# 没有复活的情况下，执行完后续击杀事件：
				if(!$revival_flag)
				{
					global $now,$alivenum,$deathnum;
					$pd['hp'] = 0;
					$pd['endtime'] = $pd['deathtime'] = $now;
					# 初始化遗言
					if (!$pd['type'])
					{
						//死者是玩家，增加击杀数并保存系统状况。
						$pa['killnum'] ++;
						$alivenum --;
						if(!empty($lastword)) $log .= "<span class='evergreen'>你用尽最后的力气喊道：“".$lastword."”</span><br>";
					}
					else 
					{
						//死者是NPC，加载NPC遗言
						$log .= npc_chat_rev ($pd,$pa, 'death' );
					}
					$deathnum ++;

					# 初始化killmsg
					if(!$pa['type'])
					{
						global $db,$tablepre;
						$pname = $pa['name'];
						$result = $db->query("SELECT killmsg FROM {$tablepre}users WHERE username = '$pname'");
						$killmsg = $db->result($result,0);
						if(!empty($killmsg)) $log .= "<span class=\"evergreen\">{$pa['nm']}对{$pd['nm']}说：“{$killmsg}”</span><br>";
					}
					else
					{
						$log .= npc_chat_rev ($pa,$pd,'kill');
					}

					# 杀人rp结算
					get_killer_rp($pa,$pd,$active);
					# 执行死亡事件（灵魂绑定等）
					check_death_events($pa,$pd,$active);
					# 检查成就 大补丁：击杀者是玩家时才会检查成就
					if(!$pa['type'])
					{
						include_once GAME_ROOT.'./include/game/achievement.func.php';
						check_battle_achievement_rev($pa,$pd);	
					}
					# 保存游戏进行状态
					include_once GAME_ROOT.'./include/system.func.php';
					save_gameinfo();
				}
				else 
				{
					//如果希望复活后能继续战斗，在这里加入判定条件
					//例：if($revival_flag == 99) return 1;
				}
				return 0;
			}
		}
		return 1;
	}

	# 执行不需要考虑复活问题的击杀事件：
	# 再重复一遍：这里的第一个参数指的是杀人者(敌对方)视角，第二个参数指的是死者(受到伤害者)视角。
	function pre_kill_events(&$pa,&$pd,$active,$death) 
	{
		global $log, $now, $db, $tablepre, $typeinfo, $lwinfo;
		
		// 登记死法
		// 传入了数字编号死法
		if (is_numeric($death)) {
			$pd['state'] = $death;
		// 否则按照指定武器类型判断
		} elseif ($death == 'N') {
			$pd['state'] = 20;
		} elseif ($death == 'P') {
			$pd['state'] = 21;
		} elseif ($death == 'K') {
			$pd['state'] = 22;
		} elseif ($death == 'G') {
			$pd['state'] = 23;
		} elseif ($death == 'J') {
			$pd['state'] = 23;
		} elseif ($death == 'C') {
			$pd['state'] = 24;
		} elseif ($death == 'D') {
			$pd['state'] = 25;
		} elseif ($death == 'F') {
			$pd['state'] = 29;
		} elseif ($death == 'dn') {
			$pd['state'] = 28;
		} else {
			$pd['state'] = 10;
		}
		//初始化死者信息
		$dtype = $pd['type']; $dname = $pd['name']; $dpls = $pd['pls'];
		$lwname = $typeinfo [$dtype] . ' ' . $dname;
		//初始化NPC遗言
		if($dtype)
		{
			$lastword = is_array($lwinfo[$dtype]) ? $lwinfo[$dtype][$dname] : $lwinfo[$dtype];
		}
		//初始化玩家遗言
		else 
		{
			$result = $db->query ( "SELECT lastword FROM {$tablepre}users WHERE username ='$dname'");
			$lastword = $db->result ( $result, 0 );
		}
		//向聊天框发送遗言
		$db->query ( "INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('3','$now','$lwname','$dpls','$lastword')" );

		//发送news
		$kname = $pa['type'] ? $pa['name'] : get_title_desc($pa['nick']).' '.$pa['name'];
		//$dname = $pd['type'] ? $pd['name'] : get_title_desc($pd['nick']).' '.$pd['name'];
		addnews ($now,'death'.$pd['state'],$dname,$dtype,$kname,$pa['wep_name'],$lastword );

		return $lastword;
	}

	# 执行复活事件：
	# 重要的事情要说三次：这里的第一个参数指的是杀人者(敌对方)视角，第二个参数指的是死者(受到伤害者)视角。
	function revive_process(&$pa,&$pd,$active)
	{
		global $log,$weather,$now;

		$revival_flag = 0;

		$dname = $pd['type'] ? $pd['name'] : get_title_desc($pd['nick']).' '.$pd['name'];

		#极光天气下，玩家有10%概率、NPC有1%概率无条件复活
		if (!$revival_flag && $weather == 17)
		{
			$aurora_rate = $pd['type'] ? 1 : 10; //玩家10%概率复活
			$aurora_dice = diceroll(99);
			if($aurora_dice<=$aurora_rate)
			{
				#奥罗拉复活效果
				$revival_flag = 17; //保存复活标记为通过奥罗拉复活
				addnews($now,'aurora_revival',$dname);
				$pd['hp'] += min($pd['mhp'],max($aurora_dice,1)); 
				$pd['sp'] += min($pd['msp'],max($aurora_dice,1));
				$pd['state'] = 0;
				$log.= "<span class=\"lime\">但是，空气中弥漫着的奥罗拉让{$pd['nm']}重新站了起来！</span><br>";;
				return $revival_flag;
			}
		}

		#决死结界复活：
		if (!$revival_flag && $pd['club']==99 && !$pd['type'])	
		{
			#决死结界复活效果：
			$revival_flag = 99; //保存复活标记为通过称号复活
			addnews($now,'revival',$dname);	
			$pd['hp'] = $pd['mhp']; $pd['sp'] = $pd['msp'];
			$pd['state'] = 0; changeclub(17,$pd);
			$log .= '<span class="yellow">但是，由于及时按下BOMB键，'.$pd['nm'].'原地满血复活了！</span><br>';
			return $revival_flag;
		}

		return $revival_flag;
	}

	# 特殊死亡事件（灵魂绑定等）
	function check_death_events(&$pa,&$pd,$active)
	{
		global $db,$tablepre,$log,$now,$nosta;

		# 静流下线事件：
		if($pd['type'] == 15)
		{
			//静流AI
			global $gamevars;
			$gamevars['sanmadead'] = 1;
			save_gameinfo();
		}

		# 快递被劫事件：
		if(isset($pd['clbpara']['post'])) 
		{	
			$log.="<span class='sienna'>某样东西从{$pd['name']}身上掉了出来……</span><br>";
			//获取快递信息
			$iid = $pd['clbpara']['postid'];
			//获取金主信息
			$sponsorid = $pd['clbpara']['sponsor'];
			$result = $db->query("SELECT * FROM {$tablepre}gambling WHERE uid = '$sponsorid'");
			$sordata = $db->fetch_array($result);
			//发一条news 表示快递被劫走了
			addnews($now,'gpost_failed',$sordata['uname'],$pd['itm'.$iid]);
			//消除快递相关参数
			unset($pd['clbpara']['post']);unset($pd['clbpara']['postid']);unset($pd['clbpara']['sponsor']);
			//解除快递锁
			$db->query("UPDATE {$tablepre}gambling SET bnid=0 WHERE uid='$sponsorid'");
		}

		# 灵魂绑定事件：
		foreach(Array('wep','arb','arh','ara','arf','art') as $equip)
		{
			// ……我为什么不把这个装备名数组放进resources里……用了一万遍了
			if(!empty($pd[$equip.'s']) && strpos($pd[$equip.'sk'],'v')!==false)
			{
				$log .= "伴随着{$pd['nm']}的死亡，<span class=\"yellow\">{$pd[$equip]}</span>也化作灰烬消散了。<br>";
				$pd[$equip] = $pd[$equip.'k'] = $pd[$equip.'sk'] = '';
				$pd[$equip.'e'] = $pd[$equip.'s'] = 0;
				if($equip == 'wep')
				{
					$pd[$equip] = '拳头'; $pd[$equip.'k'] = 'WN'; $pd[$equip.'sk'] = '';
					$pd[$equip.'e'] = 0; $pd[$equip.'s'] = $nosta;
				}
				elseif($equip == 'arb')
				{
					$pd[$equip] = '内衣'; $pd[$equip.'k'] = 'DN'; $pd[$equip.'sk'] = '';
					$pd[$equip.'e'] = 0; $pd[$equip.'s'] = $nosta;
				}
			}
		}
		for($i=0;$i<=6;$i++)
		{
			if(!empty($pd['itms'.$i]) && strpos($pd['itmsk'.$i],'v')!==false)
			{
				$log .= "伴随着{$pd['nm']}的死亡，<span class=\"yellow\">{$pd['itm'.$i]}</span>也化作灰烬消散了。<br>";
				$pd['itm'.$i] = $pd['itmk'.$i] = $pd['itmsk'.$i] = '';
				$pd['itme'.$i] = $pd['itms'.$i] = 0;
			}
		}

		#「掠夺」判定：
		if(isset($pa['skill_c4_loot']))
		{
			//获取抢钱率
			$sk_p = get_skillvars('c4_loot','goldr');
			$lootgold = $pa['lvl'] * $sk_p;
			$log.="<span class='yellow'>「掠夺」使{$pa['nm']}获得了{$lootgold}元！</span><br>";
		}

		return;
	}

	# 战斗怒气结算
	function rgup_rev(&$pa,&$pd,$active)
	{
		# 计算pa(攻击方)因攻击行为获得的怒气
		# pa(攻击方)拥有重击辅助属性，每次攻击额外获得1~2点怒气
		if(!empty($pa['ex_keys']) && in_array('c',$pa['ex_keys']))
		{
			$pa_rgup = rand(1,2);
			$pa['rage'] = min(255,$pa['rage']+$pa_rgup);
		}
		# 计算pd(防守方)因挨打获得的怒气
		$rgup = round(($pa['lvl'] - $pd['lvl'])/3);
		# 单次获得怒气上限：15
		$rgup = min(15,max(1,$rgup));
		# 「灭气」技能效果
		if(isset($pd['skill_c1_burnsp'])) $rgup += rand(1,2);
		$pd['rage'] = min(255,$pd['rage']+$rgup);
		return;
	}

	# 战斗经验结算
	function expup_rev(&$pa,&$pd,$active) 
	{
		global $log,$baseexp;
		$expup = round ( ($pd['lvl'] - $pa['lvl']) / 3 );
		$expup = $expup > 0 ? $expup : 1;
		$pa['exp'] += $expup;
		//$log .= "$isplayer 的经验值增加 $expup 点<br>";

		//升到下级所需的exp 直接在这里套公式计算 不用global了
		$pa['upexp'] = round(($pa['lvl']*$baseexp)+(($pa['lvl']+1)*$baseexp));

		if ($pa['exp'] >= $pa['upexp']) 
		{
			lvlup_rev ($pa,$pd,$active);
		}
		return;
	}

	# 战斗等级提升
	function lvlup_rev (&$pa,&$pd,$active) 
	{
		global $log,$baseexp;
		$up_exp_temp = round ( (2 * $pa['lvl'] + 1) * $baseexp );
		if ($pa['exp'] >= $up_exp_temp && $pa['lvl'] < 255) 
		{
			$sklanginfo = Array ('wp' => '殴熟', 'wk' => '斩熟', 'wg' => '射熟', 'wc' => '投熟', 'wd' => '爆熟', 'wf' => '灵熟', 'all' => '全系熟练度' );
			$sknlist = Array (1 => 'wp', 2 => 'wk', 3 => 'wc', 4 => 'wg', 5 => 'wd', 9 => 'wf', 16 => 'all' );
			$skname = isset($sknlist[$pa['club']]) ? $sknlist[$pa['club']] : 0;
			//升级判断
			$lvup = 1 + floor (($pa['exp'] - $up_exp_temp)/$baseexp/2);
			$lvup = $lvup > 255 - $pa['lvl'] ? 255 - $pa['lvl'] : $lvup;
			$lvuphp = $lvupatt = $lvupdef = $lvupskill = $lvupsp = $lvupspref = 0;
			//升级数值计算
			for($i = 0; $i < $lvup; $i += 1) 
			{
				if ($pa['club'] == 13) {
					$lvuphp += rand ( 14, 18 );
				} else {
					$lvuphp += rand ( 8, 10 );
				}
				$lvupsp += rand( 4,6);
				if ($pa['club'] == 14) {
					$lvupatt += rand ( 4, 6 );
					$lvupdef += rand ( 5, 8 );
				} else {
					$lvupatt += rand ( 2, 4 );
					$lvupdef += rand ( 3, 5 );
				}
				
				if ($skname == 'all') {
					$lvupskill += rand ( 2, 4 );
				} elseif ($skname == 'wd' || $skname == 'wf') {
					$lvupskill += rand ( 3, 5 );
				}elseif($skname){
					$lvupskill += rand ( 4, 6 );
				}
				$lvupspref += round($pa['msp'] * 0.1);		
			}
			//应用升级
			$pa['lvl'] += $lvup;
			$up_exp_temp = round ( (2 * $pa['lvl'] + 1) * $baseexp );
			if ($pa['lvl'] >= 255) {
				$pa['lvl'] = 255;
				$pa['exp'] = $up_exp_temp;
			}
			$pa['upexp'] = $up_exp_temp;
			$pa['hp'] += $lvuphp;
			$pa['mhp'] += $lvuphp;
			$pa['sp'] += $lvupsp;
			$pa['msp'] += $lvupsp;
			$pa['att'] += $lvupatt;
			$pa['def'] += $lvupdef;
			$pa['skillpoint'] += $lvup;
			if(!empty($skname))
			{
				if ($skname == 'all') {
					$pa['wp'] += $lvupskill;
					$pa['wk'] += $lvupskill;
					$pa['wg'] += $lvupskill;
					$pa['wc'] += $lvupskill;
					$pa['wd'] += $lvupskill;
					$pa['wf'] += $lvupskill;
				} elseif ($skname) {
					$pa[$skname] += $lvupskill;
				}
			}
			$pa['sp'] = min($lvupspref+$pa['sp'],$pa['msp']);
			
			if ($skname) {
				$sklog = "，{$sklanginfo[$skname]}+{$lvupskill}";
			}
			$lvlup_log = "<span class=\"yellow\">{$pa['nm']}升了{$lvup}级！生命上限+{$lvuphp}，体力上限+{$lvupsp}，攻击+{$lvupatt}，防御+{$lvupdef}";
			if(isset($sklog)) $lvlup_log .= $sklog;
			$lvlup_log .= "，体力恢复了{$lvupspref}，获得了{$lvup}点技能点！</span><br>";
			if(!$pa['type'])
			{
				if($pa['nm'] == '你') $log.= $lvlup_log;
				else $pa['lvlup_log'] = $lvlup_log;
			}
		} elseif ($pa['lvl'] >= 255) {
			$pa['lvl'] = 255;
			$exp = $up_exp_temp;
		}
		return;
	}

	# NPC自动换装
	#说实话没有完全看懂，但是能跑就行
	function npc_changewep_rev(&$pa,&$pd,$acitve)
	{
		global $now,$log;
		global $rangeinfo,$ex_dmg_def;

		if(!$pa['type'] || $pa['club'] != 98) return;

		$dice = diceroll(99);
		
		if($dice > 50)
		{
			$weplist = Array();
			$wepklist = Array($pa['wepk']); $weplist2 = Array();
			for($i=0;$i<=6;$i++)
			{
				if(!empty($pa['itms'.$i]) && !empty($pa['itme'.$i]) && strpos($pa['itmk'.$i],'W')===0)
				{
					$weplist[] = Array($i,$pa['itm'.$i],$pa['itmk'.$i],$pa['itme'.$i],$pa['itms'.$i],$pa['itmsk'.$i]);
					$wepklist[] = $pa['itmk'.$i];
				}
			}
			if(!empty($weplist))
			{
				$wepklist = array_unique($wepklist);
				$temp_pd_ex_keys = array_merge(get_equip_ex_array($pd),get_wep_ex_array($pd));
				$wepkAI = $wepskAI = true;
				if(!empty($temp_pd_ex_keys))
				{
					if(count($wepklist)<=1) $wepkAI = false;
					foreach($temp_pd_ex_keys as $ex)
					{
						if(in_array($ex,Array('A','B'))) $wepkAI = false;
						if(in_array($ex,Array('a','b'))) $wepskAI = false;
					}
				}
				if($wepkAI)
				{
					$wepk_temp = $pa['wepk'];
					foreach($weplist as $val)
					{
						if($rangeinfo[substr($val[2],1,1)] >= $rangeinfo[substr($wepk_temp,1,1)] && !in_array(substr($val[2],1,1),$temp_pd_ex_keys))
						{
							$weplist2[] = $val;
						}
					}
					if($weplist2)
					{
						$weplist = $weplist2;
					}
				}
				if($wepskAI && $weplist)
				{
					$minus = array();
					foreach($weplist as $val)
					{
						foreach($ex_dmg_def as $key => $val2){
							if(strpos($val[5],$key)!==false && !in_array($val2,$temp_pd_ex_keys)){
								$minus[] = $val;
							}
						}
					}
					if(count($minus) < count($weplist)){
						$weplist = array_diff($weplist,$minus);
					}				
				}
			}
			else 
			{
				//没有获取到可换装列表，直接返回
				return;
			}
			
			if(!empty($weplist))
			{
				$oldwep = $pa['wep'];
				shuffle($weplist);
				$chosen = $weplist[0];$c = $chosen[0];
				//var_dump($chosen);
				$pa['itm'.$c] = $pa['wep']; $pa['itmk'.$c] = $pa['wepk']; $pa['itmsk'.$c] = $pa['wepsk'];
				$pa['itme'.$c] = $pa['wepe']; $pa['itms'.$c] = $pa['weps'];
				$pa['wep'] = $chosen[1]; $pa['wepk'] = $chosen[2]; $pa['wepe'] = $chosen[3]; $pa['weps'] = $chosen[4]; $pa['wepsk'] = $chosen[5];
				get_wep_kind($pa);
				$pa['wep_range'] = get_wep_range($pa);
				$pa['wep_skill'] = get_wep_skill($pa);
				$pa['change_wep_log'] = "<span class=\"yellow\">{$pa['nm']}</span>将手中的<span class=\"yellow\">{$oldwep}</span>卸下，装备了<span class=\"yellow\">{$pa['wep']}</span>！<br>";
			}
		}
		return;
	}

	# NPC喊话
	# pa指npc pd指另一视角
	function npc_chat_rev(&$pa,&$pd,$mode='') 
	{
		global $npcchat;
		if(!empty($npcchat[$pa['type']][$pa['name']])) 
		{
			$nchat = $npcchat[$pa['type']][$pa['name']];
			$chatcolor = $nchat['color'];
			$npcwords = !empty($chatcolor) ? "<span class = \"{$chatcolor}\">" : '<span>';
			switch ($mode) 
			{
				case 'attack' :
					if (empty($pa['itmsk0'])) 
					{
						$npcwords .= "{$nchat[0]}";
						$pa['itmsk0'] = 1;
					}
					elseif ($pa['hp'] > ($pa['mhp'] / 2)) 
					{
						$dice = rand ( 1, 2 );
						$npcwords .= "{$nchat[$dice]}";
					} 
					else 
					{
						$dice = rand ( 3, 4 );
						$npcwords .= "{$nchat[$dice]}";
					}
					break;
				case 'defend' :
					if (empty($pa['itmsk0']))
					{
						$npcwords .= "{$nchat[0]}";
						$pa['itmsk0'] = 1;
					}
					elseif($pa['hp'] > ($pa['mhp'] / 2)) 
					{
						$dice = rand ( 5, 6 );
						$npcwords .= "{$nchat[$dice]}";
					} 
					else 
					{
						$dice = rand ( 7, 8 );
						$npcwords .= "{$nchat[$dice]}";
					}
					break;
				case 'death' :
					$npcwords .= "{$nchat[9]}";
					break;
				case 'escape' :
					$npcwords .= "{$nchat[10]}";
					break;
				case 'cannot' :
					$npcwords .= "{$nchat[11]}";
					break;
				case 'critical' :
					$npcwords .= "{$nchat[12]}";
					break;
				case 'kill' :
					$npcwords .= "{$pa['nm']}对{$pd['nm']}说道：{$nchat[13]}";
					break;
			}
			$npcwords .= '</span><br>';
			return $npcwords;
		} 
		elseif ($mode == 'death') 
		{
			global $lwinfo;
			if (is_array($lwinfo[$pa['type']])) 
			{
				$lastword = $lwinfo[$pa['type']][$pa['name']];
			} 
			else 
			{
				$lastword = $lwinfo[$pa['type']];
			}
			$npcwords = "<span class=\"yellow\">“{$lastword}”</span><br>";
			return $npcwords;
		}
		else 
		{
			return;
		}
	}

?>