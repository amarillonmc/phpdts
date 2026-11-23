<?php

if (!defined('IN_GAME')) {
	exit('Access Denied');
}

if (!defined('GAME_ROOT')) {
	define('GAME_ROOT', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR);
}

$gamecfg = 1;
$ending_routes_v2 = 1;
$club_skills = 2;
$groomid = 0;
$tablepre = 'test_';
$gtablepre = 'test_';
$now = 1000;
$areanum = 4;
$areaadd = 4;
$hack = 0;
$log = '';
$cmd = '';
$mode = 'command';

class XharmerTestResult
{
	public $rows = array();
	public $idx = 0;
	public function __construct($rows = array())
	{
		$this->rows = array_values($rows);
	}
}

class XharmerTestDb
{
	public $queries = array();
	public $alive_npcs = array();

	public function query($sql)
	{
		$this->queries[] = $sql;
		if (preg_match("/SELECT hp FROM .*players WHERE pid = '(\\d+)'/", $sql, $m)) {
			$pid = (int)$m[1];
			if (isset($this->alive_npcs[$pid])) {
				return new XharmerTestResult(array(array('hp' => $this->alive_npcs[$pid])));
			}
		}
		return new XharmerTestResult();
	}

	public function num_rows($res)
	{
		return ($res instanceof XharmerTestResult) ? count($res->rows) : 0;
	}

	public function fetch_array($res)
	{
		if (!($res instanceof XharmerTestResult) || !isset($res->rows[$res->idx])) {
			return false;
		}
		return $res->rows[$res->idx++];
	}

	public function insert_id()
	{
		return 1;
	}

	public function array_insert($table, $data)
	{
		$this->queries[] = 'INSERT ' . $table;
		return true;
	}

	public function array_update($table, $data, $where)
	{
		$this->queries[] = 'UPDATE ' . $table;
		return true;
	}
}

$db = new XharmerTestDb();

require_once GAME_ROOT . 'include/global.func.php';
require_once GAME_ROOT . 'include/resources.func.php';
require_once GAME_ROOT . 'include/system.func.php';
require_once GAME_ROOT . 'include/game.func.php';
require_once GAME_ROOT . 'include/game/ending_routes.func.php';
require_once GAME_ROOT . 'include/game/fcg_skills.func.php';
require_once GAME_ROOT . 'include/game/quest_sendo.func.php';
require_once GAME_ROOT . 'include/game/dissociation.func.php';
require_once GAME_ROOT . 'include/game/severe_status.func.php';
require_once GAME_ROOT . 'include/game/revclubskills.func.php';
require_once GAME_ROOT . 'include/game/itemmain.func.php';
require_once GAME_ROOT . 'include/game/itemmix.func.php';
require_once GAME_ROOT . 'include/game/item.weapon_mod.php';
require_once GAME_ROOT . 'include/game/item2.func.php';
require_once GAME_ROOT . 'include/game/item.xharmer_mod.php';
require_once GAME_ROOT . 'include/game/quest.func.php';
require_once GAME_ROOT . 'include/game/item.quest.php';

include config('resources', 1);
include config('combatcfg', 1);
include config('clubskills', 1);
include config('tooltip', 1);
include GAME_ROOT . 'gamedata/cache/itmpara_tooltip.php';
include config('dialogue', 1);

function xtest_assert($cond, $msg)
{
	if (!$cond) {
		fwrite(STDERR, "FAIL: {$msg}\n");
		exit(1);
	}
	echo "PASS: {$msg}\n";
}

function xtest_use($slot, &$p)
{
	global $nosta;
	if (function_exists('ending_routes_enabled') && ending_routes_enabled()
		&& function_exists('severe_can_use_item') && !severe_can_use_item($p, $p['itmk' . $slot], $p['itm' . $slot])) {
		return;
	}
	item_weapon_mod($slot, $p);
	if ($p['itms' . $slot] <= 0 && $p['itm' . $slot] && $p['itms' . $slot] !== $nosta) {
		$p['itm' . $slot] = $p['itmk' . $slot] = $p['itmsk' . $slot] = $p['itmpara' . $slot] = '';
		$p['itme' . $slot] = $p['itms' . $slot] = 0;
	}
}

