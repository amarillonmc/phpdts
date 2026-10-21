<?php
// 仅供本机 PHP 内置服务器联调；不读取部署配置，不连接站点数据库。
// php -d short_open_tag=1 -S 127.0.0.1:8097 -t . gamedata/ruleset/ANOTHER/tests/browser_router.php
if(PHP_SAPI!=='cli-server' || !in_array($_SERVER['REMOTE_ADDR']??'',array('127.0.0.1','::1'),true)) { http_response_code(403); exit; }
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if(preg_match('~^/(gamedata/ruleset/ANOTHER/(assets|img)/|img/)[a-zA-Z0-9_./-]+\.(css|js|svg|gif|png|jpg)$~',$path) && strpos($path,'..')===false) return false;
define('IN_GAME',true);
define('GAME_ROOT',dirname(__DIR__,4).'/');
require __DIR__.'/fixture.php';
require dirname(__DIR__).'/include/ruleset.func.php';
require GAME_ROOT.'include/template.func.php';
function language($file,$id=0,$dir='') { return GAME_ROOT.'templates/default/'.$file.'.lang.php'; }
function template($file,$id=0,$dir='') {
    parse_template($file,$id,$dir);
    return GAME_ROOT.'gamedata/templates/'.$id.'_'.$file.'.tpl.php';
}
function gexit($message) { throw new RuntimeException($message); }
error_reporting(E_ALL);
set_error_handler(function($level,$message,$file,$line){ throw new ErrorException($message,0,$level,$file,$line); });
$state_path=sys_get_temp_dir().'/phpdts-another-browser-'.substr(sha1(GAME_ROOT),0,12).'.state';
$plock=fopen($state_path,'c+b'); flock($plock,LOCK_EX);
$state=stream_get_contents($plock);
an_fixture_reset(); $groomid=1; $cuser='湾城测试员';
if($state && $path!=='/__reset') {
    $stored=unserialize($state,array('allowed_classes'=>array('AnotherTestDB')));
    foreach($stored as $key=>$value) $GLOBALS[$key]=$value;
} else {
    $test_offset=0; $starttime=$now=time();
    $db->tables['bra_users'][1]=array('uid'=>1,'username'=>$cuser,'password'=>'browser-fixture-only','groupid'=>1,'ip'=>'127.0.0.1','gender'=>'m','icon'=>0,'wingames'=>0);
    another_seed_world($now);
}
$now=time()+$test_offset; $udata=$db->tables['bra_users'][1]; $cpass=$udata['password'];
register_shutdown_function(function()use($plock){
    $saved=array();
    foreach(array('db','gamevars','test_gameinfo','gamenum','gamestate','starttime','test_ends','test_offset') as $key) $saved[$key]=$GLOBALS[$key];
    rewind($plock); ftruncate($plock,0); fwrite($plock,serialize($saved)); fflush($plock); flock($plock,LOCK_UN); fclose($plock);
});
if($path==='/__advance') {
    $test_offset+=max(0,min(4000,(int)($_GET['seconds']??0)));
    header('Location: /game.php'); exit;
}
if($path==='/__opponent') {
    $room=another_read_room(); $players=another_read_players(); $old_room=$room; $old_players=$players;
    $other=array('uid'=>2,'username'=>'对手测试员','password'=>'browser-fixture-other','groupid'=>1,'ip'=>'127.0.0.2','gender'=>'f','icon'=>0,'wingames'=>0);
    $db->tables['bra_users'][2]=$other;
    another_join($other,array('controls'=>1),$room,$players,$now);
    foreach($players as &$actor) if(!$actor['type']) foreach($players as $target) if($target['pid']!=$actor['pid'] && (int)$target['pls']===(int)$actor['pls']) another_discover_player($actor,$target,$now);
    unset($actor);
    another_save_world($room,$players,$old_room,$old_players);
    header('Location: /game.php'); exit;
}
if(!in_array($path,array('/','/game.php','/valid.php','/command.php','/end.php','/__reset'),true)) { http_response_code(404); exit; }
another_http_page($path==='/command.php'?'command':($path==='/valid.php'?'join':'game'));
