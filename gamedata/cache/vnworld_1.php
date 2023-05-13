<?php
if(!defined('IN_GAME')) exit('Access Denied');

#幻想世界相关配置文件
//看板娘头像链接：
$vnworld_img = 'img/Crimzonnews.gif';
//看板娘文本：
$vnworld_notice = "喔！年轻人呦！你发现了一个不得了的地方呢！<br>
……你问我为什么在这里？……其实我也只是临时在这儿代个班而已。<br>
总之，在这里的员工回来之前，就请你自己先随便逛逛吧。<br>";

#玩家自定义合成相关：

//是否开启道具名的联想输入列表：（默认：1=开启）
$vnmix_name_assoc = 1;
//编辑或审批提交的合成列表需要的最低权限等级：（默认：5级）
$vnmix_editor_group = 5;
//提交一次合成需要消耗的切糕：（默认：200。0为不消耗）
$vnmix_c2_cost = 200;
//自定义合成最多可以添加几个属性（最多不能超过30个，默认：7个）
$vnmix_max_sk = 7;
//允许普通玩家选择的道具类别
$vn_iteminfo = Array
(
	'Ag' => '同志饰物',
	'Al' => '热恋饰物',
	'A'  => '饰物（无属性）',
	'Ac'  => '饰物（重击辅助）',
	'B' => '电池',
	'Ca' => '药剂（全恢复）',
	'Ce' => '药剂（治疗麻痹）',
	'Ci' => '药剂（治疗冻结）',
	'Cp' => '药剂（治疗中毒）',
	'Cu' => '药剂（治疗烧伤）',
	'Cw' => '药剂（治疗混乱）',
	'DB' => '身体装备',
	'DH' => '头部装备',
	'DA' => '手臂装备',
	'DF' => '腿部装备',
	'EE' => '电脑设备',
	'HH' => '生命恢复',
	'HS' => '体力恢复',
	'HB' => '命体恢复',
	'HM' => '歌魂增加',
	'HT' => '歌魂恢复',
	'GBr' => '机枪弹药',
	'GBi' => '气体弹药',
	'GBh' => '重型弹药',
	'GBe' => '能源弹药',
	'GB' => '手枪弹药',	
	'MA'=> '攻击力强化药物',
	'MD'=> '防御力强化药物',
	'ME'=> '经验强化药物',
	'MH'=> '生命强化药物',
	'MS'=> '体力强化药物',
	'PM' => '歌魂增加（有毒）',
	'PT' => '歌魂恢复（有毒）',
	'PH' => '生命恢复（有毒）',
	'PS' => '体力恢复（有毒）',
	'PB' => '命体恢复（有毒）',
	'PB2' => '命体恢复（剧毒）',
	'VP'=> '殴系技能书籍',
	'VK'=> '斩系技能书籍',
	'VC'=> '投系技能书籍',
	'VG'=> '射系技能书籍',
	'VD'=> '爆系技能书籍',
	'VF'=> '灵系技能书籍',
	'VV'=> '全系技能书籍',
	'ss' => '歌词卡片',
	'TN' => '陷阱',
	'U' => '扫雷设备',
	'WGK' => '枪刃',#射+斩
	'WCF' => '符札',#投+符
	'WCP' => '重物',#投+殴
	'WKF' => '灵刃',#斩+符
	'WKP' => '重剑',#斩+殴
	'WFK' => '魔刃',#符+斩
	'WDG' => '巨炮',#爆+射
	'WDF' => '落魂',#爆+符
	'WJ' => '重型枪械',
	'WP' => '钝器',
	'WG' => '远程兵器',
	'WK' => '锐器',
	#鹅鹅鹅鹅鹅鹅鹅
	'WC01' => '投掷兵器(★1)',
	'WC02' => '投掷兵器(★2)',
	'WC03' => '投掷兵器(★3)',
	'WC04' => '投掷兵器(★4)',
	'WC05' => '投掷兵器(★5)',
	'WC06' => '投掷兵器(★6)',
	'WC07' => '投掷兵器(★7)',
	'WC08' => '投掷兵器(★8)',
	'WC09' => '投掷兵器(★9)',
	'WK01' => '游戏王一星素材',
	'WK02' => '游戏王两星素材',
	'WK03' => '游戏王三星素材',
	'WK04' => '游戏王四星素材',
	'WK05' => '游戏王五星素材',
	'WK09' => '游戏王九星素材',
	'WK10' => '游戏王十星素材',
	#鹅鹅鹅鹅鹅鹅鹅
	'WC' => '投掷兵器',
	'WD' => '爆炸物',
	'WF' => '灵力兵器',	
	'X' => '合成专用',
	'Y' => '特殊',
	'Z' => '特殊（不可合并）',
);
//允许管理员在上述基础上额外选择的道具类别
$vn_gm_iteminfo = Array
(
	'AA' => '数据护盾',
	'AB' => '毒物中和',
	'Ah'  => '饰物（伤害制御）',
	'WN'  => '空手',
	'EW' => '天气控制',
	'ER' => '探测仪器',
	'p' => '礼物',
	'p0P' => '福袋（殴系）',
	'p0K' => '福袋（斩系）',
	'p0C' => '福袋（投系）',
	'p0G' => '福袋（射系）',
	'p0D' => '福袋（爆系）',
	'p0F' => '福袋（灵系）',
	'p0O1' => '福袋（杂项1）',
	'p00' => '超级福袋（00）',
	'p0AV' => 'VTuber大福袋',
	'fy' => '全地图唯一的野生浮云礼物盒',
	'ygo' => '卡包',
	'XA' =>'代码残片·绿',
	'XB' =>'代码残片·紫',
	'XC' =>'代码残片·黄',
	'XX' =>'杀意已决',
	'XY' =>'杀意未决',
	'ZA' => '代码漏洞',
	'ZB'=> '称号卡',
);
//允许普通玩家选择的合成属性
$vn_itemspkinfo = Array
(
	'none' => '无',
	'A' => '全系防御',
	'a' => '属性防御',
	'B' => '伤害抹消',
	'b' => '属性抹消',
	'C' => '防投',
	'c' => '重击辅助',
	'D' => '防爆',
	'd' => '爆炸',
	'E' => '绝缘',
	'e' => '电击',	
	'F' => '防符',
	'f' => '灼焰',
	'G' => '防弹',
	'g' => '同志',
	'H' => 'HP制御',
	'h' => '伤害制御',
	'I' => '防冻',
	'i' => '冻气',
	'j' => '多重',
	'J' => '超量素材',
	'K' => '防斩',
	'k' => '冰华',
	'l' => '热恋',
	'M' => '陷阱探测',
	'm' => '陷阱迎击',
	'N' => '冲击',
	'n' => '贯穿',
	'y' => '破格',
	'o' => '一发',
	'P' => '防殴',
	'p' => '带毒',
	'q' => '防毒',
	'R' => '混沌',
	'r' => '连击',
	'S' => '消音',
	's' => '调整',
	'U' => '防火',
	'u' => '火焰',
	'v' => '灵魂绑定',
	'W' => '隔音',
	'w' => '音波',
	'X' => '直死', //NPC专用
	'x' => '奇迹',
	'Z' => '菁英',
	'z' => '天然',
	'^' => '背包',
);
//允许管理员在上述基础上额外选择的属性
$vn_gm_itemspkinfo = Array
(
	//'L' => '致残',
	'-' => '精神抽取',
	'*' => '灵魂抽取',
	'+' => '技能抽取',
	//0-99数字做在这里面还是再开一个框？要考虑考虑。
	'1' => '编号1',
	'2' => '编号2',
	'3' => '编号3',
	'4' => '编号4',
	'5' => '编号5',
	'6' => '编号6',
	'7' => '编号7',
	'8' => '编号8',
	'9' => '编号9',
	'10' => '编号10',
	'11' => '编号11',
	'12' => '编号12',
	'13' => '编号13',
	'14' => '编号14',
	'15' => '编号15',
	'16' => '编号16',
	'17' => '编号17',
	'95' => '编号95',
	'96' => '编号96',
	'97' => '编号97',
	'98' => '编号98',
	'99' => '编号99',
);
//道具组别
$vrclassinfo = Array
(
	'wp'=> array('殴系武器','yellow'),
	'wk'=> array('斩系武器','yellow'),
	'wg'=> array('射系武器','yellow'),
	'wc'=> array('投系武器','yellow'),
	'wd'=> array('爆系武器','yellow'),
	'wf'=> array('灵系武器','yellow'),
	'wmu' => array('多重武器','yellow'),
	'w' => array('其他装备','yellow'),
	'h' => array('补给品','lime'),
	'pokemon'=> array('小黄系道具','yellow'),
	'fseed'=> array('种火系道具','lime'),
	'ocg'=> array('游戏王系道具','clan'),
	'key'=> array('KEY系道具','lime'),
	'cube'=> array('方块系道具','yellow'),
	'item'=> array('其他道具','yellow'),
	'titles'=> array('头衔奖励相关道具','sienna'),
);
//显示在编辑合成页面上方的信息
$vnmix_top_tips = '提示：每个配方至少需要两种合成素材，素材与道具名最长不可以超过30个字符。<br>将道具耐久设置为0时可以让耐久度变为“∞”。';
if($vnmix_c2_cost) $vnmix_top_tips.='每次提交需要消耗'.$vnmix_c2_cost.'份切糕。';
//显示在编辑合成与打印合成表页面上的提示信息
$check_tips = Array('<span class="yellow">提交中</span>','<span class="lime">已采纳</span>','<span class="red">未采纳</span>');
$check_infos = Array
(
	0 =>'<span class="yellow">成功保存了配方！</span><br>',
	1 =>'<span class="yellow">成功编辑了配方！</span><br>',
	2 =>'<span class="red">删除了配方。</span><br>',
	3 =>'<span class="yellow">采纳了配方！</span><br>',
	4 =>'<span class="red">将配方状态变更为未采纳。</span><br>',
);

?>
