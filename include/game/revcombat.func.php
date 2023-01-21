<?php

	if(!defined('IN_GAME')) {
		exit('Access Denied');
	}
	include_once GAME_ROOT.'./include/game/clubskills.func.php';
	include_once GAME_ROOT.'./include/game/combat.func.php';
	include_once GAME_ROOT.'./include/game/attr.func.php';
	
	/*这个文件里的函数是供npc与npc战斗使用的。
	但是只要提供了正确的pa和pd当然也可以给玩家使用。 //哈哈！不行！玩家数据存不回去！傻了吧！ //虽然不能直接替换掉原版战斗函数，但是也许能用做这个的思路对原版进行一些优化……
	本质上就是一套整理过的原版战斗函数。*/


	//战斗准备流程：通过传入的战斗双方ID初始化
	function rev_combat_prepare($nid,$eid) 
	{
		global $db,$tablepre,$log,$mode,$main,$cmd,$battle_title;
		global $n_type,$n_name,$n_gd,$n_sNo,$n_icon,$n_hp,$n_mhp,$n_sp,$n_msp,$n_rage,$n_wep,$n_wepk,$n_wepe,$n_lvl,$n_pose,$n_tactic,$n_inf;
		global $w_type,$w_name,$w_gd,$w_sNo,$w_icon,$w_hp,$w_mhp,$w_wep,$w_wepk,$w_wepe,$w_lvl,$w_pose,$w_tactic,$w_inf;
		//初始化进攻方数据
		if($nid)
		{
			$result = $db->query("SELECT * FROM {$tablepre}players WHERE pid='$nid' AND hp>0");
			if($db->num_rows($result)>0)
			{
				$ndata = $db->fetch_array($result);
				extract($ndata,EXTR_PREFIX_ALL,'n');
			}
		}
		//初始化防守方数据
		if($eid)
		{
			$result = $db->query("SELECT * FROM {$tablepre}players WHERE pid='$eid' AND hp>0");
			if($db->num_rows($result)>0) 
			{
				$edata = $db->fetch_array($result);
				extract($edata,EXTR_PREFIX_ALL,'w');
			}
		}
		if($ndata && $edata && $nid!=$eid)
		{
			init_battle(1);
			$main = 'revbattle';
			//进入战斗流程
			rev_combat($ndata,$edata,1);
			include template('revbattleresult');
			$cmd = ob_get_contents();
			ob_clean();
		}
		else 
		{
			$log.="初始化战斗对象失败，可能是攻方/守方已死，或传入的NPCID非法。<br>";
		}
		return;
	}

	//战斗流程：pa(攻方)pd(守方)active(1=玩家为pa;0=玩家为pd)
	function rev_combat(&$pa,&$pd,$active,$wep_kind='') 
	{
		global $log,$mode,$main,$cmd,$action,$db,$tablepre,$now,$nosta,$hdamage,$hplayer;
		global $infinfo,$plsinfo,$battle_title,$message;

		$battle_title = '战斗发生';

		if (!$wep_kind) 
		{
			$w1 = substr ($pa['wepk'], 1, 1 );
			$w2 = substr ($pa['wepk'], 2, 1 );
			if ((($w1 == 'G')||($w1=='J')) && ($pa['weps'] == $nosta)) $wep_kind = $w2 ? $w2 : 'P';
			else $wep_kind = $w1;
		}
		elseif(strpos($pa['wepk'],$wep_kind)===false && $wep_kind != 'back')
		{
			$wep_kind = substr ($pa['wepk'], 1, 1 );
		}
		$pa['wep_kind'] = $wep_kind;

		//战斗发起者是玩家时才会判断的一些事件
		if(!$pa['type'] && $active)
		{
			if($pa['pls'] != $pd['pls']) 
			{
				$log .= "<span class=\"yellow\">".$pd['name']."</span>已经离开了<span class=\"yellow\">{$plsinfo[$pa['pls']]}</span>。<br>";
				$action = '';
				$mode = 'command';
				return;
			}
			if($pd['hp'] <= 0) 
			{
				$log .= "<span class=\"red\">".$pd['name']."</span>已经死亡，不能被攻击。<br>";
				return;
			}
			if ($message) 
			{
				$log.="<span class=\"lime\">你对{$pd['name']}大喊：{$message}</span><br>";
				if (!$pd['type']) 
				{
					$w_log = "<span class=\"lime\">{$name}对你大喊：{$message}</span><br>";
					logsave ($pd['pid'],$now,$w_log,'c');
				}
			}
		}
	
		if($active)
		{
			$log .= "你向<span class=\"red\">{$pd['name']}</span>发起了攻击！<br>";
		}
		else
		{
			$log .= "<span class=\"red\">{$pa['name']}</span>突然向你袭来！<br>";
		}
		//战斗发起者是NPC时进行的判断
		if($pa['type'])
		{
			//$log .= npc_chat ($pa['type'],$pa['name'],'attack');
			//npc_changewep(); TODO
		}

		//打击流程
		$att_dmg = rev_attack($pa,$pd,$active);

		//暴毙流程
		if($pa['ggflag'])
		{
			unset($pa['ggflag']);
			return;
		}
		
		//打击效果结算
		if(isset($att_dmg))
		{
			//扣血
			$pd['hp'] = max(0,$pd['hp']-$att_dmg);
			//判断是否触发击杀或复活
			$att_result = rev_combat_result($pa,$pd,$active);
		} 

		//判断是否进入反击流程 把!$att_result去掉可以实现复活后反击 很酷吧！
		if (($pd['hp'] > 0) && ($pd['pose'] != 5) && ($pd['tactic'] != 4) && !$att_result) 
		{
			global $rangeinfo;
			$w_w1 = substr ($pd['wepk'], 1, 1 );
			$w_w2 = substr ($pd['wepk'], 2, 1 );
			if ((($w_w1 == 'G')||($w_w1=='J')) && ($pd['weps'] == $nosta)) {
				$pd['wep_kind'] = $w_w2 ? $w_w2 : 'P';
			} else {
				$pd['wep_kind'] = $w_w1;
			}
			//echo "【DEBUG】{$pd['name']}的攻击方式是{$pd['wep_kind']}<br>";
			$d_wep_temp = $pd['wep'];

			if ($rangeinfo [$pa['wep_kind']] <= $rangeinfo [$pd['wep_kind']] && $rangeinfo [$pa['wep_kind']] !== 0) 
			{
				$counter = get_counter ($pd['wep_kind'], $pd['tactic'], $pd['club'], $pd['inf']);
				$counter *= rev_get_clubskill_bonus_counter($pd['club'],$pd['skills'],$pd,$pa['club'],$pa['skills'],$pa);
				$counter_dice = rand ( 0, 99 );
				if ($counter_dice < $counter) 
				{
					$log .= "<span class=\"red\">{$pd['name']}的反击！</span><br>";
					$log .= npc_chat ($pd['type'],$pd['name'], 'defend' );
					//反击打击实行
					$def_dmg = rev_attack($pd,$pa,1);
				} 
				else 
				{
					$log .= npc_chat ($pd['type'],$pd['name'], 'escape' );
					$log .= "<span class=\"red\">{$pd['name']}处于无法反击的状态，逃跑了！</span><br>";
				}
			} 
			else 
			{
				$log .= npc_chat($pd['type'],$pd['name'], 'cannot' );
				$log .= "<span class=\"red\">{$pd['name']}攻击范围不足，不能反击，逃跑了！</span><br>";
			}
		}
		elseif($pd['hp']>0) 
		{
			$log .= "<span class=\"red\">{$pd['name']}逃跑了！</span><br>";
		}

		//反击效果结算
		if(isset($def_dmg))
		{
			//扣血
			$pa['hp'] = max(0,$pa['hp']-$def_dmg);
			//判断是否触发击杀或复活
			$def_result = rev_combat_result($pd,$pa,1-$active);
		}

		//检查是否更新最高伤害情报
		$att_dmg = $att_dmg ? $att_dmg : 0;
		$def_dmg = $def_dmg ? $def_dmg : 0;
		if (($att_dmg > $hdamage) && ($att_dmg >= $def_dmg) && (!$pa['type'])) {
			$hdamage = $att_dmg;
			$hplayer = $pa['name'];
			save_combatinfo ();
		} elseif (($def_dmg > $hdamage) && (!$pd['type'])) {
			$hdamage = $def_dmg;
			$hplayer = $pd['name'];
			save_combatinfo ();
		}

		//如果战斗中出现了死人 更新action标记
		if ($active) 
		{ 
			if ($pd['hp']<=0 && $pa['hp']>0)
			{
				$pa['action']='corpse'.$pd['pid'];
			}
			if ($pa['hp']<=0 && $pd['hp']>0 && $pd['action']=='' && $pd['type']==0)
			{
				$pd['action'] = 'pacorpse'.$pa['pid']; 
			}		
		}
		else
		{
			if ($pd['hp']<=0 && $pa['hp']>0 && $pa['action']=='' && $pa['type']==0)
			{
				$pa['action']='pacorpse'.$pd['pid'];
			}
			if ($pa['hp']<=0 && $pd['hp']>0)
			{
				$pd['action'] = 'corpse'.$pa['pid']; 
			}
		}
		$log = str_replace('你',$pa['name'],$log); //偷懒做法 如果NPC的台词里有“你”出现的话 会变得很怪23333
		//保存两个人的状态
		player_save($pa);player_save($pd);
		//刷新界面状态
		global $n_iconImg,$n_type,$n_name,$n_gd,$n_sNo,$n_icon,$n_hp,$n_mhp,$n_sp,$n_msp,$n_rage,$n_wep,$n_wepk,$n_wepe,$n_lvl,$n_pose,$n_tactic,$n_inf,$n_wep_words,$n_wepk_words;
		global $w_type,$w_name,$w_gd,$w_sNo,$w_icon,$w_hp,$w_mhp,$w_wep,$w_wepk,$w_wepe,$w_lvl,$w_pose,$w_tactic,$w_inf;
		extract($pa,EXTR_PREFIX_ALL,'n'); extract($pd,EXTR_PREFIX_ALL,'w');
		$main = 'revbattle';
		init_battle (1);
		//条件TODO：由玩家控制的，或者与玩家处于盟友状态的NPC完成了击杀，给玩家传一个摸尸体标记，这样玩家点了确定之后就可以去摸尸体了
		global $action;
		$action = $pa['action'];
		//毁尸灭迹
		unset($pa);unset($pd);
		return;
	}

	//打击流程：pa(打击方);pd(被打方);active(1=pa主动攻击;0=pa发起反击)
	function rev_attack(&$pa,&$pd,$active = 1) 
	{
		//通用
		global $now,$nosta,$log,$infobbs,$infinfo,$attinfo,$skillinfo,$wepimprate,$specialrate;
		global $db,$tablepre;
		//枪托攻击标识
		$is_wpg = false;
		//武器效果值修正
		$watt=-1;
		if (((strpos ($pa['wepk'], 'G' ) == 1)||(strpos($pa['wepk'],'J')==1)) && ($pa['weps'] == $nosta)) {
			if (($pa['wep_kind'] == 'G')||($pa['wep_kind'] == 'P')||($pa['wep_kind']=='J')) 
			{
				$pa['wep_kind'] = 'P';
				$is_wpg = true;
				$watt = round ($pa['wepe']/ 5 );
			} 
			else 
			{
				$watt = $pa['wepe'];
			}
		}
		
		$log .= "{$pa['name']}使用{$pa['wep']}<span class=\"yellow\">{$attinfo[$pa['wep_kind']]}</span>{$pd['name']}！<br>";
		
		$pa['att_key'] = getatkkey ( $pa['wepsk'], $pa['arhsk'], $pa['arbsk'], $pa['arask'], $pa['arfsk'], $pa['artsk'], $pa['artk'], $is_wpg );
		$pd['def_key'] = getdefkey ( $pd['wepsk'], $pd['arhsk'], $pd['arbsk'], $pd['arask'], $pd['arfsk'], $pd['artsk'], $pd['artk'] );
		
		//三抽标识
		$mdr = $skdr = $sldr = false;
		if(strpos($pa['att_key'].$pd['def_key'],'-')!==false){$mdr = true;}//精抽
		if(strpos($pa['att_key'].$pd['def_key'],'*')!==false){$sldr = true;}//魂抽
		if(strpos($pa['att_key'].$pd['def_key'],'+')!==false){$skdr = true;}//技抽
		if($mdr || $sldr || $skdr){
			list($wsk,$hsk,$bsk,$ask,$fsk,$tsk,$tk)=Array($pa['wepsk'], $pa['arhsk'], $pa['arbsk'], $pa['arask'], $pa['arfsk'], $pa['artsk'], $pa['artk']);
			list($wwsk,$whsk,$wbsk,$wask,$wfsk,$wtsk,$wtk)=Array($pd['wepsk'],$pd['arhsk'],$pd['arbsk'],$pd['arask'],$pd['arfsk'],$pd['artsk'],$pd['artk']);
			if($mdr){
				$log .= "<span class=\"yellow\">精神抽取使双方的防具属性全部失效！</span><br>";
				$hsk = $bsk = $ask = $fsk = $whsk = $wbsk = $wask = $wfsk = '';
			}
			if($sldr){
				$log .= "<span class=\"yellow\">灵魂抽取使双方的武器和饰物属性全部失效！</span><br>";
				$wsk = $tsk = $tk = $wwsk = $wtsk = $wtk = '';
			}
			if($skdr){
				$log .= "<span class=\"yellow\">技能抽取使双方的武器熟练度在战斗中大幅下降！</span><br>";
			}
			$pa['att_key'] = getatkkey ( $wsk,$hsk,$bsk,$ask,$fsk,$tsk,$tk, $is_wpg );
			$pd['def_key'] = getdefkey ( $wwsk,$whsk,$wbsk,$wask,$wfsk,$wtsk,$wtk );
		}
		//echo "【DEBUG】pa_att_key={$pa['att_key']}，pd_def_key={$pd['def_key']}<br>";
		
		//直死 NPC打NPC 无效果

		//真红暮护盾/特效
		if(($pd['type']==19)&&($pd['name']=="红暮")&&(substr($pa['wepk'],0,2)!=$pd['wepk']))
		{
			$log .= "<span class=\"red\">红暮身上的武器投射出了防护罩，轻松挡下了{$pa['name']}的攻击！</span><br>";
			return 0;
		}

		//数据护盾 NPC打NPC
		if($pd['artk']=="AA")
		{ //主动攻击判定
			if($pd['type']!=0)
			{ //pd是NPC
				if($pd['arte'] < 100)
				{
					$log .= "<span class=\"red\">对手身上的数据护盾投射出了防护罩，轻松挡下了{$pa['name']}的攻击！</span><br>";
					$pd['arte'] = $pd['arte'] + $pd['arts'];
					if($pd['arte'] > 100){$pd['arte'] = 100;}
					return 0;
				}
				else
				{
					$log .= "<span class=\"red\">对手身上的数据护盾失效了！</span><br>";
				}
			}
		}
	
		//迷你蜂 - NPC打NPC TODO
	
		//电子狐 - NPC打NPC TODO
		
		//熟练度修正
		//$add_skill = &$pa[$skillinfo[$pa['wep_kind']]];
		//提醒自己一下：$skillinfo[$pa['wep_kind']]返回的是'wp','wk'...这种熟练名字段，对应熟练度值是$pa[$skillinfo[$pa['wep_kind']]]
		if ($pa['club']==18){
			$pa['wep_skill']=round($pa[$skillinfo[$pa['wep_kind']]]*0.7+($pa['wp']+$pa['wk']+$pa['wc']+$pa['wg']+$pa['wd']+$pa['wf'])*0.3);
		}else{
			$pa['wep_skill']=$pa[$skillinfo[$pa['wep_kind']]];
		}
		//三抽修正
		if($skdr)
		{
			$pa['wep_skill']=sqrt($pa['wep_skill']);
		}
		//空手武器效果值修正
		if ($watt==-1)
		{
			if ($pa['wep_kind'] == 'N') 
			{
				$watt = round ($pa['wep_skill']*2/3);	
			} 
			else
			 {
				$watt = $pa['wepe'] * 2;
			}
		}
		//echo "【DEBUG】pa_wep_kind={$pa['wep_kind']}，pa_wep_skill={$pa['wep_skill']},pa_skills={$pa['skills']}<br>";
		$hitrate = get_hitrate ($pa['wep_kind'],$pa['wep_skill'], $pa['club'], $pa['inf'] );
		//echo "【DEBUG】hitrate={$hitrate}<br>";
		$hitrate *= rev_get_clubskill_bonus_hitrate($pa['club'],$pa['skills'],$pa,$pd['club'],$pd['skills'],$pd);
		//echo "【DEBUG】修正后hitrate={$hitrate}<br>";
		$damage_p = get_damage_p ( $pa['rage'], $pa['att_key'], 0, $pa['name'] , $pa['club'], $message);
		//echo "【DEBUG】damage_p={$damage_p}<br>";

		//……咕咕？
		$clb_bonus_imfrate = rev_get_clubskill_bonus_imfrate($pa['club'],$pa['skills'],$pa,$pd['club'],$pd['skills'],$pd);
		$clb_bonus_imftime = rev_get_clubskill_bonus_imftime($pa['club'],$pa['skills'],$pa,$pd['club'],$pd['skills'],$pd);
		$clb_bonus_imprate = rev_get_clubskill_bonus_imprate($pa['club'],$pa['skills'],$pa,$pd['club'],$pd['skills'],$pd);
		$clb_bonus_hitrate = rev_get_clubskill_bonus_hitrate($pa['club'],$pa['skills'],$pa,$pd['club'],$pd['skills'],$pd);

		$hit_time = get_hit_time ($pa['att_key'],$pa['wep_skill'],$hitrate, $pa['wep_kind'], $pa['weps'],$infobbs[$pa['wep_kind']]*$clb_bonus_imfrate,$clb_bonus_imftime,$wepimprate[$pa['wep_kind']]*$clb_bonus_imprate,$is_wpg,$clb_bonus_hitrate);
		//echo "【DEBUG】看上面是输出的hit_time数组".print_r($hit_time)."<br>";

		if ($hit_time [1] > 0) 
		{
			if(strpos($pa['att_key'],'R')!==false)
			{
				//随机伤害无视一切伤害计算
				$maxdmg = $pd['mhp'] > $pa['wepe'] ? $pa['wepe'] : $pd['mhp'];
				$damage = rand(1,$maxdmg);
				$log .= "武器随机造成了<span class=\"red\">$damage</span>点伤害！<br>";
			}
			else
			{
				$gender_dmg_p = check_gender($pa['name'],$pd['name'],$pa['gd'],$pd['gd'],$pa['att_key']);
				if ($gender_dmg_p == 0) 
				{
					$damage = 1;
				} 
				else 
				{
					$attack = $pa['att'] + $watt;
					$defend = checkdef($pd['def'] , $pd['arbe'] + $pd['arhe'] + $pd['arae'] + $pd['arfe'] , $pa['att_key'], 1);
					$damage = rev_get_original_dmg ( $pa , $pd , $attack, $defend, $pa['wep_skill'] , $pa['wep_kind'] );
					//echo "【DEBUG】rev_get_original_dmg={$damage}<br>";
					if ($pa['wep_kind'] == 'F') 
					{
						if($sldr)
						{
							$log.="<span class=\"red\">由于灵魂抽取的作用，灵系武器伤害大幅降低了！</span><br>";
						}
						else
						{
							$damage = round (($pa['wepe']+$damage)*rev_get_WF_p($pa, $pa['club'],$pa['wepe']));
							//echo "【DEBUG】rev_get_WF_p修正后damage={$damage}<br>";
						}
					}
					if ($pa['wep_kind'] == 'J') 
					{
						$adddamage=$pd['mhp']/3;
						if ($adddamage>20000) {$adddamage=10000;}
						$damage += round($pa['wepe']*2/3+$adddamage);
					}
					checkarb ( $damage, $pa['wep_kind'], $pa['att_key'], $pd['def_key'] ,1);
					$damage *= $damage_p;
					$damage = $damage > 1 ? round ( $damage ) : 1;
					$damage *= $gender_dmg_p;
				}
				if ($pd['wepk']=='WJ')
				{
					$log.="<span class=\"red\">由于{$pd['name']}手中的武器过于笨重，受到的伤害大增！真是大快人心啊！</span><br>";
					$damage+=round($damage*0.5);
				}
				
				// 书中虫 TODO

				if ($hit_time [1] > 1) 
				{
					$d_temp = $damage;
					if ($hit_time [1] == 2) {
						$dmg_p = 2;
					} elseif ($hit_time [1] == 3) {
						$dmg_p = 2.8;
					} else {
						$dmg_p = 2.8 + 0.6 * ($hit_time [1] - 3);
					}
					//$dmg_p = $hit_time[1] - ($hit_time[1]-1)*0.2;
					$damage = round ( $damage * $dmg_p );
					$log .= "造成{$d_temp}×{$dmg_p}＝<span class=\"red\">$damage</span>点伤害！<br>";
				} else {
					$log .= "造成<span class=\"red\">$damage</span>点伤害！<br>";
				}

				$pdamage = $damage;
				$damage += rev_get_ex_dmg ($pa,$pd, 0, $pa['club'], $pd['inf'], $pa['att_key'], $pa['wep_kind'], $pa['wepe'], $pa['wep_skill'], $pd['def_key'] );
				$damage = checkdmgdef($damage, $pa['att_key'],$pd['def_key'],1);

				$bonus_dmg = get_clubskill_bonus_dmg_rate($pa['club'],$pa['skills'],$pd['club'],$pd['skills'])*100;

				if($bonus_dmg < 100)
				{
					$log.="<span class=\"yellow\">由于技能效果的作用，伤害下降至".$bonus_dmg."%！</span><br>";
					$damage = round($damage * $bonus_dmg / 100);
				}

				$rpdmg=get_clubskill_bonus_dmg_val($pa['club'],$pa['skills'],$pa['rp'],$pd['rp']);
				if($rpdmg > 0)
				{
					$log .= "<span class=\"yellow\">由于技能的影响，对方受到了<span class=\"red\">$rpdmg</span>点额外伤害。</span><br>";
					$damage += $rpdmg;
				}
				
				if($pdamage != $damage){
					$log .= "<span class=\"yellow\">造成的总伤害：<span class=\"red\">$damage</span>。</span><br>";
				}
			}
			
			checkdmg ( $pa['name'], $pd['name'], $damage );
			
			if(!$pa['type']) get_dmg_punish ( $pa['name'], $damage, $pa['hp'], $pa['att_key'] ); //npc不受反噬伤害
			
			rev_get_inf ($pd, $hit_time [2], $pa['wep_kind']);
			
			check_KP_wep ( $pa['name'], $hit_time [3], $pa['wep'], $pa['wepk'], $pa['wepe'], $pa['weps'], $pa['wepsk'] );
			
			$is_player_flag = $pa['type'] ? 0 : 1;
			exprgup ( $pa['lvl'], $pd['lvl'], $pa['exp'], $is_player_flag , $pd['rage'] );
		
		} else {
			$damage = 0;
			$log .= "但是没有击中！<br>";
		}

		//真蓝凝伏计
		if (($pd['type']==19)&&($pd['name']=='蓝凝'))
		{
			$ttr="♪臻蓝之愿♪";$ttr2="♫钴蓝之灵♫";$ttr3="❀矢车菊的回忆❀";
			if (rand(1,100)<5) $pa['rp']=rand(1,33);
			$le=rand(1,200)+$pa['mhp']-100;
			if ($le>1001) $le=1001;
			$w_pid = $pd['pid'];$n_rp = $pa['rp'];
			$db->query("INSERT INTO {$tablepre}maptrap (itm, itmk, itme, itms, itmsk, pls) VALUES ('$ttr', 'TO', '$le', '1', '$w_pid', '$n_rp')");
			$le=rand(1,200)+$damage-100;
			if ($le>2000) $le=2000;
			$db->query("INSERT INTO {$tablepre}maptrap (itm, itmk, itme, itms, itmsk, pls) VALUES ('$ttr2', 'TO', '$le', '1', '$w_pid', '$n_rp')");
			$le=rand(1,$pa['hp']);
			$db->query("INSERT INTO {$tablepre}maptrap (itm, itmk, itme, itms, itmsk, pls) VALUES ('$ttr3', 'TO', '$le', '1', '$w_pid', '$n_rp')");
			$log .= "从蓝凝的身边飞出了数个光球，散布在了战场上！<br>";
		}

		//echo "【DEBUG】武器耐久为：{$pa['weps']}<br>";
		check_GCDF_wep ( $pa['name'], $hit_time [0], $pa['wep'], $pa['wep_kind'], $pa['wepk'], $pa['wepe'], $pa['weps'], $pa['wepsk'] );
		//echo "【DEBUG】武器耐久变更为：{$pa['weps']}<br>";
		addnoise ( $pa['wep_kind'], $pa['wepsk'], $now, $pa['pls'], $pa['pid'], $pd['pid'], $pa['wep_kind'] );

		if($pa['club'] == 10)
		{
			//就这样了 不用引用了
			$pa[$skillinfo[$pa['wep_kind']]] +=2;
		}
		else
		{
			$pa[$skillinfo[$pa['wep_kind']]] +=1;
		}
	
		if ($pd['hp']<=$damage)
		{
			foreach (Array('wep','arb','arh','ara','arf','art') as $a) 
			{
				if(strpos($pd[$a.'sk'],'v')!==false)
				{
					$log .= "伴随着对方的死亡，对方的<span class=\"yellow\">{$pd[$a]}</span>也化作灰烬消散了。<br>";
					$pd[$a] = $pd[$a.'k'] = $pd[$a.'sk'];
					$pd[$a.'e'] = $pd[$a.'s'];
				}
			}
			for($i = 0;$i <= 6;$i++)
			{
				if(strpos($pd['itm'.$i.'sk'],'v')!==false)
				{
					$log .= "伴随着对方的死亡，对方的<span class=\"yellow\">{$pd['itm'.$i]}</span>也化作灰烬消散了。<br>";
					$pd['itm'.$i] = $pd['itm'.$i.'k'] = $pd['itm'.$i.'sk'];
					$pd['itm'.$i.'e'] = $pd['itm'.$i.'s'];
				}
			}
		}
		return $damage;
	}

	//战斗结算流程：pa(杀人方);pd(被杀方);active(1=pa视角;0=pd视角)
	function rev_combat_result(&$pa,&$pd,$active)
	{
		global $log;
		if($pd['hp']<= 0)
		{
			//复活判定
			if($pd['club'] == 99)
			{
				//NPC进化
				if ($pd['type']) 
				{
					$log .= npc_chat ($pd['type'],$pd['name'], 'death' );
					include_once GAME_ROOT . './include/system.func.php';
					$npcdata = evonpc ($pd['type'],$pd['name']);
					$log .= '<span class="yellow">'.$pd['name'].'却没死去，反而爆发出真正的实力！</span><br>';
					if($npcdata){
						addnews($now , 'evonpc',$pd['name'], $npcdata['name'], $pa['name']);
						foreach($npcdata as $key => $val)
						{
							$pd[$key] = $val;
						}
					}
				}
				//决死结界复活
				else
				{
					$killmsg = rev_kill($pa,$pd,$pa['wep_kind'],$pa['wep']);
					if($active) $log .= '<span class="yellow">'.$pd['name'].'由于其及时按了BOMB键而原地满血复活了！</span><br>';
					else $log .= '<span class="yellow">由于你及时按了BOMB键，你原地满血复活了！</span><br>';
				}
				return 1;
			}
			elseif($pd['hp'] <= 0)
			{
				$pd['bid'] = $pa['pid'];
				$pd['hp'] = 0;
				if (!$pd['type']) $pa['killnum'] ++;
				$killmsg = rev_kill($pa,$pd,$pa['wep_kind'],$pa['wep']);
				$log .= npc_chat ($pd['type'],$pd['name'], 'death' );
	
				if($active)
				{
					$log .= "<span class=\"red\">{$pd['name']}被你杀死了！</span><br>";
					if($killmsg) $log .= "<span class=\"yellow\">你对{$pd['name']}说：“{$killmsg}”</span><br>";
				}
				else 
				{
					$log .= "<span class=\"red\">你被{$pa['name']}杀死了！</span><br>";
					if($killmsg) $log .= "<span class=\"yellow\">{$pd['name']}对你说：“{$killmsg}”</span><br>";
				}
				
				//杀人rp结算
				if(!$pd['type'])
				{
					if($pd['rp'] < 80){
						$rpup = 80;
					}else{$rpup = $pd['rp'];}
				}
				else{$rpup = 20;}		
				//晶莹剔透修正
				if($pa['club'] == 19)
				{
					$rpdec = 30;
					$rpdec += get_clubskill_rp_dec($pa['club'],$pa['skills']);
					$pa['rp'] += round($rpup*(100-$rpdec)/100);
				}		
				else{
					$pa['rp'] += $rpup;
				}
				return 1;
			}
		}
		return 0;
	}

	//pa、pd格式的社团技能-命中率系数修正
	//这个函数能帮你找回10年前的记忆
	function rev_get_clubskill_bonus_hitrate($aclub,$askl,$pa,$bclub,$bskl,$pd)
	{
		//命中率系数
		getskills2($clskl);
		getlearnt($alearn,$aclub,$askl);
		getlearnt($blearn,$bclub,$bskl);
		$a1=((int)($askl/10))%10; $a2=$askl%10;
		$b1=((int)($bskl/10))%10; $b2=$bskl%10;
		$r=1;
		for ($i=1; $i<=2; $i++)
		{
			if ($alearn['learn'.$i]==3 && $pa['wep_kind']=="K")	//见敌必斩称号
			{
				$r*=(1+$clskl[3][${'a'.$i}][1]/100);
			}
			if ($alearn['learn'.$i]==5 && ($pa['wep_kind']=="G" || $pa['wep_kind']=="J"))	//狙击鹰眼称号
			{
				$r*=(1+$clskl[5][${'a'.$i}][1]/100);
			}
			if ($blearn['learn'.$i]==12)						//宛如疾风称号
			{
				$r*=(1-$clskl[12][${'b'.$i}][1]/100);
			}
		}
		return $r;
	}
	//时代变得很快，我们还没有跟上时代
	function rev_get_clubskill_bonus_imfrate($aclub,$askl,$pa,$bclub,$bskl,$pd)
	{
		//防具损坏率系数
		getskills2($clskl);
		getlearnt($alearn,$aclub,$askl);
		getlearnt($blearn,$bclub,$bskl);
		$a1=((int)($askl/10))%10; $a2=$askl%10;
		$b1=((int)($bskl/10))%10; $b2=$bskl%10;
		$r=1;
		for ($i=1; $i<=2; $i++)
		{
			if ($alearn['learn'.$i]==6 && ($pa['wep_kind']=="G" || $pa['wep_kind']=="J"))	//狙击鹰眼称号
			{
				$r*=(1+$clskl[6][${'a'.$i}][1]/100);
			}
		}
		return $r;
	}
	//也可能我们只是想活在过去
	function rev_get_clubskill_bonus_imftime($aclub,$askl,$pa,$bclub,$bskl,$pd)
	{
		//防具损坏效果系数
		getskills2($clskl);
		getlearnt($alearn,$aclub,$askl);
		getlearnt($blearn,$bclub,$bskl);
		$a1=((int)($askl/10))%10; $a2=$askl%10;
		$b1=((int)($bskl/10))%10; $b2=$bskl%10;
		$r=1;
		for ($i=1; $i<=2; $i++)
		{
			if ($alearn['learn'.$i]==6 && ($pa['wep_kind']=="G" || $pa['wep_kind']=="J"))	//狙击鹰眼称号
			{
				$r+=$clskl[6][${'a'.$i}][2];
			}
		}
		return $r;
	}
	//还是过去拽住了我们的腿不让我们向前走
	function rev_get_clubskill_bonus_imprate($aclub,$askl,$pa,$bclub,$bskl,$pd)
	{
		//武器损坏率系数
		getskills2($clskl);
		getlearnt($alearn,$aclub,$askl);
		getlearnt($blearn,$bclub,$bskl);
		$a1=((int)($askl/10))%10; $a2=$askl%10;
		$b1=((int)($bskl/10))%10; $b2=$bskl%10;
		$r=1;
		for ($i=1; $i<=2; $i++)
		{
			if ($alearn['learn'.$i]==4 && $pa['wep_kind']=="K")	//见敌必斩称号
			{
				$r*=(1-$clskl[4][${'a'.$i}][1]/100);
			}
		}
		return $r;
	}
	//用引用的方式修改一个变量，很像人和过去藕断丝连的关系。你以为你已经把过去的自己消化干净了，但ta仍会在某个毫无预兆的夜晚，出现在你的面前，让你辗转反侧、难以入眠。
	function rev_get_clubskill_bonus($aclub,$askl,$pa,$bclub,$bskl,$pd,&$att,&$def)
	{
		//攻击防御力加成
		getskills2($clskl);
		getlearnt($alearn,$aclub,$askl);
		getlearnt($blearn,$bclub,$bskl);
		$a1=((int)($askl/10))%10; $a2=$askl%10;
		$b1=((int)($bskl/10))%10; $b2=$bskl%10;
		$att=0; $def=0;
		for ($i=1; $i<=2; $i++)
		{
			if ($blearn['learn'.$i]==1 && $pd['wep_kind']=="P")	//铁拳无敌称号
			{
				$dup=$clskl[1][${'b'.$i}][1]/100*$pd['wepe'];
				if ($dup>2000) $dup=2000;
				$def+=$dup;
			}
		}
	}
	//复杂的东西是怎么变得复杂的：可能是因为我们把它想得太简单。当一个问题出现时，我们总以为ta会是最后一个出现的问题。
	function rev_get_clubskill_bonus_p($aclub,$askl,$pa,$bclub,$bskl,$pd,&$att,&$def)
	{
		//攻击防御加成系数
		getskills2($clskl);
		getlearnt($alearn,$aclub,$askl);
		getlearnt($blearn,$bclub,$bskl);
		$a1=((int)($askl/10))%10; $a2=$askl%10;
		$b1=((int)($bskl/10))%10; $b2=$bskl%10;
		$att=1; $def=1;
		for ($i=1; $i<=2; $i++)
		{
			if ($alearn['learn'.$i]==2 && $pa['wep_kind']=="P")	//铁拳无敌称号
			{
				$att*=(1+$clskl[2][${'a'.$i}][1]/100);
				if (rand(0,99)<$clskl[2][${'a'.$i}][2]) $def*=(1-$clskl[2][${'a'.$i}][3]/100);
			}
			if ($alearn['learn'.$i]==8 && $pa['wep_kind']=="C")	//灌篮高手称号
			{
				$att*=(1+$clskl[8][${'a'.$i}][1]/100);
			}
			if ($alearn['learn'.$i]==6 && ($pa['wep_kind']=="G" || $pa['wep_kind']=="J"))	//狙击鹰眼称号
			{
				if (rand(0,99)<$clskl[6][${'a'.$i}][3]) $att*=(1+$clskl[6][${'a'.$i}][4]/100);
			}
		}
	}
	//反而是我们以为很复杂的人，其实意外的更容易看懂。
	function rev_get_clubskill_bonus_fluc($aclub,$askl,$pa,$bclub,$bskl,$pd)
	{
		//伤害浮动值
		getskills2($clskl);
		getlearnt($alearn,$aclub,$askl);
		getlearnt($blearn,$bclub,$bskl);
		$a1=((int)($askl/10))%10; $a2=$askl%10;
		$b1=((int)($bskl/10))%10; $b2=$bskl%10;
		$r=0;
		for ($i=1; $i<=2; $i++)
		{
			if ($alearn['learn'.$i]==8 && ${$prefix1.'wepk'}=="WC")	//灌篮高手称号
			{
				$r+=$clskl[8][${'a'.$i}][2];
			}
		}
		return $r;
	}
	function rev_get_clubskill_bonus_counter($aclub,$askl,$pa,$bclub,$bskl,$pd)
	{
		//反击率加成
		getskills2($clskl);
		getlearnt($alearn,$aclub,$askl);
		getlearnt($blearn,$bclub,$bskl);
		$a1=((int)($askl/10))%10; $a2=$askl%10;
		$b1=((int)($bskl/10))%10; $b2=$bskl%10;
		$r=1;
		for ($i=1; $i<=2; $i++)
		{
			if ($alearn['learn'.$i]==7 && $pa['wep_kind']=='C')	//灌篮高手称号
			{
				$r*=(1+$clskl[7][${'a'.$i}][1]/100);
			}
			if ($alearn['learn'.$i]==11)						//宛如疾风称号
			{
				$r*=(1+$clskl[11][${'a'.$i}][3]/100);
			}
			if ($blearn['learn'.$i]==13 && $pd['wep_kind']=='F')	//超能力者称号
			{
				$r*=(1-$clskl[13][${'b'.$i}][2]/100);
			}
		}
		return $r;
	}	

	//pa、pd格式的原始伤害计算
	//其实想翻新一个函数，不一定需要看得懂它本身——也许只要看懂当时写它的人。
	function rev_get_original_dmg($pa, $pd, $att, $def, $ws, $wp_kind, $active=1) 
	{
		global $skill_dmg, $dmg_fluc, $weather, $pls;
		include_once GAME_ROOT.'./include/game/clubskills.func.php';
		rev_get_clubskill_bonus($pa['club'],$pa['skills'],$pa,$pd['club'],$pa['skills'],$pd,$att1,$def1);
		$att+=$att1; $def+=$def1;
		$attack_p = get_attack_p($weather,$pls, $pa['pose'], $pa['tactic'], $pa['club'], $pa['inf'], $active);
		$att_pow = $att * $attack_p;
		$defend_p = get_defend_p($weather,$pls, $pd['pose'], $pd['tactic'], $pd['club'], $pd['inf'], 1-$active);
		$def_pow = $def * $defend_p;
		rev_get_clubskill_bonus_p($pa['club'],$pa['skills'],$pa,$pd['club'],$pa['skills'],$pd,$attfac,$deffac);
		$att_pow *= $attfac;
		$def_pow *= $deffac;
		if($def_pow <= 0){$def_pow = 0.01;}
		$damage = ($att_pow / $def_pow) * $ws * $skill_dmg [$wp_kind];
		$dfluc = $dmg_fluc [$wp_kind];
		$dfluc += rev_get_clubskill_bonus_fluc($pa['club'],$pa['skills'],$pa,$pd['club'],$pa['skills'],$pd);
		$dmg_factor = (100 + rand ( - $dfluc, $dfluc )) / 100;
		$damage = round ( $damage * $dmg_factor * rand ( 4, 10 ) / 10 );
		return $damage;
	}
	function rev_get_WF_p($pa, $clb, $we) {
		global $log;
					
		if ($pa['type']) 
		{
			//你要找的是不是：NPC作弊
			$factor = 0.5;
		} 
		else 
		{
			$we = $we > 0 ? $we : 1;
			if ($clb == 9) {
				include_once GAME_ROOT.'./include/game/clubskills.func.php';
				$spd0 = round ( 0.2*get_clubskill_bonus_spd($clb,$pa['skills'])*$we);
			} else {
				$spd0 = round ( 0.25*$we);
			}
			if ($spd0 >= $pa['sp']) {
				$spd = $pa['sp'] - 1;
			} else {
				$spd = $spd0;
			}
			$factor = 0.5 + $spd / $spd0 / 2;
			$f = round ( 100 * $factor );
			$log .= "你消耗{$spd}点体力，发挥了灵力武器{$f}％的威力！";
			$pa['sp'] -= $spd;
		}
		return $factor;
	}
	function rev_get_ex_dmg($pa, $pd, $sd, $clb, &$inf, $ky, $wk, $we, $ws, $dky) 
	{
		if ($ky) 
		{
			global $log, $exdmgname, $exdmginf, $ex_attack,$specialrate,$now;
			global $ex_dmg_def, $ex_base_dmg,$ex_max_dmg, $ex_wep_dmg, $ex_skill_dmg, $ex_dmg_fluc, $ex_inf, $ex_inf_r, $ex_max_inf_r, $ex_skill_inf_r, $ex_inf_punish, $ex_good_wep, $ex_good_club;
			$ex_final_dmg = 0;
			$exinv = false;
			$ex_list = array();
			foreach ( $ex_attack as $ex_dmg_sign ) {
				if (strpos ( $ky, $ex_dmg_sign ) !== false){
					$ex_list[] = $ex_dmg_sign;
				}
			}
			if (strpos ( $dky, 'b' ) !== false && !empty($ex_list)){
				$dice = rand ( 0, 99);
				if ($dice < $specialrate['b']) {//几率4%
					$ex_final_dmg = 1;$exnum = 0;
					foreach ( $ex_attack as $ex_dmg_sign ) {
						if (strpos ( $ky, $ex_dmg_sign ) !== false) {
							$exnum ++;
						}
					}
					$log .= "<span class=\"red\">属性攻击的力量完全被防具吸收了！</span>只造成了<span class=\"red\">{$exnum}</span>点伤害！<br>";
					$exinv = true;
				}else{
					$log .= "纳尼？防具使属性攻击无效化的属性竟然失效了！<br>";
				}
			}
			if(!$exinv){
				foreach ( $ex_list as $ex_dmg_sign ) {
					$dmgnm = $exdmgname [$ex_dmg_sign];
					$def = $ex_dmg_def [$ex_dmg_sign];
					$bdmg = $ex_base_dmg [$ex_dmg_sign];
					$mdmg = $ex_max_dmg [$ex_dmg_sign];
					$wdmg = $ex_wep_dmg [$ex_dmg_sign];
					$sdmg = $ex_skill_dmg [$ex_dmg_sign];
					$fluc = $ex_dmg_fluc [$ex_dmg_sign];
					if (in_array($ex_dmg_sign,array_keys($ex_inf))) {
						$dmginf = $exdmginf [$ex_inf[$ex_dmg_sign]];
						$ex_inf_sign = $ex_inf [$ex_dmg_sign];
						$infr = $ex_inf_r [$ex_inf_sign];
						$minfr = $ex_max_inf_r [$ex_inf_sign];
						$sinfr = $ex_skill_inf_r [$ex_inf_sign];
						$punish = $ex_inf_punish [$ex_dmg_sign];
						$e_htr = $ex_good_club [$ex_inf_sign] == $clb ? 20 : 0;
					} else {
						$ex_inf_sign = '';
						$punish = 1;
						$e_htr = 0;
					}
					$wk_dmg_p = $ex_good_wep [$ex_dmg_sign] == $wk ? 2 : 1;
					$e_dmg = $bdmg + $we/$wdmg + $ws/$sdmg; 
					if(($mdmg>0)&&($wk!='H')){
						$e_dmg = round($wk_dmg_p*$mdmg*($e_dmg/($e_dmg+$mdmg/2))*rand(100 - $fluc, 100 + $fluc)/100);
					} else{
						$e_dmg =  round($wk_dmg_p*$e_dmg*rand(100 - $fluc, 100 + $fluc)/100);
					}
					$ex_def_dice = rand(0,99);
					if (strpos ( $dky, $def ) === false || $ex_def_dice > 90) {
						if(strpos ( $dky, $def ) !== false){
							$log .= "属性防御装备没能发挥应有的作用！";
						}
						if ($ex_inf_sign && strpos ( $inf, $ex_inf_sign ) !== false && $punish > 1) {
							$log .= "由于{$pd['name']}已经{$dmginf}，{$dmgnm}伤害倍增！";
							$e_dmg *= $punish;
						} elseif ($ex_inf_sign && strpos ( $inf, $ex_inf_sign ) !== false && $punish < 1) {
							$log .= "由于{$pd['name']}已经{$dmginf}，{$dmgnm}伤害减少！";
							$e_dmg *= $punish;
						} else {
							$e_htr += $infr + $ws * $sinfr;
							$e_htr = $e_htr > $minfr ? $minfr : $e_htr;
						}
						$e_dmg = round($e_dmg);
						$log .= "{$dmgnm}造成了<span class=\"red\">{$e_dmg}</span>点额外伤害！<br>";
						if (!empty($ex_inf_sign) && (strpos ( $inf, $ex_inf_sign ) === false)) {
							$dice = rand ( 0, 99 );
							if ($dice < $e_htr) {
								$inf .= $ex_inf_sign;
								if ($sd == 0) {
									$pd['combat_inf'] .= $ex_inf_sign;
								}
								$log .= "并造成{$pd['name']}{$dmginf}了！<br>";
								addnews($now,'inf',$pa['name'],$pd['name'],$ex_inf_sign);
							}
						}
					} else {
						$e_dmg = round ( $e_dmg / 2 );
						$log .= "{$dmgnm}被防御效果抵消了！造成了<span class=\"red\">{$e_dmg}</span>点额外伤害！<br>";
					}
					$ex_final_dmg += $e_dmg;
				}
			}
			return $ex_final_dmg;
		} else {
			return 0;
		}
	}
	function rev_get_inf($pd,$ht,$wp_kind) 
	{
		if ($ht > 0) {
			global $infatt,$log;
			$infatt_dice = rand ( 1, 4 );
			if (($infatt_dice == 1) && (strpos ( $infatt [$wp_kind], 'b' ) !== false)) {
				$inf_att = 'b';
			} elseif (($infatt_dice == 2) && (strpos ( $infatt [$wp_kind], 'h' ) !== false)) {
				$inf_att = 'h';
			} elseif (($infatt_dice == 3) && (strpos ( $infatt [$wp_kind], 'a' ) !== false)) {
				$inf_att = 'a';
			} elseif (($infatt_dice == 4) && (strpos ( $infatt [$wp_kind], 'f' ) !== false)) {
				$inf_att = 'f';
			}
			if ($inf_att) 
			{
				if ($pd['ar'.$inf_att.'s']) 
				{
					$pd['ar'.$inf_att.'s'] -= $ht;
					$log .= "{$pd['name']}的{$pd['ar'.$inf_att]}的耐久度下降了{$ht}！<br>";
					if ($pd['ar'.$inf_att.'s'] <= 0) 
					{
						$log .= "{$nm}的<span class=\"red\">{$pd['ar'.$inf_att.'s']}</span>受损过重，无法再装备了！<br>";
						$pd['ar'.$inf_att] = $pd['ar'.$inf_att.'k'] = $pd['ar'.$inf_att.'sk'] = '';
						$pd['ar'.$inf_att.'e'] = $pd['ar'.$inf_att.'s'] = 0;
					}
				} 
				else 
				{
					global $infinfo;
					if (strpos($pd['inf'],$inf_att) === false)
					{
						$pd['inf'] .= $inf_att;
						$pd['combat_inf'] .= $inf_att;
						$log .= "{$pd['name']}的<span class=\"red\">$infinfo[$inf_att]</span>部受伤了！<br>";				
					}
				}
			}
		}
		return;
	}

	function rev_kill(&$pa,&$pd,$death,$annex = '') 
	{
		global $now, $db, $tablepre, $alivenum, $deathnum, $typeinfo, $lwinfo;
		
		//echo "【DEBUG】检测到{$pa['name']}使用{$death}击杀了{$pd['name']}，凶器是{$annex}。<br>";

		//登记玩家狠话
		$killmsg = '';
		if(!$pa['type'])
		{
			$pname = $pa['name'];
			$result = $db->query("SELECT killmsg FROM {$tablepre}users WHERE username = '$pname'");
			$killmsg = $db->result($result,0);
		}
		
		//登记死法
		if ($death == 'N') {
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
		} elseif ($death == 'dn') {
			$pd['state'] = 28;
		} else {
			$pd['state'] = 10;
		}
		
		//死者是玩家 更新幸存者数
		if (!$pd['type']) $alivenum --;
		$deathnum ++;
		
		//发遗言
		$dtype = $pd['type']; $dname = $pd['name']; $dpls = $pd['pls'];
		if($dtype == 15)
		{	//静流AI
			global $gamevars;
			$gamevars['sanmadead'] = 1;
			save_gameinfo();
		}
		//死者是？
		$lwname = $typeinfo [$dtype] . ' ' . $dname;
		//遗言是？
		if($dtype)
		{
			$lastword = is_array($lwinfo[$dtype]) ? $lwinfo[$dtype][$dname] : $lwinfo[$dtype];
		}
		else 
		{
			$result = $db->query ( "SELECT lastword FROM {$tablepre}users WHERE username ='$dname'");
			$lastword = $db->result ( $result, 0 );
		}
		$db->query ( "INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('3','$now','$lwname','$dpls','$lastword')" );

		//发news
		$kname = $pa['type'] ? $pa['name'] : $pa['nick'].' '.$pa['name'];
		addnews ($now,'death'.$pd['state'],$dname,$dtype,$kname,$annex,$lastword );
		
		//玩家决死结界复活判定
		$revivaled = false;
		if (!$pd['type'] && $pd['club']==99 && ($death=="N" || $death=="P" || $death=="K" || $death=="G" || $death=="C" ||$death=="D" || $death=="F" || $death=="J" || $death=="trap"))	
		{
			addnews($now,'revival',$pd['name']);
			$pd['hp'] = $pd['mhp'];
			$pd['sp'] = $pd['msp'];
			$pd['club'] = 17;
			$pd['state'] = 0;
			$alivenum++;
		}
		save_gameinfo();
		return $killmsg;
	}

?>