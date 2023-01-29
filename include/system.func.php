<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}

function rs_game($mode = 0) {
	global $db,$tablepre,$gamecfg,$now,$gamestate,$plsinfo,$typeinfo,$areanum,$areaadd,$afktime,$combonum,$deathlimit;
//	$stime=getmicrotime();
	$dir = GAME_ROOT.'./gamedata/';
	$sqldir = GAME_ROOT.'./gamedata/sql/';
	if ($mode & 1) {
		//重设玩家互动信息、聊天记录、地图道具、地图陷阱、进行状况
		$sql = file_get_contents("{$sqldir}reset.sql");
		$sql = str_replace("\r", "\n", str_replace(' bra_', ' '.$tablepre, $sql));
		
		$db->queries($sql);
		
		//重设游戏进行状况的时间
		if($fp = fopen("{$dir}newsinfo.php", 'wb')) {
			global $checkstr;
			fwrite($fp, $checkstr);
			fclose($fp);
		} else {
			gexit('Can not write to cache files, please check directory ./gamedata/ and ./gamedata/cache/ .', __file__, __line__);
		}
		
		//清空战斗信息
		global $hdamage,$hplayer,$noisetime,$noisepls,$noiseid,$noiseid2,$noisemode,$starttime,$gamevars;
		$hdamage = 0;
		$hplayer = '';
		$noisetime = 0;
		$noisepls = 0;
		$noiseid = 0;
		$noiseid2 = 0;
		$noisemode = '';
		save_combatinfo();
		
		//修改反挂机间隔
		$afktime = $starttime;
		//重设连斗判断死亡数
		$combonum = $deathlimit;
		//重设游戏剧情开关
		$gamevars = 0;
		save_gameinfo();
		
		
		
	}
	if ($mode & 2) {
		//echo " - 禁区初始化 - ";
		global $arealist,$areanum,$weather,$hack,$areatime,$starttime,$startmin,$areaadd,$areahour;
		list($sec,$min,$hour,$day,$month,$year,$wday,$yday,$isdst) = localtime($starttime);
		$areatime = (ceil(($starttime + $areahour*60)/600))*600;//$areahour已改为按分钟计算，ceil是为了让禁区分钟为10的倍数
		$plsnum = sizeof($plsinfo);
		$arealist = range(1,$plsnum-1);
		shuffle($arealist);
		array_unshift($arealist,0);
		$areanum = 0;
		$weather = rand(0,9);
		$hack = 0;
		movehtm($areatime);
	}
	if ($mode & 4) {
		//echo " - 角色数据库初始化 - ";
		global $validnum,$alivenum,$deathnum;
		$sql = file_get_contents("{$sqldir}players.sql");
		$sql = str_replace("\r", "\n", str_replace(' bra_', ' '.$tablepre, $sql));
		$db->queries($sql);
		//runquery($sql);
		$validnum = $alivenum = $deathnum = 0;
	}
	if ($mode & 8) {
		//echo " - NPC初始化 - ";
		$db->query("DELETE FROM {$tablepre}players WHERE type>0 ");
		include_once config('npc',$gamecfg);
		//$typenum = sizeof($typeinfo);
		$plsnum = sizeof($plsinfo);
		$npcqry = '';
		
		//for($i = 1; $i < $typenum; $i++) {
		foreach ($npcinfo as $i => $npcs){
			if(!empty($npcs)) {
				if (sizeof($npcs['sub'])>$npcs['num'])shuffle($npcs['sub']);
				for($j = 1; $j <= $npcs['num']; $j++) {
					$npc = array_merge($npcinit,$npcs);
					//$npc = $npcinfo[$i];
					$npc['type'] = $i;
					$npc['endtime'] = $now;
					$npc['sNo'] = $j;
					
					//if(($npc['mode'] == 1)&&($npc['num'] <= $npc['sub'])){
					//	$npc = array_merge($npc,$npc[$j]);
					//} elseif($npc['mode'] == 2) {
					//	$k = rand(1,$npc['sub']);
					//	$npc = array_merge($npc,$npc[$k]);
					//} else {
					//	$npc = array_merge($npc,$npc[1]);
					//}
					
					
					$subnum = sizeof($npc['sub']);
					$sub = $j % $subnum;
					$npc = array_merge($npc,$npc['sub'][$sub]);
					$npc['hp'] = $npc['mhp'];
					$npc['sp'] = $npc['msp'];
					$npc['exp'] = round(2*$npc['lvl']*$GLOBALS['baseexp']);
					foreach(Array('p','k','g','c','d','f') as $val){
						if(!$npc['w'.$val]){
							$npc['w'.$val] = $npc['skill'];
						}
					}
					//$npc['wp'] = $npc['wk'] = $npc['wg'] = $npc['wc'] = $npc['wd'] = $npc['wf'] = $npc['skill'];
					if($npc['gd'] == 'r'){$npc['gd'] = rand(0,1) ? 'm':'f';}
					do{$rpls=rand(1,$plsnum-1);}while ($rpls==34);
					if($npc['pls'] == 99){$npc['pls'] = $rpls; }
					$npc['state'] = 0;
					$npc=player_format_with_db_structure($npc);
					$db->array_insert("{$tablepre}players", $npc);
					//$npcqry .= "('".$npc['name']."','".$npc['pass']."','".$npc['type']."','".$npc['endtime']."','".$npc['gd']."','".$npc['sNo']."','".$npc['icon']."','".$npc['club']."','".$npc['rp']."','".$npc['hp']."','".$npc['mhp']."','".$npc['sp']."','".$npc['msp']."','".$npc['att']."','".$npc['def']."','".$npc['pls']."','".$npc['lvl']."','".$npc['exp']."','".$npc['money']."','".$npc['bid']."','".$npc['inf']."','".$npc['rage']."','".$npc['pose']."','".$npc['tactic']."','".$npc['killnum']."','".$npc['state']."','".$npc['wp']."','".$npc['wk']."','".$npc['wg']."','".$npc['wc']."','".$npc['wd']."','".$npc['wf']."','".$npc['teamID']."','".$npc['teamPass']."','".$npc['wep']."','".$npc['wepk']."','".$npc['wepe']."','".$npc['weps']."','".$npc['arb']."','".$npc['arbk']."','".$npc['arbe']."','".$npc['arbs']."','".$npc['arh']."','".$npc['arhk']."','".$npc['arhe']."','".$npc['arhs']."','".$npc['ara']."','".$npc['arak']."','".$npc['arae']."','".$npc['aras']."','".$npc['arf']."','".$npc['arfk']."','".$npc['arfe']."','".$npc['arfs']."','".$npc['art']."','".$npc['artk']."','".$npc['arte']."','".$npc['arts']."','".$npc['itm0']."','".$npc['itmk0']."','".$npc['itme0']."','".$npc['itms0']."','".$npc['itm1']."','".$npc['itmk1']."','".$npc['itme1']."','".$npc['itms1']."','".$npc['itm2']."','".$npc['itmk2']."','".$npc['itme2']."','".$npc['itms2']."','".$npc['itm3']."','".$npc['itmk3']."','".$npc['itme3']."','".$npc['itms3']."','".$npc['itm4']."','".$npc['itmk4']."','".$npc['itme4']."','".$npc['itms4']."','".$npc['itm5']."','".$npc['itmk5']."','".$npc['itme5']."','".$npc['itms5']."','".$npc['itm6']."','".$npc['itmk6']."','".$npc['itme6']."','".$npc['itms6']."','".$npc['wepsk']."','".$npc['arbsk']."','".$npc['arhsk']."','".$npc['arask']."','".$npc['arfsk']."','".$npc['artsk']."','".$npc['itmsk0']."','".$npc['itmsk1']."','".$npc['itmsk2']."','".$npc['itmsk3']."','".$npc['itmsk4']."','".$npc['itmsk5']."','".$npc['itmsk6']."','".$npc['skills']."'),";
					//$db->query("INSERT INTO {$tablepre}players (name,pass,type,endtime,gd,sNo,icon,club,hp,mhp,sp,msp,att,def,pls,lvl,`exp`,money,bid,inf,rage,pose,tactic,killnum,state,wp,wk,wg,wc,wd,wf,teamID,teamPass,wep,wepk,wepe,weps,arb,arbk,arbe,arbs,arh,arhk,arhe,arhs,ara,arak,arae,aras,arf,arfk,arfe,arfs,art,artk,arte,arts,itm0,itmk0,itme0,itms0,itm1,itmk1,itme1,itms1,itm2,itmk2,itme2,itms2,itm3,itmk3,itme3,itms3,itm4,itmk4,itme4,itms4,itm5,itmk5,itme5,itms5,wepsk,arbsk,arhsk,arask,arfsk,artsk,itmsk0,itmsk1,itmsk2,itmsk3,itmsk4,itmsk5) VALUES ('".$npc['name']."','".$npc['pass']."','".$npc['type']."','".$npc['endtime']."','".$npc['gd']."','".$npc['sNo']."','".$npc['icon']."','".$npc['club']."','".$npc['hp']."','".$npc['mhp']."','".$npc['sp']."','".$npc['msp']."','".$npc['att']."','".$npc['def']."','".$npc['pls']."','".$npc['lvl']."','".$npc['exp']."','".$npc['money']."','".$npc['bid']."','".$npc['inf']."','".$npc['rage']."','".$npc['pose']."','".$npc['tactic']."','".$npc['killnum']."','".$npc['death']."','".$npc['wp']."','".$npc['wk']."','".$npc['wg']."','".$npc['wc']."','".$npc['wd']."','".$npc['wf']."','".$npc['teamID']."','".$npc['teamPass']."','".$npc['wep']."','".$npc['wepk']."','".$npc['wepe']."','".$npc['weps']."','".$npc['arb']."','".$npc['arbk']."','".$npc['arbe']."','".$npc['arbs']."','".$npc['arh']."','".$npc['arhk']."','".$npc['arhe']."','".$npc['arhs']."','".$npc['ara']."','".$npc['arak']."','".$npc['arae']."','".$npc['aras']."','".$npc['arf']."','".$npc['arfk']."','".$npc['arfe']."','".$npc['arfs']."','".$npc['art']."','".$npc['artk']."','".$npc['arte']."','".$npc['arts']."','".$npc['itm0']."','".$npc['itmk0']."','".$npc['itme0']."','".$npc['itms0']."','".$npc['itm1']."','".$npc['itmk1']."','".$npc['itme1']."','".$npc['itms1']."','".$npc['itm2']."','".$npc['itmk2']."','".$npc['itme2']."','".$npc['itms2']."','".$npc['itm3']."','".$npc['itmk3']."','".$npc['itme3']."','".$npc['itms3']."','".$npc['itm4']."','".$npc['itmk4']."','".$npc['itme4']."','".$npc['itms4']."','".$npc['itm5']."','".$npc['itmk5']."','".$npc['itme5']."','".$npc['itms5']."','".$npc['wepsk']."','".$npc['arbsk']."','".$npc['arhsk']."','".$npc['arask']."','".$npc['arfsk']."','".$npc['artsk']."','".$npc['itmsk0']."','".$npc['itmsk1']."','".$npc['itmsk2']."','".$npc['itmsk3']."','".$npc['itmsk4']."','".$npc['itmsk5']."')");
					unset($npc);
				}
			}
		}
		/*if(!empty($npcqry)){
			$npcqry = "INSERT INTO {$tablepre}players (name,pass,type,endtime,gd,sNo,icon,club,rp,hp,mhp,sp,msp,att,def,pls,lvl,`exp`,money,bid,inf,rage,pose,tactic,killnum,state,wp,wk,wg,wc,wd,wf,teamID,teamPass,wep,wepk,wepe,weps,arb,arbk,arbe,arbs,arh,arhk,arhe,arhs,ara,arak,arae,aras,arf,arfk,arfe,arfs,art,artk,arte,arts,itm0,itmk0,itme0,itms0,itm1,itmk1,itme1,itms1,itm2,itmk2,itme2,itms2,itm3,itmk3,itme3,itms3,itm4,itmk4,itme4,itms4,itm5,itmk5,itme5,itms5,itm6,itmk6,itme6,itms6,wepsk,arbsk,arhsk,arask,arfsk,artsk,itmsk0,itmsk1,itmsk2,itmsk3,itmsk4,itmsk5,itmsk6,skills) VALUES ".substr($npcqry, 0, -1);
			$db->query($npcqry);
			unset($npcqry);
		}*/
	}
	if ($mode & 16) {
		//echo " - 地图道具/陷阱初始化 - ";
		//感谢 Martin1994 提供地图道具数据库化的源代码
		$plsnum = sizeof($plsinfo);
		$iqry = $tqry = '';
//		if($gamestate == 0){
//			global $checkstr;
//			dir_clear("{$dir}mapitem/");
//			for($i = 0;$i < $plsnum; $i++){
//				$mapfile = GAME_ROOT."./gamedata/mapitem/{$i}mapitem.php";
//				writeover($mapfile,$checkstr);
//			}
//		}
		$file = config('mapitem',$gamecfg);
		$itemlist = openfile($file);
		$in = sizeof($itemlist);
		$an = $areanum ? ceil($areanum/$areaadd) : 0;
		//$mapitem = array();
		//$ifqry = $iqry = 'INSERT INTO '.$tablepre.'mapitem (itm,itmk,itme,itms,itmsk,map) VALUES ';
		for($i = 1; $i < $in; $i++) {
			if(!empty($itemlist[$i]) && strpos($itemlist[$i],',')!==false){
				list($iarea,$imap,$inum,$iname,$ikind,$ieff,$ista,$iskind) = explode(',',$itemlist[$i]);
				if(($iarea == $an)||($iarea == 99)) {
					for($j = $inum; $j>0; $j--) {
						if($imap == 99) {
							$rmap = rand(1,$plsnum-1);
							while ($rmap==34){$rmap = rand(1,$plsnum-1);}
							if(strpos($ikind ,'TO')===0){
								$tqry .= "('$iname', '$ikind','$ieff','$ista','$iskind','$rmap'),";
							}else{
								$iqry .= "('$iname', '$ikind','$ieff','$ista','$iskind','$rmap'),";
							}
							//$iqry[$rmap] .= "('$iname', '$ikind','$ieff','$ista','$iskind'),";
							//$db->query("INSERT INTO {$tablepre}{$rmap}mapitem (itm,itmk,itme,itms,itmsk) VALUES ('$iname', '$ikind','$ieff','$ista','$iskind')");
						}else{
							if(strpos($ikind ,'TO')===0){
								$tqry .= "('$iname', '$ikind','$ieff','$ista','$iskind','$imap'),";
							}else{
								$iqry .= "('$iname', '$ikind','$ieff','$ista','$iskind','$imap'),";
							}
							//$db->query("INSERT INTO {$tablepre}{$imap}mapitem (itm,itmk,itme,itms,itmsk) VALUES ('$iname', '$ikind','$ieff','$ista','$iskind')");
						}
						
						//if($imap == 99) {
						//	$imap = rand(1,$plsnum-1);
							//$mapitem[$rmap] .= "$iname,$ikind,$ieff,$ista,$iskind,\n";
						//} else {
							//$mapitem[$imap] .= "$iname,$ikind,$ieff,$ista,$iskind,\n"; 
						//}
					}
				}
			}
		}
		if(!empty($iqry)){
			$iqry = "INSERT INTO {$tablepre}mapitem (itm,itmk,itme,itms,itmsk,pls) VALUES ".substr($iqry, 0, -1);
			$db->query($iqry);
		}
		if(!empty($tqry)){
			$tqry = "INSERT INTO {$tablepre}maptrap (itm,itmk,itme,itms,itmsk,pls) VALUES ".substr($tqry, 0, -1);
			$db->query($tqry);
		}
//		for($imap = 0;$imap<$plsnum;$imap++){
//			if(!empty($iqry[$imap])){
//				$iqry[$imap] = "INSERT INTO {$tablepre}{$imap}mapitem (itm,itmk,itme,itms,itmsk) VALUES ".substr($iqry[$imap], 0, -1);
//				$db->query($iqry[$imap]);
//			}	
//		}
//		if($ifqry != $iqry){//判定是否有数据写入
//			$iqry = substr($iqry, 0, -1);//去除尾部多余的逗号
//			$db->query($iqry);
//		}
//		foreach($mapitem as $map => $itemdata) {
//			$mapfile = GAME_ROOT."./gamedata/mapitem/{$map}mapitem.php";
//			writeover($mapfile,$itemdata,'ab');
//		}
		
		unset($itemlist);unset($iqry);
		//unset($mapitem);
		//挤一挤 仓库道具初始化
		include_once GAME_ROOT.'./include/game/depot.func.php';
		if(isset($npc_depot) && count($npc_depot)>0)
		{
			foreach($npc_depot as $nd_num => $nd_arr)
			{
				foreach($nd_arr['itm'] as $nd_itm_arr)
				{
					$ditm = $nd_itm_arr['itm'];$ditmk = $nd_itm_arr['itmk'];$ditmsk = $nd_itm_arr['itmsk'];
					$ditme = $nd_itm_arr['itme'];$ditms = $nd_itm_arr['itms'];
					$dname = $nd_arr['name'];$dtype = $nd_arr['type'];
					$db->query("INSERT INTO {$tablepre}itemdepot (itm, itmk, itme, itms, itmsk ,itmowner, itmpw) VALUES ('$ditm', '$ditmk', '$ditme', '$ditms', '$ditmsk', '$dname', '$dtype')");
				}
			}
		}
	}
	if ($mode & 32) {
		//echo " - 商店初始化 - ";
		$sql = file_get_contents("{$sqldir}shopitem.sql");
		$sql = str_replace("\r", "\n", str_replace(' bra_', ' '.$tablepre, $sql));
		$db->queries($sql);
		//runquery($sql);
		
		$file = config('shopitem',$gamecfg);
		$shoplist = openfile($file);
		$qry = '';
		foreach($shoplist as $lst){
			if(!empty($lst) && strpos($lst,',')!==false){
				list($kind,$num,$price,$area,$item,$itmk,$itme,$itms,$itmsk)=explode(',',$lst);
				if($kind != 0){
					$qry .= "('$kind','$num','$price','$area','$item','$itmk','$itme','$itms','$itmsk'),";
				}
			}			
		}
		if(!empty($qry)){
			$qry = "INSERT INTO {$tablepre}shopitem (kind,num,price,area,item,itmk,itme,itms,itmsk) VALUES ".substr($qry, 0, -1);
		}
		$db->query($qry);
		
	}
}

