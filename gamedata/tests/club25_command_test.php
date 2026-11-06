<?php
if(!defined('IN_GAME')) exit('Access Denied');
// php -d short_open_tag=1 -r "define('IN_GAME',true); require 'gamedata/tests/club25_command_test.php';"
// 在临时站点运行真实 command.php 与 club25_skill，仅数据库、外围初始化及画面渲染用桩。
$cases = Array('enemy-board','enemy-modify','item-hide','item-feed','corpse-feed','corpse-away');
if(empty($argv[1])) {
	foreach($cases as $case) {
		$script = "define('IN_GAME',true); require ".var_export(__FILE__,true).';';
		passthru(escapeshellarg(PHP_BINARY).' -d short_open_tag=1 -r '.escapeshellarg($script).' -- '.escapeshellarg($case),$status);
		if($status) exit($status);
	}
	exit;
}
$case=$argv[1];
if(!in_array($case,$cases,true)) throw new Exception('Unknown case');
$repo=dirname(__DIR__,2).DIRECTORY_SEPARATOR;
$testroot=sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('phpdts-c25-command-').DIRECTORY_SEPARATOR;
define('GAME_ROOT',$testroot);
define('TPLDIR','templates');
mkdir($testroot); mkdir($testroot.'include'); mkdir($testroot.'include/game'); mkdir($testroot.'templates');
$written=Array();
function c25_command_file($path,$content) {
	global $testroot,$written;
	file_put_contents($testroot.$path,$content); $written[]=$testroot.$path;
}
c25_command_file('include/common.inc.php','<?php');
c25_command_file('include/game.func.php','<?php');
foreach(Array('club25.func.php','club25.items.php') as $file)
	c25_command_file('include/game/'.$file,'<?php require '.var_export($repo.'include/game/'.$file,true).';');
c25_command_file('include/game/revbattle.func.php', <<<'PHP'
<?php
namespace revbattle;
function revbattle_prepare($command) { throw new \Exception('只读/被拒命令不应再次进入战斗准备：'.$command); }
function findenemy_rev($enemy) { $GLOBALS['enemy_renders']++; $GLOBALS['cmd']='enemy:'.$enemy['pid']; }
PHP
);
c25_command_file('include/game/battle.func.php', <<<'PHP'
<?php
function findcorpse($enemy) { $GLOBALS['corpse_renders']++; $GLOBALS['cmd']='corpse:'.$enemy['pid']; }
PHP
);
c25_command_file('templates/stub.php','<?php echo "template:".$GLOBALS["selected_template"];');

class Club25CommandDB {
	public function query($sql) {
		if(preg_match('/^SELECT .* FROM test_(log|maptrap|mapitem) /',$sql)) return Array();
		throw new Exception('Unexpected SQL: '.$sql);
	}
	public function fetch_array(&$result) { return array_shift($result); }
}
function config($name,$version) { return $GLOBALS['repo'].'gamedata/cache/'.$name.'_'.$version.'.php'; }
function fetch_playerdata_by_name($name) { return $GLOBALS['fixture']; }
function fetch_playerdata_by_pid($pid) { return $pid === 2 ? $GLOBALS['opponent'] : false; }
function get_clbpara($value) { return is_array($value) ? $value : (json_decode($value,true) ?: Array()); }
function get_itmpara($value) { return get_clbpara($value); }
function get_itmsk_array($value) { return str_split((string)$value); }
function init_playerdata() {}
function init_profile() {}
function init_bgm() { return ''; }
function wheelchair_entry_notice(&$data) {}
function wheelchair_has($data,$skill,$active=true) { return false; }
function wheelchair_review_after(&$data,$before,$command) {}
function wheelchair_panel($data) { return ''; }
function wheelchair_penalty_count($data) { return 0; }
function player_save($data) { $GLOBALS['saved']=$data; }
function template($name) { $GLOBALS['selected_template']=$name; return GAME_ROOT.'templates/stub.php'; }
function compatible_json_encode($data) { return json_encode($data,JSON_UNESCAPED_UNICODE); }
function c25_command_assert($ok,$message) { if(!$ok) throw new Exception($message); }

$fixture=Array('pid'=>1,'name'=>'可怜猫测试','pass'=>'test','type'=>0,'club'=>25,'hp'=>100,'sp'=>100,'rage'=>100,
	'att'=>100,'wd'=>100,'lvl'=>1,'clbpara'=>Array('skill'=>Array()),'action'=>'','bid'=>0,'pls'=>1,'pgroup'=>0,
	'arbsk'=>'','arbs'=>0,'arbe'=>0,'state'=>0,'teamID'=>'','cmdnum'=>0,'wep'=>'测试刀','wepk'=>'WK','wepe'=>30,'weps'=>6,'wepsk'=>'','weppara'=>'');
