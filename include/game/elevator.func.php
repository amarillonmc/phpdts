<?php
if (!defined('IN_GAME')) exit('Access Denied');

require_once GAME_ROOT.'gamedata/ruleset/ELEVATOR/include/elevator.config.php';

function elevator_decode($value) {
    if (is_array($value)) return $value;
    $value = json_decode((string)$value,true);
    return is_array($value) ? $value : array();
}
function elevator_html($value) { return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8'); }
function elevator_sql($value) { return "X'".bin2hex((string)$value)."'"; }
function elevator_room($now) {
    return array('on'=>true,'state'=>'idle','floor'=>1,'dir'=>0,'route'=>array(),'reqs'=>array(),
        'door'=>'closed','door_deadline'=>0,'travel_end'=>0,'phase'=>'finite','endless_vote'=>null,
        'start_time'=>$now,'limit'=>elevator_config('limit_finite'),'event'=>null,'attention'=>0,
        'stops'=>0,'last_event_stop'=>-2,'last_event_time'=>0,'last_stop'=>$now,'last_tick'=>$now,
        'unique_items'=>array(),'plan'=>array(),'news'=>array(),'joined'=>0,'round'=>bin2hex(random_bytes(12)));
}
function elevator_player_state($floor,$now) {
    return array('on'=>true,'in'=>false,'floor'=>$floor,'budget'=>elevator_budget($floor),
        'budget_start'=>$now,'protected'=>true,'protect_until'=>$now+elevator_config('protection'),
        'board_req'=>null,'revive'=>1,'deaths'=>0,'result'=>null,'endless_opt'=>null,'last_seen'=>$now,
        'seq'=>0,'csrf'=>bin2hex(random_bytes(16)),'floors'=>array(),'deepest'=>0,'depth_time'=>0,
        'rides'=>0,'cats'=>0,'attic'=>$floor===7,'fireseed'=>$floor===-8,'endpoint'=>abs($floor)===7,
        'fragments'=>array(),'box'=>array(),'notes'=>array(),'pending'=>array());
}
function elevator_active($p) {
    $c=elevator_decode($p['clbpara']??'');
    return empty($p['type']) && !empty($c['elevator']['on']) && empty($c['elevator']['result']);
}
function elevator_notice(&$e,$message,$at) {
    $e['news'][] = array('time'=>$at,'text'=>$message);
    $e['news'] = array_slice($e['news'],-18);
}
function elevator_personal(&$p,$message) {
    $p['clbpara']['elevator']['messages'][] = $message;
    $p['clbpara']['elevator']['messages'] = array_slice($p['clbpara']['elevator']['messages'],-10);
}
function elevator_lock() {
    global $plock,$elevator_lock_handle;
    if (is_resource($plock) || is_resource($elevator_lock_handle)) return true;
    $elevator_lock_handle = @fopen(GAME_ROOT.'gamedata/process.lock','ab');
    if (!$elevator_lock_handle || !flock($elevator_lock_handle,LOCK_EX)) {
        if (is_resource($elevator_lock_handle)) fclose($elevator_lock_handle);
        $elevator_lock_handle=null; return false;
    }
    load_gameinfo();
    return true;
}
function elevator_read_players() {
    global $db,$tablepre;
    $all = array();
    $q = $db->query("SELECT * FROM {$tablepre}players WHERE type=0");
    while ($p = $db->fetch_array($q)) {
        foreach (array('pid','pls','pgroup','type') as $key) $p[$key]=(int)$p[$key];
        $p['clbpara'] = elevator_decode($p['clbpara']);
        if (!empty($p['clbpara']['elevator'])) $all[(int)$p['pid']] = $p;
    }
    return $all;
}
function elevator_save_players($players,$before) {
    global $validnum,$alivenum,$deathnum;
    $alivenum = 0;
    foreach ($players as $id=>$p) {
        if (elevator_active($p)) $alivenum++;
        if (!isset($before[$id]) || $before[$id] != $p) {
            // 当前玩家经EXTR_REFS与页面变量相连；原生格式化会把clbpara改成JSON。
            $save=array(); foreach ($p as $key=>$value) $save[$key]=$value;
            player_save($save);
        }
    }
    $validnum = count($players); $deathnum = $validnum-$alivenum;
}
function elevator_entrance(&$p,$floor,$at,$protect = false) {
    $s = &$p['clbpara']['elevator'];
    if ($s['floor'] !== $floor) {
        $s['floors'][$s['floor']]['budget'] = $s['budget'];
        $s['budget'] = isset($s['floors'][$floor]['budget']) ? $s['floors'][$floor]['budget'] : elevator_budget($floor);
        $s['floor'] = $floor;
    }
    $s['in'] = false; $s['budget_start'] = $at; $s['board_req'] = null;
    $s['protected'] = $protect; $s['protect_until'] = $protect ? $at+elevator_config('protection') : 0;
    $p['pls'] = elevator_pls($floor); $p['pgroup'] = 251; $p['action'] = ''; $p['bid'] = 0; $p['state'] = 0;
    unset($p['clbpara']['smeo'],$s['watching']);
    if ($floor===7) $s['attic']=true;
    if ($floor===-8) $s['fireseed']=true;
    if (abs($floor)===7) $s['endpoint']=true;
    elevator_trim_floor_memory($s);
}
function elevator_trim_floor_memory(&$s) {
    $endless=array(); foreach ($s['floors'] as $f=>$v) if ($f>7 || $f<-8) $endless[]=$f;
    while (count($endless)>8) { $old=array_shift($endless); if ((int)$old!==$s['floor']) unset($s['floors'][$old]); }
}
function elevator_board(&$p,$e,$at) {
    $s = &$p['clbpara']['elevator'];
    if (!elevator_active($p) || $s['in'] || $s['floor'] !== $e['floor'] || $p['pls'] !== elevator_pls($s['floor']) || $e['state'] !== 'doors') return false;
    $s['in'] = true; $s['board_req'] = null; $s['protected'] = true; $s['budget_start'] = $at;
    $s['rides']++; $p['pls'] = 9000; $p['pgroup'] = 250; $p['action'] = ''; $p['bid'] = 0;
    unset($p['clbpara']['smeo']);
    return true;
}
function elevator_request(&$e,$floor,$via,$pid,$at) {
    if (in_array($e['state'],array('hold','ended'),true) || !in_array($via,array('cabin','call'),true)) return false;
    if (!$floor || ($e['phase']==='finite' && ($floor < -8 || $floor > 7)) || abs($floor)>100000) return false;
    foreach ($e['route'] as $r) if ($r['floor']===$floor) return true;
    foreach ($e['reqs'] as $i=>$r) if ($r['floor']===$floor) {
        if ($via==='cabin' && $r['via']==='call') {
            $r['via']='cabin'; $r['ts']=$at; $r['by']=$pid;
            array_splice($e['reqs'],$i,1); $e['reqs'][]=$r;
        }
        return true;
    }
    if (count($e['reqs'])>=32) return false;
    $e['reqs'][] = array('floor'=>$floor,'via'=>$via,'by'=>$pid,'ts'=>$at);
    return true;
}
function elevator_open(&$e,&$players,$at) {
    $e['state']='doors'; $e['door']='open'; $e['door_deadline']=$at+elevator_config('door_open');
    foreach ($players as &$p) {
        if (!empty($p['clbpara']['elevator']['board_req'])) elevator_board($p,$e,$at);
        if (elevator_active($p) && $p['clbpara']['elevator']['in']) {
            if ($e['floor']===7) $p['clbpara']['elevator']['attic']=true;
            if ($e['floor']===-8) $p['clbpara']['elevator']['fireseed']=true;
            if (abs($e['floor'])===7) $p['clbpara']['elevator']['endpoint']=true;
        }
    }
    unset($p);
}
function elevator_depart(&$e,&$players,$at) {
    foreach ($players as &$p) {
        $s = &$p['clbpara']['elevator'];
        if (elevator_active($p) && !$s['in'] && $s['floor']===$e['floor']) {
            $s['protected']=false; $s['protect_until']=0; $s['budget_start']=$at;
        }
    }
    unset($p,$s);
    $e['door']='closed';
    $dest=$e['route'][0]['floor'];
    $e['dir']=$dest>$e['floor']?1:-1;
    $delay = !empty($e['slow_buttons']) ? 5 : 0;
    if ($delay) $e['slow_buttons']--;
    $e['state']='travel';
    $e['travel_end']=$at+abs($dest-$e['floor'])*elevator_config('travel_unit')+$delay+($e['pollution']??0);
    $e['pollution']=0;
}
function elevator_settle_player(&$p,$result,$at,$e,$reason='') {
    $s=&$p['clbpara']['elevator'];
    if (!empty($s['result'])) return false;
    $s['result']=$result; $s['finished_at']=$at; $s['reason']=$reason;
    $s['escape_floor']=$s['in']?$e['floor']:$s['floor'];
    $s['phase']=$e['phase']; $s['solo']=$e['joined']===1;
    $s['in']=false; $s['board_req']=null; $s['protected']=false;
    $p['hp']=0; $p['state']=$result==='escape'?64:65; $p['endtime']=$at; $p['action']=''; $p['bid']=0;
    // 保留玩家行以拒绝本局再次起装；离场物品不成为可搜尸的奖励。
    $p['pls']=0; $p['pgroup']=0;
    return true;
}
function elevator_death(&$p,$at,$e) {
    $s=&$p['clbpara']['elevator'];
    if (!empty($s['result'])) return false;
    $s['deaths']++;
    if ($s['revive']>0) {
        $s['revive']--;
        $floor=$s['in']?$e['floor']:$s['floor'];
        $p['hp']=max(1,(int)ceil($p['mhp']*.3)); $p['inf']='';
        elevator_entrance($p,$floor,$at,true);
        elevator_personal($p,'电梯口的灯重新照亮了你。生命恢复到三成，本局唯一的复活机会已用尽。');
        return true;
    }
    elevator_settle_player($p,'dead',$at,$e,'灯光终于离开了你的视野。');
    return false;
}
function elevator_budget_tick(&$p,$from,$to,$e) {
    if (!elevator_active($p)) return;
    $s=&$p['clbpara']['elevator'];
    if ($s['in'] || $s['floor']===-8) { $s['budget_start']=$to; return; }
    // 页面每4秒通过原有AJAX报到；断线窗口以外的时间不会在重连时补扣。
    $until=min($to,$s['last_seen']+elevator_config('presence'));
    $from=max($from,$s['budget_start']);
    if ($s['protected']) {
        if (!empty($s['board_req'])) { $s['budget_start']=$to; return; }
        $from=max($from,$s['protect_until']);
        if ($to >= $s['protect_until']) $s['protected']=false;
    }
    $spent=max(0,$until-$from);
    $excess=max(0,$spent-$s['budget']);
    $s['budget']=max(0,$s['budget']-$spent); $s['budget_start']=$to;
    if ($excess) {
        $p['hp']=max(0,$p['hp']-$excess*elevator_config('erosion'));
        if (!$p['hp']) elevator_death($p,$from+$spent,$e);
    }
}
function elevator_vote_finish(&$e,&$players,$at) {
    $vote=$e['endless_vote']; $yes=array();
    foreach ($players as $id=>$p) if (elevator_active($p) && ($vote['opts'][$id]??0)===1) $yes[]=$id;
    foreach ($players as $id=>&$p) if (elevator_active($p) && !in_array($id,$yes,true)) {
        elevator_settle_player($p,'escape',$at,$e,'你选择在门仍然敞开的时候离开。');
        $p['clbpara']['elevator']['vote_escape']=true;
    }
    unset($p);
    if ($yes) {
        $e['phase']='endless'; $e['limit']=elevator_config('limit_endless'); $e['endless_dir']=$vote['dir'];
        foreach ($yes as $id) {
            $players[$id]['clbpara']['elevator']['endless']=true;
            if (!empty($vote['fireseed'])) $players[$id]['clbpara']['elevator']['refused']=true;
        }
        elevator_notice($e,'无尽行程开始。留下的乘客再也无法普通脱出。',$at);
    }
    $e['endless_vote']=null;
}
// deadline逐段推进：到站、开门、关门全部完成后才可承诺下一行程。
function elevator_tick(&$e,&$players,$now) {
    if (empty($e) || $e['state']==='ended') return;
    $cursor=$e['last_tick'];
    for ($steps=0;$steps<512;$steps++) {
        $next=$now;
        if (in_array($e['state'],array('doors','closing'),true)) $next=min($next,$e['door_deadline']);
        if ($e['state']==='travel') $next=min($next,$e['travel_end']);
        if ($e['state']==='hold' && !empty($e['event'])) $next=min($next,$e['event']['deadline']);
        if ($e['endless_vote']) $next=min($next,$e['endless_vote']['deadline']);
        $next=max($cursor,min($next,$e['start_time']+$e['limit']));
        foreach ($players as &$p) elevator_budget_tick($p,$cursor,$next,$e);
        unset($p);
        $cursor=$next;
        if ($e['endless_vote'] && $cursor >= $e['endless_vote']['deadline']) elevator_vote_finish($e,$players,$cursor);
        if ($cursor >= $e['start_time']+$e['limit']) {
            foreach ($players as &$p) if (elevator_active($p)) elevator_settle_player($p,'dead',$cursor,$e,'末班铃响过，电梯的灯熄灭了。');
            unset($p); $e['state']='ended'; break;
        }
        if ($e['state']==='travel' && $cursor >= $e['travel_end']) {
            $e['floor']=$e['route'][0]['floor']; $e['route'][0]['arrived']=true;
            $e['stops']++; $e['last_stop']=$cursor; $e['attention']++;
            if (function_exists('elevator_materialize_floor')) elevator_materialize_floor($e,$e['floor'],$cursor);
            $intrusion=function_exists('elevator_intrusion_arbiter') && elevator_intrusion_arbiter($e,$players,$cursor);
            if (!$intrusion) elevator_open($e,$players,$cursor);
        } elseif ($e['state']==='doors' && $cursor >= $e['door_deadline']) {
            $e['state']='closing'; $e['door']='closing'; $e['door_deadline']=$cursor+elevator_config('door_close');
        } elseif ($e['state']==='closing' && $cursor >= $e['door_deadline']) {
            if (!empty($e['route']) && $e['route'][0]['floor']!==$e['floor']) elevator_depart($e,$players,$cursor);
            else { $e['route']=array(); $e['state']='idle'; $e['door']='closed'; }
        } elseif ($e['state']==='hold' && $cursor >= $e['event']['deadline']) {
            elevator_intrusion_tick($e,$players,$cursor);
        }
        if ($e['state']==='idle' && !$e['route'] && $e['reqs']) {
            $chosen=0;
            foreach ($e['reqs'] as $i=>$r) if ($r['via']==='cabin') { $chosen=$i; break; }
            $e['route']=array($e['reqs'][$chosen]); array_splice($e['reqs'],$chosen,1);
            if ($e['route'][0]['floor']===$e['floor']) { $e['route'][0]['arrived']=true; elevator_open($e,$players,$cursor); }
            else {
                // 停车时先给当前楼层8秒登梯时间，随后5秒关门；不覆盖已承诺的目的地。
                elevator_open($e,$players,$cursor);
            }
        }
        if ($e['state']==='idle' && $cursor>=($e['idle_checked']??$e['start_time'])+30) {
            $e['idle_checked']=$cursor;
            if (function_exists('elevator_intrusion_arbiter')) elevator_intrusion_arbiter($e,$players,$cursor);
        }
        $active=0; foreach ($players as $p) if (elevator_active($p)) $active++;
        if ($players && !$active) { $e['state']='ended'; break; }
        if ($cursor >= $now) break;
    }
    $e['last_tick']=$cursor;
    foreach ($players as &$p) {
        $s=$p['clbpara']['elevator'];
        if (!empty($s['trade']) && (!$s['in'] || $e['state']==='hold' || $s['trade']['until']<=$now || !elevator_active($p))) elevator_trade_cancel($p);
    }
    unset($p);
}

function elevator_request_tick() {
    global $gamevars,$now,$gamestate;
    if (empty($gamevars['elevator']) || $gamestate<20) return;
    $players=elevator_read_players(); $before=$players;
    elevator_tick($gamevars['elevator'],$players,$now);
    elevator_save_players($players,$before);
    foreach ($players as $p) if (!empty($p['clbpara']['elevator']['result'])) elevator_record_result($p);
    elevator_refresh_maps();
    save_gameinfo();
    if ($gamevars['elevator']['state']==='ended' && $gamestate>0) gameover($now,'end8','');
}
