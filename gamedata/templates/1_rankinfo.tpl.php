<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<TABLE border="0" cellspacing="0" cellpadding="0">
<TR height="20">
<TD class="b1"><span>排名</span></TD>
<TD class="b1"><span>UID</span></TD>
<TD class="b1"><span>姓名</span></TD>
<TD class="b1"><span>性别</span></TD>
<TD class="b1"><span>头像</span></TD>
<TD class="b1" style="maxwidth:120"><span>口头禅</span></TD>
<TD class="b1"><span>战斗力</span></TD>
<TD class="b1"><span>切糕</span></TD>
<TD class="b1"><span>参加游戏场数</span></TD>
<TD class="b1"><span>获胜场数</span></TD>
<TD class="b1"><span>胜率</span></TD>
<TD class="b1"><span>最后游戏</span></TD>
</TR>
<?php if(is_array($rankdata)) { foreach($rankdata as $urdata) { ?>
<TR height="20">
<TD class="b2"><span>
<?php if($urdata['number']==1) { ?>
<a title="触手！"><span class="red">榜首</span></a>
<?php } elseif($urdata['number']<=10) { ?>
<span class="yellow"><?php echo $urdata['number']?></span>
<?php } else { ?>
<?php echo $urdata['number']?>
<?php } ?>
</span></TD>
<TD class="b3"><span><?php echo $urdata['uid']?></span></TD>
<TD class="b3"><span><u><a href="user_profile.php?playerID=<?php echo $urdata['username']?>"><?php echo $urdata['username']?></a></u></span></TD>
<TD class="b3"><span>
<?php if($urdata['gender']) { ?>
<?php echo $sexinfo[$urdata['gender']]?>
<?php } else { ?>
<?php echo $sexinfo['0']?>
<?php } ?>
</span></TD>
<TD class="b3"><span><IMG src="img/<?php echo $urdata['img']?>" width="70" height="40" border="0" align="absmiddle"></span></TD>
<TD class="b3"><span><?php echo $urdata['motto']?></span></TD>
<TD class="b3"><span><span class="yellow"><?php echo $urdata['credits']?></span></span></TD>
<TD class="b3"><span><span class="yellow"><?php echo $urdata['credits2']?></span></span></TD>
<TD class="b3"><span><?php echo $urdata['validgames']?></span></TD>
<TD class="b3"><span><?php echo $urdata['wingames']?></span></TD>
<TD class="b3"><span><?php echo $urdata['winrate']?></span></TD>
<TD class="b3"><span><?php echo $urdata['lastgame']?></span></TD>
</TR>
<?php } } ?>
</TABLE>
