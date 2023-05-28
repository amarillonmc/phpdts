<?php


define('CURSCRIPT', 'index');

require './include/common.inc.php';


$timing = 0;
if($gamestate > 10) {
	$timing = $now - $starttime;
} else {
	if($starttime > $now) {
		$timing = $starttime - $now;
	} else {
		$timing = 0;
	}
}

$adminmsg = file_get_contents('./gamedata/adminmsg.htm') ;
$systemmsg = file_get_contents('./gamedata/systemmsg.htm') ;


if(!empty($roomact))
{
	$rindex = Array();
	if(!$cuser||!$cpass) {$rerror = 'no_login'; goto roommng_flag; }
	if(!$udata) {$rerror = 'login_check'; goto roommng_flag;}
	if($udata['password'] != $cpass) {$rerror = 'wrong_pw'; goto roommng_flag;}
	if($udata['groupid'] <= 0) {$rerror = 'user_ban'; goto roommng_flag;}

	if($roomact == 'create')
	{
		roommng_create_new_room($udata);
	}
	elseif(strpos($roomact,'join') !== false)
	{
		$join_id = (int)str_replace("join","",$roomact);
		roommng_join_room($join_id,$udata);
	}
	elseif($roomact == 'exit')
	{
		roommng_exit_room($udata);
	}
	elseif($roomact == 'close')
	{
		roommng_close_own_room($udata);
	}
	unset($roomact);
	roommng_flag:
	if(!empty($rerror) && isset($_ERROR[$rerror])) $rindex['innerHTML']['roomerror'] = $_ERROR[$rerror];
	else $rindex['url'] = 'index.php';
	ob_clean();
	echo compatible_json_encode($rindex);
	ob_end_flush();	
	exit();
}
else 
{
  if (isset($_GET['is_new'])) {
    echo json_encode(array(
      // 当前回合数
      "num" => $gamenum,
      // 当前游戏状态
      "state" => $gstate[$gamestate],
      // 当前游戏时间
      "timing" => $timing,
      // 显示当前游戏时间
      "showNowTime" => $gamestate > 10,
      // 显示下局游戏时间
      "showNextTime" => $starttime > $now,
      // 最高伤害玩家
      "maxDamagePlayer" => $hplayer,
      // 最高伤害值
      "maxDamage" => $hdamage,
      // 上局结果
      "lastResult" => $gwin[$winmode],
      // 上局优胜者
      "lastWinner" => $winner,
      // 禁区
      "areaHour" => $areahour,
	  "areaLimit" => $arealimit,
	  "areaAdd" => $areaadd,
	  "areaNum" => $areanum,
	  // 自动逃避禁区
	  "areaAutoHide" => $areaesc && $gamestate < 40,
	  // 人数
	  "validNum" => $validnum,
	  "aliveNum" => $alivenum,
	  "deathNum" => $deathnum,
	  // 当前房间号：
	  "roomID" => $groomid,
	  // 用户名
	  "username" => $cuser,
    ));
    return;
  } else {
    include template('index');
  }
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
 <head>
<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try {
var pageTracker = _gat._getTracker("UA-9744745-2");
pageTracker._trackPageview();
} catch(err) {}</script>
 </head>
</html>