function xtest_player()
{
	global $nosta, $nowep, $noarb;
	$p = array(
		'pid' => 10,
		'name' => '测试员',
		'type' => 0,
		'race' => 0,
		'club' => 0,
		'nick' => 0,
		'lvl' => 30,
		'exp' => 1000,
		'money' => 50000,
		'rp' => 0,
		'hp' => 1200,
		'mhp' => 1200,
		'sp' => 600,
		'msp' => 600,
		'ss' => 100,
		'mss' => 100,
		'att' => 450,
		'def' => 500,
		'pls' => 5,
		'pgroup' => 0,
		'inf' => '',
		'rage' => 0,
		'pose' => 1,
		'tactic' => 3,
		'horizon' => 0,
		'wp' => 500, 'wk' => 500, 'wg' => 500, 'wc' => 500, 'wd' => 500, 'wf' => 500,
		'wep' => '『斩舰刀』', 'wepk' => 'WK', 'wepe' => 800, 'weps' => 100, 'wepsk' => 'cZ', 'weppara' => '',
		'wep2' => '', 'wep2k' => '', 'wep2e' => 0, 'wep2s' => 0, 'wep2sk' => '', 'wep2para' => '',
		'arb' => '☆防弹背心☆', 'arbk' => 'DB', 'arbe' => 600, 'arbs' => 100, 'arbsk' => 'GZ', 'arbpara' => '',
		'arh' => '☆军用头盔☆', 'arhk' => 'DH', 'arhe' => 600, 'arhs' => 100, 'arhsk' => 'CZ', 'arhpara' => '',
		'ara' => '☆军用盾牌☆', 'arak' => 'DA', 'arae' => 600, 'aras' => 100, 'arask' => 'KPZ', 'arapara' => '',
		'arf' => '☆军靴☆', 'arfk' => 'DF', 'arfe' => 600, 'arfs' => 100, 'arfsk' => 'DZ', 'arfpara' => '',
		'art' => '', 'artk' => '', 'arte' => 0, 'arts' => 0, 'artsk' => '', 'artpara' => '',
		'clbpara' => array('skill' => array(), 'skillpara' => array()),
	);
	for ($i = 0; $i <= 6; $i++) {
		$p['itm' . $i] = '';
		$p['itmk' . $i] = '';
		$p['itme' . $i] = 0;
		$p['itms' . $i] = 0;
		$p['itmsk' . $i] = '';
		$p['itmpara' . $i] = '';
	}
	return $p;
}

