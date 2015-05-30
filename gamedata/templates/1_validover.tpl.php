<?php if(!defined('IN_GAME')) exit('Access Denied'); include template('header'); ?>
<div class="subtitle" >入场表格填写完成</div>
<p><img border="0" src="img/story_0.gif" style="align:center"></p>
<table border="0" cellspacing="0" align="center">
  <tbody>
<tr>
<td class="b1"><span>姓名</span></td>
<td class="b3"><span><?php echo $cuser?></span></td>
<td rowspan="3" colspan="2" class="b3"><span><img src="./img/<?php echo $gd?>_<?php echo $icon?>.gif" border="0" /></span></td>
</tr>
<tr>
<td class="b1"><span>学号</span></td>
<td class="b3"><span><?php echo $sexinfo[$gd]?><?php echo $sNo?>号</span></td>

</tr>
<tr>
<td class="b1"><span>内定称号</span></td>
<td class="b3"><span><?php echo $clubinfo[$club]?></span></td>
</tr>
<tr>
<td class="b1"><span>生命</span></td>
<td class="b3"><span><?php echo $hp?> / <?php echo $mhp?></span></td>
<td class="b1"><span>体力</span></td>
<td class="b3"><span><?php echo $sp?> / <?php echo $msp?></span></td>
</tr>
<tr>
<td class="b1"><span>攻击力</span></td>
<td class="b3"><span><?php echo $att?></span></td>
<td class="b1"><span>防御力</span></td>
<td class="b3"><span><?php echo $def?></span></td>
</tr>
<tr>
<td class="b1"><span>武器</span></td>
<td class="b3" colspan="3"><span><?php echo $wep?></span></td>
</tr>
<tr>
<td class="b1"><span>随机道具1</span></td>
<td class="b3" colspan="3"><span><?php echo $itm['3']?></span></td>
</tr>
<tr>
<td class="b1"><span>随机道具2</span></td>
<td class="b3" colspan="3"><span><?php echo $itm['4']?></span></td>
</tr>
  </tbody>
</table>
<p align="center">“<?php echo $cuser?>，对吧？正在为您创建虚拟身份……<br>
“创建完成！您可以凭这个身份参加我们的特别活动了。
<br>
“会场入口就在前面。动漫祭的开幕仪式就要开始了，请您尽快入场。”<br><br>

<form method="post"  action="valid.php" style="margin: 0px">
<input type="hidden" name="mode" value="notice">
<input type="submit" name="enter" value="进入会场">
</form>
</p>
<?php include template('footer'); ?>
