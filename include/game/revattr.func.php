<?php
namespace revattr
{

	if(!defined('IN_GAME')) {
		exit('Access Denied');
	}

	include_once GAME_ROOT.'./include/game/itemmain.func.php';
	include_once GAME_ROOT.'./include/game/revattr.calc.php';
	include_once GAME_ROOT.'./include/game/revattr_extra.func.php';

	# 获取真实攻击类别
	function get_wep_kind(&$pa,$wep_kind='',$pd_range=NULL)
	{
		global $nosta,$attinfo;

		# 刷新空弹药判定
		if(isset($pa['is_wpg'])) unset($pa['is_wpg']);

		# 检查是否为双系武器
		$w1 = substr($pa['wepk'],1,1);
		$w2 = substr($pa['wepk'],2,1);
		$w2 = !empty($w2) && isset($attinfo[$w2]) ? $w2 : '';

		# 输入了预设的攻击方式，检查是否合法
		if(!empty($wep_kind)) 
		{
			if(strpos($pa['wepk'],$wep_kind)!==false) $pa['wep_kind'] = $wep_kind;
			else $pa['wep_kind'] = $wep_kind == $w2 ? $w2 : $w1;
		}
		# 没有输入预设攻击方式，自动选择
		else 
		{
			if(empty($w2))
			{
				$pa['wep_kind'] = $w1;
			}
			else 
			{
				# 射系武器没有子弹的情况下，自动选用第二攻击模式
				if(($w1 == 'G' || $w1 == 'J') && $pa['weps'] == $nosta)
				{
					$pa['wep_kind'] = $w2;
				}
				elseif(($w2 == 'G' || $w2 == 'J') && $pa['weps'] == $nosta) 
				{
					$pa['wep_kind'] = $w1;
				}
				# 检查策略模式：射程优先 & 熟练优先
				else
				{				
					$pa['wep_kind'] = $w1; $w1_skill = get_wep_skill($pa); $w1_range = get_wep_range($pa);
					$pa['wep_kind'] = $w2; $w2_skill = get_wep_skill($pa); $w2_range = get_wep_range($pa);
					# 传入pd射程，且当前两种攻击方式在射程判定上没有区别，则熟练优先
					if(isset($pd_range) && (($w1_range > $pd_range && $w2_range > $pd_range) || ($w1_range < $pd_range && $w2_range < $pd_range)))
					{
						$pa['wep_kind'] = $w1_skill > $w2_skill ? $w1 : $w2;
					}
					else 
					{
						# 两把武器熟练都大于250的情况下，射程优先
						if($w1_skill > 250 && $w2_skill > 250) $pa['wep_kind'] = $w1_range > $w2_range ? $w1 : $w2;
						# 否则熟练优先
						else $pa['wep_kind'] = $w1_skill > $w2_skill ? $w1 : $w2;
					}
				}
			}
		}
		# 这里是最终判断是否为枪托打人的环节
		if(($pa['wep_kind'] == 'G' || $pa['wep_kind'] == 'J') && ($pa['weps'] == $nosta))
		{
			$pa['wep_kind'] = 'P';
			$pa['is_wpg'] = 1;
		}
		return $pa['wep_kind']; //保险起见……
	}

	# 获取武器射程
	function get_wep_range(&$pa)
	{
		global $rangeinfo;

		if(empty($pa['wep_kind'])) get_wep_kind($pa);

		$range = isset($rangeinfo[$pa['wep_kind']]) ? $rangeinfo[$pa['wep_kind']] : NULL;

		# 获取社团技能对武器射程的修正
		$range = get_clbskill_wep_range($pa,$range);

		return $range;
	}

	# 获取武器对应熟练度
	function get_wep_skill(&$pa)
	{
		global $skillinfo,$log;

		if(empty($pa['wep_kind'])) get_wep_kind($pa);

		# 天赋异禀在计算熟练时附加25%别系熟练
		if ($pa['club'] == 10)
		{
			$wep_skill = round($pa[$skillinfo[$pa['wep_kind']]]+($pa['wp']+$pa['wk']+$pa['wc']+$pa['wg']+$pa['wd']+$pa['wf'])*0.25);
		}
		# 「人杰」技能判定
		elseif(isset($pa['skill_renjie']))
		{
			foreach(Array('wp','wk','wc','wg','wd','wf') as $skw) $wep_skill = max($pa[$skw],$wep_skill);
		}
		else
		{
			$wep_skill = $pa[$skillinfo[$pa['wep_kind']]];
		}

		# 获取社团技能对武器熟练度的修正
		$wep_skill = get_clbskill_wep_skill($pa,$wep_skill);
		return $wep_skill;
	}

	# 初始化进攻方(pa)的主动技能参数
	function get_attr_bskills(&$pa,&$pd,$active)
	{
		global $log,$cskills,$now;

		# 检查先攻者是否有要发动的主动技能：
		# 1.玩家：须自主发动
		# 2.NPC：如满足条件则随机发动
		if($pa['type'] && !empty($pa['clbpara']['skill']))
		{
			# 先攻者是NPC时，打乱并遍历pa技能队列 寻找可用战斗技
			if($pa['type'])
			{
				$npc_skill = $pa['clbpara']['skill'];
				shuffle($npc_skill);
				foreach($npc_skill as $sk)
				{
					if(get_skilltags($sk,'battle') && !check_skill_unlock($sk,$pa) && !check_skill_cost($sk,$pa))
					{
						$pa['bskill'] = $sk;
						break;
					}
				}
			}
		}
		if(!empty($pa['bskill']))
		{
			# 检查战斗技是否可用，可用则执行流程
			if(!check_skill_unlock($pa['bskill'],$pa) && !check_skill_cost($pa['bskill'],$pa))
			{
				$bsk = $pa['bskill'];
				$bsk_name = $cskills[$bsk]['name'];
				# 扣除怒气
				$bsk_cost = get_skillvars($bsk,'ragecost');
				if($bsk_cost) $pa['rage'] -= $bsk_cost;
				# 成功释放主动技，应用标记
				$pa['bskill_'.$bsk] = 1;
				$pa['bskilllog'] = "<span class=\"lime\">{$pa['nm']}对{$pd['nm']}发动了技能「{$bsk_name}」！</span><br>";
				# 限次技每次使用时次数+1
				if(get_skilltags($bsk,'limit'))
				{
					set_skillpara($bsk,'active_t',get_skillpara($bsk,'active_t',$pa['clbpara'])+1,$pa['clbpara']);
				}
				# 重新判定双系武器的攻击方式
				if(isset($cskills[$bsk]['wepk']) && !in_array($pa['wep_kind'],$cskills[$bsk]['wepk']))
				{
					get_wep_kind($pa,$cskills[$bsk]['wepk'][0]);
				}
				# 检查是否需要addnews
				addnews($now,'bsk_'.$bsk,$pa['name'],$pd['name']);
				# 检查是否需要进行logsave
				if(!$pd['type'] && $pd['nm']!='你') $pd['logsave'] .= "<span class=\"yellow\">{$pa['name']}</span>对你发动了技能<span class=\"red\">「{$bsk_name}」</span>！";
				elseif(!$pa['type'] && $pa['nm']!='你') $pa['logsave'] .= "你对<span class=\"yellow\">{$pd['name']}</span>发动了技能<span class=\"red\">「{$bsk_name}」</span>！";
			}
			else 
			{
				# 主动技不满足使用条件或来源非法，直接注销标记
				unset($pa['bskill']);
			}
		}
		return;
	}

