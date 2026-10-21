<?php
if (!defined('IN_GAME')) exit('Access Denied');

function another_items_config()
{
    static $cfg = null;
    if ($cfg === null) $cfg = include __DIR__.'/another.items.config.php';
    return $cfg;
}

function another_item_catalog()
{
    $cfg = another_items_config();
    return $cfg['catalog'];
}

function &another_items_state(&$player)
{
    if (!isset($player['clbpara']) || !is_array($player['clbpara'])) {
        $decoded = isset($player['clbpara']) ? json_decode($player['clbpara'], true) : array();
        $player['clbpara'] = is_array($decoded) ? $decoded : array();
    }
    if (!isset($player['clbpara']['another']) || !is_array($player['clbpara']['another'])) $player['clbpara']['another'] = array();
    return $player['clbpara']['another'];
}

function another_next_id(&$room)
{
    $room['next_id'] = isset($room['next_id']) ? max(1, intval($room['next_id'])) + 1 : 1;
    return $room['next_id'];
}

function another_item_make($id, $count = null)
{
    $catalog = another_item_catalog();
    if (!isset($catalog[$id])) return array();
    $def = $catalog[$id];
    $meta = array('id' => $id);
    foreach (array('tier','effects','nullify','poisoned') as $key) if (isset($def[$key])) $meta[$key] = $def[$key];
    if (substr($def['kind'], 0, 1) === 'W') {
        $meta['uid'] = bin2hex(random_bytes(8));
        $meta['wear'] = 0;
        if ($def['kind'] === 'WG') $meta['ammo'] = isset($def['ammo']) ? $def['ammo'] : 0;
    }
    return array('name'=>$def['name'], 'kind'=>$def['kind'], 'effect'=>$def['effect'],
        'count'=>$count === null ? $def['count'] : max(0, intval($count)), 'attrs'=>'', 'para'=>array('another'=>$meta));
}

function another_item_fields($slot)
{
    if (preg_match('/^(?:itm)?([0-6])$/', (string)$slot, $match)) {
        $n = $match[1];
        return array('name'=>'itm'.$n, 'kind'=>'itmk'.$n, 'effect'=>'itme'.$n,
            'count'=>'itms'.$n, 'attrs'=>'itmsk'.$n, 'para'=>'itmpara'.$n);
    }
    if (!in_array($slot, array('wep','arb','arh','ara','arf','art'), true)) return array();
    return array('name'=>$slot,'kind'=>$slot.'k','effect'=>$slot.'e','count'=>$slot.'s','attrs'=>$slot.'sk','para'=>$slot.'para');
}

function another_item_get($player, $slot)
{
    $fields = another_item_fields($slot);
    if (!$fields) return array();
    $item = array();
    foreach ($fields as $key=>$field) $item[$key] = isset($player[$field]) ? $player[$field] : '';
    if (!is_array($item['para'])) {
        $decoded = json_decode((string)$item['para'], true);
        $item['para'] = is_array($decoded) ? $decoded : array();
    }
    $item['count'] = intval($item['count']);
    $item['effect'] = intval($item['effect']);
    return $item;
}

function another_item_set(&$player, $slot, $item)
{
    $fields = another_item_fields($slot);
    if (!$fields) return false;
    foreach ($fields as $key=>$field) $player[$field] = isset($item[$key]) ? $item[$key] : (in_array($key,array('count','effect'),true) ? 0 : ($key === 'para' ? array() : ''));
    return true;
}

function another_item_present($item)
{
    return !empty($item['name']) && !empty($item['kind']) && isset($item['count']) && $item['count'] > 0;
}

function another_item_id($item)
{
    return isset($item['para']['another']['id']) ? $item['para']['another']['id'] : '';
}

