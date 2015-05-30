<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table><tr>
<td>
<?php if(($cpl['13']=='999') ) { ?>
<img src="img/ach/13_1.gif">
<?php } if(($cpl['13']=='0') ) { ?>
<img src="img/ach/N.gif">
<?php } if((($cpl['13']!='999')&&($cpl['13']!='0')) ) { ?>
<img src="img/ach/D.gif">
<?php } ?>
</td>
<td valign="top" align="left">
<b>
<?php if(($cpl['13']=='0') ) { ?>
深度冻结
<?php } if(($cpl['13']=='1') ) { ?>
跨过彩虹
<?php } if(($cpl['13']=='999') ) { ?>
跨过彩虹
<?php } ?>
</b>
<?php if(($cpl['13']=='999') ) { ?>
<span class="lime">[完成]</span>
<?php } else { if(($cpl['13']==0) ) { ?>
<span class="red">[未完成]</span>
<?php } else { ?>
<span class="clan">[进行中]</span>
<?php } } ?>
<br>
<font color="yellow">推倒次数： <?php echo $prc['13']?>次<br></font>
<?php if(($cpl['13']=='0') ) { ?>
<font color="olive">奖励： 积分150 切糕250<br></font> 推倒蓝凝1次<br>
<?php } if(($cpl['13']=='1') ) { ?>
<font color="olive">奖励： <span class="evergreen">称号 跨过彩虹</span><br></font> 推倒蓝凝3次<br>
<?php } if(($cpl['13']=='999') ) { ?>
<font color="olive">奖励： <span class="evergreen">称号 跨过彩虹</span><br></font> 推倒蓝凝3次<br>
<?php } ?>
</td>
</tr></table>
