<?php if(!defined('IN_GAME')) exit('Access Denied'); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset?>">
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv='expires' content='0'>
<title> Hunger Games </title>
<?php if(isset($extrahead)) { ?>
<?php echo $extrahead?>
<?php } if($allowcsscache) { ?>
<link rel="stylesheet" type="text/css" id="css" href="gamedata/cache/style_20130726.css">
<?php } else { ?>
<style type="text/css" id="css">
<?php include template('css'); ?>
</style>
<?php } ?>
<script type="text/javascript" src="include/common.js"></script>
<script type="text/javascript" src="include/game20130526.js"></script>
<script type="text/javascript" src="include/json.js"></script>
</head>
<BODY 
<?php if(CURSCRIPT == 'game' && $hotkeyon) { ?>
onkeydown="hotkey(event);"
<?php } ?>
>
<div class="title" >Hunger Games</div>

<div class="headerlink" >
<a href="index.php">>>Index</a>
<?php if(isset($cuser) && isset($cpass)) { ?>
<a href="user_profile.php">>>User Data</a>
<?php } else { ?>
<a href="register.php">>>Register</a>
<?php } ?>
<a href="game.php">>>Game</a>
<a href="map.php">>>Map</a>
<a href="news.php">>>News</a>
<a href="alive.php">>>Survivors</a>
<a href="winner.php">>>Winners</a>
<a href="rank.php">>>Ranking</a>
<a >>>Help</a>
<a href="admin.php">>>Admin</a>
<a href="http://76573.org/" target="_blank">>>Forum</a>
<a href="<?php echo $homepage?>" target="_blank">>>Website</a>
<!--[if lt IE 7]> <div style=' clear: both; height: 59px; padding:0 0 0 15px; position: relative;'> <a href="http://windows.microsoft.com/en-US/internet-explorer/products/ie/home?ocid=ie6_countdown_bannercode"><img src="http://storage.ie6countdown.com/assets/100/images/banners/warning_bar_0027_Simplified Chinese.jpg" border="0" height="42" width="820" alt="You are using an outdated browser. For a faster, safer browsing experience, upgrade for free today." /></a></div> <![endif]-->
<?php if((CURSCRIPT == 'game' && $pls=='34' && $gamestate<50)) { } ?>
</div>
<div>
