<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE);
set_magic_quotes_runtime(0);
//ini_set('date.timezone','Asia/Shanghai');
$now = time(); 
define('IN_GAME', TRUE);
define('GAME_ROOT', substr(dirname(__FILE__), 0, 0));
define('GAMENAME', 'bra');
if(PHP_VERSION < '4.3.0') {
	exit('PHP version must >= 4.3.0!');
}
require_once GAME_ROOT.'./include/global.func.php';
require_once GAME_ROOT.'./config.inc.php';

extract(gaddslashes($_COOKIE));
extract(gaddslashes($_POST));
extract(gaddslashes($_GET));

if($attackevasive) {
	include_once GAME_ROOT.'./include/security.inc.php';
}

if($gzipcompress && function_exists('ob_gzhandler') && CURSCRIPT != 'wap') {
	ob_start('ob_gzhandler');
} else {
	$gzipcompress = 0;
	ob_start();
}

require_once GAME_ROOT.'./include/db_'.$database.'.class.php';
$db = new dbstuff;
$db->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect);
unset($dbhost, $dbuser, $dbpw, $dbname, $pconnect);
$db->select_db($dbname);
require_once GAME_ROOT.'./gamedata/system.php';
if(!$username||!$password){
	gexit($_ERROR['login_info'],__file__,__line__);
}else{
	include_once GAME_ROOT.'./gamedata/system.php';

	if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
		$onlineip = getenv('HTTP_CLIENT_IP');
	} elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
		$onlineip = getenv('HTTP_X_FORWARDED_FOR');
	} elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
		$onlineip = getenv('REMOTE_ADDR');
	} elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
		$onlineip = $_SERVER['REMOTE_ADDR'];
	}

	$password = md5($password);
	$groupid = 1;
	$credits = 0;
	$gender = 0;

	$result = $db->query("SELECT * FROM {$tablepre}users WHERE username = '$username'");
	if(!$db->num_rows($result)) {
		$groupid = 1;
		$db->query("INSERT INTO {$tablepre}users (username,`password`,groupid,ip,credits,gender) VALUES ('$username', '$password', '$groupid', '$onlineip', '$credits', '$gender')");
	} else {
		$userdata = $db->fetch_array($result);
		if($userdata['groupid'] <= 0){
			gexit($_ERROR['user_ban'],__file__,__line__);
		} elseif($userdata['password'] != $password) {
			gexit($_ERROR['login_check'],__file__,__line__);
		} else {

		}
	}
	gsetcookie('user',$username);
	gsetcookie('pass',$password);
}

Header("Location: index.php");
exit();

?>

