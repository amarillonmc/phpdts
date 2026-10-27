<?php
if (!defined('IN_GAME')) exit('Access Denied');

function elevator_merchant($e,$at,$refresh=true) {
    global $db,$tablepre;
    $q=$db->query("SELECT * FROM {$tablepre}players WHERE type=98 LIMIT 1"); $npc=$db->fetch_array($q);
    if (!$npc) return null;
    $npc['clbpara']=elevator_decode($npc['clbpara']);
    if (!$refresh || $npc['hp']<=0 || !empty($npc['clbpara']['hostile'])) return $npc;
    $skins=array('神秘的兜售者','废土拾荒者','自动贩卖机','披着围巾的它');
    $npc['name']=$e['floor']<=-6?$skins[3]:($e['floor']<0?$skins[1]:$skins[0]);
    if (abs($e['floor'])%5===0) $npc['name']=$skins[2];
    if (($npc['clbpara']['stock_until']??0)>$at) { player_save($npc); return $npc; }
    $stock=array(); $pool=elevator_reward_pool($e['floor'],$e); shuffle($pool);
    // 常备补给和配方素材；唯一剧情物品仍只从楼层投放，不由商店复制。
    foreach (array('猫粮罐头','自动贩卖机饮料','急救绷带','生锈的螺栓','空罐装咖啡','猫毛球','玻璃化沙子（可作磨料）','浮岛边缘的石块','未完成的道具模型') as $name) {
        $id=elevator_item_id($name); if (!$id) continue;
        $item=elevator_item($id,$e['round']);
        $stock[]=array('item'=>$item,'price'=>35,'count'=>8);
    }
    $stock[]=array('item'=>elevator_item(9001,$e['round']),'price'=>15,'count'=>12);
    foreach (array_slice($pool,0,4) as $item) $stock[]=array('item'=>$item,'price'=>max(40,(int)round((80+abs($e['floor'])*18)*mt_rand(80,120)/100)),'count'=>mt_rand(1,3));
    global $gamevars;
    $stock[]=array('item'=>array('itm'=>'旧式撬棍[+5]','itmk'=>'WP','itme'=>120,'itms'=>50,'itmsk'=>'','itmpara'=>array()),'price'=>180,'count'=>1);
    $stock[]=array('item'=>rr_blueprint_item(array_rand($gamevars['rr_recipes']),$e['floor']),'price'=>100,'count'=>mt_rand(1,3));
    $stock[]=array('item'=>elevator_relic($e['floor'],$e['round']),'price'=>150,'count'=>2);
    $npc['clbpara']['stock']=$stock; $npc['clbpara']['stock_until']=$at+180;
    $npc['clbpara']['stock_id']=bin2hex(random_bytes(8)); player_save($npc); return $npc;
}
function elevator_cabin_service($p,$e) {
    if (!elevator_active($p) || !$p['clbpara']['elevator']['in'] || $p['pls']!==9000 || in_array($e['state'],array('hold','ended'),true)) throw new RuntimeException('请在没有入侵的车厢内办理。');
}
function elevator_buy(&$p,$index,$stock_id,$e,$at) {
    elevator_cabin_service($p,$e);
    if (!empty($p['itms0'])) throw new RuntimeException('请先处理手里的物品。');
    $npc=elevator_merchant($e,$at);
    if (!$npc || $npc['hp']<=0 || !empty($npc['clbpara']['hostile'])) throw new RuntimeException('行商已经不再营业。');
    $stock=&$npc['clbpara']['stock'];
    if (($npc['clbpara']['stock_id']??'')!==$stock_id || !isset($stock[$index]) || $stock[$index]['count']<1) throw new RuntimeException('库存已经变化，请重新查看货架。');
    $price=(int)ceil($stock[$index]['price']*(!empty($p['clbpara']['elevator']['discount'])?.8:1));
    if ($p['money']<$price) throw new RuntimeException('现金不足。');
    $p['money']-=$price; unset($p['clbpara']['elevator']['discount']); $stock[$index]['count']--;
    player_save($npc); elevator_give($p,$stock[$index]['item']);
}
function elevator_shop_talk(&$p,$choice,&$e,$at) {
    elevator_cabin_service($p,$e); $npc=elevator_merchant($e,$at);
    if (!$npc || $npc['hp']<=0 || !empty($npc['clbpara']['hostile'])) throw new RuntimeException('对方已不愿交谈。');
    if ($choice==='info') {
        if ($p['money']<30) throw new RuntimeException('情报需要30现金。');
        $p['money']-=30; elevator_personal($p,elevator_next_hint($e));
    } elseif ($choice==='tip') {
        if ($p['money']<10) throw new RuntimeException('小费需要10现金。');
        if (!empty($p['clbpara']['elevator']['discount'])) throw new RuntimeException('下次购物的八折已经为你留好了。');
        $p['money']-=10; $p['clbpara']['elevator']['discount']=true;
    } elseif ($choice==='attack') {
        if ($e['floor']===-8) throw new RuntimeException('灰色会客厅的灯下，没有人会举起武器。');
        $npc['clbpara']['hostile']=1;
        $goods=array_filter($npc['clbpara']['stock'],function($s){ return $s['count']>0; }); shuffle($goods);
        foreach (array_slice($goods,0,6) as $i=>$row) elevator_set_slot($npc,$i+1,$row['item']);
        player_save($npc); elevator_focus($p,(int)$npc['pid']);
    } else throw new RuntimeException('请选择购物、情报、小费或离开。');
}
function elevator_sale_price($item) {
    if (strpos($item['itm'],'Query failed')!==false) return 120;
    return max(5,min(100,(int)round($item['itme']/4)+5));
}
function elevator_sell(&$p,$slot,$token,$e,$at) {
    elevator_cabin_service($p,$e); $npc=elevator_merchant($e,$at);
    if (!$npc || $npc['hp']<=0 || !empty($npc['clbpara']['hostile'])) throw new RuntimeException('行商已经不再营业。');
    if ($slot<0 || $slot>6 || empty($p['itms'.$slot]) || !hash_equals(elevator_slot_token($p,$slot),$token)) throw new RuntimeException('出售物已变化，请重新选择。');
    $item=elevator_slot($p,$slot); $p['money']+=elevator_sale_price($item); elevator_set_slot($p,$slot);
    elevator_personal($p,'行商收下了'. $item['itm'].'。');
}
function elevator_store(&$p,$slot,$token,$e) {
    elevator_cabin_service($p,$e);
    if ($slot<0 || $slot>6 || empty($p['itms'.$slot]) || !hash_equals(elevator_slot_token($p,$slot),$token)) throw new RuntimeException('物品已变化，请重新选择。');
    $box=&$p['clbpara']['elevator']['box'];
    if (count($box)>=6) throw new RuntimeException('六格个人保管箱已经满了。');
    $box[]=elevator_slot($p,$slot); elevator_set_slot($p,$slot);
}
function elevator_withdraw(&$p,$slot,$token,$e) {
    elevator_cabin_service($p,$e); $box=&$p['clbpara']['elevator']['box'];
    if (!empty($p['itms0'])) throw new RuntimeException('请先处理手里的物品。');
    if (!isset($box[$slot]) || !hash_equals(hash('sha256',json_encode($box[$slot],JSON_UNESCAPED_UNICODE)),$token)) throw new RuntimeException('箱内物品已变化。');
    $item=$box[$slot]; array_splice($box,$slot,1); elevator_give($p,$item);
}
// 报价物冻结在发起人的clbpara，接收人明确同意后才交付；离梯/入侵会取消。
function elevator_trade_cancel(&$p) {
    $s=&$p['clbpara']['elevator'];
    if (empty($s['trade'])) return;
    $item=$s['trade']['item']; unset($s['trade']); elevator_give($p,$item);
    elevator_personal($p,'交易取消，报价物已退还。');
}
function elevator_trade_offer(&$p,$slot,$target,$price,$token,$e,$at) {
    elevator_cabin_service($p,$e);
    $other=fetch_playerdata_by_pid($target);
    if (!$other || !elevator_active($other) || empty($other['clbpara']['elevator']['in']) || (int)$other['pid']===(int)$p['pid']) throw new RuntimeException('请选择同车厢的另一位乘客。');
    if (!empty($p['clbpara']['elevator']['trade'])) throw new RuntimeException('请先结束上一笔报价。');
    if ($slot<0 || $slot>6 || empty($p['itms'.$slot]) || !hash_equals(elevator_slot_token($p,$slot),$token) || $price<0 || $price>1000000) throw new RuntimeException('报价无效。');
    $p['clbpara']['elevator']['trade']=array('id'=>bin2hex(random_bytes(8)),'target'=>(int)$target,'price'=>$price,'item'=>elevator_slot($p,$slot),'until'=>$at+120);
    elevator_set_slot($p,$slot);
}
function elevator_trade_accept(&$p,$seller,$offer_id,$e,$at) {
    elevator_cabin_service($p,$e); $other=fetch_playerdata_by_pid($seller);
    if (!$other || !elevator_active($other) || empty($other['clbpara']['elevator']['in'])) throw new RuntimeException('对方已离开车厢。');
    $offer=$other['clbpara']['elevator']['trade']??array();
    if (!$offer || $offer['target']!==(int)$p['pid'] || $offer['id']!==$offer_id || $offer['until']<=$at) throw new RuntimeException('这笔报价已经失效。');
    if (!empty($p['itms0']) || $p['money']<$offer['price']) throw new RuntimeException('请腾出双手，并准备足够现金。');
    $p['money']-=$offer['price']; $other['money']+=$offer['price']; unset($other['clbpara']['elevator']['trade']);
    player_save($other); elevator_give($p,$offer['item']);
}
