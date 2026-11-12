<?php
if(!defined('IN_GAME')) exit('Access Denied');

// 与现役 addnpc 相同的数据格式；新角色不进入开局常驻 NPC 清单。
$er_base = array('mode'=>1,'num'=>1,'pass'=>'bra','club'=>0,'bid'=>0,'inf'=>'','state'=>1,'rage'=>160,'pose'=>1,'tactic'=>3,'killnum'=>0,'rp'=>400,'gd'=>'f','pls'=>99,'msp'=>800,'lvl'=>110,'skill'=>1500,'money'=>5000,'weps'=>65535,'arb'=>'教头护甲','arbk'=>'DB','arbe'=>400,'arbs'=>600,'arh'=>'护额','arhk'=>'DH','arhe'=>200,'arhs'=>400,'ara'=>'护手','arak'=>'DA','arae'=>200,'aras'=>400,'arf'=>'战靴','arfk'=>'DF','arfe'=>200,'arfs'=>400,'art'=>'棋子徽章','artk'=>'A','arte'=>1,'arts'=>1);
$er_new = array(
	array('铁城','m',60000,6500,5000,'钨钢指虎','WP',900,'n',array('npc_rook_chase','npc_rook_lock'),'车'),
	array('灵翼','m',25000,8000,2000,'灵翼式长柄狙击枪','WGK',1200,'r',array('npc_arsenal','npc_headshot'),'象'),
	array('破石','m',28000,7500,2500,'考古学家的复合弓','WB',1000,'n',array('npc_weak_analysis','npc_weak_break'),'马'),
	array('银锤','f',20000,4500,2200,'银锤式炸药包','WD',800,'d',array('npc_stage_mine','npc_pawn_evolve'),'兵'),
	array('电反','f',30000,5000,3000,'电反式电磁杖','WF',800,'e',array('npc_interference','c7_electric','c7_overload'),'王'),
	array('三步','f',12000,9000,1500,'三步的提灯短匕','WK',900,'n',array('npc_shadow_step','npc_shadow_mark'),'后'),
	array('白凰','f',22000,6000,2000,'白凰的符咒法杖','WF',1100,'uie',array('npc_talisman','npc_nightmare'),'炮'),
	array('绯刹','f',15000,10000,1200,'绯刹的淬毒匕首','WK',1000,'p',array('npc_stealth','npc_blood_poison'),'士'),
);
$anpcinfo[34]=$er_base; $anpcinfo[34]['sub']=array();
foreach($er_new as $er_i=>$er_row) {
	$er_n=array('name'=>$er_row[0],'gd'=>$er_row[1],'mhp'=>$er_row[2],'att'=>$er_row[3],'def'=>$er_row[4],'wep'=>$er_row[5],'wepk'=>$er_row[6],'wepe'=>$er_row[7],'wepsk'=>$er_row[8],'clubskill'=>$er_row[9],'icon'=>'ending_routes/type34_'.$er_i.'.jpg','clbpara'=>array('er_sub'=>$er_i,'er_role'=>'new'),'itm6'=>$er_row[10].'之令牌','itmk6'=>'Z','itme6'=>1,'itms6'=>1,'description'=>'新红杀教头。击破全部八位教头，即可向红暮与蓝凝发起最后的挑战。');
	if($er_i==0) { $er_n['itm1']='背负的巨剑'; $er_n['itmk1']='WK'; $er_n['itme1']=1500; $er_n['itms1']=800; $er_n['itmsk1']='rn'; }
	if($er_i==1) {
		$er_n['club']=98;
		foreach(array(array('灵翼式穿甲弹','WG','n'),array('灵翼式爆裂弹','WD','d'),array('灵翼式燃烧弹','WF','u'),array('灵翼式标准弹','WG','r')) as $er_j=>$er_ammo) { $er_s=$er_j+1; $er_n['itm'.$er_s]=$er_ammo[0]; $er_n['itmk'.$er_s]=$er_ammo[1]; $er_n['itme'.$er_s]=1200; $er_n['itms'.$er_s]=9999; $er_n['itmsk'.$er_s]=$er_ammo[2]; }
	}
	if($er_i==3) { $er_n['itm1']='银锤的压轴烟花'; $er_n['itmk1']='TN'; $er_n['itme1']=3000; $er_n['itms1']=4; }
	if($er_i==4) $er_n['club']=7;
	if($er_i==6) { $er_n['itm1']='白凰的护符'; $er_n['itmk1']='AA'; $er_n['itme1']=0; $er_n['itms1']=25; }
	$anpcinfo[34]['sub'][$er_i]=$er_n;
}

