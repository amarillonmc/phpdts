<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<h3 style="font-size:16pt" class="lime">Bets Earnings Last Round</h3>
<div class="lime"><?php echo $gblog?></div>
<?php if(!empty($bwlist)) { ?>
<table border="1">
<tr>
<th class="b1"><span>Better</span></th>
<th class="b1"><span>Gold</span></th>
<th class="b1"><span>Player betted</span></th>
<th class="b1"><span>Odds</span></th>
<th class="b1"><span>Earnings</span></th>
<th class="b1"><span>Total</span></th>
</tr>
<?php if(is_array($bwlist)) { foreach($bwlist as $bw) { ?>
<tr>
<td class="b3"><span><?php echo $bw['uname']?></span></td>
<td class="b3"><span><?php echo $bw['wager']?></span></td>
<td class="b3"><span><?php echo $bw['bname']?></span></td>
<td class="b3"><span><?php echo $bw['odds']?></span></td>
<td class="b3"><span><?php echo $bw['crup']?></span></td>
<td class="b3"><span><?php echo $bw['crrst']?></span></td>
</tr>
<?php } } ?>
</table>
<?php } ?>