function another_item_description($item)
{
    $catalog=another_item_catalog(); $id=another_item_id($item); $cfg=another_items_config();
    if (!isset($catalog[$id])) return '';
    $def=$catalog[$id]; $text=array(); $meta=isset($item['para']['another'])?$item['para']['another']:array();
    if (substr($item['kind'],0,1)==='W') {
        $text[]='效果'.$item['effect'];
        if ($item['kind']!=='WC') $text[]='磨损'.round(isset($meta['wear'])?$meta['wear']:0,1).'%';
        if ($item['kind']==='WG') $text[]='已装弹'.intval(isset($meta['ammo'])?$meta['ammo']:0).'发';
        $names=array('poison'=>'施毒触发率','burn'=>'灼伤触发率','slow'=>'迟缓触发率','pierce'=>'防具穿透','bonus'=>'附加伤害');
        if (!empty($meta['effects'])) foreach($meta['effects'] as $kind=>$power) if(isset($names[$kind])) $text[]=$names[$kind].$power.($kind==='bonus'?'':'%');
    }
    if (substr($item['kind'],0,1)==='D') $text[]='防具效果'.$item['effect'];
    if (!empty($meta['nullify'])) $text[]='抹消武器的附加属性伤害与状态；基础攻击仍有效';
    if (isset($def['hp'])) $text[]='回复有效最大生命的'.$def['hp'].'%';
    if (isset($def['sp'])) $text[]='回复有效最大体力的'.$def['sp'].'%';
    if (isset($def['hp']) || isset($def['sp'])) {
        $text[]='消化'.$cfg['digestion'].'秒';
        if ($id==='poison_food') $text[]='明显变质';
        elseif (!empty($meta['tested'])) $text[]=!empty($meta['poisoned'])?'检出毒素':'检验未检出毒素';
        else $text[]='未检验';
    }
    $uses=array('ammo'=>'用于给射系武器补弹','gun_part'=>'组装升级枪械，确定成功','whetstone'=>'斩系磨刀强化',
        'gem'=>'为斩系镶嵌毒、灼伤、迟缓或穿甲属性','armor_patch'=>'强化防具效果','high_part'=>'用于高阶射／斩／防具整备',
        'repair'=>'维修斩／射武器，去除'.$cfg['repair_wear'].'点磨损','radio'=>'携带后可以远购普通商品，猎鹰配送',
        'satellite'=>'携带后可消耗REP呼叫气球；木之多彩可尝试异常终止','poison'=>'给一份补给下毒',
        'poison_test'=>'检查一叠补给是否含毒','food_antidote'=>'给一份食品解毒；用于无毒食品会产生毒性',
        'antidote'=>'解除自己已中的毒','scrap'=>'用于合成装备、整备材料和陷阱');
    if(isset($uses[$id])) $text[]=$uses[$id];
    if(isset($def['text'])) $text[]=$def['text'];
    if(isset($def['trap'])) $text[]='可布设；'.($item['effect']>0?'伤害为有效最大生命的'.$item['effect'].'%；':'').'触发时报告位置'.($def['trap']==='alarm'?'': '并可能施加状态');
    if(isset($def['bundle'])) { $parts=array(); foreach($def['bundle'] as $part=>$count) $parts[]=$catalog[$part]['name'].'×'.$count; $text[]='打开获得'.implode('、',$parts); }
    return implode('；',$text);
}

function another_item_stackable($a, $b)
{
    if (!another_item_present($a) || !another_item_present($b)) return false;
    if ($a['kind'] === 'WK' || $a['kind'] === 'WG' || substr($a['kind'],0,1) === 'D') return false;
    $pa = $a['para']; $pb = $b['para'];
    unset($pa['another']['uid'], $pb['another']['uid']);
    return $a['name'] === $b['name'] && $a['kind'] === $b['kind'] && $a['effect'] == $b['effect']
        && $a['attrs'] === $b['attrs'] && $pa == $pb && $a['count'] + $b['count'] <= 99999;
}

// 背包不足时完全不修改原库存，由调用者保留货物或撤销整项操作。
function another_item_add(&$player, $item)
{
    if (!another_item_present($item)) return false;
    for ($slot=1; $slot<=6; $slot++) {
        $old = another_item_get($player,$slot);
        if (another_item_stackable($old,$item)) {
            $old['count'] += $item['count'];
            return another_item_set($player,$slot,$old);
        }
    }
    for ($slot=1; $slot<=6; $slot++) if (!another_item_present(another_item_get($player,$slot))) return another_item_set($player,$slot,$item);
    return false;
}

function another_item_consume(&$player, $slot, $count=1)
{
    $item = another_item_get($player,$slot);
    $count = intval($count);
    if ($count < 1 || !another_item_present($item) || $item['count'] < $count) return false;
    $item['count'] -= $count;
    another_item_set($player,$slot,$item['count'] > 0 ? $item : array());
    return true;
}

function another_item_find($player, $id)
{
    for ($slot=1; $slot<=6; $slot++) {
        $item = another_item_get($player,$slot);
        if (another_item_present($item) && another_item_id($item) === $id) return $slot;
    }
    return false;
}

function another_items_notice(&$player, $text, $now)
{
    if (function_exists('another_personal_notice')) { another_personal_notice($player,$text,$now); return; }
    $state =& another_items_state($player);
    if (!isset($state['messages'])) $state['messages'] = array();
    $state['messages'][] = array('time'=>$now, 'text'=>$text);
    $state['messages'] = array_slice($state['messages'], -25);
}

function another_items_ground(&$room, $item, $pls)
{
    $id = another_next_id($room);
    $room['ground'][$id] = array('id'=>$id,'pls'=>intval($pls),'item'=>$item);
    return $id;
}

