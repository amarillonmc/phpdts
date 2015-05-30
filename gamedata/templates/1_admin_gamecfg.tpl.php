<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<form method="post" name="gamecfgmng" onsubmit="admin.php">
<input type="hidden" name="mode" value="gamecfgmng">
<input type="hidden" id="command" name="command" value="">
<table class="admin">
<tr>
<th><?php echo $lang['variable']?></th>
<th><?php echo $lang['value']?></th>
<th><?php echo $lang['comment']?></th>
</tr>
<tr>
<td><?php echo $lang['areahour']?></td>
<td><input type="text" name="areahour" value="<?php echo $areahour?>" size="30"></td>
<td><?php echo $lang['areahour_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['areaadd']?></td>
<td><input type="text" name="areaadd" value="<?php echo $areaadd?>" size="30"></td>
<td><?php echo $lang['areaadd_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['arealimit']?></td>
<td><input type="text" name="arealimit" value="<?php echo $arealimit?>" size="30"></td>
<td><?php echo $lang['arealimit_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['areaesc']?></td>
<td><input type="text" name="areaesc" value="<?php echo $areaesc?>" size="30"></td>
<td><?php echo $lang['areaesc_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['antiAFKertime']?></td>
<td><input type="text" name="antiAFKertime" value="<?php echo $antiAFKertime?>" size="30"></td>
<td><?php echo $lang['antiAFKertime_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['corpseprotect']?></td>
<td><input type="text" name="corpseprotect" value="<?php echo $corpseprotect?>" size="30"></td>
<td><?php echo $lang['corpseprotect_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['coldtimeon']?></td>
<td><input type="radio" name="coldtimeon" value="1" 
<?php if($coldtimeon) { ?>
checked="true"
<?php } ?>
><?php echo $lang['on']?>&nbsp;&nbsp;&nbsp;<input type="radio" name="coldtimeon" value="0" 
<?php if(!$coldtimeon) { ?>
checked="true"
<?php } ?>
><?php echo $lang['off']?></td>
<td><?php echo $lang['coldtimeon_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['showcoldtimer']?></td>
<td><input type="radio" name="showcoldtimer" value="1" 
<?php if($showcoldtimer) { ?>
checked="true"
<?php } ?>
><?php echo $lang['on']?>&nbsp;&nbsp;&nbsp;<input type="radio" name="showcoldtimer" value="0" 
<?php if(!$showcoldtimer) { ?>
checked="true"
<?php } ?>
><?php echo $lang['off']?></td>
<td><?php echo $lang['showcoldtimer_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['validlimit']?></td>
<td><input type="text" name="validlimit" value="<?php echo $validlimit?>" size="30"></td>
<td><?php echo $lang['validlimit_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['combolimit']?></td>
<td><input type="text" name="combolimit" value="<?php echo $combolimit?>" size="30"></td>
<td><?php echo $lang['combolimit_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['deathlimit']?></td>
<td><input type="text" name="deathlimit" value="<?php echo $deathlimit?>" size="30"></td>
<td><?php echo $lang['deathlimit_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['splimit']?></td>
<td><input type="text" name="splimit" value="<?php echo $splimit?>" size="30"></td>
<td><?php echo $lang['splimit_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['hplimit']?></td>
<td><input type="text" name="hplimit" value="<?php echo $hplimit?>" size="30"></td>
<td><?php echo $lang['hplimit_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['sleep_time']?></td>
<td><input type="text" name="sleep_time" value="<?php echo $sleep_time?>" size="30"></td>
<td><?php echo $lang['sleep_time_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['heal_time']?></td>
<td><input type="text" name="heal_time" value="<?php echo $heal_time?>" size="30"></td>
<td><?php echo $lang['heal_time_comment']?></td>
</tr>
<tr>
<td><?php echo $lang['teamlimit']?></td>
<td><input type="text" name="teamlimit" value="<?php echo $teamlimit?>" size="30"></td>
<td><?php echo $lang['teamlimit_comment']?></td>
</tr>
</table>
<input type="submit" value="修改" onclick="$('command').value='edit';">
</form>