<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}

include GAME_ROOT.'./bot/revbot.config.php';

# bot初始化
function bot_player_valid($vnums)
{
	global $validnum,$alivenum,$now,$db,$tablepre,$gamevars;
	global $bot_player_list;

	$bot_nums = count($bot_player_list['sub']);
	if(!empty($vnums) && !empty($bot_nums))
	{
		$ids = Array();
		for($i=0;$i<$vnums;$i++)
		{
			$bot = rand(0,$bot_nums-1);
			$bots = array_merge($bot_player_list,$bot_player_list['sub'][$bot]);
			unset($bots['sub']);
			extract($bots);
			$validnum++;
			$alivenum++;
			$endtime = $validtime = $now;
			$sNo = $validnum;
			$hp = $mhp;
			$sp = $msp;
			$pls = 0;
			$killnum = 0;
			$lvl = 0;
			$exp = $areanum * 20;
			$money = 20;
			$rage = 0;
			$pose = 3;
			$tactic = 2;
			$pass = 'bot';
			$state = 0;
			$bid = 0;
			$inf = $teamID = $teamPass = '';
			$ndata = update_db_player_structure();
			foreach($ndata as $key)
			{
				if(isset($$key)) $ndata[$key] = $$key; 
			}
			include_once GAME_ROOT.'./include/game/clubslct.func.php';
			if(!empty($club)) changeclub($club,$ndata);
			$ndata['clbpara']['botphase'] = 0; $ndata['clbpara']['botact']['sitm'] = 0;
			$ndata = player_format_with_db_structure($ndata);
			if(!empty($ndata)) $db->array_insert("{$tablepre}players", $ndata);
			$ids[] = $db->insert_id();
			# 初始化头衔tooltip
			include_once GAME_ROOT.'./include/game/titles.func.php';
			$nickinfo = get_title_desc($nick);
			addnews($now,'newpc',$nickinfo.' '.$name,"{$sexinfo[$gd]}{$sNo}号",$ip,$nick);
		}
		return $ids;
	}
}

# bot行为
function bot_acts($id)
{
	global $log;

	$bdata = fetch_playerdata_by_pid($id);
	if($bdata && $bdata['hp'] > 0)
	{
		# 先检查是否需要使用道具
		bot_use_items($bdata);
		# 在行动前检查一遍行动策略
		bot_pre_act_check($bdata);
		# 判定bot是否需要移动：
		include_once GAME_ROOT.'./include/game/search.func.php';
		if(isset($bdata['clbpara']['botact']['moveto']) && $bdata['clbpara']['botact']['moveto'] != $bdata['pls'])
		{
			$flag = move($bdata['clbpara']['botact']['moveto'],$bdata);
		}
		else 
		{
			$flag = search($bdata);
		}	
		if(isset($bdata['clbpara']['botact']['moveto']))
		{
			unset($bdata['clbpara']['botact']['moveto']);
		}
		echo $log;
		echo "bot {$bdata['name']} 行动完成。";
		player_save($bdata);
		return 1;
	}
	else 
	{
		return 0;
	}
}