function rs_sttime() {
	//echo " - 游戏开始时间初始化 - ";
	global $starttime,$now,$startmode,$starthour,$startmin;

	list($sec,$min,$hour,$day,$month,$year,$wday,$yday,$isdst) = localtime($now);
	$month++;
	$year += 1900;
	
	if($startmode == 1) {
		if($hour >= $starthour){ $nextday = $day+1;}
		else{$nextday = $day;}
		$nexthour = $starthour;
		$starttime = mktime($nexthour,$startmin,0,$month,$nextday,$year);
	} elseif($startmode == 2) {
		$starthour = $starthour> 0 ? $starthour : 1;
		$startmin = $startmin> 0 ? $startmin : 1;
		$nexthour = $hour + $starthour;
		$starttime = mktime($nexthour,$startmin,0,$month,$day,$year);
	} elseif($startmode == 3) {
		$starthour = $starthour> 0 ? $starthour : 1;
		$nextmin = $min + $starthour;
		$nexthour = $hour;
//		if($nextmin % 60 >= 40){//回避速1禁
//			$nextmin+=20;
//		}
		if($nextmin % 60 == 0){
			$nextmin +=1;
		}
		$starttime = mktime($nexthour,$nextmin,0,$month,$day,$year);
	} else {
		$starttime = 0;
	}
	
	return;
}


