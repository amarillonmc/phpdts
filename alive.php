<?php

define('CURSCRIPT', 'alive');

require './include/common.inc.php';
require './include/game/special.func.php';
//extract(gkillquotes($_POST));
//unset($_GET);

if(!isset($alivemode) || $alivemode == 'last'){
	//$result = $db->query("SELECT * FROM {$gtablepre}users RIGHT JOIN {$tablepre}players ON {$tablepre}players.name={$gtablepre}users.username WHERE {$tablepre}players.type=0 AND {$tablepre}players.hp>0 ORDER BY {$tablepre}players.money DESC, {$tablepre}players.killnum DESC LIMIT $alivelimit");
	$result = $db->query("SELECT * FROM {$tablepre}players WHERE {$tablepre}players.type=0 AND {$tablepre}players.hp>0 ORDER BY {$tablepre}players.money DESC, {$tablepre}players.killnum DESC LIMIT $alivelimit");
}elseif($alivemode == 'all'){
	//$result = $db->query("SELECT * FROM {$gtablepre}users RIGHT JOIN {$tablepre}players ON {$tablepre}players.name={$gtablepre}users.username WHERE {$tablepre}players.type=0 AND {$tablepre}players.hp>0 ORDER BY {$tablepre}players.money DESC, {$tablepre}players.killnum DESC");
	$result = $db->query("SELECT * FROM {$tablepre}players WHERE {$tablepre}players.type=0 AND {$tablepre}players.hp>0 ORDER BY {$tablepre}players.money DESC, {$tablepre}players.killnum DESC");
}else{
	echo 'error';
	exit();
}
$alivedata = $apdata = Array();
while($apdata = $db->fetch_array($result)) 
{
	$uresult = $db->query("SELECT * FROM {$gtablepre}users WHERE username = '{$apdata['name']}'");
	$urdata = $db->fetch_array($uresult);
	$apdata = array_merge($urdata,$apdata);
	unset($uresult); unset($urdata);
	$apdata['iconImg'] = "{$apdata['gd']}_{$apdata['icon']}.gif";
	$apdata['winrate'] = $apdata['wingames'] ? round($apdata['wingames']/$apdata['validgames']*100).'%' : '0%';
	if (($apdata['endtime'] - $apdata['validtime'])>0) {
		$apdata['apm'] = round($apdata['cmdnum']/($apdata['endtime'] - $apdata['validtime']) * 60 * 1000)/1000;
	} else{
		$apdata['apm'] = 0;
	}
	//	$result3 = $db->query("SELECT motto FROM {$gtablepre}users WHERE username = '".$apdata['name']."'");
//	$apdata['motto'] = $db->result($result3, 0);

	$alivedata[$apdata['pid']] = $apdata;
}

