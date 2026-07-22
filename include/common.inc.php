<?php

//set_magic_quotes_runtime(0);

define('IN_GAME', TRUE);
define('GAME_ROOT', substr(dirname(__FILE__), 0, -7));
define('GAMENAME', 'bra');

if(version_compare(PHP_VERSION, '4.3.0', '<')) {
	exit('PHP version must >= 4.3.0!');
}
require GAME_ROOT.'./include/global.func.php';
require GAME_ROOT.'./include/user.func.php';
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

// 检查是否直接使用主数据库 (slave_level = 3)
if(isset($slave_level) && $slave_level == 3 && !empty($master_dbhost) && !empty($master_dbuser) && !empty($master_dbname)) {
	$db->connect($master_dbhost, $master_dbuser, $master_dbpw, $master_dbname, $pconnect);
	$gtablepre = $master_tablepre;
} else {
	$db->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect);
	$gtablepre = $tablepre;
}
//$db->select_db($dbname);
unset($dbhost, $dbuser, $dbpw, $dbname, $pconnect);

require GAME_ROOT.'./gamedata/system.php';
require GAME_ROOT.'./include/init.func.php';
require GAME_ROOT.'./include/news.func.php';
require GAME_ROOT.'./include/resources.func.php';
require GAME_ROOT.'./include/roommng.func.php';
require GAME_ROOT.'./include/game/revclubskills.func.php';
require GAME_ROOT.'./include/game/dice.func.php';
require GAME_ROOT.'./include/game/titles.func.php';

// $gtablepre 已在数据库连接时设置，这里不再重新赋值
if(!isset($gtablepre)) {
	$gtablepre = $tablepre;
}

// RuleSet字段必须先于房间列表读取和残留房间修复存在，也因此早于第一次config()查询。
// The RuleSet column must exist before room reads and stale-room repair, and therefore before config() too.
roommng_ensure_ruleset_game_structure();

ob_start();

$cuser = & ${$gtablepre.'user'};
$cpass = & ${$gtablepre.'pass'};

$roomlist = Array();
$result = $db->query("SELECT * FROM {$gtablepre}game WHERE groomid>0");
while($roominfo = $db->fetch_array($result))
{
	$roomlist[$roominfo['groomid']] = $roominfo;
}

if($cuser) $udata = fetch_userdata_by_username($cuser);

// 在用户数据加载后重新设置模板
if(isset($udata) && $udata && isset($udata['u_templateid'])) {
    $user_templateid = intval($udata['u_templateid']);

    // 由于PHP常量不能重新定义，我们需要使用全局变量来覆盖
    global $TEMPLATEID_OVERRIDE, $TPLDIR_OVERRIDE;

    switch($user_templateid) {
        case 1:
            // LULUXIA模板（未实装）
            $TEMPLATEID_OVERRIDE = 1;
            $TPLDIR_OVERRIDE = './templates/luluxia';
            // 如果模板目录不存在，fallback到默认模板
            if(!file_exists(GAME_ROOT.'./templates/luluxia')) {
                $TPLDIR_OVERRIDE = './templates/default';
            }
            break;
        case 2:
            // NOUVEAU模板
            $TEMPLATEID_OVERRIDE = 2;
            $TPLDIR_OVERRIDE = './templates/nouveau';
            // 如果模板目录不存在，fallback到默认模板
            if(!file_exists(GAME_ROOT.'./templates/nouveau')) {
                $TPLDIR_OVERRIDE = './templates/default';
            }
            break;
        default:
            // 默认模板 - 使用已定义的常量
            $TEMPLATEID_OVERRIDE = null;
            $TPLDIR_OVERRIDE = null;
            break;
    }
}

$groomid = isset($udata['roomid']) ? $udata['roomid'] : 0;

