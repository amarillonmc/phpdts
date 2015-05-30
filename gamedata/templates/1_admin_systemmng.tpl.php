<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<form method="post" name="systemmng" onsubmit="admin.php">
<input type="hidden" name="mode" value="systemmng">
<input type="hidden" id="command" name="command" value="">	
<table class="admin">
<tr>
<th><?php echo $lang['variable']?></th>
<th><?php echo $lang['value']?></th>
<th><?php echo $lang['comment']?></th>
</tr>
<tr>
<td><?php echo $lang['adminmsg']?></td>
<td><textarea cols="30" rows="4" style="overflow:auto" name="adminmsg" value=""><?php echo $adminmsg?></textarea></td>
<td><?php echo $lang['adminmsg_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['systemmsg']?></td>
<td><textarea cols="30" rows="4" style="overflow:auto" name="systemmsg" value=""><?php echo $systemmsg?></textarea></td>
<td><?php echo $lang['systemmsg_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['startmode']?></td>
<td><?php echo $startmode_input?></td>
<td><?php echo $lang['startmode_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['starthour']?></td>
<td><input type="text" name="starthour" value="<?php echo $starthour?>" size="30"></td>
<td><?php echo $lang['starthour_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['startmin']?></td>
<td><input type="text" name="startmin" value="<?php echo $startmin?>" size="30"></td>
<td><?php echo $lang['startmin_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['iplimit']?></td>
<td><input type="text" name="iplimit" value="<?php echo $iplimit?>" size="30"></td>
<td><?php echo $lang['iplimit_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['newslimit']?></td>
<td><input type="text" name="newslimit" value="<?php echo $newslimit?>" size="30"></td>
<td><?php echo $lang['newslimit_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['alivelimit']?></td>
<td><input type="text" name="alivelimit" value="<?php echo $alivelimit?>" size="30"></td>
<td><?php echo $lang['alivelimit_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['winlimit']?></td>
<td><input type="text" name="winlimit" value="<?php echo $winlimit?>" size="30"></td>
<td><?php echo $lang['winlimit_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['noiselimit']?></td>
<td><input type="text" name="noiselimit" value="<?php echo $noiselimit?>" size="30"></td>
<td><?php echo $lang['noiselimit_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['chatlimit']?></td>
<td><input type="text" name="chatlimit" value="<?php echo $chatlimit?>" size="30"></td>
<td><?php echo $lang['chatlimit_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['chatrefresh']?></td>
<td><input type="text" name="chatrefresh" value="<?php echo $chatrefresh?>" size="30"></td>
<td><?php echo $lang['chatrefresh_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['chatinnews']?></td>
<td><input type="text" name="chatinnews" value="<?php echo $chatinnews?>" size="30"></td>
<td><?php echo $lang['chatinnews_comment']?></td>
</tr>
</table>
<input type="submit" value="修改" onclick="$('command').value='edit';">
</form> 