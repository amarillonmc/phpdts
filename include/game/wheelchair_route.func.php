<?php
if(!defined('IN_GAME')) exit('Access Denied');

include_once GAME_ROOT.'./include/game/itemmix.func.php';
include_once GAME_ROOT.'./include/game/itemplace.func.php';

function wheelchair_recipes()
{
	static $recipes;
	if($recipes === null) {
		$recipes = array();
		foreach(get_mixinfo() as $recipe) if(($recipe['class'] ?? '') !== 'hidden' && !empty($recipe['stuff']) && count($recipe['stuff'])<=6) {
			$recipes[sha1(json_encode($recipe))] = $recipe;
		}
	}
	return $recipes;
}

function wheelchair_normal_name($name)
{
	return htmlspecialchars_decode(itemmix_name_proc($name),ENT_QUOTES);
}

function wheelchair_route_find(&$data,$input)
{
	global $log;
	if(!is_string($input) || strlen($input)>300 || trim($input)==='') { wheelchair_log('导航','请输入目标物品名或一部分名称。'); return; }
	$input = trim($input); $found=0;
	foreach(wheelchair_recipes() as $id=>$recipe) {
		if(strpos($recipe['result'][0],$input)===false) continue;
		$label = $recipe['result'][0].'〔'.($recipe['result'][4] ?? '无附加属性').'〕：'.implode('＋',$recipe['stuff']);
		$log .= wheelchair_button('wc_route_pick_'.$id,$label).'<br>';
		if(++$found>=30) { wheelchair_log('导航','结果较多，请输入更完整的名称缩小范围。'); break; }
	}
	if(!$found) wheelchair_log('导航','没有找到公开的通常合成配方。可先在合成索引中查看来源；隐藏配方不会被导航公开。');
}

function wheelchair_route_pick(&$data,$id)
{
	if((int)$data['club']===20) { wheelchair_log('导航','元素大师无法使用通常合成，请先更换社团；元素合成仍由你自行选择。'); return; }
	$recipes = wheelchair_recipes();
	if(!isset($recipes[$id])) { wheelchair_log('导航','该配方不存在，请重新查找。'); return; }
	$data['clbpara']['wheelchair']['recipe'] = $id;
	unset($data['clbpara']['wheelchair']['paused']['wc_route']);
	$data['clbpara']['wheelchair']['target'] = $recipes[$id]['result'][0];
	unset($data['clbpara']['wheelchair']['funding'],$data['clbpara']['wheelchair']['route_tries']);
	wheelchair_log('导航','目标设为 '.$recipes[$id]['result'][0].'；配方：'.implode('＋',$recipes[$id]['stuff']).'。继续探索将逐步收集素材。');
}

function wheelchair_route_plan($data)
{
	$wc = $data['clbpara']['wheelchair'] ?? array();
	if(empty($wc['recipe'])) return null;
	$recipes=wheelchair_recipes();
	if(!isset($recipes[$wc['recipe']])) return array('kind'=>'blocked','name'=>$wc['target'],'reason'=>'目标配方已变化，请重新选择。');
	$stock=array();
	foreach(array('itm0','itm1','itm2','itm3','itm4','itm5','itm6','wep','wep2','arb','arh','ara','arf','art') as $slot) {
		$item=wheelchair_item($data,$slot);
		if(!empty($item['itms'])) $stock[wheelchair_normal_name($item['itm'])][]=$slot;
	}
	$budget=80;
	return wheelchair_plan_node(wheelchair_normal_name($wc['target']),$stock,$recipes,array(),$budget,$wc['recipe']);
}

