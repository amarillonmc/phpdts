<?php

define('CURSCRIPT', 'game');
require './include/common.inc.php';
require GAME_ROOT.'./include/game.func.php';


if(!$cuser||!$cpass) { gexit($_ERROR['no_login'],__file__,__line__); }
if(isset($mode) && $mode == 'quit') {

	gsetcookie('user','');
	gsetcookie('pass','');
	header("Location: index.php");
	exit();

}
//$result = $db->query("SELECT * FROM {$tablepre}players WHERE name = '$cuser' AND type = 0");
$pdata = fetch_playerdata_by_name($cuser);
if(!$pdata) { header("Location: valid.php".'?'.$_SERVER['QUERY_STRING']);exit(); }

//$pdata = $db->fetch_array($result);
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

if($gamestate == 0) {
	header("Location: end.php");exit();
}

//临时用
$gametype = 0;

$pdata['clbpara'] = get_clbpara($pdata['clbpara']);
//Also copying for $weppara, $itmpara and such.
$pdata['weppara'] = get_itmpara($pdata['weppara']);
$pdata['wep2para'] = get_itmpara($pdata['wep2para']);
//$pdata['itm0para'] = get_itmpara($pdata['itm0para']);
//$pdata['itm1para'] = get_itmpara($pdata['itm1para']);
//$pdata['itm2para'] = get_itmpara($pdata['itm2para']);
//$pdata['itm3para'] = get_itmpara($pdata['itm3para']);
//$pdata['itm4para'] = get_itmpara($pdata['itm4para']);
//$pdata['itm5para'] = get_itmpara($pdata['itm5para']);
//$pdata['itm6para'] = get_itmpara($pdata['itm6para']);
$pdata['itmpara0'] = get_itmpara($pdata['itmpara0']);
$pdata['itmpara1'] = get_itmpara($pdata['itmpara1']);
$pdata['itmpara2'] = get_itmpara($pdata['itmpara2']);
$pdata['itmpara3'] = get_itmpara($pdata['itmpara3']);
$pdata['itmpara4'] = get_itmpara($pdata['itmpara4']);
$pdata['itmpara5'] = get_itmpara($pdata['itmpara5']);
$pdata['itmpara6'] = get_itmpara($pdata['itmpara6']);
$pdata['arbpara'] = get_itmpara($pdata['arbpara']);
$pdata['arhpara'] = get_itmpara($pdata['arhpara']);
$pdata['arapara'] = get_itmpara($pdata['arapara']);
$pdata['arfpara'] = get_itmpara($pdata['arfpara']);
$pdata['artpara'] = get_itmpara($pdata['artpara']);

extract($pdata,EXTR_REFS);
init_playerdata();
init_profile();

$log = '';
//读取聊天信息
$chatdata = getchat(0,$teamID);
//读取表情信息
$emdata = get_emdata();