// 1. 核对商店、Tooltip、Dialogue与里设定隔离
$shop_lines = file(GAME_ROOT . 'gamedata/cache/shopitem_1.php', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$shop_map = array();
foreach ($shop_lines as $sline) {
	if (strpos($sline, '<?') === 0) continue;
	$cols = explode(',', $sline);
	if (count($cols) >= 9 && (int)$cols[0] === 12) {
		$shop_map[$cols[4]] = $cols;
	}
}
$expected_shop = array(
	'「X」牌金属活性诱导膏' => array(4, 5800, 1, 'Y', 1, 1),
	'「X」印恶魔角铆钉组' => array(4, 6200, 1, 'Y', 220, 2),
	'漫展淘来的「非法电弧谐振器」' => array(3, 5800, 1, 'Y', 1, 1),
	'心尾烙印·契约回火钳' => array(2, 7500, 1, 'Y', 1, 1),
	'「X」牌超韧记忆金属内衬' => array(4, 5400, 1, 'Y', 1, 1),
	'「X」式重装手足拘束具' => array(3, 6400, 1, 'Y', 1, 1),
	'「X」印折翼偏折镀层' => array(2, 12800, 1, 'Y', 1, 1),
	'「X」印噬魔虹彩涂装' => array(2, 11800, 1, 'Y', 1, 1),
);
foreach ($expected_shop as $sname => $spec) {
	xtest_assert(isset($shop_map[$sname]), "商店杂物货架包含 {$sname}");
	$c = $shop_map[$sname];
	xtest_assert((int)$c[1] === $spec[0] && (int)$c[2] === $spec[1] && (int)$c[3] === $spec[2]
		&& $c[5] === $spec[3] && (int)$c[6] === $spec[4] && (int)$c[7] === $spec[5],
		"{$sname} 商店库存/价格/禁区/效耐规格正确");
}
xtest_assert(!isset($shop_map['「X」印·魅魔的心核匣']), '魅魔的心核匣不在商店直售，仅通过合成获取');

$all_xitems = array_merge(array_keys($expected_shop), array('「X」印·魅魔的心核匣'));
$forbidden_terms = array('16161', '幻界', '幻铁', '军需官', '时空特使', '岚江口', '红暮', '蓝凝');
foreach ($all_xitems as $xnm) {
	xtest_assert(!empty($tps_name[$xnm]['title']) && !empty($tps_name_lore[$xnm]['title']), "{$xnm} 已注册表层与灵子视界tooltip");
	$std_html = parse_nameinfo_desc($xnm, 0);
	$hor_html = parse_nameinfo_desc($xnm, 1);
	xtest_assert(strpos($std_html, 'tooltip=') !== false && strpos($hor_html, $tps_name_lore[$xnm]['title']) !== false,
		"{$xnm} 在标准与灵子视界均可正确渲染tooltip");
	$combined_text = $tps_name[$xnm]['title'] . $tps_name_lore[$xnm]['title'];
	foreach ($forbidden_terms as $fterm) {
		xtest_assert(strpos($combined_text, $fterm) === false, "{$xnm} 文案严格隔离里设定词「{$fterm}」");
	}
}
foreach (array('xharmer_quest_gift', 'xharmer_wing_coat', 'xharmer_iris_coat', 'xharmer_corebox') as $did) {
	xtest_assert(!empty($dialogues[$did]) && !empty($dialogue_log[$did]), "对白 {$did} 已完整登记");
}

// 2. 测试① 「X」牌金属活性诱导膏（含从 itm0 使用、上限截断、元素继承、不扣上限）
$p = xtest_player();
$p['wep'] = '喷气式红杀重铁剑';
$p['wepe'] = 800; $p['weps'] = 100; $p['wepsk'] = 'c';
$p['itm0'] = '「X」牌金属活性诱导膏'; $p['itmk0'] = 'Y'; $p['itme0'] = 1; $p['itms0'] = 1;
$p['itm2'] = '■石破LOVELOVE天惊拳■'; $p['itmk2'] = 'WP'; $p['itme2'] = 1000; $p['itms2'] = 300; $p['itmsk2'] = 'd';
$cmd = ''; $log = '';
xtest_use(0, $p);
xtest_assert(strpos($cmd, 'usemode" value="xharmer_mod"') !== false && strpos($cmd, 'xm_2') !== false, '诱导膏在itm0也能正常生成二级菜单');
$pdata = &$p; $itmp = 0; $cmd = '';
item_xharmer_mod_exec('2', $p);
xtest_assert($p['wepe'] === 1450 && $p['weps'] === 250, '诱导膏按85%效(+650封顶)与60%耐(+150封顶)强化主武器');
xtest_assert(strpos($p['wepsk'], 'd') !== false && strpos($p['wepsk'], 'v') !== false && strpos($p['wepsk'], '🔰') !== false,
	'诱导膏继承元素d并附加灵魂绑定v与防拆标记');
xtest_assert($p['itm2'] === '' && $p['itms2'] === 0 && $p['itm0'] === '' && $p['itms0'] === 0, '诱导膏与被吞噬祭品均正常消耗');
xtest_assert($p['hp'] === 600 && $p['sp'] === 300 && $p['mhp'] === 1200 && $p['msp'] === 600, '诱导膏只扣当前HP/SP各50%，不扣上限');
$fed_tp = parse_nameinfo_desc($p['wep'], 1, '', '', $p['weppara'], $p['wepk']);
xtest_assert(strpos($p['wep'], '贪食的') === 0 && strpos($fed_tp, '将最基础的武器魔改') !== false && strpos($fed_tp, '金属活性熔接') !== false,
	'贪食的前缀保留原武器灵子视界文案并追加改造lore');
// 同一把武器不能喂第二次
$p['itm1'] = '「X」牌金属活性诱导膏'; $p['itmk1'] = 'Y'; $p['itme1'] = 1; $p['itms1'] = 1;
$p['itm2'] = '光束刀'; $p['itmk2'] = 'WK'; $p['itme2'] = 80; $p['itms2'] = 20;
$cmd = '';
xtest_use(1, $p);
xtest_assert($cmd === '' && $p['itms1'] === 1, '诱导膏对同一把武器严格限制仅可喂食一次');

// 3. 测试② 「X」印恶魔角铆钉组（可重复敲打、物理限定、射系1.5倍、第3枚起反咬不伤武器）
$p = xtest_player();
$p['wepk'] = 'WF';
$p['itm1'] = '「X」印恶魔角铆钉组'; $p['itmk1'] = 'Y'; $p['itme1'] = 220; $p['itms1'] = 2;
xtest_use(1, $p);
xtest_assert($p['itms1'] === 2 && $p['wepe'] === 800, '恶魔角铆钉拒绝灵力武器');

$p['wepk'] = 'WK';
xtest_use(1, $p); // 第1枚
xtest_assert($p['wepe'] === 1020 && $p['weps'] === 85 && strpos($p['wepsk'], 'N') !== false && strpos($p['wepsk'], 'n') === false
	&& strpos($p['wepsk'], 'Z') === false && $p['itms1'] === 1 && $p['sp'] === 480 && strpos($p['inf'], 'a') !== false,
	'第1枚铆钉+220效、给冲击N、抹Z、磨15%耐、扣120体力并腕伤');
xtest_use(1, $p); // 第2枚
$wpara = get_itmpara($p['weppara']);
xtest_assert($p['wepe'] === 1240 && strpos($p['wepsk'], 'N') !== false && strpos($p['wepsk'], 'n') !== false
	&& (int)$wpara['AddDamagePercentage'] === 16 && $p['itms1'] === 0,
	'第2枚铆钉+220效、升阶贯穿n、终伤加成16%并用完第一盒');

// 再买两盒连敲第3~6枚，验证百分比24%封顶且武器绝不损坏、不扣上限
$p['sp'] = 600;
$p['itm1'] = '「X」印恶魔角铆钉组'; $p['itmk1'] = 'Y'; $p['itme1'] = 220; $p['itms1'] = 4;
for ($r = 3; $r <= 6; $r++) {
	$p['sp'] = 600;
	xtest_use(1, $p);
}
$wpara = get_itmpara($p['weppara']);
xtest_assert($p['wepe'] === 2120 && (int)$wpara['AddDamagePercentage'] === 24 && (int)$wpara['x_rivets'] === 6
	&& $p['hp'] >= 1 && $p['mhp'] === 1200 && substr_count($p['wepsk'], '🔰') === 1,
	'重复敲击6枚铆钉面板持续累加、终伤封顶24%、防拆标记不重复膨胀且绝不伤武器或上限');
$rivet_tp = parse_nameinfo_desc($p['wep'], 0, '', '', $p['weppara'], $p['wepk']);
xtest_assert(strpos($rivet_tp, '最终伤害增加: 24%') !== false && strpos($rivet_tp, '已敲入 6 枚配重角') !== false,
	'铆钉改造武器的tooltip正确显示终伤百分比与铆钉枚数');

// 射系 WG 获得 +330 效且不扣弹药
$pgun = xtest_player();
$pgun['wep'] = '『军用火焰放射器』'; $pgun['wepk'] = 'WG'; $pgun['wepe'] = 800; $pgun['weps'] = 40; $pgun['wepsk'] = 'u';
$pgun['itm1'] = '「X」印恶魔角铆钉组'; $pgun['itmk1'] = 'Y'; $pgun['itme1'] = 220; $pgun['itms1'] = 1;
xtest_use(1, $pgun);
xtest_assert($pgun['wepe'] === 1130 && $pgun['weps'] === 40, '射系武器敲铆钉获得+330效且不磨损弹匣');

// 4. 测试③ 漫展淘来的「非法电弧谐振器」（不扣歌魂、抹消音S、火电升f、冰升k）
$parc = xtest_player();
$parc['wep'] = '『军用火焰放射器』'; $parc['wepk'] = 'WG'; $parc['wepe'] = 800; $parc['weps'] = 40; $parc['wepsk'] = 'uSZ';
$parc['itm1'] = '漫展淘来的「非法电弧谐振器」'; $parc['itmk1'] = 'Y'; $parc['itme1'] = 1; $parc['itms1'] = 1;
xtest_use(1, $parc);
$ap = get_itmpara($parc['weppara']);
xtest_assert($parc['wepe'] === 1220 && (int)$ap['AddDamageRaw'] === 220
	&& strpos($parc['wepsk'], 'y') !== false && strpos($parc['wepsk'], 'e') !== false && strpos($parc['wepsk'], 'f') !== false
	&& strpos($parc['wepsk'], 'S') === false && strpos($parc['wepsk'], 'Z') === false
	&& $parc['sp'] === 320 && $parc['ss'] === 100 && $parc['mss'] === 100
	&& strpos($parc['inf'], 'e') !== false && strpos($parc['inf'], 'u') !== false,
	'非法电弧谐振器+420效/+220固伤/赋予yef/抹除消音S，只扣当前SP绝不碰歌魂');

// 5. 测试④ 心尾烙印·契约回火钳（A燃尽 & B永伴，均不扣上限）
$pb = xtest_player();
$pb['wep'] = '『军用火焰放射器』'; $pb['wepk'] = 'WG'; $pb['wepe'] = 800; $pb['weps'] = 40; $pb['wepsk'] = 'uZ';
$pb['itm1'] = '心尾烙印·契约回火钳'; $pb['itmk1'] = 'Y'; $pb['itme1'] = 1; $pb['itms1'] = 1;
$pdata = &$pb; $itmp = 1;
item_xharmer_mod_exec('burn', $pb);
$bp = get_itmpara($pb['weppara']);
xtest_assert($pb['wepe'] === 1400 && $pb['weps'] === 8 && (int)$bp['AddDamageRaw'] === 280
	&& strpos($pb['wepsk'], 'c') !== false && strpos($pb['wepsk'], 'o') !== false && strpos($pb['wepsk'], 'v') !== false
	&& $pb['hp'] === 950 && $pb['sp'] === 400 && $pb['mhp'] === 1200 && $pb['msp'] === 600,
	'燃尽契约正确放大效果至1400、压低弹药并锁o、只扣当前HP/SP');

$pe = xtest_player();
$pe['itm1'] = '心尾烙印·契约回火钳'; $pe['itmk1'] = 'Y'; $pe['itme1'] = 1; $pe['itms1'] = 1;
$pdata = &$pe; $itmp = 1;
item_xharmer_mod_exec('eternal', $pe);
xtest_assert($pe['wepe'] === 640 && $pe['weps'] === $nosta && strpos($pe['wepsk'], 'v') !== false
	&& $pe['hp'] === 1 && $pe['sp'] === 0 && $pe['mhp'] === 1200 && $pe['msp'] === 600 && strpos($pe['inf'], 'u') !== false,
	'永伴契约使近战耐久变∞、将当前HP抽至1且SP归0，完全不扣生命/体力上限');

$pe_gun = xtest_player();
$pe_gun['wep'] = '『AK-47』'; $pe_gun['wepk'] = 'WG'; $pe_gun['wepe'] = 1000; $pe_gun['weps'] = 20; $pe_gun['wepsk'] = 'r';
$pe_gun['itm1'] = '心尾烙印·契约回火钳'; $pe_gun['itmk1'] = 'Y'; $pe_gun['itme1'] = 1; $pe_gun['itms1'] = 1;
$pdata = &$pe_gun; $itmp = 1;
item_xharmer_mod_exec('eternal', $pe_gun);
xtest_assert($pe_gun['wepe'] === 800 && $pe_gun['weps'] === 150 && strpos($pe_gun['wepsk'], 'o') !== false,
	'枪械签永伴契约改为灌注150发魔弹并锁o，避免空枪砸人');

// 6. 测试⑤ 「X」牌超韧记忆金属内衬（+550防/+150耐/18%减伤/诅咒V，且兼容妖精羽翼原名）
$pl = xtest_player();
$pl['race'] = 2;
$pl['arb'] = '妖精羽翼·炎'; $pl['arbk'] = 'DB'; $pl['arbe'] = 600; $pl['arbs'] = 100; $pl['arbsk'] = '';
$pl['itm1'] = '「X」牌超韧记忆金属内衬'; $pl['itmk1'] = 'Y'; $pl['itme1'] = 1; $pl['itms1'] = 1;
$pdata = &$pl; $itmp = 1;
item_xharmer_mod_exec('arb', $pl);
$lpara = get_itmpara($pl['arbpara']);
xtest_assert($pl['arbe'] === 1150 && $pl['arbs'] === 250 && (int)$lpara['DecreaseDamagePercentage'] === 18
	&& strpos($pl['arbsk'], 'V') !== false && $pl['hp'] === 1000 && $pl['mhp'] === 1200,
	'记忆金属内衬+550防/+150耐/+18%终伤减免/附加V诅咒并扣200当前HP');
xtest_assert($pl['arb'] === '妖精羽翼·炎' && race_fairy_bound($pl) === 'u',
	'记忆金属内衬保留妖精羽翼原名，不破坏妖精种族绑定元素');

// 7. 测试⑥ 「X」式重装手足拘束具（+500防/+120耐/固减200 + 无效上下肢破坏）
$ps = xtest_player();
$ps['itm1'] = '「X」式重装手足拘束具'; $ps['itmk1'] = 'Y'; $ps['itme1'] = 1; $ps['itms1'] = 2;
$pdata = &$ps; $itmp = 1;
item_xharmer_mod_exec('ara', $ps);
$ps['hp'] = 1200; $ps['sp'] = 600;
item_xharmer_mod_exec('arf', $ps);
$ap_arm = get_itmpara($ps['arapara']);
$ap_leg = get_itmpara($ps['arfpara']);
xtest_assert($ps['arae'] === 1100 && $ps['aras'] === 220 && (int)$ap_arm['DecreaseDamageRaw'] === 200 && strpos($ps['arask'], 'H') !== false,
	'重装拘束具改手臂+500防/+120耐/固减200/赋予HP制御H');
xtest_assert($ps['arfe'] === 1100 && $ps['arfs'] === 220 && (int)$ap_leg['DecreaseDamageRaw'] === 200
	&& strpos($ps['arfsk'], 'm') !== false && strpos($ps['arfsk'], 'M') !== false,
	'重装拘束具改腿部+500防/+120耐/固减200/赋予双防雷mM');

severe_apply($ps, 'upper');
severe_apply($ps, 'lower');
$ps['sp'] = 50; // 远低于80%体力(480)
xtest_assert(severe_has($ps, 'upper') && !severe_effective($ps, 'upper') && severe_can_use_item($ps, 'HH', '面包'),
	'穿戴改造手臂防具时完全无效化「上肢破坏」无法用道具限制');
xtest_assert(severe_has($ps, 'lower') && !severe_effective($ps, 'lower') && severe_can_move($ps) && severe_action_cost($ps, 15, true) === 15,
	'穿戴改造腿部防具时完全无效化「下肢破坏」八成体力移动限制');
$ps['aras'] = 0; $ps['arfs'] = 0;
xtest_assert(severe_effective($ps, 'upper') && severe_effective($ps, 'lower') && !severe_can_move($ps),
	'改造手足防具耐久归零损坏后，上下肢破坏恢复生效');

// 8. 测试⑦A、⑦B 与 ⑧ 合成及双抹消重代价契约
$pn = xtest_player();
$pn['arhe'] = 600; $pn['arhs'] = 100; $pn['arhsk'] = 'C';
$pn['arae'] = 600; $pn['aras'] = $nosta; $pn['arask'] = 'K';
$pn['itm1'] = '「X」印折翼偏折镀层'; $pn['itmk1'] = 'Y'; $pn['itme1'] = 1; $pn['itms1'] = 1;
$pn['itm2'] = '「X」印噬魔虹彩涂装'; $pn['itmk2'] = 'Y'; $pn['itme2'] = 1; $pn['itms2'] = 1;
$pdata = &$pn; $itmp = 1;
item_xharmer_mod_exec('arh', $pn);
xtest_assert(strpos($pn['arhsk'], 'B') !== false && strpos($pn['arhsk'], 'V') !== false
	&& $pn['arhe'] === 300 && $pn['arhs'] === 35 && $pn['mhp'] === 840 && $pn['hp'] === 840
	&& ($pn['clbpara']['dialogue'] ?? '') === 'xharmer_wing_coat',
	'7A折翼偏折镀层赋予伤害抹消B、头盔防减半耐压至35、永久扣30%生命上限并触发对白');

// 已有 B 的防具不能再刷 7B
$pn['itm3'] = '「X」印噬魔虹彩涂装'; $pn['itmk3'] = 'Y'; $pn['itme3'] = 1; $pn['itms3'] = 1;
$itmp = 3;
item_xharmer_mod_exec('arh', $pn);
xtest_assert($pn['itms3'] === 1 && strpos($pn['arhsk'], 'b') === false, '7A与7B禁止在同一件已有抹消防具上直接叠加');

// 在手臂无限耐防具上刷 7B
unset($pn['clbpara']['dialogue']);
$itmp = 2;
item_xharmer_mod_exec('ara', $pn);
xtest_assert(strpos($pn['arask'], 'b') !== false && strpos($pn['arask'], 'V') !== false
	&& $pn['arae'] === 300 && $pn['aras'] === 25 && $pn['msp'] === 380 && $pn['att'] === 360
	&& ($pn['clbpara']['dialogue'] ?? '') === 'xharmer_iris_coat',
	'7B噬魔虹彩涂装赋予属性抹消b、无限耐压至25、永久扣体力上限与20%基础攻并触发对白');

function xtest_bind(&$data)
{
	$GLOBALS['pdata'] = &$data;
	foreach ($data as $k => &$v) {
		$GLOBALS[$k] = &$v;
	}
}

// 测试 7A + 7B + 悲叹之种 → ⑧ 魅魔的心核匣 合成与使用
$pmix = xtest_player();
$pmix['itm1'] = '「X」印折翼偏折镀层'; $pmix['itmk1'] = 'Y'; $pmix['itme1'] = 1; $pmix['itms1'] = 1;
$pmix['itm2'] = '「X」印噬魔虹彩涂装'; $pmix['itmk2'] = 'Y'; $pmix['itme2'] = 1; $pmix['itms2'] = 1;
$pmix['itm3'] = '悲叹之种'; $pmix['itmk3'] = 'X'; $pmix['itme3'] = 1; $pmix['itms3'] = 1;
xtest_bind($pmix);
$res = itemmix_get_result(array(1, 2, 3), $pmix);
xtest_assert(count($res) === 1 && $res[0]['result'][0] === '「X」印·魅魔的心核匣', '7A+7B+悲叹之种可唯一合成魅魔的心核匣');
$log = '';
itemmix_rev(array(1, 2, 3), -1, $pmix);
xtest_assert($pmix['itm1'] === '「X」印·魅魔的心核匣' && $pmix['itms1'] === 1 && $pmix['itms2'] === 0 && $pmix['itms3'] === 0
	&& strpos($log, '折翼金属膜与噬魔虹彩漆') !== false,
	'合成后消耗三件素材、输出合成文案并将魅魔的心核匣收入包裹');

$itmp = 1;
item_xharmer_mod_exec('core', $pmix);
xtest_assert(strpos($pmix['arbsk'], 'B') !== false && strpos($pmix['arbsk'], 'b') !== false && strpos($pmix['arbsk'], 'V') !== false
	&& $pmix['arbe'] === 1 && $pmix['arbs'] === 80
	&& $pmix['mhp'] === 600 && $pmix['msp'] === 300 && $pmix['def'] === 250 && $pmix['rp'] >= 666
	&& ($pmix['clbpara']['dialogue'] ?? '') === 'xharmer_corebox',
	'魅魔的心核匣赋予身体防具Bb双抹消+80耐，并永久腰斩mhp/msp/def、胸甲防归1、加报应与对白');

// 9. 测试码语行人 club 21 拦截与防拆标记
$pc21 = xtest_player();
$pc21['club'] = 21;
$pc21['itm1'] = '「X」印恶魔角铆钉组'; $pc21['itmk1'] = 'Y'; $pc21['itme1'] = 220; $pc21['itms1'] = 2;
xtest_use(1, $pc21);
xtest_assert($pc21['itms1'] === 2 && $pc21['wepe'] === 800, '码语行人无法使用Xharmer改造物品且不消耗道具');

require_once GAME_ROOT . 'include/game/club21.func.php';
$pc21['itm2'] = $pe['wep']; $pc21['itmk2'] = $pe['wepk']; $pc21['itme2'] = $pe['wepe']; $pc21['itms2'] = $pe['weps']; $pc21['itmsk2'] = $pe['wepsk'];
$pdata = &$pc21;
$club = &$pc21['club']; $sp = &$pc21['sp']; $hp = &$pc21['hp']; $exp = &$pc21['exp'];
$itm2 = &$pc21['itm2']; $itmk2 = &$pc21['itmk2']; $itme2 = &$pc21['itme2']; $itms2 = &$pc21['itms2']; $itmsk2 = &$pc21['itmsk2'];
item_extract_trait('itms2', 2);
xtest_assert($pc21['itmk2'] === 'WK' && $pc21['itms2'] === $nosta, '经Xharmer改造带防拆标记的装备无法被码语行人拆取无限耐久代码片段');

// 10. 测试 Q1~Q7 奖励映射与 Q2 交付闭环
$qitems = get_questiteminfo();
$expected_qrewards = array(
	'q1_reward' => '「X」牌金属活性诱导膏',
	'q2_reward' => '「X」牌超韧记忆金属内衬',
	'q3_reward' => '「X」印恶魔角铆钉组',
	'q4_reward' => '心尾烙印·契约回火钳',
	'q5_reward' => '「X」牌超韧记忆金属内衬',
	'q6_reward' => '「X」式重装手足拘束具',
	'q7_reward' => '漫展淘来的「非法电弧谐振器」',
);
foreach ($expected_qrewards as $qkey => $qname) {
	xtest_assert(($qitems[$qkey]['itm'] ?? '') === $qname && empty($qitems[$qkey]['itmpara']['IsQuestItem']),
		"QUEST {$qkey} 已对接为免费奖励 {$qname}");
}
$pq2 = xtest_player();
$db->alive_npcs[555] = 100;
$pq2['clbpara']['quest'] = array(
	'active' => array('Q2' => array('id' => 'Q2', 'linked_npc_id' => 555, 'buff_level' => 1, 'step' => 1)),
	'completed' => array(), 'failed' => array(), 'cooldown' => array(),
);
$pq2['itm0'] = '护卫终端'; $pq2['itmk0'] = 'YQ'; $pq2['itme0'] = 1; $pq2['itms0'] = 1;
$pq2['itmpara0'] = json_encode(array('IsQuestItem' => 1, 'QuestID' => 'Q2', 'QuestAction' => 'guard', 'LinkedNPCID' => 555, 'BuffLevel' => 1));
$areanum = 4; // >= ban_success(1)
item_quest(0, $pq2);
xtest_assert($pq2['itm0'] === '护送证明' && !empty($pq2['clbpara']['quest']['active']['Q2']['ready_to_claim']),
	'Q2达成禁区条件后护卫终端正常转化为护送证明');
item_quest(0, $pq2);
xtest_assert($pq2['itm0'] === '「X」牌超韧记忆金属内衬' && $pq2['itms0'] === 1 && empty($pq2['clbpara']['quest']['active']['Q2']),
	'在itm0直接使用护送证明交付Q2也能无覆盖地获得记忆金属内衬奖励');

echo "All Xharmer equipment modification tests passed.\n";
