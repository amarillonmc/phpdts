<?php if(!defined('IN_GAME')) exit('Access Denied'); include template('header'); ?>
<div class="subtitle" align="center">账号注册</div>

<p align="center">
<span id="notice"></span>
<span id="info" class="yellow"></span>
</p>
<center>
<form method="post" id="reg" name="reg">
<input type="hidden" name="cmd" value="post_register">
<span class ="yellow">账户密码</span>
<?php include template('userbasicdata'); ?>
<br />
<span class ="yellow">个性化资料</span>
<?php include template('usergdicon'); ?>
<br />
<?php include template('userwords'); ?>
<br />
<div id="postreg">
<input type="submit" id="post" onClick="postCmd('reg','register.php');return false;" value="提交">
<input type="reset" id="reset" name="reset" value="重设">
</div>
</form>
</center>
<br />
<?php include template('footer'); ?>
