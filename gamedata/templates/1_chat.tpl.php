<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table id="chat" border="0" width="720" cellspacing="0" cellpadding="0" style="valign:top">
<tr>
<td height="20px" width="100%" class="b1"><span>消息</span></td>
</tr>
<tr>
<td valign="top" class="b3" style="text-align: left" height="1px">
<div id="chatlist" class="chatlist">
<?php if(is_array($chatdata['msg'])) { foreach($chatdata['msg'] as $msg) { ?>
<?php echo $msg?>
<?php } } ?>
</div>
</td>
</tr>
<tr>
<td class="b3" height="5"></td>
</tr>
<tr>
<td class="b3" height="35">
<div>
<form type="post" id="sendchat" name="sendchat" action="chat.php" onsubmit="return false;" >
<input type="hidden" id="lastcid" name="lastcid" value="<?php echo $chatdata['lastcid']?>">
<input type="hidden" id="teamID" name="teamID" value="<?php echo $teamID?>">
<input type="hidden" id="sendmode" name="sendmode" value="ref">
<span id="chattype">
<select name="chattype" value="2">
<option value="0" selected><?php echo $chatinfo['0']?>
<?php if($teamID) { ?>
<option value="1" ><?php echo $chatinfo['1']?>
<?php } ?>
</select>
</span>
<input type="text" id="chatmsg" name="chatmsg" maxlength="60" >
<input type="button" id="send" onClick="document['sendchat']['sendmode'].value='send';chat('send',<?php echo $chatrefresh?>);return false;" value="发送">
<input type="button" id="ref" onClick="document['sendchat']['sendmode'].value='ref';chat('ref',<?php echo $chatrefresh?>);return false;" value="刷新">
</form>
<script type="text/javascript">chat('ref',<?php echo $chatrefresh?>);</script>
</div>
</td>
</tr>
</table> 