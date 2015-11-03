<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<table align="center">
<?php if(CURSCRIPT == 'register') { ?>
<tr>
<td>Username</td>
<td><input type="text" name="username" size="15" maxlength="15" value=""></td>
<td>maximum 15 bytes, No symbols allowed.</td>
</tr>
<tr>
<td>New Password</td>
<td><input type="password" id="npass" name="npass" size="15" maxlength="24" value=""></td>
<td>maximum 24 bytes, leave blank to ignore</td>
</tr>
<tr>
<td>Repeat Password</td>
<td><input type="password" id="rnpass" name="rnpass" size="15" maxlength="24" value=""></td>
<td>maximum 24 bytes, leave blank to ignore</td>
</tr>
<?php } elseif(CURSCRIPT == 'user') { ?>
<tr>
<td>Username</td>
<td><?php echo $username?></td>
<td> </td>
</tr>
<tr>
<td>Original Password</td>
<td><input type="password" id="opass" name="opass" size="15" maxlength="24" value=""></td>
<td> </td>
</tr>
<tr>
<td>New Password</td>
<td><input type="password" id="npass" name="npass" size="15" maxlength="24" value=""></td>
<td>maximum 24 bytes, leave blank to ignore</td>
</tr>
<tr>
<td>Repeat Password</td>
<td><input type="password" id="rnpass" name="rnpass" size="15" maxlength="24" value=""></td>
<td>maximum 24 bytes, leave blank to ignore</td>
</tr>
<?php } else { ?>
<tr>
<td>Username</td>
<td><?php echo $username?></td>
<td> </td>
</tr>
<tr>
<td>Password</td>
<td>!cannotedit!</td>
<td> </td>
</tr>
<?php } ?>
</table>