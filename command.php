<?php

define('CURSCRIPT', 'game');

require './include/common.inc.php';
//$t_s=getmicrotime();
//require_once GAME_ROOT.'./include/JSON.php';
require GAME_ROOT.'./include/game.func.php';

//判断是否进入游戏
if(!$cuser||!$cpass) { gexit($_ERROR['no_login'],__file__,__line__); } 

//$result = $db->query("SELECT * FROM {$tablepre}players WHERE name = '$cuser' AND type = 0");
$pdata = fetch_playerdata_by_name($cuser);

if(!$pdata) { header("Location: valid.php");exit(); }

//$pdata = $db->fetch_array($result);

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
$pdata['clbpara'] = get_clbpara($pdata['clbpara']);
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
			$log .= "<span class=\"yellow\">{$noisesec}秒前，{$plsinfo[$noisepls]}传来了{$noiseinfo[$noisemode]}。</span><br>";
		} else {
			$noisemin = floor(($now-$noisetime)/60);
			$log .= "<span class=\"yellow\">{$noisemin}分钟前，{$plsinfo[$noisepls]}传来了{$noiseinfo[$noisemode]}。</span><br>";
		}
	}
	
	if ($club==0 && !isset($clubavl))
	{
		include_once GAME_ROOT.'./include/game/clubslct.func.php';
		getclub($name,$c1,$c2,$c3);
		$clubavl[0]=0; $clubavl[1]=$c1; $clubavl[2]=$c2; $clubavl[3]=$c3;
	}

	//PORT
	//判断背包内道具是否超限
	if(strpos($arbsk,'^')!==false && $arbs && $arbe){
		global $itmnumlimit;
		$itmnumlimit = $arbe>=$arbs ? $arbs : $arbe;
		include_once GAME_ROOT.'./include/game/itembag.func.php';
		overnumlimit();
	}
	
	//判断冷却时间是否过去
	if($coldtimeon){
		$cdover = $cdsec*1000 + $cdmsec + $cdtime;
		$nowmtime = floor(getmicrotime()*1000);
		$rmcdtime = $nowmtime >= $cdover ? 0 : $cdover - $nowmtime;
	}

	//如果身上存在时效性技能，检查技能是否超时
	if($hp > 0 && !empty($clbpara['lasttimes'])) check_skilllasttimes($pdata);
	//应用眩晕状态效果
	if($hp > 0 && !empty($clbpara['skill']) && in_array('inf_dizzy',$clbpara['skill']))
	{
		$dizzy_times = (($clbpara['starttimes']['inf_dizzy'] + $clbpara['lasttimes']['inf_dizzy']) - $now)*1000;
		$log .= '<span class="yellow">你现在处于眩晕状态，什么都做不了！</span><br>眩晕状态持续时间还剩：<span id="timer" class="yellow">'.$dizzy_times.'</span>秒<br><script type="text/javascript">demiSecTimerStarter('.$dizzy_times.');</script>';
		goto cd_flag;
	}

	//执行动作前，身上存在追击标记时，直接进入追击判定
	if(!empty($action) && in_array($action,Array('chase','pchase','dfight','cover')) && $mode !== 'revcombat')
	{
		$command = $action;
		goto chase_flag;
	}
	//执行动作前检查是否有无法跳过且未阅览过的对话框
	if(isset($clbpara['noskip_dialogue']) && strpos($command,'end_dialogue')===false)
	{
		$dialogue_id = $clbpara['dialogue'];
	}elseif($coldtimeon && $rmcdtime > 0 && (strpos($command,'move')===0 || strpos($command,'search')===0 || (strpos($command,'itm')===0)&&($command != 'itemget') || strpos($sp_cmd,'sp_weapon')===0 || strpos($command,'song')===0)){
		$log .= '<span class="yellow">冷却时间尚未结束！</span><br>';
		cd_flag:
		$mode = 'command';
	}else{
		//进入指令判断
		if(!empty($itemindex))
		{
			$opendialog = 'itemmix_tips';
			$mode = 'command';
			$command = 'itemmain';
			$itemcmd = 'itemmix';
		}
		if($action != 'chase' && $action != 'dfight' && $mode !== 'combat' && $mode !== 'revcombat' && $mode !== 'corpse' && $action != 'pacorpse' && $mode !== 'senditem'){
			$action = ''; $bid = 0;
		}
		if($command == 'menu') {
			$mode = 'command';
			//$action = '';
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
				if(($club == 20 && $itemcmd == 'itemmix') || ($club != 20 && ($itemcmd == 'elementmix' || $itemcmd == 'elementbag'))){
					$log .= "你的手突然掐住了你的头左右摇摆！<br><span class='yellow'>“你还想要干什么，啊？你还想要干什么！！”</span><br>看来你的手和脑子之间起了一点小摩擦。<br><br>";
					$mode = 'command';
				}else {	
					if($club == 20){
						global $elements_info;
						include_once GAME_ROOT.'./include/game/elementmix.func.php';
						$emax = get_emix_itme_max();
						foreach($elements_info as $e_key=>$e_info) ${'etaginfo'.$e_key} ="<span tooltip=\"".print_elements_tags($e_key)."\">";
					}
					elseif($itemcmd == 'itemmix'){
						$main = 'itemmix_tips';
					}
					$mode = $itemcmd;
				}
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
			} elseif(strpos($command,'consle') === 0) {
				if(isset($clbpara['console']))
				{
					$cls_cmd = substr($command,7);
					include_once GAME_ROOT.'./include/game/console.func.php';
					if($cls_cmd == 'wthchange'){console_wthchange($cwth);}
					elseif($cls_cmd == 'dbutton'){console_dbutton();}
					elseif($cls_cmd == 'radar'){
						include_once GAME_ROOT.'./include/game/item2.func.php';
						newradar(2);
					}elseif($cls_cmd == 'search'){
						$cls_cmd_kind = substr($csc,7);
						console_searching($cls_cmd_kind,$csnm,$cstype);
					}elseif(strpos($cls_cmd,'areactrl')===0){
						$cls_cmd_kind = substr($cls_cmd,8);
						console_areacontrol($cls_cmd_kind);
					}
				}
				else{
					$mode='command';
				}
			} elseif(strpos($command,'end_dialogue') === 0) {
				//$log.="【DEBUG】关闭了对话框。";
				if(isset($dialogue_log[$clbpara['dialogue']])) $log.= $dialogue_log[$clbpara['dialogue']];
				unset($clbpara['dialogue']); unset($clbpara['noskip_dialogue']);
			} elseif (strpos($command,'memory')===0) {
				$smn = substr($command,6);
				if(!empty($clbpara['smeo'] && isset($clbpara['smeo'][$smn]))){
					$iid = $clbpara['smeo'][$smn][0]; $itp = $clbpara['smeo'][$smn][1]; 
					lost_searchmemory($smn,$pdata);
					if($itp == 'itm'){
						include_once GAME_ROOT.'./include/game/search.func.php';
						focus_item($pdata,$iid);
					}else{
						$action = 'focus'; $bid = $iid; $command = 'focus';
						goto chase_flag;
					}
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
			} elseif(strpos($command,'split_itm') === 0) {
				//把道具分解为元素 在数据库里注销道具的流程已经在discover()里走完了
				$split_item = substr($command,9);
				include_once GAME_ROOT . './include/game/elementmix.func.php';
				split_item_to_elements($split_item);
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
					include_once GAME_ROOT.'./include/game/itemmix.func.php';
					if (isset($itemselect))
						itemmix_rev($mixlist,$itemselect);
					else  itemmix_rev($mixlist);
				}
			} elseif($command == 'elementmix') {
				if($club == 20){
					$e_mixlist = Array();
					foreach($elements_info as $e_key=>$e_info){
						global ${'element'.$e_key};
						$m_e_key = $e_key + 1;//这样就不用污染原本的js了
						if(isset(${'emitm'.$e_key.'_num'})) ${'emitm'.$e_key.'_num'} = round( ${'emitm'.$e_key.'_num'});
						if(${'mitm'.$m_e_key}>=0 && ${'element'.$e_key} && ${'emitm'.$e_key.'_num'}>0 && ${'emitm'.$e_key.'_num'}<=${'element'.$e_key}){
							$e_mixlist[$e_key] = ${'emitm'.$e_key.'_num'}; //打入参与合成的元素编号与数量
						}
					}
					if(count($e_mixlist)>0){
						$er = ($lvl>=15 && $emitme_r && $change_emr>0) ? $emitme_r : NULL;
						$emr = ($lvl>=5 && $emitme_max_r && $change_emax>0) ? $emitme_max_r : NULL;
						include_once GAME_ROOT.'./include/game/elementmix.func.php';
						element_mix($e_mixlist,$emr,$er);
					}
					else{$log.="至少要放入一份元素。<br>";}
				}
				else {$log.="你挠了挠头，没搞懂自己到底要干什么。<br>";}
				$mode='command';
			} elseif($command == 'elementbag') {
				if($club == 20){
					include_once GAME_ROOT.'./include/game/elementmix.func.php';
					print_elements_info();
				}
				$mode='command';
			} elseif($command == 'itemencase') {
				if(strpos($arbsk,'^')!==false && $arbs && $arbe){
					$ilist = array();
					for($i=1;$i<=6;$i++){
						if(isset(${'mitm'.$i}) && ${'mitm'.$i} == $i){
							$ilist[] = $i;
						}
					}
					item_encase($ilist);
				}else{
					$log.="<span class='red'>你身上没有背包，或是没有将背包装备上！<br>";
				}
			} elseif($command == 'iteminfo') {
				if(strpos($arbsk,'^')!==false && $arbs && $arbe){
					item_info();
				}else{
					$log.="<span class='red'>你身上没有背包，或是没有将背包装备上！<br>";
				}
			} elseif(strpos($command,'usebagitm') !==false) {
				if(strpos($arbsk,'^')!==false && $arbs && $arbe){
					$itemid = substr($command,10);
					item_out($itemid);
				}else{
					$log.="<span class='red'>你身上没有背包，或是没有将背包装备上！<br>";
				}
			} elseif(strpos($command,'changewep') !==false) {
				include_once GAME_ROOT.'./include/game/revbattle.func.php';
				change_wep_in_battle();
				$mode = 'command';
			}
		} elseif($mode == 'special') {
			include_once GAME_ROOT.'./include/game/special.func.php';
			if(strpos($command,'pose') === 0) {
				$cpose = substr($command,4,1);
				if(in_array($cpose,$apose)){
					if($cpose == 8 && isset($clbpara['starttimes']['pose8']) && ($now < ($clbpara['starttimes']['pose8'] + 60))){
						$log .= "现在无法切换至{$poseinfo[$cpose]}。剩余冷却时间：".round($clbpara['starttimes']['pose8'] + 60 - $now)."秒。<br>";
						goto command_end_flag;
					} elseif($cpose == 8) {
						$clbpara['starttimes']['pose8'] = $now;
					}
					$pose = $cpose;
					$log .= "基础姿态变为<span class=\"yellow\">$poseinfo[$pose]</span>。<br> ";
					$mode = 'command';
				}else{
					$log .= "<span class=\"yellow\">这个姿势太奇怪了！</span><br> ";
					$mode = 'command';
				}
			} elseif(strpos($command,'tac') === 0) {
				$ctac = substr($command,3,1);
				if(in_array($ctac,$atac)){
					$tactic = $ctac;
					$log .= "应战策略变为<span class=\"yellow\">$tacinfo[$tactic]</span>。<br> ";
					$mode = 'command';
				}else{
					$log .= "<span class=\"yellow\">这种策略太奇怪了！</span><br> ";
					$mode = 'command';
				}
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
		} elseif($mode == 'revcombat'){
			chase_flag:
			include_once GAME_ROOT.'./include/game/revbattle.func.php';
			if(!isset($message)) $message = '';
			revbattle_prepare($command,$message);
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
			if ($command=="teammake") teammake($nteamID,$nteamPass,(int)$ticon);
			if ($command=="teamjoin") teamjoin($nteamID,$nteamPass);
			if ($command=="teamquit") teamquit($nteamID,$nteamPass);
		} elseif($mode == 'shop') {
			if(in_array($pls,$shops) || !check_skill_unlock('c11_ebuy',$pdata)){
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
		} elseif($mode == 'depot') {
			include_once GAME_ROOT.'./include/game/depot.func.php';
			if(in_array($pls,$depots))
			{
				$saveitem_list = depot_getlist($name,$type);
				switch($command)
				{
					case 'sp_depot_save':
						$mode = 'sp_depot_save';
						break;
					case 'sp_depot_load':
						$mode = 'sp_depot_load';
						break;
					case strpos($command,'saveitem')===0:
						$iid = substr($command,9);
						depot_save($iid);
						break;
					case strpos($command,'loaditem')===0:
						$lid = substr($command,9);
						depot_load($lid);
						break;
					default :
						$mode = 'sp_depot';
				}
			}
			else
			{
				$log .= '<span class="yellow">你所在的地区没有安全箱。</span><br />';
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
			//include_once GAME_ROOT.'./include/game/clubskills.func.php';
			//upgradeclubskills($command);
			//calcskills($skarr);
			//$p12[1]=1; $p12[2]=2;
			$log .= "不存在该指令！<br>";
			$mode = 'command';
		} elseif ($mode == 'revskpts') {
			$sk = substr($command,9);
			if(isset($cskills[$sk])) {
				if(strpos($command,'upgskill_')!==false) {
					if(isset($cskills[$sk]['num_input'])){
						$nums = isset(${$command.'_nums'}) ? (int)${$command.'_nums'} : 1;
						upgclbskills($sk,$nums);
					}else{
						upgclbskills($sk);
					}
				} elseif(strpos($command,'swtskill_')!==false) {
					if(isset(${$sk.'upgpara'}) && isset($cskills[$sk]['choice']) && in_array(${$sk.'upgpara'},$cskills[$sk]['choice'])) {
						switchclbskills($sk,${$sk.'upgpara'});
					}
				} elseif(strpos($command,'actskill_')!==false) {
					# 其他特殊技能按钮
					include_once GAME_ROOT.'./include/game/revclubskills_extra.func.php';
					if($sk == 'c4_roar' || $sk == 'c4_sniper'){skill_c4_unlock($sk);}
					elseif($sk == 'c11_merc'){
						if(isset(${$sk.'mkey'}) && isset(${$sk.'fire'}) && ${$sk.'fire'} == ${$sk.'mkey'}){
							skill_merc_fire($sk,${$sk.'mkey'});
						} elseif(isset(${$sk.'mkey'}) && isset(${$sk.'chase'})){
							skill_merc_chase($sk,${$sk.'mkey'});
						} elseif(isset(${$sk.'mkey'}) && isset(${$sk.${$sk.'mkey'}.'moveto'})){
							skill_merc_move($sk,${$sk.'mkey'},${$sk.${$sk.'mkey'}.'moveto'});
						} 
					} 
				}
			}
			$mode = 'command';
		} elseif ($mode == 'sp_pbomb') {
			include_once GAME_ROOT.'./include/game/special.func.php';
			if ($command=="YES") press_bomb();
			$mode = 'command';
		} else {
			command_end_flag:
			$mode = 'command';
		}
		
		if($action == 'pacorpse' && $gamestate < 40){
//			if($state == 1 || $state == 2 || $state ==3){
//				$state = 0;
//			}
			$cid = $bid;
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
		//读取背包内道具
		if(strpos($arbsk,'^')!==false && $arbs && $arbe){
			include_once GAME_ROOT.'./include/game/itembag.func.php';
			$itemlist = item_arr();
		}
		$endtime = $now;
		$cmdnum ++;
		//var_dump($pdata['action']);
		//$db->query("UPDATE {$tablepre}players SET endtime='$now',cdsec='$cdsec',cdmsec='$cdmsec',cdtime='$cdtime',club='$club',hp='$hp',mhp='$mhp',sp='$sp',msp='$msp',att='$att',def='$def',pls='$pls',lvl='$lvl',exp='$exp',money='$money',rp='$rp',bid='$bid',inf='$inf',rage='$rage',pose='$pose',tactic='$tactic',state='$state',killnum='$killnum',wp='$wp',wk='$wk',wg='$wg',wc='$wc',wd='$wd',wf='$wf',teamID='$teamID',teamPass='$teamPass',wep='$wep',wepk='$wepk',wepe='$wepe',weps='$weps',wepsk='$wepsk',arb='$arb',arbk='$arbk',arbe='$arbe',arbs='$arbs',arbsk='$arbsk',arh='$arh',arhk='$arhk',arhe='$arhe',arhs='$arhs',arhsk='$arhsk',ara='$ara',arak='$arak',arae='$arae',aras='$aras',arask='$arask',arf='$arf',arfk='$arfk',arfe='$arfe',arfs='$arfs',arfsk='$arfsk',art='$art',artk='$artk',arte='$arte',arts='$arts',artsk='$artsk',itm0='$itm0',itmk0='$itmk0',itme0='$itme0',itms0='$itms0',itmsk0='$itmsk0',itm1='$itm1',itmk1='$itmk1',itme1='$itme1',itms1='$itms1',itmsk1='$itmsk1',itm2='$itm2',itmk2='$itmk2',itme2='$itme2',itms2='$itms2',itmsk2='$itmsk2',itm3='$itm3',itmk3='$itmk3',itme3='$itme3',itms3='$itms3',itmsk3='$itmsk3',itm4='$itm4',itmk4='$itmk4',itme4='$itme4',itms4='$itms4',itmsk4='$itmsk4',itm5='$itm5',itmk5='$itmk5',itme5='$itme5',itms5='$itms5',itmsk5='$itmsk5',itm6='$itm6',itmk6='$itmk6',itme6='$itme6',itms6='$itms6',itmsk6='$itmsk6' where pid='$pid'");
	}
	//检查是否需要重生成播放器
	$bgm_player = init_bgm();
	if(!empty($bgm_player))
	{
		$gamedata['innerHTML']['ingamebgm'] = $bgm_player;
	}
	//检查执行动作后是否有对话框产生
	if(isset($clbpara['dialogue']))
	{
		$dialogue_id = $clbpara['dialogue'];
	}
	//显示指令执行结果
	$gamedata['innerHTML']['notice'] = ob_get_contents();
	if(($coldtimeon && $showcoldtimer && $rmcdtime) || isset($dizzy_times)){
		$gamedata['timer'] = isset($dizzy_times) ? $dizzy_times : $rmcdtime;
	}
	if($hp > 0 && $coldtimeon && $showcoldtimer && $rmcdtime){
		$log .= "行动冷却时间：<span id=\"timer\" class=\"yellow\">0.0</span>秒<br>";
	}
	player_save($pdata);
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

//存在 $opendialog 时 尝试打开id为 $opendialog 值的悬浮窗口
if(isset($opendialog)){$log.="<span style=\"display:none\" id=\"open-dialog\">{$opendialog}</span>";}

if(isset($url)){$gamedata['url'] = $url;}
$gamedata['innerHTML']['pls'] = (!isset($plsinfo[$pls]) && isset($hplsinfo[$pgroup])) ? $hplsinfo[$pgroup][$pls] : $plsinfo[$pls];
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
