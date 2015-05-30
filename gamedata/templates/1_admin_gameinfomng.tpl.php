<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<form method="post" name="gameinfomng" onsubmit="admin.php">
<input type="hidden" name="mode" value="gameinfomng">
<input type="hidden" id="command" name="command" value="">

<table class="admin">
<tr>
<th>游戏变量</th>
<th>当前数据</th>
<th>操作</th>
</tr>

<tr>
<td>游戏局数</td>
<td><?php echo $gamenum?></td>
<td></td>
</tr>
<tr>
<td>游戏状态</td>
<td><?php echo $gstate[$gamestate]?></td>
<td>
<?php if($gamestate == 0) { ?>
<input type="submit" value="准备开始" onclick="$('command').value='gsedit_10'">
<?php } elseif($gamestate == 10) { ?>
<input type="submit" value="开始游戏" onclick="$('command').value='gsedit_20'"><br>
<input type="submit" value="结束游戏" onclick="$('command').value='gsedit_0'">
<?php } elseif($gamestate == 20) { ?>
<input type="submit" value="停止激活" onclick="$('command').value='gsedit_30'"><br>
<input type="submit" value="进入连斗" onclick="$('command').value='gsedit_40'"><br>
<input type="submit" value="进入死斗" onclick="$('command').value='gsedit_50'"><br>
<input type="submit" value="结束游戏" onclick="$('command').value='gsedit_0'">
<?php } elseif($gamestate == 30) { ?>
<input type="submit" value="进入连斗" onclick="$('command').value='gsedit_40'"><br>
<input type="submit" value="进入死斗" onclick="$('command').value='gsedit_50'"><br>
<input type="submit" value="结束游戏" onclick="$('command').value='gsedit_0'">
<?php } elseif($gamestate == 40) { ?>
<input type="submit" value="进入死斗" onclick="$('command').value='gsedit_50'"><br>
<input type="submit" value="结束游戏" onclick="$('command').value='gsedit_0'">
<?php } elseif($gamestate == 50) { ?>
<input type="submit" value="结束游戏" onclick="$('command').value='gsedit_0'">
<?php } ?>
</td>
</tr>
<tr>
<td>
<?php if($gamestate) { ?>
当前游戏开始时间
<?php } else { ?>
下局游戏开始时间
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
<input type="submit" value="无法设定" disabled>
<?php } else { ?>
<input type="submit" value="设定时间" onclick="$('command').value='sttimeedit'">
<?php } ?>
</td>
</tr>
<tr>
<td>当前天气</td>
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
<td><input type="submit" value="更改天气" onclick="$('command').value='wthedit'"></td>
</tr>
<tr>
<td>禁区列表</td>
<td><?php echo $arealiststr?></td>
<td></td>
</tr>
<tr>
<td>下次禁区列表</td>
<td><?php echo $nextarealiststr?></td>
<td></td>
</tr>
<tr>
<td>已有禁区数目</td>
<td><?php echo $areanum?></td>
<td></td>
</tr>
<tr>
<td>下次禁区时间</td>
<td>
<input type="text" name="areayear" size="4" disabled value="<?php echo $aryear?>"><?php echo $lang['year']?>
<input type="text" name="areamonth" size="2" disabled value="<?php echo $armonth?>"><?php echo $lang['month']?>
<input type="text" name="areaday" size="2" disabled value="<?php echo $arday?>"><?php echo $lang['day']?>
<input type="text" name="areahour" size="2" disabled value="<?php echo $arhour?>"><?php echo $lang['hour']?>
<input type="text" name="areamin" size="2" disabled value="<?php echo $armin?>"><?php echo $lang['min']?>
</td>
<td><input type="submit" value="立刻禁区" onclick="$('command').value='areaadd'"></td>
</tr>
<tr>
<td>禁区解除</td>
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
<td><input type="submit" value="更改状态" onclick="$('command').value='hackedit'"></td>
</tr>
</table>
</form> 