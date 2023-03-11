<?php
if(!defined('IN_ADMIN')) {
	exit('Access Denied');
}

if($gamestate >= 20){
	require_once GAME_ROOT.'./include/system.func.php';
	
	$result = $db->query("SELECT pid FROM {$tablepre}players WHERE type=0");
	$validnum = $db->num_rows($result);
	
	$result = $db->query("SELECT pid FROM {$tablepre}players WHERE hp>0 AND type=0");
	$alivenum = $db->num_rows($result);
	
	$result = $db->query("SELECT pid FROM {$tablepre}players WHERE hp<=0 OR state>=10");
	$deathnum = $db->num_rows($result);
	
	movehtm();
	
	save_gameinfo();
	
	adminlog('infomng');
	
	$cmd_info = "状态更新：激活人数 {$validnum},生存人数 {$alivenum},死亡人数 {$deathnum}<br>";
	$cmd_info .= "已重置移动地点缓存数据";
}else{
	$cmd_info = "当前游戏未开始！";
}

//$db->query("ALTER TABLE {$tablepre}winners ADD itmsk6 char(5) not null default '' AFTER itmsk5");
//$db->query("ALTER TABLE {$tablepre}winners ADD itms6 char(5) not null default '0' AFTER itmsk5");
//$db->query("ALTER TABLE {$tablepre}winners ADD itme6 mediumint unsigned NOT NULL default '0' AFTER itmsk5");
//$db->query("ALTER TABLE {$tablepre}winners ADD itmk6 char(5) not null default '' AFTER itmsk5");
//$db->query("ALTER TABLE {$tablepre}winners ADD itm6 CHAR( 30 ) NOT NULL default '' AFTER itmsk5");
//$db->query("ALTER TABLE {$tablepre}winners CHANGE itme0 itme0 mediumint unsigned NOT NULL default '0'");
//$db->query("ALTER TABLE {$tablepre}winners CHANGE itme1 itme1 mediumint unsigned NOT NULL default '0'");
//$db->query("ALTER TABLE {$tablepre}winners CHANGE itme2 itme2 mediumint unsigned NOT NULL default '0'");
//$db->query("ALTER TABLE {$tablepre}winners CHANGE itme3 itme3 mediumint unsigned NOT NULL default '0'");
//$db->query("ALTER TABLE {$tablepre}winners CHANGE itme4 itme4 mediumint unsigned NOT NULL default '0'");
//$db->query("ALTER TABLE {$tablepre}winners CHANGE itme5 itme5 mediumint unsigned NOT NULL default '0'");
//$db->query("ALTER TABLE {$tablepre}users ADD validgames smallint unsigned NOT NULL default '0' AFTER credits");
//$db->query("ALTER TABLE {$tablepre}users ADD wingames smallint unsigned NOT NULL default '0' AFTER validgames");

//UNCOMMENT THOSE WHEN YELLOWLIFE IS PUSHED TO MOMOBAKO-SERIES
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

//game表结构变动
$db->query("ALTER TABLE {$tablepre}game DROP gamevars");
$db->query("ALTER TABLE {$tablepre}game ADD gamevars text NOT NULL AFTER combonum");

include template('admin_menu');

?>