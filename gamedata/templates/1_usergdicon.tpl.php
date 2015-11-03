<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table align="center">
<tr>
<td>Gender</td>
<td>
<input type="radio" id="male" name="gender" onclick="userIconMover()" value="m" 
<?php if($gender != "f") { ?>
checked
<?php } ?>
 ><?php echo $sexinfo['m']?><br />
<input type="radio" name="gender" onclick="userIconMover()" value="f" 
<?php if($gender == "f") { ?>
checked
<?php } ?>
><?php echo $sexinfo['f']?>
</td>
<td> </td>
</tr>
<tr>
<td>Avatar</td>
<td>
<select id="icon" name="icon" onchange="userIconMover()">
<?php if(is_array($iconarray)) { foreach($iconarray as $icon) { ?>
<?php echo $icon?>
<?php } } ?>
</select>（0为随机）
</td>
<td>
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
</table>