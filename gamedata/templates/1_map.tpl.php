<?php if(!defined('IN_GAME')) exit('Access Denied'); include template('header'); ?>
<div class="subtitle" >Map</div>
<div id="notice"></div>
<center>
<div><?php echo $mapcontent?></div>
<p><span class="red">RED=Restrictd Areas</span>；<span class="yellow">YELLOW=Will be Restricted</span>；<span class="lime">GREEN=Normal</span></p>
</center>
<?php include template('footer'); ?>
