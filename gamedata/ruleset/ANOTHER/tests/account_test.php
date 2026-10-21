<?php
if (!defined('IN_GAME')) exit('Access Denied');
error_reporting(E_ALL);
set_error_handler(function($severity,$message,$file,$line) { throw new ErrorException($message,0,$severity,$file,$line); });
if (!defined('GAME_ROOT')) define('GAME_ROOT', dirname(__DIR__,4).DIRECTORY_SEPARATOR);
require_once GAME_ROOT.'include/system.func.php';

class AnotherAccountTestRows {
    public $rows;
    public function __construct($rows) { $this->rows = array_values($rows); }
}
class AnotherAccountTestDB {
    public $users = array();
    public $players = array();
    public $writes = array();
    public function query($sql) {
        if (strpos($sql, 'RIGHT JOIN') !== false) {
            $rows = array();
            foreach ($this->players as $p) if (!$p['type']) $rows[] = array_merge($this->users[$p['name']], $p);
            return new AnotherAccountTestRows($rows);
        }
        if ($sql === 'SELECT * FROM room_players') return new AnotherAccountTestRows($this->players);
        if (preg_match("/^SELECT \\* FROM global_users WHERE username=X'([a-f0-9]+)'$/", $sql, $match)) {
            $owner = hex2bin($match[1]);
            return new AnotherAccountTestRows(isset($this->users[$owner]) ? array($this->users[$owner]) : array());
        }
        throw new RuntimeException('Unexpected query: '.$sql);
    }
    public function fetch_array($result) { return array_shift($result->rows); }
    public function multi_update($table, $updates, $key) {
        if ($table !== 'global_users' || $key !== 'username') throw new RuntimeException('Wrong account target');
        $this->writes[] = $updates;
        foreach ($updates as $owner=>$data) $this->users[$owner] = array_merge($this->users[$owner],$data);
    }
}
function check_end_achievement_rev($owner,$mode,$data=null) {
    global $db, $achievement_calls;
    $achievement_calls[] = array($owner,$mode);
    $db->users[$owner]['credits'] += 7;
}
function aa_player($pid,$name,$owner,$hp=400) {
    $p = array('pid'=>$pid,'name'=>$name,'type'=>0,'hp'=>$hp,'lvl'=>2,'killnum'=>0,'money'=>0,
        'wp'=>0,'wk'=>0,'wc'=>0,'wg'=>0,'wd'=>0,'wf'=>0,
        'clbpara'=>json_encode(array('another'=>array('owner'=>$owner))));
    foreach (array('wep','wep2','arb','arh','ara','arf','art') as $slot) $p[$slot.'para']='{}';
    for($i=0;$i<=6;$i++) $p['itmpara'.$i]='{}';
    return $p;
}
$checks = 0;
function aa_check($condition,$message) { global $checks; $checks++; if (!$condition) throw new RuntimeException($message); }
$db = new AnotherAccountTestDB();
$gtablepre='global_'; $tablepre='room_'; $gamblingon=0; $gamenum=1; $pdata=array();
$db->users = array('Old'=>array('username'=>'Old','credits'=>100,'credits2'=>20,'validgames'=>5,'wingames'=>1));
$db->players = array(1=>aa_player(1,'Old','Old'));
$winner='Old'; $winmode=2;
set_credits();
aa_check($db->users['Old']['credits']==301 && $db->users['Old']['credits2']===30,'普通模式无钩子时沿用原生积分');
aa_check($db->users['Old']['validgames']===6 && $db->users['Old']['wingames']===2,'普通模式局数不变');

require_once dirname(__DIR__).'/include/another.core.php';
require_once dirname(__DIR__).'/include/another.account.php';
aa_check(ruleset_legacy_bot_allowed_hook()===false,'ANOTHER拒绝旧BOT协议');
$initial_users = array(
    'Alice'=>array('username'=>'Alice','credits'=>100,'credits2'=>20,'validgames'=>5,'wingames'=>1),
    'Bob'=>array('username'=>'Bob','credits'=>200,'credits2'=>30,'validgames'=>6,'wingames'=>2),
    'Carol'=>array('username'=>'Carol','credits'=>300,'credits2'=>40,'validgames'=>7,'wingames'=>3));
$db->users=$initial_users; $db->writes=array();
$db->players=array(1=>aa_player(1,'Alice','Alice',0),2=>aa_player(2,'AN2:1:abc123','Alice',100),
    3=>aa_player(3,'Bob','Bob',0),4=>aa_player(4,'Carol','Carol',0),5=>aa_player(5,'discarded','',0));
