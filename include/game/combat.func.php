<?php

if (! defined ( 'IN_GAME' )) {
	exit ( 'Access Denied' );
}

function combat($active = 1, $wep_kind = '') {
	global $log, $mode, $main, $cmd, $battle_title, $db, $tablepre, $pls, $message, $now, $w_log, $nosta, $hdamage, $hplayer;
	global $pid, $name, $club, $inf, $lvl, $exp, $killnum, $bid, $tactic, $pose, $hp,$mhp;
	global $wep, $wepk, $wepe, $weps, $wepsk;
	global $edata, $w_pid, $w_name, $w_pass, $w_type, $w_endtime,$w_deathtime, $w_gd, $w_sNo, $w_icon, $w_club, $w_hp, $w_mhp, $w_sp, $w_msp, $w_att, $w_def, $w_pls, $w_lvl, $w_exp, $w_money, $w_bid, $w_inf, $w_rage, $w_pose, $w_tactic, $w_killnum, $w_state, $w_wp, $w_wk, $w_wg, $w_wc, $w_wd, $w_wf, $w_teamID, $w_teamPass;
	global $w_wep, $w_wepk, $w_wepe, $w_weps, $w_arb, $w_arbk, $w_arbe, $w_arbs, $w_arh, $w_arhk, $w_arhe, $w_arhs, $w_ara, $w_arak, $w_arae, $w_aras, $w_arf, $w_arfk, $w_arfe, $w_arfs, $w_art, $w_artk, $w_arte, $w_arts, $w_itm0, $w_itmk0, $w_itme0, $w_itms0, $w_itm1, $w_itmk1, $w_itme1, $w_itms1, $w_itm2, $w_itmk2, $w_itme2, $w_itms2, $w_itm3, $w_itmk3, $w_itme3, $w_itms3, $w_itm4, $w_itmk4, $w_itme4, $w_itms4, $w_itm5, $w_itmk5, $w_itme5, $w_itms5,$w_itm6, $w_itmk6, $w_itme6, $w_itms6, $w_wepsk, $w_arbsk, $w_arhsk, $w_arask, $w_arfsk, $w_artsk, $w_itmsk0, $w_itmsk1, $w_itmsk2, $w_itmsk3, $w_itmsk4, $w_itmsk5, $w_itmsk6;
	global $infinfo, $w_combat_inf;
	global $rp,$w_rp,$action,$w_action,$achievement,$w_achievement,$skills,$w_skills,$skillpoint,$w_skillpoint;
	
	$battle_title = '战斗发生';
	
	if (! $wep_kind) {
		$w1 = substr ( $wepk, 1, 1 );
		$w2 = substr ( $wepk, 2, 1 );
		if ((($w1 == 'G')||($w1=='J')) && ($weps == $nosta)) {
			$wep_kind = $w2 ? $w2 : 'P';
		} else {
			$wep_kind = $w1;
		}
	} elseif (strpos($wepk,$wep_kind)===false && $wep_kind != 'back'){
		$wep_kind = substr ( $wepk, 1, 1 );
	}
	
	$wep_temp = $wep;
	
	include_once GAME_ROOT.'./include/game/clubskills.func.php';
	if ($active) {
		
		if ($wep_kind == 'back') {
			$log .= "你逃跑了。";
			$action = '';
			$mode = 'command';
			return;
		}
		$enemyid = $active ? str_replace('enemy','',$action) : $bid;
		if(!$enemyid || strpos($action,'enemy')===false){
			$log .= "<span class=\"yellow\">你没有遇到敌人，或已经离开战场！</span><br>";
			$action = '';
			$mode = 'command';
			return;
		}
		
		$result = $db->query ( "SELECT * FROM {$tablepre}players WHERE pid='$enemyid'" );
		if (! $db->num_rows ( $result )) {
			$log .= "对方不存在！<br>";
			$action = '';
			$mode = 'command';
			return;
		}
		
		$edata = $db->fetch_array ( $result );
		
		if ($edata ['pls'] != $pls) {
			$log .= "<span class=\"yellow\">" . $edata ['name'] . "</span>已经离开了<span class=\"yellow\">$plsinfo[$pls]</span>。<br>";
			$action = '';
			$mode = 'command';
			return;
		} elseif ($edata ['hp'] <= 0) {
			global $corpseprotect,$gamestate;
			$log .= "<span class=\"red\">" . $edata ['name'] . "</span>已经死亡，不能被攻击。<br>";
			if($edata['endtime'] < $now -$corpseprotect && $gamestate < 40){
				$action = 'corpse'.$edata['pid'];
				include_once GAME_ROOT . './include/game/battle.func.php';
				findcorpse ( $edata );
			}
			//$action = '';
			return;
		}
		
		if ($message) {
//			foreach ( Array('<','>',';',',') as $value ) {
//				if(strpos($message,$value)!==false){
//					$message = str_replace ( $value, '', $message );
//				}
//			}
			$log .= "<span class=\"lime\">你对{$edata ['name']}大喊：{$message}</span><br>";
			if (! $edata ['type']) {
				$w_log = "<span class=\"lime\">{$name}对你大喊：{$message}</span><br>";
				logsave ( $edata ['pid'], $now, $w_log ,'c');
			}
		}
		
		extract ( $edata, EXTR_PREFIX_ALL, 'w' );
		init_battle ( 1 );
		include_once GAME_ROOT . './include/game/attr.func.php';
		
		$log .= "你向<span class=\"red\">$w_name</span>发起了攻击！<br>";
		$att_dmg = attack ( $wep_kind, 1 );
		global $ggflag;
		if($ggflag){return;}
		
		$w_hp -= $att_dmg;
		
		if (($w_hp > 0) && ($w_tactic != 4) && ($w_pose != 5)) {
			global $rangeinfo;
			$w_w1 = substr ( $w_wepk, 1, 1 );
			$w_w2 = substr ( $w_wepk, 2, 1 );
			if ((($w_w1 == 'G')||($w_w1=='J')) && ($w_weps == $nosta)) {
				$w_wep_kind = $w_w2 ? $w_w2 : 'P';
			} else {
				$w_wep_kind = $w_w1;
			}
			//if (($rangeinfo [$wep_kind] == $rangeinfo [$w_wep_kind]) || ($rangeinfo [$w_wep_kind] == 'M')) {
			if ($rangeinfo [$wep_kind] <= $rangeinfo [$w_wep_kind] && $rangeinfo [$wep_kind] !== 0) {
				$counter = get_counter ( $w_wep_kind, $w_tactic, $w_club, $w_inf );
				
				$counter *= get_clubskill_bonus_counter($w_club,$w_skills,'w_',$club,$skills,'');
				$counter_dice = rand ( 0, 99 );
				if ($counter_dice < $counter) {
					$log .= "<span class=\"red\">{$w_name}的反击！</span><br>";
					
					$log .= npc_chat ( $w_type,$w_name, 'defend' );
					
					$def_dmg = defend ( $w_wep_kind );
				} else {
					
					$log .= npc_chat ( $w_type,$w_name, 'escape' );
					
					$log .= "<span class=\"red\">{$w_name}处于无法反击的状态，逃跑了！</span><br>";
				}
			} else {
				
				$log .= npc_chat ( $w_type,$w_name, 'cannot' );
				
				$log .= "<span class=\"red\">{$w_name}攻击范围不足，不能反击，逃跑了！</span><br>";
			}
		
		} elseif($w_hp > 0) {
			$log .= "<span class=\"red\">{$w_name}逃跑了！</span><br>";
		}
	} else {
		$result = $db->query ( "SELECT * FROM {$tablepre}players WHERE pid='$bid'" );
		$edata = $db->fetch_array ( $result );
		extract ( $edata, EXTR_PREFIX_ALL, 'w' );
		init_battle ( 1 );
		include_once GAME_ROOT . './include/game/attr.func.php';
		
		$log .= "<span class=\"red\">$w_name</span>突然向你袭来！<br>";
		
		
		$log .= npc_chat ( $w_type,$w_name, 'attack' );
		npc_changewep();
		
		$w_w1 = substr ( $w_wepk, 1, 1 );
		$w_w2 = substr ( $w_wepk, 2, 1 );
		if ((($w_w1 == 'G')||($w_w1=='J')) && ($w_weps == $nosta)) {
			$w_wep_kind = $w_w2 ? $w_w2 : 'P';
		} else {
			$w_wep_kind = $w_w1;
		}
		$def_dmg = defend ( $w_wep_kind, 1 );
		if (($hp > 0) && ($tactic != 4) && ($pose != 5)) {
			global $rangeinfo;
			if ($rangeinfo [$wep_kind] >= $rangeinfo [$w_wep_kind] && $rangeinfo [$w_wep_kind] !== 0) {
				$counter = get_counter ( $wep_kind, $tactic, $club, $inf );
				
				$counter *= get_clubskill_bonus_counter($club,$skills,'',$w_club,$w_skills,'w_');
				$counter_dice = rand ( 0, 99 );
				if ($counter_dice < $counter) {
					$log .= "<span class=\"red\">你的反击！</span><br>";
					$wep_kind = substr ( $wepk, 1, 1 );
					$att_dmg = attack ( $wep_kind );
					$w_hp -= $att_dmg;
				} else {
					$log .= "<span class=\"red\">你处于无法反击的状态，逃跑了！</span><br>";
				}
			} else {
				$log .= "<span class=\"red\">你攻击范围不足，不能反击，逃跑了！</span><br>";
			}
		} elseif($hp > 0) {
			$log .= "<span class=\"red\">你逃跑了！</span><br>";
		}
	}

	if($hp == 0 && !$w_action){$w_action = 'pacorpse'.$pid;}
	w_save ( $w_pid );
	$att_dmg = $att_dmg ? $att_dmg : 0;
	$def_dmg = $def_dmg ? $def_dmg : 0;
	
	if (! $w_type) {
		$w_inf_log = '';
		if ($w_combat_inf) {
			global $exdmginf;
			foreach ( $exdmginf as $inf_ky => $w_inf_words ) {
				if (strpos ( $w_combat_inf, $inf_ky ) !== false) {
					$w_inf_log .= "敌人的攻击造成你{$w_inf_words}了！<br>";
				}
			}

		}
		if($active){
			$w_log = "手持<span class=\"red\">$wep_temp</span>的<span class=\"yellow\">$name</span>向你袭击！<br>你受到其<span class=\"yellow\">$att_dmg</span>点攻击，对其做出了<span class=\"yellow\">$def_dmg</span>点反击。<br>$w_inf_log";
		}else{
			$w_log = "你发现了手持<span class=\"red\">$wep_temp</span>的<span class=\"yellow\">$name</span>并且先发制人！<br>你对其做出<span class=\"yellow\">$def_dmg</span>点攻击，受到其<span class=\"yellow\">$att_dmg</span>点反击。<br>$w_inf_log";
		}
		if($hp == 0){
			$w_log .= "<span class=\"yellow\">$name</span><span class=\"red\">被你杀死了！</span><br>";
			//include_once GAME_ROOT.'./include/game/achievement.func.php';
			//check_battle_achievement($w_achievement,$w_type,$name);
		}
		
		logsave ( $w_pid, $now, $w_log ,'b');
	}
	
	if (($att_dmg > $hdamage) && ($att_dmg >= $def_dmg)) {
		$hdamage = $att_dmg;
		$hplayer = $name;
		save_combatinfo ();
	} elseif (($def_dmg > $hdamage) && (! $w_type)) {
		$hdamage = $def_dmg;
		$hplayer = $w_name;
		save_combatinfo ();
	}
	
	//$bid = $w_pid;
	
	if ($w_hp <= 0 && $w_club != 99) {
		$w_bid = $pid;
		$w_hp = 0;
		if ($w_type==0){$killnum ++;};
		
		include_once GAME_ROOT . './include/state.func.php';
		$killmsg = kill ( $wep_kind, $w_name, $w_type, $w_pid, $wep_temp );
		$log .= npc_chat ( $w_type,$w_name, 'death' );
		
		include_once GAME_ROOT.'./include/game/achievement.func.php';
		check_battle_achievement($name,$w_type,$w_name,$wep_temp);
			
		$log .= "<span class=\"red\">{$w_name}被你杀死了！</span><br>";
		//$rp = $rp + 20 ;
		
		if(!$w_type){$rpup = $w_rp;}
		else{$rpup = 20;}		
		if($club == 19){
			$rpdec = 30;
			$rpdec += get_clubskill_rp_dec($club,$skills);
			$rp += round($rpup*(100-$rpdec)/100);
		}		
		else{
			$rp += $rpup;
		}
		
		if($killmsg){$log .= "<span class=\"yellow\">你对{$w_name}说：“{$killmsg}”</span><br>";}
		include_once GAME_ROOT . './include/game/battle.func.php';
		$result = $db->query ( "SELECT * FROM {$tablepre}players WHERE pid='$w_pid'" );
		$cdata = $db->fetch_array ( $result );
		$action = 'corpse'.$edata['pid'];
		findcorpse ( $cdata );
		return;
	} else {
		if($w_hp <= 0){//有第二阶段
			if ($w_type) 
			{
				$log .= npc_chat ( $w_type,$w_name, 'death' );
				include_once GAME_ROOT . './include/system.func.php';
				$npcdata = evonpc ($w_type,$w_name);
				$log .= '<span class="yellow">'.$w_name.'却没死去，反而爆发出真正的实力！</span><br>';
				if($npcdata){
					addnews($now , 'evonpc',$w_name, $npcdata['name'], $name);
					foreach($npcdata as $key => $val){
						${'w_'.$key} = $val;
					}
				}
			}
			else
			{
				include_once GAME_ROOT . './include/state.func.php';
				$killmsg = kill ( $wep_kind, $w_name, $w_type, $w_pid, $wep_temp );
				$log .= '<span class="yellow">'.$w_name.'由于其及时按了BOMB键而原地满血复活了！</span><br>';
			}	
		}
		$main = 'battle';
		init_battle ( 1 );
		
		if (CURSCRIPT !== 'botservice')
		{
			include template('battleresult');
			//$cmd = '<br><br><input type="hidden" name="mode" value="command"><input type="radio" name="command" id="back" value="back" checked><a onclick=sl("back"); href="javascript:void(0);" >确定</a><br>';
			$cmd = ob_get_contents();
			ob_clean();
			//$bid = $hp <= 0 ? $bid : 0;
		}
		$action = '';
		return;
	}
}