$anpcinfo[34]['sub'][8]=array('name'=>'白凰的符灵幻象','mhp'=>5000,'msp'=>300,'att'=>1200,'def'=>800,'lvl'=>40,'skill'=>300,'money'=>0,'wep'=>'游离符火','wepk'=>'WF','wepe'=>300,'weps'=>65535,'wepsk'=>'w','clubskill'=>array(),'clbpara'=>array('er_sub'=>8,'er_role'=>'illusion'),'icon'=>'ending_routes/type34_6.jpg','description'=>'白凰唤出的符灵幻象，击破后消散，不会留下战利品。');
foreach(array('arb','arh','ara','arf','art') as $er_slot) foreach(array('','k','e','s','sk','para') as $er_suffix) $anpcinfo[34]['sub'][8][$er_slot.$er_suffix]=in_array($er_suffix,array('e','s'),true) ? 0 : '';
unset($er_slot,$er_suffix);

$er_animals=array(
	array('Blasty','f',25000,3500,2000,'合金拳套','WP','npc_honor_fist'),
	array('Dante','m',25000,2800,3500,'守门臂盾','WP','npc_endurance'),
	array('Akane','f',20000,3300,2200,'柔术缠带','WN','npc_joint_lock'),
	array('Holly','f',15000,2400,1800,'雪原狙击枪','WG','npc_fighting_spirit'),
	array('Sandy','f',18000,3000,2000,'九尾折扇','WF','npc_foxfire'),
	array('Albert','m',17000,2600,2500,'机修工具箱','WP','npc_gnaw'),
	array('Kiko','f',16000,3000,1600,'偶然捡到的树枝','WK','npc_luck_charge'),
);
foreach(array(36,37) as $er_type) {
	$anpcinfo[$er_type]=array_merge($er_base,array('pgroup'=>$er_type==37 ? 3 : 0,'pls'=>$er_type==37 ? 121 : 34,'rage'=>$er_type==37 ? 150 : 0,'pose'=>$er_type==37 ? 1 : 7,'arb'=>'动物团外套','art'=>'动物团徽章','sub'=>array()));
	foreach($er_animals as $er_i=>$er_row) {
		$anpcinfo[$er_type]['sub'][$er_i]=array('name'=>$er_row[0],'gd'=>$er_row[1],'mhp'=>$er_row[2],'att'=>$er_row[3],'def'=>$er_row[4],'wep'=>$er_row[5],'wepk'=>$er_row[6],'wepe'=>600,'clubskill'=>array($er_row[7]),'icon'=>'ending_routes/type'.$er_type.'_'.$er_i.'.jpg','clbpara'=>array('er_sub'=>$er_i,'er_role'=>$er_type==36 ? 'hub' : 'boss'),'description'=>$er_type==36 ? '动物大排档的成员。' : '湾城投影中的动物团成员。击破后会削弱善德与恶德。');
	}
}
$anpcinfo[35]=array_merge($er_base,array('pgroup'=>3,'pls'=>128,'mhp'=>50000,'att'=>4500,'def'=>3000,'arb'=>'书院长衣','art'=>'书院印章','clubskill'=>array_column($er_animals,7),'sub'=>array(
	0=>array('name'=>'善德','club'=>13,'wep'=>'拳头','wepk'=>'WN','wepe'=>0,'icon'=>'ending_routes/type35_0.jpg','clbpara'=>array('er_sub'=>0,'er_role'=>'boss'),'itm6'=>'善之印','itmk6'=>'X','itme6'=>1,'itms6'=>1),
	1=>array('name'=>'恶德','club'=>2,'wep'=>'破境之刃','wepk'=>'WK','wepe'=>1000,'icon'=>'ending_routes/type35_1.jpg','clbpara'=>array('er_sub'=>1,'er_role'=>'boss'),'itm6'=>'恶之印','itmk6'=>'X','itme6'=>1,'itms6'=>1),
)));