//读取玩家互动信息
$result = $db->query("SELECT lid,time,log FROM {$tablepre}log WHERE toid = '$pid' AND prcsd = 0 ORDER BY time,lid");
$llist = '';
while($logtemp = $db->fetch_array($result)){
	$log .= date("H:i:s",$logtemp['time']).'，'.$logtemp['log'].'<br />';
	$llist .= $logtemp['lid'].',';
}
if(!empty($llist)){
	$llist = '('.substr($llist,0,-1).')';
	$db->query("UPDATE {$tablepre}log SET prcsd=1 WHERE toid = '$pid' AND lid IN $llist");
}
if($hp > 0){//判断冷却时间是否过去
	//显示枪声信息
	if(($now <= $noisetime+$noiselimit)&&$noisemode&&($noiseid!=$pid)&&($noiseid2!=$pid)) {
		if(($now-$noisetime) < 60) {
			$noisesec = $now - $noisetime;
			$log .= "<span class=\"yellow\">{$noisesec}秒前，{$plsinfo[$noisepls]}传来了{$noiseinfo[$noisemode]}。</span><br>";
		} else {
			$noisemin = floor(($now-$noisetime)/60);
			$log .= "<span class=\"yellow\">{$noisemin}分钟前，{$plsinfo[$noisepls]}传来了{$noiseinfo[$noisemode]}。</span><br>";
		}
	}
	if($coldtimeon){
		$cdover = $cdsec*1000 + $cdmsec + $cdtime;
		$nowmtime = floor(getmicrotime()*1000);
		$rmcdtime = $nowmtime >= $cdover ? 0 : $cdover - $nowmtime;
	}
}
//var_dump($itm3);
if($hp <= 0){
	$dtime = date("Y年m月d日H时i分s秒",$endtime);
	$kname='';
	if($bid) {
		$result = $db->query("SELECT name FROM {$tablepre}players WHERE pid='$bid'");
		if($db->num_rows($result)) { $kname = $db->result($result,0); }
	}

	// 检查是否需要显示RuleSet结束剧情
	if(isset($groomid) && empty($clbpara['ruleset_ending_shown'])) {
		$room_id = intval($groomid);
		$result = $db->query("SELECT gruleset FROM {$gtablepre}game WHERE groomid = {$room_id}");
		if ($db->num_rows($result)) {
			$room_data = $db->fetch_array($result);
			$ruleset_id = $room_data['gruleset'];
			if (!empty($ruleset_id)) {
				include_once GAME_ROOT.'./gamedata/ruleset/story_config.php';
				$story = get_ruleset_story($ruleset_id, 'ending');
				if ($story) {
					// 标记结束剧情已显示
					$clbpara['ruleset_ending_shown'] = true;
					$db->query("UPDATE {$tablepre}players SET clbpara='".json_encode($clbpara, JSON_UNESCAPED_UNICODE)."' WHERE name='$cuser'");

					// 设置结束剧情显示
					$opendialog = 'ruleset_ending';
					$dialogue_id = 'ruleset_ending';

					// 动态添加RuleSet结束剧情到对话系统
					global $dialogues, $dialogue_log;
					$dialogues['ruleset_ending'] = array(
						0 => $story['content']
					);
					$dialogue_log['ruleset_ending'] = "<span class='red'>※ 时光重现结束</span><br>{$story['title']}<br><br>";
				}
			}
		}
	}

	$mode = 'death';
} elseif($state ==1 || $state == 2 || $state == 3){
	$mode = 'rest';
} elseif($itms0){
	$mode = 'itemmain';
} else {
	$mode = 'command';
}
$command = 'enter';
$cmd = $main = '';
if($action == 'corpse' || $action == 'pacorpse' && $gamestate<40){
	$cid = $bid;
	if($cid){
		$result = $db->query("SELECT * FROM {$tablepre}players WHERE pid='$cid' AND hp=0");
		if($db->num_rows($result)>0){
			$edata = $db->fetch_array($result);
			include_once GAME_ROOT.'./include/game/battle.func.php';
			findcorpse($edata);
			extract($edata,EXTR_PREFIX_ALL,'w');
			init_battle_rev($pdata,$edata,1);
			$main = 'battle_rev';
		}
	}
}
elseif($action == 'chase' || $action == 'pchase' || $action == 'dfight'){
	$enemyid = $bid;
	$result = $db->query("SELECT * FROM {$tablepre}players WHERE pid='$enemyid' AND hp>0 AND pls='$pls'");
	if($db->num_rows($result)>0){
		$edata = $db->fetch_array($result);
		include_once GAME_ROOT.'./include/game/revbattle.func.php';
		\revbattle\findenemy_rev($edata);
		$main = 'battle_rev';
	}
}
elseif($action == 'neut'){
	$nid = $bid;
	if($nid){
		$result = $db->query("SELECT * FROM {$tablepre}players WHERE pid='$nid' AND hp>0");
		if($db->num_rows($result)>0){
			$edata = $db->fetch_array($result);
			include_once GAME_ROOT.'./include/game/revbattle.func.php';
			\revbattle\findneut($edata,1);
			extract($edata,EXTR_PREFIX_ALL,'w');
			init_battle_rev($pdata,$edata,1);
			$main = 'battle_rev';
		}
	}
}
if($hp > 0 && $coldtimeon && $showcoldtimer && $rmcdtime){$log .= "行动冷却时间：<span id=\"timer\" class=\"yellow\">0.0</span>秒<script type=\"text/javascript\">demiSecTimerStarter($rmcdtime);</script><br>";}
//如果身上存在时效性技能，检查技能是否超时
if($hp > 0 && !empty($clbpara['lasttimes']))
{
	$flag = check_skilllasttimes($pdata);
	if($flag)
	{
		player_save($pdata);
		$pdata = fetch_playerdata_by_name($cuser);
		$pdata['clbpara'] = get_clbpara($pdata['clbpara']);
		extract($pdata,EXTR_REFS);
		init_playerdata();
		init_profile();
	}
}
if($hp > 0 && !empty($clbpara['skill']) && in_array('inf_dizzy',$clbpara['skill']))
{
	$dizzy_times = (($clbpara['starttimes']['inf_dizzy'] + $clbpara['lasttimes']['inf_dizzy']) - $now)*1000;
	$log .= '<span class="yellow">你现在处于眩晕状态，什么都做不了！</span><br>眩晕状态持续时间还剩：<span id="timer" class="yellow">'.$dizzy_times.'.0</span>秒<br><script type="text/javascript">demiSecTimerStarter('.$dizzy_times.');</script>';
}
if ($club==0)
{
	include_once GAME_ROOT.'./include/game/clubslct.func.php';
	getclub($name,$c1,$c2,$c3);
	$clubavl[0]=0; $clubavl[1]=$c1; $clubavl[2]=$c2; $clubavl[3]=$c3;
}
// 检查是否有对话需要显示，但如果刚刚处理了对话选择，则不显示
// 通过检查 $_POST['command'] 是否包含 'dialogue_choice' 来判断
$just_made_choice = isset($_POST['command']) && strpos($_POST['command'], 'dialogue_choice') === 0;

