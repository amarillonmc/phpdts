<?php
// Optional local MySQL regression: php -d short_open_tag=1 gamedata/tests/fcg_database_test.php --local-db
// Only creates and removes randomly named test tables; existing account and game rows are untouched.
if(PHP_SAPI!=='cli' || ($argv[1] ?? '')!=='--local-db') exit("Use --local-db to test the configured local database.\n");
define('IN_GAME',true); define('GAME_ROOT',dirname(__DIR__,2).'/');
require isset($argv[2]) ? $argv[2] : GAME_ROOT.'config.inc.php';
if(!preg_match('/^(localhost|127\.0\.0\.1|::1)(:\d+)?$/',$dbhost)) exit("This test only supports a local database.\n");
require GAME_ROOT.'include/db_mysqli.class.php';
$db=new dbstuff(); $db->connect($dbhost,$dbuser,$dbpw,$dbname,0);
require GAME_ROOT.'include/fcg/account.php';
function roommng_acquire_create_lock() { return fopen('php://temp','r+'); }
function roommng_release_create_lock($lock) { fclose($lock); }
function dbcheck($ok,$message) { if(!$ok) throw new RuntimeException($message); }
function dbaction($state,$action,$more=array()) { return array_merge(array('action'=>$action,'rev'=>$state['rev'],'token'=>bin2hex(random_bytes(16))),$more); }
$gtablepre='fcgtest_'.bin2hex(random_bytes(6)).'_'; $created=array();
$cuser='fcg-test'; $cpass='local-test-password-hash'; $titles_list=array(9101=>'五色余烬',9106=>'具现的暗影');
try {
    $db->query("CREATE TABLE {$gtablepre}users (uid INT PRIMARY KEY,username VARCHAR(60),password VARCHAR(64),credits2 INT,nicksrev TEXT,roomid INT,groupid INT) ENGINE=MyISAM DEFAULT CHARSET=utf8"); $created[]=$gtablepre.'users';
    $db->query("CREATE TABLE {$gtablepre}game (groomid INT PRIMARY KEY,gruleset VARCHAR(50)) ENGINE=MyISAM DEFAULT CHARSET=utf8"); $created[]=$gtablepre.'game';
    fcg_ensure_storage(); $created[]=$gtablepre.'shop_items';
    $db->query("INSERT INTO {$gtablepre}game VALUES (0,''),(1,'FCG')");
    $db->query("INSERT INTO {$gtablepre}users (uid,username,password,credits2,nicksrev,roomid,groupid) VALUES (1,".fcg_sql($cuser).','.fcg_sql($cpass).",1000,'{\"nicks\":[0]}',0,1)");
    $s=fcg_request(array('action'=>'status'),1000);
    dbcheck(count($s['deck'])>=25 && $s['rev']===1,'first visit initializes durable collection');
    $input=dbaction($s,'buy',array('item'=>2,'price'=>10)); $s=fcg_request($input,1000); $once=$s;
    dbcheck($s['credits']===990,'purchase debited once');
    $s=fcg_request($input,1000); dbcheck($s['credits']===990 && $s['rev']===$once['rev'] && (array)$s['collection']===(array)$once['collection'],'duplicate purchase returns same account');
    $input=dbaction($s,'buy',array('item'=>2,'price'=>10));
    $db->query("UPDATE {$gtablepre}shop_items SET price_credits2=11 WHERE item_id=2");
    try { fcg_request($input,1000); throw new RuntimeException('old price accepted'); } catch(InvalidArgumentException $e) {}
    $s=fcg_request(array('action'=>'status'),1000); dbcheck($s['credits']===990,'repricing rejects stale quote without debit');
    $s=fcg_request(dbaction($s,'buy',array('item'=>20,'price'=>500)),1000);
    dbcheck($s['credits']===490 && in_array(9101,$s['titles']),'title grant and currency commit together');
    for($i=0;$i<5;$i++) $s=fcg_request(dbaction($s,'buy',array('item'=>1,'price'=>0)),1000);
    try { fcg_request(dbaction($s,'buy',array('item'=>1,'price'=>0)),1000); throw new RuntimeException('daily quota bypassed'); } catch(InvalidArgumentException $e) {}
    try { fcg_request(dbaction($s,'start',array('opponent'=>0)),1000); throw new RuntimeException('room bypassed'); } catch(InvalidArgumentException $e) {}
    $db->query("UPDATE {$gtablepre}users SET roomid=1 WHERE uid=1");
    $s=fcg_request(dbaction($s,'start',array('opponent'=>0)),1000); dbcheck($s['match'] && $s['in_room'],'actual room gates match start');
    $s=fcg_request(dbaction($s,'surrender',array('match'=>$s['match']['id'])),1000); dbcheck($s['match']['result']==='loss' && $s['played']===1,'match settles once');
    $s=fcg_request(array('action'=>'status'),1000); dbcheck($s['played']===1,'reload does not settle twice');
    $old=$db->fetch_array($db->query("SELECT * FROM {$gtablepre}users WHERE uid=1")); $user=$old; $data=fcg_load_account($old['fcgdata']); $data['rev']++; $user['credits2']-=10;
    dbcheck(fcg_account_save($old,$user,$data),'first competing writer commits');
    dbcheck(!fcg_account_save($old,$user,$data),'second writer with same snapshot cannot debit');
    $s=fcg_request(array('action'=>'status'),1000); dbcheck($s['credits']===480,'competing save deducted exactly once');
    // 让真实计时引擎结算一场胜局，再从数据库重读验证掉落只发一次。
    $row=$db->fetch_array($db->query("SELECT * FROM {$gtablepre}users WHERE uid=1")); $data=fcg_load_account($row['fcgdata']);
    $data['match']=fcg_new_match($data['deck'],0,1000); $data['match']['sides'][0]['atk']=100; $data['match']['ai_at']=PHP_INT_MAX;
    $before=array_sum($data['collection']); $db->query("UPDATE {$gtablepre}users SET fcgdata=".fcg_sql(fcg_json($data))." WHERE uid=1");
    $s=fcg_request(array('action'=>'status'),1010); $reward=$s['last_reward'];
    dbcheck($s['match']['result']==='win' && $s['played']===2 && $s['wins']->{0}===1,'timer victory settles through real database request');
    $s=fcg_request(array('action'=>'status'),1020);
    dbcheck($s['played']===2 && $s['last_reward']===$reward && array_sum((array)$s['collection'])===$before+1,'victory reward survives reload without duplication');
    echo "FCG database: migration, purchases, quota, price changes, title delivery, room gate, match settlement and competing writes passed on MyISAM.\n";
} finally { foreach(array_reverse($created) as $table) $db->query('DROP TABLE `'.$table.'`','SILENT'); }