function attack($wep_kind = 'N', $active = 0) {
	global $now, $nosta, $log, $infobbs, $infinfo, $attinfo, $skillinfo,  $wepimprate,$specialrate;
	global $name, $lvl, $gd, $pid, $pls, $hp, $sp, $rage, $exp, $club, $att, $inf, $message,$w_mhp;
	global $wep, $wepk, $wepe, $weps, $wepsk;
	global $w_arbe, $w_arbsk, $w_arhe, $w_arae, $w_arfe,$w_wepk;
	global $artk, $arhsk, $arbsk, $arask, $arfsk, $artsk;
	global $w_hp, $w_rage, $w_lvl, $w_pid, $w_gd, $w_name, $w_type, $w_inf, $w_def;
	global $w_wepsk, $w_arhsk, $w_arask, $w_arfsk, $w_artsk, $w_artk;
	global $wp,$wk,$wc,$wg,$wd,$wf,$skills,$w_skills,$w_club,$skillpoint,$w_skillpoint,$rp,$w_rp;
	include_once GAME_ROOT.'./include/game/clubskills.func.php';
	//npc_changewep();
	$is_wpg = false;
	$watt=-1;
	if (((strpos ( $wepk, 'G' ) == 1)||(strpos($wepk,'J')==1)) && ($weps == $nosta)) {
		if (($wep_kind == 'G') || ($wep_kind == 'P')||($wep_kind=='J')) {
			$wep_kind = 'P';
			$is_wpg = true;
			$watt = round ( $wepe / 5 );
		} else {
			$watt = $wepe;
		}
	}
	
	$log .= "使用{$wep}<span class=\"yellow\">$attinfo[$wep_kind]</span>{$w_name}！<br>";
	
	$att_key = getatkkey ( $wepsk, $arhsk, $arbsk, $arask, $arfsk, $artsk, $artk, $is_wpg );
	$w_def_key = getdefkey ( $w_wepsk, $w_arhsk, $w_arbsk, $w_arask, $w_arfsk, $w_artsk, $w_artk );
	$mdr = $skdr = $sldr = false;
	if(strpos($att_key.$w_def_key,'-')!==false){$mdr = true;}//精抽
	if(strpos($att_key.$w_def_key,'*')!==false){$sldr = true;}//魂抽
	if(strpos($att_key.$w_def_key,'+')!==false){$skdr = true;}//技抽
	
	if($mdr || $sldr || $skdr){
		list($wsk,$hsk,$bsk,$ask,$fsk,$tsk,$tk)=Array($wepsk, $arhsk, $arbsk, $arask, $arfsk, $artsk, $artk);
		list($wwsk,$whsk,$wbsk,$wask,$wfsk,$wtsk,$wtk)=Array( $w_wepsk, $w_arhsk, $w_arbsk, $w_arask, $w_arfsk, $w_artsk, $w_artk);
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
			//$bsk = $ask = $fsk = $wbsk = $wask = $wfsk = '';
		}
		$att_key = getatkkey ( $wsk,$hsk,$bsk,$ask,$fsk,$tsk,$tk, $is_wpg );
		$w_def_key = getdefkey ( $wwsk,$whsk,$wbsk,$wask,$wfsk,$wtsk,$wtk );
	}
	
	
	//判定直死
	if(strpos($att_key,'X')!==false){
		global $ggflag;
		$ggflag = false;
		$ddice = rand(0,99);
		if($ddice <=14){
			$log .= "<span class=\"red\">你手中的武器忽然失去了控制，喀吧一声就斩断了什么。你发现那似乎是你的死线。</span><br>";
			include_once GAME_ROOT . './include/state.func.php';
			death('gg','','',$wep);
			$ggflag = true;
			return 0;
		}
	}
		
	//attack函数是玩家打npc专用，在这里加npc内容是没用的
	
//	if ((strpos($att_key,"X")!==false)&&($type)&&(!$w_type)&&(rand(1,5)>3)){  
//		if ($wep=='燕返262'){
//			$log.="<img src=\"img/other/262.png\"><br>";
//		}
//		$damage=$w_mhp;
//		$log .= "造成<span class=\"red\">$damage</span>点伤害！<br>";
//		checkdmg ( $name, $w_name, $damage );
//		return $damage;
//	}
	
	global ${$skillinfo [$wep_kind]};
	$add_skill = & ${$skillinfo [$wep_kind]};
	if ($club==18){
		$wep_skill=round(${$skillinfo [$wep_kind]}*0.7+($wp+$wk+$wc+$wg+$wd+$wf)*0.3);
	}else{
		$wep_skill=${$skillinfo [$wep_kind]};
	}
	if($skdr){
		$wep_skill=sqrt($wep_skill);
	}
	if ($watt==-1){
		if ($wep_kind == 'N') {
			$watt =  round ($wep_skill*2/3);	
		} else {
			$watt = $wepe * 2;
		}
	}
	
	$hitrate = get_hitrate ( $wep_kind, $wep_skill, $club, $inf );
	
	
	$hitrate *= get_clubskill_bonus_hitrate($club,$skills,'',$w_club,$w_skills,'w_');
	
	$damage_p = get_damage_p ( $rage, $att_key, 0, '你' , $club, $message);
	$hit_time = get_hit_time ( $att_key, $wep_skill, $hitrate, $wep_kind, $weps, $infobbs [$wep_kind] * get_clubskill_bonus_imfrate($club,$skills,'',$w_club,$w_skills,'w_'), get_clubskill_bonus_imftime($club,$skills,'',$w_club,$w_skills,'w_'), $wepimprate [$wep_kind] * get_clubskill_bonus_imprate($club,$skills,'',$w_club,$w_skills,'w_'), $is_wpg, get_clubskill_bonus_hitrate($club,$skills,'',$w_club,$w_skills,'w_'));
	if ($hit_time [1] > 0) {
		if(strpos($att_key,'R')!==false){//随机伤害无视一切伤害计算
			$maxdmg = $w_mhp > $wepe ? $wepe : $w_mhp;
			$damage = rand(1,$maxdmg);
			$log .= "武器随机造成了<span class=\"red\">$damage</span>点伤害！<br>";
		}else{
			$gender_dmg_p = check_gender ( '你', $w_name, $gd, $w_gd, $att_key );
			if ($gender_dmg_p == 0) {
				$damage = 1;
			} else {
				$w_active = 1 - $active;
				$attack = $att + $watt;
				$defend = checkdef($w_def , $w_arbe + $w_arhe + $w_arae + $w_arfe , $att_key, 1);
				
				
				
				$damage = get_original_dmg ( '', 'w_', $attack, $defend, $wep_skill, $wep_kind );
				
				if ($wep_kind == 'F') {
					if($sldr){
						$log.="<span class=\"red\">由于灵魂抽取的作用，灵系武器伤害大幅降低了！</span><br>";
					}else{
						$damage = round ( ($wepe + $damage) * get_WF_p ( '', $club, $wepe) ); //get_spell_factor ( 0, $club, $att_key, $sp, $wepe ) );
					}
					
				}
				if ($wep_kind == 'J') {
					$adddamage=$w_mhp/3;
					if ($adddamage>20000) {$adddamage=10000;}
					$damage += round($wepe*2/3+$adddamage);
				}
				checkarb ( $damage, $wep_kind, $att_key, $w_def_key ,1);
				$damage *= $damage_p;
				
				$damage = $damage > 1 ? round ( $damage ) : 1;
				$damage *= $gender_dmg_p;
			}
			if ($w_wepk=='WJ'){
				$log.="<span class=\"red\">由于{$w_name}手中的武器过于笨重，受到的伤害大增！真是大快人心啊！</span><br>";
				$damage+=round($damage*0.5);
			}
			
			
			
			if ($hit_time [1] > 1) {
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
			$damage += get_ex_dmg ( $w_name, 0, $club, $w_inf, $att_key, $wep_kind, $wepe, $wep_skill, $w_def_key );
			$damage = checkdmgdef($damage, $att_key,$w_def_key,1);
			//好人卡特别活动
			if($w_type == 0){
				$gm = ceil(count_good_man_card(0)*rand(80,120)/100);
				if($gm){
					$log .= "在{$w_name}身上的<span class=\"yellow\">好人卡</span>的作用下，{$w_name}受到的伤害增加了<span class=\"red\">$gm</span>点！<br>";
					$damage += $gm;
				}
			}
			$bonus_dmg = get_clubskill_bonus_dmg_rate($club,$skills,$w_club,$w_skills)*100;
			if($bonus_dmg < 100){
				$log.="<span class=\"yellow\">由于技能效果的作用，伤害下降至".$bonus_dmg."%！</span><br>";
				$damage = round($damage * $bonus_dmg / 100);
			}
			$rpdmg=get_clubskill_bonus_dmg_val($club,$skills,$rp,$w_rp);
			if($rpdmg > 0){
				$log .= "<span class=\"yellow\">由于技能的影响，对方受到了<span class=\"red\">$rpdmg</span>点额外伤害。</span><br>";
				$damage += $rpdmg;
			}
			
			if($pdamage != $damage){
				$log .= "<span class=\"yellow\">造成的总伤害：<span class=\"red\">$damage</span>。</span><br>";
			}
		}
		
		checkdmg ( $name, $w_name, $damage );
		
		get_dmg_punish ( '你', $damage, $hp, $att_key );
		
		get_inf ( $w_name, $hit_time [2], $wep_kind);
		
		check_KP_wep ( '你', $hit_time [3], $wep, $wepk, $wepe, $weps, $wepsk );
		
		exprgup ( $lvl, $w_lvl, $exp, 1, $w_rage );
	
	} else {
		$damage = 0;
		$log .= "但是没有击中！<br>";
	}
	check_GCDF_wep ( '你', $hit_time [0], $wep, $wep_kind, $wepk, $wepe, $weps, $wepsk );
	
	addnoise ( $wep_kind, $wepsk, $now, $pls, $pid, $w_pid, $wep_kind );
	if($club == 10){
		$add_skill +=2;
	}else{
		$add_skill +=1;
	}

	//PORT
	if ($w_hp<=$damage){
		global $w_wep, $w_wepk, $w_wepsk, $w_weps, $w_wepe, $w_arb, $w_arh, $w_ara, $w_arf, $w_art;
		global $w_itm0, $w_itmk0, $w_itme0, $w_itms0, $w_itm1, $w_itmk1, $w_itme1, $w_itms1, $w_itm2, $w_itmk2, $w_itme2, $w_itms2, $w_itm3, $w_itmk3, $w_itme3, $w_itms3, $w_itm4, $w_itmk4, $w_itme4, $w_itms4, $w_itm5, $w_itmk5, $w_itme5, $w_itms5,$w_itm6, $w_itmk6, $w_itme6, $w_itms6, $w_wepsk, $w_arbsk, $w_arhsk, $w_arask, $w_arfsk, $w_artsk, $w_itmsk0, $w_itmsk1, $w_itmsk2, $w_itmsk3, $w_itmsk4, $w_itmsk5, $w_itmsk6;
		for($i = 1;$i <= 6;$i++){
			if(strpos(${'w_itmsk'.$i},'v')!==false){
			$log .= "伴随着对方的死亡，对方的<span class=\"yellow\">${'w_itm'.$i}</span>也化作灰烬消散了。<br>";
			${'w_itm'.$i} = ${'w_itmk'.$i} = ${'w_itmsk'.$i} = '';
			${'w_itme'.$i} = ${'w_itms'.$i} = 0;
			}
			if(strpos($w_wepsk,'v')!==false){
			$log .= "伴随着对方的死亡，对方的<span class=\"yellow\">{$w_wep}</span>也化作灰烬消散了。<br>";
			$w_wep = '拳头' ; $w_wepk = 'WN' ; $w_wepsk ='';
			$w_weps = '∞' ; $w_wepe = 0;
			}
			if(strpos($w_arbsk,'v')!==false){
			$log .= "伴随着对方的死亡，对方的<span class=\"yellow\">{$w_arb}</span>也化作灰烬消散了。<br>";
			$w_arb='内衣';$w_arbk ='DN';$w_arbsk ='';
			$w_arbs='∞';$w_arbe = 0;
			}
			if(strpos($w_arhsk,'v')!==false){
			$log .= "伴随着对方的死亡，对方的<span class=\"yellow\">{$w_arh}</span>也化作灰烬消散了。<br>";
			$w_arh=$w_arhk=$w_arhsk ='';
			$w_arhs=$w_arhe = 0;
			}
			if(strpos($w_arask,'v')!==false){
			$log .= "伴随着对方的死亡，对方的<span class=\"yellow\">{$w_ara}</span>也化作灰烬消散了。<br>";
			$w_ara=$w_arak=$w_arask ='';
			$w_aras=$w_arae = 0;
			}
			if(strpos($w_arfsk,'v')!==false){
			$log .= "伴随着对方的死亡，对方的<span class=\"yellow\">{$w_arf}</span>也化作灰烬消散了。<br>";
			$w_arf=$w_arfk=$w_arfsk ='';
			$w_arfs=$w_arfe = 0;
			}
			if(strpos($w_artsk,'v')!==false){
			$log .= "伴随着对方的死亡，对方的<span class=\"yellow\">{$w_art}</span>也化作灰烬消散了。<br>";
			$w_art = $w_artk = $w_artsk ='';
			$w_arts=$w_arte = 0;
			}
		}
//		if((strpos($wepsk,'|')!==false)&&($w_lvl>=10)){
//			$wepe+=round($w_lvl-4);
//		}
//		if((strpos($wepsk,'=')!==false)&&(rand(1,100)<=25)){
//			$hp=max($hp,$mhp);$sp=max($sp,$msp);
//		}
	}
	return $damage;
}

