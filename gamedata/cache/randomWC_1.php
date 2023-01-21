<?php
if(!defined('IN_GAME')) exit('Access Denied');

//投系福袋 - WC
//CATCH THEM ALL 「全部收集」
//(0-50)itmlow
//(51-75)itmmedium
//(76-98)itmhigh
//(99-100)antimeta 

$itmlow = <<<EOT
脸,WC,60,12,,
高级球,WC,221,34,n,
超级球,WC,183,46,w,
豪华球,WC,166,68,,
快速球,WC,127,55,,
黑暗球,WC,175,14,,
计时球,WC,163,90,r,
究极球,WC,91,183,Zr,
EOT;

$itmmedium = <<<EOT
铁兽战线 徒花之费莉吉特 Ｌ2,WC,204,60,nd,
铁兽战线 凶鸟之施莱格 Ｌ3,WC,460,40,e,
电子龙·凯旋 Ｌ2,WC,446,50,p,
秘旋谍-双螺旋特工 Ｌ2,WC,502,70,,
海晶少女 妙晶心 Ｌ2,WC,323,75,rw,
海晶少女 奶嘴海葵 Ｌ2,WC,343,70,r,
淘气仙星·霍莉安琪儿 Ｌ2,WC,480,65,rui,
真超级量子机神王 烈辉大炎磁 Ｌ3,WC,520,30,Zrn,
EOT;

$itmhigh = <<<EOT
闭锁世界的冥神 Ｌ5,WC,2222,256,Zuwdr,
铁兽式强袭机动兵装改牛头伯劳2 Ｌ5,WC,2777,188,Zfd,
防火龙·暗流体 Ｌ5,WC,1555,256,Zkw,
前托枪管龙 Ｌ5,WC,3877,158,Zikdr,
电子界到临者＠火灵天星 Ｌ6,WC,4788,480,Zkfd,
EOT;

$antimeta = <<<EOT
随机数之神的神力,WC,88888,888,ZR,
EOT;
?>