function another_items_use(&$actor, &$players, $slot, $now, &$messages)
{
    $item = another_item_get($actor,$slot);
    $catalog = another_item_catalog(); $id = another_item_id($item); $cfg = another_items_config();
    if (!another_item_present($item) || !isset($catalog[$id])) { $messages[]='这里没有可使用的物品。'; return false; }
    $def = $catalog[$id]; $state =& another_items_state($actor);
    if (isset($def['hp']) || isset($def['sp'])) {
        if (!empty($state['digestion_until']) && $state['digestion_until'] > $now) {
            $messages[]='补给尚在消化，还需等待'.($state['digestion_until']-$now).'秒。'; return false;
        }
        $quick = another_talent($actor,'S5') > 0;
        $ratio = $quick ? $cfg['quick_recovery'] : 1;
        $hp = isset($def['hp']) ? intval(floor(another_max_hp($actor)*$def['hp']/100*$ratio)) : 0;
        $sp = isset($def['sp']) ? intval(floor(another_max_sp($actor)*$def['sp']/100*$ratio)) : 0;
        $actualhp = max(0,min($hp,another_max_hp($actor)-$actor['hp']));
        $actualsp = max(0,min($sp,another_max_sp($actor)-$actor['sp']));
        $actor['hp'] += $actualhp; $actor['sp'] += $actualsp;
        $state['digestion_until'] = $now + ($quick ? $cfg['quick_digestion'] : $cfg['digestion']);
        another_item_consume($actor,$slot);
        $messages[]='使用'.$item['name'].'，回复'.$actualhp.'生命、'.$actualsp.'体力。';
        if (!empty($item['para']['another']['poisoned'])) {
            if (another_talent($actor,'L6') > 0 && mt_rand(1,10000) <= another_talent_value($actor,'L6')*100) $messages[]='卦运使这次毒性没有发作。';
            else another_apply_status($actor,'poison',array('duration'=>$cfg['poison_duration'],'power'=>$cfg['poison_power'],'source'=>isset($item['para']['another']['poison_owner']) ? $item['para']['another']['poison_owner'] : 0),$players,$now,$messages);
        }
        return true;
    }
    if ($id === 'antidote') {
        if (empty($state['statuses']['poison'])) { $messages[]='你目前没有需要解除的中毒状态。'; return false; }
        unset($state['statuses']['poison']); another_item_consume($actor,$slot);
        $messages[]='毒性已经解除；队友身上的毒需要各自处理。'; return true;
    }
    if (isset($def['bundle'])) {
        $copy = $actor; another_item_consume($copy,$slot);
        foreach ($def['bundle'] as $part=>$amount) if (!another_item_add($copy,another_item_make($part,$amount))) {
            $messages[]='背包放不下整箱物资，请先腾出空间。'; return false;
        }
        $actor = $copy; $messages[]='打开了'.$item['name'].'。'; return true;
    }
    $messages[]='这件物品需通过对应的整备、通讯或陷阱操作使用。'; return false;
}

function another_items_food(&$actor, $action, $slot, $target, $now, &$messages)
{
    $food = another_item_get($actor,$target);
    $ids = array('poison'=>'poison','test_food'=>'poison_test','clean_food'=>'food_antidote');
    $tool = another_item_get($actor,$slot);
    if (!isset($ids[$action]) || another_item_id($tool) !== $ids[$action] || !another_item_present($tool)
        || !another_item_present($food) || substr($food['kind'],0,1) !== 'H' || (string)$slot === (string)$target) {
        $messages[]='请选择对应工具和背包中的一份补给。'; return false;
    }
    $poisoned = !empty($food['para']['another']['poisoned']);
    if ($action === 'test_food') {
        $food['para']['another']['tested'] = true;
        another_item_set($actor,$target,$food); another_item_consume($actor,$slot);
        $messages[]=$food['name'].'的检验结果：'.($poisoned ? '含有毒素。' : '未检出毒素。'); return true;
    }
    // 一剂只处理一份食物。先在副本上拆分，背包不足时不消耗工具。
    $copy = $actor; another_item_consume($copy,$slot); another_item_consume($copy,$target);
    $food['count'] = 1; $food['para']['another']['tested'] = false;
    $food['para']['another']['poisoned'] = $action === 'poison' || !$poisoned;
    $food['para']['another']['poison_owner'] = !empty($food['para']['another']['poisoned']) ? intval($actor['pid']) : 0;
    if (!another_item_add($copy,$food)) { $messages[]='请先腾出一个背包格，用来存放处理后的单份食物。'; return false; }
    $actor = $copy;
    $messages[]=$action === 'poison' ? '已向一份补给加入毒剂。' : '食品处理完成；解毒剂用于无毒食品也会产生毒性。';
    return true;
}

