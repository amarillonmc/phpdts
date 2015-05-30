<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
你想丢掉什么？<br><br>
<input type="hidden" name="mode" value="itemmain">
<input type="radio" name="command" id="menu" value="menu" checked><a onclick=sl('menu'); href="javascript:void(0);" >返回</a><br><br>
<?php if($weps && $wepe) { ?>
<input type="radio" name="command" id="wep" value="dropwep"><a onclick=sl('wep'); href="javascript:void(0);" ><?php echo $wep?>/<?php echo $wepe?>/<?php echo $weps?> </a><br>
<?php } if($arbs && $arbe) { ?>
<input type="radio" name="command" id="arb" value="droparb"><a onclick=sl('arb'); href="javascript:void(0);" ><?php echo $arb?>/<?php echo $arbe?>/<?php echo $arbs?> </a><br>
<?php } if($arhs) { ?>
<input type="radio" name="command" id="arh" value="droparh"><a onclick=sl('arh'); href="javascript:void(0);" ><?php echo $arh?>/<?php echo $arhe?>/<?php echo $arhs?> </a><br>
<?php } if($aras) { ?>
<input type="radio" name="command" id="ara" value="dropara"><a onclick=sl('ara'); href="javascript:void(0);" ><?php echo $ara?>/<?php echo $arae?>/<?php echo $aras?> </a><br>
<?php } if($arfs) { ?>
<input type="radio" name="command" id="arf" value="droparf"><a onclick=sl('arf'); href="javascript:void(0);" ><?php echo $arf?>/<?php echo $arfe?>/<?php echo $arfs?> </a><br>
<?php } if($arts) { ?>
<input type="radio" name="command" id="art" value="dropart"><a onclick=sl('art'); href="javascript:void(0);" ><?php echo $art?>/<?php echo $arte?>/<?php echo $arts?> </a><br>
<?php } if($itms1) { ?>
<input type="radio" name="command" id="itm1" value="dropitm1"><a onclick=sl('itm1'); href="javascript:void(0);" ><?php echo $itm1?>/<?php echo $itme1?>/<?php echo $itms1?> </a><br>
<?php } if($itms2) { ?>
<input type="radio" name="command" id="itm2" value="dropitm2"><a onclick=sl('itm2'); href="javascript:void(0);" ><?php echo $itm2?>/<?php echo $itme2?>/<?php echo $itms2?> </a><br>
<?php } if($itms3) { ?>
<input type="radio" name="command" id="itm3" value="dropitm3"><a onclick=sl('itm3'); href="javascript:void(0);" ><?php echo $itm3?>/<?php echo $itme3?>/<?php echo $itms3?> </a><br>
<?php } if($itms4) { ?>
<input type="radio" name="command" id="itm4" value="dropitm4"><a onclick=sl('itm4'); href="javascript:void(0);" ><?php echo $itm4?>/<?php echo $itme4?>/<?php echo $itms4?> </a><br>
<?php } if($itms5) { ?>
<input type="radio" name="command" id="itm5" value="dropitm5"><a onclick=sl('itm5'); href="javascript:void(0);" ><?php echo $itm5?>/<?php echo $itme5?>/<?php echo $itms5?> </a><br>
<?php } if($itms6) { ?>
<input type="radio" name="command" id="itm6" value="dropitm6"><a onclick=sl('itm6'); href="javascript:void(0);" ><?php echo $itm6?>/<?php echo $itme6?>/<?php echo $itms6?> </a><br>
<?php } ?>
<br><br>
<input type="button" class="cmdbutton" name="submit" value="确定并丢弃" onclick="postCmd('gamecmd','command.php');this.disabled=true;">