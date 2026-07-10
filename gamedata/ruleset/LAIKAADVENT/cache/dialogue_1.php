<?php
if(!defined('IN_GAME')) exit('Access Denied');

# 对话框相关配置文件：
# 用法：在行动中加入 $clbpara['dialogue'] = '单组对白名'; 行动后便会自动跳出对话框。默认情况下，对话框可以直接点击外侧窗口关闭（即跳过）；
# 如果想要生成不能跳过（比如存在选择肢）的对话框，加入 $clbpara['noskip_dialogue'] = 1；

# 单组对白：
$dialogues = Array
(
	//仅作演示用
	'thiphase' => Array
	(
		0 => '在你唱出那单一的旋律的霎那，<br>整个虚拟世界起了翻天覆地的变化……',
		1 => '世界响应着这旋律，产生了异变……<br>因为破灭之歌的作用，全部锁定被打破了！',
		2 => '在下一个瞬间——像是受到电磁干扰般，<br>你的战术界面突然变得花白一片。',
		3 => '<span class="grey">“……防火墙……已……<br>……请到……山丘上……来……”</span>',
		4 => '什么？',
		5 => '没等你反应过来，那位不速之客便已切断了通讯。<br>你呆望着恢复如常的界面，试图厘清这段语焉不详的讯息究竟有何含义……',
	),
	// Time for this to be used!
	'club21entry' => Array
	(
		0 => '你将这个蛋状物捧在手心，<br>你发现它上面并没有什么开关或缝隙。',
		1 => '正在你觉得是不是买到了个玩笑的时候。<br>蛋突然破成了四瓣！<br>随后从蛋中冒出来大量灰黑色，青蓝色，深紫色的像丝带一样的东西，<br>向着你的心口直刺而来！',
		2 => '你猝不及防，被这些丝带一样的东西击中，顿时，大量的数据塞满了你的大脑！<br>你头像炸开一样，不禁蹲躺了下去……',
		3 => '<span class="grey">🎶Ρжжηψψρип ρип, ρжжηψψρжжρип ρип<br>
		ρψψρип ρип, ρип ρип ρжжηψψρжж ρδ<br>
		ρжжηψψρип ρип, ρжжηψψρжжρип ρип<br>
		ρψψρип ρип, ρип ρип ρжжηψψρжж ρδ🎶<br></span>
		<span class="glitch1">“开开心心感叹号，<br>
		搞搞弄弄真快活！<br>
		此地犹如三重彩，<br>
		不愧吾等折腾多！”<br></span>
		<span class="grey">🎶Ρжжηψψρип ρип, ρжжηψψρжжρип ρип<br>
		ρψψρип ρип, ρип ρип ρжжηψψρжж ρδ<br>
		ρжжηψψρип ρип, ρжжηψψρжжρип ρип<br>
		ρψψρип ρип, ρип ρип ρжжηψψρжж ρδ🎶<br></span>',
		4 => '你似乎看到了，听到了，感觉到了一个模糊的场景，<br>但你不知道这是什么。',
		5 => '大量类似的场景掠过你的脑海，而你已经无力吸收。<br>你浑身疼痛，不禁口吐鲜血，无助地等待着一切结束。',
	),
	'club22entry' => Array
	(
		0 => '你打开了这本笔记本，<br>上面空无一物，<br>但你却感觉有声音进入了你的脑海……<br>你的意识在被一些非「你」之物所占据……',
		1 => '这就是你正在寻找的……<br>种火……的力量吗？<br>那么总之……<br>去寻找他们吧！',
	),

	//NPC Platform Usage
	'npcplatform' => Array
	(
		0 => '你将这个按钮部署在了地上，它扩张成了一个平台。<br>你毅然地站了进去。',
		1 => '你看着脚底下的使用说明，将脚放在了平台边缘的按钮上，<br>将其按下。',
		2 => '刹那间，七彩的光束从平台涌动而出，将你淹没。',
		3 => '你感觉你的一切都被读取，扭曲，改写。<br>血液在翻滚，内脏在舞动，意识在倾泻。<br>你甚至已经有了一种你不是你自己的错觉。',
		4 => '随着光束散去，平台也凭空消失。<br>留下的只有一位崭新的你。',
		5 => '希望……这真的值得。',
	),

	//TESTING ONLY - DELETE THIS WHEN DEPLOYING
	'testingDialog' => Array
	(
		0 => '这是测试对话第一页',
		1 => '这是测试对话第二页',
		2 => '现在给出选择支',
	),

	// 带选择的测试对话
	'choiceTestingDialog' => Array
	(
		0 => '这是带选择的测试对话第一页',
		1 => '这是带选择的测试对话第二页',
		2 => '请选择下面的选项：',
	),

	// RuleSet开场剧情
	'opening' => Array
	(
		0 => '欢迎来到游戏世界！',
		1 => '在这里，你将体验到精彩的大逃杀玩法。',
		2 => '准备好开始你的冒险了吗？',
	),

	// 莱卡的进击：模式导入 / Laika Advent: mode introduction
	'laika_intro' => Array
	(
		0 => '夜空并没有发生变化。<br>然而，当你产生“要在这里继续前进”的念头时，群星之间忽然多出了一道本不该存在的视线。',
		1 => '披着星空内衬白色披风的少女悬在某个无法抵达的坐标上。<br>她既不是参战者，也不是能够搜索或攻击的NPC。蓝色眼瞳里没有敌意——也没有任何可以被称为感情的东西。',
		2 => '<span class="yellow b">【星辰之代价】</span>：杀死足够多的NPC、取得重要物品或让数值越过范畴时，必须支付代价。拒付会让对应进度回归原点。<br><br><span class="lime b">【哇呼～悖论祝福】</span>：莱卡会要求你从数种增益中选择，并在选择后揭示与之绑定的减益。',
		3 => '<span class="red b">【宇宙公理之齿轮】</span>：移动PC只会招来一次警告；真正使用召唤剧情敌人的关键物品后，齿轮将嵌入你与当时队伍的规则，并随有效行动不断加速。',
		4 => '“我不会追逐你，也不会攻击你。”<br><br>“我只负责证明：所有前进都曾经牺牲过什么。”<br><br><span class="grey">——「莱卡」（神库特）</span>',
	),

	'laika_pc_warning' => Array
	(
		0 => '移动PC亮起的瞬间，屏幕上的星图自行偏转。<br>一张没有任何情绪的面孔出现在所有程序窗口之后。',
		1 => '“解除禁区只是改变通行条件，尚不足以构成路线。”<br><br>“但你已经接近收费站了。”',
		2 => '“使用<span class="yellow">挑战者之印、黑色碎片、【我想要领略真正的红杀之力】或破灭之诗</span>时，我会把牺牲写入你与当时队伍的规则。”<br><br>画面恢复正常。这段警告不会再出现第二次。',
	),

	// 以下三组会被 RuleSet 行为代码写入动态内容；这里保留刷新页面时的后备文本。
	// The next groups are populated dynamically; these are reload-safe fallbacks.
	'laika_cog_start' => Array
	(
		0 => '<span class="red b">宇宙公理之齿轮已经启动。</span><br>你和当时的队伍必须在牺牲变得无法承受前完成路线。',
	),
	'laika_tax' => Array
	(
		0 => '<span class="yellow b">星辰之代价正在等待结算。</span><br>支付则前进成立；拒绝则相关进度回归原点。',
	),
	'laika_blessing' => Array
	(
		0 => '<span class="lime b">“哇呼～请选择一个祝福吧。”</span><br>对应的悖论反面将在选择后揭晓。',
	),
);

