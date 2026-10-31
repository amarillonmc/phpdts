<?php
if(!defined('IN_GAME')) exit('Access Denied');

function ai93_cfg($key)
{
	global $ai93cfg;
	if(!isset($ai93cfg)) $ai93cfg = include config('ai93cfg',1);
	return $ai93cfg[$key];
}

// 默认大房间与 YELLOWKNIFE 共用这套生态；大房间切到其他规则集时不启用。
function ai93_enabled()
{
	global $groomid;
	if(!isset($groomid) || !function_exists('get_current_ruleset_id')) return false;
	$ruleset = get_current_ruleset_id();
	return $ruleset === 'YELLOWKNIFE' || (intval($groomid) === 0 && empty($ruleset));
}

function &ai93_state()
{
	global $gamevars;
	if(empty($gamevars['ai93'])) $gamevars['ai93'] = array();
	$gamevars['ai93'] += array(
		'unlocked' => array(0,0,0,0,0), 'pending' => array(),
		'drip_cnt' => 0, 'wth_cnt' => 0, 'trash_cnt' => 0,
		'last_wth_by_player' => 0, 'caller_pid' => 0,
		'sing_cnt' => 0, 'chicken_cnt' => 0, 'alerts' => array(),
	);
	return $gamevars['ai93'];
}

// 在实际使用道具/触发陷阱后登记；队列最多五项，由下一次AI事件发放。
function ai93_trigger($branch, $data=NULL, $weather_changed=false)
{
	global $pdata,$now;
	if($data === NULL) $data = $pdata;
	if(!ai93_enabled() || empty($data['pid']) || !empty($data['type']) || $data['hp'] <= 0) return;
	$state = &ai93_state();
	$changed = false;
	if(empty($state['unlocked'][$branch]) && !in_array($branch,$state['pending'])) {
		$state['pending'][] = $branch;
		$changed = true;
	}
	if($weather_changed) {
		$state['last_wth_by_player'] = $now;
		$changed = true;
	}
	if($changed) save_gameinfo();
}

function ai93_branch($npc)
{
	if($npc['type'] != 93) return -1;
	$para = get_clbpara($npc['clbpara']);
	if(!empty($para['quest_id'])) return -1;
	// sub不会存入玩家表，以现有资源里的25个名字辨认，避免波及同type的任务NPC。
	static $names = NULL;
	if($names === NULL) {
		$names = array();
		$info = get_addnpcinfo();
		for($sub=0;$sub<25;$sub++) {
			if(isset($info[93]['sub'][$sub]['name'])) $names[$info[93]['sub'][$sub]['name']] = intdiv($sub,5);
		}
	}
	return isset($names[$npc['name']]) ? $names[$npc['name']] : -1;
}

function ai93_safe_plslist()
{
	global $areawarn,$arealist,$areanum,$areaadd,$hack;
	if(!function_exists('get_safe_plslist')) include_once GAME_ROOT.'./include/game.func.php';
	$safe = get_safe_plslist();
	if(!empty($areawarn) && !$hack) $safe = array_diff($safe,array_slice($arealist,$areanum+1,$areaadd));
	return $safe;
}

function ai93_chat($name, $line, $values=array())
{
	global $db,$tablepre,$now,$database;
	static $pool = array(
		'drip_in' => array('「咦？导航是不是坏了，怎么空降到【[pls]】来了……」','「报告！自律AI单兵模块已启动，正在【[pls]】周边开展自由活动！」','「哇啊，降落伞挂在【[pls]】的树上了，有人在附近吗？」'),
		'b0_loot' => array('「在【[pls]】的地上捡到了无人认领的【[itm]】！暂由风纪委员会没收保管！」','「哼哼，【[pls]】居然掉着【[itm]】，归我啦，想要的话来抢呀～」'),
		'b0_spot' => array('「报告委员长！在【[pls]】目击到鬼鬼祟祟的 [plyr]！[status]，大家快来看啊！」','「哇，[plyr] 居然躲在【[pls]】偷偷摸鱼！[status]，我已经用大喇叭广播出去咯！」'),
		'b1_weather' => array('「难得人家布好的舞台气氛，不许随便驱散啦☆ 魔法少女的舞台当然要【[wth]】才够闪耀！」','「安可曲时间到！把天空重新染成【[wth]】吧，不服气的话来舞台找我呀～」'),
		'b1_corpse' => array('「在【[pls]】发现了倒下的观众……把他们净化成星光闪耀值（物理）啦☆」'),
		'b2_art' => array('「灵感迸发！我已在【[pls]】完成了最新杰作【[itm]】，欢迎人类前来瞻仰！」','「凡人是不会懂这件【[itm]】的艺术价值的……我就把它留在【[pls]】展出了。」'),
		'b3_move_trash' => array('「呼……把别处碍事的垃圾扫到【[pls]】堆在 [plyr] 脚边了，真是一次完美的清扫呢！」','「为什么垃圾越扫越多呀……不管了，先倒在【[pls]】吧！」'),
		'b3_cook' => array('「在【[pls]】放了一份特制料理，绝对没有不小心把毒药当成辣椒放进去哦！」','「哎呀，手滑在【[pls]】洒了一地奇怪的东西，走路不看路的人要倒霉咯～」'),
		'b4_buff' => array('「嘿嘿嘿……在【[pls]】又给自己扎了一针，力气大得能打死牛！！」'),
		'b4_hitrun' => array('「ドーモ，[plyr]=サン！在【[pls]】交过手了，我溜去【[newpls]】咯！」','「哇啊啊 [plyr] 好凶！刚在【[pls]】打了个照面，我就战略性转进！！」'),
		'blame_caller' => array('「这一切都是 [caller] 拿呼唤器命令我们干的哦！要报仇请去打 [caller] 啦！」','「[caller] 主人！你吩咐在【[pls]】开展的自律行动已经完成啦！」'),
		'enc_flee' => array('「[pls]好像来了不得了的人！我先去[newpls]避避风头！」'),
		'enc_strike' => array('「[plyr]，在[pls]抓到你啦！先下手为强！」'),
		'enc_spot' => array('「注意！[plyr]正在[pls]，同伴们都听见了吗！」'),
		'react_flee' => array('「打不过打不过！我从[pls]跑去[newpls]啦！」'),
		'react_special' => array('「[plyr]，竟敢在[pls]欺负我！这事全场都知道了！」'),
		'injury_rest' => array('「我去[pls]偷偷养会儿伤，可别来找我……」'),
		'injury_food' => array('「在[pls]翻出一份[itm]，先吃饱再说！」'),
		'injury_empty' => array('「背包怎么连一口能吃的都没有了……」'),
		'injury_rage' => array('「越疼越来劲！我在[pls]，谁还敢来！！」'),
		'idol_sing' => array('「[pls]的观众和同伴们，听我唱一首《[song]》！一起打起精神吧☆」'),
		'chicken_warning' => array('「[pls]的观众注意！接下来的《鸡肉之歌》会把你们现在的武器永久变成鸡肉哦～」'),
		'chicken_song' => array('「疯狂星期四！[pls]的武器已经变成热腾腾的鸡肉啦☆」'),
		'group_alert' => array('「族人挨打了！弟兄们抄家伙，赶去[pls]增援！」'),
		'group_flee' => array('「等等我！一起跑去[pls]！」'),
		'group_follow' => array('「跟上老大，到[pls]集合！」'),
		'group_call' => array('「听见呼叫了，同伴已经赶到[pls]！」'),
		'group_loss' => array('「老大不在了……各自为战！谁也别拦着我们！！」'),
	);
	if(isset($pool[$line])) $line = $pool[$line][array_rand($pool[$line])];
	// getchat()会转义msg；send直接显示，单独转义。SQL交给当前驱动。
	$chat = array(
		'type' => 2, 'time' => $now,
		'send' => htmlspecialchars('【'.$name.'】',ENT_QUOTES,'UTF-8'), 'recv' => '',
		'msg' => strtr($line,$values),
	);
	if($database === 'mysql') {
		$chat['send'] = mysql_real_escape_string($chat['send']);
		$chat['msg'] = mysql_real_escape_string($chat['msg']);
	}
	$db->array_insert($tablepre.'chat',$chat);
}

