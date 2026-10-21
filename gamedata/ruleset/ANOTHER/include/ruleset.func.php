<?php
if (!defined('IN_GAME')) exit('Access Denied');

require_once __DIR__.'/another.config.php';
require_once __DIR__.'/another.talents.php';
require_once __DIR__.'/another.items.php';
require_once __DIR__.'/another.combat.php';
require_once __DIR__.'/another.core.php';
require_once __DIR__.'/another.actions.php';
require_once __DIR__.'/another.account.php';

function ruleset_round_init_hook($mode) {
    global $now;
    if($mode & (4|8|16)) another_seed_world($now,$mode);
}

function ruleset_round_clock_hook() {
    global $gamestate,$now,$starttime,$areanum,$arealist,$areatime,$weather;
    if($gamestate<20) return true;
    if (!function_exists('create_dummy_playerdata')) require_once GAME_ROOT.'include/game.func.php';
    $room=another_read_room(); $players=another_read_players();
    $old_room=$room; $old_players=$players;
    another_tick($room,$players,$now,$starttime);
    if(empty($room['result'])) another_spawn_mirrors($room,$players,$now,$starttime);
    $arealist=array_merge(array(0),$room['closed']); $areanum=count($room['closed']);
    $areatime=$starttime+another_config()['deadline'];
    foreach(another_config()['zones'] as $seconds=>$maps) if($starttime+$seconds>$now) { $areatime=$starttime+$seconds; break; }
    if(!empty($room['entry_closed'])) $gamestate=30;
    if(isset($room['weather'])) $weather=$room['weather'];
    another_save_world($room,$players,$old_room,$old_players);
    another_record_result($room,$players);
    return true;
}

function ruleset_get_team_winners_hook($winmode,$winner_data) {
    global $gamevars;
    $names=array();
    $players=another_read_players();
    foreach($gamevars['another']['result']['winners']??array() as $id) if(isset($players[$id])) {
        $name=another_owner($players[$id]);
        if($name!==''&&!in_array($name,$names,true)) $names[]=$name;
    }
    return $names;
}
function ruleset_should_auto_gameover($alive,$context='') { return false; }

function ruleset_valid_page_hook() { another_http_page('join'); return true; }
function ruleset_game_page_hook() { another_http_page('game'); return true; }
function ruleset_command_page_hook() { another_http_page('command'); return true; }
function ruleset_ending_page_hook() { another_http_page('game'); return true; }

function another_http_page($surface) {
    global $now,$starttime,$gamestate;
    if (!function_exists('create_dummy_playerdata')) require_once GAME_ROOT.'include/game.func.php';
    require_once __DIR__.'/another.ui.php';
    try { $user=another_authorized_user(); another_lock(); }
    catch(RuntimeException $e) { http_response_code(403); echo another_html($e->getMessage()); return; }
    $room=another_read_room(); $players=another_read_players();
    $old_room=$room; $old_players=$players; $error='';
    if($gamestate>=20) another_tick($room,$players,$now,$starttime);
    $ids=another_current_player_ids($players,$user['username']);
    $input=$_POST;
    $is_post=isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD']==='POST';
    $csrf=another_csrf($user);
    $primary=$ids?$ids[0]:0;
    $seq=$primary?(int)($players[$primary]['clbpara']['another']['request_seq']??0):0;
    $active=$primary?(int)($players[$primary]['clbpara']['another']['active_pid']??$primary):0;
    if(!in_array($active,$ids,true)) $active=$primary;
    if($active && $players[$active]['hp']<=0) foreach($ids as $id) if($players[$id]['hp']>0) { $active=$id; break; }
    if($is_post) {
        try {
            if(!isset($input['csrf']) || !is_string($input['csrf']) || !hash_equals($csrf,$input['csrf'])) throw new RuntimeException('页面已过期，请刷新后重试。');
            if(!$primary && $surface==='join') {
                if($gamestate<20) throw new RuntimeException('对局尚未开始。');
                another_join($user,$input,$room,$players,$now);
                $ids=another_current_player_ids($players,$user['username']); $primary=$active=$ids[0];
            } elseif($primary && $surface==='command') {
                if(!isset($input['seq']) || !is_scalar($input['seq']) || (string)$seq !== (string)$input['seq']) throw new RuntimeException('状态已经更新，这条重复或过期指令没有执行。');
                $players[$primary]['clbpara']['another']['request_seq']=++$seq;
                $action=isset($input['action'])&&is_string($input['action'])?$input['action']:'';
                if($action==='switch') {
                    $to=isset($input['target_pid'])&&is_scalar($input['target_pid'])?(int)$input['target_pid']:0;
                    if(!in_array($to,$ids,true)) throw new RuntimeException('不能操纵这个角色。');
                    $active=$to;
                } else {
                    foreach($input as $value) if(!is_scalar($value)) throw new RuntimeException('指令参数无效。');
                    $messages=array();
                    $now_ms=$now*1000+(int)floor((microtime(true)-floor(microtime(true)))*1000);
                    another_action($room,$players,$active,$action,$input,$now,$messages,$now_ms);
                    foreach($messages as $message) another_personal_notice($players[$active],$message,$now);
                }
            }
        } catch(RuntimeException $e) { $error=$e->getMessage(); }
    }
    if($primary) $players[$primary]['clbpara']['another']['active_pid']=$active;
    another_save_world($room,$players,$old_room,$old_players);
    another_record_result($room,$players);
    $csrf=another_csrf($user);
    $body=another_render_body($room,$players,$user['username'],$active,$seq,$csrf,$now,!$primary,$error);
    if($surface==='command') {
        if(ob_get_level()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(array('html'=>$body,'seq'=>$seq,'csrf'=>$csrf,'ended'=>!empty($room['result'])),JSON_UNESCAPED_UNICODE);
    } elseif($surface==='join' && $primary && $is_post && !$error) {
        header('Location: game.php');
    } else another_render_page($body);
}