function defend($w_wep_kind = 'N', $active = 0) {
	global $now, $nosta, $log, $infobbs, $infinfo, $attinfo, $skillinfo,  $wepimprate,$specialrate;
	global $w_name, $w_lvl, $w_gd, $w_pid, $pls, $w_hp, $w_sp, $w_rage, $w_exp, $w_club, $w_att, $w_inf;
	global $w_wep, $w_wepk, $w_wepe, $w_weps, $w_wepsk;
	global $arbe, $arbsk, $arhe, $arae, $arfe,$wepk;
	global $w_artk, $w_arhsk, $w_arbsk, $w_arask, $w_arfsk, $w_artsk;
	global $hp, $rage, $lvl, $pid, $gd, $name, $inf, $att, $def, $club;
	global $wepsk, $arhsk, $arask, $arfsk, $artsk, $artk;
	global $w_type, $w_sNo, $w_killnum,$mhp;
	global $w_wp,$w_wk,$w_wc,$w_wg,$w_wf,$w_wd,$w_skills,$skills,$skillpoint,$w_skillpoint,$w_rp;
	include_once GAME_ROOT.'./include/game/clubskills.func.php';
	//npc_changewep();
	$watt=-1;
	$w_wep_temp = $w_wep;
	$is_wpg = false;
	if (((strpos ( $w_wepk, 'G' ) == 1)||(strpos($w_wepk,'J')==1)) && ($w_wep_kind == 'P')) {
		$watt = round ( $w_wepe / 5 );
		$is_wpg = true;
	} 

	$x_temp_log=$log;	//这是为了NPC放技能作弊…… 所以在return之前一！定！要！记得写$log=$x_temp_log.$log！
	$log='';

	$log .= "{$w_name}使用{$w_wep}<span class=\"yellow\">$attinfo[$w_wep_kind]</span>你！<br>";
	
	$w_att_key = getatkkey ( $w_wepsk, $w_arhsk, $w_arbsk, $w_arask, $w_arfsk, $w_artsk, $w_artk, $is_wpg );
	$def_key = getdefkey ( $wepsk, $arhsk, $arbsk, $arask, $arfsk, $artsk, $artk );
	$mdr = $skdr = $sldr = false;
	if(strpos($w_att_key.$def_key,'-')!==false){$mdr = true;}//精抽
	if(strpos($w_att_key.$def_key,'*')!==false){$sldr = true;}//魂抽
	if(strpos($w_att_key.$def_key,'+')!==false){$skdr = true;}//技抽
	if($mdr || $sldr || $skdr){
		list($wsk,$hsk,$bsk,$ask,$fsk,$tsk,$tk)=Array($wepsk, $arhsk, $arbsk, $arask, $arfsk, $artsk, $artk);
		list($wwsk,$whsk,$wbsk,$wask,$wfsk,$wtsk,$wtk)=Array( $w_wepsk, $w_arhsk, $w_arbsk, $w_arask, $w_arfsk, $w_artsk, $w_artk);
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
			//$bsk = $ask = $fsk = $wbsk = $wask = $wfsk = '';
		}
		$w_att_key = getatkkey ( $wwsk,$whsk,$wbsk,$wask,$wfsk,$wtsk,$wtk, $is_wpg );
		$def_key = getdefkey ( $wsk,$hsk,$bsk,$ask,$fsk,$tsk,$tk );
	}
	
	//开始搞事！	
	global $arb, $arbk, $arbe, $arbs;
	global $arh, $arhk, $arhe, $arhs;
	global $ara, $arak, $arae, $aras;
	global $arf, $arfk, $arfe, $arfs;
	global $art, $artk, $arte, $arts;
	global $wep,$wepk,$wepe,$weps,$wepsk;
	global $itmk0, $itme0, $itms0, $itmsk0;
	global $itm1, $itmk1, $itme1, $itms1, $itmsk1;
	global $itm2, $itmk2, $itme2, $itms2, $itmsk2;
	global $itm3, $itmk3, $itme3, $itms3, $itmsk3;
	global $itm4, $itmk4, $itme4, $itms4, $itmsk4;
	global $itm5, $itmk5, $itme5, $itms5, $itmsk5;
	global $itm6, $itmk6, $itme6, $itms6, $itmsk6;
	global $w_itm0, $w_itmk0, $w_itme0, $w_itms0, $w_itm1, $w_itmk1, $w_itme1, $w_itms1, $w_itm2, $w_itmk2, $w_itme2, $w_itms2, $w_itm3, $w_itmk3, $w_itme3, $w_itms3, $w_itm4, $w_itmk4, $w_itme4, $w_itms4, $w_itm5, $w_itmk5, $w_itme5, $w_itms5,$w_itm6, $w_itmk6, $w_itme6, $w_itms6, $w_wepsk, $w_arbsk, $w_arhsk, $w_arask, $w_arfsk, $w_artsk, $w_itmsk0, $w_itmsk1, $w_itmsk2, $w_itmsk3, $w_itmsk4, $w_itmsk5, $w_itmsk6;
	global $money,$exp;
	//global $rp;
	
	//正式上线时修改NPC名称！
	if (($w_type==89)&&($w_name=='电子狐')){ // 电子狐
		$log .= "<span class=\"yellow\">【电子狐】的双眼突然闪耀了起来！</span><br>
		<span class=\"neonblue\">“侦测到敌意实体，开始扫描~”</span><br>";
		$dice = rand(1,1024);
		$log .= "<span class=\"yellow\">【DEBUG】骰子检定结果：<span class=\"red\">$dice</span>/1024。</span><br>";
		if($dice<=127){ //8%
			$log .= "<span class=\"yellow\">“似乎【电子狐】具现化了你的武器！”</span><br>
			<span class=\"neonblue\">“你的<span class=\"red\">$wep</span>，我就收下了！”</span><br>";
			$w_wep = $wep;
			$w_wepk = $wepk;
			$w_wepe = $wepe;
			$w_weps = $weps;
			$w_wepsk = $wepsk;
		}elseif($dice<=635){
			$dice2 = rand(1,5);
			$log .= "<span class=\"yellow\">“似乎【电子狐】扫描了你的武器！”</span><br>
			<span class=\"neonblue\">“你的<span class=\"red\">$wep</span>，已扫描入<span class=\"red\">$dice2</span>号位。”<br>
			“我会妥善保管的~”</span><br>";
			if($dice2 == 1){
				$w_itm1 = $wep;
				$w_itmk1 = $wepk;
				$w_itme1 = $wepe;
				$w_itms1 = $weps;
				$w_itmsk1 = $wepsk;
			}elseif($dice2 == 2){
				$w_itm2 = $wep;
				$w_itmk2 = $wepk;
				$w_itme2 = $wepe;
				$w_itms2 = $weps;
				$w_itmsk2 = $wepsk;
			}elseif($dice2 == 3){
				$w_itm3 = $wep;
				$w_itmk3 = $wepk;
				$w_itme3 = $wepe;
				$w_itms3 = $weps;
				$w_itmsk3 = $wepsk;
			}elseif($dice2 == 4){
				$w_itm4 = $wep;
				$w_itmk4 = $wepk;
				$w_itme4 = $wepe;
				$w_itms4 = $weps;
				$w_itmsk4 = $wepsk;
			}elseif($dice2 == 5){
				$w_itm5 = $wep;
				$w_itmk5 = $wepk;
				$w_itme5 = $wepe;
				$w_itms5 = $weps;
				$w_itmsk5 = $wepsk;
			}
		}elseif($dice>=1024){ // 1/1024 几率直接抢夺玩家全部背包
			$log .= "<span class=\"yellow\">哎呀，骰子检定结果是大·失·败！</span><br>";
			$log .= "<span class=\"yellow\">“【电子狐】将你的全身扫描了个遍！”</span><br>
			<span class=\"neonblue\">“我判定你身上的东西放到我身上可能更好一点~”<br>
			“我会妥善保管的~”</span><br>";
			$w_itm1 = $itm1;
			$w_itmk1 = $itmk1;
			$w_itme1 = $itme1;
			$w_itms1 = $itms1;
			$w_itmsk1 = $itmsk1;
			$w_itm2 = $itm2;
			$w_itmk2 = $itmk2;
			$w_itme2 = $itme2;
			$w_itms2 = $itms2;
			$w_itmsk2 = $itmsk2;
			$w_itm3 = $itm3;
			$w_itmk3 = $itmk3;
			$w_itme3 = $itme3;
			$w_itms3 = $itms3;
			$w_itmsk3 = $itmsk3;
			$w_itm4 = $itm4;
			$w_itmk4 = $itmk4;
			$w_itme4 = $itme4;
			$w_itms4 = $itms4;
			$w_itmsk4 = $itmsk4;
			$w_itm5 = $itm5;
			$w_itmk5 = $itmk5;
			$w_itme5 = $itme5;
			$w_itms5 = $itms5;
			$w_itmsk5 = $itmsk5;
			//哎哟喂啊，真是倒霉，但这就是人生啊。
			$itm1 = ''; $itmk1 = ''; $itme1 = 0; $itms1 = 0; $w_itmsk1 = '';
			$itm2 = ''; $itmk2 = ''; $itme2 = 0; $itms2 = 0; $w_itmsk2 = '';
			$itm3 = ''; $itmk3 = ''; $itme3 = 0; $itms3 = 0; $w_itmsk3 = '';
			$itm4 = ''; $itmk4 = ''; $itme4 = 0; $itms4 = 0; $w_itmsk4 = '';
			$itm5 = ''; $itmk5 = ''; $itme5 = 0; $itms5 = 0; $w_itmsk5 = '';
		}else{
			$log .= "<span class=\"yellow\">“不过似乎什么都没发生！”</span><br>
			<span class=\"neonblue\">“扫描失败了么……”</span><br>";
		}

		
	}

	if (($w_type==89)&&($w_name=='百命猫')){ // 百命猫
		//并非战斗机制，所以毫无反应，就是个白板，但每次等级和怒气都会上升。
		if($w_lvl < 255){
			$w_lvl++;
			$w_rage++;
		}
	}

	if (($w_type==89)&&($w_name=='笼中鸟')){ // 笼中鸟
		global $rp;
		global $w_mhp, $w_msp;
		//70%几率吸收玩家HP值成为自己的HP和SP值，SP值上升到一定程度时变身，变身后各种数值直接膨胀。三段变身。
		$log .= "<span class=\"yellow\">“【笼中鸟】含情脉脉地看着你！”</span><br>";
		$dice=rand(1,20);
		$log .= "<span class=\"yellow\">【DEBUG】骰子检定结果：<span class=\"red\">$dice</span>。</span><br>";
		if($dice>=14){
			$log .= "<span class=\"yellow\">“你感觉你的生命被她汲取，但同时更有一种奇怪的暖洋洋的舒畅感。”</span><br>";
			//继续投d20，1~10吸收30%，11~19吸收65%，大失败直接吸到1。
			$dice2=rand(1,20);
			$log .= "<span class=\"yellow\">【DEBUG】骰子2检定结果：<span class=\"red\">$dice2</span>。</span><br>";
			if($dice2<=10){
				$log .= "<span class=\"yellow\">“你稍微稳了稳身形，似乎问题不是很严重。”</span><br>";
				$gain = $hp * 0.3;
			}elseif($dice2<=19){
				$log .= "<span class=\"yellow\">“你觉得头晕目眩。”</span><br>";
				$gain = $hp * 0.65;
			}elseif($dice2>=20){
				$log .= "<span class=\"yellow\">哎呀，骰子检定结果是大·失·败！</span><br>";
				//哎哟喂啊，真是倒霉，但这就是人生啊。
				$log .= "<span class=\"yellow\">“你整个人都倒了下去，不过想到你的生命力将要打开她的镣铐，这让你充满了决心。”</span><br>";
				$gain = $hp - 1;
				$def = $def + ($gain * 0.25);
			}
		$w_hp = $w_hp + ($gain * 30);
		$w_mhp = $w_mhp + ($gain * 30);
		$w_msp = $w_msp + ($gain * 30);
		$hp = $hp - $gain;
		$rp = $rp - $gain;
		}
		else{
			$log .= "<span class=\"yellow\">“不过什么也没有发生！”</span><br>";
		}
		//处理直接变身
		if($w_msp > 5003){
			$log .= "<span class=\"yellow\">“【笼中鸟】的枷锁被打破了一些。”</span><br>";
			$w_mhp = $w_mhp * 5; $w_hp = $w_hp * 5; $w_wf = $w_wf * 5; $w_att = $w_att * 5; $w_def = $w_def * 5;
		}elseif($w_msp > 13377){
			$log .= "<span class=\"yellow\">“【笼中鸟】的枷锁被打破了一些。”</span><br>";
			$w_mhp = $w_mhp * 10; $w_hp = $w_hp * 10; $w_wf = $w_wf * 10; $w_att = $w_att * 10; $w_def = $w_def * 10;
		}elseif($w_msp > 33777){
			$log .= "<span class=\"yellow\">“【笼中鸟】的枷锁被完全打破了！”</span><br>";
			$w_mhp = $w_mhp * 30; $w_hp = $w_hp * 30; $w_wf = $w_wf * 30; $w_att = $w_att * 30; $w_def = $w_def * 30;
			$w_name = "完全解放的鸟儿";
		}
		//Void Damage
		$log=$x_temp_log.$log;
		return 0;

	}

	if (($w_type==89)&&($w_name=='走地羊')){ // 走地羊
		//旧电波直port的削武器防具耐久NPC，削爆直接消失。不过被削掉的数值会加算在其金钱上。
		$event_dice=rand(1,100);
		if($event_dice >=30){
			global $wep,$wepk,$wepe,$weps,$wepsk;
			global $ara,$arak,$arae,$aras,$arask;
			global $arf,$arfk,$arfe,$arfs,$arafk;
			global $art,$artk,$arte,$arts,$artsk;
			global $w_money;
			$log .= "<span class=\"neonblue\">“我这双拳头……很强……很厉害……咚咚打你……”</span><br>";
		$damage=rand(5,40);
		if(($weps !=0)&&($weps !='∞')){
			$weps-=$damage;
			$log .= "攻击使得<span class=\"red\">$wep</span>的耐久度下降了<span class=\"red\">$damage</span>点！<br>";
			if($weps <= 0){
				$log .= "<span class=\"red\">$wep</span>被彻底破坏了！<br>";
				$wep = $wepk = $wepsk ='';
				$wepe = $weps =0;
				$w_money = $w_money + ($damage * 120);
			}
		}
		if(($aras !=0)&&($aras !='∞')){
			$aras-=$damage;
			$log .= "攻击使得<span class=\"red\">$ara</span>的耐久度下降了<span class=\"red\">$damage</span>点！<br>";
			if($aras <= 0){
				$log .= "<span class=\"red\">$ara</span>被彻底破坏了！<br>";
				$ara = $arak = $arask ='';
				$arae = $aras =0;
				$w_money = $w_money + ($damage * 60);
			}
		}
		if(($arfs !=0)&&($arfs !='∞')){
			$arfs-=$damage;
			$log .= "攻击使得<span class=\"red\">$arf</span>的耐久度下降了<span class=\"red\">$damage</span>点！<br>";
			if($arfs <= 0){
				$log .= "<span class=\"red\">$arf</span>被彻底破坏了！<br>";
				$arf = $arfk = $arfsk ='';
				$arfe = $arfs =0;
				$w_money = $w_money + ($damage * 60);
			}
		}
		if(($arts !=0)&&($arts !='∞')){
			$arts-=$damage;
			$log .= "攻击使得<span class=\"red\">$art</span>的耐久度下降了<span class=\"red\">$damage</span>点！<br>";
			if($arts <= 0){
				$log .= "<span class=\"red\">$art</span>被彻底破坏了！<br>";
				$art = $artk = $artsk ='';
				$arte = $arts =0;
				$w_money = $w_money + ($damage * 60);
			}
		}
		$w_money = $w_money + ($damage * 30);
		$inf.='a';
		$inf.='f';
		$log .= "致伤攻击使你的<span class=\"red\">腕部</span>和<span class=\"red\">足部</span>受伤了！<br>";
	}
	}

	if (($w_type==89)&&($w_name=='书中虫')){ // 书中虫
		global $rp;
		$log .= "<span class=\"yellow\">“你真的愿意对这个手无寸铁的高中女生下手么？”</span><br>";
		$dice = rand(1,444);
		if($dice<=200){
			$log .= "<span class=\"neonblue\">“你感觉到了罪恶感。”</span><br>";
			$log .= "<span class=\"neonblue\">“【DEBUG】你的rp上升了<span class=\"red\">$dice</span>点。”</span><br>";
		}else{
			$log .= "<span class=\"neonblue\">“你不该这么做的。”</span><br>";
			$log .= "<span class=\"neonblue\">“【DEBUG】你的rp上升了<span class=\"red\">$dice</span>点。”</span><br>";
		}
		$rp = $rp + $dice;
	}

	if (($w_type==89)&&($w_name=='书中虫·讨价还价')){ // 书中虫·讨价还价
		global $rp;
		$dice = rand(1,1777);
		$log .= "<span class=\"yellow\">“对面似乎真的没有敌意，你还是要下手么？”</span><br>";
		if($dice<=200){
			$log .= "<span class=\"neonblue\">“你感觉到了罪恶感。”</span><br>";
			$log .= "<span class=\"neonblue\">“【DEBUG】你的rp上升了<span class=\"red\">$dice</span>点。”</span><br>";
		}elseif($dice<=400){
			$log .= "<span class=\"neonblue\">“你不该这么做的。”</span><br>";
			$log .= "<span class=\"neonblue\">“【DEBUG】你的rp上升了<span class=\"red\">$dice</span>点。”</span><br>";
		}else{
			$log .= "<span class=\"neonblue\">“罪恶感爬上了你的脊梁！”</span><br>";
			$log .= "<span class=\"neonblue\">“【DEBUG】你的rp上升了<span class=\"red\">$dice</span>点。”</span><br>";
		}
		$rp = $rp + $dice;
	}

	if (($w_type==89)&&($w_name=='书中虫·接受')){ // 书中虫·接受
		global $rp;
		$dice = rand(1777,4888);
		$log .= "<span class=\"yellow\">“你对一位毫无反抗能力，并且已经表示无敌意的女高中生横下死手。”</span><br>";
		$log .= "<span class=\"neonblue\">“希望你的良心还能得以安生。”</span><br>";
		$log .= "<span class=\"neonblue\">“【DEBUG】你的rp上升了<span class=\"red\">$dice</span>点。”</span><br>";
		$rp = $rp + $dice;
	}

	if (($w_type==89)&&($w_name=='迷你蜂')){ // 迷你蜂
		//随机固定伤害和异常效果。
		$log .= "<span class=\"neonblue\">“这只小蜜蜂勇敢地朝你袭来！”</span><br>";
		$dice = rand(1,4);
		if($dice == 1){
			$log .= "<span class=\"yellow\">魔法蜂针朝你刺来！造成了<span class=\"red\">250</span>点伤害！并且使你<span class=\"red\">麻痹</span>了！</span><br>";
			$hp-=250;
			if($hp < 0) $hp=0;
			$inf.='e';
		}elseif($dice == 2){
			$log .= "<span class=\"yellow\">幻惑花粉朝你扑来！造成了<span class=\"red\">250</span>点伤害！并且使你<span class=\"red\">混乱</span>了！</span><br>";
			$hp-=250;
			if($hp < 0) $hp=0;
			$inf.='w';
		}elseif($dice == 3){
			$log .= "<span class=\"yellow\">凶猛翼击朝你袭来！造成了<span class=\"red\">250</span>点伤害！并且使你<span class=\"red\">炎上</span>了！</span><br>";
			$hp-=250;
			if($hp < 0) $hp=0;
			$inf.='u';
		}elseif($dice == 4){
			$log .= "<span class=\"yellow\">剧毒蜂针朝你刺来！造成了<span class=\"red\">250</span>点伤害！并且使你<span class=\"red\">中毒</span>了！</span><br>";
			$hp-=250;
			if($hp < 0) $hp=0;
			$inf.='p';
		}else{
			$log .= "<span class=\"yellow\">体当冲刺朝你袭来！造成了<span class=\"red\">550</span>点伤害！<br>";
			$hp-=550;
			if($hp < 0) $hp=0;
		}
		//Void Damage
		$log=$x_temp_log.$log;
		return 0;
	}

	if (($w_type==89)&&($w_name=='种火花')){ // 种火花
		//就是个巨大种火，没有反应，这里只是白板。
	}
	
	if ((strpos($w_att_key,"X")!==false)&&($w_type)&&(!$type)&&(rand(1,100)>90)){
		if ($w_wep=='燕返262'){
			$log.="<img src=\"img/other/262.png\"><br>";
		}
		$damage=$mhp;
		$log .= "造成<span class=\"red\">$damage</span>点伤害！<br>";
		checkdmg ( $w_name, $name, $damage );
		$hp = 0;
		$w_killnum ++;
		include_once GAME_ROOT . './include/state.func.php';
		$killmsg = death ( $w_wep_kind, $w_name, $w_type, $w_wep_temp );
		$log .= npc_chat ( $w_type,$w_name, 'kill' );
		return $damage;
	}
	
	global ${'w_' . $skillinfo [$w_wep_kind]};
	$w_add_skill = & ${'w_' . $skillinfo [$w_wep_kind]};
	if ($w_club==18){
		$w_wep_skill=round(${'w_' .$skillinfo [$w_wep_kind]}*0.5+($w_wp+$w_wk+$w_wc+$w_wg+$w_wd+$w_wf)*0.5);
	}else{
		$w_wep_skill=${'w_' .$skillinfo [$w_wep_kind]};
	}
	if($skdr){
		$w_wep_skill=sqrt($w_wep_skill);
	}
	
	if ($watt==-1){
		if ($w_wep_kind == 'N') {
			global $w_wp;
			$watt =  round ($w_wep_skill*2/3);
		} else {
			$watt = $w_wepe * 2;
		}
	}
	

	
	$hitrate = get_hitrate ( $w_wep_kind, $w_wep_skill, $w_club, $w_inf );
	
	$hitrate *= get_clubskill_bonus_hitrate($w_club,$w_skills,'w_',$club,$skills,'');
	$damage_p = get_damage_p ( $w_rage, $w_att_key, $w_type, $w_name , $w_club);
	$hit_time = get_hit_time ( $w_att_key, $w_wep_skill, $hitrate, $w_wep_kind, $w_weps, $infobbs [$w_wep_kind] * get_clubskill_bonus_imfrate($w_club,$w_skills,'w_',$club,$skills,''), get_clubskill_bonus_imftime($w_club,$w_skills,'w_',$club,$skills,''), $wepimprate[$w_wep_kind] * get_clubskill_bonus_imprate($w_club,$w_skills,'w_',$club,$skills,''), $is_wpg, get_clubskill_bonus_hitrate($w_club,$w_skills,'w_',$club,$skills,'') );
	
	if ($hit_time [1] > 0) {
		if(strpos($w_att_key,'R')!==false){//随机伤害无视一切伤害计算
			$maxdmg = $mhp > $wepe ? $wepe : $mhp;
			$damage = rand(1,$maxdmg);
			$log .= "武器随机造成了<span class=\"red\">$damage</span>点伤害！<br>";
		}else{
			$gender_dmg_p = check_gender ( $w_name, '你', $w_gd, $gd, $w_att_key );
			if ($gender_dmg_p == 0) {
				$damage = 1;
			} else {
				global $w_att;
				$w_active = 1 - $active;
				$attack = $w_att + $watt;
				$defend = checkdef($def , $arbe + $arhe + $arae + $arfe,$w_att_key);
				
				
				$damage = get_original_dmg ( 'w_', '', $attack, $defend, $w_wep_skill, $w_wep_kind );
				
				if ($w_wep_kind == 'F') {
					if($sldr){
						$log.="<span class=\"red\">由于灵魂抽取的作用，灵系武器伤害大幅降低了！</span><br>";
					}else{
						$damage = round ( ($w_wepe + $damage) * get_WF_p ( 'w_', $w_club, $w_wepe) ); //get_spell_factor ( 1, $w_club, $w_att_key, $w_sp, $w_wepe ) );
					}
					
					
				}
				if ($w_wep_kind == 'J') {
					$adddamage=$mhp/3;
					if ($adddamage>20000) {$adddamage=10000;}
					$damage +=round($w_wepe*2/3+$adddamage);
				}
				checkarb ( $damage, $w_wep_kind, $w_att_key, $def_key );
				$damage *= $damage_p;
				
				$damage = $damage > 1 ? round ( $damage ) : 1;
				$damage *= $gender_dmg_p;
			}
			if ($wepk=='WJ'){
				$log.="<span class=\"red\">由于你手中的武器过于笨重，受到的伤害大增！真是大快人心啊！</span><br>";
				$damage+=round($damage*0.5);
			}
			
			
			
			if ($hit_time [1] > 1) {
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
			$damage += get_ex_dmg ( "你", 1, $w_club, $inf, $w_att_key, $w_wep_kind, $w_wepe, $w_wep_skill, $def_key );
			$damage = checkdmgdef($damage, $w_att_key, $def_key, 0);
			//好人卡特别活动
			$gm = ceil(count_good_man_card(1)*rand(80,120)/100);
			if($gm){
				$log .= "在你身上的<span class=\"yellow\">好人卡</span>的作用下，你受到的伤害增加了<span class=\"red\">$gm</span>点！<br>";
				$damage += $gm;
			}
			$bonus_dmg = get_clubskill_bonus_dmg_rate($w_club,$w_skills,$club,$skills)*100;
			if($bonus_dmg < 100){
				$log.="<span class=\"yellow\">由于技能效果的作用，伤害下降至".$bonus_dmg."%！</span><br>";
				$damage = round($damage * $bonus_dmg / 100);
			}
			if($damage != $pdamage){
				$log .= "<span class=\"yellow\">造成的总伤害：<span class=\"red\">$damage</span>。</span><br>";
			}
		}
		
		checkdmg ( $w_name, $name, $damage );
		
		get_dmg_punish ( $w_name, $damage, $w_hp, $w_att_key );
		
		get_inf ( '你', $hit_time [2], $w_wep_kind);
		
		check_KP_wep ( $w_name, $hit_time [3], $w_wep, $w_wepk, $w_wepe, $w_weps, $w_wepsk );
		
		exprgup ( $w_lvl, $lvl, $w_exp, 0, $rage );
		
		$hp -= $damage;
		
		if ($hp <= 0) {
			$tmp_club=$club;
			$hp = 0;
			$w_killnum ++;
			$rpup = 20;
			if($w_club == 19){
				$rpdec = 30;
				$rpdec += get_clubskill_rp_dec($w_club,$w_skills);
				$w_rp += round($rpup*(100-$rpdec)/100);
			}
			else{
				$w_rp += $rpup;
			}
			
			include_once GAME_ROOT . './include/state.func.php';
			$killmsg = death ( $w_wep_kind, $w_name, $w_type, $w_wep_temp );
			$log .= npc_chat ( $w_type,$w_name, 'kill' );
			if ($tmp_club==99)
				$log .= '<span class="yellow">由于你及时按了BOMB键，你原地满血复活了！</span><br>';
		}
	} else {
		$damage = 0;
		$log .= "但是没有击中！<br>";
	}

	$log = $x_temp_log.$log;
	
	check_GCDF_wep ( $w_name, $hit_time [0], $w_wep, $w_wep_kind, $w_wepk, $w_wepe, $w_weps, $w_wepsk );
	
	addnoise ( $w_wep_kind, $w_wepsk, $now, $pls, $w_pid, $pid, $w_wep_kind );
	
	if($w_club == 10){
		$w_add_skill +=2;
	}else{
		$w_add_skill +=1;
	}
	
	return $damage;
}

