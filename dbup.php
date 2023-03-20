<?php

define('CURSCRIPT', 'dbup');

require './include/common.inc.php';

if(!$cuser||!$cpass) { gexit($_ERROR['no_login'],__file__,__line__); }
$result = $db->query("SELECT * FROM {$tablepre}users WHERE username='$cuser'");
if(!$db->num_rows($result)) { gexit($_ERROR['login_check'],__file__,__line__); }
$udata = $db->fetch_array($result);
if($udata['password'] != $cpass) { gexit($_ERROR['wrong_pw'], __file__, __line__); }
elseif(($udata['groupid'] <= 1)&&($cuser!==$gamefounder)) { gexit($_ERROR['no_admin'], __file__, __line__); }

/*define('IN_GAME', TRUE);
define('GAME_ROOT', dirname(__FILE__));
define('GAMENAME', 'bra');

if(version_compare(PHP_VERSION, '4.3.0', '<')) {
	exit('PHP version must >= 4.3.0!');
}
require GAME_ROOT.'./include/global.func.php';

$magic_quotes_gpc = get_magic_quotes_gpc();
extract(gstrfilter($_COOKIE));
extract(gstrfilter($_POST));
unset($_GET);
$_FILES = gstrfilter($_FILES);

require GAME_ROOT.'./config.inc.php';

$errorinfo ? error_reporting(E_ALL) : error_reporting(0);
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
require config('gamecfg',$gamecfg);*/

//include GAME_ROOT.'./gamedata/gameinfo.php';
/*$q = "ALTER TABLE {$tablepre}winners ADD killnum2 smallint unsigned NOT NULL default 0 AFTER killnum";
echo $q.'<br>';
echo $db->query($q);
$q = "ALTER TABLE {$tablepre}users ADD credits2 mediumint NOT NULL default 0 AFTER credits";
echo $q.'<br>';
echo $db->query($q);*/

//winner表新增字段
$result = $db->query("DESCRIBE {$tablepre}winners nick");
if(!$db->num_rows($result)) $db->query("ALTER TABLE {$tablepre}winners ADD nick text not null AFTER type");
$result = $db->query("DESCRIBE {$tablepre}winners ss");
if(!$db->num_rows($result)) $db->query("ALTER TABLE {$tablepre}winners ADD ss mediumint unsigned NOT NULL default '0' AFTER msp");
$result = $db->query("DESCRIBE {$tablepre}winners mss");
if(!$db->num_rows($result)) $db->query("ALTER TABLE {$tablepre}winners ADD mss smallint unsigned NOT NULL default '0' AFTER ss");
$result = $db->query("DESCRIBE {$tablepre}winners skillpoint");
if(!$db->num_rows($result)) $db->query("ALTER TABLE {$tablepre}winners ADD skillpoint smallint unsigned NOT NULL default '0' AFTER nick");
$result = $db->query("DESCRIBE {$tablepre}winners teamMate");
if(!$db->num_rows($result)) $db->query("ALTER TABLE {$tablepre}winners ADD teamMate text NOT NULL default '' AFTER teamPass");
$result = $db->query("DESCRIBE {$tablepre}winners teamIcon");
if(!$db->num_rows($result)) $db->query("ALTER TABLE {$tablepre}winners ADD teamIcon smallint unsigned NOT NULL default '0' AFTER teamMate");
$result = $db->query("DESCRIBE {$tablepre}winners clbpara");
if(!$db->num_rows($result)) $db->query("ALTER TABLE {$tablepre}winners ADD clbpara text NOT NULL AFTER teamMate");

//以下内容均为chatGPT生成，让我们对新时代的赛博苦力致以敬意：
$db->query("ALTER TABLE {$tablepre}winners MODIFY wep char(30) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY wepk char(40) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY wepe int(10) unsigned NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY weps char(10) NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY wepsk char(40) NOT NULL default ''");

$db->query("ALTER TABLE {$tablepre}winners MODIFY arb char(30) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY arbk char(40) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY arbe int(10) unsigned NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY arbs char(10) NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY arbsk char(40) NOT NULL default ''");

$db->query("ALTER TABLE {$tablepre}winners MODIFY arh char(30) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY arhk char(40) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY arhe int(10) unsigned NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY arhs char(10) NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY arhsk char(40) NOT NULL default ''");

$db->query("ALTER TABLE {$tablepre}winners MODIFY ara char(30) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY arak char(40) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY arae int(10) unsigned NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY aras char(10) NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY arask char(40) NOT NULL default ''");

$db->query("ALTER TABLE {$tablepre}winners MODIFY arf char(30) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY arfk char(40) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY arfe int(10) unsigned NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY arfs char(10) NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY arfsk char(40) NOT NULL default ''");

$db->query("ALTER TABLE {$tablepre}winners MODIFY art char(30) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY artk char(40) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY arte int(10) unsigned NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY arts char(10) NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY artsk char(40) NOT NULL default ''");

$db->query("ALTER TABLE {$tablepre}winners MODIFY itm0 char(30) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itmk0 char(40) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itme0 int(10) unsigned NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itms0 char(10) NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itmsk0 char(40) NOT NULL default ''");

$db->query("ALTER TABLE {$tablepre}winners MODIFY itm1 char(30) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itmk1 char(40) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itme1 int(10) unsigned NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itms1 char(10) NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itmsk1 char(40) NOT NULL default ''");

$db->query("ALTER TABLE {$tablepre}winners MODIFY itm2 char(30) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itmk2 char(40) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itme2 int(10) unsigned NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itms2 char(10) NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itmsk2 char(40) NOT NULL default ''");

$db->query("ALTER TABLE {$tablepre}winners MODIFY itm3 char(30) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itmk3 char(40) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itme3 int(10) unsigned NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itms3 char(10) NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itmsk3 char(40) NOT NULL default ''");

$db->query("ALTER TABLE {$tablepre}winners MODIFY itm4 char(30) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itmk4 char(40) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itme4 int(10) unsigned NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itms4 char(10) NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itmsk4 char(40) NOT NULL default ''");

$db->query("ALTER TABLE {$tablepre}winners MODIFY itm5 char(30) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itmk5 char(40) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itme5 int(10) unsigned NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itms5 char(10) NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itmsk5 char(40) NOT NULL default ''");

$db->query("ALTER TABLE {$tablepre}winners MODIFY itm6 char(30) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itmk6 char(40) NOT NULL default ''");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itme6 int(10) unsigned NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itms6 char(10) NOT NULL DEFAULT '0'");
$db->query("ALTER TABLE {$tablepre}winners MODIFY itmsk6 char(40) NOT NULL default ''");


//user表结构变动
$result = $db->query("DESCRIBE {$tablepre}users volume");
if($db->num_rows($result)) $db->query("ALTER TABLE {$tablepre}users DROP volume");
$result = $db->query("DESCRIBE {$tablepre}users achrev");
if(!$db->num_rows($result)) $db->query("ALTER TABLE {$tablepre}users ADD achrev text NOT NULL default '' AFTER achievement");
$result = $db->query("DESCRIBE {$tablepre}users daily");
if(!$db->num_rows($result)) $db->query("ALTER TABLE {$tablepre}users ADD daily varchar(255) NOT NULL DEFAULT '' AFTER achrev");

//game表结构变动
$db->query("ALTER TABLE {$tablepre}game DROP gamevars");
$db->query("ALTER TABLE {$tablepre}game ADD gamevars text NOT NULL AFTER combonum");

echo "Update Fish.<br>";
?>