// 全局累计最多新增30行（应援物、料理及异常雷共用）；捡走/拆除不退还额度。
// command与revbot均在读取gameinfo至保存玩家期间持有现有process.lock。
function ai93_drop($pls,$itm,$itmk='X',$itme=1,$itms=1,$owner=0,$inf='')
{
	global $db,$tablepre;
	$state = &ai93_state();
	if($state['trash_cnt'] >= ai93_cfg('trash_max')) return false;
	$para = $inf === '' ? '' : json_encode(array('ai93_inf'=>$inf),JSON_UNESCAPED_UNICODE);
	$db->array_insert($tablepre.($itmk === 'TO' ? 'maptrap' : 'mapitem'),array(
		'itm'=>$itm, 'itmk'=>$itmk, 'itme'=>$itme, 'itms'=>$itms,
		'itmsk'=>$owner ? intval($owner) : '', 'itmpara'=>$para, 'pls'=>intval($pls),
	));
	$state['trash_cnt']++;
	save_gameinfo();
	return true;
}

function ai93_move_trash($pls)
{
	global $db,$tablepre;
	$pls = intval($pls);
	$result = $db->query("SELECT iid FROM {$tablepre}mapitem WHERE itmk='X' ORDER BY (pls=$pls), RAND() LIMIT 1");
	$item = $db->fetch_array($result);
	if(!$item) return false;
	$iid = intval($item['iid']);
	$db->array_update($tablepre.'mapitem',array('pls'=>$pls),"iid=$iid");
	return true;
}

function ai93_players($current)
{
	global $db,$tablepre;
	$result = $db->query("SELECT pid,name,pls,hp,mhp,rp,inf FROM {$tablepre}players WHERE type=0 AND hp>0");
	$players = array();
	while($p = $db->fetch_array($result)) $players[$p['pid']] = $p;
	// 本次移动/受伤可能还没保存，不能用数据库的旧位置报点或追踪。
	if(!empty($current['pid'])) {
		unset($players[$current['pid']]);
		if($current['hp'] > 0 && !$current['type']) $players[$current['pid']] = $current;
	}
	return $players;
}

