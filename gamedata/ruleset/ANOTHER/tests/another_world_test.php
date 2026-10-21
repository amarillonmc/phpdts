<?php
if(!defined('IN_GAME')) exit('Access Denied');
if(!defined('GAME_ROOT')) define('GAME_ROOT',dirname(__DIR__,4).'/');
require_once dirname(__DIR__).'/include/another.config.php';

function another_world_expect($condition,$message)
{
    if(!$condition) throw new RuntimeException($message);
}
function another_world_reachable($start,$allowed,$maps)
{
    $seen=array(); $queue=array($start);
    while($queue) {
        $at=array_shift($queue);
        if(isset($seen[$at]) || !isset($allowed[$at])) continue;
        $seen[$at]=true;
        foreach($maps[$at]['neighbors'] as $neighbor) $queue[]=$neighbor;
    }
    return $seen;
}

$cfg=another_config();
$items=require dirname(__DIR__).'/include/another.items.config.php';
another_world_expect(count($cfg['maps'])===18,'地图必须包含18个地点');
another_world_expect(count($cfg['edges'])===29,'竞技路网应包含29条无向连接');
$safe=array_fill_keys(array_keys($cfg['maps']),true);
$stocks=0;
foreach($cfg['maps'] as $id=>$map) {
    another_world_expect(isset($map['neighbors']) && count($map['neighbors'])>0,'孤立地图 '.$id);
    foreach($map['neighbors'] as $neighbor) {
        another_world_expect(isset($cfg['maps'][$neighbor]),'连接不存在的地图');
        another_world_expect(in_array($id,$cfg['maps'][$neighbor]['neighbors'],true),'地图连接不是双向');
    }
    $found=array();
    foreach($map['drops'] as $drop) {
        another_world_expect(isset($items['catalog'][$drop['item']]),'未定义的掉落 '.$drop['item']);
        another_world_expect($drop['stock']>0,'地图初始库存必须有限且为正');
        another_world_expect($drop['item']!=='null_armor','REP专供防具不可进入地图掉落');
        $stocks+=$drop['stock'];
        $found[$drop['item']]=true;
    }
    foreach(array('heal','stamina','ammo','slash','throw','armor') as $required) another_world_expect(isset($found[$required]),'基础物资缺少 '.$id.' / '.$required);
}
foreach($cfg['zones'] as $time=>$closing) {
    $next=$safe;
    foreach($closing as $id) {
        another_world_expect(isset($safe[$id]),'重复或未知禁区 '.$id);
        unset($next[$id]);
    }
    if($next) {
        another_world_expect(count(another_world_reachable(key($next),$next,$cfg['maps']))===count($next),'收缩后的安全地图不连通：'.$time);
        foreach($closing as $id) {
            $reachable=another_world_reachable($id,$safe,$cfg['maps']);
            another_world_expect(count(array_intersect_key($reachable,$next))>0,'预告期间无法撤出 '.$id);
        }
    }
    $safe=$next;
}
another_world_expect(!$safe,'65分钟后仍有安全地图');
another_world_expect(max(array_keys($cfg['zones']))===3900,'末轮不是65分钟');
$types=array();
foreach($cfg['npc_templates'] as $role=>$npc) {
    another_world_expect($npc['type']>0 && $npc['type']<=127,'NPC类型超出有符号tinyint');
    another_world_expect(!isset($types[$npc['type']]),'NPC类型重复');
    $types[$npc['type']]=true;
    another_world_expect($npc['clbpara']['another']['npc_role']===$role,'NPC角色键不一致');
    another_world_expect(is_file(GAME_ROOT.$npc['clbpara']['another']['portrait']),'缺少NPC头像');
}
foreach($cfg['npc_spawns'] as $spawn) another_world_expect(isset($cfg['npc_templates'][$spawn['template']],$cfg['maps'][$spawn['pls']]) && $spawn['count']>0,'NPC初始投放配置无效');
foreach($cfg['npc_reinforcements'] as $time=>$spawns) foreach($spawns as $spawn) {
    foreach($cfg['zones'] as $zone_time=>$closing) if($zone_time<=$time) another_world_expect(!in_array($spawn['pls'],$closing,true),'补充NPC投进已封地图');
}
$public_text=json_encode($cfg['story'],JSON_UNESCAPED_UNICODE);
another_world_expect(!preg_match('/DIAMOND|OMICRON|电掣|时空特使/u',$public_text),'公开剧情泄露了幕后身份');

// 资源加载必须清掉公共角色与地图，不能仅在页面上隐藏。
require dirname(__DIR__).'/cache/resources_1.php';
another_world_expect(count($plsinfo)===19 && $plsinfo[1]==='旧商业街集市','公共地图未被替换');
another_world_expect(count($typeinfo)===7 && !isset($typeinfo[1]),'旧NPC分类泄漏');
$npcinfo=array(1=>array('name'=>'旧NPC'));
require dirname(__DIR__).'/cache/npc_1.php';
another_world_expect($npcinfo===array(),'旧NPC初始化资源未清空');
require dirname(__DIR__).'/cache/clubskills_1.php';
require dirname(__DIR__).'/cache/titles_1.php';
another_world_expect($cskills===array() && $club_skillslist===array() && $title_valid===array(),'社团/称号能力仍有启用入口');
echo "AN:OTHER world OK: 18 maps, 29 edges, 8 closures, ".$stocks." initial ground bundles.\n";