function another_items_shop(&$actor, &$room, $args, $now, &$messages)
{
    $catalog = another_item_catalog(); $cfg = another_items_config();
    $id = isset($args['id']) ? (string)$args['id'] : '';
    $channel = isset($args['channel']) ? (string)$args['channel'] : 'local';
    $qty = isset($args['qty']) ? intval($args['qty']) : 1;
    if (!isset($catalog[$id]) || $qty < 1 || $qty > 9999 || !in_array($channel,array('local','radio','rep'),true)) {
        $messages[]='商品或购买数量无效。'; return false;
    }
    $def = $catalog[$id]; $rep = $channel === 'rep'; $state =& another_items_state($actor);
    if (!isset($def[$rep ? 'rep' : 'money'])) { $messages[]='该商品不在这份货单中。'; return false; }
    if ($rep && another_item_find($actor,'satellite') === false) { $messages[]='需要携带卫星电话才能呼叫援助。'; return false; }
    if ($channel === 'radio' && another_item_find($actor,'radio') === false) { $messages[]='需要携带对讲机才能远程购物。'; return false; }
    if ($channel === 'local' && intval($actor['pls']) !== 1) { $messages[]='实体商店位于旧商业街集市。'; return false; }
    $unit = $def[$rep ? 'rep' : 'money'];
    if (!$rep && another_talent($actor,'C2') > 0) $unit = max(1,intval(floor($unit*(1-another_talent_value($actor,'C2')/100))));
    $price = $unit*$qty;
    $balance = $rep ? (isset($state['rep']) ? $state['rep'] : 0) : $actor['money'];
    if ($balance < $price) { $messages[]=$rep ? 'REP不足。' : '金钱不足。'; return false; }
    $stock = isset($room['stock'][$id]) ? $room['stock'][$id] : (isset($def['stock']) ? $def['stock'] : -1);
    if ($stock >= 0 && $stock < $qty) { $messages[]='这批商品已经没有足够库存。'; return false; }
    $items = array();
    if ($def['kind'] === 'WK' || $def['kind'] === 'WG' || substr($def['kind'],0,1) === 'D') {
        for ($i=0; $i<$qty; $i++) $items[] = another_item_make($id);
    } else {
        $count = $def['count']*$qty;
        if ($count > 99999) { $messages[]='单组物品数量过多。'; return false; }
        $items[] = another_item_make($id,$count);
    }
    if ($channel === 'local') {
        $copy = $actor;
        foreach ($items as $item) if (!another_item_add($copy,$item)) { $messages[]='背包空间不足，没有扣款。'; return false; }
        $actor = $copy; $state =& another_items_state($actor);
    }
    if ($rep) $state['rep'] = round($balance-$price,2); else $actor['money'] -= $price;
    if ($stock >= 0) $room['stock'][$id] = $stock-$qty;
    if ($channel !== 'local') {
        // 装备逐件记录，同批同时到达；不让大量装备塞满一行物品扩展字段。
        foreach ($items as $item) {
            $did = another_next_id($room);
            $room['deliveries'][$did] = array('id'=>$did,'owner'=>intval($actor['pid']),'pls'=>intval($actor['pls']),
                'kind'=>$rep ? 'balloon' : 'falcon','due'=>$now+($rep ? $cfg['balloon_seconds'] : $cfg['falcon_seconds']),
                'items'=>array($item), 'hide'=>$rep && $balance < 50 && another_talent($actor,'L5') > 0 ? 1-another_talent_value($actor,'L5')/100 : 1);
            $state['known_deliveries'][$did] = true;
        }
        $messages[]=$rep ? '气球已出发，'.$cfg['balloon_seconds'].'秒后在呼叫地点落地；途中可能被发现和击落。' : '猎鹰已出发，'.$cfg['falcon_seconds'].'秒后送到你手中。';
    } else $messages[]='购得'.$def['name'].' × '.$qty.'。';
    return true;
}

function another_items_tick(&$room, &$players, $now, &$messages)
{
    // 只保留仍存在对象的发现记录，不把整局探索历史塞入角色字段。
    foreach ($players as &$player) {
        $state =& another_items_state($player);
        foreach (array('known_ground'=>'ground','known_traps'=>'traps','known_deliveries'=>'deliveries') as $key=>$source) {
            if (isset($state[$key])) $state[$key] = array_intersect_key($state[$key],isset($room[$source])?$room[$source]:array());
        }
    }
    unset($player);
    if (empty($room['deliveries'])) return;
    foreach (array_keys($room['deliveries']) as $id) {
        $delivery = $room['deliveries'][$id];
        if ($delivery['due'] > $now) continue;
        $owner = intval($delivery['owner']);
        if ($delivery['kind'] === 'balloon') {
            foreach ($delivery['items'] as $item) {
                $gid = another_items_ground($room,$item,$delivery['pls']);
                if (isset($players[$owner])) {
                    $state =& another_items_state($players[$owner]);
                    $state['known_ground'][$gid] = true;
                }
            }
            if (isset($players[$owner])) another_items_notice($players[$owner],'援助气球已在呼叫地点落地。',$now);
            unset($room['deliveries'][$id]);
        } elseif (isset($players[$owner])) {
            $copy = $players[$owner]; $ok = true;
            foreach ($delivery['items'] as $item) if (!another_item_add($copy,$item)) { $ok=false; break; }
            if ($ok) {
                $players[$owner] = $copy; another_items_notice($players[$owner],'猎鹰送来了所订物资。',$now);
                unset($room['deliveries'][$id]);
            }
        }
    }
}

function another_items_remove_id(&$player, $id, $amount)
{
    $available = 0;
    for ($slot=1;$slot<=6;$slot++) {
        $item = another_item_get($player,$slot);
        if (another_item_id($item) === $id) $available += $item['count'];
    }
    if ($available < $amount) return false;
    for ($slot=1;$slot<=6 && $amount>0;$slot++) {
        $item = another_item_get($player,$slot);
        if (another_item_id($item) !== $id) continue;
        $take = min($amount,$item['count']); another_item_consume($player,$slot,$take); $amount -= $take;
    }
    return true;
}

