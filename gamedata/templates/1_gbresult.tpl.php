<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<h3 style="font-size:16pt" class="lime">上局切糕情况</h3>
<div class="lime"><?php echo $gblog?></div>
<?php if(!empty($bwlist)) { ?>
<table border="1">
<tr>
<th class="b1"><span>下注人</span></th>
<th class="b1"><span>下注金额</span></th>
<th class="b1"><span>下注对象</span></th>
<th class="b1"><span>系数</span></th>
<th class="b1"><span>收益</span></th>
<th class="b1"><span>总计应付</span></th>
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
