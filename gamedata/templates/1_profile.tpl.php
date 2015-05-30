<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table border="0" width="720" height="100%" cellspacing="0" cellpadding="0"  valign="middle">
<tr>
<td>
<table border="0" width="720" cellspacing="0" cellpadding="0"  valign="middle">
<tr>
<td width="210" colspan="3" class="b1"><span><?php echo $nick?> <?php echo $name?></span></td>
<td width="100" colspan="1" class="b1"><span><?php echo $sexinfo[$gd]?><?php echo $sNo?>号</span></td>
<td width="95" colspan="2" class="b1"><span>天气:<?php echo $wthinfo[$weather]?></span></td>
<td width="215" colspan="1" class="b1"><span><?php echo $month?>月<?php echo $day?>日 星期<?php echo $week["$wday"]?> <?php echo $hour?>:<?php echo $min?>
<?php if($gamestate == 40 ) { ?>
<span class="yellow">连斗</span>
<?php } if($gamestate == 50 ) { ?>
<span class="red">死斗</span>
<?php } ?>
</span></td>
</tr>
<tr>
<td rowspan="4" colspan="2" width="150" height="80" class="b3"><span><img src="img/<?php echo $iconImg?>" border="0" style="width:140;height:80" 
<?php if($hp==0) { ?>
style="filter:Xray()"
<?php } ?>
 /></span></td>
<td width="70" class="b2"><span>等级</span></td>
<td width="120" class="b3"><span>Lv. <?php echo $lvl?></span></td>
<td width="60" class="b2"><span>
<?php if($wp >= 100) { ?>
殴熟
<?php } else { ?>
<span class="grey">殴熟</span>
<?php } ?>
</span></td>
<td width="80" class="b3"><span><?php echo $wp?></span></td>
<td rowspan="9" width="215" height="160" class="b3">
<div>
<table border="0" width=215px height=160px cellspacing="0" cellpadding="0" style="position:relative">
<tr height=160px>
<td width=160px background="img/state1.gif" style="position:relative;background-repeat:no-repeat;background-position:right top;">
<div style="border:0; margin:0; cellspacing:0; cellpadding:0; position:absolute;z-index:10;top:0;left:0;">
<img id="injuerd" 
<?php if(strpos($inf,'h') !== false || strpos($inf,'b') !== false ||strpos($inf,'a') !== false ||strpos($inf,'f') !== false) { ?>
src="img/injured.gif"
<?php } else { ?>
src="img/injured2.gif"
<?php } ?>
 style="position:absolute;top:0;left:10;width:84;height:20;">
<img id="poisoned" 
<?php if(strpos($inf,'p') !== false) { if($club==16 && CURSCRIPT == 'game' && $mode == 'command') { ?>
onclick="$('mode').value='special';$('command').value='infp';postCmd('gamecmd','command.php');return false;" 
<?php } ?>
src="img/p.gif"
<?php } else { ?>
src="img/p2.gif"
<?php } ?>
 style="position:absolute;top:20;left:4;width:98;height:20;">
<img id="burned" 
<?php if(strpos($inf,'u') !== false) { if($club==16 && CURSCRIPT == 'game' && $mode == 'command') { ?>
onclick="$('mode').value='special';$('command').value='infu';postCmd('gamecmd','command.php');return false;" 
<?php } ?>
src="img/u.gif"
<?php } else { ?>
src="img/u2.gif"
<?php } ?>
 style="position:absolute;top:40;left:11;width:81;height:20;">
<img id="frozen" 
<?php if(strpos($inf,'i') !== false) { if($club==16 && CURSCRIPT == 'game' && $mode == 'command') { ?>
onclick="$('mode').value='special';$('command').value='infi';postCmd('gamecmd','command.php');return false;" 
<?php } ?>
src="img/i.gif"
<?php } else { ?>
src="img/i2.gif"
<?php } ?>
 style="position:absolute;top:60;left:13;width:77;height:20;">