# bot在行动前检查策略
function bot_pre_act_check(&$pa)
{
	global $db,$tablepre;
	global $noisepls;
	global $bot_moveto_phase,$bot_action_phase;

	$plslist = get_safe_plslist();

	# -1.存在异常状态，花1点技能点解除下异常状态
	if(!empty($pa['inf']) && $pa['skillpoint'])
	{
		$pa['skillpoint'] --;
		$pa['inf'] = '';
		$pa['hp'] = $pa['mhp']; $pa['sp'] = $pa['msp'];
	}

	# 0.探索阶段：刚刚入场，目标是寻找合成素材
	if($pa['clbpara']['botphase'] == 0)
	{
		if(!bot_check_can_mixitem($pa))
		{
			# 将目标地图设置为寻物所需位置
			$bmoveto = $pa['pls'] == $bot_moveto_phase[$pa['club']] ? NULL : $bot_moveto_phase[$pa['club']];
			# 能否到达指定寻物位置？
			if((isset($bmoveto) && !in_array($bmoveto,$plslist)) || $pa['clbpara']['botact']['stimes'] >= 100)
			{
				unset($bmoveto);
				# 被禁了！那只能黑幕一个了（
				if($pa['club'] == 1)
				{
					$pa['clbpara']['botmix']['c11'] = 1; $pa['clbpara']['botmix']['c12'] = 1;
				}
				elseif($pa['club'] == 2)
				{
					$pa['clbpara']['botmix']['c21'] = 1;
				}
				elseif($pa['club'] == 3)
				{
					$pa['clbpara']['botmix']['c31'] = 1; $pa['clbpara']['botmix']['c32'] = 1; $pa['clbpara']['botmix']['c33'] = 1;
				}
				elseif($pa['club'] == 4)
				{
					$pa['wep'] = '高性能子机'; $pa['wepk'] = 'WG'; $pa['wepe'] = 77; $pa['weps'] = 77; $pa['wepsk'] = 'r';
				}
			}
			else 
			{
				$pa['clbpara']['botact']['stimes']++;
			}
		}
		else 
		{
			unset($pa['clbpara']['botact']['stimes']);
			unset($bdata['clbpara']['botact']['moveto']);
			$pa['clbpara']['botphase'] = 1;
		}
	}
	# 1.偷反阶段：攒够合成素材，开始刷兵，会主动移动到有声音或有兵死掉的位置
	# 2.（没合出广域的）强袭阶段：武器效果到达阈值，开始合成广域主动猎杀场上玩家目标
	elseif($pa['clbpara']['botphase'] == 1 || (!isset($pa['clbpara']['botstf']['r2']) && $pa['clbpara']['botphase'] == 2))
	{
		bot_check_can_mixitem($pa);
		# bot优先移动到有声音的位置
		if($noisepls && $bdata['pls'] != $noisepls)
		{
			$bmoveto = $noisepls;
		}
		# 如果没有 移动到上一个死人的位置
		else
		{
			$cpresult = $db->query("SELECT recv FROM {$tablepre}chat WHERE type = 3 ORDER BY time DESC");
			while($spls = $db->fetch_array($cpresult))
			{
				if(in_array($spls,$plslist)) 
				{
					$bmoveto = $spls;
					break;
				}
			}
		}
	}
	# 2.（合出广域的）强袭阶段：武器效果到达阈值，挑选一位比较牛的玩家开始猎杀
	elseif($pa['clbpara']['botphase'] == 2)
	{
		$htresult = $db->query("SELECT pls FROM {$tablepre}chat WHERE type = 0 AND pass != 'bot' ORDER BY lvl DESC");
		while($spls = $db->fetch_array($htresult))
		{
			if(in_array($spls,$plslist)) 
			{
				$bmoveto = $spls;
				break;
			}
		}
	}
	# 3.躲避阶段：场上有触手，使用一些卑鄙招数
	else
	{
		# 有钱买雷先去买雷
		if(empty($pa['clbpara']['botact']['gettrap']) && $pa['money'] >= 2500 && (in_array(14,$plslist) || in_array(27,$plslist)))
		{
			$bmoveto = in_array(14,$plslist) ? 14 : 27;
		}
		# 买到雷去干坏事
		elseif(!empty($pa['clbpara']['botact']['gettrap'])) 
		{
			$htresult = $db->query("SELECT pls FROM {$tablepre}chat WHERE type = 0 AND pass != 'bot' ORDER BY wepe DESC");
			if($db->num_rows($result)) $bmoveto = $db->fetch_array($result,0);
		}
		# 买不起雷，那跑路吧
		else 
		{
			$bmoveto = $plslist[array_rand($plslist)];
		}
	}
	if(!empty($bmoveto)) $pa['clbpara']['botact']['moveto'] = $bmoveto;
	$pa['pose'] = $bot_action_phase[$pa['clbpara']['botphase']]['pose'];
	$pa['tactic'] = $bot_action_phase[$pa['clbpara']['botphase']]['tactic'];
	return $bmoveto;
}

# bot在行动后修正策略
function bot_end_act_check(&$pa)
{
}