function add_once_area($atime) {
	//实际上GAMEOVER的判断是在common.inc.php里
	global $db,$tablepre,$now,$gamestate,$areaesc,$arealist,$areanum,$arealimit,$areaadd,$plsinfo,$weather,$hack,$validnum,$alivenum,$deathnum;
	
	if (($gamestate > 10)&&($now > $atime)) {
		$plsnum = sizeof($plsinfo) - 1;
		if(($areanum >= $arealimit*$areaadd)&&($validnum<=0)) {//无人参加GAMEOVER不是因为这里，这里只是保险。
			gameover($atime,'end4');
			return;
		} elseif(($areanum + $areaadd) >= $plsnum) {
			$areaaddlist = array_slice($arealist,$areanum+1);
			$areanum = $plsnum;
			$weather = rand(0,9);
			//addnews($atime,'addarea',$areaaddlist,$weather);
			addnews($atime, 'addarea',$areaaddlist,$weather);
			storyputchat($now,'areaadd');
			systemputchat($atime,'areaadd',$areaaddlist);
			$query = $db->query("SELECT * FROM {$tablepre}players WHERE type=0 AND hp>0");
			while($sub = $db->fetch_array($query)) {
				$pid = $sub['pid'];
				$hp = 0;
				$state = 11;
				$deathpls = $sub['pls'];
				$bid = 0;
				$endtime = $atime;
				$db->query("UPDATE {$tablepre}players SET hp='$hp', bid='$bid', state='$state', endtime='$endtime' WHERE pid=$pid");
				addnews($endtime,"death$state",$sub['name'],$sub['type'],$deathpls);
			}
			$db->free_result($query);
			$alivenum = 0;
			$dquery = $db->query("SELECT pid FROM {$tablepre}players WHERE hp<=0");
			$deathnum = $db->num_rows($dquery);
			$db->free_result($dquery);
			gameover($atime,'end1');
			return;
		} else {
			$weather = rand(0,9);
			if($hack > 0){$hack--;}
			$areaaddlist = array_slice($arealist,$areanum+1,$areaadd);
			$areanum += $areaadd;
			movehtm();
			//addnews($atime,'addarea',$areaaddlist,$weather);
			addnews($atime, 'addarea',$areaaddlist,$weather);
			storyputchat($now,'areaadd');
			systemputchat($atime,'areaadd',$areaaddlist);
			$str_arealist = implode(',',array_slice($arealist,0,$areanum+1));
			$query = $db->query("SELECT * FROM {$tablepre}players WHERE pls IN ($str_arealist) AND hp>0");
			while($sub = $db->fetch_array($query)) {
				$pid = $sub['pid'];
				if(!$sub['type']) {
					if(($gamestate >= 40)||(!$areaesc&&($sub['tactic']!=4))) {
					$hp = 0;
					$state = 11;
					$deathpls = $sub['pls'];
					$bid = 0;
					$endtime = $atime;
					$db->query("UPDATE {$tablepre}players SET hp='$hp', bid='$bid', state='$state', endtime='$endtime' WHERE pid=$pid");
					addnews($endtime,"death$state",$sub['name'],$sub['type'],$deathpls);
					$deathnum++;
					} else {
					do{$pls = $arealist[rand($areanum+1,$plsnum)];}while ($pls==34);
					$db->query("UPDATE {$tablepre}players SET pls='$pls' WHERE pid=$pid ");
					}
				} elseif($sub['type'] != 1 && $sub['type'] != 7 && $sub['type'] != 9 && $sub['type'] != 13 && $sub['type'] != 20 && $sub['type'] != 21 && $sub['type'] != 88 && $sub['type'] != 22 && $sub['type'] != 92) {
					do{$pls = $arealist[rand($areanum+1,$plsnum)];}while ($pls==34);
					$db->query("UPDATE {$tablepre}players SET pls='$pls' WHERE pid=$pid");
				}
			}
			$alivenum = $db->result($db->query("SELECT COUNT(*) FROM {$tablepre}players WHERE hp>0 AND type=0"), 0);
			if(($alivenum == 1)&&($gamestate >= 30)) { 
				gameover($atime);
				return;
			} elseif(($alivenum <= 0)&&($gamestate >= 30)) {
				gameover($atime,'end1');
				return $atime;
			} else {
				rs_game(16+32);
				//$areatime += $areahour*3600;
				//addarea($areatime);
				return;
			}
		}
	} else {
		return;
	}
}

