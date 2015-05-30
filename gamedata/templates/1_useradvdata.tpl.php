<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table align="center">
<tr>
<td>性别</td>
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
<td>头像</td>
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
<td>口头禅</td>
<td><input size="30" type="text" name="motto" maxlength="30" value="<?php echo $motto?>">写下彰显个性的台词，30个字以内。</td>
</tr>
<tr>
<tr>
<td>杀人宣言</td>
<td><input size="30" type="text" name="killmsg" maxlength="30" value="<?php echo $killmsg?>">写下你杀死对手的留言，30个字以内</td>
</tr>
<tr>
<td>遗言</td>
<td><input size="30" type="text" name="lastword" maxlength="30" value="<?php echo $lastword?>">写下你不幸被害时的台词，30个字以内</td>
</tr>
<tr>
<td>头衔</td>
<td>
<select name="nick">
<?php if(is_array($utlist)) { foreach($utlist as $key => $val) { ?>
<option value="<?php echo $val?>"><?php echo $val?></option>
<?php } } ?>
</select>
</td>
</tr>
</table> 