for($i=0;$i<=6;$i++) foreach(Array('itm'=>'','itmk'=>'','itme'=>0,'itms'=>0,'itmsk'=>'','itmpara'=>'') as $field=>$value) $fixture[$field.$i]=$value;
$opponent=Array('pid'=>2,'name'=>'测试对象','hp'=>100,'pls'=>1,'pgroup'=>0);
$mode='club25'; $command='board'; $c25_target='wep'; $c25_count=1; $c25_kind='damage';
if(strpos($case,'enemy-') === 0) {
	$fixture['action']='enemy'; $fixture['bid']=2;
	$command=$case === 'enemy-board' ? 'board' : 'modify';
} elseif(strpos($case,'item-') === 0) {
	$fixture['itm0']='待拾取补给'; $fixture['itmk0']='HB'; $fixture['itme0']=70; $fixture['itms0']=3;
	$fixture['itmpara0']=Array('other_marker'=>17);
	$command=$case === 'item-hide' ? 'hide' : 'feed'; $c25_target='0';
} else {
	$fixture['action']='corpse'; $fixture['bid']=2; $opponent['hp']=0; $command='feed';
	if($case === 'corpse-away') $opponent['pls']=2;
}
$cuser=$fixture['name']; $cpass='test'; $gamestate=1; $db=new Club25CommandDB; $tablepre='test_';
$now=100; $noisetime=$noiselimit=$noisemode=0; $coldtimeon=false; $sp_cmd=''; $nosta='∞';
$plsinfo=Array(1=>'测试地图'); $hplsinfo=Array(); $alivenum=2; $chatinfo=Array('公聊','队聊');
$enemy_renders=$corpse_renders=0; $saved=NULL; $_GET=Array();
$_POST=Array('mode'=>$mode,'command'=>$command,'c25_target'=>$c25_target);
$oldcwd=getcwd(); $buffer_level=ob_get_level();
try {
	chdir($testroot);
	// command.php 会清理并关闭内层缓冲；外层捕获真实 JSON 响应。
	ob_start(); ob_start();
	require $repo.'command.php';
	$response=json_decode(ob_get_clean(),true);
	c25_command_assert(is_array($response),'响应必须是有效JSON');
	c25_command_assert(is_array($saved),'必须执行玩家保存');
	if(strpos($case,'enemy-') === 0) {
		c25_command_assert($enemy_renders === 1 && $response['innerHTML']['cmd'] === 'enemy:2','保留现有敌人画面');
		c25_command_assert($saved['action'] === 'enemy' && $saved['bid'] === 2,'不能靠技能退出遭遇');
		c25_command_assert($saved['weps'] === 6 && $saved['sp'] === 100 && $saved['hp'] === 100,'刷新/拒绝改装不消耗武器或发动战斗');
	} elseif(strpos($case,'item-') === 0) {
		c25_command_assert($response['innerHTML']['cmd'] === 'template:itemfind','仍显示物品发现页');
		c25_command_assert($saved['itm0'] === '待拾取补给' && $saved['itms0'] === 3 && $saved['itme0'] === 70,'临时物品名称效果数量完整保存');
		$para=get_itmpara($saved['itmpara0']);
		c25_command_assert($para['other_marker'] === 17,'原物品参数保留');
		if($case === 'item-hide') c25_command_assert(isset($para['c25_hide']) && $saved['sp'] === 80,'真实捉迷藏加工写入并扣体力');
		else c25_command_assert($saved['itmk1'] === 'HB' && $saved['itms1'] === 3 && $saved['rage'] === 75,'投喂写入空背包槽');
	} elseif($case === 'corpse-feed') {
		c25_command_assert($corpse_renders === 1 && $response['innerHTML']['cmd'] === 'corpse:2','尸体页技能完成后恢复尸体画面');
		c25_command_assert($saved['itms1'] === 3 && $saved['bid'] === 2,'投喂与尸体标记同时保存');
	} else c25_command_assert(!$corpse_renders && $response['innerHTML']['cmd'] === 'template:command','已离开当前地图的尸体不恢复');
	echo 'PASS: '.$case." 实际 command.php 请求与玩家保存\n";
} finally {
	while(ob_get_level()>$buffer_level) ob_end_clean();
	chdir($oldcwd);
	foreach($written as $path) unlink($path);
	rmdir($testroot.'templates'); rmdir($testroot.'include/game'); rmdir($testroot.'include'); rmdir($testroot);
}
