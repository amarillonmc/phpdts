<?php

if (!defined('IN_GAME')) {
    exit('Access Denied');
}

define('GAME_ROOT', dirname(__DIR__, 4).DIRECTORY_SEPARATOR);

function get_clbpara($value)
{
    if (is_array($value)) return $value;
    if (empty($value)) return Array();
    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : Array();
}

function gstrfilter($value)
{
    return str_replace(Array("'", '\\'), '', $value);
}

$laika_save_gameinfo_calls = 0;
function save_gameinfo()
{
    global $laika_save_gameinfo_calls;
    $laika_save_gameinfo_calls++;
}

class LaikaFakeDb
{
    public $updates = Array();

    public function array_update($table, $data, $where)
    {
        $this->updates[] = Array('table' => $table, 'data' => $data, 'where' => $where);
    }
}

function laika_test_assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    echo "PASS: {$message}\n";
}

function laika_test_player()
{
    return Array(
        'pid' => 1,
        'type' => 0,
        'name' => 'Tester',
        'teamID' => '',
        'pls' => 0,
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
        'exp' => 0,
        'money' => 1000,
        'rp' => 100,
        'skillpoint' => 0,
        'wp' => 100,
        'wk' => 100,
        'wg' => 100,
        'wc' => 100,
        'wd' => 100,
        'wf' => 100,
        'wep' => '测试武器',
        'wepk' => 'WP',
        'wepe' => 100,
        'weps' => 100,
        'wepsk' => '',
        'weppara' => '',
        'wep2' => '', 'wep2k' => '', 'wep2e' => 0, 'wep2s' => 0, 'wep2sk' => '', 'wep2para' => '',
        'arb' => '', 'arbk' => '', 'arbe' => 0, 'arbs' => 0, 'arbsk' => '', 'arbpara' => '',
        'arh' => '', 'arhk' => '', 'arhe' => 0, 'arhs' => 0, 'arhsk' => '', 'arhpara' => '',
        'ara' => '', 'arak' => '', 'arae' => 0, 'aras' => 0, 'arask' => '', 'arapara' => '',
        'arf' => '', 'arfk' => '', 'arfe' => 0, 'arfs' => 0, 'arfsk' => '', 'arfpara' => '',
        'art' => '', 'artk' => '', 'arte' => 0, 'arts' => 0, 'artsk' => '', 'artpara' => '',
        'itm0' => '', 'itmk0' => '', 'itme0' => 0, 'itms0' => 0, 'itmsk0' => '', 'itmpara0' => '',
        'clbpara' => Array(),
    ) + laika_test_empty_slots();
}

function laika_test_empty_slots()
{
    $slots = Array();
    for ($i = 1; $i <= 6; $i++) {
        $slots['itm'.$i] = '';
        $slots['itmk'.$i] = '';
        $slots['itme'.$i] = 0;
        $slots['itms'.$i] = 0;
        $slots['itmsk'.$i] = '';
        $slots['itmpara'.$i] = '';
    }
    return $slots;
}

$log = '';
require_once GAME_ROOT.'gamedata/ruleset/LAIKAADVENT/include/ruleset.func.php';

$request_lock_result = ruleset_command_request_begin_hook();
laika_test_assert($request_lock_result && laika_command_lock_is_held(), '莱卡请求在读取玩家行前取得共享进度锁');
ruleset_command_request_end_hook();
laika_test_assert(!laika_command_lock_is_held(), '莱卡请求结束后释放共享进度锁');

$command_source = file_get_contents(GAME_ROOT.'command.php');
$request_lock_pos = strpos($command_source, 'ruleset_command_request_begin_hook()');
$player_read_pos = strpos($command_source, 'fetch_playerdata_by_name($cuser)');
$player_save_pos = strpos($command_source, 'player_save($pdata)');
$request_unlock_pos = strpos($command_source, 'ruleset_command_post_save_hook');
laika_test_assert($request_lock_pos !== false && $player_read_pos !== false
    && $player_save_pos !== false && $request_unlock_pos !== false
    && $request_lock_pos < $player_read_pos && $player_save_pos < $request_unlock_pos,
    '莱卡共享进度锁覆盖玩家读取、团队进度改写和最终保存');

$config = laika_get_config();
laika_test_assert(isset($config['cog']['trigger_items']['破灭之诗']), '旧解离触发物已配置');
laika_test_assert($config['tax']['ordinary_npc_threshold'] === 5, '普通NPC税阈值集中可配置');

$mode = 'command';
$sp_cmd = '';
$moveto = 1;
$plsinfo = Array(0 => '起点', 1 => '目标');
$hplsinfo = Array();
$arealist = Array();
$areanum = 0;
$hack = 1;
$movesp = 10;
$movehp = 1;
$inf_move_sp = Array();
laika_test_assert(laika_is_effective_action('move', laika_test_player()), '移动会计入有效行动');
$moveto = 0;
laika_test_assert(!laika_is_effective_action('move', laika_test_player()), '同地点移动不会计入有效行动');
$moveto = 1;
laika_test_assert(!laika_is_effective_action('menu', Array('clbpara' => Array())), '打开菜单不会计入有效行动');
laika_test_assert(!laika_is_effective_action('itm6', laika_test_player()), '空物品栏的使用指令不会计入有效行动');