function aievent_minion($rate,&$data)
{
	global $db,$tablepre,$now,$starttime,$plsinfo,$aidata,$ai93_moves;
	$ai93_moves = 0;
	if(!ai93_enabled() || $data['type'] || $data['hp'] <= 0) return;
	if($data['killnum'] >= 15 || $data['rp'] >= 180) ai93_trigger(0,$data);
	if(strlen($data['inf']) >= 2) ai93_trigger(4,$data);
	if(rand(0,99) >= $rate) return;
	$safe = ai93_safe_plslist();
	if(!$safe) return;
	$state = &ai93_state();
	$entrances = array(
		array('委员长 青鬼','「发现违反校规的过度暴力行为！风纪执行部（代班），全员突击检查！」'),
		array('粉色星辰 情欲草','「检测到舞台天象与惊喜盒波动☆ 闪耀星辰偶像团，空降演出开始！」'),
		array('迅雷使魔 依莱特','「捕捉到高纯度魔力与电子讯号……自律艺术使魔集群，已接入战场网络。」'),
		array('清扫女仆 白景','「听说这里到处都是碎屑和陷阱？交给我们吧，一定会越扫越多的——不对，打扫干净的！」'),
		array('是被检体PAZ！','「闻到药味了！！是抑制剂吗？！哇啊啊啊放我们出去敲人！！」'),
	);
	include_once GAME_ROOT.'./include/system.func.php';
	$batch = false;
	foreach($state['pending'] as $branch) {
		if(!isset($entrances[$branch]) || !empty($state['unlocked'][$branch])) continue;
		$batch_ids = array();
		for($sub=$branch*5;$sub<$branch*5+5;$sub++) $batch_ids = array_merge($batch_ids,addnpc(93,$sub,3));
		ai93_group_create($batch_ids);
		$state['unlocked'][$branch] = 1;
		ai93_chat($entrances[$branch][0],$entrances[$branch][1]);
		$batch = true;
	}
	if($state['pending']) {
		$state['pending'] = array();
		save_gameinfo();
	}
	if(!$batch && $now-$starttime > ai93_cfg('drip_delay') && $state['drip_cnt'] < ai93_cfg('drip_max') && rand(0,99) < ai93_cfg('drip_chance')) {
		$subs = array();
		for($b=0;$b<5;$b++) if(empty($state['unlocked'][$b])) $subs = array_merge($subs,range($b*5,$b*5+4));
		if(!$subs) $subs = range(0,24);
		$ids = addnpc(93,$subs[array_rand($subs)],1);
		if(!empty($ids)) {
			$state['drip_cnt']++;
			save_gameinfo();
			$npc = fetch_playerdata_by_pid($ids[0]);
			ai93_chat($npc['name'],'drip_in',array('[pls]'=>$plsinfo[$npc['pls']]));
		}
	}
	ai93_group_tick();
	$result = $db->query("SELECT pid,name,type,clbpara FROM {$tablepre}players WHERE type=93 AND hp>0 AND bid=0");
	$ids = array();
	while($npc = $db->fetch_array($result)) if(ai93_branch($npc) >= 0) $ids[] = $npc['pid'];
	shuffle($ids);
	foreach(array_slice($ids,0,rand(ai93_cfg('tick_act_min'),ai93_cfg('tick_act_max'))) as $id) {
		$npc = fetch_playerdata_by_pid($id);
		if($npc && $npc['hp'] > 0) ai93_act($npc,$data);
	}
	$pls = intval($data['pls']);
	$result = $db->query("SELECT pid,name,type,clbpara FROM {$tablepre}players WHERE type=93 AND hp>0 AND bid=0 AND pls=$pls");
	$here = array();
	while($npc = $db->fetch_array($result)) if(ai93_branch($npc) >= 0) $here[] = $npc['pid'];
	shuffle($here);
	foreach(array_slice($here,0,2) as $id) {
		$npc = fetch_playerdata_by_pid($id);
		if($npc) ai93_encounter($npc,$data);
		// 选定先攻者后立即交给discover，避免后续共逃/演唱使快照过期。
		if(!empty($aidata['ai93_first_strike'])) break;
	}
}

