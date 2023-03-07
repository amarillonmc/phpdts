<?php

/*Game system settings*/

//文件验证字符串
$checkstr = "<? if(!defined('IN_GAME')) exit('Access Denied'); ?>\n";
//是否允许游客进入插件。0=不允许，1=允许
$isLogin = 1;
//是否缓存css文件。0=不缓存，1=缓存
$allowcsscache = 1;
//游戏版本
$gameversion = 'GE942 ～TORONTO';
//站长留言
//$systemmsg = '';
//游戏开始方式 0=后台手动开始，1=每天固定时间开始，2=上局结束后，间隔固定小时后的整点开始，3=上局结束后，间隔固定分钟开始
$startmode = 3;
//游戏开始的小时，如果，如果$startmode = 1,表示开始时间0~23，如果$startmode = 2，表示间隔小时，>0，如果$startmode = 3，表示间隔分钟，>0
$starthour = 3;
//游戏开始的分钟数，范围1~59
$startmin = 1;
//游戏所用配置文件
$gamecfg = 1;


//同ip限制激活人数。0为不限制
$iplimit = 0;
//头像数量（男女相同）
$iconlimit = 20;
//游戏进行状况显示条数
$newslimit = 50;
//生存者显示条数
$alivelimit = 30;
//玩家排行榜显示条数
$ranklimit = 20;
//历史优胜者显示条数
$winlimit = 50;
//枪声间隔时间(秒)
$noiselimit = 300;
//失焦冻结时间，单位分钟
//$lostfocusmin = 10;

//游戏内聊天信息显示条数
$chatlimit = 50;
//聊天信息更新时间(单位:毫秒)
$chatrefresh = 15000;
//游戏进行中是否显示聊天。0为不显示，数字为显示条数
$chatinnews = 50;

//开启gzip压缩功能？0为不开启，1为开启
//$gzipcompress=0;

/*template settings*/
//模板编号。默认为1
define('STYLEID', '1');
define('TEMPLATEID', '1');
define('TPLDIR', './templates/default');

?>