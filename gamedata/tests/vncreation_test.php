<?php
if(!defined('IN_GAME')) exit('Access Denied');
if(!defined('GAME_ROOT')) define('GAME_ROOT',dirname(__DIR__,2).DIRECTORY_SEPARATOR);

// 与其他独立回归脚本一致：假数据库验证付费、审核、投放，不接触站点数据。
function vc_assert($ok,$message) {
	if(!$ok) throw new RuntimeException('FAIL: '.$message);
	echo 'PASS: '.$message."\n";
}
function vc_reject($callback,$message) {
	try { $callback(); } catch(InvalidArgumentException $e) { vc_assert(true,$message); return; }
	throw new RuntimeException('FAIL: '.$message);
}
class VcResult {
	public $rows;
	function __construct($rows) { $this->rows = array_values($rows); }
}
class VcDb {
	public $users = array(), $rows = array(), $players = array(), $items = array();
	public $affected = 0, $lastid = 0, $locked = false, $failSave = false, $throwSave = false, $failDebit = false, $queryCount = 0;
	function query($sql,$mode='') {
		$this->queryCount++;
		$this->affected = 0;
		if(strpos($sql,'CREATE TABLE') === 0) return true;
		if(strpos($sql,'LOCK TABLES') === 0) { $this->locked = true; return true; }
		if($sql === 'UNLOCK TABLES') { $this->locked = false; return true; }
		if(strpos($sql,'SELECT gruleset FROM bra_game') === 0) return new VcResult(array(array('gruleset'=>'')));
		if(preg_match("/SELECT \* FROM bra_users WHERE username='([^']*)'/",$sql,$m)) return new VcResult(array_filter($this->users,function($u) use($m) { return $u['username'] === $m[1]; }));
		if(preg_match('/SELECT \* FROM bra_users WHERE uid=(\d+)/',$sql,$m)) return new VcResult(isset($this->users[$m[1]]) ? array($this->users[$m[1]]) : array());
		if(preg_match('/SELECT \* FROM bra_vncreation WHERE id=(\d+)/',$sql,$m)) return new VcResult(isset($this->rows[$m[1]]) ? array($this->rows[$m[1]]) : array());
		if(preg_match("/SELECT \* FROM bra_vncreation WHERE kind='(npc|item)'(?: AND status=(\d))? ORDER BY id DESC LIMIT (\d+),21/",$sql,$m)) {
			$rows = array_filter($this->rows,function($row) use($m) { return $row['kind'] === $m[1] && ($m[2] === '' || $row['status'] === (int)$m[2]); });
			krsort($rows);
			return new VcResult(array_slice(array_values($rows),(int)$m[3],21));
		}
		if(preg_match("/SELECT \* FROM bra_vncreation WHERE kind='(npc|item)' AND status=1/",$sql,$m)) {
			$rows = array_values(array_filter($this->rows,function($row) use($m) { return $row['kind'] === $m[1] && $row['status'] === 1; }));
			if(preg_match('/ORDER BY RAND\(\) LIMIT (\d+)/',$sql,$lim)) { shuffle($rows); $rows = array_slice($rows,0,(int)$lim[1]); }
			return new VcResult($rows);
		}
		if(preg_match('/UPDATE bra_users SET credits2=credits2([+-])(\d+) WHERE uid=(\d+)/',$sql,$m)) {
			if(!$this->locked) throw new RuntimeException('扣费缺少表锁');
			if($this->failDebit) { $this->failDebit = false; return false; }
			$cost = (int)$m[2]; $uid = (int)$m[3];
			if($m[1] === '-' && $this->users[$uid]['credits2'] < $cost) return true;
			$this->users[$uid]['credits2'] += $m[1] === '+' ? $cost : -$cost;
			$this->affected = 1;
			return true;
		}
		if(preg_match("/INSERT INTO bra_vncreation .* VALUES \((\d+),X'([a-f0-9]*)','(npc|item)',0,X'([a-f0-9]*)',(\d+),'',(\d+)\)/",$sql,$m)) {
			if($this->failSave) { $this->failSave = false; return false; }
			$id = ++$this->lastid;
			$this->rows[$id] = array('id'=>$id,'uid'=>(int)$m[1],'creator'=>hex2bin($m[2]),'kind'=>$m[3],'status'=>0,'content'=>hex2bin($m[4]),'spent'=>(int)$m[5],'note'=>'','updated'=>(int)$m[6]);
			return true;
		}
		if(preg_match("/UPDATE bra_vncreation SET content=X'([a-f0-9]*)',status=0,note='',spent=(\d+),updated=(\d+) WHERE id=(\d+)/",$sql,$m)) {
			if($this->throwSave) { $this->throwSave = false; throw new RuntimeException('模拟数据库异常'); }
			if($this->failSave) { $this->failSave = false; return false; }
			$id = (int)$m[4];
			$this->rows[$id] = array_replace($this->rows[$id],array('content'=>hex2bin($m[1]),'status'=>0,'note'=>'','spent'=>(int)$m[2],'updated'=>(int)$m[3]));
			return true;
		}
		if(preg_match("/UPDATE bra_vncreation SET status=(\d+),note=X'([a-f0-9]*)',updated=(\d+) WHERE id=(\d+)/",$sql,$m)) {
			$this->rows[$m[4]] = array_replace($this->rows[$m[4]],array('status'=>(int)$m[1],'note'=>hex2bin($m[2]),'updated'=>(int)$m[3]));
			return true;
		}
		throw new RuntimeException('未处理的测试SQL：'.$sql);
	}
	function fetch_array($result) { return array_shift($result->rows); }
	function num_rows($result) { return count($result->rows); }
	function affected_rows() { return $this->affected; }
	function insert_id() { return $this->lastid; }
	function array_insert($table,$data) {
		if($table === 'bra_players') $this->players[] = $data;
		elseif($table === 'bra_mapitem') $this->items[] = $data;
		else throw new RuntimeException('投放到了错误的表');
	}
}
if(defined('VN_CREATION_TEST_DB_ONLY')) return;
if(!defined('VN_CREATION_TEST_DB_ONLY')) {
function config($name,$version=1) {
	global $groomid,$vcConfigFile;
	if($groomid !== 0) throw new RuntimeException('投稿配置未使用大房间');
	return $name === 'vncreation' ? $vcConfigFile : GAME_ROOT.'gamedata/cache/'.$name.'_1.php';
}
function get_npcinit() { include GAME_ROOT.'gamedata/cache/npc_1.php'; return $npcinit; }
function player_format_with_db_structure($data) { return $data; }
function save_gameinfo() { $GLOBALS['savedGamevars'] = $GLOBALS['gamevars']; }
}