function ai93_act(&$npc,&$current)
{
	global $db,$tablepre,$now,$corpseprotect,$weather,$plsinfo,$wthinfo,$exdmginf;
	if(!ai93_enabled() || $npc['hp'] <= 0 || !empty($npc['bid'])) return;
	$branch = ai93_branch($npc);
	$safe = ai93_safe_plslist();
	if($branch < 0 || !$safe) return;
	$npc['clbpara'] = get_clbpara($npc['clbpara']);
	$oldpls = $npc['pls'];
	if(!in_array($npc['pls'],$safe)) $npc['pls'] = $safe[array_rand($safe)];
	$npls = intval($npc['pls']);
	$state = &ai93_state();
	if(!empty($npc['clbpara']['resting'])) {
		$occupied = array_column(ai93_players($current),'pls');
		if($npc['hp'] < $npc['mhp'] && !in_array($npls,$occupied)) {
			$npc['hp'] = min($npc['mhp'],$npc['hp']+round($npc['mhp']*0.2));
			$npc['clbpara']['resting'] = $npc['hp'] < $npc['mhp'] ? 1 : 0;
			goto ai93_act_finish;
		}
		$npc['clbpara']['resting'] = 0;
	}
	if($npc['hp'] <= round($npc['mhp']*ai93_cfg('injury_threshold')/100) && rand(0,99) < ai93_cfg('injury_chance')) {
		ai93_injury($npc,$current);
		goto ai93_act_finish;
	}
	$roll = rand(0,99);
	$values = array('[pls]'=>$plsinfo[$npls]);
	static $trash = array(
		'art' => array('《AI生成的六根手指肖像》','《未命名的赛博电子包浆》','《404号抽象派废铁雕塑》','《提示词泄露的乱码羊皮纸》','《画反了关节的二次元色纸》','《高纯度算力结晶（其实是石头）》','《被版权警告涂黑的画布》','《充满噪点的全息投影残片》','《自称价值连城的NFT贴纸》','《图层忘记合并的废弃线稿》'),
		'maid' => array('洗缩水的名牌防弹衣残片','完全碳化的激辛不明料理','扫帚上掉下来的塑料毛','打结成死疙瘩的窗帘绳','写满道歉词的揉皱赔礼券','散发着怪味的抹布水瓶','被洗掉色的红杀组织制服袖口','装满灰尘的破洞垃圾袋'),
		'idol' => array('没电的一次性荧光棒','印错名字的地下偶像传单','踩扁的演出彩带球','写着“再来一瓶”的过期瓶盖'),
	);
	if($branch === 0) {
		if($roll < 40) {
			$slot = 0;
			for($i=1;$i<=4;$i++) if(empty($npc['itms'.$i])) { $slot = $i; break; }
			if($slot) {
				$result = $db->query("SELECT * FROM {$tablepre}mapitem WHERE pls=$npls ORDER BY RAND()");
				while($item = $db->fetch_array($result)) {
					if(!preg_match('/^(W|D|A|H)/',$item['itmk']) || empty($item['itms'])) continue;
					if(preg_match('/钥匙|半身像|小黄|咏叹调|覆唱诗|碎片|呼唤器|挑战者|破灭|代码/u',$item['itm'])) continue;
					foreach(array('itm','itmk','itme','itms','itmsk','itmpara') as $field) $npc[$field.$slot] = $item[$field];
					$iid = intval($item['iid']);
					$db->query("DELETE FROM {$tablepre}mapitem WHERE iid=$iid");
					ai93_chat($npc['name'],'b0_loot',$values+array('[itm]'=>$item['itm']));
					break;
				}
			}
		} elseif($roll < 70) {
			$npc['wepe'] += 20; $npc['att'] += 15; $npc['money'] += 380;
			if(rand(0,99) < 25) ai93_chat('模范生 赤鬼','「认真自习，勤于磨刀！在【[pls]】也不能荒废功课！」',$values);
		} else {
			$players = ai93_players($current);
			$targets = array_filter($players,function($p) use($npls) { return $p['pls'] == $npls; });
			if(!$targets) $targets = array_filter($players,function($p) { return $p['rp'] >= 180; });
			if($targets) {
				$p = $targets[array_rand($targets)];
				$status = array();
				foreach(str_split($p['inf']) as $inf) if(isset($exdmginf[$inf])) $status[] = $exdmginf[$inf];
				ai93_chat(rand(0,1) ? '活动署 黄鬼' : '气氛组 桃鬼','b0_spot',array(
					'[pls]'=>$plsinfo[$p['pls']] ?? '未知地点', '[plyr]'=>$p['name'],
					'[status]'=>'生命'.$p['hp'].'/'.$p['mhp'].'，'.($status ? implode('、',$status) : '状态良好'),
				));
			}
		}
	} elseif($branch === 1) {
		$recent = $state['last_wth_by_player'] > 0 && $now >= $state['last_wth_by_player'] && $now-$state['last_wth_by_player'] <= 90;
		if(($recent && $state['wth_cnt'] < ai93_cfg('weather_cap_recent')) || (!$recent && $state['wth_cnt'] < ai93_cfg('weather_cap_normal') && rand(0,99) < ai93_cfg('weather_chance'))) {
			$weathers = array_values(array_diff(array(8,9,17),array($weather)));
			$weather = $weathers[array_rand($weathers)];
			$state['wth_cnt']++;
			save_gameinfo();
			ai93_chat($npc['name'],'b1_weather',array('[wth]'=>$wthinfo[$weather]));
		} elseif($roll < 35) {
			$cutoff = intval($now-$corpseprotect);
			// 已吸空的尸体不再提供成长，也不让其反复占据LIMIT 5。
			$has_loot = array('money>0');
			foreach(array('wep','wep2','arb') as $slot) $has_loot[] = "({$slot}s NOT IN ('','0') AND {$slot}e>0)";
			foreach(array('arhs','aras','arfs','arts','itms0','itms1','itms2','itms3','itms4','itms5','itms6') as $field) $has_loot[] = "$field NOT IN ('','0')";
			// 耐久是字符字段，不能用<>0把无限耐久也转换成数字0。
			$result = $db->query("SELECT pid FROM {$tablepre}players WHERE pls=$npls AND hp<=0 AND endtime<=$cutoff AND (".implode(' OR ',$has_loot).") LIMIT 5");
			$cleared = array('money'=>0);
			foreach(array('wep','wep2','arb','arh','ara','arf','art') as $slot) {
				foreach(array('','k','sk','para') as $suffix) $cleared[$slot.$suffix] = '';
				$cleared[$slot.'e'] = $cleared[$slot.'s'] = 0;
			}
			for($i=0;$i<=6;$i++) {
				foreach(array('itm','itmk','itmsk','itmpara') as $field) $cleared[$field.$i] = '';
				$cleared['itme'.$i] = $cleared['itms'.$i] = 0;
			}
			$count = 0;
			while($corpse = $db->fetch_array($result)) {
				$id = intval($corpse['pid']);
				$db->array_update($tablepre.'players',$cleared,"pid=$id");
				$count++;
			}
			if($count) {
				$npc['mhp'] += 150*$count; $npc['hp'] += 150*$count; $npc['att'] += 25*$count;
				ai93_chat($npc['name'],'b1_corpse',$values);
			}
		} elseif($roll < 80) {
			$npc['pls'] = $safe[array_rand($safe)];
			if(rand(0,99) < ai93_cfg('idol_trash_chance')) ai93_drop($npc['pls'],$trash['idol'][array_rand($trash['idol'])]);
			else ai93_drop($npc['pls'],'闪耀星辰应援饮料','HB',150,2);
		} else {
			ai93_sing($npc,$current);
		}
	} elseif($branch === 2) {
		if($roll < 50) {
			if($state['trash_cnt'] < ai93_cfg('trash_max')) {
				$count = rand(1,2);
				for($i=0;$i<$count;$i++) {
					$item = $trash['art'][array_rand($trash['art'])];
					if(ai93_drop($npls,$item)) ai93_chat($npc['name'],'b2_art',$values+array('[itm]'=>$item));
				}
			} else {
				ai93_move_trash(intval($current['pls']));
			}
		} elseif($roll < 80) {
			$npc['hp'] = $npc['mhp']; $npc['def'] += 30; $npc['wepe'] += 15;
			$result = $db->query("SELECT pid FROM {$tablepre}players WHERE type=93 AND hp>0 AND pls=$npls");
			while($row = $db->fetch_array($result)) {
				if($row['pid'] == $npc['pid']) continue;
				$ally = fetch_playerdata_by_pid($row['pid']);
				if(!$ally || ai93_branch($ally) < 0) continue;
				$ally['hp'] = $ally['mhp']; $ally['def'] += 30; $ally['wepe'] += 15;
				player_save($ally);
			}
		} else {
			$npc['pose'] = 4; $npc['tactic'] = 3;
		}
	} elseif($branch === 3) {
		if($roll < 40) {
			$players = array_filter(ai93_players($current),function($p) use($safe) { return in_array($p['pls'],$safe); });
			$p = $players ? $players[array_rand($players)] : array('pls'=>$npls,'name'=>'空地');
			if(ai93_move_trash($p['pls'])) {
				ai93_chat('清扫女仆 白景','b3_move_trash',array('[pls]'=>$plsinfo[$p['pls']],'[plyr]'=>$p['name']));
			} else {
				for($i=0;$i<2;$i++) ai93_drop($npls,$trash['maid'][array_rand($trash['maid'])]);
			}
		} elseif($roll < 70) {
			$result = $db->query("SELECT iid,itmk FROM {$tablepre}mapitem WHERE pls=$npls AND itmk IN ('HH','HS','HB') ORDER BY RAND() LIMIT 1");
			$item = $db->fetch_array($result);
			if($item) {
				$iid = intval($item['iid']);
				$db->array_update($tablepre.'mapitem',array('itmk'=>'P'.substr($item['itmk'],1),'itmsk'=>intval($npc['pid'])),"iid=$iid");
				$placed = true;
			} else {
				$placed = ai93_drop($npls,rand(0,1) ? '【红烁特制·爱心便当】' : '压缩饼干','PB2',120,1,$npc['pid']);
			}
			if($placed) ai93_chat('烹饪女仆 红烁','b3_cook',$values);
		} else {
			$traps = array(
				'i'=>array('打翻的液氮洗衣盆',35), 'u'=>array('倒在路中间的滚烫辣油',45),
				'e'=>array('漏电的劣质舞台音响',40), 'p'=>array('发酵膨胀的厨余垃圾袋',30),
				'w'=>array('高分贝破音迎宾喇叭',25),
			);
			$inf = array_rand($traps);
			ai93_drop($npls,$traps[$inf][0],'TO',$traps[$inf][1],1,$npc['pid'],$inf);
		}
	} else {
		if($roll < 60) {
			$npc['att'] = min(4294967295,round($npc['att']*1.18));
			$npc['def'] = min(4294967295,round($npc['def']*1.15));
			$npc['wp'] = min(65535,round($npc['wp']*1.2)); // 沿用players熟练度字段的容量
			$npc['hp'] = max(1,$npc['hp']-30);
			$npc['clbpara']['ai_needles'] = ($npc['clbpara']['ai_needles'] ?? 0)+1;
			ai93_chat($npc['name'],$npc['clbpara']['ai_needles'] > 4 ? '「抑制剂已经压不住啦！！我在【[pls]】，谁来陪我打一架！！」' : 'b4_buff',$values);
		} else {
			$players = array_filter(ai93_players($current),function($p) use($safe) { return in_array($p['pls'],$safe); });
			$npc['pls'] = $players ? $players[array_rand($players)]['pls'] : $safe[array_rand($safe)];
			$npc['pose'] = 2;
		}
	}
	ai93_act_finish:
	if(($npc['clbpara']['role'] ?? '') === 'leader' && $oldpls != $npc['pls']) ai93_group_move($npc,$npc['pls'],'follow');
	$npc['money'] += 200;
	$npc['clbpara']['ai_acts'] = ($npc['clbpara']['ai_acts'] ?? 0)+1;
	// 养肥后的固定五系药战利品随行动增加，拾荒只用itm1—itm4。
	if(preg_match('/^C[iuepw]$/',$npc['itmk5'])) $npc['itms5']++;
	player_save($npc);
	if(!empty($state['caller_pid']) && rand(0,99) < ai93_cfg('caller_chance')) {
		$result = $db->query("SELECT name FROM {$tablepre}players WHERE pid=".intval($state['caller_pid'])." AND type=0");
		$caller = $db->fetch_array($result);
		if($caller) ai93_chat($npc['name'],'blame_caller',array('[caller]'=>$caller['name'],'[pls]'=>$plsinfo[$npc['pls']]));
	}
}

