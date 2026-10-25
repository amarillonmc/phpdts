<?php
if(!defined('IN_GAME')) exit('Access Denied');
$fcg_title_defs = array(
    9101 => array('五色余烬', 'attune', '调律五色火苗；成功行动积蓄能量，交战自动释放。'),
    9102 => array('因果断', 'thorns', '苦味剂截断重创并反噬攻击者。每45次行动补充一层。'),
    9103 => array('盲眼荷官', 'evade', '每30次行动准备一次哑弹：危险攻击只造成1点伤害，反击必定暴击。'),
    9104 => array('记忆之链', 'chain', '持有至多三张微缩记忆卡，可平时使用或装填，在下一场战斗开幕自动倾泻。'),
    9105 => array('阿卡夏的共犯', 'imprint', '两次器物拓印权限；记录承受过的重击，在下一次主动交战覆唱。'),
    9106 => array('具现的暗影', 'mimic', '拟态种火独立追击，并以自身消散代替一次战斗死亡；40次行动后重构。'),
    9111 => array('✧霓虹失眠✧', '', '让名字流过霓虹。纯外观，无战斗效果。'),
    9112 => array('★星屑过载★', '', '让名字闪耀星屑。纯外观，无战斗效果。'),
    9113 => array('≋量子掉帧≋', '', '让名字偶尔发生彩色错位。纯外观，无战斗效果。'),
    9121 => array('🃏幻境荷官', '', '在真人切磋或残影挑战中累计获胜10次。'),
    9122 => array('🔥传火牌手', '', '在真人切磋或残影挑战中累计获胜50次。'),
);
foreach($fcg_title_defs as $fcg_id => $fcg_def) {
    $titles_list[$fcg_id] = $fcg_def[0];
    $title_desc[$fcg_id] = array('class' => 'fcg-title fcg-title-'.$fcg_id, 'title' => $fcg_def[2]);
    if($fcg_def[1]) $title_valid[$fcg_id] = array('clbpara' => array('skill' => array('tl_fcg_'.$fcg_def[1])));
}
unset($fcg_title_defs, $fcg_id, $fcg_def);
