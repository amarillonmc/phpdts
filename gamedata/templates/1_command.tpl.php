<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<input type="hidden" id="mode" name="mode" value="command">
<input type="hidden" id="command" name="command" value="menu">
<input type="hidden" id="subcmd" name="subcmd" value="">
现在想要做什么？<br /><br />

<input type="button" class="cmdbutton" id="search" name="search" value="搜寻" onclick="$('command').value='search';postCmd('gamecmd','command.php');this.disabled=true;" 
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
value="装备"
<?php } else { ?>
value="使用"
<?php } ?>
 onclick="$('mode').value='command';$('command').value='itm1';postCmd('gamecmd','command.php');this.disabled=true;"><span class="yellow"><?php echo $itm1?></span>/<?php echo $itme1?>/<?php echo $itms1?><br>
<?php } if($itms2) { ?>
<input type="button" class="cmdbutton" 
<?php if(strpos($itmk2,'W')===0 || strpos($itmk2,'D')===0 || strpos($itmk2,'A')===0) { ?>
value="装备"
<?php } else { ?>
value="使用"
<?php } ?>
 onclick="$('mode').value='command';$('command').value='itm2';postCmd('gamecmd','command.php');this.disabled=true;"><span class="yellow"><?php echo $itm2?></span>/<?php echo $itme2?>/<?php echo $itms2?><br>
<?php } if($itms3) { ?>
<input type="button" class="cmdbutton" 
<?php if(strpos($itmk3,'W')===0 || strpos($itmk3,'D')===0 || strpos($itmk3,'A')===0) { ?>
value="装备"
<?php } else { ?>
value="使用"
<?php } ?>
 onclick="$('mode').value='command';$('command').value='itm3';postCmd('gamecmd','command.php');this.disabled=true;"><span class="yellow"><?php echo $itm3?></span>/<?php echo $itme3?>/<?php echo $itms3?><br>
<?php } if($itms4) { ?>
<input type="button" class="cmdbutton" 
<?php if(strpos($itmk4,'W')===0 || strpos($itmk4,'D')===0 || strpos($itmk4,'A')===0) { ?>
value="装备"
<?php } else { ?>
value="使用"
<?php } ?>
 onclick="$('mode').value='command';$('command').value='itm4';postCmd('gamecmd','command.php');this.disabled=true;"><span class="yellow"><?php echo $itm4?></span>/<?php echo $itme4?>/<?php echo $itms4?><br>
<?php } if($itms5) { ?>
<input type="button" class="cmdbutton" 
<?php if(strpos($itmk5,'W')===0 || strpos($itmk5,'D')===0 || strpos($itmk5,'A')===0) { ?>
value="装备"
<?php } else { ?>
value="使用"
<?php } ?>
 onclick="$('mode').value='command';$('command').value='itm5';postCmd('gamecmd','command.php');this.disabled=true;"><span class="yellow"><?php echo $itm5?></span>/<?php echo $itme5?>/<?php echo $itms5?><br>
<?php } if($itms6) { ?>
<input type="button" class="cmdbutton" 
<?php if(strpos($itmk6,'W')===0 || strpos($itmk6,'D')===0 || strpos($itmk6,'A')===0) { ?>
value="装备"
<?php } else { ?>
value="使用"
<?php } ?>
 onclick="$('mode').value='command';$('command').value='itm6';postCmd('gamecmd','command.php');this.disabled=true;"><span class="yellow"><?php echo $itm6?></span>/<?php echo $itme6?>/<?php echo $itms6?><br>
<?php } ?>
<br>
<input type="button" class="cmdbutton" id="itemmix" name="itemmix" value="道具合成" onclick="$('command').value='itemmain';$('subcmd').name='itemcmd';$('subcmd').value='itemmix';postCmd('gamecmd','command.php');this.disabled=true;">
<input type="button" class="cmdbutton" id="itemmerge" name="itemmerge" value="整理包裹" onclick="$('command').value='itemmain';$('subcmd').name='itemcmd';$('subcmd').value='itemmerge';postCmd('gamecmd','command.php');this.disabled=true;">
<?php if(strpos($artk,'ss')!==false) { ?>
<input type="button" class="cmdbutton" id="sp_weapon" name="sp_weapon" value="歌唱" onclick="$('command').value='song';$('subcmd').name='song';postCmd('gamecmd','command.php');this.disabled=true;">
<?php } ?>
<br>
<input type="button" class="cmdbutton" id="itemdrop" name="itemdrop" value="道具移动" onclick="$('command').value='itemmain';$('subcmd').name='itemcmd';$('subcmd').value='itemmove';postCmd('gamecmd','command.php');this.disabled=true;">
<input type="button" class="cmdbutton" id="itemdrop" name="itemdrop" value="道具丢弃" onclick="$('command').value='itemmain';$('subcmd').name='itemcmd';$('subcmd').value='itemdrop';postCmd('gamecmd','command.php');this.disabled=true;">
<input type="button" class="cmdbutton" id="sp_weapon" name="sp_weapon" value="武器模式" onclick="$('command').value='special';$('subcmd').name='sp_cmd';$('subcmd').value='sp_weapon';postCmd('gamecmd','command.php');this.disabled=true;">

