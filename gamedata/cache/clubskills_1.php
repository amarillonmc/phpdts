<?php
if(!defined('IN_GAME')) exit('Access Denied');

# 技能相关配置文件：

# 社团变更时可获得的技能清单：
$club_skillslist = Array
(
	1  => Array('s_hp','s_ad','f_heal','c1_def','c1_crit','c1_stalk','c1_burnsp','c1_bjack','c1_veteran'), #'铁拳无敌',
	2  => Array('s_hp','s_ad','f_heal','c2_butcher','c2_intuit','c2_raiding','c2_master','c2_annihil'), #'见敌必斩',
	3  => Array('s_hp','s_ad','f_heal','c3_pitchpow','c3_enchant','c3_potential','c3_hawkeye','c3_offset','c3_numerous'), #'灌篮高手',
	4  => Array('s_hp','s_ad','f_heal','c4_stable','c4_break','c4_aiming','c4_loot','c4_roar','c4_sniper','c4_headshot'), #'狙击鹰眼',
	5  => Array('s_hp','s_ad','f_heal','c5_sneak','c5_caution','c5_review','c5_focus','c5_higheg','c5_double'), #'拆弹专家',
	6  => Array('s_hp','s_ad','f_heal'), #'宛如疾风',
	7  => Array('s_hp','s_ad','f_heal'), #'锡安成员',
	8  => Array('s_hp','s_ad','f_heal'), #'黑衣组织',
	9  => Array('s_hp','s_ad','f_heal'), #'超能力者',
	10 => Array('s_hp','s_ad','f_heal'), #'高速成长',
	11 => Array('s_hp','s_ad','f_heal'), #'富家子弟',
	12 => Array('s_hp','s_ad','f_heal'), #'全能骑士',
	13 => Array('s_hp','s_ad','f_heal'), #'根性兄贵',
	14 => Array('s_hp','s_ad','f_heal'), #'肌肉兄贵',
	15 => Array('f_heal'), #'<span class="L5">L5状态</span>',
	16 => Array('s_hp','s_ad','f_heal'), #'全能骑士',
	17 => Array('f_heal'), #'走路萌物',
	18 => Array('s_hp','s_ad','f_heal'), #'天赋异禀',
	19 => Array('s_hp','s_ad','f_heal'), #'晶莹剔透',
	20 => Array('s_hp','s_ad','f_heal'), #'元素大师', #商店购买社团卡
	21 => Array('s_hp','s_ad','f_heal'), #'灵子梦魇', #暂定名，商店购买社团卡
	22 => Array('s_hp','s_ad','f_heal'), #'偶像大师', #暂定名，「除错大师」头衔奖励
	98 => Array('s_hp','s_ad','f_heal'), #'换装迷宫',
	99 => Array('s_hp','s_ad','f_heal'), #'决死结界'
);

# 社团技能黑名单：（禁止特定社团升级/学习对应技能）
$cskills_blist = Array
(
	# 走路萌物无法升级生命
	's_hp' => Array(0,17),
	# 走路萌物无法升级攻防
	's_ad' => Array(0,17),
);

# 社团技能白名单：（允许特定社团升级/学习对应技能）
$cskills_wlist = Array
(

);

# 社团技能标签介绍：
$cskills_tags = Array
(
	'battle' => '<span tooltip="可以在战斗中主动使用" class="gold">【战斗技】</span>',
	'passive' => '<span tooltip="满足条件时自动触发" class="gold">【被动技】</span>',
	'active' => '<span tooltip="在主动启动后才会产生效果" class="gold">【主动技】</span>',
	//'cd' => '<span tooltip="隐藏标签：有此标签的技能会在载入时检查是否处于冷却状态" class="gold">【冷却技】</span>',
	'openning' => '<span tooltip="仅在先制发现敌人时可用" class="gold">【开幕技】</span>',
	'limit' => '<span tooltip="每局游戏内可发动次数有限" class="gold">【限次技】</span>',
	//'buff' => '<span tooltip="隐藏标签：代表这是一个临时性状态" class="gold">【状态】</span>',
	//'unlock_battle_hidden' => '<span tooltip="隐藏标签：未解锁时不会在战斗界面显示" class="gold">【隐藏】</span>',
);

