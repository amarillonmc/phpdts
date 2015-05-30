<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table><tr>
<td>
<?php if(($cpl['2']) ) { ?>
<img src="img/achievement_0.gif">
<?php } else { ?>
<img src="img/achievement_not_done.gif">
<?php } ?>
</td>
<td valign="top" align="left">
<b>
<?php if(($cpl['2']=='0') ) { ?>
Run With Wolves
<?php } if(($cpl['2']=='1') ) { ?>
Day Game
<?php } if(($cpl['2']=='2') ) { ?>
Thousand Enemies
<?php } if(($cpl['2']=='999') ) { ?>
Thousand Enemies
<?php } ?>
</b>
<?php if(($cpl['2']=='999') ) { ?>
<span class="lime">[完成]</span>
<?php } else { if(($cpl['2']==0) ) { ?>
<span class="red">[未完成]</span>
<?php } else { ?>
<span class="clan">[进行中]</span>
<?php } } ?>
<br>
<font color="yellow">击杀总数： <?php echo $prc['2']?>名<br></font>
<?php if(($cpl['2']=='0') ) { ?>
<font color="olive">奖励： 积分10<br></font> 在自己的行动中击杀10名玩家<br>
<?php } if(($cpl['2']=='1') ) { ?>
<font color="olive">奖励： 积分500 <span class="evergreen">称号 二度打</span><br></font> 在自己的行动中击杀100名玩家<br>
<?php } if(($cpl['2']=='2') ) { ?>
<font color="olive">奖励： 切糕200 <span class="evergreen">称号 G.D.M</span><br></font> 在自己的行动中击杀1000名玩家<br>
<?php } if(($cpl['2']=='999') ) { ?>
<font color="olive">奖励： 切糕200 <span class="evergreen">称号 G.D.M</span><br></font> 在自己的行动中击杀1000名玩家<br>
<?php } ?>
</td>
</tr></table>