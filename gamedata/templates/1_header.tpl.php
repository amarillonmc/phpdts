<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset?>">
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv='expires' content='0'>
<title> ACFUN 大 逃 杀 </title>
<?php if(isset($extrahead)) { ?>
<?php echo $extrahead?>
<?php } if($allowcsscache) { ?>
<link rel="stylesheet" type="text/css" id="css" href="gamedata/cache/style_<?php echo STYLEID?>.css">
<?php } else { ?>
<style type="text/css" id="css">
<?php include template('css'); ?>
</style>
<?php } ?>
</style>
<script type="text/javascript" src="include/common.js"></script>
<script type="text/javascript" src="include/game.js"></script>
<script type="text/javascript" src="include/json.js"></script>
</head>
<BODY 
<?php if(CURSCRIPT == 'game' && $hotkeyon) { ?>
onkeydown="hotkey(event);"
<?php } ?>
>
<div class="title" >ACFUN 大 逃 杀</div>

<div class="headerlink" >
<a href="index.php">>>首页</a>
<?php if(isset($cuser) && isset($cpass)) { ?>
<a href="user_profile.php">>>帐号资料</a>
<?php } else { ?>
<a href="register.php">>>账号注册</a>
<?php } ?>
<a href="game.php">>>进入游戏</a>
<a href="map.php">>>战场地图</a>
<a href="news.php">>>进行状况</a>
<a href="alive.php">>>当前幸存</a>
<a href="winner.php">>>历史优胜</a>
<a href="rank.php">>>玩家排行</a>
<a href="help.php">>>游戏帮助</a>
<a href="admin.php">>>游戏管理</a>
<a href="http://76573.org/" target="_blank">>>官方讨论区</a>
<a href="<?php echo $homepage?>" target="_blank">>>官方网站</a>
<!--[if lt IE 7]> <div style=' clear: both; height: 59px; padding:0 0 0 15px; position: relative;'> <a href="http://windows.microsoft.com/en-US/internet-explorer/products/ie/home?ocid=ie6_countdown_bannercode"><img src="http://storage.ie6countdown.com/assets/100/images/banners/warning_bar_0027_Simplified Chinese.jpg" border="0" height="42" width="820" alt="You are using an outdated browser. For a faster, safer browsing experience, upgrade for free today." /></a></div> <![endif]-->


</div>
<div>
