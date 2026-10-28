<?php
if (!defined('IN_GAME')) exit('Access Denied');
if (!defined('GAME_ROOT')) define('GAME_ROOT',dirname(__DIR__,4).DIRECTORY_SEPARATOR);
if (!defined('CURSCRIPT')) define('CURSCRIPT','game');
// 独立内存数据库：使用正式物品、任务、战斗公式；不连接或重置部署数据。
class FlashTestResult { public $rows; function __construct($rows=array()) { $this->rows=array_values($rows); } }
class FlashTestDb {
    public $players=array(),$mapitem=array(),$maptrap=array(),$shopitem=array(),$id=0,$last=0;
    function query($sql) {
        if (preg_match("/^UPDATE test_shopitem SET num = '(\d+)' WHERE sid = '(\d+)'$/",$sql,$m)) { $this->shopitem[(int)$m[2]]['num']=(int)$m[1]; return true; }
        if (preg_match('/^SELECT \* FROM test_(players|mapitem|maptrap|shopitem)(?: WHERE (.*?))?(?: ORDER BY sid)?$/',$sql,$m)) {
            $rows=$this->{$m[1]}; $where=$m[2]??'';
            $rows=array_filter($rows,function($r)use($where){
                foreach (preg_split('/ AND /',$where) as $part) {
                    if ($part==='') continue;
                    if (!preg_match("/^(\w+)\s*(=|>|<|<=|>=)\s*'?(-?\d+)'?$/",$part,$x)) throw new RuntimeException('Unsupported test filter: '.$part);
                    $value=$r[$x[1]]??0; $want=(int)$x[3];
                    if ($x[2]==='=' && $value!=$want || $x[2]==='>' && $value<=$want || $x[2]==='<' && $value>=$want) return false;
                    if ($x[2]==='<=' && $value>$want || $x[2]==='>=' && $value<$want) return false;
                }
                return true;
            }); return new FlashTestResult($rows);
        }
        if (preg_match('/^DELETE FROM test_(players|mapitem|maptrap)(?: WHERE (pid|iid|tid)=(\d+))?$/',$sql,$m)) {
            if (isset($m[3])) unset($this->{$m[1]}[(int)$m[3]]); else $this->{$m[1]}=array(); return true;
        }
        throw new RuntimeException('Unimplemented test SQL: '.$sql);
    }
    function fetch_array($result) { return array_shift($result->rows); }
    function num_rows($result) { return count($result->rows); }
    function array_insert($table,$row) {
        $name=substr($table,5); $key=array('players'=>'pid','mapitem'=>'iid','maptrap'=>'tid','shopitem'=>'sid')[$name];
        $row[$key]=++$this->id; $this->{$name}[$row[$key]]=$row; $this->last=$row[$key];
    }
    function insert_id() { return $this->last; }
}
function get_clbpara($v) { return is_array($v)?$v:(json_decode($v?:'[]',true)?:array()); }
function &get_itmpara($v) { $out=get_clbpara($v); return $out; }
function get_current_ruleset_id() { return 'FLASH'; }
function config($file,$cfg=1) { $path=GAME_ROOT.'gamedata/ruleset/FLASH/cache/'.$file.'_1.php'; return is_file($path)?$path:GAME_ROOT.'gamedata/cache/'.$file.'_1.php'; }
function openfile($path) { return file($path,FILE_IGNORE_NEW_LINES); }
function ft_fields() {
    static $fields; if ($fields) return $fields;
    preg_match_all('/^\s+`?(\w+)`?\s+(\w+)/m',file_get_contents(GAME_ROOT.'gamedata/sql/players.sql'),$matches,PREG_SET_ORDER);
    foreach ($matches as $m) $fields[$m[1]]=strpos(strtolower($m[2]),'int')!==false?0:'';
    return $fields;
}
function create_dummy_playerdata() { $p=ft_fields(); $p['clbpara']=array(); $p['money']=10000; return $p; }
function player_format_with_db_structure($p) {
    $out=array(); foreach (ft_fields() as $key=>$default) { $v=$p[$key]??$default; $out[$key]=is_array($v)?json_encode($v,JSON_UNESCAPED_UNICODE):$v; }
    if (function_exists('reload_equip_items')) reload_equip_items($out);
    return $out;
}
function player_save($p) { $GLOBALS['db']->players[$p['pid']]=player_format_with_db_structure($p); }
function fetch_playerdata_by_pid($id) { $p=$GLOBALS['db']->players[$id]??null; if ($p) { $p['clbpara']=get_clbpara($p['clbpara']); $p['pls']=(int)$p['pls']; $p['type']=(int)$p['type']; } return $p; }
function save_gameinfo() { $GLOBALS['ft_saved_game']=json_encode($GLOBALS['gamevars'],JSON_UNESCAPED_UNICODE); }
function load_gameinfo() {}
function save_combatinfo() {}
function addnews() {}
function logsave() {}
if (!defined('FLASH_TEST_UI')) { function init_battle_rev() {} }
function npc_changewep_rev() {}
function npc_chat_rev() { return ''; }
function addnoise() {}
if (!defined('FLASH_TEST_UI')) { function check_add_searchmemory() {} }
if (!defined('FLASH_TEST_UI')) { function template($name) { return GAME_ROOT.'gamedata/ruleset/FLASH/cache/mapitem_1.php'; } }
if (!defined('FLASH_TEST_UI')) {
    function parse_nameinfo_desc($name) { return $name; }
    function parse_kinfo_desc($kind,$keys='') { return $kind; }
    function parse_skinfo_desc($keys,$kind='',$mode=0) { return $keys; }
}
function get_itmsk_array($keys) { return array_values(array_filter(preg_split('//u',(string)$keys,-1,PREG_SPLIT_NO_EMPTY),function($k){return isset($GLOBALS['itemspkinfo'][$k]);})); }
function get_itmsk_strlen($keys,$max=30) { return implode('',$keys); }
function diceroll($max) { return isset($GLOBALS['ft_dice'])?min($max,$GLOBALS['ft_dice']):mt_rand(0,$max); }
// 原生 itemuse 的全量加载会连接 common.inc.php；这里仅替换分派，处理函数仍用正式源码。
function itemuse($slot,&$p) {
    if (ruleset_itemuse_hook($p,$slot,flash_item_at($p,$slot))) return;
    $kind=$p['itmk'.$slot];
    require_once GAME_ROOT.'include/game/item.weapon.php'; require_once GAME_ROOT.'include/game/item.recovery.php'; require_once GAME_ROOT.'include/game/item.cure.php';
    if (preg_match('/^(W|D|A)/',$kind)) item_weapon($slot,$p);
    elseif ($kind==='HB') item_recovery_both($slot,$p);
    elseif ($kind==='HS') item_recovery_stamina($slot,$p);
    elseif ($kind==='HH') item_recovery_health($slot,$p);
    elseif ($kind==='HR') item_recovery_rage($slot,$p);
    elseif (strpos($kind,'C')===0) item_cure($slot,$p);
    else throw new RuntimeException('Unsupported test item: '.$kind);
}
function ft_assert($ok,$message) { if (!$ok) throw new RuntimeException('FAIL: '.$message); $GLOBALS['ft_count']++; }
function ft_reject($fn,$message) { try { $fn(); } catch (RuntimeException $e) { ft_assert(true,$message); return; } throw new RuntimeException('FAIL accepted: '.$message); }

