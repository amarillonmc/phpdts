<?php
if(!defined('IN_GAME')) {
	exit('Access Denied');
}

function aievent($rate){
	global $log,$now,$plsinfo,$db,$tablepre,$gamevars,$validnum,$chatlimit;
	global $validtime,$killnum;
	
	$sanmachat = Array(
		'showup' => Array('不需要伪装了，上吧。','我是静流，任务开始。'),
		'selfbuff' => Array('……这个叫做拍BUFF……！','进行自我强化！'),
		'selfcure' => Array('进行自我回复。','一般人大概是活不下刚才那一下的，但是我不是一般人。','下次再取你的小命！'),
		'move' => Array('我已抵达[pls]，开始执行任务。','目标地点：[pls]。开始搜索。','在[pls]发现触手，进入警戒模式。'),
		'move2' => Array('非目标范围。回避至[pls]。','暂时回避。已抵达[pls]。','上一地点会误伤无辜，我已回避至[pls]。','不能加害无辜的人……目前我位于[pls]。'),
		'combat' => Array('……歼灭[plyr]。','[plyr]，吃招！','目标：[plyr]，抹杀开始！'),
		'itm' => Array('[itm]位于[plss]。','在[plss]地点发现[itm]。','[itm]存在于[plss]。'),
		'unfound' => Array('……[unfound]在地图上不存在。欺骗风纪委员是违反校规的。','找不到这个物品啊：[unfound]……要我做一个出来么？'),
	);
	
	//echo "进入AIEVENT";
	//TESTCASE: IF GOLDEN MINION SURVIVES: DO NOTHING.
	if(!isset($gamevars['sanmaact']) && !isset($gamevars['sanmadead'])){//$sanmaact = 0表示静流没放出，需要判断小兵状态，$sanmaact = 1表示静流已放出
		$checkMinionSurvive = "SELECT * FROM {$tablepre}players WHERE `type` =91 AND `name` = 'AC专业职人'";
		$ifMinionSurvive = $db->query($checkMinionSurvive);
		$minionHP = $db->fetch_array($ifMinionSurvive);
		//echo "成功获得$minionHP";
		if ($minionHP ['hp'] > 0){
			//echo "我是黄金小兵，我还活着。";
			//$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('0','$now','【AIDEBUG】','$plsinfo','我是黄金小兵，我还活着。')");
		}else{
			//echo "我是黄金小兵，我死了！放一只静流。";
			$cht = $sanmachat['showup']; shuffle($cht); $cht = $cht[0]; 
			$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('2','$now','【SANMA_TK】','','$cht')");
			include_once GAME_ROOT . './include/system.func.php';
			addnpc(15, 0, 1);
			$gamevars['sanmaact'] = 1;
			save_gameinfo();
		}
	}elseif(!isset($gamevars['sanmadead'])){
		
		//echo "静流已放出。";
		$checkSanma = $db->query("SELECT * FROM {$tablepre}players WHERE type = 15 AND name = '【SANMA_TK】'");
		$sdata = $db->fetch_array($checkSanma);
		# 不准直接从数据库拉玩家数据了
		$spid = $sdata['pid'];
		$sdata = fetch_playerdata_by_pid($spid);
//		$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('2','$now','SANMA_TK','$splsinfo','……静流已抵达{$splsinfo}并待机。')");
		if($sdata['hp'] <= 0){//若静流已死则中止循环，更新标签
			$gamevars['sanmadead'] = 1;
			save_gameinfo();
		}	elseif($validnum > 1){//AI在激活人数多于x人时才开始运行
			global $pls,$pid,$name,$rp,$killnum,$state;
			global $aidata;
			$updflag = false;
			$rpqrylimit = round($validnum/2);//静流回避的对象是(RP/游戏时间)的比值平均线以下的玩家
			if($rpqrylimit <= 1){$rpqrylimit = 1;}
			$result = $db->query("SELECT pid,name,rp,validtime FROM {$tablepre}players WHERE type = 0 AND hp > 0 ORDER BY (rp / ($now - validtime)) DESC LIMIT $rpqrylimit");
			$rptopdata = $db->fetch_array($result);
			$rplist = Array();
			while($rpr = $db->fetch_array($result)){
				$rplist['pid'] = $rpr;
			}
			$rplist[$rptopdata['pid']] = $rptopdata;
			
			/*if(!$sdata['achievement']){//AI一些信息的储存位置
				$sdata['achievement'] = Array('chatid' => 0, 'curenum' => 0);
			}else{
				$sdata['achievement'] = json_decode($sdata['achievement'],true);
			}
			$svars = & $sdata['achievement'];*/

			# AI信息现在储存在clbpara内
			if(empty($sdata['clbpara']['chatid'])) $sdata['clbpara']['chatid'] = 0;
			if(empty($sdata['clbpara']['curenum'])) $sdata['clbpara']['curenum'] = 0;
			$svars = & $sdata['clbpara'];
			$chatid = & $svars['chatid'];
			$curenum = & $svars['curenum'];

			//AI聊天卖萌部分
			//AI读取聊天记录
			if(!$chatid){$chatid = 0;}
			$chatdata = Array();
			$chatid = (int)$chatid; $chatlimit = (int)$chatlimit;
			$result = $db->query("SELECT * FROM {$tablepre}chat WHERE cid>$chatid AND send != '【SANMA_TK】' AND type='0' ORDER BY cid DESC LIMIT $chatlimit");//静流自动忽略$chatlimit条以外的聊天记录
			$nowchatid = 0;
			while($chat = $db->fetch_array($result)) {
				$chatdata[] = $chat;
				$nowchatid = $chat['cid'];
			}
			if($nowchatid > $chatid){
				$chatid = $nowchatid;
				$updflag = true;
			}
			//$keyflag = $yellowflag = false;
			$checkcdata = Array();
			
			if(!empty($chatdata)){
				foreach($chatdata as $val){
					if((strpos($val['msg'],'静流')!==false || strpos($val['msg'],'SANMA')!==false) && (strpos($val['msg'],'位置')!==false || strpos($val['msg'],'哪')!==false)){//聊天记录中存在对位置的询问
						$checkcdata[] = $val;
					}
				}
			}
			//$ylwdh = $ylwda = $ylwdf = $key1 = $key2 = $key3 = $key4 = $key5 = $key6 = $keymd1 = $keymd2 = false;
			$prcslist = Array(
				'草帽' => '《小黄的草帽》', '钓鱼竿' => '《小黄的钓鱼竿》', '行军靴' => '《小黄的行军靴》',
				'月宫 亚由' => '月宫 亚由的半身像', '神尾 观铃' => '神尾 观铃的半身像', '古河 渚' => '古河 渚的半身像',
				'天泽 郁末' => '天泽 郁末的半身像', '长森 瑞佳' => '长森 瑞佳的半身像', '枣 铃' => '枣 铃的半身像', 
				'咏叹调' => '四季流转的咏叹调', '覆唱诗' => '旁观轮回的覆唱诗', 
			);
			
			$checkitms = Array();
			if(!empty($checkcdata)){
				foreach($prcslist as $pkey => $pval){
					if(strpos($pkey,' ')!==false){//有空格分开判断
						list($pkey1,$pkey2) = explode(' ',$pkey);
						foreach($checkcdata as $val){
							if(strpos($val['msg'],$pkey1)!==false && strpos($val['msg'],$pkey2)!==false){
								$checkitms[] = $pval;
								break;
							}
						}						
					}else{
						foreach($checkcdata as $val){
							if(strpos($val['msg'],$pkey)!==false){
								$checkitms[] = $pval;
								break;
							}
						}						
					}					
				}				
			}
			if(!empty($checkitms)){
				//查询物品位置
				$qrywhere = '';
				foreach($checkitms as $val){
					$qrywhere .= "'".$val."',";
				}
				$qrywhere = '('.substr($qrywhere,0,-1).')';
				$result = $db->query("SELECT * FROM {$tablepre}mapitem WHERE itm IN $qrywhere");
				$itmdata = array();
				while($idata = $db->fetch_array($result)){
					$itmdata[$idata['itm']][] = $idata['pls'];
				}
				//物品位置转化成聊天消息
				$itmchat = array();
				if(!empty($itmdata)){
					foreach($itmdata as $itm => $ipls){
						$cht = $sanmachat['itm']; shuffle($cht); $cht = $cht[0]; 
						$cht = str_replace('[itm]',$itm,$cht);
						$plss = '';
						$pvallist = Array();
						foreach($ipls as $pval){
							if(!in_array($pval,$pvallist)){
								$plss .= $plsinfo[$pval].',';
								$pvallist[] = $pval;
							}							
						}
						$cht = str_replace('[plss]',substr($plss,0,-1),$cht);
						$itmchat[] = Array('type' => '2', 'time' => $now, 'send' => '【SANMA_TK】', 'msg' => $cht);
					}
				}
				//未发现物品则卖萌
				$unfounditms = array_diff($checkitms,array_keys($itmdata));
				//var_dump($unfounditms);
				if(!empty($unfounditms)){
					
					$unfound = '';
					foreach($unfounditms as $uval){
						$unfound .= $uval.',';					
					}
					$cht = $sanmachat['unfound']; shuffle($cht); $cht = $cht[0]; 
					$cht = str_replace('[unfound]',substr($unfound,0,-1),$cht);
					$itmchat[] = Array('type' => '2', 'time' => $now, 'send' => '【SANMA_TK】', 'msg' => $cht); 
				}
				if(!empty($itmchat)){
					foreach($itmchat as $ickey => $icvalues)
					{
						$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('{$icvalues['type']}','{$icvalues['time']}','{$icvalues['send']}','','{$icvalues['msg']}')");
					}
					//$db->multi_insert("{$tablepre}chat",$itmchat);
				}
			}
			
			
			//AI实际行动部分
			if($sdata['hp'] <= $sdata['mhp'] * 0.75){//AI优先补血
				
				$curenum ++;
				if(rand(0,4) < $curenum){//AI被打伤时有概率给自己上BUFF
					$curenum = 0;
					$sdata['hp'] = $sdata['mhp'] = round($sdata['mhp']*1.05);//HP上限+5%
					$sdata['att'] = round($sdata['att']*1.1);//攻防+10%
					$sdata['def'] = round($sdata['def']*1.1);
					foreach(array('wp','wk','wg','wc','wd','wf') as $val){
						$sdata[$val] = round($sdata[$val]*1.1);//全系熟练度+10%
					}
					$cht = $sanmachat['selfbuff'];
				}else{
					$sdata['hp'] = $sdata['mhp'];
					$cht = $sanmachat['selfcure'];//其他情况只是一般的回复
				}
				$updflag = true;
				shuffle($cht); $cht = $cht[0];
				$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('2','$now','【SANMA_TK】','','{$cht}')");				
				
			}elseif(rand(0,99) < $rate && in_array($pid,array_keys($rplist)) && $rp >= 50){//如果玩家的RP比值在平均线上，那么静流以玩家为目标$rptopdata['pid'] == $pid && 
				$mvflg = $cmbtflg = false;
				//RP/游戏时间比值的判断
				$myrprate = $rp / ($now - $validtime);
				$rpratesafe = 0.6;//安全值，暂时设0.6，大致相当于30分钟杀60个兵。
				$rpratelvl = 0.8;//80%被跟踪
				$rpratelvl2 = 1.0;//80%被直接切入战斗
				$mvrate = round(($myrprate - $rpratesafe) / ($rpratelvl - $rpratesafe) * 80);if($mvrate > 80){$mvrate = 80;}elseif($mvrate < 0){$mvrate = 0;}
				//$log.= '跟踪'.$mvrate;
				$cmbtrate = round(($myrprate - $rpratelvl) / ($rpratelvl2 - $rpratelvl) * 80);if($cmbtrate > 80){$cmbtrate = 80;}elseif($cmbtrate < 0){$cmbtrate = 0;}
				//$log.= '战斗'.$cmbtrate;
//				$mvrplvl = 500;//RP在500以上时最高80%触发静流移动
//				$cmbtrplvl = 1000;//RP在1000以上时最高80%触发静流攻击（前提是静流跟你在一个地图）
//				$mvrate = round($rp / $mvrplvl * 80);if($mvrate > 80){$mvrate = 80;}
//				$cmbtrate = round(($rp - 500) / ($cmbtrplvl - $mvrplvl) * 80);if($cmbtrate > 80){$cmbtrate = 80;}
				$dice = rand(0,99);
				if($dice < $mvrate){
					if($sdata['pls'] != $pls){
						$mvflg = true;
						$sdata['pls'] = $pls;//静流移动
					}
					if($dice < $cmbtrate && $sdata['pls'] == $pls && $rptopdata['pid'] == $pid && $killnum > 0){//注意：【划掉】这里暗含：静流刚移到此地点时不会立刻攻击【/划掉】有情无用！
						$cmbtflg = true;
					}
				}
				
				if($mvflg){
					//$sdata['pls'] = $pls;//静流移动
					$sdata['pose'] = 2;$sdata['tactic'] = 3;//静流姿态变为强袭+反击
					//$sdata['l']
					$updflag = true;
					//$db->array_update("{$tablepre}players", $sdata, " pid = '$spid'");//先更为敬，虽然其实可以巧妙构筑流程减少这一次更新
					$splsinfo = $plsinfo[$sdata['pls']];
					$cht = $sanmachat['move']; shuffle($cht); $cht = $cht[0]; $cht = str_replace('[pls]',$splsinfo,$cht);
					$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('2','$now','【SANMA_TK】','','{$cht}')");
				}
				if($cmbtflg){
					//if(is_array($sdata['achievement'])){$sdata['achievement'] = json_encode($sdata['achievement']);}
					$aidata = $sdata;
					//echo '静流开始对你实施追击。';
					$sanmams = $name;
					$cht = $sanmachat['combat']; shuffle($cht); $cht = $cht[0]; $cht = str_replace('[plyr]',$sanmams,$cht);
					$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('2','$now','【SANMA_TK】','','{$cht}')");
				}
			}	elseif(!in_array($pid,array_keys($rplist)) || $rp < 50) {//如果玩家RP位于平均线以下，那么静流会回避玩家
				//echo '进入躲避判断';
				$newsflag = false;
				if($sdata['pls'] == $pls){//静流和玩家位置相同
					$sdata['pose'] = 0;$sdata['tactic'] = 4;//静流姿态变为普通+躲避
					if(rand(0,99) < 50){//50%概率移动到别的位置
						while($sdata['pls'] == $pls){
							$sdata['pls'] = rand(1,count($plsinfo)-1);
						}
						$newsflag = true;
					}
					$updflag = true;
				}
				
				if($newsflag){
					$splsinfo = $plsinfo[$sdata['pls']];
					$cht = $sanmachat['move2']; shuffle($cht); $cht = $cht[0]; $cht = str_replace('[pls]',$splsinfo,$cht);
					$db->query("INSERT INTO {$tablepre}chat (type,`time`,send,recv,msg) VALUES ('2','$now','【SANMA_TK】','','{$cht}')");
				}
			}
			if($updflag)
			{
				//if(is_array($sdata['achievement'])){$sdata['achievement'] = json_encode($sdata['achievement']);}
				//$db->array_update("{$tablepre}players", $sdata, " pid = '$spid'");
				player_save($sdata);
			}	
		}
	}
}
?>
