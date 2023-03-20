<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}
# TODO: NO GLOBAL

# resources_1.php
function get_equip_list()
{
	//装备列表
	$equip_list = Array('wep','arb','arh','ara','arf','art');
	return $equip_list;
}

# mixitem_1.php; vnmixitem_1.php;
function get_mixinfo()
{
	include config("mixitem",1);
	include config("vnmixitem",1);
	if(!empty($vn_mixinfo)) $mixinfo = array_merge($mixinfo,$vn_mixinfo);
	return $mixinfo;
}

# achievement_1.php
function get_achtype()
{
	include config("achievement",1);
	return $ach_type;
}
function get_hidden_achtype()
{
	include config("achievement",1);
	return $hidden_ach_type;
}
function get_achlist($a=NULL)
{
	include config("achievement",1);
	if(isset($a) && isset($ach_list[$a])) return $ach_list[$a];
	return $ach_list;
}

# setitems_1.php
function get_set_items()
{
	include config('setitems',1);
	return $set_items;
}
function get_set_items_info()
{
	include config('setitems',1);
	return $set_items_info;
}


?>
