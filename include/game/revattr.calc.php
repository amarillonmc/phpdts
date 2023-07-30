<?php
namespace revattr
{

	if(!defined('IN_GAME')) {
		exit('Access Denied');
	}

	# 获取社团技能对武器射程的修正
	function get_clbskill_wep_range(&$pa,$range)
	{
		#「穿杨」效果判定：
		if(isset($pa['bskill_c4_sniper']))
		{
			//获取射程加成
			$sk_rn = get_skillvars('c4_sniper','rangegain');
			$range += $sk_rn;
		}
		
		#「妙手」效果判定：
		if(isset($pa['bskill_tl_pickpocket']))
		{
			//射程与灵系相同
			$range = 1;
		}
		return $range;
	}

	# 获取社团技能对武器熟练度的修正
	function get_clbskill_wep_skill(&$pa,$wep_skill)
	{
		# 「天威」技能判定
		if(isset($pa['bskill_c6_godpow']))
		{
			$sk_fix = min($pa['rage']+($pa['lvl']/6),get_skillvars('c6_godpow','skmax'));
			if(!empty($sk_fix))
			{
				$wep_skill += $sk_fix;
				$pa['bskilllog2'] .='<span class="yellow">「天威」使'.$pa['nm'].'的熟练度暂时增加了'.ceil($sk_fix).'点！</span><br>';
			}
		}

		#「人杰」技能判定
		if(isset($pa['skill_c11_renjie']))
		{
			foreach(Array('wp','wk','wc','wg','wd','wf') as $skw) $wep_skill = max($pa[$skw],$wep_skill);
		}
		
		return $wep_skill;
	}

