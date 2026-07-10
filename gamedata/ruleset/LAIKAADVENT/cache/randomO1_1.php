<?php
if(!defined('IN_GAME')) exit('Access Denied');

//杂项1福袋 - O1
//防具，经验书，强化药，补给
//SPECIAL TECH 「特选科技」
//(0-50)itmlow
//(51-75)itmmedium
//(76-98)itmhigh
//(99-100)antimeta 

$itmlow = <<<EOT
脸,HB,60,998,,
灼眼头盔,DH,601,15,ZUC,
漂水盔甲,DB,602,15,ZEG,
疾风手套,DA,603,15,ZKq,
裂地跑鞋,DF,604,15,ZIW,
奇特数据,VV,101,1,,
勇气数据,MA,151,1,,
防卫数据,MD,142,3,,
EOT;

$itmmedium = <<<EOT
【Poini Kune的死库水】,DB,5,5,Bb,
【Madoka的死库水】,DB,10,10,AB,
【Erul Tron的泳装】,DB,30,30,b,
【空羽亚乃亚的泳装】,DB,30,30,a,
【Tita Nium的泳装】,DB,25,25,Aa,
【Emon 5的沙滩短裤】,DB,99999,1,,
奇特数据,VV,101,2,,
勇气数据,MA,151,2,,
防卫数据,MD,142,6,,
大脸,HB,300,998,,
EOT;

$itmhigh = <<<EOT
殴系速成书,VP,251,2,,
斩系速成书,VK,252,2,,
射系速成书,VG,253,2,,
投系速成书,VC,254,2,,
爆系速成书,VD,255,2,,
灵系速成书,VF,256,2,,
蝙蝠侠速成书,VV,1234,1,,
超人药,ME,1234,1,,
大圆脸,HB,666,998,,
EOT;

$antimeta = <<<EOT
随机数之神的庇佑,Z,1,1,Zz
EOT;
?>