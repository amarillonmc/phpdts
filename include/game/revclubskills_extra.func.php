<?php

	if (! defined ( 'IN_GAME' )) {
		exit ( 'Access Denied' );
	}

	# 新社团技能 - 特殊社团技能处理：

	# 升级指定技能会触发的事件，返回0时代表无法升级技能
	function upgclbskills_events($event,$sk,&$data=NULL)
	{
		global $log,$cskills,$now,$club_skillslist;

		if(!isset($data))
		{
			global $pdata;
			$data = &$pdata;
		}
		extract($data,EXTR_REFS);

		# 事件：激活技能
		if($event == 'active_news')
		{
			addnews($now,'ask_'.$sk,$name);
			return 1;
		}
		# 事件：治疗
		if($event == 'heal')
		{
			# 事件效果：回复满生命、体力，并清空所有异常状态
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
		# 事件：怒气充能
		if($event == 'charge')
		{
			if($rage >= 255)
			{
				$log .= "你不需要使用这个技能！<br>";
				return 0;
			}
			$rage = min(255,$rage + get_skillvars($sk,'rageadd'));
			// 检查当前技能使用次数
			$active_t = get_skillpara($sk,'active_t',$clbpara);
			// 第3次使用时开始冷却
			if($active_t+1 > get_skillvars($sk,'freet'))
			{
				$event = 'setstarttimes_'.$sk.'_charge';
			}
			else 
			{
				return 1;
			}
		}
		# 事件：广域探测
		if($event == 'radar')
		{
			include_once GAME_ROOT.'./include/game/item2.func.php';
			newradar(2);
			return 1;
		}
		# 事件：灵感
		if($event == 'inspire')
		{
			# 事件效果：随机获取一个选定社团的技能……嗯……
			$sk_c = get_skillpara($sk,'choice',$data['clbpara']);
			$sk_list = $club_skillslist[$sk_c];
			if(!empty($sk_list))
			{
				do{
					$get_skill = $sk_list[array_rand($sk_list)];
				}while(get_skilltags($get_skill,'player'));
				// 检查是否为未学习技能
				$gsk_name = $cskills[$get_skill]['name'];
				$log .= "你灵光一现，忽然想到了技能<span class='lime'>「{$gsk_name}」</span>的用法！<br>";
				if(!in_array($get_skill,$data['clbpara']['skill']))
				{
					getclubskill($get_skill,$data['clbpara']);
					addnews($now,'inssk_'.$get_skill,$name,$sk);
				}
				else
				{
					$log .= "但是你已经学过<span class='lime'>「{$gsk_name}」</span>了……<br>";
					addnews($now,'inssk_failed',$name,$sk);
				}
				return 1;
			}
			else 
			{
				$log .= "所选称号无可学习技能，这可能是一个BUG，请联系管理员。<br>";
			}
			return 0;
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
		# 事件：为指定技能1设置技能2中的静态参数3
		if(strpos($event,'setskillvars_') === 0)
		{
			$sk_arr = str_replace('setskillvars_','',$event);
			$sk_arr = explode('|',$sk_arr);
			if(count($sk_arr) == 3)
			{
				$sk0 = $sk_arr[0]; $sk1 = $sk_arr[1]; $sk_vars = $sk_arr[2];
				$sk_vars = strpos($sk_vars,'+')!==false ? explode('+',$sk_vars) : Array($sk_vars);
				if(isset($cskills[$sk1]['maxlvl'])) $sklvl = get_skilllvl($sk1,$data);
				foreach($sk_vars as $var)
				{
					$sk_var = isset($sklvl) ? get_skillvars($sk1,$var,$sklvl) : get_skillvars($sk1,$var);
					set_skillpara($sk0,$var,$sk_var,$data['clbpara']);
				}
				return 1;
			}
			else 
			{
				$log .= "参数设置错误<br>";
				return 0;
			}
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
		# 事件：天运
		if($event == 'c6_godluck' || $event == 'c6_godsend')
		{
			$dice0 = rand(1,2);
			$dice1 = rand(get_skillvars($event,'flucmin'),get_skillvars($event,'flucmax'));
			if($event == 'c6_godluck')
			{
				if($dice0 == 1)
				{
					set_skillpara($event,'accloss',get_skillpara($event,'accloss',$clbpara)+$dice1,$clbpara);
					set_skillpara($event,'rbloss',get_skillpara($event,'rbloss',$clbpara)+$dice1,$clbpara);
				}
				else 
				{
					set_skillpara($event,'accgain',get_skillpara($event,'accgain',$clbpara)+$dice1,$clbpara);
					set_skillpara($event,'rbgain',get_skillpara($event,'rbgain',$clbpara)+$dice1,$clbpara);
				}
			}
			else 
			{
				if($dice0 == 1)
				{
					set_skillpara($event,'actgain',get_skillpara($event,'actgain',$clbpara)+$dice1,$clbpara);
					set_skillpara($event,'hidegain',get_skillpara($event,'hidegain',$clbpara)+$dice1,$clbpara);
				}
				else 
				{
					set_skillpara($event,'countergain',get_skillpara($event,'countergain',$clbpara)+$dice1,$clbpara);
				}
			}
		}
		return 1;
	}

	# 「穿杨」与「咆哮」解锁
	function skill_c4_unlock($csk)
	{
		global $log,$pdata,$cskills;
		if(($csk != 'c4_roar' && $csk != 'c4_sniper') || !in_array($csk,$pdata['clbpara']['skill']))
		{
			$log .= "要解锁的技能{$csk}不存在。<br>";
			return;
		}
		if(!check_skill_unlock('c4_roar',$pdata) || !check_skill_unlock('c4_sniper',$pdata))
		{
			$log .= "无法重复解锁。<br>";
			return;
		}
		//include_once GAME_ROOT.'./include/game/revclubskills.func.php';
		set_skillpara($csk,'active',1,$pdata['clbpara']);
		set_skillpara(get_skillvars($csk,'disableskill'),'disable',1,$pdata['clbpara']);
		$log .= "<span class='yellow'>已解锁技能「{$cskills[$csk]['name']}」！</span><br>";
		return;
	}

	# 尸体发火！
	function skill_tl_cstick_act(&$edata)
	{
		global $log,$pdata,$cskills;
		//include_once GAME_ROOT.'./include/game/revclubskills.func.php';
		$lock = check_skill_unlock('tl_cstick',$pdata);
		if(!$lock)
		{
			# 扣除怒气
			$pdata['rage'] -= get_skillvars('tl_cstick','ragecost');
			addnews($now,'bsk_tl_cstick',$pdata['name'],$edata['name'].'的尸体');
			addnews($now,'cstick',$pdata['name'],$edata['name'].'的尸体');
			# 炼到了不该炼的尸体
			if(in_array($edata['type'],get_skillvars('tl_cstick','notype')))
			{
				$log .= "仿佛觉察到了你那邪恶的念头，你刚一伸出手，{$edata['name']}的尸体便化作尘埃随风散去了……<br>不知为何，你感到有些惭愧。<br>";
				destory_corpse($edata);
				$pdata['rp'] += 333;
				return;
			}
			# 开抡！
			$log .= "你干脆利落地把<span class='red'>{$edata['name']}</span>从地上拽了起来！然后卯足力气，在空中挥舞了两下。<br>……<br>";
			$pdata['itm0'] = "{$edata['name']}尸体模样的棍棒";
			$pdata['itmk0'] = 'WP'; 
			$pdata['itme0'] = round($edata['msp']); 
			$pdata['itms0'] = round($edata['mhp']); 
			$pdata['itmsk0'] = '';
			$dice = diceroll(99);
			$N_obbs = pow($edata['lvl'],1.3);
			$z_obbs = !$edata['type'] ? pow($edata['lvl'],1.3) : pow($edata['lvl'],1.15);
			if($dice < $N_obbs)
			{
				$pdata['itmsk0'] .= 'N'; 
				$log .= "不错！份量不轻不重刚刚好！<br>";
			}
			if($dice < $z_obbs)
			{
				$pdata['itmsk0'] .= 'Z'; 
				$log .= "越是挥舞，越觉趁手！这尸体仿佛死来就是为你准备的！<br>哇，这下真正捡到宝了！<br>";
			}
			if(empty($pdata['itmsk0']))
			{
				$log .= "哎呀……好像这具尸体和你的相性不是很好。但是无所谓啦！<br>";
			}
			# 出生啊！
			$max_rp_dice = $pdata['itme0']+$pdata['itms0'] > 300 ? $pdata['itme0']+$pdata['itms0'] : 300;
			$rp_dice = rand(300,$max_rp_dice);
			$pdata['rp'] += $rp_dice;
			# 做成棍了就没有尸体了
			destory_corpse($edata);
			include_once GAME_ROOT.'./include/game/itemmain.func.php';
			itemget();
		}
		else 
		{
			$log .= isset($cskills['tl_cstick']['lockdesc'][$lock]) ? $cskills['tl_cstick']['lockdesc'][$lock] : $lock;
		}
		return;
	}

?>