function areawarn(){
	global $now,$arealist,$areanum,$areaadd,$areawarn;
	$areaaddlist = array_slice($arealist,$areanum+1,$areaadd);
	$areawarn = 1;
	storyputchat($now,'areawarn');
	systemputchat($now,'areawarn',$areaaddlist);
	return;
}

function duel($time = 0,$keyitm = ''){
	global $now,$gamestate,$name,$nick;
	if($gamestate < 30){
		return 30;
	} elseif($gamestate >= 50) {
		return 51;
	}	else{
		$time = $time == 0 ? $now : $time;
		$gamestate = 50;
		save_gameinfo();
		addnews($time,'duelkey',$nick.' '.$name,$keyitm);
		addnews($time,'duel');
		systemputchat($time,'duel');
		return 50;
	}
	
}
//------游戏结束------
//模式：0保留：程序故障；1：全部死亡；2：最后幸存；3：禁区解除；4：无人参加；5：核爆全灭；6：GM中止
function gameover($time = 0, $mode = '', $winname = '') {
	global $gamestate,$winmode,$alivenum,$winner,$now,$gamenum,$db,$tablepre,$gamenum,$starttime,$validnum,$hdamage,$hplayer;
	if($gamestate < 10){return;}
	if((!$mode)||(($mode==2)&&(!$winname))) {//在没提供游戏结束模式的情况下，自行判断模式
		if($validnum <= 0) {//无激活者情况下，全部死亡
			$alivenum = 0;
			$winmode = 4;
			$winner = '';
			
		} else {//判断谁是最后幸存者
			$result = $db->query("SELECT * FROM {$tablepre}players WHERE hp>0 AND type=0");
			$alivenum = $db->num_rows($result);
			if(!$alivenum) {//全部死亡
				$winmode = 1;
				$winner = '';
			} elseif($alivenum == 1) {//最后幸存
				$winmode = 2;
				$wdata = $db->fetch_array($result);
				$winner = $wdata['name'];
				$db->query("UPDATE {$tablepre}players SET state='5' where pid='{$wdata['pid']}'");
			} else {//不满足游戏结束条件，返回
				save_gameinfo();
				return;
			}
		}
	} else {//提供了游戏结束模式的情况下
		$winmode = substr($mode,3,1);
		$winner = $winname;
	}
	$time = $time ? $time : $now;
	$result = $db->query("SELECT gid FROM {$tablepre}winners ORDER BY gid DESC LIMIT 1");//判断当前游戏局数是否正确，以优胜列表为准
	if($db->num_rows($result)&&($gamenum <= $db->result($result, 0))) {
		$gamenum = $db->result($result, 0) + 1;
	}
	if($winmode == 4){//无人参加；不需要记录任何资料
		$getime = $time;
		$db->query("INSERT INTO {$tablepre}winners (gid,wmode,vnum,getime) VALUES ('$gamenum','$winmode','$validnum','$getime')");
	}	elseif(($winmode == 0)||($winmode == 1)||($winmode == 6)){//程序故障、全部死亡、GM中止，不需要记录优胜者资料
		$gstime = $starttime;
		$getime = $time;
		$gtime = $time - $starttime;
		$result = $db->query("SELECT name,killnum FROM {$tablepre}players WHERE type=0 order by killnum desc, lvl desc limit 1");
		$hk = $db->fetch_array($result);
		$hkill = $hk['killnum'];
		$hkp = $hk['name'];
		$db->query("INSERT INTO {$tablepre}winners (gid,wmode,vnum,gtime,gstime,getime,hdmg,hdp,hkill,hkp) VALUES ('$gamenum','$winmode','$validnum','$gtime','$gstime','$getime','$hdamage','$hplayer','$hkill','$hkp')");
	} else {//最后幸存、锁定解除、核爆全灭，需要记录优胜者资料
		$result = $db->query("SELECT * FROM {$tablepre}players WHERE name='$winner' AND type=0");
		$pdata = $db->fetch_array($result);
		$result2 = $db->query("SELECT motto FROM {$tablepre}users WHERE username='$winner'");
		$pdata['motto'] = $db->result($result2, 0);
		$result3 = $db->query("SELECT name,killnum FROM {$tablepre}players WHERE type=0 order by killnum desc, lvl desc limit 1");
		$hk = $db->fetch_array($result3);
		$pdata['hkill'] = $hk['killnum'];
		$pdata['hkp'] = $hk['name'];
		$pdata['wmode'] = $winmode;
		$pdata['vnum'] = $validnum;
		$pdata['gtime'] = $time - $starttime;
		$pdata['gstime'] = $starttime;
		$pdata['getime'] = $time;
		$pdata['hdmg'] = $hdamage;
		$pdata['hdp'] = $hplayer;
		$db->query("INSERT INTO {$tablepre}winners (gid,name,pass,type,endtime,gd,sNo,icon,club,hp,mhp,sp,msp,att,def,pls,lvl,`exp`,money,bid,inf,rage,pose,tactic,killnum,killnum2,state,wp,wk,wg,wc,wd,wf,teamID,teamPass,wep,wepk,wepe,weps,arb,arbk,arbe,arbs,arh,arhk,arhe,arhs,ara,arak,arae,aras,arf,arfk,arfe,arfs,art,artk,arte,arts,itm0,itmk0,itme0,itms0,itm1,itmk1,itme1,itms1,itm2,itmk2,itme2,itms2,itm3,itmk3,itme3,itms3,itm4,itmk4,itme4,itms4,itm5,itmk5,itme5,itms5,itm6,itmk6,itme6,itms6,motto,wmode,vnum,gtime,gstime,getime,hdmg,hdp,hkill,hkp,wepsk,arbsk,arhsk,arask,arfsk,artsk,itmsk0,itmsk1,itmsk2,itmsk3,itmsk4,itmsk5,itmsk6) VALUES ('".$gamenum."','".$pdata['name']."','".$pdata['pass']."','".$pdata['type']."','".$pdata['endtime']."','".$pdata['gd']."','".$pdata['sNo']."','".$pdata['icon']."','".$pdata['club']."','".$pdata['hp']."','".$pdata['mhp']."','".$pdata['sp']."','".$pdata['msp']."','".$pdata['att']."','".$pdata['def']."','".$pdata['pls']."','".$pdata['lvl']."','".$pdata['exp']."','".$pdata['money']."','".$pdata['bid']."','".$pdata['inf']."','".$pdata['rage']."','".$pdata['pose']."','".$pdata['tactic']."','".$pdata['killnum']."','".$pdata['killnum2']."','".$pdata['state']."','".$pdata['wp']."','".$pdata['wk']."','".$pdata['wg']."','".$pdata['wc']."','".$pdata['wd']."','".$pdata['wf']."','".$pdata['teamID']."','".$pdata['teamPass']."','".$pdata['wep']."','".$pdata['wepk']."','".$pdata['wepe']."','".$pdata['weps']."','".$pdata['arb']."','".$pdata['arbk']."','".$pdata['arbe']."','".$pdata['arbs']."','".$pdata['arh']."','".$pdata['arhk']."','".$pdata['arhe']."','".$pdata['arhs']."','".$pdata['ara']."','".$pdata['arak']."','".$pdata['arae']."','".$pdata['aras']."','".$pdata['arf']."','".$pdata['arfk']."','".$pdata['arfe']."','".$pdata['arfs']."','".$pdata['art']."','".$pdata['artk']."','".$pdata['arte']."','".$pdata['arts']."','".$pdata['itm0']."','".$pdata['itmk0']."','".$pdata['itme0']."','".$pdata['itms0']."','".$pdata['itm1']."','".$pdata['itmk1']."','".$pdata['itme1']."','".$pdata['itms1']."','".$pdata['itm2']."','".$pdata['itmk2']."','".$pdata['itme2']."','".$pdata['itms2']."','".$pdata['itm3']."','".$pdata['itmk3']."','".$pdata['itme3']."','".$pdata['itms3']."','".$pdata['itm4']."','".$pdata['itmk4']."','".$pdata['itme4']."','".$pdata['itms4']."','".$pdata['itm5']."','".$pdata['itmk5']."','".$pdata['itme5']."','".$pdata['itms5']."','".$pdata['itm6']."','".$pdata['itmk6']."','".$pdata['itme6']."','".$pdata['itms6']."','".$pdata['motto']."','".$pdata['wmode']."','".$pdata['vnum']."','".$pdata['gtime']."','".$pdata['gstime']."','".$pdata['getime']."','".$pdata['hdmg']."','".$pdata['hdp']."','".$pdata['hkill']."','".$pdata['hkp']."','".$pdata['wepsk']."','".$pdata['arbsk']."','".$pdata['arhsk']."','".$pdata['arask']."','".$pdata['arfsk']."','".$pdata['artsk']."','".$pdata['itmsk0']."','".$pdata['itmsk1']."','".$pdata['itmsk2']."','".$pdata['itmsk3']."','".$pdata['itmsk4']."','".$pdata['itmsk5']."','".$pdata['itmsk6']."')");
	}
	
	//检查成就
	include_once GAME_ROOT.'./include/game/achievement.func.php';
	check_end_achievement($winner,$winmode);
	
	
	rs_sttime();//重置游戏开始时间和当前游戏状态
	$gamestate = 0;
	save_gameinfo();
	//echo '**游戏结束**';
	//$gamestate = 0;
	//addnews($time, "end$winmode" , $winner);
	addnews($time, "end$winmode",$winner);
	//addnews($time, 'gameover',$gamenum);
	addnews($time, 'gameover' ,$gamenum);
	systemputchat($time,'gameover');
	include_once './include/news.func.php';
	$newsinfo = nparse_news(0,65535);
	writeover(GAME_ROOT."./gamedata/bak/{$gamenum}_newsinfo.html",$newsinfo,'wb+');
	//writeover(GAME_ROOT."./gamedata/bak/{$gamenum}_newsinfo.php",readover(GAME_ROOT.'./gamedata/newsinfo.php'),'wb+');
	//rs_sttime();
	//save_gameinfo();
	set_credits();
	return;
}

