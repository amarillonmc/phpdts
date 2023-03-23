<?php

//set_magic_quotes_runtime(0);

define('IN_GAME', TRUE);
define('GAME_ROOT', substr(dirname(__FILE__), 0, -7));
define('GAMENAME', 'bra');

if(version_compare(PHP_VERSION, '4.3.0', '<')) {
	exit('PHP version must >= 4.3.0!');
}
require GAME_ROOT.'./include/global.func.php';
require GAME_ROOT.'./include/resources.func.php';
require GAME_ROOT.'./include/game/revclubskills.func.php';
require GAME_ROOT.'./include/game/dice.func.php';
error_reporting(E_ALL);
set_error_handler('gameerrorhandler');
$magic_quotes_gpc = false;
extract(gstrfilter($_COOKIE));
extract(gstrfilter($_POST));
extract(gstrfilter($_GET));
//$_GET = gstrfilter($_GET);
$_REQUEST = gstrfilter($_REQUEST);
$_FILES = gstrfilter($_FILES);

require GAME_ROOT.'./config.inc.php';



//$errorinfo ? error_reporting(E_ALL) : error_reporting(0);
date_default_timezone_set('Etc/GMT');
//$now = time() + $moveutmin*60;
$now = time() + $moveut*3600 + $moveutmin*60;   
list($sec,$min,$hour,$day,$month,$year,$wday) = explode(',',date("s,i,H,j,n,Y,w",$now));


//if($attackevasive) {
//	include_once GAME_ROOT.'./include/security.inc.php';
//}

require GAME_ROOT.'./include/db_'.$database.'.class.php';
$db = new dbstuff;
$db->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect);
//$db->select_db($dbname);
unset($dbhost, $dbuser, $dbpw, $dbname, $pconnect);

require GAME_ROOT.'./gamedata/system.php';
require config('resources',$gamecfg);
require config('gamecfg',$gamecfg);
require config('combatcfg',$gamecfg);
require config('clubskills',$gamecfg);
require config('dialogue',$gamecfg);
require config('audio',$gamecfg);
require config('tooltip',$gamecfg);

include GAME_ROOT.'./gamedata/combatinfo.php';

ob_start();

