<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
你想要合并什么？<br>

<input type="hidden" name="mode" value="itemmain">
<input type="radio" name="command" id="menu" value="menu" checked><a onclick=sl('menu'); href="javascript:void(0);" >返回</a><br><br>
<input type="radio" name="command" id="itemmerge" value="itemmerge"><a onclick=sl('itemmerge'); href="javascript:void(0);">合并</a>
<br>
<select name="merge1" onclick=sl('itemmerge'); href="javascript:void(0);">
<option value="0">■ 道具一 ■<br />
<?php if($itms1) { ?>
<option value="1"><?php echo $itm1?>/<?php echo $itme1?>/<?php echo $itms1?><br />
<?php } if($itms2) { ?>
<option value="2"><?php echo $itm2?>/<?php echo $itme2?>/<?php echo $itms2?><br />
<?php } if($itms3) { ?>
<option value="3"><?php echo $itm3?>/<?php echo $itme3?>/<?php echo $itms3?><br />
<?php } if($itms4) { ?>
<option value="4"><?php echo $itm4?>/<?php echo $itme4?>/<?php echo $itms4?><br />
<?php } if($itms5) { ?>
<option value="5"><?php echo $itm5?>/<?php echo $itme5?>/<?php echo $itms5?><br />
<?php } if($itms6) { ?>
<option value="6"><?php echo $itm6?>/<?php echo $itme6?>/<?php echo $itms6?><br />
<?php } ?>
</select>
<br>
<br>
<select name="merge2" onclick=sl('itemmerge'); href="javascript:void(0);">
<option value="0">■ 道具二 ■<br />
<?php if($itms1) { ?>
<option value="1"><?php echo $itm1?>/<?php echo $itme1?>/<?php echo $itms1?><br />
<?php } if($itms2) { ?>
<option value="2"><?php echo $itm2?>/<?php echo $itme2?>/<?php echo $itms2?><br />
<?php } if($itms3) { ?>
<option value="3"><?php echo $itm3?>/<?php echo $itme3?>/<?php echo $itms3?><br />
<?php } if($itms4) { ?>
<option value="4"><?php echo $itm4?>/<?php echo $itme4?>/<?php echo $itms4?><br />
<?php } if($itms5) { ?>
<option value="5"><?php echo $itm5?>/<?php echo $itme5?>/<?php echo $itms5?><br />
<?php } if($itms6) { ?>
<option value="6"><?php echo $itm6?>/<?php echo $itme6?>/<?php echo $itms6?><br />
<?php } ?>
</select>
<br>
<input type="button" class="cmdbutton" name="submit" value="提交" onclick="postCmd('gamecmd','command.php');this.disabled=true;">