<?php
if(!defined('IN_GAME')) exit('Access Denied');

// Initial catalog only. Once installed, the shopmng admin page owns prices and availability.
$fcg_shop_seed = array(
	array(1,'fcg_pack','每日试手包','斯诺：今天也来陪小家伙们玩吧！每包一张普通未升级卡。',0,'fcg_pack',array('upgraded'=>false,'rare'=>false),5,0),
	array(2,'fcg_pack','表象补充包','露比：先看清表面，再谈追根究底。非稀有未升级卡随机一张。',10,'fcg_pack',array('upgraded'=>false,'rare'=>false),0,0),
	array(3,'fcg_pack','白色倾向包','器物与锚点。白色全池随机一张。',30,'fcg_pack',array('color'=>'W'),0,0),
	array(4,'fcg_pack','红色倾向包','武技与冲动。红色全池随机一张。',30,'fcg_pack',array('color'=>'R'),0,0),
	array(5,'fcg_pack','黄色倾向包','谋略与机关。黄色全池随机一张。',30,'fcg_pack',array('color'=>'Y'),0,0),
	array(6,'fcg_pack','绿色倾向包','生机与侵蚀。绿色全池随机一张。',30,'fcg_pack',array('color'=>'G'),0,0),
	array(7,'fcg_pack','蓝色倾向包','灵子与秘仪。蓝色全池随机一张。',30,'fcg_pack',array('color'=>'B'),0,0),
	array(8,'fcg_pack','深潜重构包','露比：已经升级好的记忆，不提供反悔服务。非稀有已升级卡随机一张。',60,'fcg_pack',array('upgraded'=>true,'rare'=>false),0,0),
	array(9,'fcg_pack','核心残响包','斯诺：这里面的小涂鸦，好像特别认真呢。稀有全池随机一张。',150,'fcg_pack',array('rare'=>true),0,0),
	array(40,'fcg_pack','演武表象包（Vol.2）','红杀演武：40 张未升级记忆等概率抽取一张。',10,'fcg_pack',array('series'=>'vol2','upgraded'=>false),0,0),
	array(41,'fcg_pack','演武深潜包（Vol.2）','红杀演武：40 张已升级记忆等概率抽取一张。',60,'fcg_pack',array('series'=>'vol2','upgraded'=>true),0,0),
	array(42,'fcg_pack','教头残响包（Vol.2 稀有）','红杀演武：30 张稀有记忆等概率抽取一张，含传奇帮手。',150,'fcg_pack',array('series'=>'vol2','rare'=>true),0,0),
	array(43,'fcg_pack','万象表象包（Vol.3）','常磐万象与电波狂想：40 张未升级记忆等概率抽取一张。',10,'fcg_pack',array('series'=>'vol3','upgraded'=>false),0,0),
	array(44,'fcg_pack','万象深潜包（Vol.3）','常磐万象与电波狂想：40 张已升级记忆等概率抽取一张。',60,'fcg_pack',array('series'=>'vol3','upgraded'=>true),0,0),
	array(45,'fcg_pack','狂想残响包（Vol.3 稀有）','常磐万象与电波狂想：26 张稀有记忆等概率抽取一张。',150,'fcg_pack',array('series'=>'vol3','rare'=>true),0,0),
	array(20,'title','五色余烬','装备后获得「调律」：切换五色姿态，探索充能后发动不同效果。',500,'title',array('title'=>9101),0,1),
	array(21,'title','因果断','装备后获得「苦味」：限次截断重创并反噬攻击者。',2000,'title',array('title'=>9102),0,1),
	array(22,'title','盲眼荷官','装备后获得「哑弹」：行动积蓄概率偏移，化解危急攻击。',4000,'title',array('title'=>9103),0,1),
	array(23,'title','记忆之链','装备后获得「抽牌／装填」：三张微缩手牌，场外使用或开幕倾泻。',6000,'title',array('title'=>9104),0,1),
	array(24,'title','阿卡夏的共犯','装备后获得「模因拓印」：复现灵魂绑定的幻影器物，或覆唱上一记重击。',15000,'title',array('title'=>9105),0,1),
	array(30,'title','✧霓虹失眠✧','名字在霓虹色带中流动。只有视觉效果。',50,'title',array('title'=>9111),0,1),
	array(31,'title','★星屑过载★','名字闪烁着灿金星屑。只有视觉效果。',80,'title',array('title'=>9112),0,1),
	array(32,'title','≋量子掉帧≋','名字偶尔在青紫残影间错位。只有视觉效果。',100,'title',array('title'=>9113),0,1),
);
return $fcg_shop_seed;
