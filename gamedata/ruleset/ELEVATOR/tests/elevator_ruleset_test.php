<?php
if (!defined('IN_GAME')) exit('Access Denied');
if (!defined('GAME_ROOT')) define('GAME_ROOT',dirname(__DIR__,4).DIRECTORY_SEPARATOR);
// 独立内存数据库，不连接站点；SQL入账和网页联调另见改动记录。
class ElevatorTestResult { public $rows; public function __construct($rows=array()) { $this->rows=array_values($rows); } }
class ElevatorTestDb {
    public $players=array(),$floors=array(),$ground=array(),$id=0;
    public function query($sql) {
        if (strpos($sql,'SELECT username,depth,depth_time,finished_at FROM test_elevator_results')===0) return new ElevatorTestResult();
        if (preg_match('/^SELECT .* FROM test_elevator_floor(?: WHERE floor=(-?\d+))?/',$sql,$m)) {
            $rows=array(); foreach ($this->floors as $f=>$d) if (!isset($m[1]) || $f==(int)$m[1]) $rows[]=array('floor'=>$f,'data'=>json_encode($d));
            return new ElevatorTestResult($rows);
        }
        if (preg_match("/^REPLACE INTO test_elevator_floor .* VALUES \((-?\d+),X'([a-f0-9]+)'\)/s",$sql,$m)) { $this->floors[(int)$m[1]]=json_decode(hex2bin($m[2]),true); return true; }
        if (strpos($sql,'SELECT * FROM test_players WHERE type=')===0) {
            preg_match('/type=(\d+)/',$sql,$m); return new ElevatorTestResult(array_filter($this->players,function($p)use($m){return (int)$p['type']===(int)$m[1];}));
        }
        if (preg_match('/DELETE FROM test_players WHERE pid=(\d+)/',$sql,$m)) { unset($this->players[(int)$m[1]]); return true; }
        if (preg_match('/DELETE FROM test_elevator_floor WHERE floor=(-?\d+)/',$sql,$m)) { unset($this->floors[(int)$m[1]]); return true; }
        if (strpos($sql,'DELETE FROM test_')===0 && strpos($sql,'BETWEEN')!==false) return true;
        throw new RuntimeException('Unimplemented test query: '.$sql);
    }
    public function fetch_array($r) { return array_shift($r->rows); }
    public function num_rows($r) { return count($r->rows); }
    public function array_insert($table,$row) {
        if ($table==='test_players') { $row['pid']=++$this->id; $this->players[$this->id]=$row; }
        elseif ($table==='test_mapitem') $this->ground[]=$row;
        else throw new RuntimeException('Unexpected table '.$table);
    }
    public function insert_id() { return $this->id; }
}
function get_clbpara($v) { return is_array($v)?$v:(json_decode($v?:'[]',true)?:array()); }
function get_itmpara($v) { return get_clbpara($v); }
function config($name,$cfg=1) { return GAME_ROOT.'gamedata/cache/'.$name.'_1.php'; }
function create_dummy_playerdata() {
    $p=array(); $sql=file_get_contents(GAME_ROOT.'gamedata/sql/players.sql');
    preg_match_all('/^\s+`?(\w+)`?\s+(\w+)/m',$sql,$matches,PREG_SET_ORDER);
    foreach ($matches as $m) $p[$m[1]]=strpos(strtolower($m[2]),'int')!==false?0:'';
    $p['clbpara']=array(); return $p;
}
function player_format_with_db_structure($p) { foreach ($p as $k=>$v) if (is_array($v)) $p[$k]=json_encode($v,JSON_UNESCAPED_UNICODE); return $p; }
function player_save($p) { $GLOBALS['db']->players[$p['pid']]=player_format_with_db_structure($p); $GLOBALS['db']->id=max($GLOBALS['db']->id,$p['pid']); }
function fetch_playerdata_by_pid($id) { $p=$GLOBALS['db']->players[$id]??null; if ($p) $p['clbpara']=get_clbpara($p['clbpara']); return $p; }
function save_gameinfo() {}
function et_assert($value,$message) { if (!$value) throw new RuntimeException('FAIL: '.$message); $GLOBALS['et_count']++; }
function et_reject($fn,$message) { try { $fn(); } catch (RuntimeException $ex) { et_assert(true,$message); return; } throw new RuntimeException('FAIL accepted: '.$message); }
function et_player($id=1,$floor=1,$at=1000) {
    $p=create_dummy_playerdata(); $p=array_merge($p,array('pid'=>$id,'type'=>0,'name'=>'乘客'.$id,'hp'=>400,'mhp'=>400,'sp'=>400,'msp'=>400,'money'=>500,'att'=>100,'def'=>100));
    $p['clbpara']=array('elevator'=>elevator_player_state($floor,$at),'skill'=>array(),'BGMBrand'=>''); elevator_entrance($p,$floor,$at,true);
    return $p;
}
function et_room($floor=1) {
    $GLOBALS['db']=new ElevatorTestDb(); $GLOBALS['gamevars']=array('elevator'=>elevator_room(1000));
    $e=&$GLOBALS['gamevars']['elevator']; $e['floor']=$floor; $e['joined']=2; $e['last_event_time']=100000; rr_create_recipes(10);
    $GLOBALS['now']=1000; return $e;
}
$tablepre=$gtablepre='test_'; $now=1000; $nosta='∞'; $log=''; $gamecfg=1; $et_count=0;
require GAME_ROOT.'gamedata/cache/resources_1.php';
require GAME_ROOT.'gamedata/ruleset/ELEVATOR/cache/clubskills_1.php';
require dirname(__DIR__).'/include/ruleset.func.php';
mt_srand(2701);