function movehtm($atime = 0) {
	global $plsinfo,$arealist,$areanum,$hack,$pls,$xyinfo,$areahour,$areaadd;

	/*$movehtm = GAME_ROOT.TPLDIR.'/move.htm';
	$movedata = '<option value="main">■ 移动 ■<br />';

	foreach($plsinfo as $key => $value) {
		if(array_search($key,$arealist) > $areanum || $hack){
		$movedata .= "<option value=\"$key\"><!--{if \$pls == $key}--><--现在位置--><!--{else}-->$value($xyinfo[$key])<!--{/if}--><br />";
		}
	} 
	writeover($movehtm,$movedata);*/
	
	/*$areahtm = GAME_ROOT.TPLDIR.'/areainfo.htm';
	$areadata = '<span class="evergreen"><b>现在的禁区是：</b></span>';
	for($i=0;$i<=$areanum;$i++){
		$areadata .= '&nbsp;'.$plsinfo[$arealist[$i]];
	}
	$areadata .= '<br><span class="evergreen"><b>下回的禁区是：</b></span>';*/
	
	$areadata = '';
	if(!$atime){
		global $areatime;
		$atime = $areatime;
	}
	if($areanum < count($plsinfo)) {
		$at= getdate($atime);
		$nexthour = $at['hours'];$nextmin = $at['minutes'];
		while($nextmin >= 60){
			$nexthour +=1;$nextmin -= 60;
		}
		if($nexthour >= 24){$nexthour-=24;}
		$areadata .= "<b>{$nexthour}时{$nextmin}分：</b> ";
		for($i=1;$i<=$areaadd;$i++) {
			$areadata .= '&nbsp;'.$plsinfo[$arealist[$areanum+$i]].'&nbsp;';
		}
	}
	if($areanum+$areaadd < count($plsinfo)) {
		$at2= getdate($atime + $areahour*60);
		$nexthour2 = $at2['hours'];$nextmin2 = $at2['minutes'];
		while($nextmin2 >= 60){
			$nexthour2 +=1;$nextmin2 -= 60;
		}
		if($nexthour2 >= 24){$nexthour2-=24;}
		$areadata .= "；<b>{$nexthour2}时{$nextmin2}分：</b> ";
		for($i=1;$i<=$areaadd;$i++) {
			$areadata .= '&nbsp;'.$plsinfo[$arealist[$areanum+$areaadd+$i]].'&nbsp;';
		}
	}
	if($areanum+$areaadd*2 < count($plsinfo)) {
		$at3= getdate($atime + $areahour*120);
		$nexthour3 = $at3['hours'];$nextmin3 = $at3['minutes'];
		while($nextmin3 >= 60){
			$nexthour3 +=1;$nextmin3 -= 60;
		}
		if($nexthour3 >= 24){$nexthour3-=24;}
		$areadata .= "；<b>{$nexthour3}时{$nextmin3}分：</b> ";
		for($i=1;$i<=$areaadd;$i++) {
			$areadata .= '&nbsp;'.$plsinfo[$arealist[$areanum+$areaadd*2+$i]].'&nbsp;';
		}
	}
	return $areadata;
	//writeover($areahtm,$areadata);
	//return;
}