if(CURSCRIPT !== 'chat'){
	//if($gzipcompress && function_exists('ob_gzhandler') && CURSCRIPT != 'wap') {
	//	ob_start('ob_gzhandler');
	//} else {
	//	$gzipcompress = 0;
	//	ob_start();
	//}
	
	//$gamestate状态：0-上局游戏结束；10-新游戏准备阶段；20-游戏开放激活；30-游戏停止激活；40-游戏连斗；50-游戏死斗。
	//$pt = getmicrotime();
	
	$plock=fopen(GAME_ROOT.'./gamedata/process.lock','ab');
	flock($plock,LOCK_EX);
	load_gameinfo();
	$lostfocus = false;
	
//	$losttime = $now - $lastupdate;
//	if($losttime >= $lostfocusmin * 60){
//		$result = $db->query("SELECT pid FROM {$tablepre}players WHERE endtime > '$lastupdate' AND type = '0'");
//		if(!$db->num_rows($result)){//满足失去焦点的判定条件
//			$lostfocus = true;
//			echo 'LOST FOCUS:'.$losttime;
//			if($now - $afktime > $losttime){
//				$afktime = $now;
//				addnews();
//			}
//		}
//	}

	//include GAME_ROOT.'./gamedata/gameinfo.php';
	$ginfochange = false;
//	$old_gamestate = $gamestate;
//	$old_areanum = $areanum;
	if(!$gamestate) { //判定游戏准备
		if(($starttime)&&($now > $starttime - $startmin*60)) {
			$gamenum++;
			$gamestate = 10;
			$hdamage = 0;
			$hplayer = '';
			$noisemode = '';
			//save_gameinfo();
			include_once GAME_ROOT.'./include/system.func.php';
			rs_game(1+2+4+8+16+32);
			//save_gameinfo();
			$ginfochange = true;
		}
	}
	if($gamestate == 10) {//判定游戏开始
		if($now >= $starttime) {
			$gamestate = 20;
			//save_gameinfo();
			//addnews($starttime,'newgame',$gamenum);
			addnews($starttime,'newgame',$gamenum);
			systemputchat($starttime,'newgame');
			//是否部署BOT -> 数量;  
			$gamevars['botplayer'] = $rsgame_bots;
			$ginfochange = true;
		}
	}
	//if (($gamestate > 10)&&($now > $areatime)) {
	//	include_once GAME_ROOT.'./include/system.func.php';
	//	addarea($areatime);
	//	save_gameinfo();
	//}
	//$combatinfo = file_get_contents(GAME_ROOT.'./gamedata/combatinfo.php');
	//list($hdamage,$hplayer,$noisetime,$noisepls,$noiseid,$noiseid2,$noisemode) = explode(',',$combatinfo);
	if (($gamestate > 10)&&($now > $areatime)) {//判定增加禁区
		include_once GAME_ROOT.'./include/system.func.php';
		while($now>$areatime){
			$o_areatime = $areatime;
			$areatime += $areahour*60;
			//save_gameinfo();
			add_once_area($o_areatime);
			$areawarn = 0;
			//save_gameinfo();
			$ginfochange = true;
	//		testlog('禁区增加');
		}
		//addarea($areatime);
	}elseif(($gamestate > 10)&&($now > $areatime - $areawarntime)&&(!$areawarn)){//判定警告增加禁区
		include_once GAME_ROOT.'./include/system.func.php';
		areawarn();
		//save_gameinfo();
		$ginfochange = true;
	}
	
	if($gamestate == 20) {
		$arealimit = $arealimit > 0 ? $arealimit : 1; 
		if(($validnum <= 0)&&($areanum >= $arealimit*$areaadd)) {//判定无人参加并结束游戏
			gameover($areatime-3599,'end4');
		} elseif(($areanum >= $arealimit*$areaadd) || ($validnum >= $validlimit)) {//判定游戏停止激活
			$gamestate = 30;
			//save_gameinfo();
			$ginfochange = true;
		}
	}
	
	if($gamestate < 40 && $gamestate > 20 && $alivenum <= $combolimit) {//判定进入连斗条件1：停止激活时玩家数少于特定值
		$gamestate = 40;
		addnews($now,'combo');
		systemputchat($now,'combo');
		$ginfochange = true;
	}elseif($gamestate < 40 && $gamestate >= 20 && $combonum && $deathnum >= $combonum){//判定进入连斗条件2：死亡人数超过特定公式计算出的值
		$real_combonum = $deathlimit + ceil($validnum/$deathdeno) * $deathnume;
		if($deathnum >= $real_combonum){
			$gamestate = 40;
			addnews($now,'combo');
			systemputchat($now,'combo');
		}else{
			$combonum = $real_combonum;
			addnews($now,'comboupdate',$combonum,$deathnum);
			systemputchat($now,'comboupdate',$combonum);
		}		
		$ginfochange = true;
	}
	
//	if((($gamestate == 30)&&($alivenum <= $combolimit))||($deathlimit&&($gamestate < 40)&&($gamestate >= 20)&&($deathnum >= $deathlimit))) {//判定进入连斗
//		$gamestate = 40;
//		//save_gameinfo();
//		//$db->query("UPDATE {$tablepre}players SET teamID='',teamPass='' WHERE type=0 ");
//		addnews($now,'combo');
//		systemputchat($now,'combo');
//		$ginfochange = true;
//	}
	
	if (($gamestate >= 40)&&($now > $afktime + $antiAFKertime * 60)) {//判定自动反挂机
		include_once GAME_ROOT.'./include/system.func.php';
		antiAFK();
		$afktime = $now;
		//echo 'afk';
		//save_gameinfo();
		$ginfochange = true;
	}
	
	if($gamestate >= 40) {
		if($alivenum <= 1) {
			include_once GAME_ROOT.'./include/system.func.php';
			gameover();
	//		testlog('游戏结束');
		}
	}
	
	if($ginfochange || $lostfocus){
		save_gameinfo();
	}
	
	fclose($plock); 
}

if (CURSCRIPT == 'botservice')
{
	$cuser = $botname;
	$cpass = $botpass;
}
else
{
	$cuser = & ${$tablepre.'user'};
	$cpass = & ${$tablepre.'pass'};
}

//function testlog($name){
//	global $month,$day,$hour,$min,$sec,$old_gamestate,$gamestate,$old_areanum,$areanum,$pt;
//	//$a = file_get_contents(GAME_ROOT.'./gamedata/gameinfo.php');
//	$pt2 = getmicrotime();
//	$nowtime = "{$month}月{$day}日{$hour}时{$min}分{$sec}秒；{$pt} {$pt2}";
//	$a = "{$nowtime}\n {$name}\n 『旧游戏状态:{$old_gamestate}；新游戏状态:{$gamestate}』\n 『旧禁区数目:{$old_areanum}；新禁区数目:{$areanum}』\n\n";
//	$filec = file_get_contents(GAME_ROOT.'./log.txt');
//	$a = $a.$filec;
//	file_put_contents(GAME_ROOT.'./log.txt',$a);
//	return;
//}
?>
