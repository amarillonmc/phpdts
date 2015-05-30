<?php if(!defined('IN_GAME')) exit('Access Denied'); if(is_array($p)) { foreach($p as $key => $valuekey) { if(isset($npcdescription[$kind]['sub'][$key]['description'])) { ?>
<table border="1" cellspacing="0" cellpadding="0" valign="middle">
<tr>
<td>
<table border="1" cellspacing="0" cellpadding="0" valign="middle">
<tr>
<td>
<IMG width=140px src="img/n_<?php echo $npcinfo[$kind]['sub'][$key]['icon']?>.gif" border="0" valign="middle"/>
</td>
<td>
<table border="1" height=100% width=100% cellspacing="0" cellpadding="0">
<tr>
<td width=100px align="center" class="b1">
NPC类别
</td>
<td width=100px align="center" class="b3">
<?php echo $typeinfo[$kind]?>
</td>
<td width=100px align="center" class="b1">
数目
</td>
<td width=100px align="center" class="b3">
<?php echo $npcdescription[$kind]['sub'][$key]['count']?>
</td>
<td width=100px align="center" class="b1">
所处地点
</td>
<td width=100px align="center" class="b3">
<?php if(isset($npcinfo[$kind]['sub'][$key]['pls'])) { if($npcinfo[$kind]['sub'][$key]['pls']==99) { ?>
随机
<?php } else { ?>
<span class="yellow">
<?php echo $plsinfo[$npcinfo[$kind]['sub'][$key]['pls']]?>
</span>
<?php } } else { if($npcinfo[$kind]['pls']==99) { ?>
随机
<?php } else { ?>
<span class="yellow">
<?php echo $plsinfo[$npcinfo[$kind]['pls']]?>
</span>
<?php } } ?>
</td>
</tr>
<tr>
<td width=100px align="center" class="b1">
NPC等级
</td>
<td width=100px align="center" class="b3">
<span>
Lv. 
<?php if(isset($npcinfo[$kind]['sub'][$key]['lvl'])) { ?>
<?php echo $npcinfo[$kind]['sub'][$key]['lvl']?>
<?php } else { ?>
<?php echo $npcinfo[$kind]['lvl']?>
<?php } ?>
</span>
</td>
<td width=100px align="center" class="b1">
NPC名称
</td>	
<td width=100px align="center" class="b3">
<span class="lime">
<?php echo $npcinfo[$kind]['sub'][$key]['name']?>
</span>
</td>
<td width=100px align="center" class="b1">
性别
</td>
<td width=100px align="center" class="b3">
<?php if(isset($npcinfo[$kind]['sub'][$key]['gd'])) { if($npcinfo[$kind]['sub'][$key]['gd']=='m') { ?>
男
<?php } else { if($npcinfo[$kind]['sub'][$key]['gd']=='f') { ?>
女
<?php } else { ?>
随机
<?php } } } else { if($npcinfo[$kind]['gd']=='m') { ?>
男
<?php } else { if($npcinfo[$kind]['gd']=='f') { ?>
女
<?php } else { ?>
随机
<?php } } } ?>
</td>
</tr>					
<tr>
<td width=100px align="center" class="b1">
内定称号
</td>					
<td width=100px align="center" class="b3">
<?php if(isset($npcinfo[$kind]['sub'][$key]['club'])) { if($npcinfo[$kind]['sub'][$key]['club']==99) { ?>
第一形态
<?php } else { ?>
<?php echo $clubinfo[$npcinfo[$kind]['sub'][$key]['club']]?>
<?php } } else { if(isset($npcinfo[$kind]['club'])) { if($npcinfo[$kind]['club']==99) { ?>
第一形态
<?php } else { ?>
<?php echo $clubinfo[$npcinfo[$kind]['club']]?>
<?php } } else { ?>
无
<?php } } ?>
</td>
<td width=100px align="center" class="b1">
基础姿态
</td>
<td width=100px align="center" class="b3">
<?php if(isset($npcinfo[$kind]['sub'][$key]['pose'])) { ?>
<?php echo $poseinfo[$npcinfo[$kind]['sub'][$key]['pose']]?>
<?php } else { ?>
<?php echo $poseinfo[$npcinfo[$kind]['pose']]?>
<?php } ?>
</td>
<td width=100px align="center" class="b1">
应战策略
</td>
<td width=100px align="center" class="b3">
<?php if(isset($npcinfo[$kind]['sub'][$key]['tactic'])) { ?>
<?php echo $tacinfo[$npcinfo[$kind]['sub'][$key]['tactic']]?>
<?php } else { ?>
<?php echo $tacinfo[$npcinfo[$kind]['tactic']]?>
<?php } ?>
</td>
</tr>
<tr>
<td width=100px align="center" class="b1">
生命上限
</td>
<td width=100px align="center" class="b3">
<?php if(isset($npcinfo[$kind]['sub'][$key]['mhp'])) { ?>
<?php echo $npcinfo[$kind]['sub'][$key]['mhp']?>
<?php } else { ?>
<?php echo $npcinfo[$kind]['mhp']?>
<?php } ?>
</td>
<td width=100px align="center" class="b1">
熟练度
</td>
<td width=100px align="center" class="b3">
<?php if(isset($npcinfo[$kind]['sub'][$key]['skill'])) { ?>
<?php echo $npcinfo[$kind]['sub'][$key]['skill']?>
<?php } else { ?>
<?php echo $npcinfo[$kind]['skill']?>
<?php } ?>
</td>
<td width=100px align="center" class="b1">
怒气值
</td>
<td width=100px align="center" class="b3">
<?php if(isset($npcinfo[$kind]['sub'][$key]['rage'])) { ?>
<?php echo $npcinfo[$kind]['sub'][$key]['rage']?>
<?php } else { ?>
<?php echo $npcinfo[$kind]['rage']?>
<?php } ?>
</td>
</tr>
<tr>
<td width=100px align="center" class="b1">
基础攻击
</td>
<td width=100px align="center" class="b3">
<?php if(isset($npcinfo[$kind]['sub'][$key]['att'])) { ?>
<?php echo $npcinfo[$kind]['sub'][$key]['att']?>
<?php } else { ?>
<?php echo $npcinfo[$kind]['att']?>
<?php } ?>
</td>
<td width=100px align="center" class="b1">
基础防御
</td>
<td width=100px align="center" class="b3">
<?php if(isset($npcinfo[$kind]['sub'][$key]['def'])) { ?>
<?php echo $npcinfo[$kind]['sub'][$key]['def']?>
<?php } else { ?>
<?php echo $npcinfo[$kind]['def']?>
<?php } ?>
</td>
<td width=100px align="center" class="b1">
掉落金钱
</td>
<td width=100px align="center" class="b3">
<?php if(isset($npcinfo[$kind]['sub'][$key]['money'])) { ?>
<?php echo $npcinfo[$kind]['sub'][$key]['money']?>
<?php } else { ?>
<?php echo $npcinfo[$kind]['money']?>
<?php } ?>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
<tr>
<td>
<table border="1" cellspacing="0" cellpadding="0" valign="middle">
<tr>
<td width=140px>
<?php echo $npcdescription[$kind]['sub'][$key]['description']?>
</td>
<td>
<table border="1" cellspacing="0" cellpadding="0" valign="middle">
<tr>
<td width=100px align="center" class="b1">
武器名称
</td>
<td width=505px align="center" class="b3">
<?php echo $npcinfo[$kind]['sub'][$key]['wep']?>/<?php echo $iteminfo[$npcinfo[$kind]['sub'][$key]['wepk']]?>/<?php echo $npcinfo[$kind]['sub'][$key]['wepe']?>/<?php echo $npcinfo[$kind]['sub'][$key]['weps']?>
<?php if(isset($npcinfo[$kind]['sub'][$key]['wepsk'])) { if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['sub'][$key]['wepsk'],$ky)!==false) { if(strpos($npcinfo[$kind]['sub'][$key]['wepsk'],$ky)===0) { ?>
/<?php echo $vl?>
<?php } } } } if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['sub'][$key]['wepsk'],$ky)!==false) { if(strpos($npcinfo[$kind]['sub'][$key]['wepsk'],$ky)!==0) { ?>
+<?php echo $vl?>
<?php } } } } } ?>
</td>
</tr>
<tr>
<td width=100px align="center" class="b1">
身体装备
</td>
<td width=505px align="center" class="b3">
<?php if(isset($npcinfo[$kind]['sub'][$key]['arb'])) { ?>
<?php echo $npcinfo[$kind]['sub'][$key]['arb']?>/<?php echo $npcinfo[$kind]['sub'][$key]['arbe']?>/<?php echo $npcinfo[$kind]['sub'][$key]['arbs']?>
<?php if(isset($npcinfo[$kind]['sub'][$key]['arbsk'])) { if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['sub'][$key]['arbsk'],$ky)!==false) { if(strpos($npcinfo[$kind]['sub'][$key]['arbsk'],$ky)===0) { ?>
/<?php echo $vl?>
<?php } } } } if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['sub'][$key]['arbsk'],$ky)!==false) { if(strpos($npcinfo[$kind]['sub'][$key]['arbsk'],$ky)!==0) { ?>
+<?php echo $vl?>
<?php } } } } } } else { ?>
<?php echo $npcinfo[$kind]['arb']?>/<?php echo $npcinfo[$kind]['arbe']?>/<?php echo $npcinfo[$kind]['arbs']?>
<?php if(isset($npcinfo[$kind]['arbsk'])) { if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['arbsk'],$ky)!==false) { if(strpos($npcinfo[$kind]['arbsk'],$ky)===0) { ?>
/<?php echo $vl?>
<?php } } } } if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['arbsk'],$ky)!==false) { if(strpos($npcinfo[$kind]['arbsk'],$ky)!==0) { ?>
+<?php echo $vl?>
<?php } } } } } } ?>
</td>
</tr>
<tr>
<td width=100px align="center" class="b1">
头部装备
</td>
<td width=505px align="center" class="b3">
<?php if(isset($npcinfo[$kind]['sub'][$key]['arh'])) { ?>
<?php echo $npcinfo[$kind]['sub'][$key]['arh']?>/<?php echo $npcinfo[$kind]['sub'][$key]['arhe']?>/<?php echo $npcinfo[$kind]['sub'][$key]['arhs']?>
<?php if(isset($npcinfo[$kind]['sub'][$key]['arhsk'])) { if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['sub'][$key]['arhsk'],$ky)!==false) { if(strpos($npcinfo[$kind]['sub'][$key]['arhsk'],$ky)===0) { ?>
/<?php echo $vl?>
<?php } } } } if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['sub'][$key]['arhsk'],$ky)!==false) { if(strpos($npcinfo[$kind]['sub'][$key]['arhsk'],$ky)!==0) { ?>
+<?php echo $vl?>
<?php } } } } } } else { ?>
<?php echo $npcinfo[$kind]['arh']?>/<?php echo $npcinfo[$kind]['arhe']?>/<?php echo $npcinfo[$kind]['arhs']?>
<?php if(isset($npcinfo[$kind]['arhsk'])) { if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['arhsk'],$ky)!==false) { if(strpos($npcinfo[$kind]['arhsk'],$ky)===0) { ?>
/<?php echo $vl?>
<?php } } } } if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['arhsk'],$ky)!==false) { if(strpos($npcinfo[$kind]['arhsk'],$ky)!==0) { ?>
+<?php echo $vl?>
<?php } } } } } } ?>
</td>
</tr>
<tr>
<td width=100px align="center" class="b1">
手臂装备
</td>
<td width=505px align="center" class="b3">
<?php if(isset($npcinfo[$kind]['sub'][$key]['ara'])) { ?>
<?php echo $npcinfo[$kind]['sub'][$key]['ara']?>/<?php echo $npcinfo[$kind]['sub'][$key]['arae']?>/<?php echo $npcinfo[$kind]['sub'][$key]['aras']?>
<?php if(isset($npcinfo[$kind]['sub'][$key]['arask'])) { if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['sub'][$key]['arask'],$ky)!==false) { if(strpos($npcinfo[$kind]['sub'][$key]['arask'],$ky)===0) { ?>
/<?php echo $vl?>
<?php } } } } if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['sub'][$key]['arask'],$ky)!==false) { if(strpos($npcinfo[$kind]['sub'][$key]['arask'],$ky)!==0) { ?>
+<?php echo $vl?>
<?php } } } } } } else { ?>
<?php echo $npcinfo[$kind]['ara']?>/<?php echo $npcinfo[$kind]['arae']?>/<?php echo $npcinfo[$kind]['aras']?>
<?php if(isset($npcinfo[$kind]['arask'])) { if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['arask'],$ky)!==false) { if(strpos($npcinfo[$kind]['arask'],$ky)===0) { ?>
/<?php echo $vl?>
<?php } } } } if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['arask'],$ky)!==false) { if(strpos($npcinfo[$kind]['arask'],$ky)!==0) { ?>
+<?php echo $vl?>
<?php } } } } } } ?>
</td>
</tr>
<tr>
<td width=100px align="center" class="b1">
腿部装备
</td>
<td width=505px align="center" class="b3">
<?php if(isset($npcinfo[$kind]['sub'][$key]['arf'])) { ?>
<?php echo $npcinfo[$kind]['sub'][$key]['arf']?>/<?php echo $npcinfo[$kind]['sub'][$key]['arfe']?>/<?php echo $npcinfo[$kind]['sub'][$key]['arfs']?>
<?php if(isset($npcinfo[$kind]['sub'][$key]['arfsk'])) { if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['sub'][$key]['arfsk'],$ky)!==false) { if(strpos($npcinfo[$kind]['sub'][$key]['arfsk'],$ky)===0) { ?>
/<?php echo $vl?>
<?php } } } } if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['sub'][$key]['arfsk'],$ky)!==false) { if(strpos($npcinfo[$kind]['sub'][$key]['arfsk'],$ky)!==0) { ?>
+<?php echo $vl?>
<?php } } } } } } else { ?>
<?php echo $npcinfo[$kind]['arf']?>/<?php echo $npcinfo[$kind]['arfe']?>/<?php echo $npcinfo[$kind]['arfs']?>
<?php if(isset($npcinfo[$kind]['arfsk'])) { if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['arfsk'],$ky)!==false) { if(strpos($npcinfo[$kind]['arfsk'],$ky)===0) { ?>
/<?php echo $vl?>
<?php } } } } if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['arfsk'],$ky)!==false) { if(strpos($npcinfo[$kind]['arfsk'],$ky)!==0) { ?>
+<?php echo $vl?>
<?php } } } } } } ?>
</td>
</tr>
<tr>
<td width=100px align="center" class="b1">
饰品 
</td>
<td width=505px align="center" class="b3">
<?php if(isset($npcinfo[$kind]['sub'][$key]['art'])) { ?>
<?php echo $npcinfo[$kind]['sub'][$key]['art']?>/<?php echo $iteminfo[$npcinfo[$kind]['sub'][$key]['artk']]?>
<?php if(isset($npcinfo[$kind]['sub'][$key]['artsk'])) { if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['sub'][$key]['artsk'],$ky)!==false) { if(strpos($npcinfo[$kind]['sub'][$key]['artsk'],$ky)===0) { ?>
/<?php echo $vl?>
<?php } } } } if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['sub'][$key]['artsk'],$ky)!==false) { if(strpos($npcinfo[$kind]['sub'][$key]['artsk'],$ky)!==0) { ?>
+<?php echo $vl?>
<?php } } } } } } else { ?>
<?php echo $npcinfo[$kind]['art']?>/<?php echo $iteminfo[$npcinfo[$kind]['artk']]?>
<?php if(isset($npcinfo[$kind]['artsk'])) { if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['artsk'],$ky)!==false) { if(strpos($npcinfo[$kind]['artsk'],$ky)===0) { ?>
/<?php echo $vl?>
<?php } } } } if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['artsk'],$ky)!==false) { if(strpos($npcinfo[$kind]['artsk'],$ky)!==0) { ?>
+<?php echo $vl?>
<?php } } } } } } ?>
</td>
</tr>
<?php if(is_array($itemlst)) { foreach($itemlst as $id => $vid) { if(isset($npcinfo[$kind]['sub'][$key]['itm'.$id])) { ?>
<tr>
<td width=100px align="center" class="b1">
掉落物品
</td>
<td width=505px align="center" class="b3">
<?php echo $npcinfo[$kind]['sub'][$key]['itm'.$id]?>/<?php echo $iteminfo[$npcinfo[$kind]['sub'][$key]['itmk'.$id]]?>/<?php echo $npcinfo[$kind]['sub'][$key]['itme'.$id]?>/<?php echo $npcinfo[$kind]['sub'][$key]['itms'.$id]?>
<?php if(isset($npcinfo[$kind]['sub'][$key]['itmsk'.$id])) { if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['sub'][$key]['itmsk'.$id],$ky)!==false) { if(strpos($npcinfo[$kind]['sub'][$key]['itmsk'.$id],$ky)===0) { ?>
/<?php echo $vl?>
<?php } } } } if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['sub'][$key]['itmsk'.$id],$ky)!==false) { if(strpos($npcinfo[$kind]['sub'][$key]['itmsk'.$id],$ky)!==0) { ?>
+<?php echo $vl?>
<?php } } } } } ?>
</td>
</tr>
<?php } elseif(isset($npcinfo[$kind]['itm'.$id])) { ?>
<tr>
<td width=100px align="center" class="b1">
掉落物品
</td>
<td width=505px align="center" class="b3">
<?php echo $npcinfo[$kind]['itm'.$id]?>/<?php echo $iteminfo[$npcinfo[$kind]['itmk'.$id]]?>/<?php echo $npcinfo[$kind]['itme'.$id]?>/<?php echo $npcinfo[$kind]['itms'.$id]?>
<?php if(isset($npcinfo[$kind]['itmsk'.$id])) { if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['itmsk'.$id],$ky)!==false) { if(strpos($npcinfo[$kind]['itmsk'.$id],$ky)===0) { ?>
/<?php echo $vl?>
<?php } } } } if(is_array($itemspkinfo)) { foreach($itemspkinfo as $ky => $vl) { if(strpos($npcinfo[$kind]['itmsk'.$id],$ky)!==false) { if(strpos($npcinfo[$kind]['itmsk'.$id],$ky)!==0) { ?>
+<?php echo $vl?>
<?php } } } } } ?>
</td>
</tr>
<?php } } } ?>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
<br>
<?php } } } ?>
