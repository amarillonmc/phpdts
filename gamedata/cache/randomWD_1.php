<?php
if(!defined('IN_GAME')) exit('Access Denied');

//爆系福袋 - WD
//SUDDEN BREAKER 「突然爆裂」
//(0-50)itmlow
//(51-75)itmmedium
//(76-98)itmhigh
//(99-100)antimeta 

$itmlow = <<<EOT
脸,WD,60,12,d,
晨辉爆弹,WD,221,30,dn,
日蚀机雷,WD,181,40,dw,
光子火箭,WDG,161,60,d,
离子播散器,WD,121,55,d,
氢气地雷,TN,600,1,,
破阵地雷,TN,300,5,,
连环地雷,TN,150,10,,
EOT;

$itmmedium = <<<EOT
【阵列撕裂者】,WD,200,60,nd,
【震撼火箭弹】,WD,460,40,ed,
【彗星发射器】,WD,480,80,pd,
【猎头炸药】,WD,502,80,d,
【灾难尖刺】,WD,323,75,rwd,
【怨灵之瓶】,TN,2022,3,,
【单人用娱乐火箭】,TN,2503,2,,
【汉诺的崇高力量】,TN,3333,3,Z,
EOT;

$itmhigh = <<<EOT
「喧嚣叙事曲」,WD,2222,256,Zuwdr,
「升天」,WD,2777,188,Zikdr,
「曳光」,WD,1555,256,Zkfd,
「人生重来箱」,TN,9997,8,Z,
「菁英宅之怒」,TN,1333337,1,Z,
EOT;

$antimeta = <<<EOT
随机数之神的震撼,WD,88888,888,ZRd,
随机数之神的恶戏,TN8,8,88888,Z,
EOT;
?>