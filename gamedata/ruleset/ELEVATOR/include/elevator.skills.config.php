<?php
if (!defined('IN_GAME')) exit('Access Denied');

// 2026-10-27 作者确认：以 HANDOFF 概要为玩法依据。与电波原实现不同的数值集中于此。
function elevator_skill_definitions() {
    $defs=array(
        582=>array('招福','物品发现率提高20%。',array('find'=>1.2)),
        572=>array('丰收','获得时向每个已生成地点散落一份补给，随后消失。',array()),
        535=>array('挖矿','每次有效移动或搜索有10%机会获得1现金。',array('chance'=>10,'cash'=>1)),
        723=>array('上膛','弹匣容量翻倍；战后从背包中的匹配弹药补充弹匣，直至装满或弹药用尽。',array('capacity'=>2)),
        552=>array('伐木','每次击杀额外取得一份当前主题的资源。',array()),
        458=>array('鸡肉','将背包中的非补给物品转为剧毒补给；你吃有毒补给时正常恢复。',array('infinite_count'=>10)),
        710=>array('解限','熟练手册不再因已有熟练度过高而衰减。',array()),
        738=>array('换挡','低挡减少4点探索体力消耗；高挡多耗8点，暴露预算消耗速度降低8%。',array('low'=>-4,'high'=>8,'budget'=>.92)),
        449=>array('一指','对本模式敌人的最终伤害提高8%。',array('damage'=>1.08)),
        456=>array('突击','主动进攻时伤害提高40%。',array('damage'=>1.4)),
        464=>array('抖M','每次受到有效伤害，最高系熟练度增加1。',array('gain'=>1)),
        513=>array('心墙','每次交战开始获得等于生命上限10%的屏障，抵挡本次交战伤害。',array('barrier'=>.1)),
        515=>array('杀意','最终伤害提高15%。',array('damage'=>1.15)),
        532=>array('无毁','防具与近战武器不损耗耐久；弹药和投掷物仍会消耗。',array()),
        558=>array('抗争','攻击生命上限高于自己的敌人时，最终伤害提高30%。',array('damage'=>1.3)),
        570=>array('入道','可消耗50怒气，使下一次命中伤害翻倍。',array('cost'=>50,'damage'=>2)),
        755=>array('重生','额外获得两次复活，恢复30%生命；重复学习不补充次数。',array('uses'=>2,'hp'=>.3)),
        760=>array('激怒','每次受击累积一层；每层使反击伤害提高20%，最多10层。',array('gain'=>.2,'cap'=>10)),
        437=>array('EM','击杀后恢复敌人等级×5的生命。',array('heal'=>5)),
        443=>array('决斗','可指名同处敌人单挑30秒，双方伤害提高20%；其他乘客暂不能攻击该目标。',array('duration'=>30,'damage'=>1.2)),
        471=>array('气功','灵力武器造成的伤害提高25%。',array('damage'=>1.25)),
        422=>array('枪斗','可将枪械切为枪托打法，按斗殴熟练与近战射程计算；WG武器效果×1.2，WJ×1。',array('WG'=>1.2,'WJ'=>1)),
        455=>array('无敌','每局一次：免疫战斗与陷阱伤害10秒，无法抵挡大楼侵蚀。',array('duration'=>10,'uses'=>1)),
        442=>array('E术','攻击伤害采用固定中值，消除原始伤害的随机波动。',array('factor'=>.7)),
        401=>array('硬化','按角色等级每10级提高一档物理减伤：10%／20%／30%／50%／90%；与现有防御机制叠乘。',array('reduce'=>array(10,20,30,50,90))),
        761=>array('两仪','怒气不高于50时探索少耗12体力；高于50时造成和受到的伤害翻倍。',array('rage'=>50,'sp'=>12,'damage'=>2)),
        430=>array('搬运','获得两个随身行囊格，可随时存取整件物品。',array('slots'=>2)),
        801=>array('首领／爪牙','首领死亡时，其配置关联的爪牙离开战场。',array('npc'=>true)),
        803=>array('铁壳','不能反击、不会先攻，免疫负面状态。',array('npc'=>true)),
        804=>array('速递','每三次停靠投放一只补给箱；箱体被击破时留下2至4件主题奖励。',array('npc'=>true,'stops'=>3))
    );
    return array_replace_recursive($defs,$GLOBALS['elevator_skill_definitions']??array());
}
function elevator_skill_pool_config() {
    return $GLOBALS['elevator_skill_pool']??array(582,572,535,723,552,458,710,738,449,456,464,513,515,532,558,570,755,760,437,443,471,422,455,442,401,761,430,801,803,804);
}
// 装备词条只选持续生效的能力，次数制与储物能力仍由技能笔记学习。
function elevator_equipment_skill_pool() {
    $ids=$GLOBALS['elevator_equipment_skills']??array(582,723,449,456,464,513,515,532,558,760,437,471,422,442,401,761);
    return array_values(array_intersect(elevator_round_skill_pool(),array_map(function($id){return 'el_'.$id;},$ids)));
}
