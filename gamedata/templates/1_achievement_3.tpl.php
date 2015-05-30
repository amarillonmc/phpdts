<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table><tr>
<td>
<?php if(($cpl['3']) ) { ?>
<img src="img/achievement_0.gif">
<?php } else { ?>
<img src="img/achievement_not_done.gif">
<?php } ?>
</td>
<td valign="top" align="left">
<b>
<?php if(($cpl['3']=='0') ) { ?>
脚本小子
<?php } if(($cpl['3']=='1') ) { ?>
黑客
<?php } if(($cpl['3']=='2') ) { ?>
幻境解离者？
<?php } if(($cpl['3']=='999') ) { ?>
幻境解离者？
<?php } ?>
</b>
<?php if(($cpl['3']=='999') ) { ?>
<span class="lime">[完成]</span>
<?php } else { if(($cpl['3']==0) ) { ?>
<span class="red">[未完成]</span>
<?php } else { ?>
<span class="clan">[进行中]</span>
<?php } } ?>
<br>
<font color="yellow">击杀总数： <?php echo $prc['3']?>名<br></font>
<?php if(($cpl['3']=='0') ) { ?>
<font color="olive">奖励： 切糕5<br></font> 击杀100名NPC<br>
<?php } if(($cpl['3']=='1') ) { ?>
<font color="olive">奖励： 积分200 <span class="evergreen">称号 黑客</span><br></font> 击杀500名NPC<br>
<?php } if(($cpl['3']=='2') ) { ?>
<font color="olive">奖励： 积分500 切糕15  <span class="evergreen">称号 最后一步</span><br></font> 击杀10000名NPC<br>
<?php } if(($cpl['3']=='999') ) { ?>
<font color="olive">奖励： 积分500 切糕15 <span class="evergreen">称号 最后一步</span><br></font> 击杀10000名NPC<br>
<?php } ?>
</td>
</tr></table>
