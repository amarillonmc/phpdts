<?php if(!defined('IN_GAME')) exit('Access Denied'); include template('header'); ?>
<span class="subtitle">Historial Winners</span>
<?php if($command == 'info') { include template('winnerinfo'); } elseif($command == 'news') { ?>
<form method="post" name="info" action="winner.php">
<input type="submit" value="Back to Winner List">
<div align="left">
<?php echo $hnewsinfo?>
</div>
<input type="submit" value="Back to Winner List">
</form>
<?php } else { include template('winnerlist'); } include template('footer'); ?>
