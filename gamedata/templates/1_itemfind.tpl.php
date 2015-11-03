<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
You have found <span class="yellow"><?php echo $itm0?></span>，TYPE：<?php echo $itmk0_words?>
<?php if(($itmsk0) && !is_numeric($itmsk0)) { ?>
，Property：<?php echo $itmsk0_words?>
<?php } ?>
，EFF：<?php echo $itme0?>，DUR：<?php echo $itms0?>。
<br>
<br>
What would you do?
<br>
<input type="hidden" id="mode" name="mode" value="itemmain">
<input type="hidden" id="command" name="command" value="itemget">
<input type="button" class="cmdbutton" name="itemget" value="PICK UP" onclick="postCmd('gamecmd','command.php');this.disabled=true;"><br><br>
<input type="button" class="cmdbutton" name="dropitm0" value="DISCARD" onclick="$('command').value='dropitm0';postCmd('gamecmd','command.php');this.disabled=true;">