$tablepre=$gtablepre='test_'; $gamecfg=1; $groomid=7; $gamestate=20; $starttime=$now=1000; $areanum=0; $weather=0; $ft_count=0;
$log=''; $hdamage=0; $hplayer=''; $event=0; $hack=0; $mode='command'; $validnum=$alivenum=$deathnum=0;
require GAME_ROOT.'gamedata/ruleset/FLASH/cache/resources_1.php';
require GAME_ROOT.'gamedata/ruleset/FLASH/cache/gamecfg_1.php';
require GAME_ROOT.'gamedata/cache/combatcfg_1.php';
require GAME_ROOT.'gamedata/cache/clubskills_1.php';
require GAME_ROOT.'include/resources.func.php';
require GAME_ROOT.'include/game/revclubskills.func.php';
require GAME_ROOT.'include/game/quest.func.php';
require GAME_ROOT.'include/game/revcombat.func.php';
require dirname(__DIR__).'/include/ruleset.func.php';

function ft_start($seed=2810) {
    mt_srand($seed); $GLOBALS['db']=new FlashTestDb(); $GLOBALS['now']=$GLOBALS['starttime']=1000;
    $GLOBALS['log']=$GLOBALS['cmd']=$GLOBALS['flash_cmd_template']=$GLOBALS['opendialog']=''; $GLOBALS['flash_cmd_vars']=array();
    $GLOBALS['gamestate']=20; $GLOBALS['areanum']=0; $GLOBALS['gamevars']=array('flash'=>flash_room(1000));
    $GLOBALS['arealist']=array_merge(array(0),$GLOBALS['gamevars']['flash']['order']);
    $empty=null; flash_world_reset($empty,true);
    $p=flash_join(array('username'=>'旅人','password'=>'test-only','gender'=>'f','icon'=>1),'WK');
    foreach (openfile(config('shopitem')) as $line) {
        $row=explode(',',$line,10);
        if (count($row)<9 || !ctype_digit($row[0]) || !$row[0]) continue;
        $row=array_pad($row,10,'');
        $GLOBALS['db']->array_insert('test_shopitem',array_combine(array('kind','num','price','area','item','itmk','itme','itms','itmsk','itmpara'),$row));
    }
    return $p;
}
function ft_reload(&$p) { player_save($p); $p=fetch_playerdata_by_pid($p['pid']); $GLOBALS['pdata']=&$p; }
function ft_command(&$p,$post) {
    $post+=array('flash_csrf'=>$p['clbpara']['flash']['csrf'],'flash_rev'=>(string)$p['clbpara']['flash']['revision']);
    list($action,$arg,$extra)=flash_validate_command($p,$post);
    $GLOBALS['cmd']=$GLOBALS['flash_cmd_template']=''; $GLOBALS['flash_cmd_vars']=array();
    if ($action!=='status') { $p['clbpara']['flash']['revision']++; flash_action($p,$action,$arg,$extra); }
    ft_reload($p);
}

if (defined('FLASH_TEST_BOOTSTRAP')) return;

$p=ft_start(); $pdata=&$p;
ft_assert($p['itm1']==='面包' && $p['itms1']===15 && $p['itm2']==='矿泉水' && $p['itms2']===15,'initial bread and mineral water');
ft_assert(count(flash_closed())===0 && count(flash_safe())===35,'zero-ban world and permanent safe origin');
ft_assert(count(array_filter(flash_npcs(),function($n){return flash_role($n)==='boss';}))===5,'five evidence bosses');
ft_assert(count(array_filter(flash_npcs(),function($n){return flash_role($n)==='hazard';}))===3,'old zero-ban hazard trio');
ft_reject(function(){flash_join(array('username'=>'second','password'=>'x'),'WK');},'single participant enforced');
$post=array('command'=>'flash_move','arg'=>'1','flash_csrf'=>$p['clbpara']['flash']['csrf'],'flash_rev'=>'0');
ft_assert(flash_validate_command($p,$post)[0]==='move','valid request');
$p['clbpara']['flash']['revision']++; ft_reject(function()use($p,$post){flash_validate_command($p,$post);},'replayed revision rejected');
$post['flash_csrf']='wrong'; ft_reject(function()use($p,$post){flash_validate_command($p,$post);},'foreign csrf rejected');
$post['flash_csrf']=$p['clbpara']['flash']['csrf']; $post['flash_rev']='1'; $post['arg']=array(1); ft_reject(function()use($p,$post){flash_validate_command($p,$post);},'array argument rejected');

// 保留三类中的每一种；全字段、跨请求及找回难度冻结。
foreach (array('weapon','armor','stats') as $choice) {
    $p=ft_start(); $pdata=&$p; $p['att']=900; $p['def']=700; $p['mhp']=2000; $p['msp']=600; $p['lvl']=20;
    flash_set_equipment($p,'wep',flash_item('标记武器','WK',777,90,'rH',array('stamp'=>'kept')));
    flash_set_equipment($p,'arb',flash_item('标记防具','DB',555,70,'AH',array('stamp'=>'armor')));
    flash_mark($p,'FL_evidence_1'); flash_die($p,'test'); flash_die($p,'duplicate');
    ft_assert($p['clbpara']['flash']['deaths']===1,'one death cannot settle twice');
    flash_tick($p); ft_reload($p);
    ft_assert($p['hp']===0 && $p['clbpara']['flash']['pending'],'reload stays dead');
    ft_reject(function()use(&$p,$choice){flash_keep($p,$choice);},'keep cannot bypass death screen');
    ft_reject(function()use(&$p){flash_action($p,'explore','');},'dead player cannot act');
    flash_action($p,'revive',''); ft_reload($p);
    ft_reject(function()use(&$p){flash_action($p,'revive','');},'continue cannot repeat');
    flash_keep($p,$choice); ft_reload($p);
    ft_assert($p['itm1']==='面包' && $p['itme1']===120 && $p['itms1']===15 && $p['itm2']==='矿泉水' && $p['itme2']===140 && $p['itms2']===15,'each category restores starting food');
    ft_assert(flash_fragments($p)===1 && flash_report_count($p)===1,'quest progress survives reload and loop');
    ft_assert(($p['wepe']==777)===($choice==='weapon'),'weapon category '.$choice);
    ft_assert(($p['arbe']==555)===($choice==='armor'),'armor category '.$choice);
    ft_assert(($p['att']==900)===($choice==='stats'),'stats category '.$choice);
    if ($choice==='weapon') ft_assert(get_itmpara($p['weppara'])['stamp']==='kept','weapon para retained');
    ft_reject(function()use(&$p,$choice){flash_keep($p,$choice);},'keep cannot be submitted twice');
    ft_assert($p['clbpara']['quest']['active']['FL_recover_1']['accepted'],'recovery automatically accepted'); $need=$p['clbpara']['quest']['active']['FL_recover_1']['need'];
    flash_die($p,'again'); flash_tick($p); flash_revive($p); flash_keep($p,'stats');
    ft_assert($p['clbpara']['quest']['active']['FL_recover_1']['need']===$need,'accepted difficulty locked across another death');
    flash_job_event($p,'explore',$need); flash_claim($p,'FL_recover_1','stats');
    ft_reject(function()use(&$p){flash_claim($p,'FL_recover_1','stats');},'recover reward not paid twice');
}
$p=ft_start(); $pdata=&$p; $expected=array(1,2,4,6,9,12,16,20,24,28,32,34);
foreach ($expected as $i=>$count) {
    flash_die($p,'sequence'); ft_assert(count(flash_closed())===$count,'ban expansion '.$i);
    ft_assert(!in_array(0,flash_closed(),true),'origin never collapses');
    if ($i<11) { flash_tick($p); flash_revive($p); flash_keep($p,'stats'); ft_assert($p['hp']>0 && $p['sp']===$p['msp'],'revival fills pools'); }
}
ft_assert($p['clbpara']['flash']['result']==='lost' && $p['clbpara']['flash']['deaths']===12,'twelfth death fails');
ft_reject(function()use(&$p){flash_action($p,'revive','');},'twelfth death has no revival');

