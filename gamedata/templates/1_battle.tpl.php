<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table border="0" width=720px height=380px cellspacing="0" cellpadding="0" valign="middle">
<TR align="center" >
<TD valign="middle" class="b5">
<TABLE border="0" width=720px height=380px align="center" cellspacing="0" cellpadding="0" class="battle">
<tr>
<td>
<table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr>
<td>
<table border="0"  cellspacing="0" cellpadding="0" valign="top" width="100%">
<tr>
<td class="b1" colspan=2 height=20px><span>Lv. <?php echo $w_lvl?></span></td>											
<td class="b1" colspan=2><span><?php echo $w_name?></span></td>											
<td class="b1" colspan=2><span><?php echo $w_sNoinfo?></span></td>											
<td class="b5" rowspan=4 width=140px colspan=1 height=80px><IMG src="img/<?php echo $w_iconImg?>" height=80px border="0" align="middle" 
<?php if($w_hp==0) { ?>
style="filter:Xray()"
<?php } ?>
 /></td>
</tr>
<tr>
<td class="b2" width=75px height=20px><span>Rage</span></td>
<td class="b3" width=90px><span><?php echo $w_ragestate?></span></td>
<td class="b2" width=75px><span>SP</span></td>
<td class="b3" width=90px><span><?php echo $w_spstate?></span></td>
<td class="b2" width=100px><span>HP</span></td>
<td class="b3" width=145px><span><?php echo $w_hpstate?></span></td>
</tr>
<tr>
<td class="b2" height=20px><span>WeaponsATK</span></td>
<td class="b3"><span><?php echo $w_wepestate?></span></td>
<td class="b2"><span>WeaponsKinds</span></td>
<td class="b3"><span>
<?php if($w_wepk != '') { ?>
<?php echo $iteminfo[$w_wepk]?>
<?php } else { ?>
？？？
<?php } ?>
</span></td>
<td class="b2"><span>Weapons</span></td>
<td class="b3"><span><?php echo $w_wep?></span></td>
</tr>
<tr>
<td class="b2" height=20px><span>Tactics</span></td>
<td class="b3"><span>
<?php if($w_tactic >= 0) { ?>
<?php echo $tacinfo[$w_tactic]?>
<?php } else { ?>
？？？
<?php } ?>
</span></td>
<td class="b2"><span>Stance</span></td>
<td class="b3"><span>
<?php if($w_pose >= 0) { ?>
<?php echo $poseinfo[$w_pose]?>
<?php } else { ?>
？？？
<?php } ?>
</span></td>
<td class="b2"><span>Wounds</span></td>
<td class="b3"><span>
<?php if($w_infdata) { ?>
<?php echo $w_infdata?>
<?php } else { ?>
NONE
<?php } ?>
</span></td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</TR>
<tr>
<TD class="b3" height="100%">
<span><B><FONT color="#ff0000" size="5" face="黑体"><?php echo $battle_title?></FONT></B></span>
</TD>
</TR>
<tr>
<td>
<table border="0" width=720px cellspacing="0" cellpadding="0" valign="middle">
<tr>
<td class="b5" rowspan=4 colspan=1 width=140px height=80px><IMG src="img/<?php echo $iconImg?>" height=80px border="0" align="middle" 
<?php if($hp==0) { ?>
style="filter:Xray()"
<?php } ?>
 /></td>
<td class="b1" colspan=2 height=20px><span><?php echo $typeinfo[$type]?>(<?php echo $sexinfo[$gd]?><?php echo $sNo?>)</span></td>
<td class="b1" colspan=2><span><?php echo $name?></span></td>
<td class="b1" colspan=2><span>Lv. <?php echo $lvl?></span></td>
</tr>
<tr>
<td class="b2" width=100px height=20px><span>HP</span></td>
<td class="b3" width=145px><span><span class="<?php echo $hpcolor?>"><?php echo $hp?> / <?php echo $mhp?></span></span></td>
<td class="b2" width=75px><span>SP</span></td>
<td class="b3" width=90px><span><?php echo $sp?> / <?php echo $msp?></span></td>
<td class="b2" width=75px><span>Rage</span></td>
<td class="b3" width=90px><span>
<?php if($rage >=30) { ?>
<span class="yellow"><?php echo $rage?></span>
<?php } else { ?>
<?php echo $rage?>
<?php } ?>
</span></td>
</tr>
<tr>
<td class="b2" height=20px><span>Weapons</span></td>
<td class="b3"><span><?php echo $wep?></span></td>
<td class="b2"><span>WeaponsKinds</span></td>
<td class="b3"><span><?php echo $iteminfo[$wepk]?></span></td>
<td class="b2"><span>WeaponsATK</span></td>
<td class="b3"><span><?php echo $wepe?></span></td>
</tr>
<tr>
<td class="b2" height=20px><span>Wounds</span></td>
<td class="b3">
<span>
<?php if($inf) { if(is_array($infinfo)) { foreach($infinfo as $key => $val) { if(strpos($inf,$key)!==false) { ?>
<?php echo $val?>
<?php } } } } else { ?>
NONE
<?php } ?>
</span>
</td>
<td class="b2"><span>Stance</span></td>
<td class="b3"><span><?php echo $poseinfo[$pose]?></span></td>
<td class="b2"><span>Tactics</span></td>
<td class="b3"><span><?php echo $tacinfo[$tactic]?></span></td>
</tr>
</table>
</td>
</tr>
</TABLE>
</TD>
</TR>
</table>
