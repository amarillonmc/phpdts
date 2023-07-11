<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}

function init_icon_states(&$pa,$pd,$ismeet=0)
{
	global $sexinfo,$typeinfo,$fog;
	# 「天眼」技能判定
	if(!check_skill_unlock('c6_godeyes',$pd)) $ismeet = 1;
	//雾天显示？？？
	if($fog && !$ismeet)
	{
		$pa['typeinfo'] = '？？？';
		$pa['sNoinfo'] = '';
		$pa['iconImg'] = 'question.gif';
		$pa['iconImgB'] = '';
		return;
	}
	//更新编号情报
	$pa['sNoinfo'] = "(".$sexinfo[$pa['gd']].$pa['sNo']."号)";
	$pa['typeinfo'] = $typeinfo[$pa['type']];
	
	# 更新头像情报
	# 检查是否存在固定头像
	if(file_exists('img/'.$pa['icon']))
	{
		$iconImg = $pa['icon'];
	}
	else 
	{
		$itype = $pa['type'] > 0 ? 'n' : $pa['gd'];
		$iconImg = $itype.'_'.$pa['icon'].'.gif';
	}
	# 检查是否存在大头像
	$iconImgB = str_replace('.','a.',$iconImg);
	if(file_exists('img/'.$iconImgB))
	{
		$pa['iconImgB'] = $iconImgB;
	}
	else 
	{
		$pa['iconImg'] = $iconImg;
	}
}

function init_single_hp_states($pa)
{
	global $hpinfo;
	if($pa['hp'] <= 0)
	{
		$hpstate = "<span class=\"red\">$hpinfo[3]</span>";
	}
	else
	{
		if($pa['hp'] < $pa['mhp']*0.2) 
		{
			$hpstate = "<span class=\"red\">$hpinfo[2]</span>";
		} 
		elseif($pa['hp'] < $pa['mhp']*0.5) 
		{
			$hpstate = "<span class=\"yellow\">$hpinfo[1]</span>";
		} 
		else 
		{
			$hpstate = "<span class=\"clan\">$hpinfo[0]</span>";
		}
	}
	return $hpstate;
}

function init_hp_states(&$pa,$pd,$ismeet=0)
{
	global $fog,$hpinfo,$spinfo,$rageinfo;
	# 「护盾」数值显示
	if(!check_skill_unlock('buff_shield',$pa))
	{
		$pa['shield_info'] = "<span class=\"blueseed\" tooltip2=\"【护盾】：可抵消等同于护盾值的伤害。护盾值只在抵消属性伤害时消耗，抵消电击伤害时双倍消耗。护盾存在时不会受到反噬伤害或陷入异常状态。\">(".get_skillpara('buff_shield','svar',$pa['clbpara']).")</span>";
	}
	# 「天眼」技能判定
	if(!check_skill_unlock('c6_godeyes',$pd))
	{
		$pa['hpstate'] = $pa['hp'].' / '.$pa['mhp'];
		$pa['spstate'] = $pa['sp'].' / '.$pa['msp'];
		$pa['ragestate'] = $pa['rage'];
		return;
	}
	if($fog && !$ismeet)
	{
		$pa['hpstate'] = '？？？';
		$pa['spstate'] = '？？？';
		$pa['ragestate'] = '？？？';
		return;
	}
	if($pa['hp'] <= 0)
	{
		$pa['hpstate'] = "<span class=\"red\">$hpinfo[3]</span>";
		$pa['spstate'] = "<span class=\"red\">$spinfo[3]</span>";
		$pa['ragestate'] = "<span class=\"red\">$rageinfo[3]</span>";
	}
	else
	{
		/*if($pa['hp'] < $pa['mhp']*0.2) 
		{
			$pa['hpstate'] = "<span class=\"red\">$hpinfo[2]</span>";
		} 
		elseif($pa['hp'] < $pa['mhp']*0.5) 
		{
			$pa['hpstate'] = "<span class=\"yellow\">$hpinfo[1]</span>";
		} 
		else 
		{
			$pa['hpstate'] = "<span class=\"clan\">$hpinfo[0]</span>";
		}*/

		$pa['hpstate'] = init_single_hp_states($pa);

		if($pa['sp'] < $pa['msp']*0.2) 
		{
			$pa['spstate'] = $spinfo[2];
		} 
		elseif($pa['sp'] < $pa['msp']*0.5) 
		{
			$pa['spstate'] = $spinfo[1];
		} 
		else 
		{
			$pa['spstate'] = $spinfo[0];
		}

		if($pa['rage'] >= 100) 
		{
			$pa['ragestate'] = "<span class=\"red\">$rageinfo[2]</span>";
		} 
		elseif($pa['rage'] >= 30) 
		{
			$pa['ragestate'] = "<span class=\"yellow\">$rageinfo[1]</span>";
		} 
		else 
		{
			$pa['ragestate'] = $rageinfo[0];
		}
	}
}

