<?php

namespace revbattle
{

	if(!defined('IN_GAME')) {
		exit('Access Denied');
	}

	include_once GAME_ROOT.'./include/game/revbattle.calc.php';
	include_once GAME_ROOT.'./include/game/revcombat.func.php';

	# 处理从界面传回的战斗相关指令，包含以下两种情况：
	# 1.主动遇敌先制发现敌人；
	# 2.与敌人战斗结束、显示战斗报告后，点击确认按钮时身上存在额外action，跳转回此函数判断接下来该显示哪一页面；
	function revbattle_prepare($command,$message=NULL,$data=NULL)
	{
		global $log,$mode,$plsinfo,$db,$tablepre,$action_list;
		if(!isset($data))
		{
			global $pdata;
			$data = &$pdata;
		}
		extract($data,EXTR_REFS);
		# 检查是否存在战斗动作
		if(empty($action) || empty($bid) || !in_array($action,$action_list))
		{
			$log .= "你没有遇到敌人，或已经离开战场！<br>";
			$action = ''; $bid = 0;
			$mode = 'command';
			return;
		}
		# 检查是否遇敌
		$enemyid = $bid;
		$result = $db->query ( "SELECT * FROM {$tablepre}players WHERE pid='$enemyid'");
		if (!$db->num_rows($result)) 
		{
			$log .= "对方不存在！<br>";
			$action = ''; $bid = 0;
			$mode = 'command';
			return;
		}
		# 获取敌人数据
		//$edata = $db->fetch_array($result);
		$edata = fetch_playerdata_by_pid($enemyid);
		# 检查敌人是否处于当前位置
		if ($edata['pls'] != $pls) 
		{
			$log .= "<span class=\"yellow\">" . $edata ['name'] . "</span>已经离开了<span class=\"yellow\">$plsinfo[$pls]</span>。<br>";
			$action = ''; $bid = 0;
			$mode = 'command';
			return;
		}
		# 检查敌人是否已死亡
		if ($edata ['hp'] <= 0)
		{
			if($action != 'focus') $log .= "<span class=\"red\">" . $edata ['name'] . "</span>已经死亡，不能被攻击。<br>";
			include_once GAME_ROOT . './include/game/battle.func.php';
			$action = 'corpse'; $bid = $edata['pid'];
			findcorpse($edata);
			return;
		}
		# 输入切换武器指令时，切换武器
		if ($command == 'changewep') 
		{
			include_once GAME_ROOT . './include/game/itemmain.func.php';
			change_subwep();
			findenemy_rev($edata);
			return;
		}
		# 准备进入标准战斗流程
		include_once GAME_ROOT.'./include/game/revcombat.func.php';
		# 追击流程
		if ($command == 'chase' || $command == 'pchase' || $command == 'dfight') 
		{
			findenemy_rev($edata);
			return;
		}
		# 逃跑流程
		if ($command == 'back') 
		{
			//$log .= "你逃跑了。";
			$flag = escape_from_enemy($data,$edata);
			if($flag) $mode = 'command';
			else \revcombat\rev_combat_prepare($data,$edata,0);
			return;
		}
		# 重新遭遇视野中的敌人
		if ($command == 'focus') 
		{
			// 迎战视野中的敌人先制率-40
			$active_r = min(4,calc_active_rate($data,$edata)-40);
			$active_dice = diceroll(99);
			if($active_dice < $active_r){
				$action = 'enemy'; $bid = $edata['pid'];
				findenemy_rev($edata);
			}else {
				\revcombat\rev_combat_prepare($edata,$data,0);
			}
			return;
		}
		# 由协战对象攻击敌人
		if ($command == 'cover')
		{
			$action = ''; $bid = 0;
			if(empty($clbpara['coveratk']))
			{
				$log .= "协战对象不存在！<br>";
				$mode = 'command';
				return;
			}
			$coid = $clbpara['coveratk']; unset($clbpara['coveratk']);
			# 检查协战对象数据，要求：活的、同一地图
			$result = $db->query ( "SELECT * FROM {$tablepre}players WHERE pid='$coid' AND pls='$pls' AND hp>0 ");
			if (!$db->num_rows($result)) 
			{
				$log .= "协战对象不存在！<br>";
				$mode = 'command';
				return;
			}
			# 获取协战对象数据
			$cdata = fetch_playerdata_by_pid($coid);
			# 协战对象是佣兵的话要加钱，不过毕竟是自愿行为，只+1行动次数就好了
			if(!empty(get_skillpara('c11_merc','id',$clbpara)) && in_array($coid,get_skillpara('c11_merc','id',$clbpara)))
			{
				include_once GAME_ROOT.'./include/game/revclubskills_extra.func.php';
				$cokey = array_search($coid,get_skillpara('c11_merc','id',$clbpara));
				skill_merc_paid('c11_merc',$cokey,$cdata);
			}
			# 添加协战标记
			$cdata['is_coveratk'] = 1;
			# 去打人吧！
			\revcombat\rev_combat_prepare($cdata,$edata,1);
			return;
		}
		# 指派佣兵攻击敌人
		if (strpos($command,'bskill_c11_merc') === 0) 
		{
			$mkey = str_replace('bskill_c11_merc','',$command);
			$sk = 'c11_merc';
			# 检查是否存在对应可攻击的佣兵
			if(!empty($clbpara['skillpara'][$sk]['cancover'][$mkey]))
			{
				# 获取对应佣兵数据
				$mid = $clbpara['skillpara'][$sk]['id'][$mkey];
				$mdata = fetch_playerdata_by_pid($mid);
				# 检查并扣除指挥的佣兵花费、佣兵行动次数增加对应数，并结算一次工资
				include_once GAME_ROOT.'./include/game/revclubskills_extra.func.php';
				$clbpara['skillpara'][$sk]['mms'][$mkey] += get_skillvars($sk,'atkp');
				skill_merc_paid($sk,$mkey,$mdata);
				# 扣钱
				$mccost = get_skillvars($sk,'atkp') * get_skillpara($sk,'paid',$clbpara)[$mkey];
				$money -= $mccost;
				# 干活！
				$log .= "<span class='yellow'>你掏出{$mccost}元振臂一呼，{$mdata['name']}接过钱后毫不犹豫地扑向了敌人！</span><br>";
				# 登记为收钱办事
				$mdata['is_merc'] = 1;
				# 是否要检查先后手？
				if(isset($message) && $message == 'noactive')
				{
					$message = '';
					$active_r = calc_active_rate($mdata,$edata);
					$active_dice = diceroll(99);
					if($active_dice < $active_r)
					{
						\revcombat\rev_combat_prepare($mdata,$edata,1); 
					}
					else 
					{
						$log .= "<span class='yellow'>但是敌人早已做好了准备！</span><br>";
						\revcombat\rev_combat_prepare($edata,$mdata,0);
					}
				}
				else 
				{
					\revcombat\rev_combat_prepare($mdata,$edata,1); 
				}
			}
			# 没有佣兵，你自己上吧！
			else
			{
				\revcombat\rev_combat_prepare($data,$edata,1);
			}
			return;
		}
		if(!empty($message)) $data['message'] = $message;
		# 上述流程没有拦截，直接进入标准战斗流程……可能会有问题？
		\revcombat\rev_combat_prepare($data,$edata,1,$command);
		return;
	}