<img id="paralysed" 
<?php if(strpos($inf,'e') !== false) { if($club==16 && CURSCRIPT == 'game' && $mode == 'command') { ?>
onclick="$('mode').value='special';$('command').value='infe';postCmd('gamecmd','command.php');return false;" 
<?php } ?>
src="img/e.gif"
<?php } else { ?>
src="img/e2.gif"
<?php } ?>
 style="position:absolute;top:80;left:2;width:101;height:20;">
<img id="confused" 
<?php if(strpos($inf,'w') !== false) { if($club==16 && CURSCRIPT == 'game' && $mode == 'command') { ?>
onclick="$('mode').value='special';$('command').value='infw';postCmd('gamecmd','command.php');return false;" 
<?php } ?>
src="img/w.gif"
<?php } else { ?>
src="img/w2.gif"
<?php } ?>
 style="position:absolute;top:100;left:3;width:100;height:20;">
<?php if(strpos($inf,'h') !== false) { ?>
<img src="img/hurt.gif" style="position:absolute;top:0;left:121;width:37;height:37;" 
<?php if(CURSCRIPT == 'game' && $mode == 'command') { ?>
onclick="$('mode').value='special';$('command').value='infh';postCmd('gamecmd','command.php');return false;"
<?php } ?>
>
<?php } if(strpos($inf,'b') !== false) { ?>
<img src="img/hurt.gif" style="position:absolute;top:43;left:121;width:37;height:37;" 
<?php if(CURSCRIPT == 'game' && $mode == 'command') { ?>
onclick="$('mode').value='special';$('command').value='infb';postCmd('gamecmd','command.php');return false;"
<?php } ?>
>
<?php } if(strpos($inf,'a') !== false) { ?>
<img src="img/hurt.gif" style="position:absolute;top:17;left:102;width:37;height:37;" 
<?php if(CURSCRIPT == 'game' && $mode == 'command') { ?>
onclick="$('mode').value='special';$('command').value='infa';postCmd('gamecmd','command.php');return false;"
<?php } ?>
>
<?php } if(strpos($inf,'f') !== false) { ?>
<img src="img/hurt.gif" style="position:absolute;top:111;left:121;width:37;height:37;" 
<?php if(CURSCRIPT == 'game' && $mode == 'command') { ?>
onclick="$('mode').value='special';$('command').value='inff';postCmd('gamecmd','command.php');return false;"
<?php } ?>
>
<?php } if($hp <= 0) { ?>
<img src="img/dead.gif" style="position:absolute;top:120;left:6;width:94;height:40">
<?php } elseif($hp <= $mhp*0.2) { ?>
<img src="img/danger.gif" style="position:absolute;top:120;left:5;width:95;height:37">
<?php } elseif($hp <= $mhp*0.5) { ?>
<img src="img/caution.gif" style="position:absolute;top:120;left:5;width:95;height:36">
<?php } elseif(!$inf) { ?>
<img src="img/fine.gif" style="position:absolute;top:120;left:12;width:81;height:38">
<?php } ?>
</div>
<div style="border:0; margin:0; cellspacing:0; cellpadding:0; position:absolute;top:0px;left:105px;z-index:1;">
<?php echo $newspimg?>
</div>
</td>
<td width=55px background="img/state2.gif" style="position:relative;background-repeat:no-repeat;background-position:left top;">
<div style="border:0; margin:0; cellspacing:0; cellpadding:0; position:absolute;top:0px;right:55px;z-index:1;">
<?php echo $newhpimg?>
</div>
</td>
</tr>
</table>
</div>
</td>
</tr>
<tr>
<td class="b2"><span>经验值</span></td>
<td class="b3"><span><?php echo $exp?> / <?php echo $upexp?></span></td>
<td class="b2"><span>
<?php if($wk >= 100) { ?>
斩熟
<?php } else { ?>
<span class="grey">斩熟</span>
<?php } ?>
</span></td>
<td class="b3"><span><?php echo $wk?></span></td>
</tr>
<tr>
<td class="b2"><span>队伍</span></td>
<td class="b3"><span>
<?php if($teamID && $gamestate < 40 ) { ?>
<?php echo $teamID?>
<?php } else { ?>
 无 
<?php } ?>
</span></td>
<td class="b2"><span>
<?php if($wg >= 100) { ?>
射熟
<?php } else { ?>
<span class="grey">射熟</span>
<?php } ?>
</span></td>
<td class="b3"><span><?php echo $wg?></span></td>
</tr>
<tr>
<td class="b2"><span>内定称号</span></td>
<td class="b3"><span>
<?php if($club!=0) { ?>
<?php echo $clubinfo[$club]?>
<?php } else { ?>
<select id="clubsel" name="clubsel" onchange="$('mode').value='special';$('command').value=$('clubsel').value;postCmd('gamecmd','command.php');return false;" 
<?php if(CURSCRIPT != 'game' || $mode != 'command') { ?>
disabled
<?php } ?>
>
<?php if(is_array($clubavl)) { foreach($clubavl as $key => $value) { ?>
<option value="clubsel<?php echo $key?>"
<?php if($club == $key) { ?>
selected
<?php } ?>
><?php echo $clubinfo[$value]?>
<?php } } ?>
</select>
<?php } ?>
</span></td>
<td class="b2"><span>
<?php if($wc >= 100) { ?>
投熟
<?php } else { ?>
<span class="grey">投熟</span>
<?php } ?>
</span></td>
<td class="b3"><span><?php echo $wc?></span></td>
</tr>
<tr>
<td width="70" class="b2"><span>攻击力</span></td>
<td width="80" class="b3"><span><?php echo $att?> + <?php echo $wepe?></span></td>
<td class="b2"><span>金钱</span></td>
<td class="b3"><span><?php echo $money?> 元</span></td>
<td class="b2"><span>
<?php if($wd >= 100) { ?>
爆熟
<?php } else { ?>
<span class="grey">爆熟</span>
<?php } ?>
</span></td>
<td class="b3"><span><?php echo $wd?></span></td>
</tr>
<tr>
<td class="b2"><span>防御力</span></td>
<td class="b3"><span><?php echo $def?> + <?php echo $ardef?></span></td>
<td class="b2"><span>受伤部位</span></td>
<td class="b3">
<span>
<?php if($inf) { if(is_array($infinfo)) { foreach($infinfo as $key => $val) { if(strpos($inf,$key)!==false) { ?>
<?php echo $val?>
<?php } } } } else { ?>
无
<?php } ?>
</span>
</td>
<td class="b2"><span>
<?php if($wf >= 100) { ?>
灵熟
<?php } else { ?>
<span class="grey">灵熟</span>
<?php } ?>
</span></td>
<td class="b3"><span><?php echo $wf?></span></td>
</tr>
<tr>
<td class="b2"><span>基础姿态</span></td>
<td class="b3">
<span>
<select id="pose" name="pose" onchange="$('mode').value='special';$('command').value=$('pose').value;postCmd('gamecmd','command.php');return false;" 
<?php if(CURSCRIPT != 'game' || $mode != 'command') { ?>
disabled
<?php } ?>
>
<?php if(is_array($poseinfo)) { foreach($poseinfo as $key => $value) { if(($value)&&($value!='强袭姿态')) { ?>
<option value="pose<?php echo $key?>"
<?php if($pose == $key) { ?>
selected
<?php } ?>
><?php echo $value?>
<?php } } } ?>
</select>
</span>
</td>
<td class="b2"><span>体力</span></td>
<td class="b3"><span><span class="
<?php if($sp <= $msp*0.2) { ?>
grey
<?php } elseif($sp <= $msp*0.5) { ?>
yellow
<?php } else { ?>
clan
<?php } ?>
"><?php echo $sp?> / <?php echo $msp?></span></span></td>
<td class="b2"><span>怒气</span></td>
<td class="b3"><span>
<?php if($rage >= 30) { ?>
<span class="yellow"><?php echo $rage?></span>
<?php } else { ?>
<?php echo $rage?>
<?php } ?>
</span></td>
</tr>
<tr>
<td class="b2"><span>应战策略</span></td>
<td class="b3">
<span>
<select id="tactic" name="tactic" onchange="$('mode').value='special';$('command').value=$('tactic').value;postCmd('gamecmd','command.php');return false;" 
<?php if(CURSCRIPT != 'game' || $mode != 'command') { ?>
disabled
<?php } ?>
>
<?php if(is_array($tacinfo)) { foreach($tacinfo as $key => $value) { if($value) { ?>
<option value="tac<?php echo $key?>"
<?php if($tactic == $key) { ?>
selected
<?php } ?>
><?php echo $value?>
<?php } } } ?>
</select>
</span>
</td>
<td class="b2"><span>生命</span></td>
<td class="b3"><span><span class="
<?php if($hp <= $mhp*0.2) { ?>
red
<?php } elseif($hp <= $mhp*0.5) { ?>
yellow
<?php } else { ?>
clan
<?php } ?>
"><?php echo $hp?> / <?php echo $mhp?></span></span></td>
<td class="b2"><span>技能点</span></td>
<td class="b3">
<?php if($skillpoint > 0) { ?>
<span class="lime"><?php echo $skillpoint?></span>
<?php } else { ?>
<span><?php echo $skillpoint?></span>
<?php } ?>
</td>
</tr>
<tr>
<td class="b2">■■：</td>
<td class="b3">
■■■应■回■■■。</td>
<td class="b2"><span>歌魂</span></td>
<td class="b3"><span class="
<?php if($ss <= $mss*0.2) { ?>
red
<?php } elseif($ss <= $mss*0.5) { ?>
yellow
<?php } else { ?>
clan
<?php } ?>
"><?php echo $ss?> / <?php echo $mss?></span></td>
<td class="b2"><span>击杀数</span></td>
<td class="b3"><span><?php echo $killnum?></span></td>
</tr>
</table>
</td>
</tr>
<tr>
<td height="10" class="b5"></td>
</tr>
<tr>
<td>
  		<TABLE border="0" cellSpacing=0 cellPadding=0 height=140 width=720>
  			<tr>
      		<td>
      	<TABLE border="0" cellSpacing=0 cellPadding=0 height=100% width=100%>
  						<TR>
          			<TD class=b1 width="60"><span>装备种类</span></TD>
          			<TD class=b1><span>名称</span></TD>
          			<TD class=b1 width="70"><span>属性</span></TD>
          			<TD class=b1 width="40"><span>效</span></TD>
          			<TD class=b1 width="40"><span>耐</span></TD>
          		</tr>
          		<tr>
    						<TD class=b2 height="26"><span>
