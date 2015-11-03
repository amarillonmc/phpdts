<?php if(!defined('IN_GAME')) exit('Access Denied'); include template('header'); ?>
<div id="notice"></div>
<div id="aliveinfo">
<center>
<span class="subtitle">Survivors</span>
<form method="post" name="alive" onSubmit="return false;">
<input type="hidden" id="alivemode" name="alivemode" value="last">
<input type="hidden" id="gbmode" name="gbmode" value="none">
<p>
<input type="button" value="Display first <?php echo $alivelimit?> Survivors" onClick="$('alivemode').value='last';$('gbmode').value='none';postCmd('alive','alive.php');">
【Survivors：<?php echo $alivenum?>】
<input type="button" value="Display All Survivors" onClick="$('alivemode').value='all';$('gbmode').value='none';postCmd('alive','alive.php');">
</p>
<?php if($gamblingon && $gamestate >= 20) { ?>
<p>
<?php include template('gambling'); ?>
</p>
<?php } elseif($gamblingon && $gamestate <= 10) { ?>
<p>
<?php include template('lastgb'); ?>
</p>
<?php } ?>
</form>

<div id="alivelist">
<?php include template('alivelist'); ?>
</div>
<form method="post" name="backindex" action="index.php">
<input type="submit" value="Back to Index">
</form>
</center>
</div>
<?php include template('footer'); ?>
