<?php

	if(!defined('IN_GAME')) {
		exit('Access Denied');
	}

	//include_once GAME_ROOT.'./include/game/dice.func.php';
	include_once GAME_ROOT.'./include/game/itemmain.func.php';
	//include_once GAME_ROOT.'./include/game/revclubskills.func.php';

	//revattr_extra.func.php: 记录NPC特殊战斗机制...玩家战斗技也先放这里了 嘻嘻
	//Q：为什么要把每个NPC的特殊战斗机制都新建一个函数保存？
	//A：也不是每个都要这么干……这个做法主要用于存在大段log、多段判定的机制，分离出来一是方便定位这个NPC的相关机制在哪个阶段执行，二是确保原流程的可读性；

	# 技能判定（主动型）
	function attr_extra_active_skills(&$pa,&$pd,$active,$sk='')
	{
		global $log,$cskills;
		# 检查主动技合法性
		if(isset($pa['bskill']))
		{
			if(!check_skill_unlock($pa['bskill'],$pa) && !check_skill_cost($pa['bskill'],$pa))
			{
				$bsk = $pa['bskill'];
				$bsk_name = $cskills[$bsk]['name'];
				# 扣除怒气
				$bsk_cost = get_skillvars($bsk,'ragecost');
				if($bsk_cost) $pa['rage'] -= $bsk_cost;
				# 成功释放主动技，应用标记
				$pa['bskill_'.$bsk] = 1;
				$log .= "<span class=\"lime\">{$pa['nm']}对{$pd['nm']}发动了技能「{$bsk_name}」！</span><br>";
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

	# 技能判定（被动型）：这里通常只判定技能是否生效，如生效，提供一个标记
	function attr_extra_passive_skills(&$pa,&$pd,$active,$sk='')
	{
		global $cskills;
		if(!empty($pa['clbpara']['skill']))
		{
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
						$sk_obbs = isset($pa['bskill_c1_sneak'])||isset($pa['bskill_c1_bjack']) ? 100 : get_skillvars('c1_crit','rate');
						# 成功触发时
						if($sk_dice < $sk_obbs)
						{
							$pa['skill_c1_crit'] = 1;
							$pa['skill_c1_crit_log'] = "<span class=\"yellow b\">{$pa['nm']}朝着{$pd['nm']}打出了凶猛的一击！<span class=\"cyan b\">{$pd['nm']}被打晕了过去！</span></span><br>";
						}
					}
					# 「枭眼」特殊判定：射程不小于对方时激活效果
					elseif($sk == 'c3_hawkeye' && $pa['wep_range'] >= $pd['wep_range'])
					{
						$pa['skill_c3_hawkeye'] = 1;
					}
					# 其他非特判技能，默认给一个触发标记
					else 
					{
						$pa['skill_'.$sk] = 1;
						//$pa['skill_'.$sk.'_log'] = "";
					}
				}
				# active标签技能通用判定
				if(get_skilltags($sk,'active') && !empty(get_skillpara($sk,'active',$pa['clbpara'])) && !check_skill_unlock($sk,$pa))
				{
					$pa['askill_'.$sk] = 1;
				}
			}
		}
		return;
	}

	# 获取社团技能对先攻率（pa是否先攻pd）的修正（新）
	function get_clbskill_activerate(&$pa,&$pd)
	{
		$r = 1;
		# pa持有「枭眼」时的效果判定：
		if(!check_skill_unlock('c3_hawkeye',$pa))
		{
			//计算双方射程差
			$pa['wep_range'] = get_wep_range($pa); 
			$pd['wep_range'] = get_wep_range($pd); 
			if($pa['wep_range'] >= $pd['wep_range'])
			{
				$sk_r = get_skillvars('c3_hawkeye','activer');
				$r += $sk_r/100;
			}
		}
		# pd持有「枭眼」时的效果判定：
		if(!check_skill_unlock('c3_hawkeye',$pd))
		{
			//计算双方射程差
			$pa['wep_range'] = get_wep_range($pa); 
			$pd['wep_range'] = get_wep_range($pd); 
			if($pa['wep_range'] < $pd['wep_range'])
			{
				$sk_r = get_skillvars('c3_hawkeye','activer');
				$r -= $sk_r/100;
			}
		}
		return $r;
	}

	# 获取社团技能对基础反击率的修正（新）
	function get_clbskill_counterate(&$pa,&$pd,$active,$counterate)
	{
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
		return $counterate;
	}

	# 获取社团技能对基础命中率的修正（新）
	function get_clbskill_hitrate(&$pa,&$pd,$active,$hitrate)
	{
		# 加成：
		#「潜能」效果判定：
		if(isset($pa['bskill_c3_potential']))
		{
			//原来必中是这个意思……
			return 10000;
		}
		#「直感」效果判定：
		if(isset($pa['skill_c2_intuit']))
		{
			$sk_lvl = get_skilllvl('c2_intuit',$pa);
			$sk_r = 1 + (get_skillvars('c2_intuit','accgain',$sk_lvl) / 100);
			$hitrate *= $sk_r;
		}
		#「静息」效果判定：
		if(isset($pa['skill_c4_stable']))
		{
			$sk_lvl = get_skilllvl('c4_stable',$pa);
			$sk_r = 1 + (get_skillvars('c4_stable','accgain',$sk_lvl) / 100);
			$hitrate *= $sk_r;
		}
		#「瞄准」效果判定：
		if(isset($pa['skill_c4_aiming']))
		{
			$sk_r = 1 + (get_skillvars('c4_aiming','accgain') / 100);
			$hitrate *= $sk_r;
		}
		#「穿杨」效果判定：
		if(isset($pa['skill_c4_sniper']))
		{
			$sk_r = 1 + (get_skillvars('c4_sniper','accgain') / 100);
			$hitrate *= $sk_r;
		}

		# 减益：
		#「枭眼」效果判定：
		if(isset($pd['skill_c3_hawkeye']))
		{
			$sk_r = 1 - (get_skillvars('c3_hawkeye','accloss') / 100);
			$hitrate *= $sk_r;
		}
		return $hitrate;
	}

	# 获取社团技能对连击命中率的修正（新）
	function get_clbskill_r_hitrate(&$pa,&$pd,$active,$hitrate)
	{
		# 加成：
		#「潜能」效果判定：
		if(isset($pa['bskill_c3_potential']))
		{
			//潜能激活时连击无衰减
			return 1;
		}
		#「直感」效果判定：
		if(isset($pa['skill_c2_intuit']))
		{
			$sk_lvl = get_skilllvl('c2_intuit',$pa);
			//获取连击命中率加成
			$sk_r = 1 + (get_skillvars('c2_intuit','rbgain',$sk_lvl) / 100);
			$hitrate *= $sk_r;
		}
		#「静息」效果判定：
		if(isset($pa['skill_c4_stable']))
		{
			$sk_lvl = get_skilllvl('c4_stable',$pa);
			//获取连击命中率加成
			$sk_r = 1 + (get_skillvars('c4_stable','rbgain',$sk_lvl) / 100);
			$hitrate *= $sk_r;
		}
		# 减益：
		#「枭眼」效果判定：
		if(isset($pd['skill_c3_hawkeye']))
		{
			$sk_r = 1 - (get_skillvars('c3_hawkeye','rbloss') / 100);
			$hitrate *= $sk_r;
		}
		return $hitrate;
	}

	# 获取社团技能对致伤率（防具耐久损伤率）的修正（新）
	function get_clbskill_infrate(&$pa,&$pd,$active,$infrate)
	{
		#「破甲」效果判定：
		if(isset($pa['skill_c4_break']))
		{
			$sk_lvl = get_skilllvl('c4_break',$pa);
			//获取致伤率加成
			$sk_r = 1 + (get_skillvars('c4_break','infrgain',$sk_lvl) / 100);
			$infrate *= $sk_r;
		}
		return $infrate;
	}

	# 获取社团技能对基础致伤效果（每次致伤会损耗多少点防具耐久）的修正（新）
	function get_clbskill_inftimes(&$pa,&$pd,$active,$inftimes)
	{
		#「破甲」效果判定：
		if(isset($pa['skill_c4_break']))
		{
			$sk_lvl = get_skilllvl('c4_break',$pa);
			//获取致伤效果加成
			$sk_fix = get_skillvars('c4_break','inftfix',$sk_lvl);
			$inftimes += $sk_fix;
		}
		#「咆哮」效果判定：
		if(isset($pa['skill_c4_roar']))
		{
			//获取致伤效果加成
			$sk_fix = get_skillvars('c4_roar','inftfix');
			$inftimes += $sk_fix;
		}
		return $inftimes;
	}

	# 获取社团技能对伤害浮动的修正（新）
	function get_clbskill_fluc(&$pa,&$pd,$active)
	{
		#「直感」效果判定：
		if(isset($pa['skill_c2_intuit']))
		{
			$sk_lvl = get_skilllvl('c2_intuit',$pa);
			//获取伤害浮动加成
			$sk_fix = get_skillvars('c2_intuit','flucgain',$sk_lvl);
			return $sk_fix;
		}
		return 0;
	}

	# 计算社团技能对单个属性基础伤害的系数补正
	function get_clbskill_ex_base_dmg_r(&$pa,&$pd,$active,$key)
	{
		$ex_dmg_r = 1;
		# 「附魔」效果判定：
		if(isset($pa['skill_c3_enchant']))
		{
			$exdmgarr = get_skillvars('c3_enchant','exdmgarr');
			if(isset($exdmgarr[$key]) && !empty(get_skillpara('c3_enchant',$exdmgarr[$key],$pa['clbpara'])))
			{
				$ex_r = get_skillpara('c3_enchant',$exdmgarr[$key],$pa['clbpara']);
				$ex_dmg_r += $ex_r/100;
				//echo "【DEBUG】附魔使{$key}伤害提高了{$ex_r}%<br>";
			}
		}
		return $ex_dmg_r;
	}

	# 获取社团技能对单个属性基础伤害的定值补正
	function get_clbskill_ex_base_dmg_fix(&$pa,&$pd,$active,$key)
	{
		$ex_dmg_fix = 0;
		# 「歼灭」效果判定：
		if(isset($pa['skill_buff_annihil']))
		{
			global $ex_wep_dmg;
			$sk_var = round($pa['att']/$ex_wep_dmg[$key]);
			$ex_dmg_fix += $sk_var;
		}
		return $ex_dmg_fix;
	}

	# 真红暮特殊判定
	function attr_extra_19_crimson(&$pa,&$pd,$active,$phase=0)
	{
		global $log;

		# 真红暮作为防御方时的事件
		if($pd['type'] == 19 && $pd['name'] == '红暮' && $pa['wep_kind'] != $pd['wep_kind'] && $phase == 'defend')
		{	
			$log .= "<span class=\"red\">红暮身上的武器投射出了防护罩，轻松挡下了{$pa['nm']}的攻击！</span><br>";
			return 0;
		}

		# 真红暮作为进攻方时的事件：
		if($pa['type'] == 19 && $pa['name'] == '红暮' && $phase == 'attack')
		{
			$log .= "<span class=\"yellow\">“那么说好了，不留手咯~”<br></span>";
			//$log .= "红暮吐气扬声，向你袭来！<br>";
			
			if($pa['wep'] != '喷气式红杀重铁剑')
			{
				$event_dice=rand(1,6);
				$log .= "<span class=\"neonred\">只见红暮手上的巨大铁剑带着一条火光向你飞去。</span><br>";
				if($event_dice == 1)
				{
					get_inf_rev($pd,'w');
					get_inf_rev($pd,'u');
					$log .= "<span class=\"yellow\">你被赤红热风扫过，顿感头晕目眩，而且身上也起了火！</span><br>";
				}
				else
				{
					$log .= "<span class=\"lime\">不过你灵活地躲开了赤红热风！</span><br>";
				}
			}
			if($pa['wep'] != '绯红记忆')
			{
				$event_dice=rand(1,8);
				$log .= "<span class=\"neonred\">只见从红暮身边飞出来了一个红色的光球！</span><br>";
				if($event_dice==1)
				{
					$damage = min($pd['hp']-1,round($pd['mhp']*0.5)); //罪不至死
					$pd['hp'] -= $damage;
					//$log .= "<span class=\"yellow\">“虽说我不是什么超能力者，但是最高级的科技也和超能力无异了！”红暮大笑。</span><br>";
					$log .= "<span class=\"yellow\">红色的光球直击你的心脏！</span><br>";
					$log .= "这一发绯红锥心弹对你造成<span class=\"red\">$damage</span>点伤害！你感觉你半条命都没咯~<br>";
				}
				else
				{
					$log .= "<span class=\"lime\">你大呼不妙，连忙侧身躲过了这发绯红锥心弹！</span><br>";
				}
			}
			if($pa['wep'] != '血色强袭')
			{
				$event_dice=rand(1,4);
				$log .= "<span class=\"neonred\">红暮从背后抽出一把重炮，向你扣下了扳机！</span><br>";
				if($event_dice==1)
				{
					$wdamage=rand(5,40);
					weapon_loss($pd,$wdamage,1,1);
					get_inf_rev($pd,'a');
					$log .= "<span class=\"yellow\">这一发强袭追踪弹结实地打到了你手持武器的手上，你痛的龇牙咧嘴，武器也受到了损伤！</span><br>";
				}
				else
				{
					$log .= "<span class=\"lime\">你身形一矮，躲过了这发强袭追踪弹。</span><br>";
				}
			}
			if($pa['wep'] != '狮虎丝带')
			{
				$event_dice=rand(1,4);
				//$log .= "<span class=\"yellow\">红暮打了一个响指，从背后飞出来两条丝带！<br>“虽然这种玩意蓝凝应该用的更顺手吧……”</span><br>";
				$log .= "<span class=\"neonred\">红暮打了一个响指，从背后飞出来两条丝带！”</span><br>";
				if($event_dice==1)
				{
					$pd['sp'] = max(0,$pd['sp']-250);
					$log .= "<span class=\"yellow\">丝带将你缠绕，吸收了你的体力！</span><br>";
				}
				else
				{
					$log .= "<span class=\"lime\">你赶快腾跃躲避，两条丝带擦身而过！</span><br>";
				}
			}
			if($pa['wep'] != '落樱巨锤')
			{
				$event_dice=rand(1,6);
				//$log .= "<span class=\"yellow\">红暮高高一跃，跳到空中！<br>“询问淑女的体重固然很不礼貌，但我自然不是什么淑女！”</span><br>";
				$log .= "<span class=\"neonred\">红暮高高一跃，跳到空中！</span><br>";
				if($event_dice==1)
				{
					$pd['hp']-=1107;
					$log .= "<span class=\"yellow\">巨大的机甲一下便将你碾压！造成了<span class=\"red\">1107</span>点伤害！</span><br>";
				}
				else
				{
					$log .= "<span class=\"lime\">你在地上进行了一次翻滚，躲开了从天而降的机甲！</span><br>";
				}
			}
			if($pa['wep'] != '八八连流星浮游炮')
			{
				$event_dice=rand(1,6);
				//$log .= "<span class=\"yellow\">从红暮的机甲中发射出了大量的火箭弹！<br>“知道吗，量变终究会引起质变！”</span><br>";
				$log .= "<span class=\"neonred\">从红暮的机甲中发射出了大量的火箭弹！</span><br>";
				if($event_dice==1)
				{
					$log .= "<span class=\"yellow\">虽然火箭弹的精度颇低，但是大量的火箭弹还是对你的防具造成了可观的伤害！</span><br>";
					$adamage=rand(5,40);
					foreach(Array('arb','arh','ara','arf') as $ar)
					{
						if(!empty(${$ar.'s'})) armor_hurt($pd,$ar,$adamage,1);
					}
				}
				else
				{
					$log .= "<span class=\"lime\">然而飞弹的精度太低，你并没有被它们打中。</span><br>";
				}
			}
		}

		return NULL;
	}

	# 真蓝凝特殊判定
	function attr_extra_19_azure(&$pa,&$pd,$active,$phase=0)
	{
		global $db,$tablepre,$log;

		if($pd['type'] == 19 && $pd['name'] == '蓝凝')
		{
			$id = $pd['pid'];
			$dice = diceroll(100);
			$ttr="♪臻蓝之愿♪";
			$ttr2="♫钴蓝之灵♫";
			$ttr3="❀矢车菊的回忆❀";
			//$rp=18;
			//不要起这种会和玩家数据混淆的变量名啊喂！
			$rpls = $pa['pls'];
			if ($dice<5) $rpls=rand(1,33);

			$le=diceroll(200)+$pa['mhp']-100;
			if ($le>1001) $le=1001;
			$db->query("INSERT INTO {$tablepre}maptrap (itm, itmk, itme, itms, itmsk, pls) VALUES ('$ttr', 'TO', '$le', '1', '$id', '$rpls')");

			$le=rand(1,200)+$pa['final_damage']-100;
			if ($le>2000) $le=2000;
			$db->query("INSERT INTO {$tablepre}maptrap (itm, itmk, itme, itms, itmsk, pls) VALUES ('$ttr2', 'TO', '$le', '1', '$id', '$rpls')");

			$le=rand(1,$pa['hp']);
			$db->query("INSERT INTO {$tablepre}maptrap (itm, itmk, itme, itms, itmsk, pls) VALUES ('$ttr3', 'TO', '$le', '1', '$id', '$rpls')");
	
			$log .= "从蓝凝的身边飞出了数个光球，散布在了战场上！<br>";
		}
		return;
	}

	# 电子狐特殊判定
	function attr_extra_89_efox(&$pa,&$pd,$active,$phase=0)
	{
		global $log;

		# 进攻方(pa)为米娜
		if ($pa['type'] == 89 && $pa['name'] == '电掣部长 米娜')
		{
			$log .= "<span class=\"yellow\">米娜的双眼突然闪耀了起来！</span><br>
			<span class=\"neonblue\">“侦测到敌意实体，开始扫描~”</span><br>";
			$dice = diceroll(1024);
			//$log .= "<span class=\"yellow\">【DEBUG】骰子检定结果：<span class=\"red\">$dice</span>/1024。</span><br>";
			if($dice<=126)//8%
			{ 
				$log .= "<span class=\"yellow\">“似乎米娜具现化了你的武器！”</span><br>
				<span class=\"neonblue\">“你的<span class=\"red\">{$pd['wep']}</span>，我就收下了！”</span><br>";
				$pa['wep'] = $pd['wep']; $pa['wepk'] = $pd['wepk']; $pa['wepsk'] = $pd['wepsk'];
				$pa['wepe'] = $pd['wepe']; $pa['weps'] = $pd['weps']; 
				get_wep_kind($pa);
			}
			elseif($dice<=635)
			{
				$dice2 = rand(1,5);
				$log .= "<span class=\"yellow\">“似乎米娜扫描了你的武器！”</span><br>
				<span class=\"neonblue\">“你的<span class=\"red\">{$pd['wep']}</span>，已扫描入<span class=\"red\">$dice2</span>号位。”<br>
				“我会妥善保管的~”</span><br>";
				$pa['itm'.$dice2] = $pd['wep']; $pa['itmk'.$dice2] = $pd['wepk']; $pa['itmsk'.$dice2] = $pd['wepsk'];
				$pa['itme'.$dice2] = $pd['wepe']; $pa['itms'.$dice2] = $pd['weps']; 
			}
			elseif($dice>=1024)  // 1/1024 几率直接抢夺玩家全部背包
			{
				$log .= "<span class=\"yellow\">哎呀，骰子检定结果是大·失·败！</span><br>";
				$log .= "<span class=\"yellow\">“米娜将你的全身扫描了个遍！”</span><br>
				<span class=\"neonblue\">“我判定你身上的东西放到我身上可能更好一点~”<br>
				“我会妥善保管的~”</span><br>";
				for($i=1;$i<=6;$i++)
				{
					if(!empty($pd['itms'.$i]))
					{
						//复制
						$pa['itm'.$i] = $pd['itm'.$i]; $pa['itmk'.$i] = $pd['itmk'.$i]; $pa['itmsk'.$i] = $pd['itmsk'.$i];
						$pa['itme'.$i] = $pd['itme'.$i]; $pa['itms'.$i] = $pd['itms'.$i]; 
						//哎哟喂啊，真是倒霉，但这就是人生啊。
						$pd['itm'.$i] =  $pd['itmk'.$i] =  $pd['itmsk'.$i] = '';
						$pd['itme'.$i] =  $pd['itms'.$i] = 0;
					}
				}
			}
			else
			{
				$log .= "<span class=\"yellow\">不过似乎什么都没发生！</span><br>
				<span class=\"neonblue\">“扫描失败了么……”</span><br>";
			}
		}
		return;
	}

	# 书中虫特殊判定
	function attr_extra_89_bookworm(&$pa,&$pd,$active,$phase=0)
	{
		global $log;

		if($pd['type'] == 89)
		{
			$rp_up = 0; $dmg_p = -1;
			if($pd['name'] == '高中生·白神')
			{
				if($phase == 'rp')
				{
					$log .= "<span class=\"yellow\">“你真的愿意对这个手无寸铁的高中女生下手么？”</span><br>";
					$dice = diceroll(444);
					if($dice<=200){
						$log .= "<span class=\"neonblue\">“你感觉到了罪恶感。”</span><br>";
					}else{
						$log .= "<span class=\"neonblue\">“你不该这么做的。”</span><br>";
					}
					$rp_up = $pa['rp'] + $dice;
				}
				elseif($phase == 'defend')
				{
					if ($pa['original_dmg'] > 400)
					{
						$log .= "<span class=\"yellow\">白神从裙底抽出了她的名为WIN MAX 2的微型电脑！<br>“哪能这样被你干打？”</span><br>";
						$log .= "<span class=\"yellow\">白神的高超黑客技术大幅度降低了你造成的伤害！</span><br>";
						$dmg_p = 0.005;
					}
				}
			}
			if ($pd['name'] == '白神·讨价还价')
			{
				if($phase == 'rp')
				{
					$dice = diceroll(1777);
					$log .= "<span class=\"yellow\">“对面似乎真的没有敌意，你还是决定要下手么？”</span><br>";
					if($dice<=200){
						$log .= "<span class=\"neonblue\">“你感觉到了罪恶感。”</span><br>";
					}elseif($dice<=400){
						$log .= "<span class=\"neonblue\">“你不该这么做的。”</span><br>";
					}else{
						$log .= "<span class=\"neonblue\">“罪恶感爬上了你的脊梁！”</span><br>";
					}
					$rp_up = $pa['rp'] + $dice;
				}
				elseif($phase == 'defend')
				{
					if ($pa['original_dmg'] > 400)
					{
						$log .= "<span class=\"yellow\">白神从裙底抽出了她的名为DECK的微型电脑！<br>“哪能这样被你干打？”</span><br>";
						$log .= "<span class=\"yellow\">白神的高超黑客技术大幅度降低了你造成的伤害！</span><br>";
						$dmg_p = 0.005;
					}
				}
			}
			if ($pd['name'] == '白神·接受')
			{
				if($phase == 'rp')
				{
					$dice = rand(1777,4888);
					$log .= "<span class=\"yellow\">“你对一位毫无反抗能力，并且已经表示无敌意的女高中生横下死手。”</span><br>";
					$log .= "<span class=\"neonblue\">“希望你的良心还能得以安生。”</span><br>";
					//$log .= "<span class=\"neonblue\">“【DEBUG】你的rp上升了<span class=\"red\">$dice</span>点。”</span><br>";
					$rp_up = $pa['rp'] + $dice;
				}
			}
			//结算rp上升事件
			if($phase == 'rp' && $rp_up > 0) $pa['rp'] = $pa['rp'] + $rp_up;
			//返回一个伤害系数
			if($phase == 'defend' && $dmg_p > 0) return $dmg_p;
		}
		return;
	}

	# 百命猫特殊判定
	function attr_extra_89_100lifecat(&$pa,&$pd,$active,$phase=0)
	{
		if ($pa['type'] == 89 && $pa['name']=='是TSEROF啦！')
		{ 
			if($pa['lvl'] < 255) $pa['lvl']++;
			if($pa['rage'] < 255) $pa['rage']++;
		}
		elseif ($pd['type'] == 89 && $pd['name']=='是TSEROF啦！')
		{
			if($pd['lvl'] < 255) $pd['lvl']++;
			if($pd['rage'] < 255) $pd['rage']++;
		}
		return;
	}

	# 笼中鸟特殊判定
	function attr_extra_89_cagedbird(&$pa,&$pd,$active,$phase=0)
	{
		global $log;

		if($pa['type'] == 89 && $pa['name'] =='笼中鸟')
		{
			if($pa['statusa'] < 3)
			{
				$continue_flag = 0;
				//70%几率吸收玩家HP值成为自己的HP和SP值，SP值上升到一定程度时变身，变身后各种数值直接膨胀。三段变身。
				$log .= "<span class=\"yellow\">“笼中鸟含情脉脉地看着你！”</span><br>";
				$dice=diceroll(20);
				//$log .= "<span class=\"yellow\">【DEBUG】骰子检定结果：<span class=\"red\">$dice</span>。</span><br>";
				if($dice>=14)
				{
					$log .= "<span class=\"yellow\">“你感觉你的生命被她汲取，但同时更有一种奇怪的暖洋洋的舒畅感。”</span><br>";
					//继续投d20，1~10吸收30%，11~19吸收65%，大失败直接吸到1。
					$dice2=rand(1,20);
					//$log .= "<span class=\"yellow\">【DEBUG】骰子2检定结果：<span class=\"red\">$dice2</span>。</span><br>";
					if($dice2<=10){
						$log .= "<span class=\"yellow\">“你稍微稳了稳身形，似乎问题不是很严重。”</span><br>";
						$gain = $pd['hp'] * 0.3;
					}elseif($dice2<=19){
						$log .= "<span class=\"yellow\">“你觉得头晕目眩。”</span><br>";
						$gain = $pd['hp'] * 0.65;
					}elseif($dice2>=20){
						$log .= "<span class=\"yellow\">哎呀，骰子检定结果是大·失·败！</span><br>";
						//哎哟喂啊，真是倒霉，但这就是人生啊。
						$log .= "<span class=\"yellow\">“你整个人都倒了下去，不过想到你的生命力将要打开她的镣铐，这让你充满了决心。”</span><br>";
						$gain = $pd['hp'] - 1;
						$pd['def'] = $pd['def'] + ($gain * 0.25);
					}
					$pa['hp'] = $pa['hp'] + ($gain * 30);
					$pa['mhp']= $pa['mhp'] + ($gain * 30);
					$pa['msp'] = $pa['msp'] + ($gain * 30);
					$pd['hp'] = round($pd['hp'] - $gain);
					$pd['rp'] = round($pd['rp'] - $gain);
					$continue_flag = 1;
				}
				else
				{
					$log .= "<span class=\"yellow\">“不过什么也没有发生！”</span><br>";
				}
			}
			//处理直接变身 在$pa['statusa']加了个限定条件 不然无限变身了
			if($pa['msp']> 5003 && $pa['statusa'] == 0){
				$log .= "<span class=\"yellow\">“笼中鸟的枷锁被打破了一些。”</span><br>";
				$pa['statusa'] = 1;
				$pa['mhp'] = $pa['mhp'] * 5; $pa['hp'] = $pa['hp'] * 5; $pa['wf'] = $pa['wf'] * 5; $pa['att'] = $pa['att'] * 5; $pa['def'] = $pa['def'] * 5;
			}elseif($pa['msp'] > 13377 && $pa['statusa'] == 1){
				$log .= "<span class=\"yellow\">“笼中鸟的枷锁被打破了一些。”</span><br>";
				$pa['statusa'] = 2;
				$pa['mhp'] = $pa['mhp'] * 10; $pa['hp'] = $pa['hp'] * 10; $pa['wf'] = $pa['wf'] * 10; $pa['att'] = $pa['att'] * 10; $pa['def'] = $pa['def'] * 10;
			}elseif($pa['msp'] > 33777 && $pa['statusa'] == 2){
				$log .= "<span class=\"yellow\">“笼中鸟的枷锁被完全打破了！”</span><br>";
				$pa['statusa'] = 3;
				$pa['mhp'] = $pa['mhp'] * 30; $pa['hp'] = $pa['hp'] * 30; $pa['wf'] = $pa['wf'] * 30; $pa['att'] = $pa['att'] * 30; $pa['def'] = $pa['def'] * 30;
				$pa['name'] = $pa['nm'] = "完全解放的鸟儿";
			}
			//成功喂养笼中鸟会跳过战斗
			if($continue_flag) return -1;
		}
		return 0;
	}

	#走地羊特殊判定
	function attr_extra_89_walksheep(&$pa,&$pd,$active,$phase=0)
	{
		global $log;

		if($pa['type'] == 89 && $pa['name'] == '坚韧之子·拉姆')
		{
			$event_dice=diceroll(99);
			if($event_dice >=30)
			{
				$log .= "<span class=\"neonblue\">“我这双拳头……很强……很厉害……咚咚打你……”</span><br>";
				$damage=rand(5,40);
				if(!empty($pd['wepe']) && $pd['wepk']!='WN')
				{
					$log .= "攻击使得<span class=\"red\">{$pd['wep']}</span>的效果下降了<span class=\"red\">$damage</span>点！<br>";
					$loss_flag = weapon_loss($pd,$damage,1,1);
					if($loss_flag < 0)
					{
						$pa['money'] = $pa['money'] + ($damage * 120);
					}
				}
				foreach(Array('arb','arh','ara','arf') as $ar)
				{
					if(!empty($pd[$ar.'s']))
					{
						$loss_flag = armor_hurt($pd,$ar,$damage,1);
						if($loss_flag < 0)
						{
							$pa['money'] = $pa['money'] + ($damage * 60);
						}
					}
				}
				$w_money = $w_money + ($damage * 30);
				get_inf_rev($pd,'a');
				get_inf_rev($pd,'f');
				$log .= "致伤攻击使你的<span class=\"red\">腕部</span>和<span class=\"red\">足部</span>受伤了！<br>";
			}
		}
		return;
	}

	# 迷你蜂特殊判定
	function attr_extra_89_minibee(&$pa,&$pd,$active,$phase=0)
	{
		global $log;

		if ($pa['type'] == 89 && $pa['name'] == '诚心使魔·阿摩尔') // 迷你蜂
		{ 
			$log .= "<span class=\"neonblue\">“这只小蜜蜂勇敢地朝你袭来！”</span><br>";
			$dice = diceroll(4);
			if($dice == 0){
				$log .= "<span class=\"yellow\">魔法蜂针朝你刺来！造成了<span class=\"red\">250</span>点伤害！并且使你<span class=\"red\">麻痹</span>了！</span><br>";
				$dmg = 250;
				get_inf_rev($pd,'e');
			}elseif($dice == 1){
				$log .= "<span class=\"yellow\">幻惑花粉朝你扑来！造成了<span class=\"red\">250</span>点伤害！并且使你<span class=\"red\">混乱</span>了！</span><br>";
				$dmg = 250;
				get_inf_rev($pd,'w');
			}elseif($dice == 2){
				$log .= "<span class=\"yellow\">凶猛翼击朝你袭来！造成了<span class=\"red\">250</span>点伤害！并且使你<span class=\"red\">炎上</span>了！</span><br>";
				$dmg = 250;
				get_inf_rev($pd,'u');
			}elseif($dice == 3){
				$log .= "<span class=\"yellow\">剧毒蜂针朝你刺来！造成了<span class=\"red\">250</span>点伤害！并且使你<span class=\"red\">中毒</span>了！</span><br>";
				$dmg = 250;
				get_inf_rev($pd,'p');
			}else{
				$log .= "<span class=\"yellow\">体当冲刺朝你袭来！造成了<span class=\"red\">550</span>点伤害！<br>";
				$dmg = 250;
			}
			return $dmg;
		}
		return;
	}

?>