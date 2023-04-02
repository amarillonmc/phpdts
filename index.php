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
	if(!$cuser||!$cpass) { gexit($_ERROR['no_login'],__file__,__line__); }
	$result = $db->query("SELECT * FROM {$gtablepre}users WHERE username='$cuser'");
	if(!$db->num_rows($result)) { gexit($_ERROR['login_check'],__file__,__line__); }
	$udata = $db->fetch_array($result);
	if($udata['password'] != $cpass) { gexit($_ERROR['wrong_pw'], __file__, __line__); }
	if($udata['groupid'] <= 0) { gexit($_ERROR['user_ban'], __file__, __line__); }

	if(strpos($roomact,'join') !== false)
	{
		if(!empty($udata['roomid'])) gexit('你已经在房间里了，想加入其它房间要先从当前房间退出。', __file__, __line__);
		$join_id = (int)str_replace("join","",$roomact);
		if(in_array($join_id,range(1,$max_rooms)))
		{
			$db->query("UPDATE {$gtablepre}users SET roomid = {$join_id} WHERE username='$cuser'");
			$result = $db->query("SELECT groomnums FROM {$gtablepre}game WHERE groomid = {$join_id}");
			if($db->num_rows($result)) 
			{ 
				$join_nums = $db->result($result, 0);
				$join_nums++;
				$db->query("UPDATE {$gtablepre}game SET groomnums = {$join_nums} WHERE groomid = {$join_id}");
			}
		}
		else 
		{
			gexit('要加入的房间不存在！', __file__, __line__);
		}
		unset($roomact);
	}
	elseif($roomact == 'exit')
	{
		if(empty($udata['roomid'])) gexit('你没有在任何房间里！', __file__, __line__);
		$result = $db->query("SELECT groomnums FROM {$gtablepre}game WHERE groomid = {$udata['roomid']}");
		if($db->num_rows($result)) 
		{ 
			$join_nums = $db->result($result, 0);
			$join_nums--;
			$db->query("UPDATE {$gtablepre}game SET groomnums = {$join_nums} WHERE groomid = {$udata['roomid']}");
		}
		$db->query("UPDATE {$gtablepre}users SET roomid = 0 WHERE username='$cuser'");
		unset($roomact);
	}
}
else 
{
	include template('index');
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