function wheelchair_plan_node($name,&$stock,$recipes,$path,&$budget,$preferred='')
{
	if(!empty($stock[$name])) return array('kind'=>'have','name'=>$name,'slot'=>array_shift($stock[$name]));
	if(--$budget<0 || count($path)>16 || in_array($name,$path,true)) return array('kind'=>'blocked','name'=>$name,'reason'=>'配方有循环或分支过深，请选择其他配方。');
	$candidates=array();
	foreach($recipes as $id=>$r) {
		if(wheelchair_normal_name($r['result'][0])!==$name || ($preferred && $id!==$preferred)) continue;
		$score=-count($r['stuff']);
		foreach($r['stuff'] as $s) if(!empty($stock[wheelchair_normal_name($s)])) $score+=10;
		$candidates[]=array('recipe'=>$r,'score'=>$score);
	}
	if(!$candidates) return array('kind'=>'need','name'=>$name);
	usort($candidates,function($a,$b) { return $b['score']<=>$a['score']; });
	$path[]=$name;
	foreach($candidates as $candidate) {
		$trial=$stock; $children=array(); $blocked=false;
		foreach($candidate['recipe']['stuff'] as $s) {
			$child=wheelchair_plan_node(wheelchair_normal_name($s),$trial,$recipes,$path,$budget);
			if($child['kind']==='blocked') { $blocked=true; break; }
			$children[]=$child;
		}
		if(!$blocked) { $stock=$trial; return array('kind'=>'mix','name'=>$name,'recipe'=>$candidate['recipe'],'children'=>$children); }
		if($budget<0) break;
	}
	return array('kind'=>'blocked','name'=>$name,'reason'=>'可选配方有循环或分支过深，请选择其他配方。');
}

function wheelchair_plan_next($node,$data=null)
{
	if(!$node || $node['kind']!=='mix') return $node;
	$best=null; $bestscore=-INF;
	foreach($node['children'] as $child) if($child['kind']!=='have') {
		$next=wheelchair_plan_next($child,$data);
		// 先消耗已齐的素材腾包；缺材料时优先做能增强角色的中间装备，便于打钱。
		if($next['kind']==='mix') return $next;
		$score=0;
		if($data && $child['kind']==='mix') {
			$r=$child['recipe']['result'];
			$p=wheelchair_replacement_power($data,array('itm'=>$r[0],'itmk'=>$r[1],'itme'=>$r[2],'itms'=>$r[3],'itmsk'=>$r[4] ?? '','itmpara'=>$r[5] ?? ''));
			if($p!==null) $score=max(0,$p-wheelchair_power($data,true));
		}
		if($score>$bestscore) { $best=$next; $bestscore=$score; }
	}
	return $best ?: $node;
}

function wheelchair_plan_names($node)
{
	if(!$node) return array();
	$names=array($node['name']);
	foreach($node['children'] ?? array() as $child) $names=array_merge($names,wheelchair_plan_names($child));
	return array_unique($names);
}

function wheelchair_material($data,$item)
{
	if(!wheelchair_has($data,'wc_route') || empty($data['clbpara']['wheelchair']['recipe'])) return false;
	return in_array(wheelchair_normal_name($item['itm']),wheelchair_plan_names(wheelchair_route_plan($data)),true);
}

function wheelchair_sources($name)
{
	global $gamecfg,$areanum,$areaadd;
	static $maps;
	if($maps===null) {
		$maps=array();
		foreach(openfile(config('mapitem',$gamecfg)) as $line) {
			$r=explode(',',trim($line));
			if(count($r)<8 || !is_numeric($r[0]) || !is_numeric($r[1])) continue;
			$maps[wheelchair_normal_name($r[3])][] = array('area'=>(int)$r[0],'pls'=>(int)$r[1]);
		}
	}
	$result=array();
	foreach($maps[$name] ?? array() as $source) if($source['area']===99 || $source['area']<=floor($areanum/max(1,$areaadd))) $result[]=$source['pls'];
	return array_unique($result);
}

function wheelchair_shop_items()
{
	global $db,$tablepre,$areanum,$areaadd;
	// 商店清单是公开信息；采购时仍由 itembuy 再检查库存和价格。
	$result=$db->query("SELECT * FROM {$tablepre}shopitem WHERE num>0 AND area<=".(int)floor($areanum/max(1,$areaadd)).' ORDER BY price,sid');
	$rows=array();
	while($row=$db->fetch_array($result)) $rows[]=$row;
	return $rows;
}

function wheelchair_price($data,$row)
{
	return (int)($data['club']==11 ? round($row['price']*.75) : $row['price']);
}

function wheelchair_at_shop($data)
{
	global $shops;
	return in_array($data['pls'],$shops) || !check_skill_unlock('c11_ebuy',$data);
}

function wheelchair_buy(&$data,$row,$reason)
{
	if(!empty($data['itms0']) || !wheelchair_at_shop($data)) return false;
	$before=$data['money'];
	itembuy((int)$row['sid'],(int)$row['kind'],1,$data);
	if($data['money']<$before) { wheelchair_log('采购',$reason.'，花费 '.($before-$data['money']).' 元购买 '.$row['item'].'。'); return true; }
	return false;
}

