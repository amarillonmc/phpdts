<?php

define('CURSCRIPT', 'game');

require './include/common.inc.php';
//$t_s=getmicrotime();
//require_once GAME_ROOT.'./include/JSON.php';
require GAME_ROOT.'./include/game.func.php';
require config('combatcfg',$gamecfg);

//判断是否进入游戏
if(!$cuser||!$cpass) { gexit($_ERROR['no_login'],__file__,__line__); } 

$result = $db->query("SELECT * FROM {$tablepre}players WHERE name = '$cuser' AND type = 0");

if(!$db->num_rows($result)) { header("Location: valid.php");exit(); }

$pdata = $db->fetch_array($result);

//判断是否密码错误
if($pdata['pass'] != $cpass) {
	$tr = $db->query("SELECT `password` FROM {$tablepre}users WHERE username='$cuser'");
	$tp = $db->fetch_array($tr);
	$password = $tp['password'];
	if($password == $cpass) {
		$db->query("UPDATE {$tablepre}players SET pass='$password' WHERE name='$cuser'");
	} else {
		gexit($_ERROR['wrong_pw'],__file__,__line__);
	}
}

//判断游戏状态和玩家状态，如果符合条件则忽略指令
if($gamestate == 0) {
	$gamedata['url'] = 'end.php';
	ob_clean();
	$jgamedata = compatible_json_encode($gamedata);
	echo $jgamedata;
	ob_end_flush();
	exit();
}

//初始化各变量
extract($pdata,EXTR_REFS);
$log = $cmd = $main = '';
$gamedata = array();
init_playerdata();

//读取玩家互动信息
$result = $db->query("SELECT lid,time,log FROM {$tablepre}log WHERE toid = '$pid' AND prcsd = 0 ORDER BY time,lid");
$llist = '';
while($logtemp = $db->fetch_array($result)){
	$log .= date("H:i:s",$logtemp['time']).'，'.$logtemp['log'].'<br />';
	$llist .= $logtemp['lid'].',';
}
if(!empty($llist)){
	$llist = '('.substr($llist,0,-1).')';
	$db->query("UPDATE {$tablepre}log SET prcsd=1 WHERE toid = '$pid' AND lid IN $llist");
}