function addnpc($type,$sub,$num,$time = 0,$clbstatus=NULL,$aitem=NULL,$apls=NULL) {
	global $now,$db,$tablepre,$log,$plsinfo,$typeinfo,$anpcinfo,$npcinit,$arealist,$areanum,$gamecfg;
	$time = $time == 0 ? $now : $time;
	$plsnum = sizeof($plsinfo);
	if(empty($anpcinfo) || empty($npcinit)){
		include_once config('addnpc',$gamecfg);
	}
	$npc=array_merge($npcinit,$anpcinfo[$type]);
	//$npcwordlist = Array();
	if(!$npc){
		//echo 'no npc.';
		return;
	} else {
		for($i=0;$i< $num;$i++){
			$npc = array_merge($npc,$npc['sub'][$sub]);		
			$npc['type'] = $type;
			$npc['endtime'] = $time;
			$npc['exp'] = round(($npc['lvl']*2+1)*$GLOBALS['baseexp']);
			$npc['sNo'] = $i;
			$npc['hp'] = $npc['mhp'];
			$npc['sp'] = $npc['msp'];
			if(!isset($npc['state'])){$npc['state'] = 0;}
			foreach(Array('p','k','g','c','d','f') as $val){
				if(!$npc['w'.$val]){
					$npc['w'.$val] = $npc['skill'];
				}
			}
			//$npc['wp'] = $npc['wk'] = $npc['wg'] = $npc['wc'] = $npc['wd'] = $npc['wf'] = $npc['skill'];
			if($npc['gd'] == 'r'){$npc['gd'] = rand(0,1) ? 'm':'f';}
			if($npc['pls'] == 99){
				$areaarr = array_slice($arealist,$areanum+1);
				if(empty($areaarr)){
					$npc['pls'] = 0;
				}else{
					shuffle($areaarr);
					$npc['pls'] = $areaarr[0];
				}
				//$npc['pls'] = rand(1,$plsnum-1);
			}	
			//自定义addnpc出现位置，会覆盖原本预设的位置。 TODO：要不要发个特别的news？
			if(isset($apls)) $npc['pls'] = (int)$apls;
			//自定义addnpc身上携带的道具，会覆盖原本预设的道具。 格式：$aitem=Array($iid=>Array($itm,$itmk,$itme,$itms,$itmsk),...)
			if(isset($aitem))
			{
				$aid = $aitem[0];
				$npc['itm'.$aid] = $aitem[1];$npc['itmk'.$aid] = $aitem[2];$npc['itme'.$aid] = $aitem[3];$npc['itms'.$aid] = $aitem[4];$npc['itmsk'.$aid] = $aitem[5];
			}
			//自定义addnpc身上的社团参数，会覆盖原本预设的参数。 格式：$clbstatus=Array('a'=>'int(10)',...'clbpara'=> $arr')
			if(isset($clbstatus))
			{
				foreach(Array('a','b','c','d','e') as $cbs)
				{
					if(isset($clbstatus[$cbs])) $npc['clbstatus'.$cbs] = $clbstatus[$cbs];
				}
				if(isset($clbstatus['clbpara']))
				{
					$npc['clbpara'] = is_array($npc['clbpara']) ? array_merge($npc['clbpara'],$clbstatus['clbpara']) : $clbstatus['clbpara'];
				}
			}
			//对将要插入数据库的npc数组格式化，现在可以直接在npc配置文件里预设那些后添加的字段了。
			$npc=player_format_with_db_structure($npc);
			$db->array_insert("{$tablepre}players", $npc);
			$summon_ids[] = $db->insert_id();
			//获取新生成npc的pid。不知道高并发时会不会出BUG……呃……出BUG了再看看？但是出BUG了我也不会修啊！
			$newsname=$typeinfo[$type].' '.$npc['name'];
			addnews($now, 'addnpc', $newsname);
		}
	}
	/*if($num > $npc['num']){
	//if($num > 1){
		$newsname=$typeinfo[$type];
		addnews($time, 'addnpcs', $newsname,$i);
	}else{
//		for($i=0;$i< $num;$i++){
//			addnews($time, 'addnpc', $npcwordlist[$i]);
//		}
		//$newsname=$typeinfo[$type].' '.$npc['name'];
		//addnews($time, 'addnpc', $newsname);
	}*/
	return $summon_ids;
}

function evonpc($type,$name){
	global $now,$db,$tablepre,$log,$plsinfo,$typeinfo,$enpcinfo,$gamecfg;
	if(!$type || !$name){return false;}
	if(empty($enpcinfo)){
		include_once config('evonpc',$gamecfg);
	}
	if(!isset($enpcinfo[$type])){return false;}
	$result = $db->query("SELECT * FROM {$tablepre}players WHERE type = '$type' AND name = '$name'");
	$num = $db->num_rows($result);
	if(!$num){return false;}	
	if(!isset($enpcinfo[$type][$name])){return false;}
	$npc=$enpcinfo[$type][$name];
	$npc['hp'] = $npc['mhp'];
	$npc['sp'] = $npc['msp'];
	$npc['exp'] = round(($npc['lvl']*2+1)*$GLOBALS['baseexp']);
	if(!isset($npc['state'])){$npc['state'] = 0;}
	$npc['wp'] = $npc['wk'] = $npc['wg'] = $npc['wc'] = $npc['wd'] = $npc['wf'] = $npc['skill'];
	unset($npc['skill']);
	$qry = '';
	foreach($npc as $key => $val){
		$qry .= "$key = '{$val}',";
	}
	if(!empty($qry)){
		$qry = substr($qry,0,-1);
		$db->query( "UPDATE {$tablepre}players SET $qry WHERE type = '$type' AND name = '$name'" );
	}
		
	return $npc;
}

