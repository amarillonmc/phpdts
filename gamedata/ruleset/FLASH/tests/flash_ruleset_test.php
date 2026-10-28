<?php
if (!defined('IN_GAME')) exit('Access Denied');
if (!defined('GAME_ROOT')) define('GAME_ROOT',dirname(__DIR__,4).DIRECTORY_SEPARATOR);
if (!defined('CURSCRIPT')) define('CURSCRIPT','game');
// 独立内存数据库：使用正式物品、任务、战斗公式；不连接或重置部署数据。
class FlashTestResult { public $rows; function __construct($rows=array()) { $this->rows=array_values($rows); } }
class FlashTestDb {
    public $players=array(),$mapitem=array(),$maptrap=array(),$id=0,$last=0;
    function query($sql) {
        if (preg_match('/^SELECT \* FROM test_(players|mapitem|maptrap)(?: WHERE (.*))?$/',$sql,$m)) {
            $rows=$this->{$m[1]}; $where=$m[2]??'';
            $rows=array_filter($rows,function($r)use($where){
                foreach (preg_split('/ AND /',$where) as $part) {
                    if ($part==='') continue;
                    if (!preg_match("/^(\w+)\s*(=|>|<)\s*'?(-?\d+)'?$/",$part,$x)) throw new RuntimeException('Unsupported test filter: '.$part);
                    $value=$r[$x[1]]??0; $want=(int)$x[3];
                    if ($x[2]==='=' && $value!=$want || $x[2]==='>' && $value<=$want || $x[2]==='<' && $value>=$want) return false;
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
        $name=substr($table,5); $key=array('players'=>'pid','mapitem'=>'iid','maptrap'=>'tid')[$name];
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
if (!defined('FLASH_TEST_UI')) { function template($name) { return GAME_ROOT.'gamedata/ruleset/FLASH/cache/mapitem_1.php'; } }
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
    $GLOBALS['log']='';
    $GLOBALS['gamestate']=20; $GLOBALS['areanum']=0; $GLOBALS['gamevars']=array('flash'=>flash_room(1000));
    $GLOBALS['arealist']=array_merge(array(0),$GLOBALS['gamevars']['flash']['order']);
    $empty=null; flash_world_reset($empty,true);
    return flash_join(array('username'=>'旅人','password'=>'test-only','gender'=>'f','icon'=>1),'WK');
}
function ft_reload(&$p) { player_save($p); $p=fetch_playerdata_by_pid($p['pid']); $GLOBALS['pdata']=&$p; }

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
    flash_accept($p,'FL_recover_1'); $need=$p['clbpara']['quest']['active']['FL_recover_1']['need'];
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

$p=ft_start(); $pdata=&$p; flash_accept($p,'patrol'); flash_job_event($p,'minion',3); $before=$p['att']; flash_claim($p,'FL_job_patrol','stats');
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
$before=$p['clbpara']['quest']['active']; ft_reject(function()use(&$p){flash_accept($p,'FL_recover_fake');},'forged recovery rejected');
ft_assert($before===$p['clbpara']['quest']['active'],'forged recovery does not add active state');
ft_reject(function()use(&$p){flash_claim($p,'FL_job_collect','equipment_WX');},'forged weapon family rejected');

// 扫荡每名杂兵 30 SP，停在强敌/低血量/低体力前；时间流逝不恢复 SP。
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
$now+=100; flash_tick($p); ft_assert($p['sp']===5,'no passive stamina recovery');
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
$p['pls']=1; $p['hp']=400; $ft_dice=99; flash_trap($p,true); unset($ft_dice);
ft_assert($p['clbpara']['flash']['pending'] && $p['clbpara']['flash']['deaths_log'][1]['reason']==='阔剑地雷','native claymore triggers loop with readable cause');
ft_assert(!$p['itms0'],'trap processing clears temporary slot'); flash_tick($p); ft_reload($p);
ft_assert($p['hp']===0 && $p['pls']===1 && $p['state']===27,'claymore death persists before continue');
flash_revive($p); flash_keep($p,'stats'); $p['pls']=2;
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

echo 'FLASH: '.$ft_count." assertions passed.\n";
