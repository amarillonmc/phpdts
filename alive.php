<?php

define('CURSCRIPT', 'alive');

require './include/common.inc.php';
//extract(gkillquotes($_POST));
//unset($_GET);

if(!isset($alivemode) || $alivemode == 'last'){
	$result = $db->query("SELECT * FROM {$tablepre}users RIGHT JOIN {$tablepre}players ON {$tablepre}players.name={$tablepre}users.username WHERE {$tablepre}players.type=0 AND {$tablepre}players.hp>0 ORDER BY {$tablepre}players.money DESC, {$tablepre}players.killnum DESC LIMIT $alivelimit");
}elseif($alivemode == 'all'){
	$result = $db->query("SELECT * FROM {$tablepre}users RIGHT JOIN {$tablepre}players ON {$tablepre}players.name={$tablepre}users.username WHERE {$tablepre}players.type=0 AND {$tablepre}players.hp>0 ORDER BY {$tablepre}players.money DESC, {$tablepre}players.killnum DESC");
}else{
	echo 'error';
	exit();
}
$alivedata = $apdata = Array();
while($apdata = $db->fetch_array($result)) {
	$apdata['iconImg'] = "{$apdata['gd']}_{$apdata['icon']}.gif";
	$apdata['winrate'] = $apdata['wingames'] ? round($apdata['wingames']/$apdata['validgames']*100).'%' : '0%';
	if (($apdata['endtime'] - $apdata['validtime'])>0) {
		$apdata['apm'] = round($apdata['cmdnum']/($apdata['endtime'] - $apdata['validtime']) * 60 * 1000)/1000;
	} else{
		$apdata['apm'] = 0;
	}
	//	$result3 = $db->query("SELECT motto FROM {$tablepre}users WHERE username = '".$apdata['name']."'");
//	$apdata['motto'] = $db->result($result3, 0);

	$alivedata[$apdata['pid']] = $apdata;
}