function get_original_dmg($w1, $w2, $att, $def, $ws, $wp_kind) {
	global $skill_dmg, $dmg_fluc, $weather, $pls;
	global ${$w1 . 'pose'}, ${$w1 . 'tactic'}, ${$w1 . 'club'}, ${$w1 . 'inf'}, ${$w1 . 'active'}, ${$w2 . 'pose'}, ${$w2 . 'tactic'}, ${$w2 . 'club'}, ${$w2 . 'inf'}, ${$w2 . 'active'},${$w2 . 'skills'},${$w1 . 'skills'};
	include_once GAME_ROOT.'./include/game/clubskills.func.php';
	get_clubskill_bonus(${$w1 . 'club'},${$w1 . 'skills'},$w1,${$w2 . 'club'},${$w2 . 'skills'},$w2,$att1,$def1);
	$att+=$att1; $def+=$def1;
	$attack_p = get_attack_p ( $weather, $pls, ${$w1 . 'pose'}, ${$w1 . 'tactic'}, ${$w1 . 'club'}, ${$w1 . 'inf'}, ${$w1 . 'active'} );
	$att_pow = $att * $attack_p;
	$defend_p = get_defend_p ( $weather, $pls, ${$w2 . 'pose'}, ${$w2 . 'tactic'}, ${$w2 . 'club'}, ${$w2 . 'inf'}, ${$w2 . 'active'} );
	$def_pow = $def * $defend_p;
	get_clubskill_bonus_p(${$w1 . 'club'},${$w1 . 'skills'},$w1,${$w2 . 'club'},${$w2 . 'skills'},$w2,$attfac,$deffac);
	$att_pow *= $attfac;
	$def_pow *= $deffac;
	if($def_pow <= 0){$def_pow = 0.01;}
	$damage = ($att_pow / $def_pow) * $ws * $skill_dmg [$wp_kind];
	
	$dfluc = $dmg_fluc [$wp_kind];
	$dfluc += get_clubskill_bonus_fluc(${$w1 . 'club'},${$w1 . 'skills'},$w1,${$w2 . 'club'},${$w2 . 'skills'},$w2);
	
	$dmg_factor = (100 + rand ( - $dfluc, $dfluc )) / 100;
	
	$damage = round ( $damage * $dmg_factor * rand ( 4, 10 ) / 10 );
	return $damage;
}

