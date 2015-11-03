<?php if(!defined('IN_GAME')) exit('Access Denied'); include template('header'); ?>
<div class="subtitle" align="center">Entry Form</div>

<p><img border="0" src="img/story_0.gif" style="align:center"></p>

<p align="center">“Welcome to Hunger Games<br />
“Please enter your details<br />
“May the odds be ever in your favor.<br />


<form method="post"  action="valid.php" name="valid">
<input type="hidden" name="mode" value="enter"> Name : <?php echo $username?> <br />
<?php include template('usergdicon'); ?>
<br />
<?php include template('userwords'); ?>
<br />

<input type="submit" name="enter" value="Submit">
<input type="reset" name="reset" value="Reset">
</form>
<br />
<?php include template('footer'); ?>