function init_wep_states(&$pa,$pd,$ismeet=0)
{
	global $wepeinfo,$fog;
	# 「天眼」技能判定
	if(!check_skill_unlock('c6_godeyes',$pd))
	{
		$pa['wepestate'] = $pa['wepe'];
		$pa['wep_words'] = parse_nameinfo_desc($pa['wep'],$pa['horizon']);
		$pa['wepk_words'] = parse_kinfo_desc($pa['wepk'],$pa['wepsk']);
		return;
	}
	if($fog && !$ismeet)
	{
		$pa['wepestate'] = '？？？';
		$pa['wep_words'] = '？？？';
		$pa['wepk_words'] = '？？？';
		return;
	}
	if($pa['wepe'] >= 400)
	{
		$pa['wepestate'] = $wepeinfo[3];
	}
	elseif($pa['wepe'] >= 200)
	{
		$pa['wepestate'] = $wepeinfo[2];
	}
	elseif($pa['wepe'] >= 60)
	{
		$pa['wepestate'] = $wepeinfo[1];
	}
	else 
	{
		$pa['wepestate'] = $wepeinfo[0];
	}

	//更新武器名、武器类别情报
	$pa['wep_words'] = parse_nameinfo_desc($pa['wep'],$pa['horizon']);
	$pa['wepk_words'] = parse_kinfo_desc($pa['wepk'],$pa['wepsk']);
}

function init_inf_states(&$pa,$pd,$ismeet=0)
{
	global $infinfo,$poseinfo,$tacinfo,$fog,$posetips,$tactips;;
	# 「天眼」技能判定
	if(!check_skill_unlock('c6_godeyes',$pd)) $ismeet = 1;
	if($fog && !$ismeet)
	{
		$pa['nameinfo'] = '？？？';
		$pa['lvlinfo'] = '？？？';
		$pa['poseinfo'] = '？？？';
		$pa['tacinfo'] = '？？？';
		$pa['infdata'] = '？？？';
		return;
	}
	$pa['nameinfo'] = $pa['name'];
	$pa['lvlinfo'] = 'Lv. '.$pa['lvl'];
	$pa['poseinfo'] = "<span tooltip=\"{$posetips[$pa['pose']]}\">".$poseinfo[$pa['pose']]."</span>";
	$pa['tacinfo'] = "<span tooltip=\"{$tactips[$pa['tactic']]}\">".$tacinfo[$pa['tactic']]."</span>";
	//更新受伤状态
	if($pa['inf']) 
	{
		$pa['infdata'] = '';
		foreach ($infinfo as $inf_ky => $inf_nm) 
		{
			if(strpos($pa['inf'],$inf_ky) !== false) $pa['infdata'] .= $inf_nm;	
		}
	}
	else 
	{
		$pa['infdata'] = '无';
	}
}

function init_friedship_states($pa,$sk,$mid)
{
	if(isset($pa['clbpara']['skillpara'][$sk]['coverp'][$mid]))
	{
		$fs = $pa['clbpara']['skillpara'][$sk]['coverp'][$mid];
		if($fs > 80)
		{
			$desc = "<span class='gold'>(崇拜)</span>";
		}
		elseif($fs > 65)
		{
			$desc = "<span class='redseed'>(尊敬)</span>";
		}
		elseif($fs > 50)
		{
			$desc = "<span class='lime'>(友善)</span>";
		}
		elseif($fs > 35)
		{
			$desc = "<span class='clan'>(友好)</span>";
		}
		elseif($fs > 20)
		{
			$desc = "<span>(普通)</span>";
		}
		else
		{
			$desc = "<span class='grey'>(冷淡)</span>";
		}
		return $desc;
	}
}


?>
