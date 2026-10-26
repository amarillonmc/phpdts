<?php
if (!defined('IN_GAME')) exit('Access Denied');

function rr_pick($values) { return $values[array_rand($values)]; }
function rr_gen_itmsk($rank,$kind) {
    // 常磐合法字母；不投直死、灵魂绑定、复合属性及电波连接属性。
    $pools=array('W'=>array('c','u','e','i','w','p','N','n','r','d','L','y'),
        'D'=>array('U','E','I','W','q','P','K','C','G','D','F','A','a','m','B','b'),
        'A'=>array('c','A','a','H','M','m','B','b'));
    $key=substr($kind,0,1); if (!isset($pools[$key])) return '';
    $tier=min(4,(int)ceil($rank/5));
    $pool=array_slice($pools[$key],0,min(count($pools[$key]),4+$tier*3));
    shuffle($pool);
    return implode('',array_slice($pool,0,mt_rand(0,min($tier,count($pool)))));
}
function rr_skill_pool() {
    return array(1=>array('c1_def','c10_insight'),2=>array('c1_crit','c4_break'),
        3=>array('c2_intuit','c10_insight'),4=>array('c1_veteran','c4_break'),
        5=>array('npc_flying','c1_crit'),6=>array('npc_purity','c10_insight'));
}
function rr_generate_npc($rank,$offens_tend=0,$defens_tend=0,$variety=0) {
    $rank=max(1,(int)$rank); $r=min(20,$rank); $linear=1+max(0,$rank-20)*.12;
    $variety=max(0,min(20,(int)$variety));
    $off=mt_rand(90-$variety,110+$variety)/100*(1+$offens_tend/100);
    $def=mt_rand(90-$variety,110+$variety)/100*(1+$defens_tend/100);
    $npc=array('type'=>96,'name'=>'夹层住民·'.$rank,'gd'=>'m','icon'=>0,'club'=>rr_pick(array(1,2,3,4,5,6,7,8,9,10,11,13,14,17,18,19,20,21,24)),
        // 全模式HANDOFF已将移植稿的1.35曲线改为1.15；20档后只线性增加。
        'mhp'=>(int)round(min(8000,320*pow(1.15,$r)*$def)*$linear),'msp'=>400,
        'att'=>(int)round(40*pow(1.12,$r)*$off*$linear),'def'=>(int)round(40*pow(1.12,$r)*$def*$linear),
        'lvl'=>$r*3,'money'=>140+40*($r-1),'pose'=>rr_pick(array(0,1,2,4)),'tactic'=>mt_rand(1,4),
        'state'=>0,'pgroup'=>251,'rage'=>0,'clbpara'=>array('elevator_npc'=>array('rank'=>$rank)));
    $npc['hp']=$npc['mhp']; $npc['sp']=$npc['msp'];
    $skill=(int)round(25*pow(1.25,$r)/4*$off);
    foreach (array('wp','wk','wg','wc','wd','wf') as $key) $npc[$key]=$skill;
    $npc['wepk']=rr_pick(array('WP','WK','WC','WG','WF','WD'));
    $weapons=array('WP'=>'旧式撬棍','WK'=>'裂纹短刃','WC'=>'碎片投掷器','WG'=>'旧式手枪','WF'=>'回声棱镜','WD'=>'废弃爆破包');
    $npc['wep']=$weapons[$npc['wepk']];
    $npc['wepe']=(int)round(max($r*10,pow(1.35,$r))*.6*$off*$linear);
    $npc['weps']=max(10,$r*8); $npc['wepsk']=rr_gen_itmsk($r,$npc['wepk']);
    foreach (array('arb'=>'DB','arh'=>'DH','ara'=>'DA','arf'=>'DF','art'=>'A') as $slot=>$kind) {
        $npc[$slot]=$slot==='art'?'微光护符':'旧日防护具'; $npc[$slot.'k']=$kind;
        $npc[$slot.'e']=$kind==='A'?1:(int)round(pow(1.25,$r)*3*$def*$linear);
        $npc[$slot.'s']=max(5,$r*5); $npc[$slot.'sk']=rr_gen_itmsk($r,$kind);
    }
    $skills=rr_skill_pool(); $tier=min(6,1+(int)floor($r/4));
    $npc['clbpara']['skill']=array(rr_pick($skills[$tier]));
    if ($tier>=3) $npc['clbpara']['skill'][]=rr_pick($skills[1]);
    $npc['clbpara']['skill']=array_values(array_unique($npc['clbpara']['skill']));
    foreach ($npc['clbpara']['skill'] as $sk) $npc['clbpara']['skillpara'][$sk]=array('lvl'=>min(2,(int)floor($r/8)));
    // 主要收益来自普通补给；1%蓝图、1%强化剂、2%额外补给。
    $npc['itm1']='夹层急救包'; $npc['itmk1']='HH'; $npc['itme1']=80+$r*5; $npc['itms1']=2;
    $dice=mt_rand(1,100);
    if ($dice===1 && function_exists('rr_blueprint_item')) {
        global $gamevars;
        if (!empty($gamevars['rr_recipes'])) foreach (rr_blueprint_item(array_rand($gamevars['rr_recipes'])) as $key=>$v) $npc[$key.'2']=$v;
    } elseif ($dice===2) {
        $npc['itm2']='肉体强化剂'; $npc['itmk2']='MH'; $npc['itme2']=10; $npc['itms2']=1;
    } elseif ($dice<=4) {
        $npc['itm2']='夹层能量饮料'; $npc['itmk2']='HS'; $npc['itme2']=150; $npc['itms2']=2;
    }
    return $npc;
}
function elevator_insert_npc($npc,$at) {
    global $db,$tablepre;
    $row=array_merge(create_dummy_playerdata(),$npc);
    $row['validtime']=$row['endtime']=$at;
    unset($row['pid']);
    $row=player_format_with_db_structure($row); unset($row['pid']);
    $db->array_insert($tablepre.'players',$row);
    return (int)$db->insert_id();
}
function rr_add_randnpc($rank,$num,$offens_tend=0,$defens_tend=0,$variety=0,$pls=0,$addnews=0) {
    global $now;
    $ids=array();
    for ($i=0;$i<$num;$i++) {
        $npc=rr_generate_npc($rank,$offens_tend,$defens_tend,$variety); $npc['pls']=(int)$pls;
        $ids[]=elevator_insert_npc($npc,$now);
    }
    return $ids;
}
