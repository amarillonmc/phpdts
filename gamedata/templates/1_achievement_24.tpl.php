<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table><tr>
<td>
<?php if(($cpl['24']) ) { ?>
<img src="img/ach/24.gif">
<?php } else { ?>
<img src="img/achievement_not_done.gif">
<?php } ?>
</td>
<td valign="top" align="left">
<b>逆推</b>
<?php if(($cpl['24']) ) { ?>
<span class="lime">[完成]</span>
<?php } else { ?>
<span class="red">[未完成]</span>
<?php } ?>
<br>
<font color="yellow">击破次数： <?php echo $prc['24']?>次<br></font>
<font color="olive">奖励： 积分 211 切糕 299 <span class="evergreen">称号 时代眼泪</span><br></font> 
击破北京推倒你<br>
</td>
</tr></table>