# 技能登记：
$cskills = Array
(
	// 可以通过在此文件中填写配置项来创建一个新技能，系统会自动生成模板。如果配置文件不能满足需求，可以自己创建一个模板文件
	/*'技能编号' => Array
	( 	
		'name' => '技能名', //（必填）技能名
		'tags' => Array(), //（非必填）定义一个技能带有的标签
		'desc' => '', //（非必填）技能介绍，显示在技能面板上，可以使用[: :]设置一些静态参数，会在生成时自动替换对应参数。
			// [:cost:]：消耗的技能点
			// [::]：还可以替换为任意'effect'、'vars'内定义过的键名
			// [^^]：可以被替换为在'pvars'内定义过的角色数据，比如lvl

		'bdesc' => '', //（战斗技必填）显示在战斗界面上的短介绍，战斗技必填
		'maxlvl' => 0, //（非必填）定义一个技能为可升级技能，并定义该技能的等级上限。注意：带有等级上限的技能如果设置了'cost'，'cost'的值必须是一个数组，对应每等级升级时需消耗的技能点
		'cost' => 1, //（可升级/操作技能必填）升级/操作技能要花费的技能点，如果设置过'maxlvl'，这里应该设置成一个Array
		'input' => '升级',//（可升级/操作技能必填）自动生成模板时，对应操作按钮的名字，不存在时不会生成按钮
		'num_input' => 1,//（非必填）自动生成模板时，是否会为其生成数字输入框（便于快速提交多次升级）
		'log' => '', //（可升级/操作技能必填）升级/操作技能后显示的提示文本

		'status' => Array('hp','mhp'),//（非必填）每次升级时，直接提升的玩家属性。
			// 和头衔入场奖励类似，支持所有在数据库中登记过的字段名
			// 依 skillpara|c1_crit-lvl 格式设定，可以改变储存在clbpara内的内容。具体格式为：键名|技能名-子键名 如没有子键名只需要填 键名|技能名
		'effect' => Array(0 => Array('att' => 4, 'def' => 6),13 => Array('att' => 9, 'def' => 12),),//（非必填）每次升级时，直接提升的玩家属性对应的值。
			// 键名为 0 时，代表默认情况下会增加的对应属性值。键值可以是一个由字段名构成的数组。也可以只是一个数字——代表会增加所有'status'中登记的属性值
			// 键名为 其他数字 时，代表该数字对应 社团 会增加的属性值
		'events' => Array(''); //（非必填）每次升级时会触发的事件
		'link' => Array(), //（非必填）技能的关联对象：技能在生成介绍模板时，会同时从关联对象中获取静态参数
		'vars' => Array(), //（非必填）技能内预设的静态参数，比如'ragecost'怒气消耗。预设的参数可以自动填充'desc'中对应[::]的内容
		'svars' => Array(), //（非必填）初次获得技能时，保存在clbpara['skillpara']['技能编号']中的动态技能参数。可以用来定义技能的使用次数等。
		'pvars' => Array(), //（非必填）技能会受到对应的角色数据影响，可见技能-解牛；
		'slast' => Array('lasttimes' => 0,'lastturns' => 0,), //（非必填）初次获得时效性技能时，保存在clbpara内的数据。暂时只支持以下参数：
			// 'lasttimes' => 0,  代表技能持续的时间，保存在clbpara['lasttimes']['技能编号']中
			// 'lastturns' => 0,  代表技能持续的回合，保存在clbpara['lastturns']['技能编号']中
			// 时效性技能才初次霍德师，还会获得一个等于当前时间戳的'starttimes'，保存在clbpara['starttimes']['技能编号']中
			// 玩家在行动时会判断时效性技能是否结束，NPC敌人在被玩家发现时会判断时效性技能是否结束，并在战斗开始前保存状态
		'lockdesc' => '', //（需解锁技能必填）不满足解锁条件时的介绍
		'unlock' => Array('lvl' => '[:lvl:] >= 3',), //（非必填）技能解锁条件，键名和键值[::]内的内容要相同。键值须为PHP支持的条件判断语句。支持“或”类型判断，请参考下方例子。
			// Array('wepk+wep_kind' => "[:wepk:] == 'WP' || [:wep_kind:] == 'P'",), 键名中的+是分隔符，处理时会依此将条件分割为数组，替换键值内的判断语句
	),*/
	's_hp' => Array
	(
		'name' => '生命',
		'desc' => '每消耗<span class="lime">[:cost:]</span>技能点，生命上限<span class="yellow">+[:hp:]</span>点',
		'cost' => 1,
		'input' => '升级',
		'num_input' => 1,
		'log' => '消耗了<span class="lime">[:cost:]</span>点技能点，你的生命上限增加了<span class="yellow">[:hp:]</span>点。<br>',
		'status' => Array('hp','mhp'),
		'effect' => Array(
			0 => Array( 'hp' => '+=::3', 'mhp' => '+=::3',), 
			13 => Array( 'hp' => '+=::6', 'mhp' => '+=::6',), 
		),
	),
	's_ad' => Array
	(
		'name' => '攻防',
		'desc' => '每消耗<span class="lime">[:cost:]</span>技能点，基础攻击<span class="yellow">+[:att:]</span>点，基础防御<span class="yellow">+[:def:]</span>点',
		'cost' => 1,
		'input' => '升级',
		'num_input' => 1,
		'log' => '消耗了<span class="lime">[:cost:]</span>点技能点，你的基础攻击增加了<span class="yellow">[:att:]</span>点，基础防御增加了<span class="yellow">[:def:]</span>点。<br>',
		'status' => Array('att','def'),
		'effect' => Array(
			0 => Array('att' => '+=::4', 'def' => '+=::6'),
			14 => Array('att' => '+=::9', 'def' => '+=::12'),
		),
	),
	'f_heal' => Array
	(
		'name' => '自愈',
		'desc' => '消耗<span class="lime">[:cost:]</span>技能点，解除全部受伤与异常状态，并完全恢复生命与体力',
		'cost' => 1,
		'input' => '治疗',
		'log' => '消耗了<span class="lime">[:cost:]</span>技能点。<br>',
		'events' => Array('heal'),
	),
	'c1_def' => Array
	(
		'name' => '格挡',
		'tags' => Array('passive'),
		'desc' => '持殴系武器时，武器效果值的<span class="yellow">[:trans:]%</span>计入防御力(最多[:maxtrans:]点)<br>',
		'vars' => Array(
			'trans' => 40, //效&防转化率
			'maxtrans' => 2000, //转化上限
		),
		'lockdesc' => Array(
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">殴系武器</span>时生效',
		),
		'unlock' => Array(
			'wepk+wep_kind' => "[:wepk:] == 'WP' || [:wepk:] == 'WCP' || [:wepk:] == 'WKP' || [:wep_kind:] == 'P'",
		),
	),
	'c1_crit' => Array
	(
		'name' => '猛击',
		'tags' => Array('passive'),
		'desc' => '持殴系武器战斗时<span class="yellow">[:rate:]%</span>几率触发，触发则物理伤害增加<span class="yellow">[:attgain:]%</span>，<br>
		且晕眩敌人<span class="cyan">[:stuntime:]</span>秒。晕眩状态下敌人无法进行任何行动或战斗。<br></span>',
		'maxlvl' => 2,
		'cost' => Array(10,11,-1),
		'input' => '升级',
		'log' => '<span class="yellow">技能「猛击」升级成功。</span>',
		'status' => Array('skillpara|c1_crit-lvl'),
		'effect' => Array(
			0 => Array('skillpara|c1_crit-lvl' => '+=::1'),
		),
		'svars' => Array(
			'lvl' => 0, //初次获得时等级为0
		),
		'vars' => Array(
			'attgain' => Array(20,50,80), //物理伤害增加
			'stuntime' => Array(1,1,2), //晕眩时间（单位:秒）
			'rate' => 25, //触发率
		),
		'lockdesc' => Array(
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">殴系武器</span>时生效',
		),
		'unlock' => Array(
			'wepk+wep_kind' => "[:wepk:] == 'WP' || [:wepk:] == 'WCP' || [:wepk:] == 'WKP' || [:wep_kind:] == 'P'",
		),
	),
	'c1_stalk' => Array
	(
		'name' => '偷袭',
		'tags' => Array('battle','opening'),
		'desc' => '本次攻击必定触发技能“<span class="yellow">猛击</span>”且不会被反击。<br>
		持殴系武器方可发动，发动消耗<span class="yellow">[:ragecost:]</span>点怒气。<br>',
		'bdesc' => '必定触发技能“<span class="yellow">猛击</span>”且不会被反击。消耗<span class="red">[:ragecost:]</span>怒气',
		'vars' => Array(
			'ragecost' => 25, //消耗怒气
		),
		'lockdesc' => Array(
			'lvl' => '3级时解锁',
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">殴系武器</span>时可发动',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 3',
			'wepk+wep_kind' => "[:wepk:] == 'WP' || [:wepk:] == 'WCP' || [:wepk:] == 'WKP' || [:wep_kind:] == 'P'",
		),
	),
	'c1_burnsp' => Array
	(
		'name' => '灭气',
		'tags' => Array('passive'),
		'desc' => '持殴系武器攻击后敌人体力减少<span class="yellow">伤害值的[:burnspr:]%</span>点<br>
		被攻击时你额外获得<span class="yellow">[:mingrg:]～[:maxgrg:]点</span>怒气',
		'vars' => Array(
			'burnspr' => 33, //体力减少&伤害占比
			'mingrg' => 1, //最小怒气增益
			'maxgrg' => 2, //最大怒气增益
		),
		'lockdesc' => Array(
			'lvl' => '6级时解锁',
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">殴系武器</span>时可发动',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 6',
			'wepk+wep_kind' => "[:wepk:] == 'WP' || [:wepk:] == 'WCP' || [:wepk:] == 'WKP' || [:wep_kind:] == 'P'",
		),
	),
	'c1_bjack' => Array
	(
		'name' => '闷棍',
		'tags' => Array('battle'),
		'desc' => '本次攻击必定触发技能“<span class="yellow">猛击</span>”，<br>
		并对敌人额外造成(<span class="yellow">敌方体力上限减当前体力</span>)点的最终伤害。<br>
		持钝器方可发动，发动消耗<span class="yellow">[:ragecost:]</span>点怒气。',
		'bdesc' => '必定触发技能“<span class="yellow">猛击</span>”，并附加(<span class="yellow">敌方体力上限减当前体力</span>)点伤害。消耗<span class="red">[:ragecost:]</span>怒气',
		'vars' => Array(
			'ragecost' => 85, //消耗怒气
		),
		'lockdesc' => Array(
			'lvl' => '11级时解锁',
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">殴系武器</span>时可发动',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 11',
			'wepk+wep_kind' => "[:wepk:] == 'WP' || [:wepk:] == 'WCP' || [:wepk:] == 'WKP' || [:wep_kind:] == 'P'",
		),
	),
	'c1_veteran' => Array
	(
		# 这是一个使用固定模板的技能 在这里进行编辑不会有任何效果……等等，还是有点效果的……编辑下面提供的内容是会有效果的
		'name' => '百战',
		'tags' => Array('passive'),
		'log' => "切换了「百战」的防御类型。",
		'choice' => Array('P','K','C','G','F','D','I','U','q','W','E'), //可选择的单系防御类型
		'svars' => Array(
			'choice' => '', //初始默认选择的单项防御
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 18',
		),
	),
	'c2_butcher' => Array
	(
		'name' => '解牛',
		'tags' => Array('battle'),
		'desc' => '本次攻击附加<span class="yellow">([:fixdmg:]+<span tooltip="基于你目前的等级">[^lvl^]</span>)</span>点的最终伤害，且武器损耗率减半。<br>
		持斩系武器方可发动，消耗<span class="yellow">[:ragecost:]</span>点怒气',
		'bdesc' => '本次攻击附加<span class="yellow">[:fixdmg:]+[^lvl^]</span>点伤害，且武器损耗率减半，消耗<span class="red">[:ragecost:]</span>怒气',
		'vars' => Array(
			'ragecost' => 5, 
			'fixdmg' => 30, //基础固定伤害
			'wepimpr' => 0.5, //武器损耗率
		),
		'pvars' => Array('lvl'),
		'lockdesc' => Array(
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">斩系武器</span>时可发动',
		),
		'unlock' => Array(
			'wepk+wep_kind' => "[:wepk:] == 'WK' || [:wepk:] == 'WGK' || [:wepk:] == 'WKP' || [:wepk:] == 'WKF' || [:wepk:] == 'WFK' || [:wep_kind:] == 'K'",
		),
	),
	'c2_intuit' => Array
	(
		'name' => '直感',
		'tags' => Array('passive'),
		'desc' => '持斩系武器时，你的命中率<span class="yellow">+[:accgain:]%</span>，反击率<span class="yellow">+[:countergain:]%</span>，<br>
		连击命中率惩罚降低<span class="yellow">[:rbgain:]%</span>，武器伤害浮动范围<span class="yellow">+[:flucgain:]%</span>，<br>
		有<span class="yellow">[:rangerate:]%</span>概率允许超射程反击(爆系除外)<br>',
		'maxlvl' => 6,
		'cost' => Array(4,4,4,4,5,5,-1),
		'input' => '升级',
		'log' => '<span class="yellow">技能「直感」升级成功。</span>',
		'status' => Array('skillpara|c2_intuit-lvl'),
		'effect' => Array(
			0 => Array('skillpara|c2_intuit-lvl' => '+=::1'),
		),
		'svars' => Array(
			'lvl' => 0, //初次获得时等级为0
		),
		'vars' => Array(
			'accgain' => Array(0,2,4,6,8,11,14), //命中增益
			'rbgain' => Array(0,2,4,6,8,10,12), //连击命中惩罚降低
			'flucgain' => Array(0,5,10,15,20,25,30), //伤害浮动修正
			'rangerate' => Array(0,20,40,60,80,100,100), //超射程反击率
			'countergain' => Array(0,2,3,4,10,12,30), //基础反击率
		),
		'lockdesc' => '武器不适用，持<span class="yellow">斩系武器</span>时生效',
		'unlock' => Array(
			'wepk+wep_kind' => "[:wepk:] == 'WK' || [:wepk:] == 'WGK' || [:wepk:] == 'WKP' || [:wepk:] == 'WKF' || [:wepk:] == 'WFK' || [:wep_kind:] == 'K'",
		),
	),
	'c2_raiding' => Array
	(
		'name' => '强袭',
		'tags' => Array('battle'),
		'desc' => '本次攻击无视减半类防御属性，最终伤害<span class="yellow">+[:findmgr:]%</span>',
		'bdesc' => '本次攻击攻击最终伤害<span class="yellow">+[:findmgr:]%</span>，无视敌方减半类防御属性；消耗<span class="red">[:ragecost:]</span>怒气',
		'vars' => Array(
			'ragecost' => 70, 
			'findmgr' => 40, //最终伤害加成
		),
		'lockdesc' => Array(
			'lvl' => '15级时解锁',
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">斩系武器</span>时可发动',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 15',
			'wepk+wep_kind' => "[:wepk:] == 'WK' || [:wepk:] == 'WGK' || [:wepk:] == 'WKP' || [:wepk:] == 'WKF' || [:wepk:] == 'WFK' || [:wep_kind:] == 'K'",
		),
	),
	'c2_master' => Array
	(
		'name' => '舞钢',
		'tags' => Array('passive'),
		'desc' => '使用斩系武器时，你的武器伤害浮动不会出现负值。',
		'lockdesc' => Array(
			'lvl' => '15级时解锁',
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">斩系武器</span>时生效',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 15',
			'wepk+wep_kind' => "[:wepk:] == 'WK' || [:wepk:] == 'WGK' || [:wepk:] == 'WKP' || [:wepk:] == 'WKF' || [:wepk:] == 'WFK' || [:wep_kind:] == 'K'",
		),
	),
	'c2_annihil' => Array
	(
		'name' => '歼灭',
		'tags' => Array('active','cd'),
		'desc' => '发动后获得增益效果：<br>
		持斩系武器时，你的攻击有<span class="yellow">[:rate:]%</span>概率造成<span class="red b">[:findmgr:]%</span>最终伤害；<br>
		计算属性伤害时你的基础攻击力将视作武器攻击力。<br>
		增益效果持续时间<span class="yellow">[:lasttimes:]</span>秒，冷却时间<span class="clan">[:cd:]</span>秒。<br>',
		'input' => '发动',
		'log' => '<span class="lime">技能「歼灭」发动成功。</span><br>',
		'status' => Array('skillpara|c2_annihil-active'),
		'effect' => Array(
			0 => Array('skillpara|c2_annihil-active' => '=::1'),
		),
		'events' => Array('setstarttimes_c2_annihil','getskill_buff_annihil'),
		'link' => Array('buff_annihil'),
		'vars' => Array(
			'lasttimes' => 200, //持续时间 仅供介绍文本显示用
			'cd' => 900, //冷却时间
		),
		'svars' => Array(
			'active' => 0, 
		),
		'lockdesc' => Array(
			'lvl' => '21级时解锁',
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">斩系武器</span>时可发动',
			'skillpara|c2_annihil-active' => '技能发动中！',
			'skillcooldown' => '技能冷却中！<br>剩余冷却时间：<span class="red">[:cd:]</span> 秒',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 21',
			'wepk+wep_kind' => "[:wepk:] == 'WK' || [:wepk:] == 'WGK' || [:wepk:] == 'WKP' || [:wepk:] == 'WKF' || [:wepk:] == 'WFK' || [:wep_kind:] == 'K'",
			'skillpara|c2_annihil-active' => 'empty([:skillpara|c2_annihil-active:])',
			'skillcooldown' => 0,
		),
	),
	'buff_annihil' => Array
	(
		'name' => '[状态]歼灭',
		'tags' => Array('buff'),
		'desc' => '<span class="lime">「歼灭」生效中！<br>
		增益效果剩余时间：<span class="yellow">[^lasttimes^]</span> 秒</span>',
		'vars' => Array(
			'rate' => 20, //发动概率
			'findmgr' => 200, //最终伤害加成
		),
		'slast' => Array(
			'lasttimes' => 200, //真正作用的持续时间
		),
		'pvars' => Array('lasttimes'),
		'lostevents' => Array('unactive_c2_annihil'),
		'lockdesc' => Array(
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">斩系武器</span>时生效',
		),
		'unlock' => Array(
			'wepk+wep_kind' => "[:wepk:] == 'WK' || [:wepk:] == 'WGK' || [:wepk:] == 'WKP' || [:wepk:] == 'WKF' || [:wepk:] == 'WFK' || [:wep_kind:] == 'K'",
		),
	),
	'c3_pitchpow' => Array
	(
		'name' => '臂力',
		'tags' => Array('passive'),
		'desc' => '手持投系武器时，反击率<span class="yellow">+[:countergain:]%</span>',
		'maxlvl' => 6,
		'cost' => Array(2,2,2,2,3,3,-1),
		'input' => '升级',
		'log' => '<span class="yellow">技能「臂力」升级成功。</span>',
		'status' => Array('skillpara|c3_pitchpow-lvl'),
		'effect' => Array(
			0 => Array('skillpara|c3_pitchpow-lvl' => '+=::1'),
		),
		'svars' => Array(
			'lvl' => 0, //初次获得时等级为0
		),
		'vars' => Array(
			'countergain' => Array(0,20,40,60,80,100,125), 
		),
		'lockdesc' => Array(
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">投系武器</span>时生效',
		),
		'unlock' => Array(
			'wepk+wep_kind' => "[:wepk:] == 'WC' || [:wepk:] == 'WCF' || [:wepk:] == 'WCP' || [:wep_kind:] == 'C'",
		),
	),
	'c3_enchant' => Array
	(
		'name' => '附魔',
		'tags' => Array('battle','passive'),
		'desc' => '<span tooltip="主动发动时，若角色身上不存在伤害类属性，则会为其临时附加一项随机属性。"><span class="grey">[附加提示]</span>
		主动发动时，<br>在本次施加的下列属性中随机选择一种，<br>你持投系武器造成的该属性伤害永久<span class="yellow">+[:exdmggain:]%</span>(最高[:exdmgmax:]%)。<br>
		持投掷兵器时生效，消耗<span class="yellow">[:ragecost:]</span>点怒气。<br>
		目前各属性加成统计：<br></span>',
		'bdesc' => '<span tooltip="主动发动时，若角色身上不存在伤害类属性，则会为其临时附加一项随机属性。"><span class="grey">[附加提示]</span>
		发动后将使某一随机属性伤害永久<span class="yellow">+[:exdmggain:]%</span>；消耗<span class="red">[:ragecost:]</span>怒气</span>',
		'vars' => Array(
			'ragecost' => 8, 
			'exdmggain' => 3, //单项属性伤害加成
			'exdmgmax' => 150, //单项属性伤害加成上限
			'exdmgarr' => Array( //单项属性与加成的对应关系
				'u' => 'ur', 'f' => 'ur',
				'i' => 'ir', 'k' => 'ir',
				'e' => 'er',
				'p' => 'pr',
				'w' => 'wr',
				'd' => 'dr',
			),
			'exdmgdesc' => Array( //介绍该附魔对应的加成关系
				'u' => '火焰/灼焰', 
				'i' => '冻气/冰华', 
				'p' => '毒性',
				'w' => '音波',
				'e' => '电气',
				'd' => '爆炸',
			),
		),
		'svars' => Array(
			'ur' => 0, 
			'ir' => 0, 
			'pr' => 0, 
			'er' => 0, 
			'wr' => 0, 
			'dr' => 0, 
			'active_t' => 0,//技能发动次数
		),
		'lockdesc' => Array(
			'lvl' => '5级时解锁',
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">投系武器</span>时生效',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 5',
			'wepk+wep_kind' => "[:wepk:] == 'WC' || [:wepk:] == 'WCF' || [:wepk:] == 'WCP' || [:wep_kind:] == 'C'",
		),
	),
	'c3_potential' => Array
	(
		'name' => '潜能',
		'tags' => Array('battle'),
		'desc' => '本次攻击必中且物理伤害<span class="yellow">+[:phydmgr:]%</span><br>
		持投系武器方可发动，消耗<span class="yellow">[:ragecost:]</span>点怒气',
		'bdesc' => '攻击必中且物理伤害<span class="yellow">+[:phydmgr:]%</span><br>消耗<span class="red">[:ragecost:]</span>怒气',
		'vars' => Array(
			'ragecost' => 70, 
			'phydmgr' => 20, 
		),
		'lockdesc' => Array(
			'lvl' => '7级时解锁',
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">投系武器</span>时生效',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 7',
			'wepk+wep_kind' => "[:wepk:] == 'WC' || [:wepk:] == 'WCF' || [:wepk:] == 'WCP' || [:wep_kind:] == 'C'",
		),
	),
	'c3_hawkeye' => Array
	(
		'name' => '枭眼',
		'tags' => Array('passive'),
		'desc' => '如果你的武器射程不小于敌人，你对其先制攻击率<span class="yellow">+[:actgain:]%</span>，<br>
		其攻击你时命中率<span class="yellow">-[:accloss:]%</span>，连击命中率惩罚<span class="yellow">+[:rbloss:]%</span>',
		'vars' => Array(
			'actgain' => 10, 
			'accloss' => 12, 
			'rbloss' => 8,
		),
		'lockdesc' => Array(
			'lvl' => '9级时解锁',
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">投系武器</span>时生效',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 9',
			'wepk+wep_kind' => "[:wepk:] == 'WC' || [:wepk:] == 'WCF' || [:wepk:] == 'WCP' || [:wep_kind:] == 'C'",
		),
	),
	'c3_offset' => Array
	(
		'name' => '对撞',
		'tags' => Array('active'),
		'desc' => '持投系武器时，你有<span class="yellow">(<span tooltip="取决于你的投系熟练度">[^wc^]</span>×[:chancegainr:])%</span>的几率(<span class="yellow">上限[:maxchance:]%</span>)，<br>
		在受到伤害时抵挡<span class="yellow">(武器效果值的平方根×[:wepeffectr:])</span>点伤害(<span class="yellow">上限[:maxeffect:]点</span>)。<br>
		成功抵挡伤害时，会使武器效果降低<span class="red">[:wepsloss:]%</span><br>
		点击右侧的<span class="yellow">“切换”</span>按键可以随时激活或禁用该技能。<br>
		[^skill-active^]',
		'input' => '切换',
		'log' => '<span class="yellow">切换了「对撞」的状态。</span>',
		'events' => Array('active|c3_offset'),
		'vars' => Array(
			'maxeffect' => 3000,
			'wepeffectr' => 10,
			'wepsloss' => 3,
			'minchance' => 0,
			'maxchance' => 70,
			'chancegainr' => 0.1,
		),
		'svars' => Array(
			'active' => 0, 
		),
		'pvars' => Array('wc','skill-active'),
		'lockdesc' => Array(
			'lvl' => '13级时解锁',
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">投系武器</span>时生效',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 13',
			'wepk+wep_kind' => "[:wepk:] == 'WC' || [:wepk:] == 'WCF' || [:wepk:] == 'WCP' || [:wep_kind:] == 'C'",
		),
	),
	'c3_numerous' => Array
	(
		'name' => '百出',
		'tags' => Array('passive'),
		'desc' => '持投系武器时物理伤害<span class="yellow b">+([:dmgr:]×[^skillpara|c3_enchant-active_t^])%</span><br>
		其中<span class="yellow">×</span>后的数值是你发动<span class="yellow">“附魔”</span>的次数<br>',
		'vars' => Array(
			'dmgr' => 2,
		),
		'pvars' => Array('skillpara|c3_enchant-active_t'),
		'lockdesc' => Array(
			'skillpara|c3_enchant-ur+skillpara|c3_enchant-ir+skillpara|c3_enchant-pr+skillpara|c3_enchant-er+skillpara|c3_enchant-wr+skillpara|c3_enchant-dr' => '“附魔”中最高的属性伤害加成达到120%时解锁',
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">投系武器</span>时生效',
		),
		//……
		'unlock' => Array(
			'skillpara|c3_enchant-ur+skillpara|c3_enchant-ir+skillpara|c3_enchant-pr+skillpara|c3_enchant-er+skillpara|c3_enchant-wr+skillpara|c3_enchant-dr' => '[:skillpara|c3_enchant-ur:] >= 120 || [:skillpara|c3_enchant-ir:] >= 120 || [:skillpara|c3_enchant-er:] >= 120 || [:skillpara|c3_enchant-wr:] >= 120 || [:skillpara|c3_enchant-pr:] >= 120 || [:skillpara|c3_enchant-dr:] >= 120',
			'wepk+wep_kind' => "[:wepk:] == 'WC' || [:wepk:] == 'WCF' || [:wepk:] == 'WCP' || [:wep_kind:] == 'C'",
		),
	),
	'c4_stable' => Array
	(
		'name' => '静息',
		'tags' => Array('passive'),
		'desc' => '持射系武器时，你的命中率<span class="yellow">+[:accgain:]%</span>，连击命中率惩罚降低<span class="yellow">[:rbgain:]%</span><br>',
		'maxlvl' => 6,
		'cost' => Array(2,2,3,3,4,5,-1),
		'input' => '升级',
		'log' => '<span class="yellow">技能「静息」升级成功。</span><br>',
		'status' => Array('skillpara|c4_stable-lvl','skillpara|c4_stable-costcount'),
		'effect' => Array(
			0 => Array(
				'skillpara|c4_stable-lvl' => '+=::1',
				'skillpara|c4_stable-costcount' => Array('=::2','=::4','=::7','=::10','=::14','=::19','=::19'),
			),
		),
		'svars' => Array(
			'lvl' => 0, //初次获得时等级为0
			'costcount' => 0, //初次获得时花费点数为0
		),
		'vars' => Array(
			'accgain' => Array(0,2,4,6,8,10,12), //命中增益
			'rbgain' => Array(0,2,4,6,8,10,12), //连击命中惩罚降低
		),
		'lockdesc' => Array(
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">射系武器</span>或<span class="yellow">重型枪械</span>时生效',
		),
		'unlock' => Array(
			'wepk+wep_kind' => "[:wepk:] == 'WG' || [:wepk:] == 'WJ' || [:wepk:] == 'WGK' || [:wepk:] == 'WDG' || [:wep_kind:] == 'G' || [:wep_kind:] == 'J'",
		),
	),
	'c4_break' => Array
	(
		'name' => '破甲',
		'tags' => Array('passive'),
		'desc' => '持射系武器时，你的攻击致伤率<span class="yellow">[:infrgain:]</span>，造成的防具损坏效果<span class="yellow">+[:inftfix:]</span><br>
		战斗中每造成敌人一处受伤，最终伤害增加<span class="yellow">[:infdmgr:]%</span>',
		'maxlvl' => 3,
		'cost' => Array(6,6,7,-1),
		'input' => '升级',
		'log' => '<span class="yellow">技能「破甲」升级成功。</span><br>',
		'status' => Array('skillpara|c4_break-lvl','skillpara|c4_break-costcount'),
		'effect' => Array(
			0 => Array(
				'skillpara|c4_break-lvl' => '+=::1',
				'skillpara|c4_break-costcount' => Array('=::6','=::12','=::19','=::19'),
			),
		),
		'svars' => Array(
			'lvl' => 0, //初次获得时等级为0
			'costcount' => 0, //初次获得时花费点数为0
		),
		'vars' => Array(
			'infrgain' => Array(0,50,100,150), //致伤率提高
			'inftfix' => Array(0,1,2,4), //额外耐久削减
			'infdmgr' => Array(0,10,20,30), //每处致伤提高最终伤害
		),
		'lockdesc' => Array(
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">射系武器</span>或<span class="yellow">重型枪械</span>时生效',
		),
		'unlock' => Array(
			'wepk+wep_kind' => "[:wepk:] == 'WG' || [:wepk:] == 'WJ' || [:wepk:] == 'WGK' || [:wepk:] == 'WDG' || [:wep_kind:] == 'G' || [:wep_kind:] == 'J'",
		),
	),
	'c4_aiming' => Array
	(
		'name' => '瞄准',
		'tags' => Array('battle'),
		'desc' => '本次攻击物理伤害<span class="yellow">+[:phydmgr:]</span>，命中率<span class="yellow">+[:accgain:]%</span><br>
		使用射系武器方可发动，消耗<span class="yellow">[:ragecost:]</span>点怒气',
		'bdesc' => '本次攻击物理伤害<span class="yellow">+[:phydmgr:]%</span>，<br>命中率<span class="yellow">+[:accgain:]%</span><br>
		消耗<span class="red">[:ragecost:]</span>怒气',
		'vars' => Array(
			'ragecost' => 20, 
			'accgain' => 15, //命中增益
			'phydmgr' => 20, //物理伤害加成
		),
		'lockdesc' => Array(
			'lvl' => '3级时解锁',
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">射系武器</span>或<span class="yellow">重型枪械</span>时生效',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 3',
			'wepk+wep_kind' => "[:wepk:] == 'WG' || [:wepk:] == 'WJ' || [:wepk:] == 'WGK' || [:wepk:] == 'WDG' || [:wep_kind:] == 'G' || [:wep_kind:] == 'J'",
		),
	),
	'c4_loot' => Array
	(
		'name' => '掠夺',
		'tags' => Array('passive'),
		'desc' => '当你在战斗中击杀敌人时，你立即获得<span class="yellow">(<span tooltip="基于你目前的等级">[^lvl^]</span>×[:goldr:])</span>点金钱。',
		'vars' => Array(
			'goldr' => 2, 
		),
		'pvars' => Array('lvl'),
		'lockdesc' => Array(
			'lvl' => '8级时解锁',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 8',
		),
	),
	'c4_roar' => Array
	(
		'name' => '咆哮',
		'tags' => Array('battle','unlock_battle_hidden'),
		'desc' => '本次攻击物理伤害<span class="yellow">+[:phydmgr:]%</span>，属性伤害<span class="yellow">+[:exdmgr:]%</span>，<br>
		防具损坏效果<span class="yellow">+[:inftfix:]</span>。使用射系武器方可发动，消耗<span class="yellow">[:ragecost:]</span>点怒气',
		'bdesc' => '物理伤害<span class="yellow">+[:phydmgr:]%</span>，属性伤害<span class="yellow">+[:exdmgr:]%</span>，
		防具损坏效果<span class="yellow">+[:inftfix:]</span>。消耗<span class="red">[:ragecost:]</span>怒气',
		'vars' => Array(
			'ragecost' => 75, 
			'inftfix' => 2, 
			'phydmgr' => 20, //物理伤害加成
			'exdmgr' => 80, //属性伤害加成
			'disableskill' => 'c4_sniper',
		),
		'lockdesc' => Array(
			'skillpara|c4_roar-disable' => '已无法使用该技能！',
			'skillpara|c4_roar-active' => '点击「解锁」获得此技能，之后将无法使用技能「穿杨」<br>',
			'lvl' => '已解锁，15级后可用',
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">射系武器</span>或<span class="yellow">重型枪械</span>时生效',
		),
		'unlock' => Array(
			'skillpara|c4_roar-disable' => 'empty([:skillpara|c4_roar-disable:])',
			'skillpara|c4_roar-active' => '!empty([:skillpara|c4_roar-active:])',
			'lvl' => '[:lvl:] >= 15',
			'wepk+wep_kind' => "[:wepk:] == 'WG' || [:wepk:] == 'WJ' || [:wepk:] == 'WGK' || [:wepk:] == 'WDG' || [:wep_kind:] == 'G' || [:wep_kind:] == 'J'",
		),
	),
	'c4_sniper' => Array
	(
		'name' => '穿杨',
		'tags' => Array('battle','unlock_battle_hidden'),
		'desc' => '物理伤害<span class="yellow">+[:phydmgr:]%</span>，命中率<span class="yellow">+[:accgain:]%</span>，射程<span class="yellow">+[:rangegain:]</span>，<span class="yellow">连击</span>无效，<br>
		但<span class="yellow">[:prfix:]%概率贯穿</span>。使用远程武器/重型枪械方可发动，消耗<span class="yellow">[:ragecost:]</span>点怒气',
		'bdesc' => '物理伤害<span class="yellow">+[:phydmgr:]%</span>，命中率<span class="yellow">+[:accgain:]%</span>，射程<span class="yellow">+[:rangegain:]</span>，<span class="yellow">连击</span>无效，
		但<span class="yellow">[:prfix:]%概率贯穿</span>。消耗<span class="yellow">[:ragecost:]</span>点怒气',
		'vars' => Array(
			'ragecost' => 95, 
			'rangegain' => 1,
			'phydmgr' => 80, //物理伤害加成
			'accgain' => 20, //命中率加成
			'prfix' => 90, //贯穿触发率定值
			'disableskill' => 'c4_roar',
		),
		'lockdesc' => Array(
			'skillpara|c4_sniper-disable' => '已无法使用该技能！',
			'skillpara|c4_sniper-active' => "点击「解锁」获得此技能，之后将无法使用技能「咆哮」<br>",
			'lvl' => '已解锁，15级后可用',
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">射系武器</span>或<span class="yellow">重型枪械</span>时生效',
		),
		'unlock' => Array(
			'skillpara|c4_sniper-disable' => 'empty([:skillpara|c4_sniper-disable:])',
			'skillpara|c4_sniper-active' => '!empty([:skillpara|c4_sniper-active:])',
			'lvl' => '[:lvl:] >= 15',
			'wepk+wep_kind' => "[:wepk:] == 'WG' || [:wepk:] == 'WJ' || [:wepk:] == 'WGK' || [:wepk:] == 'WDG' || [:wep_kind:] == 'G' || [:wep_kind:] == 'J'",
		),
	),
	'c4_headshot' => Array
	(
		'name' => '爆头',
		'tags' => Array('passive'),
		'desc' => '使用射系武器造成超过<span class="yellow">[:killline:]%</span>目标当前生命值的伤害时，自动将其秒杀',
		'vars' => Array(
			'killline' => 85, 
		),
		'lockdesc' => Array(
			'lvl' => '15级时解锁',
			'skillpara|c4_stable-costcount+skillpara|c4_break-costcount' => '在「静息」和「破甲」上共计花费至少15技能点以解锁',
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">射系武器</span>或<span class="yellow">重型枪械</span>时生效',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 15',
			'skillpara|c4_stable-costcount+skillpara|c4_break-costcount' => '[:skillpara|c4_stable-costcount:]+[:skillpara|c4_break-costcount:] >= 15',
			'wepk+wep_kind' => "[:wepk:] == 'WG' || [:wepk:] == 'WJ' || [:wepk:] == 'WGK' || [:wepk:] == 'WDG' || [:wep_kind:] == 'G' || [:wep_kind:] == 'J'",
		),
	),
	'c5_sneak' => Array
	(
		'name' => '潜行',
		'tags' => Array('passive'),
		'desc' => '你的隐蔽率提高<span class="yellow">[:hidegain:]%</span>，主动攻击时先攻率提高<span class="yellow">[:actgain:]%</span>',
		'maxlvl' => 5,
		'cost' => Array(2,3,3,4,4,-1),
		'input' => '升级',
		'log' => '<span class="yellow">技能「潜行」升级成功。</span><br>',
		'status' => Array('skillpara|c5_sneak-lvl'),
		'effect' => Array(
			0 => Array('skillpara|c5_sneak-lvl' => '+=::1',),
		),
		'svars' => Array('lvl' => 0),
		'vars' => Array(
			'hidegain' => Array(0,2,4,6,8,10), 
			'actgain' => Array(0,2,4,6,8,10), 
		),
	),
	'c5_caution' => Array
	(
		'name' => '谨慎',
		'tags' => Array('passive'),
		'desc' => '你的陷阱回避率提高<span class="yellow">[:evgain:]%</span>，陷阱重复使用率提高<span class="yellow">[:reugain:]%</span>',
		'maxlvl' => 5,
		'cost' => Array(2,2,2,3,3,-1),
		'input' => '升级',
		'log' => '<span class="yellow">技能「谨慎」升级成功。</span><br>',
		'status' => Array('skillpara|c5_caution-lvl'),
		'effect' => Array(
			0 => Array('skillpara|c5_caution-lvl' => '+=::1',),
		),
		'svars' => Array('lvl' => 0),
		'vars' => Array(
			'evgain' => Array(0,2,4,6,8,10), 
			'reugain' => Array(0,4,8,12,16,20), 
		),
	),
	'c5_review' => Array
	(
		'name' => '反思',
		'tags' => Array('passive'),
		'desc' => "使用爆系武器时，<br>即使攻击没有命中，也可以获得[:expgain:]点固定经验值",
		'vars' => Array(
			'expgain' => 1, 
		),
		'lockdesc' => Array(
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">爆系武器</span>时生效',
		),
		'unlock' => Array(
			'wepk+wep_kind' => "[:wepk:] == 'WD' || [:wepk:] == 'WDG' || [:wepk:] == 'WDF' || (!empty([:wep_kind:]) && [:wep_kind:] == 'D')",
		),
	),
	'c5_focus' => Array
	(
		'name' => '专注',
		'tags' => Array('passive'),
		'desc' => "你可随意于下列三个状态间切换：",
		'log' => "切换了「专注」的状态。",
		'choice' => Array(0,1,2), //无效果/重视遇敌/重视探物
		'svars' => Array(
			'choice' => 0, 
		),
		'vars' => Array(
			'meetgain' => 15,
			'itmgain' => 15,
		),
		'lockdesc' => Array(
			'lvl' => '3级时解锁',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 3',
		),
	),
	'c5_higheg' => Array
	(
		'name' => '高能',
		'tags' => Array('battle'),
		'desc' => '本次攻击中爆炸属性伤害无视一切增益减益效果，<br>
		使用爆系武器方可发动，消耗<span class="yellow">[:ragecost:]</span>点怒气。',
		'bdesc' => '本次攻击中爆炸属性伤害无视一切增益减益效果；消耗<span class="red">[:ragecost:]</span>怒气',
		'vars' => Array(
			'ragecost' => 40, 
		),
		'lockdesc' => Array(
			'lvl' => '6级时解锁',
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">爆系武器</span>时生效',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 6',
			'wepk+wep_kind' => "[:wepk:] == 'WD' || [:wepk:] == 'WDG' || [:wepk:] == 'WDF' || (!empty([:wep_kind:]) && [:wep_kind:] == 'D')",
		),
	),
	'c5_double' => Array
	(
		'name' => '双响',
		'tags' => Array('battle','limit'),
		'desc' => '本局已发动<span class="yellow">[^skillpara|c5_double-active_t^]/[:maxactive_t:]次</span><br>使用爆系武器方可发动，连续攻击[:chase_t:]次。',
		'bdesc' => '本次战斗你将连续攻击[:chase_t:]次；本局已发动<span class="yellow">[^skillpara|c5_double-active_t^]/[:maxactive_t:]</span>次',
		'svars' => Array(
			'active_t' => 0,
		),
		'vars' => Array(
			'ragecost' => 0, 
			'chase_t' => 2,
			'maxactive_t' => 2,
		),
		'pvars' => Array(
			'skillpara|c5_double-active_t',
		),
		'lockdesc' => Array(
			'skillpara|c5_double-active_t' => '次数耗尽，已无法发动该技能',
			'lvl' => '19级时解锁',
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">爆系武器</span>时生效',
		),
		'unlock' => Array(
			'skillpara|c5_double-active_t' => '[:skillpara|c5_double-active_t:] < 2',
			'lvl' => '[:lvl:] >= 19',
			'wepk+wep_kind' => "[:wepk:] == 'WD' || [:wepk:] == 'WDG' || [:wepk:] == 'WDF' || (!empty([:wep_kind:]) && [:wep_kind:] == 'D')",
		),
	),
	'tl_cstick' => Array
	(
		'name' => '抡尸',
		'tags' => Array('passive'),
		'desc' => '发现尸体时，可消耗<span class="red">[:ragecost:]</span>点怒气将尸体作为<span class="yellow">殴系武器</span>拔出。<br>
		武器的<span class="yellow">效耐</span>取决于尸体的<span class="yellow">最大生命</span>与<span class="yellow">体力</span>，上限为<span class="red">[:limit:]</span>点。<br>
		优秀的尸源有概率为武器附加<span class="yellow">冲击</span>与<span class="yellow">精英</span>属性',
		'vars' => Array(
			'ragecost' => 100, 
			'limit' => 2000,
			'notype' => Array(88,92),//不能用来抡的NPC
		),
	),
	'inf_dizzy' => Array
	(
		'name' => '眩晕',
		'tags' => Array('inf'),
		'desc' => '你感到头晕目眩，无法进行任何行动或战斗！<br>眩晕状态持续时间还剩<span class="red">[^lasttimes^]</span>秒',
		'pvars' => Array('lasttimes'),
		'slast' => Array(
			'lasttimes' => 0, //真正作用的持续时间
		),
	),
);



?>
