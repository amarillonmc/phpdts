<?php if(!defined('IN_GAME')) exit('Access Denied'); include template('header'); ?>
<div id="notice"></div>
<div class="subtitle" >News</div>

<div align="left">
<div class="clearfix">
<span style="float:left;" ><img border="0" src="img/question.gif"></span>
<span><span class="evergreen">“May the Odds be ever in your favor!<br />You can review news here.<br />Good Luck.”</span></span>
</div>
<br>
<span class="evergreen">Current Time：<?php echo $month?>/<?php echo $day?> <?php echo $week["$wday"]?> <?php echo $hour?>:<?php echo $min?></span><br />
<span class="evergreen">Current Weather：<?php echo $wthinfo[$weather]?></span><br />
<?php if($gamestate==40) { ?>
<span class="yellow">Game in LOCKDOWN Mode!</span><br />
<?php } if($gamestate==50) { ?>
<span class="red">Game in DUEL Mode!</span><br />
<?php } if($hack) { ?>
<span class="evergreen">（LOCKDOWN OVERRIDED for 1 Cycle）</span>
<?php } include template('areainfo'); ?>
<br><br>
<form method="post" name="news" onSubmit="return false;">
<input type="hidden" id="newsmode" name="newsmode" value="last">
<button onClick="$('newsmode').value='last';postCmd('news','news.php');">Display newest <?php echo $newslimit?> news</button>
<button onClick="$('newsmode').value='all';postCmd('news','news.php');">Display All News</button>
<button onClick="$('newsmode').value='chat';postCmd('news','news.php');">Display All Chatlog</button>
</form>


<div id="newsinfo">
<?php if($newsmode == 'all') { include template('newsinfo'); } else { include template('lastnews'); } ?>
</div>

</div>
<br>
<form method="post" name="backindex" action="index.php">
<input type="submit" name="enter" value="Back to Index">
</form>
<?php include template('footer'); ?>
