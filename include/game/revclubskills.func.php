<?php

	if (! defined ( 'IN_GAME' )) {
		exit ( 'Access Denied' );
	}

	# 新社团技能：

	include_once GAME_ROOT.'./include/game/dice.func.php';

	# 获得指定技能 $sk：技能名；$para：$clbpara
	function getclubskill($sk,&$para=NULL)
	{
		global $now,$cskills;
		if(!$para)
		{
			global $clbpara;
			$para = &$clbpara;
		}
		if(isset($cskills[$sk]) && (empty($para['skill']) || !in_array($sk,$para['skill'])))
		{
			$para['skill'][] = $sk;
			# 获得指定技能时 定义预设参数（会覆盖原有参数）
			if(!empty($cskills[$sk]['svars']))
			{
				foreach($cskills[$sk]['svars'] as $key => $var)
					$para['skillpara'][$sk][$key] = $var;
			}
			# 获得指定技能时 定义预设时长（会覆盖原有时长）
			if(!empty($cskills[$sk]['slast']))
			{
				foreach($cskills[$sk]['slast'] as $ltkey => $lt)
				{
					# 定义初次获得时间戳
					if($ltkey == 'lasttimes') $para['starttimes'][$sk] = $now;
					# 防呆
					if($ltkey == 'lasttimes' || $ltkey == 'lastturns') $para[$ltkey][$sk] = $lt;
				}
			}
		}
		return;
	}

	# 失去指定技能 $sk：技能名；$para：$clbpara
	function lostclubskill($sk,&$para=NULL)
	{
		if(!$para)
		{
			global $clbpara;
			$para = &$clbpara;
		}
		if(in_array($sk,$para['skill']))
		{
			$sk_key = array_search($sk,$para['skill']);
			unset($para['skill'][$sk_key]);
			# 失去指定技能时，注销对应技能参数
			if(!empty($para['skillpara'][$sk]))
			{
				unset($para['skillpara'][$sk]);
			}
			# 失去指定技能时，注销对应技能持续时间
			if(!empty($para['lasttimes'][$sk]))
			{
				unset($para['lasttimes'][$sk]);
				unset($para['starttimes'][$sk]);
			}
			# 失去指定技能时，注销对应技能持续回合
			if(!empty($para['lastturns'][$sk]))
			{
				unset($para['lastturns'][$sk]);
			}
		}
		return;
	}

	# 激活指定技能（技能名，额外参数）（这个做法还是太离谱，暂时废弃）
	/*function actclbskills($data,$sk,$skpara=NULL)
	{
		global $log,$cskills;

		if(isset($cskills[$sk]))
		{
			# 合法性检查
			$flag = check_skill_unlock($sk,$data);
			if($flag) return;
			include_once GAME_ROOT.'./include/game/revclubskills_extra.func.php';
			$sk_func = 'skill_'.$sk.'act';
			if(function_exists($sk_func))
			{
				if(!empty($skpara))
					eval("$sk_func($skpara);");
				else
					eval("$sk_func();");
			}
		}
	}*/

	# 升级指定技能 $sk：技能名；$nums：升级次数
	function upgclbskills($sk,$nums=1)
	{
		global $log,$club,$clbpara,$skillpoint,$gamecfg;
		global $cskills;

		# 合法性检查
		$flag = check_can_upgclbskills($sk,$nums);
		if(!$flag) return;

		# 获取技能信息
		$cskill = $cskills[$sk];
		# 检查技能是否存在等级
		if(isset($cskill['maxlvl']))
		{
			$now_clvl = isset($clbpara['skillpara'][$sk]['lvl']) ? $clbpara['skillpara'][$sk]['lvl'] : 0;
			if($now_clvl >= $cskill['maxlvl'])
			{
				$log .= "该技能已到达等级上限！<br>";
				return;
			}
		}
		# 获取技能此次升级需要消耗的技能点
		$cost = isset($now_clvl) ? $cskill['cost'][$now_clvl] :  $cskill['cost'];
		if($nums > 1) $cost *= $nums; 
		if($cost > $skillpoint)
		{
			$log .= "技能点不足。<br>";
			return;
		}
		
		# 获取技能升级后文本
		$clog = $cskill['log'];
		$clog = str_replace("[:cost:]",$cost,$clog);
		# 检查技能升级后会触发的事件：
		if(isset($cskill['events']))
		{
			foreach($cskill['events'] as $event)
			{
				$flag = upgclbskills_events($event);
			}
			# 会触发多个事件时，只要有一个事件成功触发就会继续升级流程
			if(!$flag) return;
		}
		# 检查技能升级是否会直接影响属性：
		if(isset($cskill['status']))
		{
			# 检查当前社团是否存在特殊效果判定
			$ceffect = isset($cskill['effect'][$club]) ? $cskill['effect'][$club] : $cskill['effect'][0];
			# 遍历该技能关联属性
			foreach($cskill['status'] as $snm)
			{
				# $snm为数组时，代表关联的属性是技能参数 - $clbpara['skillpara']['技能名']['参数名']
				if(is_array($snm))
				{
					foreach($snm as $skey)
					{
						$clbpara['skillpara'][$sk][$skey] += $ceffect[$skey];
					}
				}
				else 
				{
					global $$snm;
					# $ceffect为数组时，视为技能升级时会使多个属性增加不同值
					if(is_array($ceffect))
					{
						$$snm += $ceffect[$snm]*$nums;
						$clog = str_replace("[:effect:]".$snm,$ceffect[$snm]*$nums,$clog);
					}
					# 否则技能所影响的属性都增加相同值
					else
					{
						$$snm += $ceffect*$nums;
						$clog = str_replace("[:effect:]",$ceffect*$nums,$clog);
					}
				}
			}
		}
		# 扣除技能点
		$skillpoint -= $cost;
		$log .= $clog;
		# 存在复选框的技能，升级后重载技能页面
		if(isset($cskill['num_input']))
		{
			global $opendialog;
			$opendialog = 'skillpage';
		}
		return;
	}

	# 升级指定技能会触发的事件，返回0时代表无法升级技能
	function upgclbskills_events($event)
	{
		global $log;
		if($event == 'heal')
		{
			# 升级治疗技能时，回复满生命、体力，并清空所有异常状态
			global $hp,$mhp,$sp,$msp,$inf;
			$heal_flag = 0;
			if(!empty($inf))
			{
				$inf = ''; 
				$heal_flag = 1;
				$log .= "你的所有异常状态全部解除了！<br>";
			}
			if($hp < $mhp || $sp < $msp)
			{
				$hp = $mhp; $sp = $msp;
				$heal_flag = 1;
				$log .= "你的生命与体力完全恢复了！<br>";
			}
			if(!$heal_flag)
			{
				$log .= "你不需要使用这个技能！<br>";
				return 0;
			}
		}
		return 1;
	}

	# 升级技能时的合法性检查
	function check_can_upgclbskills($sk,$nums)
	{
		global $log,$gamecfg;
		global $club,$clbpara,$skillpoint;
		global $cskills,$cskills_blist,$cskills_wlist;
		if($nums <= 0)
		{
			$log.="输入了无效的升级指令。<br>";
			return 0;
		}
		if(!in_array($sk,$clbpara['skill']))
		{
			$log.="该技能不存在！<br>";
			return 0;
		}
		# 只有特定技能可以一次性升复数级
		if($nums != 1 && !isset($cskills[$sk]['num_input']))
		{
			$log.="该技能每次只能提升1级。";
			return;
		}
		if(array_key_exists($sk,$cskills_wlist) && !in_array($club,$cskills_wlist[$sk]))
		{
			$log.="你不能升级此技能。<br>";
			return 0;
		}
		if(array_key_exists($sk,$cskills_blist) && in_array($club,$cskills_blist[$sk]))
		{
			$log.="你不能升级此技能。<br>";
			return 0;
		}
		return 1;
	}

	# 技能是否解锁，返回为0时解锁，否则返回对应的未满足条件  $sk：技能名；$data：角色数据
	function check_skill_unlock($sk,$data)
	{
		global $cskills;
		$data['clbpara'] = get_clbpara($data['clbpara']);
		if(!in_array($sk,$data['clbpara']['skill']))
		{
			return 1;
		}
		if(!empty($cskills[$sk]['unlock']))
		{
			$unlock = $cskills[$sk]['unlock'];
			foreach($unlock as $key => $lock)
			{
				if(strpos($key,'+')!==false) 
				{
					$arr_key = explode("+",$key);
					foreach($arr_key as $skey)
					{
						$lock = str_replace("[:".$skey.":]","\$data['".$skey."']",$lock);
					}
					if(!eval("return ($lock);")) return $key;
				}
				else 
				{
					$lock = str_replace("[:".$key.":]","\$data['".$key."']",$lock);
					if(!eval("return ($lock);")) return $key;
				}
			}
		}
		return 0;
	}

	# 技能是否可激活，返回0时为可激活，否则返回对应的未满足条件 $sk：技能名；$data：角色数据
	function check_skill_active($sk,$data)
	{
		global $cskills;
		# 不满足激活条件输出的文本，先写在这里，之后挪到配置文件里
		$cannot_active_log = Array(
			0 => '怒气不足，需要<span class="red">[:ragecost:]</span>点怒气',
		);
		# 检查技能需要消耗的怒气条件是否满足
		$ragecost = get_skillvars($sk,'ragecost');
		if($ragecost && $data['rage'] < $ragecost)
		{
			$clog = str_replace('[:ragecost:]',$ragecost,$cannot_active_log[0]);
			return $clog;
		}
		return 0;
	}

	# 获取指定技能标签 $sk：技能名；$stag：要寻找的特定标签；(非必须)$para：$clbpara
	function get_skilltags($sk,$stag='',&$para=NULL)
	{
		global $cskills;
		$cskill = $cskills[$sk];
		if(isset($cskill['tags']))
		{
			if(!empty($stag))
			{
				if(in_array($stag,$cskill['tags'])) return 1;
				return 0;
			}
			else 
			{
				return $cskill['tags'];
			}
		}
		return 0;
	}

	# 获取指定技能静态参数 $sk：技能名；$vars：要寻找的静态参数名；$sklvl：当前技能等级
	function get_skillvars($sk,$vars,$sklvl=NULL)
	{
		global $cskills;
		if(isset($cskills[$sk]['vars'][$vars]))
		{
			$sk_var = $cskills[$sk]['vars'][$vars];
			if(is_array($sk_var) && isset($sklvl)) $sk_var = $sk_var[$sklvl];
			return $sk_var;
		}
		return 0;
	}

	# 获取保存在clbpara内的指定技能参数  $sk：技能名；$skpara：指定技能参数；$data：角色数据
	function get_skillpara($sk,$skpara,$data)
	{
		if(isset($data['clbpara']['skillpara'][$sk][$skpara])) return $data['clbpara']['skillpara'][$sk][$skpara];
		return 0;
	}

	# 获取指定技能等级
	function get_skilllvl($sk,$data)
	{
		$sk_lvl = isset($data['clbpara']['skillpara'][$sk]['lvl']) ? $data['clbpara']['skillpara'][$sk]['lvl'] : 0;
		return $sk_lvl;
	}

	# 格式化指定技能描述文本 $shortdesc：不显示等级、标签描述
	function parse_skilldesc($sk,$data,$shortdesc=0)
	{
		global $cskills;
		# 初始化技能描述
		$cskill = $cskills[$sk];
		$sk_desc = $shortdesc && isset($cskill['bdesc']) ? $cskill['bdesc'] : $cskill['desc'];
		# 技能存在标签时
		if(isset($cskill['tags']) && !$shortdesc)
		{
			$tag_desc = '';
			foreach($cskill['tags'] as $sk_tag)
			{
				if($sk_tag == 'battle') $tag_desc .= '<span tooltip="可以在战斗中主动使用" class="gold">【战斗技】</span>';
				if($sk_tag == 'active') $tag_desc .= '<span tooltip="不能在追击/鏖战状态下使用" class="gold">【先制】</span>';
				if($sk_tag == 'passive') $tag_desc .= '<span tooltip="满足条件时自动触发" class="gold">【被动技】</span>';
			}
			if(!empty($tag_desc)) $tag_desc .= ' ';
		}
		# 技能存在等级时
		if(isset($cskill['maxlvl']))
		{
			$max_lvl_flag = 0;
			$now_clvl = get_skilllvl($sk,$data);
			if($now_clvl >= $cskill['maxlvl'])
			{
				$max_lvl_flag = 1;
				if(!$shortdesc) $lvl_desc = "<span class='lime'>当前级别{$now_clvl}/{$cskill['maxlvl']}</span>&nbsp;<br>";
			}
			else 
			{
				if(!$shortdesc) $lvl_desc = "<span class='lime'>当前级别{$now_clvl}/{$cskill['maxlvl']}</span>&nbsp;消耗<span class='lime'>[:cost:]</span>点技能点升至下一级<br>";
			}
		}
		# 格式化技能描述前缀
		if(!$shortdesc && isset($lvl_desc)) $sk_desc = $lvl_desc.$sk_desc;
		if(!$shortdesc && isset($tag_desc)) $sk_desc = $tag_desc.$sk_desc;
		# 技能存在升级消耗时
		if(isset($cskill['cost']))
		{
			$cost = isset($now_clvl) ? $cskill['cost'][$now_clvl] : $cskill['cost'];
			# 替换描述文本内的技能点消耗值
			$sk_desc = str_replace("[:cost:]",$cost,$sk_desc);
		}
		# 技能存在其他静态参数时
		if(isset($cskill['vars']))
		{
			foreach($cskill['vars'] as $key => $var)
			{
				# 静态参数是数组的情况下 选用当前等级对应的参数
				if(is_array($var)) $var = $var[$now_clvl];
				# 替换描述文本
				$sk_desc = str_replace("[:".$key.":]",$var,$sk_desc);
			}
		}
		# 如果输入了玩家数据，则可以根据数据判定技能关联，否则关联默认属性
		if(isset($cskill['status']))
		{
			# 检查技能升级是否会直接影响属性：
			$ceffect = !empty($data) && isset($cskill['effect'][$data['club']]) ? $cskill['effect'][$data['club']] : $cskill['effect'][0];
			# 遍历技能关联属性，替换介绍文本内的数值
			foreach($cskill['status'] as $snm)
			{
				if(!is_array($snm))
				{
					# $ceffect为数组时，视为技能升级时会使多个属性增加不同值
					if(is_array($ceffect))
					{
						$sk_desc = str_replace("[:effect:]".$snm,$ceffect[$snm],$sk_desc);
					}
					# 否则技能所影响的属性都增加相同值
					else
					{
						$sk_desc = str_replace("[:effect:]",$ceffect,$sk_desc);
					}
				}
			}
		}
		return $sk_desc;
	}

?>