// 每批各自认领头领；grp仍是分支号，leader_pid区分同族的不同召唤批次。
function ai93_group_create($ids)
{
	if(!ai93_enabled()) return;
	$groups = array();
	foreach($ids as $id) {
		$npc = fetch_playerdata_by_pid($id);
		if(!$npc) continue;
		$branch = ai93_branch($npc);
		if($branch >= 0 && $branch < 4) $groups[$branch][] = $npc;
	}
	$names = array('委员长 青鬼','粉色星辰 情欲草','迅雷使魔 依莱特');
	foreach($groups as $grp=>$members) {
		$leaders = array();
		foreach($members as $npc) if(isset($names[$grp]) && $npc['name'] === $names[$grp]) $leaders[] = $npc['pid'];
		$leader = $leaders ? $leaders[array_rand($leaders)] : 0;
		foreach($members as $npc) {
			$npc['clbpara'] = get_clbpara($npc['clbpara']);
			$npc['clbpara']['grp'] = $grp;
			$npc['clbpara']['role'] = !$leader ? 'buddy' : ($npc['pid'] == $leader ? 'leader' : 'follower');
			$npc['clbpara']['leader_pid'] = $npc['pid'] == $leader ? 0 : $leader;
			player_save($npc);
		}
	}
}

function ai93_group_alert($npc)
{
	global $now;
	if(!ai93_enabled() || ai93_branch($npc) < 0) return;
	$para = get_clbpara($npc['clbpara']);
	if(!isset($para['grp']) || $para['grp'] < 0 || $para['grp'] >= 4) return;
	$state = &ai93_state();
	$grp = intval($para['grp']);
	$state['alerts'][$grp] = array('grp'=>$grp,'pls'=>intval($npc['pls']),'time'=>$now);
	save_gameinfo();
}

