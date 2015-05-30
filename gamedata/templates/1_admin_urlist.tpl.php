<?php if(!defined('IN_GAME')) exit('Access Denied'); if($urcmd == 'list') { ?>
<form method="post" name="urpage" onsubmit="admin.php">
<input type="hidden" name="mode" value="urlist">
<input type="hidden" id="urcmd" name="urcmd" value="list">
<?php if($pagecmd=='check') { ?>
<input type="hidden" name="pagecmd" value="check">
<input type="hidden" name="urorder" value="<?php echo $urorder?>">
<input type="hidden" name="urorder2" value="<?php echo $urorder2?>">
<?php } elseif($pagecmd=='find') { ?>
<input type="hidden" name="pagecmd" value="find">
<input type="hidden" name="checkinfo" value="<?php echo $checkinfo?>">
<input type="hidden" name="checkmode" value="<?php echo $checkmode?>">
<?php } ?>
<input type="hidden" name="start" value="<?php echo $start?>">
<input type="hidden" id="pagemode" name="pagemode" value="">
<input type="submit" value="上一页" onclick="$('pagemode').value='up';">
<span class="yellow"><?php echo $resultinfo?></span>
<input type="submit" value="下一页" onclick="$('pagemode').value='down';">
<?php if($urdata) { ?>
<table class="admin">
<tr>
<th>选</th>
<th>账号</th>
<th>密码</th>
<th>权限</th>
<th>最新游戏</th>
<th>ip</th>
<th>分数1</th>
<th>分数2</th>
<th>性别</th>
<th>头像</th>
<th>社团</th>
<th>成就</th>
<th>称号表</th>
<th>口头禅</th>
<th>杀人留言</th>
<th>遗言</th>
<th>操作</th>
</tr>
<?php if($urdata) { if(is_array($urdata)) { foreach($urdata as $n => $ur) { ?>
<tr>
<?php if($ur['groupid']>$mygroup) { ?>
<td><input type="checkbox" id="user_<?php echo $n?>" name="user_<?php echo $n?>" value="<?php echo $ur['uid']?>" disabled="true"></td>
<td><?php echo $ur['username']?></td>
<td><input type="text" name="pass_<?php echo $n?>" size="20" maxlength="20" value="无法修改" disabled="true"></td>
<td><?php echo $urgroup[$ur['groupid']]?></td>
<td>第<?php echo $ur['lastgame']?>局</td>
<td><?php echo $ur['ip']?></td>
<td><input type="text" name="credits_<?php echo $n?>" size="6" maxlength="10" value="<?php echo $ur['credits']?>" disabled="true"></td>
<td><input type="text" name="credits2_<?php echo $n?>" size="6" maxlength="10" value="<?php echo $ur['credits2']?>" disabled="true"></td>
<td>
<select name="gender_<?php echo $n?>" disabled="true">
<option value="0" 
<?php if($ur['gender']==0) { ?>
selected
<?php } ?>
><?php echo $ursex['0']?>
<option value="m" 
<?php if($ur['gender']=='m') { ?>
selected
<?php } ?>
><?php echo $ursex['m']?>
<option value="f" 
<?php if($ur['gender']=='f') { ?>
selected
<?php } ?>
><?php echo $ursex['f']?>
</select>
</td>
<td><input type="text" name="icon_<?php echo $n?>" size="2" maxlength="2" value="<?php echo $ur['icon']?>" disabled="true"></td>
<td><?php echo $clubinfo[$ur['club']]?></td>
<td><input type="text" name="achievement_<?php echo $n?>" size="20" maxlength="400" value="<?php echo $ur['achievement']?>" disabled="true"></td>
<td><input type="text" name="nicks_<?php echo $n?>" size="20" maxlength="300" value="<?php echo $ur['nicks']?>" disabled="true"></td>
<td><input type="text" name="motto_<?php echo $n?>" size="20" maxlength="60" value="<?php echo $ur['motto']?>" disabled="true"></td>
<td><input type="text" name="killmsg_<?php echo $n?>" size="20" maxlength="20" value="<?php echo $ur['killmsg']?>" disabled="true"></td>
<td><input type="text" name="lastword_<?php echo $n?>" size="20" maxlength="20" value="<?php echo $ur['lastword']?>" disabled="true"></td>
<td>
<input type="submit" value="修改" disabled="true">
</td>
<?php } else { ?>
<td><input type="checkbox" id="user_<?php echo $n?>"  name="user_<?php echo $n?>" value="<?php echo $ur['uid']?>"></td>
<td><?php echo $ur['username']?></td>
<td><input type="text" name="pass_<?php echo $n?>" size="20" maxlength="20" value=""></td>
<td><?php echo $urgroup[$ur['groupid']]?></td>
<td>第<?php echo $ur['lastgame']?>局</td>
<td><?php echo $ur['ip']?></td>
<td><input type="text" name="credits_<?php echo $n?>" size="6" maxlength="10" value="<?php echo $ur['credits']?>"></td>
<td><input type="text" name="credits2_<?php echo $n?>" size="6" maxlength="10" value="<?php echo $ur['credits2']?>"></td>
<td>
<select name="gender_<?php echo $n?>">
<option value="0" 
<?php if($ur['gender']==0) { ?>
selected
<?php } ?>
><?php echo $ursex['0']?>
<option value="m" 
<?php if($ur['gender']=='m') { ?>
selected
<?php } ?>
><?php echo $ursex['m']?>
<option value="f" 
<?php if($ur['gender']=='f') { ?>
selected
<?php } ?>
><?php echo $ursex['f']?>
</select>
</td>
<td><input type="text" name="icon_<?php echo $n?>" size="2" maxlength="2" value="<?php echo $ur['icon']?>"></td>
<td><?php echo $clubinfo[$ur['club']]?></td>
<td><input type="text" name="achievement_<?php echo $n?>" size="20" maxlength="400" value="<?php echo $ur['achievement']?>"></td>
<td><input type="text" name="nicks_<?php echo $n?>" size="20" maxlength="300" value="<?php echo $ur['nicks']?>"></td>
<td><input type="text" name="motto_<?php echo $n?>" size="20" maxlength="60" value="<?php echo $ur['motto']?>"></td>
<td><input type="text" name="killmsg_<?php echo $n?>" size="20" maxlength="60" value="<?php echo $ur['killmsg']?>"></td>
<td><input type="text" name="lastword_<?php echo $n?>" size="20" maxlength="60" value="<?php echo $ur['lastword']?>"></td>
<td>
<input type="submit" value="修改" onclick="$('urcmd').value='edit_<?php echo $n?>_<?php echo $ur['uid']?>'">
</td>
<?php } ?>
</tr>
<?php } } ?>
<tr>
<td colspan=2><input type="checkbox" name="user_all" onchange="for(i=0; i<=<?php echo $n?>;i++){if(! $('user_'+i).disabled){if(this.checked==true){$('user_'+i).checked=true}else{$('user_'+i).checked=false}}}">全选</td>
<td colspan=12 style="text-align:center;">
<input type="submit" name="submit" value="封停选中玩家" onclick="$('urcmd').value='ban'">
<input type="submit" name="submit" value="解封选中玩家" onclick="$('urcmd').value='unban'">
<input type="submit" name="submit" value="删除选中玩家" onclick="$('urcmd').value='del'">
</td>
</tr>
<?php } ?>
</table>
<?php } ?>
</form>
<form method="post" name="backtolist" onsubmit="admin.php">
<input type="hidden" name="mode" value="urlist">
<input type="hidden" name="command" value="">
<input type="submit" name="submit" value="返回玩家帐户管理">
</form>
<?php } else { ?>
<form method="post" name="urlist" onsubmit="admin.php">
<input type="hidden" name="mode" value="urlist">
<input type="hidden" name="pagecmd" id="pagecmd" value="">
<input type="hidden" name="urcmd" value="list">
<table class="admin">
<tr>
<th>搜索指定帐户</th>
<th>查看帐户列表</th>
</tr>
<tr>
<td>
条件：
<select name="checkmode">
<option value="username" selected>用户名
<option value="ip">用户IP
</select>
类似
<input size="30" type="text" name="checkinfo" id="checkinfo" maxlength="30" />
</td>
<td>
按：
<select name="urorder">
<option value="groupid" selected>用户所属组
<option value="lastgame">最新游戏
<option value="uid">用户编号
</select>
<select name="urorder2">
<option value="DESC" selected>降序排列
<option value="ASC">升序排列
</select>
</td>
</tr>
<tr>
<td style="text-align:center;"><input style="width:100px;height:30px;" type="submit" value="搜索" onclick="$('pagecmd').value='find';"></td>
<td style="text-align:center;"><input style="width:100px;height:30px;" type="submit" value="查看" onclick="$('pagecmd').value='check';"></td>
</tr>
</table>

</form>
<?php } ?>
