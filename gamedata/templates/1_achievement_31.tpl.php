<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table><tr>
<td>
<?php if(($cpl['31']) ) { ?>
<img src="img/ach/31.gif">
<?php } else { ?>
<img src="img/achievement_not_done.gif">
<?php } ?>
</td>
<td valign="top" align="left">
<b>Return to Sender</b>
<?php if(($cpl['31']) ) { ?>
<span class="lime">[完成]</span>
<?php } else { ?>
<span class="red">[未完成]</span>
<?php } ?>
<br>
<font color="yellow">完成次数： <?php echo $prc['31']?>次<br></font>
<?php if((!$cpl['31']) ) { ?>
<font color="olive">奖励： ■■■■■■ ■■■■<br></font> 
<?php } if((!$cpl['31']) ) { ?>
■■■■■■■ ■■<br>■■■■■■■■   ■■■■
<?php } if(($cpl['31']) ) { ?>
<font color="olive">奖励： <span class="evergreen">称号 R.T.S</span><br></font> 
<?php } if(($cpl['31']) ) { ?>
用【KEY系催泪弹】主动击杀<span class="evergreen">“KEY男”</span>持有者<br>
<?php } ?>
</td>
</tr></table>
