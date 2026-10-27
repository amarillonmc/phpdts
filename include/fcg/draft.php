<?php
if(!defined('IN_GAME')) exit('Access Denied');

// 入场与结算共用的经济档位，也提供给前端展示。
function fcg_draft_tiers()
{
	return array(
		'entry'=>250,
		0=>array('deck'=>0,'upgraded'=>0,'rare'=>0,'embers'=>1),
		1=>array('deck'=>1,'upgraded'=>0,'rare'=>0,'embers'=>1),
		2=>array('deck'=>1,'upgraded'=>1,'rare'=>0,'embers'=>2),
		3=>array('deck'=>2,'upgraded'=>1,'rare'=>0,'embers'=>3),
		4=>array('deck'=>3,'upgraded'=>0,'rare'=>1,'embers'=>4),
		5=>array('deck'=>2,'upgraded'=>1,'rare'=>1,'embers'=>5),
	);
}

// 仅在轮抽重放的已播种 MT 序列中调用；四个未升级位和一个 60/30/10 加权位。
function fcg_draft_deal()
{
	$normal=$upgraded=$rare=$plain=array();
	foreach(fcg_cards() as $id=>$c) if(!$c['unlimited']) {
		if(!$c['upgraded']) $plain[]=$id;
		if($c['rare']) $rare[]=$id;
		elseif($c['upgraded']) $upgraded[]=$id;
		else $normal[]=$id;
	}
	$packs=array();
	for($i=0;$i<24;$i++) {
		$roll=mt_rand(1,100); $pool=$roll<=60?$normal:($roll<=90?$upgraded:$rare);
		$pack=array($pool[mt_rand(0,count($pool)-1)]);
		for($j=0;$j<4;$j++) $pack[]=$plain[mt_rand(0,count($plain)-1)];
		$packs[]=$pack;
	}
	return $packs;
}

function fcg_draft_pack($run)
{
	// 只保存种子及玩家的 24 次选择，重放四位牌手；不公开种子或未来包。
	mt_srand($run['seed'],MT_RAND_MT19937);
	try {
		$packs=fcg_draft_deal(); $bots=array_fill(1,4,array()); $cards=fcg_cards(); $scores=array();
		foreach($cards as $id=>$c) $scores[$id]=fcg_card_prior_score($c);
		for($n=0;$n<24;$n++) {
			$circle=intdiv($n,8); $pick=$n%8; $dir=$circle%2?-1:1;
			$pack=$packs[$circle*8+($dir===1?$pick:7-$pick)];
			if($n===2) foreach($bots as &$locked) {
				$locked=array_values(array_unique($locked));
				if(count($locked)<2) { $other=array_values(array_diff(fcg_colors(),$locked)); $locked[]=$other[mt_rand(0,count($other)-1)]; }
			} unset($locked);
			for($step=0;$step<5;$step++) {
				$seat=($pick%5+$dir*$step+5)%5;
				if($seat===0) {
					if($n===count($run['picks'])) return array_values($pack);
					$slot=array_search($run['picks'][$n],$pack,true);
					if($slot===false) throw new RuntimeException('轮抽记录无法还原，原记录已保留。');
				} else {
					$slot=mt_rand(0,count($pack)-1);
					if($n>=2) {
						$best=-INF;
						foreach($pack as $at=>$id) {
							$score=$scores[$id]+(in_array($cards[$id]['color'],$bots[$seat],true)?40:0)+mt_rand(-6,6);
							if($score>$best) { $best=$score; $slot=$at; }
						}
					} else $bots[$seat][]=$cards[$pack[$slot]]['color'];
				}
				array_splice($pack,$slot,1);
			}
		}
		return array();
	} finally { mt_srand(); } // 不让重放的已知种子影响后续战斗洗牌。
}

function fcg_draft_energy($picks)
{
	$counts=array_fill_keys(fcg_colors(),0); $cards=fcg_cards();
	foreach($picks as $id) if(!$cards[$id]['unlimited']) $counts[$cards[$id]['color']]++;
	arsort($counts,SORT_NUMERIC); $colors=array_keys(array_filter($counts)); $base=array();
	$amounts=count($colors)===1?array(8):(isset($colors[2]) && $counts[$colors[2]]>=4?array(3,3,2):array(4,4));
	foreach($amounts as $i=>$amount) {
		$id=4*array_search($colors[$i],fcg_colors(),true);
		// 每主色先给升级产能/打火各两张；单色不足部分补普通产能桩。
		foreach(array_slice(array($id+1,$id+3,$id+1,$id+3),0,$amount) as $card) $base[]=$card;
		for($j=4;$j<$amount;$j++) $base[]=$id;
	}
	return $base;
}

function fcg_draft_prepare_battle(&$run)
{
	$layer=$run['layer'];
	$pool=$layer<=2?range(0,4):($layer===3?range(5,10):($layer===4?range(11,16):range(17,20)));
	$pool=array_values(array_diff($pool,$run['opponents']));
	$run['opponent']=$pool[random_int(0,count($pool)-1)];
	$run['stage']='battle'; $run['node']=null;
}

