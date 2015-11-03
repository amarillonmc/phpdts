<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table align="center">
<tr>
<td>Motto</td>
<td><input size="30" type="text" name="motto" maxlength="30" value="<?php echo $motto?>">Maximum 30 bytes.</td>
</tr>
<tr>
<tr>
<td>Kill Note</td>
<td><input size="30" type="text" name="killmsg" maxlength="30" value="<?php echo $killmsg?>">Maximum 30 bytes.</td>
</tr>
<tr>
<td>Last Note</td>
<td><input size="30" type="text" name="lastword" maxlength="30" value="<?php echo $lastword?>">Maximum 30 bytes.</td>
</tr>
</table> 