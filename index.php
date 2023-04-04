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
	$result = $db->query("SELECT * FROM {$gtablepre}users WHERE username='$cuser'");
	if(!$db->num_rows($result)) {$rerror = 'login_check'; goto roommng_flag;}
	$udata = $db->fetch_array($result);
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