# 单组对白中哪一页对话会显示头像：
$dialogue_icon = Array
(
	'thiphase' => Array
	(
		//第三页时会显示头像
		3 => 'img/n_0.gif',
	),
);

# 单组对白结束时关闭对话框候显示的log
$dialogue_log = Array
(
	'thiphase' => "<span class='lime'>※ 权限重载完成，控制模块已解锁。</span><br>……这又是什么时候的事？<br><br>",
	'club21entry' => "<span class='yellow'>虽然打开了蛋，但你被其中的<span class='glitchb'>数据风暴</span>狂暴吸入，受到了大量的伤害！</span><br>你屁滚尿流地重新站了起来。<br><br>",
	'testingDialog' => "<span class='yellow'>测试已结束！</span><br><br>",
	'choiceTestingDialog' => "<span class='yellow'>选择测试已结束！</span><br><br>",
	'choiceTestingDialog_choice_0' => "<span class='yellow'>你选择了选项A！</span><br>这是选项A的结果。<br><br>",
	'choiceTestingDialog_choice_1' => "<span class='yellow'>你选择了选项B！</span><br>这是选项B的结果。<br><br>",
	'choiceTestingDialog_choice_2' => "<span class='yellow'>你选择了选项C！</span><br>这是选项C的结果。<br><br>",
	'club22entry' => "<span class='yellow'>你获得了收纳种火的力量！</span><br>作为开始，去寻找种火的残骸吧……<br><br>",
	'opening' => "<span class='lime'>※ 游戏开始！</span><br>愿你在这个世界中找到属于自己的道路。<br><br>",
	'laika_intro' => "<span class='yellow'>※ 特殊模式【莱卡的进击】已开始。</span><br>群星正在记录你的每一次前进。<br><br>",
	'laika_pc_warning' => "<span class='grey'>移动PC中的异常星图消失了。</span><br><br>",
	'laika_cog_start' => '',
	'laika_tax' => '',
	'laika_blessing' => '',
);

# 单组对白结束时提供选择肢：
$dialogue_branch = Array
(
	'testingDialog' => Array(
		//0 => '选项A',
		//1 => '选项B',
		//2 => '选项C',
		//3 => '选项A',
		//4 => '选项B',
		//5 => '选项C',
		'选项A','选项B','选项C',
	),
	'choiceTestingDialog' => Array(
		'选项A','选项B','选项C',
	),
	'laika_tax' => Array(
		'支付代价','拒绝支付',
	),
	'laika_blessing' => Array(
		'接受第一项祝福','接受第二项祝福','接受第三项祝福',
	),
);

# 单组对白结束提供特殊结束按钮（非必须、仅在结束对白会触发特殊事件时调用）：
$dialogue_ending = Array
(

);


?>