function fcg_settle_draft(&$data, &$m)
{
	if($m['result']===null || $m['rewarded']) return;
	$r=&$data['run'];
	if(!$r || $r['id']!==$m['run_id'] || $r['layer']!==$m['run_layer'] || $r['stage']!=='battle') throw new RuntimeException('远征牌局与行程不符，原记录已保留。');
	$m['rewarded']=true; $r['hp']=max(0,min($r['mhp'],$m['sides'][0]['hp']));
	if($m['result']==='win') {
		$r['wins']++;
		$r['mhp']=min(100,$r['mhp']+5);
		$r['hp']=min($r['mhp'],$r['hp']+(int)ceil($r['mhp']*0.15));
		$cards=fcg_cards();
		$pool=array_values(array_unique(array_filter($m['drop_pool'],function($id)use($cards){return !$cards[$id]['unlimited'];}))); shuffle($pool);
		$r['stage']='node'; $r['node']=array('type'=>'reinforce','opts'=>array_slice($pool,0,3));
		fcg_log($m,'本层通过。选择一张幻影记忆补强远征卡组；永久奖励在远征结束后领取。');
	} else {
		$r['hp']=0; $r['stage']='over'; $r['node']=null; $r['ending']=$m['result']==='draw'?'draw':'loss';
		fcg_log($m,'远征在这里停下了。可按已取得的胜场领取最终奖励。');
	}
}