<?php if($wepk_words) { ?>
<?php echo $wepk_words?>
<?php } else { ?>
<?php echo $mltwk?>
<?php } ?>
</span></TD>
                <TD class=b3>
                
<?php if(CURSCRIPT == 'game' && $mode == 'command' && $wepe) { ?>
<span><input type="button" value="卸下" onclick="$('mode').value='itemmain';$('command').value='offwep';postCmd('gamecmd','command.php');return false;"</span>
<?php } ?>
                	<span>
<?php if($weps) { ?>
<?php echo $wep?>
<?php } else { ?>
<?php echo $nowep?>
<?php } ?>
</span>
                </TD>
                <TD class=b3><span><?php echo $wepsk_words?></span></TD>
                <TD class=b3><span><?php echo $wepe?></span></TD>
                <TD class=b3><span><?php echo $weps?></span></TD>
          </tr>
          <tr>
    	          <TD class=b2 height="26"><span>
<?php if($arbs) { ?>
<?php echo $iteminfo['DB']?>
<?php } else { ?>
<span class="grey"><?php echo $iteminfo['DB']?></span>
<?php } ?>
</span></TD>
                <TD class=b3>
                
<?php if(CURSCRIPT == 'game' && $mode == 'command' && $arbe) { ?>
<span><input type="button" value="卸下" onclick="$('mode').value='itemmain';$('command').value='offarb';postCmd('gamecmd','command.php');return false;"</span>
<?php } ?>
                	<span>
