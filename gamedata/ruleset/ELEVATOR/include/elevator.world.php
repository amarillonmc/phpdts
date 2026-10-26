<?php
if (!defined('IN_GAME')) exit('Access Denied');

function elevator_ensure_schema() {
    global $db,$tablepre,$gtablepre;
    static $done=false; if ($done) return;
    // 只在当前房间的进程锁内扩容；旧站覆盖代码后也能进入动态楼层。
    foreach (array('players','mapitem','maptrap') as $table) {
        $q=$db->query("SHOW COLUMNS FROM {$tablepre}{$table} LIKE 'pls'");
        $col=$db->fetch_array($q);
        if ($col && strpos(strtolower($col['Type']),'tinyint')!==false) $db->query("ALTER TABLE {$tablepre}{$table} MODIFY pls int unsigned NOT NULL DEFAULT 0");
    }
    $db->query("CREATE TABLE IF NOT EXISTS {$tablepre}elevator_floor (floor int NOT NULL PRIMARY KEY, data mediumtext NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->query("CREATE TABLE IF NOT EXISTS {$gtablepre}elevator_results (
        round_id char(24) NOT NULL, username varchar(40) NOT NULL, roomid int NOT NULL,
        result varchar(12) NOT NULL, phase varchar(12) NOT NULL, direction tinyint NOT NULL DEFAULT 0,
        depth int NOT NULL DEFAULT 0, depth_time int unsigned NOT NULL DEFAULT 0, finished_at int unsigned NOT NULL,
        reward int NOT NULL DEFAULT 0, paid tinyint NOT NULL DEFAULT 0, details text NOT NULL,
        PRIMARY KEY(round_id,username), KEY ranking(phase,direction,depth,depth_time)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $done=true;
}
function elevator_floor_read($floor) {
    global $db,$tablepre;
    $q=$db->query("SELECT data FROM {$tablepre}elevator_floor WHERE floor=".(int)$floor);
    $r=$db->fetch_array($q);
    return $r ? elevator_decode($r['data']) : array();
}
function elevator_floor_save($floor,$data) {
    global $db,$tablepre;
    $json=elevator_sql(json_encode($data,JSON_UNESCAPED_UNICODE));
    $db->query("REPLACE INTO {$tablepre}elevator_floor (floor,data) VALUES (".(int)$floor.",$json)");
}
function elevator_choose_pool($floor) {
    $r=mt_rand(1,100); $up=$floor>0; $deep=abs($floor)>=6;
    if ($floor>7) return $r<=50?7:($r<=75?5:6);
    if ($floor<-8) return $r<=60?8:($r<=80?5:6);
    if ($deep) return $r<=60?($up?7:8):($r<=85?rr_pick($up?array(1,3):array(2,4)):rr_pick(array(5,6)));
    return $r<=40?($up?1:2):($r<=80?($up?3:4):($r<=95?rr_pick(array(5,6)):($up?7:8)));
}
function elevator_blueprint($floor) {
    if ($floor===-8) return array('tpls'=>array(),'objects'=>array(),'bias'=>0);
    $tpls=array(); $objects=array(); $count=mt_rand(2,5); $all=elevator_objects();
    for ($i=0;$i<$count;$i++) {
        do { $tpl=elevator_choose_pool($floor)*100+mt_rand(1,8); } while (in_array($tpl,$tpls,true));
        $tpls[]=$tpl;
    }
    $candidates=array();
    foreach ($all as $id=>$o) foreach ($tpls as $i=>$tpl) if ((int)floor($tpl/100)===$o[1]) $candidates[]=array('id'=>$id,'index'=>$i+1);
    shuffle($candidates);
    if ($floor>7 && mt_rand(1,100)<=min(80,20+($floor-7)*5)) {
        foreach ($tpls as $i=>$tpl) if ((int)floor($tpl/100)===6) { array_unshift($candidates,array('id'=>601,'index'=>$i+1)); break; }
    }
    foreach ($candidates as $o) {
        if (count($objects)>=2) break;
        $exists=false; foreach ($objects as $old) if ($old['id']===$o['id']) $exists=true;
        if (!$exists) $objects[]=$o;
    }
    return array('tpls'=>$tpls,'objects'=>$objects,'bias'=>mt_rand(0,100));
}
function elevator_ground($item,$pls) {
    global $db,$tablepre;
    $row=$item; $row['pls']=(int)$pls;
    $row['itmpara']=json_encode(elevator_decode($row['itmpara']??''),JSON_UNESCAPED_UNICODE);
    $db->array_insert($tablepre.'mapitem',$row);
}
function elevator_materialize_floor(&$e,$floor,$at) {
    if (elevator_floor_read($floor)) return;
    $plan=$e['plan'][$floor]??elevator_blueprint($floor);
    $data=$plan+array('floor'=>$floor,'spawned_at'=>$at,'objects_state'=>array());
    $catalog=elevator_catalog();
    foreach ($plan['tpls'] as $i=>$tpl) {
        $pool=(int)floor($tpl/100); $pls=elevator_pls($floor,$i+1);
        foreach ($catalog as $id=>$spec) {
            if ($spec['map']!==$tpl) continue;
            if ($spec['unique'] && isset($e['unique_items'][$id])) continue;
            $item=elevator_item($id,$e['round']);
            for ($copy=0;$copy<$spec['copies'];$copy++) elevator_ground($item,$pls);
            if ($spec['unique']) $e['unique_items'][$id]=1;
        }
        // 每个点留有实际可用的补给；随机配方所需的垃圾也在公共商品中供应。
        elevator_ground(array('itm'=>'夹层急救包','itmk'=>'HH','itme'=>100,'itms'=>2,'itmsk'=>'','itmpara'=>array()),$pls);
        elevator_ground(array('itm'=>'夹层能量饮料','itmk'=>'HS','itme'=>100,'itms'=>2,'itmsk'=>'','itmpara'=>array()),$pls);
    }
    if ($floor!==-8) {
        global $gamevars;
        elevator_ground(rr_blueprint_item(array_rand($gamevars['rr_recipes']),$floor),elevator_pls($floor,1));
        $skins=array(1=>'巷子里的混混',2=>'模因残留体',3=>'噪点体',4=>'旧红杀残兵',5=>'复制猫毛球',6=>'回声体',7=>'失控应援牌',8=>'数据碎片聚合体');
        $n=mt_rand(2,4); $elite=mt_rand(1,100)<=25;
        for ($i=0;$i<$n+($elite?1:0);$i++) {
            $index=mt_rand(1,count($plan['tpls'])); $pool=(int)floor($plan['tpls'][$index-1]/100);
            $rank=elevator_rank($floor)+($i===$n?2:0); $npc=rr_generate_npc($rank,$floor<0?25:0,$floor>0?15:0,min(20,$rank*2));
            if ($i===$n) { $npc['hp']=$npc['mhp']=(int)round(min($npc['mhp']*1.5,8000*(1+max(0,$rank-20)*.12))); $npc['clbpara']['elevator_npc']['elite']=true; }
            $npc['name']=$skins[$pool].($i===$n?'·精英':'·'.($i+1)); $npc['pls']=elevator_pls($floor,$index);
            $npc['clbpara']['elevator_npc']['floor']=$floor;
            if (mt_rand(1,100)<=10) {
                $ids=array(); foreach ($catalog as $id=>$s) if ($s['pool']===$pool && !$s['unique']) $ids[]=$id;
                foreach (elevator_item(rr_pick($ids),$e['round']) as $k=>$v) $npc[$k.'3']=$v;
            }
            elevator_insert_npc($npc,$at);
        }
    }
    elevator_floor_save($floor,$data);
}
function elevator_refresh_maps() {
    global $db,$tablepre,$hplsinfo,$areainfo;
    $hplsinfo[250]=array(9000=>'共享电梯厢'); $hplsinfo[251]=array();
    $maps=elevator_maps();
    $q=$db->query("SELECT data FROM {$tablepre}elevator_floor");
    while ($row=$db->fetch_array($q)) {
        $data=elevator_decode($row['data']); $floor=$data['floor'];
        $pls=elevator_pls($floor); $hplsinfo[251][$pls]=elevator_floor_label($floor).' · 电梯口';
        $areainfo[$pls]=$floor===-8?'灰色的会客厅里，有人替你留了一盏小灯。这里没有战斗，也没有侵蚀。':'返回这里登记候乘，就能暂停暴露预算。';
        foreach ($data['tpls'] as $i=>$tpl) {
            $loc=elevator_pls($floor,$i+1); $hplsinfo[251][$loc]=elevator_floor_label($floor).' · '.$maps[$tpl]['name'];
            $areainfo[$loc]=elevator_html($maps[$tpl]['text']);
        }
    }
}
function elevator_collect_empty_floors($e,$players) {
    global $db,$tablepre;
    if ($e['phase']!=='endless') return;
    $keep=array($e['floor']);
    foreach ($players as $p) if (elevator_active($p) && !$p['clbpara']['elevator']['in']) $keep[]=$p['clbpara']['elevator']['floor'];
    foreach (array_merge($e['route'],$e['reqs']) as $r) $keep[]=$r['floor'];
    $q=$db->query("SELECT floor FROM {$tablepre}elevator_floor"); $remove=array();
    while ($r=$db->fetch_array($q)) if (abs((int)$r['floor'])>8 && !in_array((int)$r['floor'],$keep,true) && abs((int)$r['floor']-$e['floor'])>2) $remove[]=(int)$r['floor'];
    foreach ($remove as $floor) {
        $lo=elevator_pls($floor); $hi=$lo+15;
        foreach (array('mapitem','maptrap') as $table) $db->query("DELETE FROM {$tablepre}{$table} WHERE pls BETWEEN $lo AND $hi");
        $db->query("DELETE FROM {$tablepre}players WHERE type>0 AND pls BETWEEN $lo AND $hi");
        $db->query("DELETE FROM {$tablepre}elevator_floor WHERE floor=$floor");
    }
}
