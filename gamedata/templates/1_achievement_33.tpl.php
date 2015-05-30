<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table><tr>
<td>
<?php if(($cpl['33']) ) { ?>
<img src="img/ach/33.gif">
<?php } else { ?>
<img src="img/achievement_not_done.gif">
<?php } ?>
</td>
<td valign="top" align="left">
<b>
<?php if(($cpl['33']=='0') ) { ?>
诅咒之刃
<?php } if(($cpl['33']=='999') ) { ?>
诅咒之刃
<?php } ?>
</b>
<?php if(($cpl['33']=='999') ) { ?>
<span class="lime">[完成]</span>
<?php } else { ?>
<span class="red">[未完成]</span>
<?php } ?>
<br>
<font color="yellow">完成次数： <?php echo $prc['33']?>次<br></font>
<font color="olive">奖励： 切糕522 <span class="evergreen">称号 剑圣</span><br></font>化解诅咒需以毒攻毒。豪运自然也不可或缺。<br>
</td>
</tr></table>