<?php if($arbs) { ?>
<?php echo $arb?>
<?php } else { ?>
<?php echo $noarb?>
<?php } ?>
</span>
                </TD>
                <TD class=b3><span><?php echo $arbsk_words?></span></TD>
                <TD class=b3><span><?php echo $arbe?></span></TD>
                <TD class=b3><span><?php echo $arbs?></span></TD>
          </tr>
          <tr>
    						<TD class=b2 height="26"><span>
<?php if($arhs) { ?>
<?php echo $iteminfo['DH']?>
<?php } else { ?>
<span class="grey"><?php echo $iteminfo['DH']?></span>
<?php } ?>
</span></TD>
                <TD class=b3>
                
<?php if(CURSCRIPT == 'game' && $mode == 'command' && $arhs) { ?>
<span><input type="button" value="卸下" onclick="$('mode').value='itemmain';$('command').value='offarh';postCmd('gamecmd','command.php');return false;"</span>
<?php } ?>
                	<span>
<?php if($arhs) { ?>
<?php echo $arh?>
<?php } else { ?>
<?php echo $noitm?>
<?php } ?>
</span>
                </TD>
                <TD class=b3><span><?php echo $arhsk_words?></span></TD>
                <TD class=b3><span><?php echo $arhe?></span></TD>
                <TD class=b3><span><?php echo $arhs?></span></TD>
          </tr>
          <tr>
    			  		<TD class=b2 height="26"><span>