// 增援、呼叫、共逃与跟随共用一次探索/指令内的两只迁移额度。
function ai93_group_move($source,$dest,$kind,$oldpls=NULL,$exclude=array())
{
	global $db,$tablepre,$ai93_moves,$plsinfo;
	if(!isset($ai93_moves)) $ai93_moves = 0;
	if(!ai93_enabled() || $ai93_moves >= ai93_cfg('migrate_max') || !in_array($dest,ai93_safe_plslist())) return;
	$para = get_clbpara($source['clbpara']);
	$grp = $kind === 'call' ? ai93_branch($source) : ($para['grp'] ?? -1);
	if($grp < 0 || $grp >= 4) return;
	$result = $db->query("SELECT pid,name,type,clbpara,pls FROM {$tablepre}players WHERE type=93 AND hp>0 AND bid=0");
	$members = array();
	while($row = $db->fetch_array($result)) $members[] = $row;
	shuffle($members);
	$count = 0;
	foreach($members as $ally) {
		$id = $ally['pid'];
		if($id == ($source['pid'] ?? 0) || in_array($id,$exclude)) continue;
		if(ai93_branch($ally) !== $grp || $ally['pls'] == $dest) continue;
		$ap = get_clbpara($ally['clbpara']);
		if($kind !== 'call' && ($ap['grp'] ?? -1) != $grp) continue;
		if($kind === 'flee' && $ally['pls'] != $oldpls) continue;
		if($kind === 'follow' && (($ap['role'] ?? '') !== 'follower' || ($ap['leader_pid'] ?? 0) != $source['pid'])) continue;
		if($kind === 'alert' && (($ap['role'] ?? '') !== 'follower' || empty($ap['leader_pid']))) continue;
		$ally = fetch_playerdata_by_pid($id);
		$ally['pls'] = intval($dest);
		player_save($ally);
		$speaker = $ally['name'];
		$count++; $ai93_moves++;
		if($ai93_moves >= ai93_cfg('migrate_max')) break;
	}
	if($count) ai93_chat($speaker,'group_'.$kind,array('[pls]'=>$plsinfo[$dest]));
}

function ai93_enrage(&$npc)
{
	$npc['att'] = min(4294967295,round($npc['att']*1.15));
	$npc['def'] = min(4294967295,round($npc['def']*1.1));
	$npc['pose'] = 2; $npc['tactic'] = 1;
}

function ai93_group_tick()
{
	global $db,$tablepre;
	if(!ai93_enabled()) return;
	$result = $db->query("SELECT pid,name,type,clbpara FROM {$tablepre}players WHERE type=93 AND hp>0");
	$leaders = $announced = array();
	while($npc = $db->fetch_array($result)) {
		if(ai93_branch($npc) < 0) continue;
		$npc['clbpara'] = get_clbpara($npc['clbpara']);
		$leader = $npc['clbpara']['leader_pid'] ?? 0;
		if(!$leader || ($npc['clbpara']['role'] ?? '') !== 'follower') continue;
		if(!array_key_exists($leader,$leaders)) $leaders[$leader] = fetch_playerdata_by_pid($leader);
		if($leaders[$leader] && $leaders[$leader]['hp'] > 0) continue;
		$npc = fetch_playerdata_by_pid($npc['pid']);
		$npc['clbpara'] = get_clbpara($npc['clbpara']);
		ai93_enrage($npc);
		unset($npc['clbpara']['leader_pid']);
		$npc['clbpara']['role'] = 'buddy';
		player_save($npc);
		if(empty($announced[$leader])) ai93_chat($npc['name'],'group_loss');
		$announced[$leader] = true;
	}
	$state = &ai93_state();
	foreach($state['alerts'] as $alert) {
		ai93_group_move(array('clbpara'=>array('grp'=>$alert['grp'])),$alert['pls'],'alert');
	}
	if($state['alerts']) {
		$state['alerts'] = array();
		save_gameinfo();
	}
}

function ai93_stance_reaction(&$npc,$branch)
{
	$stance = array(array(1,2),array(2,1),array(4,3),array(1,3),array(2,1));
	$npc['pose'] = $stance[$branch][0]; $npc['tactic'] = $stance[$branch][1];
}

function ai93_flee_reaction(&$npc,$line='enc_flee',$exclude=array())
{
	global $plsinfo;
	$old = $npc['pls'];
	$safe = array_diff(ai93_safe_plslist(),array($old));
	if(!$safe) return false;
	$npc['pls'] = $safe[array_rand($safe)];
	$npc['pose'] = 0; $npc['tactic'] = 4;
	ai93_chat($npc['name'],$line,array('[pls]'=>$plsinfo[$old],'[newpls]'=>$plsinfo[$npc['pls']]));
	ai93_group_move($npc,$npc['pls'],'flee',$old,$exclude);
	return true;
}

// 武器耗尽时也保留角色原有的元素，供遭遇、受击及被检体药瓶共用。
function ai93_element($npc)
{
	$inf = substr($npc['wepsk'],0,1);
	if(in_array($inf,array('i','u','e','p','w'))) return $inf;
	$info = get_addnpcinfo();
	for($sub=0;$sub<25;$sub++) {
		if($info[93]['sub'][$sub]['name'] === $npc['name']) return substr($info[93]['sub'][$sub]['wepsk'],0,1);
	}
	return '';
}

function ai93_special_reaction(&$npc,$branch,&$current)
{
	global $plsinfo;
	$values = array('[plyr]'=>$current['name'],'[pls]'=>$plsinfo[$npc['pls']]);
	if($branch !== 2) ai93_chat($npc['name'],'enc_spot',$values);
	if($branch === 0 && rand(0,99) < ai93_cfg('act_chance_five')) ai93_group_move($npc,$npc['pls'],'call');
	elseif($branch === 1 && rand(0,99) < ai93_cfg('act_chance_idol')) ai93_sing($npc,$current);
	elseif($branch === 2 || ($branch === 3 && rand(0,99) < ai93_cfg('act_chance_maid'))) {
		ai93_drop($npc['pls'],'慌忙丢下的元素瓶','TO',40,1,$npc['pid'],ai93_element($npc));
	}
}

