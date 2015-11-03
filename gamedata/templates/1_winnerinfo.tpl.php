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
<td height="20" class="b1"><span><span class="yellow">System</span></td>
</tr>
<tr><td valign="top"class="b3" style="text-align: left"><div>
Game Round <?php echo $gid?> <br>
Players: <?php echo $vnum?> <br>
Win Method： <?php echo $gwin[$wmode]?><br>
Game Time：<?php echo $gdate?><br>
Game Start Time：<?php echo $gsdate?><br>           
Game End Time：<?php echo $gedate?><br>
Highest Damage： <u><a href="user_profile.php?playerID=<?php echo $hdp?>"><?php echo $hdp?></a></u> (<?php echo $hdmg?>)<br>
Highest Kills： <u><a href="user_profile.php?playerID=<?php echo $hkp?>"><?php echo $hkp?></a></u> (<?php echo $hkill?>)<br>
<br>
<form method="post" name="back" action="winner.php">
<input type="submit" name="submit" value="Back">
</form>

</div></td>
</tr>
</table>
</td>
</tr>
</table>