$p=ft_start(); $pdata=&$p;
for ($i=1;$i<=6;$i++) flash_set_item($p,$i,flash_item('保留武器'.$i,'WK',200,50,'',array('slot'=>$i)));
flash_die($p,'full bag'); flash_revive($p); flash_keep($p,'weapon'); ft_reload($p);
ft_assert(count($p['clbpara']['flash']['loot'])===2 && $p['clbpara']['flash']['loot'][0]['itm']==='面包' && $p['clbpara']['flash']['loot'][1]['itm']==='矿泉水','full retained bag still receives both starting supplies');
for ($i=1;$i<=6;$i++) ft_assert($p['itm'.$i]==='保留武器'.$i && get_itmpara($p['itmpara'.$i])['slot']===$i,'supplies never overwrite retained weapon '.$i);

$p=ft_start(); $pdata=&$p; $boss=flash_npc('boss1'); $boss['hp']=137; player_save($boss);
$final=flash_npc('final'); $final['hp']=1; $final['clbpara']['flash_npc']['rewinds']=2; player_save($final);
$j=flash_npc('jojo'); $j['att']=999; player_save($j); $old_minions=array_filter(flash_npcs(),function($n){return flash_role($n)==='minion';});
flash_die($p,'world reset'); flash_tick($p);
ft_assert(flash_npc('final')['hp']===1 && array_intersect_key($old_minions,flash_npcs())===$old_minions,'world waits at death screen');
flash_revive($p);
ft_assert(flash_npc('boss1')['hp']===137,'intermediate boss health persists');
ft_assert(flash_npc('final')['hp']===flash_npc('final')['mhp'] && flash_npc('final')['clbpara']['flash_npc']['rewinds']===0,'final health and rewind allowance reset');
ft_assert(flash_npc('jojo')['att']===999,'companion growth persists');
ft_assert(!array_intersect_key($old_minions,flash_npcs()),'ordinary world replaced');
flash_keep($p,'stats'); $blocked=flash_closed()[0]; ft_assert(!ruleset_move_allowed_hook($p,$blocked),'closed map blocked');
$p['clbpara']['flash']['path']=true; ft_assert(ruleset_move_allowed_hook($p,$blocked),'PC path allows eroded map');
$p['pls']=$blocked; $p['hp']=100; $mhp=$p['mhp']; $msp=$p['msp']; flash_erosion($p,true);
ft_assert($p['hp']===90 && $p['mhp']===$mhp-2 && $p['msp']===$msp-2,'erosion charges hp and max pools');
flash_erosion($p,true); flash_erosion($p,true); ft_assert(strpos($p['inf'],'u')!==false && isset($p['clbpara']['quest']['completed']['FL_clue_'.$blocked]),'third eroded search gives status and clue');

// 正式战斗公式：弱点包括固伤，三段+四次攻击，反噬与死亡保存。
$p=ft_start(); $pdata=&$p; $j=flash_npc('jojo');
ft_assert($j['wk']===100,'companion starts at one hundred slash proficiency');
$j['nm']='乔乔'; $j['wep_name']=$j['wep']; $j['final_damage']=5000;
$j['ex_keys']=array_merge(\revattr\get_equip_ex_array($j),\revattr\get_wep_ex_array($j));
$n=flash_npc('boss1'); $hp=$j['hp']; \revattr\attack_finish_events($j,$n,1);
ft_assert((int)$j['hp']===$hp-(int)floor(floor($hp*4/5)/10),'companion cloak applies native HP control to recoil');
$hp=$j['hp']; \revattr\attack_finish_events($j,$n,1);
ft_assert($j['hp']>2700 && (int)$j['hp']===(int)$hp-(int)floor(floor($hp*4/5)/10),'two high damage attacks no longer leave companion near death');

$p=ft_start(); $pdata=&$p; $n=flash_npc('boss2'); $p['wep_kind']='N';
ft_assert(ruleset_get_fix_damage_hook($p,$n,1)===0 && ruleset_final_damage_fix_hook($p,$n,1,9999)===0,'wrong weakness also blocks fixed damage');
$p['wep_kind']=$gamevars['flash']['weak'][2]; ft_assert(ruleset_final_damage_fix_hook($p,$n,1,100)===null,'correct weakness follows native damage');
$j=flash_npc('jojo'); $j['nm']='乔乔'; $n['nm']='目标'; flash_prepare_fighter($j,$n); $hits=array();
do { $hits[]=ruleset_hit_count_hook($j,$n,1,6); } while (\revcombat\attack_check_can_loop($j,$n,1));
ft_assert($hits===array(3,1,1,1,1),'native extra-attack chain has seven segments');
$j['flash_suppressed']=true; $j['skill_c12_swell']=4; $j['skill_c13_quick']=2;
ft_assert(ruleset_hit_count_hook($j,$n,1,6)===1 && !\revcombat\attack_check_can_loop($j,$n,1),'suppression stops native extra attacks');
$n=flash_npc('boss1'); $n['nm']=$n['name']; $n['wep_name']=$n['wep']; $n['final_damage']=5000; $n['ex_keys']=array(); $hp=$n['hp']; \revattr\attack_finish_events($n,$p,1);
ft_assert((int)$n['hp']===$hp-(int)floor($hp*4/5),'recoil prototype uses native backlash');
$n=flash_npc('final'); $n['nm']=$n['name']; $n['wep_name']=$n['wep']; $n['final_damage']=5000; $n['ex_keys']=array(); $hp=$n['hp']; \revattr\attack_finish_events($n,$p,1);
ft_assert((int)$n['hp']===$hp,'final cannot be killed through recoil');

// 实际往返原生战斗与序列化，而非仅直接调用死亡钩子。
$p=ft_start(); $pdata=&$p; $n=flash_npc('boss1'); $p['pls']=$n['pls']=1; $n['wepsk']=''; $n['att']=100000; $n['wepe']=100000; player_save($n);
$p['clbpara']['flash']['stance']='suppress'; $p['hp']=1; $ft_dice=0;
flash_fight($p,$n['pid'],false,1); unset($ft_dice); flash_tick($p); ft_reload($p);
ft_assert($p['clbpara']['flash']['pending'] && $p['clbpara']['flash']['deaths']===1 && $p['hp']===0 && $p['pls']===1 && $p['bid']===$n['pid'],'native lethal combat preserves death location and killer after save');

$p=ft_start(); $pdata=&$p; flash_job_event($p,'minion',3); $before=$p['att']; flash_claim($p,'FL_job_patrol','stats');
ft_assert($p['att']>$before && isset($p['clbpara']['quest']['completed']['FL_job_patrol']),'job completion pays');
ft_reject(function()use(&$p){flash_claim($p,'FL_job_patrol','stats');},'job double payout rejected');
ft_assert(count($p['clbpara']['flash']['loot'])<=1,'dream reward is bounded');

