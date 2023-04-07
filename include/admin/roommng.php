<?php
if(!defined('IN_ADMIN')) {
	exit('Access Denied');
}

if($command == 'killroom')
{
	roommng_close_room($roomkey,1);
	$command = '';
}

echo <<<EOT
<form method="post" name="roommng" onsubmit="admin.php">
<input type="hidden" name="mode" value="roommng">
<input type="hidden" name="command" value="killroom">
强制关闭
<select name="roomkey">
EOT;

foreach($roomlist as $rkey => $rinfo)
{
echo <<<EOT
	<option value="{$rkey}">房间 {$rkey} 号 | 正在游玩人数：{$rinfo['alivenum']}
EOT;
}

echo <<<EOT
</select><br>
<input type="submit" value="关闭"><br>
</form>
EOT;

?>