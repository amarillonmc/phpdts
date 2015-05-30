<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table><tr>
<td>
<?php if(($cpl['18']) ) { ?>
<img src="img/ach/18.gif">
<?php } else { ?>
<img src="img/achievement_not_done.gif">
<?php } ?>
</td>
<td valign="top" align="left">
<b>最后的荣光</b>
<?php if(($cpl['18']) ) { ?>
<span class="lime">[完成]</span>
<?php } else { ?>
<span class="red">[未完成]</span>
<?php } ?>
<br>
<font color="yellow">完成次数： <?php echo $prc['18']?>次<br></font>
<font color="olive">奖励： 积分500 <span class="evergreen">称号 最后的荣光</span><br></font> 
完成结局：锁定解除<br>
</td>
</tr></table>