//var_dump($_POST);
if($hp > 0){
	//显示枪声信息
	if(($now <= $noisetime+$noiselimit)&&$noisemode&&($noiseid!=$pid)&&($noiseid2!=$pid)) {
		if(($now-$noisetime) < 60) {
			$noisesec = $now - $noisetime;
			$log .= "<span class=\"yellow b\">{$noisesec}秒前，{$plsinfo[$noisepls]}传来了{$noiseinfo[$noisemode]}。</span><br>";
		} else {
			$noisemin = floor(($now-$noisetime)/60);
			$log .= "<span class=\"yellow b\">{$noisemin}分钟前，{$plsinfo[$noisepls]}传来了{$noiseinfo[$noisemode]}。</span><br>";
		}
	}
	
	if ($club==0 && !isset($clubavl))
	{
		include_once GAME_ROOT.'./include/game/clubslct.func.php';
		getclub($name,$c1,$c2,$c3);
		$clubavl[0]=0; $clubavl[1]=$c1; $clubavl[2]=$c2; $clubavl[3]=$c3;
	}
	
	//判断冷却时间是否过去
	if($coldtimeon){
		$cdover = $cdsec*1000 + $cdmsec + $cdtime;
		$nowmtime = floor(getmicrotime()*1000);
		$rmcdtime = $nowmtime >= $cdover ? 0 : $cdover - $nowmtime;
	}
	
	if($coldtimeon && $rmcdtime > 0 && (strpos($command,'move')===0 || strpos($command,'search')===0 || (strpos($command,'itm')===0)&&($command != 'itemget') || strpos($sp_cmd,'sp_weapon')===0 || strpos($command,'song')===0)){
		$log .= '<span class="yellow">冷却时间尚未结束！</span><br>';
		$mode = 'command';
	}else{
		//进入指令判断
		if($mode !== 'combat' && $mode !== 'corpse' && strpos($action,'pacorpse')===false && $mode !== 'senditem'){
			$action = '';
		}
		if($command == 'menu') {
			$mode = 'command';
			$action = '';
		} elseif($mode == 'command') {
			if($command == 'move') {
				include_once GAME_ROOT.'./include/game/search.func.php';
				move($moveto);
				if($coldtimeon){$cmdcdtime=$movecoldtime;}
			} elseif($command == 'search') {
				include_once GAME_ROOT.'./include/game/search.func.php';
				search();
				if($coldtimeon){$cmdcdtime=$searchcoldtime;}
			} elseif(strpos($command,'itm') === 0) {
				include_once GAME_ROOT.'./include/game/item.func.php';
				$item = substr($command,3);
				itemuse($item);
				if($coldtimeon){$cmdcdtime=$itemusecoldtime;}
			} elseif(strpos($command,'rest') === 0) {
				if($command=='rest3' && !in_array($pls,$hospitals)){
					$log .= '<span class="yellow">你所在的位置并非医院，不能静养！</span><br>';
				}else{
					$state = substr($command,4,1);
					$mode = 'rest';
				}
			} elseif($command == 'itemmain') {
				$mode = $itemcmd;
			} elseif($command == 'song') {
				$sname=trim(trim($art,'【'),'】');
				include_once GAME_ROOT.'./include/game/song.inc.php';
				//$log.=$sname;
				sing($sname);
			}elseif($command == 'special') {
				if($sp_cmd == 'sp_word'){
					include_once GAME_ROOT.'./include/game/special.func.php';
					getword();
					$mode = $sp_cmd;
				}elseif($sp_cmd == 'sp_adtsk'){
					include_once GAME_ROOT.'./include/game/special.func.php';
					adtsk();
					$mode = 'command';
				}elseif($sp_cmd == 'sp_trapadtsk'){
					$position = 0;
					if ($club==7)
					{	
						foreach(Array(1,2,3,4,5,6) as $imn)
							if(strpos(${'itmk'.$imn},'B')===0 && ${'itme'.$imn} > 0 ){
								$position = $imn;
								break;
							}
						if (!$position) 
						{
							$log .= '<span class="red">你没有电池，无法改造陷阱！</span><br />';
							$mode = 'command';
						}
					}
					else  if ($club==8)
					{
						foreach(Array(1,2,3,4,5,6) as $imn)
							if(${'itm'.$imn} == '毒药' && ${'itmk'.$imn} == 'Y' && ${'itme'.$imn} > 0 ){
								$position = $imn;
								break;
							}
						if (!$position) 
						{
							$log .= '<span class="red">你没有毒药，无法改造陷阱！</span><br />';
							$mode = 'command';
						}
					}
					else  
					{
						$log .= '<span class="red">你不懂得如何改造陷阱！</span><br />';
						$mode = 'command';
					}
					if ($position)
					{
						$position = 0;
						foreach(Array(1,2,3,4,5,6) as $imn)
							if(strpos(${'itmk'.$imn},'T')===0 && ${'itme'.$imn} > 0 ){
								$position = $imn;
								break;
							}
						if (!$position)
						{
							$log .= '<span class="red">你的背包中没有陷阱，无法改造！</span><br />';
							$mode = 'command';
						}
						else  $mode = 'sp_trapadtsk';
					}
				}elseif($sp_cmd == 'sp_trapadtskselected'){
					if (!isset($choice) || $choice=='menu')
					{
						$mode='command';
					}
					else
					{
						$choice=(int)$choice;
						if ($choice<1 || $choice>6)
							$log.='<span class="red">无此物品。</span><br />';
						else
						{
							include_once GAME_ROOT.'./include/game/special.func.php';
							trap_adtsk($choice);
						}
						$mode='command';
					}
				}elseif($sp_cmd == 'sp_pbomb'){
					$mode = 'sp_pbomb';
				}elseif($sp_cmd == 'sp_weapon'){
					include_once GAME_ROOT.'./include/game/special.func.php';
					weaponswap();
					$mode = 'command';
					if($coldtimeon){$cmdcdtime=$weaponswapcoldtime;}
				}elseif($sp_cmd == 'oneonone'){
					$mode='oneonone';
				}elseif($sp_cmd == 'sp_skpts'){
					include_once GAME_ROOT.'./include/game/clubskills.func.php';
					calcskills($skarr);
					$p12[1]=1; $p12[2]=2;
					$mode='sp_skpts';
				}else{
					$mode = $sp_cmd;
				}
				
			} elseif($command == 'team') {
				include_once GAME_ROOT.'./include/game/team.func.php';
				if($teamcmd == 'teamquit') {				
					teamquit();
				} else{
					teamcheck();
				}
			}
		} elseif($mode == 'item') {
			include_once GAME_ROOT.'./include/game/item2.func.php';
			$item = substr($command,3);
			use_func_item($usemode,$item);
		} elseif($mode == 'itemmain') {
			include_once GAME_ROOT.'./include/game/itemmain.func.php';
			if($command == 'itemget') {
				itemget();
			} elseif($command == 'itemadd') {
				itemadd();
			} elseif($command == 'itemmerge') {
				if($merge2 == 'n'){itemadd();}
				else{itemmerge($merge1,$merge2);}
			} elseif($command == 'itemmove') {
				itemmove($from,$to);
			} elseif(strpos($command,'drop') === 0) {
				$drop_item = substr($command,4);
				itemdrop($drop_item);
			} elseif(strpos($command,'off') === 0) {
				$off_item = substr($command,3);
				itemoff($off_item);
				//itemadd();
			} elseif(strpos($command,'swap') === 0) {
				$swap_item = substr($command,4);
				itemdrop($swap_item);
				itemadd();
			} elseif($command == 'itemmix') {
				if (isset($itemselect) && $itemselect==999)
					$mode='command';
				else
				{
					$mixlist = array();
					if (!isset($mixmask))
					{
						for($i=1;$i<=6;$i++)
							if(isset(${'mitm'.$i}) && ${'mitm'.$i} == $i)
								$mixlist[] = $i;
					}
					else
					{
						for($i=1;$i<=6;$i++)
							if ($mixmask&(1<<($i-1)))
								$mixlist[] = $i;
					}
					if (isset($itemselect))
						itemmix($mixlist,$itemselect);
					else  itemmix($mixlist);
				}
			}
		} elseif($mode == 'special') {
			include_once GAME_ROOT.'./include/game/special.func.php';
			if(strpos($command,'pose') === 0) {
				$pose = substr($command,4,1);
				$log .= "基础姿态变为<span class=\"yellow\">$poseinfo[$pose]</span>。<br> ";
				$mode = 'command';
			} elseif(strpos($command,'tac') === 0) {
				$tactic = substr($command,3,1);
				$log .= "应战策略变为<span class=\"yellow\">$tacinfo[$tactic]</span>。<br> ";
				$mode = 'command';
			} elseif(strpos($command,'inf') === 0) {
				$infpos = substr($command,3,1);
				chginf($infpos);
			} elseif(strpos($command,'chkp') === 0) {
				$itmn = substr($command,4,1);
				chkpoison($itmn);
			} elseif(strpos($command,'shop') === 0) {
				$shop = substr($command,4,2);
				shoplist($shop);
			} elseif(strpos($command,'clubsel') === 0) {
				$clubchosen = substr($command,7,1);
				include_once GAME_ROOT.'./include/game/clubslct.func.php';
				$retval=selectclub($clubchosen);
				if ($retval==0)
					$log.="称号选择成功。<br>";
				else if ($retval==1)
					$log.="称号选择失败，称号一旦被选择便无法更改。<br>";
				else if ($retval==2)
					$log.="未选择称号。<br>";
				else  $log.="称号选择非法！<br>";
				$mode = 'command';
			}
		} elseif($mode == 'senditem') {
			include_once GAME_ROOT.'./include/game/battle.func.php';
			senditem();
		} elseif($mode == 'combat') {
			include_once GAME_ROOT.'./include/game/combat.func.php';
			combat(1,$command);
		} elseif($mode == 'rest') {
			include_once GAME_ROOT.'./include/state.func.php';
			rest($command);
//		} elseif($mode == 'chgpassword') {
//			include_once GAME_ROOT.'./include/game/special.func.php';
//			chgpassword($oldpswd,$newpswd,$newpswd2);
//		} elseif($mode == 'chgword') {
//			include_once GAME_ROOT.'./include/game/special.func.php';
//			chgword($newmotto,$newlastword,$newkillmsg);
		} elseif($mode == 'corpse') {
			include_once GAME_ROOT.'./include/game/itemmain.func.php';
			getcorpse($command);
		} elseif($mode == 'team') {
			include_once GAME_ROOT.'./include/game/team.func.php';
			if ($command=="teammake") teammake($nteamID,$nteamPass);
			if ($command=="teamjoin") teamjoin($nteamID,$nteamPass);
			if ($command=="teamquit") teamquit($nteamID,$nteamPass);
		} elseif($mode == 'shop') {
			if(in_array($pls,$shops)){
				if($command == 'shop') {
					$mode = 'sp_shop';
				} else {
					include_once GAME_ROOT.'./include/game/itemmain.func.php';
					itembuy($command,$shoptype,$buynum);
				}
			}else{
				$log .= '<span class="yellow">你所在的地区没有商店。</span><br />';
				$mode = 'command';
			}
		} elseif($mode == 'deathnote') {
			if($dnname){
				include_once GAME_ROOT.'./include/game/item2.func.php';
				deathnote($item,$dnname,$dndeath,$dngender,$dnicon,$name);
			} else {
				$log .= '嗯，暂时还不想杀人。<br>你合上了■DeathNote■。<br>';
				$mode = 'command';
			}
		}elseif($mode == 'oneonone') {
			if($dnname){
						include_once GAME_ROOT.'./include/game/special.func.php';
						oneonone($dnname,$name);
					} else {
						$log .= '约战取消。<br>';
						$mode = 'command';
					}
		} elseif ($mode == 'sp_skpts') {
			include_once GAME_ROOT.'./include/game/clubskills.func.php';
			upgradeclubskills($command);
			calcskills($skarr);
			$p12[1]=1; $p12[2]=2;
		} elseif ($mode == 'sp_pbomb') {
			include_once GAME_ROOT.'./include/game/special.func.php';
			if ($command=="YES") press_bomb();
			$mode = 'command';
		} else {
			$mode = 'command';
		}
		
		if(strpos($action,'pacorpse')===0 && $gamestate < 40){
//			if($state == 1 || $state == 2 || $state ==3){
//				$state = 0;
//			}
			$cid = str_replace('pacorpse','',$action);
			if($cid){
				$result = $db->query("SELECT * FROM {$tablepre}players WHERE pid='$cid' AND hp=0");
				if($db->num_rows($result)>0){
					$edata = $db->fetch_array($result);
					include_once GAME_ROOT.'./include/game/battle.func.php';
					findcorpse($edata);					
				}	
			}	
		}
				
		//指令执行完毕，更新冷却时间
		if($coldtimeon && isset($cmdcdtime)){
			$nowmtime = floor(getmicrotime()*1000);
			$cdsec = floor($nowmtime/1000);
			$cdmsec = fmod($nowmtime , 1000);
			$cdtime = $cmdcdtime;
			//$psdata = Array('pid' => $pid, 'cdsec' => $cdsec, 'cdmsec' => $cdmsec, 'cdtime' => $cdtime, 'cmd' => $mode);
			//set_pstate($psdata);
			$rmcdtime = $cmdcdtime;
		}
		$endtime = $now;
		$cmdnum ++;
		//var_dump($pdata['action']);
		player_save($pdata);
		//$db->query("UPDATE {$tablepre}players SET endtime='$now',cdsec='$cdsec',cdmsec='$cdmsec',cdtime='$cdtime',club='$club',hp='$hp',mhp='$mhp',sp='$sp',msp='$msp',att='$att',def='$def',pls='$pls',lvl='$lvl',exp='$exp',money='$money',rp='$rp',bid='$bid',inf='$inf',rage='$rage',pose='$pose',tactic='$tactic',state='$state',killnum='$killnum',wp='$wp',wk='$wk',wg='$wg',wc='$wc',wd='$wd',wf='$wf',teamID='$teamID',teamPass='$teamPass',wep='$wep',wepk='$wepk',wepe='$wepe',weps='$weps',wepsk='$wepsk',arb='$arb',arbk='$arbk',arbe='$arbe',arbs='$arbs',arbsk='$arbsk',arh='$arh',arhk='$arhk',arhe='$arhe',arhs='$arhs',arhsk='$arhsk',ara='$ara',arak='$arak',arae='$arae',aras='$aras',arask='$arask',arf='$arf',arfk='$arfk',arfe='$arfe',arfs='$arfs',arfsk='$arfsk',art='$art',artk='$artk',arte='$arte',arts='$arts',artsk='$artsk',itm0='$itm0',itmk0='$itmk0',itme0='$itme0',itms0='$itms0',itmsk0='$itmsk0',itm1='$itm1',itmk1='$itmk1',itme1='$itme1',itms1='$itms1',itmsk1='$itmsk1',itm2='$itm2',itmk2='$itmk2',itme2='$itme2',itms2='$itms2',itmsk2='$itmsk2',itm3='$itm3',itmk3='$itmk3',itme3='$itme3',itms3='$itms3',itmsk3='$itmsk3',itm4='$itm4',itmk4='$itmk4',itme4='$itme4',itms4='$itms4',itmsk4='$itmsk4',itm5='$itm5',itmk5='$itmk5',itme5='$itme5',itms5='$itms5',itmsk5='$itmsk5',itm6='$itm6',itmk6='$itmk6',itme6='$itme6',itms6='$itms6',itmsk6='$itmsk6' where pid='$pid'");
	}
	
	//显示指令执行结果
	$gamedata['innerHTML']['notice'] = ob_get_contents();
	if($coldtimeon && $showcoldtimer && $rmcdtime){
		$gamedata['timer'] = $rmcdtime;
	}
	if($hp > 0 && $coldtimeon && $showcoldtimer && $rmcdtime){
		$log .= "行动冷却时间：<span id=\"timer\" class=\"yellow\">0.0</span>秒<br>";
	}
	
}
init_profile();

