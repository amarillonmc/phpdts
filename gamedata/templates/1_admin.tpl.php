<?php if(!defined('IN_GAME')) exit('Access Denied'); include template('header'); ?>
<center>
<div class="subtitle" >Admin</div>
<div><span class="yellow"><?php echo $lang['mygroup']?> <?php echo $mygroup?></span></div>
<div><span class="yellow"><?php echo $cmd_info?></span></div>
<?php if($showdata) { ?>
<?php echo $showdata?>
<div>
<form method="post" name="goto_menu" action="admin.php">
<input type="submit" name="enter" value="<?php echo $lang['goto_menu']?>">
</form>
</div>
<?php } else { include template('admin_menu'); } ?>
</center>
<?php include template('footer'); ?>