	# 初始化指定pa的被动技能参数，这里通常只判定技能是否生效，如生效，则提供一个标记
	function get_attr_passive_skills(&$pa,&$pd,$active)
	{
		global $cskills,$log;

		if(!empty($pa['clbpara']['skill']))
		{
			$pa['skilllog'] = '';
			# 遍历pa技能队列 检查是否解锁
			foreach($pa['clbpara']['skill'] as $sk)
			{
				# passive、buff、inf标签技能通用判定
				# 对于解锁技能，如果有特殊触发条件，在这里加入判定，否则会默认给一个触发标记
				if((get_skilltags($sk,'passive') || get_skilltags($sk,'buff') || get_skilltags($sk,'inf')) && !check_skill_unlock($sk,$pa))
				{
					# 「猛击」特殊判定
					if($sk == 'c1_crit')
					{
						$sk_dice = diceroll(99);
						# 「偷袭」或「闷棍」技能生效时，「猛击」必定触发；
						$sk_obbs = isset($pa['bskill_c1_stalk'])||isset($pa['bskill_c1_bjack']) ? 100 : get_skillvars('c1_crit','rate');
						# 成功触发时
						if($sk_dice < $sk_obbs) $pa['skill_c1_crit'] = 1;
					}
					# 「枭眼」特殊判定：射程不小于对方时激活效果
					elseif($sk == 'c3_hawkeye' && $pa['wep_range'] >= $pd['wep_range'])
					{
						$pa['skill_c3_hawkeye'] = 1;
					}
					# 「护盾」特殊判定：生命值不小于指定百分比时激活一个护盾
					elseif($sk == 'c7_shield')
					{
						$sk_lvl = get_skilllvl('c7_shield',$pa);
						$hpalert = get_skillvars('c7_shield','hpalert',$sk_lvl);
						if($pa['hp'] <= $pa['mhp']*($hpalert/100))
						{
							getclubskill('buff_shield',$pa['clbpara']);
							set_skillpara('buff_shield','svar',get_skillvars('c7_shield','svar',$sk_lvl),$data['clbpara']);
							$pa['skill_buff_shield'] = 1;
							//$pa['skilllog'] .= "<span class='lime'>感知到危险，闪烁着淡蓝幽光的护盾自动出现在{$pa['nm']}身旁！<br></span>";
							$log .= "<span class='lime'>感知到危险，闪烁着淡蓝幽光的护盾自动出现在{$pa['nm']}身旁！<br></span>";
						}
					}
					# 「暗杀」特殊判定：输出一段破隐log，并丢失buff技能
					elseif($sk == 'buff_assassin')
					{
						$pa['skill_buff_assassin'] = 1;
						$pa['skilllog'] .= "<span class='yellow'>{$pa['nm']}从阴影中现出身形，打了{$pd['nm']}一个措手不及！</span><br>";
						lostclubskill('buff_assassin',$pa['clbpara']);
					}
					# 「洞察」特殊判定：熟练度高于对方时触发
					elseif($sk == 'c10_insight' && $pa['wep_skill'] > $pd['wep_skill'])
					{
						$pa['skill_c10_insight'] = 1;
						$pa['skilllog'] .= "<span class='yellow'>{$pa['nm']}凭借丰富的经验看穿了{$pd['nm']}的破绽！</span><br>";
					}
					# 「海虎」特殊判定：
					elseif($sk == 'c12_swell')
					{
						$sk_var = get_skillvars('c12_swell','swellr') * calc_enmity_losshpr($pa,$pd);
						$dice = diceroll(99);
						if($dice < $sk_var)
						{
							$pa['skill_c12_swell'] = $pa['hp'] <= $pa['mhp']*0.3 ? 2 : 1;
						}
					}
					# 其他非特判技能，默认给一个触发标记
					else 
					{
						$pa['skill_'.$sk] = 1;
						//$pa['skill_'.$sk.'_log'] = "";
					}
				}
				# switch标签技能通用判定：active为1时才算激活
				if(get_skilltags($sk,'switch') && !empty(get_skillpara($sk,'active',$pa['clbpara'])) && !check_skill_unlock($sk,$pa))
				{
					$pa['askill_'.$sk] = 1;
				}
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

	//获取额外属性，该属性不受三抽影响
	function get_extra_ex_array(&$pa)
	{
		global $log,$itemspkinfo;
		# 「百战」效果判定：
		if(isset($pa['skill_c1_veteran']))
		{
			$sk_def = get_skillpara('c1_veteran','choice',$pa['clbpara']);
			if($sk_def)
			{
				global $itemspkinfo;
				$pa['ex_keys'][] = $sk_def;
				//$log .= "百战使{$pa['nm']}拥有了【{$itemspkinfo[$sk_def]}】属性！<br>";
			}
		}
		# 「穿杨」效果判定：
		if(isset($pa['bskill_c4_sniper']) && in_array('r',$pa['ex_keys']))
		{
			$key = array_search('r',$pa['ex_keys']);
			unset($pa['ex_keys'][$key]);
		}
		# 「天义」效果判定：
		if(isset($pa['skill_c6_justice']) && (empty($pa['ex_keys']) || !in_array('N',$pa['ex_keys']))) $pa['ex_keys'][] = 'N';
		return;
	}

	//攻击方(pa)在命中流程前触发的事件（直死、DOT结算、踩陷阱……） 返回值小于0：中止打击流程
	function hitrate_prepare_events(&$pa,&$pd,$active)
	{
		global $log,$attinfo;

		# 玩家直死反噬：这是一个暴毙死法，触发时将中止后续战斗动作。
		if(in_array('X',$pa['ex_keys']) && !$pa['type'])
		{
			$xdice = diceroll(99);
			if($xdice <= 14)
			{
				$log .= "<span class=\"red\">{$pa['nm']}手中的武器忽然失去了控制，喀吧一声就斩断了什么！那似乎是{$pa['nm']}的死线……</span><br>";
				$pa['gg_flag'] = 39;  #这个标记用于登记暴毙死法 39-武器反噬
				$pa['hp'] = 0;
				return -1;
			}
		}

		# 真红暮进攻事件：
		# 注意：真红暮的进攻事件虽然有让敌人扣血致死的可能，但是不应该返回-1，因为死的不是自己。在这个函数里，只有攻击方在造成伤害前暴毙才需要返回-1。
		# 如果想为真红暮的特殊攻击提供指定死法，请在判定中添加：$pd['gg_flag'] = '死法编号';
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

		# 书中虫受伤时rp上升事件：
		if($pd['type'] == 89 && ($pd['name'] == '高中生·白神' || $pd['name'] == '白神·讨价还价' || $pd['name'] == '白神·接受'))
		{
			attr_extra_89_bookworm($pa,$pd,$active,'rp');
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
					//从原属性数组中剔除当前武器属性 
					if(!empty($pa['wepsk'])) unset_ex_from_array($pa,get_itmsk_array($pa['wepsk']));
					$pa['wep'] = $pd['wep']; $pa['wepk'] = $pd['wepk']; $pa['wepsk'] = $pd['wepsk'];
					$pa['wepe'] = $pd['wepe']; $pa['weps'] = $pd['weps']; 
					//没有灵抽的情况下，向属性数组中打入复制后武器的属性
					if(!isset($pa['sldr_flag'])) $pa['ex_keys'] = array_merge($pa['ex_keys'],get_itmsk_array($pa['wepsk']));
					get_wep_kind($pa);
					$log .= "{$pa['nm']}使用{$pa['wep']}<span class=\"yellow\">{$attinfo[$pa['wep_kind']]}</span>{$pd['nm']}！<br>";
				}
			}
			else
			{
				$log .= "<span class=\"red\">但是似乎失败了！</span><br>";	
			}
		}
		
		# 存在其他方式提供的暴毙死法，也中止后续战斗动作。
		if(isset($pa['gg_flag']))
		{
			return -1;
		}
		
		return 0;
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
		//获取社团技能对基础命中率的修正（旧）
		//$hitrate *= rev_get_clubskill_bonus_hitrate($pa['club'],$pa['skills'],$pa,$pd['club'],$pd['skills'],$pd);
		//获取社团技能对基础命中率的修正（新）
		$hitrate = get_clbskill_hitrate($pa,$pd,$active,$hitrate);
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

		//获取基础连击命中率衰减系数：注意，这个值越高衰减越慢
		$hitratebonus = 0.8;
		//获取社团技能对连击命中率衰减系数的修正（旧）
		//$hitratebonus *= rev_get_clubskill_bonus_hitrate($pa['club'],$pa['skills'],$pa,$pd['club'],$pd['skills'],$pd);
		//获取社团技能对连击命中率衰减系数的修正（新）
		$hitratebonus = get_clbskill_r_hitrate($pa,$pd,$active,$hitratebonus);

		//获取基础致伤率（防具耐久损伤率）系数
		$inf_r = $infobbs[$pa['wep_kind']];
		//获取社团技能对致伤率（防具耐久损伤率）的修正（旧）
		//$inf_r *= rev_get_clubskill_bonus_imfrate($pa['club'],$pa['skills'],$pa,$pd['club'],$pd['skills'],$pd);
		//获取社团技能对致伤率（防具耐久损伤率）的修正（新）
		$inf_r = get_clbskill_infrate($pa,$pd,$active,$inf_r);
		//获取基础致伤效果（每次致伤会损耗多少点防具耐久）
		$inf_points = 1;
		//$inf_points = rev_get_clubskill_bonus_imftime($pa['club'],$pa['skills'],$pa,$pd['club'],$pd['skills'],$pd);
		//获取社团技能对基础致伤效果（每次致伤会损耗多少点防具耐久）的修正（新）
		$inf_points = get_clbskill_inftimes($pa,$pd,$active,$inf_points);
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
			//社团技能对武器损伤系数的修正（旧）
			//$wep_imp_obbs *= rev_get_clubskill_bonus_imprate($pa['club'],$pa['skills'],$pa,$pd['club'],$pd['skills'],$pd);
			//社团技能对武器损伤系数的修正（新）
			# 「解牛」技能效果：
			if(isset($pa['bskill_c2_butcher']))
			{
				$wep_imp_obbs *= get_skillvars('c2_butcher','wepimpr');
			}
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
		$pa['hitrate_times'] = $pa['inf_times'] = $pa['wep_imp_times'] = 0;
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
		global $log,$nosta;

		# 黑熊吃香蕉事件：
		if($pa['type'] && in_array('X',$pa['ex_keys']))
		{
			$x_dice = diceroll(99);
			if($x_dice >= 90)
			{
				if ($pa['wep'] == '燕返262') $log.="<img src=\"img/other/262.png\"><br>";
				$damage = 999983;
				$pd['sp_death_flag'] = 1; #这个标记用于影响是否复活或登记特殊死法的判断
				$log .= "造成<span class=\"red\">$damage</span>点伤害！<br>";
				return $damage;
			}
		}

		# 真红暮防御事件：
		if($pd['type'] == 19 && $pd['name'] == '红暮')
		{	
			$p = attr_extra_19_crimson($pa,$pd,$active,'defend');
			if(isset($p)) return $p;
		}

		# 「天佑」技能判定
		if(isset($pd['skill_buff_godbless']))
		{
			$no_type = get_skillvars('buff_godbless','no_type');
			if(in_array($pa['type'],$no_type))
			{
				$log .= "<span class=\"yellow\">{$pa['nm']}的攻击不受「天佑」影响！</span><br>";
			}
			else 
			{
				$log .= "<span class=\"yellow\">「天佑」使{$pa['nm']}的攻击没能造成任何伤害！</span><br>";
				return 0;
			}
		}

		# 数据护盾：这个有意思
		if($pd['artk'] == "AA")
		{
			if($pd['type'])
			{
				if($pd['arte'] < 100)
				{
					$pd['arte'] = min(100,$pd['arte']+$pd['arts']);
					$log .= "<span class=\"red\">{$pd['nm']}身上的数据护盾投射出了防护罩，轻松挡下了{$pa['nm']}的攻击！</span><br>";
					return 0;
				}
			}
			else 
			{
				if($pd['arte'] > 1)
				{
					$pd['arte'] = max(1,$pd['arte']-$pd['arts']);
					$log .= "<span class=\"red\">{$pd['nm']}身上的数据护盾投射出了防护罩，轻松挡下了{$pa['nm']}的攻击！</span><br>";
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
			else $log .= "<span class=\"lime\">然而{$pd['nm']}的防御力实在太高，{$pa['nm']}根本无法对其造成有效伤害！</span><br>";

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
			# 混沌武器伤害最大值：
			$maxdmg = $pd['mhp'] > $pa['wepe'] ? $pa['wepe'] : $pd['mhp'];
			# 混沌武器耐久损耗：
			$max_imp_times = $pa['weps'] == $nosta ? $pa['wepe'] : $pa['weps'];
			$min_imp_times = $pa['weps'] == $nosta ? -$pa['wepe'] : -$pa['weps'];
			# 奇迹属性可以将混沌伤害升格：伤害下限提升、耐久不再损耗
			if(in_array('x',$pa['ex_keys']))
			{
				$damage = rand($maxdmg*0.1,$maxdmg);
			}
			else
			{
				$mindmg = max(1,($pd['mhp'] - $pd['hp'])/2);
				do{
					$damage = rand(-1*$mindmg,$maxdmg);
				}while(empty($damage));
				if(rand($min_imp_times,$max_imp_times) < 0)
				{
					do{
						$pa['wep_imp_times'] = generate_ndnumbers($min_imp_times,$max_imp_times)[0];
					}while(empty($pa['wep_imp_times']));
				}
			}
			# 结算
			if($damage > 0) $log .= "武器随机造成了<span class=\"red\">$damage</span>点伤害！<br>";
			else $log .= "武器随机为{$pd['nm']}回复了<span class=\"lime\">".abs($damage)."</span>点生命！<br>";
			# 混沌伤害打满时 保存至成就
			if($damage == $maxdmg) $pa['clbpara']['achvars']['full_chaosdmg'] = 1;
			return $damage;
		}
		return NULL;
	}

	//获取pa的攻击力
	function get_base_att(&$pa,&$pd,$active,$tooltip=0)
	{
		if(!isset($pa['wep_kind'])) get_wep_kind($pa);

		# 计算武器面板攻击：
		//空手 武器伤害=2/3熟练度
		if($pa['wep_kind'] == 'N') 
		{
			if(!isset($pa['wep_skill'])) $pa['wep_skill'] = get_wep_skill($pa);
			$pa['wepe_t'] = round($pa['wep_skill']*2/3);	
		} 
		//射系 武器伤害=面板数值
		elseif($pa['wep_kind'] == 'G' || $pa['wep_kind'] == 'J') 
		{
			$pa['wepe_t'] = $pa['wepe'];
		}
		//枪托打人 武器伤害=面板数值/5
		elseif(isset($pa['is_wpg'])) 
		{
			$pa['wepe_t'] = round ($pa['wepe']/ 5 );
		}
		//其他武器 武器伤害=面板数值*2
		else
		{
			$pa['wepe_t'] = $pa['wepe'] * 2;
		}

		# 汇总：：
		$base_att = $pa['att'] + $pa['wepe_t'];

		# 初始化tooltip
		if($tooltip)
		{
			$tooltip = "<span tooltip=\" 基础攻击值：{$pa['att']}+{$pa['wepe_t']}";
			if(!empty($att1)) $tooltip .="+{$att1}";
			$tooltip .= "\r";
		}
		# 计算攻击力修正
		$base_att = get_base_att_modifier($pa,$pd,$active,$base_att,$tooltip);
		return $base_att;
	}
	
	//获取pa的攻击力修正
	function get_base_att_modifier(&$pa,&$pd,$active,$base_att,$tooltip=0)
	{
		global $weather,$log,$gamecfg;
		include config('combatcfg',$gamecfg);
		# 计算天气、姿态、策略、地点对pa攻击力的修正
		$base_atk_per = 100;
		//天气修正
		$wth_atk_per = isset($weather_attack_modifier[$weather]) ? $weather_attack_modifier[$weather] : 0 ;
		//地点修正
		$pls_atk_per = isset($pls_attack_modifier[$pa['pls']]) ? $pls_attack_modifier[$pa['pls']] : 0;
		//姿态修正只在先制攻击阶段生效？ //pa身上没有反击标记 代表这是一次先制攻击
		if(!isset($pa['is_counter']) && $pose_attack_active) $pose_atk_per = isset($pose_attack_modifier[$pa['pose']]) ? $pose_attack_modifier[$pa['pose']] : 0 ;
		//姿态修正始终生效
		elseif(!$pose_attack_active) $pose_atk_per = isset($pose_attack_modifier[$pa['pose']]) ? $pose_attack_modifier[$pa['pose']] : 0 ;
		//策略修正只在反击阶段生效？ //pa身上没有反击标记 代表这是一次先制攻击
		if(!empty($pa['is_counter']) && $tactic_attack_active) $tac_atk_per = isset($tactic_attack_modifier[$pa['tactic']]) ? $tactic_attack_modifier[$pa['tactic']] : 0;
		//策略修正始终生效
		elseif(!$tactic_attack_active) $tac_atk_per = isset($tactic_attack_modifier[$pa['tactic']]) ? $tactic_attack_modifier[$pa['tactic']] : 0;
		//上述系数修正最低不低于1%
		$base_atk_per += $wth_atk_per+$pls_atk_per+$pose_atk_per+$tac_atk_per;
		$base_atk_per = $base_atk_per > 0 ? $base_atk_per : 1;

		# 计算受伤状态对pa攻击力的修正
		$inf_atk_per = 100;
		if(!empty($pa['inf']))
		{
			global $inf_att_p;
			foreach ($inf_att_p as $inf_ky => $value) 
			{
				if(strpos($pa['inf'], $inf_ky)!==false) $inf_atk_per *= $value;
			}	
		}

		# 计算社团技能对pa攻击力的修正（旧）
		/*$club_atk_per = 100;
		if(!empty($pa['club']) || !empty($pa['skills']))
		{
			rev_get_clubskill_bonus_p($pa['club'],$pa['skills'],$pa,$pd['club'],$pa['skills'],$pd,$attfac,$deffac);
			$club_atk_per *= $attfac;
		}*/

		# 汇总
		$base_att = round($base_att*($base_atk_per/100)*($inf_atk_per/100));
		$base_att = max(1,$base_att);

		if($tooltip)
		{
			$tooltip .= "天气修正：{$wth_atk_per}%\r 地点修正：{$pls_atk_per}%\r 姿态修正：{$pose_atk_per}%\r 策略修正：{$tac_atk_per}%";
			if($inf_atk_per <> 100) $tooltip .=" \r 异常状态修正：{$inf_atk_per}%";
			//if($club_atk_per <> 100) $tooltip .=" \r 称号技能修正：{$club_atk_per}%";
			$tooltip .="\">".$base_att."</span>";
			return $tooltip;
		}
		else 
		{
			return $base_att;
		}
	}

	//获取pd的防御力与修正
	function get_base_def(&$pa,&$pd,$active,$tooltip=0)
	{
		global $specialrate,$log,$cskills;
		if(!isset($pd['wep_kind'])) get_wep_kind($pd);
		# pd基础防御力：
		$base_def = $pd['def'];
		# pd装备提供防御力：
		$equip_def = $pd['arbe']+$pd['arhe']+$pd['arae']+$pd['arfe'];
		# 是否受pa冲击效果影响：
		if(isset($pa['ex_keys']) && in_array('N',$pa['ex_keys']))
		{
			$Ndice = diceroll(99);
			if($Ndice < $specialrate['N'])
			{
				$equip_def =  round($equip_def/2);
				//为了美观考虑……冲击的log在之后的deal_damage_prepare_events()显示
				$pa['charge_flag'] = 1;
			}
		}
		# 获取pd社团技能对防御力的加成（旧）
		/*if(!empty($pd['skills']))
		{
			rev_get_clubskill_bonus($pa['club'],$pa['skills'],$pa,$pd['club'],$pa['skills'],$pd,$att1,$def1);
		}*/
		# 获取pd社团技能对防御力的加成（新）
		# 「格挡」技能加成
		if(!check_skill_unlock('c1_def',$pd))
		{
			$def_trans_rate = $cskills['c1_def']['vars']['trans'];
			$def_maxtrans = $cskills['c1_def']['vars']['maxtrans'];
			$sk_def = min($def_maxtrans, $def_trans_rate * $pd['wepe'] / 100);
		}
		# 汇总：
		$total_def = $base_def+$equip_def;
		if(!empty($def1)) $total_def += $def1;
		if(!empty($sk_def)) $total_def += $sk_def;

		# 初始化tooltip
		if($tooltip)
		{
			$tooltip = "<span tooltip=\" 基础防御值：{$base_def}+{$equip_def}";
			if(!empty($def1)) $tooltip .="+{$def1}";
			if(!empty($sk_def)) $tooltip .="+{$sk_def}(技能加成)";
			$tooltip .= "\r";
		}
		# 计算防御力修正
		$total_def = get_base_def_modifier($pa,$pd,$active,$total_def,$tooltip);
		return $total_def;
	}

	//获取pd的防御力修正
	function get_base_def_modifier(&$pa,&$pd,$active,$total_def,$tooltip=0)
	{
		global $weather,$log,$gamecfg;
		include config('combatcfg',$gamecfg);
		# 计算天气、姿态、策略、地点对pd防御力的修正
		$base_def_per = 100;
		//天气修正
		$wth_def_per = isset($weather_defend_modifier[$weather]) ? $weather_defend_modifier[$weather] : 0 ;
		//地点修正		
		$pls_def_per = isset($pls_defend_modifier[$pd['pls']]) ? $pls_defend_modifier[$pd['pls']] : 0; 
		//姿态修正只在受到先制攻击时生效？ //pa身上没有反击标记 代表这是一次先制攻击
		if(!isset($pa['is_counter']) && $pose_defend_active) $pose_def_per = isset($pose_defend_modifier[$pd['pose']]) ? $pose_defend_modifier[$pd['pose']] : 0 ;
		//姿态修正始终生效
		elseif(!$pose_defend_active) $pose_def_per = isset($pose_defend_modifier[$pd['pose']]) ? $pose_defend_modifier[$pd['pose']] : 0 ;
		//策略修正只在反击阶段生效？ //pa身上有反击标记 代表这是一次反击攻击
		if(!empty($pa['is_counter']) && $tactic_defend_active) $tac_def_per = isset($tactic_defend_modifier[$pd['tactic']]) ? $tactic_defend_modifier[$pd['tactic']] : 0;
		//策略修正始终生效
		elseif(!$tactic_defend_active) $tac_def_per = isset($tactic_defend_modifier[$pd['tactic']]) ? $tactic_defend_modifier[$pd['tactic']] : 0;
		//上述各项系数修正最低不低于1%
		$base_def_per += $wth_def_per+$pls_def_per+$pose_def_per+$tac_def_per;
		$base_def_per = $base_def_per > 0 ? $base_def_per : 1;
		
		# 计算受伤状态对pd防御力的修正
		$inf_def_per = 100;
		if(!empty($pd['inf']))
		{
			global $inf_def_p;
			foreach($inf_def_p as $inf_ky => $value) 
			{
				if(strpos($pd['inf'], $inf_ky)!==false) $inf_def_per *= $value;
			}
		}
		
		# 计算社团技能对pd防御力的修正（新）	
		#「根性」技能加成
		if(!check_skill_unlock('c12_garrison',$pd))
		{
			$sk_lvl = get_skilllvl('c12_garrison',$pd);
			$sk_var = 100+round(get_skillvars('c12_garrison','defgain',$sk_lvl) * calc_garrison_losshpr($pa,$pd));
		}
		# 汇总
		$total_def = round($total_def*($base_def_per/100)*($inf_def_per/100));
		if(isset($sk_var)) $total_def = round($total_def*($sk_var/100));
		$total_def = max(0.01,$total_def);

		if($tooltip)
		{
			$tooltip .= "天气修正：{$wth_def_per}% \r 地点修正：{$pls_def_per}% \r 姿态修正：{$pose_def_per}% \r 策略修正：{$tac_def_per}%";
			if($inf_def_per <> 100) $tooltip .=" \r 异常状态修正：{$inf_def_per}%";
			//if($club_def_per <> 100) $tooltip .=" \r 称号技能修正：{$club_def_per}%";
			if(isset($sk_var)) $tooltip .=" \r 技能修正：".($sk_var-100)."%";
			$tooltip .="\">".$total_def."</span>";
			return $tooltip;
		}
		else
		{
			return $total_def;
		}
	}

	//计算原始伤害
	function get_original_dmg_rev(&$pa,&$pd,$active) 
	{
		global $skill_dmg, $dmg_fluc, $weather, $pls, $log;

		//$log.= "【DEBUG】原始伤害计算阶段：{$pa['name']}的攻击系数为{$pa['base_att']}，{$pd['name']}的防御系数为{$pd['base_def']}，";
		//原始伤害：(pa基础攻击/pd基础防御) * pa熟练度 * pa熟练度系数
		$damage = ($pa['base_att'] / $pd['base_def']) * $pa['wep_skill'] * $skill_dmg[$pa['wep_kind']];
		//获取伤害浮动系数：
		$dfluc = $dmg_fluc [$pa['wep_kind']];
		//获取社团技能对伤害浮动系数的修正：（旧）
		//$dfluc += rev_get_clubskill_bonus_fluc($pa['club'],$pa['skills'],$pa,$pd['club'],$pa['skills'],$pd);
		//获取社团技能对伤害浮动系数的修正（新）：
		$dfluc += get_clbskill_fluc($pa,$pd,$active);
		//计算具体伤害浮动：
		$dfluc = rand(-$dfluc,$dfluc);
		//「舞钢」效果判定：
		if(isset($pa['skill_c2_intuit']) && $dfluc < 0) $dfluc = abs($dfluc);
		//汇总
		$dmg_factor = (100 + $dfluc) / 100;
		//echo "浮动前 damage = {$damage}<br>";
		$damage = round ( $damage * $dmg_factor * rand ( 4, 10 ) / 10 );
		//echo "浮动后 damage = {$damage}<br>";
		//把计算得到的原始伤害保存在$pa['original_dmg']里
		$pa['original_dmg'] = $damage;
		return $damage;
	}

	//计算在原始伤害基础上附加的固定伤害
	function get_original_fix_dmg_rev(&$pa,&$pd,$active)
	{
		$damage = 0;
		# 重枪
		if ($pa['wep_kind'] == 'J') 
		{
			$adddamage=$pd['mhp']/3;
			if ($adddamage>20000) $adddamage=10000;
			$damage += round($pa['wepe']*2/3+$adddamage);
		}
		# 灵力武器
		if ($pa['wep_kind'] == 'F') 
		{
			global $log;
			if(isset($pa['sldr_flag']) || isset($pd['sldr_flag'])) 
			{
				$log.="<span class=\"red\">由于灵魂抽取的作用，灵系武器伤害大幅降低了！</span><br>";
			}
			else
			{
				$damage += $pa['wepe'];
			}
		}
		$pa['original_dmg'] += $damage;
		return $damage;
	}

	//计算伤害倍率变化（攻击方）
	function get_damage_p_rev(&$pa,&$pd,$active) 
	{
		global $log,$cskills;

		//每一条伤害倍率判定变化提示会以$dmg_p[]= $r;的形式放入伤害倍率数组内，
		//在输出成log时会显示为：总计造成了100x0.5x1.2...=111点伤害 的形式
		$dmg_p = Array();

		# 灵力武器伤害↔体力消耗系数判定：
		if($pa['wep_kind'] == 'F')
		{
			//玩家使用灵力武器才会计算体力消耗
			if(!$pa['type'])
			{
				//获取体力消耗系数：
				$sp_cost_r = $pa['club'] == 9 ? 0.2 : 0.25;
				//获取社团技能对体力消耗系数的修正：（旧）
				//$sp_cost_r *= get_clubskill_bonus_spd($pa['club'],$pa['skills']);
				//获取社团技能对体力消耗系数的修正：（新）
				#「灵力」效果判定：
				if(isset($pa['skill_c9_spirit']))
				{
					$sk_lvl = get_skilllvl('c9_spirit',$pa);
					$sk_r = 1 - (get_skillvars('c9_spirit','spcloss',$sk_lvl) / 100);
					$sp_cost_r *= $sk_r;
				}
				//获取理论消耗体力最大值：
				$sp_cost_max = $sp_cost_r*$pa['wepe'];
				//获取实际消耗体力：
				$sp_cost = min($sp_cost_max,$pa['sp']-1);
				$log_sp_cost = ceil($sp_cost);
				$log .= "消耗{$log_sp_cost}点体力，";
			}
			else 
			{
				$sp_cost = 0;
			}
			//获取威力系数：NPC固定为50%
			$factor = $pa['type'] ? 0.5 : 0.5+round(($sp_cost/$sp_cost_max)/2,1);
			//获取伤害变化倍率并扣除体力
			$dmg_p[]= round($factor,2); 
			if(isset($log_sp_cost)) $pa['sp'] -= $log_sp_cost;
			//输出log
			$f = round ( 100 * $factor );
			$log .= "发挥了灵力武器{$f}％的威力！<br>";
		}

		#「必杀」效果判定：（原喊话必杀技）
		if(isset($pa['bskill_c9_lb']))
		{
			//获取伤害变化倍率
			$sk_r = get_skillvars('c9_lb','phydmgr');
			$dmg_p[]= $sk_r; 
			//输出log
			if($pa['type']) $log .= npc_chat_rev ($pa,$pd,'critical');
			$log .= "<span class=\"red\">发动必杀技！</span><br>";
		}

		# 连击判定：
		# 只要命中次数上限大于1就进入连击判定，不需要再检查武器有没有连击属性。方便一些技能强制附加连击
		if($pa['hitrate_max_times'] > 1)
		{	
			//获取连击次数伤害倍率：2次2倍，3次2.8倍，之后=2.8+(次数-3)*0.6
			$r_dmg_p = Array(2=>2,3=>2.8);
			$p = isset($r_dmg_p[$pa['hitrate_times']]) ? $r_dmg_p[$pa['hitrate_times']] : 2.8 + ($pa['hitrate_times']-3)*0.6;
			$dmg_p[]= $p; 
			//输出log
			$log .= "{$pa['hitrate_max_times']}次连续攻击命中<span class=\"yellow\">{$pa['hitrate_times']}</span>次！";
		}

		#「猛击」判定：
		if(isset($pa['skill_c1_crit']))
		{
			// 获取猛击技能等级……整个函数吧
			$sk_lvl = get_skilllvl('c1_crit',$pa);
			// 获取猛击倍率
			$sk_p = 1 + (get_skillvars('c1_crit','attgain',$sk_lvl) / 100);
			$dmg_p[]= $sk_p; 
			//输出log
			$log .= "<span class=\"yellow\">{$pa['nm']}朝着{$pd['nm']}打出了凶猛的一击！<span class=\"clan\">{$pd['nm']}被打晕了过去！</span></span><br>";
		}
		#「潜能」判定：
		if(isset($pa['bskill_c3_potential']))
		{
			$sk_p = get_skillvars('c3_potential','phydmgr');
			$p = 1 + ($sk_p / 100);
			$dmg_p[]= $p; 
			$log.="<span class='yellow'>{$pa['nm']}爆发潜能打出了致命一击！</span><br>";
		}
		#「百出」判定：
		if(isset($pa['skill_c3_numerous']))
		{
			$sk_p = get_skillvars('c3_numerous','dmgr')*get_skillpara('c3_enchant','active_t',$pa['clbpara']);
			$p = 1 + ($sk_p / 100);
			$dmg_p[]= $p; 
			$log.="<span class='yellow'>{$pa['nm']}打得{$pd['nm']}落花流水，物理伤害增加了{$sk_p}%！</span><br>";
		}
		#「瞄准」判定：
		if(isset($pa['bskill_c4_aiming']))
		{
			$sk_p = get_skillvars('c4_aiming','phydmgr');
			$p = 1 + ($sk_p / 100);
			$dmg_p[]= $p; 
			$log.="<span class='yellow'>「瞄准」使{$pa['nm']}造成的物理伤害提高了{$sk_p}%！</span><br>";
		}
		#「咆哮」判定：
		if(isset($pa['bskill_c4_roar']))
		{
			$sk_p = get_skillvars('c4_roar','phydmgr');
			$p = 1 + ($sk_p / 100);
			$dmg_p[]= $p; 
			$log.="<span class='yellow'>「咆哮」使{$pa['nm']}造成的物理伤害提高了{$sk_p}%！</span><br>";
		}
		#「穿杨」判定：
		if(isset($pa['bskill_c4_sniper']))
		{
			$sk_p = get_skillvars('c4_sniper','phydmgr');
			$p = 1 + ($sk_p / 100);
			$dmg_p[]= $p; 
			$log.="<span class='yellow'>「穿杨」使{$pa['nm']}造成的物理伤害提高了{$sk_p}%！</span><br>";
		}
		#「解构」判定：
		if(isset($pa['bskill_c10_decons']))
		{
			$sk_p = get_skillvars('c10_decons','phydmgr');
			$p = 1 + ($sk_p / 100);
			$dmg_p[]= $p; 
			$log.="<span class='yellow'>「解构」使{$pa['nm']}造成的物理伤害提高了{$sk_p}%！</span><br>";
		}
		return $dmg_p;
	}

	//攻击方在造成伤害前触发的事件
	function deal_damage_prepare_events(&$pa,&$pd,$active)
	{
		global $log,$def_kind,$specialrate,$itemspkinfo;

		# 冲击效果log显示（实际的效果判断在get_base_def()阶段）
		if(!empty($pa['charge_flag']))
		{
			$log .= "<span class=\"yellow\">{$pa['nm']}的攻击隔着{$pd['nm']}的防具造成了伤害！</span><br>";
		}

		# 检查防守方(pd)有没有伤害抹消属性
		if(in_array('B',$pd['ex_keys']))
		{
			$dice = diceroll(99);
			# 失效率
			$obbs = 1 - $specialrate['B'];
			# 「天义」效果判定：
			if(isset($pa['skill_c6_justice'])) $obbs *= get_skillvars('c6_justice','pdefbkr');
			# 「暗杀」效果判定：
			if(isset($pa['skill_buff_assassin'])) $obbs += get_skillvars('buff_assassin','pdefbkr');
			//检查抹消属性是否生效
			if($dice > $obbs)
			{
				$pd['phy_def_flag'] =  2;
				#「脉冲」效果判定：
				if(isset($pa['bskill_c7_emp']) || isset($pd['bskill_c7_emp']))
				{
					$log .= "<span class='yellow'>在电磁脉冲的干扰下，伤害抹消力场被无效化了！</span><br>";
					$pa['bskill_c7_emp'] = 2;
					unset($pd['phy_def_flag']);
				}
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
			# 失效率
			$obbs = 10;
			# 「天义」效果判定：
			if(isset($pa['skill_c6_justice'])) $obbs *= get_skillvars('c6_justice','pdefbkr');
			# 「暗杀」效果判定：
			if(isset($pa['skill_buff_assassin'])) $obbs += get_skillvars('buff_assassin','pdefbkr');
			//检查防御属性是否生效
			if($dice > $obbs)
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
			# 失效率
			$obbs = 10;
			# 「天义」效果判定：
			if(isset($pa['skill_c6_justice'])) $obbs *= get_skillvars('c6_justice','pdefbkr');
			# 「暗杀」效果判定：
			if(isset($pa['skill_buff_assassin'])) $obbs += get_skillvars('buff_assassin','pdefbkr');
			if($dice > $obbs)
			{
				$pd['phy_def_flag'] = $def_kind[$pa['wep_kind']];
			}
			else 
			{
				$log .= "{$pd['nm']}的{$itemspkinfo[$def_kind[$pa['wep_kind']]]}没能发挥减半伤害的效果！<br>";
			}
		}

		# 贯穿效果判定：
		if(in_array('n',$pa['ex_keys'])) 
		{
			$dice = diceroll(99);
			# 未贯穿率
			$obbs = 1 - $specialrate['n'];
			# 「暗杀」效果判定：
			if(isset($pa['skill_buff_assassin'])) $obbs += get_skillvars('buff_assassin','pdefbkr');
			if($dice > $obbs)
			{
				if(!empty($pd['phy_def_flag']))
				{
					$pd['phy_def_flag'] = 0;
					$log .= "<span class=\"yellow\">{$pa['nm']}的攻击贯穿了{$pd['nm']}的防具！</span><br>";
				}
				$pa['pierce_flag'] = 1;
			}
		}

		# 「强袭」效果判定：
		if(isset($pa['bskill_c2_raiding']) && isset($pd['phy_def_flag']) && $pd['phy_def_flag'] != 2)
		{
			$pd['phy_def_flag'] = 0;
			$log .= "{$pa['nm']}的攻击无视了{$pd['nm']}的伤害减半效果！<br>";
		}

		#「穿杨」效果判定：
		if(isset($pa['bskill_c4_sniper']) && !isset($pa['pierce_flag']) && !empty($pd['phy_def_flag']))
		{
			$dice = diceroll(99);
			# 冴冴说这应该是odds……但是对不起，已经太迟了……^ ^;
			$obbs = get_skillvars('c4_sniper','prfix');
			if($dice < $obbs)
			{
				$pa['pierce_flag'] = 1;
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
		if(!empty($pd['phy_def_flag']))
		{
			//存在抹消属性
			if($pd['phy_def_flag']==2)
			{
				$p = 0;
				$log .= "<span class=\"red\">{$pa['nm']}的攻击完全被{$pd['nm']}的装备吸收了！</span><br>";
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

	//预受伤事件：
	//提取判断pd是否防具受损、受伤，但log在结尾统一输出
	function get_hurt_prepare_events(&$pa,&$pd,$active)
	{
		global $infatt_rev,$log;
		# pa致伤次数＞0时，计算pd防具受损或致伤情况
		if($pa['inf_times']>0)
		{
			# 「护盾」存在时无视致伤效果
			if (isset($pd['skill_buff_shield'])) return;

			//获取可致伤部位
			$inf_parts = $infatt_rev[$pa['wep_kind']];
			$inf_att = Array();
			for($i=0;$i<$pa['inf_times'];$i++)
			{
				//随机选择一个可致伤的部位
				$aim = rand(0,count($inf_parts)-1);
				$inf_aim = $inf_parts[$aim];
				//对应部位致伤次数+1
				$inf_att[$inf_aim] = isset($inf_att[$inf_aim]) ? $inf_att[$inf_aim]+1 : 1;
			}
			//记载防具受损、受伤情况
			if(!empty($inf_att))
			{
				$pd['armor_hurt'] = Array('arb' => 0,'arh' => 0,'ara' => 0,'arf' => 0,);
 				foreach($inf_att as $ipt => $times)
				{
					$which = 'ar'.$ipt;
					# 没有装备或装备耐久为0的情况下，受伤
					if(empty($pd[$which.'s']) || $pd[$which.'k'] == 'DN' || (!empty($pd[$which.'sdmg']) && $pd[$which.'s'] < $pd[$which.'sdmg']))
					{
						$pd['inf_hurt'][$ipt] = 1;

					}
					# 否则扣除装备耐久
					else
					{
						if(!isset($pd[$which.'sdmg'])) $pd[$which.'sdmg'] = 0;
						$pd[$which.'sdmg'] += $times;
						$pd['armor_hurt'][$which] += $times;
					}
				}
			}
		}
		return;
	}

	//获取pa能造成的属性伤害队列
	function get_base_ex_att_array(&$pa,&$pd,$active)
	{
		global $ex_attack,$log,$itemspkinfo,$elements_info,$r_elements_info;

		$ex_keys = Array();
		foreach($pa['ex_keys'] as $ex)
		{
			if(in_array($ex,$ex_attack))
			{
				//把$ex_attack和$pa['ex_keys']位置调换可过滤重复属性，现在不会过滤
				$ex_keys[]= $ex; 
			}
		}

		# 「附魔」效果判定：
		if(isset($pa['bskill_c3_enchant']))
		{
			// 用于获取属性对应的附魔加成
			$exdmgarr = get_skillvars('c3_enchant','exdmgarr');
			$exarr = get_skillvars('c3_enchant','exdmgdesc');
			$flip_exdmgarr = array_flip($exdmgarr);
			// 身上不存在伤害类属性，给一个随机属性
			if(empty($ex_keys) || !array_intersect($ex_keys,$flip_exdmgarr))
			{
				$pa['skill_c3_enchant_ex'] = array_rand($exarr);
				$ex_keys[] = $pa['skill_c3_enchant_ex'];
				$log .= "<span class='lime'>技能「附魔」附加了<span class='yellow'>{$itemspkinfo[$pa['skill_c3_enchant_ex']]}</span>属性！</span><br>";
			}
			if(!isset($pa['skill_c3_enchant_ex'])) $pa['skill_c3_enchant_ex'] = array_rand($exarr);
			// 检查是否提升属性伤害增益
			$exr_gain = get_skillvars('c3_enchant','exdmggain');
			$exr_gain_max = get_skillvars('c3_enchant','exdmgmax');
			// 检查属性伤害增益是否达到上限
			$now_exr = get_skillpara('c3_enchant',$exdmgarr[$pa['skill_c3_enchant_ex']],$pa['clbpara']);
			if($now_exr + $exr_gain < $exr_gain_max)
			{
				set_skillpara('c3_enchant',$exdmgarr[$pa['skill_c3_enchant_ex']],$now_exr + $exr_gain,$pa['clbpara']);
				$log .= "<span class='lime'>{$pa['nm']}的<span class='yellow'>{$exarr[$pa['skill_c3_enchant_ex']]}</span>伤害永久提升了{$exr_gain}%！</span><br>";
			}
			// 使用次数+1
			set_skillpara('c3_enchant','active_t',get_skillpara('c3_enchant','active_t',$pa['clbpara'])+1,$pa['clbpara']);
		}

		# 「磁暴」效果判定：
		if(isset($pa['bskill_c7_electric']) && (empty($ex_keys) || !in_array('e',$ex_keys))) $ex_keys[] = 'e';

		# 「渗透」效果判定：
		if(isset($pa['skill_c8_infilt']))
		{
			$sk_lvl = get_skilllvl('c8_infilt',$pa);
			$sk_keys = get_skillvars('c8_infilt','exext',$sk_lvl);
			if(!empty($sk_keys))
			{
				do{
					$ex_keys[] = 'p';
					$sk_keys--;
				}while($sk_keys);
				$log .= "<span class='purple'>致命毒雾从{$pa['nm']}身遭蔓延开来……</span><br>";
			}
		}

		# 「闪电」效果判定：
		if(isset($pa['bskill_c20_lighting']))
		{
			$sk = 'c20_lighting';
			# 元素 ↔ 效果队列
			$sk_etype = get_skillvars($sk,'emextype');
			# 获取已使用次数，并+1计数：
			$sk_t = get_skillpara($sk,'active_t',$pa['clbpara']);
			set_skillpara($sk,'active_t',$sk_t+1,$pa['clbpara']);
			# 根据已使用次数计算可释放次数
			$sk_t = floor((sqrt(8 * $sk_t + 1) - 1) / 2);
			# 根据可释放次数依次执行对应效果
			$sk_eid = range(0,5);
			for($s=1;$s<=$sk_t;$s++)
			{
				shuffle($sk_eid);
				$t = 0; $no_cost = 0;
				# 在当前库存的6种元素中，满足消耗量的元素
				do{
					$sk_cost = $s * get_skillvars($sk,'emcost');
					$eid = $sk_eid[$t];
					if($sk_cost < $pa['element'.$eid])
					{
						$pa['element'.$eid] -= $sk_cost;
						# 如果造成的伤害是属性伤害，直接加入队列；否则保存进 $pa['bskill_c20_lighting_white_dmg'] 内
						if($eid == 0)
						{
							if(!isset($pa['bskill_c20_lighting_white_dmg'])) $pa['bskill_c20_lighting_white_dmg'] = 0;
							$pa['bskill_c20_lighting_white_dmg'] += $sk_cost;
						}
						else
						{
							if(empty($light_log) || !in_array($r_elements_info[$eid],$light_log)) $light_log[] = $r_elements_info[$eid];
							$ex_keys[] = $sk_etype[$eid];
						}
						break;
					}
					$t++;
					if($t == 5) $no_cost = 1;
				}while($t <= 5);
				# 库存内没有可消耗的元素了，提前终止
				if($no_cost) break;
			}
			if(!empty($light_log))
			{
				$log .= implode('、',$light_log)."<span class='yellow'>附着在了{$pa['nm']}的武器上！</span><br>";
			}
		}
		return $ex_keys;
	}

	//pa在造成属性伤害前触发的事件
	function deal_ex_damage_prepare_events(&$pa,&$pd,$active)
	{
		global $log,$ex_attack,$ex_def_kind,$specialrate,$itemspkinfo;

		# 检查防守方(pd)有没有属性抹消属性
		if(in_array('b',$pd['ex_keys']))
		{
			$dice = diceroll(99);
			# 失效率
			$obbs = 1 - $specialrate['b'];
			# 「暗杀」效果判定：
			if(isset($pa['skill_buff_assassin'])) $obbs += get_skillvars('buff_assassin','pdefbkr');
			//检查抹消属性是否生效
			if($dice > $obbs)
			{
				$pd['ex_def_flag'] =  2;
				#「脉冲」效果判定：
				if(isset($pa['bskill_c7_emp']) || isset($pd['bskill_c7_emp']))
				{
					$log .= "<span class='yellow'>在电磁脉冲的干扰下，属性抹消力场被无效化了！</span><br>";
					$pa['bskill_c7_emp'] = 2;
					unset($pd['ex_def_flag'] );
				}
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
			# 失效率
			$obbs = 10;
			# 「暗杀」效果判定：
			if(isset($pa['skill_buff_assassin'])) $obbs += get_skillvars('buff_assassin','pdefbkr');
			if($dice > $obbs)
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
					# 失效率
					$obbs = 10;
					# 「暗杀」效果判定：
					if(isset($pa['skill_buff_assassin'])) $obbs += get_skillvars('buff_assassin','pdefbkr');
					if($dice > $obbs) $pd['ex_def_flag'][] = $ex; //单项防御生效，加入队列
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
		if(in_array('y',$pa['ex_keys'])) 
		{
			$dice = diceroll(99);
			# 未破格率
			$obbs = 1 - $specialrate['y'];
			# 「暗杀」效果判定：
			if(isset($pa['skill_buff_assassin'])) $obbs += get_skillvars('buff_assassin','pdefbkr');
			if ($dice > $obbs) 
			{
				if(!empty($pd['ex_def_flag']))
				{
					$pd['ex_def_flag'] = 0;
					$log .= "<span class=\"yellow\">{$pa['nm']}的攻击瓦解了{$pd['nm']}的属性防护！</span><br>";
				}
				$pa['ex_pierce_flag'] = 1;
			}
		}

		# 「强袭」效果判定：
		if(isset($pa['bskill_c2_raiding']) && isset($pd['ex_def_flag']) && $pd['ex_def_flag'] != 2)
		{
			$pd['ex_def_flag'] = 0;
			$log .= "{$pa['nm']}的攻击无视了{$pd['nm']}的属性伤害减半效果！<br>";
		}
		return;
	}

	//计算可造成的属性伤害
	function get_original_ex_dmg(&$pa,&$pd,$active)
	{
		global $log,$now;
		//触发了属抹效果，直接返回固定伤害值
		if(isset($pd['ex_def_flag']) && $pd['ex_def_flag'] == 2) 
		{
			$total_ex_dmg = count($pa['ex_attack_keys']);
			$log .= "<span class=\"red\">属性攻击的力量完全被防具吸收了！</span>仅造成了<span class=\"red\">{$total_ex_dmg}</span>点伤害！<br>";
			return $total_ex_dmg;
		}

		//遍历并执行单次属性伤害，返回的是一个数组
		$ex_dmg = 0; $ex_inf = 0;
		//      属性攻击名   异常状态	 对应防御属性   基础属性伤害   属性伤害上限  效果↔伤害系数  熟练↔伤害系数     伤害浮动
		global $exdmgname, $exdmginf, $ex_def_kind, $ex_base_dmg, $ex_max_dmg, $ex_wep_dmg, $ex_skill_dmg, $ex_dmg_fluc;
		//	   属性↔异常   异常率     异常率上限     熟练↔异常率系数    异常↔伤害系数    得意社团		得意武器
		global $ex_inf, $ex_inf_r, $ex_max_inf_r, $ex_skill_inf_r, $ex_inf_punish, $ex_good_club, $ex_good_wep;

		foreach($pa['ex_attack_keys'] as $ex)
		{
			$pa['ex_dmgpsh_log'] = '';$pa['ex_dmginf_log'] = ''; $pa['ex_dmgdef_log'] = '';
			//计算单个属性的基础属性伤害： 基础伤害 + 效果↔伤害系数修正 + 熟练↔伤害系数修正
			$ex_dmg = $ex_base_dmg[$ex] + $pa['wepe']/$ex_wep_dmg[$ex] + $pa['wep_skill']/$ex_skill_dmg[$ex];
			//计算得意武器类型对单个属性伤害的系数修正
			if(isset($ex_good_wep[$ex]) && $ex_good_wep[$ex] == $pa['wep_kind']) $ex_dmg *= 2;
			//计算单个属性能造成的基础伤害上限
			$ex_dmg = get_ex_base_dmg_max($pa,$pd,$active,$ex,$ex_dmg);
			//计算属性伤害浮动
			$ex_dmg = round($ex_dmg * rand(100-$ex_dmg_fluc[$ex],100+$ex_dmg_fluc[$ex])/100);
			//计算单个属性的属性伤害变化：
			$ex_dmg = get_ex_base_dmg_p($pa,$pd,$active,$ex,$ex_dmg);
			//pd身上保有「护盾」的情况下 计算会对护盾造成的损害
			if(isset($pd['skill_buff_shield']))
			{
				$shield = get_skillpara('buff_shield','svar',$pd['clbpara']);
				$ex_shield_dmg_r = $ex == 'e' ? 2 : 1;
				$ex_shield_dmg = $ex_dmg * $ex_shield_dmg_r;
				if($ex_shield_dmg >= $shield)
				{
					$ex_dmg = max(1,round(($ex_shield_dmg - $shield)/$ex_shield_dmg_r));
					unset($pd['skill_buff_shield']);
					lostclubskill('buff_shield',$pd['clbpara']);
					$pa['ex_breakshield_log'] = 1;
				}
				else 
				{
					$shield -= $ex_dmg;
					$ex_dmg = 0;
					set_skillpara('buff_shield','svar',$shield,$pd['clbpara']);
					$pa['ex_shield_log'] = 1;
				}
			}
			//计算是否能够施加属性异常
			if(empty($pd['ex_def_flag']) && empty($pd['skill_buff_shield']) && isset($ex_inf[$ex]) && strpos($pd['inf'],$ex_inf[$ex])===false)
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
					$pa['ex_dmginf_log'] = $ex_inf[$ex];
					$pd['inf'] .= $pa['ex_dmginf_log'];
					addnews($now,'inf',$pa['name'],$pd['name'],$pa['ex_dmginf_log']);
				}
			}
			//整理后统一输出文本	
			if(!empty($pa['ex_dmgpsh_log'])) $log .= $pa['ex_dmgpsh_log'];	//由于对方已经xx xx伤害提升/降低
			$log .= $exdmgname[$ex]; //xx
			if($ex_dmg)
			{
				$ex_dmg = ceil($ex_dmg);
				if(!empty($pa['ex_dmgdef_log'])) $log .= "被防御效果抵消了！仅";
				$log .= "造成了<span class=\"red\">{$ex_dmg}</span>点伤害！";
				if(!empty($pa['ex_dmginf_log'])) $log .= "并使{$pd['nm']}{$exdmginf[$pa['ex_dmginf_log']]}了！";
				$log .= "<br>";
				if(!empty($pa['ex_breakshield_log'])) $log .= "<span class='red'>{$pd['nm']}的「护盾」被打破了！</span><br>";
				$total_ex_dmg[] = $ex_dmg;
			}
			else 
			{
				if(!empty($pa['ex_shield_log'])) $log .= "被「护盾」抵消了！";
				$log .= "没能造成伤害！<br>";
				if(!empty($pa['ex_shield_log'])) $log .= "<span class='grey'>{$pd['nm']}的「护盾」效力减弱了……</span><br>";
			}
		}
		return $total_ex_dmg;
	}

	//计算属性总伤害加成
	function get_ex_dmg_p(&$pa,&$pd,$active)
	{
		global $log;
		$ex_dmg_p = Array();
		# 「咆哮」判定：
		if(isset($pa['bskill_c4_roar']))
		{
			//获取倍率
			$sk_p = get_skillvars('c4_roar','exdmgr');
			$p = 1 + ($sk_p / 100);
			$ex_dmg_p[]= $p; 
			//输出log
			$log.="<span class='yellow'>「咆哮」使{$pa['nm']}造成的属性伤害提高了{$sk_p}%！</span><br>";
		}
		# 「特攻」判定：
		if(isset($pa['skill_c8_expert']))
		{
			$sk_lvl = get_skilllvl('c8_expert',$pa);
			$sk_p = get_skillvars('c8_expert','exdmgr',$sk_lvl);
			if(!empty($sk_p))
			{
				$p = 1 + ($sk_p / 100);
				$ex_dmg_p[]= $p; 
			}
		}
		# 「催化」判定：
		if(isset($pa['bskill_c8_catalyst']))
		{
			$sk_nums = $pa['bskill_c8_catalyst']-1;
			if(!empty($sk_nums))
			{
				$sk_p = get_skillvars('c8_catalyst','exdmgr')*$sk_nums;
				$p = 1 + ($sk_p / 100);
				$ex_dmg_p[]= $p; 
				$log.="<span class='yellow'>「催化」使{$pa['nm']}造成的属性伤害提高了{$sk_p}%！</span><br>";
			}
		}
		return $ex_dmg_p;
	}

	//计算最终伤害的系数变化
	function get_final_dmg_p(&$pa,&$pd,$active)
	{
		global $log;
		$fin_dmg_p = Array();

		# 「强袭」效果判定：
		if(isset($pa['bskill_c2_raiding']))
		{
			$sk_p = get_skillvars('c2_raiding','findmgr');
			if($sk_p)
			{
				$p = 1+($sk_p / 100);
				$log.= "<span class='yellow'>「强袭」使{$pa['nm']}造成的最终伤害提高了{$sk_p}%！</span><br>";
				$fin_dmg_p[] = $p;
			}
		}
		# 「歼灭」效果判定：
		if(isset($pa['skill_buff_annihil']))
		{
			# 「歼灭」有两段效果，一段为概率触发，一段为固定触发，所以只在概率触发的位置判定概率。
			$sk_dice = diceroll(99);
			$sk_obbs = get_skillvars('buff_annihil','rate');
			if($sk_dice < $sk_obbs)
			{
				$sk_p = get_skillvars('buff_annihil','findmgr');
				if($sk_p)
				{
					$p = $sk_p / 100;
					$log.= "<span class='red'>暴击！</span><span class='lime'>「歼灭」使{$pa['nm']}造成的最终伤害提高了{$sk_p}%！</span><br>";
					$fin_dmg_p[] = $p;
				}
			}
		}
		#「破甲」效果判定：
		if(isset($pa['skill_c4_break']) && !empty($pd['inf_hurt']))
		{
			$sk_lvl = get_skilllvl('c4_break',$pa);
			//获取伤害加成
			$sk_p = get_skillvars('c4_break','infdmgr',$sk_lvl);
			//获取致伤处
			$inf_p = count($pd['inf_hurt']);
			$sk_p *= $inf_p;
			if($sk_p)
			{
				$p = 1+($sk_p / 100);
				$log.= "<span class='yellow'>「破甲」使{$pa['nm']}造成的最终伤害提高了{$sk_p}%！</span><br>";
				$fin_dmg_p[] = $p;
			}
		}
		# 「心火」效果判定：
		if(isset($pa['bskill_c9_heartfire']))
		{
			$sk_p = get_skillvars('c9_heartfire','findmgr');
			if($sk_p)
			{
				$log.= "<span class='lime'>{$pa['nm']}敛神聚气，谷足力量凝聚在这凌厉一击上！</span><br>";
				$fin_dmg_p[] = $sk_p;
			}
		}
		# 「底力」效果判定：
		if(isset($pa['skill_c12_enmity']))
		{
			$sk_lvl = get_skilllvl('c12_enmity',$pa);
			$sk_p = round(get_skillvars('c12_enmity','findmgr',$sk_lvl) * calc_enmity_losshpr($pa,$pd));
			if($sk_p > 5)
			{
				$p = 1+($sk_p / 100);
				$log.= "<span class='yellow'>「底力」使{$pa['nm']}造成的最终伤害提高了{$sk_p}%！</span><br>";
				$fin_dmg_p[] = $p;
			}
		}

		# 书中虫防守事件：移动到最终伤害系数变化阶段了
		if($pd['type'] == 89 && ($pd['name'] == '高中生·白神' || $pd['name'] == '白神·讨价还价'))
		{
			$p = attr_extra_89_bookworm($pa,$pd,$active,'defend');
			if($p>0) $fin_dmg_p[]= $p; 
		}

		# 「莹心」pa效果判定：
		if(isset($pa['skill_c19_purity']))
		{
			$sk = 'c19_purity';
			$sk_lvl = get_skilllvl($sk,$pa);
			$sk_p = get_skillvars($sk,'findmgr',$sk_lvl);
			if($sk_p)
			{
				$p = 1 - ($sk_p / 100);
				$log.="<span class=\"yellow\">在「莹心」的作用下，{$pa['nm']}造成的最终伤害降低了{$sk_p}%！</span><br>";
				$fin_dmg_p[] = $p;
			}
		}

		# 「莹心」pd效果判定：
		if(isset($pd['skill_c19_purity']))
		{
			$sk = 'c19_purity';
			$sk_lvl = get_skilllvl($sk,$pd);
			$sk_p = get_skillvars($sk,'findmgdefr',$sk_lvl);
			if($sk_p)
			{
				$p = 1 - ($sk_p / 100);
				$log.="<span class=\"yellow\">在「莹心」的作用下，{$pa['nm']}造成的最终伤害降低了{$sk_p}%！</span><br>";
				$fin_dmg_p[] = $p;
			}
		}
		return $fin_dmg_p;
	}

	//计算最终伤害的定值变化
	function get_final_dmg_fix(&$pa,&$pd,$active,$fin_dmg)
	{
		global $log,$plsinfo,$now;

		# 「量心」效果判定 手加减：
		if(isset($pa['askill_c19_dispel']) && $fin_dmg >= $pd['hp'])
		{
			$fin_dmg = $pd['hp'] - 1;
			$pa['askill_c19_dispel'] = 2;
			$log.="<span class=\"yellow\">{$pa['nm']}在出手时保持了最大限度的克制！</span><br>";
			return $fin_dmg;
		}

		# 「闷棍」技能效果：
		if(isset($pa['bskill_c1_bjack']))
		{
			if($pd['sp'] < $pd['msp'])
			{
				$sk_dmg = $pd['msp'] - $pd['sp'];
				$log.="闷棍对体力不支的{$pd['nm']}造成了<span class=\"yellow\">{$sk_dmg}</span>点额外伤害！<br>";
				$fin_dmg += $sk_dmg;
			}
			else 
			{
				$log.="闷棍没有造成额外伤害！<br>";
			}
		}
		# 「解牛」技能效果：
		if(isset($pa['bskill_c2_butcher']))
		{
			$sk_dmg = get_skillvars('c2_butcher','fixdmg') + $pa['lvl'];
			$log.='<span class="yellow">「解牛」附加了'.$sk_dmg.'点伤害！</span><br>';
			$fin_dmg += $sk_dmg;
		}
		#「对撞」技能效果：
		if(isset($pd['askill_c3_offset']))
		{
			$dice = diceroll(99);
			$obbs = min(get_skillvars('c3_offset','minchance') + $pd['wc'] * get_skillvars('c3_offset','chancegainr'),get_skillvars('c3_offset','maxchance'));
			if($dice < $obbs)
			{
				$offset_dmg = min(round(sqrt($pd['wepe'])*get_skillvars('c3_offset','wepeffectr')),get_skillvars('c3_offset','maxeffect'));
				$offset_dmg = min($fin_dmg,$offset_dmg);
				$log .= "<span class='yellow'>但{$pd['nm']}及时掷出手中的{$pd['wep']}，抵消了<span class='red'>{$offset_dmg}</span>点伤害！</span><br>";
				//扣除对撞所消耗的效果
				if(!empty(get_skillvars('c3_offset','wepsloss')))
				{
					$weploss = round($pd['wepe'] * (get_skillvars('c3_offset','wepsloss')/100));
					weapon_loss($pd,$weploss,1);
				}
				$fin_dmg -= $offset_dmg;
			}
		}
		# 伤害制御判定：
		if(in_array('h',$pd['ex_keys']) && $fin_dmg>=1950)
		{
			$dice = diceroll(99);
			// 失效率
			$obbs = 10;

			# 「暗杀」效果判定
			if(isset($pa['skill_buff_assassin'])) $obbs += get_skillvars('buff_assassin','pdefbkr');

			if ($dice > $obbs) 
			{
				//贯穿与破格同时生效时 穿透伤害制御
				if(isset($pa['ex_pierce_flag']) && isset($pa['pierce_flag']))
				{
					$log .= "<span class='gold'>{$pa['nm']}凌厉的攻势直接突破了{$pd['nm']}的伤害限制！</span><br>";
				}
				else 
				{
					#「脉冲」效果判定：
					if(isset($pa['bskill_c7_emp']) || isset($pd['bskill_c7_emp']))
					{
						$log .= "<span class='yellow'>在电磁脉冲的干扰下，伤害制御力场被无效化了！</span><br>";
						$pa['bskill_c7_emp'] = 2;
					}
					else
					{
						$fin_dmg = 1950 + $dice;
						$log .= "在{$pd['nm']}的装备的作用下，攻击伤害被限制了！<br>";
					}
				}
			}
			else
			{
				$log .= "{$pd['nm']}的装备没能发挥限制攻击伤害的效果！<br>";
			}
		}

		# 「祛障」效果判定：
		if(isset($pa['bskill_c19_redeem']))
		{
			# rp低于对方时，附加白字伤害
			if($pd['rp'] > $pa['rp'])
			{
				$rp_dmg = $pd['rp'] - $pa['rp'];
				$fin_dmg += $rp_dmg;
				$log .= "<span class=\"yellow\">在「祛障」的作用下，{$pd['nm']}受到了<span class=\"red\">$rp_dmg</span>点额外伤害。</span><br>";
			}
			else 
			{
				$min_rp = get_skillvars('c19_redeem','rpmin');
				$move_rp = max($min_rp,$pd['rp']);
				$pd['rp'] += $move_rp; $pa['rp'] -= $move_rp;
				$log .= "<span class=\"yellow\">在「祛障」的作用下，{$pa['nm']}将部分罪业转移给了{$pd['nm']}！<br>";
			}
		}

		# 「狂怒」效果判定：
		if(isset($pa['bskill_c12_rage']))
		{
			$sk_dmg = round($pa['mhp']*0.25); 
			$pa['hp'] -= $sk_dmg;
			$sk_vars = get_skillvars('c12_enmity','findmgr',get_skilllvl('c12_enmity',$pa)) * calc_enmity_losshpr($pa,$pd);
			if($sk_vars > 1) $sk_dmg = round($sk_dmg *(1 + ($sk_vars/100)));
			if($sk_dmg > 0)
			{
				$fin_dmg += $sk_dmg;
				$log .= "<span class=\"yellow\">{$pa['nm']}燃烧生命打出了狂怒一击！附加了<span class=\"red\">$sk_dmg</span>点额外伤害！</span><br>";
			}
		}

		# 「闪电」无色伤害效果判定：
		if(isset($pa['bskill_c20_lighting_white_dmg']))
		{
			global $elements_info;
			$fin_dmg += $pa['bskill_c20_lighting_white_dmg'];
			$log .= "<span class='mtgcolorless'>{$elements_info[0]}使{$pa['nm']}的攻击附加了{$pa['bskill_c20_lighting_white_dmg']}点额外伤害！</span><br>";
			unset($pa['bskill_c20_lighting_white_dmg']);
		}				

		# 「护盾」效果判定
		if(isset($pd['skill_buff_shield']))
		{
			$sk_var = get_skillpara('buff_shield','svar',$pd['clbpara']);
			$fin_dmg = max(0,$fin_dmg - $sk_var);
			$log .= "<span class=\"lime\">「护盾」使{$pd['nm']}受到的伤害降低了{$sk_var}点！</span><br>";
		}

		# 「灵俑」抵挡伤害判定
		if(!empty($pd['clbpara']['zombieid']))
		{
			$mate_list = $pd['clbpara']['zombieid']; 
			shuffle($mate_list);
			foreach($mate_list as $mid)
			{
				$mdata = fetch_playerdata_by_pid($mid);
				# 跳过不在同一地图且状态不健康的灵俑
				if($mdata['pls'] != $pd['pls'] || $mdata['hp'] < 10) continue;
				# 抵挡伤害
				$max_cover_dmg = min($fin_dmg,round($mdata['hp']/2));
				if($max_cover_dmg)
				{
					$mdata['hp'] -= $max_cover_dmg;
					player_save($mdata);
					$fin_dmg -= $max_cover_dmg;
					$log .= "<span class=\"yellow\">但是{$mdata['name']}为{$pd['nm']}抵挡了{$max_cover_dmg}点伤害！</span><br>";
					break;
				}
			}
		}

		# 「火花」技能判定：救命
		if($fin_dmg >= $pd['hp'] && isset($pd['askill_c20_sparkle']))
		{
			$sk = 'c20_sparkle';
			unset($pa['askill_c20_sparkle']);
			$plslist = get_safe_plslist(); 
			$sp_pls = $plslist[array_rand($plslist)];
			# 注销技能状态
			set_skillpara($sk,'active',0,$pd['clbpara']);
			set_skillpara($sk,'active_t',1,$pd['clbpara']);
			# 切换地图
			$pd['pls'] = $sp_pls; 
			$pd['tp_by_sparkle'] = $sp_pls;
			$log .= "<span class=\"yellow\">千钧一发之际，{$pd['nm']}点燃火花传送到了别处！</span><br>";
			if(!$pa['type']) $pa['log_save'] .= "{$pd['name']}在千钧一发之际点燃了火花，传送到不知道哪去了！";
			addnews($now,'sparklerevival',$pd['name'],$pa['name'],$plsinfo[$sp_pls]);
			return 0;
		}

		# 「爆头」技能效果
		if(isset($pa['skill_c4_headshot']) && $fin_dmg > $pd['hp']*0.85 && $fin_dmg < $pd['hp'])
		{
			$fin_dmg =  $pd['hp'];
			$log .= "<span class=\"yellow\">{$pa['nm']}的攻击直接将{$pd['nm']}爆头！</span><br>";
		}

		return $fin_dmg;
	}

	//攻击方(pa)在造成伤害后触发的事件
	function attack_finish_events(&$pa,&$pd,$active)
	{
		global $log;

		#计算反噬伤害：
		if ($pa['final_damage'] >= 1000) 
		{
			if ($pa['final_damage'] < 2000) {
				$hp_d = floor($pa['hp']/2);
			} elseif ($pa['final_damage'] < 5000) {
				$hp_d = floor($pa['hp']*2/3);
			} else {
				$hp_d = floor($pa['hp']*4/5);
			}
			if (in_array('H',$pa['ex_keys'])) 
			{
				#「脉冲」效果判定：
				if(isset($pa['bskill_c7_emp']) || isset($pd['bskill_c7_emp']))
				{
					$log .= "<span class='yellow'>在电磁脉冲的干扰下，HP制御力场被无效化了！</span><br>";
					$pa['bskill_c7_emp'] = 2;
				}
				else
				{
					$hp_d = floor ( $hp_d / 10 );
				}
			}
			# 「护盾」效果判定
			if ($hp_d && isset($pa['skill_buff_shield']))
			{
				$hp_d = 0;
				$log .= "<span class='yellow'>「护盾」使{$pa['nm']}免受反噬伤害！</span><br>";
			}
			# 「冰心」技能判定
			if ($hp_d && isset($pa['skill_c9_iceheart']))
			{
				$sk_r = 1 - (get_skillvars('c9_iceheart','hpshloss') / 100);
				$hp_d = floor($hp_d * $sk_r);
				$log .= "<span class='yellow'>「冰心」使{$pa['nm']}受到的反噬伤害降低了！</span><br>";
			}
			if($hp_d > 0)
			{
				$log .= "惨无人道的攻击对{$pa['nm']}自身造成了<span class=\"red\">$hp_d</span>点<span class=\"red\">反噬伤害！</span><br>";
				$pa['hp'] -= $hp_d;
			}
		}

		# 检查成就502
		if ($pa['wep_name'] == '翼人的羽毛') $pa['clbpara']['achvars']['ach502'] = $pa['final_damage'];

		return;
	}

	//防守方(pd)在受到伤害后触发的事件
	function get_hurt_events(&$pa,&$pd,$active) 
	{
		global $log,$infinfo,$exdmginf;
		
		# pd存在防具受损况，在这里应用
		if(!empty($pd['armor_hurt']))
		{
			foreach($pd['armor_hurt'] as $which => $times) armor_hurt($pd,$which,$times);
		}

		# pd存在受伤情况，在这里应用
		if(!empty($pd['inf_hurt']))
		{
			foreach($pd['inf_hurt'] as $which => $times)
			{
				$flag = get_inf_rev($pd,$which);
				if($flag) $log .= "{$pd['nm']}的{$exdmginf[$which]}了！<br>";
			}
		}

		# 将pa造成的伤害记录在pd的成就里
		if(!$pd['type'] && $pa['final_damage'] >= 1000000) $pd['clbpara']['achvars']['takedmg'] = $pa['final_damage'];
		
		return;
	}

	//受到致伤或异常
	function get_inf_rev(&$pa,$infnm,$type=0)
	{
		global $log;

		if(strpos($pa['inf'],$infnm) === false)
		{
			$pa['inf'] .= $infnm;
			//$pa['combat_inf'] .= $infnm;		
			return 1;	
		}
		return 0;
	}

	# 应用技能类异常
	function get_skillinf_rev(&$pa,$sk,$last=0)
	{
		global $log;
		$flag = getclubskill($sk,$pa['clbpara']);
		if($last && $flag) set_lasttimes($sk,$last,$pa['clbpara']);
		return $flag;
	}

	# 从异常状态中恢复
	function heal_inf_rev(&$pa,$infnm)
	{
		global $log;
		if(strpos($pa['inf'],$infnm) !== false)
		{
			$pa['inf'] = str_replace($infnm,"",$pa['inf']);
			//$pa['combat_inf'] .= $infnm;		
			return 1;	
		}
		return 0;
	}

	//计算战场距离：仅供追击/鏖战机制使用
	function get_battle_range(&$pa,&$pd,$active)
	{
		//战场距离 = (双方射程差值 - 战斗回合数) * 10
		if(!isset($pa['wep_range'])) $pa['wep_range'] = get_wep_range($pa);
		if(!isset($pd['wep_range'])) $pd['wep_range'] = get_wep_range($pd);
		$range = abs($pa['wep_range'] - $pd['wep_range']);
		$turns = get_battle_turns($pa,$pd,$active);
		$range = max(0,$range-$turns);
		return $range;
	}

	//获取战斗轮次：仅供追击/鏖战机制使用
	function get_battle_turns(&$pa,&$pd,$active)
	{
		if(!isset($pa['clbpara']['battle_turns']) || !isset($pd['clbpara']['battle_turns']))
		{
			change_battle_turns($pa,$pd,$active);
		}
		# 如果敌我双方记录的战斗轮次不同步，选择其中更小的一方……这是因为逃跑时可能不会重置NPC的战斗轮次。
		# 或许本来也不用重置NPC的战斗轮次……？
		$turns = $pa['clbpara']['battle_turns'] == $pd['clbpara']['battle_turns'] ? $pa['clbpara']['battle_turns'] : min($pa['clbpara']['battle_turns'],$pd['clbpara']['battle_turns']);
		return $turns;
	}

	//战斗轮步进
	function change_battle_turns(&$pa,&$pd,$active)
	{
		if(!isset($pa['clbpara']['battle_turns']))
		{
			$pa['clbpara']['battle_turns'] = 0;
		}
		else 
		{
			$pa['clbpara']['battle_turns'] ++;
		}
		if(!isset($pd['clbpara']['battle_turns']))
		{
			$pd['clbpara']['battle_turns'] = 0;
		}
		else 
		{
			$pd['clbpara']['battle_turns'] ++;
		}
		return;
	}

	//重置战斗回合
	function rs_battle_turns(&$pa,&$pd)
	{
		if(isset($pa['clbpara']['battle_turns'])) unset($pa['clbpara']['battle_turns']);
		if(isset($pd['clbpara']['battle_turns'])) unset($pd['clbpara']['battle_turns']);
		return;
	}
}
?>