et_room(); $e=&$gamevars['elevator']; $p=et_player(); $other=et_player(2); $players=array(1=>$p,2=>$other);
et_assert(count(elevator_maps())===64 && count(elevator_objects())===26 && count(elevator_catalog())===131,'all source content registered');
foreach (elevator_catalog() as $id=>$item) et_assert(isset(elevator_maps()[$item['map']]),'item has a real source map: '.$id);
$ids=array(9000=>true); for($f=-30;$f<=30;$f++) if ($f) for($i=0;$i<=5;$i++) { $id=elevator_pls($f,$i); et_assert(!isset($ids[$id]),'unique runtime map'); $ids[$id]=true; }
for ($i=0;$i<200;$i++) {
    $f=$i%2?($i+1):(-$i-1); $plan=elevator_blueprint($f);
    if ($f!==-8) et_assert(count($plan['tpls'])>=2 && count($plan['tpls'])<=5 && count(array_unique($plan['tpls']))===count($plan['tpls']),'2-5 unique points');
    et_assert(count($plan['objects'])<=2,'objects bounded');
    $npc=rr_generate_npc($i+1,20,10,15);
    foreach ($npc['clbpara']['skill'] as $sk) et_assert(isset($cskills[$sk]),'native skill exists: '.$sk);
    foreach (array('wepsk','arbsk','arhsk','arask','arfsk','artsk') as $k) if ($npc[$k]!=='') foreach (str_split($npc[$k]) as $trait) et_assert(isset($itemspkinfo[$trait]) && $trait!=='X','legal native equipment trait: '.$trait);
    $r=rr_generate_recipe(); et_assert($r['extra']['materials']>=2 && $r['extra']['materials']<=5,'recipe material count');
}
et_assert(elevator_rank(40)===elevator_rank(7) && elevator_rank(-40)>20,'upward combat caps, downward scales');
$p=et_player(); for ($i=8;$i<40;$i++) elevator_entrance($p,$i,1000,false);
et_assert(count($p['clbpara']['elevator']['floors'])<=9,'unexplored endless entrances do not grow personal history');
elevator_entrance($p,7,1000); elevator_entrance($p,1,1000);
et_assert($p['clbpara']['elevator']['endpoint'] && $p['clbpara']['elevator']['attic'],'endpoint honors persist after leaving endpoint');
et_assert(strpos(rr_blueprint_item(301,-5)['itm'],'错误配方')===0,'downward blueprint skin');
et_assert(count(rr_create_recipes())===30,'standalone recipe default30'); rr_create_recipes(10);

