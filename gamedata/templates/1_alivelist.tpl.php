<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<TABLE border="1">
<tr align="center" class="b1">
<td class="b1"><span>NAMEID</span></td>
<td width="140" class="b1"><span>Avatar</span></td>
<td class="b1"><span>LVL</span></td>
<td class="b1"><span>Kills</span></td>
<td class="b1"><span>Coins</span></td>
<td class="b1"><span>APM</span></td>
<?php if($gamestate < 40 ) { ?>
<td class="b1"><span>Team</span></td>
<?php } ?>
<td width="300" class="b1"><span>Comment</span></td>
<td class="b1"><span>Win Ratio</span></td>
<?php if($gamblingon ) { ?>
<td class="b1"><span>Sponsers</span></td>
<td class="b1"><span>Golds Betted</span></td>
<?php } ?>
</tr>
<?php if(is_array($alivedata)) { foreach($alivedata as $alive) { ?>
<tr class="b3">
<td align="center" class="b3"><span><u><a href="user_profile.php?playerID=<?php echo $alive['name']?>"><?php echo $alive['name']?></a></u><br><?php echo $sexinfo[$alive['gd']]?> <?php echo $alive['sNo']?> </span></td>
<td align="center" class="b3"><span><IMG src="img/<?php echo $alive['iconImg']?>" width="140" height="80" border="0" align="absmiddle"></span></td>
<td class="b3"><span><?php echo $alive['lvl']?></span></td>
<td class="b3"><span><?php echo $alive['killnum']?></span></td>
<td class="b3"><span><?php echo $alive['money']?></span></td>
<td class="b3"><span><?php echo $alive['apm']?></span></td>
<?php if($gamestate < 40 ) { ?>
<td class="b3"><span>
<?php if($alive['teamID']) { ?>
<?php echo $alive['teamID']?>
<?php } else { ?>
NONE
<?php } ?>
</span></td>
<?php } ?>
<td class="b3"><span><?php echo $alive['motto']?></span></td>
<td class="b3"><span><?php echo $alive['winrate']?></span></td>
<?php if($gamblingon ) { ?>
<td class="b3"><span><?php echo $alive['gbnum']?></span></td>
<td class="b3"><span><?php echo $alive['gbsum']?></span></td>
<?php } ?>
</tr>
<?php } } ?>
</table>
