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
);

# 单组对白结束时提供选择肢：
$dialogue_branch = Array
(

);

# 单组对白结束提供特殊结束按钮（非必须、仅在结束对白会触发特殊事件时调用）：
$dialogue_ending = Array
(

);


?>
