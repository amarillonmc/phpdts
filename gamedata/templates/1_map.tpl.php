<?php if(!defined('IN_GAME')) exit('Access Denied'); include template('header'); ?>
<div class="subtitle" >虚拟世界地图</div>
<div id="notice"></div>
<center>
<div><?php echo $mapcontent?></div>
<p><span class="red">红字=禁区</span>；<span class="yellow">黄字=即将成为禁区</span>；<span class="lime">绿字=正常通行</span></p>
</center>
<?php include template('footer'); ?>
