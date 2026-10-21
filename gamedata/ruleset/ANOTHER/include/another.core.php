<?php
if (!defined('IN_GAME')) exit('Access Denied');

function another_html($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function another_sql_text($value) { return "X'".bin2hex((string)$value)."'"; }
function another_decode($value) {
    if (is_array($value)) return $value;
    $decoded = json_decode((string)$value, true);
    return is_array($decoded) ? $decoded : array();
}
function another_name($player) {
    return isset($player['clbpara']['another']['display_name']) ? $player['clbpara']['another']['display_name'] : $player['name'];
}
function another_owner($player) { return isset($player['clbpara']['another']['owner']) ? $player['clbpara']['another']['owner'] : ''; }
function another_notice(&$room, $message, $now) {
    $room['news'][] = array('time' => $now, 'text' => $message);
    $room['news'] = array_slice($room['news'], -35);
}
function another_personal_notice(&$player, $message, $now) {
    $player['clbpara']['another']['messages'][] = array('time' => $now, 'text' => $message);
    $player['clbpara']['another']['messages'] = array_slice($player['clbpara']['another']['messages'], -25);
}

function another_lock() {
    global $plock, $another_lock_handle;
    if (is_resource($plock) || is_resource($another_lock_handle)) return;
    $another_lock_handle = fopen(GAME_ROOT.'gamedata/process.lock', 'ab');
    if (!$another_lock_handle || !flock($another_lock_handle, LOCK_EX)) throw new RuntimeException('房间繁忙，请重试。');
    load_gameinfo();
}

function another_read_players() {
    global $db, $tablepre;
    $players = array();
    $result = $db->query("SELECT * FROM {$tablepre}players");
    while ($p = $db->fetch_array($result)) {
        $p['clbpara'] = another_decode($p['clbpara']);
        foreach (array('wep','wep2','arb','arh','ara','arf','art') as $slot) $p[$slot.'para'] = another_decode($p[$slot.'para']);
        for ($i = 0; $i <= 6; $i++) $p['itmpara'.$i] = another_decode($p['itmpara'.$i]);
        $players[(int)$p['pid']] = $p;
    }
    return $players;
}

// 地图物资与陷阱沿用各自的表；不把整张地图挤进gamevars的TEXT字段。
function another_read_room() {
    global $gamevars, $db, $tablepre;
    $room = isset($gamevars['another']) ? $gamevars['another'] : array();
    foreach (array('ground' => 'mapitem', 'deliveries' => 'mapitem', 'traps' => 'maptrap') as $key => $table) {
        $room[$key] = array();
        $result = $db->query("SELECT * FROM {$tablepre}{$table}");
        while ($row = $db->fetch_array($result)) {
            $para = another_decode($row['itmpara']);
            if (!isset($para['another_object'])) continue;
            if (($para['another_kind'] ?? ($table==='maptrap'?'traps':'ground')) !== $key) continue;
            $object = $para['another_object'];
            $room[$key][(int)$object['id']] = $object;
        }
    }
    return $room;
}

function another_save_world($room, $players, $old_room = array(), $old_players = array()) {
    global $db, $tablepre, $gamevars, $validnum, $alivenum, $deathnum;
    $validnum=$alivenum=$deathnum=0;
    foreach ($players as $pid => $player) {
        if(!$player['type']) { $validnum++; if($player['hp']>0) $alivenum++; else $deathnum++; }
        if (isset($old_players[$pid]) && $old_players[$pid] == $player) continue;
        player_save($player);
    }
    foreach (array('ground' => array('mapitem','iid'), 'deliveries' => array('mapitem','iid'), 'traps' => array('maptrap','tid')) as $key => $spec) {
        $old = isset($old_room[$key]) ? $old_room[$key] : array();
        $current = isset($room[$key]) ? $room[$key] : array();
        foreach ($old as $id => $object) {
            if (!isset($current[$id])) $db->query("DELETE FROM {$tablepre}{$spec[0]} WHERE {$spec[1]}=".(int)$id);
        }
        foreach ($current as $id => $object) {
            if (isset($old[$id]) && $old[$id] == $object) continue;
            $row = array($spec[1] => (int)$id, 'pls' => (int)$object['pls'],
                'itm' => isset($object['item']['name']) ? $object['item']['name'] : '异常陷阱',
                'itmk' => $key === 'ground' ? 'AN' : 'TN', 'itme' => 0, 'itms' => '1', 'itmsk' => '',
                'itmpara' => json_encode(array('another_kind'=>$key, 'another_object' => $object), JSON_UNESCAPED_UNICODE));
            if (isset($old[$id])) $db->array_update($tablepre.$spec[0], $row, $spec[1].'='.(int)$id);
            else $db->array_insert($tablepre.$spec[0], $row);
        }
    }
    unset($room['ground'], $room['traps'], $room['deliveries']);
    $gamevars['another'] = $room;
    save_gameinfo();
}

function another_empty_player() {
    $p = create_dummy_playerdata();
    $p = array_merge($p, array('type'=>0, 'club'=>0, 'skillpoint'=>0, 'rp'=>0, 'lvl'=>1, 'exp'=>0,
        'hp'=>400, 'mhp'=>400, 'sp'=>400, 'msp'=>400, 'att'=>100, 'def'=>100, 'pls'=>1,
        'money'=>300, 'state'=>0, 'clbpara'=>array('another'=>array('talents'=>array(), 'rep'=>0, 'messages'=>array()))));
    return $p;
}
function another_insert_player($p) {
    global $db, $tablepre;
    unset($p['pid']);
    $row = player_format_with_db_structure($p);
    unset($row['pid']);
    $db->array_insert($tablepre.'players', $row);
    $p['pid'] = (int)$db->insert_id();
    return $p;
}

function another_spawn_npc(&$players, $template, $pls, $now, $snapshot = null) {
    $config = another_config();
    if (!isset($config['npc_templates'][$template])) return;
    $spec = $config['npc_templates'][$template];
    $p = array_replace_recursive(another_empty_player(), $spec);
    $p['pls'] = (int)$pls;
    $p['validtime'] = $p['endtime'] = $now;
    $p['clbpara']['another']['npc_role'] = $template;
    $p['clbpara']['another']['death_settled'] = false;
    $p['weppara']['another']['uid'] = bin2hex(random_bytes(12));
    if (isset($spec['weapon'])) another_item_set($p, 'wep', another_item_make($spec['weapon']));
    if (isset($spec['armor'])) another_item_set($p, 'arb', another_item_make($spec['armor']));
    if ($snapshot) {
        $p['name'] = '优胜者镜像';
        $p['clbpara']['another']['display_name'] = $snapshot['name'].'的镜像';
        $p['clbpara']['another']['talents'] = $snapshot['talents'];
        // 专用制式装备按构筑选择，斩系优胜者的日之愤怒也必须有可消耗的武器。
        $kind=another_talent($p,'SU')?'slash':(another_talent($p,'A1')>another_talent($p,'P1')?'throw':'gun');
        $loadouts=$config['mirrors']['loadouts'];
        $weapon=another_item_make($loadouts[$kind]['item']);
        $weapon['name']=$loadouts[$kind]['name'];
        another_item_set($p,'wep',$weapon);
        $armor=another_item_make($config['mirrors']['armor']); $armor['name']='镜像制式护甲';
        another_item_set($p,'arb',$armor);
        foreach (array('lvl','mhp','msp','att','def') as $k) if (isset($snapshot[$k])) $p[$k] = (int)$snapshot[$k];
        $p['hp'] = another_max_hp($p); $p['sp'] = another_max_sp($p);
        $p['clbpara']['another']['mirror'] = true;
    }
    $p = another_insert_player($p);
    $players[$p['pid']] = $p;
}

function another_seed_world($now, $mode=63) {
    global $starttime, $arealist, $areanum, $areatime, $gamevars;
    if (!function_exists('create_dummy_playerdata')) require_once GAME_ROOT.'include/game.func.php';
    $config = another_config();
    $old_room=another_read_room(); $full=($mode & 4) || empty($gamevars['another']);
    $room = $full ? array('started_at'=>$starttime, 'next_id'=>1, 'ground'=>array(), 'traps'=>array(), 'deliveries'=>array(), 'stock'=>array(),
        'news'=>array(), 'closed'=>array(), 'zone_index'=>0, 'reinforced'=>array(), 'mirrors_spawned'=>false) : $old_room;
    $players = another_read_players();
    if($full || ($mode & 16)) {
    $room['ground']=$room['traps']=$room['deliveries']=$room['stock']=array();
    foreach ($config['maps'] as $pls => $map) {
        foreach ($map['drops'] as $drop) {
            for ($i=0; $i < $drop['stock']; $i++) {
                $item = another_item_make($drop['item'], isset($drop['count']) ? $drop['count'] : null);
                if (!empty($drop['name'])) $item['name'] = $drop['name'];
                $id = another_next_id($room);
                $room['ground'][$id] = array('id'=>$id, 'pls'=>(int)$pls, 'item'=>$item);
            }
        }
    }
    foreach ($config['wild_traps'] as $trap) {
        $id=another_next_id($room);
        $is_hazard=$trap['item']==='trap_hazard';
        $room['traps'][$id]=array('id'=>$id,'pls'=>$trap['pls'],'owner'=>0,'item'=>another_item_make($trap['item']),
            'catalog'=>$trap['item'],'hide'=>1,'gold'=>$is_hazard,'announced'=>$is_hazard);
    }
    }
    if($full || ($mode & 8)) foreach ($config['npc_spawns'] as $spawn) for ($i=0; $i<$spawn['count']; $i++) another_spawn_npc($players,$spawn['template'],$spawn['pls'],$now);
    $arealist = array_merge(array(0),$room['closed']); $areanum = count($room['closed']); $areatime = $starttime + 600;
    another_save_world($room, $players, $old_room, $players);
}

function another_safe_maps($room) {
    return array_values(array_diff(array_keys(another_config()['maps']), isset($room['closed']) ? $room['closed'] : array()));
}

function another_tick(&$room, &$players, $now, $started_at) {
    if (!empty($room['result'])) return;
    // 网页无人请求时也按禁区原定时刻推进，不能把早已幸存的队伍补判为65分钟全灭。
    $last=$room['last_tick']??$started_at;
    $steps=array();
    $boundaries=array_keys(another_config()['zones']);
    $boundaries[]=another_config()['entry_close'];
    $boundaries[]=another_config()['deadline'];
    sort($boundaries);
    foreach(array_unique($boundaries) as $seconds) if($started_at+$seconds>$last && $started_at+$seconds<=$now) $steps[]=$started_at+$seconds;
    $steps[]=$now;
    foreach(array_unique($steps) as $at) {
        another_tick_at($room,$players,$at,$started_at);
        $room['last_tick']=$at;
        if(!empty($room['result'])) break;
    }
}

function another_tick_at(&$room, &$players, $now, $started_at) {
    $config = another_config();
    if (!empty($room['result'])) return;
    $elapsed = max(0, $now - $started_at);
    $room['entry_closed'] = $elapsed >= (isset($config['entry_close']) ? $config['entry_close'] : 600);
    if(isset($config['weather_schedule'])) foreach($config['weather_schedule'] as $seconds=>$weather) if($elapsed >= $seconds) $room['weather']=$weather;
    $closed = array(); $closed_at=array();
    foreach ($config['zones'] as $seconds => $locations) if ($elapsed >= $seconds) {
        $closed = array_merge($closed, $locations);
        foreach($locations as $location) $closed_at[$location]=$started_at+$seconds;
    }
    $newly_closed = array_diff($closed, isset($room['closed']) ? $room['closed'] : array());
    $room['closed'] = array_values($closed);
    if ($newly_closed) {
        $names = array(); foreach ($newly_closed as $pls) $names[] = $config['maps'][$pls]['name'];
        another_notice($room, '禁区关闭：'.implode('、',$names).'。', $now);
    }
    foreach ($players as &$p) {
        if ((int)$p['hp'] <= 0) continue;
        $messages = array();
        another_tick_statuses($p, isset($closed_at[$p['pls']])?min($now,$closed_at[$p['pls']]-1):$now, $messages);
        foreach ($messages as $message) another_personal_notice($p, $message, $now);
        if ($p['hp']>0 && isset($closed_at[$p['pls']])) {
            $p['hp'] = 0; $p['state'] = 11; $p['deathtime'] = $p['endtime'] = $closed_at[$p['pls']];
            $p['clbpara']['another']['death_reason'] = '禁区关闭';
        }
        if (!empty($p['clbpara']['another']['rest']) && $p['hp'] > 0) {
            $last = isset($p['clbpara']['another']['rest_tick']) ? $p['clbpara']['another']['rest_tick'] : $now;
            $dt = max(0,$now-$last);
            if ($dt) {
                $multiplier = 1 + another_talent_value($p,'I4')/100;
                foreach(array('hp','sp') as $stat) {
                    $maximum=$stat==='hp'?another_max_hp($p):another_max_sp($p);
                    $carry=$p['clbpara']['another']['rest_fraction_'.$stat]??0;
                    $gain=$carry+$maximum*$dt*$config['action']['rest_'.$stat.'_fraction_per_second']*$multiplier;
                    $p[$stat]=min($maximum,$p[$stat]+(int)floor($gain));
                    $p['clbpara']['another']['rest_fraction_'.$stat]=$p[$stat]>=$maximum?0:$gain-floor($gain);
                }
                $p['clbpara']['another']['rest_tick'] = $now;
            }
        }
    }
    unset($p);
    $messages = array();
    another_items_tick($room,$players,$now,$messages);
    foreach ($messages as $message) another_notice($room,$message,$now);
    if (isset($config['npc_reinforcements'])) foreach ($config['npc_reinforcements'] as $seconds => $spawns) {
        if ($elapsed < $seconds || !empty($room['reinforced'][$seconds])) continue;
        $room['reinforced'][$seconds] = true;
        foreach ($spawns as $spawn) if (!in_array((int)$spawn['pls'],$closed,true)) for ($i=0;$i<$spawn['count'];$i++) another_spawn_npc($players,$spawn['template'],$spawn['pls'],$now);
        another_notice($room,'城区深处传来了新的金属脚步声。',$now);
    }
    another_settle_deaths($room,$players,$now);
    if ($elapsed >= $config['deadline']) $room['result'] = array('kind'=>'timeout','winners'=>array(),'time'=>$started_at+$config['deadline']);
    else another_check_survival($room,$players,$now);
}

function another_settle_deaths(&$room, &$players, $now) {
    $config = another_config();
    foreach (array_keys($players) as $pid) {
        if ($players[$pid]['hp'] > 0 || !empty($players[$pid]['clbpara']['another']['death_settled'])) continue;
        $dead = &$players[$pid];
        $dead['clbpara']['another']['death_settled'] = true;
        if(empty($dead['deathtime'])) $dead['deathtime']=$now;
        $dead['endtime'] = $dead['deathtime'];
        if ($dead['state'] < 10) $dead['state'] = 20;
        $killer = isset($dead['clbpara']['another']['killer']) ? (int)$dead['clbpara']['another']['killer'] : (int)$dead['bid'];
        if (!$dead['type']) another_notice($room,another_name($dead).'已被淘汰。',$now);
        $messages = array();
        if ($killer && isset($players[$killer]) && $killer !== $pid && (int)$dead['state'] !== 11) {
            another_combat_rewards($players[$killer],$dead,$players,$room,$now,$messages);
            foreach ($messages as $message) another_personal_notice($players[$killer],$message,$now);
        } else {
            $environment = array('pid'=>0,'type'=>1,'name'=>'环境');
            another_combat_rewards($environment,$dead,$players,$room,$now,$messages);
        }
        unset($dead);
    }
}

function another_mirror_pool() {
    global $db, $gtablepre;
    $pool = array();
    $result = $db->query("SELECT gid,clbpara FROM {$gtablepre}winners WHERE clbpara LIKE '%another_victors%'");
    while ($row = $db->fetch_array($result)) {
        $para = another_decode($row['clbpara']);
        if (!isset($para['another_victors'])) continue;
        foreach ($para['another_victors'] as $victor) $pool[] = $victor;
    }
    return $pool;
}

function another_spawn_mirrors(&$room,&$players,$now,$started_at) {
    $config = another_config();
    if (!empty($room['mirrors_spawned']) || $now-$started_at < $config['mirrors']['spawn_at']) return;
    $room['mirrors_spawned'] = true;
    $pool = another_mirror_pool(); $safe = another_safe_maps($room);
    if (!$pool || !$safe) return;
    shuffle($pool);
    foreach (array_slice($pool,0,$config['mirrors']['count']) as $snapshot) {
        another_spawn_npc($players,'mirror',$safe[array_rand($safe)],$now,$snapshot);
        $id=array_key_last($players); $mirror=$players[$id];
        if(another_talent($mirror,'IU')) for($i=0;$i<3;$i++) {
            $tid=another_next_id($room); $room['traps'][$tid]=array('id'=>$tid,'pls'=>(int)$mirror['pls'],'owner'=>$id,
                'item'=>another_item_make('trap_combo'),'catalog'=>'trap_combo','hide'=>1-another_talent_value($mirror,'I2')/100,'gold'=>true);
        }
    }
    another_notice($room,'几段旧的优胜记录被重新载入了城市。',$now);
}

function another_record_result(&$room,&$players) {
    global $db, $gtablepre, $gamenum, $gamevars, $gamestate;
    if (empty($room['result']) || !empty($room['result']['recorded'])) return;
    $result = &$room['result'];
    $result['recorded'] = true;
    $snapshots = array(); $owners = array(); $winner_name = '';
    foreach ($result['winners'] as $pid) {
        $p = &$players[$pid];
        $p['clbpara']['another']['won'] = true;
        if ($p['hp'] > 0) $p['state'] = $result['kind'] === 'escape' ? 6 : 5;
        $owners[another_owner($p)] = true;
        if ($winner_name === '') $winner_name = another_owner($p);
        $snapshot = array('name'=>another_name($p),'talents'=>$p['clbpara']['another']['talents']);
        foreach (array('lvl','mhp','msp','att','def') as $key) $snapshot[$key]=(int)$p[$key];
        $snapshots[]=$snapshot;
        player_save($p);
        unset($p);
    }
    $stored = $room; unset($stored['ground'],$stored['traps'],$stored['deliveries']);
    $gamevars['another']=$stored;
    save_gameinfo();
    // 沿用现有战绩与账户结算。主账号行始终存在，第二角色不冒充一个新账户。
    $entered=false; $best_kills=-1; $best_name='';
    foreach($players as $p) if(!$p['type']) {
        $entered=true;
        if($p['killnum']>$best_kills) { $best_kills=$p['killnum']; $best_name=another_name($p); }
    }
    gameover($result['time'], $winner_name !== '' ? ($result['kind']==='escape' ? 'end3' : 'end2') : ($entered?'end1':'end4'), $winner_name);
    if($entered) $db->array_update($gtablepre.'winners',array('hkp'=>$best_name), 'gid='.(int)$gamenum);
    if ($snapshots) {
        $db->array_update($gtablepre.'winners',array('clbpara'=>json_encode(array('another_victors'=>$snapshots),JSON_UNESCAPED_UNICODE)), 'gid='.(int)$gamenum);
    }
}

function another_current_player_ids($players, $owner) {
    $ids=array(); foreach($players as $pid=>$p) if (!$p['type'] && another_owner($p)===$owner) $ids[]=(int)$pid;
    sort($ids); return $ids;
}

function another_authorized_user() {
    global $udata, $cpass;
    if (!$udata || !is_string($cpass) || !hash_equals((string)$udata['password'],$cpass) || (int)$udata['groupid']<=0) {
        throw new RuntimeException('登录已失效，请重新登录。');
    }
    return $udata;
}
function another_csrf($user) {
    global $groomid, $gamenum;
    return hash_hmac('sha256','ANOTHER:'.$groomid.':'.$gamenum.':'.$user['uid'],(string)$user['password']);
}

function another_join($user, $input, &$room, &$players, $now) {
    if (!empty($room['entry_closed']) || !empty($room['result'])) throw new RuntimeException('本局已停止入场。');
    if (another_current_player_ids($players,$user['username'])) return;
    $count=isset($input['controls']) ? (int)$input['controls'] : 1;
    if (!in_array($count,array(1,2),true)) throw new RuntimeException('请选择单控或双控。');
    $talents=array(); $error='';
    for($i=1;$i<=$count;$i++) {
        $talents[$i]=another_validate_talents(isset($input['talents'.$i])?$input['talents'.$i]:array(),$error);
        if($talents[$i]===false) throw new RuntimeException('角色'.$i.'：'.$error);
    }
    $config=another_config(); $team='AN'.$user['uid'];
    foreach($talents as $slot=>$talent) {
        $p=another_empty_player();
        $p['name']=$slot===1 ? $user['username'] : 'AN2:'.$user['uid'].':'.bin2hex(random_bytes(3));
        $p['pass']=$user['password']; $p['ip']=$user['ip'];
        $p['gd']=isset($user['gender'])&&$user['gender']==='f'?'f':'m'; $p['icon']=$user['icon'];
        $p['teamID']=$team; $p['validtime']=$p['endtime']=$now; $p['pls']=$config['start_map'];
        $p['clbpara']['another']=array('owner'=>$user['username'],'owner_uid'=>(int)$user['uid'],'slot'=>$slot,
            'display_name'=>$user['username'].($count===2?($slot===1?'①':'②'):''),'talents'=>$talent,'rep'=>$config['economy']['start_rep'],
            'messages'=>array(),'notices'=>array(),'known_players'=>array(),'known_ground'=>array(),'request_seq'=>0);
        $p['money']=(int)floor($config['economy']['start_money']*(1+another_talent_value($p,'C4')/100));
        $p['hp']=another_max_hp($p); $p['sp']=another_max_sp($p);
        another_item_add($p,another_item_make('heal',2)); another_item_add($p,another_item_make('stamina'));
        another_item_set($p,'arb',another_item_make('armor'));
        // 中央摊位的成品明摆着可抢，不能入场后先随机搜索数十次才能开战。
        foreach($room['ground'] as $id=>$object) if($object['pls']===$config['start_map'] && substr($object['item']['kind'],0,1)==='W') $p['clbpara']['another']['known_ground'][$id]=true;
        $p=another_insert_player($p); $players[$p['pid']]=$p;
    }
    $ids=another_current_player_ids($players,$user['username']);
    $players[$ids[0]]['clbpara']['another']['active_pid']=$ids[0];
    global $db,$gtablepre,$gamenum;
    $db->array_update($gtablepre.'users',array('lastgame'=>$gamenum),'uid='.(int)$user['uid']);
    another_notice($room,$user['username'].'落入了这座复制城市。',$now);
}

function another_team_members($players, $winner) {
    $ids = array();
    foreach ($players as $pid => $p) if (!$p['type'] && ($pid == $winner['pid'] || another_talent_same_team($p,$winner))) $ids[] = (int)$pid;
    return $ids;
}
function another_check_survival(&$room,$players,$now) {
    if (!empty($room['result'])) return;
    $alive = array(); $entered = 0;
    foreach ($players as $p) {
        if ($p['type']) continue;
        $entered++;
        if ($p['hp'] > 0) $alive[] = $p;
    }
    if (!$entered) return;
    if (!$alive) { $room['result']=array('kind'=>'annihilation','winners'=>array(),'time'=>$now); return; }
    // 入场窗口未关闭时，不因第一人刚加入就立即结算。
    if (empty($room['entry_closed'])) return;
    $winner = $alive[0];
    foreach ($alive as $p) if ($p['pid'] != $winner['pid'] && !another_talent_same_team($p,$winner)) return;
    $room['result'] = array('kind'=>'survival','winners'=>another_team_members($players,$winner),'time'=>$now);
}