require GAME_ROOT.'include/vnworld/vncreation.func.php';
$vcConfigFile = tempnam(sys_get_temp_dir(),'phpdts_vn_');
copy(GAME_ROOT.'gamedata/cache/vncreation_1.php',$vcConfigFile);
register_shutdown_function(function() use($vcConfigFile) { unlink($vcConfigFile); });
$groomid = 0; $gamecfg = 1; $gtablepre = $tablepre = 'bra_'; $now = 1792627200;
$baseexp = 10; $nosta = '∞'; $gamevars = array(); $gamefounder = '站长';
$_SESSION = array();
$db = new VcDb();
$user = array('uid'=>1,'username'=>'测试作者','password'=>'test-only','groupid'=>1,'credits2'=>2000000);
$other = array_replace($user,array('uid'=>2,'username'=>'其他作者'));
$admin = array_replace($user,array('uid'=>3,'username'=>'审核员','groupid'=>5));
$db->users = array(1=>$user,2=>$other,3=>$admin);
$cfg = vncreation_config();
$groomid = 8;
vc_assert(vncreation_config()['places'] === $cfg['places'] && $groomid === 8,'私人房间投稿读取大房间配置并恢复房间上下文');
$groomid = 0;
vncreation_ensure_table();
$item = array('name'=>'星光药剂','k'=>'HH','e'=>50,'s'=>3,'sk'=>array('q'),'count'=>2,'place'=>1);
$clean = vncreation_normalize('item',$item,$cfg);
$price = vncreation_price('item',$clean,$cfg);
foreach(array('e','s','count') as $field) {
	$more = $clean; $more[$field]++;
	vc_assert(vncreation_price('item',$more,$cfg) > $price,'增加 '.$field.' 会增加费用');
}
$more = $clean; $more['sk'][] = 'A';
vc_assert(vncreation_price('item',$more,$cfg) > $price,'增加额外属性会增加费用');
$finite = $clean; $finite['s'] = 65535; $infinite = $clean; $infinite['s'] = 0;
vc_assert(vncreation_price('item',$infinite,$cfg) > vncreation_price('item',$finite,$cfg),'无限耐久比最高有限耐久更贵');
foreach(array('1.2','1e3',array(1),'-1') as $value) vc_reject(function() use($item,$value,$cfg) { $item['e']=$value; vncreation_normalize('item',$item,$cfg); },'拒绝非法效果输入');
foreach(array('<img src=x>','a\'b','a\\b','0',str_repeat('长',31)) as $value) vc_reject(function() use($item,$value,$cfg) { $item['name']=$value; vncreation_normalize('item',$item,$cfg); },'拒绝不安全、会被当作空物品或超长名称');
foreach(array('k'=>'admin','place'=>0,'count'=>101,'sk'=>array('invalid')) as $field=>$value) vc_reject(function() use($item,$field,$value,$cfg) { $item[$field]=$value; vncreation_normalize('item',$item,$cfg); },'拒绝越界种类、地点、数量或属性');
$npc = array('name'=>'梦境守卫','level'=>2,'equip'=>array('wep'=>array('name'=>'星刃','k'=>'WK','tier'=>3),'arb'=>array('name'=>'星衣','k'=>'DB','tier'=>4)), 'bag'=>array(6=>array('name'=>'守卫的赠礼','k'=>'HH','e'=>88,'s'=>0,'sk'=>array('q','v'))));
$normalized = vncreation_normalize('npc',$npc,$cfg);
$forged = $npc; $forged['equip']['wep']['roll'] = array('e'=>999999,'s'=>999999,'sk'=>array('X'));
vc_assert(vncreation_normalize('npc',$forged,$cfg) === $normalized,'客户端不能指定装备随机结果');
$npcPrice = vncreation_price('npc',$normalized,$cfg);
$result = vncreation_save($user,'npc',0,'save',$npc,'',$cfg);
$id = $result['id']; $row = vncreation_get($id); $stored = json_decode($row['content'],true);
vc_assert($row['status'] === 0 && $db->users[1]['credits2'] === $user['credits2']-$npcPrice,'新NPC保存并且精确扣费，进入待审核');
vc_assert(count($stored['equip']['wep']['roll']['sk']) === 2 && count($stored['equip']['arb']['roll']['sk']) === 3,'装备等级控制效果与普通、稀有属性数量');
$renamed = $npc; $renamed['name'] = '改名后的守卫';
vncreation_save($user,'npc',$id,'save',$renamed,vncreation_stamp($row),$cfg);
$row = vncreation_get($id); $renamedStored = json_decode($row['content'],true);
vc_assert($renamedStored['equip'] === $stored['equip'] && $db->users[1]['credits2'] === $result['balance'],'重新加载、改名不免费重掷也不重复扣费');
vc_reject(function() use($other,$id,$npc,$row,$cfg) { vncreation_save($other,'npc',$id,'save',$npc,vncreation_stamp($row),$cfg); },'禁止编辑他人投稿');
$balance = $db->users[1]['credits2']; $db->users[1]['credits2'] = 0;
vc_reject(function() use($user,$id,$row,$cfg) { vncreation_save($user,'npc',$id,'reroll',array('slot'=>'wep'),vncreation_stamp($row),$cfg); },'余额不足不能重掷');
vc_assert(vncreation_get($id) === $row && !$db->locked,'失败不改变投稿且释放表锁');
$db->users[1]['credits2'] = $balance;
foreach(array('failSave','throwSave','failDebit') as $failure) {
	$db->$failure = true;
	try { vncreation_save($user,'npc',$id,'reroll',array('slot'=>'wep'),vncreation_stamp($row),$cfg); throw new LogicException('应当失败'); }
	catch(RuntimeException $e) {}
	vc_assert($db->users[1]['credits2'] === $balance && vncreation_get($id) === $row && !$db->locked,'数据库 '.$failure.' 时不吞切糕、不改原投稿');
}
vc_reject(function() use($user,$id,$row) { vncreation_review($user,$id,1,'',vncreation_stamp($row)); },'普通用户不能审核');
vncreation_review($admin,$id,1,'采纳',vncreation_stamp($row));
$approved = vncreation_get($id);
$reroll = vncreation_save($user,'npc',$id,'reroll',array('slot'=>'wep'),vncreation_stamp($approved),$cfg);
$row = vncreation_get($id);
vc_assert($reroll['cost'] === 60 && $row['status'] === 0 && $row['note'] === '','重掷单件按等级扣费，已采纳投稿回到待审核');
vc_reject(function() use($admin,$id,$approved) { vncreation_review($admin,$id,1,'',vncreation_stamp($approved)); },'不能用重掷前的审核表单放行新内容');
vc_reject(function() use($user,$id,$approved,$cfg) { vncreation_save($user,'npc',$id,'reroll',array('slot'=>'wep'),vncreation_stamp($approved),$cfg); },'旧编辑表单不会再次扣费');
$token = vncreation_form_token('npc:0:save');
vc_assert(vncreation_check_token($token,'npc:0:save') === '','表单令牌绑定操作');
vc_reject(function() use($token) { vncreation_check_token($token,'admin:1'); },'不能跨操作复用令牌');
unset($_SESSION['vncreation_forms'][$token]);
vc_reject(function() use($token) { vncreation_check_token($token,'npc:0:save'); },'已消费的表单不能重复提交');
$moved = $normalized; unset($moved['equip']['arb']); $moved['equip']['arh'] = array('name'=>'星帽','k'=>'DH','tier'=>4);
vc_assert(vncreation_edit_cost('npc',$moved,$normalized,$cfg) >= 400,'移除旧部位不能抵扣新部位并免费刷新属性');
$changed = $normalized; $changed['equip']['wep']['k'] = 'WP';
vc_assert(vncreation_edit_cost('npc',$changed,$normalized,$cfg) === 60,'同级换武器种类也需重掷费');

