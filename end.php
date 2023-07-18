<?php

define('CURSCRIPT', 'end');

require './include/common.inc.php';
require './include/game.func.php';
if(!$cuser||!$cpass) { gexit($_ERROR['no_login'],__file__,__line__); } 
$result = $db->query("SELECT * FROM {$tablepre}players WHERE name = '$cuser' AND type = 0");
if(!$db->num_rows($result)) { header("Location: index.php");exit(); }

$pdata = $db->fetch_array($result);
if($pdata['pass'] != $cpass) {
	$tr = $db->query("SELECT `password` FROM {$gtablepre}users WHERE username='$cuser'");
	$tp = $db->fetch_array($tr);
	$password = $tp['password'];
	if($password == $cpass) {
		$db->query("UPDATE {$tablepre}players SET pass='$password' WHERE name='$cuser'");
	} else {
		gexit($_ERROR['wrong_pw'],__file__,__line__);
	}
}

extract($pdata);
init_playerdata();

if($hp<=0 || $state>=10) {
	$result = $db->query("SELECT lastword FROM {$gtablepre}users WHERE username='$name'");
	$motto = $db->result($result,0);
	$dtime = date("Y年m月d日H时i分s秒",$endtime);
	if($bid) {
		$result = $db->query("SELECT name FROM {$tablepre}players WHERE pid='$bid'");
		if($db->num_rows($result)) { $kname = $db->result($result,0); }
	}
}

if ($udata['u_templateid'] == 1 && !strstr($_SERVER['HTTP_REFERER'], 'php') && $_SERVER['HTTP_REFERER'] != '') {
  echo json_encode(array(
	"page" => "end",
	"title" => $hp <= 0 ? $stateinfo[$state] : $gwin[$winmode],
	"deadInfo" => $hp <= 0 ? array(
		"avatar" => $iconImg,
		"motto" => $motto,
		"info" => $dinfo[$state],
		"time" => $dtime,
		"killer" => !empty($kname) && $state >= 10 ? $kname : null,
	) : null,
	"flag" => array(
		"number" => $sNo,
		"killNum" => $killnum,
		"winner" => $winner,
		"state" => $state,
	)
  ));
} else {
  include template('ending');
}


?>