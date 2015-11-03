<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
What do you want to mix?<br>

<input type="hidden" name="mode" value="itemmain">
<input type="hidden" name="command" id="command" value="menu">
<br>
<?php if($itms1) { ?>
<input type="checkbox" id="mitm1" name="mitm1" value="0"><a onclick="$('mitm1').click();" href="javascript:void(0);"><?php echo $itm1?>/<?php echo $itme1?>/<?php echo $itms1?></a><br>
<?php } if($itms2) { ?>
<input type="checkbox" id="mitm2" name="mitm2" value="0"><a onclick="$('mitm2').click();" href="javascript:void(0);"><?php echo $itm2?>/<?php echo $itme2?>/<?php echo $itms2?></a><br>
<?php } if($itms3) { ?>
<input type="checkbox" id="mitm3" name="mitm3" value="0"><a onclick="$('mitm3').click();" href="javascript:void(0);"><?php echo $itm3?>/<?php echo $itme3?>/<?php echo $itms3?></a><br>
<?php } if($itms4) { ?>
<input type="checkbox" id="mitm4" name="mitm4" value="0"><a onclick="$('mitm4').click();" href="javascript:void(0);"><?php echo $itm4?>/<?php echo $itme4?>/<?php echo $itms4?></a><br>
<?php } if($itms5) { ?>
<input type="checkbox" id="mitm5" name="mitm5" value="0"><a onclick="$('mitm5').click();" href="javascript:void(0);"><?php echo $itm5?>/<?php echo $itme5?>/<?php echo $itms5?></a><br>
<?php } if($itms6) { ?>
<input type="checkbox" id="mitm6" name="mitm6" value="0"><a onclick="$('mitm6').click();" href="javascript:void(0);"><?php echo $itm6?>/<?php echo $itme6?>/<?php echo $itms6?></a><br>
<?php } ?>
<br>
<input type="button" class="cmdbutton" name="submit" value="SUBMIT" onclick="$('command').value='itemmix';itemmixchooser();postCmd('gamecmd','command.php');this.disabled=true;">
<input type="button" class="cmdbutton" name="submit" value="BACK" onclick="postCmd('gamecmd','command.php');this.disabled=true;">