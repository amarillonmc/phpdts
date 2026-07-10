<?php

if(!defined('IN_GAME')) exit('Access Denied');

// 枫火歌者社团配置文件

// 收为随从的成功率（默认100%）
$fireseed_recruit_rate = 100;

// 跟随时强化玩家倍率（默认每个1%）
$fireseed_follow_bonus_rate = 1;

// 每一次探物检定成功率（默认60%）
$fireseed_search_rate = 60;

// 每一次削减NPC HP检定成功率（默认50%）
$fireseed_drain_rate = 50;

// 强化倍率（默认1x、8x、32x、64x、128x）
$fireseed_enhance_multipliers = array(
    '◆焰火' => 1,
    '✦烈焰火' => 8,
    '★华焰火★' => 32,
    '☾真焰火☽' => 64,
    '☼焰火☼' => 128
);

// 种火部署状态
$fireseed_deploy_modes = array(
    0 => '跟随', // 在战斗时增加玩家攻击/防御
    1 => '探物', // 部署在地图上，为玩家收集该地的掉落物品
    2 => '索敌', // 部署后稳定按照比率削减该地图NPC生命至最低1点
    3 => '隐藏'  // 没有额外效果
);

?>
