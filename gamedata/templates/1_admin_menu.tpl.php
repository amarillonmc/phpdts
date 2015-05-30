<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<form method="post" name="admin" onsubmit="admin.php">
<input type="hidden" name="mode" id="mode" value="admin_menu">
<input type="hidden" name="command" id="command" value="menu">
<table>
<tr>
<td valign="top">
<table class="admin">
<tr>
<td colspan=3 class="tdtitle"><?php echo $lang['emenu']?></td>
</tr>
<tr>
<th><?php echo $lang['options']?></th>
<th width="240"><?php echo $lang['comments']?></th>
<th width="30"><?php echo $lang['groups']?></th>
</tr>
<tr height="45px">
<td><input type="submit" style="width:100;height:40;" value="<?php echo $lang['configmng']?>" onclick="$('command').value='configmng'" 
<?php if($mygroup < $admin_cmd_list['configmng']) { ?>
disabled="true"
<?php } ?>
></td>
<td><?php echo $lang['configmng_comment']?></td>
<td><?php echo $admin_cmd_list['configmng']?></td>
</tr>
<tr height="45px">
<td><input type="submit" style="width:100;height:40;" value="<?php echo $lang['systemmng']?>" onclick="$('command').value='systemmng'" 
<?php if($mygroup < $admin_cmd_list['systemmng']) { ?>
disabled="true"
<?php } ?>
></td>
<td><?php echo $lang['systemmng_comment']?></td>
<td><?php echo $admin_cmd_list['systemmng']?></td>
</tr>
<tr height="45px">
<td><input type="submit" style="width:100;height:40;" value="<?php echo $lang['gamecfgmng']?>" onclick="$('command').value='gamecfgmng'" 
<?php if($mygroup < $admin_cmd_list['gamecfgmng']) { ?>
disabled="true"
<?php } ?>
></td>
<td><?php echo $lang['gamecfgmng_comment']?></td>
<td><?php echo $admin_cmd_list['gamecfgmng']?></td>
</tr>
<tr height="45px">
<td><input type="submit" style="width:100;height:40;" value="<?php echo $lang['banlistmng']?>" onclick="$('command').value='banlistmng'" 
<?php if($mygroup < $admin_cmd_list['banlistmng']) { ?>
disabled="true"
<?php } ?>
></td>
<td><?php echo $lang['banlistmng_comment']?></td>
<td><?php echo $admin_cmd_list['banlistmng']?></td>
</tr>
<tr height="45px">
<td><input type="submit" style="width:100;height:40;" value="<?php echo $lang['gmlist']?>" onclick="$('command').value='gmlist'" 
<?php if($mygroup < $admin_cmd_list['gmlist']) { ?>
disabled="true"
<?php } ?>
></td>
<td><?php echo $lang['gmlist_comment']?></td>
<td><?php echo $admin_cmd_list['gmlist']?></td>
</tr>
<tr height="45px">
<td><input type="submit" style="width:100;height:40;" value="<?php echo $lang['urlist']?>" onclick="$('command').value='urlist'" 
<?php if($mygroup < $admin_cmd_list['urlist']) { ?>
disabled="true"
<?php } ?>
></td>
<td><?php echo $lang['urlist_comment']?></td>
<td><?php echo $admin_cmd_list['urlist']?></td>
</tr>

</table>
</td>
<td valign="top">	
<table class="admin">
<tr>
<td colspan=3 class="tdtitle"><?php echo $lang['gmenu']?></td>
</tr>
<tr>
<th><?php echo $lang['options']?></th>
<th width="240"><?php echo $lang['comments']?></th>
<th width="30"><?php echo $lang['groups']?></th>
</tr>
<tr height="45px">
<td><input type="submit" style="width:100;height:40;" value="<?php echo $lang['pcmng']?>" onclick="$('command').value='pcmng'" 
<?php if($mygroup < $admin_cmd_list['pcmng']) { ?>
disabled="true"
<?php } ?>
></td>
<td><?php echo $lang['pcmng_comment']?></td>
<td><?php echo $admin_cmd_list['pcmng']?></td>
</tr>
<tr height="45px">
<td><input type="submit" style="width:100;height:40;" value="<?php echo $lang['npcmng']?>" onclick="$('command').value='npcmng'" 
<?php if($mygroup < $admin_cmd_list['npcmng']) { ?>
disabled="true"
<?php } ?>
></td>
<td><?php echo $lang['npcmng_comment']?></td>
<td><?php echo $admin_cmd_list['npcmng']?></td>
</tr>
<tr height="45px">
<td><input type="submit" style="width:100;height:40;" value="<?php echo $lang['gameinfomng']?>" onclick="$('command').value='gameinfomng'" 
<?php if($mygroup < $admin_cmd_list['gameinfomng']) { ?>
disabled="true"
<?php } ?>
></td>
<td><?php echo $lang['gameinfomng_comment']?></td>
<td><?php echo $admin_cmd_list['gameinfomng']?></td>
</tr>
<tr height="45px">
<td><input type="submit" style="width:100;height:40;" value="<?php echo $lang['antiAFKmng']?>" onclick="$('command').value='antiAFKmng'" 
<?php if($mygroup < $admin_cmd_list['antiAFKmng']) { ?>
disabled="true"
<?php } ?>
></td>
<td><?php echo $lang['antiAFKmng_comment']?></td>
<td><?php echo $admin_cmd_list['antiAFKmng']?></td>
</tr>
<tr height="45px">
<td><input type="submit" style="width:100;height:40;" value="<?php echo $lang['gamecheck']?>" onclick="$('command').value='gamecheck'" 
<?php if($mygroup < $admin_cmd_list['gamecheck']) { ?>
disabled="true"
<?php } ?>
></td>
<td><?php echo $lang['gamecheck_comment']?></td>
<td><?php echo $admin_cmd_list['gamecheck']?></td>
</tr>
</table>
</td>
</tr>
</table>

</form> 