$db->players[1]['killnum']=1; $db->players[1]['money']=200;
$db->players[2]['killnum']=3; $db->players[2]['money']=800; $db->players[2]['lvl']=5; $db->players[2]['wk']=25;
$winner='Alice'; $winmode=2; $achievement_calls=array();
$gamevars=array('another'=>array('result'=>array('winners'=>array(1,2,3))));
set_credits();
aa_check($db->users['Alice']['credits']==314,'双控角色击杀与金钱合计，等级熟练取最高');
aa_check($db->users['Bob']['credits']==408,'死亡队友仍获胜，基础积分与成就都到账');
aa_check($db->users['Carol']['credits']==311,'非胜者沿用死亡基础积分');
aa_check($db->users['Alice']['validgames']===6 && $db->users['Alice']['wingames']===2 && $db->users['Alice']['credits2']===30,'双控账户只计一局一次奖励');
aa_check($db->users['Bob']['wingames']===3 && $db->users['Carol']['wingames']===3,'所有胜利账户各加一次胜局');
aa_check(count($db->writes[0])===3 && !isset($db->users['AN2:1:abc123']),'不向虚构副角色或无主行写账户');
aa_check($achievement_calls===array(array('Bob',2)),'主胜者成就不重复，队友成就一次');
$after=$db->users; set_credits();
aa_check($after===$db->users && count($db->writes)===1 && count($achievement_calls)===1,'重复结算不发奖');
$gamevars=json_decode(json_encode($gamevars),true); set_credits();
aa_check($after===$db->users && count($db->writes)===1,'重新加载后结算仍幂等');
$db->users=$initial_users; $db->writes=array(); $achievement_calls=array(); $winmode=3;
$gamevars=array('another'=>array('result'=>array('winners'=>array(1,2,3)))); set_credits();
aa_check($db->users['Alice']['credits']==614 && $db->users['Bob']['credits']==708,'异常终止全队使用原生500胜利积分');
$db->users=$initial_users; $db->writes=array(); $achievement_calls=array(); $winmode=1; $winner='';
$gamevars=array('another'=>array('result'=>array('winners'=>array()))); set_credits();
aa_check($db->users['Alice']['credits']==139 && $db->users['Bob']['credits']==211 && !$achievement_calls,'无胜者结局只发参与积分');

// 用真实入口文件配临时common桩：检查拒绝发生在旧游戏逻辑加载前，也验证普通模式仍放行。
$temp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'another-bot-test-'.bin2hex(random_bytes(6));
mkdir($temp); mkdir($temp.'/include'); mkdir($temp.'/bot');
copy(GAME_ROOT.'botservice.php',$temp.'/botservice.php');
copy(GAME_ROOT.'bot/revbotservice.php',$temp.'/bot/revbotservice.php');
file_put_contents($temp.'/include/game.func.php', '<?php echo "legacy_allowed=1\n"; exit;');
try {
    foreach (array(false,true) as $mode) {
        $common = '<?php define("IN_GAME",true); define("GAME_ROOT",dirname(__DIR__).DIRECTORY_SEPARATOR);';
        if ($mode) $common .= 'require '.var_export(dirname(__DIR__).'/include/another.account.php',true).';';
        file_put_contents($temp.'/include/common.inc.php',$common);
        foreach (array('botservice.php','bot/revbotservice.php') as $entry) {
            $pipes=array();
            $process=proc_open(array(PHP_BINARY,'-d','short_open_tag=1',$temp.'/'.$entry),array(1=>array('pipe','w'),2=>array('pipe','w')),$pipes,$temp);
            if (!is_resource($process)) throw new RuntimeException('Cannot run bot fixture');
            $output=stream_get_contents($pipes[1]); $errors=stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]); $code=proc_close($process);
            aa_check($code===0 && $errors==='' && trim($output)===($mode?'ruleset_unsupported=1':'legacy_allowed=1'),'实际BOT入口隔离：'.$entry.($mode?' ANOTHER':' 普通模式'));
        }
    }
} finally {
    foreach (array('/botservice.php','/bot/revbotservice.php','/include/common.inc.php','/include/game.func.php') as $file) unlink($temp.$file);
    rmdir($temp.'/bot'); rmdir($temp.'/include'); rmdir($temp);
}
echo 'AN:OTHER accounts: '.$checks." checks passed; native credit path and both real BOT entries checked.\n";
