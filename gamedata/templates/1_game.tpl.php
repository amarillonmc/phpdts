<?php if(!defined('IN_GAME')) exit('Access Denied'); include template('header'); ?>
<div id="notice"></div>
<table border="0" cellspacing="10" cellpadding="0" align="center">
<tr valign=top>
<td>
<div id="main">
<?php if($main=='battle') { include template('battle'); } else { include template('profile'); } ?>
</div>
</td>
<td valign="top" rowspan="2">
<table border="0" width="300" height="560" cellspacing="0" cellpadding="0" >
<tr>
<td height="20" class="b1">
<div>
<span class="yellow" id="pls"><?php echo $plsinfo[$pls]?></span>【<span class="red">REMAIN：<span id="anum"><?php echo $alivenum?></span> PLAYERS</span>】
</div>
</td>
</tr>
<tr>
<td valign="top" class="b3" height="540" style="text-align: left;overflow:auto;overflow-x:hidden;">
<div id="log"><?php echo $log?></div>
<div>
<form method="post" id="gamecmd" name="gamecmd" style="margin: 0px" >
<div id="cmd">
<?php if($hp <= 0) { include template('death'); } elseif($state >=1 && $state <= 3) { include template('rest'); } elseif($itms0) { include template('itemfind'); } elseif($cmd) { ?>
<?php echo $cmd?>
<?php } else { include template('command'); } ?>
</div>						
</form>
</div>
</td>
</tr>
</table>
</td>
</tr>
<tr>
<td>
<?php include template('chat'); ?>
</td>
</tr>
</table>
<?php include template('footer'); ?>
