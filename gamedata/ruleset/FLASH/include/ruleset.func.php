<?php
if (!defined('IN_GAME')) exit('Access Denied');
require_once __DIR__.'/flash.content.php';
require_once __DIR__.'/flash.world.php';
require_once __DIR__.'/flash.state.php';
require_once __DIR__.'/flash.combat.php';
require_once __DIR__.'/flash.actions.php';
require_once __DIR__.'/flash.ui.php';

function ruleset_legacy_bot_allowed_hook() { return false; }
function ruleset_should_auto_gameover($alive,$context='') { return false; }
function ruleset_set_credits_hook() { return true; }
function ruleset_end_achievement_hook($owner,$winmode,$p) { return true; }
function ruleset_format_news_hook($news,$time,$a,$b,$c,$d,$e) {
    $ends=array('end1'=>'幻境与旅人一起，爆发四散。','end3'=>'门在身后合拢。新的一天开始了。','end5'=>'人流中，一个装扮成猫娘的身影与你擦肩而过。');
    return isset($ends[$news])?'<li>'.date('H:i:s',(int)$time).'，<span class="yellow">FLASH · '.$ends[$news].'</span></li>':null;
}
function ruleset_round_init_hook($mode) {
    global $gamevars,$starttime,$now,$arealist,$areanum,$weather;
    if (!(($mode&4) || empty($gamevars['flash']))) return;
    require_once GAME_ROOT.'include/game.func.php';
    require_once GAME_ROOT.'include/game/quest.func.php';
    $gamevars['flash']=flash_room($starttime?:$now); $areanum=0;
    $arealist=array_merge(array(0),$gamevars['flash']['order']); $weather=0;
    $empty=null; flash_world_reset($empty,true); save_gameinfo();
}
function ruleset_round_clock_hook() {
    global $gamevars,$gamestate,$now;
    if (empty($gamevars['flash']) || $gamestate<20) return true;
    require_once GAME_ROOT.'include/game.func.php'; require_once GAME_ROOT.'include/game/quest.func.php';
    $p=flash_read_player();
    if ($p) {
        $reset=!empty($p['clbpara']['flash']['reset_world']); flash_tick($p);
        if (!empty($p['clbpara']['flash']['result'])) flash_settle($p);
        elseif ($reset) player_save($p);
    }
    elseif ($now >= $gamevars['flash']['start']+flash_cfg('seconds')) gameover($now,'end4');
    return true;
}
function ruleset_command_request_begin_hook() { return flash_lock(); }
function ruleset_valid_page_hook() { flash_http('join'); return true; }
function ruleset_game_page_hook() { flash_http('game'); return true; }
function ruleset_command_page_hook() { flash_http('command'); return true; }
function ruleset_ending_page_hook() { flash_http('end'); return true; }

