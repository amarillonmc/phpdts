<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table><tr>
<td>
<img src="img/ach/32.gif">
</td>
<td valign="top" align="left">
<b>
<?php if(($cpl['32']) ) { ?>
kernel on chessboard
<?php } else { ?>
0xFFFFFFFFFFFFFFFF
<?php } ?>
</b>
<?php if(($cpl['32']) ) { ?>
<span class="lime">[完成]</span>
<?php } else { ?>
<span class="red">[未完成]</span>
<?php } ?>
<br>
<span class="linen">s=1;t=1;<br>for (i=1;i<=63;i++){<br>&nbsp;&nbsp;&nbsp;&nbsp;t*=2;s+=t;<br>}</span><br>
</td>
</tr></table>
