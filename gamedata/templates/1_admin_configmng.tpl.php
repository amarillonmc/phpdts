<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<form method="post" name="configmng" onsubmit="admin.php">
<input type="hidden" id="mode" name="mode" value="configmng">
<input type="hidden" id="command" name="command" value="">

<table class="admin">
<tr>
<th><?php echo $lang['variable']?></th>
<th><?php echo $lang['value']?></th>
<th><?php echo $lang['comment']?></th>
</tr>
<tr>
  <td><?php echo $lang['moveut']?></td>
  <td><input type="text" name="moveut" value="<?php echo $moveut?>" size="5">Hours<input type="text" name="moveutmin" value="<?php echo $moveutmin?>" size="5">Minutes</td>
  <td><?php echo $lang['moveut_comment']?><br><?php echo $lang['orin_time']?><?php echo $orin_time?><br><?php echo $lang['set_time']?><?php echo $set_time?></td>
</tr>
<tr>
  <td><?php echo $lang['authkey']?></td>
  <td><input type="text" name="authkey" value="<?php echo $authkey?>" size="30" disabled="true"></td>
  <td><?php echo $lang['authkey_comment']?></td>
</tr>
<tr>
  <td><?php echo $lang['tplrefresh']?></td>
  <td><input type="radio" name="tplrefresh" value="1" 
<?php if($tplrefresh) { ?>
checked="true"
<?php } ?>
><?php echo $lang['on']?>&nbsp;&nbsp;&nbsp;<input type="radio" name="tplrefresh" value="0" 
<?php if(!$tplrefresh) { ?>
checked="true"
<?php } ?>
><?php echo $lang['off']?></td>
  <td><?php echo $lang['tplrefresh_comment']?></td>
</tr>
<tr>
  <td><?php echo $lang['errorinfo']?></td>
  <td><input type="radio" name="errorinfo" value="1" 
<?php if($errorinfo) { ?>
checked="true"
<?php } ?>
><?php echo $lang['on']?>&nbsp;&nbsp;&nbsp;<input type="radio" name="errorinfo" value="0" 
<?php if(!$errorinfo) { ?>
checked="true"
<?php } ?>
><?php echo $lang['off']?></td>
  <td><?php echo $lang['errorinfo_comment']?></td>
</tr>
<tr>
  <td><?php echo $lang['bbsurl']?></td>
  <td><input type="text" name="bbsurl" value="<?php echo $bbsurl?>" size="30"></td>
  <td><?php echo $lang['bbsurl_comment']?></td>
</tr>
<tr>
  <td><?php echo $lang['gameurl']?></td>
  <td><input type="text" name="gameurl" value="<?php echo $gameurl?>" size="30"></td>
  <td><?php echo $lang['gameurl_comment']?></td>
</tr>
<tr>
  <td><?php echo $lang['homepage']?></td>
  <td><input type="text" name="homepage" value="<?php echo $homepage?>" size="30"></td>
  <td><?php echo $lang['homepage_comment']?></td>
</tr>
</table>
<input type="submit" value="SUBMIT" onclick="$('command').value='edit';">
</form>