<?php

	if(!defined('IN_GAME')) exit('Access Denied');

	/********元素大师配置文件********/

	//过滤选项：
	//不可以被拆解的NPC类型
	$no_type_to_e_list = Array();
	//不可以被拆解的【道具】（关键词匹配）
	$no_itm_to_e_list = Array('提示纸条',);
	//不可以被拆解的【道具类别】
	$no_itmk_to_e_list = Array('N','WN','p','fy','ygo');
	//不可以被拆解的【道具属性】
	$no_itmsk_to_e_list = Array('v','^');
	//使用元素合成的道具自带灵魂绑定属性（启用：1）
	$no_emix_circulation = 0;
	//是否启用研究模式（启用：1）
	//TODO：研究模式：每局游戏初始化时，为元素随机分配不同的特征，为特征组合分配随机配方。
	//玩家需要通过阅读提示纸条，或合成出对应的类型/属性，才能了解到元素特征、组合配方，并显示在技能升级页面。
	//$enable_emix_research_mode = 0;

	/********拆解相关配置********/
	//定值：（顺序：优先判定全词匹配→之后判定关键词匹配）
	//拆解【特定道具】（全词匹配） 固定会获得的元素、数量
	$split_itm_fix = Array
	(
		/*** 秘钥分解 ***/
		//分解冰炎钥匙 获得 随机配方_2 的第 0,1 条素材
		'冰炎钥匙·冰' => 'r_2-0',
		'冰炎钥匙·炎' => 'r_2-1',
		//分解三女主歌词卡 获得 随机配方_3 的第 0,1,2 条素材
		'歌词卡片【海洋】' => 'r_3-0',
		'歌词卡片【大地】' => 'r_3-1',
		'歌词卡片【星空】' => 'r_3-2',
		//分解黑色碎片、十字发卡 获得 随机配方_4 的第 0,1 条素材
		'黑色碎片' => 'r_4-0',
		'十字发卡' => 'r_4-1',
		//分解琉璃血 获得随机配方_5 的第 1 条素材
		'琉璃血' => 'r_5-1',
		/** 会吐道具的分解 **/
		'安雅人体冰雕' => Array('spitm'=>Array('武器师安雅的奖赏','Y','1','1','')),
		/*** 道具分解 ***/
		'◆火之碎片' => Array(0=>77,1=>77,2=>77,3=>77,4=>77,5=>77), //全元素+77
		'白色方块' => Array(0=>110), //昼闪闪+100
		'黑色方块' => Array(5=>110), //夜静静+100
		'消音器' => Array(5=>22), //夜静静+22
		'打火机' => Array(1=>111), //暖洋洋+111
		'树叶' => Array(3=>88), //郁萌萌+88
		'毒药' => Array(5=>21), //夜静静+21
		'电池' => Array(0=>15,4=>17), //亮晶晶+15、昼闪闪+17
		'手机' => Array(0=>20,),
		'笔记本电脑' => Array(4=>20,),
		'バカ⑨制冰块' => Array(2=>19,5=>21), //冷冰冰+19、夜静静+21
	);
	//拆解【特定道具】（关键词匹配，顺序越靠前优先级越高） 固定会获得的元素、数量
	$split_spitm_fix = Array
	(
		'宝石方块' => 200, //随机元素+200
		'方块' => 100, //随机元素+100
	);
	//拆解【特定类别NPC尸体】会获得的元素、数量
	$split_spcorpse_fix = Array
	(
		/*** 秘钥分解 ***/
		//分解种火尸体 获得 随机配方_0 的第 r(随机) 条素材
		92 => 'r_0-r',
		//分解执行官尸体 获得 随机配方_1 的第 0,1,2 条素材
		7 => Array('电击使 御坂 美琴'=> 'r_1-0','班主任 坂持 金发'=> 'r_1-1','花之领主 风见 幽香'=> 'r_1-2',),
		//分解武神尸体 获得随机配方_5 的第 0 条素材
		21 => 'r_5-0',
	);
	
	//系数：
	//拆解的对象等级与获得元素的数量关系（默认：等级*1） 什么，小兵竟然有30级！？
	$split_corpse_lvl_r = 1; 
	//任意【类别】对获取元素数量的系数修正（默认：0.8）（基础的元素数量：道具(效果+耐久)/2 *该系数）
	$split_default_itmk_r = 0.8;
	//指定【类别】对获取元素数量的系数修正
	$split_itmk_r = Array
	(
		//陷阱
		'T' => 0.3,
		//（*拆解复合武器 x1.5）
		'WGK' => 1.5,
		'WCF' => 1.5,
		'WCP' => 1.5,
		'WKF' => 1.5,
		'WKP' => 1.5,
		'WFK' => 1.5,
		'WDG' => 1.5,
		'WDF' => 1.5,
		'WJ'  => 1.2,
		'WB'  => 1.2,
		//补给品x0.05
		'HH' => 0.03,
		'HS' => 0.03,
		'HB' => 0.05,
		'PB2' => 0.035,	
		'PB' => 0.03,
		'PH' => 0.02,
		'PS' => 0.02,
		//药剂 x10
		'C' => 10,
		//强化药物……这也舍得拆？
		'ME'=> 18,
		'MH'=> 17,
		'M'=> 10,
		'V'=> 10,
		'VV'=> 15,
		//弹药……这也要拆！？
		'GBh' => 5,
		'GBr' => 0.003,
		'GBi' => 0.005,
		'GBe' => 0.005,
		'GB' => 0.004,	
	);
	//单个任意【属性】对标的元素数量（默认：50）
	$split_default_itmsk_fix = 50;
	//特定【属性】对标的元素数量
	$split_itmsk_fix = Array
	(
		//物理攻击：
		'R' => 11, //馄饨伤害
		'N' => 68, //冲击
		'n' => 89, //贯穿
		'y' => 90, //破格
		'r' => 144, //连击
		//属性攻击：
		'u' => 52, //火焰
		'i' => 34, //冻气
		'w' => 49, //音波
		'e' => 39, //带电
		'p' => 51, //带毒
		'd' => Array('default'=>77,'WD'=>22,'WDG'=>33,'WDF'=>33), //爆炸 出现在爆炸物上时降低价值
		'f' => 78, //灼焰
		'k' => 79, //冰华
		//物理防御类：14
		'P' => 14,
		'K' => 14,
		'C' => 14,
		'G' => 14,
		'F' => 14,
		'D' => 28, //防爆好！
		'A' => 101, //全系防御
		'B' => 1, //伤害抹消……
		//属性防御类：17
		'U' => 17,
		'E' => 17,
		'I' => 17,
		'W' => 17,
		'q' => 17,
		'a' => 89, //属性防御
		'b' => 1, //属性抹消
		//杂项：
		'M' => 33, //探雷
		'S' => 15, //消音
		'c' => 26, //重击辅助
		'H' => 42, //HP制御
		'h' => 1997, //伤害制御
		//？：
		'j' => 11, //多重
		'J' => 22, //超量素材 //我有一个想法 但是这里写不下了
		'o' => 66, //一发
		'z' => 77, //天然
		'x' => 777, //奇迹
		'Z' => 1, //菁英
	);

	/********合成相关配置********/
	//【特征】：每个元素带有若干个【主要特征】与【次要特征】。
	//【主要特征】：用于决定合成产物的【类别】，合成时【投入份数最多】的元素会显现其主要特征。投入单数份元素时提供【第一个主特征】，否则提供【第二个主特征】
	//【次要特征】：用于决定合成产物的【属性】。
	//元素特征预设模板（关闭研究模式的情况下，每局游戏内元素的特征依此模板固定。）
	$temp_etags = Array
	(
		//dom => 主要特征（类别）  sub => 次要特征（属性）  
		//max_d(s)_tags => 仅在研究模式开启的情况下生效，用于限定元素随机生成的标签上限
		0 => Array('dom' => Array('WD','DF'), 'sub' => Array('d','D','H','z'), 'max_d_tags'=>2, 'max_s_tags'=>4,), //亮晶晶
		1 => Array('dom' => Array('WG','DA'), 'sub' => Array('u','G','U','c'), 'max_d_tags'=>2, 'max_s_tags'=>4,), //暖洋洋
		2 => Array('dom' => Array('WC','DH'), 'sub' => Array('i','C','I','M'), 'max_d_tags'=>2, 'max_s_tags'=>4,), //冷冰冰
		3 => Array('dom' => Array('WK','HS'), 'sub' => Array('p','K','q','H'), 'max_d_tags'=>2, 'max_s_tags'=>4,), //郁萌萌
		4 => Array('dom' => Array('WP','HH'), 'sub' => Array('e','P','E','x'), 'max_d_tags'=>2, 'max_s_tags'=>4,), //昼闪闪
		5 => Array('dom' => Array('WF','DB'), 'sub' => Array('w','F','W','S'), 'max_d_tags'=>2, 'max_s_tags'=>4,), //夜静静
	);
	$flip_d_tag = Array 
	(
	  'WD' => 0,
	  'DF' => 0,
	  'WG' => 1,
	  'DA' => 1,
	  'WC' => 2,
	  'DH' => 2,
	  'WK' => 3,
	  'HS' => 3,
	  'WP' => 4,
	  'HH' => 4,
	  'WF' => 5,
	  'DB' => 5,
	);
	$flip_s_tag = Array 
	(
	  'd' => 0,
	  'D' => 0,
	  'R' => 0,
	  'z' => 0,
	  'u' => 1,
	  'G' => 1,
	  'U' => 1,
	  'c' => 1,
	  'i' => 2,
	  'C' => 2,
	  'I' => 2,
	  'M' => 2,
	  'p' => 3,
	  'K' => 3,
	  'q' => 3,
	  'H' => 3,
	  'e' => 4,
	  'P' => 4,
	  'E' => 4,
	  'x' => 4,
	  'w' => 5,
	  'F' => 5,
	  'W' => 5,
	  'S' => 5,
	);
	//合成出的道具效果上限值（0级时）：
	$max_emix_itme_start = 45;
	//每等级能够提升的道具效果上限（具体计算方法在get_emix_itme_max()内）
	$max_emix_itme_up = 29;
	# 固定结果配方：按照指定顺序投入arr[1]份arr[0]元素
	$emix_fixlist = Array
	(
		# 移动PC：亮晶晶20、昼闪闪20
		Array('stuff'=>Array(0=>Array(0,20),1=>Array(4,20)),'result'=>Array('移动PC','EE',5,1,'z'),),
		# UG：投入全种类元素各1份
		Array('stuff'=>Array(0=>Array(0,1),1=>Array(1,1),2=>Array(2,1),3=>Array(3,1),4=>Array(4,1),5=>Array(5,1)),'result'=>Array('Untainted Glory','A',1,1,'Z'),),
		# 仪水镜：投入全种类元素各7份
		Array('stuff'=>Array(0=>Array(0,7),1=>Array(1,7),2=>Array(2,7),3=>Array(3,7),4=>Array(4,7),5=>Array(5,7)),'result'=>Array('仪水镜','Y',1,1,''),),
	);
	# 素材随机的固定结果配方
	$rand_emix_fixlist = Array
	(
		//'stuff'=> 键名=>随机元素在数组中的自然排序的位置 键值=> 'r_x-y' 的格式等同于 rand(x,y)
		//'class'=>'hidden' 不会被提示纸条揭露的配方
		//0.电掣召唤仪：分解种火尸体随机获得：数量分别在1~10、10~20、20~30、30~40、40~50之间的5种不同元素
		0 => Array('stuff'=>Array(0=>'r1-10',1=>'r10-20',2=>'r20-30',3=>'r30-40',4=>'r40-50'),'result'=>Array('电掣召唤仪','Y',1,1,)),
		//1.游戏解除钥匙（执行官ID卡）：分解执行官尸体时依次获得：数量在1000~2000之间的3种不同元素
		1 => Array('class'=>'hidden','stuff'=>Array(0=>'r1000-2000',1=>'r1000-2000',2=>'r1000-2000'),'result'=>Array('游戏解除钥匙','Y',1,1,'v')),
		//2.游戏解除钥匙（真红暮）：分解冰炎钥匙火·冰时依次获得：数量在10000~99999之间的2种不同元素
		2 => Array('class'=>'hidden','stuff'=>Array(0=>'r10000-99999',1=>'r10000-99999'),'result'=>Array('游戏解除钥匙','Y',1,1,'Zv')),
		//3.破灭之诗：分解三女主歌词卡时依次获得：数量在100~999之间的3种不同元素
		3 => Array('class'=>'hidden','stuff'=>Array(0=>'r100-999',1=>'r100-999',2=>'r100-999'),'result'=>Array('破灭之诗','Y',1,1,95)),
		//4.黑色发卡：分解黑色碎片、十字发卡时依次获得：数量在500~1000、10000~29999之间的2种不同元素
		4 => Array('class'=>'hidden','stuff'=>Array(0=>'r500-1000',1=>'r10000-29999'),'result'=>Array('黑色发卡','X',1,1,)),
		//5.『C.H.A.O.S』：分解武神尸体、琉璃血时依次获得：数量在39999~59999、39999~79999之间的2种不同元素 
		5 => Array('class'=>'hidden','stuff'=>Array(0=>'r39999-59999',1=>'r39999-79999'),'result'=>Array('『C.H.A.O.S』','Y',1,1,)),
		//单人脱出系列：大师要有大师风范，不准跑路！
	);
	//主要特征组合配方：合成时存在多个主要特征，依此表检查是否有特殊变化
	//obbs：满足条件时有obbs%的概率组合，不设置即为100%组合；random：开启研究模式的情况下，随机为此配方生成random个合成条件。
	$dommix_list = array
	( 	
		Array('stuff'=>Array('WG','WG','WG','WC','WD'),'result'=>'WJ','obbs'=>15,), //3射+1投+1爆=重枪（15%概率）
		Array('stuff'=>Array('WC','WC','WC','WG','WK'),'result'=>'WB','obbs'=>25,), //3投+1射+1斩=弓（25%概率）
		Array('stuff'=>Array('WG','WK'),'result'=>'WGK','obbs'=>77,), //射+斩=枪刃 
		Array('stuff'=>Array('WC','WF'),'result'=>'WCF','obbs'=>77,), //投+符=符札
		Array('stuff'=>Array('WC','WP'),'result'=>'WCP','obbs'=>77,), //投+殴=重物
		Array('stuff'=>Array('WF','WK'),'result'=>'WFK','obbs'=>77,), //符+斩=魔刃……？
		Array('stuff'=>Array('WK','WF'),'result'=>'WKF','obbs'=>77,), //斩+符=灵刃
		Array('stuff'=>Array('WK','WP'),'result'=>'WKP','obbs'=>77,), //斩+殴=重剑
		Array('stuff'=>Array('WD','WG'),'result'=>'WDG','obbs'=>77,), //爆+射=巨炮
		Array('stuff'=>Array('WD','WF'),'result'=>'WDF','obbs'=>77,), //爆+符=落魂
		Array('stuff'=>Array('WD','DF','WD'),'result'=>'VD'), 
		Array('stuff'=>Array('WG','DA','WG'),'result'=>'VG'), 
		Array('stuff'=>Array('WC','DH','WC'),'result'=>'VC'), 
		Array('stuff'=>Array('WK','HS','WK'),'result'=>'VK'), 
		Array('stuff'=>Array('WP','HH','WP'),'result'=>'VP'), 
		Array('stuff'=>Array('WF','DB','WF'),'result'=>'VF'), 
		Array('stuff'=>Array('HH','HS'),'result'=>'HB','obbs'=>77,), //回命+回体=命体回复
		Array('stuff'=>Array('HH','WF'),'result'=>'HM','obbs'=>77,), //回命+灵=歌魂增加
		Array('stuff'=>Array('HS','WD'),'result'=>'HT'), //回体+爆=歌魂恢复
		Array('stuff'=>Array('HH','WG'),'result'=>'MH','obbs'=>15,), //回命+射=生命强化
		Array('stuff'=>Array('HS','WC'),'result'=>'MS','obbs'=>60,), //回体+投=体力强化
		//我认为这里应该有一个配方 可以合出有毒补给或者地雷（
	);
	# 次要特征组合配方：将次要特征转化为道具属性时，检查是否满足组合条件。两个配方存在重复元素时，排在前面的会优先生成。
	$submix_list = array
	( 	
		Array('stuff'=>Array('u','x','z'),'result'=>'f','obbs'=>33), 
		Array('stuff'=>Array('i','x','z'),'result'=>'k','obbs'=>33),
		//伤害制御：5个奇迹……现在的机制的话，大概是0.67*0.67*0.01的概率
		Array('stuff'=>Array('x','x','x','x','x'),'result'=>'h','obbs'=>1),
	);
	# 随机的次要特征组合配方 注意：指定属性（sk_*）一定要放到最前面
	$random_submix_list = Array
	(
		//连击：随机1个价值在20以上，1个价值在30以上的次要特征
		Array('stuff'=>Array(0=>'v_20',1=>'v_30'),'result'=>'r','obbs'=>33),
		//冲击：随机1个价值在20以上特征+1个随机“攻击”标签特征
		Array('stuff'=>Array(0=>'tags_W',1=>'v_20'),'result'=>'N','obbs'=>50),
		//贯穿：重击辅助+随机2个价值在30以上的次要特征
		Array('stuff'=>Array(0=>'c',1=>'v_30',2=>'v_30'),'result'=>'n','obbs'=>45),
		//破格：陷阱探测+随机2个价值在30以上的次要特征
		Array('stuff'=>Array(0=>'M',1=>'v_30',2=>'v_30'),'result'=>'y','obbs'=>45),
		//属性防御：随机1个价值在30以上+2个随机“防御”标签特征
		Array('stuff'=>Array(0=>'v_30',1=>'tags_D',2=>'tags_D'),'result'=>'a','obbs'=>50),
		//全系防御：随机2个价值在30以上的次要特征+1个随机“防御”标签特征
		Array('stuff'=>Array(0=>'v_30',1=>'v_30',2=>'tags_D'),'result'=>'A','obbs'=>50),
	);
	# 可重复生成的次要特征
	$repeat_subtags = Array('u','i','e','w','p');

	//合成结果-名字：
	$emix_luck_info = Array(-2 => '自己要倒大霉了QAQ……',1 => '这次合成的结果应该没什么特别之处。', 2=>'这次合成的结果还算不错。', 3=>'这次合成的结果似乎相当不错！' ,4=>'有什么大好事要发生了！');
	//合成过程中随机产生的东西
	$emix_tips_arr = Array('天然','喜悦','灵感','奇迹','爱','期待','愤怒','悲伤','乐子','苦闷','既视感','疯狂','幸运','ＲＰ','罪业');
	//括在名字外围的符号
	$emix_name_brackets_arr = Array('☆+☆','★+★','〖+〗','【+】','『+』','「+」','✦+✦','☾+☽','☼+☼');
	//修饰前缀，由主元素决定，有多个主元素时随机抽取。主元素组合成功时再抽取通用前缀。
	$emix_name_prefix_arr = Array
	(
		0 => Array('灰白的','雾蒙蒙的','失去颜色的','罕见的','挑染的','浮夸的','有名的','吸引来龙的','不值钱的','黑色布偶熊的','大咧咧的','吃不完的'),//'亮晶晶'
		1 => Array('火热的','温暖的','混沌的','燃烧着的','地雷的','致命的','有引力的','阳炎的','红发的','超可爱的','被余晖染上的','疯狂的','活在记忆里的'),//'暖洋洋'
		2 => Array('无暇的','快速的','冻住的','生人勿近的','猫的','喜欢宅在家里的','流动的','冷静的','浊心的','高洁的','黏在铁上的','被召唤来的'),//'冷冰冰'
		3 => Array('翡翠的','活力的','生命的','爱的','空虚的','有反差的','双马尾的','迷路了的','加了五分糖的','天然呆的','从树上长出来的','落叶归根的'),//'郁萌萌' //什么！？原来不是郁闷的郁！
		4 => Array('晨曦的','闪闪发光的','金发的','高洁的','像齿轮一样的','迈向明日的','初生的','跃动的','环形的','心怀希冀的','金色的','小男孩的'),//'昼闪闪'
		5 => Array('宵暗的','无声的','活死人的','能停止时间的','无眠的','已经困了的','噩梦的','梦见羊的','梦见门之钥的','喝提神饮料的','黑漆漆的'),//'夜静静'
		6 => Array('神秘的','有魔力的','结构复杂的','用AI模拟的','不可名状的','差不多完美的','终局的','烂尾的','打通了的','世界尽头的','第四面的'),//泛用
	);
	//元词缀，随便挑一个非主元素的
	$emix_name_meta_arr = Array
	(
		0 => Array('乱序之','宝石纹','镭射型','镜面纹','藏书','晶化','化合型','糖浆','石纹','杂糅','透明纹','印花','蕾丝缀','简约'),//'亮晶晶'
		1 => Array('燃素之','篝火之','黄昏之','晌午','凤凰','猩红','暖石','强袭型','破坏型','苹果','血纹','酒红'),//'暖洋洋'
		2 => Array('迅捷化','鱼纹','操弄之','碎心','旁观之','路人之','狡黠之','凛风之','北极星','雾之','魔咒','激流之'),//'冷冰冰'
		3 => Array('生命之','蔷薇纹','根须','咖啡','妖精','吱吱乱叫之','生姜','拿铁','叶形纹','风化','仿制','纺锤状'),//'郁萌萌'
		4 => Array('启示之','神之','王国制','旭日之','英雄之','荣光之','灯笼状','萤火之','教条','麻将','骑士之','亮片'),//'昼闪闪'
		5 => Array('怪异化','黑洞之','视界之','牧人羊','往事之','循环之','吊诡之','小鬼之','刻奇之','五芒星','爬行者'),//'夜静静'
		6 => Array('空白','留声型','映像型','回响型','气动型','双生之','差分机','飞艇型','纸之','琉璃','以太制','黄铜制','贤者之'),//拓展1
	);
	//词尾
	$emix_name_tail_arr = Array
	(
		'WK' => Array('剑','刃','斧','戟','锋刃','光剑','匕首','菜刀','牙','骨','燧石','线'),//'锐器'
		'WP' => Array('棒','锤','槌','惃棒','木棒','昆棒','盾','指虎','火箭飞拳','鞭','松果弹抖闪电鞭','气劲','伪器'),//'钝器'
		'WC' => Array('镖','卡牌','球','雪','飞盘','飞弹','弓','矢','青蛙','水滴','钢琴','猫'),//'投掷'
		'WG' => Array('枪','光枪','燧发枪','步枪','炮','浮游炮','激光','掌心雷','弹弓','导弹发射器'),//'远程'
		'WJ' => Array('狙击步枪','反坦克步枪','巨炮','武器阵列','手提箱','连装炮'),//'重枪'
		'WB' => Array('强弓','角弓','箭雨','铁弓','短弓','竖琴','猎弓','暴风','落星'),//'弓'
		'WD' => Array('炸弹','地雷','打击','此面向敌','贝蒂','绳','陷坑','抽卡游戏','短视频','胜负欲','这是什么？摸一下'),//'爆炸物'
		'WF' => Array('符','手环','节杖','触','法书','纸人','信徒','精神性','理论','天赋','秘录','蟑螂'),//'灵武'
		//TODO：复合武器
		'D' => Array('物'),//不分类防具
		'DB' => Array('甲','衣','装','翼','西装','洋装','裙','死库水','灯笼裤','胖次','纸箱','弹药包','战甲B'),
		'DH' => Array('盔','镜','帽','风帽','缎带','发卡','护目镜','投影','假说','抑制器','检测装置','战甲H'),
		'DA' => Array('盾','掌','手套','朋友','匣子','鱼鳞','力场盾','鱼竿','手表','手环','镣铐','命数','战甲A'),
		'DF' => Array('鞋','靴','爪','加护','轨迹','脚步','飞毯','马靴','草鞋','触手','尾巴','滑板','战甲F'),
		'H' => Array('秘药','罐头','糊糊','杏仁豆腐','烤鱼面包','章鱼须','乌鸡肉','生海带','幸运饼干','长芽土豆','眼泪','趣味'),//补给
		'V' => Array('指南','卷轴','手册','秘籍','乐谱','知识','共鸣','秘术','法典','广告','指引','教导','语录'),//技能书
		'M' => Array('羽毛','结晶','精华','甘露','魂','眼','心','血','魔核','宝珠','力量','勇气','波纹'),//强化药物
		'0' => Array('怪东西','？？？','竟然是它','■■■','数据削除'),//BUG词尾：没有找到类别的情况下会变成这个
	);

	/********研究模式配置********/
	//特征的“特征”……
	//嘛总之就是那个意思：武器上不会出现防御，防具上不会出现攻击属性这样……	
	$itmk_to_itmsk_tags = Array
	(
		//攻击面特征
		'W' => Array('u','e','i','w','p','d','r','f','k','R','S','o','n','N','y'),
		//可以直接由元素得到的攻击面特征
		'W_0' => Array('u','e','i','w','p','d','S'),
		//防御面特征
		'D' => Array('P','K','C','G','D','F','U','E','I','W','q','M','a','A','B','b'),
		//可以直接由元素得到的防御面特征
		'D_0' => Array('P','K','C','G','D','F','U','E','I','W','q','M'),
		//杂项特征
		'misc' => Array('z','x','c','h','H','l','g','Z'),
	);

	//提示纸条：
	$emix_slip = Array
	(
		0 => '“在元素合成时，投入份数最多的元素会成为‘主元素’，其展现出的‘主特征’会影响所合成道具的‘用途’”<br>“这点你已经知道了对吧？”<br>“但如果投入其他元素的‘份数’与‘主元素’很接近的话……也许它们的‘主特征’会混合起来？”<br>',
		1 => '“在元素合成时，投入的元素所展现出的‘次要特征’会影响所合成道具的‘属性’”<br>“这点你已经知道了对吧？”<br>“但如果把不同元素的‘特征’混合起来的话，它们有时会彼此反应，从而衍生出新的‘特征’。”<br>',
		2 => '“补给品、子弹这些消耗品，能分解出来的元素很少，有种说法是元素们讨厌这种被消耗的感觉。”<br>“但是从书本或强化药物这种价值不菲的消耗品上获得的元素又会变多。”<br>“很有哲理吧？”<br>',
		3 => '“如果在分解元素的过程中遇到了什么奇怪的状况，找个本子记录下来会比较好。”<br>“说不定以后会派上用处的。”<br>',
		4 => '“如果实在不知道该合成什么的话，不妨试试把每种元素都丢1份进去看看。”<br>“说实话我也不知道会发生什么，所以出了事不要怨我啊！”<br>',
		5 => '“据说元素们在变化成具体形状时会发出奇怪的动静……说不定是在用元素语骂人呢？”<br>',
		6 => '“据说要将元素的‘次要特征’附着在物品上时，会产生某种奇特的发酵物的味道。”<br>“其实元素合成的原理和腌咸菜也差不多吧？”<br>',
		7 => '“有种有趣的说法是：‘在数字1-10里，7是最孤独的——因为只有它既不能成为因子、也不能被整除。’”<br>“如果你很闲的话，应该还能找到很多这样的规律。”<br>“或者你也可以试试把每种元素都丢进去7份看看？”<br>',
		8 => '“你有没有试着分解过‘手机’和‘笔记本电脑’？”<br>“虽然不知道为什么这些东西上也会有元素。但寄宿在其上的元素数量与种类似乎是固定的。如果把这些固定的元素重新组合起来的话……？”<br>',
		9 => '“你有没有试过把带有‘生命恢复’与‘体力恢复’特征的元素组合起来？”<br>“嘛，就算没试过，你应该一眼就能看出来会发生什么了。但是如果把它们分别与更有‘攻击性’的元素组合起来的话……？”<br>',
	);
?>