$p=ft_start(); $pdata=&$p; flash_action($p,'gate',''); ft_assert($p['clbpara']['flash']['pending'] && !isset($p['clbpara']['quest']['completed']['FL_gate']),'unsupported gate entry has lethal finish');
$p=ft_start(); $pdata=&$p; foreach (array('first','chain','drift','wrong','kind') as $key) flash_report($p,$key);
for ($i=1;$i<=5;$i++) flash_mark($p,'FL_evidence_'.$i);
ft_assert(flash_true_available($p),'true route flags'); $p['clbpara']['flash']['jojo_deaths']=1; ft_assert(!flash_true_available($p),'past companion death disqualifies true route');
$p['clbpara']['flash']['jojo_deaths']=0; $p['clbpara']['flash']['deaths']=9; ft_assert(!flash_true_available($p),'true route revival threshold');
$p['clbpara']['flash']['deaths']=5; flash_action($p,'gate',''); $n=flash_npc('final'); $n['hp']=0; $p['inf']='e';
ruleset_combat_result_hook($p,$n,1); ft_assert($n['hp']===$n['mhp'],'final rewinds while player paralyzed');
$n['hp']=0; $p['inf']=''; ruleset_combat_result_hook($p,$n,1); ft_assert($n['hp']===0,'curing paralysis interrupts final rewind'); player_save($n);
flash_action($p,'escape',''); ft_assert($p['clbpara']['flash']['result']==='normal','ordinary ending');
ft_reject(function()use(&$p){flash_action($p,'escape','');},'ending cannot settle twice');
$p=ft_start(); $pdata=&$p; $now=4600; flash_tick($p); ft_assert($p['clbpara']['flash']['result']==='lost' && $p['clbpara']['flash']['deaths']===0,'timeout independent of death and no chain death');

// 两套皮肤复用相同信息区和死亡控制，玩家文本不泄露原 NPC 身份。
$p=ft_start(); $pdata=&$p; $html=flash_panel($p); ft_assert(strpos($html,'data-action="explore"')!==false && strpos($html,'data-action="keep"')===false,'active controls render');
flash_die($p,'preview'); $html=flash_panel($p);
ft_assert(substr_count($html,'data-action="revive"')===1 && strpos($html,'data-action="keep"')===false,'death screen offers only explicit continue');
flash_revive($p); $html=flash_panel($p); ft_assert(substr_count($html,'data-action="keep"')===3,'revival has exactly three choices');
foreach (array('EA-772','奥西戈夫','AI','训练','时空特使','七星','QM') as $secret) ft_assert(strpos(strip_tags($html),$secret)===false,'public copy excludes '.$secret);
// 待选物品与满包，正式换装/喂食处理，保留 para 与旧装备。
$p=ft_start(); $pdata=&$p;
for ($i=1;$i<=6;$i++) flash_set_item($p,$i,flash_item('占位'.$i,'Y',1,1));
$item=flash_item('有标记的刀','WK',300,99,'H',array('stamp'=>'reward'));
flash_loot($p,array($item)); ft_reject(function()use(&$p){flash_action($p,'take','0');},'full bag rejects pickup');
ft_assert(count($p['clbpara']['flash']['loot'])===1,'failed pickup preserves offered item');
flash_action($p,'loot_use','0'); ft_reload($p);
ft_assert($p['wep']==='有标记的刀' && get_itmpara($p['weppara'])['stamp']==='reward','direct equip works from full bag');
ft_assert($p['clbpara']['flash']['loot'][0]['itm']==='醒来时攥着的短刀','replaced equipment stays in offer');
$att=flash_npc('jojo')['att']; flash_action($p,'loot_feed','0'); ft_reload($p);
ft_assert(!$p['clbpara']['flash']['loot'] && flash_npc('jojo')['att']>$att,'feeding offered weapon grows saved companion');
flash_action($p,'unequip','wep'); ft_reload($p);
ft_assert($p['wepk']==='WN' && !$p['itms0'] && $p['clbpara']['flash']['loot'][0]['itmpara']['stamp']==='reward','native unequip preserves full-bag equipment and para in offer');
ft_reject(function()use(&$p){flash_action($p,'unequip','arb');},'unequip cannot overwrite unhandled offer');
$before=$p['clbpara']['quest']['active']; ft_reject(function()use(&$p){flash_action($p,'accept','FL_recover_fake');},'forged recovery rejected');
ft_assert($before===$p['clbpara']['quest']['active'],'forged recovery does not add active state');
ft_reject(function()use(&$p){flash_claim($p,'FL_job_collect','equipment_WX');},'forged weapon family rejected');

// 扫荡每名杂兵 30 SP，停在强敌/低血量/低体力前；等待时按实际时间回复。
$p=ft_start(); $pdata=&$p; $p['pls']=1; $p['lvl']=200; $p['exp']=0; $p['att']=$p['def']=100000;
$p['hp']=$p['mhp']=100000; $p['sp']=95; $p['wepsk']='H';
foreach (flash_npcs() as $n) {
    if (flash_role($n)==='minion' && (int)$n['pls']===1) $n['hp']=1;
    elseif ((int)$n['pls']===1) $n['pls']=0;
    player_save($n);
}
$ft_dice=0; flash_battle_action($p,true); unset($ft_dice);
ft_assert($p['sp']===5 && $p['killnum']===3,'sweep settles three minions for ninety SP');
ft_assert(!$p['clbpara']['flash']['target'],'sweep leaves no stale corpse target');
$now+=100; flash_tick($p); ft_assert($p['sp']===35,'passive stamina recovery follows elapsed time');
$p['sp']=200; $p['hp']=$p['mhp']; $n=flash_npc('boss4'); $n['pls']=1; player_save($n); $p['clbpara']['flash']['target']=$n['pid'];
flash_battle_action($p,true); ft_assert($p['sp']===200 && flash_npc('boss4')['hp']===$n['hp'],'advanced encounter pauses before charge or damage');
$p['hp']=1; flash_battle_action($p,true); ft_assert($p['sp']===200,'low health stops sweep before first fight');
ft_assert(count(flash_safe())===35 && !in_array(101,flash_neighbours(0),true),'native battle hidden maps never leak into FLASH world');

// 追踪失败照扣，移动PC的成功/失败均不解禁，地雷与排雷车走正式处理。
$p=ft_start(); $pdata=&$p;
for ($seed=0;$seed<100;$seed++) { mt_srand($seed); if (mt_rand(1,100)>flash_cfg('track_chance')) break; }
mt_srand($seed); flash_track($p,flash_npc('boss2')['pid']); ft_assert($p['sp']===355 && $p['pls']===0,'failed tracking charges 45 without teleport');
$areanum=1; $closed=flash_closed();
foreach (array(true,false) as $success) {
    for ($seed=0;$seed<1000;$seed++) { mt_srand($seed); $pass=mt_rand(0,99)<$hack_obbs; $side=mt_rand(0,99); if ($pass===$success && $side>=8) break; }
    $p['clbpara']['flash']['path']=false; flash_set_item($p,4,flash_item('移动PC','EE',5,1)); mt_srand($seed); flash_use($p,4);
    ft_assert($p['clbpara']['flash']['path']===$success && $p['itme4']===4 && flash_closed()===$closed && !$hack,'PC keeps collapsed areas and consumes power');
}
$p['pls']=flash_cfg('mine_places')[0]; $p['hp']=400; $ft_dice=99; flash_trap($p,true); unset($ft_dice);
ft_assert($p['clbpara']['flash']['pending'] && $p['clbpara']['flash']['deaths_log'][1]['reason']==='阔剑地雷','native claymore triggers loop with readable cause');
ft_assert(!$p['itms0'],'trap processing clears temporary slot'); flash_tick($p); ft_reload($p);
ft_assert($p['hp']===0 && $p['pls']===flash_cfg('mine_places')[0] && $p['state']===27,'claymore death persists before continue');
flash_revive($p); flash_keep($p,'stats');
ft_assert(count($db->maptrap)===count(flash_cfg('mine_places'))-1,'revival preserves remaining mines without replacement');
$p=ft_start(); $pdata=&$p; $p['pls']=flash_cfg('mine_places')[1];
flash_set_item($p,2,flash_item('混沌人肉探雷车','Y',1,1)); $before=count($db->maptrap); flash_use($p,2);
ft_assert(count($db->maptrap)===$before-1 && !$p['itms2'],'mine car removes one area claymore and is consumed');
flash_set_item($p,2,flash_item('混沌人肉探雷车','Y',1,1)); ft_reject(function()use(&$p){flash_use($p,2);},'empty disarm does not consume');
ft_assert($p['itms2']===1,'unsuccessful mine car retained');