elevator_materialize_floor($e,1,1000); $before=count($db->ground); elevator_materialize_floor($e,1,1001); et_assert(count($db->ground)===$before,'floor generated exactly once');
$e['plan'][1]=array('tpls'=>array(101,102),'objects'=>array());
$db->floors[1]=array('floor'=>1,'tpls'=>array(101,102),'objects'=>array());
elevator_request($e,3,'call',1,1000); elevator_request($e,5,'cabin',2,1000); elevator_tick($e,$players,1000);
et_assert($e['route'][0]['floor']===5 && $e['state']==='doors','cabin priority and departure open');
elevator_request($e,7,'cabin',2,1001); et_assert($e['route'][0]['floor']===5,'committed destination immutable');
$players[1]['clbpara']['elevator']['board_req']=array('ts'=>1000); elevator_board($players[1],$e,1000);
et_assert($players[1]['clbpara']['elevator']['in'] && !$players[2]['clbpara']['elevator']['in'],'registered/explicit boarding only');
elevator_tick($e,$players,1008); et_assert($e['state']==='closing','8sec open');
elevator_tick($e,$players,1013); et_assert($e['state']==='travel' && $e['travel_end']===1025,'5sec close, distance times3');
elevator_tick($e,$players,1025); et_assert($e['floor']===5 && $e['state']==='doors','arrival opens before next route');
elevator_tick($e,$players,1038); et_assert($e['route'][0]['floor']===7 && $e['state']==='doors','old arrival cleared then FIFO cabin');
$order=elevator_room(1000); elevator_request($order,3,'call',1,1000); elevator_request($order,5,'cabin',2,1001); elevator_request($order,3,'cabin',2,1002); elevator_tick($order,$players,1002);
et_assert($order['route'][0]['floor']===5,'promoted outside call does not jump ahead of earlier cabin button');

$p=et_player(); $p['clbpara']['elevator']['protected']=false;
elevator_budget_tick($p,1000,2000,$e); et_assert($p['clbpara']['elevator']['budget']===138,'disconnect only presence lease, no catchup');
$p['clbpara']['elevator']['last_seen']=2000; elevator_budget_tick($p,2000,2004,$e); et_assert($p['clbpara']['elevator']['budget']===134,'reconnect resumes current time');
$p['clbpara']['elevator']['protected']=true; $p['clbpara']['elevator']['board_req']=array('ts'=>2004); elevator_budget_tick($p,2004,2010,$e); et_assert($p['clbpara']['elevator']['budget']===134,'waiting protection pauses');
$p=et_player(); et_assert(elevator_death($p,1001,$e) && $p['hp']===120 && $p['clbpara']['elevator']['revive']===0,'one revival30percent');
et_assert(!elevator_death($p,1002,$e) && $p['clbpara']['elevator']['result']==='dead','second death settles');
$state=$p; elevator_death($p,1003,$e); et_assert($state===$p,'repeat death idempotent');

et_room(7); $e=&$gamevars['elevator']; $a=et_player(1,7); $b=et_player(2,7); $players=array(1=>$a,2=>$b);
elevator_action($e,$a,'elevator_vote',array('value'=>'1'),1000); $players[1]=$a; elevator_tick($e,$players,1120);
et_assert($e['phase']==='endless' && elevator_active($players[1]) && $players[2]['clbpara']['elevator']['result']==='escape','vote yes stays, no answer escapes');
et_assert(elevator_reward($players[2]['clbpara']['elevator'])===10,'vote escape10');
et_reject(function()use(&$e,&$players){elevator_action($e,$players[1],'elevator_escape',array(),1121);},'no ordinary endless escape');
$p=et_player(1,-8); for($i=0;$i<3;$i++) elevator_fireseed($e,$p,'0',1200+$i);
et_assert($p['clbpara']['elevator']['riddle_step']===3 && empty($p['clbpara']['elevator']['perfect_riddle']),'wrong riddles never trap player');
$e['phase']='finite'; elevator_action($e,$p,'elevator_escape',array(),1204); et_assert($p['clbpara']['elevator']['result']==='escape','escape after wrong answers');
$p=et_player(1,-8); for($i=0;$i<3;$i++) elevator_fireseed($e,$p,'1',1200+$i);
et_assert($p['clbpara']['elevator']['perfect_riddle'] && $p['clbpara']['elevator']['dictionary'],'three correct grants dictionary');

