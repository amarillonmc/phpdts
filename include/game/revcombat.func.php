<?php

	if(!defined('IN_GAME')) {
		exit('Access Denied');
	}
	include_once GAME_ROOT.'./include/game/dice.func.php';
	include_once GAME_ROOT.'./include/game/attr.func.php';
	include_once GAME_ROOT.'./include/game/revattr.func.php';
	include_once GAME_ROOT.'./include/game/combat.func.php';

	//战斗准备流程：根据active为交战双方分配视角，初始化界面
	function rev_combat_prepare($pa,$pd,$active,$wep_kind='',$msg='',$log_print=1) 
	{
		global $db,$tablepre,$log,$mode,$main,$cmd,$battle_title;

		//没有传入pd时，读取当前玩家数据
		if(!isset($pd)) $pd = current_player_save();

		//显示界面
		if($log_print)
		{
			//格式化交战双方信息
			$init_data = update_db_player_structure();
			foreach(Array('w_','s_') as $p)
			{
				foreach ($init_data as $i) global ${$p.$i};
			}
			if($active)
			{	//先制攻击，主视角是pa，给一个s_前缀；敌对视角是pd，给一个w_前缀；
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

		//加入喊话
		if(!empty($msg)) $pa['message'] = $msg;

		//进入战斗流程
		rev_combat($pa,$pd,$active,$wep_kind,$log_print);
	}

	//战斗流程：
	function rev_combat(&$pa,&$pd,$active,$wep_kind='',$log_print=1) 
	{
		global $db,$tablepre,$now,$mode,$main,$cmd,$log;
		global $hdamage,$hplayer,$message;
		global $infinfo,$plsinfo,$hplsinfo,$nosta;

		//登记非功能性地点信息时合并隐藏地点
		foreach($hplsinfo as $hgroup=>$hpls) $plsinfo += $hpls;

		//登记称谓
		$pa['nm'] = (!$pa['type'] && $active) ? '你' : $pa['name'];
		$pd['nm'] = (!$pd['type'] && !$active && $pa['nm']!=='你') ? '你' : $pd['name'];

		//在初始化战斗阶段触发的事件。即：无论是否反击都只会触发1次的事件。
		//如果返回值小于0，则中断战斗。
		$cp_flag = combat_prepare_events($pa,$pd,$active);
		if($cp_flag < 0) goto battle_finish_flag;

		if($active)
		{
			if(!$pa['type'] && $pa['message'])
			{
				$log.="<span class=\"lime\">{$pa['nm']}大喊着：{$pa['message']}，向<span class=\"red\">{$pd['nm']}</span>发起了攻击！</span><br>";
				if (!$pd['type']) 
				{
					$w_log = "<span class=\"lime\">{$pa['name']}对你大喊：{$pa['message']}</span><br>";
					logsave ($pd['pid'],$now,$w_log,'c');
				}
			}
			else 
			{
				$log .= "{$pa['nm']}向<span class=\"red\">{$pd['nm']}</span>发起了攻击！<br>";
			}
		}
		else
		{
			$log .= "<span class=\"red\">{$pa['nm']}</span>突然向{$pd['nm']}袭来！<br>";
		}
	
		//战斗发起者是NPC时进行的判断
		if($pa['type'])
		{
			$log .= npc_chat ($pa['type'],$pa['name'],'attack');
			//换装判定
			npc_changewep();
		}
		
		//传入了攻击方式/主动技的情况下，在这里判断传入参数的合法性，并初始化$pa['wep_kind']
		get_wep_kind($pa,$wep_kind);
		get_wep_kind($pd);

		//打击流程
		$att_dmg = rev_attack($pa,$pd,$active);

		//暴毙流程
		if($pa['gg_flag'])
		{
			//登记暴毙死法
			//执行复活判定：
			//$revival_flag = revive_process($pa,$pd,$active);
			//if(!$revival_flag) goto battle_finish_flag;
		}
		
		//打击效果结算
		if(isset($att_dmg))
		{
			//扣血
			$pd['hp'] = max(0,$pd['hp']-$att_dmg);
			//判断是否触发击杀或复活：1-继续战斗；0-中止战斗
			$att_result = rev_combat_result($pa,$pd,$active);
		} 

		//判断是否进入反击流程 把!$att_result去掉可以实现复活后反击 很酷吧！
		if (($pd['hp'] > 0) && ($pd['pose'] != 5) && ($pd['tactic'] != 4) && $att_result>0) 
		{
			global $rangeinfo;
			//echo "【DEBUG】{$pd['name']}的攻击方式是{$pd['wep_kind']}<br>";
			$d_wep_temp = $pd['wep'];

			if ($rangeinfo [$pa['wep_kind']] <= $rangeinfo [$pd['wep_kind']] && $rangeinfo [$pa['wep_kind']] !== 0) 
			{
				$counter = get_counter ($pd['wep_kind'], $pd['tactic'], $pd['club'], $pd['inf']);
				$counter *= rev_get_clubskill_bonus_counter($pd['club'],$pd['skills'],$pd,$pa['club'],$pa['skills'],$pa);
				$counter_dice = rand ( 0, 99 );
				if ($counter_dice < $counter) 
				{
					$log .= "<span class=\"red\">{$pd['nm']}的反击！</span><br>";
					$log .= npc_chat ($pd['type'],$pd['nm'], 'defend' );
					//反击打击实行
					$def_dmg = rev_attack($pd,$pa,1);
				} 
				else 
				{
					$log .= npc_chat ($pd['type'],$pd['nm'], 'escape' );
					$log .= "<span class=\"red\">{$pd['nm']}处于无法反击的状态，逃跑了！</span><br>";
				}
			} 
			else 
			{
				$log .= npc_chat($pd['type'],$pd['nm'], 'cannot' );
				$log .= "<span class=\"red\">{$pd['nm']}攻击范围不足，不能反击，逃跑了！</span><br>";
			}
		}
		elseif($pd['hp']>0  && $att_result>0) 
		{
			$log .= "<span class=\"red\">{$pd['nm']}没有反击，转身逃开了！</span><br>";
		}

		//反击效果结算
		if(isset($def_dmg))
		{
			//扣血
			$pa['hp'] = max(0,$pa['hp']-$def_dmg);
			//判断是否触发击杀或复活
			$def_result = rev_combat_result($pd,$pa,1-$active);
		}

		//检查是否更新最高伤害情报
		$att_dmg = $att_dmg ? $att_dmg : 0;
		$def_dmg = $def_dmg ? $def_dmg : 0;
		if (($att_dmg > $hdamage) && ($att_dmg >= $def_dmg) && (!$pa['type'])) {
			$hdamage = $att_dmg;
			$hplayer = $pa['name'];
			save_combatinfo ();
		} elseif (($def_dmg > $hdamage) && (!$pd['type'])) {
			$hdamage = $def_dmg;
			$hplayer = $pd['name'];
			save_combatinfo ();
		}

		//logsave
		if (!$pd['type'])
		{

		}

		battle_finish_flag:
		//如果战斗中出现了死人 更新action标记
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
		if(!$pa['type'] && $acitve)
		{
			//主视角-pa是NPC的情况下，把身上的标记传递给玩家
			global $action;
			$action = $pa['action'];
		}
		//保存两个人的状态
		if ($active)
		{
			//pa是玩家/主视角NPC的情况下 把edata（w_前缀）发给$pd
			$edata=$pd; $sdata=$pa;
			player_save($pa); player_save($pd);
			if (!$pd['type']) save_enemy_battlelog($pd);
		}
		else
		{
			//pd是玩家/主视角NPC的情况下 把edata（w_前缀）发给$pa
			$edata=$pa; $sdata=$pd;
			player_save($pa); player_save($pd);
			if (!$pa['type']) save_enemy_battlelog($pa);
		}

		//刷新玩家状态
		if(!$sdata['type']) player_load($sdata);

		//刷新界面状态
		$init_data = update_db_player_structure();
		foreach(Array('w_','s_','') as $p)
		{
			foreach ($init_data as $i) global ${$p.$i};
		}
		extract($sdata,EXTR_PREFIX_ALL,'s'); extract($edata,EXTR_PREFIX_ALL,'w');
		init_rev_battle (1);
		$main = 'battle_rev';
		
		//获取后续页面
		if(substr($action,0,6)=='corpse')
		{
			include_once GAME_ROOT . './include/game/battle.func.php';
			findcorpse($edata);
		}
		else 
		{
			include template('battleresult');
			$cmd = ob_get_contents();
			ob_clean();
		}
		return;
	}

	//打击流程：
	function rev_attack(&$pa,&$pd,$active = 1) 
	{
		//通用
		global $now,$nosta,$log,$infobbs,$infinfo,$attinfo,$skillinfo,$wepimprate,$specialrate;
		global $db,$tablepre;

		# 在打击流程开始前判定的事件（直死、临摹装置、DOT结算、踩陷阱……） 返回1-继续打击流程 返回0-中止打击流程
		$gg_flag = hitrate_prepare_events($pa,$pd,$active);
		if(!$gg_flag) return 0;

		//枪托修正：你怎么老搞特殊化
		if (($pa['wep_kind'] == 'G'||$pa['wep_kind']=='J') && ($pa['weps'] == $nosta)) 
		{
			$pa['wep_kind'] = 'P';
			$pa['is_wpg'] = true;
		}

		//登记武器名
		$pa['wep_name'] = $pa['wep'];
		
		$log .= "{$pa['nm']}使用{$pa['wep']}<span class=\"yellow\">{$attinfo[$pa['wep_kind']]}</span>{$pd['nm']}！<br>";
		
		# 获取属性
		$pa['ex_equip_keys'] = $pa['ex_wep_keys'] = Array();
		$pa['ex_equip_keys'] = get_equip_ex_array($pa); //获取pa防具上的所有属性
		$pa['ex_wep_keys'] = get_wep_ex_array($pa); //获取pd武器、饰品上的所有属性

		$pd['ex_equip_keys'] = $pd['ex_wep_keys'] = Array();
		$pd['ex_equip_keys'] = get_equip_ex_array($pd);//获取pd防具上的所有属性
		$pd['ex_wep_keys'] = get_wep_ex_array($pd);//获取pd武器、饰品上的所有属性

		//技能抽取判定
		if(in_array('+',array_merge($pa['ex_wep_keys'],$pa['ex_equip_keys'])) || in_array('+',array_merge($pd['ex_wep_keys'],$pd['ex_equip_keys'])))
		{
			$log .= "<span class=\"yellow\">技能抽取使双方的武器熟练度在战斗中大幅下降！</span><br>";
			$pa['skdr_flag'] = $pd['skdr_flag'] = 1;
		}
		//灵魂抽取判定
		if(in_array('*',$pa['ex_wep_keys']) || in_array('*',$pd['ex_wep_keys']))
		{
			$log .= "<span class=\"yellow\">灵魂抽取使双方的武器和饰物属性全部失效！</span><br>";
			$pa['ex_wep_keys'] = $pd['ex_wep_keys'] = Array();
			$pa['sldr_flag'] = $pd['sldr_flag'] = 1;
		}
		//精神抽取判定
		if(in_array('-',$pa['ex_equip_keys']) || in_array('-',$pd['ex_equip_keys']))
		{
			$log .= "<span class=\"yellow\">精神抽取使双方的防具属性全部失效！</span><br>";
			$pa['ex_equip_keys'] = $pd['ex_equip_keys'] = Array();
			$pa['mdr_flag'] = $pd['mdr_flag'] = 1;
		}
		//PS:三抽检定现在没有做彼此保留的额外判定。因为单独写在这里太丑陋了。
		//因此如果一件武器/防具上同时带有3抽，有可能会被灵抽/精抽洗掉对方的效果。但是现在游戏里还没有这样的装备，所以等出问题了再解决。

		//三抽检定过后把2个属性数组合并，不然每次都要拖着一长串
		$pa['ex_keys'] = array_merge($pa['ex_wep_keys'],$pa['ex_equip_keys']); unset($pa['ex_wep_keys']); unset($pa['ex_equip_keys']);
		$pd['ex_keys'] = array_merge($pd['ex_wep_keys'],$pd['ex_equip_keys']); unset($pd['ex_wep_keys']); unset($pd['ex_equip_keys']);
		
		# 获取真实熟练度 保存在$pa['wep_skill']内
		if ($pa['club'] == 18)
		{
			$pa['wep_skill']=round($pa[$skillinfo[$pa['wep_kind']]]*0.7+($pa['wp']+$pa['wk']+$pa['wc']+$pa['wg']+$pa['wd']+$pa['wf'])*0.3);
		}
		else
		{
			$pa['wep_skill']=$pa[$skillinfo[$pa['wep_kind']]];
		}
		//应用技抽效果
		if(isset($pa['skdr_flag']) || isset($pd['skdr_flag']))
		{
			$pa['wep_skill']=sqrt($pa['wep_skill']);
		}

		# 计算武器基础命中率 保存在$pa['hitrate']内
		$pa['hitrate'] = get_hitrate_rev($pa,$pd,$active);
		# 计算命中次数 保存在$pa['hitrate_times']内
		get_hit_time_rev($pa,$pd,$active);

		# 命中次数大于0时 执行伤害判断
		if ($pa['hitrate_times'] > 0) 
		{
			//检查是否存在造成不受其他因素影响的固定伤害（例：混沌伤害、直死）
			$fix_dmg = get_fix_damage($pa,$pd,$active);
			if(isset($fix_dmg))
			{
				$damage = $fix_dmg;
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
				attack_prepare_events($pa,$pd,$active);
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
				
				# 属性伤害计算部分：
				//获取攻击方(pa)能造成的属性伤害类型
				$pa['ex_attack_keys'] = get_base_ex_att_array($pa,$pd,$active);
				//攻击方(pa)存在属性伤害：
				if($pa['ex_attack_keys'])
				{	
					//获取攻击方(pa)在造成属性伤害前触发的事件（检查pd身上是否有防御属性，pa是否触发了属穿）
					ex_attack_prepare_events($pa,$pd,$active);
					//获取攻击方(pa)能造成的属性伤害
					$ex_damage = get_original_ex_dmg($pa,$pd,$active);

					//存在大于1种属性伤害，输出一段 A+B+C=D 样式的文本
					$total_ex_damage = 0;
					if(is_array($ex_damage))
					{
						if(count($ex_damage)>1)
						{
							$log .= "造成了";
							$elog = '';
							foreach($ex_damage as $edmg)
							{
								$total_ex_damage += $edmg;
								if(!empty($elog)) $elog .= "＋".$edmg;
								else $elog = $edmg;
							}
							$log .= $elog."＝<span class=\"red\">{$total_ex_damage}</span>点属性伤害！<br>";
						}
						else 
						{
							$total_ex_damage = $ex_damage[0];
						}
					}
					else 
					{
						$total_ex_damage = $ex_damage;
					}
					//最终属性伤害
					$damage += $total_ex_damage;
				}

				#最终伤害计算部分：
				//获取最终伤害的系数变化（晶莹）
				$fin_damage_p = get_final_dmg_p($pa,$pd,$active);
				foreach($fin_damage_p as $fin_p)
				{
					$damage = round($damage * $fin_p);
				}
				//获取最终伤害的定值变化（伤害制御、剔透）
				$damage = get_final_dmg_fix($pa,$pd,$active,$damage);
				//输出log
				if($pdamage != $damage)
				{
					$log .= "<span class=\"yellow\">造成的总伤害：<span class=\"red\">$damage</span>。</span><br>";
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
			$is_player_flag = $pa['type'] ? 0 : 1;
			exprgup ( $pa['lvl'], $pd['lvl'], $pa['exp'], $is_player_flag , $pd['rage']);
		} 
		else 
		{
			$damage = 0;
			$log .= "但是没有击中！<br>";
		}
		//计算武器损耗
		weapon_loss($pa);
		//发出声音
		addnoise ( $pa['wep_kind'], $pa['wepsk'], $now, $pa['pls'], $pa['pid'], $pd['pid'], $pa['wep_kind'] );
		//增加熟练度
		$pa[$skillinfo[$pa['wep_kind']]] += $pa['club'] == 10 ? 2 : 1;

		return $damage;
	}

	//战斗结算流程：返回1=继续战斗；返回0=中止战斗；
	function rev_combat_result(&$pa,&$pd,$active)
	{
		global $log;

		# 防守方血量低于0时 结算击杀/复活事件
		if($pd['hp']<= 0)
		{
			# NPC二阶段处理：
			if($pd['club'] == 99 && $pd['type'])
			{
				$log .= npc_chat ($pd['type'],$pd['name'], 'death' );
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
				$log .= "<span class=\"red\">{$pd['nm']}被{$pa['nm']}杀死了！</span><br>";
				//执行不需要考虑复活问题的击杀事件：
				$lastword = pre_kill_events($pa,$pd,$active,$pa['wep_kind']);
				//执行复活判定：
				$revival_flag = revive_process($pa,$pd,$active);
				//没有复活的情况下，执行完后续击杀事件：
				if(!$revival_flag)
				{
					global $alivenum,$deathnum;
					$pd['hp'] = 0;
					//初始化遗言
					if (!$pd['type'])
					{
						//死者是玩家，增加击杀数并保存系统状况。
						$pa['killnum'] ++;
						$alivenum --;
					}
					else 
					{
						//死者是NPC，加载NPC遗言
						$lastword = npc_chat ($pd['type'],$pd['name'], 'death' );
					}
					$deathnum ++;
					if(!empty($lastword)) $log.= "<span class='yellow'>“".$lastword."”</span><br>";
					//初始化killmsg
					if(!$pa['type'])
					{
						global $db,$tablepre;
						$pname = $pa['name'];
						$result = $db->query("SELECT killmsg FROM {$tablepre}users WHERE username = '$pname'");
						$killmsg = $db->result($result,0);
					}
					else
					{
						$killmsg = npc_chat ($pa['type'],$pa['name'],'kill');
					}
					if($killmsg) $log .= "<span class=\"yellow\">{$pa['nm']}对{$pd['nm']}说：“{$killmsg}”</span><br>";
					
					//杀人rp结算
					get_killer_rp($pa,$pd,$active);
					//保存游戏进行状态
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

	//执行不需要考虑复活问题的击杀事件：
	function pre_kill_events(&$pa,&$pd,$active,$death) 
	{
		global $log, $now, $db, $tablepre, $typeinfo, $lwinfo;
		
		//登记死法
		if ($death == 'N') {
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
		$kname = $pa['type'] ? $pa['name'] : $pa['nick'].' '.$pa['name'];
		addnews ($now,'death'.$pd['state'],$pd['name'],$pd['type'],$pa['name'],$pa['wep_name'],$lastword );

		return $lastword;
	}

	//执行复活事件：
	function revive_process(&$pa,&$pd,$active)
	{
		global $log,$weather;

		$revival_flag = 0;

		#极光天气下，玩家有10%概率、NPC有1%概率无条件复活
		if (!$revival_flag && $weather == 17)
		{
			$aurora_rate = $pd['type'] ? 1 : 10; //玩家10%概率复活
			$aurora_dice = diceroll(99);
			if($aurora_dice<=$aurora_rate)
			{
				#奥罗拉复活效果
				$revival_flag = 17; //保存复活标记为通过奥罗拉复活
				addnews($now,'aurora_revival',$pd['name']);
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
			$revival_flag = 99; //保存复活标记为通过奥罗拉复活
			addnews($now,'revival',$pd['name']);	//玩家春哥附体称号的处理
			$pd['hp'] = $pd['mhp']; $pd['sp'] = $pd['msp'];
			$pd['state'] = 0; $pd['club'] = 17;
			$log .= '<span class="yellow">但是，由于及时按下BOMB键，'.$pd['nm'].'原地满血复活了！</span><br>';
			return $revival_flag;
		}

		return $revival_flag;
	}

?>