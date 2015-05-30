<?php if(!defined('IN_GAME')) exit('Access Denied'); include template('header'); ?>
<div id="notice"></div>
<span class="subtitle">玩家排行榜</span>
<center>
<form id='showrank' name="showrank" method="post">
<input type="hidden" name="checkmode" id="checkmode" value="credits">
<div>
<input type="button" id="credits" value="查看积分榜" onClick="document['showrank']['checkmode'].value='credits';postCmd('showrank','rank.php');return false;">
<?php if($gamblingon) { ?>
<input type="button" id="credits2" value="查看切糕榜" onClick="document['showrank']['checkmode'].value='credits2';postCmd('showrank','rank.php');return false;">
<?php } ?>
<input type="button" id="winrate" value="查看胜率榜" onClick="document['showrank']['checkmode'].value='winrate';postCmd('showrank','rank.php');return false;">
</div>
</form>
<div id="rank">
<?php include template('rankinfo'); ?>
</div>
</center>
<?php include template('footer'); ?>