function get_damage_p(&$rg, $atkcdt, $type, $nm,$cl = 0, $msg = '' ) {
	$cri_dice = rand ( 0, 99 );
	if ($cl == 9) {
		$rg_m = 50;
		$dmg_p = 2;
		if (!empty($msg) || $rg >= 255) {
			$max_dice = 100;
		} elseif ($type != 0) {
			$max_dice = 40;
		} else {
			$max_dice = 0;
		}
		$cri_word = '发动必杀技';
	} else {
		$rg_m = 30;
		$dmg_p = 1.5;
		if ($rg >= 255) {
			$max_dice = 100;
		} else {
			$max_dice = 30;
		}
		$cri_word = '使出重击';
	}
	
	if (strpos ( $atkcdt, "c" ) !== false) {
		$rg_m = $cl == 9 ? 20 : 10;
		if ($max_dice != 0) {
			$max_dice += 30;
		}
	}
	if ($cri_dice <= $max_dice && $rg >= $rg_m) {
		global $log;
		
		$log .= npc_chat ( $type,$nm, 'critical' );
		
		if ($nm == '你') {
			$log .= "{$nm}消耗<span class=\"yellow\">$rg_m</span>点怒气，<span class=\"red\">{$cri_word}</span>！";
		} else {
			$log .= "{$nm}<span class=\"red\">{$cri_word}</span>！";
		}
		$rg -= $rg_m;
		return $dmg_p;
	} else {
		return 1;
	}
	/*if ($cl == 9) {
		if ($sd == 0) {
			if ((! empty ( $msg )) && ($rg >= $rg_m) || $rg == 255) {
				$log .= "你消耗<span class=\"yellow\">$rg_m</span>点怒气，<span class=\"red\">发动必杀技</span>！";
				$damage_p = 2;
				$rg -= $rg_m;
			}
		} else {
			if (($cri_dice < $max_dice && ($rg >= $rg_m)) || $rg == 255) {
				global $w_type;
				if ($w_type == 1) {
					$log .= npc_chat ( $w_type, 'critical' );
				}
				$log .= "<span class=\"red\">发动必杀技</span>！";
				$damage_p = 2;
				$rg -= $rg_m;
			}
		}
	} elseif ($cri_dice < $max_dice || $rg == 255) {
		if (($rg >= $rg_m) && ($sk >= 20) &&($lv > 3)) {
			if ($sd == 0) {
				$log .= "你消耗<span class=\"yellow\">$rg_m</span>点怒气，使出";
			} else {
				global $w_type;
				if ($w_type == 1) {
					$log .= npc_chat ( $w_type, 'critical' );
				}
			}
			$log .= "<span class=\"red\">重击</span>！";
			$damage_p = 1.5;
			$rg -= $rg_m;
		}
	}
	return $damage_p;*/
}

