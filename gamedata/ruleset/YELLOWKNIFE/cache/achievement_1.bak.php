<?php
if(!defined('IN_GAME')) exit('Access Denied');

# 成就大类列表：
$ach_type = Array
(
	'daily' => Array(
		'name' => '每日挑战',
		'desc' => '<font color="olive">这里是用来为日常游玩调味的佐餐成就。<br>
		虽然叫做每日挑战，但其实每六个小时就能刷新一次。</font>',
		'ach' => Array(601,602,603,604,605,606,607,608,609,610),
	),
	'end' => Array(
		'name' => '结局成就',
		'desc' => '<font color="olive">这里是与游戏结局相关的成就。<br>
		虽说有些看起来帮助中没提到，但找寻它们也正是这游戏的醍醐味之一。<br></font>',
		'ach' => Array(16,17,18,34,19,100,101,102),
	),
	'hunt' => Array(
		'name' => '猎人成就',
		'desc' => '<font color="olive">这里是与其他玩家战斗相关的成就。<br>
		专注，战斗，取得胜利！<br></font>',
		'ach' => Array(2,60,61,62,63,64,65,66,67,68,69),
	),
	'battle' => Array(
		'name' => '战斗成就',
		'desc' => '<font color="olive">这里是与击破特定NPC相关的成就。<br>
		打倒他们来证明自己吧！<br></font>',
		'ach' => Array(3,56,57,27,4,13,22,23,25,20,21,24,26,255),
	),
	'mixitem' => Array(
		'name' => '合成成就',
		'desc' => '<font color="olive">这里是与合成某些特殊物品相关的成就。<br>
		物是人的延展，这些物品背后或许有些值得一听的故事。<br></font>',
		'ach' => Array(0,14,15,49,51,52,50),
	),
	'explore' => Array(
		'name' => '探索成就',
		'desc' => '<font color="olive">这里是与你在游戏中会遇到的惊奇发现相关的成就。<br>
		今天又会遇到些什么呢？<br></font>',
		'ach' => Array(33,31,32),
	),
	'lifetime' => Array(
		'name' => '生涯成就',
		'desc' => '<font color="olive">这里是记录了你在这个游戏中的积累相关的成就。<br>
		呜呼——玩家们出发了……<br></font>',
		'ach' => Array(29,30,53,54,55,208,600,255),
	),
	'challenge' => Array(
		'name' => '挑战成就',
		'desc' => '<font color="olive">这里是与特定游戏中挑战相关的成就。<br>
		虽然颇为浮云，但毕竟山就在那里。<br></font>',
		'ach' => Array(1,200,201,28,202,203,204,205,206,207,255),
	),
);

# 日记大类列表：
$diary_type = Array
(
	'mixdiary' => Array
	(
		'name' => '合成日记',
		'desc' => '<font color="olive">这里记载着你在游戏中合成过的道具的记录。<br>
		这些内容不会被统计在总成就完成度内。<br></font>',
		'ach' => Array(35,36,37,38,39,40,41,42,43,44,45,46,47,48),
	),
);

# 隐藏成就列表：（隐藏成就ID → 完成后会显示在哪个大类）只在完成时显示
$hidden_ach_type = Array
(
	//KEY系隐藏成就：吃下【像围棋子一样的饼干】【桔黄色的果酱】并且活下来
	501 => 'explore',
	//KEY系隐藏成就：使用【翼人的羽毛】打出7230点以上伤害
	502 => 'explore',
	//KEY系隐藏成就：穿着【智代专用熊装】连续攻击同一个玩家/NPC64次以上
	503 => 'explore',
	//KEY系隐藏成就：在【RF高校】使用每一种系的武器各杀死一个目标
	504 => 'explore',
	//KEY系隐藏成就：一击秒杀【守卫者 静流】
	505 => 'explore',

	//74隐藏成就：通过元素合成获得G.A.M.E.O.V.E.R
	103 => 'explore',
);

