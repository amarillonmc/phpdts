<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table><tr>
<td>
<?php if(($cpl['30']=='999') ) { ?>
<img src="img/ach/30.gif">
<?php } if(($cpl['30']=='0') ) { ?>
<img src="img/ach/N.gif">
<?php } if((($cpl['30']!='999')&&($cpl['30']!='0')) ) { ?>
<img src="img/ach/D.gif">
<?php } ?>
</td>
<td valign="top" align="left">
<b>
<?php if(($cpl['30']=='0') ) { ?>
饥不择食
<?php } if(($cpl['30']=='1') ) { ?>
尝百草
<?php } if(($cpl['30']=='2') ) { ?>
吞食天地
<?php } if(($cpl['30']=='999') ) { ?>
吞食天地
<?php } ?>
</b>
<?php if(($cpl['30']=='999') ) { ?>
<span class="lime">[完成]</span>
<?php } else { if(($cpl['30']==0) ) { ?>
<span class="red">[未完成]</span>
<?php } else { ?>
<span class="clan">[进行中]</span>
<?php } } ?>
<br>
<font color="yellow">目前进度： <?php echo $prc['30']?>次<br></font>
<?php if(($cpl['30']=='0') ) { ?>
<font color="olive">奖励： 切糕5<br></font> 食用30效以上的有毒补给5次<br>
<?php } if(($cpl['30']=='1') ) { ?>
<font color="olive">奖励： 切糕50 <span class="evergreen">称号 神农</span><br></font> 食用30效以上的有毒补给133次<br>
<?php } if(($cpl['30']=='2') ) { ?>
<font color="olive">奖励： 切糕200 <span class="evergreen">称号 贝爷</span><br></font> 食用30效以上的有毒补给365次<br>
<?php } if(($cpl['30']=='999') ) { ?>
<font color="olive">奖励： 切糕200 <span class="evergreen">称号 贝爷</span><br></font> 食用30效以上的有毒补给365次<br>
<?php } ?>
</td>
</tr></table>