$er_old=array(
	array('裂堡','m',80000,9000,6000,'液压重拳','WP','n',array('npc_hydraulic','npc_bonearmor','npc_rook_chase')),
	array('弑翼','m',45000,10000,3000,'弹幕织机','WG','r',array('npc_ammo_roulette','npc_lockhead')),
	array('黑岩','m',60000,11000,4000,'死兆·火焰日本刀','WK','u',array('npc_deathomen','npc_blood_draw')),
	array('烬锤','m',50000,8000,3200,'白磷工房','WD','u',array('npc_white_phosphorus')),
	array('断序','f',55000,8500,4500,'序列终止器','WF','e',array('npc_sequence')),
	array('瞬刃','f',35000,12000,2500,'一线天','WK','r',array('npc_afterimage','npc_wire')),
	array('魇凰','f',48000,9000,3200,'梦魇锁链权杖','WF','w',array('npc_nightmare_domain','npc_nightmare')),
	array('血翎','f',38000,13000,2200,'血翎双毒刃','WK','p',array('npc_stealth','npc_blood_poison','npc_blood_draw')),
);
$anpcinfo[39]=array_merge($er_base,array('pgroup'=>3,'horizon'=>2,'arb'=>'旧式教头战甲','art'=>'旧红杀徽章','skill'=>2200,'lvl'=>160,'sub'=>array()));
foreach($er_old as $er_i=>$er_row) {
	$er_n=array('name'=>$er_row[0],'gd'=>$er_row[1],'mhp'=>$er_row[2],'att'=>$er_row[3],'def'=>$er_row[4],'wep'=>$er_row[5],'wepk'=>$er_row[6],'wepe'=>1800,'wepsk'=>$er_row[7],'clubskill'=>$er_row[8],'pls'=>120+$er_i,'icon'=>'ending_routes/type39_'.$er_i.'.jpg','clbpara'=>array('er_sub'=>$er_i,'er_role'=>'old'),'itm1'=>'旧式毕业武装·斩','itmk1'=>'WK','itme1'=>2500,'itms1'=>9999,'itmsk1'=>'rn','itm2'=>'旧式毕业武装·射','itmk2'=>'WG','itme2'=>2500,'itms2'=>9999,'itmsk2'=>'uy','itm3'=>'旧式毕业武装·灵','itmk3'=>'WF','itme3'=>1800,'itms3'=>9999,'itmsk3'=>'eiy','itm4'=>'强效急救剂','itmk4'=>'HB','itme4'=>6000,'itms4'=>6,'description'=>'跨越旧日战场而来的教头，战斗方式凶险异常。');
	if($er_i==6) $er_n['clbpara']['curses']=array('revenge'=>1);
	if($er_i==5) $er_n['clbpara']['curses']=array('legbreak'=>1);
	$anpcinfo[39]['sub'][$er_i]=$er_n;
}