// 目击即登记；反杀灭口者不误入“杀错人”，回溯后两人重新出现。
$p=ft_start(); $pdata=&$p; $p['att']=$p['def']=100000; $p['mhp']=$p['hp']=100000; $p['wepsk']='H'; $p['clbpara']['flash']['stance']='suppress';
$ft_dice=0; flash_event($p,'chain'); unset($ft_dice);
ft_assert(isset($p['clbpara']['quest']['completed']['FL_report_chain']) && flash_npc('friend_red')['hp']<=0,'counterkill still keeps witnessed page');
ft_assert(flash_ready($p) && !isset($p['clbpara']['quest']['completed']['FL_report_wrong']),'counterkill continues without wrong-person death');
flash_die($p,'回溯'); flash_tick($p); flash_revive($p);
ft_assert(flash_npc('friend_red')['hp']>0 && flash_npc('friend_blue')['hp']>0 && empty(flash_npc('friend_red')['clbpara']['flash_npc']['silence']),'murder pair reset together');

// 代打的真实战斗、死亡牵连、找回，以及终局重置后再次领取的条件。
$p=ft_start(); $pdata=&$p; $n=flash_npc('boss1'); $n['pls']=0; $n['hp']=$n['mhp']=1000000; $n['att']=$n['wepe']=1000000; player_save($n);
$j=flash_npc('jojo'); $j['hp']=1; player_save($j); $p['clbpara']['flash']['stance']='suppress'; $ft_dice=0;
flash_fight($p,$n['pid'],true,1); unset($ft_dice); flash_tick($p); ft_reload($p);
ft_assert($p['clbpara']['flash']['pending'] && $p['clbpara']['flash']['jojo_deaths']===1 && flash_npc('jojo')['hp']<=0,'companion defeat forces player loop');
flash_revive($p); flash_keep($p,'stats'); flash_action($p,'recover_jojo',''); ft_reload($p);
ft_assert(!$p['clbpara']['flash']['jojo_missing'] && flash_npc('jojo')['hp']>0 && !flash_true_available($p),'recover companion does not erase prior death');
$p=ft_start(); $pdata=&$p; $p['clbpara']['flash']['gate']=true; $p['inf']='e'; $n=flash_npc('final');
for ($i=0;$i<3;$i++) { $n['hp']=0; ruleset_combat_result_hook($p,$n,1); ft_assert(($n['hp']>0)===($i<2),'final rewinds at most twice'); }
player_save($n); flash_die($p,'门后的回溯'); flash_tick($p); flash_revive($p); $n=flash_npc('final');
ft_assert($n['hp']===$n['mhp'] && empty($n['clbpara']['flash_npc']['rewarded']),'next loop final can settle again');
$now=$gamevars['flash']['start']+3600; flash_tick($p); ft_assert($p['clbpara']['flash']['result']==='lost' && $p['clbpara']['flash']['deaths']===1,'timeout while choosing does not spend another life');
$a=array('bskill_c7_electric'=>1); $b=array();
ft_assert(\revattr\get_clbskill_ex_base_dmg_fix($a,$b,1,'e')===get_skillvars('c7_electric','exdmgfix'),'native electric skill applies its configured fixed bonus');

// 高阶重复委托和喂食仍应能写回现有 tinyint/smallint 字段。
$p=ft_start(); $pdata=&$p; $p['lvl']=254; $p['wk']=65534;
flash_reward($p,7,'stats'); ft_reload($p);
ft_assert($p['lvl']===255 && $p['wk']===65535,'reward keeps level and proficiency within stored bounds');
$j=flash_npc('jojo'); $j['wk']=65000; player_save($j);
flash_set_item($p,4,flash_item('高阶长刀','WK',200000,300)); flash_feed($p,4); ft_reload($p);
ft_assert(flash_npc('jojo')['wk']===65535 && !$p['itms4'],'high tier feeding saves capped proficiency and consumes weapon');

$p=ft_start(); $pdata=&$p; $areanum=1; $p['pls']=flash_closed()[0];
for ($i=0;$i<3;$i++) flash_erosion($p,true);
ft_reload($p); $qid='FL_clue_'.$p['pls'];
ft_assert(!empty($p['clbpara']['quest']['completed'][$qid]['reason']) && strpos(flash_journal_panel($p),'空白里的学生证')!==false,'optional clue can be reread after reload');

// 气泡只出现在同伴确实同行时，后端也校验喂食对象的状态。
$p=ft_start(); $pdata=&$p;
ft_assert(strpos(flash_companion($p),'data-action="feed"')!==false && strpos(flash_panel($p),'data-action="feed"')===false,'companion controls live in floating bubble');
$j=flash_npc('jojo'); $j['pls']=1; player_save($j);
ft_assert(flash_companion($p)==='','companion not at player location is hidden');
ft_reject(function()use(&$p){flash_feed($p,1);},'feeding distant companion rejected');
$j['pls']=0; $j['hp']=0; player_save($j);
ft_assert(flash_companion($p)==='','dead companion is hidden');
ft_reject(function()use(&$p){flash_feed($p,1);},'feeding dead companion rejected');
$j['hp']=$j['mhp']; player_save($j); $p['clbpara']['flash']['jojo_missing']=true;
ft_assert(flash_companion($p)==='','missing companion is hidden');
$p['clbpara']['flash']['jojo_missing']=false; flash_die($p,'preview');
ft_assert(flash_companion($p)==='','death screen hides companion');
flash_revive($p); ft_assert(flash_companion($p)==='','retention choice hides companion');
flash_keep($p,'stats'); ft_assert(strpos(flash_companion($p),'flash-jojo-toggle')!==false,'continued journey restores companion widget');

