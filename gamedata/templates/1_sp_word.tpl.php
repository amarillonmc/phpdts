<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
想改变什么话？（留空为不修改）<br>

<input type="hidden" name="mode" value="chgword">
<div>座右铭 : <input size="30" type="text" name="newmotto" maxlength="60" value="<?php echo $motto?>"></div>
<br />写下代表你性格的一句话，30个字以内。
<div>留  言 : <input size="30" type="text" name="newkillmsg" maxlength="60" value="<?php echo $killmsg?>"></div>
<br />写下你杀死对手的留言，30个字以内。
<div>遗  言 : <input size="30" type="text" name="newlastword" maxlength="60" value="<?php echo $lastword?>"></div>
<br />写下你不幸被害时的台词，30个字以内。
<br />
<br />
<input type="button" class="cmdbutton" name="submit" value="提交" onclick="postCmd('gamecmd','command.php');this.disabled=true;">