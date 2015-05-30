<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table><tr>
<td>
<?php if(($cpl['29']) ) { ?>
<img src="img/achievement_0.gif">
<?php } else { ?>
<img src="img/achievement_not_done.gif">
<?php } ?>
</td>
<td valign="top" align="left">
<b>
<?php if(($cpl['29']=='0') ) { ?>
及时补给
<?php } if(($cpl['29']=='1') ) { ?>
衣食无忧
<?php } if(($cpl['29']=='2') ) { ?>
奥义很爽
<?php } if(($cpl['29']=='999') ) { ?>
奥义很爽
<?php } ?>
</b>
<?php if(($cpl['29']=='999') ) { ?>
<span class="lime">[完成]</span>
<?php } else { if(($cpl['29']==0) ) { ?>
<span class="red">[未完成]</span>
<?php } else { ?>
<span class="clan">[进行中]</span>
<?php } } ?>
<br>
<font color="yellow">目前进度： <?php echo $prc['29']?>点<br></font>
<?php if(($cpl['29']=='0') ) { ?>
<font color="olive">奖励： 切糕5<br></font> 使用无毒补给的总效果达到32767点<br>
<?php } if(($cpl['29']=='1') ) { ?>
<font color="olive">奖励： 切糕50 <span class="evergreen">称号 美食家</span><br></font> 使用无毒补给的总效果达到142857点<br>
<?php } if(($cpl['29']=='2') ) { ?>
<font color="olive">奖励： 切糕200 <span class="evergreen">称号 补给掠夺者</span><br></font> 使用无毒补给的总效果达到999983点<br>
<?php } if(($cpl['29']=='999') ) { ?>
<font color="olive">奖励： 切糕200 <span class="evergreen">称号 补给掠夺者</span><br></font> 使用无毒补给的总效果达到999983点<br>
<?php } ?>
</td>
</tr></table>