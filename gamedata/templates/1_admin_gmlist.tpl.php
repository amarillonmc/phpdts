<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<form method="post" name="gmlist" onsubmit="admin.php">
<input type="hidden" id="mode" name="mode" value="gmlist">
<input type="hidden" id="command" name="command" value="">
<input type="hidden" id="adminuid" name="adminuid" value="">
<table class="admin">
<tr>
<th>UID</th>
<th width="250px">账号</th>
<th>权限</th>
<th>操作</th>
</tr>
<?php if(is_array($gmdata)) { foreach($gmdata as $n => $gm) { ?>
<tr>
<td><?php echo $gm['uid']?></td>
<td><?php echo $gm['username']?></td>	
<td>
<?php if($gm['groupid'] >= $mygroup) { ?>
<span class="red"><?php echo $gm['groupid']?></span>
<?php } else { ?>
<select name="<?php echo $gm['uid']?>_group">
<?php if(is_array(Array(2,3,4,5,6,7,8,9))) { foreach(Array(2,3,4,5,6,7,8,9) as $i) { ?>
<option value="<?php echo $i?>" 
<?php if($gm['groupid'] == $i) { ?>
selected="true"
<?php } ?>
><?php echo $i?></option>
<?php } } ?>
</select>
<?php } ?>
</td>		
<td>
<input type="submit" value="编辑" 
<?php if($gm['groupid'] >= $mygroup) { ?>
disabled="true"
<?php } ?>
 onclick="$('command').value='edit';$('adminuid').value='<?php echo $gm['uid']?>';">
<input type="submit" value="删除" 
<?php if($gm['groupid'] >= $mygroup) { ?>
disabled="true"
<?php } ?>
 onclick="$('command').value='del';$('adminuid').value='<?php echo $gm['uid']?>';">
</td>
</tr>
<?php } } ?>
<tr>
<td>新增</td>
<td><input type="text" name="addname" value="" size="30" maxlength="15"></td>	
<td>
<select name="addgroup">
<option value="2" selected="true">2</option>
<?php if(is_array(Array(3,4,5,6,7,8,9))) { foreach(Array(3,4,5,6,7,8,9) as $i) { ?>
<option value="<?php echo $i?>"><?php echo $i?></option>
<?php } } ?>
</select>
</td>
<td><input type="submit" value="新增" onclick="$('command').value='add'"></td>
</tr>
</table>
</form>