// 初见杀阶段跨轮回保留；四个来源任一触发后都不再累加教学死。
foreach (array(0,1,2,3) as $source) {
    $p=ft_start(); $pdata=&$p;
    for ($seed=0;$seed<1000;$seed++) {
        mt_srand($seed); $roll=mt_rand(1,100); $which=$roll<=80?mt_rand(0,3):4;
        if ($which===$source) break;
    }
    $dest=flash_neighbours(0)[0]; mt_srand($seed); flash_move($p,$dest); ft_reload($p);
    ft_assert(!empty($p['clbpara']['flash']['events']['first_step']),'first encounter ends for source '.$source);
    $mines=$db->maptrap;
    if ($p['clbpara']['flash']['pending']) { flash_revive($p); flash_keep($p,'stats'); }
    else flash_world_reset($p);
    ft_reload($p);
    ft_assert($db->maptrap===$mines && !flash_npc('hazard0') && !flash_npc('hazard1') && !flash_npc('hazard2'),'hazards retire and mines persist after source '.$source);
    ft_assert(count($db->mapitem)>0 && count(array_filter(flash_npcs(),function($n){return flash_role($n)==='minion';}))>0,'normal world still populated after source '.$source);
    $before=$p['clbpara']['flash']['deaths']; $p['sp']=1000;
    for ($step=0;$step<8;$step++) { $routes=array_values(array_intersect(flash_neighbours((int)$p['pls']),flash_safe())); flash_move($p,$routes[0]); }
    ft_assert($p['clbpara']['flash']['deaths']===$before,'subsequent moves do not spend tutorial revivals '.$source);
}
$p=ft_start(); $pdata=&$p; $p['clbpara']['flash']['events']['first_step']=true;
$n=flash_npc('hazard0'); $p['pls']=$n['pls'];
ft_reject(function()use(&$p,$n){flash_track($p,$n['pid']);},'spent hazard cannot be tracked');
ft_reject(function()use(&$p,$n){flash_fight($p,$n['pid']);},'spent hazard cannot be fought');
$hp=$p['hp']; $before=count($db->maptrap); flash_trap($p,true);
ft_assert($p['hp']===$hp && count($db->maptrap)===$before,'leftover tutorial mines cannot hit a survivor');
$p=ft_start(); $pdata=&$p; flash_die($p,'首次主动回溯'); unset($p['clbpara']['flash']['events']['first_step']); ft_reload($p);
flash_revive($p); ft_reload($p);
ft_assert(count($db->maptrap)===count(flash_cfg('mine_places')) && !flash_npc('hazard0') && $p['clbpara']['flash']['events']['first_step'],'older save with first death retires hazards but keeps mines');
flash_keep($p,'stats');
ft_assert(isset($p['clbpara']['quest']['active']['FL_job_disarm']),'mine quest automatically starts after first revival');
ft_reject(function()use(&$p){flash_action($p,'cancel','FL_job_disarm');},'automatic once-only quest cannot be withdrawn');
ft_reject(function()use(&$p){flash_action($p,'cancel','FL_recover_1');},'recoverable losses cannot be discarded by task cancellation');

// 原生商店、整理、合成与技能操作经过请求校验和保存，不能绕过死亡/重复提交。
$p=ft_start(); $pdata=&$p; $p['pls']=$shops[0];
ft_command($p,array('command'=>'special','sp_cmd'=>'sp_shop'));
ft_assert($flash_cmd_template==='sp_shop','native shop menu opens');
ft_command($p,array('mode'=>'special','command'=>'shop1'));
ft_assert($flash_cmd_template==='shop' && count($itemdata)>0,'native stock list opens');
$stock=array_values(array_filter($db->shopitem,function($item){return $item['item']==='面包' && (int)$item['area']===0;}))[0];
$post=array('mode'=>'shop','command'=>(string)$stock['sid'],'shoptype'=>'1','buynum'=>'2','flash_csrf'=>$p['clbpara']['flash']['csrf'],'flash_rev'=>(string)$p['clbpara']['flash']['revision']);
$cash=$p['money']; ft_command($p,$post);
ft_assert($p['money']===$cash-2*(int)$stock['price'] && (int)$db->shopitem[$stock['sid']]['num']===(int)$stock['num']-2,'buy uses native price and decrements stock');
ft_assert($p['clbpara']['flash']['loot'][0]['itm']==='面包' && (int)$p['clbpara']['flash']['loot'][0]['itms']===2,'native merge prompt becomes retained loot');
ft_reject(function()use(&$p,$post){ft_command($p,$post);},'buy replay rejected');
flash_action($p,'take','0'); ft_assert($p['itms1']===17,'purchased food joins original bread without loss');
$cash=$p['money']; $num=$db->shopitem[$stock['sid']]['num'];
foreach (array('0','-1','1.5','1001',array('1')) as $bad) ft_reject(function()use(&$p,$stock,$bad){ft_command($p,array('mode'=>'shop','command'=>(string)$stock['sid'],'shoptype'=>'1','buynum'=>$bad));},'invalid purchase count rejected');
ft_assert($p['money']===$cash && $db->shopitem[$stock['sid']]['num']===$num,'bad counts preserve money and stock');
$p['money']=0; ft_command($p,array('mode'=>'shop','command'=>(string)$stock['sid'],'shoptype'=>'1','buynum'=>'1'));
ft_assert($p['money']===0 && $db->shopitem[$stock['sid']]['num']===$num,'insufficient funds do not buy');
$p['money']=300; $db->shopitem[$stock['sid']]['num']=0;
ft_command($p,array('mode'=>'shop','command'=>(string)$stock['sid'],'shoptype'=>'1','buynum'=>'1'));
ft_assert($p['money']===300 && !$p['clbpara']['flash']['loot'],'sold out stock does not charge');
$db->shopitem[$stock['sid']]['num']=$num;
ft_command($p,array('mode'=>'shop','command'=>(string)$stock['sid'],'shoptype'=>'1','buynum'=>(string)($num+1)));
ft_assert($p['money']===300 && $db->shopitem[$stock['sid']]['num']===$num,'stock cannot be oversold');
$later=array_values(array_filter($db->shopitem,function($item){return $item['item']==='圆形罐头' && (int)$item['area']===1;}))[0];
ft_command($p,array('mode'=>'shop','command'=>(string)$later['sid'],'shoptype'=>'1','buynum'=>'1'));
ft_assert($p['money']===300 && !$p['clbpara']['flash']['loot'],'future shop stock remains locked');
$p['money']=300; $p['pls']=array_values(array_diff(range(0,34),$shops))[0];
ft_reject(function()use(&$p,$stock){ft_command($p,array('mode'=>'shop','command'=>(string)$stock['sid'],'shoptype'=>'1','buynum'=>'1'));},'remote shop purchase rejected');
$p['pls']=$shops[0];
ft_reject(function()use(&$p,$stock){ft_command($p,array('mode'=>'shop','command'=>(string)$stock['sid'],'shoptype'=>'2','buynum'=>'1'));},'wrong shop category rejected');
for ($i=1;$i<=6;$i++) flash_set_item($p,$i,flash_item('占位'.$i,'WK',10,10,'',array('slot'=>$i)));
ft_command($p,array('mode'=>'shop','command'=>(string)$stock['sid'],'shoptype'=>'1','buynum'=>'1'));
ft_assert(count($p['clbpara']['flash']['loot'])===1 && !$p['itms0'] && get_itmpara($p['itmpara6'])['slot']===6,'full bag purchase keeps item and bag metadata');
$cash=$p['money'];
ft_reject(function()use(&$p,$stock){ft_command($p,array('mode'=>'shop','command'=>(string)$stock['sid'],'shoptype'=>'1','buynum'=>'1'));},'unhandled purchase cannot be overwritten');
ft_assert($p['money']===$cash,'blocked second buy does not charge');
flash_die($p,'商店前死亡');
ft_reject(function()use(&$p){ft_command($p,array('command'=>'special','sp_cmd'=>'sp_shop'));},'dead player cannot open shop');