function another_items_craft(&$actor, $recipe, &$messages)
{
    $cfg = another_items_config();
    if (!isset($cfg['recipes'][$recipe])) { $messages[]='没有这份合成配方。'; return false; }
    $copy = $actor;
    foreach ($cfg['recipes'][$recipe] as $part=>$count) if (!another_items_remove_id($copy,$part,$count)) {
        $messages[]='合成材料不足。'; return false;
    }
    $item = another_item_make($recipe);
    if (!another_item_add($copy,$item)) { $messages[]='背包放不下成品，材料未消耗。'; return false; }
    $actor = $copy;
    $rep = in_array(substr($item['kind'],0,1),array('W','D'),true) ? another_add_rep($actor,$cfg['craft_rep']) : 0;
    $messages[]='合成了'.$item['name'].($rep ? '，获得'.$rep.' REP。' : '。');
    return true;
}

function another_items_improve(&$actor, $action, $args, &$messages)
{
    $cfg = another_items_config();
    $slot = isset($args['target']) ? $args['target'] : (isset($args['slot']) ? $args['slot'] : 'wep');
    $item = another_item_get($actor,$slot);
    if (!another_item_present($item)) { $messages[]='请选择一件仍可使用的装备。'; return false; }
    $kind = $item['kind'];
    $material = array('upgrade'=>'gun_part','sharpen'=>'whetstone','socket'=>'gem','armor_upgrade'=>'armor_patch','repair'=>'repair','reload'=>'ammo');
    if (!isset($material[$action])) return false;
    $part = isset($args['material']) ? (string)$args['material'] : $material[$action];
    if ($part !== $material[$action] && !($part === 'high_part' && in_array($action,array('upgrade','sharpen','armor_upgrade'),true))) {
        $messages[]='这项整备不能使用该材料。'; return false;
    }
    if (($action === 'upgrade' || $action === 'reload') && $kind !== 'WG'
        || ($action === 'sharpen' || $action === 'socket') && $kind !== 'WK'
        || $action === 'armor_upgrade' && substr($kind,0,1) !== 'D'
        || $action === 'repair' && !in_array($kind,array('WG','WK'),true)) {
        $messages[]='武器系别与整备方式不符；投系只能补充成品。'; return false;
    }
    $meta = isset($item['para']['another']) ? $item['para']['another'] : array();
    $tier = isset($meta['tier']) ? intval($meta['tier']) : 1;
    if ($action === 'upgrade' && $tier >= 3) { $messages[]='这件枪械已装齐当前最高阶组件。'; return false; }
    if ($action === 'repair' && empty($meta['wear'])) { $messages[]='这件武器尚未磨损。'; return false; }
    $socket_effect = isset($args['effect']) ? (string)$args['effect'] : 'poison';
    if ($action === 'socket' && !in_array($socket_effect,array('poison','burn','slow','pierce'),true)) { $messages[]='嵌芯属性无效。'; return false; }
    $copy = $actor;
    $consume = $action === 'reload' ? min($cfg['reload_count'],another_items_count_id($actor,'ammo')) : 1;
    if ($consume < 1 || !another_items_remove_id($copy,$part,$consume)) { $messages[]='缺少所需整备材料。'; return false; }
    $rep = 0;
    if (in_array($action,array('sharpen','socket','armor_upgrade'),true)) {
        $failure = $cfg['enhance_failure']*(1-another_talent_value($actor,'I1')/100);
        if (mt_rand(1,10000) <= round($failure*100)) {
            $actor = $copy; $messages[]='强化失败，材料已消耗，原装备保留。'; return true;
        }
    }
    if ($action === 'reload') {
        $meta['ammo'] = (isset($meta['ammo']) ? intval($meta['ammo']) : 0) + $consume;
    } elseif ($action === 'repair') {
        $meta['wear'] = max(0,$meta['wear']-$cfg['repair_wear']);
    } elseif ($action === 'upgrade') {
        $newtier = $part === 'high_part' ? 3 : $tier+1;
        $upgraded = another_item_make($newtier === 3 ? 'gun_high' : 'gun_mid');
        $upgraded['para']['another']['ammo'] = isset($meta['ammo']) ? $meta['ammo'] : 0;
        $item = $upgraded; $meta = $item['para']['another']; $rep = $cfg['upgrade_rep'];
    } elseif ($action === 'sharpen') {
        $item['effect'] += $part === 'high_part' ? $cfg['high_sharpen_gain'] : $cfg['sharpen_gain']; $rep = $part === 'high_part' ? $cfg['rare_enhance_rep'] : $cfg['enhance_rep'];
    } elseif ($action === 'socket') {
        $effect = $socket_effect;
        $meta['effects'][$effect] = min(100,(isset($meta['effects'][$effect]) ? $meta['effects'][$effect] : 0)+$cfg['socket_power']);
        $item['effect'] += $cfg['socket_gain']; $rep = $cfg['rare_enhance_rep'];
    } elseif ($action === 'armor_upgrade') {
        $item['effect'] += $part === 'high_part' ? $cfg['high_armor_gain'] : $cfg['armor_gain']; $rep = $part === 'high_part' ? $cfg['rare_enhance_rep'] : $cfg['enhance_rep'];
    }
    $item['para']['another'] = $meta; another_item_set($copy,$slot,$item);
    if ($action === 'upgrade' && $slot === 'wep') {
        $state =& another_items_state($copy); unset($state['moon']);
    }
    $actor = $copy;
    if ($rep) $rep = another_add_rep($actor,$rep);
    $messages[]='完成整备：'.$item['name'].($rep ? '，获得'.$rep.' REP。' : '。');
    return true;
}

