<?php
if(!defined('IN_GAME')) exit('Access Denied');

//灵系福袋 - WF
//BORN FROM WISH 「幻想啼音」
//(0-50)itmlow
//(51-75)itmmedium
//(76-98)itmhigh
//(99-100)antimeta 

$itmlow = <<<EOT
脸,WF,60,12,d,
地狱「炼狱气息」,WF,186,30,n,
伞符「细雪的过客」,WF,162,40,i,
水符「水色绒毯」,WF,115,60,,
秋符「落叶的疾风」,WF,121,55,,
鱼符「鱼的学校」,WF,95,78,,
御经「无限念佛」,WF,72,111,w,
铳符「月之铳」,WF,76,99,,
EOT;

$itmmedium = <<<EOT
魔法「紫云之兆」,WF,187,60,nd,
光符「净化之魔」,WF,354,40,ed,
「信仰之针」,WF,288,80,pd,
神签「犯规结界」,WF,404,80,d,
月见酒「疯狂的九月」,WF,316,75,rwd,
EOT;

$itmhigh = <<<EOT
「信仰之山」,WF,1987,256,Zuwr,
「间断的噩梦」,WF,1532,188,Zikr,
「运钝根的捕物帐」,WF,840,256,Zkf,
EOT;

$antimeta = <<<EOT
随机数之神的摄理,WF,88888,888,ZR,
EOT;
?>