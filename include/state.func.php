<?php

	if (! defined ( 'IN_GAME' )) {
		exit ( 'Access Denied' );
	}

	# 没有目标击杀，自然死亡时的事件（可能也不是那么“自然”……）
	function death($death, $kname = '', $ktype = 0, $annex = '',&$data=NULL) 
	{
		global $now, $db, $tablepre, $gtablepre, $alivenum, $deathnum, $killmsginfo, $typeinfo, $weather;

		if(!isset($data))
		{
			global $pdata;
			$data = &$pdata;
		}
		extract($data,EXTR_REFS);

		$bid = 0; $action = '';

		if (!$death) return;
			
		$hp = 0;
		if ($death == 'N') {
			$state = 20;
		} elseif ($death == 'P') {
			$state = 21;
		} elseif ($death == 'K') {
			$state = 22;
		} elseif ($death == 'G') {
			$state = 23;
		} elseif ($death == 'C') {
			$state = 24;
		} elseif ($death == 'D') {
			$state = 25;
		} elseif ($death == 'F') {
			$state = 29;
		} elseif ($death == 'J') {
			$state = 23;
		}elseif ($death == 'poison') {
			$state = 26;
		} elseif ($death == 'trap') {
			$state = 27;
		} elseif ($death == 'event') {
			$state = 13;
		} elseif ($death == 'hack') {
			$state = 14;
		} elseif ($death == 'pmove') {
			$state = 12;
		} elseif ($death == 'hsmove') {
			$state = 17;
		} elseif ($death == 'umove') {
			$state = 18;
		} elseif ($death == 'button') {
			$state = 30;
		} elseif ($death == 'suiside') {
			$state = 31;
		} elseif ($death == 'gradius') {
			$state = 33;
		} elseif ($death == 'SCP') {
			$state = 34;
		} elseif ($death == 'salv'){
			$state = 35;
		} elseif ($death == 'kagari1'){
			$state = 36;
		} elseif ($death == 'kagari2'){
			$state = 37;
		} elseif ($death == 'kagari3'){
			$state = 38;
		} elseif ($death == 'gg'){
			$state = 39;
		} elseif ($death == 'fake_dn'){
			$state = 28;
		} elseif ($death == 'thunde'){
			$state = 40;
		} elseif ($death == 's_escape'){
			$state = 42;
		} else {
			$state = 10;
		}
		
		$killmsg = '';
		if ($ktype == 0 && $kname) {
			$result = $db->query ( "SELECT killmsg FROM {$gtablepre}users WHERE username = '$kname'" );
			$killmsg = $db->result ( $result, 0 );
		} elseif ($ktype != 0 && $kname) {
			$killmsg = $killmsginfo [$ktype];
			$kname = "$typeinfo[$ktype] $kname";
		} else {
			$kname = '';
			$killmsg = '';
		}
		
		if (!$type) 
		{
			$alivenum--;
			$deathnum++;
			$result = $db->query ( "SELECT lastword FROM {$gtablepre}users WHERE username = '$name'" );
			$lastword = $db->result ( $result, 0 );
			$lwname = $typeinfo [$type] . ' ' . $name;
			/*$result = $db->query("SELECT pls FROM {$tablepre}players WHERE name = '$name' AND type = '$type'");
			$pls = $db->result($result, 0);*/
			$db->query ( "INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('3','$now','$lwname','$pls','$lastword')" );
		}
		$deathtime = $now;
		$result = $db->query("SELECT nick FROM {$tablepre}players WHERE name = '$kname' AND type = '$type'");
		$knick = $db->result($result, 0);
		$knname = isset($knick) ? $knick.' '.$kname : $kname;
		addnews ( $now, 'death' . $state, $name, $type, $knname, $annex, $lastword );
		//$alivenum = $db->result($db->query("SELECT COUNT(*) FROM {$tablepre}players WHERE hp>0 AND type=0"), 0);
		
		# 执行死亡事件（灵魂绑定等）
		if(!$data['type'] && empty($data['nm'])) $data['nm'] = '你';
		check_death_events(create_dummy_playerdata(),$data,1);

		player_save($data);

		save_gameinfo ();
		return $killmsg;
	}

		# 执行不需要考虑复活问题的击杀事件：
	# 再重复一遍：这里的第一个参数指的是杀人者(敌对方)视角，第二个参数指的是死者(受到伤害者)视角。
	function pre_kill_events(&$pa,&$pd,$active,$death) 
	{
		global $log, $now, $db, $gtablepre, $tablepre, $typeinfo, $lwinfo;
		
		// 登记死法
		// 传入了数字编号死法
		if (is_numeric($death)) {
			$pd['state'] = $death;
		// 否则按照指定武器类型判断
		} elseif ($death == 'N') {
			$pd['state'] = 20;
		} elseif ($death == 'P') {
			$pd['state'] = 21;
		} elseif ($death == 'K') {
			$pd['state'] = 22;
		} elseif ($death == 'G') {
			$pd['state'] = 23;
		} elseif ($death == 'J') {
			$pd['state'] = 23;
		} elseif ($death == 'C') {
			$pd['state'] = 24;
		} elseif ($death == 'D') {
			$pd['state'] = 25;
		} elseif ($death == 'F') {
			$pd['state'] = 29;
		} elseif ($death == 'poison') {
			$pd['state'] = 26;
		} elseif ($death == 'trap') {
			$pd['state'] = 27;
		} elseif ($death == 'dn') {
			$pd['state'] = 28;
		} else {
			$pd['state'] = 10;
		}
		//初始化死者信息
		$dtype = $pd['type']; $dname = $pd['name']; $dpls = $pd['pls'];
		$lwname = $typeinfo [$dtype] . ' ' . $dname;
		//初始化NPC遗言
		if($dtype)
		{
			$lastword = is_array($lwinfo[$dtype]) ? $lwinfo[$dtype][$dname] : $lwinfo[$dtype];
		}
		//初始化玩家遗言
		else 
		{
			$result = $db->query ( "SELECT lastword FROM {$gtablepre}users WHERE username ='$dname'");
			$lastword = $db->result ( $result, 0 );
		}
		//向聊天框发送遗言
		$db->query ( "INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('3','$now','$lwname','$dpls','$lastword')" );

		//发送news
		$kname = $pa['type'] ? $pa['name'] : get_title_desc($pa['nick']).' '.$pa['name'];
		//$dname = $pd['type'] ? $pd['name'] : get_title_desc($pd['nick']).' '.$pd['name'];
		addnews ($now,'death'.$pd['state'],$dname,$dtype,$kname,$pa['wep_name'],$lastword );

		return $lastword;
	}

	# 执行复活事件：
	# 重要的事情要说三次：这里的第一个参数指的是杀人者(敌对方)视角，第二个参数指的是死者(受到伤害者)视角。
	function revive_process(&$pa,&$pd,$active)
	{
		global $log,$weather,$now,$gamevars;
		include_once GAME_ROOT.'./include/game/clubslct.func.php';

		if(empty($pa['nm'])) $pa['nm'] = $active && !$pa['type'] ? '你' : $pa['name'];
		if(empty($pd['nm'])) $pd['nm'] = !$active && !$pd['type'] ? '你' : $pd['name'];

		$revival_flag = 0;

		$dname = $pd['type'] ? $pd['name'] : get_title_desc($pd['nick']).' '.$pd['name'];

		#光玉雨天气下，提供者有概率复活
		if (!$revival_flag && $weather == 18 && $gamevars['wth18pid'] == $pd['pid'])
		{
			# 计算雨势
			$wthlastime = $now - $gamevars['wth18stime'];
			# 雨势在前7分钟递增，后3分钟递减
			$wthlastime = $wthlastime <= 420 ? $wthlastime : 600 - $wthlastime;
			$wthpow = min(7,max(1,round($wthlastime / 60)));
			# 复活概率：基础10% + 效力x2 最高24%
			$wth18_obbs = 10 + diceroll($wthpow) + diceroll($wthpow);
			$wth18_dice = diceroll(99);
			if($wth18_dice < $wth18_obbs)
			{
				#奥罗拉复活效果
				$revival_flag = 18; //保存复活标记为通过光玉雨复活
				addnews($now,'wth18_revival',$dname);
				$pd['hp'] += min($pd['mhp'],max($wth18_obbs,1)); 
				$pd['sp'] += min($pd['msp'],max($wth18_obbs,1));
				$pd['state'] = 0;
				$log.= "<span class=\"lime\">但是，飞舞着的光玉们钻进了{$pd['nm']}的身体，让{$pd['nm']}重新站了起来！</span><br>";;
				return $revival_flag;
			}
		}

		#极光天气下，玩家有10%概率、NPC有1%概率无条件复活
		if (!$revival_flag && $weather == 17)
		{
			$aurora_rate = $pd['type'] ? 1 : 10; //玩家10%概率复活
			$aurora_dice = diceroll(99);
			if($aurora_dice<=$aurora_rate)
			{
				#奥罗拉复活效果
				$revival_flag = 17; //保存复活标记为通过奥罗拉复活
				addnews($now,'aurora_revival',$dname);
				$pd['hp'] += min($pd['mhp'],max($aurora_dice,1)); 
				$pd['sp'] += min($pd['msp'],max($aurora_dice,1));
				$pd['state'] = 0;
				$log.= "<span class=\"lime\">但是，空气中弥漫着的奥罗拉让{$pd['nm']}重新站了起来！</span><br>";;
				return $revival_flag;
			}
		}

		# 「涅槃」复活：
		if (!$revival_flag && isset($pd['skill_c19_nirvana']))	
		{
			# 「涅槃」复活效果：
			$revival_flag = 'nirvan'; //保存复活标记为通过技能复活
			addnews($now,'revival',$dname);	
			# 添加「涅槃」激活次数
			set_skillpara('c19_nirvana','active_t',get_skillpara('c19_nirvana','active_t',$pd['clbpara'])+1,$pd['clbpara']);
			$pd['state'] = 0; 
			$pd['hp'] = 1; $pd['sp'] = 1;
			# 将多出的rp转化为生命和防御力
			if($pd['rp'])
			{
				$tot_rp = abs(round($pd['rp']/2));
				if($tot_rp)
				{
					$pd['mhp'] += $tot_rp; $pd['def'] += $tot_rp;
				}
				$pd['rp'] = 0;
			}
			$log .= '<span class="lime">但是，'.$pd['nm'].'涅槃重生了！</span><br>';
			return $revival_flag;
		}

		return $revival_flag;
	}

	# 执行死透了后的事件：
	function final_kill_events(&$pa,&$pd,$active,$last=0)
	{
		global $log,$now,$alivenum,$deathnum,$db,$gtablepre,$tablepre;

		if(empty($pa['nm'])) $pa['nm'] = $active && !$pa['type'] ? '你' : $pa['name'];
		if(empty($pd['nm'])) $pd['nm'] = !$active && !$pd['type'] ? '你' : $pd['name'];

		$pd['hp'] = 0; $pd['bid'] = $pa['pid'];	$pd['action'] = '';
		$pd['endtime'] = $pd['deathtime'] = $now;

		# 初始化遗言
		if (!$pd['type'])
		{
			//死者是玩家，增加击杀数并保存系统状况。
			$pa['killnum'] ++;
			$alivenum --;
			if(!empty($last)) $log .= "<span class='evergreen'>你用尽最后的力气喊道：“".$last."”</span><br>";
		}
		else 
		{
			//死者是NPC，加载NPC遗言
			if(!empty($last)) $log .= npc_chat_rev ($pd,$pa, 'death' );
		}
		$deathnum ++;

		# 初始化killmsg
		if(!$pa['type'])
		{
			global $db,$tablepre;
			$pname = $pa['name'];
			$result = $db->query("SELECT killmsg FROM {$gtablepre}users WHERE username = '$pname'");
			$killmsg = $db->result($result,0);
			if(!empty($killmsg)) $log .= "<span class=\"evergreen\">{$pa['nm']}对{$pd['nm']}说：“{$killmsg}”</span><br>";
		}
		else
		{
			$log .= npc_chat_rev ($pa,$pd,'kill');
		}

		# 杀人rp结算
		get_killer_rp($pa,$pd,$active);
		# 执行死亡事件（灵魂绑定等）
		check_death_events($pa,$pd,$active);
		# 检查成就 大补丁：击杀者是玩家时才会检查成就
		if(!$pa['type'])
		{
			include_once GAME_ROOT.'./include/game/achievement.func.php';
			check_battle_achievement_rev($pa,$pd);	
		}
		# 保存游戏进行状态
		player_save($pd);
		save_gameinfo();
		return;
	}

	# 特殊死亡事件（灵魂绑定等）
	function check_death_events(&$pa,&$pd,$active)
	{
		global $db,$tablepre,$log,$now,$nosta;

		# 静流下线事件：
		if($pd['type'] == 15)
		{
			//静流AI
			global $gamevars;
			$gamevars['sanmadead'] = 1;
			save_gameinfo();
		}

		# 保存击杀女主的记录
		if($pd['type'] == 14)
		{
			$pa['clbpara']['achvars']['kill_n14'] += 1;
			# 不一定是一击秒杀……但是先这样吧^ ^;
			if($pd['name'] == '守卫者 静流' && $pa['final_damage'] >= $pd['mhp']) $pa['clbpara']['achvars']['ach505'] = 1;
		}

		# 保存击杀种火或小兵的记录
		if(empty($pa['clbpara']['achvars']['kill_minion']) && ($pd['type'] == 90 || $pd['type'] == 91 || $pd['type'] == 92)) $pa['clbpara']['achvars']['kill_minion'] = 1;

		# 成就504，保存在RF高校用过的武器记录
		if($pa['pls'] == 2) $pa['clbpara']['achvars']['ach504'][$pa['wep_kind']] = 1;


		# 快递被劫事件：
		if(isset($pd['clbpara']['post'])) 
		{	
			$log.="<span class='sienna'>某样东西从{$pd['name']}身上掉了出来……</span><br>";
			//获取快递信息
			$iid = $pd['clbpara']['postid'];
			//获取金主信息
			$sponsorid = $pd['clbpara']['sponsor'];
			$result = $db->query("SELECT * FROM {$tablepre}gambling WHERE uid = '$sponsorid'");
			$sordata = $db->fetch_array($result);
			//发一条news 表示快递被劫走了
			addnews($now,'gpost_failed',$sordata['uname'],$pd['itm'.$iid]);
			//消除快递相关参数
			unset($pd['clbpara']['post']);unset($pd['clbpara']['postid']);unset($pd['clbpara']['sponsor']);
			//解除快递锁
			$db->query("UPDATE {$tablepre}gambling SET bnid=0 WHERE uid='$sponsorid'");
		}

		# 灵魂绑定事件：
		foreach(get_equip_list() as $equip)
		{
			// ……我为什么不把这个装备名数组放进resources里……用了一万遍了
			// 哈哈，放了！
			if(!empty($pd[$equip.'s']) && strpos($pd[$equip.'sk'],'v')!==false)
			{
				$log .= "伴随着{$pd['nm']}的死亡，<span class=\"yellow\">{$pd[$equip]}</span>化作灰烬消散了。<br>";
				include_once GAME_ROOT.'./include/game/itemmain.func.php';
				destory_single_equip($pd,$equip);
			}
		}
		for($i=0;$i<=6;$i++)
		{
			if(!empty($pd['itms'.$i]) && strpos($pd['itmsk'.$i],'v')!==false)
			{
				$log .= "伴随着{$pd['nm']}的死亡，<span class=\"yellow\">{$pd['itm'.$i]}</span>化作灰烬消散了。<br>";
				include_once GAME_ROOT.'./include/game/itemmain.func.php';
				destory_single_item($pd,$i);
			}
		}

		# 带有“天然”属性的副武器，在死亡时会掉到地图上……
		if(!empty($pd['wep2e']) && !empty($pd['wep2sk']) && in_array('z',get_itmsk_array($pd['wep2sk'])))
		{
			$db->query("INSERT INTO {$tablepre}mapitem (itm, itmk, itme, itms, itmsk ,pls) VALUES ('{$pd['wep2']}', '{$pd['wep2k']}', '{$pd['wep2e']}', '{$pd['wep2s']}', '{$pd['wep2sk']}', '{$pd['pls']}')");
			include_once GAME_ROOT.'./include/game/itemmain.func.php';
			destory_single_equip($pd,'wep2');
		}

		#「掠夺」判定：
		if(isset($pa['skill_c4_loot']))
		{
			//获取抢钱率
			$sk_p = get_skillvars('c4_loot','goldr');
			$lootgold = $pa['lvl'] * $sk_p;
			$log.="<span class='yellow'>「掠夺」使{$pa['nm']}获得了{$lootgold}元！</span><br>";
		}

		# 「天威」技能判定
		if(isset($pa['bskill_c6_godpow']) && $pa['final_damage'] <= $pd['mhp'] * get_skillvars('c6_godpow','mhpr'))
		{
			$rageback = get_skillvars('c6_godpow','rageback');
			if(!empty($rageback))
			{
				$pa['rage'] = min(255,$pa['rage']+$rageback);
				$log .= '<span class="yellow">「天威」使'.$pa['nm'].'的怒气回复了'.$rageback.'点！</span><br>';
			}
		}

		# 「浴血」技能判定
		if(isset($pa['skill_c12_bloody']))
		{
			if($pa['hp'] <= $pa['mhp']*0.3) $sk_lvl = 2;
			elseif($pa['hp'] <= $pa['mhp']*0.5) $sk_lvl = 1;
			else $sk_lvl = 0;
			$sk_att_vars = get_skillvars('c12_bloody','attgain',$sk_lvl);
			$sk_def_vars = get_skillvars('c12_bloody','defgain',$sk_lvl);
			$pa['att'] += $sk_att_vars; $pa['def'] += $sk_def_vars; 
			$log .= '<span class="yellow">「浴血」使'.$pa['nm'].'的攻击增加了'.$sk_att_vars.'点，防御增加了'.$sk_def_vars.'点！</span><br>';
		}

		# 佣兵死亡时，自动解除与雇主的雇佣关系
		if(isset($pd['clbpara']['oid']))
		{
			$odata = fetch_playerdata_by_pid($pd['clbpara']['oid']);
			include_once GAME_ROOT.'./include/game/revclubskills_extra.func.php';
			$log .= "<span clas='red'>由于战死，";
			skill_merc_fire('c11_merc',$pd['clbpara']['mkey'],$pd,1);
		}
		# 灵俑死亡时，从创造者的灵俑队列中删除
		if(isset($pd['clbpara']['zombieoid']))
		{
			$odata = fetch_playerdata_by_pid($pd['clbpara']['zombieoid']);
			$mkey = array_search($pd['pid'],$odata['clbpara']['mate']);
			unset($odata['clbpara']['mate'][$mkey]);
			$zkey = array_search($pd['pid'],$odata['clbpara']['zombieid']);
			unset($odata['clbpara']['zombieid'][$zkey]);
			player_save($odata);
			$w_log = "<span class=\"grey\">你的灵俑{$pd['name']}归于尘土了……</span><br>";
			logsave($odata['pid'],$now,$w_log,'c');
		}

		return;
	}
		
	# 怒气上涨事件
	function rgup_rev(&$pa,&$pd,$active)
	{
		# 攻击命中的情况下，计算pa(攻击方)因攻击行为获得的怒气
		if($pa['hitrate_times'] > 0)
		{
			# pa(攻击方)拥有重击辅助属性，每次攻击额外获得1~2点怒气
			if(!empty($pa['ex_keys']) && in_array('c',$pa['ex_keys']))
			{
				$pa_rgup = rand(1,2);
				$pa['rage'] = min(255,$pa['rage']+$pa_rgup);
				# 成功发动战斗技时，额外返还10%怒气
				if(isset($pa['bskill']) && isset($pa['bskill_'.$pa['bskill']]))
				{
					$bsk = $pa['bskill'];
					$bsk_cost = get_skillvars($bsk,'ragecost');
					if($bsk_cost)
					{
						$pa['rage'] += round($bsk_cost*0.1);
						# 必杀技能额外返还15点怒气
						if($bsk == 'c9_lb') $pa['rage'] += get_skillvars('c9_lb','rageback');
					}
				}
			}
		}
		# 无论攻击是否命中，计算pd(防守方)因挨打获得的怒气
		$rgup = round(($pa['lvl'] - $pd['lvl'])/3);
		# 单次获得怒气上限：15
		$rgup = min(15,max(1,$rgup));
		# 「灭气」技能效果
		if(isset($pd['skill_c1_burnsp'])) $rgup += rand(1,2);
		$pd['rage'] = min(255,$pd['rage']+$rgup);
		return;
	}

	# 战斗后结算rp事件
	function get_killer_rp(&$pa,&$pd,$active)
	{
		# 杀人rp结算
		$rpup = $pd['type'] ? 20 : max(80,$pd['rp']);
		rpup_rev($pa,$rpup);
		return;
	}

	# rp上涨事件
	function rpup_rev(&$pa,$rpup)
	{
		# 「转业」效果判定
		if(!check_skill_unlock('c19_reincarn',$pa))
		{
			$sk = 'c19_reincarn';
			$sk_lvl = get_skilllvl($sk,$pa);
			if($rpup > 0)
			{
				$sk_var = get_skillvars($sk,'rpgain',$sk_lvl);
				$rpup = round($rpup*(1-($sk_var/100)));
			}
			else 
			{
				$sk_var = get_skillvars($sk,'rploss',$sk_lvl);
				$rpup = round($rpup*(1+($sk_var/100)));
			}
		}
		$pa['rp'] += $rpup;
	}

	# 经验上涨事件
	function expup_rev(&$pa,&$pd,$active) 
	{
		global $log,$baseexp;
		# 攻击命中的情况下，计算获得经验
		if($pa['hitrate_times'] > 0)
		{
			$expup = round ( ($pd['lvl'] - $pa['lvl']) / 3 );
			$expup = $expup > 0 ? $expup : 1;
		}
		# 攻击未命中，也许有其他渠道获得经验
		else
		{
			#「反思」技能效果
			if(isset($pa['skill_c5_review'])) $expup = 1;
		}
		if(isset($pa['bskill_c10_decons']) && $pa['final_damage'] > $pd['hp'])
		{
			$sk_up = ceil($pd['lvl'] - ($pa['lvl']*0.15));
			$log.='<span class="yellow">「解构」使'.$pa['nm'].'获得了额外'.$sk_up.'点经验！</span><br>';
			$expup += $sk_up;
		}
		if(!empty($expup)) $pa['exp'] += $expup;
		//$log .= "$isplayer 的经验值增加 $expup 点<br>";

		//升到下级所需的exp 直接在这里套公式计算 不用global了
		$pa['upexp'] = round(($pa['lvl']*$baseexp)+(($pa['lvl']+1)*$baseexp));

		if ($pa['exp'] >= $pa['upexp']) 
		{
			lvlup_rev ($pa,$pd,$active);
		}
		return;
	}

	# 等级提升事件
	function lvlup_rev(&$pa,&$pd,$active) 
	{
		global $log,$baseexp,$upexp;
		if(empty($pa['nm'])) $pa['nm'] = $active ? '你' : $pa['name'];
		$up_exp_temp = round ( (2 * $pa['lvl'] + 1) * $baseexp );
		if ($pa['exp'] >= $up_exp_temp && $pa['lvl'] < 255) 
		{
			$sklanginfo = Array ('wp' => '殴熟', 'wk' => '斩熟', 'wg' => '射熟', 'wc' => '投熟', 'wd' => '爆熟', 'wf' => '灵熟', 'all' => '全系熟练度' );
			$sknlist = Array (1 => 'wp', 2 => 'wk', 3 => 'wc', 4 => 'wg', 5 => 'wd', 9 => 'wf', 12 => 'all' );
			$skname = isset($sknlist[$pa['club']]) ? $sknlist[$pa['club']] : 0;
			//升级判断
			$lvup = 1 + floor (($pa['exp'] - $up_exp_temp)/$baseexp/2);
			$lvup = $lvup > 255 - $pa['lvl'] ? 255 - $pa['lvl'] : $lvup;
			$lvuphp = $lvupatt = $lvupdef = $lvupskill = $lvupsp = $lvupspref = 0;
			//升级数值计算
			for($i = 0; $i < $lvup; $i += 1) 
			{
				if ($pa['club'] == 12) {
					$lvuphp += rand ( 14, 18 );
				} else {
					$lvuphp += rand ( 8, 10 );
				}
				$lvupsp += rand( 4,6);
				if ($pa['club'] == 12) {
					$lvupatt += rand ( 4, 6 );
					$lvupdef += rand ( 5, 8 );
				} else {
					$lvupatt += rand ( 2, 4 );
					$lvupdef += rand ( 3, 5 );
				}
				
				if ($skname == 'all') {
					$lvupskill += rand ( 2, 4 );
				}elseif ($skname == 'wf') {
					$lvupskill += rand ( 3, 5 );
				}elseif ($skname == 'wd') {
					$lvupskill += rand ( 6, 8 );
				}elseif($skname){
					$lvupskill += rand ( 4, 6 );
				}
				$lvupspref += round($pa['msp'] * 0.1);		
			}
			//应用升级
			$pa['lvl'] += $lvup;
			$up_exp_temp = round ( (2 * $pa['lvl'] + 1) * $baseexp );
			if ($pa['lvl'] >= 255) {
				$pa['lvl'] = 255;
				$pa['exp'] = $up_exp_temp;
			}
			$pa['upexp'] = $up_exp_temp;
			$pa['hp'] += $lvuphp;
			$pa['mhp'] += $lvuphp;
			$pa['sp'] += $lvupsp;
			$pa['msp'] += $lvupsp;
			$pa['att'] += $lvupatt;
			$pa['def'] += $lvupdef;
			$pa['skillpoint'] += $lvup;
			if(!empty($skname))
			{
				if ($skname == 'all') {
					$pa['wp'] += $lvupskill;
					$pa['wk'] += $lvupskill;
					$pa['wg'] += $lvupskill;
					$pa['wc'] += $lvupskill;
					$pa['wd'] += $lvupskill;
					$pa['wf'] += $lvupskill;
				} elseif ($skname) {
					$pa[$skname] += $lvupskill;
				}
			}
			$pa['sp'] = min($lvupspref+$pa['sp'],$pa['msp']);
			
			if ($skname) {
				$sklog = "，{$sklanginfo[$skname]}+{$lvupskill}";
			}
			$lvlup_log = "<span class=\"yellow\">{$pa['nm']}升了{$lvup}级！生命上限+{$lvuphp}，体力上限+{$lvupsp}，攻击+{$lvupatt}，防御+{$lvupdef}";
			if(isset($sklog)) $lvlup_log .= $sklog;
			$lvlup_log .= "，体力恢复了{$lvupspref}，获得了{$lvup}点技能点！</span><br>";
			if(!$pa['type'])
			{
				if($pa['nm'] == '你') $log.= $lvlup_log;
				else $pa['lvlup_log'] = $lvlup_log;
			}
		} elseif ($pa['lvl'] >= 255) {
			$pa['lvl'] = 255;
			$pa['exp'] = $up_exp_temp;
		}
		$upexp = round(($pa['lvl']*$baseexp)+(($pa['lvl']+1)*$baseexp));
		return;
	}

	/*
	function kill($death, $dname, $dtype = 0, $dpid = 0, $annex = '', &$revival_flag=0) {
		global $now, $db, $tablepre, $gtablepre;
		global $alivenum, $deathnum, $name, $w_state, $type, $pid, $typeinfo, $pls, $lwinfo, $w_achievement;
		global $weather;
		
		if (! $death || ! $dname) {
			return;
		}
		
		if ($death == 'N') {
			$w_state = 20;
		} elseif ($death == 'P') {
			$w_state = 21;
		} elseif ($death == 'K') {
			$w_state = 22;
		} elseif ($death == 'G') {
			$w_state = 23;
		} elseif ($death == 'J') {
			$w_state = 23;
		} elseif ($death == 'C') {
			$w_state = 24;
		} elseif ($death == 'D') {
			$w_state = 25;
		} elseif ($death == 'F') {
			$w_state = 29;
		} elseif ($death == 'dn') {
			$w_state = 28;
		} else {
			$w_state = 10;
		}
		
		$killmsg = '';
		$result = $db->query ( "SELECT killmsg FROM {$gtablepre}users WHERE username = '$name'" );
		$killmsg = $db->result ( $result, 0 );
		
		if (! $dtype) {
			//$alivenum = $db->result($db->query("SELECT COUNT(*) FROM {$tablepre}players WHERE hp>0 AND type=0"), 0);
			$alivenum --;
		}
		$deathnum ++;
		
		
		if ($dtype) {
			$lwname = $typeinfo [$dtype] . ' ' . $dname;
			if (is_array ( $lwinfo [$dtype] )) {
				$lastword = $lwinfo [$dtype] [$dname];
			} else {
				$lastword = $lwinfo [$dtype];
			}
			
			$db->query ( "INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('3','$now','$lwname','$pls','$lastword')" );
		} else {
			$lwname = $typeinfo [$dtype] . ' ' . $dname;
			$result = $db->query ( "SELECT lastword FROM {$gtablepre}users WHERE username = '$dname'" );
			$lastword = $db->result ( $result, 0 );
			
			$db->query ( "INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('3','$now','$lwname','$pls','$lastword')" );
		}
		$result = $db->query("SELECT nick FROM {$tablepre}players WHERE name = '$name' AND type = '$type'");
		$knick = $db->result($result, 0);
		addnews ( $now, 'death' . $w_state, $dname, $dtype, $knick.' '.$name, $annex, $lastword );
		
		$result = $db->query("SELECT * FROM {$tablepre}players WHERE pid=$dpid" );
		$res=$db->fetch_array($result);
		
		$revivaled=false;
		//依次判定复活效果
		if (!$revival_flag && $weather == 17)
		{
			//include_once GAME_ROOT.'./include/game/dice.func.php';
			$aurora_rate = $dtype ? 1 : 10; //NPC概率1% 玩家概率10%
			$aurora_dice = diceroll(100);
			if($aurora_dice<=$aurora_rate)
			{
				//奥罗拉复活效果
				addnews($now,'aurora_revival',$res['name']);
				$res['hp'] += min($res['mhp'],max($aurora_dice,1)); $res['sp'] += min($res['msp'],max($aurora_dice,1));
				$res['state']=0;
				$alivenum++; 
				$revival_flag = 17;
			}
		}
		if (!$revival_flag && $res['type']==0 && $res['club']==99 && ($death=="N" || $death=="P" || $death=="K" || $death=="G" || $death=="C" ||$death=="D" || $death=="F" || $death=="J" || $death=="trap"))	
		{
			addnews($now,'revival',$res['name']);	//玩家春哥附体称号的处理
			$res['hp'] = $res['mhp']; $res['sp'] = $res['msp'];
			$res['club'] = 17; $res['state'] = 0;
			/*$db->query ( "UPDATE {$tablepre}players SET hp=mhp WHERE pid=$dpid" );
			$db->query ( "UPDATE {$tablepre}players SET sp=msp WHERE pid=$dpid" );
			$db->query ( "UPDATE {$tablepre}players SET club=17 WHERE pid=$dpid" );
			$db->query ( "UPDATE {$tablepre}players SET state=0 WHERE pid=$dpid" );
			$alivenum++;
			$revivaled = 99;
		}
		if (!$revivaled)
		{
			if($dtype == 15){//静流AI
				global $gamevars;
				unset($gamevars['act']);
				$gamevars['sanmadead'] = 1;
				save_gameinfo();
			}
			//$db->query ( "UPDATE {$tablepre}players SET hp='0',endtime='$now',deathtime='$now',bid='$pid',state='$w_state' WHERE pid=$dpid" );
		}

		player_save($res);
		save_gameinfo();
		return $killmsg;
	}

	function lvlup(&$lvl, &$exp, $isplayer = 1) {
		global $baseexp;
		$up_exp_temp = round ( (2 * $lvl + 1) * $baseexp );
		if ($exp >= $up_exp_temp && $lvl < 255) {
			if ($isplayer) {
				$perfix = '';
			} else {
				$perfix = 'w_';
			}
			global ${$perfix . 'name'}, ${$perfix . 'hp'}, ${$perfix . 'mhp'}, ${$perfix . 'sp'}, ${$perfix . 'msp'}, ${$perfix . 'att'}, ${$perfix . 'def'}, ${$perfix . 'upexp'}, ${$perfix . 'club'}, ${$perfix . 'type'}, ${$perfix . 'skillpoint'};
			global ${$perfix . 'wp'}, ${$perfix . 'wk'}, ${$perfix . 'wc'}, ${$perfix . 'wg'}, ${$perfix . 'wd'}, ${$perfix . 'wf'};
			$sklanginfo = Array ('wp' => '殴熟', 'wk' => '斩熟', 'wg' => '射熟', 'wc' => '投熟', 'wd' => '爆熟', 'wf' => '灵熟', 'all' => '全系熟练度' );
			$sknlist = Array (1 => 'wp', 2 => 'wk', 3 => 'wc', 4 => 'wg', 5 => 'wd', 9 => 'wf', 16 => 'all' );
			$skname = $sknlist [${$perfix . 'club'}];
			//升级判断
			$lvup = 1 + floor ( ($exp - $up_exp_temp) / $baseexp / 2 );
			$lvup = $lvup > 255 - $lvl ? 255 - $lvl : $lvup;
			$lvuphp = $lvupatt = $lvupdef = $lvupskill = $lvupsp = $lvupspref = 0;
			for($i = 0; $i < $lvup; $i += 1) {
				if (${$perfix . 'club'} == 13) {
					$lvuphp += rand ( 14, 18 );
				} else {
					$lvuphp += rand ( 8, 10 );
				}
				$lvupsp += rand( 4,6);
				if (${$perfix . 'club'} == 14) {
					$lvupatt += rand ( 4, 6 );
					$lvupdef += rand ( 5, 8 );
				} else {
					$lvupatt += rand ( 2, 4 );
					$lvupdef += rand ( 3, 5 );
				}
				
				if ($skname == 'all') {
					$lvupskill += rand ( 2, 4 );
				} elseif ($skname == 'wd' || $skname == 'wf') {
					$lvupskill += rand ( 3, 5 );
				}elseif($skname){
					$lvupskill += rand ( 4, 6 );
				}
				$lvupspref += round(${$perfix . 'msp'} * 0.1);		
			}
			$lvl += $lvup;
			$up_exp_temp = round ( (2 * $lvl + 1) * $baseexp );
			if ($lvl >= 255) {
				$lvl = 255;
				$exp = $up_exp_temp;
			}
			${$perfix . 'upexp'} = $up_exp_temp;
			${$perfix . 'hp'} += $lvuphp;
			${$perfix . 'mhp'} += $lvuphp;
			${$perfix . 'sp'} += $lvupsp;
			${$perfix . 'msp'} += $lvupsp;
			${$perfix . 'att'} += $lvupatt;
			${$perfix . 'def'} += $lvupdef;
			${$perfix . 'skillpoint'} += $lvup;
			if ($skname == 'all') {
				${$perfix . 'wp'} += $lvupskill;
				${$perfix . 'wk'} += $lvupskill;
				${$perfix . 'wg'} += $lvupskill;
				${$perfix . 'wc'} += $lvupskill;
				${$perfix . 'wd'} += $lvupskill;
				${$perfix . 'wf'} += $lvupskill;
			} elseif ($skname) {
				${$perfix . $skname} += $lvupskill;
			}
			
			if (${$perfix . 'sp'}+$lvupspref >= ${$perfix . 'msp'}) {
				$lvupspref =  ${$perfix . 'msp'} - ${$perfix . 'sp'};
				
			}
			${$perfix . 'sp'} += $lvupspref;
			if ($skname) {
				$sklog = "，{$sklanginfo[$skname]}+{$lvupskill}";
			}
			if ($isplayer) {
				global $log;
				$log .= "<span class=\"yellow\">你升了{$lvup}级！生命上限+{$lvuphp}，体力上限+{$lvupsp}，攻击+{$lvupatt}，防御+{$lvupdef}{$sklog}，体力恢复了{$lvupspref}，获得了{$lvup}点技能点！</span><br>";
			} elseif (! $w_type) {
				global $w_pid, $now;
				$w_log = "<span class=\"yellow\">你升了{$lvup}级！生命上限+{$lvuphp}，体力上限+{$lvupsp}，攻击+{$lvupatt}，防御+{$lvupdef}{$sklog}，体力恢复了{$lvupspref}，获得了{$lvup}点技能点！</span><br>";
				logsave ( $w_pid, $now, $w_log,'s');
			}
		} elseif ($lvl >= 255) {
			$lvl = 255;
			$exp = $up_exp_temp;
		}
		return;
	}*/

	//玩家被攻击时的生命恢复未实现

	function calculate_rest_upsp($rtime,&$pa)
	{
		global $sleep_time,$db,$tablepre,$log;
		# 治疗姿态下恢复速率变为3倍
		if($pa['pose'] == 5) $rtime *= 3;
		$upsp = round ($pa['msp'] * $rtime / $sleep_time / 100 );
		# 灵子姿态下，恢复速率受种火数量加成
		if($pa['pose'] == 8 && $pa['sp'] < $pa['msp'])
		{
			$result = $db->query("SELECT pid FROM {$tablepre}players WHERE type=92 AND pls={$pa['pls']} AND hp>0 ");
			$nums = $db->num_rows($result);
			if($nums)
			{
				$log .= "在你闭目养神之际……似乎有什么东西戳了戳你的脸……<br>感觉身体轻松了不少。<br>";
				$upsp += $nums * 10 * $rtime;
			}
		}
		return $upsp;
	}
	function calculate_rest_uphp($rtime,&$pa)
	{
		global $heal_time,$db,$tablepre,$log;
		# 治疗姿态下恢复速率变为3倍
		if($pa['pose'] == 5) $rtime *= 3;
		$uphp = round ($pa['mhp'] * $rtime / $heal_time / 100 );
		/*if (strpos ($pa['inf'], 'b' ) !== false) {
			$uphp = round ( $uphp / 2 );
		}*/
		# 灵子姿态下，恢复速率受种火数量加成
		if($pa['pose'] == 8 && $pa['hp'] < $pa['mhp'])
		{
			$result = $db->query("SELECT pid FROM {$tablepre}players WHERE type=92 AND pls={$pa['pls']} AND hp>0 ");
			$nums = $db->num_rows($result);
			if($nums)
			{
				$log .= "在你专心治疗伤口时……似乎有什么东西靠了过来……<br>伤口好像不那么疼了。<br>";
				$uphp += $nums * 10 * $rtime;
			}
		}
		return $uphp;
	}
	//静养获得怒气
	function calculate_rest_rageup($rtime,&$pa)
	{
		global $rage_time;
		$max_rage = 255;
		$rageup = round ($max_rage * $rtime / $rage_time / 100 );
		if (strpos ( $pa['inf'], 'h' ) !== false) {//脑袋受伤不容易愤怒（
			$rageup = round ( $rageup / 2 );
		}
		return $rageup;
	}
	function rest($command,&$data=NULL) {
		//global $now, $log, $mode, $cmd, $state, $endtime, $hp, $mhp, $sp, $msp, $sleep_time, $heal_time, $restinfo, $pose, $inf,$club,$exdmginf;
		global $now,$log,$mode,$cmd,$sleep_time,$heal_time,$restinfo,$exdmginf;
		global $pdata;

		if(!isset($data))
		{
			global $pdata;
			$data = &$pdata;
		}
		extract($data,EXTR_REFS);

		$resttime = $now - $endtime;
		$endtime = $now;

		if ($state == 1 || $state == 3) {
			$oldsp = $sp;
			$upsp = calculate_rest_upsp($resttime,$data);
			$sp += $upsp; $sp = min($sp, $msp);
			$upsp = $sp - $oldsp;
			$upsp=max(0,$upsp);
			if(!$upsp && $sp >= $msp) $log .= "已经不需要休息了。";
			else $log .= "你的体力恢复了<span class=\"yellow\">$upsp</span>点。";
		} 

		if ($state == 2 || $state == 3) {
			$oldhp = $hp;
			$uphp = calculate_rest_uphp($resttime,$data);
			$hp += $uphp; $hp = min($hp, $mhp);
			$uphp = $hp - $oldhp;
			$uphp=max(0,$uphp);
			if(!$uphp && $hp >= $mhp) $log .= "没有伤口需要治疗了。";
			else $log .= "你的生命恢复了<span class=\"yellow b\">$uphp</span>点。";
		} 

		if($state == 3)
		{
			if($pose != 8)
			{
				$rageup = min(255-$rage,calculate_rest_rageup($resttime,$data));
				$rage += $rageup;
				$log .= "<br>但你在病床上辗转反侧，脑中回忆起种种倒霉遭遇，忍不住越想越气！<br>怒气增加了<span class=\"yellow\">$rageup</span>点！";
			}
			if (!empty($inf))
			{
				$refintv = 90;
				if ($pose == 5) {
					$refintv -= 30;
				}
				if (strpos ( $inf, 'b' ) !== false) {
					$refintv += 30;
				}
				$spinf = preg_replace("/[h|b|a|f]/", "", $inf);
				$spinflength = strlen($spinf);
				if($spinf){
					$refflag = false;
					do{
						$dice = rand(0,$refintv);
						if($dice + 15 < $resttime){
							$infno = rand(0,$spinflength-1);
							$refinfstr = substr($spinf,$infno,1);
							$inf = str_replace($refinfstr,'',$inf);
							$spinf = str_replace($refinfstr,'',$spinf);
							$log .= "<span class=\"yellow\">你从{$exdmginf[$refinfstr]}状态中恢复了！</span><br>";
							$spinflength -= 1;
							$refflag = true;
						}
						$resttime -= $refintv;
					} while ($resttime > 0 && $spinflength > 0);
					if(!$refflag){
						$log .= "也许是时间不够吧……你没有治好任何异常状态。<br>";
					}
				}
			}
		}
		
		$log .= '<br>';

		if ($command != 'rest') {
			$state = 0;
			$endtime = $now;
			$mode = 'command';
		}
		return;
	}

?>