$player = laika_test_player();
laika_init_state($player);
laika_test_assert(isset($player['clbpara']['laika']['next_blessing']), '玩家莱卡状态可以初始化');

ruleset_itemuse_hook($player, 1, Array('itm' => '移动PC'));
laika_test_assert($player['clbpara']['laika']['pc_warned'] === 1, '移动PC只登记一次警告');
laika_test_assert($player['clbpara']['dialogue'] === 'laika_pc_warning', '移动PC警告会打开模式对话');
laika_clear_dialogue($player);

ruleset_itemuse_hook($player, 1, Array('itm' => '挑战者之印'));
laika_test_assert($player['clbpara']['laika']['cog']['route'] === 'executor', '剧情召唤物会启动对应齿轮路线');
laika_test_assert($player['clbpara']['laika']['cog']['budget'] === 90, '齿轮路线使用可配置行动预算');
laika_test_assert($player['clbpara']['laika']['cog']['member_pids'] === Array(1), '齿轮使用不可复用的玩家PID快照而非队名跟踪成员');

$blessing_player = laika_test_player();
laika_init_state($blessing_player);
$offer = $config['blessing']['pool']['astra_blade'];
$offer['id'] = 'astra_blade';
$blessing_player['clbpara']['laika']['pending'] = Array('type' => 'blessing', 'offers' => Array($offer), 'duration' => 2);
laika_resolve_blessing($blessing_player, 0);
laika_test_assert($blessing_player['att'] === 200 && $blessing_player['def'] === 40, '悖论祝福同时应用极端增益和减益');
laika_tick_blessing($blessing_player);
laika_tick_blessing($blessing_player);
laika_test_assert($blessing_player['att'] === 100 && $blessing_player['def'] === 100, '悖论祝福到期后精确撤销数值变化');

$item_player = laika_test_player();
laika_init_state($item_player);
$item_player['itm0'] = '移动PC';
$item_player['itms0'] = 1;
ruleset_itemget_hook($item_player);
$item_player['itm0'] = '';
$item_player['itms0'] = 0;
$item_player['itm1'] = '移动PC';
$item_player['itmk1'] = 'EE';
$item_player['itme1'] = 1;
$item_player['itms1'] = 1;
laika_process_item_candidate($item_player);
laika_test_assert($item_player['clbpara']['laika']['pending']['type'] === 'tax', '重要物品真正入包后触发征税');
laika_resolve_tax($item_player, 1);
laika_test_assert($item_player['itm1'] === '', '拒绝重要物品税会抹除对应物品进度');

$crafted_player = laika_test_player();
laika_init_state($crafted_player);
$before_items = laika_collect_important_item_counts($crafted_player);
$crafted_player['itm1'] = '『C.H.A.O.S』';
$crafted_player['itmk1'] = 'Y';
$crafted_player['itms1'] = 1;
laika_queue_important_item_gains($crafted_player, $before_items);
laika_process_item_candidate($crafted_player);
laika_test_assert($crafted_player['clbpara']['laika']['pending']['rollback']['item_name'] === '『C.H.A.O.S』', '合成直接入包的重要物品也会触发征税');

$threshold_player = laika_test_player();
$threshold_player['att'] = 501;
laika_init_state($threshold_player);
laika_check_stat_thresholds($threshold_player);
laika_test_assert($threshold_player['clbpara']['laika']['pending']['rollback']['kind'] === 'threshold', '数值越过配置档位会触发征税');
laika_resolve_tax($threshold_player, 1);
laika_test_assert($threshold_player['att'] === 500, '拒绝数值税会退回触发档位');

$db = new LaikaFakeDb();
$tablepre = 'test_';
$deathnum = 5;
$npc_player = laika_test_player();
$npc_player['clbpara']['achvars'] = Array('test_kill_progress' => 1);
laika_init_state($npc_player);
$npc = Array(
    'pid' => 99,
    'type' => 1,
    'name' => '普通测试NPC',
    'mhp' => 500,
    'msp' => 200,
    'clbpara' => Array(),
) + laika_test_empty_slots();
for ($i = 0; $i < 5; $i++) ruleset_player_kill_hook($npc_player, $npc, 1);
laika_test_assert($npc_player['clbpara']['laika']['pending']['rollback']['kind'] === 'npc', '普通NPC累计到阈值后触发征税');
$npc_player['clbpara']['achvars']['test_kill_progress'] = 2;
laika_resolve_tax($npc_player, 1);
laika_test_assert(!empty($db->updates) && strpos($db->updates[0]['where'], "pid='99'") !== false, '拒绝NPC税会请求复活对应NPC');
laika_test_assert($deathnum === 4 && $laika_save_gameinfo_calls === 1, '复活NPC会同步修正全局死亡计数');
laika_test_assert($npc_player['clbpara']['laika']['npc_tax_count'] === 4, '拒绝NPC税会恢复触发前的累计进度');
laika_test_assert($npc_player['clbpara']['achvars']['test_kill_progress'] === 1, '拒绝NPC税会撤销本次击杀衍生的clbpara进度');

