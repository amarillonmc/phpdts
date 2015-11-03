<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<br>

<form method="post" name="info" onsubmit="winner.php">
<input type="hidden" id="command" name="command" value="info">
<input type="hidden" id="gnum" name="gnum" value="">
<center>
<TABLE border="1" cellspacing="0" cellpadding="0">
<TR height="20">
<TD class="b1"><span>Round</span></TD>
<TD class="b1"><span>Win Method</span></TD>
<TD class="b1"><span>Winner</span></TD>
<TD class="b1"><span>Avatar</span></TD>
<TD class="b1"><span>Game Ends At</span></TD>
<TD class="b1"><span>Motto</span></TD>
<TD class="b1"><span>Weapon</span></TD>
<TD class="b1"><span>Max Damage</span></TD>
<TD class="b1"><span>Max Kill</span></TD>
<TD class="b1"><span>Details</span></TD>
</TR>
<?php if(is_array($winfo)) { foreach($winfo as $gid => $info) { ?>
<TR height="20">
<TD class="b2"><span><?php echo $gid?></span></TD>
<TD class="b3"><span><?php echo $gwin[$info['wmode']]?></span></TD>
<TD class="b3" style="white-space: nowrap;">
<?php if($info['name']) { ?>
<span class="evergreen"><u><a href="user_profile.php?playerID=<?php echo $info['name']?>"><?php echo $info['name']?></a></u></span>
<?php } else { ?>
<span class="grey">无</span>
<?php } ?>
</TD>
<TD class="b3"><span><img src="img/<?php echo $info['iconImg']?>" style="width:70;height:40;"></span></TD>
<TD class="b3"><span><?php echo $info['date']?></span><br><span><?php echo $info['time']?></span></TD>
<TD class="b3">
<?php if($info['motto']) { ?>
<span class="white"><?php echo $info['motto']?></span>
<?php } else { ?>
<span class="grey">NONE</span>
<?php } ?>
</TD>
<TD class="b3">
<?php if($info['wep']) { ?>
<span class="white"><?php echo $info['wep']?></span>
<?php } else { ?>
<span class="grey">NONE</span>
<?php } ?>
</TD>
<TD class="b3">
<?php if($info['hdmg']) { ?>
<span class="white"><u><a href="user_profile.php?playerID=<?php echo $info['hdp']?>"><?php echo $info['hdp']?></a></u></span>
<?php } else { ?>
<span class="grey">无</span>
<?php } ?>
</TD>
<TD class="b3">
<?php if($info['hkill']) { ?>
<span class="white"><u><a href="user_profile.php?playerID=<?php echo $info['hkp']?>"><?php echo $info['hkp']?></a></u></span>
<?php } else { ?>
<span class="grey">无</span>
<?php } ?>
</TD>
<TD class="b3">
<span>
<input type="button" value="CHARACTER INFO" 
<?php if($info['wmode'] && $info['wmode'] != 1 && $info['wmode'] !=4 && $info['wmode'] != 6) { ?>
onclick="$('command').value='info';$('gnum').value='<?php echo $gid?>';document.info.submit();"
<?php } else { ?>
disabled
<?php } ?>
>
<input type="button" value="ROUND NEWS LOG" 
<?php if($info['wmode'] && $info['wmode'] !=4) { ?>
onclick="$('command').value='news';$('gnum').value='<?php echo $gid?>';document.info.submit();"
<?php } else { ?>
disabled
<?php } ?>
>
</span>
</TD>
</TR>
<?php } } ?>
</TABLE>
</center>
</form>

<form method="post" name="list" action="winner.php">
<input type="hidden" name="command" value="list">
<input type="hidden" name="start" value="<?php echo $gamenum?>">
<input style="width: 120px;" type="button" value="Most Recend <?php echo $winlimit?> Rounds" onClick="document['list'].submit();">
<br>
<?php if(isset($listinfo)) { ?>
<?php echo $listinfo?>
<?php } ?>
</form>
