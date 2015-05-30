<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
想检查什么？<br>

<input type="hidden" name="mode" value="special">
<input type="radio" name="command" id="menu" value="menu" checked><a onclick=sl('menu'); href="javascript:void(0);" >返回</a><br><br>
<?php if($itms1 && ((strpos($itmk1,'P') === 0) || (strpos($itmk1,'H') === 0))) { ?>
<input type="radio" name="command" id="chkp1" value="chkp1"><a onclick=sl('chkp1'); href="javascript:void(0);" ><?php echo $itm1?> </a><br>
<?php } if($itms2 && ((strpos($itmk2,'P') === 0) || (strpos($itmk2,'H') === 0))) { ?>
<input type="radio" name="command" id="chkp2" value="chkp2"><a onclick=sl('chkp2'); href="javascript:void(0);" ><?php echo $itm2?> </a><br>
<?php } if($itms3 && ((strpos($itmk3,'P') === 0) || (strpos($itmk3,'H') === 0))) { ?>
<input type="radio" name="command" id="chkp3" value="chkp3"><a onclick=sl('chkp3'); href="javascript:void(0);" ><?php echo $itm3?> </a><br>
<?php } if($itms4 && ((strpos($itmk4,'P') === 0) || (strpos($itmk4,'H') === 0))) { ?>
<input type="radio" name="command" id="chkp4" value="chkp4"><a onclick=sl('chkp4'); href="javascript:void(0);" ><?php echo $itm4?> </a><br>
<?php } if($itms5 && ((strpos($itmk5,'P') === 0) || (strpos($itmk5,'H') === 0))) { ?>
<input type="radio" name="command" id="chkp5" value="chkp5"><a onclick=sl('chkp5'); href="javascript:void(0);" ><?php echo $itm5?> </a><br>
<?php } if($itms6 && ((strpos($itmk6,'P') === 0) || (strpos($itmk6,'H') === 0))) { ?>
<input type="radio" name="command" id="chkp6" value="chkp6"><a onclick=sl('chkp6'); href="javascript:void(0);" ><?php echo $itm6?> </a><br>
<?php } ?>
<br><br>

<input type="button" class="cmdbutton" name="submit" value="提交" onclick="postCmd('gamecmd','command.php');this.disabled=true;">