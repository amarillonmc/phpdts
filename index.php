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