function another_items_count_id($actor, $id)
{
    $count=0;
    for ($slot=1;$slot<=6;$slot++) {
        $item=another_item_get($actor,$slot);
        if (another_item_id($item)===$id) $count += $item['count'];
    }
    return $count;
}

function another_items_report(&$actor, &$room, &$players, $trap, $now, $failed=false)
{
    $owner = intval(isset($trap['owner']) ? $trap['owner'] : 0);
    $world=function_exists('another_config')?another_config():array();
    $place=isset($world['maps'][$actor['pls']]['name'])?$world['maps'][$actor['pls']]['name']:'附近';
    $text = another_talent_name($actor).'在'.$place.($failed ? '拆除陷阱失败。' : '触发了陷阱。');
    if ($owner > 0) {
        if (isset($players[$owner])) another_items_notice($players[$owner],$text,$now);
    } else {
        if (!$failed && another_talent($actor,'L7') > 0 && mt_rand(1,10000) <= another_talent_value($actor,'L7')*100) return;
        foreach ($players as &$recipient) if (empty($recipient['type'])) another_items_notice($recipient,$text,$now);
        unset($recipient);
    }
}

function another_items_trigger_trap(&$actor, &$room, &$players, $id, $now, &$messages, $failed=false)
{
    if (!isset($room['traps'][$id])) return false;
    $trap = $room['traps'][$id]; $cfg=another_items_config();
    $item = isset($trap['item']) ? $trap['item'] : another_item_make(isset($trap['catalog']) ? $trap['catalog'] : 'trap_hazard');
    $catalog = another_item_catalog(); $kind = another_item_id($item);
    if (!isset($catalog[$kind]['trap'])) return false;
    $evade = min(100,$cfg['trap_evade']+max(0,intval($actor['lvl']))/3+(another_talent($actor,'AU') > 0 ? 50 : 0));
    $forced = !empty($trap['gold']) || $failed;
    unset($room['traps'][$id]);
    $state =& another_items_state($actor); unset($state['known_traps'][$id]);
    if (!$forced && mt_rand(1,10000) <= round($evade*100)) {
        $messages[]='及时避开了'.$item['name'].'。'; return true;
    }
    $messages[]=$failed ? '拆除失败，装置发生爆响！' : '触发了'.$item['name'].'！';
    $percent = $failed ? $cfg['disarm_damage'] : $item['effect'];
    if ($percent > 0) {
        another_damage($actor,floor(another_max_hp($actor)*$percent/100),'trap',$now,$messages);
        if ($actor['hp'] <= 0) {
            $actor['bid'] = isset($trap['owner']) ? intval($trap['owner']) : 0;
            $actor['clbpara']['another']['killer'] = $actor['bid'];
        }
    }
    if (!$failed && $actor['hp'] > 0) {
        $status = $catalog[$kind]['trap'];
        $spec = array('duration'=>$cfg['trap_duration'],'power'=>25,'source'=>isset($trap['owner']) ? intval($trap['owner']) : 0);
        if ($status === 'slow' || $status === 'combo') another_apply_status($actor,'slow',$spec,$players,$now,$messages);
        if ($status === 'combo') { $spec['power']=15; another_apply_status($actor,'weaken',$spec,$players,$now,$messages); }
    }
    another_items_report($actor,$room,$players,$trap,$now,$failed);
    return true;
}

function another_items_disarm(&$actor, &$room, &$players, $id, $now, &$messages)
{
    $state =& another_items_state($actor); $cfg=another_items_config();
    if (!isset($room['traps'][$id]) || empty($state['known_traps'][$id]) || intval($room['traps'][$id]['pls']) !== intval($actor['pls'])) {
        $messages[]='必须先在此地发现仍存在的陷阱。'; return false;
    }
    if (mt_rand(1,100) > $cfg['disarm_chance']) return another_items_trigger_trap($actor,$room,$players,$id,$now,$messages,true);
    $trap=$room['traps'][$id];
    $item = isset($trap['item']) ? $trap['item'] : another_item_make(isset($trap['catalog']) ? $trap['catalog'] : 'trap_hazard');
    // 拆除工具用于完好回收；徒手也能拆解，获得可用零件。
    $recovered = another_item_find($actor,'disarm_tool') !== false ? $item : another_item_make('scrap');
    $recovered['count']=1;
    if (!another_item_add($actor,$recovered)) {
        $gid=another_items_ground($room,$recovered,$actor['pls']); $state['known_ground'][$gid]=true;
    }
    unset($room['traps'][$id],$state['known_traps'][$id]);
    $messages[]='拆除成功，获得'.$recovered['name'].'。'; return true;
}