if(!empty($groomid))
{
	$result = $db->query("SELECT * FROM {$gtablepre}game WHERE groomid='$groomid'");
	if(!$db->num_rows($result))
	{
		roommng_create_new_room($udata);
		/*$gr = $db->query("SELECT gamenum FROM {$gtablepre}game WHERE groomid=0");
		$gnums = $db->result($result, 0) + $groomid;
		$starttime = $now + $startmin*5;
		$db->query("INSERT INTO {$gtablepre}game (gamenum,groomid,groomnums,gamestate,starttime) VALUES ('$gnums','$groomid','1','0','$starttime')");*/
	}
}

$tablepre = !empty($groomid) ? $tablepre.'s'.$groomid.'_' : $tablepre;

// 现在$groomid已经设置，可以正确加载RuleSet资源文件
require config('resources',$gamecfg);
require config('gamecfg',$gamecfg);
require config('combatcfg',$gamecfg);
require config('clubskills',$gamecfg);
require config('dialogue',$gamecfg);
require config('audio',$gamecfg);
require config('tooltip',$gamecfg);
require config('titles',$gamecfg);

// 初始化RuleSet覆盖系统
include_once GAME_ROOT.'./include/ruleset_override.func.php';
init_ruleset_override();

// 加载RuleSet覆盖函数
load_ruleset_override_functions();

// 现在加载system.func.php
require GAME_ROOT.'./include/system.func.php';

// 检查数据库结构更新（在配置文件加载后执行）
if(isset($need_update_db_structrue) && $need_update_db_structrue) {
	roommng_verify_db_game_structure();
}

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
			//include_once GAME_ROOT.'./include/system.func.php';
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
			
			# 小房间开始游戏
			if(!empty($groomid))
			{
				addnews($starttime,'newroomgame',$gamenum,$groomid);
			}
			# 大房间开始游戏
			else 
			{
				addnews($starttime,'newgame',$gamenum);
				# 是否部署BOT -> 数量;  只有大房间会部署bot
				$gamevars['botplayer'] = $rsgame_bots;
			}

			systemputchat($starttime,'newgame');
			$ginfochange = true;
		}
	}
	//判定增加禁区
	if (($gamestate > 10)&&($now > $areatime)) {
		//include_once GAME_ROOT.'./include/system.func.php';
		while($now>$areatime){
			$o_areatime = $areatime;
			$areatime += $areahour*60;
			add_once_area($o_areatime);
			$areawarn = 0;
			$ginfochange = true;
		}
	//判定警告增加禁区	
	}elseif(($gamestate > 10)&&($now > $areatime - $areawarntime)&&(!$areawarn)){
		//include_once GAME_ROOT.'./include/system.func.php';
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
		//include_once GAME_ROOT.'./include/system.func.php';
		antiAFK();
		$afktime = $now;
		$ginfochange = true;
	}
	
	if($gamestate >= 40) {
		$result = $db->query("SELECT pid FROM {$tablepre}players WHERE hp>0 AND type=0");
		$alivenum = $db->num_rows($result);
		save_gameinfo();
		$should_auto_gameover = $alivenum <= 1;
		# RuleSet钩子：允许规则集覆盖连斗阶段的自动结局判断。
		# RuleSet hook: allow rulesets to override combo-stage automatic game over.
		if(function_exists('ruleset_should_auto_gameover'))
		{
			$ruleset_auto_gameover = ruleset_should_auto_gameover($alivenum,'common_combo');
			if($ruleset_auto_gameover !== NULL) $should_auto_gameover = (bool)$ruleset_auto_gameover;
		}
		# 无人存活时始终结束游戏，不允许规则集覆盖。
		# Always end the game when nobody survives; rulesets cannot override this case.
		if($alivenum <= 0) $should_auto_gameover = true;
		if($should_auto_gameover) {
			//include_once GAME_ROOT.'./include/system.func.php';
			gameover();
		}
	}
	
	if($ginfochange || $lostfocus){
		save_gameinfo();
	}
	
	//除拉取聊天以外的访问都判定一下是否有新的站内信。
	include_once GAME_ROOT.'./include/messages.func.php';
	$new_messages = message_check_new($cuser);
	
	fclose($plock); 
}
?>
