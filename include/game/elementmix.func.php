<?php

	if(!defined('IN_GAME')) {
		exit('Access Denied');
	}
	//zai jian le suoyoude global
	global $gamecfg,$elements_info;//这个$gamecfg到底是在哪定义的……
	require_once './include/game/dice.func.php';
	include_once config('elementmix',$gamecfg);

	/********界面交互部分********/
	//显示元素数量
	function print_elements_info()
	{
		global $elements_info,$log;
		$log.="当前的<span class='lime'>元素存量</span>如下：<br><span class='grey'>（将鼠标悬浮在元素上可以查看其特征）</span><br>";
		foreach($elements_info as $e_key=>$e_info)
		{
			global ${'element'.$e_key};
			if(${'element'.$e_key})
			{
				$log.="<span tooltip=\"".print_elements_tags($e_key)."\">";
				$log.="◆ {$e_info}：{${'element'.$e_key}} 份；";
				$log.="</span><br>";
			}
		}
		$log.="<br>";
	}
	//显示元素标签
	function print_elements_tags($e_key)
	{
		global $temp_etags,$iteminfo,$itemspkinfo;
		$tinfo="已了解的特征：";
		foreach($temp_etags[$e_key] as $tk => $tarr)
		{
			if(is_array($tarr))
			{
				foreach($tarr as $tm) 
					$tinfo.= $tk == 'dom' ? "[主]".$iteminfo[$tm]." " : "[次]".$itemspkinfo[$tm]." ";
			}
		}
		return $tinfo;
	}

	/********拆解元素部分********/
	//过滤掉不能拆解的道具（数组） 不能分解返回1 能分解返回0
	function split_to_elements_filter($i)
	{
		global $no_itm_to_e_list,$no_itmk_to_e_list,$no_itmsk_to_e_list;
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

	//把尸体打散成元素
	function split_corpse_to_elements(&$edata)
	{
		$ev_arr = Array();
		if($edata)
		{
			global $elements_info,$no_type_to_e_list,$split_corpse_lvl_r,$split_spcorpse_fix,$typeinfo;
			global $log,$rp,$mode;
			//过滤不能分解的尸体
			if(in_array($edata['type'],$no_type_to_e_list))
			{
				$log.="无法从{$edata['name']}身上提炼元素……为什么呢？<br><br>";
				$mode = 'command';
				return;
			}
			//成功从尸体中提炼元素
			$log.="<span class='grey'>{$edata['name']}化作点点荧光四散开来……</span><br>";
			//处理绑定有秘钥的尸体
			if($split_spcorpse_fix[$edata['type']])
			{	
				$ek_arr = Array(); unset($ekey); unset($ev);
				//获取缓存文件
				$re_list = merge_random_emix_list();
				//还原配方与素材键名
				$e_list = is_array($split_spcorpse_fix[$edata['type']]) ? $split_spcorpse_fix[$edata['type']][$edata['name']] : $split_spcorpse_fix[$edata['type']];
				$e_list = explode('-',str_replace('r_','',$e_list));
				//$e_list[0]：配方键名  $e_list[1]：素材所在数组内的位置（不是元素编号）  
				if($e_list[1] == 'r')
				{
					//返回随机素材
					$ekey = array_rand($re_list[$e_list[0]]['stuff']);
					$ev = $re_list[$e_list[0]]['stuff'][$ekey];
				}
				else
				{
					//返回指定位置素材 遍历配方 找到它的家
					$e_sort = 0;
					foreach($re_list[$e_list[0]]['stuff'] as $stuff_key => $stuff_num)
					{
						if($e_sort == $e_list[1])
						{
							$ekey = $stuff_key;
							$ev = $stuff_num;
							break;
						}	
						$e_sort ++;
					}
				}
				$log.="<span class='grey'>你发现从{$typeinfo[$edata['type']]}身上飘落的<span class='red'>{$ev}</span>份{$elements_info[$ekey]}样子有点奇怪……怎么回事呢？</span><br>";
				$ek_arr[$ekey] += $ev;
			}
			//根据尸体等级计算能获得的全种类元素数量
			$ev_lvl = ceil($edata['lvl']*$split_corpse_lvl_r);
			//把尸体上的装备道具一起打包
			$corpse_itm_arr = pack_corpse($edata);
			//计算从尸体的装备上能获得的元素种类与数量
			$ev_arr = get_evalues_by_iarr($corpse_itm_arr);
			//增加对应的元素
			$total_addev = 0;
			foreach($elements_info as $e_key=>$e_info)
			{
				global ${'element'.$e_key};
				$add_ev = $ev_arr[$e_key] + $ev_lvl + $ek_arr[$e_key];
				//如果尸体上有元素，一并获取，不过现在还不能在npc配置文件里预设NPC出生时带的元素
				//瞄了眼NPC初始化的函数，要改的话不如一步到位都改了。
				//TODO：创建一个同步player表字段的函数，在NPC初始化时对NPC数据格式化，插入数据库时用格式化后的数组，这样以后添加新字段也不再需要动初始化函数了
				if($edata['element'.$e_key])
				{
					$add_ev += $edata['element'.$e_key];
					$edata['element'.$e_key] = 0;
				}
				${'element'.$e_key} += $add_ev;
				$total_addev += $add_ev;
				$log.="获得了{$add_ev}份{$e_info}！<br>";
			}
			//分解的结果有参数合法的特殊道具
			if(is_array($ev_arr['spitm']) && count($ev_arr['spitm'])>3)
			{
				global $itm0,$itmk0,$itme0,$itms0,$itmsk0;
				$log.="但出现在你面前的不是元素，而是<span class='yellow'>{$ev_arr['result'][0]}</span>！<br>……这是什么情况……！？<br>";
				$itm0 = $ev_arr['result'][0]; $itmk0 = $ev_arr['result'][1]; $itmsk0 = $ev_arr['result'][4];
				$itme0 = $ev_arr['result'][2]; $itms0 = $ev_arr['result'][3];
				include_once GAME_ROOT.'./include/game/itemmain.func.php';
				itemget();
			}
			//销毁尸体
			destory_corpse($edata);
			//你们也一块去吧！
			unset($ev_arr); unset($corpse_itm_arr);
			//炼人油败人品
			//$ep_dice = diceroll($total_addev);
			$ep_dice = rand(0,$total_addev);
			if($ep_dice>70)
			{
				$rp += $ep_dice;
				$log.="……但是这一切真的值得吗？<br>";
			}
			$log.="<br>";
		}
		$mode = 'command';
		return;
	}

	//把道具打散成元素 改下传入的参数其实也可以拆装备
	function split_item_to_elements($iid=NULL)
	{
		$i_arr = Array();
		if(isset($iid))
		{
			global $elements_info,$itmk_to_e_list;
			global $log,$rp,$mode;
			global ${'itm'.$iid},${'itmk'.$iid},${'itme'.$iid},${'itms'.$iid},${'itmsk'.$iid};
			if(!${'itms'.$iid})
			{
				$log.="道具来源非法。<br>";
				$mode = 'command';
				return;
			}
			//打包
			$i_arr['itm'.$iid]['itme']= ${'itme'.$iid};$i_arr['itm'.$iid]['itms']= ${'itms'.$iid};
			$i_arr['itm'.$iid]['itm']= ${'itm'.$iid};$i_arr['itm'.$iid]['itmk']= ${'itmk'.$iid};$i_arr['itm'.$iid]['itmsk']= ${'itmsk'.$iid};
			//过滤掉不能分解的道具
			$filter_flag = split_to_elements_filter($i_arr['itm'.$iid]);
			if($filter_flag)
			{
				$log.="不能分解此道具。<br><br>";
				include_once GAME_ROOT.'./include/game/itemmain.func.php';
				itemfind();
				return;
			}
			//分解道具获得元素
			$log.="<span class='grey'>".${'itm'.$iid}."化作点点荧光四散开来……</span><br>";
			//计算能获得的元素种类与数量
			$i_arr = get_evalues_by_iarr($i_arr);
			//销毁道具
			${'itm'.$iid} = ${'itmk'.$iid} = ${'itmsk'.$iid} = '';
			${'itme'.$iid} = ${'itms'.$iid} = 0;
			//增加对应的元素
			$total_addev = 0;
			foreach($i_arr as $e_key=>$ev)
			{
				if(is_array($ev) && count($ev)>3)
				{
					//分解的结果里有参数合法的道具
					global $itm0,$itmk0,$itme0,$itms0,$itmsk0;
					$log.="但出现在你面前的不是元素，而是<span class='yellow'>{$ev[0]}</span>！<br>……这是什么情况……！？<br>";
					$itm0 = $ev[0]; $itmk0 = $ev[1]; $itmsk0 = $ev[4];
					$itme0 = $ev[2]; $itms0 = $ev[3];
					include_once GAME_ROOT.'./include/game/itemmain.func.php';
					itemget();
				}
				else 
				{
					global ${'element'.$e_key};
					${'element'.$e_key} += $ev;
					$total_addev += $ev;
					$log.="获得了{$ev}份{$elements_info[$e_key]}！<br>";
				}
			}
			unset($i_arr);
			//捡垃圾涨功德
			//$ep_dice = diceroll($total_addev);
			$ep_dice = rand(0,$total_addev);
			if($ep_dice>0)
			{
				$rp = $rp-$ep_dice;
				//rp可以为负吗？
			}
			$log.="<br>";
		}
		$mode='command';
		return;
	}

	//通过输入的道具/装备数组计算对应的元素价值 
	//输入的iarr默认的格式：$iarr=Array('arb或itm0'=>Array('itm'=>$arb或$itm0,'itmk'=>$arbk或$itmk0.....),)
	function get_evalues_by_iarr($iarr)
	{
		global $log,$nosta,$elements_info,$temp_etags;
		global $split_itm_fix,$split_spitm_fix,$split_itmk_r,$split_default_itmk_r;
		global $split_itmsk_fix,$split_default_itmsk_fix;

		$ev_arr = Array();
		//获取缓存文件
		$cache_file = GAME_ROOT."./gamedata/bak/elementmix.bak.php";
		if(!file_exists($cache_file)) create_emix_cache_file();
		//$cache_arr = openfile_decode($cache_file);
		include_once $cache_file;
		//开始计算元素价值
		foreach($iarr as $i => $t)
		{
			//最优先：检查拆解特定道具（全名匹配）时的事件
			if(isset($split_itm_fix[$t['itm']]))
			{
				unset($ekey);unset($ev);
				//处理秘钥道具
				if(strpos($split_itm_fix[$t['itm']],'r_')===0)
				{
					//还原配方与素材键名 暂时不考虑秘钥道具=>array的情况 有需要的情况再现加
					//$e_list[0]：配方键名  $e_list[1]：素材所在数组内的位置（不是元素编号）  
					$e_list = explode('-',str_replace('r_','',$split_itm_fix[$t['itm']]));
					//返回随机素材
					if($e_list[1] == 'r')
					{
						$ekey = array_rand($cache_arr['random_emix_list'][$e_list[0]]['stuff']);
						$ev = $cache_arr['random_emix_list'][$e_list[0]]['stuff'][$ekey];
					}
					//返回指定位置素材 遍历配方 找到它的家
					else 
					{
						$e_sort = 0;
						foreach($cache_arr['random_emix_list'][$e_list[0]]['stuff'] as $stuff_key => $stuff_num)
						{
							if($e_sort == $e_list[1])
							{
								$ekey = $stuff_key;
								$ev = $stuff_num;
								break;
							}	
							$e_sort ++;
						}
					}
					$log.="<span class='grey'>你发现构成{$t['itm']}的<span class='red'>{$ev}</span>份{$elements_info[$ekey]}样子有点奇怪……怎么回事呢？</span><br>";
					$ev_arr[$ekey] += $ev;
					//润！
					continue;
				}
				//处理吐出来的道具，一次分解最多只会吐一个道具，后到先得。
				elseif(is_array($split_itm_fix[$t['itm']]['spitm']))
				{
					if(isset($ev_arr['spitm'])) unset($ev_arr['spitm']);
					$ev_arr['spitm'] = $split_itm_fix[$t['itm']]['spitm'];
					continue;
				}
				foreach($split_itm_fix[$t['itm']] as $ekey=>$ev)
				{
					$ev_arr[$ekey] += $ev;
				}
				//echo "【DEBUG】检查到了特殊道具【{$t['itm']}】<br>";
				continue; //道具在特判列表里 不再继续计算后面的内容 直接跳到下一个道具
			}

			//次优先：检查拆解特定道具（关键词匹配）时的事件
			foreach($split_spitm_fix as $spitm=>$sp_ev)
			{
				$sp_flag = 0;
				if(preg_match("/$spitm/",$t['itm']))
				{
					//没有指定获得哪种元素 随机获得一种元素
					if(!is_array($sp_ev))
					{
						$ev_arr[array_rand($elements_info)] += $sp_ev;
					}
					else 
					{
						foreach ($sp_ev as $ekey=>$ev)
							$ev_arr[$ekey] += $ev;
							unset($ekey);unset($ev);
					}
					$sp_flag = 1;//道具在特判列表里 不再继续计算后面的内容 直接跳到下一个道具
					//echo "【DEBUG】检查到了特殊道具【{$t['itm']}】<br>";
				}	
			}

			//道具不能分解 跳过
			if(split_to_elements_filter($t) || $sp_flag) continue;

			//通过道具的效果、耐久，确定原始价值
			if($t['itms'] == $nosta) $t['itms'] = rand(1,10);
			$base_ev = round(($t['itme']+$t['itms'])/2);
			//echo "【DEBUG】{$t['itm']}的基础价值是{$base_ev}<br>";

			//通过道具类别获取价值修正
			$k_t = $t['itmk'];
			if(isset($split_itmk_r[$k_t]))
			{
				//存在对应修正 优先获取
				$k_ev_r = $split_itmk_r[$k_t];
			}
			else 
			{
				//不存在对应修正 先尝试过滤类别
				$k_t = filter_itemkind($k_t);
				//没有对应修正关系则返回默认类别的分解系数
				$k_ev_r = isset($split_itmk_r[$k_t]) ? $split_itmk_r[$k_t] : $split_default_itmk_r;
			}
			//应用价值修正
			$base_ev = ceil($base_ev*$k_ev_r);
			//echo "【DEBUG】{$t['itm']}的类别价值修正系数是{$k_ev_r}，修正后的价值是{$base_ev}。<br>";
			
			//通过道具类别关联元素
			$k_t = $t['itmk']; $k_ekey = '';
			if(isset($cache_arr['flip_d_tag'][$k_t]))
			{
				//存在对应元素 优先获取
				$k_ekey = $cache_arr['flip_d_tag'][$k_t];
			}
			else 
			{
				//不存在对应元素 先尝试过滤类别
				$k_t = filter_itemkind($k_t);
				//还是没有对应元素 返回随机一种元素
				$k_ekey = isset($cache_arr['flip_d_tag'][$k_t]) ? $cache_arr['flip_d_tag'][$k_t] : array_rand($elements_info);
			}
			//echo "【DEBUG】【{$t['itmk']}】{$t['itm']}关联到的元素是【{$elements_info[$k_ekey]}】<br>";
			
			//应用 类别=>元素 的价值
			$ev_arr[$k_ekey] += $base_ev;

			//通过属性计算道具的附加价值
			if(isset($t['itmsk']))
			{
				$t['itmsk'] = get_itmsk_array($t['itmsk']);
				foreach($t['itmsk'] as $tsk)
				{
					//获取单个属性关联的元素 没有则随机挑选一个元素
					$ekey = $cache_arr['flip_s_tag'][$tsk] ? $cache_arr['flip_s_tag'][$tsk] : array_rand($elements_info);
					if(isset($ekey))
					{
						$add_ev = 0;
						//获取属性价值
						if($split_itmsk_fix[$tsk])
						{
							if(is_array($split_itmsk_fix[$tsk]))
							{
								$add_ev = $split_itmsk_fix[$tsk][$t['itmk']] ? $split_itmsk_fix[$tsk][$t['itmk']] : $split_itmsk_fix[$tsk]['default'];
							}
							else 
							{
								$add_ev = $split_itmsk_fix[$tsk];
							}
						}
						else 
						{
							$add_ev = $split_default_itmsk_fix;
						}
						//入列！
						$ev_arr[$ekey] += $add_ev;
						//echo "【DEBUG】{$t['itm']}的【属性{$tsk}】分解出了{$add_ev}份{$elements_info[$ekey]}<br>";
					}
				}
			}
		}
		return $ev_arr;
	}

	/********元素合成部分********/

	//元素喝茶
	function element_mix($emlist,$eitme_max_r=NULL,$eitme_r=NULL)
	{
		global $now,$name,$log,$iteminfo,$itemspkinfo,$elements_info;
		global $no_emix_circulation;
		global $emix_luck_info,$emix_tips_arr,$emix_name_brackets_arr,$emix_name_prefix_arr,$emix_name_meta_arr,$emix_name_tail_arr;
		global $itm0,$itmk0,$itme0,$itms0,$itmsk0;

		if(!$emlist)
		{
			$log.="你不能用不存在的东西合成！<br>";
			return;
		}

		//输入了合法的元素参数，先初始化一些变量。
		$c_times = 0; $total_enum = 0; $dom_ekey = -1; $dom_enum = -1; $multi_dom_ekey = Array(); $emix_flag = NULL; $emix_fix = NULL;
		//自定义效/耐比的阈值：2%~98%
		$eitme_r = isset($eitme_r) ? min(98,max(2,$eitme_r)) : rand(2,98); 
		$eitme_r /= 100;
		//自定义最大效果的阈值：1%~100%
		$eitme_max_r = isset($eitme_max_r) ? min(100,max(1,$eitme_max_r)) : 100; 
		$eitme_max_r /= 100;
		//对参与合成的元素按投入数量降序排序，筛出投入数量最多的元素作为主元素
		arsort($emlist);
		$log.="从口袋中抓出了：<br>";
		foreach($emlist as $ekey=>$enum)
		{
			if($c_times == 0)
			{
				//登记主元素
				$dom_ekey = $ekey;
				$dom_enum = $enum;
			}
			else 
			{
				//其他投入的元素数量与主元素数量相差10以内 也可以提供主要特征
				if($enum >= ($dom_enum-10)) $multi_dom_ekey[$ekey]=$enum;
				//log修正
				$log.="、";
			}
			$log.="{$enum}份{$elements_info[$ekey]}";
			$c_times++;
			//登记投入的元素总量，用于计算效果、耐久
			$total_enum += $enum;
			//实际扣除投入元素，关于元素数量的合法性在提交合成时就判断过了，这里就不重复判断了
			global ${'element'.$ekey};
			${'element'.$ekey} -= $enum;
		}

		$log.="。<br>你紧张地搓了搓手。<br>合成开始了。<br>";

		//检查是否存在固定合成
		$emix_fix = check_in_emix_list($emlist);
		if($emix_fix)
		{
			$log.="<br>但是出现结果的速度比你想象中要快得多！<br>你还没反应过来，元素们就把一样东西吐了出来！<br><br>";
			$itm0 = $emix_fix[0]; $itmk0 = $emix_fix[1]; $itmsk0 = $emix_fix[4];
			$itme0 = $emix_fix[2]; $itms0 = $emix_fix[3];
			include_once GAME_ROOT.'./include/game/itemmain.func.php';
			itemget();
			addnews($now,'emix_success',$name,$emix_fix[0]);
			return;
		}

		//开始随机合成：
		$log.="<span class='grey'>…加入了一点{$emix_tips_arr[array_rand($emix_tips_arr)]}…</span><br>";

		//掷骰：
		//$emix_dice = diceroll(100);
		$emix_dice = rand(1,100);
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
		$log.="<span class='grey'>你感觉{$emix_luck_info[$emix_flag]}</span><br>";

		//生成道具类别：
		$emix_itmk = '';
		//获取主特征：
		$emix_itmk_tags = Array();
		$emix_itmk_tags[] = get_emix_dom_tags($dom_ekey,$dom_enum);
		//有备选的主特征列表，依次获取
		if(count($multi_dom_ekey)>0)
		{
			foreach($multi_dom_ekey as $md_ekey=>$md_enum)
			{
				$emix_itmk_tags[] = get_emix_dom_tags($md_ekey,$md_enum);
			}
		}
		//用获取到的主特征（是个数组）确定道具类别
		$emix_itmk = get_emix_itmk($emix_itmk_tags,$emix_flag);

		$log.="<span class='clan'>你观察到自己投入进去的那坨混合物慢慢有了形状，它似乎能被用作<span class='yellow'>{$iteminfo[$emix_itmk]}</span>。</span><br>";
		$log.="<span class='grey'>…再加一些{$emix_tips_arr[array_rand($emix_tips_arr)]}…</span><br>";

		//生成道具效果、耐久：
		$emix_itme = 0; $emix_itms = 0;
		//根据投入的元素总量 计算其中能够转化为效果、耐久的部分（不会超过当前等级的理论上限值）
		$cost_enum = get_emix_max_cost($total_enum,$eitme_max_r);
		//获取道具效耐比（随机2~98）。$eitem_r：解锁自定义比例功能后，可以自己设置固定的效耐比。
		$emix_itme_r = $eitme_r;
		$emix_itms_r = 1-$emix_itme_r;
		//计算道具效果、耐久
		$emix_itme = ($emix_flag==4) ? $cost_enum : ceil($cost_enum*$emix_itme_r*($emix_flag/10 + 1)); //只投入1份元素，至少也会有1点效果、1点耐久。你赚了我亏了好吧！
		$emix_itms = ($emix_flag==4) ? '∞' : ceil($cost_enum*$emix_itms_r*($emix_flag/10 + 1));//大成功情况下获得无限耐久
		if(strpos($emix_itmk,'D')===0 && $emix_itms=='∞') $emix_itms = $emix_itme; //防具没有这种待遇
		//临时的道具效果修正：强化药物道具效果上限
		if(strpos($emix_itmk,'M')===0 || strpos($emix_itmk,'HM')===0) 
		{
			$emix_itme = ceil(min(30*($emix_flag/10 + 1),$emix_itme));
			$emix_itms = ceil(min(2*($emix_flag/10 + 1),$emix_itms));
		}

		$log.="<span class='clan'>在那形状愈发明晰的时候，你听到<span class='yellow'>{$cost_enum}</span>份</span>{$elements_info[$dom_ekey]}<span class='clan'>在升腾的雾气中喃喃呓语。</span><br>";
		$log.="<span class='grey'>…哎呀，不小心混入了一点{$emix_tips_arr[array_rand($emix_tips_arr)]}…</span><br>";

		//生成道具属性：
		$emix_itmsk = ($no_emix_circulation || $emix_flag==-2) ? Array('v') : Array(); //是否固定带有灵魂绑定属性
		$emix_itmsk_max = ($no_emix_circulation || $emix_flag==-2) ? 4 : 5; //最多能生成几个属性
		//获取次要特征：
		$emix_itmsk_tags = Array();
		foreach($emlist as $ekey=>$enum)
		{
			//把生成道具效果、耐久吃掉的元素，根据投入比例分摊到参与合成的元素上
			//计算每个元素实际的消耗
			$tmp_cost = round($cost_enum * ($enum/$total_enum)); 
			$enum -= $tmp_cost;
			//计算消耗后如果还有盈余，才会检查元素是否有能用来生成属性的次要特征。
			//基本逻辑：低面板出属性代价低，高面板出属性代价高。
			if($enum>0) 
			{
				//获取次要特征
				$tmp_emix_itmsk_tags = Array();
				$tmp_emix_itmsk_tags = get_emix_sub_tags($ekey,$enum,$emix_itmk,$emix_flag);
				$emix_itmsk_tags = array_merge($tmp_emix_itmsk_tags,$emix_itmsk_tags);
			}
		}
		//获取到了次要特征，把次要特征转化为道具属性：
		if(count($emix_itmsk_tags)>0)
		{
			//第三个参数：能保留的属性数量上限
			$emix_itmsk = array_merge($emix_itmsk,get_emix_itmsk($emix_itmsk_tags,$emix_itmk,$emix_itmsk_max));
		}
		//把itmsk从数组转回字符串
		$emix_itmsk = get_itmsk_strlen($emix_itmsk);

		$log.="<span class='clan'>闻到了硫磺、莎草纸、</span>{$elements_info[array_rand($emlist)]}<span class='clan'>与</span><span class='yellow'>".(count($emix_itmsk_tags)+1)."种发酵物</span><span class='clan'>混合的味道。</span><br>";

		//（TODO：合成事件结算阶段）
		$log.="<span class='grey'>…最后再加一点{$emix_tips_arr[array_rand($emix_tips_arr)]}…</span><br><br>";
		$log.="结束了…？<br><br>";

		//出生了！为孩子起个可爱的名字吧
		$emix_itm = ''; $emix_itm_prefix = ''; $emix_itm_meta = ''; $emix_itm_tail = ''; $emix_name_brackets = '';
		//根据主元素获取修饰前缀词组
		$emix_itm_prefix = array_rand($emix_name_prefix_arr[$dom_ekey]);
		$emix_itm_prefix = $emix_name_prefix_arr[$dom_ekey][$emix_itm_prefix];
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
		$emix_itm = $emix_name_brackets[0].$emix_itm_prefix.$emix_itm_meta.$emix_itm_tail.$emix_name_brackets[1];

		if($emix_itm && $emix_itmk && $emix_itme && $emix_itms)
		{
			$itm0 = $emix_itm; $itmk0 = $emix_itmk; $itmsk0 = $emix_itmsk;
			$itme0 = $emix_itme; $itms0 = $emix_itms;
			include_once GAME_ROOT.'./include/game/itemmain.func.php';
			itemget();
			addnews($now,'emix_success',$name,$emix_itm);
		}
		else
		{
			$log.="<span class='red'>……合成失败了！<br>你投入进去的元素也全部打了水漂！<br>怎么这样……</span><br>";
			addnews($now,'emix_failed',$name);
		}
		return;
	}

	//获取当前等级合成出的道具效果上限 这个上限实际上是耐久+效果共用的
	//有点蛋疼
	function get_emix_itme_max()
	{
		global $max_emix_itme_start,$max_emix_itme_up;
		global $lvl;
		//每1级能够提升的上限值 （前32级时的计算公式：基础+提升值*等级*(255-等级)%）（32级后：基础+（提升值+等级）*等级)）
		$max_itme = $lvl<31 ? $max_emix_itme_start+($max_emix_itme_up*$lvl*((255-$lvl)/255)) : $max_emix_itme_start+(($max_emix_itme_up+$lvl)*$lvl);
		$max_itme = ceil($max_itme);
		return $max_itme;
	}

	//通过投入的元素数量 判断合成时最多有多少元素可以转化为效果、耐久
	function get_emix_max_cost($total_enum,$emr=1)
	{
		//获取理论上限
		$max_enum = get_emix_itme_max();
		//通过自定义上限系数修正
		$max_enum *= $emr;
		//判断投入数量有没有超过理论上限
		$max_cost = min(round($max_enum),$total_enum);
		return $max_cost;
	}

	//判断是否存在固定合成
	function check_in_emix_list($emlist)
	{
		global $emix_list,$random_emix_list;
		//先检查固定合成
		foreach($emix_list as $elist)
		{
			if(!array_diff($emlist,$elist['stuff']) && count($emlist) == count($elist['stuff']))
			{
				return $elist['result'];
			}
		}
		//再检查随机合成：尝试获取随机合成配方
		$r_emix_list = merge_random_emix_list();
		foreach($r_emix_list as $rid => $relist)
		{
			if(!array_diff($emlist,$relist['stuff']) && count($emlist) == count($relist['stuff']))
			{
				return $r_emix_list[$rid]['result'];
			}
		}
		return;
	}

	//获取元素主特征
	function get_emix_dom_tags($ekey,$enum)
	{
		global $elements_info,$temp_etags;
		//拉取元素对应的主特征
		$dom_tag = $temp_etags[$ekey]['dom'];
		//只有一个主特征 直接返回
		if(count($dom_tag)==1) return $dom_tag[0];
		//有多个主特征 按规律返回
		//判断投入的元素是单数还是双数份
		$tag_flag = fmod($enum,2); 
		//规律：投入单数份元素，返回第0个主特征；否则返回第1个主特征。如果以后有第2、3、4个主特征呢？以后的事情以后再说吧。
		$dom_tag = $tag_flag ? $dom_tag[0] : $dom_tag[1];
		return $dom_tag;
	}

	//根据主特征输出道具类别
	function get_emix_itmk($dom_tags,$emix_flag=NULL)
	{
		global $dommix_list;
		$emix_itmk = ''; $obbs_fix = 0;
		//根据合成表现修正概率
		if($emix_flag) $obbs_fix = $emix_flag*10;

		if(count($dom_tags)>1)
		{
			//存在复数个主特征，判断能否进行组合
			$mixflag = false;
			foreach($dommix_list as $minfo) 
			{
				//直接抄合成匹配逻辑了 有一种野性的美
				if(!array_diff($dom_tags,$minfo['stuff']) && !array_diff($minfo['stuff'],$dom_tags) && count($dom_tags) == count($minfo['stuff']))
				{ 
					//if($minfo['obbs'] && (diceroll(100)-$obbs_fix)>$minfo['obbs']) continue; 
					if($minfo['obbs'] && (rand(0,100)-$obbs_fix)>$minfo['obbs']) continue; //配方为概率合成 掷骰判定没通过 跳过
					$emix_itmk = $minfo['result'];
					$mixflag = true;
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

	//获取元素次要特征
	function get_emix_sub_tags($ekey,$enum,$kind,$emix_flag)
	{
		global $elements_info,$temp_etags;
		global $split_default_itmsk_fix,$split_itmsk_fix;

		$subtags = Array();
		//获取元素所有的次要特征
		$e_sub_tags = $temp_etags[$ekey]['sub'];
		//筛选出投入数量大于属性价值的次要特征
		foreach($e_sub_tags as $etag)
		{
			//获取属性价值
			if($split_itmsk_fix[$etag])
			{
				if(is_array($split_itmsk_fix[$etag]))
				{
					$evalues = $split_itmsk_fix[$etag][$kind] ? $split_itmsk_fix[$etag][$kind] : $split_itmsk_fix[$etag]['default'];
				}
				else 
				{
					$evalues = $split_itmsk_fix[$etag];
				}
			}
			else 
			{
				$evalues = $split_default_itmsk_fix;
			}
			//配吗？
			if($enum >= $evalues) $sub_tags[$etag] = $evalues;
		}
		//扣除元素存量，并随机生成特征： 
		$e_sub_tags = Array(); $tn = 0;
		if(count($sub_tags)>0)
		{
			//骰一个生成的次要特征上限
			$tag_max = rand(0,count($sub_tags)) + $emix_flag;
			do{
				$skey = array_rand($sub_tags);
				$enum -= $sub_tags[$skey];
				$e_sub_tags[]=$skey;
				unset($sub_tags[$skey]);
				$tn++;
			}while($tn < $tag_max && count($sub_tags)>0);
		}
		return $e_sub_tags;
	}

	//根据次要特征输出道具属性 $max_sk：输出的属性数量上限
	function get_emix_itmsk($sub_tags,$kind,$max_sk=5)
	{
		global $submix_list,$itmk_to_itmsk_tags;
		$sk_value = Array();
		//对传入的次要特征进行组合判断
		if(count($sub_tags)>1)
		{
			//存在复数个次要特征，判断能否进行组合
			//尝试获取随机配方
			$r_submix_list = merge_random_emix_list(1);
			//合并固定配方与随机配方
			$submix_list = array_merge_recursive($r_submix_list,$submix_list);
			foreach($submix_list as $minfo) 
			{
				//属性组合逻辑：匹配素材数量>=要求素材数量 && 不能重复生成属性
				if(count(array_intersect($sub_tags,$minfo['stuff'])) >= count($minfo['stuff']) && !in_array($minfo['result'],$sk_value))
				{ 
					//配方为概率合成 掷骰判定
					if($minfo['obbs']) 
					{
						//获取概率修正
						if(is_array($minfo['obbs']))
						{
							$obbs = $minfo['obbs'][$kind] ? $minfo['obbs'][$kind] : $minfo['obbs']['default'];
						} 
						else 
						{
							$obbs = $minfo['obbs'];
						}
						//if(diceroll(100)>$minfo['obbs']) continue; 
						if(rand(0,100)>$minfo['obbs']) continue; 
					}
					//配对成功！消除素材特征
					foreach($minfo['stuff'] as $m_sub_tags)
					{
						unset($sub_tags[array_search($m_sub_tags,$sub_tags)]);
					}
					//把组合结果丢进待生成的属性队列内
					$sk_value[] = $minfo['result'];
					break;
				}
			}
		}
		//将次要特征合并进待生成的属性队列内
		$sk_value = array_merge($sk_value,$sub_tags); 
		//将传入的道具类别与特征对比，过滤掉一些乱七八糟的属性：
		$kind = substr($kind,0,1); //只用道具类别的首字母判断……这个叫什么？大类！
		foreach($sk_value as $key_sk => $sk)
		{	
			//武器上不会生成“防御性”属性
			if($kind=='W' && in_array($sk,$itmk_to_itmsk_tags['D'])) unset($sk_value[$key_sk]);
			//防具、道具上不会生成“攻击性”属性
			if($kind!='W' && in_array($sk,$itmk_to_itmsk_tags['W'])) unset($sk_value[$key_sk]);
			//补给品只会生成杂项属性
			if($kind!='W' && $kind!='D' && !in_array($sk,$itmk_to_itmsk_tags['misc'])) unset($sk_value[$key_sk]);
		}
		//从尾部筛出超过生成上限的属性
		while(count($sk_value)>$max_sk)
		{
			//shuffle($sub_tags); //按照原顺序筛出，可以优先保留组合出的属性
			array_pop($sk_value);
		}
		return $sk_value;
	}

	//翻转标签数组 以通过类型/属性找到对应的元素
	function flip_temp_etags($tags_arr)
	{
		$cache_arr = Array();
		//翻转标签
		foreach($tags_arr as $eid=>$earr)
		{
			foreach($earr['dom'] as $dom_tag)
			{
				$cache_arr['flip_d_tag'][$dom_tag] = $eid;
			}
			foreach($earr['sub'] as $sub_tag)
			{
				$cache_arr['flip_s_tag'][$sub_tag] = $eid;
			}
		}
		return $cache_arr;
	}

	//打印随机合成结果 记得先执行上面那个翻转数组 把翻转后的数组传进这里 才能确保生成随机属性组合时每个素材都有源头元素
	function create_random_emix_list($cache_arr)
	{
		global $elements_info,$split_itmsk_fix,$split_default_itmsk_fix,$itmk_to_itmsk_tags;
		global $random_emix_list,$random_submix_list;
		$emix_arr = Array();
		//先处理随机合成
		foreach($random_emix_list as $eid=>$elist)
		{
			$emix_arr['random_emix_list'][$eid]['stuff'] = Array(); //缓存文件里只保存键值和配方
			foreach($elist['stuff'] as $ekey=>$enum)
			{
				do{  
					$ekey = array_rand($elements_info);
				}while(isset($emix_arr['random_emix_list'][$eid]['stuff'][$ekey]));  
				$enum = explode('-',substr($enum,1));
				$enum = rand($enum[0],$enum[1]);
				$emix_arr['random_emix_list'][$eid]['stuff'][$ekey]=$enum;
			}
		}
		//然后处理随机属性……呃啊
		//所有参与属性组合的“特征” 都应该存在于传入的$cache_arr（翻转数组）里 这样才能确保它是有“源头”的 
		foreach($random_submix_list as $sid=>$slist)
		{
			$emix_arr['random_smix_list'][$sid]['stuff'] = Array();
			foreach($slist['stuff'] as $skey=>$snum)
			{
				$skey = '';
				if(strpos($snum,'sk_')===0)
				{
					$skey = str_replace('sk_','',$snum);
				}
				elseif(strpos($snum,'v_')===0) 
				{
					$snum = str_replace('v_','',$snum);
					do{
						$skey = array_rand($cache_arr['flip_s_tag']);
						$sv = $split_itmsk_fix[$skey] ? $split_itmsk_fix[$skey] : $split_default_itmsk_fix;
						if(is_array($sv)) $sv = $sv['default'];
					}while($sv<$snum || in_array($emix_arr['random_smix_list'][$sid]['stuff']));
				}
				elseif(strpos($snum,'tags_')===0)
				{
					$snum = $itmk_to_itmsk_tags[str_replace('tags_','',$snum)];
					do{
						shuffle($snum);
						$skey = $snum[0];
					}while(in_array($emix_arr['random_smix_list'][$sid]['stuff']) || !array_key_exists($skey,$cache_arr['flip_s_tag']));
				}
				$emix_arr['random_smix_list'][$sid]['stuff'][] = $skey;
			}
		}
		return array_merge($emix_arr,$cache_arr);
	}

	//合并随机合成模板与素材
	function merge_random_emix_list($type=0)
	{
		global $random_emix_list,$random_submix_list;
		//获取缓存文件
		$cache_file = GAME_ROOT."./gamedata/bak/elementmix.bak.php";
		if(!file_exists($cache_file)) create_emix_cache_file();
		include_once $cache_file;
		//用已生成的随机配方替换模板配方
		if($type == 0)
		{
			$list = $random_emix_list;
			foreach($list as $rkey => $rlist)
			{
				$list[$rkey]['stuff'] = $cache_arr['random_emix_list'][$rkey]['stuff'];
			}
		}
		else 
		{
			$list = $random_submix_list;
			foreach($list as $rkey => $rlist)
			{
				$list[$rkey]['stuff'] = $cache_arr['random_smix_list'][$rkey]['stuff'];
			}
		}
		return $list;
	}

	//生成元素合成相关的临时配置文件，只在有人用了对应社团卡时执行一次 
	//现在直接用更优雅的方式生成php格式文件，可以直接include进来。
	function create_emix_cache_file()
	{
		global $temp_etags,$gamecfg,$gamenum,$log;
		$file = GAME_ROOT."./gamedata/bak/elementmix.bak.php";
		if(file_exists($file))
		{
			//检查本局游戏是不是已经生成过配置文件了
			include_once $file;
			if($cache_arr['gamenum'] == $gamenum)
			{
				return;
				$log.="【DEBUG】如果你看到这条信息，请转告管理员：“调试完记得把注释去掉！”<br>";
			}
			else 
			{
				//文件是旧的，直接删掉
				unlink($file);
			}
		}
		//翻转标签
		$cache_arr = flip_temp_etags($temp_etags);
		//生成随机合成
		$cache_arr = create_random_emix_list($cache_arr);
		//加入本局游戏编号
		$cache_arr['gamenum'] = $gamenum;
		//写入文件
		global $checkstr;
		$cache_str = str_replace('?>','',str_replace('<?','<?php',$checkstr));
		$cache_str .= '$cache_arr = ' . var_export($cache_arr,1).";\r\n?>";
		writeover($file,$cache_str);
		//$log.="【DEBUG】生成了本局游戏对应的临时配置文件。<br>";
		return;
	}

	/********一些可复用函数 也许可以挪到其他地方********/

	//打包尸体 ……
	function pack_corpse(&$edata)
	{
		if($edata && $edata['hp']<=0)
		{
			$tmp_arr = Array();
			foreach(Array('wep','arb','arh','ara','arf','ara') as $i)
			{ //搞笑！
				if($edata[$i.'s'])
				{
					$tmp_arr[$i]['itm'] = $edata[$i];$tmp_arr[$i]['itmk'] = $edata[$i.'k'];$tmp_arr[$i]['itmsk'] = $edata[$i.'sk'];
					$tmp_arr[$i]['itme'] = $edata[$i.'e'];$tmp_arr[$i]['itms'] = $edata[$i.'s'];
				}
			}
			for($iid=1;$iid<=6;$iid++)
			{ //笑不出来了
				$inm = 'itm'.$iid;
				if($edata['itms'.$iid])
				{
					$tmp_arr[$inm]['itm'] = $edata['itm'.$iid];
					$tmp_arr[$inm]['itmk'] = $edata['itmk'.$iid];
					$tmp_arr[$inm]['itmsk'] = $edata['itmsk'.$iid];
					$tmp_arr[$inm]['itme'] = $edata['itme'.$iid];
					$tmp_arr[$inm]['itms'] = $edata['itms'.$iid];
				}
			}
		}
		return $tmp_arr;
	}

	//过滤杂项道具类别（可以作为一个通用型函数） //我好傻
	function filter_itemkind($kind,$check_dualwep=0)
	{
		global $iteminfo;

		//将复合武器拆成两把武器
		if($check_dualwep && strlen($kind)==3)
		{	
			$w1 = 'W'.substr($kind,1,1);
			$w2 = 'W'.substr($kind,2,1);
			$kind = Array($w1,$w2);
			return $kind;
		}
		//过滤道具类别
		foreach($iteminfo as $info_key => $info_value)
		{
			if(strpos($kind,$info_key)===0)
			{
				$kind = $info_key;
				break;
			}
		}
		return $kind;
	}
?>