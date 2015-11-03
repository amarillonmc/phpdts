<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<TABLE border="0" cellspacing="0" cellpadding="0">
<TR height="20">
<TD class="b1"><span>Ranking</span></TD>
<TD class="b1"><span>UID</span></TD>
<TD class="b1"><span>Name</span></TD>
<TD class="b1"><span>Gender</span></TD>
<TD class="b1"><span>Avatar</span></TD>
<TD class="b1" style="maxwidth:120"><span>Motto</span></TD>
<TD class="b1"><span>Power Level</span></TD>
<TD class="b1"><span>Gold</span></TD>
<TD class="b1"><span>Total Game</span></TD>
<TD class="b1"><span>Win Game</span></TD>
<TD class="b1"><span>Win Ratio</span></TD>
<TD class="b1"><span>last Game</span></TD>
</TR>
<?php if(is_array($rankdata)) { foreach($rankdata as $urdata) { ?>
<TR height="20">
<TD class="b2"><span>
<?php if($urdata['number']==1) { ?>
<a title="KING!"><span class="red">CHAMPION</span></a>
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
