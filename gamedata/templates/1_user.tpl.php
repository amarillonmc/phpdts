<?php if(!defined('IN_GAME')) exit('Access Denied'); include template('header'); ?>
<div class="subtitle" align="center">User Data</div>

<p align="center" class="linen">
If you want to change password, enter original and new password below. Leave blank to ignore.<br />
NOTE: If player is already in game, changes made to avatar and gender would take effect in next round.<br />
<span id="notice"></span><br />
<span id="info" class="yellow"></span>
</p>
<center>
<form method="post" action="user.php" name="userdata">
<input type="hidden" name="mode" value="edit">
<table style="text-align: center">
<tr>
<td style="vertical-align:top"><span class ="yellow">Account/Password</span>
<?php include template('userbasicdata'); ?>
</td>
<td rowspan="2"style="vertical-align:top"><span class ="yellow">Personal Info</span>
<?php include template('useradvdata'); ?>
</td>
</tr>
<tr>
<td><span class ="yellow">Credit Exchange</span>
<?php include template('usercrdtsdata'); ?>
</td>
</tr>
</table>


<div id="postdata">
<input type="submit" id="post" onClick="postCmd('userdata','user.php');return false;" value="Submit">
<input type="reset" id="reset" name="reset" value="Reset">
</div>
</form>
</center>
<br />
<?php include template('footer'); ?>
 