vncreation_review($admin,$id,1,'',vncreation_stamp($row));
for($n=0;$n<4;$n++) {
	$npc['name'] = '池中守卫'.$n;
	$created = vncreation_save($user,'npc',0,'save',$npc,'',$cfg);
	if($n<3) vncreation_review($admin,$created['id'],1,'',vncreation_stamp(vncreation_get($created['id'])));
}
vncreation_spawn('npc');
vc_assert(count($db->players) === 3 && count(array_unique(array_column($db->players,'name'))) === 3,'NPC池随机抽取后台配置数量且不重复');
vc_assert(!in_array('池中守卫3',array_column($db->players,'name')),'未审核NPC不进入大房间');
$spawn = $db->players[0];
vc_assert($spawn['type'] === 95 && $spawn['hp'] === 1500 && $spawn['wk'] === 200 && $spawn['wepe'] === 200,'NPC使用所选战斗等级与装备等级');
vc_assert($spawn['itm6'] === '守卫的赠礼' && $spawn['itms6'] === $nosta && $spawn['itmsk6'] === 'qv' && $spawn['itmpara6'] === '','第六背包槽和无限耐久、属性完整写入NPC');
vc_assert(isset($cfg['places'][$spawn['pls']]),'NPC投放到大房间有效地点');
$before = $db->queryCount; $groomid = 9; vncreation_spawn('npc'); vncreation_spawn('item');
vc_assert($db->queryCount === $before,'私人房间不读取或生成公共投稿'); $groomid = 0;
vncreation_set_count(0,$cfg); $db->players = array(); vncreation_spawn('npc');
vc_assert(!$db->players && vncreation_config()['npc_count'] === 0,'后台数量配置持久生效，0可暂停NPC投放');
vncreation_set_count(100,$cfg); vncreation_spawn('npc');
vc_assert(count($db->players) === 4,'池不足时全部投放，不复制NPC凑数');
$fixed = vncreation_save($user,'item',0,'save',$item,'',$cfg);
vncreation_review($admin,$fixed['id'],1,'',vncreation_stamp(vncreation_get($fixed['id'])));
$item['place']=-1; $item['count']=3; $item['name']='游荡星光';
$random = vncreation_save($user,'item',0,'save',$item,'',$cfg);
vncreation_review($admin,$random['id'],1,'',vncreation_stamp(vncreation_get($random['id'])));
$item['name']='未审核物品'; vncreation_save($user,'item',0,'save',$item,'',$cfg);
vncreation_spawn('item');
vc_assert(count($db->items) === 5 && $db->items[0]['pls'] === 1 && $db->items[1]['pls'] === 1,'已采纳物品按数量及指定地点投放');
vc_assert($db->items[0]['itme'] === 50 && $db->items[0]['itms'] === 3 && $db->items[0]['itmsk'] === 'q' && $db->items[0]['itmpara'] === '','地图物品字段完整保留');
foreach($db->items as $drop) if(!isset($cfg['places'][$drop['pls']])) throw new RuntimeException('掉落地图非法');
$gamevars = $savedGamevars; vncreation_spawn('item');
vc_assert(count($db->items) === 5,'重新加载后重复刷新不会再次投放物品');
$gamevars = array(); vncreation_spawn('item');
vc_assert(count($db->items) === 10,'下一局重新投放');
vncreation_review($admin,$fixed['id'],2,'退回',vncreation_stamp(vncreation_get($fixed['id'])));
$gamevars = array(); $db->items = array(); vncreation_spawn('item');
vc_assert(count($db->items) === 3,'后台退回后停止后续物品投放');
vc_assert(vncreation_html('<script>"') === '&lt;script&gt;&quot;','审核说明按HTML上下文转义');
$disabled = $cfg; $disabled['enabled'] = false;
vc_reject(function() use($user,$npc,$disabled) { vncreation_save($user,'npc',0,'save',$npc,'',$disabled); },'独立规则集不接收原生付费投稿');
echo "幻想世界回归完成。\n";
