<?php

	if(!defined('IN_GAME')) {
		exit('Access Denied');
	}

	function get_clbskill_emgain_r($ev,&$data)
	{
		if(!check_skill_unlock('c20_fertile',$data))
		{
			$sk = 'c20_fertile';
			$sk_lvl = get_skilllvl($sk,$data);
			$sk_var = rand(0,get_skillvars($sk,'emsgain',$sk_lvl));
			if(!empty($sk_var))
			{
				$ev = ceil($ev * (1 + ($sk_var/100)));
			}
		}
		return $ev;
	}

	# 过滤不能拆解的道具（数组） 不能分解返回1 能分解返回0
	function split_to_elements_filter($i)
	{
		global $log,$elements_info,$gamecfg;
		include config('elementmix',$gamecfg);
		if($i)
		{
			//过滤不能分解的道具种类
			if((!is_array($i['itmk']) && in_array($i['itmk'],$no_itmk_to_e_list)) || (is_array($i['itmk']) && array_intersect($i['itmk'],$no_itmsk_to_e_list))) return 1;
			//过滤不能分解的道具名
			foreach($no_itm_to_e_list as $no_itm)
			{
				if(preg_match("/$no_itm/",$i['itm'])) return 1;
			}
			//过滤不能分解的道具属性
			if($i['itmsk'])
			{
				//把道具属性打散成数组
				$tmp_itmsk_arr = get_itmsk_array($i['itmsk']);
				if(array_intersect($tmp_itmsk_arr,$no_itmsk_to_e_list)) return 1;
			}
			return 0;
		}
		return 1;
	}

	# 分解会触发事件的尸体/道具
	function esplit_vip_things($ev_arr,$edata,&$data,$spt='corpse')
	{
		global $log,$elements_info,$gamecfg,$gamevars,$typeinfo;
		include config('elementmix',$gamecfg);
		if(!isset($data))
		{
			global $pdata;
			$data = &$pdata;
		}
		extract($data,EXTR_REFS);

		if(empty($ev_arr)) $ev_arr = Array(0 => 0,1 => 0,2 => 0,3 => 0,4 => 0,5 => 0);

		# 如果随机配方尚未生成，先生成随机配方
		if(empty($gamevars['rand_emixfixres'])) $gamevars['rand_emixfixres'] = esp_spawn_rand_emixfixres();

		# 读取配方
		if($spt == 'corpse')
		{
			$e_list = $split_spcorpse_fix[$edata['type']];
			if(is_array($e_list)) $e_list = $e_list[$edata['name']];
		}
		else 
		{
			$e_list = $split_itm_fix[$edata['itm']];
		}

		# 解码配方
		$e_list = explode('-',str_replace('r_','',$e_list));
		# 获得配方编号与素材编号
		$erkey = $e_list[0]; $eskey = $e_list[1];
		# 解码后能获得的是随机某条素材
		if($eskey == 'r') $eskey = array_rand($gamevars['rand_emixfixres'][$erkey]['stuff']);
		$earr = $gamevars['rand_emixfixres'][$erkey]['stuff'][$eskey];
		# 记录以下配方信息：素材位置、素材所需元素编号、素材所需元素数量
		$esid = $earr[0]; $esnum = $earr[1];
		
		if($spt == 'corpse')
		{
			$log.="<span class='grey'>你发现从{$typeinfo[$edata['type']]}身上飘落的<span class='red'>{$esnum}</span>份{$elements_info[$esid]}样子有点奇怪……怎么回事呢？</span><br>";
		}
		else 
		{
			$log.="<span class='grey'>你发现构成{$edata['itm']}的<span class='red'>{$esnum}</span>份{$elements_info[$esid]}样子有点奇怪……怎么回事呢？</span><br>";
		}

		if(empty($clbpara['elements']['info']['hd']['h'.$erkey]['s'.$eskey]))
		{
			$clbpara['elements']['info']['hd']['h'.$erkey]['s'.$eskey] = 1;
		}

		$ev_arr[$esid] += $esnum;
		return $ev_arr;
	}

	# 通过输入的道具/装备数组计算对应的元素价值 
	# 输入的iarr默认的格式：$iarr=Array('arb或itm0'=>Array('itm'=>$arb或$itm0,'itmk'=>$arbk或$itmk0.....),)
	function esplit_get_values_by_iarr($iarr,$ev_arr,&$data)
	{
		global $log,$mode,$typeinfo,$elements_info,$gamecfg,$gamevars;
		include config('elementmix',$gamecfg);
		if(!isset($data))
		{
			global $pdata;
			$data = &$pdata;
		}
		extract($data,EXTR_REFS);

		if(empty($ev_arr)) $ev_arr = Array(0 => 0,1 => 0,2 => 0,3 => 0,4 => 0,5 => 0);

		# 开始计算元素价值
		foreach($iarr as $i => $t)
		{
			# 最优先：检查拆解特定道具（全名匹配）时的事件
			if(isset($split_itm_fix[$t['itm']]))
			{
				# 处理秘钥道具
				if(!is_array($split_itm_fix[$t['itm']]) && strpos($split_itm_fix[$t['itm']],'r_')===0)
				{
					esplit_vip_things($ev_arr,$t,$data,'itm');
					continue;
				}
				# 处理吐出来的道具，一次分解最多只会吐一个道具，后到先得。
				elseif(!empty($split_itm_fix[$t['itm']]['spitm']))
				{
					if(isset($ev_arr['spitm'])) unset($ev_arr['spitm']);
					$ev_arr['spitm'] = $split_itm_fix[$t['itm']]['spitm'];
					continue;
				}
				else 
				{
					foreach($split_itm_fix[$t['itm']] as $ekey => $ev) $ev_arr[$ekey] += $ev;
				}
				continue; 
			}

			# 次优先：检查拆解关键词匹配道具名时的事件（改为手动添加判断条件）
			if(strpos($t['itm'],'方块')!==false || strpos($t['itm'],'宝石方块')!==false)
			{
				$ev = strpos($t['itm'],'宝石方块')!==false ? 1000 : 400;
				$ekey = rand(0,5);
				$ev_arr[$ekey] += $ev;
				continue; 
			}

			# 跳过不能分解的道具
			if(split_to_elements_filter($t)) continue;

			# 根据道具类别确定分解道具可获得的元素
			$ekey = isset($flip_d_tag[$t['itmk']]) ? $flip_d_tag[$t['itmk']] : array_rand($elements_info);
			# 计算分解道具能获得元素数量
			$ev = esplit_calc_itm_value($t['itmk'],$t['itme'],$t['itms'],$data);
			# 应用
			$ev_arr[$ekey] += $ev;

			# 根据道具属性计算道具的附加价值
			if(!empty($t['itmsk']))
			{
				$t['itmsk'] = get_itmsk_array($t['itmsk']);
				foreach($t['itmsk'] as $tsk)
				{
					//获取单个属性关联的元素 没有则随机挑选一个元素
					$ekey = isset($flip_s_tag[$tsk]) ? $flip_s_tag[$tsk] : array_rand($elements_info);
					if(isset($ekey))
					{
						$ev = emix_calc_subtag_value($tsk,$t['itme'],$t['itmk'],$data);
						$ev_arr[$ekey] += $ev;
					}
				}
			}
		}
		return $ev_arr;
	}

	# 通过道具的类别(主要特征)计算价值
	function esplit_calc_itm_value($dom,$itme,$itms,&$data)
	{
		global $log,$elements_info,$gamecfg,$nosta;

		include config('elementmix',$gamecfg);
		if(!isset($data))
		{
			global $pdata;
			$data = &$pdata;
		}
		extract($data,EXTR_REFS);

		# 获取道具类别对应系数
		if(isset($split_itmk_r[$dom]))
		{
			$evr = $split_itmk_r[$dom];
		}
		else
		{
			$dom = filter_itemkind($dom);
			$evr = isset($split_itmk_r[$dom]) ? $split_itmk_r[$dom] : $split_default_itmk_r;
		}
		# 无限耐久的道具没有耐久价值
		if($itms == $nosta) $itms = 0;
		# 道具价值 = 道具类别系数 × (道具效果+道具耐久) / 2
		$ev = $evr * (($itme + $itms)/2);
		return $ev;
	}

	# 计算元素次要特征的价值
	function emix_calc_subtag_value($sub,$itme,$itmk='default',&$data)
	{
		global $log,$elements_info,$gamecfg;
		global $ex_base_dmg,$ex_wep_dmg,$ex_max_dmg;

		include config('elementmix',$gamecfg);
		if(!isset($data))
		{
			global $pdata;
			$data = &$pdata;
		}
		extract($data,EXTR_REFS);

		# 检查是有价值属性还是默认价值属性
		$sub_value = isset($split_itmsk_fix[$sub]) ? $split_itmsk_fix[$sub] : $split_default_itmsk_fix;

		# 属性价值是数组的情况下，检查能否根据道具类别判定价值
		if(is_array($sub_value))
		{
			$sub_value = isset($sub_value[$itmk]) ? $sub_value[$itmk] : $sub_value['default'];
		}

		# 属性是攻击类属性（烧冻毒麻乱）、传入的道具类别是武器，且传入的武器效果不为0时，根据武器效果判定属性价值
		if(array_key_exists($sub,$ex_wep_dmg) && strpos($itmk,'W')===0 && !empty($itme))
		{
			# 计算属性能造成的伤害
			$ex_dmg = $ex_base_dmg[$sub] + $itme/$ex_wep_dmg[$sub];
			$ex_dmg = $ex_max_dmg[$sub] == 0 ? $ex_dmg : min($ex_dmg,$ex_max_dmg[$sub]);
			# 根据能造成的属性伤害，计算属性价值
			$sub_value = max($ex_dmg,$sub_value);
			# 爆炸物特殊修正：仅计算1/3属性
			if($sub == 'd' && strpos($itmk,'D')!==false) $sub_value = round($sub_value*0.33);
		}

		return $sub_value;
	}


	# 判断合成是否存在固定结果
	function emix_check_fix_result($emlist,$emnums,&$data)
	{
		global $now,$log,$gamecfg,$gamevars,$elements_info;
		include config('elementmix',$gamecfg);
		if(!isset($data))
		{
			global $pdata;
			$data = &$pdata;
		}
		extract($data,EXTR_REFS);
		
		# 检查固定配方
		foreach($emix_fixlist as $ffkey => $femix)
		{			
			if(count($femix['stuff']) == count($emlist))
			{
				$fix_flag = 1;
				foreach($femix['stuff'] as $fkey => $farr)
				{
					if($farr[0] != $emlist[$fkey] || $farr[1] != $emnums[$fkey]) 
					{
						$fix_flag = 0;
						break;
					}
				}
				if($fix_flag == 1)
				{
					$fix_flag = $femix['result'];
					# 将成功合成的条目保存在笔记内
					if(empty($clbpara['elements']['info']['d']['d'.$ffkey])) $clbpara['elements']['info']['d']['d'.$ffkey] = 1;
					break;
				}
			}
		}
		# 如果随机配方尚未生成，先生成随机配方
		if(empty($gamevars['rand_emixfixres'])) $gamevars['rand_emixfixres'] = esp_spawn_rand_emixfixres();
		
		if ($fix_flag == 0)
		{
			# 检查随机配方
			foreach($gamevars['rand_emixfixres'] as $fkkey => $femix)
			{
				if(count($femix['stuff']) == count($emlist))
				{
					$fix_flag = 1;
					foreach($femix['stuff'] as $fkey => $farr)
					{
						if($farr[0] != $emlist[$fkey] || $farr[1] != $emnums[$fkey]) 
						{
							$fix_flag = 0;
							break;
						}
					}
					if($fix_flag == 1)
					{
						$fix_flag = $rand_emix_fixlist[$fkkey]['result'];
						break;
					}
				}
			}
		}
		# 获取固定合成结果
		if(!empty($fix_flag))
		{
			$log.="<br>但是出现结果的速度比你想象中要快得多！<br>你还没反应过来，元素们就把一样东西吐了出来！<br><br>";
			$itm0 = $fix_flag[0]; $itmk0 = $fix_flag[1]; $itmsk0 = $fix_flag[4];
			$itme0 = $fix_flag[2]; $itms0 = $fix_flag[3];
			include_once GAME_ROOT.'./include/game/itemmain.func.php';
			itemget();
			addnews($now,'emix_success',$name,$fix_flag[0]);
			return 1;
		}
		return $fix_flag;
	}

	# 判断合成运势
	function emix_check_mix_luck($downfix=0,$upfix=0)
	{
		# 掷骰：
		$emix_dice = rand(1,100)+$upfix-$downfix;
		switch($emix_dice)
		{
			case $emix_dice<=5:
				//大成功
				$emix_flag = 4;
				break;
			case $emix_dice<=15:
				//出色表现
				$emix_flag = 3;
				break;
			case $emix_dice<=50:
				//成功
				$emix_flag = 2;
				break;
			case $emix_dice<=96:
				//普通
				$emix_flag = 1;
				break;
			default:
				//哇 这下便样衰了！（道具会带有灵魂绑定属性 可恶 为什么诅咒属性不见了）
				$emix_flag = -2;
		}
		return $emix_flag;
	}

	# 检查参与合成的元素主特征
	function emix_get_domtags($emlist,$emnums,$domkey,&$data)
	{
		global $log,$elements_info,$gamecfg;
		include config('elementmix',$gamecfg);
		$tags = Array();
		# 获取主元素的主特征
		foreach($domkey as $key)
		{
			$tag = $temp_etags[$emlist[$key]]['dom'];
			# 投入单数份元素，返回第0个主特征；否则返回第1个主特征
			if(is_array($tag))
			{
				$f = fmod($emnums[$key],2);
				$f = $f ? 0 : 1;
				$tag = $tag[$f];
			}
			# 将探索到的主特征加入笔记内
			if(empty($data['clbpara']['elements']['tags'][$emlist[$key]]['dom'][$f])) $data['clbpara']['elements']['tags'][$emlist[$key]]['dom'][$f] = 1;
			$tags[] = $tag;
		}
		return $tags;
	}

	# 根据主特征生成道具类别
	function emix_spawn_itmk($dom_tags,$emix_flag=NULL)
	{
		global $log,$elements_info,$gamecfg;
		include config('elementmix',$gamecfg);

		$emix_itmk = ''; $obbs_fix = 0;
		//根据合成表现修正概率
		if($emix_flag)
		{
			$obbs_fix = $emix_flag*10;
		}
		if(count($dom_tags)>1)
		{
			//存在复数个主特征，判断能否进行组合
			$mixflag = false;
			foreach($dommix_list as $mkey => $minfo) 
			{
				//直接抄合成匹配逻辑了 有一种野性的美
				if(!array_diff($dom_tags,$minfo['stuff']) && !array_diff($minfo['stuff'],$dom_tags) && count($dom_tags) == count($minfo['stuff']))
				{ 
					//if($minfo['obbs'] && (diceroll(100)-$obbs_fix)>$minfo['obbs']) continue; 
					if($minfo['obbs'] && (rand(0,100)-$obbs_fix)>$minfo['obbs']) continue; //配方为概率合成 掷骰判定没通过 跳过
					$emix_itmk = $minfo['result'];
					$mixflag = true;
					# 将成功合成的条目保存在笔记内
					if(empty($clbpara['elements']['info']['dd']['dd'.$mkey])) $clbpara['elements']['info']['dd']['dd'.$mkey] = 1;
					break;
				}
			}
			//没有匹配的组合 随机返回一个主特征作为类别
			if(!$mixflag) $emix_itmk = $dom_tags[array_rand($dom_tags)];
		}
		else
		{
			//只有一个主特征 直接让它作为类别返回
			$emix_itmk = $dom_tags[0];
		}
		return $emix_itmk;
	}

	function emix_calc_maxenum(&$data=NULL)
	{
		global $gamecfg;
		include config('elementmix',$gamecfg);
		if(!isset($data))
		{
			global $pdata;
			$data = &$pdata;
		}
		extract($data,EXTR_REFS);
		$max_enum = $lvl<31 ? ceil($max_emix_itme_start+($max_emix_itme_up*$lvl*((255-$lvl)/255))) : ceil($max_emix_itme_start+(($max_emix_itme_up+$lvl)*$lvl));
		return $max_enum;
	}

	# 生成道具数量、耐久
	function emix_spawn_itmes(&$emlist,&$emnums,$domkey,$eitmemax,$eitmesr,&$total_enum,$emix_itmk,&$data,$emix_flag=NULL)
	{
		global $log,$elements_info,$gamecfg;
		include config('elementmix',$gamecfg);
		if(!isset($data))
		{
			global $pdata;
			$data = &$pdata;
		}
		extract($data,EXTR_REFS);

		# 计算当前所能合成出的道具效耐最大值
		# 理论上限值：（前32级时的计算公式：基础+提升值*等级*(255-等级)%）（32级后：基础+（提升值+等级）*等级)）
		$max_enum = emix_calc_maxenum($data);
		# 通过自定义上限系数对理论上限进行修正
		$max_enum = ceil($max_enum * $eitmemax / 100);
		# 计算投入数量是否超过理论上限
		$max_enum = min($max_enum ,$total_enum);
		# 理论上限小于2时，合成失败
		if($max_enum < 2) return 0;
		# 根据投入比例，依次扣除参与合成的元素数量
		foreach($emnums as $ekey => $enum)
		{
			# 计算每个元素实际的消耗
			$tmp_cost = round($max_enum * ($enum/$total_enum)); 
			$emnums[$ekey] -= $tmp_cost;
		}
		# 从总投入量中扣除构成主体消耗掉的部分
		$total_enum -= $max_enum;

		# 为道具分配效果、耐久比例；
		# 非大成功、且自定义比例超过80%或低于20%时，有概率合成失败
		if($emix_flag != 4 && ($eitmesr >= 80 || $eitmesr <= 20))
		{
			$dice = rand(0,99);
			$obbs = $eitmesr >= 80 ? 21 - (100 - $eitmesr) : 21 - $eitmesr;
			if($dice < $obbs) return 0;
		}
		$itmer = $eitmesr; 
		$itmsr = 100 - $eitmesr;
		# 为道具分配效果、耐久
		$eitme = $emix_flag==4 ? $max_enum : ceil( $max_enum * $itmer/100 * ($emix_flag/10 + 1)); 
		$eitms = $emix_flag==4 ? '∞' : ceil( $max_enum * $itmsr/100 * ($emix_flag/10 + 1));

		# 根据道具类别，修正道具效果、耐久
		# 防具耐久调整
		if(strpos($emix_itmk,'D')===0)
		{
			# 大失败时防具耐久变成∞
			if($emix_flag == -2)
			{
				$eitms = '∞';
			}
			# 大成功时防具效果和耐久等于两者中的较大值
			if($emix_flag == 4)
			{
				$eitms = max($eitme, $eitms);
				$eitme = $eitms;
			}
		}
		# 强化药物、技能书籍效耐调整：效果最大不能超过角色等级、耐久最大不超过角色等级的平方根，且向下调整
		if(strpos($emix_itmk,'M')===0 || strpos($emix_itmk,'HM')===0 || strpos($emix_itmk,'HT')===0 || strpos($emix_itmk,'V')===0) 
		{
			$eitme = min($lvl,floor($eitme/$lvl));
			$eitms = min(ceil(sqrt($lvl)),floor($eitms/$lvl));
		}

		if(!$eitme || !$eitms) return 0;
		return Array($eitme,$eitms);
	}

	# 获取元素可展现的次要特征
	function emix_get_subtags($emlist,$emnums,$emix_itme,$emix_itmk,&$data,$emix_flag=NULL)
	{
		global $log,$elements_info,$gamecfg;
		include config('elementmix',$gamecfg);
		if(!isset($data))
		{
			global $pdata;
			$data = &$pdata;
		}
		extract($data,EXTR_REFS);

		$subtags = Array();
		# 遍历元素队列，获取可生成的次要特征
		foreach($emlist as $ekey => $eid)
		{
			if($emnums[$ekey] > 0)
			{
				$esubkey = $temp_etags[$eid]['sub'];
				# 获取并打乱该元素的次要特征队列
				shuffle($esubkey);
				# 遍历该元素的次要特征队列，将特征价值与元素剩余数量比对
				foreach($esubkey as $sub)
				{
					$sub_value = emix_calc_subtag_value($sub,$emix_itme,$emix_itmk,$data);
					# 满足价值要求的情况下，扣除对应数量的元素，并将该特征加入次要特征队列内
					if($sub_value < $emnums[$ekey])
					{
						$subtags[] = $sub;
						$emnums[$ekey] -= $sub_value;
						# 将探索到的次要特征加入笔记内
						$eskey = array_search($sub,$temp_etags[$eid]['sub']);
						if(empty($clbpara['elements']['tags'][$eid]['sub'][$eskey]))
							$clbpara['elements']['tags'][$eid]['sub'][$eskey] = 1;
					}
					if($emnums[$ekey] < 0) break;
				}
			}
		}
		return $subtags;
	}

	# 根据获取到的次要特征输出属性
	function emix_spawn_itmsk($subtags,$itmk,&$data,$emix_flag=NULL)
	{
		global $log,$elements_info,$gamecfg,$gamevars;
		include config('elementmix',$gamecfg);
		if(!isset($data))
		{
			global $pdata;
			$data = &$pdata;
		}
		extract($data,EXTR_REFS);

		$sk = Array();
		# 计算道具最多能生成的属性数量上限（当前等级的平方根；最少会生成1个属性；最多可以生成7个属性）
		$max_sk = max(1,min(7,ceil(sqrt($lvl))));

		# 如果未生成随机的属性组合条件，先生成属性组合条件
		if(empty($gamevars['rand_emixsubres'])) $gamevars['rand_emixsubres'] = esp_spawn_rand_emixsubres();

		# 如果传入的次要特征数量＞1 进行组合判断
		if(count($subtags)>1)
		{
			# 合并固定配方与随机配方
			$submix_list = array_merge_recursive($submix_list,$gamevars['rand_emixsubres']);
			foreach($submix_list as $mkey => $minfo) 
			{
				# 属性组合逻辑：匹配素材数量>=要求素材数量 && 不能重复生成属性
				if(count(array_intersect($subtags,$minfo['stuff'])) >= count($minfo['stuff']) && (in_array($minfo['result'],$repeat_subtags) || !in_array($minfo['result'],$sk)))
				{ 
					# 配方为概率合成 掷骰判定
					if(isset($minfo['obbs'])) 
					{
						$obbs = $minfo['obbs'];
						if(is_array($obbs)) $obbs = isset($obbs[$itmk]) ? $obbs[$itmk] : $obbs['default'];
						if(rand(0,100) > $obbs) continue; 
					}
					# 配对成功！消除素材特征
					//18th fix: kudos to 低维生物
					//$delsub = $minfo['stuff'];
					//$count_delsub = count($minfo['stuff']);
					$count_delsub = 0;
					if(!empty($minfo['stuff']))
					{
						$count_delsub = count($minfo['stuff']);
					}
					
					for ($i = 0; $i < count($subtags); $i++){
						if (in_array($subtags[$i], $minfo['stuff'])){
							unset($subtags[$i]);
							$count_delsub -= 1;
							if ($count_delsub <= 0) break;
						}
					}
					//if(array_search($delsub, $subtags)!== false){ //?
					//foreach($minfo['stuff'] as $delsub) unset($subtags[array_search($delsub,$subtags)]);}
					//Alternative Fix: This will consume all properties in $subtags that matches $delsub.
					//But stacking is better anyways. - comment out the above FOR loop then uncomment this to use
					//$subtags = array_diff($subtags, $delsub);
					$subtags = array_values($subtags);
					# 将组合结果加入属性队列
					$sk[] = $minfo['result'];
					# 将探索到的次要特征组合加入笔记内
					if(empty($clbpara['elements']['info']['sd']['sd'.$mkey]))
						$clbpara['elements']['info']['sd']['sd'.$mkey] = 1;
				}
			}
		}
		# 将次要特征合并进待生成的属性队列内
		$sk = array_merge($sk,$subtags); 
		# 将传入的道具类别与特征对比，过滤掉一些乱七八糟的属性：
		# 只用道具类别的首字母判断
		$kind = substr($itmk,0,1); 
		foreach($sk as $key => $skv)
		{	
			//武器上不会生成“防御性”属性
			if($kind=='W' && in_array($skv,$itmk_to_itmsk_tags['D'])) unset($sk[$key]);
			//防具、道具上不会生成“攻击性”属性
			if($kind!='W' && in_array($skv,$itmk_to_itmsk_tags['W'])) unset($sk[$key]);
			//补给品只会生成杂项属性
			if($kind!='W' && $kind!='D' && !in_array($skv,$itmk_to_itmsk_tags['misc'])) unset($sk[$key]);
		}
		# 从尾部筛出超过生成上限的属性
		while(count($sk)>$max_sk) array_pop($sk);
		return $sk;
	}

	# 为元素合成道具生成名字
	function emix_spawn_itmname($emlist,$emnums,$domkey,$emix_itmk,$emix_itme,$emix_itms,$emix_itmsk,&$data,$emix_flag=NULL)
	{
		global $log,$elements_info,$gamecfg;
		include config('elementmix',$gamecfg);
		if(!isset($data))
		{
			global $pdata;
			$data = &$pdata;
		}
		extract($data,EXTR_REFS);

		# 获取道具使用的主元素id
		$domid = $domkey[array_rand($domkey)];
		$domid = $emlist[$domid];

		//出生了！为孩子起个可爱的名字吧
		$emix_itm = ''; $emix_itm_prefix = ''; $emix_itm_meta = ''; $emix_itm_tail = ''; $emix_name_brackets = '';
		//根据主元素获取修饰前缀词组
		$emix_itm_prefix = array_rand($emix_name_prefix_arr[$domid]);
		$emix_itm_prefix = $emix_name_prefix_arr[$domid][$emix_itm_prefix];
		//随便挑一个元素的元词缀词组
		$meta_emlist_id = ($emix_flag==4 || $emix_flag==-2) ? 6 : array_rand($emlist); //大成功、失败事件可以使用拓展1词组
		$emix_itm_meta = array_rand($emix_name_meta_arr[$meta_emlist_id]);
		$emix_itm_meta = $emix_name_meta_arr[$meta_emlist_id][$emix_itm_meta];
		//根据生成的类别获取尾巴词组
		$tmp_kind = $emix_itmk;
		//过滤掉道具的子类别
		$tmp_kind = filter_itemkind($emix_itmk,1);
		if(strpos($tmp_kind,'H')===0) $tmp_kind = 'H'; //TODO：提供了单独的道具类别词组后删掉这一句
		//复合武器
		if(is_array($tmp_kind))
		{
			//把两种武器的词尾组合起来 可能会生成很怪很怪的东西！！
			for($k=0;$k<=count($tmp_kind);$k++)
			{
				$tmp_k_name = $emix_name_tail_arr[$tmp_kind[$k]][array_rand($emix_name_tail_arr[$tmp_kind[$k]])];
				//第一种武器的词尾只保留前两个字 嘻嘻
				if($k==0) $tmp_k_name = mb_substr($tmp_k_name,0,2,'utf-8');
				$emix_itm_tail .= $tmp_k_name;
			}
		}
		else
		{
			//根据类别获取词尾 如果没有对应类别的词尾则生成泛用性词尾
			$emix_itm_tail = $emix_name_tail_arr[$tmp_kind] ? $emix_name_tail_arr[$tmp_kind][array_rand($emix_name_tail_arr[$tmp_kind])] : $emix_name_tail_arr['0'][array_rand($emix_name_tail_arr['0'])];
		}
		//（只有生成的是武器时才会）根据合成出的道具效果生成一个能大幅提升时髦值的括号
		if(strpos($emix_itmk,'W')===0)
		{
			$emix_name_brackets = ($emix_itme/100) + rand(-1,1);
			$emix_name_brackets = min(max(0,$emix_name_brackets),count($emix_name_brackets_arr)-rand(1,2));
			$emix_name_brackets = explode('+',$emix_name_brackets_arr[$emix_name_brackets]);
		}
		//出生！
		$emix_itm = $emix_itm_prefix.$emix_itm_meta.$emix_itm_tail;
		if(!empty($emix_name_brackets)) $emix_itm = $emix_name_brackets[0].$emix_itm.$emix_name_brackets[1];
		return $emix_itm;
	}

	# 生成随机配方
	function esp_spawn_rand_emixfixres()
	{
		global $elements_info,$gamecfg,$gamevars;
		include config('elementmix',$gamecfg);
		# 如果未生成随机的属性组合条件，先生成属性组合条件
		$gamevars['rand_emixfixres'] = Array();
		foreach($rand_emix_fixlist as $ekey => $elist)
		{
			foreach($elist['stuff'] as $eid => $enum)
			{
				# 随机取一种元素
				$s_eid = array_rand($elements_info);
				# 在区间内给定该元素的值
				$enum = explode('-',substr($enum,1));
				$s_enum = rand($enum[0],$enum[1]);
				# 登记并保存在gamevars内
				$gamevars['rand_emixfixres'][$ekey]['stuff'][$eid] = Array($s_eid,$s_enum);
			}
		}
		save_gameinfo();
		return $gamevars['rand_emixfixres'];
	}

	# 生成随机次要特征组合配方
	function esp_spawn_rand_emixsubres()
	{
		global $elements_info,$gamecfg,$gamevars;
		include config('elementmix',$gamecfg);
		# 如果未生成随机的属性组合条件，先生成属性组合条件
		$gamevars['rand_emixsubres'] = Array();
		foreach($random_submix_list as $mainkey => $slist)
		{
			foreach($slist['stuff'] as $sid => $snum)
			{
				# 前缀以“v_”表示的，代表要选用一个在对应价值以上的属性
				if(strpos($snum,'v_')===0) 
				{
					$snum = str_replace('v_','',$snum);
					do{
						$skey = array_rand($flip_s_tag);
						$sv = emix_calc_subtag_value($skey,0,'default',$data);
					}while($sv<$snum);
				}
				# 前缀以“tags_”表示的，代表要选用带有对应标签的属性
				elseif(strpos($snum,'tags_')===0)
				{
					$snum = $itmk_to_itmsk_tags[(str_replace('tags_','',$snum)).'_0'];
					shuffle($snum);
					$skey = $snum[0];
				}
				else
				{
					$skey = $snum;
				}
				# 登记并保存在gamevars内
				$gamevars['rand_emixsubres'][$mainkey]['stuff'][$sid] = $skey;
			}
			$gamevars['rand_emixsubres'][$mainkey]['obbs'] = $slist['obbs'];
			$gamevars['rand_emixsubres'][$mainkey]['result'] = $slist['result'];
		}
		save_gameinfo();
		return $gamevars['rand_emixsubres'];
	}

?>