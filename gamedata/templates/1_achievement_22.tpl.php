<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table><tr>
<td>
<?php if(($cpl['22']) ) { ?>
<img src="img/ach/22.gif">
<?php } else { ?>
<img src="img/achievement_not_done.gif">
<?php } ?>
</td>
<td valign="top" align="left">
<b>233MAX</b>
<?php if(($cpl['22']) ) { ?>
<span class="lime">[完成]</span>
<?php } else { ?>
<span class="red">[未完成]</span>
<?php } ?>
<br>
<font color="yellow">击破次数： <?php echo $prc['22']?>次<br></font>
<font color="olive">奖励： 积分2333 <span class="evergreen">称号 l33t</span><br></font> 
击破天神冴月麟<br>
</td>
</tr></table>
