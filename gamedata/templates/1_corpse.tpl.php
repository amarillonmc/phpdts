<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
What to pick up from this corpse?<br><br>
<input type="hidden" name="mode" value="corpse">
<input type="radio" name="command" id="menu" value="menu" 
<?php if(!$w_money) { ?>
checked
<?php } ?>
><a onclick=sl('menu'); href="javascript:void(0);" >Back</a><br><br>
<?php if($w_weps && $w_wepe) { ?>
<input type="radio" name="command" id="wep" value="wep"><a onclick=sl('wep'); href="javascript:void(0);" ><?php echo $w_wep?>/<?php echo $w_wepk_words?>/<?php echo $w_wepe?>/<?php echo $w_weps?>
<?php if($w_wepsk_words) { ?>
/<?php echo $w_wepsk_words?>
<?php } ?>
</a><br>
<?php } if($w_arbs && $w_arbe) { ?>
<input type="radio" name="command" id="arb" value="arb"><a onclick=sl('arb'); href="javascript:void(0);" ><?php echo $w_arb?>/<?php echo $w_arbk_words?>/<?php echo $w_arbe?>/<?php echo $w_arbs?>
<?php if($w_arbsk_words) { ?>
/<?php echo $w_arbsk_words?>
<?php } ?>
</a><br>
<?php } if($w_arhs) { ?>
<input type="radio" name="command" id="arh" value="arh"><a onclick=sl('arh'); href="javascript:void(0);" ><?php echo $w_arh?>/<?php echo $w_arhk_words?>/<?php echo $w_arhe?>/<?php echo $w_arhs?>
<?php if($w_arhsk_words) { ?>
/<?php echo $w_arhsk_words?>
<?php } ?>
</a><br>
<?php } if($w_aras) { ?>
<input type="radio" name="command" id="ara" value="ara"><a onclick=sl('ara'); href="javascript:void(0);" ><?php echo $w_ara?>/<?php echo $w_arak_words?>/<?php echo $w_arae?>/<?php echo $w_aras?>
<?php if($w_arask_words) { ?>
/<?php echo $w_arask_words?>
<?php } ?>
</a><br>
<?php } if($w_arfs) { ?>
<input type="radio" name="command" id="arf" value="arf"><a onclick=sl('arf'); href="javascript:void(0);" ><?php echo $w_arf?>/<?php echo $w_arfk_words?>/<?php echo $w_arfe?>/<?php echo $w_arfs?>
<?php if($w_arfsk_words) { ?>
/<?php echo $w_arfsk_words?>
<?php } ?>
</a><br>
<?php } if($w_arts) { ?>
<input type="radio" name="command" id="art" value="art"><a onclick=sl('art'); href="javascript:void(0);" ><?php echo $w_art?>/<?php echo $w_artk_words?>/<?php echo $w_arte?>/<?php echo $w_arts?>
<?php if($w_artsk_words) { ?>
/<?php echo $w_artsk_words?>
<?php } ?>
</a><br>
<?php } if($w_itms0) { ?>
<input type="radio" name="command" id="itm0" value="itm0"><a onclick=sl('itm0'); href="javascript:void(0);" ><?php echo $w_itm0?>/<?php echo $w_itmk0_words?>/<?php echo $w_itme0?>/<?php echo $w_itms0?>
<?php if($w_itmsk0_words) { ?>
/<?php echo $w_itmsk0_words?>
<?php } ?>
</a><br>
<?php } if($w_itms1) { ?>
<input type="radio" name="command" id="itm1" value="itm1"><a onclick=sl('itm1'); href="javascript:void(0);" ><?php echo $w_itm1?>/<?php echo $w_itmk1_words?>/<?php echo $w_itme1?>/<?php echo $w_itms1?>
<?php if($w_itmsk1_words) { ?>
/<?php echo $w_itmsk1_words?>
<?php } ?>
</a><br>
<?php } if($w_itms2) { ?>
<input type="radio" name="command" id="itm2" value="itm2"><a onclick=sl('itm2'); href="javascript:void(0);" ><?php echo $w_itm2?>/<?php echo $w_itmk2_words?>/<?php echo $w_itme2?>/<?php echo $w_itms2?>
<?php if($w_itmsk2_words) { ?>
/<?php echo $w_itmsk2_words?>
<?php } ?>
</a><br>
<?php } if($w_itms3) { ?>
<input type="radio" name="command" id="itm3" value="itm3"><a onclick=sl('itm3'); href="javascript:void(0);" ><?php echo $w_itm3?>/<?php echo $w_itmk3_words?>/<?php echo $w_itme3?>/<?php echo $w_itms3?>
<?php if($w_itmsk3_words) { ?>
/<?php echo $w_itmsk3_words?>
<?php } ?>
</a><br>
<?php } if($w_itms4) { ?>
<input type="radio" name="command" id="itm4" value="itm4"><a onclick=sl('itm4'); href="javascript:void(0);" ><?php echo $w_itm4?>/<?php echo $w_itmk4_words?>/<?php echo $w_itme4?>/<?php echo $w_itms4?>
<?php if($w_itmsk4_words) { ?>
/<?php echo $w_itmsk4_words?>
<?php } ?>
</a><br>
<?php } if($w_itms5) { ?>
<input type="radio" name="command" id="itm5" value="itm5"><a onclick=sl('itm5'); href="javascript:void(0);" ><?php echo $w_itm5?>/<?php echo $w_itmk5_words?>/<?php echo $w_itme5?>/<?php echo $w_itms5?>
<?php if($w_itmsk5_words) { ?>
/<?php echo $w_itmsk5_words?>
<?php } ?>
</a><br>
<?php } if($w_itms6) { ?>
<input type="radio" name="command" id="itm6" value="itm6"><a onclick=sl('itm6'); href="javascript:void(0);" ><?php echo $w_itm6?>/<?php echo $w_itmk6_words?>/<?php echo $w_itme6?>/<?php echo $w_itms6?>
<?php if($w_itmsk6_words) { ?>
/<?php echo $w_itmsk6_words?>
<?php } ?>
</a><br>
<?php } if($w_money) { ?>
<input type="radio" name="command" id="money" value="money" checked><a onclick=sl('money'); href="javascript:void(0);" ><?php echo $w_money?>  Coins </a><br>
<?php } ?>
<input type="button" name="submit" value="Okay" onclick="postCmd('gamecmd','command.php');this.disabled=true;">