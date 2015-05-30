<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table><tr>
<td>
<?php if(($cpl['26']) ) { ?>
<img src="img/ach/26.gif">
<?php } else { ?>
<img src="img/achievement_not_done.gif">
<?php } ?>
</td>
<td valign="top" align="left">
<b>正直者之死</b>
<?php if(($cpl['26']) ) { ?>
<span class="lime">[完成]</span>
<?php } else { ?>
<span class="red">[未完成]</span>
<?php } ?>
<br>
<font color="yellow">击破次数： <?php echo $prc['26']?>次<br></font>
<font color="olive">奖励： 积分 1 切糕 111 <span class="evergreen">称号 吉祥物</span><br></font> 
击破便当盒<br>
</td>
</tr></table>