function wheelchair_route_step(&$data)
{
	global $mode,$cmd,$main,$shops,$plsinfo;
	if((int)$data['club']===20) { wheelchair_log('导航','当前社团无法进行通常合成，导航已暂停。'); $data['clbpara']['wheelchair']['paused']['wc_route']=1; return false; }
	$node=wheelchair_route_plan($data);
	if(!$node) return false;
	if($node['kind']==='have') {
		wheelchair_log('导航','已获得目标 '.$node['name'].'，导航结束。');
		unset($data['clbpara']['wheelchair']['recipe'],$data['clbpara']['wheelchair']['funding']);
		return false;
	}
	$next=wheelchair_plan_next($node,$data);
	if($next['kind']==='blocked') { wheelchair_log('导航',$next['reason']); return false; }
	if($next['kind']==='mix') {
		$slots=array();
		foreach($next['children'] as $child) {
			if(strpos($child['slot'],'itm')!==0) {
				if(!empty($data['itms0'])) return false;
				if($child['slot']==='wep2') change_subwep(2,$data);
				else itemoff($child['slot']);
				wheelchair_log('导航','为合成 '.$next['name'].'，尝试卸下素材 '.$child['name'].'。');
				return true;
			}
			$slots[]=(int)substr($child['slot'],3);
		}
		$results=itemmix_get_result($slots,$data);
		foreach($results as $index=>$r) if($r['result']==$next['recipe']['result']) {
			itemmix_rev($slots,$index,$data);
			wheelchair_log('导航','素材已齐，按配方合成 '.$next['name'].'；结果见上方合成记录。');
			return true;
		}
		wheelchair_log('导航','素材当前属性不符合配方，请检查合成索引。');
		return false;
	}
	$name=$next['name'];
	foreach(wheelchair_shop_items() as $row) if(wheelchair_normal_name($row['item'])===$name) {
		$price=wheelchair_price($data,$row);
		if($data['money']<$price+50) {
			$data['clbpara']['wheelchair']['funding']=$price+50;
			wheelchair_log('导航','下一件素材 '.$name.' 需 '.$price.' 元，另留 50 元补给；先通过普通遭遇打钱。');
			return wheelchair_hunt_step($data,array('kind'=>'minion','target'=>'90','label'=>'小兵（筹措素材费用）'));
		}
		unset($data['clbpara']['wheelchair']['funding']);
		if(wheelchair_at_shop($data)) return wheelchair_buy($data,$row,'目标配方缺少 '.$name);
		return wheelchair_move_choice($data,$shops,'导航','前往商店购买 '.$name);
	}
	$places=wheelchair_sources($name);
	if(in_array(99,$places,true)) $places=wheelchair_safe_places($data);
	$places=array_values(array_intersect($places,wheelchair_safe_places($data)));
	if($places) {
		$tries=&$data['clbpara']['wheelchair']['route_tries'];
		if(!is_array($tries) || ($tries['name'] ?? '')!==$name) $tries=array('name'=>$name,'n'=>0);
		$destination=$places[(int)floor($tries['n']/15)%count($places)]; $tries['n']++;
		if($data['pls']!=$destination) return wheelchair_move_choice($data,array($destination),'导航','公开物品来源显示 '.$name.' 在此刷新');
		wheelchair_log('导航','正在寻找 '.$name.'；来源表不保证此时还有存货，继续正常探索。');
		return false;
	}
	// NPC掉落也只查公开配置，不读取尚未见过的活体坐标。
	global $gamecfg;
	include config('npc',$gamecfg);
	foreach($npcinfo as $type=>$group) foreach($group['sub'] ?? array() as $sub) {
		$npc=array_merge($group,$sub);
		foreach(array('wep','arb','arh','ara','arf','art','itm1','itm2','itm3','itm4','itm5','itm6') as $slot) if(wheelchair_normal_name($npc[$slot] ?? '')===$name) {
			return wheelchair_hunt_step($data,array('kind'=>'npc','target'=>(string)$type,'label'=>($npc['name'] ?? 'NPC').'（素材 '.$name.'）'));
		}
	}
	wheelchair_log('导航','当前没有可前往的公开来源：'.$name.'。'.str_replace("\r",' ',strip_tags(get_item_place($name))));
	return false;
}
