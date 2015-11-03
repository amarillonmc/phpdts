<?php if(!defined('IN_GAME')) exit('Access Denied'); include template('header'); ?>
<div class="subtitle" >Finished Entry Form</div>
<p><img border="0" src="img/story_0.gif" style="align:center"></p>
<table border="0" cellspacing="0" align="center">
  <tbody>
<tr>
<td class="b1"><span>Name</span></td>
<td class="b3"><span><?php echo $cuser?></span></td>
<td rowspan="3" colspan="2" class="b3"><span><img src="./img/<?php echo $gd?>_<?php echo $icon?>.gif" border="0" /></span></td>
</tr>
<tr>
<td class="b1"><span>ID</span></td>
<td class="b3"><span><?php echo $sexinfo[$gd]?><?php echo $sNo?>号</span></td>

</tr>
<tr>
<td class="b1"><span>Club</span></td>
<td class="b3"><span><?php echo $clubinfo[$club]?></span></td>
</tr>
<tr>
<td class="b1"><span>HP</span></td>
<td class="b3"><span><?php echo $hp?> / <?php echo $mhp?></span></td>
<td class="b1"><span>SP</span></td>
<td class="b3"><span><?php echo $sp?> / <?php echo $msp?></span></td>
</tr>
<tr>
<td class="b1"><span>ATK</span></td>
<td class="b3"><span><?php echo $att?></span></td>
<td class="b1"><span>DEF</span></td>
<td class="b3"><span><?php echo $def?></span></td>
</tr>
<tr>
<td class="b1"><span>Weapons</span></td>
<td class="b3" colspan="3"><span><?php echo $wep?></span></td>
</tr>
<tr>
<td class="b1"><span>Random Item1</span></td>
<td class="b3" colspan="3"><span><?php echo $itm['3']?></span></td>
</tr>
<tr>
<td class="b1"><span>Random Item2</span></td>
<td class="b3" colspan="3"><span><?php echo $itm['4']?></span></td>
</tr>
  </tbody>
</table>
<p align="center">“<?php echo $cuser?>，Entry Confirmed<br>
<br>
“May the odds be ever in your favor.”<br><br>

<form method="post"  action="valid.php" style="margin: 0px">
<input type="hidden" name="mode" value="notice">
<input type="submit" name="enter" value="ENTRY">
</form>
</p>
<?php include template('footer'); ?>