	# 主动发现敌人时，初始化战斗界面（包含初始化左侧页面与初始化右侧战斗按钮）
	function findenemy_rev($edata) 
	{
		global $db,$tablepre,$log,$mode,$main,$cmd,$battle_title,$attinfo,$skillinfo,$nosta,$cskills;
		global $fog,$pdata;
    global $battle_skills;

		//格式化双方clbpara
		$edata['clbpara'] = get_clbpara($edata['clbpara']);

		//检查是否为追击状态
		$ismeet = strpos($pdata['action'],'chase')!==false || strpos($pdata['action'],'dfight')!==false ? 1 : 0;
		//初始化战场标题
		$battle_title = init_battle_title($pdata,$edata,$ismeet);
		//初始化遇敌log
		$log .= init_battle_log($pdata,$edata,$ismeet);
		//初始化战斗界面
		init_battle_rev($pdata,$edata,$ismeet);

		//检查是敌对或中立单位
		$neut_flag = $edata['pose'] == 7 ? 1 : 0;

		//初始化玩家战斗技列表
		if(!empty($pdata['clbpara']['skill']))
		{

			$battle_skills = Array();
			$sk_nums = 0;
			foreach($pdata['clbpara']['skill'] as $sk)
			{
				//遍历玩家技能，寻找带有战斗技标签的技能
				if(get_skilltags($sk,'battle'))
				{
					$sk_desc = '';
					//先检查技能是否满足解锁条件
					$unlock = check_skill_unlock($sk,$pdata);
					if($unlock)
					{
						if(get_skilltags($sk,'unlock_battle_hidden')) continue;
						$sk_desc .= parse_skilllockdesc($sk,$unlock);
						//$sk_desc .= is_array($cskills[$sk]['lockdesc']) ? $cskills[$sk]['lockdesc'][$unlock] : $cskills[$sk]['lockdesc'];
					}
					//再检查技能是否满足激活条件
					else
					{
						$unlock = check_skill_cost($sk,$pdata);
						if($unlock) $sk_desc .= $unlock;
					}
					//技能可以使用，输出介绍文本
					if(empty($sk_desc)) $sk_desc = parse_skilldesc($sk,$pdata,1);
					//存入可使用战斗技队列，顺序：是否可使用、技能名、技能介绍文本
					$battle_skills[$sk_nums] = Array($unlock,$sk,$sk_desc);
					$sk_nums++;
				}
				# 如果雇佣了佣兵，且佣兵与你在同一地图，可以指挥佣兵攻击
				if($sk == 'c11_merc' && !empty(get_skillpara('c11_merc','cancover',$pdata['clbpara'])))
				{
					# 遍历可协战佣兵队列，检查是否有可以出击的佣兵
					$mcids = get_skillpara($sk,'cancover',$pdata['clbpara']);
					foreach($mcids as $mkey => $mc)
					{
						if($mc)
						{
							# 拉取佣兵数据
							$mid = get_skillpara($sk,'id',$pdata['clbpara'])[$mkey];
							$mdata = fetch_playerdata_by_pid($mid);
							# 检查是否有钱强制命令佣兵攻击
							$mccost = get_skillvars($sk,'atkp') * get_skillpara($sk,'paid',$pdata['clbpara'])[$mkey];
							$unlock = $mccost > $pdata['money'] ? 1 : 0;
							$sk_desc = $unlock ? '你没有足够的钱指挥佣兵主动攻击' : "花费<span class='yellow'>{$mccost}</span>元，指挥<span class='yellow'>{$mdata['name']}</span>发动攻击，攻击后佣兵会<span class='yellow'>锁定</span>敌人，离开地图前可再度对敌人进行<span class='yellow'>追击</span>";
							# 将佣兵攻击指令加入指令集
							$cskills[$sk.$mkey]['name'] = "佣兵攻击";
							$battle_skills[$sk_nums] = Array($unlock,$sk.$mkey,$sk_desc);
							$sk_nums++;
						}
					}
				}
				//先制状态下可以自动给弓上箭
				if (strpos ( $pdata['wepk'], 'WB' ) === 0 && $pdata['weps'] === $nosta) {			
					//$tmp_log = $log;
					$pos_a = 0;
					//遍历所有包裹，寻找最靠前的箭矢装填
					for($i=1; $i<=6; $i++){
						if($pdata['itmk'.$i] == 'GA') {
							$pos_a = $i;
							break;
						}
					}
					//直接调用上箭的函数
					if($pos_a) {
						$log .= '你及时弯弓搭箭，';
						include_once GAME_ROOT . './include/game/item2.func.php';
						itemuse_ugb($pdata, $pos_a);
					}
					//$tmp_log_2 = substr($log, strlen($tmp_log));
					//$log = $tmp_log;//暂存一下$log，调整显示顺序，不过可能不利于以后扩展，再说吧……
				}
				//if(!empty($tmp_log_2)) $log .= $tmp_log_2;
			}
		}

		//初始化玩家攻击方式信息
		$w1 = substr($pdata['wepk'],1,1);
		$w2 = substr($pdata['wepk'],2,1);
		if(empty($w2) || is_numeric($w2) || '|' == $w2) $w2='';
		if (($w1 == 'G'||$w1=='J')&&($pdata['weps']==$nosta)) $w1 = 'P';

		include template('battlecmd_rev');
		$cmd = ob_get_contents();
		ob_clean();
		$main = 'battle_rev';
		return;
	}

