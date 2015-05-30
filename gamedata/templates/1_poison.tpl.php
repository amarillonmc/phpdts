<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<input type="hidden" name="mode" value="item">
<input type="hidden" name="usemode" value="poison">
<input type="hidden" name="itmp" value="<?php echo $itmn?>">
<input type="hidden" id="command" name="command" value="menu">
你想对什么下毒？<br>
<br>
<?php if((strpos ( $itmk1, 'H' ) === 0) || (strpos ( $itmk1, 'P' ) === 0)) { ?>
<input type="button" onclick="$('command').value='itm1';postCmd('gamecmd','command.php');this.disabled=true;" value="<?php echo $itm1?>/<?php echo $itme1?>/<?php echo $itms1?>"><br>
<?php } if((strpos ( $itmk2, 'H' ) === 0) || (strpos ( $itmk2, 'P' ) === 0)) { ?>
<input type="button" onclick="$('command').value='itm2';postCmd('gamecmd','command.php');this.disabled=true;" value="<?php echo $itm2?>/<?php echo $itme2?>/<?php echo $itms2?>"><br>
<?php } if((strpos ( $itmk3, 'H' ) === 0) || (strpos ( $itmk3, 'P' ) === 0)) { ?>
<input type="button" onclick="$('command').value='itm3';postCmd('gamecmd','command.php');this.disabled=true;" value="<?php echo $itm3?>/<?php echo $itme3?>/<?php echo $itms3?>"><br>
<?php } if((strpos ( $itmk4, 'H' ) === 0) || (strpos ( $itmk4, 'P' ) === 0)) { ?>
<input type="button" onclick="$('command').value='itm4';postCmd('gamecmd','command.php');this.disabled=true;" value="<?php echo $itm4?>/<?php echo $itme4?>/<?php echo $itms4?>"><br>
<?php } if((strpos ( $itmk5, 'H' ) === 0) || (strpos ( $itmk5, 'P' ) === 0)) { ?>
<input type="button" onclick="$('command').value='itm5';postCmd('gamecmd','command.php');this.disabled=true;" value="<?php echo $itm5?>/<?php echo $itme5?>/<?php echo $itms5?>"><br>
<?php } if((strpos ( $itmk6, 'H' ) === 0) || (strpos ( $itmk6, 'P' ) === 0)) { ?>
<input type="button" onclick="$('command').value='itm6';postCmd('gamecmd','command.php');this.disabled=true;" value="<?php echo $itm6?>/<?php echo $itme6?>/<?php echo $itms6?>"><br>
<?php } ?>
<br>
<input type="button" class="cmdbutton" onclick="postCmd('gamecmd','command.php');this.disabled=true;" value="放弃">