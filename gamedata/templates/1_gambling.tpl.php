<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
﻿
<?php if($gbnum) { ?>
当前奖池：<?php echo $gbpool?>。
<?php } else { ?>
当前还未开设赌局。
<?php } ?>
当前分成系数：<?php echo $nowodds?>
<span class="yellow" id="gbinfo">
<?php echo $gbinfo?>
</span>
<?php if($cuser && isset($credits2)) { ?>
你的切糕：<span id="credits2" class="yellow"><?php echo $credits2?></span>
<?php if($gbact == 1) { ?>
<input type="hidden" name="bet" value="<?php echo $gbudata['bid']?>">
<?php } else { ?>
选择：
<select name="bet">
<option value="none">不选择
<?php if(is_array($alivedata)) { foreach($alivedata as $alive) { ?>
<option value="<?php echo $alive['pid']?>"><?php echo $alive['name']?>
<?php } } ?>
</select>
<?php } ?>
赌注：<input type="text" name="wager" value="1" size="4" maxlength="6">	

<input type="button" 
<?php if($gbact == 1) { ?>
value="追加"
<?php } else { ?>
value="下注"
<?php } ?>
 onClick="$('gbmode').value='gamble';postCmd('alive','alive.php');">
<?php } ?>