if($hp <= 0) {
	$dtime = date("Y年m月d日H时i分s秒",$endtime);
	$kname='';
	if($bid) {
		$result = $db->query("SELECT name FROM {$tablepre}players WHERE pid='$bid'");
		if($db->num_rows($result)) { $kname = $db->result($result,0); }
	}
	ob_clean();
	include template('death');
	$gamedata['innerHTML']['cmd'] = ob_get_contents();
	$mode = 'death';
} elseif($cmd){	
	$gamedata['innerHTML']['cmd'] = $cmd;
} elseif($itms0){
	ob_clean();
	include template('itemfind');
	$gamedata['innerHTML']['cmd'] = ob_get_contents();
} elseif($state == 1 || $state == 2 || $state ==3) {
	ob_clean();
	include template('rest');
	$gamedata['innerHTML']['cmd'] = ob_get_contents();
} elseif(!$cmd) {
	ob_clean();
	if($mode&&file_exists(GAME_ROOT.TPLDIR.'/'.$mode.'.htm')) {
		include template($mode);
	} else {
		include template('command');
	}
	$gamedata['innerHTML']['cmd'] = ob_get_contents();
	//$gamedata['cmd'] .= '<br><br><input type="button" id="submit" onClick="postCommand();return false;" value="提交">';
} else {
	$log .= '游戏流程故障，请联系管理员<br>';
	//$gamedata['innerHTML']['cmd'] = $cmd;
	//$gamedata['cmd'] .= '<br><br><input type="button" id="submit" onClick="postCommand();return false;" value="提交">';
}


