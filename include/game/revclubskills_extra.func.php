<?php

	if (! defined ( 'IN_GAME' )) {
		exit ( 'Access Denied' );
	}

	# 新社团技能 - 特殊社团技能处理：

	# 升级指定技能会触发的事件，返回0时代表无法升级技能
	function upgclbskills_events($event,$sk,&$data=NULL)
	{
		global $log,$cskills,$now,$club_skillslist,$weather,$gamevars,$wthinfo,$db,$tablepre;
		global $elements_info;

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
				$event = 'setstarttimes_'.$sk;
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
		# 事件：晶璧
		if($event == 'crystal')
		{
			# 初始化护盾属性
			$slde = round(abs($rp) * get_skillvars($sk,'sldr') / 100);
			$sldt = 0;
			# 先看看能不能给自己套盾
			if(check_skill_unlock('buff_shield',$data))
			{
				getclubskill('buff_shield',$clbpara);
				set_skillpara('buff_shield','svar',$slde,$clbpara);
				$sldt++;
			}
			# 再遍历场上所有参战者（玩家、18、19）
			$result = $db->query("SELECT * FROM {$tablepre}players WHERE (type = 0 OR type =18 or type = 19) AND hp > 0 AND pid != {$pid}");
			while($sdata = $db->fetch_array($result))
			{
				$sdata['clbpara'] = get_clbpara($sdata['clbpara']);
				# 没有盾的话可以套个盾，有就不给了
				if(check_skill_unlock('buff_shield',$sdata))
				{
					getclubskill('buff_shield',$sdata['clbpara']);
					set_skillpara('buff_shield','svar',$slde,$sdata['clbpara']);
					$sldt++;
					player_save($sdata);
					$w_log = "<span class=\"yellow\">{$name}发动了技能「晶璧」，你被一层晶体护盾保护了起来！</span><br>";
					logsave ($sdata['pid'],$now,$w_log,'c');
				}
			}
			if($sldt)
			{
				# 降低rp
				$rploss = $sldt * get_skillvars($sk,'rploss');
				$rp -= $rploss;
				# 扣除怒气
				$ragecost = get_skillvars($sk,'ragecost');
				$rage -= $ragecost;
				$log .= "<span class='lime'>你用心感应，唤出晶体之盾为战场上{$sldt}名参战者提供了庇佑！</span><br>善行点数增加了！<br>";
			}
			else 
			{
				$log .= "你用心感应，但是战场上似乎已经没有需要你提供庇护的人了。<br>";
				return 0;
			}
			return 1;
		}
		# 事件：苦雨
		if($event == 'woesea')
		{
			if($weather == 18)
			{
				$log .= "战场已经处于{$wthinfo[18]}下，不能重复发动！<br>";
				return 0;
			}
			else 
			{
				$ss -= 100;
				$weather = 18;
				$gamevars['wth18stime'] = $now;
				$gamevars['wth18etime'] = $now + get_skillvars('c19_woesea','wtht');
				$gamevars['wth18pid'] = $pid;
				save_gameinfo();
				addnews($now, 'wthchange', $name, $weather, '自己积攒的善德');
				include_once GAME_ROOT.'./include/game/combat.func.php';
				$sn = 'song';
				addnoise($sn,'__',$now,$pls,0,0,$sn);
				$clbpara['event_bgmbook'] = Array('wth18');

				$log .= "你闭上双眼，伴着记忆中那轻快的旋律轻轻哼唱起来……<br>
				歌声悠然飘扬，朦胧间，你似乎感到有雨滴淅沥落下，轻轻拍在你的脸上。<br>
				当你再度睁开眼时，<br>
				不知从何而来、如妖精般飞舞着的光球们抚过你的面颊，然后飘往虚拟战场的每个角落——<br>
				……<br>
				下雨了。";
				return 1;
			}
		}
		# 事件：雇佣佣兵
		if($event == 'hiremerc')
		{
			$mcost = get_skillvars($sk,'mcost');
			$money -= $mcost;
			$anpcinfo = get_addnpcinfo();
			$mercinfo = $anpcinfo[25]['sub'];
			# 获取佣兵出货概率
			$tot = count($mercinfo);
			$tp = 0;
			for ($i=0; $i<$tot; $i++) $tp+=$mercinfo[$i]['probability'];
			$dice = rand(1,$tp);
			for ($i=0; $i<$tot; $i++)
			{
				if ($dice<=$mercinfo[$i]['probability'])
				{
					$merc = $i;
					break;
				}
				else  $dice-=$mercinfo[$i]['probability'];
			}
			# 登记佣兵序号
			$mid = get_skillpara($sk,'active_t',$clbpara);
			# 召唤佣兵，并获取佣兵pid
			include_once GAME_ROOT . './include/system.func.php';
			$merc_pid = addnpc(25,$merc,1,$now,Array('clbpara' => Array('mkey' => $mid, 'oid' => $pid, 'onm' => $name ,'mate' => Array($pid))),NULL,$pls)[0];
			# 初始化佣兵参数
			$clbpara['skillpara'][$sk]['id'][$mid] = $merc_pid;
			# 登记佣兵薪水
			$clbpara['skillpara'][$sk]['paid'][$mid] = $mercinfo[$merc]['mercsalary'];
			# 登记佣兵解雇后状态
			$clbpara['skillpara'][$sk]['leave'][$mid] = $mercinfo[$merc]['mercfireaction'];
			# 登记佣兵协战概率
			$clbpara['skillpara'][$sk]['coverp'][$mid] = $mercinfo[$merc]['coverp'];
			# 登记佣兵行动次数
			$clbpara['skillpara'][$sk]['mms'][$mid] = 0;
			# 检查佣兵能否协战（出生时在同一地图，默认可以协战）
			$clbpara['skillpara'][$sk]['cancover'][$mid] = 1;
			$log .= "你掏出千元大钞振臂一呼，「{$mercinfo[$merc]['name']}」突然出现在了你的面前！<br>";
			return 1;
		}
		# 事件：横财
		if($event == 'windfall')
		{
			$tot = $element0 + $element1 + $element2 + $element3 + $element4 + $element5;
			if($tot < 5)
			{
				$log .= "你口袋中的元素太少了！再去捡点吧！<br>";
				return 0;
			}
			$numbers = array_fill(0, 6, 14);
			# 生成5个随机数，使它们的和等于15
			$t = 16;
			for ($i = 0; $i < 6; $i++) 
			{
				$at = rand(1,$t); $t -= $at;
				$numbers[$i] += $at;
			}
			$log .= "你把口袋中的元素搅混在一起……然后满怀期待得等着它们自己把自己整理好……<br>";
			foreach($elements_info as $key => $info)
			{
				${'element'.$key} = 0;
				$add_ev = ceil($tot * ($numbers[$key]/100));
				include_once GAME_ROOT.'./include/game/elementmix.calc.php';
				$add_ev = get_clbskill_emgain_r($add_ev,$data);
				${'element'.$key} += $add_ev;
				$log .= "获得了{$add_ev}份{$info}！<br>";
			}
			return 1;
		}
		# 事件：黑莲花
		if($event == 'lotus')
		{
			foreach($elements_info as $key => $info)
			{
				# 不足3000的，补足到3000
				if(${'element'.$key} < 3000)
				{
					${'element'.$key} = 3000;
					continue;
				}
				${'element'.$key} *= 3;
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
	# 佣兵工资判定
	function skill_merc_paid($sk,$mkey,&$mdata)
	{
		global $log,$now;
		if(!isset($data))
		{
			global $pdata;
			$data = &$pdata;
		}
		extract($data,EXTR_REFS);
		# 需支付工资的行动次数
		$mst = get_skillvars($sk,'mst'); 
		# 佣兵当前行动次数+1
		$mms = get_skillpara($sk,'mms',$clbpara)[$mkey]+1;
		if($mms >= $mst)
		{
			# 应付工资
			$paid = get_skillpara($sk,'paid',$clbpara)[$mkey];
			# 有钱付工资
			if($money >= $paid)
			{
				$mdata['money'] += $paid; $money -= $paid; 
				$log .= "<span class='yellow'>花费了{$paid}元，向{$mdata['name']}(佣兵{$mkey}号)支付了工资。</span><br>";
				$clbpara['skillpara'][$sk]['mms'][$mkey] = 0;
				//成功支付佣金，信任度+3，最多不超过90
				if($clbpara['skillpara'][$sk]['coverp'][$mkey] < 90) $clbpara['skillpara'][$sk]['coverp'][$mkey] += 3;
			}
			# 没钱要挨打
			else 
			{
				$mdata['money'] += $money; $money = 0; $hp = 1;
				player_save($mdata);
				$log .= "<span class='yellow'>眼看又到了结账的时候，但你身上的钱不足以支付{$mdata['name']}(佣兵{$mkey}号)的工资……<br>被拖欠工资而恼羞成怒的佣兵狠揍了你一顿！并拿走了你所有剩下的钱！</span><br>";
				include_once GAME_ROOT.'./include/game/revclubskills_extra.func.php';
				$log .= "<span clas='red'>由于欠薪，";
				skill_merc_fire($sk,$mkey,$mdata,1);
				return;
			}
		}
		player_save($mdata);
		return;
	}
	# 佣兵移动判定
	function skill_merc_move($sk,$mkey,$moveto)
	{
		global $log,$plsinfo,$now;
		if(!isset($data))
		{
			global $pdata;
			$data = &$pdata;
		}
		extract($data,EXTR_REFS);
		if(isset($clbpara['skillpara'][$sk]['id'][$mkey]))
		{
			# 获取佣兵编号
			$mpid = get_skillpara($sk,'id',$clbpara)[$mkey];
			# 获取佣兵数据
			$mdata = fetch_playerdata_by_pid($mpid);
			if($mdata['hp'] <= 0)
			{
				$log .= "{$mdata['name']}已经西去了！放过他吧！<br>";
				return;
			}
			# 移动……？这个太扯了！
			include_once GAME_ROOT.'./include/game/search.func.php';
			//move($moveto,$mdata);
			if(!check_can_move($mdata['pls'],$mdata['pgroup'],$moveto)) return;
			# 计算移动花费 = 佣兵工资×2
			$mpaid = get_skillvars($sk,'movep') * get_skillpara($sk,'paid',$clbpara)[$mkey];
			if($money < $mpaid)
			{
				$log .= "你身上的钱不足以让佣兵离开岗位！<br>";
				return;
			}
			$money -= $mpaid; 
			$mdata['pls'] = $moveto;
			addnews($now,'mercmove',$name,$mdata['name'],$moveto);
			$log .= "花费了{$mpaid}元，你将{$mdata['name']}叫到了{$plsinfo[$moveto]}！<br>";
			// 移动后佣兵失去追击焦点
			if(!empty($mdata['clbpara']['mercchase'])) $mdata['clbpara']['mercchase'] = 0;
			# 检查下工资情况
			skill_merc_paid($sk,$mkey,$mdata);
			player_save($mdata);
			// 移动后佣兵行动次数+2
			$clbpara['skillpara'][$sk]['mms'][$mkey] += get_skillvars($sk,'movep');
			// 移动后重新检查佣兵是否可协战
			$clbpara['skillpara'][$sk]['cancover'][$mkey] = $mdata['pls'] == $pls ? 1 : 0;
		}
		return;
	}
	# 解雇佣兵判定
	function skill_merc_fire($sk,$mkey,&$mdata=NULL,$mlog=0)
	{
		global $log,$now;
		if(!isset($data))
		{
			global $pdata;
			$data = &$pdata;
		}
		extract($data,EXTR_REFS);
		if(isset($clbpara['skillpara'][$sk]['id'][$mkey]))
		{
			# 判断离场事件
			$mpid = get_skillpara($sk,'id',$clbpara)[$mkey];
			$leave_flag = get_skillpara($sk,'leave',$clbpara)[$mkey];
			$leave_desc = $leave_flag ? '直接离开了战场！' : '决定留在原地。';
			# 清除对应的佣兵数据
			unset($clbpara['skillpara'][$sk]['id'][$mkey]);
			unset($clbpara['skillpara'][$sk]['paid'][$mkey]);
			unset($clbpara['skillpara'][$sk]['leave'][$mkey]);
			unset($clbpara['skillpara'][$sk]['mms'][$mkey]);
			unset($clbpara['skillpara'][$sk]['coverp'][$mkey]);
			unset($clbpara['skillpara'][$sk]['cancover'][$mkey]);
			if(!isset($mdata)) $mdata = fetch_playerdata_by_pid($mpid);
			if($mlog)
			{
				$log .= "{$mdata['name']}与你的合作关系中止了！</span><br>";
			}
			else 
			{
				$log .= "你决定解雇{$mdata['name']}！";
				$log .= $mdata['hp']>0 ? "对方似乎{$leave_desc}<br>" : "虽然对方已经倒在工作岗位上了……<br>";
			}
			# 彻底离场类佣兵：
			if($leave_flag)
			{
				addnews($now,'mercleave',$name,$mdata['name']);
				destory_corpse($mdata);
			}
		}
		return;
	}
	# 佣兵追击判定
	function skill_merc_chase($sk,$mkey)
	{
		global $log,$now;
		if(!isset($data))
		{
			global $pdata;
			$data = &$pdata;
		}
		extract($data,EXTR_REFS);
		if(isset($clbpara['skillpara'][$sk]['id'][$mkey]))
		{
			$mid = get_skillpara($sk,'id',$clbpara)[$mkey];
			$mdata = fetch_playerdata_by_pid($mid);
			# 确实存在追击对象
			if(isset($mdata['clbpara']['mercchase']))
			{
				# 检查是否有钱强制命令佣兵追击
				$mccost = get_skillvars($sk,'atkp') * get_skillpara($sk,'paid',$data['clbpara'])[$mkey];
				if($mccost <= $money)
				{
					# 传递追击对象
					$action = 'enemy'; $bid = $mdata['clbpara']['mercchase'];
					# 佣兵追击不一定能先制，要判定一下
					include_once GAME_ROOT.'./include/game/revbattle.func.php';
					revbattle_prepare('bskill_c11_merc'.$mkey,'noactive');
				}
				else
				{
					$log .= "你身上的钱不够！<br>";
					return;
				}
			}
		}
		return;
	}
	# 检查佣兵是否可协战
	function skill_check_merc_can_cover($sk,$mkey)
	{
		global $log,$plsinfo,$now;
		if(!isset($data))
		{
			global $pdata;
			$data = &$pdata;
		}
		extract($data,EXTR_REFS);
		if(isset($clbpara['skillpara'][$sk]['cancover'][$mkey])) return $clbpara['skillpara'][$sk]['cancover'][$mkey];
		return 0;
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
			include_once GAME_ROOT.'./include/game/revcombat.func.php';
			rpup_rev($pdata,$rp_dice);
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
