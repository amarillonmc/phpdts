<?php
if(!defined('IN_GAME')) exit('Access Denied');
if(!defined('GAME_ROOT')) define('GAME_ROOT', dirname(__DIR__, 2).DIRECTORY_SEPARATOR);
require_once GAME_ROOT.'./include/paperdoll.func.php';

function paperdoll_test_assert($condition, $message)
{
	if(!$condition) { fwrite(STDERR, "FAIL: $message\n"); exit(1); }
	echo "PASS: $message\n";
}

function paperdoll_test_reject($callback, $message)
{
	try { $callback(); }
	catch(InvalidArgumentException $e) { paperdoll_test_assert(true, $message); return; }
	paperdoll_test_assert(false, $message);
}

class PaperdollTestResult
{
	public $row;
	function __construct($row = null) { $this->row = $row; }
}

class PaperdollTestDb
{
	public $user;
	public $has_column = false;
	public $alter_count = 0;
	public $updates = 0;
	public $affected = 0;
	public $race = null;
	function query($sql, $silent = '')
	{
		if(strpos($sql, 'SHOW COLUMNS') === 0) return new PaperdollTestResult($this->has_column ? array('Field' => 'paperdoll') : null);
		if(strpos($sql, 'ALTER TABLE') === 0) { $this->has_column = true; $this->alter_count++; return true; }
		if(preg_match('/^SELECT uid,password,groupid,credits2,paperdoll FROM test_users WHERE uid=(\d+)$/', $sql, $match)) return new PaperdollTestResult((int)$match[1] === $this->user['uid'] ? $this->user : null);
		if(preg_match("/^UPDATE test_users SET paperdoll=X'([0-9a-f]+)',credits2=credits2-(\d+) WHERE uid=(\d+) AND password=X'([0-9a-f]+)' AND groupid>0 AND BINARY COALESCE\\(paperdoll,''\\)=X'([0-9a-f]*)' AND credits2>=(\d+)$/", $sql, $match)) {
			$this->updates++;
			$this->affected = 0;
			if($this->race) { $race = $this->race; $this->race = null; $race($this); }
			$cost = (int)$match[2];
			if($cost !== (int)$match[6]) throw new RuntimeException('扣费和余额校验不一致。');
			if($this->user['uid'] === (int)$match[3] && $this->user['password'] === hex2bin($match[4]) && $this->user['groupid'] > 0 && (string)$this->user['paperdoll'] === hex2bin($match[5]) && $this->user['credits2'] >= $cost) {
				$this->user['paperdoll'] = hex2bin($match[1]);
				$this->user['credits2'] -= $cost;
				$this->affected = 1;
			}
			return true;
		}
		throw new RuntimeException('未处理的测试查询。');
	}
	function num_rows($result) { return $result->row === null ? 0 : 1; }
	function fetch_array($result) { return $result->row; }
	function affected_rows() { return $this->affected; }
}

$paperdoll_test_locks = 0;
function roommng_acquire_create_lock() { global $paperdoll_test_locks; $paperdoll_test_locks++; return true; }
function roommng_release_create_lock($lock) { global $paperdoll_test_locks; $paperdoll_test_locks--; }

$catalog = array(
	'unit_price' => 20,
	'default' => array('hair' => array('id' => 'hair01', 'color' => '#334455'), 'top' => array('id' => 'top01', 'color' => '#223344'), 'bottom' => array('id' => 'bottom01', 'color' => '#445566'), 'accessories' => array()),
	'hair' => array(array('id' => 'hair01'), array('id' => 'hair02')),
	'top' => array(array('id' => 'top01'), array('id' => 'top02')),
	'bottom' => array(array('id' => 'bottom01'), array('id' => 'bottom02')),
	'accessories' => array(array('id' => 'glasses01', 'slot' => 'eyes'), array('id' => 'glasses02', 'slot' => 'eyes'), array('id' => 'hat01', 'slot' => 'head'))
);
$gtablepre = 'test_';
$db = new PaperdollTestDb();
$db->user = array('uid' => 1, 'password' => md5('paperdoll-test'), 'groupid' => 1, 'credits2' => 200, 'paperdoll' => null);
paperdoll_ensure_storage();
paperdoll_ensure_storage();
paperdoll_test_assert($db->alter_count === 1 && $paperdoll_test_locks === 0, '旧账户表在锁内只补一次字段并释放锁');

