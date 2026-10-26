<?php
if (!defined('IN_GAME')) exit('Access Denied');

function elevator_action(&$e,&$p,$command,$a,$at) {
    $s=&$p['clbpara']['elevator'];
    if (!elevator_active($p) || $e['state']==='ended') throw new RuntimeException('本次行程已经结束。');
    if (($s['stun_until']??0)>$at && $command!=='elevator_panel') throw new RuntimeException('眩晕还未散去，请稍等几秒。');
    $floor=$s['in']?$e['floor']:$s['floor'];
    $arg=isset($a['value'])?(string)$a['value']:'';
    $slot=(int)($a['slot']??-1); $token=(string)($a['token']??'');
    if ($command==='elevator_panel') { unset($s['view']); return; }
    if ($command==='elevator_call') {
        if ($s['in'] || (int)$p['pls']!==elevator_pls($s['floor'])) throw new RuntimeException('请先返回本层电梯口。');
        if (!elevator_request($e,$floor,'call',$p['pid'],$at)) throw new RuntimeException('电梯暂时不能接受呼叫。');
        $s['board_req']=array('ts'=>$at); $s['protected']=($s['no_protection_until']??0)<=$at;
        $s['budget_start']=$at;
        if ($e['state']==='doors') elevator_board($p,$e,$at);
    } elseif ($command==='elevator_board') {
        if (!elevator_board($p,$e,$at)) throw new RuntimeException('请在本层电梯口等到车门完全打开。');
    } elseif ($command==='elevator_leave') {
        if (!$s['in'] || $e['state']!=='doors') throw new RuntimeException('车门完全打开时才能下车。');
        elevator_trade_cancel($p); elevator_entrance($p,$e['floor'],$at,false);
    } elseif ($command==='elevator_target') {
        if (!$s['in'] || !preg_match('/^-?[1-9][0-9]{0,5}$/',$arg)) throw new RuntimeException('请在车厢内选择有效的楼层。');
        $dest=(int)$arg;
        if ($e['phase']==='endless' && (($e['endless_dir']===1 && $dest < -8) || ($e['endless_dir']===-1 && $dest > 7))) throw new RuntimeException('本次无尽行程沿投票确定的方向延伸。');
        if (!elevator_request($e,$dest,'cabin',$p['pid'],$at)) throw new RuntimeException('电梯暂时不能接受这个目的地。');
    } elseif ($command==='elevator_escape') {
        if ($e['phase']==='endless') throw new RuntimeException('你已选择无尽，无法普通脱出。');
        if ($e['state']==='hold' || (!$s['in'] && (int)$p['pls']!==elevator_pls($floor)) || !in_array($floor,array(3,5,7,-3,-5,-7,-8),true)) throw new RuntimeException('请到脱出节点的电梯口，或等电梯停在该层。');
        if ($s['in'] && $e['state']==='travel') throw new RuntimeException('请等电梯到站后再脱出。');
        if ($floor===-8 && ($s['riddle_step']??0)<3) throw new RuntimeException('请先听完三个问题；答错也可以离开。');
        elevator_trade_cancel($p); elevator_settle_player($p,'escape',$at,$e,'你走出门，身后的灯仍为其他乘客亮着。');
    } elseif ($command==='elevator_vote') {
        if (!in_array($arg,array('1','-1'),true) || $e['phase']!=='finite' || $e['state']==='hold') throw new RuntimeException('现在无法提交无尽选择。');
        if (!$e['endless_vote']) {
            if (!in_array($floor,array(7,-7,-8),true) || (!$s['in'] && (int)$p['pls']!==elevator_pls($floor)) || $e['state']==='travel') throw new RuntimeException('请在终点的电梯口发起无尽提案。');
            if ($floor===-8 && ($s['riddle_step']??0)<3) throw new RuntimeException('请先听完三个问题。');
            $e['endless_vote']=array('deadline'=>$at+elevator_config('vote_window'),'opts'=>array(),'dir'=>$floor<0?-1:1,'fireseed'=>$floor===-8);
            elevator_notice($e,'终点提案：愿意留下的乘客，请在120秒内选择无尽；未选择或反对者将胜利离场。',$at);
        }
        $e['endless_vote']['opts'][$p['pid']]=(int)$arg; $s['endless_opt']=(int)$arg;
    } elseif ($command==='elevator_quit') {
        elevator_trade_cancel($p); elevator_settle_player($p,'quit',$at,$e,'你结束了本次行程。');
    } elseif ($command==='elevator_event') {
        elevator_intrusion_choice($e,$p,$arg,(string)($a['event']??''),$at);
    } elseif ($command==='elevator_fight') {
        $id=(int)$arg; $enemy=fetch_playerdata_by_pid($id);
        if (!$enemy || !elevator_can_fight($p,$enemy)) throw new RuntimeException('不能攻击这个对象。');
        elevator_focus($p,$id);
    } elseif ($command==='elevator_object') {
        if ($e['state']==='hold' && $s['in']) throw new RuntimeException('请先应对车厢内的异象。');
        elevator_interact($e,$p,(int)($a['object']??0),$arg,(string)($a['answer']??''),$at);
    } elseif ($command==='elevator_environment') {
        elevator_environment($e,$p,$arg,$at);
    } elseif ($command==='elevator_riddle') {
        elevator_fireseed($e,$p,$arg,$at);
    } elseif ($command==='elevator_recipe') {
        $s['view']=array('kind'=>'recipes','key'=>(int)$arg,'slot'=>$slot);
    } elseif ($command==='elevator_craft') {
        rr_craft($p,(int)$arg,$slot,$token,$e);
    } elseif ($command==='elevator_relic') {
        elevator_choose_relic($p,$slot,(int)$arg,$token,$e);
    } elseif ($command==='elevator_buy') {
        elevator_buy($p,(int)$arg,(string)($a['stock']??''),$e,$at);
    } elseif ($command==='elevator_sell') {
        elevator_sell($p,$slot,$token,$e,$at);
    } elseif ($command==='elevator_shop') {
        elevator_shop_talk($p,$arg,$e,$at);
    } elseif ($command==='elevator_store') {
        elevator_store($p,$slot,$token,$e);
    } elseif ($command==='elevator_withdraw') {
        elevator_withdraw($p,$slot,$token,$e);
    } elseif ($command==='elevator_offer') {
        elevator_trade_offer($p,$slot,(int)($a['target']??0),(int)($a['price']??0),$token,$e,$at);
    } elseif ($command==='elevator_accept') {
        elevator_trade_accept($p,(int)($a['seller']??0),$arg,$e,$at);
    } elseif ($command==='elevator_cancel') {
        elevator_trade_cancel($p);
    } elseif ($command==='elevator_claim') {
        if (!empty($p['itms0']) || !$s['pending']) throw new RuntimeException('请先腾出双手，或确认还有待领物品。');
        $item=array_shift($s['pending']); elevator_give($p,$item);
    } else throw new RuntimeException('未知的电梯指令。');
}
function elevator_validate_request(&$p) {
    $s=&$p['clbpara']['elevator'];
    $seq=$_POST['el_seq']??''; $csrf=$_POST['el_csrf']??'';
    if (!is_string($csrf) || !hash_equals($s['csrf'],$csrf) || !is_scalar($seq) || (string)$s['seq']!==(string)$seq) throw new RuntimeException('页面已更新，这条重复或过期的指令没有执行。');
    $s['seq']++;
}
function elevator_check_move($p,$dest) {
    $s=$p['clbpara']['elevator'];
    if ($s['in'] || !elevator_active($p)) return false;
    $data=elevator_floor_read($s['floor']);
    return $dest>=elevator_pls($s['floor']) && $dest<=elevator_pls($s['floor'],count($data['tpls']??array()));
}
function elevator_record_exploration(&$p,$at) {
    global $gamevars;
    $s=&$p['clbpara']['elevator']; if ($s['in']) return;
    $floor=$s['floor']; $index=(int)$p['pls']-elevator_pls($floor);
    if ($index<=0) return;
    if (!empty($s['watching']) && $s['watching']['pls']!==(int)$p['pls']) unset($s['watching']);
    $s['protected']=false; $s['protect_until']=0; $s['board_req']=null;
    $progress=&$s['floors'][$floor];
    if (!is_array($progress)) $progress=array();
    $progress['visited'][$index]=true;
    if (count($progress['visited'])>=2 && empty($progress['rewarded'])) {
        $progress['rewarded']=true;
        if (abs($floor)>$s['deepest']) { $s['deepest']=abs($floor); $s['depth_time']=max(0,$at-$gamevars['elevator']['start_time']); }
        $s['up_depth']=max($s['up_depth']??0,$floor-7);
        $s['down_depth']=max($s['down_depth']??0,-$floor-8);
        // 回收过的无尽层可再访，但不能反复领取探索遗物。
        if ($floor>7 && $floor<=($s['reward_up']??7)) return;
        if ($floor<-8 && $floor>=($s['reward_down']??-8)) return;
        if ($floor>7) $s['reward_up']=$floor;
        if ($floor<-8) $s['reward_down']=$floor;
        elevator_give($p,elevator_relic($floor,$gamevars['elevator']['round']));
        elevator_personal($p,'你完成了本层的两处探索，拾得一件夹层遗物。');
    }
    // 只保留有限楼层及最近八个无尽楼层的细节，历史深度用单值记录。
    elevator_trim_floor_memory($s);
}
