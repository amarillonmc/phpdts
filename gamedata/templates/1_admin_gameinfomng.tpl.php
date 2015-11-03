<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<form method="post" name="gameinfomng" onsubmit="admin.php">
<input type="hidden" name="mode" value="gameinfomng">
<input type="hidden" id="command" name="command" value="">

<table class="admin">
<tr>
<th>Game Variables</th>
<th>Current Data</th>
<th>Process Info</th>
</tr>

<tr>
<td>Game Number</td>
<td><?php echo $gamenum?></td>
<td></td>
</tr>
<tr>
<td>Game Status</td>
<td><?php echo $gstate[$gamestate]?></td>
<td>
<?php if($gamestate == 0) { ?>
<input type="submit" value="PREPARATION" onclick="$('command').value='gsedit_10'">
<?php } elseif($gamestate == 10) { ?>
<input type="submit" value="GAME START" onclick="$('command').value='gsedit_20'"><br>
<input type="submit" value="END GAME" onclick="$('command').value='gsedit_0'">
<?php } elseif($gamestate == 20) { ?>
<input type="submit" value="LOCK ENTRY" onclick="$('command').value='gsedit_30'"><br>
<input type="submit" value="GO LOCKDOWN" onclick="$('command').value='gsedit_40'"><br>
<input type="submit" value="GO DUEL" onclick="$('command').value='gsedit_50'"><br>
<input type="submit" value="END GAME" onclick="$('command').value='gsedit_0'">
<?php } elseif($gamestate == 30) { ?>
<input type="submit" value="GO LOCKDOWN" onclick="$('command').value='gsedit_40'"><br>
<input type="submit" value="GO DUEL" onclick="$('command').value='gsedit_50'"><br>
<input type="submit" value="END GAME" onclick="$('command').value='gsedit_0'">
<?php } elseif($gamestate == 40) { ?>
<input type="submit" value="GO DUEL" onclick="$('command').value='gsedit_50'"><br>
<input type="submit" value="END GAME" onclick="$('command').value='gsedit_0'">
<?php } elseif($gamestate == 50) { ?>
<input type="submit" value="END GAME" onclick="$('command').value='gsedit_0'">
<?php } ?>
</td>
</tr>
<tr>
<td>
<?php if($gamestate) { ?>
Current Round Start Time
<?php } else { ?>
Next Round Start Time
<?php } ?>
</td>
<td>				
<input type="text" name="setyear" size="4" value="<?php echo $styear?>"><?php echo $lang['year']?>
<input type="text" name="setmonth" size="2" value="<?php echo $stmonth?>"><?php echo $lang['month']?>
<input type="text" name="setday" size="2" value="<?php echo $stday?>"><?php echo $lang['day']?>
<input type="text" name="sethour" size="2" value="<?php echo $sthour?>"><?php echo $lang['hour']?>
<input type="text" name="setmin" size="2" value="<?php echo $stmin?>"><?php echo $lang['min']?>
</td>
<td>
<?php if($gamestate) { ?>
<input type="submit" value="CANNOT SET" disabled>
<?php } else { ?>
<input type="submit" value="SET TIME" onclick="$('command').value='sttimeedit'">
<?php } ?>
</td>
</tr>
<tr>
<td>Weather Info</td>
<td>
<select name="iweather">
<?php if(is_array($wthinfo)) { foreach($wthinfo as $n => $wth) { ?>
<option value="<?php echo $n?>" 
<?php if($weather == $n) { ?>
selected
<?php } ?>
><?php echo $wth?>
<?php } } ?>
</select>
</td>
<td><input type="submit" value="CHANGE WEATHER" onclick="$('command').value='wthedit'"></td>
</tr>
<tr>
<td>Restricted Area List</td>
<td><?php echo $arealiststr?></td>
<td></td>
</tr>
<tr>
<td>Next On List</td>
<td><?php echo $nextarealiststr?></td>
<td></td>
</tr>
<tr>
<td>Existing Restriced Areas</td>
<td><?php echo $areanum?></td>
<td></td>
</tr>
<tr>
<td>Next Restricted area in</td>
<td>
<input type="text" name="areayear" size="4" disabled value="<?php echo $aryear?>"><?php echo $lang['year']?>
<input type="text" name="areamonth" size="2" disabled value="<?php echo $armonth?>"><?php echo $lang['month']?>
<input type="text" name="areaday" size="2" disabled value="<?php echo $arday?>"><?php echo $lang['day']?>
<input type="text" name="areahour" size="2" disabled value="<?php echo $arhour?>"><?php echo $lang['hour']?>
<input type="text" name="areamin" size="2" disabled value="<?php echo $armin?>"><?php echo $lang['min']?>
</td>
<td><input type="submit" value="INSTANT ADD" onclick="$('command').value='areaadd'"></td>
</tr>
<tr>
<td>Remove all Restricted Area</td>
<td>
<input type="radio" name="ihack" value="1" 
<?php if($hack) { ?>
checked
<?php } ?>
>是&nbsp;&nbsp;&nbsp;<input type="radio" name="ihack" value="0" 
<?php if(!$hack) { ?>
checked
<?php } ?>
>否
</td>
<td><input type="submit" value="CHANGE" onclick="$('command').value='hackedit'"></td>
</tr>
</table>
</form> 