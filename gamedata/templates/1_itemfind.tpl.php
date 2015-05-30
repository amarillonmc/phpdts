<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
发现了物品 <span class="yellow"><?php echo $itm0?></span>，类型：<?php echo $itmk0_words?>
<?php if(($itmsk0) && !is_numeric($itmsk0)) { ?>
，属性：<?php echo $itmsk0_words?>
<?php } ?>
，效：<?php echo $itme0?>，耐：<?php echo $itms0?>。
<br>
<br>
你想如何处理？
<br>
<input type="hidden" id="mode" name="mode" value="itemmain">
<input type="hidden" id="command" name="command" value="itemget">
<input type="button" class="cmdbutton" name="itemget" value="拾取" onclick="postCmd('gamecmd','command.php');this.disabled=true;"><br><br>
<input type="button" class="cmdbutton" name="dropitm0" value="丢弃" onclick="$('command').value='dropitm0';postCmd('gamecmd','command.php');this.disabled=true;">