<?php

if (!defined('IN_GAME')) {
    exit('Access Denied');
}

define('GAME_ROOT', dirname(__DIR__, 4).DIRECTORY_SEPARATOR);

function raid_test_assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    echo "PASS: {$message}\n";
}

function get_clbpara($value)
{
    if (is_array($value)) return $value;
    if (empty($value)) return Array();
    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : Array();
}

function get_itmsk_array($value)
{
    return $value === '' ? Array() : preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
}

function config($file, $cfg = 1)
{
    return GAME_ROOT.'gamedata/ruleset/RAIDCAGEDBIRD/cache/'.$file.'_1.php';
}

function diceroll($max)
{
    return isset($GLOBALS['raid_test_dice']) ? intval($GLOBALS['raid_test_dice']) : 0;
}

function addnpc($type, $sub, $num, $time = 0, $custom = null)
{
    $GLOBALS['raid_test_spawn'] = Array($type, $sub, $num, $custom);
    return Array(940);
}

function raid_test_player()
{
    $data = Array(
        'pid' => 1,
        'type' => 0,
        'name' => 'Tester',
        'nick' => '',
        'teamID' => '',
        'pls' => 1,
        'pgroup' => 0,
        'horizon' => 0,
        'club' => 1,
        'inf' => '',
        'hp' => 1000,
        'mhp' => 1000,
        'sp' => 1000,
        'msp' => 1000,
        'att' => 100,
        'def' => 100,
        'lvl' => 1,
        'money' => 0,
        'rp' => 100,
        'state' => 0,
        'clbpara' => Array(),
    );
    foreach (Array('wep', 'wep2', 'arb', 'arh', 'ara', 'arf', 'art') as $slot) {
        $data[$slot] = '';
        $data[$slot.'k'] = '';
        $data[$slot.'e'] = 0;
        $data[$slot.'s'] = 0;
        $data[$slot.'sk'] = '';
        $data[$slot.'para'] = '';
    }
    for ($i = 0; $i <= 6; $i++) {
        $data['itm'.$i] = '';
        $data['itmk'.$i] = '';
        $data['itme'.$i] = 0;
        $data['itms'.$i] = 0;
        $data['itmsk'.$i] = '';
        $data['itmpara'.$i] = '';
    }
    return $data;
}

