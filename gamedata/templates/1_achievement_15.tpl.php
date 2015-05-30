<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table><tr>
<td>
<?php if(($cpl['15']) ) { ?>
<img src="img/achievement_0.gif">
<?php } else { ?>
<img src="img/achievement_not_done.gif">
<?php } ?>
</td>
<td valign="top" align="left">
<b>
<?php if(($cpl['15']=='0') ) { ?>
不屈的生命
<?php } if(($cpl['15']=='1') ) { ?>
那种话最讨厌了
<?php } if(($cpl['15']=='2') ) { ?>
明亮的未来
<?php } if(($cpl['15']=='999') ) { ?>
明亮的未来
<?php } ?>
</b>
<?php if(($cpl['15']=='999') ) { ?>
<span class="lime">[完成]</span>
<?php } else { if(($cpl['15']==0) ) { ?>
<span class="red">[未完成]</span>
<?php } else { ?>
<span class="clan">[进行中]</span>
<?php } } ?>
<br>
<font color="yellow">合成次数： <?php echo $prc['15']?>次<br></font>
<?php if(($cpl['15']=='0') ) { ?>
<font color="olive">奖励： 切糕10<br></font> 合成物品【KEY系生命弹】1次<br>
<?php } if(($cpl['15']=='1') ) { ?>
<font color="olive">奖励： 积分200 <span class="evergreen">称号 素描本</span><br></font> 合成物品【KEY系生命弹】5次<br>
<?php } if(($cpl['15']=='2') ) { ?>
<font color="olive">奖励： 积分700 <span class="evergreen">称号 未来战士</span><br></font> 合成物品【KEY系生命弹】30次<br>
<?php } if(($cpl['15']=='999') ) { ?>
<font color="olive">奖励： 积分700 <span class="evergreen">称号 未来战士</span><br></font> 合成物品【KEY系生命弹】30次<br>
<?php } ?>
</td>
</tr></table>