	# 战斗中逃跑
	function escape_from_enemy(&$pa,&$pd)
	{
		global $fog,$action,$clbpara,$chase_escape_obbs,$log;
		//include_once GAME_ROOT.'./include/game/dice.func.php';
		# 在受追击/鏖战状态下逃跑有概率失败
		if($action == 'pchase' || $action == 'dfight')
		{
			$escape_dice = diceroll(99);
			if($escape_dice < $chase_escape_obbs)
			{
				$log .= "你尝试逃跑，但是敌人在你身后紧追不舍！<br>";
				$pa['fail_escape'] = 1;
				return 0;
			}
		}
		$log .= "你逃跑了。<br>";
		$action = ''; $bid = 0;
		//逃跑后在视野里记录敌人
		$nm = $fog ? '？？？' : $pd['name'];
		check_add_searchmemory($pd['pid'],'enemy',$nm,$pa);
		unset($clbpara['battle_turns']);
		return 1;
	}

	# 主动发现中立单位时，初始化战斗界面（包含初始化左侧页面与初始化右侧信息栏）
	function findneut(&$edata,$kind=0)
	{
		global $db,$tablepre,$pdata;
		global $now,$fog,$log,$mode,$main,$cmd,$battle_title,$attinfo,$skillinfo;
		
		$battle_title = $kind ? '发现朋友' : '发现敌人？';

		//格式化双方clbpara
		$pdata['clbpara'] = get_clbpara($pdata['clbpara']); $edata['clbpara'] = get_clbpara($edata['clbpara']);

		init_battle_rev($pdata,$edata,1);

		$log .= "你发现了<span class=\"yellow\">{$edata['name']}</span>！<br>";
		if(!$kind) $log .= "对方看起来没有敌意。<br>";

		//TODO：把这一段挪到一个独立函数里
		if($edata['clbpara']['post'] == $pdata['pid']) 
		{	
			$log.="对方一看见你，便猛地朝你扑了过来！<br>
			<br><span class='sienna'>“老板！有你的快递喔！”</span><br>
			<br>你被这突然袭击吓了一跳！<br>
			但对方只是从身上摸出了一个包裹样的东西扔给了你。然后又急匆匆地转身离开了。<br>
			<br>……这是在搞啥……？<br><br>";
			$action='';
			global $itm0,$itmk0,$itme0,$itms0,$itmsk0;
			$iid=$edata['clbpara']['postid'];
			$itm0=$edata['itm'.$iid];$itmk0=$edata['itmk'.$iid];$itmsk0=$edata['itmsk'.$iid];
			$itme0=$edata['itme'.$iid];$itms0=$edata['itms'.$iid];
			//发一条news 表示快递已送达
			$sponsorid = $edata['clbpara']['sponsor'];
			$result = $db->query("SELECT * FROM {$tablepre}gambling WHERE uid = '$sponsorid'");
			$sordata = $db->fetch_array($result);
			addnews($now,'gpost_success',$sordata['uname'],$itm0,$pdata['name']);
			//再见了~快递员！
			unset($edata['clbpara']['post']);unset($edata['clbpara']['postid']);unset($edata['clbpara']['sponsor']);
			$edata['hp'] = 0;
			destory_corpse($edata);
			//解除快递锁
			$db->query("UPDATE {$tablepre}gambling SET bnid=0 WHERE uid='$sponsorid'");
		}

		include template('findneut');
		$cmd = ob_get_contents();
		ob_clean();
		$main = 'battle_rev';
		return;
	}

