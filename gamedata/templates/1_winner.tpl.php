<?php if(!defined('IN_GAME')) exit('Access Denied'); include template('header'); ?>
<span class="subtitle">历史优胜者</span>
<?php if($command == 'info') { include template('winnerinfo'); } elseif($command == 'news') { ?>
<form method="post" name="info" action="winner.php">
<input type="submit" value="返回优胜列表">
<div align="left">
<?php echo $hnewsinfo?>
</div>
<input type="submit" value="返回优胜列表">
</form>
<?php } else { include template('winnerlist'); } include template('footer'); ?>