function checkdmg($p1, $p2, $d) {
	if (($d >= 100) && ($d < 150)) {
		$words = "{$p1}对{$p2}施加了一定程度的伤害。（100-150）";
	} elseif (($d >= 150) && ($d < 200)) {
		$words = "{$p1}拿了什么神兵？{$p2}所受的损伤已经不可忽略了。（150-200）";
	} elseif (($d >= 200) && ($d < 250)) {
		$words = "{$p1}简直不是人！{$p2}只能狼狈招架。（200-250）";
	} elseif (($d >= 250) && ($d < 300)) {
		$words = "{$p1}发出会心一击！{$p2}瞬间损失了大量生命！（250-300）";
	} elseif (($d >= 300) && ($d < 400)) {
		$words = "{$p1}使出浑身解数奋力一击！{$p2}想必凶多吉少！（300-400）";
	} elseif (($d >= 400) && ($d < 500)) {
		$words = "{$p1}使出武器中内藏的力量！可怜的{$p2}已经承受不住凶残的攻击了！（400-500）";
	} elseif (($d >= 500) && ($d < 600)) {
		$words = "{$p1}眼色一变使出绝招！{$p2}无法抵挡，只能任人宰割！（500-600）";
	} elseif (($d >= 600) && ($d < 750)) {
		$words = "{$p1}手中的武器闪耀出七彩光芒！{$p2}的身躯几乎融化在光芒中！（600-750）";
	} elseif (($d >= 750) && ($d < 1000)) {
		$words = "{$p1}受到天神的加护，打出惊天动地的一击！{$p2}此刻已不成人形！（750-1000）";
	} elseif (($d >= 1000) && ($d < 5000)) {
		$words = "{$p1}燃烧自己的生命得到了不可思议的力量！{$p2}，你还活着吗？（1000-5000）";
	} elseif (($d >= 5000) && ($d < 10000)) {
		$words = "{$p1}超越自己的极限爆发出了震天动地的力量！受此神力摧残的{$p2}化作了一颗流星！（5000-10000）";
	} elseif (($d >= 10000) && ($d < 50000)) {
		$words = "{$p1}运转百万匹周天，吐气扬声，一道霸气的光束直逼{$p2}，后者的身躯瞬间被力量的洪流所吞没！（10000-50000）";
	} elseif (($d >= 50000) && ($d < 200000)) {
		$words = "{$p1}已然超越了人类的极限！【{$d}】点的伤害——疾风怒涛般的攻击令大地崩塌，而{$p2}几乎化为齑粉！";
	}	elseif (($d >= 200000) && ($d < 500000)) {
		$words = "鬼哭神嚎！风暴既逝，{$p1}仍然屹立在战场上，而受到了【{$d}】点伤害的{$p2}想必已化为宇宙的尘埃了！";
	} elseif ( $d >= 500000) {
		$words = "残虐的攻击已经无法用言语形容！将{$p2}击飞出【{$d}】点伤害的英雄——{$p1}！让我们记住他的名字吧！";
	} else {
		$words = '';
	}
	if ($words) {
		addnews ( 0, 'damage', $words );
	}
	return;
}

function checkdef($def, $ardef, $aky, $active = 0){
	global $specialrate,$log,$w_name;
	$defend = $def + $ardef;
	if(strpos($aky,'N')!==false){
		$Ndice = rand(0,99);
		if($Ndice < $specialrate['N']){
			$defend = $def + round($ardef / 2);
			$log .= $active ? "<span class=\"yellow\">你的攻击隔着{$w_name}的防具造成了伤害！</span><br>" : "<span class=\"yellow\">{$w_name}的攻击隔着你的防具造成了伤害！</span><br>";
		}
	}
	return $defend;
}

function checkarb(&$dmg, $w, $aky, $dky, $active = 0) {
	global $log,$specialrate,$w_name;
	$dmginv = false;
	if (strpos ( $aky, 'n' ) !== false && (strpos ( $dky, 'B' ) !== false || strpos ( $dky, $w ) !== false)) {
		$dice = rand ( 0, 99 );
		if ($dice < $specialrate['n']) {
			$log .= $active ? "<span class=\"yellow\">你的攻击贯穿了{$w_name}的防具！</span><br>" : "<span class=\"yellow\">{$w_name}的攻击贯穿了你的防具！</span><br>";
			return;
		}
	}
	if (strpos ( $dky, 'B' ) !== false) {
		$dice = rand ( 0, 99 );
		if ($dice < $specialrate['B']) {
			$dmg = 1;
			$log .= $active ? "<span class=\"yellow\">你的攻击完全被{$w_name}的装备吸收了！</span><br>" : "<span class=\"yellow\">{$w_name}的攻击完全被你的装备吸收了！</span><br>";
			$dmginv = true;
		}else{
			$log .= $active ? "纳尼？你的装备使攻击无效化的属性竟然失效了！<br>" : "纳尼？{$w_name}的装备使攻击无效化的属性竟然失效了！<br>";
		}
	}
	if (strpos ( $dky, $w ) !== false && !$dmginv) {
		$dice = rand ( 0, 99 );
		if ($dice < 90) {
			$dmg /= 2;
			$log .= $active ? "<span class=\"yellow\">{$w_name}的装备使你的攻击伤害减半了！</span><br>" : "<span class=\"yellow\">你的装备使{$w_name}的攻击伤害减半了！</span><br>";

		}else{
			$log .= $active ? "{$w_name}的装备没能发挥减半伤害的效果！<br>" : "你的装备没能发挥减半伤害的效果！<br>";
		}
	}
	return;
}

function checkdmgdef($dmg, $aky, $dky, $active) {
	global $log, $name, $w_name;
	//if (strpos ( $aky, 'h' ) !== false){
	//	if($active){$nm = '你';}
	//	else{$nm = $w_name;}
	//	$flag = 1;
	if (strpos ( $dky, 'h' ) !== false){
		if($active){$nm = $w_name;}
		else{$nm = '你';}
		$flag = 1;
	}else{$flag = 0;}
	if ($flag) {
		$dice = rand ( 0, 99 );
		if($dmg > 1950 + $dice){
			if ($dice < 90) {
				$dmg = 1950 + $dice;
				$log .= "在{$nm}的装备的作用下，攻击伤害被限制了！<br>";
				
			}else{
				$log .= "{$nm}的装备没能发挥限制攻击伤害的效果！<br>";
			}
		}
	}
	return $dmg;
}

function checkdmgreflex(&$dmg, $ar) {
	global $log;
	if (strpos ( $ar, 'B' ) !== false) {
		$dice = rand ( 0, 99 );
		if ($dice < 90) {
			$dmg = 1;
			$log .= "<span class=\"red\">攻击的力量被完全吸收了！</span>";
		}else{
			$log .= "防具使攻击无效化的效果失败了！";
		}
	}
	return;
}

function getatkkey($w, $ah, $ab, $aa, $af, $at, $atkind, $is_wpg) {
	global $ex_attack;
	$atkcdt = '';
	$eqpkey = $w . $ah . $ab . $aa . $af . $at . substr ( $atkind, 1, 1 );
	foreach(Array('c','l','g','H','h','N','n','X','L','-','*','+') as $value){
		if (strpos ( $eqpkey, $value ) !== false) {
			$atkcdt .= '_'.$value;
		}
	}
	if(!$is_wpg){
		foreach(Array('r','R') as $value){
			if (strpos ( $w, $value ) !== false) {
				$atkcdt .= '_'.$value;
			}
		}
	}	
	foreach ($ex_attack as $value) {
		if (strpos ( $w, $value ) !== false && ! $is_wpg) {
			$atkcdt .= '_'.$value;
		}
	}

	return $atkcdt;
}

function get_hit_time($ky, $ws, $htr, $wk, $lmt, $infr, $inft, $wimpr, $is_wpg = false, $hitratebonus) {
	global $log, $nosta;
	if ($lmt == $nosta) {
		$wimpr *= 2;
		if ($is_wpg) {
			$wimpr *= 4;
		}
	}
	if (strpos ( $ky, 'r' ) !== false) {
		$atk_t = $ws >= 800 ? 6 : 2 + floor ( $ws / 200 );
		if ($wk == 'C' || $wk == 'D' || $wk == 'F') {
			if ($lmt == $nosta) {
				$lmt = 99;
			}
			if ($atk_t > $lmt) {
				$atk_t = $lmt;
			}
		}
		if ($wk == 'G' && $atk_t > $lmt) {
			$atk_t = $lmt;
		}
		
		$ht_t = 0;
		$inf_t = 0;
		$wimp_t = 0;
		//if($htr>100){$htr=100;}
		for($i = 1; $i <= $atk_t; $i ++) {
			$dice = rand ( 0, 99 );
			$dice2 = rand ( 0, 99 );
			$dice3 = rand ( 0, 99 );
			if ($dice < $htr) {
				$ht_t ++;
				if ($dice2 < $infr) {
					$inf_t += $inft;
				}
				if ($dice3 < $wimpr) {
					$wimp_t ++;
				}
			}
			$htr *= 0.8 * $hitratebonus;
			$infr *= 0.9;
			$wimpr *= $wimpr <= 0 ? 1 : 1.2;
		}
	} else {
		$atk_t = 1;
		$ht_t = 0;
		$inf_t = 0;
		$wimp_t = 0;
		$dice = rand ( 0, 99 );
		$dice2 = rand ( 0, 99 );
		$dice3 = rand ( 0, 99 );
		if ($dice < $htr) {
			$ht_t = 1;
			if ($dice2 < $infr) {
				$inf_t += $inft;
			}
			if ($dice3 < $wimpr) {
				$wimp_t = 1;
			}
		}
	}
	if ($atk_t > 1 && $ht_t > 0) {
		$log .= "{$atk_t}次连续攻击命中<span class=\"yellow\">{$ht_t}</span>次！";
	}
	return Array ($atk_t, $ht_t, $inf_t, $wimp_t );
}