et_room(); $e=&$gamevars['elevator']; $p=et_player();
$recipe=rr_generate_recipe('WP'); $recipe['stuff1']=array('kind'=>'W','count'=>1); $recipe['stuff2']=array('name'=>'猫毛球','count'=>1); unset($recipe['stuff3'],$recipe['stuff4'],$recipe['stuff5']); $recipe['extra']=array('learnable'=>true,'consume_recipe'=>false); $gamevars['rr_recipes'][301]=$recipe;
elevator_set_slot($p,1,rr_blueprint_item(301)); elevator_set_slot($p,2,array('itm'=>'试用锤','itmk'=>'WP','itme'=>30,'itms'=>60)); elevator_set_slot($p,3,array('itm'=>'猫毛球','itmk'=>'X','itme'=>1,'itms'=>2));
$original=$p; $bad=hash('sha256','stale'); et_reject(function()use(&$p,$e,$bad){rr_craft($p,301,1,$bad,$e);},'stale blueprint rejected'); et_assert($p===$original,'failed craft consumes nothing');
rr_craft($p,301,1,elevator_slot_token($p,1),$e); et_assert($p['itmk1']==='R' && !empty($p['clbpara']['rr_learned'][301]) && $p['itms3']===1 && $p['itm2']!=='试用锤','learn, keep blueprint, whole weapon consumed');
$q=et_player(); $once=$recipe; $once['extra']=array('learnable'=>false,'consume_recipe'=>true); $gamevars['rr_recipes'][302]=$once;
elevator_set_slot($q,1,rr_blueprint_item(302)); elevator_set_slot($q,2,array('itm'=>'素材武器','itmk'=>'WP','itme'=>30,'itms'=>99)); elevator_set_slot($q,3,array('itm'=>'猫毛球','itmk'=>'WC','itme'=>1,'itms'=>2));
rr_craft($q,302,1,elevator_slot_token($q,1),$e); et_assert($q['itmk1']!=='R' && empty($q['clbpara']['rr_learned'][302]) && $q['itms3']===1,'consumable recipe consumes blueprint and exact materials');
$q=et_player(); elevator_set_slot($q,1,array('itm'=>'撬棍[+5]','itmk'=>'WP','itme'=>100,'itms'=>50)); elevator_set_slot($q,2,array('itm'=>'普通撬棍','itmk'=>'WP','itme'=>30,'itms'=>50));
$requirements=array('stuff1'=>array('kind'=>'W','count'=>1),'stuff2'=>array('kind'=>'W','contains'=>'[+5]','count'=>1));
et_assert(rr_materials($q,$requirements)===array(1=>1,2=>1),'upgraded material matched before wildcard');
elevator_set_slot($p,4,elevator_relic(5,$e['round'])); elevator_open_relic($p,4,$e); $token=elevator_slot_token($p,4); $options=elevator_slot($p,4)['itmpara']['rr_choices'];
elevator_open_relic($p,4,$e); et_assert(count($options)===6 && elevator_slot_token($p,4)===$token,'relic six stable options'); elevator_choose_relic($p,4,0,$token,$e);
et_reject(function()use(&$p,$e,$token){elevator_choose_relic($p,4,0,$token,$e);},'relic replay rejected');
$_POST=array('el_seq'=>(string)$p['clbpara']['elevator']['seq'],'el_csrf'=>$p['clbpara']['elevator']['csrf']); elevator_validate_request($p);
et_reject(function()use(&$p){elevator_validate_request($p);},'duplicate sequence rejected');