function paperdoll_test_input($user, $appearance)
{
	return array('base' => hash('sha256', (string)$user['paperdoll']), 'appearance' => $appearance);
}

$appearance = paperdoll_default($catalog);
$appearance['classic'] = true;
$result = paperdoll_save($db->user, paperdoll_test_input($db->user, $appearance), $catalog);
paperdoll_test_assert($result['cost'] === 0 && !$result['state']['free_used'] && $result['state']['classic'], '只切换经典显示不扣费且保留首次免费');
$appearance['hair']['id'] = 'hair02';
$appearance['top']['id'] = 'top02';
$appearance['accessories'] = array(array('id' => 'hat01', 'color' => '#ABCDEF'));
$first_input = paperdoll_test_input($db->user, $appearance);
$result = paperdoll_save($db->user, $first_input, $catalog);
paperdoll_test_assert($result['changed'] === 3 && $result['cost'] === 0 && $result['credits2'] === 200 && $result['state']['free_used'], '第一次实际自定义无论改几个部件都免费');
paperdoll_test_assert($result['state']['accessories'][0]['color'] === '#abcdef', '服务端将颜色规范为小写');
$result = paperdoll_save($db->user, $first_input, $catalog);
paperdoll_test_assert($result['cost'] === 0 && $result['credits2'] === 200, '第一次保存的相同重试不会再次收费');

$appearance = $result['state'];
$appearance['hair']['color'] = '#112233';
$appearance['free_used'] = false;
$result = paperdoll_save($db->user, paperdoll_test_input($db->user, $appearance), $catalog);
paperdoll_test_assert($result['changed'] === 1 && $result['cost'] === 20 && $result['credits2'] === 180 && $result['state']['free_used'], '仅换色算一个改动，客户端不能重置免费资格');
paperdoll_test_assert(paperdoll_load($db->user['paperdoll'], $catalog) === $result['state'], '重新加载保留形象、偏好和首次免费标记');

$appearance = $result['state'];
$appearance['accessories'][] = array('id' => 'glasses01', 'color' => '#121212');
$result = paperdoll_save($db->user, paperdoll_test_input($db->user, $appearance), $catalog);
paperdoll_test_assert($result['cost'] === 20 && $result['changed'] === 1, '新增一件饰品只收一次费用');
$appearance = $result['state'];
$appearance['accessories'][0]['id'] = 'glasses02';
$result = paperdoll_save($db->user, paperdoll_test_input($db->user, $appearance), $catalog);
paperdoll_test_assert($result['cost'] === 20 && $result['changed'] === 1, '同位置替换饰品算一次改动');
$appearance = $result['state'];
$appearance['accessories'] = array_reverse($appearance['accessories']);
$result = paperdoll_save($db->user, paperdoll_test_input($db->user, $appearance), $catalog);
paperdoll_test_assert($result['cost'] === 0, '饰品数组顺序变化不算换装');

$appearance = paperdoll_default($catalog);
$result = paperdoll_save($db->user, paperdoll_test_input($db->user, $appearance), $catalog);
paperdoll_test_assert($result['state']['free_used'] && $result['cost'] === 80, '恢复默认按实际改动收费，首次资格不会恢复');
$unchanged = $db->user;
$appearance = $result['state'];
$appearance['top']['id'] = 'top02';
$db->user['credits2'] = 19;
paperdoll_test_reject(function() use($db, $appearance, $catalog) { paperdoll_save($db->user, paperdoll_test_input($db->user, $appearance), $catalog); }, '余额不足拒绝保存');
paperdoll_test_assert($db->user['paperdoll'] === $unchanged['paperdoll'] && $db->user['credits2'] === 19, '失败不改变形象和余额');