function getdefkey($w, $ah, $ab, $aa, $af, $at, $atkind) {
	global $ex_dmg_def;
	$defcdt = '';
	$eqpkey = $w . $ah . $ab . $aa . $af . $at . substr ( $atkind, 1, 1 );
	foreach(Array('B','b','h','R','-','*','+') as $value){
		if (strpos ( $eqpkey, $value ) !== false) {
			$defcdt .= '_'.$value;
		}
	}
	if (strpos ( $eqpkey, 'A' ) !== false) {
		$defcdt .= '_P_K_G_C_D_F_J';
	} else {
		foreach(Array('P','K','G','C','D','F') as $value){
			if (strpos ( $eqpkey, $value ) !== false) {
				$defcdt .= '_'.$value;
			}
		}
		if (strpos($eqpkey,'G')!== false){
			$defcdt.='_J';
		}
	}
	foreach ($ex_dmg_def as $value) {
		if (strpos ( $eqpkey, $value ) !== false || strpos ( $eqpkey, 'a' ) !== false) {
			$defcdt .= '_'.$value;
		}
	}
	return $defcdt;
}

function get_ex_dmg($nm, $sd, $clb, &$inf, $ky, $wk, $we, $ws, $dky) {
	if ($ky) {
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
					//$e_dmg = $e_dmg > $mdmg ? round($wk_dmg_p*$mdmg*rand(100 - $fluc, 100 + $fluc)/100) : round($wk_dmg_p*$e_dmg*rand(100 - $fluc, 100 + $fluc)/100);
					$e_dmg = round($wk_dmg_p*$mdmg*($e_dmg/($e_dmg+$mdmg/2))*rand(100 - $fluc, 100 + $fluc)/100);
				} else{
					$e_dmg =  round($wk_dmg_p*$e_dmg*rand(100 - $fluc, 100 + $fluc)/100);
				}
				//$e_dmg += round ( ($we / ($we + $wdmg) + $ws / ($ws + $sdmg)) * rand ( 100 - $fluc, 100 + $fluc ) / 200 * $bdmg * $wk_dmg_p );
				$ex_def_dice = rand(0,99);
				if (strpos ( $dky, $def ) === false || $ex_def_dice > 90) {
					if(strpos ( $dky, $def ) !== false){
						$log .= "属性防御装备没能发挥应有的作用！";
					}
					//var_dump( $punish);
					if ($ex_inf_sign && strpos ( $inf, $ex_inf_sign ) !== false && $punish > 1) {
						$log .= "由于{$nm}已经{$dmginf}，{$dmgnm}伤害倍增！";
						$e_dmg *= $punish;
					} elseif ($ex_inf_sign && strpos ( $inf, $ex_inf_sign ) !== false && $punish < 1) {
						$log .= "由于{$nm}已经{$dmginf}，{$dmgnm}伤害减少！";
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
								global $w_combat_inf;
								$w_combat_inf .= $ex_inf_sign;
							}
							$log .= "并造成{$nm}{$dmginf}了！<br>";
							global $name,$w_name;
							if($nm == '你'){
								addnews($now,'inf',$w_name,$name,$ex_inf_sign);
							}else{
								addnews($now,'inf',$name,$w_name,$ex_inf_sign);
							}	
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
	/*
	if (strpos ( $ky, 'p' ) !== false) {
		$ex_dmg_sign = 'p';
		if ($clb == 8) {
			$e_htr = 20;
		} else {
			$e_htr = 0;
		}
	}
	if (strpos ( $ky, 'u' ) !== false) {
		$ex_dmg_sign = 'u';
		$e_htr = 0;
		if ($wk == 'G') {
			//echo 'g';
			$wk_dmg_p = 2;
		}
	}
	if (strpos ( $ky, 'i' ) !== false) {
		$ex_dmg_sign = 'i';
		$e_htr = 0;
	}
	if (isset ( $ex_dmg_sign )) {
		$dmgnm = $exdmgname [$ex_dmg_sign];
		$dmginf = $exdmginf [$ex_dmg_sign];
		$def = $ex_dmg_def [$ex_dmg_sign];
		$bdmg = $ex_base_dmg [$ex_dmg_sign];
		$wdmg = $ex_wep_dmg [$ex_dmg_sign];
		$sdmg = $ex_skill_dmg [$ex_dmg_sign];
		$fluc = $ex_dmg_fluc [$ex_dmg_sign];
		$infr = $ex_inf_r [$ex_dmg_sign];
		$minfr = $ex_max_inf_r [$ex_dmg_sign];
		$sinfr = $ex_skill_inf_r [$ex_dmg_sign];
		$punish = $ex_inf_punish [$ex_dmg_sign];
		$e_dmg = 1 + round ( ($we / ($we + $wdmg) + $ws / ($ws + $sdmg)) * rand (100 - $fluc,100 + $fluc ) / 200 * $bdmg * $wk_dmg_p );
		if (strpos ( $dky, $def ) == false) {
			if (strpos ( $inf, $ex_dmg_sign ) !== false && $punish > 1) {
				$log .= "由于{$nm}已经{$dmginf}，{$dmgnm}伤害倍增！";
				$e_htr = 0;
			} elseif (strpos ( $inf, $ex_dmg_sign ) !== false && $punish < 1) {
				$log .= "由于{$nm}已经{$dmginf}，{$dmgnm}伤害减少！";
				$e_htr = 0;
			} else {
				$e_htr += $infr + $ws * $sinfr;
				$e_htr = $e_htr > $minfr ? $minfr : $e_htr;
			}
			$e_dmg = round ( $e_dmg * $punish );
			$log .= "{$dmgnm}造成了<span class=\"red\">{$e_dmg}</span>点额外伤害！<br>";
			$dice = rand ( 0, 99 );
			if ($dice < $e_htr) {
				$inf .= $ex_dmg_sign;
				if ($sd == 0) {
					global $w_combat_inf;
					$w_combat_inf .= $ex_dmg_sign;
				}
				$log .= "并造成{$nm}{$dmginf}了！<br>";
			}
		} else {
			$e_dmg = round ( $e_dmg / 2 );
			$log .= "{$dmgnm}被防御效果抵消了！造成了<span class=\"red\">{$e_dmg}</span>点额外伤害！<br>";
		}
		return $e_dmg;
	} else {
		return;
	}
*/
}

function get_WF_p($w, $clb, $we) {
	global $log, ${$w . 'sp'}, ${$w . 'skills'};
	if (! empty ( $w )) {
		$factor = 0.5;
	} else {
		$we = $we > 0 ? $we : 1;
		if ($clb == 9) {
			include_once GAME_ROOT.'./include/game/clubskills.func.php';
			$spd0 = round ( 0.2*get_clubskill_bonus_spd($clb,${$w . 'skills'})*$we);
		} else {
			$spd0 = round ( 0.25*$we);
		}
		if ($spd0 >= ${$w . 'sp'}) {
			$spd = ${$w . 'sp'} - 1;
		} else {
			$spd = $spd0;
		}
		$factor = 0.5 + $spd / $spd0 / 2;
		$f = round ( 100 * $factor );
		$log .= "你消耗{$spd}点体力，发挥了灵力武器{$f}％的威力！";
		${$w . 'sp'} -= $spd;
	}
	return $factor;
}

function check_KP_wep($nm, $ht, &$wp, &$wk, &$we, &$ws, &$wsk) {
	global $log, $nosta;
	if ($ht > 0 && $ws == $nosta) {
		$we -= $ht;
		if ($nm == '你') {
			$log .= "{$nm}的{$wp}的攻击力下降了{$ht}！<br>";
		}
		if ($we <= 0) {
			$log .= "{$nm}的<span class=\"red\">$wp</span>使用过度，已经损坏，无法再装备了！<br>";
			$wp = '拳头';
			$wk = 'WN';
			$we = 0;
			$ws = $nosta;
			$wsk = '';
		}
	} elseif ($ht > 0 && $ws != $nosta) {
		$ws -= $ht;
		if ($nm == '你') {
			$log .= "{$nm}的{$wp}的耐久度下降了{$ht}！<br>";
		}
		if ($ws <= 0) {
			$log .= "{$nm}的<span class=\"red\">$wp</span>使用过度，已经损坏，无法再装备了！<br>";
			$wp = '拳头';
			$wk = 'WN';
			$we = 0;
			$ws = $nosta;
			$wsk = '';
		}
	}
	return;
}

function check_GCDF_wep($nm, $ht, &$wp, $wp_kind, &$wk, &$we, &$ws, &$wsk) {
	global $log, $nosta;
	if ((($wp_kind == 'C') || ($wp_kind == 'D')|| ($wp_kind == 'F')) && ($ws != $nosta)) {
		$ws -= $ht;
		if ($nm == '你') {
			$log .= "{$nm}用掉了{$ht}个{$wp}。<br>";
		}
		if ($ws <= 0) {
			$log .= "{$nm}的<span class=\"red\">$wp</span>用光了！<br>";
			$wp = '拳头';
			$wsk = '';
			$wk = 'WN';
			$we = 0;
			$ws = $nosta;
		}
	} elseif ((($wp_kind == 'G')||($wp_kind == 'J')) && ($ws != $nosta)) {
		$ws -= $ht;
		if ($nm == '你') {
			$log .= "{$nm}的{$wp}的弹药数减少了{$ht}。<br>";
		}
		if ($ws <= 0) {
			$log .= "{$nm}的<span class=\"red\">$wp</span>的弹药用光了！<br>";
			$ws = $nosta;
		}
	}
	return;
}

function get_inf($nm, $ht, $wp_kind) {
	if ($ht > 0) {
		global $infatt;
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
		if($nm == '你'){
			$w = '';
		} else {
			$w = 'w_';
		}
		if ($inf_att) {
			global $log, ${$w . 'ar' . $inf_att}, ${$w . 'ar' . $inf_att . 'k'}, ${$w . 'ar' . $inf_att . 'e'}, ${$w . 'ar' . $inf_att . 's'}, ${$w . 'ar' . $inf_att . 'sk'};
			if (${$w . 'ar' . $inf_att . 's'}) {
				${$w . 'ar' . $inf_att . 's'} -= $ht;
				if ($nm == '你') {
					$log .= "你的${$w.'ar'.$inf_att}的耐久度下降了{$ht}！<br>";
				}
				if (${$w . 'ar' . $inf_att . 's'} <= 0) {
					$log .= "{$nm}的<span class=\"red\">${$w.'ar'.$inf_att}</span>受损过重，无法再装备了！<br>";
					${$w . 'ar' . $inf_att} = ${$w . 'ar' . $inf_att . 'k'} = ${$w . 'ar' . $inf_att . 'sk'} = '';
					${$w . 'ar' . $inf_att . 'e'} = ${$w . 'ar' . $inf_att . 's'} = 0;
				}
			} else {
				global $log, ${$w . 'inf'}, $infinfo;
				if (strpos ( ${$w . 'inf'}, $inf_att ) === false) {
					${$w . 'inf'} .= $inf_att;
					if ($w == 'w_') {
						global ${$w . 'combat_inf'};
						${$w . 'combat_inf'} .= $inf_att;
					}
					$log .= "{$nm}的<span class=\"red\">$infinfo[$inf_att]</span>部受伤了！<br>";
//					global $name,$w_name;
//					if($nm == '你'){
//						addnews($now,'inf',$w_name,$name,$inf_att);
//					}else{
//						addnews($now,'inf',$name,$w_name,$inf_att);
//					}					
				}
			}
		}
	}
	return;
}

function get_dmg_punish($nm, $dmg, &$hp, $a_ky) {
	if ($dmg >= 1000) {
		global $log;
		if ($dmg < 2000) {
			$hp_d = floor ( $hp / 2 );
		} elseif ($dmg < 5000) {
			$hp_d = floor ( $hp * 2 / 3 );
		} else {
			$hp_d = floor ( $hp * 4 / 5 );
		}
		if (strpos ( $a_ky, 'H' ) != false) {
			$hp_d = floor ( $hp_d / 10 );
		}
		$log .= "惨无人道的攻击对{$nm}自身造成了<span class=\"red\">$hp_d</span>点<span class=\"red\">反噬伤害！</span><br>";
		$hp -= $hp_d;
	}
	return;
}

function exprgup(&$lv_a, $lv_d, &$exp, $isplayer, &$rg) {
	global $log;
	$expup = round ( ($lv_d - $lv_a) / 3 );
	$expup = $expup > 0 ? $expup : 1;
	$exp += $expup;
	//$log .= "$isplayer 的经验值增加 $expup 点<br>";
	if ($isplayer) {
		global $upexp;
		$nl_exp = $upexp;
	} else {
		global $w_upexp;
		$nl_exp = $w_upexp;
	}
	if ($exp >= $nl_exp) {
		include_once GAME_ROOT . './include/state.func.php';
		lvlup ( $lv_a, $exp, $isplayer );
	}
	$rgup = round ( ($lv_a - $lv_d) / 3 );
	$rg += $rgup > 0 ? $rgup : 1;
	return;
}

function addnoise($wp_kind, $wsk, $ntime, $npls, $nid1, $nid2, $nmode) {
	if ((($wp_kind == 'G') && (strpos ( $wsk, 'S' ) === false)) || ($wp_kind == 'F')) {
		global $noisetime, $noisepls, $noiseid, $noiseid2, $noisemode;
		$noisetime = $ntime;
		$noisepls = $npls;
		$noiseid = $nid1;
		$noiseid2 = $nid2;
		$noisemode = $nmode;
		save_combatinfo ();
	} elseif (strpos ( $wsk, 'd' ) !== false){
		global $noisetime, $noisepls, $noiseid, $noiseid2, $noisemode;
		$noisetime = $ntime;
		$noisepls = $npls;
		$noiseid = $nid1;
		$noiseid2 = $nid2;
		$noisemode = 'D';
		save_combatinfo ();
	}
	if (strlen($wp_kind)>=3){
		global $noisetime, $noisepls, $noiseid, $noiseid2, $noisemode,$wep;
		$noisetime = $ntime;
		$noisepls = $npls;
		$noiseid = $nid1;
		$noiseid2 = $nid2;
		$noisemode = $wp_kind;
		save_combatinfo ();
	}
	
	return;
}

function check_gender($nm_a, $nm_d, $gd_a, $gd_d, $a_ky) {
	$gd_dmg_p = 1;
	if ((((strpos ( $a_ky, "l" ) !== false) && ($gd_a != $gd_d)) || ((strpos ( $a_ky, "g" ) !== false) && ($gd_a == $gd_d))) && (! rand ( 0, 4 ))) {
		global $log;
		$log .= "<span class=\"red\">{$nm_a}被{$nm_d}迷惑，无法全力攻击！</span>";
		$gd_dmg_p = 0;
	} elseif ((((strpos ( $a_ky, "l" ) !== false) && ($gd_a == $gd_d)) || ((strpos ( $a_ky, "g" ) !== false) && ($gd_a != $gd_d))) && (! rand ( 0, 4 ))) {
		global $log;
		$log .= "<span class=\"red\">{$nm_a}被{$nm_d}激怒，伤害加倍！</span>";
		$gd_dmg_p = 2;
	}
	return $gd_dmg_p;
}

function npc_changewep($active = 0){
	global $now,$log,$w_name,$w_type,$w_club,$w_wep, $w_wepk, $w_wepe, $w_weps, $w_itm0, $w_itmk0, $w_itme0, $w_itms0, $w_itm1, $w_itmk1, $w_itme1, $w_itms1, $w_itm2, $w_itmk2, $w_itme2, $w_itms2, $w_itm3, $w_itmk3, $w_itme3, $w_itms3, $w_itm4, $w_itmk4, $w_itme4, $w_itms4, $w_itm5, $w_itmk5, $w_itme5, $w_itms5,$w_itm6, $w_itmk6, $w_itme6, $w_itms6, $w_wepsk, $w_arbsk, $w_arhsk, $w_arask, $w_arfsk, $w_artsk, $w_itmsk0, $w_itmsk1, $w_itmsk2, $w_itmsk3, $w_itmsk4, $w_itmsk5, $w_itmsk6;
	global $w_arb, $w_arbk, $w_arbe, $w_arbs, $w_arh, $w_arhk, $w_arhe, $w_arhs, $w_ara, $w_arak, $w_arae, $w_aras, $w_arf, $w_arfk, $w_arfe, $w_arfs, $w_art, $w_artk, $w_arte, $w_arts;
	global $wepk,$wepsk,$arbsk,$arask,$arhsk,$arfsk,$artsk,$artk,$rangeinfo,$ex_dmg_def;
	if(!$w_name || !$w_type || $w_club != 98){return;}
	
	$dice = rand(0,99);
	if($dice > 50){
		$weplist = array();
		$wepklist = Array($w_wepk);$weplist2 = array();
		for($i=0;$i<=6;$i++){
			if(${'w_itms'.$i} && ${'w_itme'.$i} && strpos(${'w_itmk'.$i},'W')===0){
				$weplist[] = Array($i,${'w_itm'.$i},${'w_itmk'.$i},${'w_itme'.$i},${'w_itms'.$i},${'w_itmsk'.$i});
				$wepklist[] = ${'w_itmk'.$i};
			}
		}
		if(!empty($weplist)){
			$wepklist = array_unique($wepklist);
			$temp_def_key = getdefkey($wepsk,$arhsk,$arbsk,$arask,$arfsk,$artsk,$artk);
			$wepkAI = $wepskAI = true;
			if(strpos($temp_def_key,'_P_K_G_C_D_F')!==false || strpos($temp_def_key,'B')!==false){$wepkAI = false;}
			if(count($wepklist)<=1){$wepkAI = false;}
			if(strpos($temp_def_key,'_q_U_I_D_E')!==false || strpos($temp_def_key,'b')!==false){$wepskAI = false;}
			
			if($wepkAI){
				if(!$wepk){$wepk_temp = 'WN';}else{$wepk_temp = $wepk;}
				foreach($weplist as $val){
					if($rangeinfo[substr($val[2],1,1)] >= $rangeinfo[substr($wepk_temp,1,1)] && strpos($temp_def_key,substr($val[2],1,1))===false){
						$weplist2[] = $val;
					}
				}
				if($weplist2){
					$weplist = $weplist2;
				}				
			}
			if($wepskAI && $weplist){
				$minus = array();
				foreach($weplist as $val){
					foreach($ex_dmg_def as $key => $val2){
						if(strpos($val[5],$key)!==false && strpos($temp_def_key,$val2)!==false){
							$minus[] = $val;
						}
					}
				}
				//var_dump($minus);
				if(count($minus) < count($weplist)){
					$weplist = array_diff($weplist,$minus);
				}				
			}
		}
//		var_dump($wepkAI);echo '<br>';var_dump($wepskAI);echo '<br>';
//		var_dump($weplist);
//		if(!empty($weplist2)){
//			$weplist = $weplist2;
//		}
		
		if(!empty($weplist)){
			$oldwep = $w_wep;
			shuffle($weplist);
			$chosen = $weplist[0];$c = $chosen[0];
			//var_dump($chosen);
			${'w_itm'.$c} = $w_wep;${'w_itmk'.$c} = $w_wepk;${'w_itme'.$c} = $w_wepe;${'w_itms'.$c} = $w_weps;${'w_itmsk'.$c} = $w_wepsk;
			$w_wep = $chosen[1]; $w_wepk = $chosen[2]; $w_wepe = $chosen[3];$w_weps = $chosen[4];$w_wepsk = $chosen[5];
			//list($c,$w_wep,$w_wepk,$w_wepe,$w_weps,$w_wepsk) = $chosen;
			$log .= "<span class=\"yellow\">{$w_name}</span>将手中的<span class=\"yellow\">{$oldwep}</span>卸下，装备了<span class=\"yellow\">{$w_wep}</span>！<br>";
		}
	}
	return;
}

function npc_chat($type,$nm, $mode) {
	global $npccanchat,$npcchaton;
	if ($npcchaton && in_array($type,$npccanchat)) {
		global $npcchat, $w_itmsk0, $w_hp, $w_mhp;
		$chatcolor = $npcchat[$type][$nm]['color'];
		if(!empty($chatcolor)){
			$npcwords = "<span class = \"{$chatcolor}\">";
		}else{
			$npcwords = '<span>';
		}
		switch ($mode) {
			case 'attack' :
				if (empty ( $w_itmsk0 )) {
					$npcwords .= "{$npcchat[$type][$nm][0]}";
					$w_itmsk0 = '1';
				} elseif ($w_hp > ($w_mhp / 2)) {
					$dice = rand ( 1, 2 );
					$npcwords .= "{$npcchat[$type][$nm][$dice]}";
				} else {
					$dice = rand ( 3, 4 );
					$npcwords .= "{$npcchat[$type][$nm][$dice]}";
				}
				break;
			case 'defend' :
				if (empty ( $w_itmsk0 )) {
					$npcwords .= "{$npcchat[$type][$nm][0]}";
					$w_itmsk0 = '1';
				} elseif ($w_hp > ($w_mhp / 2)) {
					$dice = rand ( 5, 6 );
					$npcwords .= "{$npcchat[$type][$nm][$dice]}";
				} else {
					$dice = rand ( 7, 8 );
					$npcwords .= "{$npcchat[$type][$nm][$dice]}";
				}
				break;
			case 'death' :
				$npcwords .= "{$npcchat[$type][$nm][9]}";
				break;
			case 'escape' :
				$npcwords .= "{$npcchat[$type][$nm][10]}";
				break;
			case 'cannot' :
				$npcwords .= "{$npcchat[$type][$nm][11]}";
				break;
			case 'critical' :
				$npcwords .= "{$npcchat[$type][$nm][12]}";
				break;
			case 'kill' :
				$npcwords .= "{$nm}对你说道：{$npcchat[$type][$nm][13]}";
				break;
		}
		$npcwords .= '</span><br>';
		return $npcwords;
	} elseif ($mode == 'death') {
		global $lwinfo;
		if (is_array ( $lwinfo [$type] )) {
			$lastword = $lwinfo [$type] [$nm];
		} else {
			$lastword = $lwinfo [$type];
		}
		$npcwords = "<span class=\"yellow\">“{$lastword}”</span><br>";
		return $npcwords;
	} else {
		return;
	}
}

function count_good_man_card($active){
	$goodmancard = 0;
	if($active){
		global $itm0,$itmk0,$itms0,$itm1,$itmk1,$itms1,$itm2,$itmk2,$itms2,$itm3,$itmk3,$itms3,$itm4,$itmk4,$itms4,$itm5,$itmk5,$itms5,$itm6,$itmk6,$itms6;
		
		for($i=0;$i<=6;$i++){
			if(${'itms'.$i} && ${'itm'.$i} == '好人卡' && ${'itmk'.$i} == 'Y'){
				$goodmancard += ${'itms'.$i};
			}
		}
	}else{
		global $w_itm0,$w_itmk0,$w_itms0,$w_itm1,$w_itmk1,$w_itms1,$w_itm2,$w_itmk2,$w_itms2,$w_itm3,$w_itmk3,$w_itms3,$w_itm4,$w_itmk4,$w_itms4,$w_itm5,$w_itmk5,$w_itms5,$w_itm6,$w_itmk6,$w_itms6;
		
		for($i=0;$i<=6;$i++){
			if(${'w_itms'.$i} && ${'w_itm'.$i} == '好人卡' && ${'w_itmk'.$i} == 'Y'){
				$goodmancard += ${'w_itms'.$i};
			}
		}
	}
	return $goodmancard;
}
?>
