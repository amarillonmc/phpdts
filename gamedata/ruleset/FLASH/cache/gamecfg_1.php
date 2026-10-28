<?php
if (!defined('IN_GAME')) exit('Access Denied');
require GAME_ROOT.'gamedata/cache/gamecfg_1.php';
$validlimit = 1;
$rsgame_bots = $gamblingon = $npcchaton = $coldtimeon = 0;
$combolimit = $deathlimit = $event_obbs = $chase_obbs = $dfight_obbs = 0;
$corpseprotect = 0;
$gshoplist = array();
// HP/SP 起点沿用公共配置；等待时温和回复，不运行时间禁区。
$flash_config = array(
    'seconds'=>3600, 'revivals'=>11, 'ban_steps'=>array(1,1,2,2,3,3,4,4,4,4,4,4),
    'true_reports'=>5, 'true_revives'=>8, 'boundary_revives'=>7,
    'sweep_hp'=>.3, 'battle_batch'=>5, 'sweep_batch'=>6, 'track_chance'=>80,
    'erosion_hp'=>.10, 'erosion_max'=>2, 'clue_searches'=>3, 'gate_returns'=>3,
    'mine_places'=>array(3,25,26), // 雪之镇、妖精驿站、键刃墓场；只在开局种植。
    'idle_seconds'=>10, 'idle_hp'=>2, 'idle_sp'=>3,
    'reward_power'=>array(1=>80,300,770,1600,5000,30000,200000),
    'reward_stats'=>array(1=>60,180,500,1500,5000,16000,50000),
);
