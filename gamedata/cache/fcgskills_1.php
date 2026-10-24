<?php
if(!defined('IN_GAME')) exit('Access Denied');
$fcg_skill_defs = array(
    'attune' => array('调律', '白：减轻装备损耗与护盾；红：伤害增幅与践踏；黄：先制、避雷与窃怒；绿：恢复、净化；蓝：属性减伤与失能。在下方种火面板切色。'),
    'thorns' => array('苦味', '初始2层，每45次行动补充1层（上限2）。单次伤害超过最大生命35%且减半后能够存活时，减半伤害并反弹减免量的150%（上限自身最大生命），附带中毒。'),
    'evade' => array('哑弹', '每30次行动准备一次哑弹。必杀、暴击、连击或达到当前生命60%的攻击改为1点伤害，回复50怒气，本场反击必定暴击。'),
    'chain' => array('抽牌 / 装填', '入场2张，之后每15次行动抽1张，上限3张。可场外用牌，或装填全部手牌于下一场交战开幕自动使用。'),
    'imprint' => array('模因拓印', '每局2次器物拓印权限。幻影物品最多25耐久，不能转移。记录存活时承受的150点以上重击，可用权限或35步充能装填覆唱。'),
    'mimic' => array('拟态牌手', '拟态种火随攻击独立追击，随本局探索与击杀成长。替主人挡下一次致命攻击，40次行动后重构。'),
);
foreach($fcg_skill_defs as $fcg_key => $fcg_def) $cskills['tl_fcg_'.$fcg_key] = array('name'=>$fcg_def[0], 'tags'=>array('passive'), 'desc'=>$fcg_def[1]);
unset($fcg_skill_defs, $fcg_key, $fcg_def);
