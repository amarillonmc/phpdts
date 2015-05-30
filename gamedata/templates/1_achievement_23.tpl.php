<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table><tr>
<td>
<?php if(($cpl['23']) ) { ?>
<img src="img/ach/23.gif">
<?php } else { ?>
<img src="img/achievement_not_done.gif">
<?php } ?>
</td>
<td valign="top" align="left">
<b>真名解放</b>
<?php if(($cpl['23']) ) { ?>
<span class="lime">[完成]</span>
<?php } else { ?>
<span class="red">[未完成]</span>
<?php } ?>
<br>
<font color="yellow">击破次数： <?php echo $prc['23']?>次<br></font>
<font color="olive">奖励： 切糕 888 <span class="evergreen">称号 赌玉狂魔</span><br></font> 
击破天神四面<br>
</td>
</tr></table>
