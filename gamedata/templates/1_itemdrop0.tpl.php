<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
Your bag is full and need to discard to make space<br><br>
<input type="hidden" name="mode" value="itemmain">
<input type="radio" name="command" id="dropitm0" value="dropitm0" checked><a onclick=sl("dropitm0"); href="javascript:void(0);" ><?php echo $itm0?>/<?php echo $itme0?>/<?php echo $itms0?></a><br><br>
<input type="radio" name="command" id="itm1" value="swapitm1"><a onclick=sl('itm1'); href="javascript:void(0);" ><?php echo $itm1?>/<?php echo $itme1?>/<?php echo $itms1?> </a><br>
<input type="radio" name="command" id="itm2" value="swapitm2"><a onclick=sl('itm2'); href="javascript:void(0);" ><?php echo $itm2?>/<?php echo $itme2?>/<?php echo $itms2?> </a><br>
<input type="radio" name="command" id="itm3" value="swapitm3"><a onclick=sl('itm3'); href="javascript:void(0);" ><?php echo $itm3?>/<?php echo $itme3?>/<?php echo $itms3?> </a><br>
<input type="radio" name="command" id="itm4" value="swapitm4"><a onclick=sl('itm4'); href="javascript:void(0);" ><?php echo $itm4?>/<?php echo $itme4?>/<?php echo $itms4?> </a><br>
<input type="radio" name="command" id="itm5" value="swapitm5"><a onclick=sl('itm5'); href="javascript:void(0);" ><?php echo $itm5?>/<?php echo $itme5?>/<?php echo $itms5?> </a><br>
<input type="radio" name="command" id="itm6" value="swapitm6"><a onclick=sl('itm6'); href="javascript:void(0);" ><?php echo $itm6?>/<?php echo $itme6?>/<?php echo $itms6?> </a><br>
<br><br>
<input type="button" class="cmdbutton" name="submit" value="Okay" onclick="postCmd('gamecmd','command.php');this.disabled=true;">