et_room(); $e=&$gamevars['elevator']; $a=et_player(); $b=et_player(2); $e['state']='doors'; elevator_board($a,$e,1000); elevator_board($b,$e,1000);
player_save($a); player_save($b); elevator_set_slot($a,1,array('itm'=>'馈赠物','itmk'=>'HH','itme'=>20,'itms'=>3,'itmpara'=>array('kept'=>1)));
elevator_trade_offer($a,1,2,30,elevator_slot_token($a,1),$e,1000); player_save($a); $offer=$a['clbpara']['elevator']['trade'];
elevator_trade_accept($b,1,$offer['id'],$e,1001); player_save($b); $a=fetch_playerdata_by_pid(1);
et_assert($b['money']===470 && $a['money']===530 && empty($a['clbpara']['elevator']['trade']) && elevator_slot($b,1)['itmpara']['kept']===1,'trade cash and full item persisted');
et_reject(function()use(&$b,$offer,$e){elevator_trade_accept($b,1,$offer['id'],$e,1002);},'trade replay rejected');
$token=elevator_slot_token($b,1); elevator_store($b,1,$token,$e); et_assert(count($b['clbpara']['elevator']['box'])===1 && !$b['itms1'],'storage full item removed from pack');
$box=$b['clbpara']['elevator']['box'][0]; elevator_withdraw($b,0,hash('sha256',json_encode($box,JSON_UNESCAPED_UNICODE)),$e); et_assert(!$b['clbpara']['elevator']['box'] && $b['itms1']===3,'withdraw persisted item metadata');

foreach (array_keys(elevator_intrusion_names()) as $type) {
    et_room(); $e=&$gamevars['elevator']; $a=et_player(); $e['state']='doors'; elevator_board($a,$e,1000); $players=array(1=>$a);
    et_assert(elevator_intrusion_arbiter($e,$players,1000,$type) && $e['state']==='hold','event enters hold: '.$type);
    et_assert(!elevator_request($e,3,'cabin',1,1001),'hold refuses route');
    if ($type==='rule') elevator_intrusion_choice($e,$players[1],'volunteer',$e['event']['id'],1001);
    if ($type==='fans') elevator_intrusion_choice($e,$players[1],'pose',$e['event']['id'],1001);
    if ($type==='watcher') elevator_intrusion_choice($e,$players[1],'away',$e['event']['id'],1001);
    if ($type==='overflow') elevator_intrusion_choice($e,$players[1],'take',$e['event']['id'],1001);
    elevator_intrusion_tick($e,$players,1020);
    if ($type==='fragment') for ($i=0;$i<3;$i++) elevator_intrusion_choice($e,$players[1],'hit',$e['event']['id'],1021+$i);
    if ($e['event']) elevator_intrusion_tick($e,$players,$e['event']['deadline']);
    et_assert($e['event']===null && $e['state']==='doors','event resolves and opens: '.$type);
    if ($type==='echo') et_assert($players[1]['hp']===280 && $e['last_event_time']===1080,'combat timeout nonfatal30percent, cooldown');
}

