
<?php

if(!defined('IN_GAME')) {
	exit('Access Denied');
}
global $gamecfg;
include_once config('combatcfg',$gamecfg);

/*
$poseinfo = Array('通常','作战姿态','','探物姿态','偷袭姿态','治疗姿态');
$tacinfo = Array('通常','','重视防御','重视反击','重视躲避',);

*/

//发现率修正 find_r,越大越容易发现目标
function get_find_r($weather = 0,$pls = 0,$pose = 0,$tactic = 0,$club = 0,$inf = ''){
	$_FIND = Array
		(
		'weather' => array(10,20,0,-2,-3,-10,-7,5,-10,-20,0,-7,-5,-30,-5,-20,0),
		//'weather' => array(10,20,0,-2,-3,-7,-10,-5,10,0,0,-7,-5,-30),
		'pls' => array(10,0,0,10,-10,10,0,10,-10,0,10,0,0,-10,0,-10,-10,-10,0,10,0,10),
		'pose' => array(0,0,0,25,-10,-25),
		'tactic' => array(),
		);
	$find_r = 0;
	$find_r += $_FIND['pose'][$pose];
	$find_r += $_FIND['weather'][$weather];
	$find_r += $_FIND['pls'][$pls];
	
	return $find_r;
}
                                                            

//躲避率修正 hide_r,越大越不容易被发现
function get_hide_r($weather = 0,$pls = 0,$pose = 0,$tactic = 0,$club = 0,$inf = ''){
	$_HIDE = Array
		(
		'weather' => array(),
		'pls' => array(),
		'pose' => array(0,-25,0,-10,10,-25),
		//'tactic' => array(),
		//'pose' => array(),
		'tactic' => array(0,0,0,-15,15),
		);
	$hide_r = 0;
	$hide_r += $_HIDE['tactic'][$tactic];
	return $hide_r;
}
//先攻几率修正，越大越容易先攻                                                            
function get_active_r($weather = 0,$pls = 0,$pose = 0,$tactic = 0,$club = 0,$inf = '',$wpose = 0){
	global $active_obbs,$inf_active_p;
	$_ACTIVE = Array
		(
		'weather' => array(10,20,0,-5,-10,-20,-15,0,-7,-10,-10,-5,0,-5,-20,-5,0),
		//'weather' => array(20,10,0,-3,-5,-5,-7,10,-10,-10,-10,-5,0,-5),
		'pls' => array(),
		'pose' => array(0,0,0,0,25,-25),
		'tactic' => array(),
		);
	$_DACTIVE= Array
		(
		'pose' => array(0,0,50,0,0,0),
		);
	$active_r = $active_obbs;
	$active_r += $_ACTIVE['weather'][$weather];
	$active_r += $_ACTIVE['pose'][$pose];
	$active_r -= $_DACTIVE['pose'][$wpose];
	foreach ($inf_active_p as $inf_ky => $value) {
		if(strpos($inf, $inf_ky)!==false){$active_r *= $value;}
	}	
	//echo 'active:'.$active_r.' ';
	return $active_r;
}
//命中率修正
function get_hitrate($wkind = 'N',$skill = 0,$club = 0,$inf = ''){
	global $hitrate_obbs,$hitrate_max_obbs,$hitrate_r,$weather,$inf_htr_p;
	$hitrate = $hitrate_obbs[$wkind];
	$hitrate += round($skill * $hitrate_r[$wkind]); 
	if($hitrate > $hitrate_max_obbs[$wkind]) {$hitrate = $hitrate_max_obbs[$wkind];}
	foreach ($inf_htr_p as $inf_ky => $value) {
		if(strpos($inf, $inf_ky)!==false){$hitrate *= $value;}
	}	
	if($weather == 12){$hitrate += 20;}
	//echo 'wkind:'.$wkind.' skill:'.$skill.' club:'.$club.' hitrate:'.$hitrate.' ';
	return $hitrate;
}

//获取反击几率
function get_counter($wkind = 'N',$tactic = 0,$club = 0,$inf = ''){
	global $counter_obbs,$inf_counter_p;
	$counter = $counter_obbs[$wkind];
	if($tactic == 4) {
		$counter = 0;
	} elseif($tactic == 3) {
		$counter += 30;
	}
	foreach ($inf_counter_p as $inf_ky => $value) {
		if(strpos($inf, $inf_ky)!==false){$counter *= $value;}
	}	
	//echo 'counter:'.$counter.' ';
	return $counter;
}
//攻击力修正，百分比增加
function get_attack_p($weather = 0,$pls = 0,$pose = 0,$tactic = 0,$club = 0,$inf = '',$active = 1){
	global $inf_att_p;
	$_ATTACK = Array
		(
		'weather' => array(10,10,0,-5,-10,-20,-15,0,0,7,20,-7,-20,-5,-10,-10,-10),
		'pls' => array(0,0,0,0,0,0,10,0,0,-10,0,0,0,0,-10,0,0,0,10,0,0,0),
		'pose' => array(0,100,0,-25,25,-50),
		'tactic' => array(0,20,-25,25,-50),
		);

	$attack = 100;
	$attack += $_ATTACK['weather'][$weather];
	$attack += $_ATTACK['pls'][$pls];
	if($active){$attack += $_ATTACK['pose'][$pose];}
	else{$attack += $_ATTACK['tactic'][$tactic];}
	foreach ($inf_att_p as $inf_ky => $value) {
		if(strpos($inf, $inf_ky)!==false){$attack *= $value;}
	}	
/*	if(strpos($inf,'a') !== false){$attack -= 20;}
	if(strpos($inf,'u') !== false){$attack -= 30;}*/
	$attack = $attack > 0 ? $attack : 1;
	
	return $attack/100;
}
//防御力修正，百分比
function get_defend_p($weather = 0,$pls = 0,$pose = 0,$tactic = 0,$club = 0,$inf = '',$active = 1){
	global $inf_def_p;
	$_DEFEND = Array
		(
		'weather' => array(10,30,0,0,-3,-15,-10,0,-20,-30,-50,-5,-20,-3,-20,5,-30),
		'pls' => array(0,-10,10,0,0,0,0,0,0,0,0,-10,10,0,0,0,0,0,0,0,10,0),
		'pose' => array(0,25,0,-25,-50,-50),
		'tactic' => array(0,-20,50,-25,0),
		);

	$defend = 100;
	$defend += $_DEFEND['weather'][$weather];
	$defend += $_DEFEND['pls'][$pls];
	if($active){$defend += $_DEFEND['pose'][$pose];}
	else{$defend += $_DEFEND['tactic'][$tactic];}
	foreach ($inf_def_p as $inf_ky => $value) {
		if(strpos($inf, $inf_ky)!==false){$defend *= $value;}
	}	
	/*if(strpos($inf,'b') !== false){$defend -= 20;}
	if(strpos($inf,'i') !== false){$attack -= 10;}*/
	$defend = $defend > 0 ? $defend : 1;
	
	return $defend/100;
}


?>
