<?php
namespace revcombat
{
	if(!defined('IN_GAME')) {
		exit('Access Denied');
	}

	
	include_once GAME_ROOT.'./include/state.func.php';
	include_once GAME_ROOT.'./include/game/revcombat_extra.func.php';
	include_once GAME_ROOT.'./include/game/revcombat.calc.php';
	include_once GAME_ROOT.'./include/game/revattr.func.php';
	include_once GAME_ROOT.'./include/game/revattr.calc.php';
	include_once GAME_ROOT.'./include/game/revattr_extra.func.php';

	# 战斗准备流程：
	# pa、pd分别代表先制发现者与被先制发现者；
	# active用于判断当前的主视角(玩家、战斗界面下方那一栏)对应pa还是pd；
	# actvie=1代表主视角是pa，否则主视角是pd；
	function rev_combat_prepare(&$pa,&$pd,$active,$wep_kind='',$log_print=1) 
	{
		global $db,$tablepre,$log,$mode,$main,$cmd,$battle_title;

		# 格式化双方clbpara：最后保存角色数据的时候会自动转写clbpara，所以想改什么直接改就行了
		$pa['clbpara'] = get_clbpara($pa['clbpara']); $pd['clbpara'] = get_clbpara($pd['clbpara']);

		# 是否显示战斗界面
		if($log_print)
		{
			if($active)
			{
				init_battle_rev($pa,$pd,1);
				$battle_title = '战斗发生';
			}
			else 
			{
				init_battle_rev($pd,$pa,1);
				$battle_title = '遭遇突袭';
			}
			$main = 'battle_rev';
		}

		# 如果传入了主动技参数，在这里登记，并注销掉攻击类别
		if(strpos($wep_kind,'bskill_') === 0)
		{
			$bskill = substr($wep_kind,7);
			$pa['bskill'] = $bskill;
			$wep_kind = '';
		}

		# 登记称谓：之后的流程里已经用不到active了（大概）
		$pa['nm'] = (!$pa['type'] && $active) ? '你' : $pa['name']; 
		$pd['nm'] = (!$pd['type'] && !$active && $pa['nm']!=='你') ? '你' : $pd['name']; 

		# 初始化双方的攻击相关参数：
		# 依以下次序判定：
		# 1.防守方防守方式、武器射程、攻击熟练度、武器名； 
		# 2.进攻方攻击方式、进攻方主动技能参数、武器射程、攻击熟练度、武器名；
		get_attr_wepbase($pa,$pd,$active,$wep_kind);

		# 进入标准战斗流程
		rev_combat($pa,$pd,$active,$log_print);
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

		# 正式进入rev_combat战斗状态后，在判定伤害、反击流程前的事件执行阶段；
		# 即无论是否反击都只会触发1次的事件，返回值小于0时会直接中断战斗；
		$cp_flag = combat_prepare_events($pa,$pd,$active);
		if($cp_flag < 0) goto battle_finish_flag;

		# 正式进入rev_combat战斗状态后，在判定伤害、反击流程前的遇敌log
		combat_prepare_logs($pa,$pd,$active);
	
		# 打击流程
		# 这里的第一个参数指的是进攻方(造成伤害的一方)；第二个参数指的是防守方(承受伤害的一方)。active已经没用了。
		# 传参的时候只用考虑参数位置，不用管pa、pd具体是谁。
		att_loop_flag:
		$att_dmg = rev_attack($pa,$pd,$active);
		$att_result = 1;
		# 存在暴毙标识：进攻方(pa)在进攻过程中未造成伤害就暴毙，可能是因为触发了武器直死。
		if(isset($pa['gg_flag']))
		{
			$pa['hp'] = 0;
			//提前进行战斗结果判断。注意：这里的pa、pd顺序。因为暴毙的是pa，所以pa放在第二个参数位置。
			//在rev_combat_result()中：传入的第一个参数代表击杀者(敌人)、第二个参数代表死者。不用管是pa还是pd，只要按照这个规则传就行了。
			$att_result = rev_combat_result($pd,$pa,$active);
		}		
		# 没有暴毙，结算打击伤害
		elseif(!empty($att_dmg))
		{
			//扣血
			$pd['hp'] = max(0,$pd['hp']-$att_dmg);
			//判断是否触发击杀或复活：1-继续战斗；0-中止战斗
			$att_result = rev_combat_result($pa,$pd,$active);
		} 

		# 检查是否循环打击流程：一些特殊技能可能需要此效果
		if(!empty($att_result))
		{
			$att_loop = attack_check_can_loop($pa,$pd,$active);
			if($att_loop) goto att_loop_flag;
		}

		# pa打击流程结束后，如pd仍存活，且战斗未终止，检查是否进入反击流程
		if ($pd['hp']>0 && !empty($att_result)) 
		{
			$counter_flag = attack_check_can_counter($pa,$pd,$active);
			if($counter_flag)
			{
				$log .= "<span class=\"red\">{$pd['nm']}的反击！</span><br>";
				# NPC反击前事件
				if($pd['type'])
				{
					npc_changewep_rev($pd,$pa,$active);
					$log .= npc_chat_rev ($pd,$pa, 'defend' );
				}
				# 登记反击标记，表示这次打击属于反击攻击
				$pd['is_counter'] = 1; 
				# 执行反击打击
				# 因为这时候进攻方(造成伤害)的一方是pd，所以向第一个位置传入pd，向第二个位置(防守方)传入pa
				$def_dmg = rev_attack($pd,$pa,1);
			}
			else
			{
				# 不能反击的原因会在attack_check_can_counter()流程中，被登记在$pd['cannot_counter']内
				if($pd['type'])
				{
					if(!empty($pd['cannot_counter'])) $log .= npc_chat_rev ($pd,$pa,$pd['cannot_counter']);
					else $pd['cannot_counter'] = 1;
				}
				# 输出一段描述不能反击的原因的log
				$pd['cannot_counter_log'] = !empty($pd['cannot_counter_log']) ? $pd['cannot_counter_log'] : "<span class=\"red\">{$pd['nm']}转身逃开了！</span><br>";
				$log .= "<span class=\"red\">".$pd['cannot_counter_log']."</span><br>";
			}
		}

		# 存在暴毙标识：反击方(pd)在反击过程中未造成伤害就暴毙，可能是因为触发了武器直死。
		if(isset($pd['gg_flag']))
		{
			$pd['hp'] = 0;
			//提前进行战斗结果判断。注意：这里的pa、pd顺序。
			$def_result = rev_combat_result($pa,$pd,$active);
		}
		# 没有暴毙，结算反击伤害
		elseif(!empty($def_dmg))
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
		if($active && !$pd['type'])
		{
			$w_log = "手持<span class=\"red\">{$pa['wep_name']}</span>的<span class=\"yellow\">{$pa['name']}</span>向你袭击！<br>";
			if(isset($pd['logsave'])) $w_log .= $pd['logsave'];
			if(isset($pd['lvlup_log'])) $w_log .= $pd['lvlup_log'];
			$w_log .= "你受到其<span class=\"yellow\">$att_dmg</span>点攻击，对其做出了<span class=\"yellow\">$def_dmg</span>点反击。<br>";
			logsave ($pd['pid'],$now,$w_log,'c');
		}
		elseif(!$active && !$pa['type'])
		{
			$w_log = "你发现了手持<span class=\"red\">{$pd['wep_name']}</span>的<span class=\"yellow\">{$pd['name']}</span>并且先发制人！<br>";
			if(isset($pa['logsave'])) $w_log .= $pa['logsave'];
			if(isset($pa['lvlup_log'])) $w_log .= $pa['lvlup_log'];
			$w_log .= "你对其做出<span class=\"yellow\">$att_dmg</span>点攻击，受到其<span class=\"yellow\">$def_dmg</span>点反击。<br>";
			logsave ($pa['pid'],$now,$w_log,'c');
		}

		# 战斗准备事件中触发了跳过战斗标记，直接goto跳转到这个位置。
		battle_finish_flag:

		#更新action标记
		if ($active) 
		{ 
			# 战斗中出现死者 
			if ($pd['hp']<=0 && $pa['hp']>0)
			{
				$pa['action'] = 'corpse'; $pa['bid'] = $pd['pid'];
			}
			# 战斗中没有出现死者，但触发了协战标记
			elseif(isset($pa['coveratk_flag']))
			{
				$pa['action'] = 'cover'; $pa['bid'] = $pd['pid'];
				$pa['clbpara']['coveratk'] = $pa['coveratk_flag'];
			}
			# 自己死于战斗，敌人是玩家
			if ($pa['hp']<=0 && $pd['hp']>0 && !$pd['type'])
			{
				$pd['action'] = 'pacorpse'; $pd['bid'] = $pa['pid']; 
			}		
		}
		else
		{
			# 自己死于战斗，敌人是玩家
			if ($pd['hp']<=0 && $pa['hp']>0 && !$pa['type'])
			{
				$pa['action']='pacorpse'; $pa['bid'] = $pd['pid'];
			}
			# 敌人死于战斗
			if ($pa['hp']<=0 && $pd['hp']>0)
			{
				$pd['action'] = 'corpse'; $pd['bid'] = $pa['pid']; 
			}
			# 敌人没有死于战斗，但自己在战斗中触发了协战标记
			elseif(isset($pa['coveratk_flag']))
			{
				$pa['action'] = 'cover'; $pa['bid'] = $pd['pid'];
				$pa['clbpara']['coveratk'] = $pa['coveratk_flag'];
			}
		}
		# 保存双方状态
		if ($active)
		{
			# active但pa不是玩家的情况下，将pa的动作临时保存，之后清空
			if($pa['type'])
			{
				if(!empty($pa['action']))
				{
					$saction = $pa['action']; $pa['action'] = '';
					$sid = $pa['bid']; $pa['bid'] = 0;
				}
				# 佣兵攻击敌人后可以锁定追击敌人
				if(isset($pa['is_merc']) && $pd['hp'] > 0) $pa['clbpara']['mercchase'] = $pd['pid'];
			}
			init_battle_rev($pa,$pd,1);
			player_save($pa); player_save($pd);
			$edata = $pd; if(!$pa['type']) $sdata = $pa;
		}
		else
		{
			# 非active且pd不是玩家的情况下，将pd的动作临时保存，之后清空
			if($pd['type'])
			{
				if(!empty($pd['action']))
				{
					$saction = $pd['action']; $pd['action'] = '';
					$sid = $pd['bid']; $pd['bid'] = 0;
				}
				# 佣兵反击敌人后可以锁定追击敌人
				if(isset($pd['is_merc']) && $pa['hp'] > 0) $pd['clbpara']['mercchase'] = $pa['pid'];
			}
			init_battle_rev($pd,$pa,1);
			player_save($pa); player_save($pd);
			$edata = $pa; if(!$pd['type']) $sdata = $pd;
		}

		$main = 'battle_rev';

		if($log_print)
		{
			# 战斗结束后 刷新实际玩家的状态
			if(empty($sdata))
			{
				global $pdata;
				extract($pdata,EXTR_REFS);
			}
			else 
			{
				$pdata = fetch_playerdata_by_name($sdata['name']);
				extract($pdata,EXTR_REFS);
			}
			# 检查是否有需要转移到玩家身上的动作
			if(isset($saction) && isset($sid))
			{
				if(isset($pa['is_merc']) || isset($pd['is_merc'])) $log .= "<span class='lime'>结束战斗后，佣兵将你带到{$pd['nm']}的尸体面前。</span><br>";
				$action = $saction; $bid = $sid;
			}
			# 根据玩家身上的标记($action) 判断接下来要跳转的页面
			if($action == 'corpse' || $action == 'pacorpse')
			{
				# 发现尸体
				include_once GAME_ROOT . './include/game/battle.func.php';
				findcorpse($edata);
				return;
			}
			elseif($action == 'chase' || $action == 'pchase' || $action == 'dfight' || $action == 'cover' || $action == 'tpmove')
			{
				$chase_flag = 1;
			}
			else
			{
				unset($clbpara['battle_turns']);
				$action = ''; $bid = 0;
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
		$pa['ex_equip_keys'] = \revattr\get_equip_ex_array($pa); //获取pa防具上的所有属性
		$pa['ex_wep_keys'] = \revattr\get_wep_ex_array($pa); //获取pa武器、饰品上的所有属性

		$pd['ex_equip_keys'] = $pd['ex_wep_keys'] = Array();
		$pd['ex_equip_keys'] = \revattr\get_equip_ex_array($pd);//获取pd防具上的所有属性
		$pd['ex_wep_keys'] = \revattr\get_wep_ex_array($pd);//获取pd武器、饰品上的所有属性

		# 技能抽取判定
		if(in_array('+',array_merge($pa['ex_wep_keys'],$pa['ex_equip_keys'])) || in_array('+',array_merge($pd['ex_wep_keys'],$pd['ex_equip_keys'])))
		{
			$log .= "<span class=\"yellow\">技能抽取使双方的武器熟练度在战斗中大幅下降！</span><br>";
			$pa['skdr_flag'] = $pd['skdr_flag'] = 1;
			# 应用技抽效果
			if(!empty($pa['skdr_flag']) || !empty($pd['skdr_flag'])) $pa['wep_skill']=sqrt($pa['wep_skill']);
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
		\revattr\get_extra_ex_array($pa);  
		\revattr\get_extra_ex_array($pd); 
		
		# 在计算命中流程开始前判定的事件，分两种：（以后看情况要不要分成两个函数）
		# 第一种：进攻方(pa)在进攻前因为某种缘故受到伤害、甚至暴毙（直死、DOT结算等） 。判断是否继续进攻流程；
		# 如果需要登记特殊的死法，请给死者(比如pa)赋一个$pa['gg_flag'] = '死法编号'; 这样在后续的流程里会自动判定特殊死亡事件，如果没有登记特殊死法，会按照正常的战死流程登记；
		# 第二种：pa对pd的装备、武器产生影响的特殊攻击（临摹装置、一些特殊的直接伤害等）。
		# 如果这个阶段让pa或pd的武器发生了改变，记得使用get_wep_kind()刷新真实攻击方式。对武器和装备的破坏请使用weapon_loss()与armor_hurt()函数，以使装备在被破坏后从ex_keys中剔除对应的属性。
		# 返回值小于0时中止流程，否则继续。
		$flag = \revattr\hitrate_prepare_events($pa,$pd,$active);
		if($flag < 0) return $flag;

		# 计算武器基础命中率 保存在$pa['hitrate']内
		$pa['hitrate'] = \revattr\get_hitrate_rev($pa,$pd,$active);
		# 计算命中次数 保存在$pa['hitrate_times']内
		\revattr\get_hit_time_rev($pa,$pd,$active);

		$log .= "{$pa['nm']}使用{$pa['wep']}<span class=\"yellow\">{$attinfo[$pa['wep_kind']]}</span>{$pd['nm']}！<br>";

		# 战斗技文本
		if(!empty($pa['bskilllog'])) $log.= $pa['bskilllog'];
		if(!empty($pa['bskilllog2'])) $log.= $pa['bskilllog2'];
		if(!empty($pa['skilllog'])) $log.= $pa['skilllog'];

		# 命中次数大于0时 执行伤害判断
		if ($pa['hitrate_times'] > 0) 
		{
			//检查是否存在造成不受其他因素影响的固定伤害（例：混沌伤害、直死）
			$fix_dmg = \revattr\get_fix_damage($pa,$pd,$active);
			if(isset($fix_dmg))
			{
				$damage = $fix_dmg;
				$pa['final_damage'] = $damage;
				if($damage == 0)  $log .= "<span class=\"yellow\">造成的总伤害：<span class=\"red\">$damage</span>。</span><br>";
			}
			//如无，则正常计算伤害
			else
			{
				# 物理伤害计算部分：
				//获取攻击方(pa)的基础攻击力与修正
				$pa['base_att'] = \revattr\get_base_att($pa,$pd,$active);
				//获取防守方(pd)的基础防御与修正
				$pd['base_def']  = \revattr\get_base_def($pa,$pd,$active);
				//获取攻击方(pa)的原始伤害
				$damage = \revattr\get_original_dmg_rev ($pa,$pd,$active);
				//获取攻击方(pa)在原始伤害基础上附加的固定伤害（重枪、灵武固伤）
				$damage +=  \revattr\get_original_fix_dmg_rev($pa,$pd,$active);
				//获取攻击方(pa)对伤害倍率施加的变化（连击、必杀、灵力武器发挥了x%的威力） 返回的是一个数组 每个值是一个单独的系数
				$damage_p = \revattr\get_damage_p_rev ($pa,$pd,$active);
				//获取攻击方(pa)在造成伤害前触发的事件（检查pd身上是否有防御属性，pa是否触发了贯穿、冲击）
				\revattr\deal_damage_prepare_events($pa,$pd,$active);
				//获取防守方(pd)对伤害倍率施加的变化（防御属性、持有重枪受伤增加、热恋、同志）	系数保存在同一个数组里，分开2个函数只是为了调整log顺序
				$damage_p = array_merge($damage_p,\revattr\get_damage_def_p_rev($pa,$pd,$active));
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
				$damage = $damage > 1 ? round ( $damage ) : $pa['hitrate_times']; //命中了至少会造成1x连击次数的伤害
				$log.="<span class=\"red\">$damage</span>点伤害！<br>";
				//造成的最终物理伤害
				$pdamage = $damage;
				$pa['phy_damage'] = $pdamage;

				# 物理伤害计算结束后、加载预受伤事件……：
				\revattr\get_hurt_prepare_events($pa,$pd,$active);
				
				# 属性伤害计算部分：
				//获取攻击方(pa)能造成的属性伤害类型
				$pa['ex_attack_keys'] = \revattr\get_base_ex_att_array($pa,$pd,$active);
				//攻击方(pa)存在属性伤害：
				if(!empty($pa['ex_attack_keys']))
				{	
					//获取攻击方(pa)在造成属性伤害前触发的事件（检查pd身上是否有防御属性，pa是否触发了属穿）
					\revattr\deal_ex_damage_prepare_events($pa,$pd,$active);
					//获取攻击方(pa)能造成的属性伤害
					$ex_damage = \revattr\get_original_ex_dmg($pa,$pd,$active);
					//攻击方(pa)能造成了多次属性伤害的情况下，进行后续判断
					if(is_array($ex_damage) || $ex_damage > 1)
					{
						//获取攻击方(pa)能造成的属性伤害加成
						$ex_damage_p = \revattr\get_ex_dmg_p($pa,$pd,$active);
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
					//将造成的最终属性伤害登记在$pa['ex_damage'] 并入最终伤害
					$pa['ex_damage'] = $ex_damage;
					$damage += $ex_damage;
				}

				#最终伤害计算部分：
				//获取最终伤害的系数变化（晶莹、书中虫减伤）
				$fin_damage_p = \revattr\get_final_dmg_p($pa,$pd,$active);
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
				$fin_damage_fix = \revattr\get_final_dmg_fix($pa,$pd,$active,$damage);
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
			\revattr\attack_finish_events($pa,$pd,$active);
			//防守方(pd)受到伤害后的事件（防具耐久下降、受伤）
			\revattr\get_hurt_events($pa,$pd,$active);
		}
		else 
		{
			$pa['final_damage'] = $damage = 0;
			$log .= "但是没有击中！<br>";
		}
		//经验结算
		expup_rev($pa,$pd,$active);
		//怒气结算
		rgup_rev($pa,$pd,$active);
		//计算武器损耗
		if(!empty($pa['wep_imp_times'])) weapon_loss($pa,$pa['wep_imp_times']);
		//发出声音
		addnoise ( $pa['wep_kind'], $pa['wepsk'], $now, $pa['pls'], $pa['pid'], $pd['pid'], $pa['wep_kind'] );
		//增加熟练度 //天赋异禀攻击时额外+1熟练度
		$pa[$skillinfo[$pa['wep_kind']]] += $pa['club'] == 10 ? 2 : 1;
		//print_r($pa);
		return $damage;
	}

	# 战斗结算流程：返回1=继续战斗；返回0=中止战斗；
	# 这里第一个参数pa指的是杀人者(敌对方)视角，第二个参数pd指的是死者(受到伤害者)视角。不需要考虑papd具体是谁，只要按照这个规则传参就行
	# active已经没用了……大概吧
	function rev_combat_result(&$pa,&$pd,$active)
	{
		global $log,$now;
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
					final_kill_events($pa,$pd,$active,$lastword);
				}
				else 
				{
					//如果希望复活后能继续战斗，在这里加入判定条件
					//例：if($revival_flag == 99) return 1;
				}
				return 0;
			}
		}
		# 死者还活着！
		else 
		{
			# 执行扣血后的战斗结算阶段事件
			attack_result_events($pa,$pd,$active);
		}
		return 1;
	}

	function checkdmg($p1, $p2, $d) 
	{
		if ($d < 0) {
			$words = "{$p1}为{$p2}回复了<span class=\"lime\">".abs($d)."</span>点生命……这是咋回事呢？";
		} elseif (($d >= 100) && ($d < 150)) {
			$words = "{$p1}对{$p2}施加了一定程度的伤害。（100-150）";
		} elseif (($d >= 150) && ($d < 200)) {
			$words = "{$p1}拿了什么神兵？{$p2}所受的损伤已经不可忽略了。（150-200）";
		} elseif (($d >= 200) && ($d < 250)) {
			$words = "{$p1}简直不是人！{$p2}只能狼狈招架。（200-250）";
		} elseif (($d >= 250) && ($d < 300)) {
			$words = "{$p1}发出会心一击！{$p2}瞬间损失了大量生命！（250-300）";
		} elseif (($d >= 300) && ($d < 400)) {
			$words = "{$p1}使出浑身解数奋力一击！{$p2}想必凶多吉少！（300-400）";
		} elseif (($d >= 400) && ($d < 500)) {
			$words = "{$p1}使出武器中内藏的力量！可怜的{$p2}已经承受不住凶残的攻击了！（400-500）";
		} elseif (($d >= 500) && ($d < 600)) {
			$words = "{$p1}眼色一变使出绝招！{$p2}无法抵挡，只能任人宰割！（500-600）";
		} elseif (($d >= 600) && ($d < 750)) {
			$words = "{$p1}手中的武器闪耀出七彩光芒！{$p2}的身躯几乎融化在光芒中！（600-750）";
		} elseif (($d >= 750) && ($d < 1000)) {
			$words = "{$p1}受到天神的加护，打出惊天动地的一击！{$p2}此刻已不成人形！（750-1000）";
		} elseif (($d >= 1000) && ($d < 5000)) {
			$words = "{$p1}燃烧自己的生命得到了不可思议的力量！{$p2}，你还活着吗？（1000-5000）";
		} elseif (($d >= 5000) && ($d < 10000)) {
			$words = "{$p1}超越自己的极限爆发出了震天动地的力量！受此神力摧残的{$p2}化作了一颗流星！（5000-10000）";
		} elseif (($d >= 10000) && ($d < 50000)) {
			$words = "{$p1}运转百万匹周天，吐气扬声，一道霸气的光束直逼{$p2}，后者的身躯瞬间被力量的洪流所吞没！（10000-50000）";
		} elseif (($d >= 50000) && ($d < 200000)) {
			$words = "{$p1}已然超越了人类的极限！【{$d}】点的伤害——疾风怒涛般的攻击令大地崩塌，而{$p2}几乎化为齑粉！";
		}	elseif (($d >= 200000) && ($d < 500000)) {
			$words = "鬼哭神嚎！风暴既逝，{$p1}仍然屹立在战场上，而受到了【{$d}】点伤害的{$p2}想必已化为宇宙的尘埃了！";
		} elseif ( $d >= 500000) {
			$words = "残虐的攻击已经无法用言语形容！将{$p2}击飞出【{$d}】点伤害的英雄——{$p1}！让我们记住他的名字吧！";
		} else {
			$words = '';
		}
		if ($words) {
			addnews ( 0, 'damage', $words );
		}
		return;
	}
}
?>