$p=ft_start(); $pdata=&$p; flash_set_item($p,5,flash_item('标记道具','C',1,3,'',array('mark'=>'saved')));
ft_command($p,array('mode'=>'itemmain','command'=>'itemmove','from'=>'5','to'=>'6'));
ft_assert(!$p['itms5'] && get_itmpara($p['itmpara6'])['mark']==='saved','native slot move saves all item fields');
flash_set_item($p,5,flash_item('面包','HH',120,2));
ft_command($p,array('mode'=>'itemmain','command'=>'itemmerge','merge1'=>'5','merge2'=>'1'));
ft_assert(!$p['itms5'] && $p['itms1']===17,'native bag merge persists');
ft_reject(function()use(&$p){ft_command($p,array('mode'=>'itemmain','command'=>'itemmove','from'=>'6','to'=>'99'));},'invalid bag slot rejected');
ft_command($p,array('mode'=>'itemmain','command'=>'dropitm6'));
ft_assert(!$p['itms6'] && count(array_filter($db->mapitem,function($i){return $i['itm']==='标记道具';}))===1,'native drop reaches map');
$dropped=array_values(array_filter($db->mapitem,function($i){return $i['itm']==='标记道具';}))[0];
ft_assert(get_itmpara($dropped['itmpara'])['mark']==='saved','native drop preserves metadata on the ground');
flash_set_item($p,5,flash_item('咖喱','HH',100,2));
ft_command($p,array('mode'=>'itemmain','command'=>'itemmix','mitm1'=>'1','mitm5'=>'5'));
ft_assert($p['itms1']===16 && $p['itms5']===1 && in_array('咖喱面包',array($p['itm1'],$p['itm2'],$p['itm3'],$p['itm4'],$p['itm5'],$p['itm6'])),'real recipe consumes one of each ingredient and produces curry bread');
ft_reject(function()use(&$p){ft_command($p,array('mode'=>'itemmain','command'=>'itemmix','mixmask'=>'17','itemselect'=>'998'));},'forged mix result rejected');
getclubskill('c12_garrison',$p['clbpara']); $p['skillpoint']=10;
ft_command($p,array('mode'=>'revskpts','command'=>'upgskill_c12_garrison'));
ft_assert($p['skillpoint']===8 && $p['clbpara']['skillpara']['c12_garrison']['lvl']===1,'learned skill upgrades and spends points');
ft_reject(function()use(&$p){ft_command($p,array('mode'=>'revskpts','command'=>'upgskill_c1_crit'));},'unlearned skill upgrade rejected');
$p['sp']=10; $sp=$p['sp'];
ft_reject(function()use(&$p){ft_command($p,array('command'=>'rest1'));},'retired rest command rejected');
ft_assert($p['sp']===$sp,'rejected rest preserves stamina');
ft_command($p,array('mode'=>'special','command'=>'hor1')); ft_assert($p['horizon']===1,'native horizon control saves');
$p['inf']='a'; $p['sp']=100; ft_command($p,array('mode'=>'special','command'=>'infa'));
ft_assert($p['inf']==='' && $p['sp']===100-$inf_sp,'native injury button bandages with stamina');
flash_set_item($p,5,flash_item('食堂的盒饭','HR',50,1)); $p['rage']=0; flash_use($p,5);
ft_assert($p['rage']===50 && !$p['itms5'],'ordinary shop rage food can be used');

// 新版一次性任务：自动解锁、旧局迁移和跨请求重复提交。
$p=ft_start(); $pdata=&$p;
ft_assert(isset($p['clbpara']['quest']['active']['FL_job_patrol'],$p['clbpara']['quest']['active']['FL_job_collect'],$p['clbpara']['quest']['active']['FL_job_explore']),'starting quests automatically active');
ft_assert(!isset($p['clbpara']['quest']['active']['FL_job_disarm']),'mine quest waits for first revival');
flash_job_event($p,'minion',3); flash_claim($p,'FL_job_patrol','stats'); $att=$p['att'];
$p['clbpara']['quest']['active']['FL_job_patrol']=array('kind'=>'minion','need'=>3,'progress'=>3,'tier'=>1);
flash_update_quests($p); ft_reload($p);
ft_assert(!isset($p['clbpara']['quest']['active']['FL_job_patrol']) && $p['att']===$att,'old repeated completed task is retired without payment');
ft_reject(function()use(&$p){flash_claim($p,'FL_job_patrol','stats');},'completed task cannot pay after reload');
ft_reject(function()use(&$p){flash_action($p,'accept','patrol');},'manual repeat acceptance rejected');
$p['lvl']=5; flash_die($p,'自动任务'); flash_revive($p); flash_keep($p,'stats');
ft_assert(isset($p['clbpara']['quest']['active']['FL_job_holo'],$p['clbpara']['quest']['active']['FL_job_escort'],$p['clbpara']['quest']['active']['FL_job_feed']),'level and revival gates automatically unlock tasks');
$p['clbpara']['quest']['active']['FL_recover_1']['accepted']=false; unset($p['clbpara']['quest']['active']['FL_recover_1']['need']);
flash_tick($p); ft_assert($p['clbpara']['quest']['active']['FL_recover_1']['accepted'] && $p['clbpara']['quest']['active']['FL_recover_1']['need']===2,'old unaccepted recovery automatically migrates');
$p=ft_start(); $pdata=&$p; $p['lvl']=20; $att=$p['att'];
flash_job_event($p,'minion',3); $tier=flash_reward_tier($p); flash_claim($p,'FL_job_patrol','stats'); ft_reload($p);
ft_assert($tier===3 && $p['att']===$att+flash_cfg('reward_stats')[3],'automatically accepted quest reward scales at claim time');

// 装备背包时仍可使用原生信息按钮；未装备时服务端拒绝。
$p=ft_start(); $pdata=&$p;
ft_reject(function()use(&$p){ft_command($p,array('command'=>'iteminfo'));},'backpack information requires equipped bag');
flash_set_equipment($p,'arb',flash_item('旅行背包','DB',10,20,'^')); $p['itembag']='[]'; $p['itmnum']=0; $p['itmnumlimit']=10;
$log=''; ft_command($p,array('command'=>'iteminfo'));
ft_assert(strpos($log,'当前背包内装有如下道具')!==false && strpos($log,'0/10')!==false,'native backpack information renders');

// 固定少量雷跨回溯保留；初次踩掉也计入后来自动生成的任务。
$p=ft_start(); $pdata=&$p;
ft_assert(array_column(flash_mines(),'pls')===flash_cfg('mine_places'),'only fixed maps have initial claymores');
$mines=$db->maptrap; flash_world_reset($p); ft_assert($db->maptrap===$mines,'world reset neither deletes nor plants mines');
$p['pls']=flash_cfg('mine_places')[0]; $ft_dice=99; flash_trap($p,true); unset($ft_dice);
flash_revive($p); flash_keep($p,'stats'); ft_reload($p);
$q=$p['clbpara']['quest']['active']['FL_job_disarm'];
ft_assert($q['need']===3 && $q['progress']===1,'initial lethal mine counts before automatic acceptance');
ft_reject(function()use(&$p){flash_claim($p,'FL_job_disarm','stats');},'mine reward cannot be chosen or claimed early');
foreach (array_slice(flash_cfg('mine_places'),1) as $place) {
    $p['pls']=$place; flash_set_item($p,5,flash_item('混沌人肉探雷车','Y',1,1)); flash_use($p,5); ft_reload($p);
}
ft_assert(!$db->maptrap && isset($p['clbpara']['quest']['completed']['FL_job_disarm']),'last cleared mine automatically completes task');
$rewards=$p['clbpara']['flash']['loot']; ft_assert(count($rewards)>=3,'automatic mine reward retained as pending loot');
flash_update_quests($p); flash_tick($p); ft_reload($p);
ft_assert($p['clbpara']['flash']['loot']===$rewards,'repeated status refresh does not duplicate mine reward');
flash_die($p,'排雷后'); flash_revive($p); flash_keep($p,'stats');
ft_assert(!$db->maptrap && !$p['clbpara']['flash']['loot'],'later revival cannot replant mines or pay completed task');
$p=ft_start(); $pdata=&$p; $db->maptrap=array(); unset($gamevars['flash']['mines_total']);
flash_die($p,'旧局'); flash_revive($p); flash_keep($p,'stats'); ft_reload($p);
ft_assert(!$db->maptrap && isset($p['clbpara']['quest']['completed']['FL_job_disarm']) && json_decode($ft_saved_game,true)['flash']['mines_total']===0,'legacy empty minefield is saved and rewarded without replanting');
$p=ft_start(); $pdata=&$p; flash_die($p,'先回溯'); flash_revive($p); flash_keep($p,'stats');
foreach (flash_mines() as $mine) if ((int)$mine['pls']!==3) $db->query('DELETE FROM test_maptrap WHERE tid='.$mine['tid']);
$p['pls']=3; $ft_dice=99; flash_trap($p,true,true); unset($ft_dice); ft_reload($p);
ft_assert($p['hp']===0 && $p['clbpara']['quest']['active']['FL_job_disarm']['progress']===3 && !isset($p['clbpara']['quest']['completed']['FL_job_disarm']),'last lethal mine updates progress while waiting at death screen');
flash_revive($p); flash_keep($p,'stats'); ft_reload($p);
ft_assert(isset($p['clbpara']['quest']['completed']['FL_job_disarm']) && count($p['clbpara']['flash']['loot'])>=3,'last lethal mine reward arrives after manual revival');

