<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table><tr>
<td>
<?php if(($cpl['4']=='999') ) { ?>
<img src="img/ach/4_1.gif">
<?php } if(($cpl['4']=='0') ) { ?>
<img src="img/ach/N.gif">
<?php } if((($cpl['4']!='999')&&($cpl['4']!='0')) ) { ?>
<img src="img/ach/D.gif">
<?php } ?>
</td>
<td valign="top" align="left">
<b>
<?php if(($cpl['4']=='0') ) { ?>
冒烟突火
<?php } if(($cpl['4']=='1') ) { ?>
红杀将军
<?php } if(($cpl['4']=='999') ) { ?>
红杀将军
<?php } ?>
</b>
<?php if(($cpl['4']=='999') ) { ?>
<span class="lime">[完成]</span>
<?php } else { if(($cpl['4']==0) ) { ?>
<span class="red">[未完成]</span>
<?php } else { ?>
<span class="clan">[进行中]</span>
<?php } } ?>
<br>
<font color="yellow">推倒次数： <?php echo $prc['4']?>次<br></font>
<?php if(($cpl['4']=='0') ) { ?>
<font color="olive">奖励： 积分50 切糕75<br></font> 推倒红暮1次<br>
<?php } if(($cpl['4']=='1') ) { ?>
<font color="olive">奖励： <span class="evergreen">称号 越红者</span><br></font> 推倒红暮9次<br>
<?php } if(($cpl['4']=='999') ) { ?>
<font color="olive">奖励： <span class="evergreen">称号 越红者</span><br></font> 推倒红暮9次<br>
<?php } ?>
</td>
</tr></table>