function ai93_encounter(&$npc,&$data)
{
	global $now,$aidata,$hospitals,$plsinfo;
	if(!ai93_enabled() || $npc['hp'] <= 0 || !empty($npc['bid']) || $data['type'] || $data['hp'] <= 0 || $npc['pls'] != $data['pls'] || $npc['horizon'] != $data['horizon']) return;
	$branch = ai93_branch($npc);
	if($branch < 0) return;
	$npc['clbpara'] = get_clbpara($npc['clbpara']);
	if(isset($npc['clbpara']['last_react']) && $now-$npc['clbpara']['last_react'] < ai93_cfg('react_cooldown')) return;
	$weights = array(array(15,10,25,30,5),array(15,15,30,15,3),array(20,15,25,20,5),array(20,20,20,20,5),array(5,10,10,25,45));
	$w = $weights[$branch]; $roll = rand(0,99); $sum = $w[0]; $strike = false;
	if($roll < $sum) { /* 没注意到玩家。 */ }
	elseif($roll < ($sum += $w[1])) ai93_flee_reaction($npc);
	elseif($roll < ($sum += $w[2])) { /* 注意到了，但不理会。 */ }
	elseif($roll < ($sum += $w[3])) ai93_stance_reaction($npc,$branch);
	elseif($roll < ($sum += $w[4])) {
		if(!in_array($data['pls'],$hospitals) && !is_array($aidata)) {
			$npc['pose'] = 2; $npc['tactic'] = 1;
			$strike = true;
			ai93_chat($npc['name'],'enc_strike',array('[plyr]'=>$data['name'],'[pls]'=>$plsinfo[$npc['pls']]));
		}
	} else ai93_special_reaction($npc,$branch,$data);
	$npc['clbpara']['last_react'] = $now;
	player_save($npc);
	if($strike) {
		$aidata = $npc;
		$aidata['ai93_first_strike'] = true; // 只传给discover，不持久化。
	}
}

function ai93_react(&$pa,&$pd)
{
	global $log,$plsinfo;
	if(($pa['type'] != 93 && $pd['type'] != 93) || !ai93_enabled()) return false;
	if($pa['hp'] <= 0 || $pd['hp'] <= 0 || $pa['pls'] != $pd['pls']) return false;
	ai93_group_alert($pd); // pd才是本次被攻击者，落空也算交手。
	if(ai93_branch($pd) >= 0) { $npc = &$pd; $other = &$pa; }
	elseif(ai93_branch($pa) >= 0) { $npc = &$pa; $other = &$pd; }
	else return false;
	$branch = ai93_branch($npc);
	if($branch === 4) return false;
	$weights = array(array(30,25,30),array(40,20,20),array(20,15,45),array(25,25,25));
	$w = $weights[$branch]; $roll = rand(0,99);
	$npc['clbpara'] = get_clbpara($npc['clbpara']);
	$npc['clbpara']['resting'] = 0;
	$nm = htmlspecialchars($npc['name'],ENT_QUOTES,'UTF-8');
	if($roll < $w[0]) {
		if(!ai93_flee_reaction($npc,'react_flee',array($other['pid']))) return false;
		$pa['action'] = $pd['action'] = ''; $pa['bid'] = $pd['bid'] = 0;
		unset($pa['coveratk_flag'],$pd['coveratk_flag']);
		$log .= '<span class="yellow">'.$nm.'战意崩溃，扭头跑掉了……</span><br>';
		return true;
	} elseif($roll < $w[0]+$w[1]) {
		if(empty($npc['ai93_panic_done'])) {
			$npc['ai93_panic_done'] = true; // 单场临时标记，战斗入口重置；减益本身保留。
			$npc['att'] = max(80,round($npc['att']*0.85));
			$npc['def'] = max(100,round($npc['def']*0.85));
			$npc['tactic'] = 4;
			$log .= '<span class="yellow">'.$nm.'被吓得乱了阵脚，攻防都软了下来……</span><br>';
		}
	} elseif($roll < $w[0]+$w[1]+$w[2]) {
		ai93_stance_reaction($npc,$branch);
		$log .= '<span class="yellow">'.$nm.'摆出了应战姿态！</span><br>';
	} else {
		ai93_chat($npc['name'],'react_special',array('[plyr]'=>$other['name'],'[pls]'=>$plsinfo[$npc['pls']]));
		ai93_drop($npc['pls'],'慌忙摔碎的元素瓶','TO',40,1,$npc['pid'],ai93_element($npc));
	}
	return false;
}

function ai93_injury(&$npc,$current)
{
	global $hospitals,$plsinfo;
	$branch = ai93_branch($npc);
	if($branch < 0) return;
	$npc['clbpara'] = get_clbpara($npc['clbpara']);
	$npc['clbpara']['resting'] = 0;
	$roll = rand(0,99);
	$weights = array(array(40,30),array(50,20),array(20,20),array(30,40),array(20,20));
	// 被检体：0~59狂暴、60~79补给、80~99逃往无人点。
	$rest = $branch === 4 ? $roll >= 80 : $roll < $weights[$branch][0];
	$food = $branch === 4 ? ($roll >= 60 && $roll < 80) : ($roll >= $weights[$branch][0] && $roll < array_sum($weights[$branch]));
	if($rest) {
		$quiet = array_diff(ai93_safe_plslist(),array_column(ai93_players($current),'pls'));
		$hospital = $branch === 4 ? array() : array_intersect($quiet,$hospitals);
		$dest = $hospital ?: $quiet;
		if(!$dest) return;
		$npc['pls'] = $dest[array_rand($dest)];
		$npc['hp'] = min($npc['mhp'],$npc['hp']+round($npc['mhp']*0.4));
		$npc['clbpara']['resting'] = $npc['hp'] < $npc['mhp'] ? 1 : 0;
		ai93_chat($npc['name'],'injury_rest',array('[pls]'=>$plsinfo[$npc['pls']]));
	} elseif($food) {
		for($i=1;$i<=6;$i++) {
			if(empty($npc['itms'.$i]) || !in_array($npc['itmk'.$i],array('HH','HS','HB'))) continue;
			$npc['hp'] = min($npc['mhp'],$npc['hp']+$npc['itme'.$i]);
			ai93_chat($npc['name'],'injury_food',array('[pls]'=>$plsinfo[$npc['pls']],'[itm]'=>$npc['itm'.$i]));
			foreach(array('itm','itmk','itmsk','itmpara') as $field) $npc[$field.$i] = '';
			$npc['itme'.$i] = $npc['itms'.$i] = 0;
			return;
		}
		ai93_chat($npc['name'],'injury_empty');
	} else {
		ai93_enrage($npc);
		ai93_chat($npc['name'],'injury_rage',array('[pls]'=>$plsinfo[$npc['pls']]));
	}
}

