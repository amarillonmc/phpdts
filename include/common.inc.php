<?php

//set_magic_quotes_runtime(0);

define('IN_GAME', TRUE);
define('GAME_ROOT', substr(dirname(__FILE__), 0, -7));
define('GAMENAME', 'bra');

if(version_compare(PHP_VERSION, '4.3.0', '<')) {
	exit('PHP version must >= 4.3.0!');
}
require GAME_ROOT.'./include/global.func.php';
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
require GAME_ROOT.'./include/init.func.php';
require GAME_ROOT.'./include/resources.func.php';
require GAME_ROOT.'./include/roommng.func.php';
require GAME_ROOT.'./include/game/revclubskills.func.php';
require GAME_ROOT.'./include/game/dice.func.php';
require config('resources',$gamecfg);
require config('gamecfg',$gamecfg);
require config('combatcfg',$gamecfg);
require config('clubskills',$gamecfg);
require config('dialogue',$gamecfg);
require config('audio',$gamecfg);
require config('tooltip',$gamecfg);

$gtablepre = $tablepre;

if($need_update_db_structrue) roommng_verify_db_game_structure();

ob_start();

$cuser = & ${$gtablepre.'user'};
$cpass = & ${$gtablepre.'pass'};

$roomlist = Array();
$result = $db->query("SELECT * FROM {$gtablepre}game WHERE groomid>0");
while($roominfo = $db->fetch_array($result))
{
	$roomlist[$roominfo['groomid']] = $roominfo;
}

if($cuser)
{
	$tr = $db->query("SELECT * FROM {$gtablepre}users WHERE username='$cuser'");
	$tp = $db->fetch_array($tr);
}
$rid = isset($tp['roomid']) ? $tp['roomid'] : 0;
$groomid = $rid;

if(!empty($rid))
{
	$result = $db->query("SELECT * FROM {$gtablepre}game WHERE groomid='$rid'");
	if(!$db->num_rows($result))
	{
		$gr = $db->query("SELECT gamenum FROM {$gtablepre}game WHERE groomid=0");
		$gnums = $db->result($result, 0) + $rid;
		$starttime = $now + $startmin*5;
		$db->query("INSERT INTO {$gtablepre}game (gamenum,groomid,groomnums,gamestate,starttime) VALUES ('$gnums','$rid','1','0','$starttime')");
	}
}

$tablepre = !empty($rid) ? $tablepre.'s'.$rid.'_' : $tablepre;

if(CURSCRIPT !== 'chat')
{
	$plock=fopen(GAME_ROOT.'./gamedata/process.lock','ab');
	flock($plock,LOCK_EX);
	load_gameinfo();
	$lostfocus = false;
	$ginfochange = false;
	//判定游戏准备
	if(!$gamestate)
	{ 
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
	//判定游戏开始
	if($gamestate == 10) 
	{
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
	//判定增加禁区
	if (($gamestate > 10)&&($now > $areatime)) {
		include_once GAME_ROOT.'./include/system.func.php';
		while($now>$areatime){
			$o_areatime = $areatime;
			$areatime += $areahour*60;
			add_once_area($o_areatime);
			$areawarn = 0;
			$ginfochange = true;
		}
	//判定警告增加禁区	
	}elseif(($gamestate > 10)&&($now > $areatime - $areawarntime)&&(!$areawarn)){
		include_once GAME_ROOT.'./include/system.func.php';
		areawarn();
		$ginfochange = true;
	}

	if($gamestate == 20) {
		$arealimit = $arealimit > 0 ? $arealimit : 1; 
		if(($validnum <= 0)&&($areanum >= $arealimit*$areaadd)) {//判定无人参加并结束游戏
			gameover($areatime-3599,'end4');
		} elseif(($areanum >= $arealimit*$areaadd) || ($validnum >= $validlimit)) {//判定游戏停止激活
			$gamestate = 30;
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
	
	if (($gamestate >= 40)&&($now > $afktime + $antiAFKertime * 60)) {//判定自动反挂机
		include_once GAME_ROOT.'./include/system.func.php';
		antiAFK();
		$afktime = $now;
		$ginfochange = true;
	}
	
	if($gamestate >= 40) {
		if($alivenum <= 1) {
			include_once GAME_ROOT.'./include/system.func.php';
			gameover();
		}
	}
	
	if($ginfochange || $lostfocus){
		save_gameinfo();
	}
	
	fclose($plock); 
}
?>