# 成就登记列表：
$ach_list = Array
(
	/*'example' => Array(
		//成就完成时所处阶段（必填）
		'lvl' => 3, 
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
		'name' => Array('永恒世界的住人','1世界的往人','永恒的覆唱'),
		'title' => Array('','1','2'),
		'c1' => Array(0,200,700),
		'c2' => Array(10,0,0),
		'desc' => Array( 
			'合成物品【KEY系催泪弹】1次',
			'合成物品【KEY系催泪弹】5次',
			'合成物品【KEY系催泪弹】30次',
		),
	),
	3 => Array(
		'lvl' => 3,
		'name' => Array('脚本小子','3','幻境解离者？'),
		'title' => Array('','3','4'),
		'c1' => Array(0,200,500),
		'c2' => Array(5,0,15),
		),
	4 => Array(
		'lvl' => 2,
		'name' => Array('冒烟突火','红杀将军'),
        'title' => Array('','5'),
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
		'name' => Array('深度冻结','6'),
        'title' => Array('','6'),
        'c1' => Array(150,0),
        'c2' => Array(250,0),
	),
	14 => Array(
		'lvl' => 3,
		'name' => Array('篝火的引导','世界的7','地=月'),
        'title' => Array('','7','8'),
        'c1' => Array(0,200,700),
        'c2' => Array(10,0,0),
	),
	15 => Array(
		'lvl' => 3,
		'name' => Array('不屈的生命','那种话最讨厌了','明亮的未来'),
        'title' => Array('','9','10'),
        'c1' => Array(0,200,700),
        'c2' => Array(10,0,0),
	),
	20 => Array(
		'lvl' => 1,
		'name' => Array('寻星急袭'),
        'title' => Array('11'),
        'c1' => Array(268),
        'c2' => Array(263),
	),
	21 => Array(
		'lvl' => 1,
		'name' => Array('权限【哔】的最期'),
        'title' => Array('12'),
        'c1' => Array(233),
        'c2' => Array(233),
	),
	22 => Array(
		'lvl' => 1,
		'name' => Array('233MAX'),
        'title' => Array('13'),
        'c1' => Array(2333),
        'c2' => Array(0),
	),
	23 => Array(
		'lvl' => 1,
		'name' => Array('真名解放'),
        'title' => Array('14'),
        'c1' => Array(0),
        'c2' => Array(888),
	),
	24 => Array(
		'lvl' => 1,
		'name' => Array('逆推'),
        'title' => Array('15'),
        'c1' => Array(211),
        'c2' => Array(299),
	),
	25 => Array(
		'lvl' => 1,
		'name' => Array('一尸两命'),
        'title' => Array('16'),
        'c1' => Array(111),
        'c2' => Array(333),
	),
	26 => Array(
		'lvl' => 1,
		'name' => Array('正直者之死'),
        'title' => Array('17'),
        'c1' => Array(1),
        'c2' => Array(111),
	),
	27 => Array(
		'lvl' => 3,
		'name' => Array('秋后算账','报仇雪恨','血洗英灵殿'),
        'title' => Array('','','18'),
        'c1' => Array(0,300,500),
        'c2' => Array(10,0,0),
	),
	29 => Array(
		'lvl' => 3,
		'name' => Array('及时补给','衣食无忧','奥义很爽'),
        'title' => Array('','19','20'),
        'c1' => Array(0,0,0),
        'c2' => Array(5,50,200),
		'desc' => Array(
			'使用无毒补给的总效果达到32767点',
			'使用无毒补给的总效果达到142857点',
			'使用无毒补给的总效果达到999983点',
		),
	),
	30 => Array(
		'lvl' => 3,
		'name' => Array('饥不择食','尝百草','吞食天地'),
        'title' => Array('','21','22'),
        'c1' => Array(0,0,0),
        'c2' => Array(5,50,200),
		'desc' => Array(
			'食用30效以上的有毒补给5次',
			'食用30效以上的有毒补给133次',
			'食用30效以上的有毒补给365次',
		),
	),
	35 => Array(
		'lvl' => 3,
		'name' => Array('试试看殴系吧！','热血的机师','24'),
        'title' => Array('','23','24'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	36 => Array(
		'lvl' => 3,
		'name' => Array('试试看斩系吧！','25','26'),
        'title' => Array('','25','26'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	37 => Array(
		'lvl' => 3,
		'name' => Array('来精进斩系吧！','27','28'),
        'title' => Array('','27','28'),
		'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	38 => Array(
		'lvl' => 3,
		'name' => Array('试试看射系吧！','29','30'),
        'title' => Array('','29','30'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	39 => Array(
		'lvl' => 3,
		'name' => Array('试试看重枪吧！','31','32'),
        'title' => Array('','31','32'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	40 => Array(
		'lvl' => 3,
		'name' => Array('试试看游戏王吧！','33','34'),
        'title' => Array('','33','34'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	41 => Array(
		'lvl' => 3,
		'name' => Array('进行35吧！','35','36'),
        'title' => Array('','35','36'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	42 => Array(
		'lvl' => 3,
		'name' => Array('试试看投系吧！','37','38'),
        'title' => Array('','37','38'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	43 => Array(
		'lvl' => 3,
		'name' => Array('试试看爆系吧！','39','40'),
        'title' => Array('','39','40'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	44 => Array(
		'lvl' => 3,
		'name' => Array('来精进爆系吧！','41','42'),
        'title' => Array('','41','42'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	45 => Array(
		'lvl' => 3,
		'name' => Array('试试看灵系吧！','43','44'),
        'title' => Array('','43','44'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	46 => Array(
		'lvl' => 3,
		'name' => Array('来精进灵系吧！','45','46'),
        'title' => Array('','45','46'),
        'c1' => Array(0,100,350),
        'c2' => Array(10),
	),
	47 => Array(
		'lvl' => 3,
		'name' => Array('知己知彼！','47','48'),
        'title' => Array('','47','48'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	48 => Array(
		'lvl' => 3,
		'name' => Array('感受一下混沌吧！','49','50'),
        'title' => Array('','49','50'),
        'c1' => Array(0,100,350),
        'c2' => Array(10,0,0),
	),
	53 => Array(
		'lvl' => 3,
		'name' => Array('来打钉子吧！','51','52'),
        'title' => Array('','51','52'),
        'c1' => Array(0,0,0),
        'c2' => Array(5,50,200),
	),
	54 => Array(
		'lvl' => 3,
		'name' => Array('来磨刀吧！','53','54'),
        'title' => Array('','53','54'),
        'c1' => Array(0,0,0),
        'c2' => Array(5,50,200),
	),
	55 => Array(
		'lvl' => 3,
		'name' => Array('来打补丁吧！','55','56'),
        'title' => Array('','55','56'),
        'c1' => Array(0,0,0),
        'c2' => Array(5,50,200),
	),
	56 => Array(
		'lvl' => 3,
		'name' => Array('种火？那是啥？','是57。','58'),
        'title' => Array('','57','58'),
        'c1' => Array(0,100,250),
        'c2' => Array(10,0,0),
	),
	57 => Array(
		'lvl' => 3,
		'name' => Array('外来的神秘','59','60'),
        'title' => Array('','59','60'),
        'c1' => Array(0,100,250),
        'c2' => Array(10,0,0),
	),
	# 结局成就:

	# 最后幸存
	16 => Array(
		'lvl' => 3,
		'name' => Array('最后幸存','只是运气好而已','不止是运气好而已？','不止是运气好而已！'),
        'title' => Array('','61','62'),
		'c1' => Array(0,0,0),
        'c2' => Array(77,777,2777),
		'desc' => Array( 
			'达成结局：最后幸存 1次',
			'达成结局：最后幸存 17次',
			'达成结局：最后幸存 177次',
		),
	),
	# 独自逃脱
	34 => Array(
		'lvl' => 3,
		'name' => Array('逃避可耻？','但它有用！','直面现实','逃脱大师'),
		'title' => Array('63','64','65'),
		'c1' => Array(0,0,0),
		'c2' => Array(15,70,300),
		'desc' => Array( 
			'独自逃离幻境1次。',
			'独自逃离幻境36次。',
			'独自逃离幻境101次。',
		),
	),
	# 核爆全灭
	17 => Array(
		'lvl' => 2,
		'name' => Array('核爆全灭','麻烦制造机？','麻烦制造机'),
        'title' => Array('','66'),
		'c1' => Array(0,0,0),
        'c2' => Array(100,500),
		'desc' => Array( 
			'达成结局：核爆全灭 1次',
			'达成结局：核爆全灭 7次',
		),
	),
	# 锁定解除
	18 => Array(
		'lvl' => 3,
		'name' => Array('锁定解除','67','执念的残火','68'),
        'title' => Array('','67','68'),
        'c1' => Array(0,0,0),
        'c2' => Array(300,1312,1777),
		'desc' => Array( 
			'<span tooltip="独自完成、或与团队共同达成结局时，均可达成此成就">参与达成结局：锁定解除 1次</span>',
			'<span tooltip="独自完成、或与团队共同达成结局时，均可达成此成就">参与达成结局：锁定解除 17次</span>',
			'<span tooltip="独自完成、或与团队共同达成结局时，均可达成此成就">参与达成结局：锁定解除 77次</span>',
		),
	),
	# 幻境解离
	19 => Array(
		'lvl' => 3,
		'name' => Array('幻境解离','69','■■的■火','70'),
		'title' => Array('69','','70'),
		'c1' => Array(1000,0,0),
		'c2' => Array(1000,3000,76531),
		'desc' => Array( 
			"<span tooltip=\"独自完成、或与团队共同达成结局时，均可达成此成就\">参与达成结局：幻境解离 1次</span>",
			"<span tooltip=\"独自完成、或与团队共同达成结局时，均可达成此成就\">参与达成结局：幻境解离 17次</span>",
			"<span tooltip=\"独自完成、或与团队共同达成结局时，均可达成此成就\">参与达成结局：幻境解离 77次</span>",
		),
	),
	# 执行官解禁
	100 => Array(
		'lvl' => 1, 
		'name' => Array('结束了？','未完待续'), 
		'title' => Array('71'),
        'c1' => Array(0),
        'c2' => Array(450),
		'desc' => Array( 
			'使用 <span class="sienna">幻影执行官</span> 掉落的道具达成结局：锁定解除'
		),
	),
	# 真红蓝解禁
	101 => Array(
		'lvl' => 1, 
		'name' => Array('势如水火','合纵连横'), 
		'title' => Array('72'),
		'c1' => Array(0),
		'c2' => Array(950),
		'desc' => Array( 
			'使用 <span class="sienna">参战者 红暮&蓝凝</span> 掉落的道具达成结局：锁定解除',
		),
	),
	# DF解禁
	102 => Array(
		'lvl' => 1, 
		'name' => Array('不可能的伟业','73'), 
		'title' => Array('73'),
		'c1' => Array(0),
		'c2' => Array(1730),
		'desc' => Array( 
			'使用 <span class="sienna">未名存在 Dark Force</span> 掉落的道具达成结局：锁定解除',
		),
	),
	# 74达成幻境解离结局
	103 => Array(
		'lvl' => 1, 
		'name' => Array('我的口袋呢？'), 
		'title' => Array('74'),
		'c1' => Array(0),
		'c2' => Array(0),
		'desc' => Array( 
			'以<span class="sienna">追本溯源</span>的方式达成结局：幻境解离',
		),
	),

	# 猎人成就：
	# 击杀玩家：
	2 => Array(
		'lvl' => 3,
		'name' => Array('Run With Wolves','Day Game','Thousand Enemies'),
		'title' => Array('','75','76'),
		'c1' => Array(10,500,0),
		'c2' => Array(0,0,200),
		'desc' => Array( 
			'累计击杀10名玩家',
			'累计击杀100名玩家',
			'累计击杀1000名玩家',
		),
	),
	# 击杀存在击杀数的玩家：
	60 => Array(
		'lvl' => 3, 
		'name' => Array('螳螂在前','黄雀在后','猫咪在哪？','猫咪在这儿！'), 
		'title' => Array('','77','78'),
		'c1' => Array(100,200,500),
		'c2' => Array(0,200,500),
		'desc' => Array( 
			'击杀1名<span class="sienna">击杀过其他玩家</span>的玩家',
			'击杀10名<span class="sienna">击杀过其他玩家</span>的玩家',
			'击杀100名<span class="sienna">击杀过其他玩家</span>的玩家',
		),
	),
	# 在死斗模式下击杀玩家
	61 => Array(
		'lvl' => 3, 
		'name' => Array('惺惺相惜','罕逢敌手','无可匹敌？','无可匹敌！'), 
		'title' => Array('','79','80'),
		'c1' => Array(155,455,755),
		'c2' => Array(0,0,0),
		'desc' => Array( 
			'在<span class="sienna">死斗模式</span>下击杀1名玩家',
			'在<span class="sienna">死斗模式</span>下击杀10名玩家',
			'在<span class="sienna">死斗模式</span>下击杀100名玩家',
		),
	),
	# 使用毒补给杀死玩家
	62 => Array(
		'lvl' => 3, 
		'name' => Array('好味！','呸呸呸！','呕呕呕呕！'), 
		'title' => Array('','81','82'),
		'c1' => Array(233,466,1791),
		'c2' => Array(0,0,0),
		'desc' => Array( 
			'使用<span class="sienna">毒性补给</span>毒杀1名玩家（不包括自己）',
			'使用<span class="sienna">毒性补给</span>毒杀10名玩家（不包括自己）',
			'使用<span class="sienna">毒性补给</span>毒杀100名玩家（不包括自己）',
		),
	),
	# 使用陷阱杀死玩家
	63 => Array(
		'lvl' => 3, 
		'name' => Array('小心脚下','此面向敌','荆棘丛生'), 
		'title' => Array('','83','84'),
		'c1' => Array(213,409,1234),
		'c2' => Array(0,0,0),
		'desc' => Array( 
			'通过<span class="sienna">埋设陷阱</span>杀死1名玩家（不包括自己）',
			'通过<span class="sienna">埋设陷阱</span>杀死10名玩家（不包括自己）',
			'通过<span class="sienna">埋设陷阱</span>杀死100名玩家（不包括自己）',
		),
	),
	# 使用■DeathNote■杀死玩家
	64 => Array(
		'lvl' => 1, 
		'name' => Array('DeathNoter','K.I.R.A'), 
		'title' => Array('85'),
		'c1' => Array(77),
		'c2' => Array(0),
		'desc' => Array( 
			'使用<span class="sienna">■DeathNote■</span>杀死1名玩家',
		),
	),
	# 击杀1名使用过移动PC的玩家
	65 => Array(
		'lvl' => 3, 
		'name' => Array('遵纪守法','绳之以法','私法制裁'), 
		'title' => Array('','86','87'),
		'c1' => Array(110,310,911),
		'c2' => Array(0,0,0),
		'desc' => Array( 
			'击杀1名使用过<span class="sienna">移动PC</span>的玩家',
			'击杀10名使用过<span class="sienna">移动PC</span>的玩家',
			'击杀100名使用过<span class="sienna">移动PC</span>的玩家',
		),
	),
	# 击杀1名改变过天气的玩家
	66 => Array(
		'lvl' => 3, 
		'name' => Array('听风是雨','年轻稚嫩','一切未曾改变'), 
		'title' => Array('','88','89'),
		'c1' => Array(110,310,911),
		'c2' => Array(0,0,0),
		'desc' => Array( 
			'击杀1名<span class="sienna">改变过天气状况</span>的玩家',
			'击杀10名<span class="sienna">改变过天气状况</span>的玩家',
			'击杀100名<span class="sienna">改变过天气状况</span>的玩家',
		),
	),
	# 击杀1名使用了破灭之诗的活跃玩家
	67 => Array(
		'lvl' => 3, 
		'name' => Array('幻境防火墙','幻境防火墙？','幻境千年虫'), 
		'title' => Array('','90','91'),
		'c1' => Array(334,667,1919),
		'c2' => Array(0,0,0),
		'desc' => Array( 
			'击杀1名使用过<span class="sienna">破灭之诗</span>的活跃玩家',
			'在入场时间更晚的情况下，击杀1名使用过<span class="sienna">破灭之诗</span>的活跃玩家',
			'在入场时间更晚的情况下，击杀13名使用过<span class="sienna">破灭之诗</span>的活跃玩家',
		),
	),
	# 击杀数据碎片后，击杀1名发现过数据碎片尸体的玩家
	68 => Array(
		'lvl' => 1, 
		'name' => Array('正当防卫','防卫过当'), 
		'title' => Array('92'),
		'c1' => Array(233),
		'c2' => Array(0),
		'desc' => Array( 
			'击杀任一数据碎片后，击杀1名<span class="sienna">发现数据碎片尸体</span>的活跃玩家',
		),
	),
	# 击杀从福袋中开出稀有道具的玩家
	69 => Array(
		'lvl' => 3, 
		'name' => Array('汪？','海豹？','欧鳇？'), 
		'title' => Array('','93','94'),
		'c1' => Array(233,234,235),
		'c2' => Array(0,0,0),
		'desc' => Array( 
			'击杀1位<span class="sienna">从福袋中开出SR物品</span>的活跃玩家',
			'击杀1位<span class="sienna">从福袋中开出SSR物品</span>的活跃玩家',
			'在入场时间更晚的情况下，击杀13位<span class="sienna">从福袋中开出SSR物品</span>的活跃玩家',
		),
	),

	# 合成成就
	# 合成春雨夏海 > 这个应该挪到合成成就里
	49 => Array(
		'lvl' => 3,
		'name' => Array('超级ＫＥＹ爱好者','95'),
		'title' => Array('','95'),
		'c1' => Array(0,700),
		'c2' => Array(100,0),
		'desc' => Array( 
			'合成物品【春雨夏海，秋叶冬雪】1次',
			'合成物品【春雨夏海，秋叶冬雪】7次',
		),
	),
	# 合成一发逆转神话 > 同上
	50 => Array(
		'lvl' => 2,
		'name' => Array('人，能够挑战神吗？','96'),
		'title' => Array('','96'),
		'c1' => Array(0,700),
		'c2' => Array(100,0),
		'desc' => Array( 
			'合成物品★一发逆转神话★1次',
			'合成物品★一发逆转神话★7次',
		),
	),
	# 合成EX 你们都挤在挑战里干什么？？
	51 => Array(
		'lvl' => 2,
		'name' => Array('究极的灵魂','97'),
		'title' => Array('','97'),
		'c1' => Array(0,700),
		'c2' => Array(100,0),
		'desc' => Array( 
			'合成物品模式『EX』1次',
			'合成物品模式『EX』7次',
		),
	),
	# 合成光之创造神 ……
	52 => Array(
		'lvl' => 2,
		'name' => Array('真正的34','◎胜利之光◎'),
		'title' => Array('','97'),
		'c1' => Array(0,700),
		'c2' => Array(100,0),
		'desc' => Array( 
			'合成物品◎光之创造神◎1次',
			'合成物品◎光之创造神◎7次',
		),
	),

	# 探索成就
	# 98：这是一个存在固定模板的成就
	33 => Array(
		'lvl' => 1,
		'name' => Array('诅咒之刃'),
        'title' => Array('98'),
        'c1' => Array(0),
        'c2' => Array(522),
	),
	# RTS：这是一个存在固定模板的成就
	31 => Array(
		'lvl' => 1,
		'name' => Array('Return to Sender'),
		'title' => Array('99'),
		'c1' => Array(0),
		'c2' => Array(0),
	),
	# KEY系隐藏成就：吃下【像围棋子一样的饼干】【桔黄色的果酱】并且活下来
	501 => Array(
		'lvl' => 1,
		'name' => Array('异世般的食材'),
		'request' => '幸存次数：[:request:]次',
		'title' => Array('100'),
		'c1' => Array(0),
		'c2' => Array(0),
		'desc' => Array( 
			'亲身体验了来自<span class="sienna">水濑秋子及月宫亚由</span>的无敌料理。',
		),
	),
	# KEY系隐藏成就：使用【翼人的羽毛】打出7230点以上伤害
	502 => Array(
		'lvl' => 1,
		'name' => Array('空真理之威力'),
		'request' => '最高造成伤害：[:request:]点',
		'title' => Array('101'),
		'c1' => Array(0),
		'c2' => Array(0),
		'desc' => Array( 
			'亲身获得了匹敌与<span class="sienna">翼人</span>的力量。',
		),
	),
	# 穿着【智代专用熊装】连续攻击同一个玩家/NPC64次以上
	503 => Array(
		'lvl' => 1,
		'name' => Array('受难的马桶圈'),
		'request' => '最高连击次数：[:request:]次',
		'title' => Array('102'),
		'c1' => Array(0),
		'c2' => Array(0),
		'desc' => Array( 
			'亲身重现了<span class="sienna">坂上智代</span>的伟业。',
		),
	),
	# 在【RF高校】使用每一种系的武器各杀死一个目标
	504 => Array(
		'lvl' => 1,
		'name' => Array('那就是Little Busters！'),
		'request' => '完成击杀的系别：[:request:]种',
		'title' => Array('103'),
		'c1' => Array(0),
		'c2' => Array(0),
		'desc' => Array( 
			'亲身再现了<span class="sienna">Little Busters</span>的日常。',
		),
	),
	# 一击秒杀【守卫者 静流】
	505 => Array(
		'lvl' => 1,
		'name' => Array('可爱，温柔，强大，但……'),
		'title' => Array('104'),
		'c1' => Array(0),
		'c2' => Array(0),
		'desc' => Array( 
			'亲身让<span class="sienna">守卫者的尖兵 中津静流</span>了解到何为无奈。',
		),
	),

	# 挑战成就
	# key男
	1 => Array(
		'lvl' => 1,
		'name' => Array('清水池之王','清水池之王'),
		'request' => Array('最快速度：[:request:]秒'),
		'title' => Array('105'),
		'c1' => Array(30),
		'c2' => Array(16),
		'desc' => Array( 
			'在开局<span class="sienna">5分钟内</span>合成【KEY系催泪弹】',
		),
	),
	# 开局15分钟内合成46
	200 => Array(
		'lvl' => 1,
		'name' => Array('不动的大图书馆'),
		'request' => Array('最快速度：[:request:]秒'),
		'title' => Array('106'),
		'c1' => Array(0),
		'c2' => Array(666),
		'desc' => Array( 
			'在开局<span class="sienna">15分钟内</span>合成火水木金土符『46』',
		),
	),
	# 开局7分钟内合成✦烈埋火
	201 => Array(
		'lvl' => 1,
		'name' => Array('星星之火','滴水石穿'),
		'request' => Array('最快速度：[:request:]秒'),
		'title' => Array('107'),
		'c1' => Array(0),
		'c2' => Array(666),
		'desc' => Array( 
			'在开局<span class="sienna">7分钟内</span>合成✦烈埋火',
		),
	),
	# 108
	28 => Array(
		'lvl' => 1,
		'name' => Array('烈火疾风',),
		'request' => Array('最快速度：[:request:]秒'),
		'title' => Array('108'),
		'c1' => Array(250),
		'c2' => Array(0),
		'desc' => Array( 
			'在开局<span class="sienna">30分钟内</span>开启死斗模式',
		),
	),
	# 开局25分钟内达成锁定解除
	202 => Array(
		'lvl' => 1,
		'name' => Array('锁孔','穿越无钥之门'),
		'request' => Array('最快速度：[:request:]秒'),
		'title' => Array('109'),
		'c1' => Array(0),
		'c2' => Array(1024),
		'desc' => Array( 
			'在开局<span class="sienna">25分钟内</span>达成结局：锁定解除',
		),
	),
	# 开局55分钟内达成幻境解离
	203 => Array(
		'lvl' => 1,
		'name' => Array('宛如梦幻','幻境旅者'),
		'request' => Array('最快速度：[:request:]秒'),
		'title' => Array('110'),
		'c1' => Array(0),
		'c2' => Array(4096),
		'desc' => Array( 
			'在开局<span class="sienna">55分钟内</span>达成结局：幻境解离',
		),
	),
	# 套装收集挑战（这是一个存在固定模板的成就）
	208 => Array(
		'lvl' => 3,
		'name' => Array('新绿的故事','111','112'),
		'title' => Array('','111','112'),
		'c1' => Array(0,0,0),
		'c2' => Array(233,234,235),
		'desc' => Array( 
			'触发过任1种<span class="sienna">套装</span>的完整效果',
			'触发过3种不同<span class="sienna">套装</span>的完整效果',
			'触发过5种不同<span class="sienna">套装</span>的完整效果',
		),
	),
	# 使用混沌武器打满伤害
	204 => Array(
		'lvl' => 1,
		'name' => Array('混沌的宠儿','随机数之神的庇佑'),
		'title' => Array('113'),
		'c1' => Array(0),
		'c2' => Array(444),
		'desc' => Array( 
			'使用带有<span class="sienna">混沌属性</span>的武器攻击时，造成1次满额伤害',
		),
	),
	# 一击承受超过一百万伤害
	205 => Array(
		'lvl' => 1,
		'name' => Array('磁场高手','磁场颠佬'),
		'request' => Array('承受最多伤害：[:request:]点'),
		'title' => Array('114'),
		'c1' => Array(0),
		'c2' => Array(1919),
		'desc' => Array( 
			'在战斗中一次性受到超过<span class="sienna">1000000</span>点伤害',
		),
	),
	# 不使用合成/元素合成达成锁定解除/幻境解离结局
	206 => Array(
		'lvl' => 1,
		'name' => Array('你是怎么做到的？'),
		'title' => Array('115'),
		'c1' => Array(7777),
		'c2' => Array(0),
		'desc' => Array( 
			'不使用<span class="sienna">合成/元素合成/队伍</span>功能<br>达成结局：锁定解除 或 幻境解离',
		),
	),
	# 不击杀小兵/种火达成锁定解除结局
	207 => Array(
		'lvl' => 1,
		'name' => Array('这是人能做到的吗？'),
		'title' => Array('116'),
		'c1' => Array(0),
		'c2' => Array(7777),
		'desc' => Array( 
			'不击杀<span class="sienna">各路党派与种火</span>达成结局：锁定解除',
		),
	),
	# 117 > TODO：修改为一个版本成就
	32 => Array(
		'lvl' => 2,
		'name' => Array('0xFFFFFFFFFFFFFFFF','kernel on chessboard'),
        'title' => Array('117'),
        'c1' => Array(0),
        'c2' => Array(0),
	),
	
	# 日常任务
	# 混进来一个生涯成就：累计完成每日任务1/10/100/1001次
	600 => Array(
		'lvl' => 4,
		'name' => Array('新篇','十日谈','百言诗','一千零一夜','尾声？'),
        'title' => Array('','','118','119'),
		'request' => '累计完成次数：[:request:]次',
        'c1' => Array(1,10,101,1001),
        'c2' => Array(1,10,101,1001),
		'desc' => Array( 
			'累计完成1次<span class="sienna">每日挑战</span>',
			'累计完成10次<span class="sienna">每日挑战</span>',
			'累计完成100次<span class="sienna">每日挑战</span>',
			'累计完成1001次<span class="sienna">每日挑战</span>',
		),
	),
	# 日常任务1：击杀10名NPC
	601 => Array(
		'lvl' => 1,
		'daily' => 1,
		'name' => Array('蜂群71'),
        'title' => Array(''),
        'c1' => Array(150),
        'c2' => Array(0),
		'desc' => Array( 
			'击杀10名NPC',
		),
	),
	# 日常任务2：击杀1名活跃玩家
	602 => Array(
		'lvl' => 1,
		'daily' => 1,
		'name' => Array('触手71'),
		'title' => Array(''),
		'c1' => Array(0),
		'c2' => Array(150),
		'desc' => Array( 
			"击杀1名<span class=\"sienna\" tooltip=\"什么是活跃玩家？\r总之小号是不行的！\">活跃玩家</span>",
		),
	),
	# 日常任务3：达成一次解禁/解离结局
	603 => Array(
		'lvl' => 1,
		'daily' => 1,
		'name' => Array('尖兵71'),
		'title' => Array(''),
		'c1' => Array(250),
		'c2' => Array(0),
		'desc' => Array( 
			'达成结局：<span class="sienna">锁定解除</span>或<span class="sienna">幻境解离</span>',
		),
	),
	# 日常任务4：开启一次死斗模式
	604 => Array(
		'lvl' => 1,
		'daily' => 1,
		'name' => Array('荣耀71'),
		'title' => Array(''),
		'c1' => Array(250),
		'c2' => Array(0),
		'desc' => Array( 
			'开启1次<span class="sienna">死斗模式</span>',
		),
	),
	# 日常任务5：击杀10名种火
	605 => Array(
		'lvl' => 1,
		'daily' => 1,
		'name' => Array('循环71'),
		'title' => Array(''),
		'c1' => Array(177),
		'c2' => Array(0),
		'desc' => Array( 
			'击杀10名<span class="sienna">种火</span>',
		),
	),
	# 日常任务6：以毒药/陷阱的方式击杀1名活跃玩家
	606 => Array(
		'lvl' => 1,
		'daily' => 1,
		'name' => Array('偏门71'),
		'title' => Array(''),
		'c1' => Array(0),
		'c2' => Array(188),
		'desc' => Array( 
			'使用<span class="sienna">毒性补给</span>或<span class="sienna">陷阱</span>杀死1名活跃玩家',
		),
	),
	# 日常任务7：使用凸眼鱼一次吸收20具尸体
	607 => Array(
		'lvl' => 1,
		'daily' => 1,
		'name' => Array('暴食71'),
		'title' => Array(''),
		'c1' => Array(0),
		'c2' => Array(155),
		'desc' => Array( 
			'使用道具<span class="sienna">凸眼鱼</span>一次性吸收20具尸体',
		),
	),
	# 日常任务8：使用移动PC解除一次禁区
	608 => Array(
		'lvl' => 1,
		'daily' => 1,
		'name' => Array('无月71'),
		'title' => Array(''),
		'c1' => Array(155),
		'c2' => Array(0),
		'desc' => Array( 
			'使用道具<span class="sienna">移动PC</span>解除1次禁区',
		),
	),
	# 日常任务9：合成一次KEY系催泪弹
	609 => Array(
		'lvl' => 1,
		'daily' => 1,
		'name' => Array('雕像71'),
		'title' => Array(''),
		'c1' => Array(233),
		'c2' => Array(0),
		'desc' => Array( 
			'合成道具<span class="sienna">【KEY系催泪弹】</span>1次',
		),
	),
	# 日常任务10：使用一次歌唱功能
	610 => Array(
		'lvl' => 1,
		'daily' => 1,
		'name' => Array('摇滚71'),
		'title' => Array(''),
		'c1' => Array(0),
		'c2' => Array(233),
		'desc' => Array( 
			'使用一次<span class="sienna">歌唱</span>功能',
		),
	),
);

?>