function antiAFK($timelimit = 0){
	global $now,$db,$tablepre,$antiAFKertime,$alivenum,$deathnum;
	if(empty($timelimit)){
		$timelimit = $antiAFKertime;
	}
	$timelimit *= 60;
	$deadline=$now-$timelimit;
	$result = $db->query("SELECT * FROM {$tablepre}players WHERE type=0 AND endtime < '$deadline' AND hp>'0' AND state<'10'");
	while($al = $db->fetch_array($result)) {
		$afkerlist[$al['pid']]=Array('name' => $al['name'] ,'pls' => $al['pls']);
	}

	if(empty($afkerlist)){return;}
	foreach($afkerlist as $kid => $kcontent){
		$db->query("UPDATE {$tablepre}players SET hp='0',state='32' WHERE pid='$kid' AND type='0' AND hp>'0' AND state<'10'");
		if($db->affected_rows()){
			addnews($now,'death32',$kcontent['name'],'',$kcontent['pls']);
			$alivenum--;
			$deathnum++;			


		}
	}
	save_gameinfo();
	return;
}

function set_credits(){
	global $db,$tablepre,$winmode,$gamenum,$winner,$pdata,$gamblingon;
	$clist = $creditlist = $updatelist = Array();
	$result = $db->query("SELECT * FROM {$tablepre}users RIGHT JOIN {$tablepre}players ON {$tablepre}players.name={$tablepre}users.username WHERE {$tablepre}players.type='0'");
	while($data = $db->fetch_array($result)){
		$clist[$data['name']] = $data;
	}
	foreach($clist as $key => $val){
		$credits = get_credit_up($val,$winner,$winmode) + $val['credits'];
		$credits2 = $val['credits2'] + 10;
		$validgames = $val['validgames'] + 1;
		$wingames = $key == $winner ? $val['wingames'] + 1 : $val['wingames'];
		$updatelist[$key] = Array(
			'username' => $key,
			'credits' => $credits,
			'credits2' => $credits2,
			'wingames' => $wingames,
			'validgames' => $validgames,
		);
	}
	$db->multi_update("{$tablepre}users", $updatelist,'username');
	if($gamblingon){//赌注系统开启
		$updatelist2 = get_gambling_result($clist,$winner,$winmode);
		if($updatelist2){//必须分两次，因为涉及字段不同
			$db->multi_update("{$tablepre}users", $updatelist2,'username');
		}
	}
	//var_dump($updatelist);
	
	
	//$db->multi_update("{$tablepre}users", $updatelist2, 'username');
	//$db->multi_update("{$tablepre}users", $updatelist2, 'username');
//	$result = $db->query("SELECT * FROM {$tablepre}players WHERE type='0'");
//	$list = $creditlist = $updatelist = Array();
//	while($data = $db->fetch_array($result)){
//		$list[$data['name']]['players'] = $data;
//	}	
//	$result = $db->query("SELECT * FROM {$tablepre}users WHERE lastgame='$gamenum'");
//	while($data = $db->fetch_array($result)){
//		$list[$data['username']]['users'] = $data;
//	}
//	foreach($list as $key => $val){
//		if(isset($val['players']) && isset($val['users'])){
//			$credits = get_credit_up($val['players'],$winner,$winmode) + $val['users']['credits'];
//			$validgames = $val['users']['validgames'] + 1;
//			$wingames = $key == $winner ? $val['users']['wingames'] + 1 : $val['users']['wingames'];
//			$updatelist[] = Array('username' => $key, 'credits' => $credits, 'wingames' => $wingames, 'validgames' => $validgames);
//			if(!empty($obtain)){
//				$udghkey[] = $key;
//				if($pdata['name'] == $key){
//					$pdata['gainhonour'] = $obtain;
//				}else{
//					$udghlist[] = Array('name' => $key, 'gainhonour' => $obtain);
//				}
//			}			
//		}
//	}
//	$db->multi_update("{$tablepre}users", $updatelist, 'username');
//	if(!empty($udghkey)){
//		$udghkey = implode(',',$udghkey);
//		$db->multi_update("{$tablepre}players", $upghlist, 'name', "name IN ($udghkey)");
//	}
	//更新成就
	//$result = $db->query("SELECT * FROM {$tablepre}players WHERE {$tablepre}players.type='0'");
	//while($data = $db->fetch_array($result)) 
	//{
	//	$dlist[$data['name']] = $data['achievement'];
	//}
	//include_once GAME_ROOT.'./include/game/achievement.func.php';
	//foreach($dlist as $key => $val)
	//{
		//$v=$val; 
		//normalize_achievement($v,$c1,$c2);
		//$res = $db->query("SELECT * FROM {$tablepre}users WHERE username='".$key."'" );
		//$data=$db->fetch_array($res);
		//$c1+=$data['credits']; $c2+=$data['credits2'];
		//$db->query("UPDATE {$tablepre}users SET achievement='$v' WHERE username='".$key."'" );
		//$db->query("UPDATE {$tablepre}users SET credits='$c1' WHERE username='".$key."'" );
		//$db->query("UPDATE {$tablepre}users SET credits2='$c2' WHERE username='".$key."'" );
	//}
	return;
}

function get_credit_up($data,$winner = '',$winmode = 0){
	if($data['name'] == $winner){//获胜
		if($winmode == 2){$up = 200;}//最后幸存+200
		elseif($winmode == 3){$up = 500;}//解禁+500
		elseif($winmode == 5){$up = 100;}//核爆+100
		elseif($winmode == 7){$up = 10000;}//幻境解离+10000
		else{$up = 50;}//其他胜利方式+50（暂时没有这种胜利方式）
	}
	elseif($data['hp']>0){$up = 25;}//存活但不是获胜者+25
	else{$up = 10;}//死亡+5
	if($data['killnum']){
		$up += $data['killnum'] * 2;//杀一玩家/NPC加2
	}
	if($data['lvl']){
		$up += round($data['lvl'] /2);//等级每2级加1
	}
//	$skill = $data['wp'] + $data['wk'] + $data['wg'] + $data['wc'] + $data['wd'] + $data['wf'];
//	$maxskill = ;
	$skill = array ($data['wp'] , $data['wk'] , $data['wg'] , $data['wc'] , $data['wd'] , $data['wf']);
	rsort ( $skill );
	$maxskill = $skill[0];
	$up += round($maxskill / 25);//熟练度最高的系每25点熟练加1
	$up += round($data['money']/500);//每500点金钱加1
//	foreach(Array('wp','wk','wg','wc','wd','wf') as $val){
//		$skill = $data[$val];
//		$up += round($skill / 100);//每100点熟练加1
//	}
	return $up;
}

