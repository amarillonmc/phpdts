<?php

	if(!defined('IN_GAME')) {
		exit('Access Denied');
	}

	//zai jian le suoyoude global
	global $gamecfg,$elements_info;//这个$gamecfg到底是在哪定义的……
	include_once config('elementmix',$gamecfg);

	/********拆解元素部分********/

	//过滤掉不能拆解的道具 不能分解返回1 能分解返回0
	function split_to_elements_filter($i)
	{
		global $no_itm_to_e_list,$no_itmk_to_e_list,$no_itmsk_to_e_list;
		if($i)
		{
			//过滤不能分解的道具种类
			if(in_array($i['itmk'],$no_itmk_to_e_list)) return 1;
			//过滤不能分解的道具名
			foreach($no_itm_to_e_list as $no_itm)
			{
				if(strpos($i['itm'],$no_itm)!==false) return 1;
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
		$tmp_arr = Array();
		if($edata)
		{
			global $elements_info,$no_type_to_e_list,$corpse_to_e_lvl_r;
			global $log,$rp;
			//过滤不能分解的尸体
			if(in_array($edata['type'],$no_type_to_e_list))
			{
				$log.="无法从{$edata['name']}身上提炼元素……为什么呢？<br><br>";
				return;
			}
			//成功从尸体中提炼元素
			$log.="{$edata['name']}化作点点荧光四散开来……<br>";
			//根据尸体等级计算能获得的全种类元素数量
			$ev_lvl = ceil($edata['lvl']*$corpse_to_e_lvl_r);
			//把尸体上的装备道具一起打包
			$corpse_itm_arr = pack_corpse($edata);
			//计算从尸体的装备上能获得的元素种类与数量
			$ev_arr = get_evalues_by_iarr($corpse_itm_arr);
			//增加对应的元素
			foreach($elements_info as $e_key=>$e_info)
			{
				global ${'element'.$e_key};
				$add_ev = $ev_arr[$e_key] + $ev_lvl;
				${'element'.$e_key} += $add_ev;
				$log.="提取到了{$add_ev}份{$e_info}！<br>";
			}
			//销毁尸体
			destory_corpse($edata);
			//你也一块去吧！
			unset($ev_arr); 
			//炼人油败人品
			$ep_dice = rand(0,100);
			if($ep_dice>70)
			{
				$rp += $ep_dice;
				$log.="……但是这一切真的值得吗？<br>";
			}
			$log.="<br>";
		}
		return;
	}

	//把道具打散成元素 改下传入的参数其实也可以拆装备
	function split_item_to_elements($iid=NULL)
	{
		if(isset($iid))
		{
			global $elements_info,$itmk_to_e_list;
			global $log,$rp;
			global ${'itm'.$iid},${'itmk'.$iid},${'itme'.$iid},${'itms'.$iid},${'itmsk'.$iid};
			if(!${'itms'.$iid})
			{
				$log.="道具来源非法。<br>";
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
			$log.=${'itm'.$iid}."化作点点荧光四散开来……<br>";
			//计算能获得的元素种类与数量
			$i_arr = get_evalues_by_iarr($i_arr);
			//增加对应的元素
			foreach($i_arr as $e_key=>$ev)
			{
				global ${'element'.$e_key};
				${'element'.$e_key} += $ev;
				$log.="提取到了{$ev}份{$elements_info[$e_key]}！<br>";
			}
			//销毁道具
			${'itm'.$iid} = ${'itmk'.$iid} = ${'itmsk'.$iid} = '';
			${'itme'.$iid} = ${'itms'.$iid} = 0;
			//一起一起
			unset($i_arr);
			//捡垃圾涨功德
			$ep_dice = rand(0,100);
			if($ep_dice>70)
			{
				$rp = max(0,$rp-$ep_dice);
				//$log.="忽闻遥远天外飞来一句赞叹：“感谢你对幻境环卫事业作出的贡献！”<br>";
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
		global $itm_to_e_fix,$itmk_to_e_fix,$itmk_to_e_r,$itmsk_to_e_fix;
		global $emix_sub_tags_values,$emix_sub_tags_default_values;

		$ev_arr = Array();
		//通过缓存文件获取翻转数组……是不是真的有必要这么搞啊……？
		$cache_file = GAME_ROOT."./gamedata/bak/elementmix.bak";
		$flip_etags_arr = file_exists($cache_file) ? openfile_decode($cache_file) : flip_temp_etags($temp_etags);
		//开始计算元素价值
		foreach($iarr as $i => $t)
		{
			//重复过滤一次
			if(split_to_elements_filter($t)) continue;
			//检查拆解固定道具时的事件
			//通过道具名为道具关联一个元素
			if($itm_to_e_fix[$t['itm']])
			{
				foreach($itm_to_e_fix[$t['itm']] as $ekey=>$ev)
				{
					$ev_arr[$ekey] += $ev;
				}
				//道具在特判列表里 不再继续计算后面的内容 直接跳到下一个道具
				continue;
			}
			//通过道具的效果、耐久，确定原始价值
			if($t['itms'] == $nosta) $t['itms'] = rand(1,10);
			$base_ev = round(($t['itme']+$t['itms'])/2);
			//通过道具类别为道具关联一个元素
			//能找到源头元素的情况下绑定源头元素 否则绑定随机元素
			$ekey = $flip_etags_arr['flip_d_tag'][$t['itmk']] ? $flip_etags_arr['flip_d_tag'][$t['itmk']] : array_rand($elements_info);
			//类别价值修正
			$base_ev *= $itmk_to_e_r[$t['itmk']] ? $itmk_to_e_r[$t['itmk']] : 1;
			$base_ev = ceil($base_ev);
			$ev_arr[$ekey] += $base_ev;
			echo "【DEBUG】【{$t['itmk']}】{$t['itm']}分解出了{$base_ev}份{$elements_info[$ekey]}<br>";
			//通过道具属性为道具关联一个或多个元素
			if(isset($t['itmsk']))
			{
				$t['itmsk'] = get_itmsk_array($t['itmsk']);
				foreach($t['itmsk'] as $tsk)
				{
					$ekey = $flip_etags_arr['flip_s_tag'][$tsk] ? $flip_etags_arr['flip_s_tag'][$tsk] : array_rand($elements_info);
					if(isset($ekey))
					{
						$add_ev = 0;
						//获取属性价值
						$add_ev = $emix_sub_tags_values[$tsk] ? $emix_sub_tags_values[$tsk] : $emix_sub_tags_default_values;
						//获取属性价值修正
						if($emix_sub_tags_values_fix[$t['itmk']]) $add_ev = max(0,$add_ev+$emix_sub_tags_values_fix[$t['itmk']]);
						//入列！
						$ev_arr[$ekey] += $add_ev;
						echo "【DEBUG】{$t['itm']}的【属性{$tsk}】分解出了{$add_ev}份{$elements_info[$ekey]}<br>";
					}
				}
			}
		}
		return $ev_arr;
	}

	/********元素合成部分********/

	//元素喝茶
	function element_mix($emlist,$eitme_r=NULL)
	{
		global $log,$elements_info,$default_emix_itme_r,$no_emix_circulation;
		global $emix_name_brackets_arr,$emix_name_prefix_arr,$emix_name_meta_arr,$emix_name_tail_arr;
		//输入了合法的元素参数，先初始化一些变量。
		$c_times = 0; $total_enum = 0; $dom_ekey = -1; $dom_enum = -1; $multi_dom_ekey = Array();
		//自定义效/耐比的阈值：2%~98%
		if(isset($eitme_r)) $eitme_r = min(0.98,max(0.02,$eitme_r));

		//对参与合成的元素按投入数量降序排序，筛出投入数量最多的元素作为主元素
		arsort($emlist);
		$log.="从口袋中抓出了";
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
				//其他元素投入了与主元素相同的数量 把其他元素登记到备选主元素列表里
				if($enum == $dom_enum) $multi_dom_ekey[$ekey]=$enum;
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
		$log.="。<br>……<br>开始了合成。<br><br>";

		//在开始随机合成前，先检查是否存在固定合成。
		//（TODO：固定的元素合成列表）
		//一些想法：元素大师不能合成结局道具啊！
		//一个思路：分解指定关键道具，生成5~4位数随机值的指定元素，把元素=>作为秘钥的随机值保存在每局重置的临时文件里。
		//把分解数个关键道具获得的元素=>秘钥 重新进行元素合成，就可以得到结局道具。

		//开始随机合成：
		//（TODO：随机的合成事件：大失败 失败 普通 成功 大成功）
		//$dice_flag = get_emix_dice_flag();

		//生成道具类别：
		$emix_itmk = '';
		//获取主特征：
		$emix_itmk_tags = Array();
		$emix_itmk_tags[] = get_emix_dom_tags($dom_ekey,$dom_enum);
		//存在复数个主元素，获取多个主特征
		if(count($multi_dom_ekey)>0)
		{
			foreach($multi_dom_ekey as $md_ekey=>$md_enum)
			{
				$emix_itmk_tags[] = get_emix_dom_tags($md_ekey,$md_enum);
			}
		}
		//用获取到的主特征（是个数组）确定道具类别（理论上来说可以存在多个重复的主特征，也许可以加入些套娃配方）
		$emix_itmk = get_emix_itmk($emix_itmk_tags);

		//生成道具效果、耐久：
		$emix_itme = 0; $emix_itms = 0;
		//根据投入的元素总量 计算其中能够转化为效果、耐久的部分（不会超过当前等级的理论上限值）
		$cost_enum = get_emix_max_cost($total_enum);
		//获取道具效果耐久比例。$eitem_r：解锁自定义比例功能后，合成时设定的效/耐比。
		$emix_itme_r = $eitme_r ? $eitme_r : $default_emix_itme_r;
		$emix_itms_r = 1-$emix_itme_r;
		//计算道具效果、耐久
		$emix_itme = ceil($cost_enum*$emix_itme_r); //只投入1份元素，至少也会有1点效果、1点耐久。你赚了我亏了好吧！
		$emix_itms = ceil($cost_enum*$emix_itms_r);

		//生成道具属性：
		$emix_itmsk = $no_emix_circulation ? Array('v') : Array(); 
		$emix_itmsk_max = $no_emix_circulation ? 4 : 5; 
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
				//get_emix_sub_tags()返回的是一个数组。
				//（可选）第四个参数：返回的标签数量上限。暂定2个，是否有必要用升级解锁等方式拓展这个数量？
				$tmp_emix_itmsk_tags = get_emix_sub_tags($ekey,$enum,$emix_itmk,2);
				$emix_itmsk_tags = array_merge($tmp_emix_itmsk_tags,$emix_itmsk_tags);
			}
		}
		//获取到了次要特征
		if(count($emix_itmsk_tags)>0)
		{
			//把次要特征转化为道具属性。第三个参数：能保留的属性数量上限
			$emix_itmsk = array_merge($emix_itmsk,get_emix_itmsk($emix_itmsk_tags,$emix_itmk,$emix_itmsk_max));
		}
		//把itmsk从数组转回字符串
		$emix_itmsk = get_itmsk_strlen($emix_itmsk);

		//（TODO：合成事件结算阶段）

		//出生了！为孩子起个可爱的名字吧
		$emix_itm = ''; $emix_itm_prefix = ''; $emix_itm_meta = ''; $emix_itm_tail = ''; $emix_name_brackets = '';
		//根据主元素获取修饰词前缀
		$emix_itm_prefix = $emix_name_prefix_arr[$dom_ekey][array_rand($emix_name_prefix_arr[$dom_ekey])];
		//随便挑一个元素作为过渡词缀
		$emix_itm_meta = $emix_name_meta_arr[array_rand($emlist)];
		$emix_itm_meta = $emix_itm_meta[array_rand($emix_itm_meta)];
		//根据生成的类别获取一个尾巴 
		//这部分太畜生了，之后得改一个正常点的
		switch ($emix_itmk)
		{
			case strpos($emix_itmk,'WK')!==false:
				$emix_itm_tail = $emix_name_tail_arr[0][array_rand($emix_name_tail_arr[0])];
				break;
			case strpos($emix_itmk,'WP')!==false:
				$emix_itm_tail = $emix_name_tail_arr[1][array_rand($emix_name_tail_arr[1])];
				break;
			case strpos($emix_itmk,'WC')!==false:
				$emix_itm_tail = $emix_name_tail_arr[2][array_rand($emix_name_tail_arr[2])];
				break;
			case strpos($emix_itmk,'WG')!==false:
				$emix_itm_tail = $emix_name_tail_arr[3][array_rand($emix_name_tail_arr[3])];
				break;
			case strpos($emix_itmk,'WD')!==false:
				$emix_itm_tail = $emix_name_tail_arr[4][array_rand($emix_name_tail_arr[4])];
				break;
			case strpos($emix_itmk,'WF')!==false:
				$emix_itm_tail = $emix_name_tail_arr[5][array_rand($emix_name_tail_arr[5])];
				break;
			case strpos($emix_itmk,'D')!==false:
				$emix_itm_tail = $emix_name_tail_arr[6][array_rand($emix_name_tail_arr[6])];
				break;
			case strpos($emix_itmk,'H')!==false:
				$emix_itm_tail = $emix_name_tail_arr[7][array_rand($emix_name_tail_arr[7])];
				break;
			default:
				//要补一个乱七八糟词组，乱七八糟的类别都套这个名字
				$emix_itm_tail = $emix_name_tail_arr[6][array_rand($emix_name_tail_arr[6])];
				return;
		}
		//根据合成出的道具效果生成一个能大幅提升时髦值的括号
		$emix_name_brackets = $emix_itme/100;
		$emix_name_brackets += rand(0,1);
		$emix_name_brackets = min(max(0,$emix_name_brackets),8);
		$emix_name_brackets = explode('+',$emix_name_brackets_arr[$emix_name_brackets]);
		//出生！
		$emix_itm = $emix_name_brackets[0].$emix_itm_prefix.$emix_itm_meta.$emix_itm_tail.$emix_name_brackets[1];

		global $now,$name;
		if($emix_itm && $emix_itmk && $emix_itme && $emix_itms)
		{
			global $itm0,$itmk0,$itme0,$itms0,$itmsk0;
			$itm0 = $emix_itm; $itmk0 = $emix_itmk; $itmsk0 = $emix_itmsk;
			$itme0 = $emix_itme; $itms0 = $emix_itms;
			include_once GAME_ROOT.'./include/game/itemmain.func.php';
			itemget();
			addnews($now,'emix_success',$name,$emix_itm);
		}
		else
		{
			$log.="<br>……但是合成失败了！你的元素也全部木大了！真是遗憾呐！<br>";
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
	function get_emix_max_cost($total_enum)
	{
		//获取理论上限
		$max_enum = get_emix_itme_max();
		//判断投入数量有没有超过理论上限
		$max_cost = min($max_enum,$total_enum);
		return $max_cost;
	}

	//获取元素主特征
	function get_emix_dom_tags($ekey,$enum)
	{
		global $elements_info,$temp_etags;
		$e_dom_tags = $temp_etags[$ekey]['dom'];
		$e_dom_tag = is_array($e_dom_tags) ? $e_dom_tags[array_rand($e_dom_tags)] : $e_dom_tags;
		return $e_dom_tag;
	}

	//根据主特征输出道具类别
	function get_emix_itmk($dom_tags)
	{
		global $emix_dom_mixlist;
		$emix_itmk = 'E'; //DEBUG用
		if(count($dom_tags)>1)
		{
			//存在复数个主特征，判断能否进行组合
			$mixflag = false;
			foreach($emix_dom_mixlist as $minfo) 
			{
				//直接抄合成匹配逻辑了 有一种野性的美
				if(!array_diff($dom_tags,$minfo['stuff']) && !array_diff($minfo['stuff'],$dom_tags) && count($dom_tags) == count($minfo['stuff']))
				{ 
					if($minfo['obbs'] && rand(1,100)>$minfo['obbs']) continue; //配方为概率合成 掷骰判定没通过 跳过
					$emix_itmk = $minfo['result'];
					$mixflag = true;
					break;
				}
			}
			//没有匹配的组合 随机挑一个吧
			if(!$mixflag) $emix_itmk = $dom_tags[array_rand($dom_tags)];
		}
		else
		{
			//只有一个主特征
			$emix_itmk = $dom_tags[0];
		}
		return $emix_itmk;
	}

	//获取元素次要特征 $tag_max：输出的次要特征数量上限
	function get_emix_sub_tags($ekey,$enum,$kind,$tag_max=4)
	{
		global $elements_info,$temp_etags,$emix_sub_tags_values,$emix_sub_tags_default_values,$emix_sub_tags_values_fix;
		$ev_sub_tags = Array();
		$e_sub_tags = $temp_etags[$ekey]['sub'];
		//根据价值筛选出符合条件的次要特征
		foreach($e_sub_tags as $etag)
		{
			//获取特征价值
			$evalues = $emix_sub_tags_values[$etag] ? $emix_sub_tags_values[$etag] : $emix_sub_tags_default_values;
			//根据道具类别获取特征价值修正（暂时没有）
			if(isset($emix_sub_tags_values_fix[$kind][$ekey]))
			{
				$evalues_fix = $emix_sub_tags_values_fix[$kind][$ekey];
				$evalues += $evalues_fix;
			}
			//配吗？
			if($enum >= $evalues) $ev_sub_tags[$etag] = $evalues;
		}
		//从特征中进一步挑选出高价值对象、限制生成的特征个数
		//参数里传进来道具类别了，如果想限制类似【武器上不能有防御属性】【防具上不能有攻击属性】，可以在这里判断。
		//但是我觉得来点乱七八糟的属性也挺好的（
		$e_sub_tags = Array(); $tn = 0;
		if(count($ev_sub_tags)>0)
		{
			//对价值数组降序排序 优先生成高价值属性
			arsort($ev_sub_tags);
			foreach($ev_sub_tags as $skey=>$values)
			{
				if($enum >= $values)
				{
					//扣除存款，输出对应特征
					$enum -= $values;
					$e_sub_tags[]=$skey;
					$tn++;
				}
				//到达可生成的特征上限 返回
				if($tn >= $tag_max) break;
			}
		}
		return $e_sub_tags;
	}

	//根据次要特征输出道具属性 $max_sk：输出的属性数量上限
	function get_emix_itmsk($sub_tags,$kind,$max_sk=5)
	{
		global $emix_sub_mixlist,$emix_sub_mix_obbs_fix;
		$sk_value = Array();
		//对传入的次要特征进行组合判断
		if(count($sub_tags)>1)
		{
			//存在复数个次要特征，判断能否进行组合
			$mixflag = false;
			foreach($emix_sub_mixlist as $minfo) 
			{
				//属性组合就不要求数量一一对应了 先到先得
				//逻辑：匹配素材数量>=要求素材数量 感觉有点问题 但又好像没有问题 暂时就这么搞！
				if(count(array_intersect($sub_tags,$minfo['stuff'])) >= count($minfo['stuff']))
				{ 
					//配方为概率合成 掷骰判定
					if($minfo['obbs']) 
					{
						//获取概率修正
						if(isset($emix_sub_mix_obbs_fix[$kind][$minfo['result']])) $minfo['obbs'] += $emix_sub_mix_obbs_fix[$kind][$minfo['result']];
						if(rand(1,100)>$minfo['obbs']) continue; 
					}
					//配对成功！消除素材特征
					foreach($minfo['stuff'] as $m_sub_tags)
					{
						unset($sub_tags[array_search($m_sub_tags,$sub_tags)]);
					}
					//把组合结果丢进待生成的属性队列内
					$sk_value[] = $minfo['result'];
					$mixflag = true;
					break;
				}
			}
		}
		//将次要特征合并进待生成的属性队列内
		$sk_value = array_merge($sk_value,$sub_tags); 
		//从尾部筛出过滤属性数量
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

	//将翻转后的标签数组保存在临时文件里 只在有人用了对应社团卡时执行一次 
	//如果不想用这个的话 也可以直接用flip_temp_etags() 但是每次拆东西的时候都要遍历一遍……
	//研究模式随机生成的一些东西也会以这种方式储存
	function create_flip_temp_etags_cache_file($tags_arr)
	{
		global $gamecfg,$gamenum,$log;
		$file = GAME_ROOT."./gamedata/bak/elementmix.bak";
		if(file_exists($file))
		{
			//检查本局游戏是不是已经生成过配置文件了
			$tmp_arr = openfile_decode($file);
			if($tmp_arr['gamenum'] == $gamenum)
			{
				$log.="【DEBUG】配置文件已存在，请不要重复生成。<br>";
				return;
			}
		}
		//翻转标签
		$tags_arr = flip_temp_etags($tags_arr);
		//加入本局游戏编号
		$tags_arr['gamenum'] = $gamenum;
		//写入文件
		writeover_encode($file,$tags_arr);
		$log.="【DEBUG】生成了本局游戏对应的临时配置文件".$file."。<br>";
		return;
	}

	/********一些可复用函数 也许可以挪到其他地方********/

	//销毁尸体
	function destory_corpse(&$edata)
	{
		if($edata && $edata['hp']<=0)
		{
			//$edata['state'] = 16;
			$edata['money'] = 0;
			$edata['weps'] = 0;$edata['arbs'] = 0;$edata['arhs'] = 0;$edata['aras'] = 0;$edata['arfs'] = 0;$edata['arts'] = 0;
			$edata['itms0'] = 0;$edata['itms1'] = 0;$edata['itms2'] = 0;$edata['itms3'] = 0;$edata['itms4'] = 0;$edata['itms5'] = 0;$edata['itms6'] = 0;
			player_save($edata);
		}
		return;
	}

	//打包尸体 ……
	function pack_corpse(&$edata)
	{
		if($edata && $edata['hp']<=0)
		{
			$tmp_arr = Array();
			foreach(Array('wep','arb','arh','ara','arf') as $i)
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

	//数组化itmsk 可能是四面的遗产
	function get_itmsk_array($sk_value)
	{
		global $itemspkinfo;
		$ret = Array();
		$i = 0;
		while ($i < strlen($sk_value))
		{
			$sub = substr($sk_value,$i,1); 
			$i++;
			if(!empty($sub) && array_key_exists($sub,$itemspkinfo)) array_push($ret,$sub); //itmsk里怪东西不少 规范一些 只会加入登记过的属性
		}
		return $ret;		
	}

	//还原itmsk为字符串 $max_length-字符串长度上限 
	function get_itmsk_strlen($sk_value,$max_length=5)
	{
		global $itemspkinfo;
		$ret = ''; $sk_count = 0;
		foreach($sk_value as $sk)
		{
			if(array_key_exists($sk,$itemspkinfo))
			{
				$ret.=$sk;
				$sk_count+=strlen($sk);
			}
			if($sk_count>=$max_length) break;
		}
		return $ret;
	}

	//使用json_encode结构将指定数组写入文件
	function writeover_encode($filename,$arr,$method='rb+')
	{	
		if(is_array($arr))
		{
			$arr = json_encode($arr);
			writeover($filename,$arr,$method);
			chmod($arr,0777);
			return;
		}
		return;
	}

	//打开使用json_encode结构保存的文件，返回整个数组，或单独某节
	function openfile_decode($filename,$key=NULL)
	{
		if(file_exists($filename))
		{
			$data = file_get_contents($filename);
			if($data)
			{
				$data = json_decode($data,true);
				if(isset($key)) return $data[$key];
			}
			return $data;
		}
		return;
	}

?>