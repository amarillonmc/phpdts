<?php
if(!defined('IN_GAME')) exit('Access Denied');

# 技能相关配置文件：

# 社团变更时可获得的技能清单：
$club_skillslist = Array
(
	1  => Array('s_hp','s_ad','f_heal','c1_def','c1_crit','c1_stalk','c1_burnsp','c1_bjack','c1_veteran'), #'街头霸王',
	2  => Array('s_hp','s_ad','f_heal','c2_butcher','c2_intuit','c2_raiding','c2_master','c2_annihil'), #'见敌必斩',
	3  => Array('s_hp','s_ad','f_heal','c3_pitchpow','c3_enchant','c3_potential','c3_hawkeye','c3_offset','c3_numerous'), #'灌篮高手',
	4  => Array('s_hp','s_ad','f_heal','c4_stable','c4_break','c4_aiming','c4_loot','c4_roar','c4_sniper','c4_headshot'), #'狙击鹰眼',
	5  => Array('s_hp','s_ad','f_heal','c5_sneak','c5_caution','c5_review','c5_focus','c5_higheg','c5_double'), #'拆弹专家',
	6  => Array('s_hp','s_ad','f_heal','c6_godluck','c6_godsend','c6_godbless','c6_godpow','c6_godeyes','c6_justice'), #'宛如疾风',
	7  => Array('s_hp','s_ad','f_heal','c7_radar','c7_shield','c7_electric','c7_field','c7_overload','c7_emp'), #'锡安成员',
	8  => Array('s_hp','s_ad','f_heal','c8_expert','c8_infilt','c8_catalyst','c8_deadheal','c8_assassin'), #'黑衣组织',
	9  => Array('s_hp','s_ad','f_heal','c9_spirit','c9_lb','c9_iceheart','c9_charge','c9_heartfire'), #'超能力者',
	10 => Array('s_hp','s_ad','f_heal','c10_inspire','c10_insight','c10_decons'), #'天赋异禀', //高速成长与天赋异禀合并为天赋异禀
	11 => Array('s_hp','s_ad','f_heal','c11_ebuy','c11_merc','c11_stock','c11_renjie'), #'富家子弟',
	12 => Array('s_hp','s_ad','f_heal','c12_huge','c12_enmity','c12_garrison','c12_rage','c12_bloody','c12_swell'), #'全能兄贵', //根性兄贵、肌肉兄贵、全能骑士合并为全能兄贵
	13 => Array('s_hp','s_ad','f_heal','c13_master','c13_kungfu','c13_quick','c13_wingchun','c13_parry','c13_duel'),
	//13 => Array('s_hp','s_ad','f_heal'), #'根性兄贵',
	//14 => Array('s_hp','s_ad','f_heal'), #'肌肉兄贵',
	15 => Array('f_heal'), #'<span class="L5">L5状态</span>',
	//16 => Array('s_hp','s_ad','f_heal'), #'全能骑士',
	17 => Array('f_heal'), #'走路萌物',
	//18 => Array('s_hp','s_ad','f_heal'), #'天赋异禀',
	19 => Array('s_hp','s_ad','f_heal','c19_nirvana','c19_reincarn','c19_purity','c19_crystal','c19_redeem','c19_dispel','c19_woesea'), #'晶莹剔透', //晶莹剔透、决死结界合并为晶莹剔透
	20 => Array('s_hp','s_ad','f_heal','c20_fertile','c20_windfall','c20_lighting','c20_zombie','c20_sparkle','c20_lotus'), #'元素大师', #商店购买社团卡
	21 => Array('s_hp','s_ad','f_heal'), #'码语行人'
	22 => Array('s_hp','s_ad','f_heal'), #'偶像大师', #暂定名，「除错大师」头衔奖励
	98 => Array('s_hp','s_ad','f_heal'), #'换装迷宫',
	99 => Array('s_hp','s_ad','f_heal'), #'第一形态'
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
	//'club' => '<span tooltip="隐藏标签：代表这个技能会显示在称号技能页面" class="gold">【称号】</span>',
	'battle' => '<span tooltip="可以在战斗中主动使用" class="gold">【战斗技】</span>',
	'passive' => '<span tooltip="满足条件时自动触发" class="gold">【被动技】</span>',
	'switch' => '<span tooltip="可主动启用或停用效果" class="gold">【开关技】</span>',
	'active' => '<span tooltip="可在技能界面直接使用" class="gold">【主动技】</span>',
	'openning' => '<span tooltip="仅在初次先制发现敌人时可用" class="gold">【开幕技】</span>',
	'limit' => '<span tooltip="每局游戏内可发动次数有限" class="gold">【限次技】</span>',
	//'inf' => '<span tooltip="隐藏标签：代表这是一个负面状态，这个技能会显示在状态页面" class="gold">【异常】</span>',
	//'buff' => '<span tooltip="隐藏标签：代表这是一个正面状态，这个技能会显示在状态页面" class="gold">【增益】</span>',
	//'unlock_battle_hidden' => '<span tooltip="隐藏标签：未解锁时不会在战斗界面显示" class="gold">【隐藏】</span>',
	//'player' => '<span tooltip="隐藏标签：只有玩家会有此技能" class="gold">【玩家】</span>',
);

