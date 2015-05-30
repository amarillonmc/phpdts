<?php if(!defined('IN_GAME')) exit('Access Denied'); include template('header'); ?>
<div id="notice"></div>
<div class="subtitle" >进行状况</div>

<div align="left">
<div class="clearfix">
<span style="float:left;" ><img border="0" src="img/question.gif"></span>
<span><span class="evergreen">“各位仍在努力奋战，咱很欣慰。<br />以下是到现在为止的游戏状况。<br />请各位再接再厉。”</span></span>
</div>
<br>
<span class="evergreen">当前时间：<?php echo $month?>月<?php echo $day?>日 星期<?php echo $week["$wday"]?> <?php echo $hour?>:<?php echo $min?></span><br />
<span class="evergreen">当前天气：<?php echo $wthinfo[$weather]?></span><br />
<?php if($gamestate==40) { ?>
<span class="yellow">游戏已经进入连斗阶段！</span><br />
<?php } if($gamestate==50) { ?>
<span class="red">游戏已经进入死斗模式！</span><br />
<?php } if($hack) { ?>
<span class="evergreen">（禁区已解除）</span>
<?php } include template('areainfo'); ?>
<br><br>
<form method="post" name="news" onSubmit="return false;">
<input type="hidden" id="newsmode" name="newsmode" value="last">
<button onClick="$('newsmode').value='last';postCmd('news','news.php');">显示最新的<?php echo $newslimit?>条消息</button>
<button onClick="$('newsmode').value='all';postCmd('news','news.php');">显示全部消息</button>
<button onClick="$('newsmode').value='chat';postCmd('news','news.php');">显示最新聊天纪录</button>
</form>


<div id="newsinfo">
<?php if($newsmode == 'all') { include template('newsinfo'); } else { include template('lastnews'); } ?>
</div>

</div>
<br>
<form method="post" name="backindex" action="index.php">
<input type="submit" name="enter" value="返回首页">
</form>
<?php include template('footer'); ?>
