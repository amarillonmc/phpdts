<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<?php echo $restinfo[$state]?> ... ...<br>
<input type="hidden" id="mode" name="mode" value="rest">
<input type="hidden" id="command" name="command" value="rest">
<input type="button" class="cmdbutton" name="rest" value="<?php echo $restinfo[$state]?>" onclick="postCmd('gamecmd','command.php');this.disabled=true;">
<input type="button" class="cmdbutton" name="back" value="BACK" onclick="$('command').value='back';postCmd('gamecmd','command.php');this.disabled=true;">