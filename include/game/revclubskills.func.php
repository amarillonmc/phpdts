<?php

	if (! defined ( 'IN_GAME' )) {
		exit ( 'Access Denied' );
	}

	# 新社团技能：

	//include_once GAME_ROOT.'./include/game/dice.func.php';

	# 获得指定技能 $sk：技能名；$para：$clbpara
	function getclubskill($sk,&$para)
	{
		global $now,$cskills;
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
					# 预设不是以回合为单位的 设置初始时间戳
					if($ltkey != 'lastturns') $para['starttimes'][$sk] = $now;
					# 防呆
					if($ltkey == 'lasttimes' || $ltkey == 'lastturns') $para[$ltkey][$sk] = $lt;
				}
			}
			return 1;
		}
		return 0;
	}

	# 失去指定技能 $sk：技能名；$para：$clbpara
	function lostclubskill($sk,&$para)
	{
		global $cskills;
		if(in_array($sk,$para['skill']))
		{
			# 检查技能丢失时是否要执行事件
			if(isset($cskills[$sk]['lostevents']))
			{
				foreach($cskills[$sk]['lostevents'] as $event)
				{
					if(strpos($event,'unactive_')===0)
					{
						$uskid = substr($event,9);
						set_skillpara($uskid,'active',0,$para);
					}
				}
			}
			$sk_key = array_search($sk,$para['skill']);
			unset($para['skill'][$sk_key]);
			# 失去指定技能时，注销对应技能参数
			if(!empty($para['skillpara'][$sk])) unset($para['skillpara'][$sk]);
			# 失去指定技能时，注销对应技能开始时间
			if(!empty($para['starttimes'][$sk])) unset($para['starttimes'][$sk]);
			# 失去指定技能时，注销对应技能持续时间
			if(!empty($para['lasttimes'][$sk])) unset($para['lasttimes'][$sk]);
			# 失去指定技能时，注销对应技能持续回合
			if(!empty($para['lastturns'][$sk])) unset($para['lastturns'][$sk]);
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
	function upgclbskills($sk,$nums=1,$choice=NULL)
	{
		global $log,$club,$clbpara,$skillpoint,$gamecfg,$now;
		global $cskills;

		# 合法性检查
		$flag = check_can_upgclbskills($sk,$nums,$choice);
		if(!$flag) return;

		# 获取技能信息
		$cskill = $cskills[$sk];
		# 获取技能升级后文本
		$clog = isset($cskill['log']) ? $cskill['log'] : '升级成功！<br>';
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
		if(isset($cskill['cost']))
		{
			$cost = isset($now_clvl) ? $cskill['cost'][$now_clvl] :  $cskill['cost'];
			if($nums > 1) $cost *= $nums; 
			if($cost > $skillpoint)
			{
				$log .= "技能点不足。<br>";
				return;
			}
			$clog = str_replace("[:cost:]",$cost,$clog);
		}
		# 检查技能升级后会触发的事件：
		if(isset($cskill['events']))
		{
			foreach($cskill['events'] as $event)
			{
				$flag = upgclbskills_events($event,$sk);
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
				# 格式化属性名
				$snm_key = parse_skillrules($snm,'','clbpara');
				global $$snm_key;
				# 如属性值为数组，则按照登记取值
				$seffect = is_array($ceffect[$snm]) ? $ceffect[$snm][$now_clvl] : $ceffect[$snm];
				# 格式化属性值
				$seffect = parse_skilloperator($seffect);
				$so = is_array($seffect) ? $seffect[0] : ''; 
				$svars = is_array($seffect) ? $seffect[1] : $seffect;
				# 计算属性变化
				eval("$$snm_key = $$snm_key $so $svars*$nums;");
				# 替换升级后文本
				$clog = str_replace("[:{$snm}:]",$svars*$nums,$clog);
			}
		}
		# 检查技能是否是要切换状态
		if(isset($choice))
		{
			$clbpara['skillpara'][$sk]['choice'] = $choice;
		}
		# 扣除技能点
		if(!empty($cost)) $skillpoint -= $cost;
		$log .= $clog;
		# 存在复选框的技能，升级后重载技能页面
		//if(isset($cskill['num_input']))
		//{
			global $opendialog;
			$opendialog = 'skillpage';
		//}
		return;
	}

	# 升级指定技能会触发的事件，返回0时代表无法升级技能
	function upgclbskills_events($event,$sk)
	{
		global $log,$cskills,$clbpara;
		# 事件：治疗
		if($event == 'heal')
		{
			# 事件效果：回复满生命、体力，并清空所有异常状态
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
			return 1;
		}
		# 事件：获取指定技能
		if(strpos($event,'getskill_') === 0)
		{
			# 事件效果：获取一个登记过的技能
			$gskid = substr($event,9);
			if(isset($cskills[$gskid]))
			{
				getclubskill($gskid,$clbpara);
			}
			else 
			{
				$log .= "技能{$gskid}不存在！这可能是一个BUG，请联系管理员。<br>";
				return 0;
			}
			return 1;
		}
		# 事件：为指定技能设置开始时间
		if(strpos($event,'setstarttimes_') === 0)
		{
			$gskid = substr($event,14);
			if(isset($cskills[$gskid])) 
			{
				set_starttimes($gskid,$clbpara);
			}
			else 
			{
				$log .= "技能{$gskid}不存在！这可能是一个BUG，请联系管理员。<br>";
				return 0;
			}
			return 1;
		}
		# 事件：为指定技能设置持续时间
		if(strpos($event,'setlasttimes_') === 0)
		{
			$gskarr = substr($event,13);
			$gskarr = explode('+',$gskarr);
			$gskid = $gskarr[0]; $gsklst = $gskarr[1];
			if(isset($cskills[$gskid]) && $gsklst) 
			{
				set_lasttimes($gskid,$gsklst,$clbpara);
			}
			else 
			{
				$log .= "技能{$gskid}不存在或持续时间{$gsklst}无效！这可能是一个BUG，请联系管理员。<br>";
				return 0;
			}
		}
		# 事件：切换技能的激活状态
		if(strpos($event,'active|') === 0)
		{
			$event = explode('|',$event); $sk = $event[1];
			$now_active = get_skillpara($sk,'active',$clbpara);
			$active = $now_active ? 0 : 1;
			$log .= $active ? "<span class='yellow'>技能已激活！</span><br>" : "<span class='yellow'>停用了技能效果。</span><br>" ; 
			set_skillpara($sk,'active',$active,$clbpara);
		}
		return 1;
	}

	# 升级技能时的合法性检查
	function check_can_upgclbskills($sk,$nums,$choice=NULL)
	{
		global $log,$gamecfg;
		global $pdata,$club,$clbpara,$skillpoint;
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
		if($nums!=1 && !isset($cskills[$sk]['num_input']))
		{
			$log.="该技能每次只能提升1级。";
			return;
		}
		if(isset($choice) && !isset($cskills[$sk]['choice']))
		{
			$log.="该技能无法变更状态。";
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
		# 检查冷却技能是否解锁
		if(check_skill_unlock($sk,$pdata))
		{
			$cskill = $cskills[$sk];
			if(is_array($unlock_flag))
			{
				$unlock_cd = $unlock_flag[1]; $unlock_flag = $unlock_flag[0];
			}
			$unlock_flag = is_array($cskill['lockdesc']) ? $cskill['lockdesc'][$unlock_flag] : $cskill['lockdesc'];
			$log .= $unlock_flag;
			return 0;
		}
		return 1;
	}

	# 技能是否解锁，返回为0时解锁，否则返回对应的未满足条件  $sk：技能名；$data：角色数据
	function check_skill_unlock($sk,$data)
	{
		global $cskills,$now;
		//if(empty($data)) $data = current_player_save();
		$data['clbpara'] = get_clbpara($data['clbpara']);
		if(empty($data['clbpara']['skill']) || !in_array($sk,$data['clbpara']['skill']))
		{
			//echo "技能{$sk}未解锁<br>";
			return "技能未解锁！<br>";
		}
		if(!empty($cskills[$sk]['unlock']))
		{
			$unlock = $cskills[$sk]['unlock'];
			foreach($unlock as $key => $lock)
			{
				# 特判：计算技能冷却时间
				if($key == 'skillcooldown')
				{
					$st = get_starttimes($sk,$data['clbpara']);
					if($st)
					{
						$cd = get_skillvars($sk,'cd');
						if($now < $st+$cd)
						{
							$last_cd = $st+$cd-$now;
							return Array($key,$last_cd);
						}
					}
				}
				# 正常计算
				else
				{
					# 第一层：检查共有几个须置换条件
					$arr = strpos($key,'+')!==false ? explode("+",$key) : Array($key);
					foreach($arr as $skey)
					{
						#第二层：格式化置换条件对应的置换内容
						$skey_value = parse_skillrules($skey);
						#第三层：置换对应内容
						$lock = str_replace("[:{$skey}:]",$skey_value,$lock);
					}
					if(!eval("return ($lock);")) return $key;
				}
			}
		}
		return 0;
	}

	function parse_skillrules($key,$prefix="\$data",$prefix2="['clbpara']")
	{
		//传入值内有'-'号，代表是clbpara内的内容
		if(strpos($key,'-')!==false)
		{
			$key = explode('-',$key);
			$key_key = $key[0]; $key_value = $key[1];
			//检查key_key内是否有“|”号
			if(strpos($key_key,'|')!==false)
			{
				//key_key内有“|”号 代表置换内容的第二层键名另有指代
				$key_key = explode('|',$key_key);
				$key_a_key = $key_key[0]; $key_sec_key = $key_key[1];
				$key = $prefix.$prefix2."['{$key_a_key}']['{$key_sec_key}']['{$key_value}']";
			}
			else 
			{
				//key_key内没有“:”号 置换内容只停留在第一层
				$key = $prefix.$prefix2."['{$key_key}']['{$key_value}']";
			}
		}
		//传入值是标准字段，直接导出
		else 
		{
			if(empty($prefix)) return $key;
			else $key = $prefix."['{$key}']";
		}
		return $key;
	}

	function parse_skilloperator($key)
	{
		//检查传入值内是否有分隔符
		if(strpos($key,'::')!==false)
		{
			$key = explode('::',$key);
			return $key;
		}
		return $key;
	}

	# 技能是否满足消耗条件，返回0时为可激活，否则返回对应的未满足条件 $sk：技能名；$data：角色数据
	function check_skill_cost($sk,$data)
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
	function get_skilltags($sk,$stag='')
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
	function get_skillpara($sk,$skpara,$para)
	{
		if(isset($para['skillpara'][$sk][$skpara])) return $para['skillpara'][$sk][$skpara];
		return 0;
	}

	# 变更保存在clbpara['skillpara']内的指定技能参数
	function set_skillpara($sk,$skpara,$skdata,&$para)
	{
		# 要有这个技能才能设置参数
		if(in_array($sk,$para['skill']))
		{
			$para['skillpara'][$sk][$skpara] = $skdata;
		}
		return;
	}

	# 获取指定技能的开始时间
	function get_starttimes($sk,$para)
	{
		if(isset($para['starttimes'][$sk])) return $para['starttimes'][$sk];
		return 0;
	}

	# 设定指定技能的开始时间
	function set_starttimes($sk,&$para,$times=0)
	{
		global $now;
		$t = $times ? $times : $now;
		$para['starttimes'][$sk] = $t;
		return;
	}

	# 获取指定技能持续时间
	function get_lasttimes($sk,$para)
	{
		if(isset($para['lasttimes'][$sk])) return $para['lasttimes'][$sk];
		return 0;
	}

	# 变更保存在clbpara['lasttimes']内的指定技能持续时间
	function set_lasttimes($sk,$skdata,&$para)
	{
		$para['lasttimes'][$sk] = $skdata;
		return;
	}

	# 获取指定技能持续回合
	function get_lastturns($sk,$para)
	{
		if(isset($para['lastturns'][$sk])) return $para['lastturns'][$sk];
		return 0;
	}

	# 变更保存在clbpara['lastturns']内的指定技能持续回合
	function set_lastturns($sk,$skdata,&$para)
	{
		$para['lastturns'][$sk] = $skdata;
		return;
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
		global $cskills,$cskills_tags,$now;
		# 初始化技能描述
		$cskill = $cskills[$sk];
		$sk_desc = $shortdesc && isset($cskill['bdesc']) ? $cskill['bdesc'] : $cskill['desc'];
		# 技能存在标签时
		if(isset($cskill['tags']) && !$shortdesc)
		{
			$tag_desc = '';
			foreach($cskill['tags'] as $sk_tag)
			{
				if(isset($cskills_tags[$sk_tag])) $tag_desc .= $cskills_tags[$sk_tag];
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
			# 技能存在关联技能，且关联技能有静态参数，一并处理
			if(isset($cskill['link']))
			{
				foreach($cskill['link'] as $lksk)
				{
					if(isset($cskills[$lksk]['vars'])) $cskill['vars'] = array_merge($cskill['vars'],$cskills[$lksk]['vars']);
				}
			}
			foreach($cskill['vars'] as $key => $var)
			{
				# 静态参数是数组的情况下 选用当前等级对应的参数
				if(is_array($var)) 
				{
					if(isset($now_clvl))
					{
						$var = $var[$now_clvl];
					}
					else 
					{
						# 静态参数是数组，但是却没有传入技能等级，说明可能是其他类型的参数，直接跳过
						continue;
					}
				}
				# 替换描述文本
				$sk_desc = str_replace("[:".$key.":]",$var,$sk_desc);
			}
		}
		# 技能存在动态参数
		if(isset($cskill['pvars']))
		{
			foreach($cskill['pvars'] as $pvar)
			{
				if($pvar == 'lasttimes' || $pvar == 'starttimes' || $pvar == 'lastturns')
				{
					if(isset($data['clbpara'][$pvar][$sk]))
					{
						$tpvar = $data['clbpara'][$pvar][$sk];
						if($pvar == 'lasttimes')
						{
							$tpvar = $data['clbpara']['starttimes'][$sk] + $tpvar - $now;
						}
						$sk_desc = str_replace("[^".$pvar."^]",$tpvar,$sk_desc);
					}
				}	
				elseif($pvar == 'skill-active')
				{
					if(isset($data['clbpara']['skillpara'][$sk]['active']))
					{
						$tpvar = $data['clbpara']['skillpara'][$sk]['active'];
						$tpdesc = $tpvar ? "<span class='clan'>【技能效果已启用】</span>" : "<span class='grey'>【技能效果已关闭】</span>";
						$sk_desc = str_replace("[^".$pvar."^]",$tpdesc,$sk_desc);
					}
				}
				elseif(strpos($pvar,'skillpara') !== false)
				{
					# 格式化置换条件对应的置换内容
					$skey_value = parse_skillrules($pvar);
					$skey_value = eval("return $skey_value;");
					# 置换对应内容
					$sk_desc = str_replace("[^{$pvar}^]",$skey_value,$sk_desc);
				}
				else 
				{
					if(isset($data[$pvar]))
					{
						$sk_desc = str_replace("[^".$pvar."^]",$data[$pvar],$sk_desc);
					}
				}

			}
		}
		# 技能存在关联属性
		if(isset($cskill['status']))
		{
			# 检查社团是否会直接影响属性：
			$ceffect = !empty($data) && isset($cskill['effect'][$data['club']]) ? $cskill['effect'][$data['club']] : $cskill['effect'][0];
			# 遍历技能关联属性，替换介绍文本内的数值
			foreach($cskill['status'] as $snm)
			{
				# 如属性值为数组，则按照等级取值
				$seffect = is_array($ceffect[$snm]) ? $ceffect[$snm][$now_clvl] : $ceffect[$snm];
				# 格式化属性值
				$seffect = parse_skilloperator($seffect);
				$so = is_array($seffect) ? $seffect[0] : ''; 
				$svars = is_array($seffect) ? $seffect[1] : $seffect;
				# 替换描述文本
				$sk_desc = str_replace("[:{$snm}:]",$svars,$sk_desc);
			}
		}
		return $sk_desc;
	}

?>
