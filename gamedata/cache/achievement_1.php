<?php
if(!defined('IN_GAME')) exit('Access Denied');

# 成就登记列表：
$ach_list = Array
(
	0 => Array(
		# 成就各阶段名称
		'name' => Array('永恒世界的住人','幻想世界的往人','永恒的覆唱'),
		# 达成阶段成就的头衔奖励
		'title' => Array('','幻想','流星'),
		# 达成阶段成就的积分奖励
		'c1' => Array(0,200,700),
		# 达成阶段成就的切糕奖励
		'c2' => Array(10,0,0),
	),
	1 => Array(
		'name' => Array('清水池之王'),
		'title' => Array('KEY男'),
		'c1' => Array(30),
		'c2' => Array(16),
	),
	2 => Array(
		'name' => Array('Run With Wolves','Day Game','Thousand Enemies'),
		'title' => Array('','二度打','G.D.M'),
		'c1' => Array(10,500,0),
		'c2' => Array(0,0,200),
	),

	3 => Array(
		'name' => Array('脚本小子','黑客','幻境解离者？'),
		'title' => Array('','黑客','最后一步'),
		'c1' => Array(0,200,500),
		'c2' => Array(5,0,15),
		),
	4 => Array(
		'name' => Array('冒烟突火','红杀将军'),
        'title' => Array('','越红者'),
        'c1' => Array(50,0),
        'c2' => Array(75,0),
	),
	5 => Array(
		//'name' => Array('自作孽不可活'),
        //'title' => Array(''),
        //'c1' => Array(10),
        //'c2' => Array(5),
	),
	6 => Array(
		//'name' => Array('野生君的邂逅'),
        //'title' => Array(''),
        //'c1' => Array(10),
        //'c2' => Array(15),
	),
	7 => Array(
		//'name' => Array('野生君的暗恋'),
        //'title' => Array(''),
        //'c1' => Array(50),
        //'c2' => Array(120),
	),
	8 => Array(
		//'name' => Array('这么死也值了！'),
        //'title' => Array(''),
        //'c1' => Array(10),
        //'c2' => Array(10),
	),
	9 => Array(
		//'name' => Array('对下雷者的“大打击”'),
        //'title' => Array(''),
        //'c1' => Array(30),
        //'c2' => Array(15),
	),
	10 => Array(
		//'name' => Array('救命的迎击'),
        //'title' => Array(''),
        //'c1' => Array(15),
        //'c2' => Array(15),
	),
	11 => Array(
		//'name' => Array('真·地雷磁铁'),
        //'title' => Array('')
        //'c1' => Array(100),
        //'c2' => Array(100),
	),
	12 => Array(
		//'name' => Array('DeathNoter'),
        //'title' => Array(''),
        //'c1' => Array(30),
        //'c2' => Array(30),
	),
	13 => Array(
		'name' => Array('深度冻结','跨过彩虹'),
        'title' => Array('','跨过彩虹'),
        'c1' => Array(150,0),
        'c2' => Array(250,0),
	),
	14 => Array(
		'name' => Array('篝火的引导','世界的树形图','地=月'),
        'title' => Array('','树形图','TERRA'),
        'c1' => Array(0,200,700),
        'c2' => Array(10,0,0),
	),
	15 => Array(
		'name' => Array('不屈的生命','那种话最讨厌了','明亮的未来'),
        'title' => Array('','素描本','未来战士'),
        'c1' => Array(0,200,700),
        'c2' => Array(10,0,0),
	),
	16 => Array(
		'name' => Array('只是运气好而已'),
        'title' => Array('生存者'),
        'c1' => Array(150),
        'c2' => Array(0),
	),
	17 => Array(
		'name' => Array('麻烦制造机'),
        'title' => Array('叶子钦定！'),
        'c1' => Array(0),
        'c2' => Array(100),
	),
	18 => Array(
		'name' => Array('最后的荣光'),
        'title' => Array('最后的荣光'),
        'c1' => Array(500),
        'c2' => Array(0),
	),
	19 => Array(
		'name' => Array('奇迹的篝火'),
        'title' => Array('奇迹的篝火'),
        'c1' => Array(1000),
        'c2' => Array(1000),
	),
	20 => Array(
		'name' => Array('寻星急袭'),
        'title' => Array('寻星者'),
        'c1' => Array(268),
        'c2' => Array(263),
	),
	21 => Array(
		'name' => Array('权限【哔】的最期'),
        'title' => Array('寂静洪流'),
        'c1' => Array(233),
        'c2' => Array(233),
	),
	22 => Array(
		'name' => Array('233MAX'),
        'title' => Array('l33t'),
        'c1' => Array(2333),
        'c2' => Array(0),
	),
	23 => Array(
		'name' => Array('真名解放'),
        'title' => Array('赌玉狂魔'),
        'c1' => Array(0),
        'c2' => Array(888),
	),
	24 => Array(
		'name' => Array('逆推'),
        'title' => Array('时代眼泪'),
        'c1' => Array(211),
        'c2' => Array(299),
	),
	25 => Array(
		'name' => Array('一尸两命'),
        'title' => Array('卸腿者'),
        'c1' => Array(111),
        'c2' => Array(333),
	),
	26 => Array(
		'name' => Array('正直者之死'),
        'title' => Array('吉祥物'),
        'c1' => Array(1),
        'c2' => Array(111),
	),
	27 => Array(
		'name' => Array('秋后算账','报仇雪恨','血洗英灵殿'),
        'title' => Array('','','替天行道'),
        'c1' => Array(0,300,500),
        'c2' => Array(10,0,0),
	),
	28 => Array(
		'name' => Array('烈火疾风'),
        'title' => Array('神触'),
        'c1' => Array(250),
        'c2' => Array(0),
	),
	29 => Array(
		'name' => Array('及时补给','衣食无忧','奥义很爽'),
        'title' => Array('','美食家','补给掠夺者'),
        'c1' => Array(0,0,0),
        'c2' => Array(5,50,200),
	),
	30 => Array(
		'name' => Array('饥不择食','尝百草','吞食天地'),
        'title' => Array('','神农','贝爷'),
        'c1' => Array(0,0,0),
        'c2' => Array(5,50,200),
	),
	31 => Array(
		'name' => Array('Return to Sender'),
        'title' => Array('R.T.S'),
        'c1' => Array(0),
        'c2' => Array(0),
	),
	32 => Array(
		'name' => Array('0xFFFFFFFFFFFFFFFF','kernel on chessboard'),
        'title' => Array('LOOP'),
        'c1' => Array(0),
        'c2' => Array(0),
	),
	33 => Array(
		'name' => Array('诅咒之刃'),
        'title' => Array('剑圣'),
        'c1' => Array(0),
        'c2' => Array(522),
	),
	34 => Array(
		'name' => Array('逃避可耻？','但它有用！','直面现实','逃脱大师'),
        'title' => Array('实用主义者','现实主义者','脚底抹油'),
        'c1' => Array(10,50,100),
        'c2' => Array(5,50,200),
	),
	35 => Array(
		'name' => Array('试试看殴系吧！','热血的机师','殴系爱好者'),
        'title' => Array('','热血机师','殴系爱好者'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	36 => Array(
		'name' => Array('试试看斩系吧！','苍蓝之光','斩系爱好者'),
        'title' => Array('','苍蓝之光','斩系爱好者'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	37 => Array(
		'name' => Array('来精进斩系吧！','合二为一','钥刃大师'),
        'title' => Array('','合二为一','钥刃大师'),
		'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	38 => Array(
		'name' => Array('试试看射系吧！','勇闯仙境','射系爱好者'),
        'title' => Array('','勇闯仙境','射系爱好者'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	39 => Array(
		'name' => Array('试试看重枪吧！','黑洞边缘','重枪爱好者'),
        'title' => Array('','黑洞边缘','重枪爱好者'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	40 => Array(
		'name' => Array('试试看游戏王吧！','光的道路','决斗者'),
        'title' => Array('','光的道路','决斗者'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	41 => Array(
		'name' => Array('进行加速同调吧！','加速同调','聚集的祈愿'),
        'title' => Array('','加速同调','聚集的祈愿'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	42 => Array(
		'name' => Array('试试看投系吧！','平和之心','投系爱好者'),
        'title' => Array('','平和之心','投系爱好者'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	43 => Array(
		'name' => Array('试试看爆系吧！','红烧天堂','爆系爱好者'),
        'title' => Array('','红烧天堂','爆系爱好者'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	44 => Array(
		'name' => Array('来精进爆系吧！','★刷刷刷★','★啪啪啪★'),
        'title' => Array('','★刷刷刷★','★啪啪啪★'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	45 => Array(
		'name' => Array('试试看灵系吧！','皇家烈焰','灵系爱好者'),
        'title' => Array('','皇家烈焰','灵系爱好者'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	46 => Array(
		'name' => Array('来精进灵系吧！','五行大师','贤者之石'),
        'title' => Array('','五行大师','贤者之石'),
        'c1' => Array(0,100,350),
        'c2' => Array(10),
	),
	47 => Array(
		'name' => Array('知己知彼！','知地利','知人和'),
        'title' => Array('','知地利','知人和'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	48 => Array(
		'name' => Array('感受一下混沌吧！','混沌爱好者','混沌的深渊'),
        'title' => Array('','混沌爱好者','混沌的深渊'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	49 => Array(
		'name' => Array('超级ＫＥＹ爱好者','键·四季赞歌'),
        'title' => Array('','键·四季赞歌'),
        'c1' => Array(0,700),
        'c2' => Array(100,0),
	),
	50 => Array(
		'name' => Array('人，能够挑战神吗？','★一发逆转！★'),
        'title' => Array('','★一发逆转！★'),
        'c1' => Array(0,700),
        'c2' => Array(100,0),
	),
	51 => Array(
		'name' => Array('究极的灵魂','『ＥＸ』'),
        'title' => Array('','『ＥＸ』'),
        'c1' => Array(0,700),
        'c2' => Array(100,0),
	),
	52 => Array(
		'name' => Array('真正的决斗者','◎胜利之光◎'),
        'title' => Array('','『ＥＸ』'),
        'c1' => Array(0,700),
        'c2' => Array(100,0),
	),
	53 => Array(
		'name' => Array('来打钉子吧！','棍棒爱好者','无情打钉者'),
        'title' => Array('','棍棒爱好者','无情打钉者'),
        'c1' => Array(0,0,0),
        'c2' => Array(5,50,200),
	),
	54 => Array(
		'name' => Array('来磨刀吧！','磨刀爱好者','无情磨刀者'),
        'title' => Array('','磨刀爱好者','无情磨刀者'),
        'c1' => Array(0,0,0),
        'c2' => Array(5,50,200),
	),
	55 => Array(
		'name' => Array('来打补丁吧！','补丁爱好者','无情补丁'),
        'title' => Array('','补丁爱好者','无情补丁'),
        'c1' => Array(0,0,0),
        'c2' => Array(5,50,200),
	),
	56 => Array(
		'name' => Array('种火？那是啥？','是都市传说。','除错大师'),
        'title' => Array('','都市传说','除错大师'),
        'c1' => Array(0,100,250),
        'c2' => Array(10,0,0),
	),
	57 => Array(
		'name' => Array('外来的神秘','风驰电掣','暴雷骤雨'),
        'title' => Array('','风驰电掣','暴雷骤雨'),
        'c1' => Array(0,100,250),
        'c2' => Array(10,0,0),
	),
	999 => Array(''),
);
//Hi
?>