<?php
if(!defined('IN_GAME')) exit('Access Denied');
$repo = dirname(__DIR__,2).DIRECTORY_SEPARATOR;
function c25_resource_assert($ok,$label) {
	if(!$ok) { fwrite(STDERR,"FAIL: $label\n"); exit(1); }
	echo "PASS: $label\n";
}

// 模板单独使用临时根目录，真实编译并渲染，不修改部署模板缓存。
if(($argv[1] ?? '') === 'templates') {
	$testroot = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('phpdts-c25-').DIRECTORY_SEPARATOR;
	define('GAME_ROOT',$testroot);
	mkdir($testroot); mkdir($testroot.'templates'); mkdir($testroot.'templates/default');
	mkdir($testroot.'gamedata'); mkdir($testroot.'gamedata/templates');
	file_put_contents($testroot.'language.php','<?php');
	function language($name,$id,$dir) { return GAME_ROOT.'language.php'; }
	function template($name) { return GAME_ROOT.'gamedata/templates/test_'.$name.'.tpl.php'; }
	function club25_board($data) { return $GLOBALS['testrows']; }
	function club25_config() { return Array('feed_rage'=>25); }
	function parse_skilldesc($id,$data) { return '技能说明'; }
	require $repo.'include/template.func.php';
	$files = Array('club25_panel','slidingpanel','battlecmd_rev','skill_c25_modify','skill_c25_hide','skill_c25_feed','skill_c25_field','skill_c25_trip');
	try {
		foreach($files as $file) {
			copy($repo.'templates/default/'.$file.'.htm',$testroot.'templates/default/'.$file.'.htm');
			parse_template($file,'test','templates/default');
			$lint = Array(); $code = 0;
			exec(escapeshellarg(PHP_BINARY).' -d short_open_tag=1 -l '.escapeshellarg(template($file)),$lint,$code);
			c25_resource_assert($code === 0,'模板编译 '.$file);
		}
		$club=25; $bid=4; $action='enemy'; $clbpara=Array('c25_catstep'=>1);
		$pdata=Array('wep'=>'<script>wep</script>','weps'=>3,'itm0'=>'<b>food</b>','itms0'=>1);
		$testrows=Array(Array('key'=>'i3','kind'=>'<tag>','source'=>'food','name'=>'<script>bad</script>','effect'=>70,'count'=>3,'place'=>'<place>','local'=>true,'owned'=>true,'can_net'=>true));
		ob_start(); include template('club25_panel'); $html=ob_get_clean();
		c25_resource_assert(strpos($html,'<script>bad</script>') === false && strpos($html,'&lt;script&gt;bad&lt;/script&gt;') !== false,'布场物品名转义');
		c25_resource_assert(strpos($html,'&lt;place&gt;') !== false && strpos($html,'&lt;tag&gt;') !== false,'地点与分类转义');
		c25_resource_assert(strpos($html,'value="撤回"') !== false && strpos($html,'value="收网"') !== false,'当地自有布置可撤回及收网');
		$club=0; ob_start(); include template('club25_panel'); $html=ob_get_clean();
		c25_resource_assert(trim($html) === '', '其他社团无可怜猫控制内容');
	} finally {
		foreach($files as $file) {
			@unlink($testroot.'templates/default/'.$file.'.htm'); @unlink(template($file));
		}
		unlink($testroot.'language.php'); rmdir($testroot.'templates/default'); rmdir($testroot.'templates');
		rmdir($testroot.'gamedata/templates'); rmdir($testroot.'gamedata'); rmdir($testroot);
	}
	exit;
}

define('GAME_ROOT',$repo);
function get_clbpara($value) { return is_array($value) ? $value : (json_decode($value,true) ?: Array()); }
function get_achlist($id) { return $GLOBALS['ach_list'][$id] ?? Array(); }
function get_title($title,$who) { $GLOBALS['awards'][]=Array($title,$who); }
function changeclub($club,&$data) { $data['club']=$club; }
class Club25AchievementDB {
	public $ach = Array();
	public $players = Array();
	public function query($sql) {
		if(preg_match("/SELECT achrev .* username = '([^']*)'/",$sql,$m)) return $this->ach[$m[1]] ?? '';
		if(preg_match("/UPDATE .* SET achrev='(.*)' WHERE username='([^']*)'/",$sql,$m)) { $this->ach[$m[2]]=$m[1]; return true; }
		if(preg_match("/SELECT \\* .* WHERE name='([^']*)' AND type=0/",$sql,$m)) return $this->players[$m[1]] ?? Array();
		throw new Exception('Unexpected SQL: '.$sql);
	}
	public function result($value,$row) { return $value; }
	public function fetch_array($value) { return $value; }
}
require $repo.'gamedata/cache/achievement_1.php';
require $repo.'include/game/achievement.func.php';
require $repo.'include/game/item.club_card.php';
$db=new Club25AchievementDB; $tablepre='test_'; $gtablepre='global_'; $titles_list=Array(124=>'请勿投喂'); $awards=Array();
$winner=Array('name'=>'猫','type'=>0,'hp'=>100,'clbpara'=>Array('achvars'=>Array('c25_trap_hit'=>1,'c25_food_hit'=>1)));
$incomplete=$winner; unset($incomplete['clbpara']['achvars']['c25_food_hit']);
check_club25_achievement('猫',2,$incomplete);
c25_resource_assert(!$awards,'只完成一类伤害不授奖');
$dead=$winner; $dead['hp']=0; check_club25_achievement('猫',2,$dead);
check_club25_achievement('猫',5,$winner);
c25_resource_assert(!$awards,'死亡或全灭结局不授奖');
check_club25_achievement('猫',2,$winner);
check_club25_achievement('猫',2,$winner);
c25_resource_assert(count($awards) === 1 && check_achievement_rev(210,'猫') === 999,'胜利授予头衔且重复结算不重复授奖');
$teammate=$winner; $teammate['name']='队友'; $teammate['clbpara']=json_encode($teammate['clbpara']); $db->players['队友']=$teammate;
check_club25_achievement('队友',7);
c25_resource_assert(count($awards) === 2 && $awards[1][1] === '队友','团队结算读取队员自己的持久化记录');
$card=Array('club'=>0,'type'=>0,'hp'=>100,'itm6'=>'沾着猫毛的粉黑蝴蝶结','itmk6'=>'ZB','itme6'=>25,'itms6'=>1,'itmsk6'=>'v','itmpara6'=>Array('test'=>1));
$club_skillslist=Array(25=>Array('c25_karma')); $log='';
$blocked=$card; $blocked['club']=1; item_club_card(6,$blocked);
c25_resource_assert($blocked['club']===1 && $blocked['itms6']===1,'已有社团不能使用社团卡且不消耗');
$blocked=$card; $blocked['hp']=0; item_club_card(6,$blocked);
c25_resource_assert($blocked['club']===0 && $blocked['itms6']===1,'死者不能入社');
item_club_card(6,$card);
c25_resource_assert($card['club']===25 && $card['itms6']===0 && $card['itmpara6']==='', '无社团活人入社并清空社团卡全部字段');
