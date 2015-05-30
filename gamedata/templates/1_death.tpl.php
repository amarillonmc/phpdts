<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<br>
<div class="subtitle"><?php echo $stateinfo[$state]?></div>
<div><?php echo $dinfo[$state]?></div>
<div>死亡时间：<?php echo $dtime?></div>
<?php if(!empty($kname) && (in_array($state,Array(20,21,22,23,24,28,29)))) { ?>
<div>凶手：<?php echo $kname?></div>
<?php } ?>
<br>
<span class="dmg">你死了。</span>
<br><br>
<?php if(isset($weibolog) && strpos($gameurl,'dianbo')!==false) { ?>
<input type="button" value="我靠！怒电波之！" onclick="window.location.href='http://dianbo.me/index.php?app=home&mod=Widget&act=share&url=http%3A%2F%2Flg.dianbo.me%2F&title=%<?php echo $weibolog?>%'">
<?php } else { ?>
<input type="button" class="cmdbutton" value="我靠！" disabled>
<?php } ?>
