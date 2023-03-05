<?php
if(!defined('IN_GAME')) exit('Access Denied');

# 技能相关配置文件：

# 社团变更时可获得的技能清单：
$club_skillslist = Array
(
	1  => Array('s_hp','s_ad','f_heal','c1_def','c1_crit','c1_sneak','c1_burnsp','c1_bjack','c1_veteran'), #'铁拳无敌',
	2  => Array('s_hp','s_ad','f_heal'), #'见敌必斩',
	3  => Array('s_hp','s_ad','f_heal'), #'灌篮高手',
	4  => Array('s_hp','s_ad','f_heal'), #'狙击鹰眼',
	5  => Array('s_hp','s_ad','f_heal'), #'拆弹专家',
	6  => Array('s_hp','s_ad','f_heal'), #'宛如疾风',
	7  => Array('s_hp','s_ad','f_heal'), #'锡安成员',
	8  => Array('s_hp','s_ad','f_heal'), #'黑衣组织',
	9  => Array('s_hp','s_ad','f_heal'), #'超能力者',
	10 => Array('s_hp','s_ad','f_heal'), #'高速成长',
	11 => Array('s_hp','s_ad','f_heal'), #'富家子弟',
	12 => Array('s_hp','s_ad','f_heal'), #'全能骑士',
	13 => Array('s_hp','s_ad','f_heal'), #'根性兄贵',
	14 => Array('s_hp','s_ad','f_heal'), #'肌肉兄贵',
	15 => Array('s_hp','s_ad','f_heal'), #'<span class="L5">L5状态</span>',
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

# 技能登记：
$cskills = Array
(
	// 可以通过在此文件中填写配置项来创建一个新技能，系统会自动生成模板。如果配置文件不能满足需求，可以自己创建一个模板文件
	/*'技能编号' => Array
	( 	
		'name' => '技能名', //（必填）技能名
		'tags' => Array('battle','active','passive','inf','hidden'), //（非必填）定义一个技能带有的标签
			// battle: 战斗技，会显示在战斗指令界面；
			// active: 战斗技，但不能在追击/鏖战中使用；
			// passive: 被动技能，在战斗中会自动判断是否生效；
			// inf: 状态技能，在战斗中会自动判断是否生效。和被动技的区别暂时还没有……；
			// hidden：隐藏技能，不会在面板中显示。暂时没有实现

		'maxlvl' => 0, //（非必填）定义一个技能为可升级技能，并定义该技能的等级上限。注意：带有等级上限的技能如果设置了'cost'，'cost'的值必须是一个数组，对应每等级升级时需消耗的技能点
		'desc' => '', //（非必填）技能介绍，显示在技能面板上，可以使用[: :]设置一些静态参数，会在生成时自动替换对应参数。
			// [:cost:]：消耗的技能点
			// [:effect:]：增加指定属性
			// [:effect:]att ：增加对应属性，可以将att替换为'effect'内定义过的键名
			// [::]：还可以替换为任意'vars'内定义过的键名

		'lockdesc' => '', //（需解锁技能必填）不满足解锁条件时的介绍
		'bdesc' => '', //（战斗技必填）显示在战斗界面上的短介绍，战斗技必填
		'log' => '', //（可升级/操作技能必填）升级/操作技能后显示的提示文本
		'cost' => 1, //（可升级/操作技能必填）升级/操作技能要花费的技能点，如果设置过'maxlvl'，这里应该设置成一个Array
		'input' => '升级',//（可升级/操作技能必填）自动生成模板时，对应操作按钮的名字，不存在时不会生成按钮
		'num_input' => 1,//（非必填）自动生成模板时，是否会为其生成数字输入框（便于快速提交多次升级）
		'status' => Array('hp','mhp'),//（非必填）每次升级时，直接提升的玩家属性。
			// 和头衔入场奖励类似，支持所有在数据库中登记过的字段名
			// 如果输入了Array('para' => Array('lvl'))，代表升级时会提升该技能的等级

		'effect' => Array(0 => Array('att' => 4, 'def' => 6),13 => Array('att' => 9, 'def' => 12),),//（非必填）每次升级时，直接提升的玩家属性对应的值。
			// 键名为 0 时，代表默认情况下会增加的对应属性值。键值可以是一个由字段名构成的数组。也可以只是一个数字——代表会增加所有'status'中登记的属性值
			// 键名为 其他数字 时，代表该数字对应【社团】会增加的属性值
			
		'events' => Array(''); //（非必填）每次升级时会触发的事件，目前只有一个'heal'，代表全恢复
		'unlock' => Array('lvl' => '[:lvl:] >= 3',), //（非必填）技能解锁条件，键名和键值[::]内的内容要相同。键值须为PHP支持的条件判断语句。支持“或”类型判断，请参考下方例子。
			// Array('wepk+wep_kind' => "[:wepk:] == 'WP' || [:wep_kind:] == 'P'",), 键名中的+是分隔符，处理时会依此将条件分割为数组，替换键值内的判断语句

		'vars' => Array(), //（非必填）技能内预设的静态参数，比如'ragecost'怒气消耗。预设的参数可以自动填充'desc'中对应[::]的内容
		'svars' => Array(), //（非必填）初次获得技能时，保存在clbpara['skillpara']['技能编号']中的动态技能参数。可以用来定义技能的使用次数等。
		'slast' => Array('lasttimes' => 0,'lastturns' => 0,), //（非必填）初次获得时效性技能时，保存在clbpara内的数据。暂时只支持以下参数：
			// 'lasttimes' => 0,  代表技能持续的时间，保存在clbpara['lasttimes']['技能编号']中
			// 'lastturns' => 0,  代表技能持续的回合，保存在clbpara['lastturns']['技能编号']中
			// 时效性技能才初次霍德师，还会获得一个等于当前时间戳的'starttimes'，保存在clbpara['starttimes']['技能编号']中
			// 玩家在行动时会判断时效性技能是否结束，NPC敌人在被玩家发现时会判断时效性技能是否结束，并在战斗开始前保存状态

	),*/
	's_hp' => Array
	(
		'name' => '生命',
		'desc' => '每消耗<span class="lime">[:cost:]</span>技能点，生命上限<span class="yellow">+[:effect:]</span>点',
		'log' => '消耗了<span class="lime">[:cost:]</span>点技能点，你的生命上限增加了<span class="yellow">[:effect:]</span>点。<br>',
		'cost' => 1,
		'input' => '升级',
		'num_input' => 1,
		'status' => Array('hp','mhp'),
		'effect' => Array(
			0 => 3, # 默认每消耗cost点技能点可增加3点生命值与最大生命值
			13 => 6,# 根性兄贵每消耗cost点技能点可增加6点生命值与最大生命值
		),
	),
	's_ad' => Array
	(
		'name' => '攻防',
		'desc' => '每消耗<span class="lime">[:cost:]</span>技能点，基础攻击<span class="yellow">+[:effect:]att</span>点，基础防御<span class="yellow">+[:effect:]def</span>点',
		'log' => '消耗了<span class="lime">[:cost:]</span>点技能点，你的基础攻击增加了<span class="yellow">[:effect:]att</span>点，基础防御增加了<span class="yellow">[:effect:]def</span>点。<br>',
		'cost' => 1,
		'input' => '升级',
		'num_input' => 1,
		'status' => Array('att','def'),
		'effect' => Array(
			0 => Array('att' => 4, 'def' => 6),
			14 => Array('att' => 9, 'def' => 12),
		),
	),
	'f_heal' => Array
	(
		'name' => '自愈',
		'desc' => '消耗<span class="lime">[:cost:]</span>技能点，解除全部受伤与异常状态，并完全恢复生命与体力',
		'log' => '消耗了<span class="lime">[:cost:]</span>技能点。<br>',
		'cost' => 1,
		'input' => '治疗',
		'events' => Array('heal'),
	),
	'c1_def' => Array
	(
		'name' => '格挡',
		'tags' => Array('passive'),
		'desc' => '持殴系武器时，武器效果值的<span class="yellow">[:trans:]%</span>计入防御力(最多[:maxtrans:]点)<br>',
		'lockdesc' => '武器不适用，持<span class="yellow">殴系武器</span>时生效',
		'log' => '<br>',
		'vars' => Array(
			'trans' => 40, //效&防转化率
			'maxtrans' => 2000, //转化上限
		),
		'unlock' => Array(
			'wepk+wep_kind' => "[:wepk:] == 'WP' || [:wepk:] == 'WCP' || [:wepk:] == 'WKP' || [:wep_kind:] == 'P'",
		),
	),
	'c1_crit' => Array
	(
		'name' => '猛击',
		'tags' => Array('passive'),
		'maxlvl' => 2,
		'desc' => '持殴系武器战斗时<span class="yellow">[:rate:]%</span>几率触发，触发则物理伤害增加<span class="yellow">[:attgain:]%</span>，<br>
		且晕眩敌人<span class="cyan">[:stuntime:]</span>秒。晕眩状态下敌人无法进行任何行动或战斗。<br></span>',
		'lockdesc' => '武器不适用，持<span class="yellow">殴系武器</span>时可发动',
		'log' => '升级成功。<br>',
		'cost' => Array(10,11,-1),
		'input' => '升级',
		'status' => Array('para' => Array('lvl')),
		'effect' => Array(
			0 => Array('lvl' => 1),
		),
		'vars' => Array(
			'attgain' => Array(20,50,80), //物理伤害增加
			'stuntime' => Array(1,1,2), //晕眩时间（单位:秒）
			'rate' => 25, //触发率
		),
		'svars' => Array(
			'lvl' => 0, //初次获得时等级为0
		),
		'unlock' => Array(
			'wepk+wep_kind' => "[:wepk:] == 'WP' || [:wepk:] == 'WCP' || [:wepk:] == 'WKP' || [:wep_kind:] == 'P'",
		),
	),
	'c1_sneak' => Array
	(
		'name' => '偷袭',
		'tags' => Array('battle','active'),
		'desc' => '本次攻击必定触发技能“<span class="yellow">猛击</span>”且不会被反击。<br>
		持殴系武器方可发动，发动消耗<span class="yellow">[:ragecost:]</span>点怒气。<br>',
		'bdesc' => '必定触发技能“<span class="yellow">猛击</span>”且不会被反击。消耗<span class="red">[:ragecost:]</span>怒气',
		'lockdesc' => Array(
			'lvl' => '3级时解锁',
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">殴系武器</span>时可发动',
		),
		'log' => '<br>',
		'vars' => Array(
			'ragecost' => 25, //消耗怒气
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
		'lockdesc' => Array(
			'lvl' => '6级时解锁',
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">殴系武器</span>时可发动',
		),
		'log' => '<br>',
		'vars' => Array(
			'burnspr' => 33, //体力减少&伤害占比
			'mingrg' => 1, //最小怒气增益
			'maxgrg' => 2, //最大怒气增益
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
		'lockdesc' => Array(
			'lvl' => '11级时解锁',
			'wepk+wep_kind' => '武器不适用，持<span class="yellow">殴系武器</span>时可发动',
		),
		'log' => '<br>',
		'vars' => Array(
			'ragecost' => 85, //消耗怒气
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
		'vars' => Array(
			'defkind' => Array('P','K','C','G','F','D','I','U','q','W','E'), //可选择的单系防御类型
		),
		'svars' => Array(
			'choice' => '', //初始默认选择的单项防御
		),
		'unlock' => Array(
			'lvl' => '[:lvl:] >= 18',
		),
	),
	'inf_dizzy' => Array
	(
		'name' => '眩晕',
		'tags' => Array('inf'),
		'desc' => '你感到头晕目眩，无法进行任何行动或战斗！<br>眩晕状态持续时间还剩<span class="red">[:lasttimes:]</span>秒',
		'slast' => Array(
			'lasttimes' => 0, //技能的持续时间由其他因素决定 这里仅留作占位
		),
	),
);



?>
