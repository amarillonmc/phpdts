<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table><tr>
<td>
<?php if(($cpl['1']) ) { ?>
<img src="img/achievement_0.gif">
<?php } else { ?>
<img src="img/achievement_not_done.gif">
<?php } ?>
</td>
<td valign="top" align="left">
<b>清水池之王</b>
<?php if(($cpl['1']) ) { ?>
<span class="lime">[完成]</span>
<?php } else { ?>
<span class="red">[未完成]</span>
<?php } ?>
<br>
<font color="yellow">最快速度： <?php echo $prc['1']?>秒<br></font>
<font color="olive">奖励： 积分30 切糕16 <span class="evergreen">称号 KEY男</span><br></font> 
在开局5分钟内合成物品【KEY系催泪弹】<br>
</td>
</tr></table>
