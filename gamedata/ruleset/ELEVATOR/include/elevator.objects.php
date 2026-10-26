<?php
if (!defined('IN_GAME')) exit('Access Denied');

function elevator_find_named($p,$name) {
    for ($i=0;$i<=6;$i++) if (!empty($p['itms'.$i]) && $p['itm'.$i]===$name) return $i;
    return -1;
}
function elevator_object_spawn($id,$p,$floor,$at) {
    $objects=elevator_objects(); $role=$objects[$id][3]; $rank=elevator_rank($floor);
    $npc=rr_generate_npc($rank,$floor<0?25:0,$floor>0?15:0,10);
    $npc['name']=$objects[$id][0]; $npc['pls']=$p['pls'];
    $npc['clbpara']['elevator_npc']=array('object'=>$id,'floor'=>$floor,'rounds'=>0);
    if ($role==='mirror') {
        $npc['name']=$p['name'];
        foreach (array('mhp','msp','att','def','lvl','wp','wk','wg','wc','wd','wf','pose','tactic') as $key) $npc[$key]=$p[$key];
        foreach (array('wep','wep2','arb','arh','ara','arf','art') as $slot) foreach (array('','k','e','s','sk','para') as $suffix) $npc[$slot.$suffix]=$p[$slot.$suffix]??'';
        $npc['hp']=$npc['mhp']; $npc['sp']=$npc['msp']; $npc['club']=0;
        $npc['clbpara']['skill']=array('c10_insight'); $npc['money']=0;
        $npc['clbpara']['elevator_npc']['owner']=$p['pid'];
    }
    if ($role==='turret') { $npc['wep']='固定弹道炮'; $npc['wepk']='WG'; $npc['tactic']=2; }
    if ($role==='error') $npc['wep']='截断SQL';
    if ($role==='tap') { $npc['name']='复制水龙头·原始体'; $npc['hp']=$npc['mhp']=max(120,(int)round($npc['mhp']*.6)); }
    for ($slot=1;$slot<=6;$slot++) elevator_set_slot($npc,$slot);
    return elevator_insert_npc($npc,$at);
}
function elevator_focus(&$p,$pid) {
    global $log;
    $enemy=fetch_playerdata_by_pid((int)$pid);
    if (!$enemy || $enemy['hp']<=0 || $enemy['pls']!=$p['pls']) throw new RuntimeException('这里已经安静下来了。');
    if (!elevator_can_fight($p,$enemy)) throw new RuntimeException('这个对象不在你当前的战场中。');
    $p['action']='enemy'; $p['bid']=(int)$pid;
    require_once GAME_ROOT.'include/game/revbattle.func.php';
    \revbattle\findenemy_rev($enemy);
}
function elevator_interact(&$e,&$p,$id,$choice,$answer,$at) {
    $s=&$p['clbpara']['elevator'];
    if ($s['in']) throw new RuntimeException('请先下车，走到交互对象所在的地点。');
    $floor=$s['floor']; $data=elevator_floor_read($floor); $index=$p['pls']-elevator_pls($floor);
    $present=false; foreach ($data['objects']??array() as $o) if ($o['id']===$id && $o['index']===$index) $present=true;
    $objects=elevator_objects();
    if (!$present || !isset($objects[$id])) throw new RuntimeException('这里没有这个对象。');
    $key=(string)$id; $progress=&$s['floors'][$floor]['objects'][$key];
    if (!is_array($progress)) $progress=array();
    if (!empty($progress['done'])) { elevator_personal($p,'这段相遇已记在你的笔记里。'); return; }
    if ($choice==='leave') { elevator_personal($p,'你向对方点头，留出安静的距离。'); return; }
    $role=$objects[$id][3];
    if (in_array($role,array('turret','burnt','soldier','tap','mirror','error','half'),true)) {
        if ($choice!=='fight') throw new RuntimeException('请选择靠近挑战，或离开。');
        $battlekey=$role==='mirror'?$key.'_'.$p['pid']:$key;
        if (empty($data['objects_state'][$battlekey]['pid'])) {
            $data['objects_state'][$battlekey]['pid']=elevator_object_spawn($id,$p,$floor,$at);
            elevator_floor_save($floor,$data);
        }
        elevator_focus($p,(int)$data['objects_state'][$battlekey]['pid']); return;
    }
    if ($role==='cat' || $role==='catloot') {
        $slot=elevator_find_named($p,'猫粮罐头'); if ($slot<0) throw new RuntimeException('你还没有猫粮罐头。车厢行商常备猫粮。');
        elevator_consume($p,$slot); $s['cats']++;
        if ($role==='cat') elevator_personal($p,mt_rand(0,1)?elevator_next_hint($e):'猫老大暗示：战斗入侵之后，至少两次停靠和60秒内不会再有入侵。');
        else elevator_intrusion_reward($p,$e,array(5));
    } elseif ($role==='student') {
        $progress['talks']=($progress['talks']??0)+1;
        $lines=array('“教室换地方了吗？我记得这里有一扇窗。”','“啊，今天已经放暑假了。谢谢你还肯听我说话。”','“这个给你吧。你回去的时候，记得看站名。”');
        elevator_personal($p,$lines[min(2,$progress['talks']-1)]);
        if ($progress['talks']<3) return;
        elevator_give($p,elevator_item(elevator_item_id('自动贩卖机饮料'),$e['round']));
    } elseif ($role==='guard') {
        if (elevator_find_named($p,'金龙通讯社记者证')>=0 || elevator_find_named($p,'过期学生卡')>=0) elevator_personal($p,'保安侧身让开：“别走太深。猫知道下一层的消息。”');
        else { $s['budget']=max(0,$s['budget']-10); elevator_entrance($p,$floor,$at,false); elevator_personal($p,'没有证件，你被请回电梯口。解释花去了10秒探索预算。'); }
    } elseif ($role==='projection') {
        if ($choice!=='touch') throw new RuntimeException('请选择触碰或离开。');
        if (mt_rand(0,1)) { $s['blessing_until']=$at+60; elevator_personal($p,'银色微光包住了你。60秒内攻击、防御与命中提升10%。'); }
        else { $s['budget']=max(0,$s['budget']-20); elevator_personal($p,'投影抖动了一下。探索预算减少20秒。'); }
    } elseif ($role==='scavenger') {
        if ($choice==='info') { elevator_personal($p,elevator_next_hint($e)); }
        elseif ($choice==='trade') {
            $fake=array('stuff1'=>array('kind'=>'HH','count'=>2)); $used=rr_materials($p,$fake);
            if ($used===false) throw new RuntimeException('拾荒者需要两份回血食物。');
            foreach ($used as $slot=>$n) elevator_consume($p,$slot,$n);
            $r=rr_generate_recipe('WP'); elevator_give($p,$r['result']);
        } else throw new RuntimeException('请选择询问情报或交换武器。');
    } elseif ($role==='forest') {
        $answer=trim(preg_replace('/\s+/u',' ',$answer));
        if ($answer!==elevator_cipher('life game')) { $progress['done']=true; elevator_personal($p,'幻影摇摇头，散作林间的雾。正确的口哨对应 LIFE GAME，密码手册里有字母表。'); return; }
        $r=rr_generate_recipe('DB'); elevator_give($p,$r['result']); elevator_personal($p,'幻影把一件护甲留在了树影里。');
    } elseif ($role==='data') {
        if (!in_array($choice,array('yes','unsure'),true)) throw new RuntimeException('请选择一个回答。');
        if ($choice==='unsure') { $s['buff_until']=$at+30; elevator_personal($p,'“愿意承认不知道，也是一种真实。”你获得30秒鼓舞。'); }
        else { $s['blind_until']=$at+30; elevator_personal($p,'轮廓暂时模糊了。30秒内命中下降。'); }
    } elseif ($role==='fragment') {
        $fragment=1+(abs($floor)+$index)%3;
        $s['fragments'][$fragment]=true;
        elevator_personal($p,elevator_cipher(array(1=>'words left behind',2=>'open the box',3=>'the gentlest part')[$fragment]));
        if (count($s['fragments'])>=3 && empty($s['fragment_reward'])) {
            $s['fragment_reward']=true; $s['dictionary']=true;
            elevator_give($p,array('itm'=>'种火语字典残页','itmk'=>'X','itme'=>1,'itms'=>1,'itmsk'=>'','itmpara'=>array('elevator_dictionary'=>true)));
            elevator_personal($p,'三句口哨合成一张字典残页。你开始听懂灰色会客厅里的英文。');
        }
    } elseif ($role==='maple') {
        if (elevator_find_named($p,'「M.D.」书架的旧书')<0) { elevator_personal($p,'“书在深夜图书馆的架子上。我记得第一句是 Words left behind。”'); return; }
        $s['dictionary']=true; elevator_personal($p,'Maple把书翻到字母表那页：“拿着它，别把那个人留下的话听错了。”');
    } elseif ($role==='farshadow') {
        elevator_personal($p,'你触碰银白盒。远影看向这里，像确认了一件小事，随后离去。');
    } elseif ($role==='watch') {
        if (empty($s['watching']) || $s['watching']['pls']!==$p['pls']) { $s['watching']=array('pls'=>$p['pls'],'since'=>$at); elevator_personal($p,'你在台前坐下。请留在这里听满30秒。'); return; }
        if ($at-$s['watching']['since']<30) throw new RuntimeException('歌还没有唱完。请留在同一地点。');
        elevator_personal($p,elevator_cipher('thank you for staying').' ……你感到一种奇异的慰藉。');
        elevator_give($p,elevator_item(elevator_item_id('「0 viewers」的截图'),$e['round']));
    } elseif ($role==='remnant') {
        $progress['talks']=($progress['talks']??0)+1;
        elevator_personal($p,array('“我们不该看那个。”','“你们也不该看。”','“但如果一定要走……别忘了回来的门。”')[min(2,$progress['talks']-1)]);
        if ($progress['talks']<3) return;
        elevator_intrusion_reward($p,$e,array(8));
    } elseif ($role==='relic') {
        elevator_personal($p,'“如果有人问起，就说这里曾经有过一段故事。”残响向你鞠躬。');
        elevator_give($p,elevator_relic($floor,$e['round']));
    } elseif ($role==='bead') {
        elevator_give($p,elevator_item(elevator_item_id('无名的玻璃珠'),$e['round']));
        elevator_personal($p,'珠子映出你的脸。残影点点头，像终于放下了一件心事。');
    } else {
        elevator_intrusion_reward($p,$e); elevator_personal($p,'回声终于跟上了你的声音，把一份小小的礼物留在你手中。');
    }
    $progress['done']=true;
}
function elevator_environment(&$e,&$p,$choice,$at) {
    $s=&$p['clbpara']['elevator']; $floor=$s['floor'];
    $data=elevator_floor_read($floor); $index=$p['pls']-elevator_pls($floor);
    $tpl=$data['tpls'][$index-1]??0;
    if (!$tpl || $s['in']) throw new RuntimeException('请先走入楼层。');
    $progress=&$s['floors'][$floor]['environment'][$index];
    if (!empty($progress['done'])) throw new RuntimeException('这里的发现已经收好了。');
    if ($tpl===507) {
        $step=(int)($progress['step']??0);
        if ($choice!==array('left','right','left')[$step]) { $progress['step']=0; elevator_personal($p,'回声回到起点。画框提示：左、右、左。'); return; }
        $progress['step']=$step+1; if ($step<2) { elevator_personal($p,'回声离你更近了一点。'); return; }
    } elseif ($tpl===604) {
        $slot=elevator_find_named($p,'锁着的门的钥匙');
        if ($choice==='unlock' && $slot>=0) elevator_consume($p,$slot);
        elseif ($choice==='knock') { $s['budget']=max(0,$s['budget']-10); elevator_personal($p,'敲门与等待花了10秒，门终于开了。'); }
        else throw new RuntimeException('可以使用本层钥匙，也可以花10秒预算敲门。');
    } elseif ($floor>7) {
        $words=array('light','home','listen','remember','yesterday','a gentle promise','the light you left behind');
        $level=min(4,(int)floor(($floor-8)/3)); $word=rr_pick(array_slice($words,$level,3));
        if (empty($progress['word'])) { $progress['word']=$word; elevator_personal($p,'墙上浮现：'.elevator_cipher($word).'。请按手册输入英文。'); return; }
        if (strtolower(trim($choice))!==$progress['word']) { $s['budget']=max(0,$s['budget']-5); throw new RuntimeException('口哨没有接上。损失5秒预算，可以再试。'); }
    } elseif ($choice!=='inspect') throw new RuntimeException('请选择查看场景。');
    $progress['done']=true;
    if ($tpl===504) elevator_personal($p,'广播用口哨反复念着字母。搜索这里，也许能找到记录对应关系的手册；读过的乘客也可以把手册交给同伴。');
    elseif ($tpl===507 || $tpl===604 || $floor>7) elevator_give($p,elevator_relic($floor,$e['round']));
    else elevator_personal($p,elevator_maps()[$tpl]['text']);
}
function elevator_fireseed(&$e,&$p,$choice,$at) {
    $s=&$p['clbpara']['elevator'];
    if ($s['in'] || $s['floor']!==-8 || $p['pls']!==elevator_pls(-8)) throw new RuntimeException('那位旧梦在更深处的灰色会客厅等你。');
    $questions=elevator_questions(); $step=(int)($s['riddle_step']??0);
    if ($step>=3) throw new RuntimeException('三个问题都已经听完。你可以选择离开，或继续向下。');
    if (!preg_match('/^[0-3]$/',(string)$choice)) throw new RuntimeException('请选择一个回答。');
    if ((int)$choice===$questions[$step][2]) { $s['riddle_correct']=($s['riddle_correct']??0)+1; elevator_personal($p,$questions[$step][3]); }
    else elevator_personal($p,'That is all right. Thank you for listening.');
    $s['riddle_step']=$step+1;
    if ($s['riddle_step']===3) {
        elevator_personal($p,'Thank you for listening to an old dream. You may leave now, or keep walking.');
        if (($s['riddle_correct']??0)===3) {
            $s['dictionary']=true; $s['perfect_riddle']=true;
            elevator_give($p,array('itm'=>'种火语字典残页','itmk'=>'X','itme'=>1,'itms'=>1,'itmsk'=>'','itmpara'=>array('elevator_dictionary'=>true)));
        }
    }
}
