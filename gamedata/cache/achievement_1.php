<?php
if(!defined('IN_GAME')) exit('Access Denied');

# 成就大类列表：
$ach_type = Array
(
	'end' => Array(
		'name' => '结局成就',
		'desc' => '<font color="olive">这里是与游戏结局相关的成就。<br>
		虽说有些看起来帮助中没提到，但找寻它们也正是这游戏的醍醐味之一。<br></font>',
		'ach' => Array(16,34,17,18,19),
		//'ach' => Array(16,34,17,18,100,101,102,19),
	),
	'battle' => Array(
		'name' => '战斗成就',
		'desc' => '<font color="olive">这里是与击破特定NPC相关的成就。<br>
		打倒他们来证明自己吧！<br></font>',
		'ach' => Array(4,13,22,23,25,20,21,24,26,56,57,255),
	),
	'mixitem' => Array(
		'name' => '合成成就',
		'desc' => '<font color="olive">这里是与合成各种物品相关的成就。<br>
		如果看合成表觉得麻烦，只以这些物品为目标似乎也不错。<br></font>',
		'ach' => Array(0,14,15,33,35,36,37,38,39,40,41,42,43,44,45,46,47,48),
	),
	'lifetime' => Array(
		'name' => '生涯成就',
		'desc' => '<font color="olive">这里是记录了你在这个游戏中的积累相关的成就。<br>
		呜呼——玩家们出发了……<br></font>',
		'ach' => Array(2,3,27,29,30,53,54,55,255),
	),
	'challenge' => Array(
		'name' => '挑战成就',
		'desc' => '<font color="olive">这里是与特定游戏中挑战相关的成就。<br>
		虽然颇为浮云，但毕竟山就在那里。<br></font>',
		'ach' => Array(1,28,31,32,49,50,51,52,255),
	),
);

