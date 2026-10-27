<?php
if (!defined('IN_GAME')) exit('Access Denied');

function rr_generate_recipe($itmk='') {
    if ($itmk==='') $itmk=rr_pick(array('WP','WK','WC','WG','WD','WF','WP','WK','WC','WG','WD','WF','DB','DH','DA','DF','A','HH','HS','HB'));
    $names=array('WP'=>'回声重锤','WK'=>'光辉短刃','WC'=>'折叠星屑','WG'=>'暮色手枪','WD'=>'瓶中雷雨','WF'=>'银色音叉',
        'DB'=>'记忆外套','DH'=>'纸月冠','DA'=>'光线护臂','DF'=>'回程长靴','A'=>'旧梦护符','HH'=>'暖光便当','HS'=>'清醒汽水','HB'=>'微光糖');
    if (!isset($names[$itmk])) $itmk='WP';
    $food=$itmk[0]==='H'; $accessory=$itmk==='A';
    $r=array('result'=>array('itm'=>'','itmk'=>$itmk,'itme'=>$accessory?1:mt_rand($food?90:160,$food?180:320),
        'itms'=>$accessory?1:mt_rand($food?20:30,$food?40:60),'itmsk'=>$food?'':rr_gen_itmsk(8,$itmk),'itmpara'=>array()),'extra'=>array());
    if ($itmk==='WD' && mt_rand(0,99)>30) $r['result']['itmsk'].='d';
    $main=mt_rand(1,2); $sub=$accessory?0:mt_rand(0,2); $extra=$accessory?mt_rand(1,3):mt_rand(0,2);
    $count=max(2,min(5,$main+$sub+$extra));
    $junk=array('生锈的螺栓','空罐装咖啡','猫毛球','玻璃化沙子（可作磨料）','浮岛边缘的石块','未完成的道具模型');
    for ($i=1;$i<=$count;$i++) {
        // 主素材按原生类别匹配，副/效果/耐久素材用楼层中实际投放的物品。
        if ($i<=$main) $s=array('kind'=>$food?'H':($itmk[0]==='D'?'D':'W'),'count'=>1,'role'=>'main');
        else $s=array('name'=>rr_pick($junk),'count'=>1,'role'=>$i<=$main+$sub?'sub':($accessory?'itmsk':rr_pick($food?array('itme','itms'):array('itme','itms','itmsk'))));
        if ($s['role']==='itme') $r['result']['itme']=mt_rand(0,1)?$r['result']['itme']+30:(int)round($r['result']['itme']*1.2);
        if ($s['role']==='itms') {
            $r['result']['itms']=mt_rand(0,1)?$r['result']['itms']+8:(int)round($r['result']['itms']*1.2);
            if (!$food && mt_rand(1,100)<=10) { $s=array('kind'=>'W','contains'=>'[+5]','count'=>1,'role'=>'itms'); $infinite=true; }
        }
        if ($s['role']==='itmsk' && !$food) $r['result']['itmsk'].=rr_gen_itmsk(5,$itmk);
        $r['stuff'.$i]=$s;
    }
    $bonus=$count===5?1.5:($count===4?1.2:1);
    $r['result']['itme']=(int)round($r['result']['itme']*$bonus);
    $r['result']['itms']=(int)round($r['result']['itms']*$bonus);
    if ($itmk[0]==='D' && $r['result']['itme']>10*$r['result']['itms']) $r['result']['itms']=mt_rand($r['result']['itms'],(int)ceil($r['result']['itme']/10));
    if (!empty($infinite)) $r['result']['itms']='∞';
    $r['result']['itmsk']=implode('',array_unique(str_split($r['result']['itmsk'])));
    $r['result']['itm']='「'.rr_pick(array('未寄出的','昨日的','微醺的','漫游的','被记得的')).$names[$itmk].'」';
    $traits=elevator_equipment_skill_pool();
    if (!$food && $traits && mt_rand(1,100)<=elevator_config('recipe_skill_rate')) {
        $trait=rr_pick($traits); $r['result']['itmpara']['elevator_skill']=$trait;
        $r['result']['itm'].=' · '.elevator_skill_definitions()[(int)substr($trait,3)][0];
    }
    $learn=mt_rand(1,100)<=70;
    $r['extra']=array('materials'=>$count,'learnable'=>$learn,'consume_recipe'=>!$learn);
    return $r;
}
function rr_create_recipes($num=30) {
    global $gamevars;
    $gamevars['rr_recipes']=array();
    for ($i=1;$i<=max(1,min(30,(int)$num));$i++) $gamevars['rr_recipes'][300+$i]=rr_generate_recipe();
    return $gamevars['rr_recipes'];
}
function rr_get_recipe($key) { global $gamevars; return $gamevars['rr_recipes'][(int)$key]??null; }
function rr_blueprint_item($key,$floor=1) {
    global $gamevars;
    $r=rr_get_recipe($key);
    return array('itm'=>($floor<0?'错误配方：':'配方残片：').($r?$r['result']['itm']:'散落的纸片'),'itmk'=>'R','itme'=>1,'itms'=>1,'itmsk'=>(string)$key,
        'itmpara'=>array('elevator_round'=>$gamevars['elevator']['round']??''));
}
function elevator_slot($p,$slot) {
    $r=array();
    foreach (array('itm','itmk','itme','itms','itmsk','itmpara') as $k) $r[$k]=$p[$k.$slot]??($k==='itme'||$k==='itms'?0:'');
    $r['itmpara']=elevator_decode($r['itmpara']); return $r;
}
function elevator_set_slot(&$p,$slot,$item=array()) {
    foreach (array('itm','itmk','itme','itms','itmsk','itmpara') as $k) $p[$k.$slot]=$item[$k]??($k==='itme'||$k==='itms'?0:'');
}
function elevator_consume(&$p,$slot,$count=1) {
    if (($p['itms'.$slot]??0)==='∞') throw new RuntimeException('这件物品不能作为消耗素材。');
    if (($p['itms'.$slot]??0)<$count) throw new RuntimeException('素材数量不足。');
    $p['itms'.$slot]-=$count;
    if (!$p['itms'.$slot]) elevator_set_slot($p,$slot);
}
function elevator_give(&$p,$item) {
    global $log;
    if (!empty($p['itms0'])) {
        // 自动探索奖励不能覆盖手里的物品；最多保留六件，其余实际落在当前地点。
        if (count($p['clbpara']['elevator']['pending'])<6) $p['clbpara']['elevator']['pending'][]=$item;
        else { elevator_ground($item,$p['pls']); elevator_personal($p,'携带空间不足，额外奖励已放在脚边。'); }
        return;
    }
    elevator_set_slot($p,0,$item);
    if (function_exists('itemget')) itemget($p);
    else {
        for ($slot=1;$slot<=6;$slot++) if (empty($p['itms'.$slot])) { elevator_set_slot($p,$slot,$item); elevator_set_slot($p,0); break; }
    }
}
function elevator_slot_token($p,$slot) { return hash('sha256',json_encode(elevator_slot($p,$slot),JSON_UNESCAPED_UNICODE)); }
function rr_materials($p,$recipe,$blueprint=-1) {
    $requirements=array();
    for ($i=1;$i<=5;$i++) if (isset($recipe['stuff'.$i])) $requirements[]=$recipe['stuff'.$i];
    usort($requirements,function($a,$b) { return (int)(isset($b['name'])||isset($b['contains']))-(int)(isset($a['name'])||isset($a['contains'])); });
    $used=array();
    foreach ($requirements as $s) {
        $left=$s['count'];
        for ($slot=1;$slot<=6 && $left>0;$slot++) {
            $item=elevator_slot($p,$slot);
            if ($slot===$blueprint || !$item['itms']) continue;
            if (isset($s['name']) && $item['itm']!==$s['name']) continue;
            if (isset($s['contains']) && strpos($item['itm'],$s['contains'])===false) continue;
            if (isset($s['kind']) && strpos($item['itmk'],$s['kind'])!==0) continue;
            $equipment=in_array(substr($item['itmk'],0,1),array('W','D','A'),true) && !preg_match('/^W[CDF]/',$item['itmk']);
            if (!$equipment && $item['itms']==='∞') continue;
            $available=($equipment?1:(int)$item['itms'])-($used[$slot]??0);
            $take=min($left,max(0,$available)); $used[$slot]=($used[$slot]??0)+$take; $left-=$take;
        }
        if ($left) return false;
    }
    return array_filter($used);
}
function rr_craft(&$p,$key,$slot,$token,$e) {
    if (!empty($p['itms0'])) throw new RuntimeException('请先处理手里的物品，再合成。');
    $r=rr_get_recipe($key); if (!$r) throw new RuntimeException('这份配方不属于本次行程。');
    $learned=!empty($p['clbpara']['rr_learned'][$key]) && ($p['clbpara']['rr_learned_round']??'')===$e['round'];
    if (!$learned) {
        if ($slot<0 || $slot>6) throw new RuntimeException('需要携带配方残片。');
        $item=elevator_slot($p,$slot);
        if ($item['itmk']!=='R' || (string)$item['itmsk']!==(string)$key || ($item['itmpara']['elevator_round']??'')!==$e['round'] || !hash_equals(elevator_slot_token($p,$slot),$token)) throw new RuntimeException('蓝图已移动或失效，请重新展开。');
    }
    $used=rr_materials($p,$r,$learned?-1:$slot);
    if ($used===false) throw new RuntimeException('素材尚未集齐。请按配方准备足够的份数。');
    foreach ($used as $s=>$n) {
        if (in_array(substr($p['itmk'.$s],0,1),array('W','D','A'),true) && !preg_match('/^W[CDF]/',$p['itmk'.$s])) elevator_set_slot($p,$s);
        else elevator_consume($p,$s,$n);
    }
    if (!$learned && $r['extra']['consume_recipe']) elevator_consume($p,$slot);
    if ($r['extra']['learnable']) {
        $p['clbpara']['rr_learned'][$key]=1; $p['clbpara']['rr_learned_round']=$e['round'];
        elevator_personal($p,'你学会了这份配方，本局后续无需携带蓝图。');
    }
    elevator_give($p,$r['result']);
}
function elevator_relic($floor,$round) {
    return array('itm'=>$floor>0?'阁楼馈赠':'钻探拾遗','itmk'=>'ER','itme'=>1,'itms'=>1,'itmsk'=>'',
        'itmpara'=>array('elevator_round'=>$round,'rr_floor'=>$floor,'rr_uid'=>bin2hex(random_bytes(8))));
}
function elevator_reward_pool($floor,$e) {
    global $gamevars;
    $pool=array(); $data=elevator_floor_read($floor); $themes=array();
    foreach ($data['tpls']??($e['plan'][$floor]['tpls']??array()) as $tpl) $themes[]=(int)floor($tpl/100);
    if (!$themes) $themes=array($floor<0?8:7);
    foreach (elevator_catalog() as $id=>$s) if (!$s['unique'] && (in_array($s['pool'],$themes,true) || in_array($s['kind'],array('HH','HS','HB'),true))) $pool[]=elevator_item($id,$e['round']);
    foreach (elevator_round_skill_pool() as $skill) $pool[]=elevator_skill_book($skill);
    $pool[]=array('itm'=>'夹层武艺手册','itmk'=>'VV','itme'=>60,'itms'=>1,'itmsk'=>'','itmpara'=>array());
    return $pool;
}
function elevator_open_relic(&$p,$slot,$e) {
    $item=elevator_slot($p,$slot);
    if ($item['itmk']!=='ER' || ($item['itmpara']['elevator_round']??'')!==$e['round']) throw new RuntimeException('这件遗物不属于本局。');
    if (empty($item['itmpara']['rr_choices'])) {
        global $gamevars;
        $floor=(int)$item['itmpara']['rr_floor']; $depth=max(0,(int)floor((abs($floor)-7)/3));
        $pool=elevator_reward_pool($floor,$e); $choices=array();
        // 不放回加权抽取：主题装备权重2，深层逐步提高强装备和技能书的权重。
        while ($pool && count($choices)<6) {
            $weights=array(); $total=0;
            foreach ($pool as $i=>$reward) {
                $weight=$reward['itmk'][0]==='H'?1:2;
                if ($reward['itme']>=100 || $reward['itmk']==='VS') $weight+=min(6,$depth);
                $total+=$weight; $weights[$i]=$total;
            }
            $roll=mt_rand(1,$total);
            foreach ($weights as $i=>$end) if ($roll<=$end) { $choices[]=$pool[$i]; unset($pool[$i]); break; }
        }
        if (mt_rand(1,100)<=min(60,10+$depth*5)) $choices[5]=rr_blueprint_item(array_rand($gamevars['rr_recipes']),$floor);
        $item['itmpara']['rr_choices']=$choices; elevator_set_slot($p,$slot,$item);
    }
    $p['clbpara']['elevator']['view']=array('kind'=>'relic','slot'=>$slot);
}
function elevator_choose_relic(&$p,$slot,$choice,$token,$e) {
    $item=elevator_slot($p,$slot);
    if ($item['itmk']!=='ER' || ($item['itmpara']['elevator_round']??'')!==$e['round'] || !hash_equals(elevator_slot_token($p,$slot),$token) || !isset($item['itmpara']['rr_choices'][$choice])) throw new RuntimeException('遗物的选项已失效，请重新打开。');
    if (!empty($p['itms0']) && $slot!==0) throw new RuntimeException('请先处理手里的物品。');
    $reward=$item['itmpara']['rr_choices'][$choice]; elevator_consume($p,$slot);
    unset($p['clbpara']['elevator']['view']); elevator_give($p,$reward);
}
