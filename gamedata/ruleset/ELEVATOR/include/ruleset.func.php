<?php
if (!defined('IN_GAME')) exit('Access Denied');

require_once GAME_ROOT.'include/game/elevator.func.php';
require_once __DIR__.'/elevator.content.php';
require_once __DIR__.'/randnpc.func.php';
require_once __DIR__.'/randrecipe.func.php';
require_once __DIR__.'/elevator.world.php';
require_once __DIR__.'/elevator.events.php';
require_once __DIR__.'/elevator.objects.php';
require_once __DIR__.'/elevator.trade.php';
require_once __DIR__.'/elevator.actions.php';
require_once __DIR__.'/elevator.account.php';
require_once __DIR__.'/elevator.ui.php';

function ruleset_legacy_bot_allowed_hook() { return false; }
function ruleset_should_auto_gameover($alive,$context='') { return false; }
function ruleset_set_credits_hook() { return true; }
function ruleset_end_achievement_hook($owner,$winmode,$p) { return true; }
function ruleset_round_init_hook($mode) {
    global $gamevars,$starttime,$now,$db,$tablepre,$arealist,$areanum,$weather;
    if (!(($mode&4) || empty($gamevars['elevator']))) return;
    require_once GAME_ROOT.'include/game.func.php';
    elevator_ensure_schema(); $db->query("DELETE FROM {$tablepre}elevator_floor");
    $gamevars['elevator']=elevator_room($starttime?:$now);
    $e=&$gamevars['elevator'];
    foreach (array_merge(range(1,7),range(-1,-8)) as $floor) $e['plan'][$floor]=elevator_blueprint($floor);
    rr_create_recipes(elevator_config('recipe_count'));
    elevator_materialize_floor($e,1,$now);
    $merchant=rr_generate_npc(7,0,15,0); $merchant['type']=98; $merchant['pls']=9000; $merchant['pgroup']=250; $merchant['name']='神秘的兜售者';
    $merchant['clbpara']=array('elevator_merchant'=>true,'hostile'=>0); elevator_insert_npc($merchant,$now);
    $arealist=array(0); $areanum=0; $weather=0; elevator_refresh_maps(); save_gameinfo();
}
function ruleset_round_clock_hook() {
    global $gamestate,$gamevars;
    require_once GAME_ROOT.'include/game.func.php';
    elevator_ensure_schema();
    if ($gamestate>=20) elevator_request_tick();
    else if (!empty($gamevars['elevator'])) elevator_refresh_maps();
    return true;
}
function ruleset_command_request_begin_hook() {
    if (!elevator_lock()) return false;
    elevator_request_tick(); return true;
}
function ruleset_game_page_hook() {
    if (!elevator_lock()) gexit('电梯繁忙，请稍后重试。',__FILE__,__LINE__);
    elevator_request_tick(); return false;
}
function ruleset_player_init_hook(&$p) {
    foreach (array('pid','pls','pgroup','type') as $key) $p[$key]=(int)$p[$key];
    elevator_player_maps($p);
}
function ruleset_command_page_hook() {
    if (($_POST['command']??'')!=='elevator_status') return false;
    global $gamevars,$now,$gamestate;
    try {
        elevator_authorized(); if (!elevator_lock()) throw new RuntimeException('房间繁忙，请稍后重试。');
        elevator_request_tick();
        $players=elevator_read_players(); $p=null;
        foreach ($players as $row) if ($row['name']===$GLOBALS['cuser']) $p=$row;
        if (!$p) throw new RuntimeException('请先加入本次行程。');
        if (!is_string($_POST['el_csrf']??null) || !hash_equals($p['clbpara']['elevator']['csrf'],$_POST['el_csrf'])) throw new RuntimeException('请刷新游戏页面。');
        $p['clbpara']['elevator']['last_seen']=$now; player_save($p);
        $response=array('innerHTML'=>array('elevator-panel'=>elevator_panel($p,$gamevars['elevator'],$now)));
        if (isset($_POST['el_pls']) && is_scalar($_POST['el_pls']) && (int)$_POST['el_pls']!==(int)$p['pls']) $response['url']='game.php';
        if (!elevator_active($p) || !$gamestate) $response['url']='end.php';
    } catch (RuntimeException $ex) { $response=array('innerHTML'=>array('notice'=>elevator_html($ex->getMessage()))); }
    ob_clean(); header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
    echo json_encode($response,JSON_UNESCAPED_UNICODE); return true;
}
function ruleset_command_prepare_hook(&$p,$command) {
    global $log,$now,$gamevars,$mode,$moveto;
    $p['clbpara']['elevator']['last_seen']=$now;
    try {
        elevator_validate_request($p);
        if (!elevator_active($p)) throw new RuntimeException('本次行程已结束。');
        if (strpos($command,'elevator_')===0) {
            $raw=$_POST['el_args']??'{}';
            if (!is_string($raw) || strlen($raw)>3000) throw new RuntimeException('指令参数无效。');
            $a=json_decode($raw,true); if (!is_array($a)) throw new RuntimeException('指令参数无效。');
            foreach ($a as $v) if (!is_scalar($v)) throw new RuntimeException('指令参数无效。');
            elevator_action($gamevars['elevator'],$p,$command,$a,$now);
            return false;
        }
        if (($p['clbpara']['elevator']['stun_until']??0)>$now) throw new RuntimeException('短暂的眩晕还未散去。');
        if ($command==='move') {
            if (!is_scalar($moveto) || !elevator_check_move($p,(int)$moveto)) throw new RuntimeException('只能在当前楼层探索；换层请使用电梯。');
        }
        if ($command==='search' && (!empty($p['clbpara']['elevator']['in']) || $p['clbpara']['elevator']['floor']===-8 || (int)$p['pls']===elevator_pls($p['clbpara']['elevator']['floor']))) throw new RuntimeException('这里是候梯区。请从面板进入楼层，或与身旁的乘客交谈。');
        // 房间外的支援、原生仓库、赠送和结局道具不适用于独立楼层；车厢面板提供本模式的交易。
        if (preg_match('/^(sp_shop|shop|depot|senditem|corpse_depot|team|sp_weapon|song|hack|hackmove|club22|club23)/',$command) || in_array($mode,array('senditem','shop','depot'),true)) throw new RuntimeException('请使用车厢内的行商、交易与个人保管箱。');
        if (!empty($p['bid']) && in_array($mode,array('revcombat','combat','corpse'),true)) {
            $enemy=fetch_playerdata_by_pid((int)$p['bid']);
            if (!$enemy || (int)$enemy['pls']!==(int)$p['pls'] || !$enemy['type']) throw new RuntimeException('目标已不在这里，或不能成为战斗目标。');
        }
        return true;
    } catch (RuntimeException $ex) { $log.='<span class="yellow">'.elevator_html($ex->getMessage()).'</span><br>'; return false; }
}
function ruleset_command_end_hook(&$p,$command) {
    global $gamevars,$now;
    $e=&$gamevars['elevator'];
    $players=elevator_read_players(); $before=$players; $players[$p['pid']]=$p;
    if ($e['event'] && !empty($e['event']['npcs'])) {
        $alive=false;
        foreach ($e['event']['npcs'] as $id) { $npc=fetch_playerdata_by_pid($id); if ($npc && $npc['hp']>0) $alive=true; }
        if (!$alive) elevator_intrusion_finish($e,$players,$now,true);
    }
    elevator_tick($e,$players,$now);
    $p=$players[$p['pid']];
    elevator_save_players($players,$before); elevator_collect_empty_floors($e,$players); elevator_refresh_maps(); save_gameinfo();
    foreach ($players as $person) if (!empty($person['clbpara']['elevator']['result'])) elevator_record_result($person);
}
function ruleset_command_post_save_hook(&$p,$command) {
    global $gamedata,$gamevars,$now;
    $p['clbpara']=elevator_decode($p['clbpara']);
    $gamedata['innerHTML']['elevator-panel']=elevator_panel($p,$gamevars['elevator'],$now);
    elevator_player_maps($p);
    // 锁由PHP请求结束释放，渲染也不能把旧状态带回新的指令。
}
function elevator_player_maps($p) {
    global $hplsinfo;
    if (empty($p['clbpara']['elevator'])) return;
    $base=elevator_pls($p['clbpara']['elevator']['floor']);
    foreach ($hplsinfo[251]??array() as $key=>$label) if ($key<$base || $key>$base+15) unset($hplsinfo[251][$key]);
}
function ruleset_move_allowed_hook(&$p,$dest) { return elevator_check_move($p,(int)$dest); }
function ruleset_move_search_success_hook(&$p,$command,$forced=false) {
    global $now,$gamevars;
    if (!$forced) {
        elevator_record_exploration($p,$now);
        if ($command==='move') $gamevars['elevator']['attention']++;
    }
}
function ruleset_encounter_allowed_hook($p,$enemy) { return elevator_can_fight($p,$enemy,true); }
function elevator_can_fight($a,$b,$corpse=false) {
    global $gamevars;
    $a['clbpara']=elevator_decode($a['clbpara']); $b['clbpara']=elevator_decode($b['clbpara']);
    if ((int)$a['pls']!==(int)$b['pls'] || (!$a['type'] && !$b['type'])) return false;
    foreach (array($a,$b) as $p) {
        if (empty($p['type']) && !elevator_active($p)) return false;
        $npc=$p['clbpara']['elevator_npc']??array();
        if (isset($npc['owner']) && !in_array((int)$npc['owner'],array((int)$a['pid'],(int)$b['pid']),true)) return false;
    }
    if ((int)$a['pls']===elevator_pls(-8)) return false;
    if ((int)$a['pls']===9000) {
        if ($gamevars['elevator']['floor']===-8) return false;
        $npc=$a['type']?$a:$b;
        if ((int)$npc['type']===98) return !empty($npc['clbpara']['hostile']);
        return (int)$npc['type']===97 && !empty($gamevars['elevator']['event']['npcs']) && in_array((int)$npc['pid'],$gamevars['elevator']['event']['npcs'],true);
    }
    return in_array((int)($a['type']?:$b['type']),array(96,97,98),true);
}
function ruleset_combat_allowed_hook(&$a,&$b) { return elevator_can_fight($a,$b); }
function ruleset_force_player_initiative_hook($enemy,$player=array()) { return true; }
function ruleset_damage_immunity_hook(&$p,$source,$damage) {
    if (!empty($p['clbpara']['elevator']['in']) || (int)$p['pls']===elevator_pls(-8)) return 0;
    return null;
}
function ruleset_natural_death_hook(&$p,$death,$name='',$type=0,$annex='') {
    global $now,$gamevars;
    if (empty($p['type']) && !empty($p['clbpara']['elevator'])) { elevator_death($p,$now,$gamevars['elevator']); return true; }
    return null;
}
function ruleset_revive_process_hook(&$a,&$b,$active) {
    global $now,$gamevars;
    if (!empty($b['clbpara']['elevator']['result'])) return 1;
    if (!$b['type'] && !empty($b['clbpara']['elevator'])) return elevator_death($b,$now,$gamevars['elevator'])?1:0;
    return 0;
}
function ruleset_final_damage_fix_hook(&$a,&$b,$active,$damage) {
    if (!elevator_can_fight($a,$b)) return 0;
    return null;
}
function ruleset_attribute_value_hook($p,$kind,$value) {
    global $now;
    if (($p['clbpara']['elevator']['blessing_until']??0)>$now) $value*=1.1;
    if ($kind==='att' && ($p['clbpara']['elevator']['buff_until']??0)>$now) $value*=1.1;
    if ($kind==='hit' && ($p['clbpara']['elevator']['blind_until']??0)>$now) $value*=.8;
    return $value;
}
function ruleset_combat_result_hook(&$a,&$b,$active) {
    global $log;
    if (($b['clbpara']['elevator_npc']['object']??0)===803 && $b['hp']<=$b['mhp']/2) {
        $b['hp']=1; $b['pls']=0; $b['action']=''; $b['bid']=0; $a['action']=''; $a['bid']=0;
        $log.='半成品沉入池底，没有留下遗物。<br>'; return 0;
    }
    return null;
}
function ruleset_battle_finish_hook(&$a,&$b) {
    global $now,$gamevars,$db,$tablepre,$log;
    foreach (array(0,1) as $side) {
        if ($side===0) { $npc=&$a; $player=&$b; } else { $npc=&$b; $player=&$a; }
        $meta=&$npc['clbpara']['elevator_npc'];
        if ($npc['hp']<=0 || ($meta['object']??0)!==502 || !empty($meta['child'])) continue;
        $meta['rounds']++;
        if ($meta['rounds']<elevator_config('tap_turns')) {
            $child=rr_generate_npc(1); $child['name']='小水龙头'; $child['hp']=$child['mhp']=30;
            $child['att']=15; $child['wepe']=10; $child['pls']=$npc['pls']; $child['money']=0;
            $child['clbpara']['elevator_npc']=array('object'=>502,'child'=>true,'floor'=>$meta['floor']);
            for ($i=1;$i<=6;$i++) elevator_set_slot($child,$i);
            elevator_insert_npc($child,$now);
            $log.='又一只小水龙头落地。必须在第'.elevator_config('tap_turns').'轮结束前击败原始体！<br>';
        } else {
            $all=elevator_read_players();
            foreach ($all as &$row) if (elevator_active($row) && !$row['clbpara']['elevator']['in'] && $row['clbpara']['elevator']['floor']===$meta['floor']) {
                elevator_entrance($row,$meta['floor'],$now,false); player_save($row);
            }
            unset($row);
            if (!$player['type']) elevator_entrance($player,$meta['floor'],$now,false);
            $npc['hp']=0; $npc['pls']=0; $npc['action']=''; $npc['bid']=0;
            elevator_notice($gamevars['elevator'],'复制水龙头淹没了'.elevator_floor_label($meta['floor']).'，所有探索者被水流推回电梯口。',$now);
            $log.='水漫过整个楼层，你被冲回电梯口。<br>';
        }
    }
    unset($npc,$player,$meta);
}
function ruleset_player_kill_hook(&$a,&$b,$active) {
    if (!empty($a['type']) || empty($a['clbpara']['elevator']) || !empty($b['clbpara']['elevator_rewarded'])) return;
    $b['clbpara']['elevator_rewarded']=true;
    $meta=$b['clbpara']['elevator_npc']??array(); $id=$meta['object']??0;
    if (!$id || !empty($meta['child'])) return;
    global $gamevars;
    $a['clbpara']['elevator']['floors'][$meta['floor']]['objects'][$id]['done']=true;
    $drops=array(204=>'武器零件',402=>'旧红杀臂章',601=>'镜子碎片',801=>'「Query failed」日志');
    if ($id===205) {
        $pool=array(); foreach (elevator_catalog() as $key=>$spec) if ($spec['pool']===2 && !$spec['unique']) $pool[]=$key;
        elevator_set_slot($b,1,elevator_item(rr_pick($pool),$gamevars['elevator']['round']));
    } elseif (isset($drops[$id])) {
        $itemid=elevator_item_id($drops[$id]);
        if ($itemid) elevator_set_slot($b,1,elevator_item($itemid,$gamevars['elevator']['round']));
        else elevator_set_slot($b,1,array('itm'=>$drops[$id],'itmk'=>'X','itme'=>1,'itms'=>1));
    } else elevator_set_slot($b,1,elevator_relic($meta['floor'],$gamevars['elevator']['round']));
    if ($id===601) {
        // 镜像保留战斗属性，击碎后只剩碎片，不能复制自己的整套装备。
        foreach (array('wep','wep2','arb','arh','ara','arf','art') as $slot) foreach (array('','k','e','s','sk','para') as $suffix) $b[$slot.$suffix]=in_array($suffix,array('e','s'))?0:'';
    }
}
function ruleset_itemget_hook(&$p) {
    $item=elevator_slot($p,0);
    if (($item['itmpara']['elevator_item']??0)===elevator_item_id('种火语密码手册') || !empty($item['itmpara']['elevator_dictionary'])) $p['clbpara']['elevator']['dictionary']=true;
}
function ruleset_itemuse_hook(&$p,$slot,$item) {
    global $gamevars,$log,$now;
    $item['itmpara']=elevator_decode($item['itmpara']); $e=$gamevars['elevator'];
    try {
        if ($item['itmk']==='R') {
            if (($item['itmpara']['elevator_round']??'')!==$e['round']) throw new RuntimeException('蓝图不属于本次行程。');
            $p['clbpara']['elevator']['view']=array('kind'=>'recipes','key'=>(int)$item['itmsk'],'slot'=>$slot); return true;
        }
        if ($item['itmk']==='ER') { elevator_open_relic($p,$slot,$e); return true; }
        if (!empty($item['itmpara']['elevator_dictionary'])) {
            $p['clbpara']['elevator']['dictionary']=true; elevator_personal($p,'你把种火语字母表记进笔记。'); return true;
        }
        if ($item['itmk']==='X' && !empty($item['itmpara']['elevator_item'])) {
            $id=$item['itmpara']['elevator_item']; $p['clbpara']['elevator']['notes'][$id]=true;
            elevator_personal($p,elevator_item_text($id));
            if ($id===elevator_item_id('种火语密码手册')) $p['clbpara']['elevator']['dictionary']=true;
            if ($id===elevator_item_id('旋转银白盒屏保程序')) { $p['sp']=min($p['msp'],$p['sp']+30); elevator_consume($p,$slot); }
            return true;
        }
        if (!preg_match('/^(W|D|A|H|P|G|M|V|C)/',$item['itmk'])) throw new RuntimeException('这件道具无法在电梯行程中使用。');
        return null;
    } catch (RuntimeException $ex) { $log.=elevator_html($ex->getMessage()).'<br>'; return true; }
}
function ruleset_death_page_copy_hook($state) {
    if ((int)$state===64) return array('time_label'=>'离场时间','status'=>'你已成功脱出。','button'=>'查看电梯结算');
    if ((int)$state===65) return array('time_label'=>'行程结束','status'=>'本次电梯行程已结束。','button'=>'查看电梯结算');
    return null;
}
function ruleset_format_news_hook($news,$time,$a,$b,$c,$d,$e) {
    if ($news!=='end8') return null;
    return '<li>'.date('H:i:s',$time).'，电梯的门关上了，再也不会打开。这栋楼记得每一个来过的人。</li>';
}
function ruleset_valid_page_hook() { elevator_join_page(); return true; }
function ruleset_ending_page_hook() { elevator_ending_page(); return true; }
