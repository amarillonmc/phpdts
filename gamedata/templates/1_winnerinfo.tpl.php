<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table align="center">
<tr><td>
<span id="main">
<?php include template('profile'); ?>
<span>
</td>
<td>
<table border="1" width="250" height="300" cellspacing="0" cellpadding="0" >
<tr height="1">
<td height="20" class="b1"><span><span class="yellow">系统状况</span></td>
</tr>
<tr><td valign="top"class="b3" style="text-align: left"><div>
第 <?php echo $gid?> 回生存游戏<br>
参加人数： <?php echo $vnum?> 人<br>
胜利方式： <?php echo $gwin[$wmode]?><br>
游戏进行时间：<?php echo $gdate?><br>
游戏开始时间：<?php echo $gsdate?><br>           
游戏结束时间：<?php echo $gedate?><br>
本场最高伤害者： <u><a href="user_profile.php?playerID=<?php echo $hdp?>"><?php echo $hdp?></a></u> (<?php echo $hdmg?>)<br>
本场最多杀人者： <u><a href="user_profile.php?playerID=<?php echo $hkp?>"><?php echo $hkp?></a></u> (<?php echo $hkill?>)<br>
<br>
<form method="post" name="back" action="winner.php">
<input type="submit" name="submit" value="返回">
</form>

</div></td>
</tr>
</table>
</td>
</tr>
</table>