	# 获取社团技能对基础命中率的修正（新）
	function get_clbskill_hitrate(&$pa,&$pd,$active,$hitrate)
	{
		# 加成：
		#「潜能」、「暗杀」必中效果判定：
		if(isset($pa['bskill_c3_potential']) || isset($pa['skill_buff_assassin']))
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
		if(isset($pa['bskill_c4_aiming']))
		{
			$sk_r = 1 + (get_skillvars('c4_aiming','accgain') / 100);
			$hitrate *= $sk_r;
		}
		#「穿杨」效果判定：
		if(isset($pa['bskill_c4_sniper']))
		{
			$sk_r = 1 + (get_skillvars('c4_sniper','accgain') / 100);
			$hitrate *= $sk_r;
		}
		#「天运」效果判定：
		if(isset($pa['skill_c6_godluck']))
		{
			$sk_r = get_skillpara('c6_godluck','accgain',$pa['clbpara']);
			if(!empty($sk_r))
			{
				$sk_r = 1 + ($sk_r / 100);
				$hitrate *= $sk_r;
			}
		}
		#「洞察」效果判定：
		if(isset($pa['skill_c10_insight']))
		{
			$sk_lvl = get_skilllvl('c10_insight',$pa);
			$sk_r = 1 + (get_skillvars('c10_insight','accgain',$sk_lvl) / 100);
			$hitrate *= $sk_r;
		}

		# 减益：
		#「枭眼」效果判定：
		if(isset($pd['skill_c3_hawkeye']))
		{
			$sk_r = 1 - (get_skillvars('c3_hawkeye','accloss') / 100);
			$hitrate *= $sk_r;
		}
		#「灵力」效果判定：
		if(isset($pd['skill_c9_spirit']))
		{
			$sk_lvl = get_skilllvl('c9_spirit',$pd);
			$sk_r = 1 - (get_skillvars('c9_spirit','accloss',$sk_lvl) / 100);
			$hitrate *= $sk_r;
		}
		#「天运」效果判定：
		if(isset($pd['skill_c6_godluck']))
		{
			$sk_r = get_skillpara('c6_godluck','accloss',$pd['clbpara']);
			if(!empty($sk_r))
			{
				$sk_r = 1 - ($sk_r / 100);
				$hitrate *= $sk_r;
			}
		}
		#「洞察」效果判定：
		if(isset($pd['skill_c10_insight']))
		{
			$sk_lvl = get_skilllvl('c10_insight',$pd);
			$sk_r = 1 - (get_skillvars('c10_insight','accloss',$sk_lvl) / 100);
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
		#「天运」效果判定：
		if(isset($pa['skill_c6_godluck']))
		{
			$sk_r = get_skillpara('c6_godluck','rbgain',$pa['clbpara']);
			if(!empty($sk_r))
			{
				$sk_r = 1 + ($sk_r / 100);
				$hitrate *= $sk_r;
			}
		}

		# 减益：
		#「枭眼」效果判定：
		if(isset($pd['skill_c3_hawkeye']))
		{
			$sk_r = 1 - (get_skillvars('c3_hawkeye','rbloss') / 100);
			$hitrate *= $sk_r;
		}
		#「灵力」效果判定：
		if(isset($pd['skill_c9_spirit']))
		{
			$sk_lvl = get_skilllvl('c9_spirit',$pd);
			$sk_r = 1 - (get_skillvars('c9_spirit','rbloss',$sk_lvl) / 100);
			$hitrate *= $sk_r;
		}
		#「天运」效果判定：
		if(isset($pd['skill_c6_godluck']))
		{
			$sk_r = get_skillpara('c6_godluck','rbloss',$pd['clbpara']);
			if(!empty($sk_r))
			{
				$sk_r = 1 - ($sk_r / 100);
				$hitrate *= $sk_r;
			}
		}
		#「洞察」效果判定：
		if(isset($pd['skill_c10_insight']))
		{
			$sk_lvl = get_skilllvl('c10_insight',$pd);
			$sk_r = 1 - (get_skillvars('c10_insight','rbloss',$sk_lvl) / 100);
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
		if(isset($pa['bskill_c4_roar']))
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

	# 背水HP系数
	function calc_enmity_losshpr(&$pa,&$pd)
	{
		$hpr = 1 - ($pa['hp']/$pa['mhp']);
		$r = (1 + 2*$hpr) * $hpr;
		return $r;
	}

	# 坚守HP系数
	function calc_garrison_losshpr(&$pa,&$pd)
	{
		$hpr = 1 - ($pa['hp']/$pa['mhp']);
		$r = -1 * pow($hpr,3) + 4 * $hpr;
		return $r;
	}
	
	//计算单个属性伤害上限
	function get_ex_base_dmg_max(&$pa,&$pd,$active,$ex,$ex_dmg)
	{
		global $ex_max_dmg;
		# 「过载」效果判定：
		if($ex == 'e' && isset($pa['skill_c7_overload'])) return $ex_dmg;
		if($ex_max_dmg[$ex]>0 && $ex_dmg>$ex_max_dmg[$ex]) $ex_dmg = $ex_max_dmg[$ex];
		return $ex_dmg;
	}

	//计算单个属性伤害系数变化
	function get_ex_base_dmg_p(&$pa,&$pd,$active,$ex,$ex_dmg)
	{
		global $ex_good_wep,$ex_inf,$ex_inf_punish,$exdmginf,$exdmgname,$log;
		# 「高能」效果判定：
		if(isset($pa['bskill_c5_higheg']) && $ex == 'd')
		{
			$log.="<span class='yellow'>「高能」使{$pa['nm']}造成的爆炸伤害不受影响！</span><br>";
			return $ex_dmg;
		}
		# 「死疗」效果判定（不会受其他技能加成）：
		if(isset($pd['skill_c8_deadheal']) && $ex == 'p')
		{
			$sk_p = get_skillvars('c8_deadheal','exdmgr');
			$ex_dmg = min($pd['mhp']-$pd['hp'],ceil($ex_dmg*($sk_p/100)));
			$pd['hp'] += $ex_dmg;
			$log .= "<span class='purple'>{$pd['nm']}从毒雾中汲取养分，恢复了<span class='lime'>{$ex_dmg}</span>点生命！</span><br>";
			return 0;
		}
		//计算社团技能对单个属性伤害的系数补正
		$ex_dmg *= get_clbskill_ex_base_dmg_r($pa,$pd,$active,$ex);
		//计算社团技能对单个属性伤害的补正
		$ex_dmg += get_clbskill_ex_base_dmg_fix($pa,$pd,$active,$ex);
		//计算已经进入的异常状态对属性攻击伤害的影响
		if(isset($ex_inf[$ex]) && strpos($pd['inf'],$ex_inf[$ex])!==false && isset($ex_inf_punish[$ex]))
		{
			$ex_dmg *= $ex_inf_punish[$ex];
			$pa['ex_dmgpsh_log'] .= "由于{$pd['nm']}已经{$exdmginf[$ex_inf[$ex]]}，{$exdmgname[$ex]}的伤害";
			$pa['ex_dmgpsh_log'] .= $ex_inf_punish[$ex]>1 ? "增加了！" : "减少了！";
		}
		//计算属性伤害是否被防御
		if(!empty($pd['ex_def_flag']) && ($pd['ex_def_flag'] == 1 || (is_array($pd['ex_def_flag']) && in_array($ex,$pd['ex_def_flag']))))
		{
			$ex_dmg = round($ex_dmg*0.5);
			$pa['ex_dmgdef_log'] = 1;
		}
		# 「催化」效果计数：
		if(isset($pa['bskill_c8_catalyst']) && $ex == 'p')
		{
			$pa['bskill_c8_catalyst'] ++;
		}
		return $ex_dmg;
	}

	# 计算社团技能对单个属性基础伤害的系数补正
	function get_clbskill_ex_base_dmg_r(&$pa,&$pd,$active,$key)
	{
		$ex_dmg_r = 1;
		# 「附魔」效果判定：
		if(isset($pa['bskill_c3_enchant']))
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
		# 「磁暴」效果判定：
		if(isset($pa['bskill_c7_electric']) && $key == 'e')
		{
			$sk_var = get_skillvars('c7_electric','exdmgfix');
			$ex_damage_fix += $sk_var;
		}
		return $ex_dmg_fix;
	}
}
?>