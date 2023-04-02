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
强制关闭<input type="number" name="roomkey" value=1 min="1" max="{$max_rooms}">号房间<br>
<input type="submit" value="关闭"><br>
</form>
EOT;

?>