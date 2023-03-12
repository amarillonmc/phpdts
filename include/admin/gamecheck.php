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

include template('admin_menu');

?>