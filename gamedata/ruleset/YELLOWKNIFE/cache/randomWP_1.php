<?php
if(!defined('IN_GAME')) exit('Access Denied');

//殴系福袋 - WP
//FUTURE BASH 「打出未来」
//(0-50)itmlow
//(51-75)itmmedium
//(76-98)itmhigh
//(99-100)antimeta 

$itmlow = <<<EOT
脸,WP,60,12,,
旋风锤,WP,220,20,n,
影月锤,WP,180,40,w,
爆裂锤,WP,160,80,,
英雄棍棒,WP,120,15,,
血怒棍棒,WP,100,10,,
象牙拳套,WP,100,100,r,
风子谨制木海星,WCP,90,90,Zr,
EOT;

$itmmedium = <<<EOT
【北斗百裂拳】,WP,200,30,nd,
【正义之锤】,WP,460,20,e,
【守护者之刺】,WP,480,40,p,
【愚钝之斧】,WP,500,80,,
【巨大号角】,WP,450,15,rw,
【完美风暴】棍棒,WP,333,10,r,
【黄金狂岚】,WP,480,200,rui,
阿耶尼的巨斧,WKP,180,180,Zrn,
EOT;

$itmhigh = <<<EOT
「金霜协奏曲」,WP,2222,256,Zuwdr,
「龙怒」,WP,2777,188,Zfd,
「宁静」,WCP,1555,256,Zkw,
「清晨恩典」,WP,3600,158,Zikdr,
神之棍棒,WP,4800,480,Zkfd,
EOT;

$antimeta = <<<EOT
随机数之神的棍棒,WP,88888,888,ZRx,
EOT;
?>