// 首步侥幸躲开时仍在初见阶段，后续阶段内踩雷会正确结束它。
$p=ft_start(); $pdata=&$p;
for ($seed=0;$seed<1000;$seed++) { mt_srand($seed); if (mt_rand(1,100)>80) break; }
mt_srand($seed); flash_move($p,26);
ft_assert(!empty($p['clbpara']['flash']['events']['first_move']) && empty($p['clbpara']['flash']['events']['first_step']),'lucky first step does not falsely finish tutorial');
$ft_dice=99; flash_trap($p,true); unset($ft_dice);
ft_assert($p['clbpara']['flash']['events']['first_step'] && $p['clbpara']['flash']['pending'],'later tutorial mine ends phase');

// 两档探索逐次取物；耗尽、非法档位、体力不足均不多取/多扣。
$old_item_obbs=$item_obbs; $item_obbs=100;
foreach (array(3=>45,10=>100) as $count=>$cost) {
    $p=ft_start(); $pdata=&$p; $before=count($db->mapitem); flash_explore($p,$count); ft_reload($p);
    ft_assert(count($p['clbpara']['flash']['loot'])===$count && count($db->mapitem)===$before-$count && $p['sp']===400-$cost,'explore batch '.$count.' settles exact cost and results');
}
$p=ft_start(); $pdata=&$p; $db->mapitem=array(); flash_explore($p,10);
ft_assert(!$p['clbpara']['flash']['loot'] && $p['sp']===300,'empty map does not invent items');
$p['sp']=99; ft_reject(function()use(&$p){flash_explore($p,10);},'ten searches require one hundred SP');
ft_assert($p['sp']===99,'insufficient stamina does not partially charge');
ft_reject(function()use(&$p){flash_action($p,'explore','100');},'forged exploration batch rejected');
// 把三个真实雷布在同区，以确认触发一个后确实停止，而非只因没有第二枚雷。
$interrupted=false;
for ($seed=0;$seed<100 && !$interrupted;$seed++) {
    $p=ft_start(); $pdata=&$p; $p['pls']=3; $db->mapitem=array();
    for ($i=0;$i<10;$i++) $db->array_insert('test_mapitem',array_merge(flash_item('本批物品'.$i,'HH',10,1,'',array('batch'=>$i)),array('pls'=>3)));
    foreach ($db->maptrap as &$mine) $mine['pls']=3; unset($mine);
    $ft_dice=99; mt_srand($seed); flash_explore($p,10); unset($ft_dice);
    $found=$p['clbpara']['flash']['loot'];
    $interrupted=$p['clbpara']['flash']['pending'] && count($found)>0;
}
$item_obbs=$old_item_obbs;
ft_assert($interrupted && count($db->maptrap)===2 && count($found)<10 && count($db->mapitem)+count($found)===10,'lethal mine stops batch after one trap and retains earlier finds');
ft_reload($p); flash_tick($p); ft_assert($p['hp']===0,'exploration death still waits at death screen');
flash_revive($p); flash_keep($p,'stats'); ft_reload($p);
ft_assert($p['clbpara']['flash']['loot']===$found,'interrupted search items and metadata survive manual revival');
$p=ft_start(); $pdata=&$p; $p['pls']=3; $p['hp']=$p['mhp']=5000;
$db->mapitem=array(); for ($i=0;$i<10;$i++) $db->array_insert('test_mapitem',array_merge(flash_item('本批物品'.$i,'HH',10,1),array('pls'=>3)));
foreach ($db->maptrap as &$mine) $mine['pls']=3; unset($mine);
$before=count($db->mapitem); $ft_dice=99; mt_srand($seed-1); flash_explore($p,10); unset($ft_dice);
ft_assert($p['hp']>0 && count($db->maptrap)===2 && count($p['clbpara']['flash']['loot'])<10 && $before-count($db->mapitem)===count($p['clbpara']['flash']['loot']),'survived mine also stops batch after one trigger');

// 被动回复只结算真实经过的完整时间片，不复活死人、不累计等待选项的时间。
$p=ft_start(); $pdata=&$p; $p['hp']=$p['sp']=100;
$now+=9; flash_tick($p); ft_assert($p['hp']===100 && $p['sp']===100,'incomplete idle interval does not heal');
$now++; flash_tick($p); ft_reload($p); ft_assert($p['hp']===102 && $p['sp']===103,'idle interval heals both pools and persists');
flash_tick($p); ft_assert($p['hp']===102 && $p['sp']===103,'same timestamp cannot heal twice');
$now+=5; flash_tick($p); ft_reload($p); $now+=5; flash_tick($p);
ft_assert($p['hp']===104 && $p['sp']===106,'partial intervals survive frequent refreshes');
flash_pay($p,15); $now+=9; flash_tick($p); ft_assert($p['sp']===91,'action starts a fresh idle interval');
$now++; flash_tick($p); ft_assert($p['sp']===94,'new idle interval heals after action');
$p['hp']=$p['mhp']-1; $p['sp']=$p['msp']-1; $now+=10; flash_tick($p);
ft_assert($p['hp']===$p['mhp'] && $p['sp']===$p['msp'],'passive recovery respects pool limits');
flash_die($p,'等待也不复活'); $now+=100; flash_tick($p); ft_reload($p);
ft_assert($p['hp']===0 && $p['clbpara']['flash']['pending'],'idle refresh cannot bypass death screen');
flash_revive($p); $now+=100; flash_tick($p);
ft_assert($p['hp']===1 && $p['sp']===0,'retention choice does not accumulate healing');
flash_keep($p,'stats'); $p['hp']=$p['sp']=100; flash_tick($p);
ft_assert($p['hp']===100 && $p['sp']===100,'revival cannot cash in time spent dead');
unset($p['clbpara']['flash']['idle_at']); $now+=100; flash_tick($p);
ft_assert($p['hp']===100 && $p['sp']===100,'legacy save starts recovery clock without retroactive grant');
$now+=5; ft_command($p,array('command'=>'shop'));
ft_assert($p['clbpara']['flash']['idle_at']===$now-5,'browsing native menu preserves idle interval');
ft_command($p,array('command'=>'itemmove','from'=>'1','to'=>'5'));
ft_assert($p['clbpara']['flash']['idle_at']===$now,'native inventory action restarts idle interval');

echo 'FLASH: '.$ft_count." assertions passed.\n";