# bot使用道具的优先级
function bot_use_items(&$pa)
{
	global $bot_can_get_itemlist,$bot_stfid;
	include_once GAME_ROOT.'./include/game/item.func.php';

	$equip_list = get_equip_list();
	$e1 = get_equip_list(1);

	for($i=1;$i<=6;$i++)
	{
		if(!empty($pa['itms'.$i]))
		{
			# 检查是否需要回血
			if($pa['hp'] < $pa['mhp'] && (strpos($pa['itmk'.$i],'HH')===0 || strpos($pa['itmk'.$i],'HB')===0))
			{
				do{
					itemuse($i,$pa);
				}while($pa['hp'] < $pa['mhp'] && !empty($pa['itms'.$i]));
				# 补货提示
				if($pa['itms'.$i] < 5) $botact['need_buy_ihp'] = 1;
				continue;
			}
			# 检查是否需要回体力（设个定值，防止浪费）
			if($pa['sp'] < 50 && (strpos($pa['itmk'.$i],'HS')===0 || strpos($pa['itmk'.$i],'HB')===0))
			{
				do{
					itemuse($i,$pa);
				}while($pa['sp'] < 50 && !empty($pa['itms'.$i]));
				# 补货提示
				if($pa['itms'.$i] < 5) $botact['need_buy_isp'] = 1;
				continue;
			}
			# 检查道具是否为通用素材
			if(isset($bot_stfid[0][$pa['itm'.$i]]))
			{
				echo "将{$pa['itm'.$i]}存入了素材库<br>";
				$stf_id = $bot_stfid[0][$pa['itm'.$i]];
				$pa['clbpara']['botstf'][$stf_id] += $pa['itms'.$i];
				$pa['itm'.$i] = $pa['itmk'.$i] = $pa['itmsk'.$i] = '';
				$pa['itms'.$i] = $pa['itme'.$i] = 0;
				continue;
			}
			# 检查道具是否为特定素材
			if(isset($bot_stfid[$pa['club']][$pa['itm'.$i]]))
			{
				echo "将{$pa['itm'.$i]}存入了素材库<br>";
				$stf_id = $bot_stfid[$pa['club']][$pa['itm'.$i]];
				$pa['clbpara']['botstf'][$stf_id] += $pa['itms'.$i];
				$pa['itm'.$i] = $pa['itmk'.$i] = $pa['itmsk'.$i] = '';
				$pa['itms'.$i] = $pa['itme'.$i] = 0;
				continue;
			}
			# 棍棒武器检查是否需要打钉
			if(strpos($pa['itm'.$i],'钉')!==false)
			{
				if(strpos($pa['wep'],'棍棒')!==false)
				{
					do{
						itemuse($i,$pa);
					}while(!empty($pa['itms'.$i]));
				}
				else 
				{
					itemdrop('itm'.$i,$pa);
				}
				continue;
			}
			# 锐器检查是否需要磨刀
			if(strpos($pa['itm'.$i],'磨刀石')!==false)
			{
				if(strpos($pa['wepk'],'K')!==false)
				{
					do{
						itemuse($i,$pa);
					}while(!empty($pa['itms'.$i]));
				}
				else 
				{
					itemdrop('itm'.$i,$pa);
				}
				continue;
			}
			# 给耐久大于10的衣服打针线包
			if($pa['arbs'] > 10 && strpos($pa['itm'.$i],'针线包')!==false)
			{
				do{
					itemuse($i,$pa);
				}while(!empty($pa['itms'.$i]));
				continue;
			}
			# 礼盒直接开
			/*if(strpos($pa['itmk'.$i],'p')!==false)
			{
				do{
					itemuse($i,$pa);
				}while(!empty($pa['itms'.$i]));
				continue;
			}*/
			# 技能书、攻防药直接吃 300效以下陷阱直接用 
			if(strpos($pa['itmk'.$i],'M')===0 || strpos($pa['itmk'.$i],'V')===0 || (strpos($pa['itmk'.$i],'T')===0 && $pa['itme'.$i]<=300) )
			{
				do{
					itemuse($i,$pa);
				}while(!empty($pa['itms'.$i]));
				continue;
			}
			# 驱云弹
			if($pa['itm'.$i] == '驱云弹') itemuse($i,$pa);
			# 检查是否需要更换装备
			if(strpos($pa['itmk'.$i],'D')===0)
			{
				if(bot_check_equipitem($pa,$e1[$pa['itmk'.$i]])) itemuse($i,$pa);
				itemdrop('itm'.$i,$pa);
				continue;
			}
			# 把所有不在素材库和白名单中的道具扔掉
			if(!in_array($pa['itm'.$i],$bot_can_get_itemlist))
			{
				itemdrop('itm'.$i,$pa);
				continue;
			}
		}
	}
	return;
}

# 检查bot是否会将道具放入背包内
function bot_check_getitem(&$pa)
{
	$e1 = get_equip_list(1);
	if(!empty($pa['itms0']))
	{
		global $bot_can_get_itemlist,$bot_stfid;
		# 白名单道具
		if(in_array($pa['itm0'],$bot_can_get_itemlist)) 
		{
			return 1;
		}
		# 通用合成素材
		if(in_array($pa['itm0'],$bot_stfid[0]))
		{
			return 1;
		}
		# 社团限定合成素材
		if(in_array($pa['itm0'],$bot_stfid[$pa['club']]))
		{
			return 1;
		}
		# 强化药物、技能书、地雷
		if(strpos($pa['itmk0'],'M')===0 || strpos($pa['itmk0'],'V')===0 || strpos($pa['itmk0'],'T')===0) return 1;
		# 装备
		if(strpos($pa['itmk0'],'D')===0 && bot_check_equipitem($pa,$e1[$pa['itmk0']])) return 1;
	}
	return 0;
}

