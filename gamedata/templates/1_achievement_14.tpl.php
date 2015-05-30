<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table><tr>
<td>
<?php if(($cpl['14']) ) { ?>
<img src="img/achievement_0.gif">
<?php } else { ?>
<img src="img/achievement_not_done.gif">
<?php } ?>
</td>
<td valign="top" align="left">
<b>
<?php if(($cpl['14']=='0') ) { ?>
篝火的引导
<?php } if(($cpl['14']=='1') ) { ?>
世界的树形图
<?php } if(($cpl['14']=='2') ) { ?>
地=月
<?php } if(($cpl['14']=='999') ) { ?>
地=月
<?php } ?>
</b>
<?php if(($cpl['14']=='999') ) { ?>
<span class="lime">[完成]</span>
<?php } else { if(($cpl['14']==0) ) { ?>
<span class="red">[未完成]</span>
<?php } else { ?>
<span class="clan">[进行中]</span>
<?php } } ?>
<br>
<font color="yellow">合成次数： <?php echo $prc['14']?>次<br></font>
<?php if(($cpl['14']=='0') ) { ?>
<font color="olive">奖励： 切糕10<br></font> 合成物品【KEY系燃烧弹】1次<br>
<?php } if(($cpl['14']=='1') ) { ?>
<font color="olive">奖励： 积分200 <span class="evergreen">称号 树形图</span><br></font> 合成物品【KEY系燃烧弹】5次<br>
<?php } if(($cpl['14']=='2') ) { ?>
<font color="olive">奖励： 积分700 <span class="evergreen">称号 TERRA</span><br></font> 合成物品【KEY系燃烧弹】30次<br>
<?php } if(($cpl['14']=='999') ) { ?>
<font color="olive">奖励： 积分700 <span class="evergreen">称号 TERRA</span><br></font> 合成物品【KEY系燃烧弹】30次<br>
<?php } ?>
</td>
</tr></table>