$anpcinfo[40]=array_merge($er_base,array('pgroup'=>3,'horizon'=>2,'pls'=>121,'clubskill'=>array('npc_retro_barrier'),'sub'=>array(
	0=>array('name'=>'林无月','gd'=>'f','mhp'=>11111,'msp'=>200,'att'=>200,'def'=>200,'lvl'=>30,'skill'=>180,'wep'=>'【Neo Axe Remote】','wepk'=>'WG','wepe'=>250,'weps'=>400,'arb'=>'黑色风衣','arbk'=>'DB','arbe'=>50,'arbs'=>100,'arh'=>'金色皇冠','arhk'=>'DH','arhe'=>25,'arhs'=>100,'arf'=>'白金球鞋','arfk'=>'DF','arfe'=>25,'arfs'=>100,'ara'=>'炼金手套','arak'=>'DA','arae'=>25,'aras'=>100,'art'=>'奇怪的挂件','artk'=>'AD','arte'=>20,'arts'=>10,'icon'=>'ending_routes/type40_0.jpg'),
	1=>array('name'=>'传承翼人·神奈备命','gd'=>'f','mhp'=>303303,'msp'=>200,'att'=>200,'def'=>303303,'lvl'=>1303,'skill'=>100,'money'=>55000,'rage'=>0,'pose'=>0,'tactic'=>0,'wep'=>'翼人的羽毛','wepk'=>'WK','wepe'=>500,'weps'=>500,'wepsk'=>'','arb'=>'巫女服','arbk'=>'DB','arbe'=>50,'arbs'=>100,'arh'=>'翼人的羽毛','arhk'=>'DH','arhe'=>25,'arhs'=>100,'ara'=>'翼人的羽毛','arak'=>'DA','arae'=>25,'aras'=>100,'arf'=>'翼人的羽毛','arfk'=>'DF','arfe'=>25,'arfs'=>100,'art'=>'翼人的羽毛','artk'=>'AD','arte'=>20,'arts'=>10,'icon'=>'ending_routes/type40_1.jpg'),
	2=>array('name'=>'钥刃装甲·残留的思念','gd'=>'m','mhp'=>40000,'msp'=>400,'att'=>800,'def'=>800,'lvl'=>64,'skill'=>700,'wep'=>'『虚空装备III』','wepk'=>'WK','wepe'=>3600,'weps'=>360,'wepsk'=>'rd','arb'=>'键刃盔甲B','arbk'=>'DB','arbe'=>500,'arbs'=>400,'arbsk'=>'Aa','arh'=>'键刃盔甲H','arhk'=>'DH','arhe'=>200,'arhs'=>300,'arf'=>'键刃盔甲F','arfk'=>'DF','arfe'=>200,'arfs'=>300,'ara'=>'键刃盔甲A','arak'=>'DA','arae'=>200,'aras'=>300,'art'=>'WAYFINDER','artk'=>'A','arte'=>200,'arts'=>300,'artsk'=>'Hc','icon'=>'ending_routes/type40_2.jpg'),
)));
foreach($anpcinfo[40]['sub'] as $er_i=>&$er_n) {
	$er_n['clbpara']=array('er_sub'=>$er_i,'er_role'=>'extra');
	$er_n['itm1']='贯穿型长枪'; $er_n['itmk1']='WG'; $er_n['itme1']=1800; $er_n['itms1']=9999; $er_n['itmsk1']='ny';
	$er_n['itm2']='高浓度毒素'; $er_n['itmk2']='Y'; $er_n['itme2']=1000; $er_n['itms2']=4;
	$er_n['itm3']='反装甲地雷'; $er_n['itmk3']='TN'; $er_n['itme3']=8000; $er_n['itms3']=4;
	$er_n['description']='时光锚定限制着交战者的力量，旧日的武器与克制关系仍然有效。';
}
unset($er_n);
$anpcinfo[41]=array_merge($er_base,array('pgroup'=>3,'horizon'=>2,'skill'=>3000,'lvl'=>200,'att'=>13000,'def'=>6000,'arb'=>'深蓝黑长外套','art'=>'双Z沙漏徽章','sub'=>array(
	0=>array('name'=>'DIAMOND','gd'=>'m','mhp'=>150000,'pls'=>128,'wep'=>'命运演算矩阵','wepk'=>'WF','wepe'=>2500,'wepsk'=>'wy','clubskill'=>array('npc_fate_record'),'clbpara'=>array('er_sub'=>0,'er_role'=>'final','curses'=>array('betrayal'=>1)),'icon'=>'ending_routes/type41_0.jpg','itm6'=>'命运之钥','itmk6'=>'X','itme6'=>1,'itms6'=>1),
	1=>array('name'=>'OMICRON','gd'=>'f','mhp'=>120000,'pls'=>129,'wep'=>'维度裂隙','wepk'=>'WK','wepe'=>2800,'wepsk'=>'rn','clubskill'=>array('npc_dimension_theft','npc_prefetch'),'clbpara'=>array('er_sub'=>1,'er_role'=>'final'),'icon'=>'ending_routes/type41_1.jpg','itm6'=>'维度之钥','itmk6'=>'X','itme6'=>1,'itms6'=>1),
)));
$anpcinfo[42]=array_merge($er_base,array('mhp'=>8000,'msp'=>300,'att'=>600,'def'=>1500,'lvl'=>90,'rage'=>0,'pose'=>7,'wep'=>'蒸汽蹄','wepk'=>'WP','wepe'=>50,'arb'=>'蒸汽外壳','art'=>'中继天线','clubskill'=>array('npc_steam_guard'),'sub'=>array(
	0=>array('name'=>'蒸汽羊·一号','icon'=>'ending_routes/type42_0.jpg','clbpara'=>array('er_sub'=>0,'er_role'=>'steam')),
	1=>array('name'=>'蒸汽羊·二号','icon'=>'ending_routes/type42_1.jpg','clbpara'=>array('er_sub'=>1,'er_role'=>'steam')),
)));
unset($er_base,$er_new,$er_old,$er_animals,$er_i,$er_j,$er_s,$er_row,$er_type,$er_ammo,$er_n);
