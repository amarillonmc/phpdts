<?php if(!defined('IN_GAME')) exit('Access Denied'); include template('header'); ?>
<div id="notice"></div>
<div id="aliveinfo">
<center>
<span class="subtitle">幸存者一览</span>
<form method="post" name="alive" onSubmit="return false;">
<input type="hidden" id="alivemode" name="alivemode" value="last">
<input type="hidden" id="gbmode" name="gbmode" value="none">
<p>
<input type="button" value="显示前<?php echo $alivelimit?>名幸存者" onClick="$('alivemode').value='last';$('gbmode').value='none';postCmd('alive','alive.php');">
【生存者数：<?php echo $alivenum?>人】
<input type="button" value="显示全部幸存者" onClick="$('alivemode').value='all';$('gbmode').value='none';postCmd('alive','alive.php');">
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
<input type="submit" value="返回首页">
</form>
</center>
</div>
<?php include template('footer'); ?>
