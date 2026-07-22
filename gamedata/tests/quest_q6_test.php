<?php

if (!defined('IN_GAME')) {
    exit('Access Denied');
}

// Q6躲猫猫任务回归测试 / Q6 hide-and-seek quest regression test

if (!defined('GAME_ROOT')) {
    define('GAME_ROOT', dirname(__DIR__, 2).DIRECTORY_SEPARATOR);
}

function quest_q6_test_assert($condition, $message)
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
    if (empty($value)) return array();
    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : array();
}

function get_itmpara($value)
{
    return get_clbpara($value);
}

class QuestQ6FakeResult
{
    public $row;

    public function __construct($row = null)
    {
        $this->row = $row;
    }
}

class QuestQ6FakeDb
{
    public $queries = array();
    public $alive_npcs = array();

    public function query($sql)
    {
        $this->queries[] = $sql;
        if (preg_match("/SELECT hp FROM .*players WHERE pid = '(\\d+)'/", $sql, $matches)) {
            $pid = intval($matches[1]);
            if (isset($this->alive_npcs[$pid])) {
                return new QuestQ6FakeResult(array('hp' => $this->alive_npcs[$pid]));
            }
            return new QuestQ6FakeResult();
        }
        return new QuestQ6FakeResult();
    }

    public function num_rows($result)
    {
        return $result instanceof QuestQ6FakeResult && is_array($result->row) ? 1 : 0;
    }

    public function fetch_array($result)
    {
        return $result->row;
    }

    public function insert_id()
    {
        return 1;
    }
}

function quest_q6_test_load_resources($directory)
{
    $questcfg = array();
    $questcfg_global = array();
    $questiteminfo = array();
    include GAME_ROOT.$directory.'/questcfg_1.php';
    include GAME_ROOT.$directory.'/questitem_1.php';
    return array($questcfg, $questiteminfo);
}

function quest_q6_test_player($progress = 0, $linked_npc_id = 0)
{
    $player = array(
        'pid' => 1,
        'type' => 0,
        'hp' => 100,
        'pls' => 1,
        'money' => 0,
        'exp' => 0,
        'wep' => '拳头',
        'wepk' => 'WN',
        'wepe' => 0,
        'weps' => 999999,
        'wepsk' => '',
        'weppara' => '',
        'clbpara' => array(
            'quest' => array(
                'active' => array(
                    'Q6' => array(
                        'id' => 'Q6',
                        'progress' => $progress,
                        'progress_max' => 3,
                        'linked_npc_id' => $linked_npc_id,
                        'target_pls' => 1,
                    ),
                ),
                'completed' => array(),
                'failed' => array(),
                'cooldown' => array(),
            ),
        ),
    );
    for ($i = 0; $i <= 6; $i++) {
        $player['itm'.$i] = '';
        $player['itmk'.$i] = '';
        $player['itme'.$i] = 0;
        $player['itms'.$i] = 0;
        $player['itmsk'.$i] = '';
        $player['itmpara'.$i] = '';
    }
    return $player;
}

function quest_q6_test_npc($pid)
{
    return array(
        'pid' => $pid,
        'type' => 93,
        'hp' => 100,
        'clbpara' => array(
            'quest_id' => 'Q6',
            'linked_player_id' => 1,
            'quest_purpose' => 'hide',
        ),
    );
}

$resource_directories = array(
    'base' => 'gamedata',
    'YELLOWKNIFE' => 'gamedata/ruleset/YELLOWKNIFE',
    'LAIKAADVENT' => 'gamedata/ruleset/LAIKAADVENT',
);
foreach ($resource_directories as $name => $directory) {
    list($questcfg, $questiteminfo) = quest_q6_test_load_resources($directory);
    $need = intval($questcfg['Q6']['candy_need']);
    quest_q6_test_assert($need === 3, $name.' Q6仍要求三颗糖果');
    quest_q6_test_assert(intval($questiteminfo['q6_glasses']['itms']) === $need,
        $name.' 侦探眼镜充能数与糖果需求一致');
}

$db = new QuestQ6FakeDb();
$tablepre = 'test_';
$log = '';
$now = 1000;
$nosta = 999999;
$areanum = 0;
$arealist = array(1, 2, 3);
$hack = 1;
$deepzones = array();
$plsinfo = array(1 => '地图一', 2 => '地图二', 3 => '地图三');

require_once GAME_ROOT.'include/game/quest.func.php';
require_once GAME_ROOT.'include/game/item.quest.php';

// 正常生成扣除一次充能，异常消失后的替代目标不重复扣除。
// A normal spawn costs one charge; replacing an abnormally missing target is free.
$charges = 3;
quest_q6_consume_clue_charge($charges, $nosta, false);
quest_q6_test_assert($charges === 2, '正常生成目标会消耗一次眼镜充能');
quest_q6_consume_clue_charge($charges, $nosta, true);
quest_q6_test_assert($charges === 2, '异常消失目标的替代NPC不重复消耗充能');