function get_gambling_result($clist, $winner='',$winmode=''){
	global $db,$tablepre,$hdamage,$validnum,$now,$areanum,$areaadd;
	$gblog = '';
	$gbfile = GAME_ROOT.TPLDIR.'/lastgb.htm';
	if(!in_array($winmode,Array(2,3,5,7))){//无人获胜，全部赌注被冴冴吃掉
		$gblog .= '无人获胜，全部切糕被冴冴吃掉！';
		$updatelist = false;
	}else{
		$result = $db->query("SELECT * FROM {$tablepre}gambling WHERE 1");
		if(!$db->num_rows($result)){
			$gblog .= '无人下注！';
			$updatelist = false;
		}else{
			$bwlist = $updatelist = Array();
			$bpool = $bwsum = $bwsum2 = 0;
			while($bdata = $db->fetch_array($result)){
				if($bdata['bname'] == $winner){
					$bwlist[$bdata['uname']] = $bdata;//此处只记录赌赢者的资料
					$bwsum += $bdata['wager'];//赌赢者本金数目
					$bwsum2 += $bdata['wager'] * $bdata['odds'];//赌赢者总系数
				}				
				$bpool += $bdata['wager'];//奖池记录所有玩家的赌注总额
			}
			
			//(所有入场玩家战斗力总和÷1000）×（每名玩家APM达到则1.0—1.1之间的随机数）×（连斗时间从10—0.1递减）=每局基础奖金——飞雪大魔王

			$creditsum = $apmnum = 0;
			foreach($clist as $cdata){
				$creditsum += $cdata['credits'];
				$apm = $cdata['deathtime'] > $cdata['validtime'] ? $cdata['cmdnum'] / ($cdata['deathtime'] - $cdata['validtime']) : $cdata['cmdnum'] / ($now - $cdata['validtime']);
				if($apm >= 1){$apmnum ++;}
			}
			
			$avrcredit = $creditsum / $validnum;//平均战斗力
			if($avrcredit > 10000){$creditodds = 1.25;}//平均战斗力超过10000则系数为1.25，否则系数减少，平均战斗力为4000时为1.1；
			else{$creditodds = round((1 + $avrcredit / 40000)*1000)/1000;}
			$apmodds = round(pow(1.02,$apmnum)*1000)/1000;//每有一名玩家达到60APM则乘以1.02
			$timeodds = 1.2 - $areanum/$areaadd * 0.1;//游戏结束时为0禁则系数为1.2，否则每禁系数减少0.1，不会低于0.8
			if($timeodds < 0.8){$timeodds = 0.8;}
			
			
			
//			$result3 = $db->query("SELECT cmdnum FROM {$tablepre}players WHERE type=0 ORDER BY cmdnum DESC LIMIT 10");
//			while($cdata = $db->fetch_array($result3)){
//				$cmdsum += $cdata['cmdnum'];
//			}
//			if($cmdsum <= 10000){$cmdodds = 1;}
//			elseif($cmdsum >= 30000){$cmdodds = 1.25;}
//			else{$cmdodds = 1+($cmdsum-10000)*0.0000125;}
			
			//$dmgprizeodds = 100 + round(pow($hdamage,0.5)) * 2;
			$obpool = $bpool;
			$bpool = round($bpool * $creditodds * $apmodds * $timeodds);
			$gblog = '奖池：'.$obpool.' * '.$creditodds.' * '.$apmodds.' * '.$timeodds.' = '.$bpool.'<br>';
			if($bwlist){
				$bnlist = array_keys($bwlist);
				$bnstr = "('".implode("','",$bnlist)."')";
				$result2 = $db->query("SELECT uid,username,credits2 FROM {$tablepre}users WHERE username IN $bnstr");
				while($udata = $db->fetch_array($result2)){
					$bwlist[$udata['username']]['credits2'] = $udata['credits2'];
				}
				if($bwsum >= $bpool){//奖池与本金相等，则大家拿回本金
					$gblog .= '奖池少于本金，系统资助判断正确者取回本金。';
					foreach($bwlist as $key => $val){
						$bwlist[$val['uname']]['crup'] = 0;
						$bwlist[$val['uname']]['crrst'] = $val['wager'];
						$credits2 = $val['credits2'] + $val['wager'];
						$updatelist[$key] = Array('username' => $key, 'credits2' => $credits2);
					}
				}else{//奖池大于本金，则大家拿回本金基础上，获胜者分得10%，其他人分掉额外的90%；
					$ext = $bpool - $bwsum;
					foreach($bwlist as $key => $val){
						$crup = ceil($ext * 0.9 * $val['wager'] * $val['odds'] / $bwsum2);
						$bwlist[$val['uname']]['crup'] = $crup;
						$bwlist[$val['uname']]['crrst'] = $val['wager'] + $crup;
						$credits2 = $val['credits2'] + $val['wager'] + $crup;
						$updatelist[$key] = Array('username' => $key, 'credits2' => $credits2);
					}
					$wcrup = ceil($ext * 0.1);
					$bwlist[] = Array('uname' => '获胜者', 'wager' => '', 'bname' => '', 'odds' => '', 'crup' => $wcrup, 'crrst' => $wcrup);
					if(is_array($updatelist) && isset($updatelist[$winner]['credits2'])){
						$updatelist[$winner]['credits2'] += $wcrup;
					}else{
						$result3 = $db->query("SELECT uid,username,credits2 FROM {$tablepre}users WHERE username='$winner'");
						$wdata = $db->fetch_array($result3);
						$updatelist[$winner] = Array('username' => $winner, 'credits2' => $wdata['credits2'] + $wcrup);
					}
				}
//				foreach($bwlist as $key => $val){//不保证本金，只瓜分奖池90%的奖励
//					$crup = $val['wager'] + round($bpool * 0.9 * $val['wager'] * $val['odds'] / $bwsum2);
//					$bwlist[$val['uname']]['crup'] = $crup;
//					$credits2 = $val['credits2'] + $crup;
//					$updatelist[$key] = Array('username' => $key, 'credits2' => $credits2);				
//				}
				
			}else{
				$gblog .= '无判断正确者，奖池的20%归获胜者。';
				$wcrup = ceil($bpool * 0.2);
				$bwlist[] = Array('uname' => '获胜者', 'wager' => '', 'bname' => '', 'odds' => '', 'crup' => $wcrup, 'crrst' => $wcrup);
				if(is_array($updatelist) && isset($updatelist[$winner]['credits2'])){
					$updatelist[$winner]['credits2'] += $wcrup;
				}else{
					$result3 = $db->query("SELECT uid,username,credits2 FROM {$tablepre}users WHERE username='$winner'");
					$wdata = $db->fetch_array($result3);
					$updatelist[$winner] = Array('username' => $winner, 'credits2' => $wdata['credits2'] + $wcrup);
				}
			}
		}
	}
	ob_start();
	include template('gbresult');
	$gbresult = ob_get_contents();
	ob_end_clean();
	writeover($gbfile,$gbresult);
	return $updatelist;
}
?>
