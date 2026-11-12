<?php
if(!defined('IN_GAME')) exit('Access Denied');

function ending_routes_achievement_enabled()
{
	if(function_exists('ending_routes_enabled')) return ending_routes_enabled();
	global $ending_routes_v2, $club_skills;
	return !empty($ending_routes_v2) && (!isset($club_skills) || $club_skills);
}

function ending_routes_achievement_account($data, $fallback='')
{
	$para = isset($data['clbpara']) ? $data['clbpara'] : array();
	if(!is_array($para)) $para = json_decode($para, true);
	return !empty($para['ai_master']) ? $para['ai_master'] : (isset($data['name']) ? $data['name'] : $fallback);
}

// 天赋点按已完成的 630~657 成就推导；此处只保存唯一事实，避免重复结算加点。
function ending_routes_award_achievement($id, $account, $mark=1)
{
	global $db, $gtablepre;
	$id = (int)$id;
	if($id < 630 || $id > 657 || $account === '') return false;
	$who = "X'".bin2hex((string)$account)."'";
	for($retry=0; $retry<8; $retry++) {
		$result = $db->query("SELECT achrev FROM {$gtablepre}users WHERE username={$who}");
		$row = $db->fetch_array($result);
		if(!$row) return false;
		$raw = (string)$row['achrev'];
		$ach = json_decode($raw, true);
		if(!is_array($ach)) $ach = array();
		if(!empty($ach[$id]['l'])) return false;
		$progress = $id == 649 ? ((int)($ach[$id]['v'] ?? 0) | ((int)$mark & 3)) : 1;
		$level = ($id != 649 || $progress == 3) ? 1 : 0;
		if(isset($ach[$id]) && $ach[$id] == array('v'=>$progress, 'l'=>$level)) return false;
		$ach[$id] = array('v'=>$progress, 'l'=>$level);
		$encoded = json_encode($ach);
		if($encoded === false) return false;
		$db->query("UPDATE {$gtablepre}users SET achrev=X'".bin2hex($encoded)."' WHERE username={$who} AND BINARY COALESCE(achrev,'')=X'".bin2hex($raw)."'");
		if($db->affected_rows()) return (bool)$level;
	}
	return false;
}

function ending_routes_check_kill_achievement($pa, $pd)
{
	if(!ending_routes_achievement_enabled() || !empty($pa['type'])) return;
	$para = $pd['clbpara'] ?? array();
	if(!is_array($para)) $para = json_decode($para, true);
	if(!isset($para['er_sub'])) return;
	$sub = (int)$para['er_sub'];
	$type = (int)$pd['type'];
	$id = 0;
	if($type == 34 && $sub >= 0 && $sub < 8) $id = 634 + $sub;
	elseif($type == 37 && $sub >= 0 && $sub < 7) $id = 642 + $sub;
	elseif($type == 39 && $sub >= 0 && $sub < 8) $id = 650 + $sub;
	elseif($type == 35 && $sub >= 0 && $sub < 2) $id = 649;
	if($id) ending_routes_award_achievement($id, ending_routes_achievement_account($pa), $id == 649 ? (1 << $sub) : 1);
}

function ending_routes_check_end_achievement($who, $mode, $data)
{
	global $gamevars;
	if(!ending_routes_achievement_enabled()) return false;
	$route = $gamevars['ending_subroute'] ?? '';
	$routes = array('jiejin_v'=>array(3,630), 'jiejin_Zv'=>array(3,631), 'jieli_phantom'=>array(7,632), 'jieli_true'=>array(7,633));
	if(!isset($routes[$route]) || (int)$mode !== $routes[$route][0]) return false;
	ending_routes_award_achievement($routes[$route][1], ending_routes_achievement_account($data, $who));
	return true;
}