function another_items_find_rate($actor, $base, $type, $hide=1, $environment=1)
{
    $cfg=another_items_config();
    $bonus = another_talent($actor,'AU') > 0 ? 30 : 0;
    if ($type === 'trap') $bonus += another_talent_value($actor,'P3');
    if ($type === 'trap' && another_item_find($actor,'detector') !== false) $bonus += $cfg['tool_find_bonus'];
    if ($type === 'balloon' && another_item_find($actor,'binoculars') !== false) $bonus += $cfg['tool_find_bonus'];
    return max(0,min(100,$base*(1+$bonus/100)*$hide*$environment));
}

// 每次探索至多发现一个物资事件。已看见的陷阱留给拆除/绕行，不强迫踩中。
function another_items_search(&$actor, &$room, &$players, $now, &$messages)
{
    $cfg=another_items_config(); $state =& another_items_state($actor);
    $world=function_exists('another_config')?another_config():array();
    $discovery=isset($world['discovery'])?$world['discovery']:array();
    $environment=function_exists('another_discovery_environment')?another_discovery_environment($room,$actor['pls']):1;
    if (!empty($room['deliveries'])) foreach ($room['deliveries'] as $id=>$delivery) {
        if ($delivery['kind'] !== 'balloon' || $delivery['due'] <= $now || intval($delivery['pls']) !== intval($actor['pls']) || !empty($state['known_deliveries'][$id])) continue;
        $rate=another_items_find_rate($actor,isset($discovery['balloon'])?$discovery['balloon']:45,'balloon',isset($delivery['hide'])?$delivery['hide']:1,$environment);
        if (mt_rand(1,10000) <= round($rate*100)) {
            $state['known_deliveries'][$id]=true; $messages[]='发现一只正在靠近的援助气球，可以用射／投武器击落。'; return true;
        }
    }
    if (!empty($room['traps'])) foreach ($room['traps'] as $id=>$trap) {
        if (intval($trap['pls']) !== intval($actor['pls']) || !empty($state['known_traps'][$id])) continue;
        $rate=another_items_find_rate($actor,isset($discovery['trap'])?$discovery['trap']:40,'trap',isset($trap['hide'])?$trap['hide']:1,$environment);
        if (!empty($trap['announced']) || mt_rand(1,10000) <= round($rate*100)) {
            $state['known_traps'][$id]=true; $messages[]='发现尚未触发的陷阱，可以拆除或绕开。'; return true;
        }
        if (mt_rand(1,100) <= $cfg['trigger_trap']) return another_items_trigger_trap($actor,$room,$players,$id,$now,$messages);
    }
    $ground=array();
    if (!empty($room['ground'])) foreach ($room['ground'] as $id=>$entry) {
        if (intval($entry['pls']) === intval($actor['pls']) && empty($state['known_ground'][$id])) $ground[]=$id;
    }
    if ($ground && mt_rand(1,10000) <= round(another_items_find_rate($actor,isset($discovery['item'])?$discovery['item']:65,'ground',1,$environment)*100)) {
        $id=$ground[array_rand($ground)]; $state['known_ground'][$id]=true;
        $messages[]='发现了'.$room['ground'][$id]['item']['name'].'。'; return true;
    }
    return false;
}

function another_items_shoot_balloon(&$actor, &$room, $id, $now, &$messages)
{
    $state =& another_items_state($actor); $cfg=another_items_config();
    if (!isset($room['deliveries'][$id]) || empty($state['known_deliveries'][$id])) { $messages[]='这只气球已经不在视野中。'; return false; }
    $delivery=$room['deliveries'][$id];
    if ($delivery['kind'] !== 'balloon' || $delivery['due'] <= $now || intval($delivery['pls']) !== intval($actor['pls'])) { $messages[]='这里没有可击落的气球。'; return false; }
    $weapon=another_combat_weapon($actor);
    if (!in_array($weapon['kind'],array('G','C'),true)) { $messages[]='需要装备射系或投系武器。'; return false; }
    if (!another_weapon_usable($weapon)) { $messages[]='武器无法使用，请检查弹药和磨损。'; return false; }
    another_pay_weapon($actor,$weapon,false,$messages);
    unset($room['deliveries'][$id],$state['known_deliveries'][$id]);
    if (mt_rand(1,100) <= $cfg['balloon_loot_chance']) {
        foreach ($delivery['items'] as $item) {
            $gid=another_items_ground($room,$item,$actor['pls']); $state['known_ground'][$gid]=true;
        }
        $messages[]='气球被击落，仍完好的物资掉在地上。';
    } else $messages[]='气球被击落，里面的物资随之损坏。';
    return true;
}