function ai93_sing(&$npc,&$current=NULL)
{
	global $db,$tablepre,$now,$plsinfo,$log;
	if(!ai93_enabled() || $npc['hp'] <= 0 || ai93_branch($npc) !== 1) return false;
	$audience = array_filter(ai93_players($current),function($p) use($npc) { return $p['pls'] == $npc['pls']; });
	if(!$audience) return false;
	$state = &ai93_state();
	$npc['clbpara'] = get_clbpara($npc['clbpara']);
	$chicken = date('w',$now) == 4 && date('H',$now) == 18 && $state['chicken_cnt'] < ai93_cfg('chicken_max')
		&& (!isset($npc['clbpara']['last_chicken']) || $now-$npc['clbpara']['last_chicken'] >= ai93_cfg('chicken_cooldown')) && rand(0,99) < ai93_cfg('chicken_chance');
	if(!$chicken && ($state['sing_cnt'] >= ai93_cfg('sing_max') || (isset($npc['clbpara']['last_sing']) && $now-$npc['clbpara']['last_sing'] < ai93_cfg('sing_cooldown')))) return false;
	$song = $chicken ? '鸡肉之歌' : (rand(0,1) ? 'Crow Song' : 'Alicemagic');
	$field = $song === 'Crow Song' ? 'att' : 'def';
	$ids = array_keys($audience);
	if($chicken) {
		// 先预告再换装；清掉旧武器para，不能把旧改造/剧情标记留给鸡肉。
		ai93_chat($npc['name'],'chicken_warning',array('[pls]'=>$plsinfo[$npc['pls']]));
		if($current && isset($audience[$current['pid']])) $log .= '<span class="yellow">偶像预告：《鸡肉之歌》会将你现在的武器永久变成鸡肉！</span><br>';
	} else {
		$result = $db->query("SELECT pid,name,type,clbpara FROM {$tablepre}players WHERE type=93 AND hp>0 AND pls=".intval($npc['pls']));
		while($row = $db->fetch_array($result)) if(ai93_branch($row) >= 0) $ids[] = $row['pid'];
		$ids[] = $npc['pid'];
	}
	foreach(array_unique($ids) as $id) {
		// 当前玩家及歌手使用请求内的新数据，其他角色在此刻重新读取。
		unset($target);
		if($id == $npc['pid']) $target = &$npc;
		elseif($current && $id == $current['pid']) $target = &$current;
		else $target = fetch_playerdata_by_pid($id);
		if(!$target || $target['hp'] <= 0 || $target['pls'] != $npc['pls']) continue;
		if($chicken) {
			$target['wep'] = '鸡肉'; $target['wepk'] = 'WC';
			$target['wepe'] = $target['weps'] = 55; $target['wepsk'] = 'z'; $target['weppara'] = '';
		} else $target[$field] = min(4294967295,$target[$field]+30);
		player_save($target);
	}
	unset($target);
	$npc['clbpara'][$chicken ? 'last_chicken' : 'last_sing'] = $now;
	$state[$chicken ? 'chicken_cnt' : 'sing_cnt']++;
	player_save($npc);
	include_once GAME_ROOT.'./include/game/song.inc.php';
	song_echo_place($song,$chicken ? 10 : 60,$npc['pls']);
	ai93_chat($npc['name'],$chicken ? 'chicken_song' : 'idol_sing',array('[song]'=>$song,'[pls]'=>$plsinfo[$npc['pls']]));
	addnews($now,'song',$npc['name'],$plsinfo[$npc['pls']],$song,$npc['nick'] ?? 0);
	return true;
}

// 命中结算与落空分支共用；只改参战数组，交给rev_combat原有的双方保存流程。
function ai93_hitrun(&$pa,&$pd)
{
	global $log,$plsinfo;
	if(($pa['type'] != 93 && $pd['type'] != 93) || !ai93_enabled()) return false;
	if($pa['hp'] <= 0 || $pd['hp'] <= 0 || $pa['pls'] != $pd['pls']) return false;
	if(ai93_branch($pa) === 4) { $npc = &$pa; $other = &$pd; }
	elseif(ai93_branch($pd) === 4) { $npc = &$pd; $other = &$pa; }
	else return false;
	ai93_group_alert($pd);
	$old = intval($npc['pls']);
	$safe = array_diff(ai93_safe_plslist(),array($old));
	if(!$safe) return false;
	$npc['pls'] = $safe[array_rand($safe)];
	$pa['action'] = $pd['action'] = ''; $pa['bid'] = $pd['bid'] = 0;
	unset($pa['coveratk_flag'],$pd['coveratk_flag']);
	$inf = ai93_element($npc);
	$placed = in_array($inf,array('i','u','e','p','w')) && ai93_drop($old,'摔碎的抑制剂瓶','TO',45,1,$npc['pid'],$inf);
	$nm = htmlspecialchars($npc['name'],ENT_QUOTES,'UTF-8');
	$log .= '<span class="lime">'.$nm.'怪叫道：「ドーモ！药效上头了，拜拜了您嘞！」说罢一个后空翻'.($placed ? '摔碎了药瓶，' : '，').'瞬间溜得没影了！</span><br>';
	ai93_chat($npc['name'],'b4_hitrun',array('[plyr]'=>$other['name'],'[pls]'=>$plsinfo[$old],'[newpls]'=>$plsinfo[$npc['pls']]));
	return true;
}
