<?php if(!defined('IN_GAME')) exit('Access Denied'); include template('header'); ?>
<div class="subtitle" ><?php echo $message?>
</div>
<?php if($errorinfo) { ?>
file=<?php echo $file?><br \>line=<?php echo $line?>
<?php } ?>
<br>
<form method="post" name="backindex" action="index.php">
<input type="submit" name="enter" value="Back to Index">
</form>
<?php include template('footer'); ?>