$gamevars = Array();
$sanma_player = laika_test_player();
laika_init_state($sanma_player);
$sanma = Array(
    'pid' => 100,
    'type' => 15,
    'name' => '特殊死亡测试NPC',
    'mhp' => 500,
    'msp' => 200,
    'clbpara' => Array(),
) + laika_test_empty_slots();
for ($i = 0; $i < 5; $i++) ruleset_player_kill_hook($sanma_player, $sanma, 1);
$gamevars['sanmadead'] = 1;
laika_resolve_tax($sanma_player, 1);
laika_test_assert(!isset($gamevars['sanmadead']), '拒绝NPC税会撤销NPC特殊死亡对全局状态的改动');

$important_npc_player = laika_test_player();
laika_init_state($important_npc_player);
$important_npc = Array(
    'pid' => 101,
    'type' => 1,
    'name' => '重要掉落测试NPC',
    'mhp' => 500,
    'msp' => 200,
    'rage' => 30,
    'clbpara' => Array(),
    'wep' => '灵魂绑定测试武器', 'wepk' => 'WP', 'wepe' => 10, 'weps' => 10, 'wepsk' => 'v', 'weppara' => '',
    'wep2' => '', 'wep2k' => '', 'wep2e' => 0, 'wep2s' => 0, 'wep2sk' => '', 'wep2para' => '',
) + laika_test_empty_slots();
$important_npc['itm1'] = '冰炎钥匙·炎';
$important_npc['itmk1'] = 'Y';
$important_npc['itms1'] = 1;
ruleset_player_kill_hook($important_npc_player, $important_npc, 1);
laika_test_assert($important_npc_player['clbpara']['laika']['pending']['source'] === 'important_npc', '携带v属性装备的重要NPC仍会独立触发击杀税');
laika_resolve_tax($important_npc_player, 1);
$last_update = $db->updates[count($db->updates) - 1]['data'];
laika_test_assert($last_update['wep'] === '灵魂绑定测试武器' && $last_update['wepsk'] === 'v', '复活重要NPC会还原死亡时消失的绑定装备');

$cog_player = laika_test_player();
laika_init_state($cog_player);
$cog_player['clbpara']['laika']['cog'] = Array(
    'active' => 1,
    'route' => 'test',
    'route_name' => '测试路线',
    'budget' => 10,
    'progress' => 10,
    'applied_progress' => 0,
    'stage' => 0,
    'team_key' => '',
);
laika_apply_cog_range($cog_player, 1, 10);
laika_test_assert($cog_player['clbpara']['laika']['cog']['stage'] === 4, '齿轮按行动预算比例进入最终阶段');
laika_test_assert($cog_player['sp'] === 0 && $cog_player['def'] < 100, '齿轮阶段会持续侵蚀玩家数值');

$borderline_player = laika_test_player();
laika_init_state($borderline_player);
$borderline_player['hp'] = 50;
$borderline_player['sp'] = 100;
$movesp = 80;
$borderline_player['clbpara']['laika']['cog'] = Array(
    'active' => 1,
    'cog_id' => 'borderline',
    'route' => 'test',
    'route_name' => '测试路线',
    'budget' => 100,
    'progress' => 0,
    'applied_progress' => 0,
    'stage' => 0,
    'team_key' => '',
    'member_pids' => Array(1),
    'trigger_item' => '测试物品',
);
$prepare_result = ruleset_command_prepare_hook($borderline_player, 'move');
laika_test_assert($prepare_result === false && $borderline_player['clbpara']['laika']['actions'] === 0 && $borderline_player['sp'] === 100, '齿轮扣除后无法支付的移动会在计数前被拦截');
$movesp = 10;

function laika_test_load_ruleset_config_scoped()
{
    include GAME_ROOT.'gamedata/ruleset/ruleset_config.php';
}

laika_test_load_ruleset_config_scoped();
$registered_ruleset = get_ruleset_config('LAIKAADVENT');
laika_test_assert(!empty($registered_ruleset['initial_setup']['clbpara_flags']['dialogue']), '规则集配置在函数内加载时仍可正确注册');

include GAME_ROOT.'gamedata/ruleset/story_config.php';
$ending_pages = get_ruleset_story_pages('LAIKAADVENT', 'ending', 1, 5, 7);
laika_test_assert(strpos(implode('', $ending_pages), 'DISSOCIATION') !== false, '模式本地结局文案可按需加载');

echo "All LAIKAADVENT tests passed.\n";

?>