$adata = Array(); if(!isset($gbmode)) $gbmode = 'none';
if($gamblingon){
	global $gshoplist,$credits2_values,$no_self_sponsored,$sponsor_title,$gnpctype,$gnpcsub;
	//初始化赌局变量
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
	if($cuser && $cpass)
	{
		if($gamestate < 20) { $gbinfo .= $_ERROR['no_start']; }
		elseif($gbmode=='gamble' && $now - $starttime >= 600) { $gbinfo .= '游戏开始超过10分钟，不可进行下注！'; }
		elseif($gbmode=='gamble' && $areanum >= $areaadd) { $gbinfo .= '游戏超过一禁，不可进行下注！'; }
		elseif($gamestate >= 30) { $gbinfo .= '游戏已停止激活，不可进行下注或赞助！'; }
		elseif($gbpool >= 8000 && $wager>50) { $gbinfo .= '本局总奖池已经超过8000切糕上限，此时每人最多只能下注50切糕！'; }
		else
		{
			$uresult = $db->query("SELECT * FROM {$gtablepre}users WHERE username='$cuser'");
			if(!$db->num_rows($uresult)) 
			{ 
				$gbinfo .= $_ERROR['login_check']; 
			}
			else
			{
				$udata = $db->fetch_array($uresult);
				if($udata['password'] != $cpass) { $gbinfo .= $_ERROR['wrong_pw']; }
				elseif($udata['groupid'] <= 0) { $gbinfo .= $_ERROR['user_ban']; }
				elseif($alivenum <= 0){ $gbinfo .= '当前生存人数为0，无法下注！<br>';}
				else
				{
					$uid = $udata['uid'];$uname = $udata['username'];
					$credits2 = $udata['credits2'];
					if(isset($gbmode) && $gbmode!=='none')
					{
						//脑瓜子嗡嗡的……连goto都用上了
						if(!$bet || $bet == 'none')
						{ 
							$gbinfo .= '选择对象有误，请检查输入。<br>'; 
							goto gb_result;
						}
						$bresult = $db->query("SELECT * FROM {$tablepre}players LEFT JOIN {$gtablepre}users ON {$tablepre}players.name={$gtablepre}users.username WHERE {$tablepre}players.pid='$bet'");
						if(!$db->num_rows($bresult)) 
						{ 
							$gbinfo .= '选择的对象不存在。<br>'; 
							goto gb_result;
						}
						$bdata = $db->fetch_array($bresult);
						if($bdata['hp'] <= 0 || $bdata['state'] >= 10) 
						{
							$gbinfo .= '选择的对象已死亡，无法下注或赞助。'; 
							goto gb_result;
						}
						if($bdata['type'] >=1) 
						{
							$gbinfo .= '选择的对象不是玩家！'; 
							goto gb_result;
						}
						$bet = (int)$bet;
						$bname = $bdata['name']; $bid = $bdata['pid'];
						$gbudata = $gbeddata[$udata['uid']];
						if($no_self_sponsored && $bname==$uname) 
						{
							$gbinfo .= '不能给自己下注或赞助！<br>'; 
							goto gb_result;
						}
						if($gbmode == 'gsponsor')
						{
							//使用切糕点外卖
							if (!empty($gbudata['bitm']) || !empty($gbudata['bnid']) ) 
							{
								$gbinfo .= '你派出去的快递员还没回来，耐心等等吧！<br>'; 
								goto gb_result;
							}
							$iresult=$db->query("SELECT * FROM {$tablepre}shopitem WHERE sid = '$gbid'");
							$iteminfo = $db->fetch_array($iresult);
							$bnum = (int)$gbinum;
							if(!$iteminfo) 
							{
								$gbinfo .= '要购买的道具不存在！<br><br>';
								goto gb_result;
							}
							if($iteminfo['num'] <= 0) {
								$gbinfo .= '此物品已经售空！<br><br>';
								goto gb_result;
							} elseif($bnum<=0) {
								$gbinfo .= '购买数量必须为大于0的整数。<br><br>';
								goto gb_result;
							} elseif($bnum>$iteminfo['num']) {
								$gbinfo .= '购买数量必须小于存货数量。<br><br>';
								goto gb_result;
							} elseif($credits2*$credits2_values < $iteminfo['price']*$bnum) {
								$gbinfo .= '切糕不足，不能购买此物品！<br><br>';
								goto gb_result;
							} elseif(!preg_match('/^(WC|WD|WF|Y|B|C|TN|GB|H|V|M)/',$iteminfo['itmk'])&&$bnum>1) {
								$gbinfo .= '此物品一次只能购买一个。<br><br>';
								goto gb_result;
							}elseif($iteminfo['area']> $areanum/$areaadd){
								$gbinfo .= '此物品尚未开放出售！<br><br>';
								goto gb_result;
							}
							$inum = $iteminfo['num']-$bnum;
							$sid = $iteminfo['sid'];
							//扣除商店库存
							$db->query("UPDATE {$tablepre}shopitem SET num = '$inum' WHERE sid = '$sid'");
							//扣除切糕
							$cost_credits2 = round($iteminfo['price']*$bnum/$credits2_values);
							$credits2 -= $cost_credits2;
							$db->query("UPDATE {$gtablepre}users SET credits2='$credits2' WHERE uid='$uid'");
							//发news
							$gbinfo .= "花费{$cost_credits2}切糕购买了{$bnum}份{$iteminfo['item']}。<br>";
							addnews($now,'gpost',$sponsor_title.' '.$udata['username'],$iteminfo['item'],$bdata['nick'].' '.$bdata['name'],$bdata['pls'],$bnum);
							//打包快递给快递员 返回新生成的快递员pid
							$gclb = Array('clbpara'=>Array('sponsor'=>$uid,'post'=>$bet,'postid'=>6),); //记录赞助者的uid、收货方的pid、道具位置
							$gitem = Array(6,$iteminfo['item'],$iteminfo['itmk'],$iteminfo['itme'],$iteminfo['itms']*$bnum,$iteminfo['itmsk']);//打包快递
							//include_once GAME_ROOT.'./include/system.func.php';
							$nid = addnpc($gnpctype,$gnpcsub,1,$now,$gclb,$gitem,$bdata['pls'])[0];
							$gbinfo .= "快递员已带着你赞助的商品前往{$bdata['name']}所在的位置！谢谢惠顾~<br>";
							//存一条发快递记录到gambling表里，一个玩家在快递被接收前不能发第二份快递。防止有人狂买低价商品挤爆players表。
							//有过投注记录
							if($gbnum && isset($gbeddata[$udata['uid']])) $db->query("UPDATE {$tablepre}gambling SET bnid='$nid' WHERE uid='$uid'");
							//没有投注记录，新生成一条
							else $db->query("INSERT INTO {$tablepre}gambling (uid,uname,bnid) VALUES ('$uid','$uname','$nid')");
						}
						elseif($gbmode == 'gamble')
						{
							//使用切糕刚不灵
							$wager = ceil((int)$wager);
							if($wager > $credits2 || $wager > 1000 ){ $gbinfo .= '投注数额过大。每人每局最多只能投注总计不超过1000切糕。';}
							elseif ($gbpool >= 8000 && $wager > 50) { $gbinfo .= '本局总奖池已经超过8000切糕上限，此时每人最多只能下注50切糕！'; }
							else
							{
								if($wager <= 0){ $gbinfo .= '投注数额有误，请检查输入。';}
								elseif($gbnum && isset($gbeddata[$udata['uid']])){//已经下注
									if ($gbudata['wager'] + $wager > 1000 ) 
									{
										$gbinfo .= '投注数额过大。每人每局最多只能投注总计不超过1000切糕。';
									}
									else if ($gbpool >= 8000 && $gbudata['wager'] + $wager > 50)
									{
										$gbinfo .= '本局总奖池已经超过8000切糕上限，此时每人最多只能下注50切糕！';
									}
									else if($gbudata['bid'] != $bet && $gbudata['bid'] != 0){$gbinfo .= '追加切糕的对象必须跟之前相同。';}
									else{
										$bwager = $gbudata['wager'] + $wager;
										$odds = ($gbudata['wager'] * $gbudata['odds'] + $nowodds * $wager)/$bwager;
										$db->query("UPDATE {$tablepre}gambling SET bid='$bet',bname='$bname',wager='$bwager',odds='$odds' WHERE uid='$uid'");
										if($db->affected_rows() == 1){
											$gbeddata[$udata['uid']]['wager']+=$wager;
											//$gbeddata[$udata['uid']]['odds']=$odds;
											$gbinfo .= '成功对'.$bname.'追加下注。';
											$credits2 -= $wager;
											if(isset($alivedata[$bet])){
												$alivedata[$bet]['gbsum']+=$wager;
												//$alivedata[$bet]['odds'] = podds($alivedata[$bet]);
											}
											
											$db->query("UPDATE {$gtablepre}users SET credits2='$credits2' WHERE uid='$uid'");
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
										
										$db->query("UPDATE {$gtablepre}users SET credits2='$credits2' WHERE uid='$uid'");
									}else{$gbinfo .= '数据库错误，请联系管理员。';}
								}
							}
						}
					}
					gb_result:
					if($gbnum && isset($gbeddata[$udata['uid']])){
						$gbudata = $gbeddata[$udata['uid']];
						if($gbudata['wager']>0) $gbinfo .= '<span class=\'yellow\'>你已下注，对象为：'.$gbudata['bname'].'，下注的切糕为：'.$gbudata['wager'].'；</span><br>';
						if($gbudata['bnid']>0) $gbinfo .= '<span class=\'yellow\'>你赞助的包裹尚在运输途中！</span><br>';
						$gbact = 1;
					}else{
						$gbinfo .= '<span class=\'grey\'>你尚未下注或投资。</span>';
						$gbact = 0;
					}
				}
			}		
		}
		$adata['innerHTML']['gbinfo'] = $gbinfo;
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