<?php if($aras) { ?>
<?php echo $iteminfo['DA']?>
<?php } else { ?>
<span class="grey"><?php echo $iteminfo['DA']?></span>
<?php } ?>
</span></TD>
                <TD class=b3>
                
<?php if(CURSCRIPT == 'game' && $mode == 'command' && $aras) { ?>
<span><input type="button" value="卸下" onclick="$('mode').value='itemmain';$('command').value='offara';postCmd('gamecmd','command.php');return false;"</span>
<?php } ?>
                	<span>
<?php if($aras) { ?>
<?php echo $ara?>
<?php } else { ?>
<?php echo $noitm?>
<?php } ?>
</span>
                </TD>
                <TD class=b3><span><?php echo $arask_words?></span></TD>
                <TD class=b3><span><?php echo $arae?></span></TD>
                <TD class=b3><span><?php echo $aras?></span></TD>
          </tr>
          <tr>
    						<TD class=b2 height="26"><span>
<?php if($arfs) { ?>
<?php echo $iteminfo['DF']?>
<?php } else { ?>
<span class="grey"><?php echo $iteminfo['DF']?></span>
<?php } ?>
</span></TD>
                <TD class=b3>
                
<?php if(CURSCRIPT == 'game' && $mode == 'command' && $arfs) { ?>
<span><input type="button" value="卸下" onclick="$('mode').value='itemmain';$('command').value='offarf';postCmd('gamecmd','command.php');return false;"</span>
<?php } ?>
                	<span>
<?php if($arfs) { ?>
<?php echo $arf?>
<?php } else { ?>
<?php echo $noitm?>
<?php } ?>
</span>
                </TD>
                <TD class=b3><span><?php echo $arfsk_words?></span></TD>
                <TD class=b3><span><?php echo $arfe?></span></TD>
                <TD class=b3><span><?php echo $arfs?></span></TD>
          </tr>
          <tr>
    						<TD class=b2 height="26"><span>
<?php if($arts) { ?>
<?php echo $artk_words?>
<?php } else { ?>
<span class="grey"><?php echo $iteminfo['A']?></span>
<?php } ?>
</span></TD>
                <TD class=b3>
                
<?php if(CURSCRIPT == 'game' && $mode == 'command' && $arts) { ?>
<span><input type="button" value="卸下" onclick="$('mode').value='itemmain';$('command').value='offart';postCmd('gamecmd','command.php');return false;"</span>
<?php } ?>
                	<span>
<?php if($arts) { ?>
<?php echo $art?>
<?php } else { ?>
<?php echo $noitm?>
<?php } ?>
</span>
                </TD>
                <TD class=b3><span><?php echo $artsk_words?></span></TD>
                <TD class=b3><span><?php echo $arte?></span></TD>
                <TD class=b3><span><?php echo $arts?></span></TD>
          		</tr>
        		</table>
</td>
<td>
  					<TABLE border="0" cellSpacing=0 cellPadding=0 height=100% width=100%>
      		<tr>