$log = '';
$gamecfg = 1;
$nosta = 999;
$mode = 'command';
$action = '';
$bid = 0;
$moveto = 2;
$plsinfo = Array(0 => '入口', 1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D');
$hplsinfo = Array(3 => Array(121 => '代码源流'));
$arealist = Array(0, 4);
$areanum = 0;
$hack = 1;
$movesp = 10;
$movehp = 1;
$inf_move_sp = Array();
$gamevars = Array();

require_once GAME_ROOT.'gamedata/ruleset/RAIDCAGEDBIRD/include/ruleset.func.php';

$cfg = raid_get_config();
raid_test_assert(raid_mysql_string_literal("队'伍") === "X'e9989f27e4bc8d'", '账号与队名使用不可注入的MySQL十六进制字面量');
raid_test_assert($cfg['action']['boss_action_threshold'] === 3, '暴走鸟默认每三次全局行动执行一回合');
raid_test_assert($cfg['boss']['charge_start_rate'] === 30, '暴走鸟默认充能概率为30%');
raid_test_assert($cfg['boss']['charge_turns'] === 3, '暴走鸟默认充能三回合');
raid_test_assert($cfg['boss']['charging_icon'] === 203, '充能状态使用独立头像ID并保留a后缀大立绘约定');
raid_test_assert($cfg['boss']['leave_player_corpses_on_wipe'] === true, '地图消灭默认保留玩家尸体');
raid_test_assert($cfg['boss']['leave_npc_corpses_on_wipe'] === true, '地图消灭默认保留NPC尸体');
raid_test_assert($cfg['boss']['destroy_seal_parts_on_wipe'] === false, '地图消灭默认不摧毁封印部件');

$path = raid_shortest_path(1, 4, Array(
    1 => Array(2, 3),
    2 => Array(1, 4),
    3 => Array(1),
    4 => Array(2),
));
raid_test_assert($path === Array(1, 2, 4), 'BFS会沿邻接路线选择最短路径');
raid_test_assert(raid_next_route_step(1, 4, Array(1 => Array(2), 2 => Array(1, 4), 4 => Array(2))) === 2, '暴走鸟每回合只沿路径前进一步');

$player = raid_test_player();
raid_init_player_state($player);
raid_test_assert($player['clbpara']['raid']['trigger_actions'] === 0, '玩家RAID行动计数可以初始化');
raid_test_assert(raid_can_start_move_search('search', $player, 99), '合法原地探索会计入行动');
raid_test_assert(raid_can_start_move_search('move', $player, 2), '合法普通移动会计入行动');
raid_test_assert(!raid_can_start_move_search('move', $player, 1), '原地移动不会计入行动');

$action_player = raid_test_player();
ruleset_command_prepare_hook($action_player, 'search');
raid_test_assert(empty($action_player['clbpara']['raid']['completed_action']), '动作入口只登记资格，不会把失败探索当作成功');
ruleset_move_search_success_hook($action_player, 'search');
raid_test_assert($action_player['clbpara']['raid']['completed_action'] === 'search', '真实探索成功出口会登记完成标记');
ruleset_command_prepare_hook($action_player, 'tpmove');
ruleset_move_search_success_hook($action_player, 'search');
raid_test_assert(empty($action_player['clbpara']['raid']['completed_action']), '强制传送后的搜索不会取得普通行动资格');
$action_player['pass'] = 'bot';
ruleset_command_prepare_hook($action_player, 'move');
ruleset_move_search_success_hook($action_player, 'move');
raid_test_assert(empty($action_player['clbpara']['raid']['completed_action']), '机器人移动不会取得普通行动资格');

$forced_player = raid_test_player();
ruleset_command_prepare_hook($forced_player, 'search');
ruleset_move_search_success_hook($forced_player, 'search', true);
raid_test_assert(empty($forced_player['clbpara']['raid']['completed_action']), '龙卷风等强制传送不会被登记为移动或探索行动');
raid_test_assert(!empty($GLOBALS['raid_command_lock_handle']) && is_resource($GLOBALS['raid_command_lock_handle']),
    'RAID指令在共享状态改写期间持有进程锁');
raid_release_command_lock();
raid_test_assert(ruleset_command_request_begin_hook() && raid_command_lock_is_held(),
    'RAID请求可在读取玩家行之前取得整条指令锁');
ruleset_command_request_end_hook();
raid_test_assert(!raid_command_lock_is_held(), 'RAID请求结束钩子会释放指令锁');

$ruleset_source = file_get_contents(GAME_ROOT.'gamedata/ruleset/RAIDCAGEDBIRD/include/ruleset.func.php');
raid_test_assert(strpos($ruleset_source, 'if ($had_lock) raid_reconcile_seal_parts($data);') !== false
    && strpos($ruleset_source, 'else raid_reconcile_seal_parts();') !== false,
    '非指令页面的玩家初始化以锁内DB行而非旧data核对部件');

$legacy_bot_source = file_get_contents(GAME_ROOT.'botservice.php');
$legacy_bot_lock_pos = strpos($legacy_bot_source, 'ruleset_command_request_begin_hook()');
$legacy_bot_read_pos = strpos($legacy_bot_source, 'SELECT * FROM {$tablepre}players WHERE name');
$legacy_bot_save_pos = strrpos($legacy_bot_source, 'player_save($pdata)');
$legacy_bot_unlock_pos = strrpos($legacy_bot_source, 'ruleset_command_request_end_hook()');
raid_test_assert($legacy_bot_lock_pos !== false && $legacy_bot_read_pos !== false
    && $legacy_bot_save_pos !== false && $legacy_bot_unlock_pos !== false
    && $legacy_bot_lock_pos < $legacy_bot_read_pos && $legacy_bot_save_pos < $legacy_bot_unlock_pos,
    '旧botservice从读取玩家前到最终保存后持有RAID指令锁');

$revbot_source = file_get_contents(GAME_ROOT.'bot/revbotservice.php');
$revbot_generic_lock_pos = strpos($revbot_source, 'if (!revbot_acquire_action_lock()) return false;');
$revbot_ruleset_lock_pos = strpos($revbot_source, "function_exists('ruleset_command_request_begin_hook')");
$revbot_generic_unlock_pos = strpos($revbot_source, 'revbot_release_action_lock();', $revbot_ruleset_lock_pos);
raid_test_assert($revbot_generic_lock_pos !== false && $revbot_ruleset_lock_pos !== false
    && $revbot_generic_unlock_pos !== false && $revbot_generic_lock_pos < $revbot_ruleset_lock_pos,
    'revbotservice在RuleSet锁外层持有跨worker的通用BOT行动锁');
$revbot_loop_pos = strpos($revbot_source, 'bot_act_flag:');
$revbot_loop_source = $revbot_loop_pos === false ? '' : substr($revbot_source, $revbot_loop_pos);
$revbot_lock_pos = strpos($revbot_loop_source, 'revbot_ruleset_action_begin()');
$revbot_load_pos = strpos($revbot_loop_source, 'load_gameinfo()');
$revbot_action_pos = strpos($revbot_loop_source, 'bot_acts($id)');
$revbot_unlock_pos = $revbot_action_pos === false ? false
    : strpos($revbot_loop_source, 'revbot_ruleset_action_end();', $revbot_action_pos);
$revbot_sleep_pos = $revbot_action_pos === false ? false
    : strpos($revbot_loop_source, 'sleep(1);', $revbot_action_pos);
raid_test_assert($revbot_lock_pos !== false && $revbot_load_pos !== false && $revbot_action_pos !== false
    && $revbot_unlock_pos !== false && $revbot_sleep_pos !== false
    && $revbot_lock_pos < $revbot_load_pos && $revbot_load_pos < $revbot_action_pos
    && $revbot_action_pos < $revbot_unlock_pos && $revbot_unlock_pos < $revbot_sleep_pos
    && substr_count($revbot_loop_source, 'revbot_ruleset_action_end();') >= 4,
    'revbotservice每次行动锁住gameinfo与玩家保存，并在sleep前释放');

$search_source = file_get_contents(GAME_ROOT.'include/game/search.func.php');
$extra_loot_pos = strpos($search_source, '$ruleset_extra_loot =');
$combo_filter_pos = strpos($search_source, 'if($gamestate>=40 && !$ruleset_extra_loot)');
raid_test_assert($extra_loot_pos !== false && $combo_filter_pos !== false && $extra_loot_pos < $combo_filter_pos,
    '连斗尸体过滤前会先保留仅含RAID账本的尸体');
raid_test_assert(strpos($search_source, "'weather_hail'") !== false
    && strpos($search_source, "'status_'.\$inf_ky") !== false
    && strpos($search_source, "'event', \$event_damage") !== false,
    '可分配暴走外壳技能覆盖冰雹、异常状态与地图事件伤害');

$command_source = file_get_contents(GAME_ROOT.'command.php');
$request_lock_pos = strpos($command_source, 'ruleset_command_request_begin_hook()');
$player_read_pos = strpos($command_source, 'fetch_playerdata_by_name($cuser)');
raid_test_assert($request_lock_pos !== false && $player_read_pos !== false && $request_lock_pos < $player_read_pos,
    '指令锁在读取玩家背包和积分前取得，避免同一玩家并发覆盖');

$route_source = file_get_contents(GAME_ROOT.'gamedata/ruleset/RAIDCAGEDBIRD/include/raid.routes.php');
$ledger_update_pos = strpos($route_source, 'UPDATE {$tablepre}players AS corpse');
$ledger_affected_pos = strpos($route_source, '$affected_rows = intval($db->affected_rows());');
$ledger_gate_pos = strpos($route_source, 'if ($affected_rows !== 2)');
$ledger_memory_pos = strpos($route_source, '$data[\'clbpara\'] = $finder_clbpara_new;');
raid_test_assert($ledger_update_pos !== false
    && strpos($route_source, 'INNER JOIN {$tablepre}players AS finder') !== false
    && strpos($route_source, 'corpse.clbpara={$corpse_new_para_sql}') !== false
    && strpos($route_source, 'finder.clbpara={$finder_new_para_sql}') !== false
    && strpos($route_source, 'BINARY COALESCE(corpse.clbpara,\'\')={$corpse_old_para_sql}') !== false
    && strpos($route_source, 'BINARY COALESCE(finder.clbpara,\'\')={$finder_old_para_sql}') !== false
    && $ledger_affected_pos !== false && $ledger_gate_pos !== false && $ledger_memory_pos !== false
    && $ledger_update_pos < $ledger_affected_pos && $ledger_affected_pos < $ledger_gate_pos
    && $ledger_gate_pos < $ledger_memory_pos,
	'尸体与领取者账本在同一条双条件CAS语句中更新，成功后才更新内存');

$ordinary = Array('itm' => '测试武器', 'itmk' => 'WP', 'itme' => 100, 'itms' => 100, 'itmsk' => '', 'itmpara' => '');
$ordinary_score = raid_calculate_item_score($ordinary);
raid_test_assert($ordinary_score === 24, '非宝藏按元素估值后应用0.3惩罚倍率');
$rounding_item = Array('itm' => '取整测试', 'itmk' => 'WP', 'itme' => 10, 'itms' => 3, 'itmsk' => '', 'itmpara' => '');
raid_test_assert(raid_calculate_item_score($rounding_item) === 2, '非宝藏积分使用配置的round取整与最低分');

$treasure = Array(
    'itm' => '失落样本', 'itmk' => 'RAID', 'itme' => 1, 'itms' => 1, 'itmsk' => '',
    'itmpara' => Array('raid_treasure' => Array('rarity' => 5, 'value' => 12345)),
);
raid_test_assert(raid_calculate_item_score($treasure) === 12345, '纯宝藏直接采用配置价值');
raid_test_assert(raid_calculate_pve_reward(2500) === 105000, 'PvE奖励公式为(个人积分+50000)乘2');

$bird = raid_test_player();
$bird['pid'] = 94;
$bird['type'] = 94;
$bird['name'] = '暴走笼中鸟';
$bird['clbpara']['skill'] = Array('raid_cagedbird');
$victim = raid_test_player();
$victim['pid'] = 2;
$victim['hp'] = 500;
$victim['mhp'] = 500;
raid_test_assert(ruleset_get_fix_damage_hook($bird, $victim, 1) > $victim['hp'], '暴走鸟命中玩家时返回机制杀伤害');
raid_test_assert(!empty($victim['clbpara']['raid']['bird_direct_hit']), '暴走鸟命中会登记无尸体标记');
$victim['fireseed4_flag'] = 1;
raid_test_assert(ruleset_final_damage_fix_hook($bird, $victim, 1, 0) > $victim['hp'],
    '暴走鸟机制杀的最终伤害可越过种火式归零保护');
require_once GAME_ROOT.'include/game/revattr.func.php';
raid_test_assert(\revattr\get_final_dmg_fix($bird, $victim, 1, 100) > $victim['hp'],
    '真实最终伤害管线会在种火IV之前执行RAID机制杀裁决');

$attacker = raid_test_player();
raid_test_assert(ruleset_get_fix_damage_hook($attacker, $bird, 1) === 0, '暴走鸟技能会抹消固定伤害');
raid_test_assert(ruleset_final_damage_fix_hook($attacker, $bird, 1, 999999) === 0, '暴走鸟技能会抹消普通最终伤害');
$bird['hp'] = 0;
raid_test_assert(ruleset_revive_process_hook($attacker, $bird, 1) === 1 && $bird['hp'] === $bird['mhp'], '暴走鸟理论死亡时取消死亡并回满生命');
$victim['hp'] = 0;
raid_test_assert(ruleset_revive_process_hook($bird, $victim, 1) === 0, '暴走鸟直接命中禁止玩家复活');

$deathnum = 10;
$alivenum = 2;
$wipe_npc = raid_test_player();
$wipe_npc['pid'] = 55;
$wipe_npc['type'] = 5;
$wipe_npc['hp'] = 100;
raid_event_kill_wipe_victim($bird, $wipe_npc, true);
raid_test_assert($deathnum === 10 && !empty($wipe_npc['clbpara']['raid']['exclude_deathnum']),
    '暴走鸟地图消灭NPC不会增加全局deathnum，并登记重算排除标记');

$GLOBALS['raid_test_dice'] = 0;
$targets = Array(
    Array('pid' => 1, 'rp' => 100, 'pls' => 1, 'pgroup' => 0),
    Array('pid' => 2, 'rp' => 500, 'pls' => 2, 'pgroup' => 0),
    Array('pid' => 3, 'rp' => 999, 'pls' => 121, 'pgroup' => 3),
);
$target = raid_choose_boss_target($targets, 70);
raid_test_assert($target['pid'] === 2, '高RP追击会忽略隐藏地图玩家');

$skill_shell = raid_test_player();
$skill_shell['type'] = 5;
$skill_shell['clbpara']['skill'] = Array('raid_cagedbird');
raid_test_assert(raid_is_boss($skill_shell), '暴走外壳机制由技能承载而非写死type94');
$skill_shell['hp'] = 0;
raid_test_assert(ruleset_revive_process_hook($attacker, $skill_shell, 1) === 1
    && $skill_shell['hp'] === $skill_shell['mhp'], '其他单位分配暴走外壳技能后同样会取消死亡并回满');
$skill_shell['hp'] = 1;
raid_test_assert(ruleset_damage_immunity_hook($skill_shell, 'poison', 9999) === 0,
    '其他单位分配暴走外壳技能后同样免疫毒物与陷阱等非战斗伤害');
$skill_shell['hp'] = 0;
raid_test_assert(ruleset_natural_death_hook($skill_shell, 'event') === true
    && $skill_shell['hp'] === $skill_shell['mhp'], '绕过常规复活的事件死亡同样被取消并回满');
$skill_victim = raid_test_player();
$skill_victim['itm1'] = '应被抹除的物品';
$skill_victim['itmk1'] = 'Y';
$skill_victim['itms1'] = 1;
raid_test_assert(raid_handle_boss_direct_kill($skill_shell, $skill_victim, 1)
    && $skill_victim['state'] === 61 && $skill_victim['itm1'] === '' && $skill_victim['pls'] === 254,
    '其他单位分配暴走外壳技能后同样复用直击无尸清除机制');

$container_enemy = raid_test_player();
$container_enemy['type'] = 93;
raid_test_assert(ruleset_force_player_initiative_hook($container_enemy, $player) === true,
    'passive配置使容器遭遇时只允许玩家先制，不会主动攻击');
raid_test_assert(ruleset_force_player_initiative_hook($attacker, $player) === NULL,
    '被动遭遇钩子不会改变普通敌人的先攻逻辑');
$revbattle_source = file_get_contents(GAME_ROOT.'include/game/revbattle.func.php');
$focus_start = strpos($revbattle_source, "if (\$command == 'focus')");
$focus_end = $focus_start === false ? false : strpos($revbattle_source, '# 由协战对象攻击敌人', $focus_start);
$focus_source = $focus_start === false || $focus_end === false
    ? '' : substr($revbattle_source, $focus_start, $focus_end - $focus_start);
raid_test_assert(strpos($focus_source, 'ruleset_force_player_initiative_hook($edata,$data)') !== false
    && strpos($focus_source, '$force_player_initiative ? ($active_r - 1) : diceroll(99)') !== false,
    '视野重遇同样消费被动容器先制钩子，普通敌人仍走原始掷骰');

$help_npcs = Array(93 => Array('asub' => range(0, 24)));
$help_descriptions = Array(93 => Array('sub' => Array(0 => Array('count' => 12), 1 => Array(), 2 => Array(), 3 => Array())));
ruleset_npc_help_filter_hook($help_npcs, $help_descriptions);
raid_test_assert(count($help_npcs[93]['asub']) === 4 && isset($help_descriptions[93]['asub'][0]['count']),
    'NPC图鉴将兼容用25子类收束为四种容器并显示数量说明');
raid_test_assert(array_sum(array_column($help_descriptions[93]['asub'], 'count')) === intval($cfg['containers']['count']),
    'NPC图鉴中的四类容器数量随中央count配置分配');

$extract_copy = ruleset_death_page_copy_hook(63);
raid_test_assert($extract_copy['status'] === '你已撤离。' && $extract_copy['time_label'] === '撤离时间',
    '主动撤离页面显示撤离结算而非死亡文案');

$normal_treasure = raid_generate_treasure_item('normal');
raid_test_assert(raid_is_treasure_item($normal_treasure), '容器可以从可配置前后缀池生成纯宝藏');
raid_test_assert(raid_calculate_item_score($normal_treasure) > 0, '生成宝藏携带可直接结算的价值');
$GLOBALS['raid_test_dice'] = 95;
$ordinary_drop = raid_generate_container_drop();
raid_test_assert($ordinary_drop['itmk'] === 'HH', '容器普通物品掉落权重读取ordinary_item_rate配置');
$GLOBALS['raid_test_dice'] = 0;

$trigger_player = raid_test_player();
for ($step = 1; $step <= 9; $step++) {
    raid_test_assert(raid_record_valid_action($trigger_player, $step % 2 ? 'move' : 'search') === 'counted', '刷鸟前第'.$step.'次有效行动只累计个人步数');
}
raid_test_assert(raid_record_valid_action($trigger_player, 'search') === 'spawned', '第10次移动或探索刷出暴走笼中鸟');
raid_test_assert(!empty($gamevars['raid']['boss_spawned']) && $gamevars['raid']['boss_action_counter'] === 0,
    '触发刷新的第10步不计入刷出后的全局行动槽');

$gamevars['raid']['material'] = Array('itm' => '测试武器', 'itmk' => 'WP');
$gamevars['raid']['progress'] = 123;
raid_sync_tasks($player, false);
raid_test_assert(isset($player['clbpara']['raid']['tasks']['raid_bird'])
    && isset($player['clbpara']['raid']['tasks']['raid_pvp'])
    && isset($player['clbpara']['raid']['tasks']['raid_pve']), 'slidingpanel任务视图同时包含鸟、PvP与PvE路线');

require_once GAME_ROOT.'gamedata/ruleset/ruleset_config.php';
$ruleset_registration = get_ruleset_config('RAIDCAGEDBIRD');
raid_test_assert(!empty($ruleset_registration['story_config']['opening_story']), 'RAID注册会实际触发芙蓉开场故事');
$bird_avatar = get_ruleset_avatar_path('RAIDCAGEDBIRD', 'npc', 201);
raid_test_assert($bird_avatar === 'gamedata/ruleset/RAIDCAGEDBIRD/img/n_201.png'
    && file_exists(GAME_ROOT.$bird_avatar)
    && file_exists(GAME_ROOT.str_replace('.', 'a.', $bird_avatar)),
    '暴走笼中鸟的规则集头像与大立绘路径均可解析');
require_once GAME_ROOT.'gamedata/ruleset/story_config.php';
$opening_pages = get_ruleset_story_pages('RAIDCAGEDBIRD', 'opening', 100, 0, 0);
$ending_pages = get_ruleset_story_pages('RAIDCAGEDBIRD', 'ending', 100, 0, 8);
raid_test_assert(strpos(implode('', $opening_pages), '芙蓉') !== false, '开场故事由芙蓉列出异常与专属路线');
raid_test_assert(strpos(implode('', $ending_pages), 'RAID COMPLETE') !== false, 'winmode 8拥有可实际路由到的封印结局页');

include config('npc', 1);
raid_test_assert(intval($npcinfo[93]['num']) === intval($cfg['containers']['count']), '开局容器数量读取RAID中央配置');
include config('resources', 1);
raid_test_assert(isset($iteminfo['RAIDT']) && $iteminfo['RAIDT'] === 'RAID宝藏', 'RAIDT宝藏类别在通用陷阱T之前完成注册');
$raid_shop_text = file_get_contents(config('shopitem', 1));
raid_test_assert(strpos($raid_shop_text, "12,99,500,0,【源流折跃信标】") !== false,
    '源流折跃信标位于玩家可进入的杂物货架');
$shop_template = file_get_contents(GAME_ROOT.'templates/default/sp_shop.htm');
raid_test_assert(strpos($shop_template, '$raid_cagedbird_shop && $sid == 16') !== false,
    'RAID商店隐藏已经清空的NPC解锁钥匙货架');
$npc_help_template = file_get_contents(GAME_ROOT.'templates/default/npcinfohelp.htm');
raid_test_assert(strpos($npc_help_template, "get_ruleset_avatar_path(\$help_ruleset_id,'npc',\$npc_icon_id)") !== false,
    'NPC图鉴使用当前规则集的容器与暴走鸟图像');
include config('addnpc', 1);
raid_test_assert(intval($anpcinfo[93]['num']) === intval($cfg['containers']['count']), '追加容器数量读取RAID中央配置');

echo "All RAIDCAGEDBIRD tests passed.\n";

?>
