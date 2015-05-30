<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table><tr>
<td>
<?php if(($cpl['20']) ) { ?>
<img src="img/ach/20.gif">
<?php } else { ?>
<img src="img/achievement_not_done.gif">
<?php } ?>
</td>
<td valign="top" align="left">
<b>寻星急袭</b>
<?php if(($cpl['20']) ) { ?>
<span class="lime">[完成]</span>
<?php } else { ?>
<span class="red">[未完成]</span>
<?php } ?>
<br>
<font color="yellow">击破次数： <?php echo $prc['20']?>次<br></font>
<font color="olive">奖励： 积分268 切糕 263 <span class="evergreen">称号 寻星者</span><br></font> 
击破虚子<br>
</td>
</tr></table>