$db->user['credits2'] = 200;
$stale_user = $db->user;
$stale_input = paperdoll_test_input($stale_user, $appearance);
$newer = paperdoll_load($db->user['paperdoll'], $catalog);
$newer['bottom']['id'] = 'bottom02';
paperdoll_save($db->user, paperdoll_test_input($db->user, $newer), $catalog);
paperdoll_test_reject(function() use($db, $stale_input, $catalog) { paperdoll_save($db->user, $stale_input, $catalog); }, '旧页面不能覆盖新形象');
$before_race = $db->user;
$db->race = function($database) { $state = json_decode($database->user['paperdoll'], true); $state['classic'] = true; $database->user['paperdoll'] = paperdoll_json($state); };
$appearance = paperdoll_load($db->user['paperdoll'], $catalog);
$appearance['top']['id'] = 'top02';
paperdoll_test_reject(function() use($db, $appearance, $catalog) { paperdoll_save($db->user, paperdoll_test_input($db->user, $appearance), $catalog); }, '读取后发生并发保存时条件更新拒绝旧请求');
paperdoll_test_assert($db->user['credits2'] === $before_race['credits2'], '并发保存失败不扣费');

$db->race = function($database) { $database->user['credits2'] = 0; };
paperdoll_test_reject(function() use($db, $appearance, $catalog) { paperdoll_save($db->user, paperdoll_test_input($db->user, $appearance), $catalog); }, '读取后余额被其他消费使用时再次校验余额');
paperdoll_test_assert($db->user['credits2'] === 0, '余额不会扣为负数');

$db->user['credits2'] = 200;
$db->race = function($database) { $database->user['credits2'] -= 7; };
$result = paperdoll_save($db->user, paperdoll_test_input($db->user, $appearance), $catalog);
paperdoll_test_assert($result['credits2'] === 173 && $db->user['credits2'] === 173, '并发其他消费保留，保存响应返回实际余额');

$appearance_a = $result['state'];
$appearance_b = $appearance_a;
$appearance_b['hair']['id'] = 'hair02';
$old_a_input = paperdoll_test_input($db->user, $appearance_b);
paperdoll_save($db->user, $old_a_input, $catalog);
paperdoll_save($db->user, paperdoll_test_input($db->user, $appearance_a), $catalog);
paperdoll_test_reject(function() use($db, $old_a_input, $catalog) { paperdoll_save($db->user, $old_a_input, $catalog); }, '换装 A→B→A 后旧页面不会重新获得扣费权限');

$bad = $appearance; $bad['top']['id'] = '../../outside';
paperdoll_test_reject(function() use($bad, $catalog) { paperdoll_normalize_appearance($bad, $catalog); }, '拒绝不存在的部件及路径输入');
$bad = $appearance; $bad['hair']['color'] = 'url(javascript:evil)';
paperdoll_test_reject(function() use($bad, $catalog) { paperdoll_normalize_appearance($bad, $catalog); }, '拒绝非十六进制颜色');
$bad = $appearance; $bad['classic'] = 'false';
paperdoll_test_reject(function() use($bad, $catalog) { paperdoll_normalize_appearance($bad, $catalog); }, '显示偏好要求布尔值');
$bad = $appearance; $bad['accessories'] = array(array('id' => 'glasses01', 'color' => '#000000'), array('id' => 'glasses02', 'color' => '#000000'));
paperdoll_test_reject(function() use($bad, $catalog) { paperdoll_normalize_appearance($bad, $catalog); }, '拒绝同位置叠加饰品');
$bad = $appearance; $bad['accessories'] = array(array('id' => 'glasses01', 'color' => '#000000'), array('id' => 'glasses01', 'color' => '#000000'));
paperdoll_test_reject(function() use($bad, $catalog) { paperdoll_normalize_appearance($bad, $catalog); }, '拒绝重复饰品');
try { paperdoll_load('{broken', $catalog); paperdoll_test_assert(false, '损坏状态不得默认为免费账户'); }
catch(RuntimeException $e) { paperdoll_test_assert(true, '损坏状态不得默认为免费账户'); }
paperdoll_test_assert(strpos(paperdoll_json(array('label' => '</script>')), '</script>') === false, 'bootstrap编码不会提前关闭script标签');

