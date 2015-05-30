<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table><tr>
<td>
<?php if(($cpl['25']) ) { ?>
<img src="img/ach/25.gif">
<?php } else { ?>
<img src="img/achievement_not_done.gif">
<?php } ?>
</td>
<td valign="top" align="left">
<b>一尸两命</b>
<?php if(($cpl['25']) ) { ?>
<span class="lime">[完成]</span>
<?php } else { ?>
<span class="red">[未完成]</span>
<?php } ?>
<br>
<font color="yellow">击破次数： <?php echo $prc['25']?>次<br></font>
<font color="olive">奖励： 积分 111 切糕 333 <span class="evergreen">称号 卸腿者</span><br></font> 
击破Yoshiko-G<br>
</td>
</tr></table>