$adata = Array();
if($gamblingon){
	$gbinfo = '';
	$gbingdata = $gbeddata = $gambled = Array();
	$gbpool = 0;
	$nowodds = odds();
	//读取赌局信息
	$result2 = $db->query("SELECT * FROM {$tablepre}gambling WHERE 1");
	$gbnum = $db->num_rows($result2);
	if($gbnum){
		while($gbdata = $db->fetch_array($result2)) {
			$gbingdata[$gbdata['bid']][$gbdata['uid']] = $gbdata;
			$gbeddata[$gbdata['uid']] = $gbdata;
			$gbpool += $gbdata['wager'];
		}
	}
	foreach($alivedata as &$ad){
		$ad['gbnum'] = gbnum($ad);
		$ad['gbsum'] = gbsum($ad);
//		if($gbnum && isset($gbingdata[$ad['pid']])){
//			$ad['gbnum'] = count($gbingdata[$ad['pid']]);
//			$ad['gbsum'] = 0;
//			foreach($gbingdata[$ad['pid']] as $gad){
//				$ad['gbsum'] += $gad['wager'];
//			}			
//		}else{$ad['gbnum'] = 0;$ad['gbsum'] = 0;}
//		$ad['odds'] = podds($ad);
	}
	//判断是否满足下注条件
	if($cuser && $cpass){
		if($gamestate < 20) { $gbinfo .= $_ERROR['no_start']; }
		//elseif($now - $starttime >= 600) { $gbinfo .= '游戏开始超过10分钟，不可进行下注！'; }
		elseif($areanum >= $areaadd) { $gbinfo .= '游戏超过一禁，不可进行下注！'; }
		elseif($gamestate >= 30) { $gbinfo .= '游戏已停止激活，不可进行下注！'; }
		elseif($gbpool >= 8000 && $wager>50) { $gbinfo .= '本局总奖池已经超过8000切糕上限，此时每人最多只能下注50切糕！'; }
		else{
			$uresult = $db->query("SELECT * FROM {$tablepre}users WHERE username='$cuser'");
			if(!$db->num_rows($uresult)) { $gbinfo .= $_ERROR['login_check']; }
			else{
				$udata = $db->fetch_array($uresult);
				if($udata['password'] != $cpass) { $gbinfo .= $_ERROR['wrong_pw']; }
				elseif($udata['groupid'] <= 0) { $gbinfo .= $_ERROR['user_ban']; }
				elseif($alivenum <= 0){ $gbinfo .= '当前生存人数为0，无法下注！';}
				else{
					$uid = $udata['uid'];$uname = $udata['username'];
					$credits2 = $udata['credits2'];
					if($gbmode == 'gamble'){
						$wager = ceil((int)$wager);
						if(!$bet || $bet == 'none'){ $gbinfo .= '投注对象有误，请检查输入。';}
						elseif($wager <= 0){ $gbinfo .= '投注数额有误，请检查输入。';}
						elseif($wager > $credits2 || $wager > 1000 ){ $gbinfo .= '投注数额过大。每人每局最多只能投注总计不超过1000切糕。';}
						elseif ($gbpool >= 8000 && $wager > 50) { $gbinfo .= '本局总奖池已经超过8000切糕上限，此时每人最多只能下注50切糕！'; }
						else{
							$bet = (int)$bet;
							$bresult = $db->query("SELECT * FROM {$tablepre}players LEFT JOIN {$tablepre}users ON {$tablepre}players.name={$tablepre}users.username WHERE {$tablepre}players.pid='$bet'");
							if(!$db->num_rows($bresult)) { $gbinfo .= '投注对象不存在。'; }
							else{
								$bdata = $db->fetch_array($bresult);
								$bname = $bdata['name'];
								if($bdata['hp'] <= 0 || $bdata['state'] >= 10) {$gbinfo .= '投注对象已死亡，无法下注。'; }
								elseif($bdata['type'] >=1) {$gbinfo .= '投注对象不是人类！'; }
								elseif($gbnum && isset($gbeddata[$udata['uid']])){//已经下注
									$gbudata = $gbeddata[$udata['uid']];
									if ($gbudata['wager'] + $wager > 1000 ) 
									{
										$gbinfo .= '投注数额过大。每人每局最多只能投注总计不超过1000切糕。';
									}
									else if ($gbpool >= 8000 && $gbudata['wager'] + $wager > 50)
									{
										$gbinfo .= '本局总奖池已经超过8000切糕上限，此时每人最多只能下注50切糕！';
									}
									else if($gbudata['bid'] != $bet){$gbinfo .= '追加切糕的对象必须跟之前相同。';}
									else{
										$bwager = $gbudata['wager'] + $wager;
										$odds = ($gbudata['wager'] * $gbudata['odds'] + $nowodds * $wager)/$bwager;
										$db->query("UPDATE {$tablepre}gambling SET wager='$bwager',odds='$odds' WHERE uid='$uid'");
										if($db->affected_rows() == 1){
											$gbeddata[$udata['uid']]['wager']+=$wager;
											//$gbeddata[$udata['uid']]['odds']=$odds;
											$gbinfo .= '成功对'.$bname.'追加下注。';
											$credits2 -= $wager;
											if(isset($alivedata[$bet])){
												$alivedata[$bet]['gbsum']+=$wager;
												//$alivedata[$bet]['odds'] = podds($alivedata[$bet]);
											}
											
											$db->query("UPDATE {$tablepre}users SET credits2='$credits2' WHERE uid='$uid'");
										}else{$gbinfo .= '数据库错误，请联系管理员。';}									
									}
								}else{//未下注
									//$odds = podds($bdata);
									//echo $odds;
									$db->query("INSERT INTO {$tablepre}gambling (uid,uname,bid,bname,wager,odds) VALUES ('$uid','$uname','$bet','$bname','$wager','$nowodds')");
									if($db->affected_rows() == 1){
										$gbeddata[$udata['uid']]['wager']=$wager;
										$gbeddata[$udata['uid']]['bname']=$bname;
										//$gbeddata[$udata['uid']]['odds']=$odds;
										$gbinfo .= '成功对'.$bname.'下注。';
										$credits2 -= $wager;
										$gbnum++;
										if(isset($alivedata[$bet])){
											$alivedata[$bet]['gbnum']++;
											$alivedata[$bet]['gbsum']+=$wager;
											//$alivedata[$bet]['odds'] = podds($alivedata[$bet]);
										}
										
										$db->query("UPDATE {$tablepre}users SET credits2='$credits2' WHERE uid='$uid'");
									}else{$gbinfo .= '数据库错误，请联系管理员。';}
								}
							}
						}
					}
					if($gbnum && isset($gbeddata[$udata['uid']])){
						$gbudata = $gbeddata[$udata['uid']];
						$gbinfo .= '你已下注，对象为：'.$gbudata['bname'].'，切糕为：'.$gbudata['wager'].'；';
						//var_dump($gbeddata[$udata['uid']]);
						$gbact = 1;
					}else{
						$gbinfo .= '你尚未下注。';
						$gbact = 0;
					}					
				}
			}		
		}
		$adata['innerHTML']['gbinfo'] .= $gbinfo;
	}else{
		$gbinfo .= $_ERROR['no_login'];
	}
}

