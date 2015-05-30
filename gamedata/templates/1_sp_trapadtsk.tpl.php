<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<input type="hidden" name="mode" value="command">
<input type="hidden" name="command" value="special">
<input type="hidden" name="sp_cmd" value="sp_trapadtskselected">
<input type="hidden" id="choice" name="choice" value="menu">
你想改造哪个陷阱？<br>
<br>
<?php if((strpos ( $itmk1, 'T' ) === 0)) { ?>
<input type="button" onclick="$('choice').value='1';postCmd('gamecmd','command.php');this.disabled=true;" value="<?php echo $itm1?>/<?php echo $itme1?>/<?php echo $itms1?>"><br>
<?php } if((strpos ( $itmk2, 'T' ) === 0)) { ?>
<input type="button" onclick="$('choice').value='2';postCmd('gamecmd','command.php');this.disabled=true;" value="<?php echo $itm2?>/<?php echo $itme2?>/<?php echo $itms2?>"><br>
<?php } if((strpos ( $itmk3, 'T' ) === 0)) { ?>
<input type="button" onclick="$('choice').value='3';postCmd('gamecmd','command.php');this.disabled=true;" value="<?php echo $itm3?>/<?php echo $itme3?>/<?php echo $itms3?>"><br>
<?php } if((strpos ( $itmk4, 'T' ) === 0)) { ?>
<input type="button" onclick="$('choice').value='4';postCmd('gamecmd','command.php');this.disabled=true;" value="<?php echo $itm4?>/<?php echo $itme4?>/<?php echo $itms4?>"><br>
<?php } if((strpos ( $itmk5, 'T' ) === 0)) { ?>
<input type="button" onclick="$('choice').value='5';postCmd('gamecmd','command.php');this.disabled=true;" value="<?php echo $itm5?>/<?php echo $itme5?>/<?php echo $itms5?>"><br>
<?php } if((strpos ( $itmk6, 'T' ) === 0)) { ?>
<input type="button" onclick="$('choice').value='6';postCmd('gamecmd','command.php');this.disabled=true;" value="<?php echo $itm6?>/<?php echo $itme6?>/<?php echo $itms6?>"><br>
<?php } ?>
<br>
<input type="button" class="cmdbutton" onclick="postCmd('gamecmd','command.php');this.disabled=true;" value="放弃">