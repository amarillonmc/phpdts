<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
现在想要做什么？<br><br>
<input type="hidden" name="mode" value="senditem">
<input type="hidden" id="command" name="command" value="back">
留言：<br><input size="30" type="text" name="message" maxlength="60"><br><br>
<input type="button" class="cmdbutton" name="back" value="返回" onclick="postCmd('gamecmd','command.php');"><br><br>
<?php if($itms1) { ?>
转让：<input type="button" name="itm1" value="<?php echo $itm1?>/<?php echo $itme1?>/<?php echo $itms1?>" onclick="$('command').value='itm1';postCmd('gamecmd','command.php');"><br>
<?php } if($itms2) { ?>
转让：<input type="button" name="itm2" value="<?php echo $itm2?>/<?php echo $itme2?>/<?php echo $itms2?>" onclick="$('command').value='itm2';postCmd('gamecmd','command.php');"><br>
<?php } if($itms3) { ?>
转让：<input type="button" name="itm3" value="<?php echo $itm3?>/<?php echo $itme3?>/<?php echo $itms3?>" onclick="$('command').value='itm3';postCmd('gamecmd','command.php');"><br>
<?php } if($itms4) { ?>
转让：<input type="button" name="itm4" value="<?php echo $itm4?>/<?php echo $itme4?>/<?php echo $itms4?>" onclick="$('command').value='itm4';postCmd('gamecmd','command.php');"><br>
<?php } if($itms5) { ?>
转让：<input type="button" name="itm5" value="<?php echo $itm5?>/<?php echo $itme5?>/<?php echo $itms5?>" onclick="$('command').value='itm5';postCmd('gamecmd','command.php');"><br>
<?php } if($itms6) { ?>
转让：<input type="button" name="itm6" value="<?php echo $itm6?>/<?php echo $itme6?>/<?php echo $itms6?>" onclick="$('command').value='itm6';postCmd('gamecmd','command.php');"><br>
<?php } ?>
