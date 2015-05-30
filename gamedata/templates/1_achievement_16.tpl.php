<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table><tr>
<td>
<?php if(($cpl['16']) ) { ?>
<img src="img/ach/16.gif">
<?php } else { ?>
<img src="img/achievement_not_done.gif">
<?php } ?>
</td>
<td valign="top" align="left">
<b>只是运气好而已</b>
<?php if(($cpl['16']) ) { ?>
<span class="lime">[完成]</span>
<?php } else { ?>
<span class="red">[未完成]</span>
<?php } ?>
<br>
<font color="yellow">完成次数： <?php echo $prc['16']?>次<br></font>
<font color="olive">奖励： 积分150 <span class="evergreen">称号 生存者</span><br></font> 
完成结局：最后幸存<br>
</td>
</tr></table>