# 成就登记列表：
$ach_list = Array
(
	/*'example' => Array(
		//成就完成时所处阶段（必填）
		'lvl' => 3, 
		//这是一个隐藏成就吗？（隐藏成就在完成前不会显示在成就界面内）
		'hidden' => 0,
		//各阶段成就名（必填）（PS：完成阶段名可填可不填，填了会显示，不填会显示前一个阶段的名字）
		'name' => Array('阶段0名','阶段1名','阶段2名','阶段完成'), 
		//各阶段状态名（选填，不填此项会应用默认状态名）
		'lvlname' => Array('<span class="red">[未完成]</span>','<span class="clan">[进行中]</span>','<span class="clan">[进行中]</span>','<span class="lime">[完成]</span>'),
		//各阶段用来显示完成情况的描述文本（选填，不填此项会应用默认完成情况描述）
		'request' => Array('目前进度：[:request:]点','目前进度：[:request:]点','目前进度：[:request:]点','完成次数：[:request:]次',),
		//各阶段头衔奖励（必填，无头衔奖励使用''占位）
		'title' => Array('完成阶段0头衔奖励','完成阶段1头衔奖励','完成阶段2头衔奖励'),
		//各阶段积分奖励（必填，无积分奖励使用''占位）
        'c1' => Array(1,100,250,250),
		//各阶段切糕奖励（必填，无切糕奖励使用''占位）
        'c2' => Array(10,0,0,0),
		//各阶段成就简介（必填）
		'desc' => Array( 
			'这是模板成就的阶段0',
			'这是模板成就的阶段1',
			'这是模板成就的阶段2',
		),
	),*/
	0 => Array(
		'lvl' => 3,
		'name' => Array('永恒世界的住人','幻想世界的往人','永恒的覆唱'),
		'title' => Array('','幻想','流星'),
		'c1' => Array(0,200,700),
		'c2' => Array(10,0,0),
		'desc' => Array( 
			'合成物品【KEY系催泪弹】1次',
			'合成物品【KEY系催泪弹】5次',
			'合成物品【KEY系催泪弹】30次',
		),
	),
	1 => Array(
		'lvl' => 1,
		'name' => Array('清水池之王','清水池之王'),
		'title' => Array('KEY男'),
		'c1' => Array(30),
		'c2' => Array(16),
	),
	2 => Array(
		'lvl' => 3,
		'name' => Array('Run With Wolves','Day Game','Thousand Enemies'),
		'title' => Array('','二度打','G.D.M'),
		'c1' => Array(10,500,0),
		'c2' => Array(0,0,200),
	),
	3 => Array(
		'lvl' => 3,
		'name' => Array('脚本小子','黑客','幻境解离者？'),
		'title' => Array('','黑客','最后一步'),
		'c1' => Array(0,200,500),
		'c2' => Array(5,0,15),
		),
	4 => Array(
		'lvl' => 2,
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
		'lvl' => 2,
		'name' => Array('深度冻结','跨过彩虹'),
        'title' => Array('','跨过彩虹'),
        'c1' => Array(150,0),
        'c2' => Array(250,0),
	),
	14 => Array(
		'lvl' => 3,
		'name' => Array('篝火的引导','世界的树形图','地=月'),
        'title' => Array('','树形图','TERRA'),
        'c1' => Array(0,200,700),
        'c2' => Array(10,0,0),
	),
	15 => Array(
		'lvl' => 3,
		'name' => Array('不屈的生命','那种话最讨厌了','明亮的未来'),
        'title' => Array('','素描本','未来战士'),
        'c1' => Array(0,200,700),
        'c2' => Array(10,0,0),
	),
	16 => Array(
		'lvl' => 1,
		'name' => Array('最后幸存','只是运气好而已'),
        'title' => Array('生存者'),
        'c1' => Array(150),
        'c2' => Array(0),
		'desc' => Array( 
			'达成结局：最后幸存 1次',
		),
	),
	17 => Array(
		'lvl' => 1,
		'name' => Array('核爆全灭','麻烦制造机'),
        'title' => Array('叶子钦定！'),
        'c1' => Array(0),
        'c2' => Array(100),
		'desc' => Array( 
			'达成结局：核爆全灭 1次',
		),
	),
	18 => Array(
		'lvl' => 1,
		'name' => Array('锁定解除','最后的荣光'),
        'title' => Array('最后的荣光'),
        'c1' => Array(500),
        'c2' => Array(0),
		'desc' => Array( 
			'<span tooltip="独自完成结局或与团队共同完成结局均可达成成就">参与达成结局：锁定解除 1次</span>',
		),
	),
	19 => Array(
		'lvl' => 1,
		'name' => Array('幻境解离','奇迹的篝火'),
        'title' => Array('奇迹的篝火'),
        'c1' => Array(1000),
        'c2' => Array(1000),
		'desc' => Array( 
			'<span tooltip="独自完成结局或与团队共同完成结局均可达成成就">参与达成结局：幻境解离 1次</span>',
		),
	),
	20 => Array(
		'lvl' => 1,
		'name' => Array('寻星急袭'),
        'title' => Array('寻星者'),
        'c1' => Array(268),
        'c2' => Array(263),
	),
	21 => Array(
		'lvl' => 1,
		'name' => Array('权限【哔】的最期'),
        'title' => Array('寂静洪流'),
        'c1' => Array(233),
        'c2' => Array(233),
	),
	22 => Array(
		'lvl' => 1,
		'name' => Array('233MAX'),
        'title' => Array('l33t'),
        'c1' => Array(2333),
        'c2' => Array(0),
	),
	23 => Array(
		'lvl' => 1,
		'name' => Array('真名解放'),
        'title' => Array('赌玉狂魔'),
        'c1' => Array(0),
        'c2' => Array(888),
	),
	24 => Array(
		'lvl' => 1,
		'name' => Array('逆推'),
        'title' => Array('时代眼泪'),
        'c1' => Array(211),
        'c2' => Array(299),
	),
	25 => Array(
		'lvl' => 1,
		'name' => Array('一尸两命'),
        'title' => Array('卸腿者'),
        'c1' => Array(111),
        'c2' => Array(333),
	),
	26 => Array(
		'lvl' => 1,
		'name' => Array('正直者之死'),
        'title' => Array('吉祥物'),
        'c1' => Array(1),
        'c2' => Array(111),
	),
	27 => Array(
		'lvl' => 3,
		'name' => Array('秋后算账','报仇雪恨','血洗英灵殿'),
        'title' => Array('','','替天行道'),
        'c1' => Array(0,300,500),
        'c2' => Array(10,0,0),
	),
	28 => Array(
		'lvl' => 1,
		'name' => Array('烈火疾风'),
        'title' => Array('神触'),
        'c1' => Array(250),
        'c2' => Array(0),
	),
	29 => Array(
		'lvl' => 3,
		'name' => Array('及时补给','衣食无忧','奥义很爽'),
        'title' => Array('','美食家','补给掠夺者'),
        'c1' => Array(0,0,0),
        'c2' => Array(5,50,200),
	),
	30 => Array(
		'lvl' => 3,
		'name' => Array('饥不择食','尝百草','吞食天地'),
        'title' => Array('','神农','贝爷'),
        'c1' => Array(0,0,0),
        'c2' => Array(5,50,200),
	),
	31 => Array(
		'lvl' => 1,
		'name' => Array('Return to Sender'),
        'title' => Array('R.T.S'),
        'c1' => Array(0),
        'c2' => Array(0),
	),
	32 => Array(
		'lvl' => 2,
		'name' => Array('0xFFFFFFFFFFFFFFFF','kernel on chessboard'),
        'title' => Array('LOOP'),
        'c1' => Array(0),
        'c2' => Array(0),
	),
	33 => Array(
		'lvl' => 1,
		'name' => Array('诅咒之刃'),
        'title' => Array('剑圣'),
        'c1' => Array(0),
        'c2' => Array(522),
	),
	34 => Array(
		'lvl' => 3,
		'name' => Array('逃避可耻？','但它有用！','直面现实','逃脱大师'),
        'title' => Array('实用主义者','现实主义者','脚底抹油'),
        'c1' => Array(10,50,100),
        'c2' => Array(5,50,200),
		'desc' => Array( 
			'独自逃离幻境1次。',
			'独自逃离幻境36次。',
			'独自逃离幻境101次。',
		),
	),
	35 => Array(
		'lvl' => 3,
		'name' => Array('试试看殴系吧！','热血的机师','殴系爱好者'),
        'title' => Array('','热血机师','殴系爱好者'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	36 => Array(
		'lvl' => 3,
		'name' => Array('试试看斩系吧！','苍蓝之光','斩系爱好者'),
        'title' => Array('','苍蓝之光','斩系爱好者'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	37 => Array(
		'lvl' => 3,
		'name' => Array('来精进斩系吧！','合二为一','钥刃大师'),
        'title' => Array('','合二为一','钥刃大师'),
		'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	38 => Array(
		'lvl' => 3,
		'name' => Array('试试看射系吧！','勇闯仙境','射系爱好者'),
        'title' => Array('','勇闯仙境','射系爱好者'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	39 => Array(
		'lvl' => 3,
		'name' => Array('试试看重枪吧！','黑洞边缘','重枪爱好者'),
        'title' => Array('','黑洞边缘','重枪爱好者'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	40 => Array(
		'lvl' => 3,
		'name' => Array('试试看游戏王吧！','光的道路','决斗者'),
        'title' => Array('','光的道路','决斗者'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	41 => Array(
		'lvl' => 3,
		'name' => Array('进行加速同调吧！','加速同调','聚集的祈愿'),
        'title' => Array('','加速同调','聚集的祈愿'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	42 => Array(
		'lvl' => 3,
		'name' => Array('试试看投系吧！','平和之心','投系爱好者'),
        'title' => Array('','平和之心','投系爱好者'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	43 => Array(
		'lvl' => 3,
		'name' => Array('试试看爆系吧！','红烧天堂','爆系爱好者'),
        'title' => Array('','红烧天堂','爆系爱好者'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	44 => Array(
		'lvl' => 3,
		'name' => Array('来精进爆系吧！','★刷刷刷★','★啪啪啪★'),
        'title' => Array('','★刷刷刷★','★啪啪啪★'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	45 => Array(
		'lvl' => 3,
		'name' => Array('试试看灵系吧！','皇家烈焰','灵系爱好者'),
        'title' => Array('','皇家烈焰','灵系爱好者'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	46 => Array(
		'lvl' => 3,
		'name' => Array('来精进灵系吧！','五行大师','贤者之石'),
        'title' => Array('','五行大师','贤者之石'),
        'c1' => Array(0,100,350),
        'c2' => Array(10),
	),
	47 => Array(
		'lvl' => 3,
		'name' => Array('知己知彼！','知地利','知人和'),
        'title' => Array('','知地利','知人和'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	48 => Array(
		'lvl' => 3,
		'name' => Array('感受一下混沌吧！','混沌爱好者','混沌的深渊'),
        'title' => Array('','混沌爱好者','混沌的深渊'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	49 => Array(
		'lvl' => 3,
		'name' => Array('超级ＫＥＹ爱好者','键·四季赞歌'),
        'title' => Array('','键·四季赞歌'),
        'c1' => Array(0,700),
        'c2' => Array(100,0),
	),
	50 => Array(
		'lvl' => 2,
		'name' => Array('人，能够挑战神吗？','★一发逆转！★'),
        'title' => Array('','★一发逆转！★'),
        'c1' => Array(0,700),
        'c2' => Array(100,0),
	),
	51 => Array(
		'lvl' => 2,
		'name' => Array('究极的灵魂','『ＥＸ』'),
        'title' => Array('','『ＥＸ』'),
        'c1' => Array(0,700),
        'c2' => Array(100,0),
	),
	52 => Array(
		'lvl' => 2,
		'name' => Array('真正的决斗者','◎胜利之光◎'),
        'title' => Array('','『ＥＸ』'),
        'c1' => Array(0,700),
        'c2' => Array(100,0),
	),
	53 => Array(
		'lvl' => 3,
		'name' => Array('来打钉子吧！','棍棒爱好者','无情打钉者'),
        'title' => Array('','棍棒爱好者','无情打钉者'),
        'c1' => Array(0,0,0),
        'c2' => Array(5,50,200),
	),
	54 => Array(
		'lvl' => 3,
		'name' => Array('来磨刀吧！','磨刀爱好者','无情磨刀者'),
        'title' => Array('','磨刀爱好者','无情磨刀者'),
        'c1' => Array(0,0,0),
        'c2' => Array(5,50,200),
	),
	55 => Array(
		'lvl' => 3,
		'name' => Array('来打补丁吧！','补丁爱好者','无情补丁'),
        'title' => Array('','补丁爱好者','无情补丁'),
        'c1' => Array(0,0,0),
        'c2' => Array(5,50,200),
	),
	56 => Array(
		'lvl' => 3,
		'name' => Array('种火？那是啥？','是都市传说。','除错大师'),
        'title' => Array('','都市传说','除错大师'),
        'c1' => Array(0,100,250),
        'c2' => Array(10,0,0),
	),
	57 => Array(
		'lvl' => 3,
		'name' => Array('外来的神秘','风驰电掣','暴雷骤雨'),
        'title' => Array('','风驰电掣','暴雷骤雨'),
        'c1' => Array(0,100,250),
        'c2' => Array(10,0,0),
	),
	/*# 结局成就
	// 16 - 最后幸存结局
	16 => Array(
		'lvl' => 3,
		'name' => Array('最后幸存','只是运气好而已','不止是运气好而已？','不止是运气好而已！'),
        'title' => Array('','生存者','生存大师'),
		'c1' => Array(0,0,0),
        'c2' => Array(77,777,2777),
		'desc' => Array( 
			'达成结局：最后幸存 1次',
			'达成结局：最后幸存 17次',
			'达成结局：最后幸存 177次',
		),
	),
	// 34 - 逃离幻境结局
	34 => Array(
		'lvl' => 3,
		'name' => Array('逃避可耻？','但它有用！','直面现实','逃脱大师'),
		'title' => Array('实用主义者','现实主义者','脚底抹油'),
		'c1' => Array(0,0,0),
		'c2' => Array(15,70,300),
		'desc' => Array( 
			'独自逃离幻境1次。',
			'独自逃离幻境36次。',
			'独自逃离幻境101次。',
		),
	),
	// 17 - 核爆全灭结局
	17 => Array(
		'lvl' => 2,
		'name' => Array('核爆全灭','麻烦制造机？','麻烦制造机'),
        'title' => Array('','叶子钦定！'),
		'c1' => Array(0,0,0),
        'c2' => Array(100,500),
		'desc' => Array( 
			'达成结局：核爆全灭 1次',
			'达成结局：核爆全灭 7次',
		),
	),
	// 18 - 个人或团队累计完成锁定解除结局
	18 => Array(
		'lvl' => 3,
		'name' => Array('锁定解除','不屈的执念','最后的荣光？','最后的荣光'),
        'title' => Array('','不屈的执念','最后的荣光'),
        'c1' => Array(0,0,0),
        'c2' => Array(300,1312,1777),
		'desc' => Array( 
			'独自或与团队一同达成结局：<br>锁定解除 1次',
			'独自或与团队一同达成结局：<br>锁定解除 17次',
			'独自或与团队一同达成结局：<br>锁定解除 77次',
		),
	),
	// 100 - 击败幻影执行官后达成锁定解除结局
	100 => Array(
		'lvl' => 1, 
		'name' => Array('结束了？','未完待续'), 
		'title' => Array('挑战者'),
        'c1' => Array(0),
        'c2' => Array(450),
		'desc' => Array( 
			'击败 <span class="yellow">幻影执行官</span> 后达成结局：锁定解除'
		),
	),
	// 101 - 击败真红蓝后达成锁定解除结局
	101 => Array(
		'lvl' => 1, 
		'name' => Array('势如水火','合纵连横'), 
		'title' => Array('【待更新】'),
		'c1' => Array(0),
		'c2' => Array(950),
		'desc' => Array( 
			'击败 <span class="yellow">参战者 红暮&蓝凝</span> 后达成结局：锁定解除',
		),
	),
	// 102 - 击败DF后达成锁定解除结局
	102 => Array(
		'lvl' => 1, 
		//'icon' => Array('ach_102_0.gif','ach_101_2.gif'),
		'name' => Array('【待更新】','【待更新】'), 
		'title' => Array('【待更新】'),
		'c1' => Array(0),
		'c2' => Array(1730),
		'desc' => Array( 
			'击败 <span class="yellow">未名存在 Dark Force</span> 后达成结局：锁定解除',
		),
	),
	// 19 - 个人或团队累计完成幻境解除结局
	19 => Array(
		'lvl' => 1,
		'name' => Array('幻境解离','奇迹的篝火'),
        'title' => Array('奇迹的篝火'),
        'c1' => Array(1000),
        'c2' => Array(1000),
		'desc' => Array( 
			'独自或与团队一同达成结局：<br>幻境解离 1次',
		),
	),*/
);
//Hi
?>