function another_items_action(&$actor, &$room, &$players, $action, $args, $now, &$messages)
{
    if (!is_array($args) || empty($actor['hp']) || $actor['hp'] <= 0) return false;
    foreach ($args as $value) if (!is_scalar($value) && $value !== null) { $messages[]='物品指令参数无效。'; return false; }
    $slot = isset($args['slot']) ? $args['slot'] : 0;
    $id = isset($args['id']) ? $args['id'] : 0;
    $state =& another_items_state($actor);
    if ($action === 'use') return another_items_use($actor,$players,$slot,$now,$messages);
    if (in_array($action,array('poison','test_food','clean_food'),true)) return another_items_food($actor,$action,$slot,isset($args['target'])?$args['target']:0,$now,$messages);
    if ($action === 'buy') return another_items_shop($actor,$room,$args,$now,$messages);
    if ($action === 'craft') return another_items_craft($actor,isset($args['recipe'])?(string)$args['recipe']:'',$messages);
    if (in_array($action,array('upgrade','sharpen','socket','armor_upgrade','repair','reload'),true)) return another_items_improve($actor,$action,$args,$messages);
    if ($action === 'disarm') return another_items_disarm($actor,$room,$players,intval($id),$now,$messages);
    if ($action === 'shoot_balloon') return another_items_shoot_balloon($actor,$room,intval($id),$now,$messages);
    if ($action === 'collect') {
        if (!isset($room['deliveries'][$id]) || intval($room['deliveries'][$id]['owner']) !== intval($actor['pid']) || $room['deliveries'][$id]['due'] > $now) {
            $messages[]='尚无可以领取的配送。'; return false;
        }
        $delivery=$room['deliveries'][$id];
        if ($delivery['kind'] !== 'falcon') { $messages[]='气球物资请到落点拾取。'; return false; }
        $copy=$actor;
        foreach($delivery['items'] as $item) if(!another_item_add($copy,$item)) { $messages[]='请先腾出背包空间。'; return false; }
        $actor=$copy; unset($room['deliveries'][$id]); $messages[]='领取了猎鹰送来的物资。'; return true;
    }
    if ($action === 'pickup') {
        if (!isset($room['ground'][$id]) || empty($state['known_ground'][$id]) || intval($room['ground'][$id]['pls']) !== intval($actor['pls'])) {
            $messages[]='此地已没有这件已发现的物品。'; return false;
        }
        $item=$room['ground'][$id]['item'];
        if (!another_item_add($actor,$item)) { $messages[]='背包已满，物品仍留在地上。'; return false; }
        unset($room['ground'][$id],$state['known_ground'][$id]); $messages[]='拾起了'.$item['name'].'。'; return true;
    }
    if ($action === 'drop') {
        $item=another_item_get($actor,$slot);
        $qty=isset($args['qty'])?intval($args['qty']):(isset($item['count'])?$item['count']:0);
        if (!another_item_present($item) || $qty < 1 || $qty > $item['count']) { $messages[]='丢弃数量无效。'; return false; }
        $drop=$item; $drop['count']=$qty; another_item_consume($actor,$slot,$qty);
        $gid=another_items_ground($room,$drop,$actor['pls']); $state['known_ground'][$gid]=true;
        if ($slot === 'wep') unset($state['moon']);
        $messages[]='放下了'.$item['name'].'。'; return true;
    }
    if ($action === 'equip') {
        if (!preg_match('/^[1-6]$/',(string)$slot)) { $messages[]='请选择背包中的装备。'; return false; }
        $item=another_item_get($actor,$slot); $target='';
        if (in_array($item['kind'],array('WK','WG','WC'),true)) $target='wep';
        $armors=array('DB'=>'arb','DH'=>'arh','DA'=>'ara','DF'=>'arf','A'=>'art');
        if(isset($armors[$item['kind']])) $target=$armors[$item['kind']];
        if (!$target || !another_item_present($item)) { $messages[]='这不是可装备物品。'; return false; }
        $old=another_item_get($actor,$target); another_item_set($actor,$target,$item); another_item_set($actor,$slot,another_item_present($old)?$old:array());
        if($target==='wep') unset($state['moon']);
        $messages[]='装备了'.$item['name'].'。'; return true;
    }
    if ($action === 'unequip') {
        if(!in_array($slot,array('wep','arb','arh','ara','arf','art'),true)) return false;
        $item=another_item_get($actor,$slot);
        if(!another_item_present($item) || !another_item_add($actor,$item)) { $messages[]='装备为空或背包已满。'; return false; }
        another_item_set($actor,$slot,array()); if($slot==='wep') unset($state['moon']);
        $messages[]='卸下了'.$item['name'].'。'; return true;
    }
    if ($action === 'plant') {
        $item=another_item_get($actor,$slot); $catalog=another_item_catalog(); $key=another_item_id($item);
        if(!another_item_present($item) || !isset($catalog[$key]['trap'])) { $messages[]='请选择一份陷阱。'; return false; }
        $item['count']=1; $tid=another_next_id($room);
        $room['traps'][$tid]=array('id'=>$tid,'pls'=>intval($actor['pls']),'owner'=>intval($actor['pid']),'item'=>$item,
            'gold'=>another_talent($actor,'IU')>0,'hide'=>1-another_talent_value($actor,'I2')/100);
        $state['known_traps'][$tid]=true; another_item_consume($actor,$slot);
        $messages[]='布置了'.$item['name'].'，触发报告只发送给你。'; return true;
    }
    return false;
}
