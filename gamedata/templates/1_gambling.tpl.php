<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
﻿
<?php if($gbnum) { ?>
Current Gold Pool：<?php echo $gbpool?>。
<?php } else { ?>
There are no bets avaliable
<?php } ?>
Current Odds：<?php echo $nowodds?>
<span class="yellow" id="gbinfo">
<?php echo $gbinfo?>
</span>
<?php if($cuser && isset($credits2)) { ?>
Your Gold：<span id="credits2" class="yellow"><?php echo $credits2?></span>
<?php if($gbact == 1) { ?>
<input type="hidden" name="bet" value="<?php echo $gbudata['bid']?>">
<?php } else { ?>
Choose：
<select name="bet">
<option value="none">NO CHOICE
<?php if(is_array($alivedata)) { foreach($alivedata as $alive) { ?>
<option value="<?php echo $alive['pid']?>"><?php echo $alive['name']?>
<?php } } ?>
</select>
<?php } ?>
Bet：<input type="text" name="wager" value="1" size="4" maxlength="6">	

<input type="button" 
<?php if($gbact == 1) { ?>
value="ADD"
<?php } else { ?>
value="DEAL"
<?php } ?>
 onClick="$('gbmode').value='gamble';postCmd('alive','alive.php');">
<?php } ?>
