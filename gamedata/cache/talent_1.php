<?php
if(!defined('IN_GAME')) exit('Access Denied');

// 每树 10 点，当前投入上限由实际节点成本之和的一半计算。
$talent_trees = array(
	'blasty'=>array('name'=>'Blasty','title'=>'振翅南飞','group'=>0,'race'=>2,'skills'=>array('npc_honor_fist','npc_honor_guard'),'skillnames'=>array('荣誉之拳','荣誉守势'),'skilldesc'=>array('以体力换取荣誉拳势，逐步压迫对手的生命底线。','保存荣誉拳势，降低与强敌缠斗时的损耗。'),'bonus'=>array('mhp'=>40),'benefit'=>array('rest_time'=>3)),
	'dante'=>array('name'=>'Dante','title'=>'守门之牙','group'=>0,'skills'=>array('npc_endurance','npc_stalwart'),'skillnames'=>array('熬战','坚守'),'skilldesc'=>array('以体力维持熬战，让对手的行动越来越沉重。','坚守战线，将持续交战的压力转为自身的生存机会。'),'bonus'=>array('msp'=>40),'benefit'=>array('move_sp'=>3)),
	'akane'=>array('name'=>'Akane','title'=>'不杀之武','group'=>0,'race'=>4,'skills'=>array('npc_joint_lock','npc_joint_escape'),'skillnames'=>array('关节锁','脱锁'),'skilldesc'=>array('付出体力锁住对手的关节，逐步限制其行动。','更容易挣脱追击与关节锁，保留退路。'),'bonus'=>array('wp'=>15),'benefit'=>array('move_sp'=>3)),
	'holly'=>array('name'=>'Holly','title'=>'雪原目击者','group'=>0,'skills'=>array('npc_fighting_spirit','npc_snow_sight'),'skillnames'=>array('战意','雪原视线'),'skilldesc'=>array('通过交锋消磨对手战意，迫使对手重新整理呼吸。','在雪原般复杂的环境中辨认敌人的踪迹。'),'bonus'=>array('wg'=>15),'benefit'=>array('trap_detect'=>3)),
	'sandy'=>array('name'=>'Sandy','title'=>'九尾灯火','group'=>0,'skills'=>array('npc_foxfire','npc_foxstep'),'skillnames'=>array('狐火','狐步'),'skilldesc'=>array('消耗体力点燃狐火，逐步扰乱敌人的瞄准。','借狐火争取先手，在交锋间隙调整步伐。'),'bonus'=>array('wf'=>15),'benefit'=>array('rest_time'=>3)),
	'albert'=>array('name'=>'Albert','title'=>'吱吱电波','group'=>0,'skills'=>array('npc_gnaw','npc_toolcare'),'skillnames'=>array('啮噬','工具养护'),'skilldesc'=>array('寻找敌人装备的薄弱处，每次交锋只能进行有限次破坏。','熟悉工具构造，减少自己装备受到的损耗。'),'bonus'=>array('wd'=>15),'benefit'=>array('rest_time'=>3)),
	'kiko'=>array('name'=>'Kiko','title'=>'鹿跃终局','group'=>0,'skills'=>array('npc_luck_charge','npc_lucky_escape'),'skillnames'=>array('天运蓄势','幸运逃脱'),'skilldesc'=>array('逐次积蓄好运，机会用尽后需要重新积蓄。','在好运充足时获得一次脱险机会，并非永久免死。'),'bonus'=>array('mhp'=>40),'benefit'=>array('trap_detect'=>3)),
	'rook'=>array('name'=>'铁城','title'=>'不退之城','group'=>1,'skills'=>array('npc_rook_chase','npc_rook_lock'),'skillnames'=>array('追猎','锁缚反击'),'skilldesc'=>array('以体力为代价，概率保持追击。','反击时可能束缚对手，妨碍其逃跑。'),'bonus'=>array('wp'=>15),'benefit'=>array('move_sp'=>3)),
	'bishop'=>array('name'=>'灵翼','title'=>'贯空之翼','group'=>1,'skills'=>array('npc_arsenal','npc_headshot'),'skillnames'=>array('武器库','爆头瞄准'),'skilldesc'=>array('根据交战距离调整武器使用方式。','在满足怒气与斩杀条件时瞄准头部；免疫者仍承受伤害。'),'bonus'=>array('wg'=>15),'benefit'=>array('trap_detect'=>3)),
	'knight'=>array('name'=>'破石','title'=>'洞察裂隙','group'=>1,'race'=>3,'skills'=>array('npc_weak_analysis','npc_weak_break'),'skillnames'=>array('弱点解析','破绽突破'),'skilldesc'=>array('连续攻击同一目标积累破绽，转攻他人会失去进度。','在解析充分后突破目标防御。'),'bonus'=>array('wc'=>15),'benefit'=>array('rest_time'=>3)),
	'pawn'=>array('name'=>'银锤','title'=>'兵临终线','group'=>1,'race'=>5,'skills'=>array('npc_stage_mine','npc_pawn_evolve'),'skillnames'=>array('受击布雷','兵卒升变'),'skilldesc'=>array('付出资源，在受击时布下反制地雷。','在生命受到威胁时暂时改变战斗形态。'),'bonus'=>array('wd'=>15),'benefit'=>array('trap_detect'=>3)),
	'king'=>array('name'=>'电反','title'=>'静电王庭','group'=>1,'skills'=>array('npc_interference','npc_steam_guard'),'skillnames'=>array('电磁干扰','蒸汽屏护'),'skilldesc'=>array('消耗体力干扰敌人的命中与电子装备。','以蒸汽干扰为自己争取防护。'),'bonus'=>array('msp'=>40),'benefit'=>array('rest_time'=>3)),
	'queen'=>array('name'=>'三步','title'=>'三步落影','group'=>1,'skills'=>array('npc_shadow_step','npc_shadow_mark'),'skillnames'=>array('影步','落影'),'skilldesc'=>array('寻找交战中的空隙，改变攻击节奏。','以更高的行动成本积累落影标记，满层触发处决。'),'bonus'=>array('wk'=>15),'benefit'=>array('move_sp'=>3)),
	'cannon'=>array('name'=>'白凰','title'=>'符火归凰','group'=>1,'race'=>1,'skills'=>array('npc_talisman','npc_nightmare'),'skillnames'=>array('贴符','噩梦'),'skilldesc'=>array('手动消耗体力贴上符盾；符盾有层数与冷却限制。','将噩梦带入交锋，限制对手切换视界。'),'bonus'=>array('wf'=>15),'benefit'=>array('rest_time'=>3)),
	'guard'=>array('name'=>'绯刹','title'=>'绯色无声','group'=>1,'skills'=>array('npc_stealth','npc_blood_poison'),'skillnames'=>array('隐踪','血毒'),'skilldesc'=>array('在合适的时机隐匿自己的踪迹。','攻击时可能留下内出血，需付出体力维持毒刃。'),'bonus'=>array('wk'=>15),'benefit'=>array('rest_time'=>3)),
	'electro'=>array('name'=>'电掣','title'=>'自主之光','group'=>2,'skills'=>array(array('npc_spark','npc_electrofox','npc_lifecat'),array('npc_groundsheep','npc_bookworm','npc_magicbee','npc_cagedbird')),'skillnames'=>array('火花、电子狐与百命猫','羊、虫、蜂与鸟'),'skilldesc'=>array('学习种火花、电子狐与百命猫的玩家版能力；百命猫每局只能保命一次。','学习走地羊、书中虫、魔法蜂与笼中鸟的玩家版能力。'),'bonus'=>array('msp'=>40),'benefit'=>array('move_sp'=>3)),
);
$talent_race_rules = array('beast_physical'=>0.35,'beast_extra'=>1.3,'beast_fire'=>2,'fairy_spirit'=>1.25,'fairy_bound'=>2,'fish_move_min'=>0.4,'fish_move_step'=>0.15,'fish_escape'=>20,'dragon_special'=>1.5,'dragon_ordinary'=>0.75,'sell_factor'=>2,'sell_max'=>20000,'ai_cost'=>20,'ai_switch_cd'=>30);

