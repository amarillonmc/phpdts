<?php

	if(!defined('IN_GAME')) {
		exit('Access Denied');
	}

	include_once GAME_ROOT.'./include/game/dice.func.php';
	include_once GAME_ROOT.'./include/game/clubskills.func.php';
	include_once GAME_ROOT.'./include/game/itemmain.func.php';
	include_once GAME_ROOT.'./include/game/revattr_extra.func.php';

	//获取真实攻击类别
	function get_wep_kind(&$pa,$wep_kind='')
	{
		if(!empty($wep_kind))
		{
			$pa['wep_kind'] = strpos($pa['wepk'],$wep_kind)===false ? substr ($pa['wepk'], 1, 1 ) : $wep_kind;
		}
		else
		{
			$w1 = substr ($pa['wepk'], 1, 1 );
			$w2 = substr ($pa['wepk'], 2, 1 );
			if ((($w1 == 'G')||($w1=='J')) && ($pa['weps'] == $nosta)) 
			{
				$pa['wep_kind']= $w2 ? $w2 : 'P';
			} 
			else 
			{
				$pa['wep_kind'] = $w1;
			}
		}
		return;
	}

	//获取防具上的属性
	//如果你想设计一个在战斗中能临时获得属性的机制，请在这两个函数执行完毕后，把属性加入返回的结果内。除非你希望技能的机制优先级高于三抽的判定。
	function get_equip_ex_array(&$pa)
	{
		$skarr = Array();
		foreach(Array('arb','arh','ara','arf') as $equip)
		{
			if($pa[$equip.'s'])
			{
				if(!empty($pa[$equip.'sk'])) $skarr = array_merge($skarr,get_itmsk_array($pa[$equip.'sk']));
			}
		}
		return $skarr;
	}

	//获取武器、饰品上的属性
	function get_wep_ex_array(&$pa)
	{
		$skarr = Array();
		if(!isset($pa['is_wpg']) && $pa['weps'] && !empty($pa['wepsk']))
		{
			$skarr = array_merge($skarr,get_itmsk_array($pa['wepsk']));
		}
		if($pa['arts'])
		{
			if(!empty($pa['artsk'])) $skarr = array_merge($skarr,get_itmsk_array($pa['artsk']));
			switch($pa['artk'])
			{
				case 'Ag':
					$skarr[] = 'g';
					break;
				case 'Al':
					$skarr[] = 'l';
					break;
				case 'Ah':
					$skarr[] = 'h';
					break;
				case 'Ac':
					$skarr[] = 'c';
					break;
			}
		}
		return $skarr;
	}

	//在初始化战斗阶段触发的事件。即：无论是否反击都只会触发1次的事件。
	function combat_prepare_events(&$pa,&$pd,$active)
	{
		# 百命猫 初始化事件： 每次初始化战斗时都会提升等级与怒气
		if (($pa['type'] == 89 && $pa['name']=='是TSEROF啦！') || ($pd['type'] == 89 && $pd['name']=='是TSEROF啦！'))
		{ 
			attr_extra_89_100lifecat($pa,$pd,$active);
		}

		# 笼中鸟 初始化事件：喂养成功会跳过战斗
		if($pa['type'] == 89 && $pa['name'] =='笼中鸟')
		{
			$flag = attr_extra_89_cagedbird($pa,$pd,$active);
		}
		if($pd['type'] == 89 && $pd['name'] =='笼中鸟')
		{
			$flag = attr_extra_89_cagedbird($pd,$pa,$active);
		}
		if($flag < 0) return $flag;

		return 1;
	}

	//攻击方(pa)在开始伤害计算流程前触发的事件（直死、临摹装置、DOT结算、踩陷阱……） 返回1-继续打击流程 返回0-中止打击流程
	function hitrate_prepare_events(&$pa,&$pd,$active)
	{
		global $log;

		# 玩家直死反噬：
		if(in_array('X',$pa['ex_keys']) && !$pa['type'])
		{
			$xdice = diceroll(99);
			if($xdice <= 14)
			{
				$log .= "<span class=\"red\">{$pa['nm']}手中的武器忽然失去了控制，喀吧一声就斩断了什么。那似乎是{$pa['nm']}的死线……</span><br>";
				$pa['gg_flag'] = 1; $pa['hp'] = 0;
			}
			return 0;
		}

		# 真红暮进攻事件：
		if($pa['type'] == 19 && $pa['name'] == '红暮')
		{
			attr_extra_19_crimson($pa,$pd,$active,'attack');
		}

		# 电子狐进攻事件：
		if($pa['type'] == 89 && $pa['name'] == '电掣部长 米娜')
		{
			attr_extra_89_efox($pa,$pd,$active);
		}

		# 走地羊进攻事件：
		if($pa['type'] == 89 && $pa['name'] == '坚韧之子·拉姆')
		{
			attr_extra_89_walksheep($pa,$pd,$active);
		}

		# 临摹装置：
		if($pa['wep'] == "临摹装置")
		{
			$log .= "<span class=\"yellow\">{$pa['nm']}尝试使用临摹装置来复制{$pd['nm']}的武器！</span><br>";
			$dice1 = diceroll(20);
			if($dice1 > 1)
			{
				$dice2 = diceroll(20);
				if(($pd['wepe'] > 17777) && ($dice2 <= 4)){ //对手武器过于强力则 1/4 可能失败！
					$log .= "<span class=\"red\">因为{$pd['nm']}的武器过于给力，临摹装置在{$pa['nm']}手上爆炸了！</span><br>";
					if($dice2 <= 2){
						//大失败！
						$log .= "<span class=\"red\">{$pa['nm']}眼前一黑，感觉小命要交代在这里了！</span><br>";
						$pa['hp'] = 1;
					}else{
						$log .= "<span class=\"red\">{$pa['nm']}受到了巨大的伤害！</span><br>";
						$pa['hp'] = round($pa['hp'] * 0.3);
					}
				}elseif(($pd['wepe'] > 999999) && ($dice2 >= 4)){
					$log .= "<span class=\"red\">因为{$pd['nm']}的武器过于给力，临摹装置在{$pa['nm']}手上爆炸了！</span><br>";
					if($dice2 <= 4){
						//大失败！
						$log .= "<span class=\"red\">{$pa['nm']}眼前一黑，感觉小命要交代在这里了！</span><br>";
						$pa['hp'] = 1;
					}else{
						$log .= "<span class=\"red\">{$pa['nm']}受到了特别巨大的伤害！</span><br>";
						$pa['hp'] = round($pa['hp'] * 0.1);
					}
				}else{
					$log .= "<span class=\"yellow\">{$pa['nm']}成功地复制了对手的武器！</span><br>";
					$log .= "<span class=\"yellow\">临摹装置化作了<span class=\"red\">{$pd['wep']}</span>！</span><br><br>";
					$pa['wep'] = $pd['wep']; $pa['wepk'] = $pd['wepk']; $pa['wepsk'] = $pd['wepsk'];
					$pa['wepe'] = $pd['wepe']; $pa['weps'] = $pd['weps']; 
					get_wep_kind($pa);
				}
			}
			else
			{
				$log .= "<span class=\"red\">但是似乎失败了！</span><br>";	
			}
		}
		
		return 1;
	}

	//获取基础命中率与修正
	function get_hitrate_rev(&$pa,&$pd,$active)
	{
		global $hitrate_obbs,$hitrate_max_obbs,$hitrate_r,$weather,$inf_htr_p;
		//基础命中率
		$hitrate = $hitrate_obbs[$pa['wep_kind']];
		//熟练度修正
		$hitrate += round($pa['wep_skill'] * $hitrate_r[$pa['wep_kind']]); 
		//武器基础命中率上限
		$hitrate = min($hitrate_max_obbs[$pa['wep_kind']],$hitrate);
		//获取社团技能对基础命中率的修正
		$hitrate *= rev_get_clubskill_bonus_hitrate($pa['club'],$pa['skills'],$pa,$pd['club'],$pd['skills'],$pd);
		//异常状态状态修正
		foreach ($inf_htr_p as $inf_ky => $value) 
		{
			if(strpos($pa['inf'], $inf_ky)!==false) $hitrate *= $value;
		}
		//天气修正
		if($weather == 12) $hitrate += 20;
		//属性修正
		return $hitrate;
	}

	//获取命中次数
	function get_hit_time_rev(&$pa,&$pd,$active) 
	{
		global $nosta,$wepimprate,$infobbs;

		//获取基础连击命中率衰减系数
		$hitratebonus = 0.8;
		//获取社团技能对连击命中率衰减系数的修正
		$hitratebonus *= rev_get_clubskill_bonus_hitrate($pa['club'],$pa['skills'],$pa,$pd['club'],$pd['skills'],$pd);

		//获取基础致伤率（防具耐久损伤率）系数
		$inf_r = $infobbs[$pa['wep_kind']];
		//获取社团技能对致伤率（防具耐久损伤率）的修正
		$inf_r *= rev_get_clubskill_bonus_imfrate($pa['club'],$pa['skills'],$pa,$pd['club'],$pd['skills'],$pd);
		//获取基础致伤效果（每次致伤会损耗多少点防具耐久）
		$inf_points = rev_get_clubskill_bonus_imftime($pa['club'],$pa['skills'],$pa,$pd['club'],$pd['skills'],$pd);

		//获取武器损耗类型
		$wep_imp = $wepimprate[$pa['wep_kind']];
		//武器是损耗型而非消耗型
		if($wep_imp > 0)
		{
			//基础损伤系数
			$wep_imp_obbs = $wep_imp;
			//额外损伤系数
			if(isset($pa['is_wpg'])) $wep_imp_obbs *= 4;
			if($pa['weps']==$nosta) $wep_imp_obbs *= 2;
			//社团技能对武器损伤系数的修正
			$wep_imp_obbs *= rev_get_clubskill_bonus_imprate($pa['club'],$pa['skills'],$pa,$pd['club'],$pd['skills'],$pd);
		}
		else 
		{
			//消耗型武器 每次连击必定消耗1
			$wep_imp_obbs = 100;
		}

		//获取可命中次数上限
		$atk_t = 1;
		if(in_array('r',$pa['ex_keys']) && !isset($pa['is_wpg']))
		{
			$atk_t = $pa['wep_skill'] >= 800 ? 6 : 2 + floor($pa['wep_skill']/200);
		}
		//对于消耗型武器，命中次数不能超过武器耐久
		if($wep_imp < 0)
		{
			$atk_t = $pa['weps']==$nosta ? $atk_t : min($atk_t,$pa['weps']);
		}
		
		//计算实际命中次数
		for($i = 1; $i <= $atk_t; $i ++) 
		{
			$dice = diceroll(99);
			$dice2 = diceroll(99);
			$dice3 = diceroll(99);
			if ($dice < $pa['hitrate']) 
			{
				//增加命中次数
				$pa['hitrate_times'] += 1;
				//增加致伤（防具损伤）次数
				if($dice2 < $inf_r) $pa['inf_times'] += $inf_points;
				//损耗型武器，按概率计算损耗
				if($wep_imp > 0 && $dice3 < $wep_imp_obbs) $pa['wep_imp_times']++;
			}
			//消耗型武器 不管有没有命中+1消耗
			if($wep_imp < 0) $pa['wep_imp_times']++;
			//连击命中系数衰减
			$pa['hitrate'] *= $hitratebonus;
			//连击致伤率衰减
			$inf_r *= 0.9;
			//武器是损耗型而非消耗型，随连击次数增加损耗率
			if($wep_imp>0) $wep_imp_obbs *= 1.2;
		}

		//将可命中次数上限保存在$pa['hitrate_max_times']内
		$pa['hitrate_max_times'] = $atk_t;
		return;
	}

	//获取不受其他条件影响的固定伤害变化（混沌伤害）
	function get_fix_damage(&$pa,&$pd,$active)
	{
		global $log;

		# 黑熊吃香蕉事件：
		if($pa['type'] && in_array('X',$pa['ex_keys']))
		{
			if ($pa['wep'] == '燕返262') $log.="<img src=\"img/other/262.png\"><br>";
			$damage = 999983;
			$pd['gg_flag'] = 1;
			$log .= "造成<span class=\"red\">$damage</span>点伤害！<br>";
			return $damage;
		}

		# 真红暮防御事件：
		if($pd['type'] == 19 && $pd['name'] == '红暮')
		{	
			$p = attr_extra_19_crimson($pa,$pd,$active,'defend');
			if(isset($p)) return $p;
		}

		# 数据护盾：这个有意思
		if($pd['artk'] == "AA")
		{
			if($pd['type'])
			{
				if($pd['arte'] < 100)
				{
					$pd['arte'] = min(100,$pd['arte']+$pd['arts']);
					$log .= "<span class=\"red\">{$pd['nm']}身上的数据护盾投射出了防护罩，轻松挡下了你的攻击！</span><br>";
					return 0;
				}
			}
			else 
			{
				if($pd['arte'] > 1)
				{
					$pd['arte'] = max(1,$pd['arte']-$pd['arts']);
					$log .= "<span class=\"red\">{$pd['nm']}身上的数据护盾投射出了防护罩，轻松挡下了你的攻击！</span><br>";
					return 0;
				}
			}
			$log .= "<span class=\"red\">{$pd['nm']}身上的数据护盾失效了！</span><br>";
		}

		# 迷你蜂进攻事件：
		if ($pa['type'] == 89 && $pa['name'] == '诚心使魔·阿摩尔')
		{
			$damage = attr_extra_89_minibee($pa,$pd,$active);
			return $damage;
		}

		# 魔法蜂针：
		if($pa['wep'] == "魔法蜂针")
		{
			$log .= "<span class=\"red\">{$pa['nm']}使用魔法蜂针攻击{$pd['nm']}！</span><br>";
			$damage = $pd['def']>65000 ? 1 : 350;

			if($damage > 1) $log .= "<span class=\"lime\">蜂针命中了{$pd['nm']}，对其造成了350点真实伤害！</span><br>";
			else $log .= "<span class=\"lime\">然而{$pd['nm']}的防御力实在太高，你根本无法对其造成有效伤害！</span><br>";

			if(strpos($pd['inf'],'p')===false)
			{
				$pd['inf'] .= 'p';
				$log .= "<span class=\"lime\">蜂针还让{$pd['nm']}中毒了！</span><br>";
			}
			return $damage;
		}

		# 混沌伤害：
		if(in_array('R',$pa['ex_keys']))
		{
			$maxdmg = $pd['mhp'] > $pa['wepe'] ? $pa['wepe'] : $pd['mhp'];
			$damage = rand(1,$maxdmg);
			global $log;
			$log .= "武器随机造成了<span class=\"red\">$damage</span>点伤害！<br>";
			return $damage;
		}

		return NULL;
	}

	//获取pa的攻击力
	function get_base_att(&$pa,&$pd,$active)
	{
		//空手 武器伤害=2/3熟练度
		if($pa['wep_kind'] == 'N') 
		{
			$pa['wepe_t'] = round($pa['wep_skill']*2/3);	
		} 
		//射系 武器伤害=面板数值
		elseif($pa['wep_kind'] == 'G' || $pa['wep_kind'] == 'J') 
		{
			$pa['wepe_t'] = $pa['wepe'];
		}
		//枪托打人 武器伤害=面板数值/5
		elseif($pa['is_wpg']) 
		{
			$pa['wepe_t'] = round ($pa['wepe']/ 5 );
		}
		//其他武器 武器伤害=面板数值*2
		else
		{
			$pa['wepe_t'] = $pa['wepe'] * 2;
		}
		//获取pa社团技能对攻击力的加成
		rev_get_clubskill_bonus($pa['club'],$pa['skills'],$pa,$pd['club'],$pa['skills'],$pd,$att1,$def1);
		//pa攻击力：
		$base_att = $pa['att'] + $pa['wepe_t'] + $att1;
			//echo "【DEBUG】{$pa['name']}的base_att是{$base_att}，";
		//计算攻击力修正
		$base_att = get_base_att_modifier($pa,$pd,$active,$base_att);
			//echo "修正后是{$base_att}。<br>";
		return $base_att;
	}
	
	//获取pa的攻击力修正
	function get_base_att_modifier(&$pa,&$pd,$active,$base_att)
	{
		//计算天气、姿态、策略、地点对pa攻击力的修正
		global $weather,$weather_attack_modifier,$pose_attack_modifier,$tactic_attack_modifier,$pls_attack_modifier;
		$wth_atk_per = $weather_attack_modifier[$weather] ?: 0 ;
		$pose_atk_per = $pose_attack_modifier[$pa['pose']] ?: 0 ;
		$tac_atk_per = $tactic_attack_modifier[$pa['tactic']] ?: 0;
		$pls_atk_per = $pls_attack_modifier[$pa['pls']] ?: 0;

		$base_att = round($base_att*((100+$wth_atk_per+$pose_atk_per+$tac_atk_per+$pls_atk_per)/100));

		//计算pa受伤状态对攻击力的修正
		global $inf_att_p;
		foreach ($inf_att_p as $inf_ky => $value) 
		{
			if(strpos($pa['inf'], $inf_ky)!==false) $base_att *= $value;
		}	

		//计算pa社团技能对攻击力的修正
		rev_get_clubskill_bonus_p($pa['club'],$pa['skills'],$pa,$pd['club'],$pa['skills'],$pd,$attfac,$deffac);
		$base_att *= $attfac;

		$base_att = max(1,$base_att);
		return $base_att;
	}

	//获取pd的防御力与修正
	function get_base_def(&$pa,&$pd,$active)
	{
		global $specialrate;
		//pd基础防御力：
		$base_def = $pd['def'];
		//pd装备提供防御力：
		$equip_def = $pd['arbe']+$pd['arhe']+$pd['arae']+$pd['arfe'];
		//是否受pa冲击效果影响：
		if(in_array('N',$pa['ex_keys']))
		{
			$Ndice = diceroll(99);
			if($Ndice < $specialrate['N'])
			{
				$equip_def =  round($equip_def/2);
				//为了美观考虑……冲击的log在之后的attack_prepare_events()显示
				$pa['charge_flag'] = 1;
			}
		}
		//获取pd社团技能对防御力的加成
		rev_get_clubskill_bonus($pa['club'],$pa['skills'],$pa,$pd['club'],$pa['skills'],$pd,$att1,$def1);
		//pd防御力：
		$total_def = $base_def+$equip_def+$def1;
			//echo "【DEBUG】{$pd['name']}的total_def是{$total_def}，";
		//计算防御力修正
		$total_def = get_base_def_modifier($pa,$pd,$active,$total_def);
			//echo "修正后是{$total_def}。<br>";
		return $total_def;
	}

	//获取pd的防御力修正
	function get_base_def_modifier(&$pa,&$pd,$active,$total_def)
	{
		//计算天气、姿态、策略、地点对pd防御力的修正
		global $weather,$weather_defend_modifier,$pose_defend_modifier,$tactic_defend_modifier,$pls_defend_modifier;
		$wth_def_per = $weather_defend_modifier[$weather] ?: 0 ;
		$pose_def_per = $pose_defend_modifier[$pd['pose']] ?: 0 ;
		$tac_def_per = $tactic_defend_modifier[$pd['tactic']] ?: 0;
		$pls_def_per = $pls_defend_modifier[$pd['pls']] ?: 0;

		$total_def = round($total_def*((100+$wth_def_per+$pose_def_per+$tac_def_per+$pls_def_per)/100));

		//计算受伤状态对pd防御力的修正
		global $inf_def_p;
		foreach ($inf_def_p as $inf_ky => $value) 
		{
			if(strpos($pd['inf'], $inf_ky)!==false) $total_def *= $value;
		}	

		//计算社团技能对pd防御力的修正
		rev_get_clubskill_bonus_p($pa['club'],$pa['skills'],$pa,$pd['club'],$pa['skills'],$pd,$attfac,$deffac);
		$total_def *= $deffac;

		$total_def = max(0.01,$total_def);
		return $total_def;
	}

	//计算原始伤害
	function get_original_dmg_rev(&$pa,&$pd,$active) 
	{
		global $skill_dmg, $dmg_fluc, $weather, $pls;

		//原始伤害：(pa基础攻击/pd基础防御) * pa熟练度 * pa熟练度系数
		$damage = ($pa['base_att'] / $pd['base_def']) * $pa['wep_skill'] * $skill_dmg[$pa['wep_kind']];
		//获取伤害浮动系数：
		$dfluc = $dmg_fluc [$pa['wep_kind']];
		//获取社团技能对伤害浮动系数的修正：
		$dfluc += rev_get_clubskill_bonus_fluc($pa['club'],$pa['skills'],$pa,$pd['club'],$pa['skills'],$pd);
		//计算伤害浮动：
		$dmg_factor = (100 + rand ( - $dfluc, $dfluc )) / 100;
		$damage = round ( $damage * $dmg_factor * rand ( 4, 10 ) / 10 );
		//把计算得到的原始伤害保存在$pa['original_dmg']里
		$pa['original_dmg'] = $damage;
		return $damage;
	}

	//计算在原始伤害基础上附加的固定伤害
	function get_original_fix_dmg_rev(&$pa,&$pd,$active)
	{
		//重枪
		if ($pa['wep_kind'] == 'J') 
		{
			$adddamage=$pd['mhp']/3;
			if ($adddamage>20000) $adddamage=10000;
			$damage += round($pa['wepe']*2/3+$adddamage);
		}
		//灵力武器
		if ($pa['wep_kind'] == 'F') 
		{
			global $log;
			if($pa['sldr_flag'] || $pd['sldr_flag']) 
			{
				$log.="<span class=\"red\">由于灵魂抽取的作用，灵系武器伤害大幅降低了！</span><br>";
			}
			else
			{
				$damage += $pa['wepe'];
			}
		}
		return $damage;
	}

	//计算伤害倍率变化（攻击方）
	function get_damage_p_rev(&$pa,&$pd,$active) 
	{
		global $log;

		//每一条伤害倍率判定变化提示会以$dmg_p[]= $r;的形式放入伤害倍率数组内，
		//在输出成log时会显示为：总计造成了100x0.5x1.2...=111点伤害 的形式
		$dmg_p = Array();

		# 书中虫防守事件：
		if($pd['type'] == 89)
		{
			$p = attr_extra_89_bookworm($pa,$pd,$active);
			if($p>0) $dmg_p[]= $p; 
		}

		# 重击判定：
		//获取触发重击需要的最小怒气值
		if(in_array('c',$pa['ex_keys']))
		{
			$rage_min_cost = $pa['club'] == 9 ? 20 : 10;
		}
		else 
		{
			$rage_min_cost = $pa['club'] == 9 ? 50 : 30;
		}
		if($pa['rage'] >= $rage_min_cost)
		{
			//获取触发概率
			if (isset($pa['message']) || $pa['rage'] >= 255) 
			{
				$max_dice = 100;
			}
			else 
			{
				if($pa['type']) $max_dice = 40;
				else $max_dice = $pa['club'] == 9 ? 0 : 30;
			}
			//掷骰
			$cri_dice = diceroll(100);
			if($cri_dice <= $max_dice)
			{
				$pa['rage'] -= $rage_min_cost;
				//获取伤害变化倍率
				$p = $pa['club'] == 9 ? 2 : 1.5;
				$dmg_p[]= $p; 
				//输出log
				$log .= npc_chat ($pa['type'],$pa['nm'],'critical');
				$log .= "{$pa['nm']}消耗<span class=\"yellow\">{$rage_min_cost}</span>点怒气，";
				if ($pa['club'] == 9) $log .= "<span class=\"red\">发动必杀技！</span>";
				else $log .= "<span class=\"red\">使出重击！</span>";
			}
		}

		# 灵力武器伤害↔体力消耗系数判定：
		if($pa['wep_kind'] == 'F')
		{
			//玩家使用灵力武器才会计算体力消耗
			if(!$pa['type'])
			{
				//获取体力消耗系数：
				$sp_cost_r = $pa['club'] == 9 ? 0.2 : 0.25;
				//获取社团技能对体力消耗系数的修正：
				$sp_cost_r *= get_clubskill_bonus_spd($pa['club'],$pa['skills']);
				//获取理论消耗体力最大值：
				$sp_cost_max = $sp_cost_r*$pa['wepe'];
				//获取实际消耗体力：
				$sp_cost = min($sp_cost_max,$pa['sp']-1);
				$log .= "消耗{$sp_cost}点体力，";
			}
			//获取威力系数：NPC固定为50%
			$factor = $pa['type'] ? 0.5 : 0.5+($sp_cost/$sp_cost_max/2);
			//获取伤害变化倍率并扣除体力
			$dmg_p[]= $factor; 
			$pa['sp'] -= $sp_cost;
			//输出log
			$f = round ( 100 * $factor );
			$log .= "发挥了灵力武器{$f}％的威力！<br>";
		}

		# 连击判定：
		# 只要命中次数大于1就进入连击判定，不需要再检查武器有没有连击属性。方便一些技能强制附加连击
		if($pa['hitrate_times'] > 1)
		{	
			//获取连击次数伤害倍率：2次2倍，3次2.8倍，之后=2.8+(次数-3)*0.6
			$r_dmg_p = Array(2=>2,3=>2.8);
			$p = isset($r_dmg_p[$pa['hitrate_times']]) ? $r_dmg_p[$pa['hitrate_times']] : 2.8 + ($pa['hitrate_times']-3)*0.6;
			$dmg_p[]= $p; 
			//输出log
			$log .= "{$pa['hitrate_max_times']}次连续攻击命中<span class=\"yellow\">{$pa['hitrate_times']}</span>次！";
		}

		return $dmg_p;
	}

	//攻击方在造成伤害前触发的事件
	function attack_prepare_events(&$pa,&$pd,$active)
	{
		global $log,$def_kind,$specialrate,$itemspkinfo;

		# 冲击效果log显示（实际的效果判断在get_base_def()阶段）
		if($pa['charge_flag'])
		{
			$log .= "<span class=\"yellow\">{$pa['nm']}的攻击隔着{$pd['nm']}的防具造成了伤害！</span><br>";
		}

		# 检查防守方(pd)有没有伤害抹消属性
		if(in_array('B',$pd['ex_keys']))
		{
			$dice = diceroll(99);
			//检查抹消属性是否生效
			if($dice < $specialrate['B'])
			{
				$pd['phy_def_flag'] =  2;
			}
			else 
			{
				$log .="纳尼？{$pd['nm']}的装备使攻击无效化的属性竟然失效了！<br>";
			}
		}

		# 抹消不存在&未生效，检查防守方(pd)有没有全系防御属性
		if(!isset($pd['phy_def_flag']) && in_array('A',$pd['ex_keys']))
		{
			$dice = diceroll(99);
			//检查防御属性是否生效
			if($dice < 90)
			{
				$pd['phy_def_flag'] =  1;
			}
			else 
			{
				$log .= "{$pd['nm']}的装备没能发挥减半伤害的效果！<br>";
			}
		}

		# 抹消、全系防御不存在&未生效，检查防守方(pd)有没有单系防御属性：
		if(!isset($pd['phy_def_flag']) && in_array($def_kind[$pa['wep_kind']],$pd['ex_keys']))
		{
			$dice = diceroll(99);
			//检查防御属性是否生效
			if($dice < 90)
			{
				$pd['phy_def_flag'] = $def_kind[$pa['wep_kind']];
			}
			else 
			{
				$log .= "{$pd['nm']}的{$itemspkinfo[$def_kind[$pa['wep_kind']]]}没能发挥减半伤害的效果！<br>";
			}
		}

		# 贯穿效果判定：
		if(in_array('n',$pa['ex_keys']) && $pd['phy_def_flag']) 
		{
			$dice = diceroll(99);
			if ($dice < $specialrate['n']) 
			{
				$pd['phy_def_flag'] = 0;
				$log .= "<span class=\"yellow\">{$pa['nm']}的攻击贯穿了{$pd['nm']}的防具！</span><br>";
			}
		}
		return;
	}

	//计算伤害倍率变化（防守方）
	function get_damage_def_p_rev(&$pa,&$pd,$active)
	{
		global $log;

		$dmg_p = Array();

		# 防守方(pd)持有重枪受到额外伤害：
		if($pd['wep_kind'] == 'J')
		{
			//获取伤害变化倍率并扣除体力
			$p = 1.5;
			$dmg_p[]= $p; 
			//输出log
			$log.="<span class=\"red\">由于{$pd['nm']}手中的武器过于笨重，受到的伤害大增！真是大快人心啊！</span><br>";
		}

		# 热恋、同志判定：
		if(in_array('l',$pd['ex_keys']))
		{
			
			$dice = diceroll(100);
			if($dice <= 25)
			{
				if($pa['gd'] != $pd['gd'])
				{	
					$p = 0;
					$dmg_p[]= $p; 
					$log .= "<span class=\"red\">{$pa['nm']}被{$pd['nm']}迷惑，无法全力攻击！</span>";
				}
				else 
				{
					$p = 2;
					$dmg_p[]= $p; 
					$log .= "<span class=\"red\">{$pa['nm']}被{$pd['nm']}激怒，伤害加倍！</span>";
				}
			}
		}
		if(in_array('g',$pd['ex_keys']))
		{
			$dice = diceroll(100);
			if($dice <= 25)
			{
				if($pa['gd'] == $pd['gd'])
				{	
					$p = 0;
					$dmg_p[]= $p; 
					$log .= "<span class=\"red\">{$pa['nm']}被{$pd['nm']}迷惑，无法全力攻击！</span>";
				}
				else 
				{
					$p = 2;
					$dmg_p[]= $p; 
					$log .= "<span class=\"red\">{$pa['nm']}被{$pd['nm']}激怒，伤害加倍！</span>";
				}
			}
		}

		# 防御属性减伤判定：
		if($pd['phy_def_flag'])
		{
			//存在抹消属性
			if($pd['phy_def_flag']==2)
			{
				$p = 0;
				$log .= "<span class=\"yellow\">{$pa['nm']}的攻击完全被{$pd['nm']}的装备吸收了！</span><br>";
			}
			else 
			{
				$p = 0.5;
				$log .= "<span class=\"yellow\">{$pd['nm']}的装备使{$pa['nm']}的攻击伤害减半了！</span><br>";
			}
			$dmg_p[]= $p; 
		}

		return $dmg_p;
	}

	//获取pa能造成的属性伤害队列
	function get_base_ex_att_array(&$pa,&$pd,$active)
	{
		global $ex_attack;
		$ex_keys =Array();
		foreach($ex_attack as $ex)
		{
			if(in_array($ex,$pa['ex_keys']))
			{
				//去除该条件后不会过滤重复属性 即可以造成多次同属性伤害
				if(!in_array($ex,$ex_keys)) $ex_keys[]= $ex; 
			}
		}
		return $ex_keys;
	}

	//pa在造成属性伤害前触发的事件
	function ex_attack_prepare_events(&$pa,&$pd,$active)
	{
		global $log,$ex_attack,$ex_def_kind,$specialrate,$itemspkinfo;

		# 检查防守方(pd)有没有属性抹消属性
		if(in_array('b',$pd['ex_keys']))
		{
			$dice = diceroll(99);
			//检查抹消属性是否生效
			if($dice < $specialrate['b'])
			{
				$pd['ex_def_flag'] =  2;
			}
			else 
			{
				$log .="纳尼？{$pd['nm']}装备上使属性攻击无效化的属性竟然失效了！<br>"; //无效化属性攻击的属性无效化了 怎么会这样
			}
		}

		# 属性抹消未生效&不存在的情况下，检查防守方(pd)是否存在属性防御
		if(!isset($pd['ex_def_flag']) && in_array('a',$pd['ex_keys']))
		{
			$dice = diceroll(99);
			if($dice < 90)
			{
				$pd['ex_def_flag'] =  1;
			}
			else 
			{
				$log .= "属性防御装备没能发挥应有的作用！<br>";
			}
		}

		# 属性抹消、防御均未生效&不存在的情况下，检查防守方(pd)是否存在单项属性防御
		if(!isset($pd['ex_def_flag']))
		{
			foreach($pa['ex_attack_keys'] as $ex)
			{
				if(in_array($ex_def_kind[$ex],$pd['ex_keys']))
				{
					$dice = diceroll(99);
					if($dice < 90) $pd['ex_def_flag'][] = $ex; //单项防御生效，加入队列
					else $invaild_ex[]= $ex; //单项防御未生效，记录一下，之后统一输出提示文本
				}
			}
			//输出未生效的单项防御提示文本
			if(isset($invaild_ex))
			{
				$ivlog = '';
				foreach($invaild_ex as $ivex) 
				{
					if(!empty($ivlog)) $ivlog.="、".$itemspkinfo[$ex_def_kind[$ivex]];
					else $ivlog = $itemspkinfo[$ex_def_kind[$ivex]];
				}
				$log.= $ivlog."装备没能发挥应有的作用！<br>";
			}
		}

		# 破格（属穿）效果判断：
		if(in_array('y',$pa['ex_keys']) && isset($pd['ex_def_flag'])) 
		{
			$dice = diceroll(99);
			if ($dice < $specialrate['y']) 
			{
				$pd['ex_def_flag'] = 0;
				$log .= "<span class=\"yellow\">{$pa['nm']}的攻击瓦解了{$pd['nm']}的属性防护！</span><br>";
			}
		}
		return;
	}

	//计算可造成的属性伤害
	function get_original_ex_dmg(&$pa,&$pd,$active)
	{
		global $log;
		//触发了属抹效果，直接返回固定伤害值
		if($pd['ex_def_flag'] == 2) 
		{
			$total_ex_dmg = count($pa['ex_attack_keys']);
			$log .= "<span class=\"red\">属性攻击的力量完全被防具吸收了！</span>仅造成了<span class=\"red\">{$total_ex_dmg}</span>点伤害！<br>";
			return $total_ex_dmg;
		}

		//遍历并执行单次属性伤害，返回的是一个数组
		$ex_dmg = 0; $ex_inf = 0;
		//      属性攻击名   异常状态	 对应防御属性   基础属性伤害   属性伤害上限  效果↔伤害系数  熟练↔伤害系数     伤害浮动
		global $exdmgname, $exdmginf, $ex_def_kind, $ex_base_dmg, $ex_max_dmg, $ex_wep_dmg, $ex_skill_dmg, $ex_dmg_fluc;
		//	   属性↔异常   异常率     异常率上限     熟练↔异常率系数    异常↔伤害系数    得意社团
		global $ex_inf, $ex_inf_r, $ex_max_inf_r, $ex_skill_inf_r, $ex_inf_punish, $ex_good_club;

		foreach($pa['ex_attack_keys'] as $ex)
		{
			$dmginf = '';
			//计算单个属性的基础属性伤害： 基础伤害 + 效果↔伤害系数修正 + 熟练↔伤害系数修正
			$ex_dmg = $ex_base_dmg[$ex] + $pa['wepe']/$ex_wep_dmg[$ex] + $pa['wep_skill']/$ex_skill_dmg[$ex];
			//计算单个属性能造成的基础伤害上限
			if($ex_max_dmg[$ex]>0 && $ex_dmg>$ex_max_dmg[$ex]) $ex_dmg = $ex_max_dmg[$ex];

			//计算得意武器类型修正
			if($ex_good_wep[$ex] == $pa['wep_kind']) $ex_dmg *= 2;
			//计算已经进入的异常状态对属性攻击伤害的影响
			if(strpos($inf,$exdmginf[$ex])!==false && isset($ex_inf_punish[$ex]))
			{
				$ex_dmg *= $ex_inf_punish[$ex];
				$log .= "由于{$pd['nm']}已经{$dmginf}，{$exdmgname[$ex]}伤害";
				$log .= $ex_inf_punish[$ex]>1 ? "倍增！" : "减少！";
			}
			//计算属性伤害浮动
			$ex_dmg = round($ex_dmg * rand(100-$ex_dmg_fluc[$ex],100+$ex_dmg_fluc[$ex])/100);

			//计算属性伤害是否被防御
			$log.=$exdmgname[$ex];
			if($pd['ex_def_flag'] == 1 || in_array($ex,$pd['ex_def_flag']))
			{
				$ex_dmg = round($ex_dmg*0.5);
				$log .="被防御效果抵消了！仅";
			}
			else 
			{
				//计算是否施加属性异常
				if (strpos($pd['inf'],$ex_inf[$ex])===false) 
				{
					$dice = diceroll(99);
					//获取属性施加异常的基础概率 + 熟练度修正
					$e_htr = $ex_inf_r[$ex] + $pa['wep_skill']*$ex_skill_inf_r[$ex];
					//获取属性施加异常率的基础上限
					$e_htr = min($e_htr,$ex_max_inf_r[$ex]);
					//获取属性施加异常概率的社团修正
					if(isset($ex_good_club[$ex]) && $ex_good_club[$ex] == $pd['club']) $e_htr += 20;
					//施加异常
					if ($dice < $e_htr) 
					{
						$dmginf = $ex_inf[$ex];
						$pd['inf'] .= $dmginf;
						addnews($now,'inf',$pa['name'],$pd['name'],$dmginf);
					}
				}
			}
			$log .= "造成了<span class=\"red\">{$ex_dmg}</span>点伤害！";
			if(!empty($dmginf)) $log .= "并造成{$pd['name']}{$exdmginf[$dmginf]}了！";
			$log .= "<br>";
			$total_ex_dmg[] = $ex_dmg;
		}
		return $total_ex_dmg;
	}

	//计算最终伤害的系数变化
	function get_final_dmg_p(&$pa,&$pd,$active)
	{
		global $log;
		$fin_dmg_p = 1;

		# 晶莹判定:
		if($pa['club'] == 19 || $pd['club'] == 19)
		{
			$p = rev_get_clubskill_bonus_dmg_rate($pa['club'],$pa['skills'],$pa,$pd['club'],$pd['skills'],$pd);
			if($p != 100)
			{
				$log.="<span class=\"yellow\">在「晶莹」的作用下，{$pa['nm']}造成的最终伤害变化至".$p."%！</span><br>";
				$fin_dmg_p[] = round($p/100);
			}
		}

		return $fin_dmg_p;
	}

	//计算最终伤害的定值变化
	function get_final_dmg_fix(&$pa,&$pd,$active,$fin_dmg)
	{
		global $log;

		# 伤害制御判定：
		if(in_array('h',$pd['ex_keys']) && $fin_dmg>=1950)
		{
			$dice = diceroll(99);
			if ($dice < 90) 
			{
				$fin_dmg = 1950 + $dice;
				$log .= "在{$pd['nm']}的装备的作用下，攻击伤害被限制了！<br>";
				
			}
			else
			{
				$log .= "{$pd['nm']}的装备没能发挥限制攻击伤害的效果！<br>";
			}
		}

		#剔透判定：
		if($pa['club'] == 19)
		{
			$rp_dmg = rev_get_clubskill_bonus_dmg_val($pa['club'],$pa['skills'],$pa,$pd);
			if($rp_dmg > 0)
			{
				$fin_dmg += $rp_dmg;
				$log .= "<span class=\"yellow\">在「剔透」的作用下，敌人受到了<span class=\"red\">$rp_dmg</span>点额外伤害。</span><br>";
			}
		}

		return $fin_dmg;
	}

	//攻击方(pa)在造成伤害后触发的事件
	function attack_finish_events($pa,$pd,$active)
	{
		global $log;

		#计算反噬伤害：
		if ($pa['final_damage'] >= 1000) 
		{
			if ($pa['final_damage'] < 2000) {
				$hp_d = floor($pa['hp']/2);
			} elseif ($dmg < 5000) {
				$hp_d = floor($pa['hp']*2/3);
			} else {
				$hp_d = floor($pa['hp']*4/5);
			}
			if (in_array('H',$pa['ex_keys'])) {
				$hp_d = floor ( $hp_d / 10 );
			}
			if($hp_d > 0)
			{
				$log .= "惨无人道的攻击对{$pa['nm']}自身造成了<span class=\"red\">$hp_d</span>点<span class=\"red\">反噬伤害！</span><br>";
				$pa['hp'] -= $hp_d;
			}
		}
		return;
	}

	//防守方(pd)在受到伤害后触发的事件
	function get_hurt_events(&$pa,&$pd,$active) 
	{
		global $log,$infatt_rev,$infinfo;

		# 真蓝凝防守事件：
		if($pd['type'] == 19 && $pd['name'] == '蓝凝')
		{
			attr_extra_19_azure($pa,$pd,$active);
		}
		
		# pa致伤次数＞0时，计算pd防具受损或致伤情况
		if($pa['inf_times']>0)
		{
			//获取可致伤部位
			$inf_parts = $infatt_rev[$pa['wep_kind']];
			$inf_att = Array();
			for($i=0;$i<$pa['inf_times'];$i++)
			{
				//随机选择一个可致伤的部位
				$aim = rand(0,count($inf_parts)-1);
				$inf_aim = $inf_parts[$aim];
				//对应部位致伤次数+1
				$inf_att[$inf_aim] += 1;
			}
			//应用防具损伤/致伤效果
			foreach($inf_att as $ipt => $times)
			{
				$which = 'ar'.$ipt;
				if($pd[$which.'s'] > 0)
				{
					armor_hurt($pd,$which,$times);
				}
				else 
				{
					$flag = get_inf_rev($pd,$inf_att);
					if($flag) $log .= "{$pd['nm']}的<span class=\"red\">$infinfo[$inf_att]</span>部受伤了！<br>";
				}
			}
		}
		return;
	}

	//战斗后结算rp事件
	function get_killer_rp(&$pa,&$pd,$active)
	{
		//杀人rp结算
		$rpup = $pd['type'] ? 20 : max(80,$pd['rp']);		
		//晶莹剔透修正
		if($pa['club'] == 19)
		{
			$rpdec = 30;
			$rpdec += get_clubskill_rp_dec($pa['club'],$pa['skills']);
			$pa['rp'] += round($rpup*(100-$rpdec)/100);
		}		
		else
		{
			$pa['rp'] += $rpup;
		}
		return;
	}

	//受到致伤或异常
	function get_inf_rev(&$pa,$infnm,$type=0)
	{
		global $log;

		if(strpos($pa['inf'],$infnm) === false)
		{
			$pa['inf'] .= $infnm;
			$pa['combat_inf'] .= $infnm;		
			return 1;	
		}
		return 0;
	}

?>