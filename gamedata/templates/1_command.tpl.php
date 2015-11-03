<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<input type="hidden" id="mode" name="mode" value="command">
<input type="hidden" id="command" name="command" value="menu">
<input type="hidden" id="subcmd" name="subcmd" value="">
What do you want to do?<br /><br />

<input type="button" class="cmdbutton" id="search" name="search" value="SEARCH" onclick="$('command').value='search';postCmd('gamecmd','command.php');this.disabled=true;" 
<?php if(array_search($pls,$arealist) <= $areanum && !$hack) { ?>
disabled
<?php } ?>
>
<select id="moveto" name="moveto" onchange="$('command').value='move';postCmd('gamecmd','command.php');this.disabled=true;">
<?php include template('move'); ?>
</select>
<br />
<br />
<?php if($itms1) { ?>
<input type="button" class="cmdbutton" 
<?php if(strpos($itmk1,'W')===0 || strpos($itmk1,'D')===0 || strpos($itmk1,'A')===0) { ?>
value="Equip"
<?php } else { ?>
value="Use"
<?php } ?>
 onclick="$('mode').value='command';$('command').value='itm1';postCmd('gamecmd','command.php');this.disabled=true;"><span class="yellow"><?php echo $itm1?></span>/<?php echo $itme1?>/<?php echo $itms1?><br>
<?php } if($itms2) { ?>
<input type="button" class="cmdbutton" 
<?php if(strpos($itmk2,'W')===0 || strpos($itmk2,'D')===0 || strpos($itmk2,'A')===0) { ?>
value="Equip"
<?php } else { ?>
value="Use"
<?php } ?>
 onclick="$('mode').value='command';$('command').value='itm2';postCmd('gamecmd','command.php');this.disabled=true;"><span class="yellow"><?php echo $itm2?></span>/<?php echo $itme2?>/<?php echo $itms2?><br>
<?php } if($itms3) { ?>
<input type="button" class="cmdbutton" 
<?php if(strpos($itmk3,'W')===0 || strpos($itmk3,'D')===0 || strpos($itmk3,'A')===0) { ?>
value="Equip"
<?php } else { ?>
value="Use"
<?php } ?>
 onclick="$('mode').value='command';$('command').value='itm3';postCmd('gamecmd','command.php');this.disabled=true;"><span class="yellow"><?php echo $itm3?></span>/<?php echo $itme3?>/<?php echo $itms3?><br>
<?php } if($itms4) { ?>
<input type="button" class="cmdbutton" 
<?php if(strpos($itmk4,'W')===0 || strpos($itmk4,'D')===0 || strpos($itmk4,'A')===0) { ?>
value="Equip"
<?php } else { ?>
value="Use"
<?php } ?>
 onclick="$('mode').value='command';$('command').value='itm4';postCmd('gamecmd','command.php');this.disabled=true;"><span class="yellow"><?php echo $itm4?></span>/<?php echo $itme4?>/<?php echo $itms4?><br>
<?php } if($itms5) { ?>
<input type="button" class="cmdbutton" 
<?php if(strpos($itmk5,'W')===0 || strpos($itmk5,'D')===0 || strpos($itmk5,'A')===0) { ?>
value="Equip"
<?php } else { ?>
value="Use"
<?php } ?>
 onclick="$('mode').value='command';$('command').value='itm5';postCmd('gamecmd','command.php');this.disabled=true;"><span class="yellow"><?php echo $itm5?></span>/<?php echo $itme5?>/<?php echo $itms5?><br>
<?php } if($itms6) { ?>
<input type="button" class="cmdbutton" 
<?php if(strpos($itmk6,'W')===0 || strpos($itmk6,'D')===0 || strpos($itmk6,'A')===0) { ?>
value="Equip"
<?php } else { ?>
value="Use"
<?php } ?>
 onclick="$('mode').value='command';$('command').value='itm6';postCmd('gamecmd','command.php');this.disabled=true;"><span class="yellow"><?php echo $itm6?></span>/<?php echo $itme6?>/<?php echo $itms6?><br>
<?php } ?>
<br>
<input type="button" class="cmdbutton" id="itemmix" name="itemmix" value="Item Mix" onclick="$('command').value='itemmain';$('subcmd').name='itemcmd';$('subcmd').value='itemmix';postCmd('gamecmd','command.php');this.disabled=true;">
<input type="button" class="cmdbutton" id="itemmerge" name="itemmerge" value="Arrange Bag" onclick="$('command').value='itemmain';$('subcmd').name='itemcmd';$('subcmd').value='itemmerge';postCmd('gamecmd','command.php');this.disabled=true;">
<?php if(strpos($artk,'ss')!==false) { ?>
<input type="button" class="cmdbutton" id="sp_weapon" name="sp_weapon" value="Sing" onclick="$('command').value='song';$('subcmd').name='song';postCmd('gamecmd','command.php');this.disabled=true;">
<?php } ?>
<br>
<input type="button" class="cmdbutton" id="itemdrop" name="itemdrop" value="Move Item" onclick="$('command').value='itemmain';$('subcmd').name='itemcmd';$('subcmd').value='itemmove';postCmd('gamecmd','command.php');this.disabled=true;">
<input type="button" class="cmdbutton" id="itemdrop" name="itemdrop" value="Discard Item" onclick="$('command').value='itemmain';$('subcmd').name='itemcmd';$('subcmd').value='itemdrop';postCmd('gamecmd','command.php');this.disabled=true;">
<input type="button" class="cmdbutton" id="sp_weapon" name="sp_weapon" value="Weapon Mode" onclick="$('command').value='special';$('subcmd').name='sp_cmd';$('subcmd').value='sp_weapon';postCmd('gamecmd','command.php');this.disabled=true;">

