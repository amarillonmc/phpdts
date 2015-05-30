<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table><tr>
<td>
<?php if(($cpl['28']) ) { ?>
<img src="img/ach/28.gif">
<?php } else { ?>
<img src="img/achievement_not_done.gif">
<?php } ?>
</td>
<td valign="top" align="left">
<b>烈火疾风</b>
<?php if(($cpl['28']) ) { ?>
<span class="lime">[完成]</span>
<?php } else { ?>
<span class="red">[未完成]</span>
<?php } ?>
<br>
<font color="yellow">最快速度： <?php echo $prc['28']?>秒<br></font>
<font color="olive">奖励： 积分250 <span class="evergreen">称号 神触</span><br></font> 
在开局30分钟内开启死斗模式<br>
</td>
</tr></table>
