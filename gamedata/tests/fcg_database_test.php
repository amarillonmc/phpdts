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
require GAME_ROOT.'include/fcg/presentation.php';
function roommng_acquire_create_lock() { return fopen('php://temp','r+'); }
function roommng_release_create_lock($lock) { fclose($lock); }
function dbcheck($ok,$message) { if(!$ok) throw new RuntimeException($message); }
function dbaction($state,$action,$more=array()) { return array_merge(array('action'=>$action,'rev'=>$state['rev'],'token'=>bin2hex(random_bytes(16))),$more); }
function dbdraft($state,$action,$more=array()) { return dbaction($state,$action,array_merge(array('run'=>$state['run']['id'] ?? null,'layer'=>$state['run']['layer'] ?? null),$more)); }
function dbuser($uid,$input,$now=2000) { global $cuser; $cuser=$uid===1?'fcg-test':'fcg-peer'.$uid; return fcg_request($input,$now); }
function dbreject($uid,$input,$message,$now=2000) { try { dbuser($uid,$input,$now); } catch(InvalidArgumentException $e) { return; } throw new RuntimeException($message); }
$gtablepre='fcgtest_'.bin2hex(random_bytes(6)).'_'; $created=array();
$cuser='fcg-test'; $cpass='local-test-password-hash'; $titles_list=array(9101=>'五色余烬',9106=>'具现的暗影');
try {
    $db->query("CREATE TABLE {$gtablepre}users (uid INT PRIMARY KEY,username VARCHAR(60),password VARCHAR(64),credits2 INT,nicksrev TEXT,nick INT DEFAULT 0,roomid INT,groupid INT,icon VARCHAR(80) DEFAULT '1',gender CHAR(1) DEFAULT 'm') ENGINE=MyISAM DEFAULT CHARSET=utf8mb4"); $created[]=$gtablepre.'users';
    $db->query("CREATE TABLE {$gtablepre}game (groomid INT PRIMARY KEY,gruleset VARCHAR(50),gamenum INT DEFAULT 1,starttime INT DEFAULT 1000,gamevars TEXT) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4"); $created[]=$gtablepre.'game';
    $created[]=$gtablepre.'shop_items'; $created[]=$gtablepre.'fcg_matches'; $created[]=$gtablepre.'fcg_mirrors'; fcg_ensure_storage();
    $catalog=fcg_catalog();
    foreach(array(2=>false,8=>true) as $id=>$upgraded) {
        $payload=json_decode($catalog[$id]['reward_payload'],true);
        dbcheck($payload===array('upgraded'=>$upgraded,'rare'=>false),'new catalog persists rebalanced pack filter');
        foreach(fcg_pack_pool($payload) as $card) dbcheck(!fcg_cards()[$card]['rare'],'seeded shop packs exclude rare cards');
    }
    foreach(array(43=>40,44=>40,45=>26) as $id=>$count) {
        $payload=json_decode($catalog[$id]['reward_payload'],true);
        dbcheck($payload['series']==='vol3' && count(fcg_pack_pool($payload))===$count,'Vol.3 seed has correct pack population');
    }
    // Simulate an existing Vol.2 installation within these disposable test tables.
    $db->query("DELETE FROM {$gtablepre}shop_items WHERE item_id IN (43,44,45)");
    fcg_ensure_social_storage();
    $vol3=array();foreach(fcg_catalog(true) as $id=>$row) if((json_decode($row['reward_payload'],true)['series'] ?? '')==='vol3') $vol3[$id]=$row;
    dbcheck(count($vol3)===3,'existing installation receives all Vol.3 packs');
    $custom_id=(int)key($vol3);
    $db->query("UPDATE {$gtablepre}shop_items SET price_credits2=17,enabled=0 WHERE item_id=$custom_id");
    fcg_ensure_social_storage();fcg_ensure_social_storage();
    $vol3=array();foreach(fcg_catalog(true) as $id=>$row) if((json_decode($row['reward_payload'],true)['series'] ?? '')==='vol3') $vol3[$id]=$row;
    dbcheck(count($vol3)===3 && (int)$vol3[$custom_id]['price_credits2']===17 && (int)$vol3[$custom_id]['enabled']===0,'Vol.3 upgrade is idempotent and preserves admin price and visibility');
    $db->query("INSERT INTO {$gtablepre}game (groomid,gruleset,gamevars) VALUES (0,'','{}'),(1,'FCG','{}')");
    $db->query("INSERT INTO {$gtablepre}users (uid,username,password,credits2,nicksrev,roomid,groupid) VALUES (1,".fcg_sql($cuser).','.fcg_sql($cpass).",1000,'{\"nicks\":[0]}',0,1)");
    $s=fcg_request(array('action'=>'status'),1000);
    $boot=json_decode(fcg_bootstrap($s,'test-csrf',false),true);
    dbcheck($boot['draft_tiers']===fcg_draft_tiers(),'browser receives server entry and reward tiers');
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
    dbcheck($s['match']['portraits']===array('img/m_1.gif',null),'NPC match exposes own avatar only');
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
    // Fresh batch from a real persisted catalog; replay must not debit or grant twice.
    $input=dbaction($s,'buy',array('item'=>40,'price'=>10,'count'=>10));$s=dbuser(1,$input,1020);
    dbcheck($s['credits']===400 && count($s['last_reward']['cards'])===10,'batch charges discounted total');$batch=$s['last_reward'];
    $s=dbuser(1,$input,1020);dbcheck($s['credits']===400 && $s['last_reward']===$batch,'batch replay is idempotent');
    // Main room 0 is also a valid FCG room. Two real account rows share one match row.
    $db->query("UPDATE {$gtablepre}game SET gruleset='FCG' WHERE groomid=0");$db->query("UPDATE {$gtablepre}users SET roomid=0 WHERE uid=1");
    $peer=fcg_new_account();$peer['collection'][151]=1;$peer['deck'][]=151;$peer['decks'][0]['cards']=$peer['deck'];
    foreach(array(2,3) as $uid) $db->query("INSERT INTO {$gtablepre}users (uid,username,password,credits2,nicksrev,roomid,groupid,fcgdata) VALUES ($uid,".fcg_sql('fcg-peer'.$uid).','.fcg_sql($cpass).",1000,'',".($uid===2?0:1).",1,".fcg_sql(fcg_json($peer)).')');
    $db->query("UPDATE {$gtablepre}users SET icon='8',gender='f' WHERE uid=2");
    $s=dbuser(1,array('action'=>'status'));$b=dbuser(2,array('action'=>'status'));$x=dbuser(3,array('action'=>'status'));
    $s=dbuser(1,dbaction($s,'deck',array('deck'=>$s['deck'])));$b=dbuser(2,dbaction($b,'deck',array('deck'=>$b['deck'])));$s=dbuser(1,array('action'=>'status'));
    dbcheck(count($s['mirrors'])===1 && $s['mirrors'][0]['uid']===2 && !isset($s['mirrors'][0]['deck']),'mirror public state excludes self and hidden deck');
    $s=dbuser(1,dbaction($s,'mirror',array('uid'=>2)));dbcheck($s['match']['pvp'] && $s['match']['sides'][0]['hp']===100 && $s['match']['sides'][1]['hp']===100,'mirror uses PvP HP');
    dbcheck($s['match']['portraits']===array('img/m_1.gif','img/f_8.gif'),'mirror uses current account portraits');
    $s=dbuser(1,dbaction($s,'surrender',array('match'=>$s['match']['id'])));
    dbreject(1,dbaction($s,'invite',array('uid'=>3)),'cross-room invitation accepted');
    $s=dbuser(1,dbaction($s,'invite',array('uid'=>2)));$b=dbuser(2,array('action'=>'status'));dbcheck($b['invite']['from']===1,'invite delivered by heartbeat');
    $b=dbuser(2,dbaction($b,'decline',array('uid'=>1)));dbcheck(!$b['invite'],'decline removes invite');
    $s=dbuser(1,dbaction($s,'invite',array('uid'=>2)));$b=dbuser(2,array('action'=>'status'));$accept=dbaction($b,'accept',array('uid'=>1));$b=dbuser(2,$accept);$matchid=$b['match']['id'];
    $b=dbuser(2,$accept);$s=dbuser(1,array('action'=>'status'));
    dbcheck($s['match']['id']===$matchid && $b['match']['names'][0]==='fcg-peer2' && $s['match']['names'][0]==='fcg-test','shared match and reversed names');
    dbcheck($s['match']['portraits']===array('img/m_1.gif','img/f_8.gif') && $b['match']['portraits']===array('img/f_8.gif','img/m_1.gif') && !isset($s['match']['uids'],$b['match']['uids']),'live portraits follow both perspectives without exposing internal UID arrays');
    dbcheck(!isset($b['match']['sides'][1]['hand'],$b['match']['decks'],$b['match']['recent']) && count($b['match']['sides'][0]['hand'])===3,'peer secrets hidden after perspective reversal');
    dbcheck($db->num_rows($db->query("SELECT id FROM {$gtablepre}fcg_matches"))===1,'accept replay creates no second match');
    dbreject(1,dbaction($s,'start',array('opponent'=>0)),'live player escaped into solo game');
    dbreject(3,dbaction($x,'surrender',array('match'=>$matchid)),'third party controlled a duel');
    $room=$db->fetch_array($db->query("SELECT * FROM {$gtablepre}game WHERE groomid=0"));
    $row=$db->fetch_array($db->query("SELECT data FROM {$gtablepre}fcg_matches WHERE id=".fcg_sql($matchid)));$m=json_decode($row['data'],true);
    $m['sides'][1]['hand']=array(4,4,4,4,4,4);$m['sides'][1]['energy']['R']=99;fcg_live_write($m,$room,2000);$b=dbuser(2,array('action'=>'status'));
    $play=dbaction($b,'play',array('match'=>$matchid,'slot'=>0,'card'=>4,'turn'=>1));$b=dbuser(2,$play);$b=dbuser(2,$play);$s=dbuser(1,array('action'=>'status'));
    dbcheck($b['match']['sides'][0]['gen']['R']===2 && $s['match']['sides'][1]['gen']['R']===2 && count($b['match']['sides'][0]['hand'])===5,'B plays only B hand and duplicate play does not repeat');
    for($i=0;$i<5;$i++)$b=dbuser(2,dbaction($b,'play',array('match'=>$matchid,'slot'=>0,'card'=>4,'turn'=>1)));
    dbcheck($b['match']['deadline']===2014,'real database duel caps extension at four seconds');
    $b=dbuser(2,dbaction($b,'sticker',array('match'=>$matchid,'sticker'=>1)));$s=dbuser(1,array('action'=>'status'));dbcheck($s['match']['stickers'][1]['id']===1,'sticker follows sender perspective');
    $permanent=(array)$s['collection'];$b=dbuser(2,dbaction($b,'surrender',array('match'=>$matchid)));$s=dbuser(1,array('action'=>'status'));$embers=$s['embers'];$pvp=$s['pvp_wins'];$reward=$s['match']['reward'];
    dbcheck($s['match']['result']==='win' && $b['match']['result']==='loss' && $reward['temp_card']===151 && (array)$s['collection']===$permanent,'live win grants temporary actual-deck rare, no permanent card');
    $s=dbuser(1,array('action'=>'status'));dbcheck($s['embers']===$embers && $s['pvp_wins']===$pvp,'live reward survives reload exactly once');
    $s=dbuser(1,dbaction($s,'buy',array('item'=>40,'price'=>10)));dbcheck($s['match']['reward']===$reward,'shopping does not overwrite live result');
    // Same-second new matches must supersede the finished match; same opponent cannot farm embers.
    for($i=0;$i<2;$i++) {
        $s=dbuser(1,dbaction($s,'invite',array('uid'=>2)));$b=dbuser(2,array('action'=>'status'));$b=dbuser(2,dbaction($b,'accept',array('uid'=>1)));$s=dbuser(1,array('action'=>'status'));
        dbcheck($s['match']['id']===$b['match']['id'] && !$s['match']['result'],'new live match selected ahead of old result');
        $b=dbuser(2,dbaction($b,'surrender',array('match'=>$b['match']['id'])));$s=dbuser(1,array('action'=>'status'));
    }
    dbcheck($s['embers']===$embers && $s['pvp_wins']===$pvp+2 && count($s['reports'])===1,'repeat opponent only yields record and third-win room report');
    $deck=array_merge($s['deck'],array_fill(0,($s['collection']->{151} ?? 0)+1,151));$s=dbuser(1,dbaction($s,'deck',array('deck'=>$deck)));dbcheck(in_array(151,$s['deck'],true),'borrowed card saved to deck');
    $s=dbuser(1,array('action'=>'status'),88400);dbcheck(!isset($s['temp_cards']->{151}) && (array_count_values($s['deck'])[151] ?? 0)===($s['collection']->{151} ?? 0),'next-day reload expires only borrowed deck cards');
    $count=$db->fetch_array($db->query("SELECT COUNT(*) AS n FROM {$gtablepre}shop_items WHERE reward_type='fcg_pack' AND reward_payload LIKE '%vol2%'"));dbcheck((int)$count['n']===3,'exactly three Vol2 seed packs');
    // HARD request, persisted reward and replay use the normal account path.
    $s=dbuser(1,array('action'=>'status'),90000);
    dbreject(1,dbaction($s,'start',array('opponent'=>0,'hard'=>true)),'tutorial accepted hard',90000);
    dbreject(1,dbaction($s,'start',array('opponent'=>5,'hard'=>'1')),'non-boolean hard accepted',90000);
    $start=dbaction($s,'start',array('opponent'=>5,'hard'=>true));$s=dbuser(1,$start,90000);
    dbcheck($s['match']['hard'] && $s['match']['sides'][1]['hp']===115,'hard request uses upgraded opponent');
    $row=$db->fetch_array($db->query("SELECT * FROM {$gtablepre}users WHERE uid=1"));$data=fcg_load_account($row['fcgdata']);
    $data['match']['sides'][0]['atk']=200;$data['match']['ai_at']=PHP_INT_MAX;
    $db->query("UPDATE {$gtablepre}users SET fcgdata=".fcg_sql(fcg_json($data)).' WHERE uid=1');
    $s=dbuser(1,array('action'=>'status'),90010);$reward=$s['last_reward'];$hard_wins=$s['hard_wins']->{5};
    dbcheck($s['match']['result']==='win' && $hard_wins===1 && !fcg_cards()[$reward['card']]['unlimited'],'hard reward excludes unlimited energy');
    $s=dbuser(1,$start,90010);$s=dbuser(1,array('action'=>'status'),90010);
    dbcheck($s['hard_wins']->{5}===$hard_wins && $s['last_reward']===$reward,'hard start replay and refresh do not repeat reward');

    $s=dbuser(1,dbaction($s,'start',array('opponent'=>21)),90020);
    $evo=fcg_evo_load(90020);dbcheck(count($evo['state']['decks'])===5 && !isset($s['match']['telemetry']),'adaptive row initialized and telemetry private');
    $s=dbuser(1,dbaction($s,'surrender',array('match'=>$s['match']['id'])),90020);
    dbcheck(fcg_evo_load(90020)['raw']===$evo['raw'],'first-turn surrender not learned');
    $row=$db->fetch_array($db->query("SELECT * FROM {$gtablepre}users WHERE uid=1"));$data=fcg_load_account($row['fcgdata']);
    $data['match']=fcg_new_match($data['deck'],21,90020,false,$evo['state']);$m=&$data['match'];
    $m['ai_color']='W';$m['sides'][1]=fcg_side($evo['state']['decks']['W'],120);
    $m['sides'][0]['hand']=array(1,33);$m['sides'][0]['energy']['R']=99;fcg_play($m,0,0);fcg_play($m,0,0);
    $m['turn']=3;$m['result']='win';unset($m);
    $db->query("UPDATE {$gtablepre}users SET fcgdata=".fcg_sql(fcg_json($data)).' WHERE uid=1');
    $s=dbuser(1,array('action'=>'status'),90020);$learned=fcg_evo_load(90020);$reward=$s['last_reward'];
    dbcheck($learned['state']['deck_stats']['W']['matches']===1 && $learned['state']['card_stats'][33]['plr_played_wins']===1,'successful multi-table settlement learns player card');
    $s=dbuser(1,array('action'=>'status'),90020);
    dbcheck(fcg_evo_load(90020)['raw']===$learned['raw'] && $s['last_reward']===$reward,'reload repeats neither learning nor reward');

    // A rejected account/global compare must change NEITHER table, even on MyISAM.
    $old=$db->fetch_array($db->query("SELECT * FROM {$gtablepre}users WHERE uid=1"));$data=fcg_load_account($old['fcgdata']);
    $data['match']['id']=bin2hex(random_bytes(12));$data['match']['result']='loss';$data['match']['rewarded']=false;
    $db->query("UPDATE {$gtablepre}users SET fcgdata=".fcg_sql(fcg_json($data)).' WHERE uid=1');
    $old=$db->fetch_array($db->query("SELECT * FROM {$gtablepre}users WHERE uid=1"));$user=$old;$pending=fcg_load_account($old['fcgdata']);$context=null;
    fcg_settle($pending,$user,90020,'',$context);$pending['rev']++;
    $data['rev']++;$db->query("UPDATE {$gtablepre}users SET fcgdata=".fcg_sql(fcg_json($data)).' WHERE uid=1');
    dbcheck(!fcg_account_save($old,$user,$pending,$context,90020) && fcg_evo_load(90020)['raw']===$learned['raw'],'stale account cannot partly learn');
    $old=$db->fetch_array($db->query("SELECT * FROM {$gtablepre}users WHERE uid=1"));$user=$old;$pending=fcg_load_account($old['fcgdata']);$context=null;
    fcg_settle($pending,$user,90020,'',$context);$pending['rev']++;
    $external=$learned['state'];$external['gen']['G']++;
    $db->query("UPDATE {$gtablepre}fcg_mirrors SET data=".fcg_sql(fcg_json($external)).' WHERE uid=0');
    dbcheck(!fcg_account_save($old,$user,$pending,$context,90020),'stale shared state rejected');
    $row=$db->fetch_array($db->query("SELECT fcgdata FROM {$gtablepre}users WHERE uid=1"));dbcheck($row['fcgdata']===$old['fcgdata'],'stale shared state cannot partly mark reward');
    $pending=fcg_load_account($old['fcgdata']);$context=null;fcg_settle($pending,$user,90020,'',$context);$pending['rev']++;
    dbcheck(fcg_account_save($old,$user,$pending,$context,90020),'retry commits both rows');
    $learned=fcg_evo_load(90020);dbcheck($learned['state']['deck_stats']['W']['matches']===2 && $learned['state']['gen']['G']===2,'retry preserves intervening shared update and learns once');
    dbcheck(!fcg_account_save($old,$user,$pending,$context,90020) && fcg_evo_load(90020)['raw']===$learned['raw'],'replaying same combined save changes neither row');

    // Finish the twelfth sample and immediately open the next match in the same request.
    $state=$learned['state'];$state['deck_stats']['W']['matches']=11;
    $db->query("UPDATE {$gtablepre}fcg_mirrors SET data=".fcg_sql(fcg_json($state)).' WHERE uid=0');
    $s=dbuser(1,array('action'=>'status'),90020);$old=$db->fetch_array($db->query("SELECT * FROM {$gtablepre}users WHERE uid=1"));$data=fcg_load_account($old['fcgdata']);
    $data['match']['rewarded']=false;$db->query("UPDATE {$gtablepre}users SET fcgdata=".fcg_sql(fcg_json($data)).' WHERE uid=1');
    $s=dbuser(1,dbaction($s,'start',array('opponent'=>21)),90020);$learned=fcg_evo_load(90020);$color=$s['match']['ai_color'];
    dbcheck($learned['state']['gen']['W']===2 && $learned['state']['deck_stats']['W']['matches']===0 && $s['match']['ai_gen']===$learned['state']['gen'][$color],'new match uses pending evolution committed by same request');
    $s=dbuser(1,dbaction($s,'surrender',array('match'=>$s['match']['id'])),90020);

    // Expedition entry, every selection and final reward persist in the account's existing CAS write.
    $db->query("UPDATE {$gtablepre}users SET credits2=2000 WHERE uid=1");$s=dbuser(1,array('action'=>'status'),100000);
    $collection=(array)$s['collection'];$deck=$s['deck'];$wins=(array)$s['wins'];$hard_wins=(array)$s['hard_wins'];$embers=$s['embers'];
    $enter=dbdraft($s,'draft_enter');$s=dbuser(1,$enter,100000);$runid=$s['run']['id'];
    dbcheck($s['credits']===1750 && $s['run']['entry_cost']===250 && count($s['run']['pack'])===5 && !isset($s['run']['seed']),'entry debited with run, only first remainder public');
    $s=dbuser(1,$enter,100000);dbcheck($s['credits']===1750 && $s['run']['id']===$runid,'entry replay cannot charge or reroll');
    dbreject(1,dbdraft($s,'draft_enter'),'new token cannot open overlapping run',100000);
    dbreject(1,dbaction($s,'start',array('opponent'=>21)),'normal duel cannot replace run',100000);
    dbreject(1,dbaction($s,'mirror',array('uid'=>2)),'mirror cannot replace run',100000);
    $peer=dbuser(2,array('action'=>'status'),100000);
    dbreject(2,dbaction($peer,'invite',array('uid'=>1)),'cannot invite a player drafting',100000);
    for($i=0;$i<24;$i++) {
        $pick=dbdraft($s,'draft_pick',array('pick'=>$i,'slot'=>0,'card'=>$s['run']['pack'][0]));$s=dbuser(1,$pick,100000);
        if($i===0) {
            $after=$s['run'];$s=dbuser(1,$pick,100000);dbcheck($s['run']===$after,'same selection replay does not advance pack');
            $stale=$pick;$stale['token']=bin2hex(random_bytes(16));$stale['rev']=$s['rev'];dbreject(1,$stale,'stale pack index rejected with current account revision',100000);
        }
        $s=dbuser(1,array('action'=>'status'),100000);dbcheck(count($s['run']['picks'])===$i+1,'selection survives reload '.$i);
    }
    dbcheck(count($s['run']['deck'])===32 && $s['run']['stage']==='battle' && (array)$s['collection']===$collection && $s['deck']===$deck,'phantom draft deck persists and prepares battle directly');
    // Identical adjacent cards check that a stale removal cannot consume another copy.
    $row=$db->fetch_array($db->query("SELECT fcgdata FROM {$gtablepre}users WHERE uid=1"));$data=fcg_load_account($row['fcgdata']);
    $data['run']['deck'][0]=24;$data['run']['deck'][1]=24;
    $db->query("UPDATE {$gtablepre}users SET fcgdata=".fcg_sql(fcg_json($data)).' WHERE uid=1');$s=dbuser(1,array('action'=>'status'),100000);
    $s=dbuser(1,dbdraft($s,'draft_battle'),100000);$first=$s['match']['opponent'];
    $old=$db->fetch_array($db->query("SELECT * FROM {$gtablepre}users WHERE uid=1"));$data=fcg_load_account($old['fcgdata']);
    $data['match']['sides'][0]['hp']=55;$data['match']['sides'][0]['atk']=200;$data['match']['ai_at']=PHP_INT_MAX;
    $db->query("UPDATE {$gtablepre}users SET fcgdata=".fcg_sql(fcg_json($data)).' WHERE uid=1');
    $s=dbuser(1,array('action'=>'status'),100010);$victory=$s['run'];
    dbcheck($s['run']['wins']===1 && $s['run']['hp']===85 && $s['run']['mhp']===85 && $s['run']['node']['type']==='victory','real timer victory persists health growth and full recovery');
    foreach($s['run']['node']['opts'] as $id) dbcheck(!fcg_cards()[$id]['unlimited'],'persisted reinforcement excludes energy');
    dbcheck((array)$s['collection']===$collection && (array)$s['wins']===$wins && (array)$s['hard_wins']===$hard_wins && $s['embers']===$embers,'layer victory does not leak normal reward');
    $s=dbuser(1,array('action'=>'status'),100010);dbcheck($s['run']===$victory,'refresh cannot reroll reinforcement or add win');
    $remove=dbdraft($s,'draft_node',array('choice'=>'remove','slot'=>0,'card'=>24,'removed'=>0));$s=dbuser(1,$remove,100010);$once=$s['run'];
    $s=dbuser(1,$remove,100010);$s=dbuser(1,array('action'=>'status'),100010);
    dbcheck($s['run']===$once && $s['run']['node']['removed']===1 && count($s['run']['deck'])===31,'first removal replay and reload preserve exactly one removal');
    dbreject(1,dbdraft($s,'draft_node',array('choice'=>'remove','slot'=>0,'card'=>24,'removed'=>0)),'fresh token cannot replay removal of identical next copy',100010);
    dbreject(1,dbdraft($s,'draft_node',array('choice'=>'upgrade','slot'=>0,'card'=>24)),'cannot switch to upgrade after first removal',100010);
    $remove=dbdraft($s,'draft_node',array('choice'=>'remove','slot'=>0,'card'=>24,'removed'=>1));$s=dbuser(1,$remove,100010);$once=$s['run'];
    $s=dbuser(1,$remove,100010);
    dbcheck($s['run']===$once && $s['run']['stage']==='battle' && count($s['run']['deck'])===30,'second removal advances once without camp');
    dbreject(1,dbdraft($s,'draft_node',array('choice'=>'remove','slot'=>0,'card'=>$s['run']['deck'][0],'removed'=>1)),'third removal rejected after advancement',100010);
    $s=dbuser(1,dbdraft($s,'draft_battle'),100010);
    dbcheck($s['match']['sides'][0]['hp']==85 && $s['match']['opponent']!==$first && $s['match']['sides'][1]['hp']===85,'second layer inherits health, scales HP and uses another opponent');
    $s=dbuser(1,dbaction($s,'surrender',array('match'=>$s['match']['id'])),100010);
    dbcheck($s['run']['stage']==='over' && $s['run']['wins']===1,'surrender persists completed expedition');
    $reward_input=dbdraft($s,'draft_reward');$s=dbuser(1,$reward_input,100010);$reward=$s['last_reward'];$total=$s['embers'];$earned=(array)$s['collection'];
    dbcheck($s['run']===null && $s['match']===null && count($reward['cards'])===1 && $reward['total_cost']===250 && !fcg_cards()[$reward['cards'][0]['card']]['unlimited'],'final reward goes to collection and clears run');
    dbcheck($total===$embers+1+($reward['cards'][0]['overflow']?1:0),'final stamp and overflow persist together');
    $s=dbuser(1,$reward_input,100010);$s=dbuser(1,array('action'=>'status'),100010);
    dbcheck($s['last_reward']===$reward && $s['embers']===$total && (array)$s['collection']===$earned && $s['credits']===1750,'claim replay and reload preserve single reward');
    dbreject(1,dbdraft($s,'draft_reward',array('run'=>$runid)),'new claim token cannot grant again',100010);

    $s=dbuser(1,dbdraft($s,'draft_enter'),100020);$db->query("UPDATE {$gtablepre}users SET roomid=99 WHERE uid=1");
    $s=dbuser(1,dbdraft($s,'draft_abandon'),100020);$s=dbuser(1,dbdraft($s,'draft_reward'),100020);
    dbcheck($s['credits']===1500 && !$s['in_room'] && !$s['last_reward']['cards'] && $s['embers']===$total+1 && (array)$s['collection']===$earned,'zero-win abandonment outside room gives one stamp only');
    $db->query("UPDATE {$gtablepre}users SET roomid=0 WHERE uid=1");
    foreach(array(4,5) as $wins) {
        $s=dbuser(1,dbdraft($s,'draft_enter'),100025);$paid=$s['credits'];$before=(array)$s['collection'];$embers=$s['embers'];
        $row=$db->fetch_array($db->query("SELECT * FROM {$gtablepre}users WHERE uid=1"));$data=fcg_load_account($row['fcgdata']);
        $data['run']['stage']='over';$data['run']['wins']=$wins;$data['run']['ending']=$wins===5?'win':'loss';
        $data['run']['deck']=$wins===4?array(24,25,26,151):array(24,26);
        $db->query("UPDATE {$gtablepre}users SET fcgdata=".fcg_sql(fcg_json($data)).' WHERE uid=1');
        $s=dbuser(1,array('action'=>'status'),100025);$claim=dbdraft($s,'draft_reward');$s=dbuser(1,$claim,100025);$receipt=$s['last_reward'];
        $ids=array_column($receipt['cards'],'card');$overflow=count(array_filter($receipt['cards'],function($card){return $card['overflow'];}));
        dbcheck(count($ids)===4 && $receipt['total_cost']===250 && $receipt['embers']===$wins && $s['embers']===$embers+$wins+$overflow,'new tier and receipt commit together');
        dbcheck(fcg_cards()[$ids[0]]['rare'] && ($wins!==4 || $ids[0]===151),'rare guarantee prioritizes final deck');
        if($wins===5) dbcheck(fcg_cards()[$ids[1]]['upgraded'] && !fcg_cards()[$ids[1]]['rare'] && !fcg_cards()[$ids[1]]['unlimited'],'missing upgrade uses non-energy global fallback');
        $expected=$before;foreach($receipt['cards'] as $card) if(!$card['overflow']) $expected[$card['card']]=($expected[$card['card']] ?? 0)+1;
        dbcheck((array)$s['collection']===$expected && $s['run']===null && $s['credits']===$paid,'four permanent grants and cleared run persist without extra debit');
        $s=dbuser(1,$claim,100025);$s=dbuser(1,array('action'=>'status'),100025);
        dbcheck($s['last_reward']===$receipt && (array)$s['collection']===$expected && $s['embers']===$embers+$wins+$overflow,'guarantee replay cannot reroll or grant again');
    }
    $old=$db->fetch_array($db->query("SELECT * FROM {$gtablepre}users WHERE uid=1"));$user=$old;$data=fcg_load_account($old['fcgdata']);
    fcg_action($data,$user,array('action'=>'draft_enter'),100030,true);$data['rev']++;
    $db->query("UPDATE {$gtablepre}users SET credits2=249 WHERE uid=1");
    dbcheck(!fcg_account_save($old,$user,$data),'concurrent spend rejects entry');
    $row=$db->fetch_array($db->query("SELECT fcgdata,credits2 FROM {$gtablepre}users WHERE uid=1"));
    dbcheck($row['fcgdata']===$old['fcgdata'] && (int)$row['credits2']===249,'rejected entry cannot leave unpaid run or partial debit');
    $db->query("UPDATE {$gtablepre}users SET credits2=1000 WHERE uid=1");

    // Publishing is bounded even after many active accounts; reserved row is never a mirror.
    $user=$db->fetch_array($db->query("SELECT * FROM {$gtablepre}users WHERE uid=2"));$data=fcg_load_account($user['fcgdata']);
    for($i=10;$i<35;$i++) { $user['uid']=$i;fcg_publish_mirror($data,$user,'0:1:1000',90000+$i); }
    $count=$db->fetch_array($db->query("SELECT COUNT(*) AS n FROM {$gtablepre}fcg_mirrors WHERE uid>0"));dbcheck((int)$count['n']===20,'mirror retention is bounded');
    dbcheck(fcg_evo_load(90100)['raw']===$learned['raw'],'mirror cleanup preserves adaptive row');
    $db->query("INSERT INTO {$gtablepre}users (uid,username,roomid,groupid) VALUES (0,'reserved-test',0,1)");
    dbcheck(!isset(fcg_mirrors(1,90100)[0]),'reserved state excluded from mirror listing');
    // Upgrade a previously configured catalog without changing an administrator's pack.
    $db->query("DROP TABLE {$gtablepre}fcg_mirrors");
    $db->query("DELETE FROM {$gtablepre}shop_items WHERE item_id IN (41,42)");
    $db->query("UPDATE {$gtablepre}shop_items SET price_credits2=17,name='custom pack' WHERE item_id=40");
    $db->query("UPDATE {$gtablepre}shop_items SET name=".fcg_sql('“共犯”')." WHERE item_id=24");
    fcg_ensure_social_storage();fcg_ensure_social_storage();$catalog=fcg_catalog();
    $count=$db->fetch_array($db->query("SELECT COUNT(*) AS n FROM {$gtablepre}shop_items WHERE reward_payload LIKE '%vol2%'"));
    dbcheck((int)$count['n']===3 && (int)$catalog[40]['price_credits2']===17 && $catalog[40]['name']==='custom pack','upgrade fills missing packs once and preserves customization');
    dbcheck($catalog[24]['name']==='阿卡夏的共犯','upgrade renames the original title seed');
    // Old catalogs receive one slot product, preserving ID 6 and administrator prices.
    $db->query("DELETE FROM {$gtablepre}shop_items WHERE reward_type='custom' AND reward_payload LIKE '%deck_slot%'");
    fcg_ensure_social_storage();fcg_ensure_social_storage();$slot_items=array();
    foreach(fcg_catalog() as $item) if($item['reward_type']==='custom' && strpos($item['reward_payload'],'deck_slot')!==false)$slot_items[]=$item;
    dbcheck(count($slot_items)===1 && (int)$slot_items[0]['price_credits2']===500 && fcg_catalog()[6]['reward_type']==='fcg_pack','existing catalog gains one slot product without overwriting green pack');
    $slot=$slot_items[0];$db->query("UPDATE {$gtablepre}shop_items SET price_credits2=501 WHERE item_id=".$slot['item_id']);fcg_ensure_social_storage();
    dbcheck((int)fcg_catalog()[$slot['item_id']]['price_credits2']===501,'slot migration preserves admin price');
    $db->query("UPDATE {$gtablepre}shop_items SET price_credits2=500 WHERE item_id=".$slot['item_id']);
    $db->query("INSERT INTO {$gtablepre}users (uid,username,password,credits2,nicksrev,roomid,groupid,fcgdata) VALUES (4,'fcg-peer4',".fcg_sql($cpass).",5000,'',0,1,NULL)");
    $s=dbuser(4,array('action'=>'status'),110000);$s=dbuser(4,dbaction($s,'quiz',array('answers'=>array())),110000);
    $s=dbuser(4,dbaction($s,'starter_claim',array('key'=>'ORIG')),110000);dbcheck(!$s['starter_claimed'] && $s['credits']===5000,'skip and original preserve free grant in DB');
    $input=dbaction($s,'starter_claim',array('key'=>'W'));$s=dbuser(4,$input,110000);$collection=$s['collection'];$s=dbuser(4,$input,110000);
    dbcheck($s['starter_claimed'] && $s['credits']===5000 && $s['collection']==$collection,'free color replay grants once');
    $input=dbaction($s,'starter_claim',array('key'=>'R'));$s=dbuser(4,$input,110000);$collection=$s['collection'];$s=dbuser(4,$input,110000);
    dbcheck($s['credits']===4900 && $s['collection']==$collection,'paid color replay debits once');
    $s=dbuser(4,dbaction($s,'starter_claim',array('key'=>'R')),110000);dbcheck($s['credits']===4900 && $s['collection']==$collection,'owned starter new request does not grant again');
    $input=dbaction($s,'buy',array('item'=>(int)$slot['item_id'],'price'=>500));$s=dbuser(4,$input,110000);$s=dbuser(4,$input,110000);
    dbcheck($s['deck_slots']===2 && $s['decks'][1]['cards']===array() && $s['credits']===4400,'slot delivery and debit survive replay');
    dbreject(4,dbaction($s,'deck_slot',array('slot'=>1,'op'=>'activate')),'empty slot activation accepted',110000);
    $s=dbuser(4,dbaction($s,'deck',array('slot'=>1,'deck'=>array_reverse($s['deck']))),110000);
    $s=dbuser(4,dbaction($s,'deck_slot',array('slot'=>1,'op'=>'rename','name'=>'测试卡盒')),110000);$s=dbuser(4,array('action'=>'status'),110000);
    dbcheck($s['active_deck']===1 && $s['deck']===$s['decks'][1]['cards'] && $s['decks'][1]['name']==='测试卡盒','slot save name and activation survive reload');
    $s=dbuser(4,dbaction($s,'set_options',array('turn_mode'=>'think')),110000);
    $s=dbuser(4,dbaction($s,'start',array('opponent'=>1)),110000);dbcheck($s['match']['turn_mode']==='think','match inherits saved turn mode');
    $input=dbaction($s,'end_turn',array('match'=>$s['match']['id'],'turn'=>$s['match']['turn']));$s=dbuser(4,$input,110000);$turn=$s['match']['turn'];$s=dbuser(4,$input,110000);
    dbcheck($turn===2 && $s['match']['turn']===2,'end-turn replay settles once');
    dbreject(4,dbaction($s,'end_turn',array('match'=>$s['match']['id'],'turn'=>1)),'stale end turn accepted',110000);
    $s=dbuser(4,dbaction($s,'surrender',array('match'=>$s['match']['id'])),110000);
    $s=dbuser(4,array('action'=>'status'),110000);$b=dbuser(2,array('action'=>'status'),110000);
    $b=dbuser(2,dbaction($b,'set_options',array('turn_mode'=>'instinct')),110000);
    $s=dbuser(4,dbaction($s,'invite',array('uid'=>2)),110000);$b=dbuser(2,array('action'=>'status'),110000);$b=dbuser(2,dbaction($b,'accept',array('uid'=>4)),110000);$s=dbuser(4,array('action'=>'status'),110000);
    dbcheck($s['match']['turn_mode']==='think' && $b['match']['turn_mode']==='instinct','live views inherit separate player modes');
    $input=dbaction($s,'end_turn',array('match'=>$s['match']['id'],'turn'=>1));$s=dbuser(4,$input,110000);$s=dbuser(4,$input,110000);$b=dbuser(2,array('action'=>'status'),110000);
    dbcheck($s['match']['my_turn_done'] && $b['match']['opp_turn_done'] && $s['match']['turn']===1,'live end and replay wait for peer');
    dbreject(4,dbaction($s,'discard',array('match'=>$s['match']['id'],'turn'=>1,'slot'=>0,'card'=>$s['match']['sides'][0]['hand'][0])),'ready player discarded',110000);
    $b=dbuser(2,dbaction($b,'end_turn',array('match'=>$b['match']['id'],'turn'=>1)),110000);$s=dbuser(4,array('action'=>'status'),110000);
    dbcheck($s['match']['turn']===2 && $b['match']['turn']===2 && !$s['match']['my_turn_done'],'second player end advances shared round once');
    echo "FCG database: migration, purchases, PvP, HARD, adaptive learning, draft expedition, privacy, replay, atomic writes, starters, deck slots and independent timers passed on MyISAM.\n";
} finally { foreach(array_reverse($created) as $table) $db->query('DROP TABLE `'.$table.'`','SILENT'); }