$talent_title_index = 9310;
foreach($talent_trees as $talent_tree_id=>&$talent_tree) {
	$talent_tree['title_id'] = $talent_title_index++;
	$talent_tree['nodes'] = array(
		array('name'=>$talent_tree['title'],'cost'=>1,'kind'=>'title','desc'=>'永久收集本树头衔。佩戴它入场，才能使用本树已收集的专属技能。'),
		array('name'=>'基础修习','cost'=>1,'kind'=>'bonus','value'=>$talent_tree['bonus'],'desc'=>'当前投入生效：'.implode('、',array_map(function($key,$value) { $names=array('mhp'=>'生命上限','msp'=>'体力上限','wp'=>'殴熟','wk'=>'斩熟','wg'=>'射熟','wc'=>'投熟','wd'=>'爆熟','wf'=>'灵熟'); return $names[$key].' +'.$value; },array_keys($talent_tree['bonus']),array_values($talent_tree['bonus']))).'。'),
		array('name'=>$talent_tree['skillnames'][0],'cost'=>2,'kind'=>'skill','value'=>(array)$talent_tree['skills'][0],'desc'=>$talent_tree['skilldesc'][0].' 永久收集，佩戴本树头衔入场生效。'),
		array('name'=>'旅途心得','cost'=>1,'kind'=>'bonus','value'=>$talent_tree['benefit'],'desc'=>'当前投入生效：'.implode('、',array_map(function($key,$value) { $names=array('move_sp'=>'移动耗体减少','rest_time'=>'静养所需时间减少','mix_time'=>'合成耗时减少','trap_detect'=>'发现地雷概率增加'); return $names[$key].$value.'%'; },array_keys($talent_tree['benefit']),array_values($talent_tree['benefit']))).'。'),
		isset($talent_tree['race']) ? array('name'=>'种族共鸣','cost'=>2,'kind'=>'race','value'=>$talent_tree['race'],'desc'=>'永久解锁对应种族，可在下次入场时选择。') : array('name'=>'深造修习','cost'=>2,'kind'=>'bonus','value'=>$talent_tree['bonus'],'desc'=>'当前投入生效：再次获得基础修习的白值收益。'),
		array('name'=>$talent_tree['skillnames'][1],'cost'=>3,'kind'=>'skill','value'=>(array)$talent_tree['skills'][1],'desc'=>$talent_tree['skilldesc'][1].' 永久收集，佩戴本树头衔入场生效。'),
	);
}
unset($talent_tree,$talent_tree_id,$talent_title_index);
