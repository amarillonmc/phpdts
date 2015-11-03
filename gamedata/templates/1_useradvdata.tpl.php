<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table align="center">
<tr>
<td>Gender</td>
<td>
<input type="radio" id="male" name="gender" onclick="userIconMover()" value="m" 
<?php if($gender != "f") { ?>
checked
<?php } ?>
 ><?php echo $sexinfo['m']?> 
<input type="radio" name="gender" onclick="userIconMover()" value="f" 
<?php if($gender == "f") { ?>
checked
<?php } ?>
><?php echo $sexinfo['f']?>
</td>
</tr>
<tr>
<td>Avatar</td>
<td>
<select id="icon" name="icon" onchange="userIconMover()">
<?php if(is_array($iconarray)) { foreach($iconarray as $icon) { ?>
<?php echo $icon?>
<?php } } ?>
</select>（0为随机）
<div id="userIconImg" class="iconImg" >
<img src="img/
<?php if($gender != 'f') { ?>
m
<?php } else { ?>
f
<?php } ?>
_<?php echo $select_icon?>.gif" alt="<?php echo $select_icon?>">
</div>
</td>
</tr>
<tr>
<td>Motto</td>
<td><input size="30" type="text" name="motto" maxlength="30" value="<?php echo $motto?>">Maximum 30 bytes.</td>
</tr>
<tr>
<tr>
<td>Kill Note</td>
<td><input size="30" type="text" name="killmsg" maxlength="30" value="<?php echo $killmsg?>">Maximum 30 bytes.</td>
</tr>
<tr>
<td>Last Note</td>
<td><input size="30" type="text" name="lastword" maxlength="30" value="<?php echo $lastword?>">Maximum 30 bytes.</td>
</tr>
<tr>
<td>Title</td>
<td>
<select name="nick">
<?php if(is_array($utlist)) { foreach($utlist as $key => $val) { ?>
<option value="<?php echo $val?>"><?php echo $val?></option>
<?php } } ?>
</select>
</td>
</tr>
</table> 