function flash_read_player() {
    global $db,$tablepre;
    $q=$db->query("SELECT * FROM {$tablepre}players WHERE type=0");
    $p=$db->fetch_array($q);
    if ($p) { $p['clbpara']=get_clbpara($p['clbpara']); $p['pls']=(int)$p['pls']; $p['type']=(int)$p['type']; $p['pid']=(int)$p['pid']; }
    return $p;
}
function flash_authorized() {
    global $udata,$cuser,$cpass;
    if (empty($udata) || empty($cuser) || empty($cpass) || $udata['username']!==$cuser || !hash_equals((string)$udata['password'],(string)$cpass) || $udata['groupid']<=0) throw new RuntimeException('请先登录再进入闪回。');
    return $udata;
}
function flash_join_token($user) { return hash_hmac('sha256','FLASH:'.$GLOBALS['groomid'].':'.$GLOBALS['gamevars']['flash']['start'].':'.$user['username'],(string)$user['password']); }
function flash_join($user,$kind) {
    global $gamevars,$gamestate,$now,$db,$tablepre,$validnum,$alivenum;
    if ($gamestate<20 || $now>=$gamevars['flash']['start']+flash_cfg('seconds')) throw new RuntimeException('请等待下一次醒来。');
    if (flash_read_player()) throw new RuntimeException('这是单人旅程，本局已经有人醒来了。');
    if (!in_array($kind,array('WP','WK','WG','WC','WD','WF'),true)) throw new RuntimeException('请选择一种起始武器。');
    $p=create_dummy_playerdata();
    $p=array_merge($p,array('name'=>$user['username'],'pass'=>$user['password'],'type'=>0,'gd'=>in_array($user['gender']??'',array('m','f'))?$user['gender']:'m',
        'icon'=>$user['icon']??0,'sNo'=>1,'validtime'=>$now,'endtime'=>$now,'pls'=>0,'pgroup'=>0,'money'=>300,'pose'=>1,'tactic'=>2,'club'=>0,'itmnumlimit'=>6));
    $p['clbpara']=array('flash'=>flash_state(),'skill'=>array(),'skillpara'=>array(),'bgmbook'=>array(),'BGMBrand'=>'','randver1'=>mt_rand(0,255),'randver2'=>mt_rand(0,255),'charge1'=>0,'charge2'=>0,'charge3'=>0,'charge4'=>0);
    foreach (flash_baseline($p) as $key=>$value) $p[$key]=$value;
    $p['hp']=$p['mhp']; $p['sp']=$p['msp']; quest_init_state($p['clbpara']);
    flash_set_equipment($p,'wep',flash_item('醒来时攥着的'.array('WP'=>'木棍','WK'=>'短刀','WG'=>'手枪','WC'=>'飞镖','WD'=>'爆破包','WF'=>'音叉')[$kind],$kind,80,200));
    flash_set_equipment($p,'wep2',flash_item('拳头','WN',0,'∞'));
    flash_set_equipment($p,'arb',flash_item('旧外套','DB',30,100));
    flash_set_item($p,1,flash_item('留在枕边的水','HB',200,6));
    flash_set_item($p,2,flash_item('混沌人肉探雷车','Y',1,1));
    flash_set_item($p,3,flash_item('万能药','Ca',1,3));
    $row=player_format_with_db_structure($p); unset($row['pid']); $db->array_insert($tablepre.'players',$row);
    $p['pid']=(int)$db->insert_id(); $gamevars['flash']['owner']=$p['pid']; $validnum=$alivenum=1;
    save_gameinfo(); return $p;
}
function flash_validate_command($p,$post) {
    foreach (array('command','flash_csrf','flash_rev') as $key) if (!isset($post[$key]) || !is_string($post[$key])) throw new RuntimeException('请刷新页面后重新操作。');
    if (!hash_equals($p['clbpara']['flash']['csrf'],$post['flash_csrf'])) throw new RuntimeException('请从当前游戏页面操作。');
    if (!ctype_digit($post['flash_rev']) || (int)$post['flash_rev']!==$p['clbpara']['flash']['revision']) throw new RuntimeException('刚才的操作已经处理，界面已更新。');
    foreach (array('arg','extra') as $key) if (isset($post[$key]) && (!is_string($post[$key]) || strlen($post[$key])>80)) throw new RuntimeException('行动参数无效。');
    if (!preg_match('/^flash_([a-z_]+)$/D',$post['command'],$m)) throw new RuntimeException('请使用闪回的行动面板。');
    $action=$m[1]; $arg=$post['arg']??'';
    if (in_array($action,array('move','track','use','feed','drop','take','loot_use','loot_feed'),true) && !ctype_digit($arg)) throw new RuntimeException('行动对象无效。');
    return array($action,$arg,$post['extra']??'');
}
function flash_http($kind) {
    global $now,$gamevars,$gamestate,$log,$pdata;
    require_once GAME_ROOT.'include/game.func.php'; require_once GAME_ROOT.'include/game/quest.func.php';
    $p=null; $error=''; $user=null; $log='';
    try {
        $user=flash_authorized();
        if (!flash_lock()) throw new RuntimeException('这一次闪回尚未结束，请稍后重试。');
        if (empty($gamevars['flash'])) throw new RuntimeException('房间正在准备，请稍后再来。');
        $p=flash_read_player();
        if ($p && $p['name']!==$user['username']) { $p=null; throw new RuntimeException('这是一段属于另一位旅人的单人旅程。请从大厅另开一间 FLASH。'); }
        if ($p) { $pdata=&$p; flash_tick($p); }
        if ($kind==='join' && !$p && ($_SERVER['REQUEST_METHOD']??'GET')==='POST') {
            if (!is_string($_POST['flash_csrf']??null) || !hash_equals(flash_join_token($user),$_POST['flash_csrf'])) throw new RuntimeException('请刷新开局页面后重试。');
            $weapon=$_POST['weapon']??''; if (!is_string($weapon)) throw new RuntimeException('起始武器无效。');
            $p=flash_join($user,$weapon); $pdata=&$p;
        }
        if ($p && $kind==='command') {
            if (($_SERVER['REQUEST_METHOD']??'')!=='POST') throw new RuntimeException('请通过行动按钮提交。');
            list($action,$arg,$extra)=flash_validate_command($p,$_POST);
            if ($action!=='status') {
                $p['clbpara']['flash']['revision']++; $p['clbpara']['flash']['clicks']++; $p['clbpara']['flash']['stage_clicks']++;
                flash_action($p,$action,$arg,$extra);
            }
        }
        if ($p) flash_settle($p);
    } catch (RuntimeException $e) {
        $error=$e->getMessage();
        // 验证后才修改；若结算已经发生，仍保存已发生的事实，不回退回溯/消耗。
        if ($p) flash_settle($p);
    }
    if ($p && $log!=='') {
        $lines=explode("\n",strip_tags(preg_replace('/<br\s*\/?\s*>/i',"\n",$log)));
        $p['clbpara']['flash']['last_log']=implode("\n",array_slice($lines,-100)); player_save($p);
    }
    if ($kind==='command') {
        if (ob_get_level()) ob_clean(); header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
        echo json_encode(array('html'=>flash_panel($p,$error),'revision'=>$p?$p['clbpara']['flash']['revision']:0),JSON_UNESCAPED_UNICODE); return;
    }
    $flash_body=$p?flash_panel($p,$error):flash_join_panel($user,$error);
    if (ob_get_level()) ob_clean(); header('Cache-Control: no-store'); include template('flash');
}
