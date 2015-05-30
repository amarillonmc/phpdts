<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
想购买什么物品？<br><br>
<input type="hidden" name="mode" value="shop">
<input type="hidden" name="shoptype" value="<?php echo $shop?>">
<input type="radio" name="command" id="menu" value="menu" checked><a onclick=sl('menu'); href="javascript:void(0);" onmouseover="status=' ';return true;">离开商店</a><br>
<input type="radio" name="command" id="shop" value="shop"><a onclick=sl('shop'); href="javascript:void(0);" onmouseover="status=' ';return true;">返回列表</a><br><br>
<?php if(is_array($itemdata)) { foreach($itemdata as $idata) { if($idata['sid']) { ?>
<a onclick=sl("<?php echo $idata['sid']?>"); href="javascript:void(0);" onmouseover="status=' ';return true;"><input type="radio" name="command" id="<?php echo $idata['sid']?>" value="<?php echo $idata['sid']?>"><?php echo $idata['item']?>/<?php echo $idata['itmk_words']?>/<?php echo $idata['itme']?>/<?php echo $idata['itms']?>
<?php if($idata['itmsk_words']) { ?>
/<?php echo $idata['itmsk_words']?>
<?php } ?>
 【价:<?php echo $idata['price']?>,数:<?php echo $idata['num']?>】</a><br>
<?php } } } if($shop==1||$shop==2||$shop==6||$shop==7||$shop==8||$shop==10||$shop==11||$shop==12) { ?>
请输入购买数量：<input type="text" name="buynum" value="1" size="5" maxlength="5">
<?php } else { ?>
<input type="hidden" name="buynum" value="1">
<?php } ?>
<br><br>
<input type="button" class="cmdbutton" name="submit" value="提交" onclick="postCmd('gamecmd','command.php');this.disabled=true;">