// 现存目标未找到前不允许消耗眼镜或重复生成NPC。
// Do not consume another charge or spawn a duplicate while the current target is alive.
$player = quest_q6_test_player(0, 700);
$player['itm1'] = '侦探眼镜';
$player['itmk1'] = 'YQ';
$player['itme1'] = 1;
$player['itms1'] = 3;
$player['itmpara1'] = json_encode(array('IsQuestItem' => 1, 'QuestID' => 'Q6', 'QuestAction' => 'clue'));
$db->alive_npcs[700] = 100;
item_quest(1, $player);
quest_q6_test_assert($player['itms1'] === 3, '当前目标存活时重复使用眼镜不消耗充能');
quest_q6_test_assert($player['clbpara']['quest']['active']['Q6']['linked_npc_id'] === 700,
    '重复使用眼镜不改变当前目标');

// 替代目标暂时无法生成时保留旧绑定，下次尝试仍不重复扣费。
// Preserve the stale binding when no replacement can spawn so a retry remains free.
unset($db->alive_npcs[700]);
$arealist = array();
item_quest(1, $player);
quest_q6_test_assert($player['itms1'] === 3, '暂时无安全藏身处时不消耗眼镜充能');
quest_q6_test_assert($player['clbpara']['quest']['active']['Q6']['linked_npc_id'] === 700,
    '替代目标生成失败时保留免费重试标记');
$arealist = array(1, 2, 3);

// 按顺序找到三个绑定NPC，每次只产出一颗糖果。
// Find three linked NPCs in sequence; each encounter must award exactly one candy.
$player = quest_q6_test_player();
for ($found = 1; $found <= 3; $found++) {
    $npc_id = 800 + $found;
    $player['clbpara']['quest']['active']['Q6']['linked_npc_id'] = $npc_id;
    $player['clbpara']['quest']['active']['Q6']['target_pls'] = 1;
    $player['itm0'] = $player['itmk0'] = $player['itmsk0'] = $player['itmpara0'] = '';
    $player['itme0'] = $player['itms0'] = 0;
    $npc = quest_q6_test_npc($npc_id);
    $result = quest_combat_prepare_events($player, $npc, 1);
    quest_q6_test_assert($result === -1, '第'.$found.'个捣蛋鬼以对话结束遭遇');
    quest_q6_test_assert($player['clbpara']['quest']['active']['Q6']['progress'] === $found,
        '第'.$found.'次发现只增加一点进度');
    quest_q6_test_assert($player['itm0'] === '捣蛋鬼的糖果' && $player['itms0'] === 1,
        '第'.$found.'次发现产出一颗糖果');
    quest_q6_test_assert($player['clbpara']['quest']['active']['Q6']['linked_npc_id'] === 0,
        '发现后清除当前NPC绑定');
    itemget($player);
    quest_q6_test_assert($player['itm1'] === '捣蛋鬼的糖果' && $player['itms1'] === $found,
        '第'.$found.'颗YQ糖果会按相同itmpara叠加进同一物品栏');
    quest_q6_test_assert($player['itms0'] === 0, '糖果收入物品栏后清空拾取栏');
}

// 旧NPC不能在重复请求中再产出糖果。
// A stale NPC cannot award another candy through a repeated request.
$player['itm0'] = $player['itmk0'] = $player['itmsk0'] = $player['itmpara0'] = '';
$player['itme0'] = $player['itms0'] = 0;
$stale_npc = quest_q6_test_npc(803);
$result = quest_combat_prepare_events($player, $stale_npc, 1);
quest_q6_test_assert($result === -1 && $player['itms0'] === 0,
    '已处理的NPC重复请求不会产出糖果');
quest_q6_test_assert($player['clbpara']['quest']['active']['Q6']['progress'] === 3,
    '任务进度不会超过配置上限');

// 只有当前绑定NPC死亡才会使Q6失败，旧NPC不影响新目标。
// Only the current linked NPC can fail Q6; a stale NPC cannot affect a newer target.
$stale_owner = quest_q6_test_player(1, 901);
$stale_npc = quest_q6_test_npc(900);
quest_handle_npc_death($stale_owner, $stale_npc);
quest_q6_test_assert(!empty($stale_owner['clbpara']['quest']['active']['Q6'])
    && empty($stale_owner['clbpara']['quest']['failed']['Q6']), '旧NPC死亡不会使新目标任务失败');
$current_npc = quest_q6_test_npc(901);
quest_handle_npc_death($stale_owner, $current_npc);
quest_q6_test_assert(empty($stale_owner['clbpara']['quest']['active']['Q6'])
    && !empty($stale_owner['clbpara']['quest']['failed']['Q6']), '当前绑定NPC死亡会正常判定任务失败');

// 由itemget真实叠加的三颗糖果可正常交付，并只发放一次奖励。
// The three candies actually merged by itemget complete Q6 and grant the reward once.
item_quest(1, $player);
quest_q6_test_assert(empty($player['clbpara']['quest']['active']['Q6'])
    && !empty($player['clbpara']['quest']['completed']['Q6']), '三颗糖果可完成Q6');
quest_q6_test_assert($player['itms1'] === 0 && $player['itm1'] === '', '交付会消耗三颗糖果');
quest_q6_test_assert($player['money'] === 120 && $player['exp'] === 50,
    'Q6数值奖励只发放一次');
quest_q6_test_assert($player['itm0'] === '捉迷藏证明' && $player['itms0'] === 1,
    'Q6交付后发放任务凭证');

echo "All Q6 quest tests passed.\n";

?>
