<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
﻿
<?php include template('header'); ?>
<div class="subtitle" >Game Start</div>

<p align="center">
Background Story Goes here.<br>
Click the button to proceed.
</p>

<form method="post" action="valid.php">
<input type="hidden" name="mode" value="tutorial">
<input type="button" value="GAME START!" onclick="window.location.href='game.php'">
</form>
<?php include template('footer'); ?>
