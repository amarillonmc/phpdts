<?php
if(!defined('IN_GAME')) exit('Access Denied');

//斩系福袋 - WK
//PAVE THE WAY 「斩开前路」
//(0-50)itmlow
//(51-75)itmmedium
//(76-98)itmhigh
//(99-100)antimeta 

$itmlow = <<<EOT
脸,WK,60,12,,
死亡之吻,WK,221,20,n,
染血匕首,WK,181,40,w,
契约短剑,WK,161,80,,
失意背刺,WK,121,15,,
巨骨剑,WK,171,10,,
瓦明威,WK,161,100,r,
微缩斧剑,WKP,90,90,Zr,
EOT;

$itmmedium = <<<EOT
【狂暴凶刃】,WK,200,30,nd,
【紫色β大刀】,WK,460,20,e,
【翡翠骑士】,WK,480,40,p,
【念力刃】,WK,502,80,,
【花好月圆】,WK,478,15,rw,
【良辰美景】,WK,343,10,r,
【克拉姆·索莱斯】,WK,480,200,rui,
万法破灭之符,WFK,180,180,Zrn,
EOT;

$itmhigh = <<<EOT
「碧海船歌」,WK,2222,256,Zuwdr,
「翼展」,WK,2777,188,Zfd,
「安谧」,WGK,1555,256,Zkw,
「午前许愿」,WK,3877,158,Zikdr,
神之圣剑,WK,4788,480,Zkfd,
EOT;

$antimeta = <<<EOT
随机数之神的圣剑,WK,88888,888,ZRx,
EOT;
?>