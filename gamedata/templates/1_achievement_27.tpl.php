<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table><tr>
<td>
<?php if(($cpl['27']) ) { ?>
<img src="img/achievement_0.gif">
<?php } else { ?>
<img src="img/achievement_not_done.gif">
<?php } ?>
</td>
<td valign="top" align="left">
<b>
<?php if(($cpl['27']=='0') ) { ?>
秋后算账
<?php } if(($cpl['27']=='1') ) { ?>
报仇雪恨
<?php } if(($cpl['27']=='2') ) { ?>
血洗英灵殿
<?php } if(($cpl['27']=='999') ) { ?>
血洗英灵殿
<?php } ?>
</b>
<?php if(($cpl['27']=='999') ) { ?>
<span class="lime">[完成]</span>
<?php } else { if(($cpl['27']==0) ) { ?>
<span class="red">[未完成]</span>
<?php } else { ?>
<span class="clan">[进行中]</span>
<?php } } ?>
<br>
<font color="yellow">击杀总数： <?php echo $prc['27']?>名<br></font>
<?php if(($cpl['27']=='0') ) { ?>
<font color="olive">奖励： 切糕10<br></font> 击杀1名英灵<br>
<?php } if(($cpl['27']=='1') ) { ?>
<font color="olive">奖励： 积分300<br></font> 击杀30名英灵<br>
<?php } if(($cpl['27']=='2') ) { ?>
<font color="olive">奖励： 积分500 <span class="evergreen">称号 替天行道</span><br></font> 击杀100名英灵<br>
<?php } if(($cpl['27']=='999') ) { ?>
<font color="olive">奖励： 积分500 <span class="evergreen">称号 替天行道</span><br></font> 击杀100名英灵<br>
<?php } ?>
</td>
</tr></table>