# 检查bot是否会拾取对应防具（不包括饰品）
function bot_check_equipitem(&$pa,$equip)
{
	if(!$pa[$equip.'s'] || $pa['itme0'] > $pa[$equip.'e']) return 1;
	return 0;
}

# bot武器升级：先这么搞着吧
function bot_check_can_mixitem(&$pa)
{
	global $db,$tableprem,$bot_mixid;

	$stf = $pa['clbpara']['botstf'];
	$mlst = $pa['clbpara']['botmix'];
	$mixflag = 0;

	if(empty($stf['r2']) && isset($stf['r0']) && isset($stf['r1']))
	{
		$pa['clbpara']['botstf']['r2'] = 1;
		addnews($now,'itemmix',get_title_desc($nick).' '.$name,'广域生命探测器');
	}

	if(empty($stf['p3']) && isset($stf['p1']) && isset($stf['p2']))
	{
		$pa['clbpara']['botstf']['p3'] = 1;
		addnews($now,'itemmix',get_title_desc($nick).' '.$name,'移动 PC');
	}

	if($pa['club'] == 1)
	{
		if(empty($mlst['m11']) && isset($stf['c11']) && isset($stf['c12']))
		{
			$pa['wep'] = '『T-LINK念动冲拳』';
			$pa['wepk'] = 'WP'; $pa['wepe'] = 240; $pa['weps'] = '∞'; $pa['wepsk'] = 'eN';
			$pa['clbpara']['botphase'] = 1;
			$mixflag = 1;
		}
	}
	elseif($pa['club'] == 2)
	{
		if(!$pa['clbpara']['botphase'] && isset($stf['c21']))
		{
			$pa['clbpara']['botphase'] = 1;
		}
		if(empty($mlst['m21']) && isset($stf['c21']) && isset($stf['c22']))
		{
			$pa['wep'] = 'Azurewrath';
			$pa['wepk'] = 'WK'; $pa['wepe'] = 9999; $pa['weps'] = '∞'; $pa['wepsk'] = 'rci';
			$mixflag = 1;
		}
	}
	elseif($pa['club'] == 3)
	{
		if(empty($mlst['m31']) && isset($stf['c31']) && isset($stf['c32']) && isset($stf['c33']))
		{
			$pa['wep'] = '《小黄的精灵球》';
			$pa['wepk'] = 'WC'; $pa['wepe'] = 386; $pa['weps'] = '∞'; $pa['wepsk'] = '';
			$mixflag = 1;
		}
		elseif(empty($mlst['m32']) && isset($stf['c34']))
		{
			$pa['wep'] = '《小黄的超级球》';
			$pa['wepk'] = 'WC'; $pa['wepe'] = 386; $pa['weps'] = '∞'; $pa['wepsk'] = 'Zir';
			$mixflag = 1;
		}
		elseif(empty($mlst['m33']) && isset($stf['c35']) && $stf['c34'] >= 2)
		{
			$pa['wep'] = '《小黄的大师球》'; $pa['money'] -= 9300;
			$pa['wepk'] = 'WC'; $pa['wepe'] = 493; $pa['weps'] = '∞'; $pa['wepsk'] = 'Zrd';
			$mixflag = 1;
		}
	}
	elseif($pa['club'] == 4)
	{
		if(!$pa['clbpara']['botphase'] && (isset($stf['c41']) || isset($stf['c42'])))
		{
			$pa['clbpara']['botphase'] = 1;
		}
	}
	if($mixflag) 
	{
		$pa['clbpara']['botmix'][$bot_mixid[$pa['wep']]] = 1;
		addnews($now,'itemmix','参展者 '.$pa['name'],$pa['wep']);
	}
	return $mixflag;
}

function get_bot_action_cost($pa,$action)
{
	$bot_action_cost = Array
	(
		'search' => 2,
		'move' => 2,
		'useitem' => 1,
		'mixitem' => 1,
		'heal' => 1,
	);
	return $bot_action_cost[$action] ?: 2;
}

?>
