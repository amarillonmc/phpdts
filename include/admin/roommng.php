<?php
if(!defined('IN_ADMIN')) {
	exit('Access Denied');
}

if($command == 'killroom')
{
	roommng_close_room($roomkey,1);
	$command = '';
}
elseif($command == 'killallroom')
{
	for($r=1;$r<=$max_rooms;$r++)
	{
		roommng_close_room($r,1,1);
	}
	$command = '';
}

echo <<<EOT
<form method="post" name="roommng" onsubmit="admin.php">
<input type="hidden" name="mode" value="roommng">
<input type="hidden" id="command" name="command" value="killroom">
强制关闭指定房间：
<select name="roomkey">
EOT;

foreach($roomlist as $rkey => $rinfo)
{
echo <<<EOT
	<option value="{$rkey}">房间 {$rkey} 号 | 正在游玩人数：{$rinfo['alivenum']}
EOT;
}

echo <<<EOT
</select>
<input type="submit" value="强制关闭">
<br>
<span class='red'>（警告：正处于游戏状态中的房间也会被关闭！）</span>
<br><br>
EOT;

echo <<<EOT
<span tooltip="只会关闭尚未开始、或已无幸存玩家的房间">
<input type="submit" value="关闭所有闲置房间" onclick="$('command').value='killallroom';"><br>
</span>
EOT;

echo <<<EOT
</form>
EOT;

?>