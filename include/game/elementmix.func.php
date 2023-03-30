<?php

	if(!defined('IN_GAME')) {
		exit('Access Denied');
	}

	include_once GAME_ROOT.'./include/game/elementmix.calc.php';

	/********界面交互部分********/

	# 显示已了解的元素特征
	function emix_init_elements_tags($eid,$etype,$ekey,$data)
	{
		global $log,$mode,$typeinfo,$elements_info,$gamecfg,$gamevars,$iteminfo,$itemspkinfo;
		include config('elementmix',$gamecfg);
		extract($data,EXTR_REFS);
		$tagdesc = $etype == 'dom' ? '[主]' : '[次]';
		if(!empty($clbpara['elements']['tags'][$eid][$etype][$ekey]))
		{
			$tagid = $temp_etags[$eid][$etype][$ekey];
			$tagcolor = $etype == 'dom' ? '' : '';
			$tagtype = $etype == 'dom' ? 'iteminfo' : 'itemspkinfo';
			$tagskdesc = $$tagtype[$tagid];
			if($tagskdesc == '重击辅助') $tagskdesc = '重击';
			if($tagskdesc == 'HP制御') $tagskdesc = '制御';
			if($tagskdesc == '陷阱探测') $tagskdesc = '探雷';
			$tag = "<span class=\"{$tagcolor}\">{$tagdesc}{$tagskdesc}</span>";
		}
		else
		{
			$tag = "<span class=\"grey\">？</span>";
		}
		return $tag;
	}

	# 显示已了解的元素配方
	function emix_init_elements_info($data)
	{
		global $elements_info,$r_elements_info,$gamecfg,$gamevars,$iteminfo,$itemspkinfo;
		include config('elementmix',$gamecfg);
		extract($data,EXTR_REFS);
		include_once GAME_ROOT.'./include/game/itemplace.func.php';
		if(!empty($clbpara['elements']['info']['d']))
		{
			$smhint = '<span class="blueseed b">已了解到的元素配方（按顺序投入）：</span><br><ul>';
			foreach($emix_fixlist as $key => $list)
			{
				if(!empty($clbpara['elements']['info']['d']['d'.$key]))
				{
					$smhint .= '<li style="margin-left:-20px"><span>';
					foreach($list['stuff'] as $skey => $slist)
					{
						if($skey != 0) $smhint .= ' + ';
						$smhint .= "<span class=''>{$slist[1]}份</span>{$r_elements_info[$slist[0]]}";
					}
					$smhint .= " →  <span class=''>".parse_itemmix_resultshow($list['result'])."</span>";
					$smhint .= '</span></li>';
				}
			}
			$smhint .= '</ul>';
		}
		if(!empty($clbpara['elements']['info']['hd']))
		{
			$smhint .= '<br><span class="blueseed b">奇怪的配方：</span><br><ul>';
			foreach($gamevars['rand_emixfixres'] as $key => $list)
			{
				if(!empty($clbpara['elements']['info']['hd']['h'.$key]))
				{
					$smhint .= '<li style="margin-left:-20px"><span>';
					foreach($list['stuff'] as $skey => $slist)
					{
						if($skey != 0) $smhint .= ' + ';
						if(!empty($clbpara['elements']['info']['hd']['h'.$key]['s'.$skey]))
						{
							$smhint .= "<span class=''>{$slist[1]}份 </span>{$r_elements_info[$slist[0]]}";
						}
						else 
						{
							$smhint .= "<span class='grey'>？？</span>";
						}
					}
					$smhint .= " →  <span class=''>".parse_itemmix_resultshow($rand_emix_fixlist[$key]['result'])."</span>";
					$smhint .= '</span></li>';
				}
			}
			$smhint .= '</ul>';
		}
		if(!empty($clbpara['elements']['info']['dd']))
		{
			$smhint .= '<br><span class="blueseed b">已了解到的主要特征组合式：</span><br><ul>';
			foreach($dommix_list as $key => $list)
			{
				if(!empty($clbpara['elements']['info']['dd']['dd'.$key]))
				{
					$smhint .= '<li style="margin-left:-20px"><span>';
					foreach($list['stuff'] as $skey => $slist)
					{
						if($skey != 0) $smhint .= ' + ';
						$smhint .= "<span class='grey'>[主]</span>".parse_info_desc($slist,'k')."";
					}
					$smobbs = !empty($list['obbs']) ? '('.$list['obbs'].'%)' : '';
					$smhint .= " →  <span class='grey'>{$smobbs}</span>".parse_info_desc($list['result'],'k')."";
					$smhint .= '</span></li>';
				}
			}
			$smhint .= '</ul>';
		}
		if(!empty($clbpara['elements']['info']['sd']))
		{
			$smhint .= '<br><span class="blueseed b">已了解到的次要特征组合式：</span><br><ul>';
			$submix_list = array_merge_recursive($submix_list,$gamevars['rand_emixsubres']);
			foreach($submix_list as $key => $list)
			{
				if(!empty($clbpara['elements']['info']['sd']['sd'.$key]))
				{
					$smhint .= '<li style="margin-left:-20px"><span>';
					foreach($list['stuff'] as $skey => $slist)
					{
						if($skey != 0) $smhint .= ' + ';
						$smhint .= "<span class='grey'>[次]</span>".parse_info_desc($slist,'sk')."";
					}
					$smobbs = !empty($list['obbs']) ? '('.$list['obbs'].'%)' : '';
					$smhint .= " →  <span class='grey'>{$smobbs}</span>".parse_info_desc($list['result'],'sk')."";
					$smhint .= '</span></li>';
				}
			}
			$smhint .= '</ul>';
		}
		return $smhint;
	}

	function print_elements_tags($e_key)
	{
		global $elements_info,$gamecfg,$iteminfo,$itemspkinfo;
		include config('elementmix',$gamecfg);
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
	# 把尸体打散成元素
	function split_corpse_to_elements(&$edata)
	{
		global $log,$mode,$typeinfo,$elements_info,$gamecfg,$gamevars;
		include config('elementmix',$gamecfg);
		if(!isset($data))
		{
			global $pdata;
			$data = &$pdata;
		}
		extract($data,EXTR_REFS);
		if($club != 20)
		{
			$log.="你还想对这具可怜的尸体干什么？<br>";
			$mode = 'command';
			return;
		}
		# 过滤不能分解的尸体
		if(in_array($edata['type'],$no_type_to_e_list))
		{
			$log.="无法从{$edata['name']}身上提炼元素……为什么呢？<br>";
			$mode = 'command';
			return;
		}
		# 开始提炼尸体
		$ev_arr = Array();
		$log.="<span class='grey'>{$edata['name']}化作点点荧光四散开来……</span><br>";

		# 处理绑定有秘钥的尸体
		if(!empty($split_spcorpse_fix[$edata['type']])) esplit_vip_things($ev_arr,$edata,$data);

		# 根据尸体等级计算能获得的全种类元素数量
		$ev_lvl = ceil($edata['lvl']*$split_corpse_lvl_r);

		# 遍历尸体上的装备道具，计算能获得的元素种类与数量
		# 打包
		$corpse_itm_arr = pack_corpse($edata);
		# 计算从尸体的装备上能获得的元素种类与数量
		$ev_arr = esplit_get_values_by_iarr($corpse_itm_arr,$ev_arr,$data);

		# 应用
		$total_addev = 0;
		foreach($elements_info as $e_key=>$e_info)
		{
			$add_ev = ceil($ev_arr[$e_key] + $ev_lvl);
			//如果尸体上有元素，一并获取，不过现在还不能在npc配置文件里预设NPC出生时带的元素
			if($edata['element'.$e_key])
			{
				$add_ev += $edata['element'.$e_key];
				$edata['element'.$e_key] = 0;
			}
			${'element'.$e_key} += $add_ev;
			$total_addev += $add_ev;
			$log.="获得了{$add_ev}份{$e_info}！<br>";
		}

		# 分解的结果有参数合法的特殊道具
		if(!empty($ev_arr['spitm']) && count($ev_arr['spitm'])>3)
		{
			global $itm0,$itmk0,$itme0,$itms0,$itmsk0;
			$log.="但出现在你面前的不是元素，而是<span class='yellow'>{$ev_arr['result'][0]}</span>！<br>……这又是什么鬼东西！<br>";
			$itm0 = $ev_arr['result'][0]; $itmk0 = $ev_arr['result'][1]; $itmsk0 = $ev_arr['result'][4];
			$itme0 = $ev_arr['result'][2]; $itms0 = $ev_arr['result'][3];
			include_once GAME_ROOT.'./include/game/itemmain.func.php';
			itemget();
		}

		# 销毁尸体
		destory_corpse($edata);
		# 炼人油败人品
		$ep_dice = rand(0,$total_addev);
		if($ep_dice>70)
		{
			include_once GAME_ROOT.'./include/game/revcombat.func.php';
			rpup_rev($data,$ep_dice);
			$log.="……但是这一切真的值得吗？<br>";
		}
		$log.="<br>";
		$mode = 'command';
		return;
	}

	# 将道具分解为元素
	function split_item_to_elements($iid=NULL)
	{
		global $log,$mode,$typeinfo,$elements_info,$gamecfg,$gamevars;
		include config('elementmix',$gamecfg);
		if(!isset($data))
		{
			global $pdata;
			$data = &$pdata;
		}
		extract($data,EXTR_REFS);
		if($club != 20)
		{
			$log .= "你的手突然掐住了你的头左右摇摆！<br><span class='yellow'>“你还想要干什么，啊？你还想要干什么！！”</span><br>看来你的手和脑子之间起了一点小摩擦。<br><br>";
			$mode = 'command';
			return;
		}
		$i_arr = Array();
		if(isset($iid))
		{
			if(!${'itms'.$iid})
			{
				$log.="道具来源非法。<br>";
				$mode = 'command';
				return;
			}
			//打包
			$ev_arr = Array();
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
			$ev_arr = esplit_get_values_by_iarr($i_arr,$ev_arr,$data);
			//销毁道具
			${'itm'.$iid} = ${'itmk'.$iid} = ${'itmsk'.$iid} = '';
			${'itme'.$iid} = ${'itms'.$iid} = 0;
			//增加对应的元素
			$total_addev = 0;
			foreach($ev_arr as $e_key => $ev)
			{
				if(!empty($ev))
				{
					if(is_array($ev) && count($ev)>3)
					{
						$log.="但出现在你面前的不是元素，而是<span class='yellow'>{$ev[0]}</span>！<br>……这是什么情况……！？<br>";
						$itm0 = $ev[0]; $itmk0 = $ev[1]; $itmsk0 = $ev[4];
						$itme0 = $ev[2]; $itms0 = $ev[3];
						include_once GAME_ROOT.'./include/game/itemmain.func.php';
						itemget();
					}
					else 
					{
						$ev = ceil($ev);
						${'element'.$e_key} += $ev;
						$total_addev += $ev;
						$log.="获得了{$ev}份{$elements_info[$e_key]}！<br>";
					}
				}
			}
			# 捡垃圾有公德
			$ep_dice = rand(0,$total_addev);
			if($ep_dice>0)
			{
				include_once GAME_ROOT.'./include/game/revcombat.func.php';
				rpup_rev($data,-$ep_dice);
			}
			$log.="<br>";
		}
		$mode='command';
		return;
	}

	/********元素合成部分********/
	# 元素合成准备阶段：处理从界面传入的数据
	function elements_mix_prepare($list,$nums,$itmemax,$itmer)
	{
		global $log,$elements_info,$gamecfg;
		if(!isset($data))
		{
			global $pdata;
			$data = &$pdata;
		}
		extract($data,EXTR_REFS);

		if($club != 20)
		{
			$log .= "你思考了一会儿，还是没明白你到底想要干什么……<br>";
			return;
		}
		# 打散队列
		$list = explode('+',$list);
		$nums = explode('+',$nums);
		# 素材队列上限：6种
		if(count($list)>6 || count($nums)>6)
		{
			$log .= "你投入的元素种类过多，元素们在锅里打起来了，甚至把你也波及到了！<br>
			你被元素们打得奄奄一息！<br>";
			$hp = 1;
			return;
		}
		# 处理传入的系数
		if($itmemax > 100 || $itmemax < 1) $itmemax = 100;
		if($itmer > 98 || $itmer < 1) $itmer = 55;
		$emixarr = Array(); $emixnums = Array(); $farr = Array();
		# 检查素材合法性
		$domkey = Array(); $domnum = 0;
		for($i = 0; $i<= count($list); $i++)
		{
			if(isset($list[$i]) && !empty($nums[$i]))
			{
				# 登记合成素材队列中第i号位使用的元素种类
				$emixarr[$i] = $list[$i];
				# 登记合成素材队列中第i号位使用的元素数量
				$emixnums[$i] = $nums[$i];
				# 检查对应种类元素是否超过库存
				if(empty($farr[$emixarr[$i]])) $farr[$emixarr[$i]] = 0;
				$farr[$emixarr[$i]] += $emixnums[$i];
				if($farr[$emixarr[$i]] > ${'element'.$emixarr[$i]})
				{
					$log .= "{$elements_info[$emixarr[$i]]}库存不足，无法合成。<br>";
					return;
				}
				# 检查是否为主元素
				if($emixnums[$i] >= $domnum - 10)
				{
					$domnum = $emixnums[$i];
					$domkey[] = $i;
				}
			}
		}
		if(!empty($emixarr) && !empty($emixnums))
		{
			element_mix($emixarr,$emixnums,$domkey,$itmemax,$itmer);
		}
		return;
	}

	# 元素合成主流程
	function element_mix($emlist,$emnums,$domkey,$eitmemax = 100,$eitmesr = 55)
	{
		global $now,$log,$iteminfo,$itemspkinfo,$elements_info,$gamecfg;
		include config('elementmix',$gamecfg);

		if(!isset($data))
		{
			global $pdata;
			$data = &$pdata;
		}
		extract($data,EXTR_REFS);
		# 尝试元素合成时 合成操作计数+1
		if(empty($clbpara['achvars']['immix'])) $clbpara['achvars']['immix'] = 1;

		$log.="从口袋中抓出了：<br>";

		# 素材位置 => 元素id
		$dom_log = Array(); $total_enum = 0;
		foreach($emlist as $ekey => $eid)
		{
			$enum = $emnums[$ekey];
			$dom_log[] = "{$enum}份{$elements_info[$eid]}";
			# 登记投入的元素总量，用于计算效果、耐久；
			$total_enum += $enum;
			# 扣除实际投入元素；
			${'element'.$eid} -= $enum;
		}
		if(!empty($dom_log)) $log .= implode('、',$dom_log);

		$log.="。<br>你紧张地搓了搓手。<br>合成开始了。<br>";

		# 判断合成是否存在固定结果
		$flag = emix_check_fix_result($emlist,$emnums,$data);
		if($flag) return;

		# 开始随机结果合成：
		$log.="<span class='grey'>…加入了一点{$emix_tips_arr[array_rand($emix_tips_arr)]}…</span><br>";

		# 检查随机合成运势：
		$emix_flag = emix_check_mix_luck();
		$log.="<span class='grey'>你感觉{$emix_luck_info[$emix_flag]}</span><br>";
		
		# 开始生成道具类别：
		$emix_itmk = '';
		# 取得主特征：
		$emix_itmk_tags = emix_get_domtags($emlist,$emnums,$domkey,$data);
		# 用获取到的主特征（是个数组）确定道具类别
		$emix_itmk = emix_spawn_itmk($emix_itmk_tags,$emix_flag);

		$log.="<span class='clan'>你观察到自己投入进去的那坨混合物慢慢有了形状，它似乎能被用作<span class='yellow'>{$iteminfo[$emix_itmk]}</span>。</span><br>";
		$log.="<span class='grey'>…再加一些{$emix_tips_arr[array_rand($emix_tips_arr)]}…</span><br>";

		# 开始生成道具效果、耐久：
		$esarr = emix_spawn_itmes($emlist,$emnums,$domkey,$eitmemax,$eitmesr,$total_enum,$emix_itmk,$data,$emix_flag);
		if(!$esarr) goto emix_failed_flag;
		$emix_itme = $esarr[0]; $emix_itms = $esarr[1]; 

		$log.="<span class='clan'>在那形状愈发明晰的时候，你听到<span class='yellow'>".($emix_itme+$emix_itms)."</span>份</span>{$elements_info[$emlist[$domkey[0]]]}<span class='clan'>在升腾的雾气中喃喃呓语。</span><br>";
		$log.="<span class='grey'>…哎呀，不小心混入了一点{$emix_tips_arr[array_rand($emix_tips_arr)]}…</span><br>";

		# 生成道具属性：
		$emix_itmsk = Array();
		# 大失败的情况下，道具带有灵魂绑定属性：
		if($no_emix_circulation || $emix_flag == -2) $emix_itmsk[] = 'v';
		# 大成功情况下，道具带有菁英属性：
		if($emix_flag == 4) $emix_itmsk[] = 'Z';
		# 生成道具效果、耐久后，元素数量还有盈余的情况下，生成道具属性
		if($total_enum > 0)
		{
			# 获取元素的次要特征队列：
			$subtags = emix_get_subtags($emlist,$emnums,$emix_itme,$emix_itmk,$data,$emix_flag);
			# 获取到了特征队列大于0，把次要特征转化为道具属性：
			if(!empty($subtags))
			{
				# 转换为道具属性
				$tmp_emix_itmsk = emix_spawn_itmsk($subtags,$emix_itmk,$data,$emix_flag);
				$emix_itmsk = array_merge($emix_itmsk,$tmp_emix_itmsk);
				$log.="<span class='clan'>闻到了硫磺、莎草纸、</span>{$elements_info[$emlist[array_rand($emlist)]]}<span class='clan'>与</span><span class='yellow'>".(count($subtags))."种发酵物</span><span class='clan'>混合的味道。</span><br>";
			}
		}
		# 将itmsk从数组转回字符串
		$emix_itmsk = get_itmsk_strlen($emix_itmsk);

		//（TODO：合成事件结算阶段）
		$log.="<span class='grey'>…最后再加一点{$emix_tips_arr[array_rand($emix_tips_arr)]}…</span><br><br>";
		$log.="结束了…？<br><br>";

		$emix_itm = emix_spawn_itmname($emlist,$emnums,$domkey,$emix_itmk,$emix_itme,$emix_itms,$emix_itmsk,$data,$emix_flag);

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
			emix_failed_flag:
			$log.="<span class='red'>……合成失败了！<br>你投入进去的元素也全部打了水漂！<br>怎么这样……</span><br>";
			addnews($now,'emix_failed',$name);
		}
		# 只要不是大失败，每次进行元素合成都能获得一定的经验
		if($emix_flag > 0) $exp += rand(1,$emix_flag);
		return;
	}

	# 初始化
	function emix_spawn_info()
	{
		global $gamevars;
		if(empty($gamevars['rand_emixfixres'])) esp_spawn_rand_emixfixres();
		if(empty($gamevars['rand_emixsubres'])) esp_spawn_rand_emixsubres();
	}

	# 翻转标签数组 以通过类型/属性找到对应的元素（已废弃）
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