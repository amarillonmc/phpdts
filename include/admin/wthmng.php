<?php
if(!defined('IN_ADMIN')) {
	exit('Access Denied');
}
if($mygroup < 3){
	exit($_ERROR['no_power']);
}
global $wth,$now;

if($chg == 1 && $wth){
	echo '当前天气修改为：'.$wthinfo[$wth];
	$weather = $wth;
	save_gameinfo();
	adminlog('wthchg',$wth);
	naddnews($now,'syswthchg',$wth);
}else{echo "当前天气：{$wthinfo[$weather]}<br />";}


$i=0;$wthlog = '';
foreach($wthinfo as $value){
	$wthlog .= "<input type=\"radio\" name=\"wth\" id=\"$i\" value=\"$i\"><a onclick=sl('$i'); href=\"javascript:void(0);\" >$wthinfo[$i]</a>				";
	$i++;
}


echo <<<EOT
<form method="post" name="wthmng" onsubmit="admin.php">
<input type="hidden" name="mode" value="gamemng">
<input type="hidden" name="command" value="wthmng">
<input type="hidden" name="chg" value="1">
$wthlog <br />
<input type="submit" name="submit" value="修改当前天气"></form>
EOT;
?>

