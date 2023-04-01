<?php

define('CURSCRIPT', 'rank');

require './include/common.inc.php';
require './include/game.func.php';
include_once GAME_ROOT.'./include/game/titles.func.php';

$result = $db->query("SELECT COUNT(*) FROM {$gtablepre}users");
$count = $db->result($result,0);
if($ranklimit < 1){$ranklimit = 1;}
//$ostart = -1;
//if(!isset($command) || !isset($start) || $start < 0) {
//	$ostart = $start = 0;
//}elseif($command == 'last'){
//	$ostart = $start;
//	$start -= $ranklimit;
//}elseif($command == 'next'){
//	$ostart = $start;
//	$start += $ranklimit;
//}
//
//if($count == 0){gexit('No data!');}
//if($start < 0){$start = 0;}
//elseif($start + $ranklimit > $count){
//	if($count - $ranklimit >= 0){
//		$start = $count - $ranklimit;
//	}else{
//		$start = 0;
//	}
//}


//elseif($start + $ranklimit > $count){$ranklimit = $count - $start;}

//$startnum = $start + 1;
//if($start + $ranklimit > $count){
//	$endnum = $count;
//}else{
//	$endnum = $start + $ranklimit;
//}

if(!isset($checkmode) || $checkmode == 'credits'){
	$result = $db->query("SELECT * FROM {$gtablepre}users WHERE validgames>0 ORDER BY credits DESC, credits2 DESC, wingames DESC, uid ASC LIMIT $ranklimit");
}elseif($checkmode == 'credits2'){
	$result = $db->query("SELECT * FROM {$gtablepre}users WHERE validgames>0 ORDER BY credits2 DESC, credits DESC, wingames DESC, uid ASC LIMIT $ranklimit");
}elseif($checkmode == 'winrate'){
	$mingames = $winratemingames >= 1 ? $winratemingames : 1;
	$result = $db->query("SELECT * FROM {$gtablepre}users WHERE validgames>='$mingames' ORDER BY (wingames/validgames) DESC, credits DESC, credits2 DESC, uid ASC LIMIT $ranklimit");
}
$rankdata = Array();
$n = 1;
while($data = $db->fetch_array($result)){
	$data['img'] = $data['gender'] == 'm' ? 'm_'.$data['icon'].'.gif' : 'f_'.$data['icon'].'.gif';
	//$data['motto'] = $data['motto'] ? rep_label($data['motto']) : '';
	//$data['slhonour'] = $data['honour'] ? init_honourwords($data['honour'],99) : '';
	//$data['honour'] = $data['honour'] ? init_honourwords($data['honour']) : '';
	$data['number'] = $n;
	$data['winrate'] = $data['wingames'] ? round($data['wingames']/$data['validgames']*100).'%' : '0%';
	$data['nickinfo'] = !empty($data['nick']) ? get_title_desc($data['nick']) : '-';
	$rankdata[] = $data;
	$n ++;
}

if(isset($schname) && !empty($schname)){
	$result = $db->query("SELECT * FROM {$gtablepre}users WHERE username='$schname'");
	if(!$db->num_rows($result)){
	}
}
		
if(isset($checkmode)){
	include template('rankinfo');
	$showdata['innerHTML']['rank'] = ob_get_contents();
	ob_clean();
	if(isset($error)){$showdata['innerHTML']['error'] = $error;}
	$jgamedata = compatible_json_encode($showdata);
	echo $jgamedata;
	ob_end_flush();
}else{
	include template('rank');
}


function schname($schname){
	global $db,$tablepre;
	$schrst = Array();
	$result = $db->query("SELECT * FROM {$gtablepre}users WHERE username='$schname'");
	if($db->num_rows($result)){
		$schobj = $db->fetch_array($result);
		$result2=$db->query("SELECT COUNT FROM {$gtablepre}users WHERE ");
	}
}
?>