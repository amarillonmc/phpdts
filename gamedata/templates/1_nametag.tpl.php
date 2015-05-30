<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
你想修改哪个道具的名字？<br><br>
<input type="hidden" name="mode" value="item">
<input type="hidden" name="usemode" value="nametag">
<input type="hidden" name="ntitm" value="<?php echo $itmn?>">
<input type="hidden" id="command" name="command" value="menu">
<input type="radio" name="command" id="menu" value="menu" checked><a onclick=sl('menu'); href="javascript:void(0);" >返回</a><br><br>
<?php if($weps && $wepe) { ?>
<input type="radio" name="command" id="wep" value="renwep"><a onclick=sl('wep'); href="javascript:void(0);" ><?php echo $wep?>/<?php echo $wepe?>/<?php echo $weps?> </a><br>
<?php } if($arbs && $arbe) { ?>
<input type="radio" name="command" id="arb" value="renarb"><a onclick=sl('arb'); href="javascript:void(0);" ><?php echo $arb?>/<?php echo $arbe?>/<?php echo $arbs?> </a><br>
<?php } if($arhs) { ?>
<input type="radio" name="command" id="arh" value="renarh"><a onclick=sl('arh'); href="javascript:void(0);" ><?php echo $arh?>/<?php echo $arhe?>/<?php echo $arhs?> </a><br>
<?php } if($aras) { ?>
<input type="radio" name="command" id="ara" value="renara"><a onclick=sl('ara'); href="javascript:void(0);" ><?php echo $ara?>/<?php echo $arae?>/<?php echo $aras?> </a><br>
<?php } if($arfs) { ?>
<input type="radio" name="command" id="arf" value="renarf"><a onclick=sl('arf'); href="javascript:void(0);" ><?php echo $arf?>/<?php echo $arfe?>/<?php echo $arfs?> </a><br>
<?php } if($arts) { ?>
<input type="radio" name="command" id="art" value="renart"><a onclick=sl('art'); href="javascript:void(0);" ><?php echo $art?>/<?php echo $arte?>/<?php echo $arts?> </a><br>
<?php } if($itms1 && (strpos($itmk1,'Y')!==0 && strpos($itmk1,'Z')!==0)) { ?>
<input type="radio" name="command" id="itm1" value="renitm1"><a onclick=sl('itm1'); href="javascript:void(0);" ><?php echo $itm1?>/<?php echo $itme1?>/<?php echo $itms1?> </a><br>
<?php } if($itms2 && (strpos($itmk2,'Y')!==0 && strpos($itmk2,'Z')!==0)) { ?>
<input type="radio" name="command" id="itm2" value="renitm2"><a onclick=sl('itm2'); href="javascript:void(0);" ><?php echo $itm2?>/<?php echo $itme2?>/<?php echo $itms2?> </a><br>
<?php } if($itms3 && (strpos($itmk3,'Y')!==0 && strpos($itmk3,'Z')!==0)) { ?>
<input type="radio" name="command" id="itm3" value="renitm3"><a onclick=sl('itm3'); href="javascript:void(0);" ><?php echo $itm3?>/<?php echo $itme3?>/<?php echo $itms3?> </a><br>
<?php } if($itms4 && (strpos($itmk4,'Y')!==0 && strpos($itmk4,'Z')!==0)) { ?>
<input type="radio" name="command" id="itm4" value="renitm4"><a onclick=sl('itm4'); href="javascript:void(0);" ><?php echo $itm4?>/<?php echo $itme4?>/<?php echo $itms4?> </a><br>
<?php } if($itms5 && (strpos($itmk5,'Y')!==0 && strpos($itmk5,'Z')!==0)) { ?>
<input type="radio" name="command" id="itm5" value="renitm5"><a onclick=sl('itm5'); href="javascript:void(0);" ><?php echo $itm5?>/<?php echo $itme5?>/<?php echo $itms5?> </a><br>
<?php } if($itms6 && (strpos($itmk6,'Y')!==0 && strpos($itmk6,'Z')!==0)) { ?>
<input type="radio" name="command" id="itm6" value="renitm6"><a onclick=sl('itm6'); href="javascript:void(0);" ><?php echo $itm6?>/<?php echo $itme6?>/<?php echo $itms6?> </a><br>
<?php } ?>
<br><br>
<input type="text" name="rename" value=""><br>
<input type="button" class="cmdbutton" name="submit" value="改名" onclick="postCmd('gamecmd','command.php');this.disabled=true;">