	# 初始化战斗界面标题
	function init_battle_title($pa,$pd,$ismeet=0)
	{
		if(strpos($pa['action'],'chase')!==false)
		{
			if(strpos($pa['action'],'pchase')!==false) $title = '遭到追击';
			else $title = '乘胜追击';
		}
		if(strpos($pa['action'],'dfight')!==false)
		{
			$title = '陷入鏖战';
		}
		else 
		{
			$title = '发现敌人';
		}
		return $title;
	}

	# 初始化战斗界面log
	function init_battle_log($pa,$pd,$ismeet=0)
	{
		global $fog;
		$pd['name'] = $fog && !$ismeet && check_skill_unlock('c6_godeyes',$pa) ? '？？？' : $pd['name'];
		if(strpos($pa['action'],'chase')!==false)
		{
			if(strpos($pa['action'],'pchase')!==false)
			{
				$battle_log = "但是<span class=\"red\">{$pd['name']}</span>在你身后紧追不舍！<br>";
			}
			else 
			{
				$battle_log = "你再度锁定了<span class=\"red\">{$pd['name']}</span>！<br>";
			}
		}
		elseif(strpos($pa['action'],'dfight')!==false)
		{
			$battle_log = "你与<span class=\"red\">{$pd['name']}</span>相互对峙着！<br>";
		}
		else 
		{
			if($pd['pose'] == 7)
			{
				$battle_log ="你发现了<span class=\"lime\">{$pd['name']}</span>！<br>对方看起来对你没有敌意。<br>";
			}
			else
			{
				$battle_log ="你发现了敌人<span class=\"red\">{$pd['name']}</span>！<br>对方好像完全没有注意到你！<br>";
			}
		}
		return $battle_log;
	}
}
?>