<TD class=b1 width="60"><span>道具用途</span></TD>
<TD class=b1><span>名称</span></TD>
<TD class=b1 width="70"><span>属性</span></TD>
<TD class=b1 width="40"><span>效</span></TD>
<TD class=b1 width="40"><span>耐</span></TD>
</TR>
<tr>          			  
<TD class=b2 height="26"><span>
<?php if($itmk1_words) { ?>
<?php echo $itmk1_words?>
<?php } else { ?>
<span class="grey">包裹1</span>
<?php } ?>
</span></TD>
<TD class=b3><span>
<?php if($itms1) { ?>
<?php echo $itm1?>
<?php } else { ?>
<?php echo $noitm?>
<?php } ?>
</span></TD>
<TD class=b3><span><?php echo $itmsk1_words?></span></TD>
<TD class=b3><span><?php echo $itme1?></span></TD>
<TD class=b3><span><?php echo $itms1?></span></TD>
</tr>
<tr>
<TD class=b2 height="26"><span>
<?php if($itmk2_words) { ?>
<?php echo $itmk2_words?>
<?php } else { ?>
<span class="grey">包裹2</span>
<?php } ?>
</span></TD>
<TD class=b3><span>
<?php if($itms2) { ?>
<?php echo $itm2?>
<?php } else { ?>
<?php echo $noitm?>
<?php } ?>
</span></TD>
<TD class=b3><span><?php echo $itmsk2_words?></span></TD>
<TD class=b3><span><?php echo $itme2?></span></TD>
<TD class=b3><span><?php echo $itms2?></span></TD>
</tr>
<tr>          			  
<TD class=b2 height="26"><span>
<?php if($itmk3_words) { ?>
<?php echo $itmk3_words?>
<?php } else { ?>
<span class="grey">包裹3</span>
<?php } ?>
</span></TD>
<TD class=b3><span>
<?php if($itms3) { ?>
<?php echo $itm3?>
<?php } else { ?>
<?php echo $noitm?>
<?php } ?>
</span></TD>
<TD class=b3><span><?php echo $itmsk3_words?></span></TD>
<TD class=b3><span><?php echo $itme3?></span></TD>
<TD class=b3><span><?php echo $itms3?></span></TD>
</tr>
<tr>          	
<TD class=b2 height="26"><span>
<?php if($itmk4_words) { ?>
<?php echo $itmk4_words?>
<?php } else { ?>
<span class="grey">包裹4</span>
<?php } ?>
</span></TD>
<TD class=b3><span>
<?php if($itms4) { ?>
<?php echo $itm4?>
<?php } else { ?>
<?php echo $noitm?>
<?php } ?>
</span></TD>
<TD class=b3><span><?php echo $itmsk4_words?></span></TD>
<TD class=b3><span><?php echo $itme4?></span></TD>
<TD class=b3><span><?php echo $itms4?></span></TD>
</tr>
<tr>          			  
<TD class=b2 height="26"><span>
<?php if($itmk5_words) { ?>
<?php echo $itmk5_words?>
<?php } else { ?>
<span class="grey">包裹5</span>
<?php } ?>
</span></TD>
<TD class=b3><span>
<?php if($itms5) { ?>
<?php echo $itm5?>
<?php } else { ?>
<?php echo $noitm?>
<?php } ?>
</span></TD>
<TD class=b3><span><?php echo $itmsk5_words?></span></TD>
<TD class=b3><span><?php echo $itme5?></span></TD>
<TD class=b3><span><?php echo $itms5?></span></TD>
</tr>
<tr>          			  
<TD class=b2 height="26"><span>
<?php if($itmk6_words) { ?>
<?php echo $itmk6_words?>
<?php } else { ?>
<span class="grey">包裹6</span>
<?php } ?>
</span></TD>
<TD class=b3><span>
<?php if($itms6) { ?>
<?php echo $itm6?>
<?php } else { ?>
<?php echo $noitm?>
<?php } ?>
</span></TD>
<TD class=b3><span><?php echo $itmsk6_words?></span></TD>
<TD class=b3><span><?php echo $itme6?></span></TD>
<TD class=b3><span><?php echo $itms6?></span></TD>
</tr>
</table>
      		</td>
      	</tr>
</TABLE>
</td>
</tr>
</table>
