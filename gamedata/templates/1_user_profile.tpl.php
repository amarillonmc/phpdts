<?php if(!defined('IN_GAME')) exit('Access Denied'); include template('header'); ?>
<div class="subtitle" align="center">User Data</div>
<center>
<table style="text-align: center">
<tr>
<td>
<?php include template('user_profile_basicdata'); ?>
</td>
<td><pre>&#9;</pre></td>
<td>
<table>
<tr><td>
<div id="userIconImg" class="iconImg" >
<img src="img/
<?php if($gender != 'f') { ?>
m
<?php } else { ?>
f
<?php } ?>
_<?php echo $select_icon?>.gif" alt="<?php echo $select_icon?>">
</div>	
</td></tr>
<tr><td>
<?php if(($curuser) ) { ?>
<button type="button" onclick="window.location.href='user.php'">Edit My Profile</button>
<?php } ?>
</td></tr>
</table>
</td>
</tr>
</table>
<table>
<tr><td>
<?php include template('user_end_achievement'); ?>
</td></tr>
<tr><td>
<?php include template('user_battle_achievement'); ?>
</td></tr>
<tr><td>
<?php include template('user_mixitem_achievement'); ?>
</td></tr>
<tr><td>
<?php include template('user_other_achievement'); ?>
</td></tr>
</table>
</center>
<?php include template('footer'); ?>
