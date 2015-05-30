<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<input type="hidden" name="mode" value="itemmain">
<input type="hidden" name="command" value="itemmerge">
<input type="hidden" name="merge1" value="0">
<input type="hidden" id="merge2" name="merge2" value="n">
<br>
是否将 <span class="yellow"><?php echo $itm0?></span>与以下物品合并？
<br><br>
<?php if(in_array(1,$sameitem)) { ?>
<input type="button" id="itm1" value="<?php echo $itm1?>/<?php echo $itme1?>/<?php echo $itms1?>" onclick="$('merge2').value='1';postCmd('gamecmd','command.php');return false;"><br> 
<?php } if(in_array(2,$sameitem)) { ?>
<input type="button" id="itm2" value="<?php echo $itm2?>/<?php echo $itme2?>/<?php echo $itms2?>" onclick="$('merge2').value='2';postCmd('gamecmd','command.php');return false;"><br> 
<?php } if(in_array(3,$sameitem)) { ?>
<input type="button" id="itm3" value="<?php echo $itm3?>/<?php echo $itme3?>/<?php echo $itms3?>" onclick="$('merge2').value='3';postCmd('gamecmd','command.php');return false;"><br> 
<?php } if(in_array(4,$sameitem)) { ?>
<input type="button" id="itm4" value="<?php echo $itm4?>/<?php echo $itme4?>/<?php echo $itms4?>" onclick="$('merge2').value='4';postCmd('gamecmd','command.php');return false;"><br> 
<?php } if(in_array(5,$sameitem)) { ?>
<input type="button" id="itm5" value="<?php echo $itm5?>/<?php echo $itme5?>/<?php echo $itms5?>" onclick="$('merge2').value='5';postCmd('gamecmd','command.php');return false;"><br> 
<?php } if(in_array(6,$sameitem)) { ?>
<input type="button" id="itm6" value="<?php echo $itm6?>/<?php echo $itme6?>/<?php echo $itms6?>" onclick="$('merge2').value='6';postCmd('gamecmd','command.php');return false;"><br> 
<?php } ?>
<br>
<input type="button" class="cmdbutton" name="no" value="不合并" onclick="postCmd('gamecmd','command.php');return false;">