<br />
<input type="button" class="cmdbutton" id="rest1" name="rest1" value="Sleep" onclick="$('command').value='rest1';postCmd('gamecmd','command.php');this.disabled=true;">
<input type="button" class="cmdbutton" id="rest2" name="rest2" value="Heal" onclick="$('command').value='rest2';postCmd('gamecmd','command.php');this.disabled=true;">
<?php if(in_array($pls,$hospitals)) { ?>
<input type="button" class="cmdbutton" id="rest3" name="rest3" value="Rest" onclick="$('command').value='rest3';postCmd('gamecmd','command.php');this.disabled=true;">
<?php } ?>
<br />
<?php if($gamestate < 40) { if(!$teamID) { ?>
<input type="button" class="cmdbutton" id="teammake" name="teammake" value="Form Team" onclick="$('command').value='team';$('subcmd').name='teamcmd';$('subcmd').value='teammake';postCmd('gamecmd','command.php');this.disabled=true;">
<input type="button" class="cmdbutton" id="teammake" name="teamjoin" value="Join Team" onclick="$('command').value='team';$('subcmd').name='teamcmd';$('subcmd').value='teamjoin';postCmd('gamecmd','command.php');this.disabled=true;">
<?php } else { ?>
<input type="button" class="cmdbutton" id="teammake" name="teamquit" value="Disband Team" onclick="$('command').value='team';$('subcmd').name='teamcmd';$('subcmd').value='teamquit';postCmd('gamecmd','command.php');this.disabled=true;">
<?php } } ?>
<br />
<?php if(in_array($pls,$shops)) { ?>
<input type="button" class="cmdbutton" id="sp_shop" name="sp_shop" value="Shop" onclick="$('command').value='special';$('subcmd').name='sp_cmd';$('subcmd').value='sp_shop';postCmd('gamecmd','command.php');this.disabled=true;">
<?php } ?>
<input type="button" class="cmdbutton" id="sp_skpts" name="sp_skpts" value="Upgrade Skills" onclick="$('command').value='special';$('subcmd').name='sp_cmd';$('subcmd').value='sp_skpts';postCmd('gamecmd','command.php');this.disabled=true;">
<br />
<?php if($club == 7) { ?>
<input type="button" class="cmdbutton" id="sp_adtsk" name="sp_adtsk" value="Modify Weapon:SHOCK" onclick="$('command').value='special';$('subcmd').name='sp_cmd';$('subcmd').value='sp_adtsk';postCmd('gamecmd','command.php');this.disabled=true;">
<input type="button" class="cmdbutton" id="sp_trapadtsk" name="sp_trapadtsk" value="Modify Trap:SHOCK" onclick="$('command').value='special';$('subcmd').name='sp_cmd';$('subcmd').value='sp_trapadtsk';postCmd('gamecmd','command.php');this.disabled=true;">
<?php } elseif($club == 8) { ?>
<input type="button" class="cmdbutton" id="sp_adtsk" name="sp_adtsk" value="Modify Weapon:POSION" onclick="$('command').value='special';$('subcmd').name='sp_cmd';$('subcmd').value='sp_adtsk';postCmd('gamecmd','command.php');this.disabled=true;">
<input type="button" class="cmdbutton" id="sp_trapadtsk" name="sp_trapadtsk" value="Modify Trap:POSION" onclick="$('command').value='special';$('subcmd').name='sp_cmd';$('subcmd').value='sp_trapadtsk';postCmd('gamecmd','command.php');this.disabled=true;">
<input type="button" class="cmdbutton" id="sp_poison" name="sp_poison" value="Check for POSION" onclick="$('command').value='special';$('subcmd').name='sp_cmd';$('subcmd').value='sp_poison';postCmd('gamecmd','command.php');this.disabled=true;">
<?php } elseif($club == 99) { ?>
<input type="button" class="cmdbutton" id="sp_pbomb" name="sp_pbomb" value="Autobomb" onclick="$('command').value='special';$('subcmd').name='sp_cmd';$('subcmd').value='sp_pbomb';postCmd('gamecmd','command.php');this.disabled=true;">
<?php } ?>
