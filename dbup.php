<?php

define('CURSCRIPT', 'dbup');

define('IN_GAME', TRUE);
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
require config('gamecfg',$gamecfg);

//include GAME_ROOT.'./gamedata/gameinfo.php';
$q = "ALTER TABLE {$tablepre}winners ADD killnum2 smallint unsigned NOT NULL default 0 AFTER killnum";
echo $q.'<br>';
echo $db->query($q);
$q = "ALTER TABLE {$tablepre}users ADD credits2 mediumint NOT NULL default 0 AFTER credits";
echo $q.'<br>';
echo $db->query($q);
?>