if(isset($url)){$gamedata['url'] = $url;}
$gamedata['innerHTML']['pls'] = $plsinfo[$pls];
$gamedata['innerHTML']['anum'] = $alivenum;

ob_clean();
$main ? include template($main) : include template('profile');
$gamedata['innerHTML']['main'] = ob_get_contents();
$gamedata['innerHTML']['log'] = $log;
if(isset($error)){$gamedata['innerHTML']['error'] = $error;}
$gamedata['value']['teamID'] = $teamID;
if($teamID){
	$gamedata['innerHTML']['chattype'] = "<select name=\"chattype\" value=\"2\"><option value=\"0\" selected>$chatinfo[0]<option value=\"1\" >$chatinfo[1]</select>";
}else{
	$gamedata['innerHTML']['chattype'] = "<select name=\"chattype\" value=\"2\"><option value=\"0\" selected>$chatinfo[0]</select>";
}
//foreach($gamedata as $k => $v){
//	$w .= "{ $k } => { $v };\n\r";
//}
//writeover('a.txt',$w);
ob_clean();
$jgamedata = compatible_json_encode($gamedata);
//$json = new Services_JSON();
//$jgamedata = $json->encode($gamedata);
echo $jgamedata;

ob_end_flush();
//$t_e=getmicrotime();
//putmicrotime($t_s,$t_e,'cmd_time');

?>