// tips
$sktrapidatk = '<span class="gold" tooltip2="【连续攻击】：攻击完毕、且造成的最终伤害结算后，在敌人反击前再度发起攻击">连续攻击</span>';
$sktpshield = '<span class="gold" tooltip2="【护盾】：可抵消等同于护盾值的伤害。护盾值只在抵消属性伤害时消耗，抵消电击伤害时双倍消耗。护盾存在时不会受到反噬伤害或陷入异常状态。">护盾</span>';
$sktprp = '<span class="yellow">报应点数</span>';
$sktpwhitedmg = '<span class="gold" tooltip2="【纯粹伤害】：不会受防御、抹消或制御效果影响的定值伤害">纯粹伤害</span>';
$sktpzombie = '<span class="gold" tooltip2="【灵俑】：此状态下的角色造成的最终伤害降低50%，受到的伤害降低25%；不会受到反噬伤害，但不能再造成除毒性、冻气外的属性伤害；">灵俑</span>';
$sktpemsdmg = "<span class=\"gold\" tooltip2=\"【亮晶晶】：造成纯粹伤害（不受防御、抹消或制御效果影响的定值伤害）\r【暖洋洋】：造成火焰伤害\r【冷冰冰】：造成冻气伤害\r【冷冰冰】：造成冻气伤害\r【郁萌萌】：造成毒性伤害\r【昼闪闪】：造成电气伤害\r【夜静静】：造成音波伤害\">属性/纯粹</span>";
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
		'tags' => Array('player'),
		'desc' => '每消耗<span class="lime">[:cost:]</span>技能点，生命上限<span class="yellow">+[:hp:]</span>点',
		'cost' => 1,
		'input' => '升级',
		'num_input' => 1,
		'log' => '消耗了<span class="lime">[:cost:]</span>点技能点，你的生命上限增加了<span class="yellow">[:hp:]</span>点。<br>',
		'status' => Array('hp','mhp'),
		'effect' => Array(
			0 => Array( 'hp' => '+=::3', 'mhp' => '+=::3',), 
			12 => Array( 'hp' => '+=::6', 'mhp' => '+=::6',), 
			20 => Array( 'hp' => '+=::4', 'mhp' => '+=::4',), 
		),
	),
	's_ad' => Array
	(
		'name' => '攻防',
		'tags' => Array('player'),
		'desc' => '每消耗<span class="lime">[:cost:]</span>技能点，基础攻击<span class="yellow">+[:att:]</span>点，基础防御<span class="yellow">+[:def:]</span>点',
		'cost' => 1,
		'input' => '升级',
		'num_input' => 1,
		'log' => '消耗了<span class="lime">[:cost:]</span>点技能点，你的基础攻击增加了<span class="yellow">[:att:]</span>点，基础防御增加了<span class="yellow">[:def:]</span>点。<br>',
		'status' => Array('att','def'),
		'effect' => Array(
			0 => Array('att' => '+=::4', 'def' => '+=::6'),
			12 => Array('att' => '+=::9', 'def' => '+=::12'),
		),
	),
	'f_heal' => Array
	(
		'name' => '自愈',
		'tags' => Array('player'),
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
			'wepk+wep_kind' => "strpos([:wepk:],'P')!==false || (!empty([:wep_kind:]) && [:wep_kind:] == 'P')",
		),
	),
	'c1_crit' => Array
	(
		'name' => '猛击',
		'tags' => Array('passive'),
		'desc' => '持殴系武器战斗时<span class="yellow">[:rate:]%</span>几率触发，触发则物理伤害增加<span class="yellow">[:attgain:]%</span>，<br>
		且晕眩敌人<span class="clan">[:stuntime:]</span>秒。晕眩状态下敌人无法进行任何行动或战斗。<br></span>',
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
			'wepk+wep_kind' => "strpos([:wepk:],'P')!==false || (!empty([:wep_kind:]) && [:wep_kind:] == 'P')",
		),
	),
	'c1_stalk' => Array
	(
		'name' => '偷袭',
		'tags' => Array('battle','opening'),
		'wepk' => Array('P'),
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
			'wepk+wep_kind' => "strpos([:wepk:],'P')!==false || (!empty([:wep_kind:]) && [:wep_kind:] == 'P')",
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
			'wepk+wep_kind' => "strpos([:wepk:],'P')!==false || (!empty([:wep_kind:]) && [:wep_kind:] == 'P')",
		),
	),
	'c1_bjack' => Array
	(
		'name' => '闷棍',
		'tags' => Array('battle'),
		'wepk' => Array('P'),
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
			'wepk+wep_kind' => "strpos([:wepk:],'P')!==false || (!empty([:wep_kind:]) && [:wep_kind:] == 'P')",
		),
	),
	'c1_veteran' => Array
	(
		# 这是一个使用固定模板的技能 在这里进行编辑不会有任何效果……等等，还是有点效果的……编辑下面提供的内容是会有效果的
		'name' => '百战',
		'tags' => Array('passive'),
		'clog' => "切换了「百战」的防御类型。",
		'choice' => Array('P','K','C','G','F','D','I','U','q','W','E'), //可选择的单系防御类型
		'svars' => Array(
			'choice' => 'D', //初始默认选择的单项防御
		),
		'lockdesc' => Array(
			'lvl' => '18级时解锁',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 18',
		),
	),
	'c2_butcher' => Array
	(
		'name' => '解牛',
		'tags' => Array('battle'),
		'wepk' => Array('K'),
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
			'wepk+wep_kind' => "strpos([:wepk:],'K')!==false || (!empty([:wep_kind:]) && [:wep_kind:] == 'K')",
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
		'lockdesc' => Array(
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">斩系武器</span>时生效',
		),
		'unlock' => Array(
			'wepk+wep_kind' => "strpos([:wepk:],'K')!==false || (!empty([:wep_kind:]) && [:wep_kind:] == 'K')",
		),
	),
	'c2_raiding' => Array
	(
		'name' => '强袭',
		'tags' => Array('battle'),
		'wepk' => Array('K'),
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
			'wepk+wep_kind' => "strpos([:wepk:],'K')!==false || (!empty([:wep_kind:]) && [:wep_kind:] == 'K')",
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
			'wepk+wep_kind' => "strpos([:wepk:],'K')!==false || (!empty([:wep_kind:]) && [:wep_kind:] == 'K')",
		),
	),
	'c2_annihil' => Array
	(
		'name' => '歼灭',
		'tags' => Array('active'),
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
		'events' => Array('setstarttimes_c2_annihil','getskill_buff_annihil','active_news'),
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
			'wepk+wep_kind' => "strpos([:wepk:],'K')!==false || (!empty([:wep_kind:]) && [:wep_kind:] == 'K')",
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
			'wepk+wep_kind' => "strpos([:wepk:],'K')!==false || (!empty([:wep_kind:]) && [:wep_kind:] == 'K')",
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
			'wepk+wep_kind' => "strpos([:wepk:],'C')!==false || (!empty([:wep_kind:]) && [:wep_kind:] == 'C')",
		),
	),
	'c3_enchant' => Array
	(
		'name' => '附魔',
		'tags' => Array('battle','passive'),
		'wepk' => Array('C'),
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
			'wepk+wep_kind' => "strpos([:wepk:],'C')!==false || (!empty([:wep_kind:]) && [:wep_kind:] == 'C')",
		),
	),
	'c3_potential' => Array
	(
		'name' => '潜能',
		'tags' => Array('battle'),
		'wepk' => Array('C'),
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
			'wepk+wep_kind' => "strpos([:wepk:],'C')!==false || (!empty([:wep_kind:]) && [:wep_kind:] == 'C')",
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
			'wepk+wep_kind' => "strpos([:wepk:],'C')!==false || (!empty([:wep_kind:]) && [:wep_kind:] == 'C')",
		),
	),
	'c3_offset' => Array
	(
		'name' => '对撞',
		'tags' => Array('switch'),
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
			'active' => 1, 
		),
		'pvars' => Array('wc','skill-active'),
		'lockdesc' => Array(
			'lvl' => '13级时解锁',
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">投系武器</span>时生效',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 13',
			'wepk+wep_kind' => "strpos([:wepk:],'C')!==false || (!empty([:wep_kind:]) && [:wep_kind:] == 'C')",
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
			'wepk+wep_kind' => "strpos([:wepk:],'C')!==false || (!empty([:wep_kind:]) && [:wep_kind:] == 'C')",
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
			'weps' => '武器弹药不足，无法发动',
		),
		'unlock' => Array(
			'wepk+wep_kind' => "strpos([:wepk:],'G')!==false || strpos([:wepk:],'J')!==false || (!empty([:wep_kind:]) && ([:wep_kind:] == 'G' || [:wep_kind:] == 'J' ))",
			'weps' => "[:weps:] != '∞'",
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
			'weps' => '武器弹药不足，无法发动',
		),
		'unlock' => Array(
			'wepk+wep_kind' => "strpos([:wepk:],'G')!==false || strpos([:wepk:],'J')!==false || (!empty([:wep_kind:]) && ([:wep_kind:] == 'G' || [:wep_kind:] == 'J' ))",
			'weps' => "[:weps:] != '∞'",
		),
	),
	'c4_aiming' => Array
	(
		'name' => '瞄准',
		'tags' => Array('battle'),
		'wepk' => Array('G','J'),
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
			'weps' => '武器弹药不足，无法发动',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 3',
			'wepk+wep_kind' => "strpos([:wepk:],'G')!==false || strpos([:wepk:],'J')!==false || (!empty([:wep_kind:]) && ([:wep_kind:] == 'G' || [:wep_kind:] == 'J' ))",
			'weps' => "[:weps:] != '∞'",
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
		'wepk' => Array('G','J'),
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
			'weps' => '武器弹药不足，无法发动',
		),
		'unlock' => Array(
			'skillpara|c4_roar-disable' => 'empty([:skillpara|c4_roar-disable:])',
			'skillpara|c4_roar-active' => '!empty([:skillpara|c4_roar-active:])',
			'lvl' => '[:lvl:] >= 15',
			'wepk+wep_kind' => "strpos([:wepk:],'G')!==false || strpos([:wepk:],'J')!==false || (!empty([:wep_kind:]) && ([:wep_kind:] == 'G' || [:wep_kind:] == 'J' ))",
			'weps' => "[:weps:] != '∞'",
		),
	),
	'c4_sniper' => Array
	(
		'name' => '穿杨',
		'tags' => Array('battle','unlock_battle_hidden'),
		'wepk' => Array('G','J'),
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
			'weps' => '武器弹药不足，无法发动',
		),
		'unlock' => Array(
			'skillpara|c4_sniper-disable' => 'empty([:skillpara|c4_sniper-disable:])',
			'skillpara|c4_sniper-active' => '!empty([:skillpara|c4_sniper-active:])',
			'lvl' => '[:lvl:] >= 15',
			'wepk+wep_kind' => "strpos([:wepk:],'G')!==false || strpos([:wepk:],'J')!==false || (!empty([:wep_kind:]) && ([:wep_kind:] == 'G' || [:wep_kind:] == 'J' ))",
			'weps' => "[:weps:] != '∞'",
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
			'weps' => '武器弹药不足，无法发动',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 15',
			'skillpara|c4_stable-costcount+skillpara|c4_break-costcount' => '[:skillpara|c4_stable-costcount:]+[:skillpara|c4_break-costcount:] >= 15',
			'wepk+wep_kind' => "strpos([:wepk:],'G')!==false || strpos([:wepk:],'J')!==false || (!empty([:wep_kind:]) && ([:wep_kind:] == 'G' || [:wep_kind:] == 'J' ))",
			'weps' => "[:weps:] != '∞'",
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
			'wepk+wep_kind' => "strpos([:wepk:],'D')!==false || (!empty([:wep_kind:]) && [:wep_kind:] == 'D')",
		),
	),
	'c5_focus' => Array
	(
		'name' => '专注',
		'tags' => Array('passive'),
		'desc' => "你可随意于下列三个状态间切换：",
		'clog' => "切换了「专注」的状态。",
		'choice' => Array(0,1,2), //无效果/重视遇敌/重视探物
		'svars' => Array(
			'choice' => 1, 
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
		'wepk' => Array('D'),
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
			'wepk+wep_kind' => "strpos([:wepk:],'D')!==false || (!empty([:wep_kind:]) && [:wep_kind:] == 'D')",
		),
	),
	'c5_double' => Array
	(
		'name' => '双响',
		'tags' => Array('battle','limit'),
		'wepk' => Array('D'),
		'desc' => '本局已发动<span class="redseed"> [^skillpara|c5_double-active_t^]/[:maxactive_t:] </span>次<br>使用爆系武器方可发动，'.$sktrapidatk.'[:chase_t:]次。',
		'bdesc' => '本次战斗你将'.$sktrapidatk.'[:chase_t:]次；本局已发动<span class="redseed">[^skillpara|c5_double-active_t^]/[:maxactive_t:]</span>次',
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
			'wepk+wep_kind' => "strpos([:wepk:],'D')!==false || (!empty([:wep_kind:]) && [:wep_kind:] == 'D')",
		),
	),
	'c9_kotodama' => Array
	(
		'name' => '言灵', //未完成
		'tags' => Array('passive'),
		'desc' => '使用灵力武器主动攻击敌人时，可通过喊话触发特殊效果<br>
		升级该技能可解锁更多触发关键词，以下是目前可触发的关键词：',
		'maxlvl' => 3,
		'cost' => Array(3,3,4,-1),
		'input' => '升级',
		'log' => '<span class="yellow">技能「言灵」升级成功。</span>',
		'status' => Array('skillpara|c9_kotodama-lvl'),
		'effect' => Array(
			0 => Array('skillpara|c9_kotodama-lvl' => '+=::1'),
		),
		'svars' => Array(
			'lvl' => 0, 
		),
		'vars' => Array(
		),
	),
	'c9_spirit' => Array
	(
		'name' => '灵力',
		'tags' => Array('passive'),
		'desc' => '敌人攻击你时，其命中率降低<span class="yellow">[:accloss:]%</span>，连击命中率惩罚<span class="yellow">+[:rbloss:]%</span><br>
		你使用灵系武器的体力消耗降低<span class="yellow">[:spcloss:]%</span>',
		'maxlvl' => 3,
		'cost' => Array(3,3,4,-1),
		'input' => '升级',
		'log' => '<span class="yellow">技能「灵力」升级成功。</span>',
		'status' => Array('skillpara|c9_spirit-lvl'),
		'effect' => Array(
			0 => Array('skillpara|c9_spirit-lvl' => '+=::1'),
		),
		'svars' => Array(
			'lvl' => 0, //初次获得时等级为0
		),
		'vars' => Array(
			'accloss' => Array(0,4,8,12), 
			'rbloss' => Array(0,2,3,4),
			'spcloss' => Array(40,50,60,70),
		),
	),
	'c9_lb' => Array
	(
		'name' => '必杀',
		'tags' => Array('battle'),
		'desc' => '本次攻击造成物理伤害<span class="yellow">×[:phydmgr:]</span><br>
		消耗<span class="yellow">[:ragecost:]</span>点怒气，若拥有<span class="yellow">重击辅助</span>属性会额外返还<span class="yellow">[:rageback:]</span>点怒气',
		'bdesc' => '本次攻击物理伤害<span class="yellow">×[:phydmgr:]</span>，消耗<span class="red">[:ragecost:]</span>怒气',
		'vars' => Array(
			'ragecost' => 40,
			'rageback' => 6,
			'phydmgr' => 2, 
		),
		'lockdesc' => Array(
			'lvl' => '3级时解锁',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 3',
		),
	),
	'c9_iceheart' => Array
	(
		'name' => '冰心',
		'tags' => Array('passive'),
		'desc' => '使用灵力武器攻击时，你受到的反噬伤害降低<span class="yellow">[:hpshloss:]%</span><br>
		受到伤害时，即刻解除<span class="yellow">[:purify:]</span>个异常/受伤状态。<br>
		每通过技能解除1个异常/受伤状态，你的怒气提升<span class="yellow">[:ragegain:]</span>点',
		'vars' => Array(
			'hpshloss' => 80,
			'purify' => 1, 
			'ragegain' => 40, 
		),
		'lockdesc' => Array(
			'lvl' => '7级时解锁',
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">灵力武器</span>时生效',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 7',
			'wepk+wep_kind' => "strpos([:wepk:],'F')!==false || (!empty([:wep_kind:]) && [:wep_kind:] == 'F')",
		),
	),
	'c9_charge' => Array
	(
		'name' => '充能',
		'tags' => Array('active'),
		'desc' => '发动后立即增加<span class="yellow">[:rageadd:]</span>点怒气。<br>
		前<span class="yellow">[:freet:]</span>次发动没有冷却时间，之后每次发动冷却时间<span class="clan">[:cd:]</span>秒<br>
		本局已发动：<span class="redseed"> [^skillpara|c9_charge-active_t^] </span>次',
		'input' => '发动',
		'log' => '<span class="lime">技能「充能」发动成功。</span><br>',
		'events' => Array('charge','active_news'),
		'status' => Array('skillpara|c9_charge-active_t'),
		'effect' => Array(
			0 => Array('skillpara|c9_charge-active_t' => '+=::1'),
		),
		'svars' => Array(
			'active_t' => 0,
		),
		'vars' => Array(
			'rageadd' => 100, 
			'freet' => 2,
			'cd' => 600, //冷却时间
		),
		'pvars' => Array(
			'skillpara|c9_charge-active_t',
		),
		'lockdesc' => Array(
			'lvl' => '11级时解锁',
			'skillcooldown' => '技能冷却中！<br>剩余冷却时间：<span class="red">[:cd:]</span> 秒',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 11',
			'skillcooldown' => 0,
		),
	),
	'c9_heartfire' => Array
	(
		'name' => '心火',
		'tags' => Array('battle'),
		'desc' => '本次攻击造成的最终伤害<span class="yellow">×[:findmgr:]</span>。消耗<span class="yellow">[:ragecost:]</span>点怒气<br>',
		'bdesc' => '本次攻击最终伤害<span class="yellow">×[:findmgr:]</span>，消耗<span class="red">[:ragecost:]</span>怒气',
		'vars' => Array(
			'ragecost' => 60,
			'findmgr' => 2, 
		),
		'lockdesc' => Array(
			'lvl' => '19级时解锁',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 19',
		),
	),
	'c6_godluck' => Array
	(
		'name' => '天运',
		'tags' => Array('passive'),
		'desc' => '升级后随机提升以下两类属性中任一项<span class="yellow">[:flucmin:]~[:flucmax:]%</span><br>
		<span class="grey">(1)闪避率 +[^skillpara|c6_godluck-accloss^]%；敌人连击命中率 -[^skillpara|c6_godluck-rbloss^]%<br>
		(2)命中率 +[^skillpara|c6_godluck-accgain^]%；连击命中率 +[^skillpara|c6_godluck-rbgain^]%</span>',
		'maxlvl' => 10,
		'cost' => Array(1,1,2,2,2,3,3,3,4,4,-1),
		'input' => '升级',
		'log' => '<span class="yellow">技能「天运」升级成功。</span><br>',
		'events' => Array('c6_godluck'),
		'status' => Array('skillpara|c6_godluck-lvl'),
		'effect' => Array(
			0 => Array('skillpara|c6_godluck-lvl' => '+=::1',),
		),
		'svars' => Array(
			'lvl' => 0,
			'accgain' => 0, 'rbgain' => 0, 'accloss' => 0, 'rbloss' => 0,
		),
		'vars' => Array(
			'flucmin' => 1, 
			'flucmax' => 3, 
		),
		'pvars' => Array('skillpara|c6_godluck-accgain','skillpara|c6_godluck-rbgain','skillpara|c6_godluck-accloss','skillpara|c6_godluck-rbloss'),
	),
	'c6_godsend' => Array
	(
		'name' => '天助',
		'tags' => Array('passive'),
		'desc' => '升级后随机提升以下两类属性中的任一项<span class="yellow">[:flucmin:]~[:flucmax:]%</span><br>
		<span class="grey">(1)隐蔽率 +[^skillpara|c6_godsend-hidegain^]%；先攻率 +[^skillpara|c6_godsend-actgain^]%<br>
		(2)反击率 +[^skillpara|c6_godsend-countergain^]% </span>',
		'maxlvl' => 10,
		'cost' => Array(2,2,2,2,2,4,4,4,4,4,-1),
		'input' => '升级',
		'log' => '<span class="yellow">技能「天助」升级成功。</span><br>',
		'events' => Array('c6_godsend'),
		'status' => Array('skillpara|c6_godsend-lvl'),
		'effect' => Array(
			0 => Array('skillpara|c6_godsend-lvl' => '+=::1',),
		),
		'svars' => Array(
			'lvl' => 0,
			'actgain' => 0, 'countergain' => 0, 'hidegain' => 0,
		),
		'vars' => Array(
			'flucmin' => 1, 
			'flucmax' => 3, 
		),
		'pvars' => Array('skillpara|c6_godsend-actgain','skillpara|c6_godsend-countergain','skillpara|c6_godsend-hidegain'),
	),
	'c6_godbless' => Array
	(
		'name' => '天佑',
		'tags' => Array('passive'),
		'desc' => '如果你受到不低于<span class="yellow">[:actmhp:]%</span>最大生命值的战斗或陷阱伤害<br>
		但存活，之后的<span class="yellow">[:lasttimes:]</span>秒内你免疫一切战斗和陷阱伤害
		<span tooltip="无效NPC：红杀将军、红杀菁英、英雄、武神、天神、巫师、使徒、■■"><a>（对部分NPC无效）</a></span><br>',
		'link' => Array('buff_godbless'),
		'vars' => Array(
			'actmhp' => 35,
			'lasttimes' => 5,
		),
	),
	'buff_godbless' => Array
	(
		'name' => '[状态]天佑',
		'tags' => Array('buff'),
		'desc' => '<span class="lime">「天佑」生效中！<br>
		增益效果剩余时间：<span class="yellow">[^lasttimes^]</span>秒</span>',
		'vars' => Array(
			'no_type' => Array(1,9,20,21,22,23,24,88),//无效NPC
		),
		'slast' => Array(
			'lasttimes' => 30, //真正作用的持续时间
		),
		'pvars' => Array('lasttimes'),
	),
	'c6_godpow' => Array
	(
		'name' => '天威',
		'tags' => Array('battle'),
		'desc' => '计算武器熟练度时额外增加<span class="yellow"><span tooltip="(怒气×等级/6)">([^rage^]×[^lvl^]/6)</span></span>点<br>
		(最高[:skmax:]点)，发动消耗<span class="yellow">[:ragecost:]</span>点怒气<br>
		若击杀敌人且伤害不超过其生命值[:mhpr:]倍，则返还<span class="yellow">[:rageback:]</span>点怒气',
		'bdesc' => '计算熟练度时增加<span class="yellow">([^rage^]×[^lvl^]/6)</span>点(最高220点)，消耗<span class="red">[:ragecost:]</span>怒气',
		'vars' => Array(
			'ragecost' => 25,
			'rageback' => 25, 
			'skmax' => 220,
			'mhpr' => 1.5,
		),
		'pvars' => Array('rage','lvl'),
		'lockdesc' => Array(
			'lvl' => '5级时解锁',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 5',
		),
	),
	'c6_godeyes' => Array
	(
		'name' => '天眼',
		'tags' => Array('passive'),
		'desc' => '在战斗界面你可以查看到对手的具体数值信息<br>
		且无视天气影响',
		'lockdesc' => Array(
			'lvl' => '7级时解锁',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 7',
		),
	),
	'c6_justice' => Array
	(
		'name' => '天义',
		'tags' => Array('passive'),
		'desc' => '你的武器视为具有<span class="yellow">冲击属性</span><br>
		敌人物理伤害防御类属性与物理抹消属性失效几率<span class="yellow">×[:pdefbkr:]</span>',
		'vars' => Array(
			'pdefbkr' => '3',
		),
		'lockdesc' => Array(
			'lvl' => '15级时解锁',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 15',
		),
	),
	'c7_radar' => Array
	(
		'name' => '探测',
		'desc' => '消耗<span class="lime">1</span>技能点，进行一次广域探测',
		'cost' => 1,
		'input' => '探测',
		'no_reload_page' => 1,
		'log' => '消耗了<span class="lime">[:cost:]</span>技能点，激活了广域探测功能。<br>',
		'events' => Array('radar'),
	),
	'c7_shield' => Array
	(
		'name' => '护盾',
		'tags' => Array('passive'),
		'desc' => "进入战斗时，若生命值低于<span class='yellow'>[:hpalert:]%</span>，生成一个拥有<span class='yellow'>[:svar:]</span>点效果的$sktpshield<br>
		护盾值耗尽后，需要等待<span class='clan'>[:cd:]</span>秒才能重新激活。",
		'maxlvl' => 5,
		'cost' => Array(4,4,5,7,9,-1),
		'input' => '升级',
		'log' => '<span class="yellow">技能「护盾」升级成功。</span><br>',
		'status' => Array('skillpara|c7_shield-lvl'),
		'effect' => Array(
			0 => Array('skillpara|c7_shield-lvl' => '+=::1',),
		),
		'svars' => Array(
			'lvl' => 0,
			'accgain' => 0, 'rbgain' => 0, 'accloss' => 0, 'rbloss' => 0,
		),
		'vars' => Array(
			'svar' => Array(110,155,185,225,265,355), 
			'cd' => Array(150,120,120,90,60,45),
			'hpalert' => Array(35,40,45,50,60,70),
		),
		'lockdesc' => Array(
			'skillpara|buff_shield-svar' => '护盾已存在，无法重复生成！',
			'skillcooldown' => '护盾充能中！<br>充能所需时间：<span class="red">[:cd:]</span> 秒',
		),
		'unlock' => Array(
			'skillpara|buff_shield-svar' => 'empty([:skillpara|buff_shield-svar:])',
			'skillcooldown' => 0,
		),
	),
	'c7_electric' => Array
	(
		'name' => '磁暴',
		'tags' => Array('battle'),
		'desc' => '消耗<span class="yellow">[:ragecost:]</span>点怒气，本次攻击<span class="yellow">带电</span>，电击属性伤害<span class="yellow">+[:exdmgfix:]</span>点，
		且有<span class="yellow">[:infr:]%</span>概率使敌人陷入<span class="yellow">麻痹</span>状态。<br>
		若敌人已处于<span class="yellow">麻痹</span>状态，则<span class="yellow">眩晕</span>敌人<span class="clan">[:lasttimes:]</span>秒',
		'bdesc' => '本次攻击<span class="yellow">带电</span>，电击属性伤害<span class="yellow">+[:exdmgfix:]</span>，有<span class="yellow">[:infr:]%</span>概率<span class="yellow">麻痹</span>敌人，或使已麻痹敌人眩晕<span class="yellow">[:lasttimes:]</span>秒；消耗<span class="red">[:ragecost:]</span>怒气',
		'vars' => Array(
			'ragecost' => 25,
			'exdmgfix' => 60, 
			'infr' => 40,
			'lasttimes' => 2,
		),
		'lockdesc' => Array(
			'lvl' => '3级时解锁',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 3',
		),
	),
	'c7_field' => Array
	(
		'name' => '力场',
		'tags' => Array('active'),
		'desc' => "消耗<span class=\"lime\">[:cost:]</span>技能点，无视冷却立刻激活一个$sktpshield",
		'cost' => 2,
		'input' => '激活',
		'log' => '<span class="yellow">「护盾」已激活！</span><br>',
		'events' => Array('getskill_buff_shield','setskillvars_buff_shield|c7_shield|svar','active_news'),
		'link' => Array('c7_shield'),
		'lockdesc' => Array(
			'lvl' => '5级时解锁',
			'skillpara|buff_shield-svar' => '护盾已存在，无法重复生成！',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 5',
			'skillpara|buff_shield-svar' => 'empty([:skillpara|buff_shield-svar:])',
		),
	),
	'buff_shield' => Array
	(
		'name' => '[状态]护盾',
		'tags' => Array('buff'),
		'desc' => '<span class="lime"><span class="gold" tooltip2="【护盾】：可抵消等同于护盾值的伤害。护盾值只在抵消属性伤害时消耗，抵消电击伤害时双倍消耗。护盾存在时不会受到反噬伤害或陷入异常状态。">护盾</span>生效中！<br>
		当前护盾值：<span class="yellow">[^skillpara|buff_shield-svar^]</span> 点</span>',
		'svars' => Array('svar' => 0),
		'pvars' => Array('skillpara|buff_shield-svar'),
		'lostevents' => Array('setstarttimes_c7_shield'),
	),
	'c7_overload' => Array
	(
		'name' => '过载',
		'tags' => Array('passive'),
		'desc' => '你造成的电击伤害没有上限',
		'lockdesc' => Array(
			'lvl' => '15级时解锁',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 15',
		),
	),
	'c7_emp' => Array
	(
		'name' => '脉冲',
		'tags' => Array('battle','limit'),
		'desc' => '本局已发动<span class="redseed"> [^skillpara|c7_emp-active_t^]/[:maxactive_t:] </span>次<br>
		消耗<span class="yellow">[:ragecost:]</span>点怒气，同时无效化你与敌人的<span class="yellow">抹消/制御类</span>属性，<br>
		成功无效化时，使敌人进入<span class="yellow">麻痹</span>状态。<br>
		若敌人已处于<span class="yellow">麻痹</span>状态，则眩晕敌人<span class="clan">[:lasttimes:]</span>秒<br>',
		'bdesc' => '无效化双方的<span class="yellow">抹消/制御类</span>属性，并<span class="yellow">麻痹</span>敌人，或使已麻痹敌人眩晕<span class="yellow">[:lasttimes:]</span>秒；
		消耗<span class="red">[:ragecost:]</span>怒气<br>本局已发动<span class="redseed"> [^skillpara|c7_emp-active_t^]/[:maxactive_t:] </span>次',
		'vars' => Array(
			'ragecost' => 60,
			'maxactive_t' => 2, 
			'lasttimes' => 3,
		),
		'svars' => Array('active_t' => 0),
		'pvars' => Array('skillpara|c7_emp-active_t'),
		'lockdesc' => Array(
			'skillpara|c7_emp-active_t' => '次数耗尽，已无法发动该技能',
			'lvl' => '21级时解锁',
		),
		'unlock' => Array(
			'skillpara|c7_emp-active_t' => '[:skillpara|c7_emp-active_t:] < 2',
			'lvl' => '[:lvl:] >= 21',
		),
	),
	'c8_expert' => Array
	(
		'name' => '特攻',
		'tags' => Array('passive'),
		'desc' => '你造成的最终属性伤害提高<span class="yellow">[:exdmgr:]%</span>',
		'maxlvl' => 4,
		'cost' => Array(6,6,6,6,-1),
		'input' => '升级',
		'log' => '<span class="yellow">技能「特攻」升级成功。</span><br>',
		'status' => Array('skillpara|c8_expert-lvl'),
		'effect' => Array(
			0 => Array('skillpara|c8_expert-lvl' => '+=::1',),
		),
		'svars' => Array('lvl' => 0),
		'vars' => Array(
			'exdmgr' => Array(10,20,30,40,50), 
		),
	),
	'c8_infilt' => Array
	(
		'name' => '渗透',
		'tags' => Array('passive'),
		'desc' => '当你处于<span class="purple">中毒</span>状态时，攻击额外附加<span class="yellow">[:exext:]</span>次毒属性攻击，<br>
		且有<span class="yellow">[:infr:]%</span>概率使敌人陷入<span class="purple">中毒</span>状态，并使敌人包裹内的补给<span class="purple">带毒</span>',
		'maxlvl' => 6,
		'cost' => Array(2,3,4,5,6,9,-1),
		'input' => '升级',
		'log' => '<span class="yellow">技能「渗透」升级成功。</span><br>',
		'status' => Array('skillpara|c8_infilt-lvl'),
		'effect' => Array(
			0 => Array('skillpara|c8_infilt-lvl' => '+=::1',),
		),
		'svars' => Array('lvl' => 0),
		'vars' => Array(
			'exext' => Array(1,1,1,2,2,2,3),
			'infr' => Array(0,10,20,30,40,50,60),
		),
		'lockdesc' => Array(
			'inf' => '自身处于<span class="purple">中毒</span>状态时才可触发',
		),
		'unlock' => Array(
			'inf' => "strpos([:inf:],'p')!==false",
		),
	),
	'c8_catalyst' => Array
	(
		'name' => '催化',
		'tags' => Array('battle'),
		'desc' => '消耗<span class="yellow">[:ragecost:]</span>点怒气，<br>
		本次攻击每造成1次毒属性伤害，最终属性伤害<span class="yellow">+[:exdmgr:]%</span>',
		'bdesc' => '本次攻击每造成1次<span class="purple">毒</span>属性伤害，最终属性伤害<span class="yellow">+[:exdmgr:]%</span>；消耗<span class="red">[:ragecost:]</span>怒气',
		'vars' => Array(
			'ragecost' => 50,
			'exdmgr' => 25, 
		),
		'lockdesc' => Array(
			'lvl' => '7级时解锁',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 7',
		),
	),
	'c8_deadheal' => Array
	(
		'name' => '死疗',
		'tags' => Array('passive'),
		'desc' => '不再受到<span class="purple">毒性</span>伤害，并将原本伤害的<span class="yellow">[:exdmgr:]%</span>转化为治疗效果',
		'vars' => Array(
			'exdmgr' => 75, 
		),
		'lockdesc' => Array(
			'lvl' => '12级时解锁',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 12',
		),
	),
	'c8_assassin' => Array
	(
		'name' => '暗杀',
		'tags' => Array('active','limit'),
		'desc' => '本局已发动<span class="redseed"> [^skillpara|c8_assassin-active_t^]/[:maxactive_t:] </span>次<br>
		发动后获得以下增益：隐蔽率<span class="yellow">+[:hidegain:]%</span>，先制率<span class="yellow">+[:actgain:]%</span>，持续<span class="yellow">60</span>秒；<br>
		增益持续时间内发动攻击会解除增益，但使此次攻击<span class="yellow">必中</span>，<br>
		且敌人防御、抹消、制御类属性失效(贯穿)率<span class="yellow">+[:pdefbkr:]%</span>',
		'input' => '发动',
		'log' => '<span class="lime">技能「暗杀」发动成功。</span><br>',
		'status' => Array('skillpara|c8_assassin-active','skillpara|c8_assassin-active_t'),
		'effect' => Array(
			0 => Array(
				'skillpara|c8_assassin-active' => '=::1',
				'skillpara|c8_assassin-active_t' => '+=::1',
			),
		),
		'events' => Array('getskill_buff_assassin','active_news'),
		'link' => Array('buff_assassin'),
		'vars' => Array(
			'maxactive_t' => 2, 
		),
		'svars' => Array('active' => 0, 'active_t' => 0,),
		'pvars' => Array('skillpara|c8_assassin-active_t'),
		'lockdesc' => Array(
			'skillpara|c8_assassin-active_t' => '次数耗尽，已无法发动该技能',
			'lvl' => '21级时解锁',
			'skillpara|c8_assassin-active' => '技能发动中！',
		),
		'unlock' => Array(
			'skillpara|c8_assassin-active_t' => '[:skillpara|c8_assassin-active_t:] < 2',
			'lvl' => '[:lvl:] >= 21',
			'skillpara|c8_assassin-active' => 'empty([:skillpara|c8_assassin-active:])',
		),
	),
	'buff_assassin' => Array
	(
		'name' => '[状态]暗杀',
		'tags' => Array('buff'),
		'desc' => '<span class="lime">「暗杀」生效中！<br>
		增益效果剩余时间：<span class="yellow">[^lasttimes^]</span> 秒</span>',
		'vars' => Array(
			'hidegain' => 90, 
			'actgain' => 100, 
			'pdefbkr' => 25,
		),
		'slast' => Array(
			'lasttimes' => 60,
		),
		'pvars' => Array('lasttimes'),
		'lostevents' => Array('unactive_c8_assassin'),
	),
	'c10_inspire' => Array
	(
		'name' => '灵感',
		'tags' => Array('active'),
		'desc' => "选定一个称号，升级本技能时将<span class='yellow'>随机</span>获得一个选定称号的<span class='yellow'>技能</span><br>
		（可能会重复获得）<br>",
		'maxlvl' => 8,
		'cost' => Array(4,5,7,9,11,14,17,20,-1),
		'input' => '思考',
		'log' => '……<br>',
		'choice' => Array(1,2,3,4,5,6,7,8,9,12), //无效果/重视遇敌/重视探物
		'clog' => '<span class="yellow">切换了选定称号。</span><br>',
		'events' => Array('inspire'),
		'status' => Array('skillpara|c10_inspire-lvl'),
		'effect' => Array(
			0 => Array('skillpara|c10_inspire-lvl' => '+=::1',),
		),
		'svars' => Array(
			'lvl' => 0,
			'choice' => 1, 
		),
	),
	'c10_insight' => Array
	(
		'name' => '洞察',
		'tags' => Array('passive'),
		'desc' => '敌人所用武器熟练度低于你的<span class="gold" tooltip2="你当前所持武器熟练度+(其他系别熟练度×0.25)">战斗熟练度</span>时，<br>
		你对其命中率<span class="yellow">+[:accgain:]%</span>；先制率<span class="yellow">+[:actgain:]%</span><br>
		敌人对你的命中率<span class="yellow">-[:accloss:]%</span>；连击命中率<span class="yellow">-[:rbloss:]%</span>',
		'maxlvl' => 4,
		'cost' => Array(2,3,4,6,-1),
		'input' => '升级',
		'log' => '<span class="yellow">「洞察」升级成功。</span><br>',
		'status' => Array('skillpara|c10_insight-lvl'),
		'effect' => Array(
			0 => Array('skillpara|c10_insight-lvl' => '+=::1',),
		),
		'svars' => Array('lvl' => 0),
		'vars' => Array(
			'accgain' => Array(5,7,10,17,30),
			'actgain' => Array(3,5,8,12,17),
			'accloss' => Array(3,6,11,15,17),
			'rbloss' => Array(4,7,14,18,22),
		),
	),
	'c10_decons' => Array
	(
		'name' => '解构',
		'tags' => Array('battle'),
		'desc' => '消耗<span class="yellow">[:ragecost:]</span>点怒气，本次攻击物理伤害<span class="yellow">+[:phydmgr:]%</span><br>
		击杀敌人时，额外获得<span class="lime">敌人等级-(0.15×<span tooltip2="等同于你当前等级">[^lvl^])</span></span>点经验',
		'bdesc' => '物理伤害<span class="yellow">+[:phydmgr:]%</span>,击杀时额外获得<span class="lime">敌人等级-(0.15×<span tooltip2="等同于你当前等级">[^lvl^]</span>)</span>点经验；消耗<span class="red">[:ragecost:]</span>怒气',
		'vars' => Array(
			'ragecost' => 18,
			'phydmgr' => 20, 
		),
		'pvars' => Array('lvl'),
		'lockdesc' => Array(
			'lvl' => '3级时解锁',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 3',
		),
	),
	'c11_ebuy' => Array
	(
		'name' => '网购',
		'tags' => Array('passive'),
		'desc' => '你可以在任意地图访问商店',
	),
	'c11_tutor' => Array
	(
		'name' => '家教', //不太合适
		'tags' => Array('active'),
		'desc' => "通过培训机构<span class='yellow'>随机</span>学习一个<span class='yellow'>技能</span><br>
		（可能会重复获得）",
		'input' => '学习',
		'log' => '……<br>',
		'events' => Array('inspire'),
	),
	'c11_merc' => Array
	(
		'name' => '佣兵', 
		'tags' => Array('active','limit'),
		'desc' => "本局已发动<span class=\"redseed\"> [^skillpara|c11_merc-active_t^]/[:maxactive_t:] </span>次<br>
		消耗<span class='yellow'>[:mcost:]</span>元，在当前地点随机召唤一名佣兵；<br>
		雇佣关系存在时，你可以指挥佣兵<span class='gold' tooltip2='遭遇敌人时，可花费一定金钱命令与你在同一地点的佣兵主动攻击敌人，佣兵主动攻击敌人后会【标记】敌人。【标记】在你或佣兵离开地图前将一直存在，存在时可通过佣兵面板继续对佣兵下达【追击】指令。'>主动出击</span>，
		或从旁<span class='gold' tooltip2='当你攻击敌人且敌人未死亡时，与你在同一地点的佣兵有概率主动为你助战，概率取决于佣兵与你的关系。'>协战</span>；<br>
		被雇佣后，佣兵会在你累计探索/移动次数达<span class='yellow'>[:mst:]</span>次时要求结算一次工资<br>
		被拖欠工资的佣兵不会再为你服务(可能会暴力讨薪)<br>",
		'input' => '雇佣',
		'no_reload_page' => 1,
		'log' => '……这是个啥呀！<br>',
		'status' => Array('skillpara|c11_merc-active_t'),
		'effect' => Array(
			0 => Array('skillpara|c11_merc-active_t' => '+=::1',),
		),
		'events' => Array('hiremerc','active_news'),
		'svars' => Array(
			'active_t' => 0,
		),
		'vars' => Array(
			'mcost' => 1500,
			'mst' => 25,
			'movep' => 2, //移动佣兵花费
			'atkp' => 10, //主动出击花费
			'maxactive_t' => 4,
		),
		'pvars' => Array(
			'lvl',
			'skillpara|c11_merc-active_t',
		),
		'lockdesc' => Array(
			'skillpara|c11_merc-active_t' => '次数耗尽，已无法再召唤佣兵',
			'money' => '招募佣兵至少需要1500元！',
		),
		'unlock' => Array(
			'skillpara|c11_merc-active_t' => '[:skillpara|c11_merc-active_t:] < 4',
			'money' => '[:money:] >= 1500',
		),
	),
	'c11_stock' => Array
	(
		'name' => '理财', 
		'tags' => Array('passive'),
		'desc' => "每探索/移动<span class='yellow'>[:mst:]</span>次，你所持金钱增加<span class='yellow'>[:earn:]%</span>；<br>
		所加金钱数最低不会低于<span class='yellow'>[:minmoney:]</span>元，最高不会超过<span class='yellow'>[:maxmoney:]</span>元<br>
		<span class='grey'>当前已探索/移动次数：[^skillpara|c11_stock-ms^] 次</span>",
		'svars' => Array('ms' => 0),
		'vars' => Array(
			'mst' => 50,
			'earn' => 20,
			'minmoney' => 100,
			'maxmoney' => 2500, 
		),
		'pvars' => Array('lvl','skillpara|c11_stock-ms'),
		'lockdesc' => Array(
			'lvl' => '7级时解锁',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 7',
		),
	),
	'c11_renjie' => Array
	(
		'name' => '人杰', 
		'tags' => Array('passive'),
		'desc' => "战斗中，你的熟练度始终取用最高熟练值。",
		'lockdesc' => Array(
			'lvl' => '19级时解锁',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 19',
		),
	),
	'c12_huge' => Array
	(
		'name' => '瞩目',
		'tags' => Array('passive'),
		'desc' => '你对敌人的隐蔽率<span class="yellow">-[:hidegain:]%</span>；敌人对你的隐蔽率<span class="yellow">-[:hideloss:]%</span>',
		'vars' => Array(
			'hidegain' => 100, 
			'hideloss' => 75,
		),
	),
	'c12_enmity' => Array
	(
		'name' => '底力',
		'tags' => Array('passive'),
		'desc' => '当前生命值越低，你造成的最终伤害越高<br>
		最终伤害增幅：<span class="yellow">[:findmgr:]%</span>×<span class="gold" tooltip2="底力系数计算公式：(1+2×已损失生命百分比)×已损失生命百分比">底力系数</span>',
		'maxlvl' => 6,
		'cost' => Array(1,1,2,2,2,3,-1),
		'input' => '升级',
		'log' => '<span class="yellow">「底力」升级成功。</span><br>',
		'status' => Array('skillpara|c12_enmity-lvl'),
		'effect' => Array(
			0 => Array('skillpara|c12_enmity-lvl' => '+=::1',),
		),
		'svars' => Array('lvl' => 0),
		'vars' => Array(
			'findmgr' => Array(10,15,20,25,35,45,55),
		),
	),
	'c12_garrison' => Array
	(
		'name' => '根性',
		'tags' => Array('passive'),
		'desc' => '当前生命值越低，基础防御力越高<br>
		基础防御力增幅：<span class="yellow">[:defgain:]%</span>×<span class="gold" tooltip2="根性系数计算公式：(-1×已损失生命百分比^3)+4×已损失生命百分比">根性系数</span>',
		'maxlvl' => 8,
		'cost' => Array(2,2,2,3,4,5,7,11,-1),
		'input' => '升级',
		'log' => '<span class="yellow">「底力」升级成功。</span><br>',
		'status' => Array('skillpara|c12_garrison-lvl'),
		'effect' => Array(
			0 => Array('skillpara|c12_garrison-lvl' => '+=::1',),
		),
		'svars' => Array('lvl' => 0),
		'vars' => Array(
			'defgain' => Array(19,24,29,34,42,52,63,75,90),
		),
	),
	'c12_rage' => Array
	(
		'name' => '狂怒',
		'tags' => Array('battle'),
		'desc' => "消耗相当于<span class=\"red\">[:hpcost:]%</span>生命上限的生命值，<br>
		附加等于<span class=\"yellow\">所消耗生命值</span>且受<span class=\"yellow\">「底力」</span>加成的{$sktpwhitedmg}<br>
		发动需消耗<span class=\"yellow\">[:ragecost:]</span>点怒气",
		'bdesc' => "消耗<span class=\"red\">[:hpcost:]%</span>生命值，附加等于消耗值且受<span class=\"yellow\">「底力」</span>加成的{$sktpwhitedmg}；发动需消耗<span class=\"red\">[:ragecost:]</span>怒气",
		'vars' => Array(
			'ragecost' => 50,
			'hpcost' => 25, 
		),
		'lockdesc' => Array(
			'lvl' => '7级时解锁',
			'hp+mhp' => '生命值在<span class="red">25%</span>以上时可发动',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 7',
			'hp+mhp' => '[:hp:] > [:mhp:]*0.25',
		),
	),
	'c12_bloody' => Array
	(
		'name' => '浴血',
		'tags' => Array('passive'),
		'desc' => '在生命值低于<span class="yellow">75%/50%/30%</span>生命上限的情况下，<br>
		击杀敌人增加<span class="yellow">2/3/11</span>点基础攻击与<span class="yellow">4/5/15</span>点基础防御',
		'vars' => Array(
			'hplimit' => Array(75,50,30),
			'attgain' => Array(2,3,11),
			'defgain' => Array(4,5,15),
		),
		'lockdesc' => Array(
			'lvl' => '13级时解锁',
			'hp+mhp' => '生命值低于<span class="red">75%</span>时可触发',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 13',
			'hp+mhp' => '[:hp:] <= [:mhp:]*0.75',
		),
	),
	'c12_swell' => Array
	(
		'name' => '海虎',
		'tags' => Array('passive'),
		'desc' => '在生命值低于<span class="yellow">50%/30%</span>生命上限的情况下，<br>
		有<span class="yellow">[:swellr:]%×</span><span class="gold" tooltip2="底力系数计算公式：(1+2×已损失生命百分比)×已损失生命百分比">底力系数</span>概率造成<span class="yellow">2/3</span>次'.$sktrapidatk.'',
		'vars' => Array(
			'swellr' => 19,
		),
		'lockdesc' => Array(
			'lvl' => '21级时解锁',
			'hp+mhp' => '生命值低于<span class="red">50%</span>时可触发',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 21',
			'hp+mhp' => '[:hp:] <= [:mhp:]*0.5',
		),
	),
	'c13_kungfu' => Array
	(
		'name' => '拳法',
		'tags' => Array('passive'),
		'desc' => '空手作战时，相当于持有等同于殴系熟练度数值的武器<br>
		攻击时有<span class="yellow">35%/15%/5%/3%</span>的几率额外获得<span class="yellow">1/2/3/4</span>点熟练<br>',
		'lockdesc' => Array(
			'wepk+wep_kind' => "空手时可发动",
		),
		'unlock' => Array(
			'wepk+wep_kind' => "strpos([:wepk:],'N')!==false || (!empty([:wep_kind:]) && [:wep_kind:] == 'N')",
		),
	),
	'c13_master' => Array
	(
		'name' => '宗师',
		'tags' => Array('passive'),
		'desc' => '手持武器时造成的物理伤害减少<span class="red">[:phydmgloss:]%</span>；<br>
		若武器是带“拳”字的钝器则减少<span class="red">[:phydmgloss_2:]%</span>；<br>
		你不能再埋设陷阱，且从陷阱处受到的伤害减少<span class="yellow">[:trapdmgloss:]%</span><br>',
		'vars' => Array
		(
			'phydmgloss' => 90, 
			'phydmgloss_2' => 50,
			'trapdmgloss' => 60,
		),
	),
	'c13_quick' => Array
	(
		'name' => '快拳',
		'tags' => Array('passive'),
		'desc' => '空手战斗时有<span class="yellow">[:rapidr:]%</span>概率'.$sktrapidatk.'2次',
		'maxlvl' => 4,
		'cost' => Array(3,3,4,4,-1),
		'input' => '升级',
		'log' => '<span class="yellow">技能「快拳」升级成功。</span><br>',
		'status' => Array('skillpara|c13_quick-lvl'),
		'effect' => Array(
			0 => Array('skillpara|c13_quick-lvl' => '+=::1',),
		),
		'svars' => Array('lvl' => 0),
		'vars' => Array(
			'rapidr' => Array(10,15,20,25,30),
		),
		'lockdesc' => Array(
			'wepk+wep_kind' => "空手时可发动",
		),
		'unlock' => Array(
			'wepk+wep_kind' => "strpos([:wepk:],'N')!==false || (!empty([:wep_kind:]) && [:wep_kind:] == 'N')",
		),
	),
	'c13_wingchun' => Array
	(
		'name' => '乱击',
		'tags' => Array('battle'),
		'maxlvl' => 2,
		'cost' => Array(6,9,-1),
		'input' => '升级',
		'log' => '<span class="yellow">技能「乱击」升级成功。</span><br>',
		'status' => Array('skillpara|c13_wingchun-lvl'),
		'effect' => Array(
			0 => Array('skillpara|c13_wingchun-lvl' => '+=::1',),
		),
		'svars' => Array('lvl' => 0),
		'desc' => "空手时可发动，消耗<span class=\"yellow\">[:ragecost:]</span>点怒气；<br>
		本次攻击附加<span class=\"yellow\">[:ragecost:]%</span>殴熟的物理伤害；<br>
		且「快拳」的发动率<span class=\"yellow\">+[:rapidr:]</span>%<br>",
		'bdesc' => "消耗<span class=\"red\">[:ragecost:]</span>点怒气，附加等于<span class=\"yellow\">[:phydmgr:]%</span>殴熟的物理伤害；本次攻击「快拳」的触发率<span class=\"yellow\">+[:rapidr:]</span>%",
		'vars' => Array(
			'ragecost' => Array(30,40,45),
			'phydmgr' => Array(25,33,50),
			'rapidr' => Array(5,15,25),
		),
		'lockdesc' => Array(
			'lvl' => '7级时解锁',
			'wepk+wep_kind' => "空手时可发动",
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 7',
			'wepk+wep_kind' => "strpos([:wepk:],'N')!==false || (!empty([:wep_kind:]) && [:wep_kind:] == 'N')",
		),
	),
	'c13_parry' => Array
	(
		'name' => '消力',
		'tags' => Array('passive'),
		'desc' => '你的基础防御力增加<span class="yellow">殴系熟练度</span>点；<br>
		战斗中，你有<span class="yellow">[:parryr:]%</span>几率消去<span class="yellow">殴系熟练度</span>点伤害（最多<span class="yellow">[:maxparry:]</span>点）<br>',
		'vars' => Array(
			'parryr' => 20,
			'maxparry' => 800,
		),
		'lockdesc' => Array(
			'lvl' => '11级时解锁',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 11',
		),
	),
	'c13_duel' => Array
	(
		'name' => '决战',
		'tags' => Array('active','limit'),
		'desc' => '发动后获得增益效果：<br>
		当前殴系熟练翻倍，但每次探索/移动时减少<span class="red">[:wploss:]</span>点殴熟；<br>
		技能生效时，「快拳」与「消力」的发动率<span class="yellow">+[:rapidr:]%</span>',
		'input' => '发动',
		'no_reload_page' => 1,
		'log' => '<span class="L5">你感觉一股力量贯通全身！</span><br>',
		'status' => Array('wp','skillpara|c13_duel-active_t'),
		'effect' => Array(
			0 => Array(
				'wp' => '*=::2',
				'skillpara|c13_duel-active_t' => '+=::1'
			),
		),
		'events' => Array('getskill_buff_duel','active_news'),
		'link' => Array('buff_duel'),
		'vars' => Array(),
		'svars' => Array(
			'active_t' => 0,
		),
		'lockdesc' => Array(
			'skillpara|c13_duel-active_t' => '次数耗尽，已无法发动该技能',
			'lvl' => '21级时解锁',
			'wp' => '需要至少250点殴熟才能发动！',
		),
		'unlock' => Array(
			'skillpara|c13_duel-active_t' => '[:skillpara|c13_duel-active_t:] < 1',
			'lvl' => '[:lvl:] >= 21',
			'wp' => '[:wp:] >= 250',
		),
	),
	'buff_duel' => Array
	(
		'name' => '[状态]决战',
		'tags' => Array('buff'),
		'desc' => '<span class="lime">「决战」生效中！',
		'vars' => Array(
			'wploss' => 5, //每次移动减少的欧熟
			'rapidr' => 20, //增加的技能发动率
		),
		'lockdesc' => Array(
			'wp' => '需要至少5点殴熟才能生效！',
		),
		'unlock' => Array(
			'wp' => '[:wp:] >= 5',
		),
	),
	'c19_nirvana' => Array
	(
		'name' => '涅槃', 
		'tags' => Array('passive','limit'),
		'desc' => "本局已生效<span class=\"redseed\"> [^skillpara|c19_nirvana-active_t^]/[:maxactive_t:] </span>次<br>
		因陷阱/战斗死亡时，转化所有的{$sktprp}并立刻复活<br>
		每转化<span class='yellow'>[:rpr:]</span>点{$sktprp}，复活后你的生命上限与防御力<span class='yellow'>+[:hpgain:]</span>",
		'svars' => Array(
			'active_t' => 0,
		),
		'vars' => Array(
			'maxactive_t' => 1,
			'rpr' => 2,
			'hpgain' => 1,
		),
		'pvars' => Array(
			'skillpara|c19_nirvana-active_t',
		),
		'lockdesc' => Array(
			'skillpara|c19_nirvana-active_t' => '次数耗尽，无法生效',
		),
		'unlock' => Array(
			'skillpara|c19_nirvana-active_t' => '[:skillpara|c19_nirvana-active_t:] < 1',
		),
	),
	'c19_reincarn' => Array
	(
		'name' => '转业',
		'tags' => Array('passive'),
		'desc' => "你的{$sktprp}增长量<span class=\"yellow\">-[:rpgain:]%</span>；降低量<span class=\"yellow\">+[:rploss:]%</span>",
		'maxlvl' => 6,
		'cost' => Array(1,2,3,4,5,6,-1),
		'input' => '升级',
		'log' => '<span class="yellow">「转业」升级成功。</span><br>',
		'status' => Array('skillpara|c19_reincarn-lvl'),
		'effect' => Array(
			0 => Array('skillpara|c19_reincarn-lvl' => '+=::1',),
		),
		'svars' => Array('lvl' => 0),
		'vars' => Array(
			'rpgain' => Array(7,15,24,34,45,57,69),
			'rploss' => Array(5,15,25,35,45,55,65),
		),
	),
	'c19_purity' => Array
	(
		'name' => '莹心',
		'tags' => Array('passive'),
		'desc' => '你受到的最终伤害降低<span class="yellow">[:findmgdefr:]%</span>；向敌人造成的最终伤害降低<span class="yellow">[:findmgr:]%</span>',
		'maxlvl' => 6,
		'cost' => Array(5,6,6,3,2,1,-1),
		'input' => '升级',
		'log' => '<span class="yellow">「莹心」升级成功。</span><br>',
		'status' => Array('skillpara|c19_purity-lvl'),
		'effect' => Array(
			0 => Array('skillpara|c19_purity-lvl' => '+=::1',),
		),
		'svars' => Array('lvl' => 0),
		'vars' => Array(
			'findmgdefr' => Array(15,30,45,60,80,85,90),
			'findmgr' => Array(7,25,35,60,85,90,98),
		),
	),
	'c19_crystal' => Array
	(
		'name' => '晶璧',
		'tags' => Array('active'),
		'desc' => "消耗<span class='yellow'>[:ragecost:]</span>点怒气，使战场内所有参战者获得{$sktpshield}<br>
		护盾值等于<span class='yellow'>(<span tooltip2='取决于你的报应点数'>[^rp^]</span>×[:sldr:]%)</span>的绝对值；<br>
		每使一位参战者(包括自己)获得{$sktpshield}，你的{$sktprp}下降<span class='yellow'>[:rploss:]</span>点",
		'input' => '发动',
		'no_reload_page' => 1,
		'log' => '……<br>',
		'events' => Array('crystal','active_news'),
		'status' => Array('skillpara|c19_crystal-lvl'),
		'effect' => Array(
			0 => Array('skillpara|c19_crystal-lvl' => '+=::1',),
		),
		'svars' => Array(
			'lvl' => 0,
		),
		'vars' => Array(
			'ragecost' => 75,
			'sldr' => 10,
			'rploss' => 160,
		),
		'pvars' => Array('rp'),
		'lockdesc' => Array(
			'lvl' => '7级时解锁',
			'rage' => '怒气不足，无法发动',
			'rp' => '报应点数为0，无法发动',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 7',
			'rage' => '[:rage:] >= 75',
			'rp' => '!empty([:rp:])',
		),
	),
	'c19_redeem' => Array
	(
		'name' => '祛障',
		'tags' => Array('battle'),
		'desc' => "消耗<span class=\"yellow\">[:ragecost:]</span>点怒气，本次攻击额外附加一段{$sktpwhitedmg}，<br>
		伤害量等于敌人与你的<span class=\"yellow\">报应点数之差</span>；<br>
		<span class=\"yellow\">差值为负</span>时不会造成伤害，但会将你的{$sktprp}部分转移给敌人；<br>
		转移量最低不低于<span class=\"yellow\">[:rpmin:]</span>，最高不超过敌人目前的{$sktprp}值",
		'bdesc' => "附加等于你与敌人<span class=\"yellow\">报应点数差值</span>的{$sktpwhitedmg}，或转移报应点数；发动需消耗<span class=\"red\">[:ragecost:]</span>怒气",
		'vars' => Array(
			'ragecost' => 40,
			'rpmin' => 100,
		),
		'lockdesc' => Array(
			'lvl' => '11级时解锁',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 11',
		),
	),
	'c19_dispel' => Array
	(
		'name' => '量心',
		'tags' => Array('switch'),
		'desc' => '技能效果开启时，你不会再直接击杀敌人。<br>
		造成伤害时，至少会为对方保留<span class="red">1</span>点生命；<br>
		同时，你不会再遭遇仅有<span class="red">1</span>点生命值的敌人，除非对方主动攻击你；<br>
		点击右侧的<span class="yellow">“切换”</span>键随时激活或禁用该技能<br>
		[^skill-active^]',
		'input' => '切换',
		'log' => '<span class="yellow">切换了「量心」的状态。</span>',
		'events' => Array('active|c19_dispel'),
		'svars' => Array(
			'active' => 0, 
		),
		'pvars' => Array('skill-active'),
		'lockdesc' => Array(
			'lvl' => '17级时解锁',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 17',
		),
	),
	'c19_woesea' => Array
	(
		'name' => '苦雨',
		'tags' => Array('active','limit'),
		'desc' => '本局已发动<span class="redseed"> [^skillpara|c19_woesea-active_t^]/[:maxactive_t:] </span>次<br>
		消耗<span class="yellow">[:sscost:]</span>点歌魂，将战场天气变更为<span class="minirainbow">光玉雨</span>；<br>
		<span class="minirainbow">光玉雨</span>持续<span class="yellow">[:wtht:]</span>秒，且不会被禁区或天气控制改变；<br>
		天气存在时，战场上所有参战者在行动时会<span class="yellow">超量</span>恢复<span class="yellow">生命、体力</span>；<br>
		技能发动者在该天气下<span class="yellow">先制率</span>提升，且死亡后有概率<span class="yellow">复活</span>；<br>
		强化效力、复活概率随天气的<span class="yellow">持续时间</span>逐渐增长，<br>
		在第<span class="yellow">7</span>分钟时达到峰值，之后渐弱<br>',
		'input' => '发动',
		'no_reload_page' => 1,
		'log' => '<br><br>',
		'status' => Array('skillpara|c19_woesea-active_t'),
		'effect' => Array(
			0 => Array(
				'skillpara|c19_woesea-active_t' => '+=::1',
			),
		),
		'events' => Array('woesea','active_news'),
		'vars' => Array(
			'sscost' => 100,
			'wtht' => 600,
			'maxactive_t' => 1, 
		),
		'svars' => Array('active_t' => 0,),
		'pvars' => Array('skillpara|c19_woesea-active_t'),
		'lockdesc' => Array(
			'skillpara|c19_woesea-active_t' => '次数耗尽，已无法发动该技能',
			'lvl' => '21级时解锁',
			'ss' => '需要100点歌魂才能发动！',
		),
		'unlock' => Array(
			'skillpara|c19_woesea-active_t' => '[:skillpara|c19_woesea-active_t:] < 1',
			'lvl' => '[:lvl:] >= 21',
			'ss' => '[:ss:] >= 100',
		),
	),
	'c20_fertile' => Array
	(
		'name' => '沃土',
		'tags' => Array('passive'),
		'desc' => '获得元素时，获得量<span class="yellow">+0%~[:emsgain:]%</span>；<br>
		每探索/移动<span class="yellow">[:mst:]</span>次，口袋中存量最低的元素数量<span class="yellow">+[:minemsgain:]%</span><br>
		<span class="grey">当前已探索/移动次数：[^skillpara|c20_fertile-ms^] 次</span>',
		'maxlvl' => 6,
		'cost' => Array(2,3,3,4,4,5,-1),
		'input' => '升级',
		'log' => '<span class="yellow">「沃土」升级成功。</span><br>',
		'status' => Array('skillpara|c20_fertile-lvl'),
		'effect' => Array(
			0 => Array('skillpara|c20_fertile-lvl' => '+=::1',),
		),
		'svars' => Array('lvl' => 0,'ms' => 0),
		'vars' => Array(
			'emsgain' => Array(1,2,2,3,3,4,5),
			'mst' => Array(35,35,30,30,25,25,20),
			'minemsgain' => Array(1,1,2,2,3,3,4),
		),
		'pvars' => Array('skillpara|c20_fertile-ms'),
	),
	'c20_windfall' => Array
	(
		'name' => '横财',
		'tags' => Array('active','cd'),
		'desc' => '清空你口袋中的所有元素，<br>
		然后以尽可能平均的方式重新获得它们。冷却时间<span class="clan">[:cd:]</span>秒',
		'input' => '发动',
		'log' => '……<br>',
		'events' => Array('windfall','setstarttimes_c20_windfall','active_news'),
		'vars' => Array(
			'cd' => 900, //冷却时间
		),
		'lockdesc' => Array(
			'skillcooldown' => '技能冷却中！<br>剩余冷却时间：<span class="red">[:cd:]</span> 秒',
		),
		'unlock' => Array(
			'skillcooldown' => 0,
		),
	),
	'c20_lighting' => Array
	(
		'name' => '闪电',
		'tags' => Array('battle'),
		'desc' => "消耗<span class='yellow'>[:ragecost:]</span>点怒气与<span class='yellow'>[:emcost:]</span>份随机元素，<br>
		根据所消耗元素种类附加{$sktpemsdmg}伤害；<br>
		累计发动次数达<span class='yellow'>(1+...+n)</span>次时，<br>
		消耗<span class='yellow'>(30×n)</span>份元素，同时触发<span class='yellow'>(n)</span>次效果；<br>
		<span class='grey'>当前累计发动次数：[^skillpara|c20_lighting-active_t^] 次</span>",
		'bdesc' => "消耗<span class='yellow'>[:emcost:]</span>份随机元素，根据所消耗元素种类附加{$sktpemsdmg}；消耗<span class='red'>[:ragecost:]</span>怒气</span>",
		'vars' => Array(
			'ragecost' => 15, 
			'emcost' => 30,
			'emextype' => Array( // 各类元素能造成的伤害类型
				0 => 'white',
				1 => 'u',
				2 => 'i',
				3 => 'p',
				4 => 'e',
				5 => 'w',
			),
		),
		'svars' => Array(
			'active_t' => 0,//技能发动次数
		),
		'pvars' => Array('skillpara|c20_lighting-active_t'),
		'lockdesc' => Array(
			'lvl' => '5级时解锁',
			'element0+element1+element2+element3+element4+element5' => '至少需要30份元素才能发动',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 5',
			'element0+element1+element2+element3+element4+element5' => "[:element0:]>=30 || [:element1:]>=30 || [:element2:]>=30 || [:element3:]>=30 || [:element4:]>=30 || [:element5:]>=30",
		),
	),
	'c20_zombie' => Array
	(
		'name' => '灵俑',
		'tags' => Array('passive'),
		'desc' => "发现尸体时，消耗等同于<span class=\"yellow\">尸体等级的平方根×提炼尸体可获得的元素数量</span>，将尸体复活为{$sktpzombie}；<br>
		复活后的{$sktpzombie}有<span class=\"yellow\">50%</span>概率为你<span class='gold' tooltip2='当你攻击敌人且敌人未死亡时，与你在同一地点的灵俑有概率主动为你助战。'>协战</span>，并在你受到攻击时，<br>
		为你抵挡最多不超过<span class=\"yellow\">[:maxdefhp:]%</span>灵俑当前生命的伤害",
		'vars' => Array(
			'maxdefhp' => 50, 
			'notype' => Array(1,9,19,88,92), //不能复活为灵俑的NPC
		),
		'lockdesc' => Array(
			'lvl' => '11级时解锁',
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 11',
		),
	),
	'c20_sparkle' => Array
	(
		'name' => '火花',
		'tags' => Array('switch','limit'),
		'desc' => '技能开启后，<br>
		造成伤害时有<span class="yellow">[:tpr:]%</span>概率将敌人传送至随机地点，<br>
		被传送者会受到轻微伤害、或遭遇意外；<br>
		火花持有者受到<span class="red">致命伤害</span>时，会紧急传送回避伤害，但<span class="red">永久失去</span>火花；<br>
		点击右侧的<span class="yellow">“切换”</span>键随时激活或禁用该技能<br>
		[^skill-active^]',
		'input' => '切换',
		'log' => '<span class="yellow">切换了「火花」的状态。</span>',
		'events' => Array('active|c20_sparkle'),
		'svars' => Array(
			'active' => 0, 
			'active_t' => 0,
		),
		'vars' => Array(
			'tpr' => 15,
			'maxactive_t' => 1,
		),
		'pvars' => Array('skill-active','skillpara|c20_sparkle-active_t'),
		'lockdesc' => Array(
			'skillpara|c20_sparkle-active_t' => '已失去火花。',
			'lvl' => '13级时解锁',
		),
		'unlock' => Array(
			'skillpara|c20_sparkle-active_t' => '[:skillpara|c20_sparkle-active_t:] < 1',
			'lvl' => '[:lvl:] >= 13',
		),
	),
	'c20_lotus' => Array
	(
		'name' => '黑莲',
		'tags' => Array('active','limit'),
		'desc' => '本局已献祭<span class="redseed"> [^skillpara|c20_lotus-active_t^]/[:maxactive_t:] </span>次<br>
		献祭黑莲花，口袋中所有元素存量<span class="yellow">x3</span><br>',
		'input' => '献祭',
		'no_reload_page' => 1,
		'log' => '<span class="mtgblack">你将一坨不知道从哪弄来的黑糊糊的东西扔进了元素口袋里……<br>
		片刻后，口袋里传来了令人毛骨悚然的咀嚼声……</span><br>
		……<br>',
		'status' => Array('skillpara|c20_lotus-active_t'),
		'effect' => Array(
			0 => Array(
				'skillpara|c20_lotus-active_t' => '+=::1',
			),
		),
		'events' => Array('lotus','active_news'),
		'vars' => Array(
			'emsgain' => 3,
			'maxactive_t' => 3, 
		),
		'svars' => Array('active_t' => 0,),
		'pvars' => Array('skillpara|c20_lotus-active_t'),
		'lockdesc' => Array(
			'skillpara|c20_lotus-active_t' => '黑莲花已经用光了。',
			'lvl' => '17级时解锁',
		),
		'unlock' => Array(
			'skillpara|c20_lotus-active_t' => '[:skillpara|c20_lotus-active_t:] < 3',
			'lvl' => '[:lvl:] >= 17',
		),
	),
	'inf_zombie' => Array
	(
		'name' => '灵俑',
		'tags' => Array('inf'),
		'desc' => '你不会受到反噬伤害，但不能造成除了毒性、冻气以外的属性伤害<br>
		你造成的最终伤害降低<span class="yellow">[:findmgloss:]%</span>，从敌人处受到的伤害降低<span class="yellow">[:findmgr:]%</span>',
		'vars' => Array(
			'findmgloss' => 50, 
			'findmgr' => 25,
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
	'inf_cursed' => Array
	(
		'name' => '霉运',
		'tags' => Array('inf'),
		'desc' => '<span class="red b">你感觉自己要倒大霉了……</span>',
	),
);



?>