if(!isset($alivemode)){
	include template('alive');
}else{	
	include template('alivelist');
	$adata['innerHTML']['alivelist'] = ob_get_contents();
	if($gamblingon){
		$adata['innerHTML']['gbinfo'] = $gbinfo;
		if(isset($credits2)){$adata['innerHTML']['credits2'] = $credits2;}
	}
	if(isset($error)){$adata['innerHTML']['error'] = $error;}
	ob_clean();
	$jgamedata = compatible_json_encode($adata);
	echo $jgamedata;
	ob_end_flush();
}

//include template('alive');

function gbnum($pdata){
	global $gbnum,$gbingdata;
	if($gbnum && isset($gbingdata[$pdata['pid']])){
		return count($gbingdata[$pdata['pid']]);
	}else{return 0;}
}

function gbsum($pdata){
	global $gbnum,$gbingdata;
	if($gbnum && isset($gbingdata[$pdata['pid']])){
		$gbsum = 0;
		foreach($gbingdata[$pdata['pid']] as $gad){
			$gbsum += $gad['wager'];
		}
		return $gbsum;
	}else{return 0;}
}

function odds(){//判断赔率的
	global $validnum,$alivenum,$deathnum,$startime,$areanum,$areaadd,$now,$starttime;
	
//	$areaodds = 2/(1+$areanum/$areaadd);//0禁赔率奖励为2，1禁赔率奖励为1，逐步降低
	$pasttime = $now - $starttime;
	if($pasttime <= 180){$timeodds = 5;}//前3分钟系数为5；
	else{$timeodds = 5/($pasttime/180);}//系数趋近于0；
	
	$timeodds = round($timeodds * 100000)/100000;
//	$validodds = $validnum/100;//激活赔率；
//	$deathodds = $deathnum/400;//死亡赔率，增长很慢
//	$winrate = $pdata['validgames'] ? $pdata['wingames']/$pdata['validgames'] : 0;
//	$wrodds = 4*(0.5-$winrate);$wrodds = $wrodds < 0 ? 1 : $wrodds + 1;//胜率赔率倍数，0胜率是5，超过50%为1；
//	$gbsum = gbsum($pdata);
//	$wagerodds = (100-$gbsum)/100; $wagerodds = $wagerodds < 0 ? 1 : $wagerodds + 1;//投注的影响，0投注是2，超过100投注是1；
//	$odds = round((1 + $areaodds + $validodds + $deathodds)*$wrodds*$wagerodds*1000)/1000;

	return $timeodds;
}
?>
