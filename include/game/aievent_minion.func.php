<?php
if(!defined('IN_GAME')) exit('Access Denied');

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
	if($state['trash_cnt'] >= 30) return false;
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
	global $db,$tablepre,$now,$starttime,$plsinfo;
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
		for($sub=$branch*5;$sub<$branch*5+5;$sub++) addnpc(93,$sub,3);
		$state['unlocked'][$branch] = 1;
		ai93_chat($entrances[$branch][0],$entrances[$branch][1]);
		$batch = true;
	}
	if($state['pending']) {
		$state['pending'] = array();
		save_gameinfo();
	}
	if(!$batch && $now-$starttime > 300 && $state['drip_cnt'] < 8 && rand(0,99) < 8) {
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
	$result = $db->query("SELECT pid,name,type,clbpara FROM {$tablepre}players WHERE type=93 AND hp>0");
	$ids = array();
	while($npc = $db->fetch_array($result)) if(ai93_branch($npc) >= 0) $ids[] = $npc['pid'];
	shuffle($ids);
	foreach(array_slice($ids,0,rand(1,2)) as $id) {
		$npc = fetch_playerdata_by_pid($id);
		if($npc && $npc['hp'] > 0) ai93_act($npc,$data);
	}
}

function ai93_act(&$npc,$current)
{
	global $db,$tablepre,$now,$corpseprotect,$weather,$plsinfo,$wthinfo,$exdmginf;
	if(!ai93_enabled() || $npc['hp'] <= 0) return;
	$branch = ai93_branch($npc);
	$safe = ai93_safe_plslist();
	if($branch < 0 || !$safe) return;
	$npc['clbpara'] = get_clbpara($npc['clbpara']);
	if(!in_array($npc['pls'],$safe)) $npc['pls'] = $safe[array_rand($safe)];
	$npls = intval($npc['pls']);
	$state = &ai93_state();
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
		if(($recent && $state['wth_cnt'] < 3) || (!$recent && $state['wth_cnt'] < 2 && rand(0,99) < 5)) {
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
			if(rand(0,99) < 80) ai93_drop($npc['pls'],$trash['idol'][array_rand($trash['idol'])]);
			else ai93_drop($npc['pls'],'闪耀星辰应援饮料','HB',150,2);
		}
		// 余下20%是演出间歇，仍会携带本次行动的酬劳。
	} elseif($branch === 2) {
		if($roll < 50) {
			if($state['trash_cnt'] < 30) {
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
	$npc['money'] += 200;
	$npc['clbpara']['ai_acts'] = ($npc['clbpara']['ai_acts'] ?? 0)+1;
	// 养肥后的固定五系药战利品随行动增加，拾荒只用itm1—itm4。
	if(preg_match('/^C[iuepw]$/',$npc['itmk5'])) $npc['itms5']++;
	player_save($npc);
	if(!empty($state['caller_pid']) && rand(0,99) < 25) {
		$result = $db->query("SELECT name FROM {$tablepre}players WHERE pid=".intval($state['caller_pid'])." AND type=0");
		$caller = $db->fetch_array($result);
		if($caller) ai93_chat($npc['name'],'blame_caller',array('[caller]'=>$caller['name'],'[pls]'=>$plsinfo[$npc['pls']]));
	}
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
	$old = intval($npc['pls']);
	$safe = array_diff(ai93_safe_plslist(),array($old));
	if(!$safe) return false;
	$npc['pls'] = $safe[array_rand($safe)];
	$pa['action'] = $pd['action'] = ''; $pa['bid'] = $pd['bid'] = 0;
	unset($pa['coveratk_flag'],$pd['coveratk_flag']);
	$inf = substr($npc['wepsk'],0,1);
	// 本次打击可能刚好耗尽武器，瓶内药剂仍按该被检体原有元素留下。
	if(!in_array($inf,array('i','u','e','p','w'))) {
		$info = get_addnpcinfo();
		for($sub=20;$sub<25;$sub++) {
			if($info[93]['sub'][$sub]['name'] === $npc['name']) {
				$inf = substr($info[93]['sub'][$sub]['wepsk'],0,1);
				break;
			}
		}
	}
	$placed = in_array($inf,array('i','u','e','p','w')) && ai93_drop($old,'摔碎的抑制剂瓶','TO',45,1,$npc['pid'],$inf);
	$nm = htmlspecialchars($npc['name'],ENT_QUOTES,'UTF-8');
	$log .= '<span class="lime">'.$nm.'怪叫道：「ドーモ！药效上头了，拜拜了您嘞！」说罢一个后空翻'.($placed ? '摔碎了药瓶，' : '，').'瞬间溜得没影了！</span><br>';
	ai93_chat($npc['name'],'b4_hitrun',array('[plyr]'=>$other['name'],'[pls]'=>$plsinfo[$old],'[newpls]'=>$plsinfo[$npc['pls']]));
	return true;
}