<br />
<input type="button" class="cmdbutton" id="rest1" name="rest1" value="睡眠" onclick="$('command').value='rest1';postCmd('gamecmd','command.php');this.disabled=true;">
<input type="button" class="cmdbutton" id="rest2" name="rest2" value="治疗" onclick="$('command').value='rest2';postCmd('gamecmd','command.php');this.disabled=true;">
<?php if(in_array($pls,$hospitals)) { ?>
<input type="button" class="cmdbutton" id="rest3" name="rest3" value="静养" onclick="$('command').value='rest3';postCmd('gamecmd','command.php');this.disabled=true;">
<?php } ?>
<br />
<?php if($gamestate < 40) { if(!$teamID) { ?>
<input type="button" class="cmdbutton" id="teammake" name="teammake" value="组建队伍" onclick="$('command').value='team';$('subcmd').name='teamcmd';$('subcmd').value='teammake';postCmd('gamecmd','command.php');this.disabled=true;">
<input type="button" class="cmdbutton" id="teammake" name="teamjoin" value="加入队伍" onclick="$('command').value='team';$('subcmd').name='teamcmd';$('subcmd').value='teamjoin';postCmd('gamecmd','command.php');this.disabled=true;">
<?php } else { ?>
<input type="button" class="cmdbutton" id="teammake" name="teamquit" value="脱离队伍" onclick="$('command').value='team';$('subcmd').name='teamcmd';$('subcmd').value='teamquit';postCmd('gamecmd','command.php');this.disabled=true;">
<?php } } ?>
<br />
<?php if(in_array($pls,$shops)) { ?>
<input type="button" class="cmdbutton" id="sp_shop" name="sp_shop" value="商店" onclick="$('command').value='special';$('subcmd').name='sp_cmd';$('subcmd').value='sp_shop';postCmd('gamecmd','command.php');this.disabled=true;">
<?php } ?>
<input type="button" class="cmdbutton" id="sp_skpts" name="sp_skpts" value="升级技能" onclick="$('command').value='special';$('subcmd').name='sp_cmd';$('subcmd').value='sp_skpts';postCmd('gamecmd','command.php');this.disabled=true;">
<br />
<?php if($club == 7) { ?>
<input type="button" class="cmdbutton" id="sp_adtsk" name="sp_adtsk" value="武器带电" onclick="$('command').value='special';$('subcmd').name='sp_cmd';$('subcmd').value='sp_adtsk';postCmd('gamecmd','command.php');this.disabled=true;">
<input type="button" class="cmdbutton" id="sp_trapadtsk" name="sp_trapadtsk" value="陷阱带电" onclick="$('command').value='special';$('subcmd').name='sp_cmd';$('subcmd').value='sp_trapadtsk';postCmd('gamecmd','command.php');this.disabled=true;">
<?php } elseif($club == 8) { ?>
<input type="button" class="cmdbutton" id="sp_adtsk" name="sp_adtsk" value="武器淬毒" onclick="$('command').value='special';$('subcmd').name='sp_cmd';$('subcmd').value='sp_adtsk';postCmd('gamecmd','command.php');this.disabled=true;">
<input type="button" class="cmdbutton" id="sp_trapadtsk" name="sp_trapadtsk" value="陷阱淬毒" onclick="$('command').value='special';$('subcmd').name='sp_cmd';$('subcmd').value='sp_trapadtsk';postCmd('gamecmd','command.php');this.disabled=true;">
<input type="button" class="cmdbutton" id="sp_poison" name="sp_poison" value="检查毒物" onclick="$('command').value='special';$('subcmd').name='sp_cmd';$('subcmd').value='sp_poison';postCmd('gamecmd','command.php');this.disabled=true;">
<?php } elseif($club == 99) { ?>
<input type="button" class="cmdbutton" id="sp_pbomb" name="sp_pbomb" value="X 按钮" onclick="$('command').value='special';$('subcmd').name='sp_cmd';$('subcmd').value='sp_pbomb';postCmd('gamecmd','command.php');this.disabled=true;">
<?php } ?>