// 检查是否有RuleSet开场剧情需要显示
if(!$just_made_choice && !empty($clbpara['ruleset_opening_story']) && empty($clbpara['ruleset_story_shown']))
{
	// 标记剧情已显示，避免重复显示
	$clbpara['ruleset_story_shown'] = true;
	$db->query("UPDATE {$tablepre}players SET clbpara='".json_encode($clbpara, JSON_UNESCAPED_UNICODE)."' WHERE name='$cuser'");

	// 显示RuleSet剧情
	$opendialog = 'ruleset_opening';
	$dialogue_id = 'ruleset_opening';

	// 动态添加RuleSet剧情到对话系统
	include_once GAME_ROOT.'./gamedata/ruleset/story_config.php';
	$room_id = intval($groomid);
	$result = $db->query("SELECT gruleset FROM {$gtablepre}game WHERE groomid = {$room_id}");
	if ($db->num_rows($result)) {
		$room_data = $db->fetch_array($result);
		$ruleset_id = $room_data['gruleset'];
		if (!empty($ruleset_id)) {
			$story = get_ruleset_story($ruleset_id, 'opening');
			if ($story) {
				// 将RuleSet剧情内容注入到对话系统
				global $dialogues, $dialogue_log;
				$dialogues['ruleset_opening'] = array(
					0 => $story['content']
				);
				$dialogue_log['ruleset_opening'] = "<span class='lime'>※ 时光重现开始！</span><br>欢迎来到{$story['title']}的世界。<br><br>";
			}
		}
	}
}
elseif(!$just_made_choice && (!empty($clbpara['dialogue']) || !empty($clbpara['noskip_dialogue'])))
{
	if(!empty($clbpara['dialogue'])) {
		$opendialog = 'dialogue';
		$dialogue_id = $clbpara['dialogue'];
	} else {
		$opendialog = $clbpara['noskip_dialogue'];
	}
}
if(isset($opendialog))
{
	$log.="<script>
	var dialogElement = document.getElementById('{$opendialog}');
	if(dialogElement && dialogElement.showModal) {
		dialogElement.showModal();
	}
	</script>";
}

//if (!strstr($_SERVER['HTTP_REFERER'], 'php') && $_SERVER['HTTP_REFERER'] != '') {
if (isset($_GET['is_new'])) {
	include './api.php';
} else {
	include template('game');
}

?>