function fcg_draft_action(&$data, &$user, $input, $now, $in_room)
{
	$action=$input['action'];
	if($action==='draft_enter') {
		if(!$in_room) throw new InvalidArgumentException('请先创建或加入 FCG 房间，再开始远征。');
		if(!empty($data['run'])) throw new InvalidArgumentException('请先完成并领取当前远征的奖励。');
		if($data['match'] && $data['match']['result']===null) throw new InvalidArgumentException('请先完成当前牌局。');
		$entry=fcg_draft_tiers()['entry'];
		if($user['credits2']<$entry) throw new InvalidArgumentException('远征需要 '.$entry.' 切糕。');
		$data['run']=array('id'=>bin2hex(random_bytes(12)),'seed'=>random_int(1,2147483647),'stage'=>'draft','entry_cost'=>$entry,
			'picks'=>array(),'deck'=>array(),'hp'=>80,'mhp'=>80,'layer'=>1,'wins'=>0,'node'=>null,'opponents'=>array());
		$user['credits2']-=$entry; $data['match']=null; $data['live_match']=null; $data['last_reward']=null;
		return;
	}
	$r=&$data['run'];
	if(!$r || ($input['run'] ?? null)!==$r['id']) throw new InvalidArgumentException('远征已结束或已切换，请刷新行程。');
	// 离房后仍可领取已结束行程或主动放弃；继续选牌、补给和战斗须回到 FCG 房间。
	if(!$in_room && !in_array($action,array('draft_reward','draft_abandon'),true)) throw new InvalidArgumentException('请先回到 FCG 房间继续远征。');
	if($action==='draft_pick') {
		if($r['stage']!=='draft' || ($input['pick'] ?? null)!==count($r['picks'])) throw new InvalidArgumentException('这一包已选过，请查看当前牌包。');
		$pack=fcg_draft_pack($r); $slot=$input['slot'] ?? null;
		if(!is_int($slot) || !isset($pack[$slot]) || ($input['card'] ?? null)!==$pack[$slot]) throw new InvalidArgumentException('请选择当前牌包中的一张卡。');
		$r['picks'][]=$pack[$slot];
		if(count($r['picks'])===24) {
			$r['deck']=array_merge($r['picks'],fcg_draft_energy($r['picks']));
			$r['stage']='node'; $r['node']=array('type'=>'camp','upgrades_left'=>2);
		}
	} elseif($action==='draft_node') {
		if($r['stage']!=='node' || ($input['layer'] ?? null)!==$r['layer']) throw new InvalidArgumentException('远征节点已更新，请查看当前行程。');
		$choice=$input['choice'] ?? null; $slot=$input['slot'] ?? null; $cards=fcg_cards();
		if($r['node']['type']==='reinforce') {
			if($choice!=='reinforce' || !is_int($slot) || !isset($r['node']['opts'][$slot]) || ($input['card'] ?? null)!==$r['node']['opts'][$slot]) throw new InvalidArgumentException('请选择本次补强中的一张卡。');
			$r['deck'][]=$r['node']['opts'][$slot];
			if($r['wins']===5) { $r['stage']='over'; $r['node']=null; $r['ending']='win'; }
			else { $r['layer']++; $r['node']=array('type'=>'camp','upgrades_left'=>2); }
			return;
		}
		if($choice==='supply' || $choice==='rest') {
			$r['hp']=min($r['mhp'],$r['hp']+(int)round($r['mhp']*($choice==='supply'?0.30:0.15)));
			if($choice==='rest') $data['embers']++;
		} elseif($choice==='upgrade' || $choice==='remove') {
			if(!is_int($slot) || !isset($r['deck'][$slot]) || ($input['card'] ?? null)!==$r['deck'][$slot]) throw new InvalidArgumentException('请选择远征卡组中的一张卡。');
			$id=$r['deck'][$slot]; $c=$cards[$id];
			if($c['unlimited']) throw new InvalidArgumentException('能量基底不能升级或移除。');
			if($choice==='upgrade') {
				$left=$r['node']['upgrades_left'] ?? 2;
				if($left<=0) throw new InvalidArgumentException('本次营地的升级机会已用完。');
				if($c['upgraded'] || !isset($cards[$id+1]) || !$cards[$id+1]['upgraded'] || $cards[$id+1]['pair']!==$c['pair']) throw new InvalidArgumentException('这张卡已经升级，或没有升级版本。');
				$r['deck'][$slot]=$id+1;
				$r['node']['upgrades_left']=$left-1;
				// 第一次升级后仍可补给、移除或小憩；没有可升级卡时直接出发。
				if($left>1) foreach($r['deck'] as $next) {
					if(!$cards[$next]['unlimited'] && !$cards[$next]['upgraded'] && isset($cards[$next+1]) && $cards[$next+1]['upgraded'] && $cards[$next+1]['pair']===$cards[$next]['pair']) return;
				}
			} else array_splice($r['deck'],$slot,1);
		} else throw new InvalidArgumentException('请选择补给或一项强化。');
		fcg_draft_prepare_battle($r);
	} elseif($action==='draft_battle') {
		if($r['stage']!=='battle' || ($input['layer'] ?? null)!==$r['layer'] || ($data['match'] && $data['match']['result']===null)) throw new InvalidArgumentException('请先完成当前节点或牌局，再确认本层对手。');
		$m=fcg_new_match($r['deck'],$r['opponent'],$now);
		$m['sides'][0]['hp']=$r['hp']; $m['sides'][0]['mhp']=$r['mhp'];
		$m['sides'][1]['hp']+=5*($r['layer']-1);
		$m['draft']=true; $m['run_id']=$r['id']; $m['run_layer']=$r['layer'];
		fcg_log($m,'种火远征第 '.$r['layer'].' 层。你带着 '.$r['hp'].' 点生命继续前行。');
		$data['match']=$m; $data['live_match']=null; $r['opponents'][]=$r['opponent'];
	} elseif($action==='draft_abandon') {
		if($r['stage']==='over') throw new InvalidArgumentException('远征已结束，请领取奖励。');
		if($data['match'] && !empty($data['match']['draft']) && !$data['match']['rewarded']) {
			$data['match']['result']='loss'; fcg_settle_draft($data,$data['match']);
		}
		$r['hp']=0; $r['stage']='over'; $r['node']=null; $r['ending']='abandoned';
	} elseif($action==='draft_reward') {
		if($r['stage']!=='over') throw new InvalidArgumentException('远征尚未结束。');
		$tiers=fcg_draft_tiers(); $tier=$tiers[min($r['wins'],5)]; $cards=fcg_cards();
		$deck=array_values(array_unique(array_filter($r['deck'],function($id)use($cards){return !$cards[$id]['unlimited'];})));
		// 改价前的行程已付 500；收据记实际费用，不随领奖时的价目变化。
		$reward=array('draft'=>true,'wins'=>$r['wins'],'cards'=>array(),'embers'=>$tier['embers'],'total_cost'=>$r['entry_cost'] ?? 500);
		// 先稀有、再升级、最后卡组抽；已抽走的卡号不再占用后续卡组奖励位。
		foreach(array('rare','upgraded','deck') as $slot) {
			$pool=array_values(array_filter($deck,function($id)use($slot,$cards){return $slot==='deck' || $cards[$id][$slot];}));
			shuffle($pool);
			for($i=0;$i<$tier[$slot];$i++) {
				if($pool) $id=array_pop($pool);
				elseif($slot!=='deck') {
					$fallback=fcg_pack_pool($slot==='rare'?array('rare'=>true):array('upgraded'=>true,'rare'=>false));
					// 通用卡包池含能量卡，远征保底须额外排除。
					$fallback=array_values(array_filter($fallback,function($id)use($cards){return !$cards[$id]['unlimited'];}));
					$id=$fallback[random_int(0,count($fallback)-1)];
				} else break;
				$deck=array_values(array_diff($deck,array($id)));
				$reward['cards'][]=fcg_add_card($data,$id);
			}
		}
		$data['embers']+=$tier['embers']; $data['last_reward']=$reward;
		$data['run']=null; $data['match']=null;
	} else throw new InvalidArgumentException('未知的远征操作。');
}

function fcg_draft_public($data)
{
	$r=$data['run'] ?? null;
	if(!$r) return null;
	unset($r['seed']);
	if($r['stage']==='draft') {
		$r['circle']=intdiv(count($r['picks']),8); $r['pick']=count($r['picks'])%8;
		$r['pack']=fcg_draft_pack($data['run']);
	}
	return $r;
}
