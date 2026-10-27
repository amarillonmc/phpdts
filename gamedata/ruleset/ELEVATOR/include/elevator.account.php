<?php
if (!defined('IN_GAME')) exit('Access Denied');

function elevator_reward($s) {
    if (($s['result']??'')==='quit') return 2;
    if (!empty($s['vote_escape'])) return 10;
    if (($s['phase']??'finite')==='endless') return min(30,8+max(0,(int)$s['deepest']-8));
    if ($s['result']==='escape') {
        $f=abs((int)$s['escape_floor']); $reward=$f>=7?12:($f>=5?8:5);
        return $reward+(!empty($s['solo']) && $f===7?3:0);
    }
    return 2+max(0,(int)$s['deepest']-1);
}
function elevator_record_result($p) {
    global $db,$gtablepre,$groomid,$gamevars;
    $s=$p['clbpara']['elevator']; if (empty($s['result'])) return;
    $round=elevator_sql($gamevars['elevator']['round']); $user=elevator_sql($p['name']);
    $reward=elevator_reward($s);
    $details=array('rides'=>$s['rides'],'cats'=>$s['cats'],'attic'=>!empty($s['attic']),'fireseed'=>!empty($s['fireseed']),
        'endless'=>!empty($s['endless']),'perfect'=>!empty($s['perfect_riddle']),'fragments'=>count($s['fragments']),
        'refused'=>!empty($s['refused']),'endpoint'=>!empty($s['endpoint']),'up'=>$s['up_depth']??0,'down'=>$s['down_depth']??0,'floor'=>$s['escape_floor']);
    $json=elevator_sql(json_encode($details,JSON_UNESCAPED_UNICODE));
    $result=elevator_sql($s['result']); $phase=elevator_sql($s['phase']); $dir=(int)($gamevars['elevator']['endless_dir']??0);
    $db->query("INSERT IGNORE INTO {$gtablepre}elevator_results (round_id,username,roomid,result,phase,direction,depth,depth_time,finished_at,reward,details)
        VALUES ($round,$user,".(int)$groomid.",$result,$phase,$dir,".(int)$s['deepest'].",".(int)$s['depth_time'].",".(int)$s['finished_at'].",$reward,$json)");
    $q=$db->query("SELECT * FROM {$gtablepre}elevator_results WHERE round_id=$round AND username=$user"); $record=$db->fetch_array($q);
    if (!$record || $record['paid']) return;
    $q=$db->query("SELECT achrev,nicksrev FROM {$gtablepre}users WHERE username=$user"); $account=$db->fetch_array($q); if (!$account) return;
    $ach=elevator_decode($account['achrev']); $titles=elevator_decode($account['nicksrev']);
    if (empty($titles['nicks'])) $titles['nicks']=array(0);
    $totals=array('games'=>0,'rides'=>0,'cats'=>0,'up'=>0,'down'=>0,'depth'=>0);
    $q=$db->query("SELECT depth,details FROM {$gtablepre}elevator_results WHERE username=$user");
    while ($r=$db->fetch_array($q)) {
        $d=elevator_decode($r['details']); $totals['games']++; $totals['rides']+=$d['rides']??0; $totals['cats']+=$d['cats']??0;
        foreach (array('attic','fireseed','endless','perfect','fragments','refused','endpoint','up','down') as $key) $totals[$key]=max($totals[$key]??0,$d[$key]??0);
        $totals['depth']=max($totals['depth'],(int)$r['depth']);
    }
    $values=array($totals['games'],$totals['attic'],$totals['fireseed'],$totals['endless'],$totals['depth'],
        $totals['perfect'],$totals['fragments'],$totals['rides'],$totals['cats'],$totals['refused']);
    $thresholds=array(1,1,1,1,15,1,3,100,10,1); $bonuses=array(10,20,20,30,50,77,30,100,30,50); $bonus=0;
    foreach ($values as $i=>$value) {
        $id=620+$i; $ach[$id]['v']=$value;
        if ($value >= $thresholds[$i] && empty($ach[$id]['l'])) { $ach[$id]['l']=1; $bonus+=$bonuses[$i]; }
        elseif (!isset($ach[$id]['l'])) $ach[$id]['l']=0;
    }
    foreach (array(9201=>$totals['games']>=10,9202=>!empty($totals['endpoint']),9203=>!empty($totals['perfect']),9204=>$totals['down']>=10,9205=>$totals['up']>=5) as $id=>$ok) if ($ok && !in_array($id,$titles['nicks'])) $titles['nicks'][]=$id;
    $achsql=elevator_sql(json_encode($ach,JSON_UNESCAPED_UNICODE)); $titlesql=elevator_sql(json_encode($titles,JSON_UNESCAPED_UNICODE));
    $won=$s['result']==='escape'?1:0;
    // 同一SQL同时修改账户和paid；即使请求在返回前重试也不会重复发钱。
    $db->query("UPDATE {$gtablepre}users u JOIN {$gtablepre}elevator_results r ON r.username=u.username
        SET u.credits2=u.credits2+r.reward+$bonus,u.validgames=u.validgames+1,u.wingames=u.wingames+$won,
        u.achrev=$achsql,u.nicksrev=$titlesql,r.paid=1
        WHERE r.round_id=$round AND r.username=$user AND r.paid=0");
}
function elevator_authorized() {
    global $udata,$cuser,$cpass;
    if (empty($udata) || empty($cuser) || empty($cpass) || $udata['username']!==$cuser || !hash_equals((string)$udata['password'],(string)$cpass) || $udata['groupid']<=0) throw new RuntimeException('请重新登录。');
    return $udata;
}
function elevator_join_csrf($user,$e) { return hash_hmac('sha256','ELEVATOR:'.$e['round'].':'.$user['username'],(string)$user['password']); }
function elevator_join($user,$club,$at) {
    global $gamevars,$gamestate,$db,$tablepre,$validnum,$alivenum;
    $e=&$gamevars['elevator'];
    if ($gamestate<20 || $e['phase']!=='finite' || $e['state']==='ended' || $at >= $e['start_time']+elevator_config('join_window')) throw new RuntimeException('有限段开局前十分钟才能加入。');
    if (!in_array($club,array(1,2,3,4,5,9),true)) throw new RuntimeException('请选择可用的起始武器路线。');
    $all=elevator_read_players();
    foreach ($all as $p) if ($p['name']===$user['username']) throw new RuntimeException('每个账户在本局只能领取一次起装。');
    if ($e['joined']>=elevator_config('max_players')) throw new RuntimeException('本班电梯已经坐满八位乘客。');
    $floor=$e['state']==='travel'?$e['route'][0]['floor']:$e['floor'];
    elevator_materialize_floor($e,$floor,$at);
    $p=create_dummy_playerdata();
    $p=array_merge($p,array('name'=>$user['username'],'pass'=>$user['password'],'type'=>0,'gd'=>in_array($user['gender']??'',array('m','f'))?$user['gender']:'m',
        'icon'=>$user['icon']??0,'sNo'=>$e['joined']+1,'validtime'=>$at,'endtime'=>$at,'mhp'=>400,'hp'=>400,'msp'=>400,'sp'=>400,
        'att'=>100,'def'=>100,'money'=>300,'lvl'=>1,'exp'=>0,'pose'=>1,'tactic'=>2,'club'=>0,'itmnumlimit'=>6));
    foreach (array('wp','wk','wg','wc','wd','wf') as $k) $p[$k]=30;
    $p['clbpara']=array('elevator'=>elevator_player_state($floor,$at),'skill'=>array(),'skillpara'=>array(),'bgmbook'=>array(),
        'randver1'=>mt_rand(0,255),'randver2'=>mt_rand(0,255),'charge1'=>0,'charge2'=>0,'charge3'=>0,'charge4'=>0);
    require_once GAME_ROOT.'include/game/clubslct.func.php'; changeclub($club,$p);
    $kind=array(1=>'WP',2=>'WK',3=>'WC',4=>'WG',5=>'WD',9=>'WF')[$club];
    $p['wep']='乘客制式'.array(1=>'撬棍',2=>'短刀',3=>'飞镖',4=>'手枪',5=>'爆破包',9=>'音叉')[$club];
    $p['wepk']=$kind; $p['wepe']=60; $p['weps']=$club===4?90:60; $p['wepsk']='';
    $p['wep2']='拳头'; $p['wep2k']='WN'; $p['wep2e']=0; $p['wep2s']='∞';
    $p['arb']='乘客外套'; $p['arbk']='DB'; $p['arbe']=25; $p['arbs']=60;
    elevator_set_slot($p,1,array('itm'=>'夹层急救包','itmk'=>'HH','itme'=>120,'itms'=>8));
    elevator_set_slot($p,2,array('itm'=>'夹层能量饮料','itmk'=>'HS','itme'=>140,'itms'=>8));
    elevator_set_slot($p,3,elevator_item(elevator_item_id('猫粮罐头'),$e['round']));
    elevator_entrance($p,$floor,$at,true);
    $row=player_format_with_db_structure($p); unset($row['pid']); $db->array_insert($tablepre.'players',$row);
    $p['pid']=(int)$db->insert_id(); $e['joined']++; $validnum++; $alivenum++;
    save_gameinfo(); return $p;
}