et_room(); $e=&$gamevars['elevator']; $p=et_player(); $players=array(1=>$p); elevator_tick($e,$players,4600); et_assert($e['state']==='ended' && !elevator_active($players[1]),'60min hard deadline');
et_room(); $e=&$gamevars['elevator']; $p=et_player(); $p['pls']=elevator_pls(1,1); $db->floors[1]=array('floor'=>1,'tpls'=>array(101,102),'objects'=>array());
elevator_record_exploration($p,1001); $p['pls']=elevator_pls(1,2); elevator_record_exploration($p,1003); $count=$p; elevator_record_exploration($p,1004);
et_assert($p===$count && $p['clbpara']['elevator']['depth_time']===3,'distinct points, reward once, elapsed ranking time');
et_assert(!elevator_can_fight($p,et_player(2)),'no PvP');
$intruder=rr_generate_npc(10); $intruder['pid']=99; $intruder['type']=98; $intruder['pls']=9000; $intruder['clbpara']['hostile']=true;
$safe=et_player(1,-8); $e['floor']=-8; $safe['pls']=9000; $safe['clbpara']['elevator']['in']=true;
et_assert(!elevator_can_fight($safe,$intruder),'hostile merchant cannot break peaceful grey floor'); $e['floor']=1;
player_save($p); $html=elevator_panel($p,$e,1004); et_assert(strpos($html,'data-el-command')!==false && strpos($html,'el-csrf')!==false,'panel contains real actions and request tokens');
$saved=player_format_with_db_structure($p); $html=elevator_panel($saved,$e,1004); et_assert(strpos($html,'data-el-csrf="'.$p['clbpara']['elevator']['csrf'].'"')!==false,'panel accepts native JSON save result');
foreach (elevator_objects() as $id=>$object) {
    et_room(); $e=&$gamevars['elevator']; $p=et_player(); $p['pls']=elevator_pls(1,1);
    $db->floors[1]=array('floor'=>1,'tpls'=>array(101,102),'objects'=>array(array('id'=>$id,'index'=>1)));
    $role=$object[3];
    if (in_array($role,array('turret','burnt','soldier','tap','mirror','error','half'),true)) {
        $pid=elevator_object_spawn($id,$p,1,1000); $npc=fetch_playerdata_by_pid($pid);
        et_assert($npc['clbpara']['elevator_npc']['object']===$id,'special combat object spawns: '.$id);
        if ($role==='half') { $npc['hp']=0; et_assert(ruleset_combat_result_hook($p,$npc,1)===0 && $npc['hp']===1 && $npc['pls']===0,'half NPC withdraws before death even on oneshot'); }
        if ($role==='mirror') { $npc['hp']=0; ruleset_player_kill_hook($p,$npc,1); et_assert(!$npc['weps'] && $npc['itms1'],'mirror drops fragment, no equipment cloning'); }
        continue;
    }
    if ($role==='armory') continue; // 共享武器库在扩展回归中单独验证。
    $choice='talk'; $answer='';
    if ($role==='cat' || $role==='catloot') { elevator_set_slot($p,1,elevator_item(elevator_item_id('猫粮罐头'),$e['round'])); $choice='feed'; }
    if ($role==='projection') $choice='touch';
    if ($role==='scavenger') { elevator_set_slot($p,1,array('itm'=>'食物','itmk'=>'HH','itme'=>30,'itms'=>2)); $choice='trade'; }
    if ($role==='forest') { $choice='answer'; $answer=elevator_cipher('life game'); }
    if ($role==='data') $choice='unsure';
    if ($role==='maple') elevator_set_slot($p,1,elevator_item(elevator_item_id('「M.D.」书架的旧书'),$e['round']));
    elevator_interact($e,$p,$id,$choice,$answer,1000);
    if (in_array($role,array('student','remnant'),true)) { elevator_interact($e,$p,$id,$choice,$answer,1001); elevator_interact($e,$p,$id,$choice,$answer,1002); }
    if ($role==='watch') { et_reject(function()use(&$e,&$p,$id){elevator_interact($e,$p,$id,'listen','',1010);},'watch requires30seconds'); elevator_interact($e,$p,$id,'listen','',1030); }
    et_assert(!empty($p['clbpara']['elevator']['floors'][1]['objects'][$id]['done']),'interaction completes: '.$id);
    $p['pls']=elevator_pls(1,1); $before=$p; elevator_interact($e,$p,$id,$choice,$answer,1035);
    foreach (array('money','hp','itm1','itms1','itm2','itms2') as $key) et_assert($p[$key]===$before[$key],'interaction reward cannot repeat: '.$id);
}
et_room(); $e=&$gamevars['elevator']; $p=et_player();
for ($f=1;$f<=3;$f++) {
    elevator_entrance($p,$f,1000); $p['pls']=elevator_pls($f,1);
    $db->floors[$f]=array('floor'=>$f,'tpls'=>array(301,302),'objects'=>array(array('id'=>301,'index'=>1)));
    elevator_interact($e,$p,301,'talk','',1000);
}
et_assert(count($p['clbpara']['elevator']['fragments'])===3 && $p['clbpara']['elevator']['dictionary'] && $p['itm1']==='种火语字典残页','three reachable positive-floor fragments give a real dictionary');
require __DIR__.'/elevator_extensions_test.php';
echo 'ELEVATOR: '.$et_count." assertions passed.\n";