// 使用正式目录补足资源与账户契约的集成检查，不连接或修改真实数据库。
$actual_catalog = paperdoll_catalog();
$actual_default = paperdoll_default();
paperdoll_test_assert(paperdoll_normalize_appearance($actual_default) === paperdoll_normalize_appearance($actual_catalog['default'] + array('classic' => false)), '正式目录默认形象规范化一致');
$assets = array($actual_catalog['base']);
foreach(array('hair', 'top', 'bottom', 'accessories') as $category) {
	paperdoll_test_assert(count($actual_catalog[$category]) === 16, '正式目录 '.$category.' 有16种部件');
	$ids = array();
	foreach($actual_catalog[$category] as $part) {
		$ids[] = $part['id'];
		$sample = $actual_default;
		$selection = array('id' => $part['id'], 'color' => $part['color']);
		if($category === 'accessories') $sample[$category] = array($selection);
		else $sample[$category] = $selection;
		paperdoll_normalize_appearance($sample);
		$assets[] = $part['src'];
	}
	paperdoll_test_assert(count(array_unique($ids)) === 16, '正式目录 '.$category.' 编号无重复且全部可选择');
}
foreach($assets as $asset) {
	$dimensions = is_file(GAME_ROOT.$asset) ? getimagesize(GAME_ROOT.$asset) : false;
	if(!$dimensions || $dimensions[0] !== 110 || $dimensions[1] !== 160 || $dimensions['mime'] !== 'image/png') {
		paperdoll_test_assert(false, '正式素材存在且尺寸格式正确：'.$asset);
	}
}
paperdoll_test_assert(count($assets) === 65, '65张正式PNG素材均存在且为110×160');
$db = new PaperdollTestDb();
$db->user = array('uid' => 1, 'password' => md5('paperdoll-test'), 'groupid' => 1, 'credits2' => 100, 'paperdoll' => null);
$result = paperdoll_save($db->user, paperdoll_test_input($db->user, $actual_default));
paperdoll_test_assert($result['cost'] === 0 && $result['changed'] === 0 && !$result['state']['free_used'], '正式默认形象原样保存不消耗免费资格');
$custom = $actual_default;
$custom['hair'] = array('id' => $actual_catalog['hair'][1]['id'], 'color' => $actual_catalog['hair'][1]['color']);
$custom['top'] = array('id' => $actual_catalog['top'][0]['id'], 'color' => $actual_catalog['top'][0]['color']);
$custom['bottom'] = array('id' => $actual_catalog['bottom'][1]['id'], 'color' => $actual_catalog['bottom'][1]['color']);
$custom['accessories'] = array(array('id' => $actual_catalog['accessories'][0]['id'], 'color' => $actual_catalog['accessories'][0]['color']));
$result = paperdoll_save($db->user, paperdoll_test_input($db->user, $custom));
paperdoll_test_assert($result['cost'] === 0 && $result['changed'] === 4 && $result['credits2'] === 100 && $result['state']['free_used'], '正式目录首次四部件修改免费保存');
$custom = $result['state'];
$custom['hair']['color'] = '#123456';
$result = paperdoll_save($db->user, paperdoll_test_input($db->user, $custom));
paperdoll_test_assert($result['cost'] === 20 && $result['changed'] === 1 && $result['credits2'] === 80, '正式目录之后换色一次扣20切糕');
paperdoll_test_assert(paperdoll_load($db->user['paperdoll']) === $result['state'], '正式目录持久化后重读一致');
echo "Paperdoll regression checks completed.\n";
