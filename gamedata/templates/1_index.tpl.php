<?php if(!defined('IN_GAME')) exit('Access Denied'); include template('header'); ?>
<table align="center" style="text-align:center;border:0;padding:0;">
<tr>
<td><span class="yellow">Game Version：</span></td>
<td style="text-align:left;"><span class="evergreen"><?php echo $gameversion?></span></td>
<td style="text-align:center;padding:0 0 0 25px;"><span class="yellow">Admin news</span></td>
</tr>
<tr>
<td><span class="yellow">Current Time：</span></td>
<td style="text-align:left;"><span class="evergreen"><?php echo $month?>/<?php echo $day?> <?php echo $week["$wday"]?> <?php echo $hour?>:<?php echo $min?></span></td>
<td rowspan="4" style="width:400px;vertical-align:top;text-align:left;padding:0 0 0 25px;"><span class="evergreen"><?php echo $adminmsg?></span></td>
</tr>
<tr>
<td><span class="yellow">System Status：</span></td>
<td style="text-align:left;"><span class="evergreen"><?php echo $systemmsg?></span></td>
</tr>
<tr>
<td><span class="yellow">Game Info：</span></td>
<td style="text-align:left;"><span class="evergreen"><span class="evergreen2">ROUND <?php echo $gamenum?> <?php echo $gstate[$gamestate]?></span></span></td>
</tr>
<tr>
<td colspan="2" style="vertical-align:top;">
<div>
<?php if($gamestate > 10 ) { ?>
Round Running for  <span id="timing"></span><script type="text/javascript">updateTime(<?php echo $timing?>,1);</script><br> 
<?php if($hplayer) { ?>
Current Highest Damage <?php echo $hplayer?> (<?php echo $hdamage?>)<br>
<?php } } else { if($starttime > $now) { ?>
Next Round Starts In  <span id="timing"></span><script type="text/javascript">updateTime(<?php echo $timing?>,0);</script>   <br>
<?php } else { ?>
Next Round Starts In  <span id="timing"></span>UNDEFINED<br>
<?php } if($hplayer) { ?>
Last Round Highest Damage <?php echo $hplayer?> (<?php echo $hdamage?>)<br>
<?php } } ?>
</div>
<div>
Last Round Result：<span id="lastwin"><?php echo $gwin[$winmode]?></span>
<?php if($winner) { ?>
，Winner：<span id="lastwinner"><?php echo $winner?></span>
<?php } ?>
</div>

Adding Restrict Areas in <?php echo $areahour?> minutes ， After <?php echo $arealimit?> times, entry would be locked.<br>
Adding <?php echo $areaadd?> of Areas ， Current number of Restrict Areas is <?php echo $areanum?><br>
Auto Dodge Restriction：
<?php if($areaesc && $gamestate < 40) { ?>
<span class="yellow">ENABLED</span>
<?php } else { ?>
<span class="red">DISABLED</span>
<?php } ?>
<br>
<span class="red">NOTE: Auto Dodge function would be disabled when in Lock Mode</span><br><br>


Players：<span id="alivenum"><?php echo $validnum?></span>
Survivors：<span id="alivenum"><?php echo $alivenum?></span>
Deaths：<span id="alivenum"><?php echo $deathnum?></span>
<br />
<?php if($cuser) { ?>
<br />Welcome，<?php echo $cuser?>！
<form method="post" name="togame" action="game.php">
<input type="hidden" name="mode" value="main">
<input type="submit" name="enter" value="Enter">
</form>

<form method="post" name="quitgame" action="game.php">
<input type="hidden" name="mode" value="quit">
<input type="submit" name="quit" value="Quit">
</form>
<?php } else { ?>
<form method="post" name="login" action="login.php">
<input type="hidden" name="mode" value="main">
Username<input type="text" name="username" size="20" maxlength="20" value="<?php echo $cuser?>">
Password<input type="password" name="password" size="20" maxlength="20" value="<?php echo $cpass?>">
<input type="submit" name="enter" value="Login">
</form>
<?php } ?>
<span class="evergreen2">May the odds be ever in your favor.</span><br>
</td>
</tr>
</table>
<?php include template('footer'); ?>
