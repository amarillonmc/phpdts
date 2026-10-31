<?php
//require_once './include/common.inc.php';



function sing($sn){
	global $log,$msg,$now,$pls,$name,$nick,$plsinfo,$hplsinfo,$ss,$mss,$noiseinfo,$arte;
	global $db,$tablepre;
	global $att,$def;
	global $wep,$wepk,$weps,$wepes,$wepsk;
	global $rp;
	global $pdata,$weather,$gamevars,$areatime,$arealist,$areanum,$deathnum,$nosta;
	
	extract($pdata, EXTR_REFS);

	if (!empty($clbpara['kimi_no_ss'])) {
		$log.="<span class=\"grey\">你的歌魂槽已经废除，再也无法歌唱了……</span><br>";
		return;
	}
	if (!empty($clbpara['setsuna_mute'])) {
		$log.="<span class=\"cyan\">刹那的余韵尚未散去，你在接下来的<span class=\"yellow\">{$clbpara['setsuna_mute']}</span>次探索或移动内无法发声歌唱。</span><br>";
		return;
	}

	//登记非功能性地点信息时合并隐藏地点
	//To be frank, from a story perspective, hidden maps shouldn't be affected by songs anyways.
	//Patch this out as a temp solution to the movelist oddity.
	//foreach($hplsinfo as $hgroup=>$hpls) $plsinfo += $hpls;

	//A better fix would be removing $hplsinfo from $plsinfo after processing the song. But still.

	//$log.=$sn.'_'.$now.'_'.$pls.'_'.$name."<br>";
	$r = ($sn == '忘忧草') ? max(intval($arte), 200) : intval($arte);
	$song_title = isset($noiseinfo[$sn]) ? $noiseinfo[$sn] : $sn;
	include_once GAME_ROOT.'./include/game/revcombat.func.php';
	
	if ($ss>=$r){
		$ss-=$r;
		$log.="消耗<span class=\"yellow\">{$r}</span>点歌魂，歌唱了<span class=\"yellow\">{$song_title}</span>。<br>";
	}else{
		$log.="需要<span class=\"yellow\">{$r}</span>歌魂才能唱这首歌！<br>";
		return;
	}

	// 记录唱歌动作
	$pdata['clbpara']['achvars']['sing'] = 1;
	
	if ($sn=="Alicemagic"){
		$log.="♪你說過在哭泣之後應該可以破涕而笑♪<br>
					♪我們的旅行　我不會忘♪<br>
					♪施展魔法　為了不再失去　我不會說再見♪<br>
					♪再次踏出腳步之時　將在某一天到來♪<br>";
					
		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','$name','$plsinfo','♪你說過在哭泣之後應該可以破涕而笑♪')");
		
		//$result = $db->query("select * from {$tablepre}players where `pls`={$pls} and hp>0 and type=0");
		$db->query ( "UPDATE {$tablepre}players SET def=def+30 WHERE `pls` ={$pls} AND hp>0 AND type=0 ");
		$def+=30;
		addnoise($sn,'__',$now,$pls,0,0,$sn);
		addnews($now,'song',$name,$plsinfo[$pls],$song_title,$nick);
		return;
		
	}elseif ($sn=="Crow Song"){
			$log.="♪从这里找一条路♪<br>
					♪找到逃离的生路♪<br>
					♪奏响激烈的摇滚♪<br>
					♪盯紧遥远的彼方♪<br>
					♪在这个连呼吸都难以为继的都市中♪<br>";
					
		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','$name','$plsinfo','♪从这里找一条路♪')");
		
		//$result = $db->query("select * from {$tablepre}players where pls='$pls' and hp>0 and type=0");
		$db->query ("UPDATE {$tablepre}players SET att=att+30 WHERE `pls`={$pls} AND hp>0 AND type=0");
		$att+=30;
		addnoise($sn,'__',$now,$pls,0,0,$sn);
		addnews($now,'song',$name,$plsinfo[$pls],$song_title,$nick);
		return;
	
	
	}elseif ($sn=="恋歌"){
			$log.="♪la la la la♪<br>
					♪la la la la♪<br>
					♪la la la♪<br>
					♪la la la la la♪<br>
					♪la la la ... ...♪<br>";
					
		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','$name','$plsinfo','♪♪la la la la♪♪...')");
		
		//$result = $db->query("select * from {$tablepre}players where pls='$pls' and hp>0 and type=0");
		$ss+=20;
		$mss+=20;
		$rp-=50;
		addnoise($sn,'__',$now,$pls,0,0,$sn);
		addnews($now,'song',$name,$plsinfo[$pls],$song_title,$nick);
		return;
	
	
	
	}elseif ($sn=="鸡肉之歌"){
			$log.="♪翼失いながらも優しくて♪<br>
					♪今は静かに眠るこの手の中で♪<br>
					♪ありがとう　感謝の言葉♪<br>
					♪あなたは教えてくれたよ　鶏肉♪<br>";
					
		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','$name','$plsinfo','♪♪la la la la♪♪...')");
		
		//$result = $db->query("select * from {$tablepre}players where pls='$pls' and hp>0 and type=0");
		$db->query ( "UPDATE {$tablepre}players SET wep='鸡肉' WHERE `pls` ={$pls} AND hp>0 AND type=0 ");
		$db->query ( "UPDATE {$tablepre}players SET wepk='wc' WHERE `pls` ={$pls} AND hp>0 AND type=0 ");
		$db->query ( "UPDATE {$tablepre}players SET weps=wepes=55 WHERE `pls` ={$pls} AND hp>0 AND type=0 ");
		$db->query ( "UPDATE {$tablepre}players SET wepsk='z' WHERE `pls` ={$pls} AND hp>0 AND type=0 ");
		addnoise($sn,'__',$now,$pls,0,0,$sn);
		addnews($now,'song',$name,$plsinfo[$pls],$song_title,$nick);
		return;

	}elseif ($sn=="忘忧草"){
		$log.="♪让软弱的我们懂得残忍♪<br>
				♪狠狠面对人生每次寒冷♪<br>
				♪依依不舍的爱过的人♪<br>
				♪往往有缘没有份♪<br>";
		$log.="<span class=\"lime\">你的歌声化作漫天飞散的忘忧草，洗刷了整座岛屿的罪业……而你的生命也随之燃尽，归于沉寂。</span><br>";

		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','$name','$plsinfo','♪让软弱的我们懂得残忍……♪')");

		$rp_drop = max(0, intval($mhp));
		$db->query("UPDATE {$tablepre}players SET rp = rp - {$rp_drop} WHERE type = 0");
		$rp -= $rp_drop;

		addnoise($sn,'__',$now,$pls,0,0,$sn);
		addnews($now,'song',$name,$plsinfo[$pls],$song_title,$nick);

		include_once GAME_ROOT.'./include/state.func.php';
		$clbpara['kimi_overloaded'] = 1;
		death('event', '', 0, $art, $pdata);
		return;

	}elseif ($sn=="きみは帰る場所"){
		$log.="♪探していた場所は　君の心の中♪<br>
				♪いつでも帰れるよ　ここが君の場所♪<br>
				♪遠回りしたって　迷い込んだって♪<br>
				♪最後にはきっと　辿り着けるから♪<br>";

		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','$name','$plsinfo','♪探していた場所は　君の心の中♪')");

		$recorded_mss = max(1, intval($mss));
		$clbpara['kimi_recorded_mss'] = $recorded_mss;
		$clbpara['kimi_no_ss'] = 1;
		$clbpara['kimi_charge'] = 0;
		unset($clbpara['kimi_overloaded']);
		$ss = 0;
		$mss = 0;
		$log.="<span class=\"clan\">温暖的归处已在心中定格——你的歌魂槽已被彻底废除（记录歌魂上限：<span class=\"yellow\">{$recorded_mss}</span>）！</span><br>";

		addnoise($sn,'__',$now,$pls,0,0,$sn);
		addnews($now,'song',$name,$plsinfo[$pls],$song_title,$nick);
		return;

	}elseif ($sn=="サイハテ"){
		$log.="♪晴れた空に　君が笑う♪<br>
				♪悲しみも全部　風に溶けてゆく♪<br>
				♪さよならじゃないよ　また会える日まで♪<br>
				♪最果ての空へ　花束を贈ろう♪<br>";

		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','$name','$plsinfo','♪最果ての空へ　花束を贈ろう♪')");

		if ($weather > 1 && $weather < 17) {
			$weather = 0;
			save_gameinfo();
			$log.="<span class=\"lime\">轻快的电音驱散了阴霾，天空放晴了！</span><br>";
		}

		$result = $db->query("SELECT * FROM {$tablepre}players WHERE pls='$pls' AND hp<=0 AND type NOT IN (88,92)");
		$corpse_cnt = 0;
		$best_item = null;
		$total_money = 0;
		while ($cdata = $db->fetch_array($result)) {
			$corpse_cnt++;
			$total_money += intval($cdata['money']);
			if ($best_item === null) {
				$slots = array(
					array('n'=>$cdata['wep'],'k'=>$cdata['wepk'],'e'=>$cdata['wepe'],'s'=>$cdata['weps'],'sk'=>$cdata['wepsk'],'p'=>isset($cdata['weppara'])?$cdata['weppara']:''),
					array('n'=>$cdata['arb'],'k'=>$cdata['arbk'],'e'=>$cdata['arbe'],'s'=>$cdata['arbs'],'sk'=>$cdata['arbsk'],'p'=>isset($cdata['arbpara'])?$cdata['arbpara']:''),
					array('n'=>$cdata['arh'],'k'=>$cdata['arhk'],'e'=>$cdata['arhe'],'s'=>$cdata['arhs'],'sk'=>$cdata['arhsk'],'p'=>isset($cdata['arhpara'])?$cdata['arhpara']:''),
					array('n'=>$cdata['ara'],'k'=>$cdata['arak'],'e'=>$cdata['arae'],'s'=>$cdata['aras'],'sk'=>$cdata['arask'],'p'=>isset($cdata['arapara'])?$cdata['arapara']:''),
					array('n'=>$cdata['arf'],'k'=>$cdata['arfk'],'e'=>$cdata['arfe'],'s'=>$cdata['arfs'],'sk'=>$cdata['arfsk'],'p'=>isset($cdata['arfpara'])?$cdata['arfpara']:''),
					array('n'=>$cdata['art'],'k'=>$cdata['artk'],'e'=>$cdata['arte'],'s'=>$cdata['arts'],'sk'=>$cdata['artsk'],'p'=>isset($cdata['artpara'])?$cdata['artpara']:''),
				);
				for ($i = 1; $i <= 6; $i++) {
					$slots[] = array('n'=>$cdata['itm'.$i],'k'=>$cdata['itmk'.$i],'e'=>$cdata['itme'.$i],'s'=>$cdata['itms'.$i],'sk'=>$cdata['itmsk'.$i],'p'=>isset($cdata['itmpara'.$i])?$cdata['itmpara'.$i]:'');
				}
				foreach ($slots as $sl) {
					if (!empty($sl['s']) && !empty($sl['n']) && $sl['n'] != '拳头' && $sl['n'] != '内衣') {
						if ($best_item === null || intval($sl['e']) > intval($best_item['e'])) {
							$best_item = $sl;
						}
					}
				}
			}
			$cdata['wep'] = ''; $cdata['wepk'] = ''; $cdata['wepe'] = 0; $cdata['weps'] = 0; $cdata['wepsk'] = '';
			$cdata['arb'] = ''; $cdata['arbk'] = ''; $cdata['arbe'] = 0; $cdata['arbs'] = 0; $cdata['arbsk'] = '';
			$cdata['arh'] = ''; $cdata['arhk'] = ''; $cdata['arhe'] = 0; $cdata['arhs'] = 0; $cdata['arhsk'] = '';
			$cdata['ara'] = ''; $cdata['arak'] = ''; $cdata['arae'] = 0; $cdata['aras'] = 0; $cdata['arask'] = '';
			$cdata['arf'] = ''; $cdata['arfk'] = ''; $cdata['arfe'] = 0; $cdata['arfs'] = 0; $cdata['arfsk'] = '';
			$cdata['art'] = ''; $cdata['artk'] = ''; $cdata['arte'] = 0; $cdata['arts'] = 0; $cdata['artsk'] = '';
			for ($i = 1; $i <= 6; $i++) {
				$cdata['itm'.$i] = ''; $cdata['itmk'.$i] = ''; $cdata['itme'.$i] = 0; $cdata['itms'.$i] = 0; $cdata['itmsk'.$i] = ''; $cdata['itmpara'.$i] = '';
			}
			$cdata['money'] = 0;
			$cdata['pls'] = 254;
			player_save($cdata);
		}
		if (function_exists('lost_searchmemory')) lost_searchmemory('all', $pdata);

		if ($corpse_cnt > 0) {
			$hp_gain = round($mhp * 0.25) * $corpse_cnt;
			$sp_gain = round($msp * 0.25) * $corpse_cnt;
			if (empty($clbpara['kimi_no_hp'])) {
				$mhp += 15 * $corpse_cnt;
				$hp = min($mhp, $hp + $hp_gain);
			}
			$mss += 10 * $corpse_cnt;
			$ss += 10 * $corpse_cnt;
			$sp = min($msp, $sp + $sp_gain);
			$rp -= 60 * $corpse_cnt;
			$sk_up = 10 * $corpse_cnt;
			$wp += $sk_up; $wk += $sk_up; $wg += $sk_up; $wc += $sk_up; $wd += $sk_up; $wf += $sk_up;
			$money += $total_money;
			$log.="<span class=\"lime\">你向<span class=\"yellow\">{$corpse_cnt}</span>具遗体献上白花将他们送往尽头，你的生命与体力大幅恢复，全熟练度+<span class=\"yellow\">{$sk_up}</span>，罪业减轻了！</span><br>";
			if ($best_item !== null && empty($itms0)) {
				$itm0 = $best_item['n'];
				$itmk0 = $best_item['k'];
				$itme0 = $best_item['e'];
				$itms0 = $best_item['s'];
				$itmsk0 = $best_item['sk'];
				$itmpara0 = $best_item['p'];
				$log.="<span class=\"clan\">逝者留下了一件信物<span class=\"yellow\">{$itm0}</span>，静静地落在你的脚边。</span><br>";
			}
		} else {
			$d_heal = min($mhp, max(20, intval($deathnum) * 10));
			if (empty($clbpara['kimi_no_hp'])) {
				$hp = min($mhp, $hp + $d_heal);
			}
			$sp = min($msp, $sp + $d_heal);
			$rp -= min(100, max(10, intval($deathnum) * 5));
			$log.="<span class=\"lime\">歌声遥寄给远方已逝的生命，你恢复了<span class=\"yellow\">{$d_heal}</span>点生命与体力，心境变得澄澈了。</span><br>";
		}

		addnoise($sn,'__',$now,$pls,0,0,$sn);
		addnews($now,'song',$name,$plsinfo[$pls],$song_title,$nick);
		return;

	}elseif ($sn=="SETSUNA"){
		$log.="♪季節が巡るたび　思い出すのは♪<br>
				♪あの日の刹那　凍りついた時間♪<br>
				♪声にならないまま　消えてゆくけれど♪<br>
				♪この瞬間だけは　永遠になる♪<br>";

		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','$name','$plsinfo','♪あの日の刹那　凍りついた時間♪')");

		$clbpara['setsuna_mute'] = 3;
		include_once GAME_ROOT.'./include/game/revclubskills.func.php';
		$result = $db->query("SELECT * FROM {$tablepre}players WHERE pls='$pls' AND hp>0 AND type=0 AND pid!='$pid'");
		$frozen_cnt = 0;
		while ($tdata = $db->fetch_array($result)) {
			if (strpos($tdata['inf'], 'i') === false) {
				$tdata['inf'] .= 'i';
			}
			getclubskill('inf_dizzy', $tdata['clbpara']);
			set_skill_Temp_status('inf_dizzy', 'starttimes', $now, $tdata['clbpara']);
			set_skill_Temp_status('inf_dizzy', 'lasttimes', 5, $tdata['clbpara']);
			player_save($tdata);
			$frozen_cnt++;
		}
		$log.="<span class=\"cyan\">无声的空灵旋律凝结了此地的时空（未产生噪音），共有<span class=\"yellow\">{$frozen_cnt}</span>名玩家陷入冻结与短暂眩晕！</span><br>";

		if (in_array($pls, array(3, 12, 18, 20)) && $weps && $wep != '拳头') {
			$rv3 = isset($clbpara['randver3']) ? intval($clbpara['randver3']) : 0;
			$target_sk = ($rv3 >= 500 && strpos($wepsk, 'x') === false) ? 'x' : 'k';
			if (strpos($wepsk, $target_sk) === false) {
				$wepsk .= $target_sk;
				$sk_name = ($target_sk == 'x') ? '奇迹' : '冰华';
				$log.="<span class=\"clan\">四季小镇的回忆在刹那间结晶，你的武器<span class=\"yellow\">{$wep}</span>获得了<span class=\"yellow\">{$sk_name}</span>属性！</span><br>";
			} else {
				$wepe += 30;
				if (is_numeric($weps) && $weps != $nosta) $weps += 10;
				$log.="<span class=\"clan\">四季小镇的回忆在刹那间共鸣，你的武器<span class=\"yellow\">{$wep}</span>效果+30、耐久+10！</span><br>";
			}
		}

		addnews($now,'song',$name,$plsinfo[$pls],$song_title,$nick);
		return;

	}elseif ($sn=="ローリンガール"){
		$log.="♪ロンリーガールはいつまでも　届かない夢見て♪<br>
				♪騒ぐ頭の中を　かき回して、かき回して♪<br>
				♪「問題ない。」と呟いて、言葉は失われた？♪<br>
				♪もう一回、もう一回！「私をどうか転がして」と♪<br>";

		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','$name','$plsinfo','♪もう一回、もう一回！♪')");

		$inf = '';
		$rage = max($rage, 100);
		$clbpara['song_rolling'] = (!empty($clbpara['song_rolling']) ? intval($clbpara['song_rolling']) : 0) + 1;
		$log.="<span class=\"lime\">「没问题！」你甩开所有异常状态，怒气高涨，进入了<span class=\"yellow\">翻滚少女（{$clbpara['song_rolling']}层）</span>状态！每次移动都将越滚越快，并可在致命攻击下翻滚逃生一次！</span><br>";

		addnoise($sn,'__',$now,$pls,0,0,$sn);
		addnews($now,'song',$name,$plsinfo[$pls],$song_title,$nick);
		return;

	}elseif ($sn=="鳥の詩"){
		$log.="♪消える飛行機雲　僕たちは見送った♪<br>
				♪眩しくて逃げた　いつだって弱くて♪<br>
				♪あの日から変わらず　いつまでも変わらずに♪<br>
				♪いられなかったこと　悔しくて指を離す♪<br>";

		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','$name','$plsinfo','♪消える飛行機雲　僕たちは見送った♪')");

		$clbpara['song_torinouta'] = (!empty($clbpara['song_torinouta']) ? intval($clbpara['song_torinouta']) : 0) + 1;
		$stage = $clbpara['song_torinouta'];
		if ($stage == 1) {
			$inf = str_replace(array('f', 'a'), '', $inf);
			$sp = min($msp, $sp + 100);
			if ($weather < 17) {
				$weather = 1;
				save_gameinfo();
			}
			$log.="<span class=\"lime\">【鳥の詩·第一乐章】夏日的海风吹散阴霾，天气化作大晴，你的足部与腕部伤势痊愈，恢复了100点体力！</span><br>";
		} elseif ($stage == 2) {
			$msp += 60;
			$sp = $msp;
			$wp += 15; $wk += 15; $wg += 15; $wc += 15; $wd += 15; $wf += 15;
			$log.="<span class=\"lime\">【鳥の詩·第二乐章】你仰望盛夏的苍穹，体力上限+60并完全恢复，全系熟练度+15！</span><br>";
			if ($pls == 12 || $pls == 8) {
				if (empty($clbpara['kimi_no_hp'])) {
					$mhp += 60;
					$hp = min($mhp, $hp + 60);
				}
				$rp -= 80;
				$log.="<span class=\"clan\">千年的夏之记忆在此地回响，你的生命上限额外+60，罪业减轻了80点！</span><br>";
			}
		} elseif ($stage == 3) {
			include_once GAME_ROOT.'./include/game/revclubskills.func.php';
			getclubskill('npc_flying', $clbpara);
			if (empty($clbpara['skill']) || !in_array('npc_flying', $clbpara['skill'])) {
				$clbpara['skill'][] = 'npc_flying';
			}
			$sp = $msp;
			$wp += 15; $wk += 15; $wg += 15; $wc += 15; $wd += 15; $wf += 15;
			$log.="<span class=\"clan\">【鳥の詩·终章】你终于化作了飞向蓝天的白翼之鸟，获得了技能<span class=\"yellow\">「飞翼」</span>（移动不再消耗体力与生命），全系熟练度+15！</span><br>";
		} else {
			$sp = min($msp, $sp + 100);
			$wp += 10; $wk += 10; $wg += 10; $wc += 10; $wd += 10; $wf += 10;
			$log.="<span class=\"lime\">苍穹的歌声持续回荡，你恢复了100点体力，全系熟练度+10！</span><br>";
		}

		addnoise($sn,'__',$now,$pls,0,0,$sn);
		addnews($now,'song',$name,$plsinfo[$pls],$song_title,$nick);
		return;

	}elseif ($sn=="Philosophyz"){
		$log.="♪この世界の果てで　書き換える物語♪<br>
				♪傷ついた分だけ　強くなれるなら♪<br>
				♪幾千の可能性　選び取る未来へ♪<br>
				♪命の答えを　今ここに刻もう♪<br>";

		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','$name','$plsinfo','♪この世界の果てで　書き換える物語♪')");

		$inf_cnt = strlen($inf);
		$lost_hp = max(0, $mhp - $hp);
		$inf = '';
		$att_gain = $inf_cnt * 25;
		$def_gain = $inf_cnt * 25;
		$mhp_gain = round($lost_hp * 0.2);
		$att += $att_gain;
		$def += $def_gain;
		if (empty($clbpara['kimi_no_hp'])) {
			$mhp += $mhp_gain;
			$hp = $mhp;
		}

		if ($wepe > 0) $wepe = ceil($wepe * 1.15);
		if (is_numeric($weps) && $weps > 0 && $weps != $nosta) $weps = ceil($weps * 1.15);
		if ($arbe > 0) $arbe = ceil($arbe * 1.15);
		if (is_numeric($arbs) && $arbs > 0 && $arbs != $nosta) $arbs = ceil($arbs * 1.15);
		$mss = max(0, $mss - 20);
		if ($ss > $mss) $ss = $mss;

		if ($rp > 0) $rp = floor($rp * 0.7);
		$clbpara['rewrite_kagari_shield'] = 1;

		$log.="<span class=\"clan\">【Rewrite】你改写了自身的伤痛与武装！生命完全恢复，生命上限+<span class=\"yellow\">{$mhp_gain}</span>，攻防+<span class=\"yellow\">{$att_gain}</span>，武器与身体防具效能提升15%，并获得了抵御一次雏菊/救济抹杀的改写护盾！</span><br>";

		addnoise($sn,'__',$now,$pls,0,0,$sn);
		addnews($now,'song',$name,$plsinfo[$pls],$song_title,$nick);
		return;

	}elseif ($sn=="だんご大家族"){
		$log.="♪だんご　だんご　だんご　だんご　だんご大家族♪<br>
				♪やんちゃな焼きだんご　やさしいあんこだんご♪<br>
				♪すこし夢見がちな　月見だんご♪<br>
				♪おすましごまだんご　四つ子串だんご♪<br>
				♪みんなみんなあわせて　百人家族♪<br>";

		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','$name','$plsinfo','♪だんご　だんご　だんご　だんご　だんご大家族♪')");

		$rp -= 50;
		$rv1 = isset($clbpara['randver1']) ? max(10, intval($clbpara['randver1'])) : 30;
		$dangos = array(
			array('n' => '顽皮的烧团子', 'k' => 'HB', 'e' => 120, 's' => 2, 'sk' => 'u'),
			array('n' => '温柔的豆沙团子', 'k' => 'HH', 'e' => 250, 's' => 2, 'sk' => 'z'),
			array('n' => '爱做梦的赏月团子', 'k' => 'HM', 'e' => $rv1, 's' => 1, 'sk' => ''),
			array('n' => '矜持的芝麻团子', 'k' => 'MD', 'e' => 15, 's' => 2, 'sk' => ''),
			array('n' => '四子串团子', 'k' => 'HB', 'e' => 80, 's' => 4, 'sk' => ''),
		);
		$picked = $dangos[array_rand($dangos)];
		$placed = false;
		if (empty($itms0)) {
			$itm0 = $picked['n']; $itmk0 = $picked['k']; $itme0 = $picked['e']; $itms0 = $picked['s']; $itmsk0 = $picked['sk']; $itmpara0 = '';
			$placed = true;
		} else {
			for ($i = 1; $i <= 6; $i++) {
				if (empty(${'itms'.$i})) {
					${'itm'.$i} = $picked['n']; ${'itmk'.$i} = $picked['k']; ${'itme'.$i} = $picked['e']; ${'itms'.$i} = $picked['s']; ${'itmsk'.$i} = $picked['sk']; ${'itmpara'.$i} = '';
					$placed = true;
					break;
				}
			}
		}
		if ($placed) {
			$log.="<span class=\"lime\">温馨的歌声唤来了团子大家族的一员，你获得了<span class=\"yellow\">{$picked['n']}</span>！</span><br>";
		} else {
			$db->query("INSERT INTO {$tablepre}mapitem (itm, itmk, itme, itms, itmsk, pls) VALUES ('{$picked['n']}', '{$picked['k']}', '{$picked['e']}', '{$picked['s']}', '{$picked['sk']}', '$pls')");
			$log.="<span class=\"lime\">温馨的歌声唤来了<span class=\"yellow\">{$picked['n']}</span>，落在了此地的地面上！</span><br>";
		}

		if ($weather == 18) {
			if (!empty($gamevars['wth18etime'])) {
				$gamevars['wth18etime'] += 120;
				save_gameinfo();
			}
			if (empty($clbpara['kimi_no_hp'])) {
				$hp += 50;
			}
			$sp += 50;
			$log.="<span class=\"clan\">漫天飞舞的光玉与团子大家族的歌声共鸣，光玉雨的持续时间延长了120秒，你的生命与体力额外突破恢复了50点！</span><br>";
		}

		addnoise($sn,'__',$now,$pls,0,0,$sn);
		addnews($now,'song',$name,$plsinfo[$pls],$song_title,$nick);
		return;

	}elseif ($sn=="ようこそジャパリパークへ"){
		$log.="♪ようこそジャパリパークへ　今日もドッタンバッタン大騒ぎ♪<br>
				♪うー！がぉー！　高らかに笑い笑えばフレンズ♪<br>
				♪喧嘩して　すっちゃかめっちゃかしても仲良し♪<br>
				♪けものはいて　のけものはいない　本当の愛はここにある♪<br>";

		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','$name','$plsinfo','♪けものはいて　のけものはいない♪')");

		$db->query("DELETE FROM {$tablepre}maptrap WHERE pls='$pls'");
		if (!empty($clbpara['skill']) && in_array('inf_cursed', $clbpara['skill'])) {
			$clbpara['skill'] = array_values(array_diff($clbpara['skill'], array('inf_cursed')));
		}
		foreach (array('wepsk', 'arbsk', 'arhsk', 'arask', 'arfsk', 'artsk') as $eq_sk) {
			if (!empty($$eq_sk) && strpos($$eq_sk, 'V') !== false) {
				$$eq_sk = str_replace('V', '', $$eq_sk);
			}
		}

		if ($rage > 0) {
			$sp = min($msp, $sp + $rage * 2);
			$exp += $rage;
			$rage = 0;
		}
		$bid = 0; $action = '';
		$result = $db->query("SELECT * FROM {$tablepre}players WHERE pls='$pls' AND hp>0 AND type=0 AND pid!='$pid'");
		while ($fdata = $db->fetch_array($result)) {
			if ($fdata['rage'] > 0) {
				$fdata['sp'] = min($fdata['msp'], $fdata['sp'] + $fdata['rage'] * 2);
				$fdata['exp'] += $fdata['rage'];
				$fdata['rage'] = 0;
			}
			$fdata['bid'] = 0;
			$fdata['action'] = '';
			player_save($fdata);
		}

		if (empty($itms0)) {
			$itm0 = '加帕里馒头'; $itmk0 = 'HB'; $itme0 = 150; $itms0 = 2; $itmsk0 = 'z'; $itmpara0 = '';
		} else {
			$db->query("INSERT INTO {$tablepre}mapitem (itm, itmk, itme, itms, itmsk, pls) VALUES ('加帕里馒头', 'HB', '150', '2', 'z', '$pls')");
		}
		$log.="<span class=\"lime\">「这里有野兽，但没有被排斥者！」此地的所有陷阱与你身上的诅咒被一扫而空，众人的怒火化作了体力与经验，空气中还飘来了热乎乎的<span class=\"yellow\">加帕里馒头</span>！</span><br>";

		addnoise($sn,'__',$now,$pls,0,0,$sn);
		addnews($now,'song',$name,$plsinfo[$pls],$song_title,$nick);
		return;

	}elseif ($sn=="星をつなげて"){
		$log.="♪夜空に瞬く　小さな光をつなげて♪<br>
				♪離れていても　同じ空を見上げてる♪<br>
				♪星座の道しるべ　迷わないように♪<br>
				♪約束の場所まで　きっと連れてゆくよ♪<br>";

		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','$name','$plsinfo','♪夜空に瞬く　小さな光をつなげて♪')");

		if (empty($clbpara['kimi_no_hp'])) {
			$hp += ceil(max(0, $mhp - $hp) * 0.5);
		}
		$sp += ceil(max(0, $msp - $sp) * 0.5);
		if (!empty($inf)) {
			$cut_len = floor(strlen($inf) / 2);
			$inf = $cut_len > 0 ? substr($inf, 0, $cut_len) : '';
		}
		$weather = 17;
		save_gameinfo();
		include_once GAME_ROOT.'./include/game/item2.func.php';
		newradar(2);

		if (!isset($clbpara['star_mark_pls']) || $clbpara['star_mark_pls'] === '') {
			$clbpara['star_mark_pls'] = intval($pls);
			if (is_array($arealist)) {
				$pos = array_search($pls, $arealist);
				if ($pos !== false && $pos > $areanum) {
					unset($arealist[$pos]);
					$arealist[] = $pls;
					$arealist = array_values($arealist);
					save_gameinfo();
				}
			}
			$log.="<span class=\"clan\">夜空连起了璀璨的极光与星图！你在<span class=\"yellow\">{$plsinfo[$pls]}</span>锚定了星标（该区域禁区顺位已延后），再次歌唱或遭遇致命危险时将顺着星光重逢！</span><br>";
		} else {
			$target_pls = intval($clbpara['star_mark_pls']);
			unset($clbpara['star_mark_pls']);
			include_once GAME_ROOT.'./include/game/search.func.php';
			$safepls = get_safe_plslist(0);
			if (!in_array($target_pls, $safepls) && !empty($safepls)) {
				$target_pls = $safepls[array_rand($safepls)];
			}
			$pls = $target_pls;
			$bid = 0; $action = '';
			if (function_exists('lost_searchmemory')) lost_searchmemory('all', $pdata);
			$log.="<span class=\"clan\">星光相连的羁绊引导你跨越旅途，瞬间回到了<span class=\"yellow\">{$plsinfo[$pls]}</span>！</span><br>";
		}

		addnoise($sn,'__',$now,$pls,0,0,$sn);
		addnews($now,'song',$name,$plsinfo[$pls],$song_title,$nick);
		return;

	}elseif ($sn=="乗ってけ！ジャパリビート"){
		$log.="♪乗ってけ！ジャパリビート　リズムに合わせて♪<br>
				♪どこまでも走るよ　ジャパリライン♪<br>
				♪風を切って進め　止まらないステップで♪<br>
				♪次のステージへ　飛び出そう！♪<br>";

		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','$name','$plsinfo','♪乗ってけ！ジャパリビート！♪')");

		$pose = 6;
		$clbpara['japari_line'] = 5;
		$sk_map = array('wp' => $wp, 'wk' => $wk, 'wg' => $wg, 'wc' => $wc, 'wd' => $wd, 'wf' => $wf);
		arsort($sk_map);
		$top_sk = key($sk_map);
		$$top_sk += 40;

		$boosted_food = 0;
		for ($i = 1; $i <= 6; $i++) {
			$k = ${'itmk'.$i};
			if (!empty(${'itms'.$i}) && is_numeric(${'itms'.$i}) && ${'itms'.$i} != $nosta && (strpos($k, 'HH') === 0 || strpos($k, 'HS') === 0 || strpos($k, 'HB') === 0)) {
				${'itms'.$i} += 1;
				$boosted_food++;
			}
		}
		$log.="<span class=\"lime\">你踏上了欢快疾驰的加帕里铁道！姿态切换为<span class=\"yellow\">✧狂飙姿态✧</span>，最高熟练度+40，背包中<span class=\"yellow\">{$boosted_food}</span>件补给品耐久+1，接下来5次移动将恢复生命体力并碾碎沿途陷阱！</span><br>";

		addnoise($sn,'__',$now,$pls,0,0,$sn);
		addnews($now,'song',$name,$plsinfo[$pls],$song_title,$nick);
		return;

	}elseif ($sn=="大空ドリーマー"){
		$log.="♪大空ドリーマー　向かい風に乗って♪<br>
				♪誰も見たことない　高さまで飛ぼう♪<br>
				♪ペンギンだって　空を飛べるんだよ♪<br>
				♪夢見る翼は　誰にも止められない！♪<br>";

		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','$name','$plsinfo','♪大空ドリーマー　向かい風に乗って♪')");

		$inf = str_replace('i', '', $inf);
		if ($arbs > 0 && strpos($arbsk, 'I') === false) {
			$arbsk .= 'I';
		}
		if (in_array($weather, array(3, 4, 5, 6, 7, 12, 13))) {
			$ss_refund = max(15, floor($r / 2));
			$ss = min($mss, $ss + $ss_refund);
			$wp += 25; $wk += 25; $wg += 25; $wc += 25; $wd += 25; $wf += 25;
			$log.="<span class=\"cyan\">你在风雨与冰雪的逆风中展翅，返还了<span class=\"yellow\">{$ss_refund}</span>点歌魂，全系熟练度+25！</span><br>";
		}
		$old_rv1 = isset($clbpara['randver1']) ? intval($clbpara['randver1']) : 64;
		$clbpara['randver1'] = min(160, $old_rv1 + 32);
		$clbpara['charge1'] = 101;
		$over_heal = $clbpara['randver1'] * 3;
		if (empty($clbpara['kimi_no_hp'])) {
			$hp += $over_heal;
		}
		$sp += $over_heal;
		$log.="<span class=\"clan\">不放弃天空之梦的歌声让你突破了界限！第一梦值提升至<span class=\"yellow\">{$clbpara['randver1']}</span>，生命与体力突破上限恢复了<span class=\"yellow\">{$over_heal}</span>点！</span><br>";

		addnoise($sn,'__',$now,$pls,0,0,$sn);
		addnews($now,'song',$name,$plsinfo[$pls],$song_title,$nick);
		return;

	}elseif ($sn=="水手"){
		$log.="♪他说风雨中这点痛算什么♪<br>
				♪擦干泪不要怕　至少我们还有梦♪<br>
				♪他说风雨中这点痛算什么♪<br>
				♪擦干泪不要问　为什么♪<br>";

		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','$name','$plsinfo','♪他说风雨中这点痛算什么　擦干泪不要怕　至少我们还有梦♪')");

		$inf_cnt = strlen($inf);
		$inf = '';
		$mult = ($weather >= 3 && $weather <= 16) ? 2 : 1;
		$def_gain = $inf_cnt * 20 * $mult;
		$mhp_gain = $inf_cnt * 30 * $mult;
		$def += $def_gain;
		if (empty($clbpara['kimi_no_hp'])) {
			$mhp += $mhp_gain;
		}

		$rv1 = isset($clbpara['randver1']) ? intval($clbpara['randver1']) : 64;
		$rv2 = isset($clbpara['randver2']) ? intval($clbpara['randver2']) : 128;
		$rv3 = isset($clbpara['randver3']) ? intval($clbpara['randver3']) : 256;
		if ($rv1 + $rv2 + $rv3 < 400) {
			$clbpara['randver1'] = max($rv1, rand(64, 128));
			$clbpara['randver2'] = max($rv2, rand(128, 256));
			$clbpara['randver3'] = max($rv3, rand(256, 1024));
		}
		$dream_sum = intval($clbpara['randver1']) + intval($clbpara['randver2']) + intval($clbpara['randver3']);
		if (empty($clbpara['kimi_no_hp'])) {
			$hp = min($mhp, $hp + $dream_sum);
		}
		$sp = min($msp, $sp + $dream_sum);
		$shield_val = round($dream_sum / 2);
		include_once GAME_ROOT.'./include/game/revclubskills.func.php';
		getclubskill('buff_shield', $clbpara);
		if (empty($clbpara['skill']) || !in_array('buff_shield', $clbpara['skill'])) {
			$clbpara['skill'][] = 'buff_shield';
		}
		set_skillpara('buff_shield', 'svar', $shield_val, $clbpara);

		$log.="<span class=\"lime\">「至少我们还有梦！」你擦干眼泪将苦痛化作坚韧，异常状态全清，防御+<span class=\"yellow\">{$def_gain}</span>、生命上限+<span class=\"yellow\">{$mhp_gain}</span>，并依凭心中的梦想恢复了<span class=\"yellow\">{$dream_sum}</span>点生命与体力，获得了<span class=\"yellow\">{$shield_val}</span>点护盾！</span><br>";

		addnoise($sn,'__',$now,$pls,0,0,$sn);
		addnews($now,'song',$name,$plsinfo[$pls],$song_title,$nick);
		return;

	}elseif ($sn=="挪威的森林"){
		$log.="♪那里湖面总是澄清　那里空气充满宁静♪<br>
				♪雪白明月照在大地　藏着你不愿提起的回忆♪<br>
				♪或许我　不该问　让你平静的心再起涟漪♪<br>
				♪只是爱你的心超出了界限　我想拥有你所有一切♪<br>";

		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','$name','$plsinfo','♪只是爱你的心超出了界限　我想拥有你所有一切♪')");

		if ($weather >= 8 && $weather <= 16) {
			$weather = 0;
			save_gameinfo();
		}
		$result = $db->query("SELECT * FROM {$tablepre}players WHERE pls='$pls' AND hp>0 AND pid!='$pid'");
		$candidates = array();
		while ($odata = $db->fetch_array($result)) {
			$odata['smeo'] = '';
			$odata['bid'] = 0;
			$odata['action'] = '';
			player_save($odata);
			if ($odata['type'] == 0 || $odata['type'] == 90) {
				$candidates[] = $odata;
			}
		}
		if (in_array($pls, array(7, 16, 29))) {
			$sp = $msp;
			$pose = 4;
		}
		if ($weps && $wep != '拳头') {
			$added_sk = false;
			if (strpos($wepsk, 'l') === false) { $wepsk .= 'l'; $added_sk = true; }
			if (strpos($wepsk, '*') === false) { $wepsk .= '*'; $added_sk = true; }
			if (!$added_sk) {
				$wepe += 100;
			}
		}
		$stole_msg = '';
		if (empty($itms0) && !empty($candidates)) {
			$target = $candidates[array_rand($candidates)];
			$bag_slots = array();
			for ($i = 1; $i <= 6; $i++) {
				if (!empty($target['itms'.$i]) && !empty($target['itm'.$i])) {
					$bag_slots[] = $i;
				}
			}
			if (!empty($bag_slots)) {
				$si = $bag_slots[array_rand($bag_slots)];
				$itm0 = $target['itm'.$si];
				$itmk0 = $target['itmk'.$si];
				$itme0 = $target['itme'.$si];
				$itms0 = $target['itms'.$si];
				$itmsk0 = $target['itmsk'.$si];
				$itmpara0 = isset($target['itmpara'.$si]) ? $target['itmpara'.$si] : '';
				$target['itm'.$si] = ''; $target['itmk'.$si] = ''; $target['itme'.$si] = 0; $target['itms'.$si] = 0; $target['itmsk'.$si] = ''; $target['itmpara'.$si] = '';
				player_save($target);
				$stole_msg = "，并悄无声息地将<span class=\"yellow\">{$target['name']}</span>的<span class=\"yellow\">{$itm0}</span>揽入了怀中！";
			}
		}
		$log.="<span class=\"clan\">迷离的森林浓雾抹去了此地所有人的探索记忆，你的武器染上了越界的执念（热恋+灵魂抽取）{$stole_msg}</span><br>";

		addnoise($sn,'__',$now,$pls,0,0,$sn);
		addnews($now,'song',$name,$plsinfo[$pls],$song_title,$nick);
		return;

	}elseif ($sn=="当"){
		$log.="♪啊～　啊～　啊～　啊～♪<br>
				♪当山峰没有棱角的时候　当河水不再流♪<br>
				♪当时间停住日夜不分　当天地万物化为虚有♪<br>
				♪让我们红尘作伴　活得潇潇洒洒♪<br>
				♪策马奔腾　共享人世繁华♪<br>
				♪对酒当歌　唱出心中喜悦♪<br>
				♪轰轰烈烈　把握青春年华♪<br>";

		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','$name','$plsinfo','♪让我们红尘作伴　活得潇潇洒洒！♪')");

		$db->query("UPDATE {$tablepre}players SET wepk='WP', wepsk=REPLACE(wepsk,'n','') WHERE pls='$pls' AND hp>0 AND type=0 AND pid!='$pid' AND wepk LIKE 'WK%'");
		$areatime += 300;
		if (is_array($arealist)) {
			foreach (array(3, 12, 18, 20) as $st_pls) {
				$pos = array_search($st_pls, $arealist);
				if ($pos !== false && $pos > $areanum) {
					unset($arealist[$pos]);
					$arealist[] = $st_pls;
				}
			}
			$arealist = array_values($arealist);
		}
		save_gameinfo();

		if ($weps && $wep != '拳头') {
			if (strpos($wepsk, 'v') === false) $wepsk .= 'v';
			$wepe = ceil($wepe * 1.25);
		}
		if ($arbs && $arb != '内衣') {
			if (strpos($arbsk, 'v') === false) $arbsk .= 'v';
			$arbe = ceil($arbe * 1.25);
		}
		$has_wine = false;
		for ($i = 1; $i <= 6; $i++) {
			if (!empty(${'itms'.$i}) && (strpos(${'itm'.$i}, '酒') !== false || strpos(${'itm'.$i}, '伏特加') !== false)) {
				$has_wine = true;
				break;
			}
		}
		$wine_msg = '';
		if ($has_wine) {
			$rage = 255;
			$att += 100;
			$wp += 50; $wk += 50; $wg += 50; $wc += 50; $wd += 50; $wf += 50;
			$wine_msg = "行囊中的美酒与豪情共鸣（对酒当歌），你的怒气沸腾至极限，基础攻击力+100、全系熟练度+50！";
		}
		$log.="<span class=\"lime\">「当山峰没有棱角的时候，当时间停住日夜不分！」此地他人的锐器尽皆失去锋芒化作钝器，下一次禁区时间被推迟了5分钟，你的武器与防具缔结了灵魂绑定且效能提升25%！{$wine_msg}</span><br>";

		addnoise($sn,'__',$now,$pls,0,0,$sn);
		addnews($now,'song',$name,$plsinfo[$pls],$song_title,$nick);
		return;
	}
	
//	if ($ss>=$r){
//		$ss-=$r;
//		$log.="消耗<span class=\"yellow\">{$r}</span>点歌魂，歌唱了<span class=\"yellow\">{$noiseinfo[$sn]}</span>。<br>";
//	}else{
//		$log.="需要<span class=\"yellow\">{$r}</span>歌魂才能唱这首歌！<br>";
//		return;
//	}

	
//	addnoise($sn,'__',$now,$pls,0,0,$sn);
//	addnews($now,'song',$name,$plsinfo[$pls],$noiseinfo[$sn]);
	return;
}
?>
