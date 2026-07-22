<?php

define('CURSCRIPT', 'game');

require './include/common.inc.php';
//$t_s=getmicrotime();
//require_once GAME_ROOT.'./include/JSON.php';
require GAME_ROOT.'./include/game.func.php';

//判断是否进入游戏
if(!$cuser||!$cpass) { gexit($_ERROR['no_login'],__file__,__line__); }

// RuleSet钩子：在读取玩家行前取得整条指令请求所需的互斥资源。
// RuleSet hook: acquire request-wide mutual exclusion before reading the player row.
if(function_exists('ruleset_command_request_begin_hook') && ruleset_command_request_begin_hook() === false) {
	gexit('RAID状态正忙，请稍后重试。',__file__,__line__);
}

//$result = $db->query("SELECT * FROM {$tablepre}players WHERE name = '$cuser' AND type = 0");
$pdata = fetch_playerdata_by_name($cuser);

if(!$pdata) { header("Location: valid.php");exit(); }

//$pdata = $db->fetch_array($result);

//判断是否密码错误
if($pdata['pass'] != $cpass) {
	$tr = $db->query("SELECT `password` FROM {$gtablepre}users WHERE username='$cuser'");
	$tp = $db->fetch_array($tr);
	$password = $tp['password'];
	if($password == $cpass) {
		$db->query("UPDATE {$tablepre}players SET pass='$password' WHERE name='$cuser'");
	} else {
		gexit($_ERROR['wrong_pw'],__file__,__line__);
	}
}

//判断游戏状态和玩家状态，如果符合条件则忽略指令
if($gamestate == 0) {
	$gamedata['url'] = 'end.php';
	ob_clean();
	$jgamedata = compatible_json_encode($gamedata);
	echo $jgamedata;
	ob_end_flush();
	exit();
}

//初始化各变量
$pdata['clbpara'] = get_clbpara($pdata['clbpara']);
extract($pdata,EXTR_REFS);
$log = $cmd = $main = '';
$gamedata = array();
init_playerdata();

//读取玩家互动信息
$result = $db->query("SELECT lid,time,log FROM {$tablepre}log WHERE toid = '$pid' AND prcsd = 0 ORDER BY time,lid");
$llist = '';
while($logtemp = $db->fetch_array($result)){
	$log .= date("H:i:s",$logtemp['time']).'，'.$logtemp['log'].'<br />';
	$llist .= $logtemp['lid'].',';
}
if(!empty($llist)){
	$llist = '('.substr($llist,0,-1).')';
	$db->query("UPDATE {$tablepre}log SET prcsd=1 WHERE toid = '$pid' AND lid IN $llist");
}
//var_dump($_POST);
if($hp > 0){
	//显示枪声信息
	if(($now <= $noisetime+$noiselimit)&&$noisemode&&($noiseid!=$pid)&&($noiseid2!=$pid)) {
		if(($now-$noisetime) < 60) {
			$noisesec = $now - $noisetime;
			$log .= "<span class=\"yellow\">{$noisesec}秒前，{$plsinfo[$noisepls]}传来了{$noiseinfo[$noisemode]}。</span><br>";
		} else {
			$noisemin = floor(($now-$noisetime)/60);
			$log .= "<span class=\"yellow\">{$noisemin}分钟前，{$plsinfo[$noisepls]}传来了{$noiseinfo[$noisemode]}。</span><br>";
		}
	}

	if ($club==0 && !isset($clubavl))
	{
		include_once GAME_ROOT.'./include/game/clubslct.func.php';
		getclub($name,$c1,$c2,$c3);
		$clubavl[0]=0; $clubavl[1]=$c1; $clubavl[2]=$c2; $clubavl[3]=$c3;
	}

	//PORT
	//判断背包内道具是否超限
	if(strpos($arbsk,'^')!==false && $arbs && $arbe){
		global $itmnumlimit;
		$itmnumlimit = $arbe>=$arbs ? $arbs : $arbe;
		include_once GAME_ROOT.'./include/game/itembag.func.php';
		overnumlimit();
	}

	//判断冷却时间是否过去
	if($coldtimeon){
		$cdover = $cdsec*1000 + $cdmsec + $cdtime;
		$nowmtime = floor(getmicrotime()*1000);
		$rmcdtime = $nowmtime >= $cdover ? 0 : $cdover - $nowmtime;
	}

	//如果身上存在时效性技能，检查技能是否超时
	if($hp > 0 && !empty($clbpara['lasttimes'])) check_skilllasttimes($pdata);
	//应用眩晕状态效果
	if($hp > 0 && !empty($clbpara['skill']) && in_array('inf_dizzy',$clbpara['skill']))
	{
		$dizzy_times = (($clbpara['starttimes']['inf_dizzy'] + $clbpara['lasttimes']['inf_dizzy']) - $now)*1000;
		$log .= '<span class="yellow">你现在处于眩晕状态，什么都做不了！</span><br>眩晕状态持续时间还剩：<span id="timer" class="yellow">'.$dizzy_times.'</span>秒<br><script type="text/javascript">demiSecTimerStarter('.$dizzy_times.');</script>';
		goto cd_flag;
	}

	// 未定义RuleSet动作钩子时，保持原有追击优先级不变。
	// Preserve the original chase priority when the active ruleset has no action hook.
	if(!function_exists('ruleset_command_prepare_hook') && !empty($action) && in_array($action,Array('chase','pchase','dfight','cover')) && $mode !== 'revcombat')
	{
		$command = $action;
		goto chase_flag;
	}

	$ruleset_command_blocked = false;
	$ruleset_command_value = isset($command) ? $command : '';
	if(!empty($action) && in_array($action,Array('chase','pchase','dfight','cover')) && $mode !== 'revcombat') $ruleset_command_value = $action;

	//执行动作前检查是否有无法跳过且未阅览过的对话框
	if(!empty($clbpara['noskip_dialogue']) && strpos($command,'end_dialogue')===false && strpos($command,'dialogue_choice')!==0)
	{
		if(!empty($clbpara['dialogue'])) {
			$opendialog = 'dialogue';
			$dialogue_id = $clbpara['dialogue'];
		} else {
			$opendialog = $clbpara['noskip_dialogue'];
		}
	}elseif($coldtimeon && $rmcdtime > 0 && (strpos($command,'move')===0 || strpos($command,'search')===0 || (strpos($command,'itm')===0)&&($command != 'itemget') || strpos($sp_cmd,'sp_weapon')===0 || strpos($command,'song')===0)){
		$log .= '<span class="yellow">冷却时间尚未结束！</span><br>';
		cd_flag:
		$mode = 'command';
	}else{
		// RuleSet钩子：只在对话与冷却检查通过后结算动作，追击也不会绕过。
		// RuleSet hook: settle actions only after dialog/cooldown checks; chase actions cannot bypass it.
		if(function_exists('ruleset_command_prepare_hook')) {
			$ruleset_command_blocked = ruleset_command_prepare_hook($pdata, $ruleset_command_value) === false;
		}
		if($ruleset_command_blocked) {
			$mode = 'command';
			goto ruleset_command_finished;
		}

		//执行动作前，身上存在追击标记时，直接进入追击判定。
		// Enter chase resolution directly when a chase marker exists before the action.
		if(!empty($action) && in_array($action,Array('chase','pchase','dfight','cover')) && $mode !== 'revcombat')
		{
			$command = $action;
			goto chase_flag;
		}

		//进入指令判断
		if(!empty($itemindex))
		{
			$opendialog = 'itemmix_tips';
			$mode = 'command';
			$command = 'itemmain';
			$itemcmd = 'itemmix';
		}
		if(!empty($action))
		{
			if($action == 'tpmove')
			{
				$mode = 'command';
				$command = 'search';
			}
			if($action != 'chase' && $action != 'dfight' && $mode !== 'combat' && $mode !== 'revcombat' && $mode !== 'corpse' && $action != 'pacorpse' && $mode !== 'senditem'){
				$action = ''; $bid = 0;
			}
		}
		if($command == 'menu') {
			$mode = 'command';
			//$action = '';
		} elseif($mode == 'command') {
			if(!empty($itms0) && !in_array($command, Array('itemget','itm0','dropitm0','split_itm'))){//手里拿着道具的时候否决一切其他命令，防止道具被覆盖！
				$log .= "你的双手都已经抓满了东西。为了完成所想，你集中意念召唤幻肢……<br>什么都没有发生，除了你的脑壳痛了起来。<br><br>";
				$mode = 'command';
			}	elseif($command == 'move') {
				include_once GAME_ROOT.'./include/game/search.func.php';
				move($moveto);
				if($coldtimeon){$cmdcdtime=$movecoldtime;}
			} elseif($command == 'search') {
				include_once GAME_ROOT.'./include/game/search.func.php';
				search();
				if($coldtimeon){$cmdcdtime=$searchcoldtime;}
			} elseif(strpos($command,'itm') === 0) {
				include_once GAME_ROOT.'./include/game/item.func.php';
				$item = substr($command,3);
				itemuse($item);
				if($coldtimeon){$cmdcdtime=$itemusecoldtime;}
			} elseif(strpos($command,'rest') === 0) {
				if($command=='rest3' && !in_array($pls,$hospitals)){
					$log .= '<span class="yellow">你所在的位置并非医院，不能静养！</span><br>';
				}else{
					$state = substr($command,4,1);
					$mode = 'rest';
				}
			} elseif($command == 'fishing') {
				include_once GAME_ROOT.'./include/game/fishing.func.php';
				start_fishing($pdata);
				if($mode == 'fishing') {
					// 如果成功开始钓鱼，同时应用休息效果
					include_once GAME_ROOT.'./include/state.func.php';
					rest('rest', $pdata);
				}
			} elseif($command == 'itemmain') {
				if(($club == 20 && $itemcmd == 'itemmix') || ($club != 20 && ($itemcmd == 'elementmix' || $itemcmd == 'elementbag'))){
					$log .= "你的手突然掐住了你的头左右摇摆！<br><span class='yellow'>“你还想要干什么，啊？你还想要干什么！！”</span><br>看来你的手和脑子之间起了一点小摩擦。<br><br>";
					$mode = 'command';
				} else {
					if($itemcmd == 'itemmix' || $itemcmd == 'elementmix'){
						$main = 'itemmix_tips';
					}
					if($club == 20){
						include_once GAME_ROOT.'./include/game/elementmix.calc.php';
						$emax = emix_calc_maxenum();
						if($clbstatusa)
						{
							$log .= "你习惯性摸了摸腰间，但那里已经没有元素口袋了……呼，至少元素们还没有离开你。<br>";
							$main = ''; $itemcmd = 'command';
						}
					}
					$mode = $itemcmd;
				}
			} elseif($command == 'song') {
				$sname=trim(trim($art,'【'),'】');
				include_once GAME_ROOT.'./include/game/song.inc.php';
				//$log.=$sname;
				sing($sname);
			}elseif($command == 'special') {
				if($sp_cmd == 'sp_word'){
					include_once GAME_ROOT.'./include/game/special.func.php';
					getword();
					$mode = $sp_cmd;
				}elseif($sp_cmd == 'sp_adtsk'){
					include_once GAME_ROOT.'./include/game/special.func.php';
					adtsk();
					$mode = 'command';
				}elseif($sp_cmd == 'sp_trapadtsk'){
					$position = 0;
					if ($club==7)
					{
						foreach(Array(1,2,3,4,5,6) as $imn)
							if(strpos(${'itmk'.$imn},'B')===0 && ${'itme'.$imn} > 0 ){
								$position = $imn;
								break;
							}
						if (!$position)
						{
							$log .= '<span class="red">你没有电池，无法改造陷阱！</span><br />';
							$mode = 'command';
						}
					}
					else  if ($club==8)
					{
						foreach(Array(1,2,3,4,5,6) as $imn)
							if(${'itm'.$imn} == '毒药' && ${'itmk'.$imn} == 'Y' && ${'itme'.$imn} > 0 ){
								$position = $imn;
								break;
							}
						if (!$position)
						{
							$log .= '<span class="red">你没有毒药，无法改造陷阱！</span><br />';
							$mode = 'command';
						}
					}
					else
					{
						$log .= '<span class="red">你不懂得如何改造陷阱！</span><br />';
						$mode = 'command';
					}
					if ($position)
					{
						$position = 0;
						foreach(Array(1,2,3,4,5,6) as $imn)
							if(strpos(${'itmk'.$imn},'T')===0 && ${'itme'.$imn} > 0 ){
								$position = $imn;
								break;
							}
						if (!$position)
						{
							$log .= '<span class="red">你的背包中没有陷阱，无法改造！</span><br />';
							$mode = 'command';
						}
						else  $mode = 'sp_trapadtsk';
					}
				}elseif($sp_cmd == 'sp_trapadtskselected'){
					if (!isset($choice) || $choice=='menu')
					{
						$mode='command';
					}
					else
					{
						$choice=(int)$choice;
						if ($choice<1 || $choice>6)
							$log.='<span class="red">无此物品。</span><br />';
						else
						{
							include_once GAME_ROOT.'./include/game/special.func.php';
							trap_adtsk($choice);
						}
						$mode='command';
					}
                //码语行人提取
                } elseif ($sp_cmd == 'sp_extract_trait') {
                    $position = 0;
                    if ($club == 21) {
                        foreach (array(1, 2, 3, 4, 5, 6) as $imn)
                            //遍历所有武器装备
                            if (strpos(${'itmk' . $imn}, 'D') === 0 || strpos(${'itmk' . $imn}, 'W') === 0) {
                                $position = $imn;
                                break;
                            }
                        if (!$position) {
                            $log .= '<span class="red">你没有武器或者装备，无法提取要素！</span><br />';
                            $mode = 'command';
                        } else  $mode = 'sp_extract_trait';
                    } else {
                        $log .= '<span class="red">你不懂得如何提取要素！</span><br />';
                        $mode = 'command';
                    }
                } elseif ($sp_cmd == 'sp_extract_trait_selected') {
                    if (!isset($choice) || $choice == 'menu') {
                        $mode = 'command';
                    } else {
                        $choice_position = (int)(substr($choice, -1));
                        //$choice = (int)$choice;
                        if ($choice_position < 1 || $choice_position > 6)
                            $log .= '<span class="red">无此物品。</span><br />';
                        else {
                            include_once GAME_ROOT . './include/game/club21.func.php';
                            item_extract_trait($choice, $choice_position);
                        }
                        $mode = 'command';
                    }
                //码语行人插入
                } elseif ($sp_cmd == 'sp_add_trait') {
                    $position = 0;
                    if ($club == 21) {
                        foreach (array(1, 2, 3, 4, 5, 6) as $imn)
                            //遍历所有武器装备
                            if (strpos(${'itmk' . $imn}, '🥚') === 0) {
                                $position = $imn;
                                break;
                            }
                        if (!$position) {
                            $log .= '<span class="red">你没有代码片段，无法插入代码片段！</span><br />';
                            $mode = 'command';
                        } else  $mode = 'sp_add_trait';
                    } else {
                        $log .= '<span class="red">你不懂得如何插入代码片段！</span><br />';
                        $mode = 'command';
                    }
                } elseif ($sp_cmd == 'sp_add_trait_selected') {
                    if (!isset($choice) || $choice == 'menu') {
                        $mode = 'command';
                    } else {
                        $choice = (int)($choice);
                        $choice2 = (int)($choice2);
                        if ($choice < 1 || $choice > 6 || $choice2 < 1 || $choice2 > 6)
                            $log .= '<span class="red">无此物品。</span><br />';
                        elseif ($choice == $choice2)
                            $log .= '<span class="red">你选择了相同的代码片段。</span><br />';
                        else {
                            include_once GAME_ROOT . './include/game/club21.func.php';
                            item_add_trait($choice, $choice2);
                        }
                        $mode = 'command';
                    }
                //码语行人吃饭
                } elseif ($sp_cmd == 'sp_consume_trait') {
                    $position = 0;
                    if ($club == 21) {
                        foreach (array(1, 2, 3, 4, 5, 6) as $imn)
                            //遍历所有道具
                            if (strpos(${'itmk' . $imn}, '🥚') === 0) {
                                $position = $imn;
                                break;
                            }
                        if (!$position) {
                            $log .= '<span class="red">你没有代码片段，无法消耗代码片段！</span><br />';
                            $mode = 'command';
                        } else  $mode = 'sp_consume_trait';
                    } else {
                        $log .= '<span class="red">你不懂得如何消耗代码片段！</span><br />';
                        $mode = 'command';
                    }
                } elseif ($sp_cmd == 'sp_consume_trait_selected') {
                    if (!isset($choice) || $choice == 'menu') {
                        $mode = 'command';
                    } else {
						$choice = (int)($choice);
                        if ($choice < 1 || $choice > 6 )
                            $log .= '<span class="red">无此物品。</span><br />';
                        else {
                            include_once GAME_ROOT . './include/game/club21.func.php';
                            consume_trait($choice);
                        }
                        $mode = 'command';
                    }
				}elseif($sp_cmd == 'sp_pbomb'){
					$mode = 'sp_pbomb';
				}elseif($sp_cmd == 'sp_weapon'){
					include_once GAME_ROOT.'./include/game/special.func.php';
					weaponswap();
					$mode = 'command';
					if($coldtimeon){$cmdcdtime=$weaponswapcoldtime;}
				}elseif($sp_cmd == 'oneonone'){
					$mode='oneonone';
				}elseif($sp_cmd == 'sp_skpts'){
					include_once GAME_ROOT.'./include/game/clubskills.func.php';
					calcskills($skarr);
					$p12[1]=1; $p12[2]=2;
					$mode='sp_skpts';
				//妙手技能
				}elseif($sp_cmd == 'sp_pickpocket_selected'){
					if (!isset($choice)) {
						$mode = 'command';
					} else {
						$choice = (int)($choice);
						include_once GAME_ROOT . './include/game/revclubskills_extra.func.php';
						skill_tl_pickpocket_act($choice);
 					}
					$mode = 'command';
				}elseif($sp_cmd == 'sp_fireseed_deploy' && $club == 22){
					include_once GAME_ROOT.'./include/game/club22.func.php';
					if(isset($fireseed_id) && isset($deploy_mode)){
						$deploy_pls = isset($deploy_pls) ? intval($deploy_pls) : $pls;
						$log .= "<span class='yellow'>DEBUG: 部署位置 $deploy_pls</span><br>";
						FireseedDeploy($fireseed_id, $deploy_mode, $deploy_pls);
					}else{
						$log .= '<span class="red">请选择要部署的种火和部署模式！</span><br>';
					}
					$mode = 'command';
				}elseif($sp_cmd == 'sp_fireseed_getitem' && $club == 22){
					include_once GAME_ROOT.'./include/game/club22.func.php';
					if(isset($fireseed_item_id) && isset($item_id)){
						// 从种火物品池中获取物品
						if(isset($clbpara['fireseed'][$fireseed_item_id]['items'][$item_id])){
							$item = $clbpara['fireseed'][$fireseed_item_id]['items'][$item_id];

							// 检查itm0是否为空
							if(empty($itm0)){
								// 将物品放入itm0（发现物品栏位）
								$itm0 = $item['itm'];
								$itmk0 = $item['itmk'];
								$itme0 = $item['itme'];
								$itms0 = $item['itms'];
								$itmsk0 = $item['itmsk'];
								$itmpara0 = isset($item['itmpara']) ? $item['itmpara'] : '';

								// 从种火物品池中移除物品
								unset($clbpara['fireseed'][$fireseed_item_id]['items'][$item_id]);

								$log .= '<span class="lime">你从种火「'.$clbpara['fireseed'][$fireseed_item_id]['name'].'」处取回了探索到的物品！</span><br>';

								// 调用itemfind函数触发物品发现流程
								include_once GAME_ROOT.'./include/game/itemmain.func.php';
								itemfind($pdata);
							}else{
								// itm0已被占用，将物品放入地图并添加到玩家视野
								$db->query("INSERT INTO {$tablepre}mapitem (itm, itmk, itme, itms, itmsk, itmpara, pls) VALUES ('{$item['itm']}', '{$item['itmk']}', '{$item['itme']}', '{$item['itms']}', '{$item['itmsk']}', '".addslashes(isset($item['itmpara']) ? $item['itmpara'] : '')."', '$pls')");
								$new_item_id = $db->insert_id();

								// 从种火物品池中移除物品
								unset($clbpara['fireseed'][$fireseed_item_id]['items'][$item_id]);

								// 将物品添加到玩家视野
								include_once GAME_ROOT.'./include/game.func.php';
								check_add_searchmemory($new_item_id, 'itm', $item['itm'], $pdata);

								// 保存更新后的clbpara数据到数据库
								$encoded_clbpara = json_encode($clbpara, JSON_UNESCAPED_UNICODE);
								$db->query("UPDATE {$tablepre}players SET clbpara='$encoded_clbpara' WHERE pid='$pid'");

								$log .= '<span class="lime">你从种火「'.$clbpara['fireseed'][$fireseed_item_id]['name'].'」处取回了探索到的物品「'.$item['itm'].'」！</span><br>';
								$log .= '<span class="yellow">由于你的双手已经拿着其他物品，取回的物品出现在了你的视野中。</span><br>';
								$mode = 'command';
							}
						}else{
							$log .= '<span class="red">指定的物品不存在！</span><br>';
							$mode = 'command';
						}
					}else{
						$log .= '<span class="red">请选择要获取物品的种火和物品！</span><br>';
						$mode = 'command';
					}
				}elseif($sp_cmd == 'sp_fireseed_enhance' && $club == 22){
					include_once GAME_ROOT.'./include/game/club22.func.php';
					if(isset($enhance_fireseed_id) && isset($enhance_item)){
						$enhance_result = FireseedEnhance($enhance_fireseed_id, $enhance_item);
						if($enhance_result) {
							// 添加侧边栏刷新标记，确保侧边栏能够实时更新
							$log .= "<span id='HsUipfcGhU'></span>";
							$log .= '<span class="lime">种火强化成功！</span><br>';
							$mode = 'command';
						}
					}else{
						$log .= '<span class="red">请选择要强化的种火和焰火物品！</span><br>';
					}
					$mode = 'command';
				}elseif($sp_cmd == 'sp_save_fireseed_select' && $club == 22){
					// 保存种火选择状态到clbpara
					if(isset($select_type) && isset($fireseed_id)){
						if(!isset($clbpara['fireseed_ui_state'])) {
							$clbpara['fireseed_ui_state'] = array();
						}
						$clbpara['fireseed_ui_state'][$select_type] = $fireseed_id;

						// 保存到数据库
						$encoded_clbpara = json_encode($clbpara, JSON_UNESCAPED_UNICODE);
						$db->query("UPDATE {$tablepre}players SET clbpara='$encoded_clbpara' WHERE pid='$pid'");

						$log .= "<!-- 种火选择状态已保存: {$select_type} = {$fireseed_id} -->";
					}
					$mode = 'command';
				}else{
					$mode = $sp_cmd;
				}
			} elseif($command == 'team') {
				include_once GAME_ROOT.'./include/game/team.func.php';
				if($teamcmd == 'teamquit') {
					teamquit();
				} else{
					teamcheck();
				}
			} elseif(strpos($command,'consle') === 0) {
				if(isset($clbpara['console']))
				{
					$cls_cmd = substr($command,7);
					include_once GAME_ROOT.'./include/game/console.func.php';
					if($cls_cmd == 'wthchange'){console_wthchange($cwth);}
					elseif($cls_cmd == 'dbutton'){console_dbutton();}
					elseif($cls_cmd == 'radar'){
						include_once GAME_ROOT.'./include/game/item2.func.php';
						newradar(2);
					}elseif($cls_cmd == 'search'){
						$cls_cmd_kind = substr($csc,7);
						console_searching($cls_cmd_kind,$csnm,$cstype);
					}elseif(strpos($cls_cmd,'areactrl')===0){
						$cls_cmd_kind = substr($cls_cmd,8);
						console_areacontrol($cls_cmd_kind);
					}
				}
				else{
					$mode='command';
				}
			} elseif(strpos($command,'dialogue_choice') === 0) {
				// 处理对话选择
				$choice_parts = explode(' ', $command);
				if(count($choice_parts) >= 3) {
					$dialogue_id = $choice_parts[1];
					$choice_index = $choice_parts[2];

					// 检查对话 ID 和选择索引是否有效
					if(isset($dialogue_branch[$dialogue_id]) && isset($dialogue_branch[$dialogue_id][$choice_index])) {
						// 记录玩家的选择
						$clbpara['dialogue_choice'] = array(
							'dialogue_id' => $dialogue_id,
							'choice_index' => $choice_index,
							'choice_text' => $dialogue_branch[$dialogue_id][$choice_index]
						);

						// 输出选择的结果
						$log .= "你选择了：<span class=\"yellow\">{$dialogue_branch[$dialogue_id][$choice_index]}</span><br>";

						// 添加非常明显的错误信息
						//$log .= "<div style='background-color: red; color: white; padding: 10px; margin: 10px; border: 2px solid black;'>对话选择调试信息: 对话 ID = {$dialogue_id}, 选择索引 = {$choice_index}</div>";

						// 如果有对应的选择结果日志，显示它
						$choice_log_key = $dialogue_id.'_choice_'.$choice_index;

						// 调试信息，显示对话日志的键值
						//$log .= "<!-- DEBUG: 对话 ID: {$dialogue_id}, 选择索引: {$choice_index}, 选择日志键: {$choice_log_key} -->";

						// 显示所有可用的对话日志键
						//$log .= "<!-- DEBUG: 可用的对话日志键: ";
						//foreach($dialogue_log as $key => $value) {
						//	$log .= "{$key}, ";
						//}
						//$log .= " -->";

						if(isset($dialogue_log[$choice_log_key]) && !empty($dialogue_log[$choice_log_key])) {
							//$log .= "<!-- DEBUG: 使用选择特定日志 -->";
							$log .= $dialogue_log[$choice_log_key];
						} elseif(isset($dialogue_log[$dialogue_id]) && !empty($dialogue_log[$dialogue_id])) {
							// 如果没有特定选择的日志，显示通用日志
							//$log .= "<!-- DEBUG: 使用通用日志 -->";
							$log .= $dialogue_log[$dialogue_id];
						} else {
							$log .= "<!-- DEBUG: 没有找到对应的对话日志 -->";
						}

						// 清除对话状态
						unset($clbpara['dialogue']);
						unset($clbpara['noskip_dialogue']);

						// 确保对话框不会重新打开
						$dialogue_id = null;
						$opendialog = null;

						// 设置命令模式为命令模式，确保页面能够正确显示选择结果
						$mode = 'command';
					} else {
						$log .= "<span class=\"red\">无效的对话选择！</span><br>";
					}
				} else {
					$log .= "<span class=\"red\">对话选择格式错误！</span><br>";
				}
			} elseif(strpos($command,'end_dialogue') === 0) {
				//$log.="【DEBUG】关闭了对话框。";
				if(!empty($dialogue_log[$clbpara['dialogue']])) $log.= $dialogue_log[$clbpara['dialogue']];
				unset($clbpara['dialogue']); unset($clbpara['noskip_dialogue']);
			} elseif ($command == 'choose_fish') {
				// 处理鱼篓子物品选择
				if (isset($clbpara['fish_basket'])) {
					include_once GAME_ROOT.'./include/game/item.nouveau_booster1.php';
					item_nouveau_booster1($clbpara['fish_basket']['position'], $pdata);
				} else {
					$log .= '出现了错误，请重新使用鱼篓子。<br>';
					$mode = 'command';
				}
			} elseif (strpos($command,'memory')===0) {
				$smn = substr($command,6);
				if(!empty($clbpara['smeo'] && isset($clbpara['smeo'][$smn]))){
					$iid = $clbpara['smeo'][$smn][0]; $itp = $clbpara['smeo'][$smn][1];
					lost_searchmemory($smn,$pdata);
					if($itp == 'itm'){
						include_once GAME_ROOT.'./include/game/search.func.php';
						focus_item($pdata,$iid);
					}else{
						$action = 'focus'; $bid = $iid; $command = 'focus';
						goto chase_flag;
					}
				}
			}
		} elseif($mode == 'item') {
			include_once GAME_ROOT.'./include/game/item2.func.php';
			$item = substr($command,3);
			use_func_item($usemode,$item);
		} elseif($mode == 'itemmain') {
			include_once GAME_ROOT.'./include/game/itemmain.func.php';
			if($command == 'itemget') {
				itemget();
			} elseif($command == 'itemadd') {
				itemadd();
			} elseif($command == 'itemmerge') {
				if($merge2 == 'n'){itemadd();}
				else{itemmerge($merge1,$merge2);}
			} elseif($command == 'itemmove') {
				itemmove($from,$to);
			} elseif(strpos($command,'split_itm') === 0) {
				$split_item = substr($command,9);
				include_once GAME_ROOT . './include/game/elementmix.func.php';
				split_item_to_elements($split_item);
			} elseif(strpos($command,'drop') === 0) {
				$drop_item = substr($command,4);
				itemdrop($drop_item);
			} elseif(strpos($command,'off') === 0) {
				$off_item = substr($command,3);
				itemoff($off_item);
				//itemadd();
			} elseif(strpos($command,'swap') === 0) {
				$swap_item = substr($command,4);
				itemdrop($swap_item);
				itemadd();
			} elseif($command == 'itemmix') {
				if (isset($itemselect) && $itemselect==999)
					$mode='command';
				else
				{
					$mixlist = array();
					if (!isset($mixmask))
					{
						for($i=1;$i<=6;$i++)
							if(isset(${'mitm'.$i}) && ${'mitm'.$i} == $i)
								$mixlist[] = $i;
					}
					else
					{
						for($i=1;$i<=6;$i++)
							if ($mixmask&(1<<($i-1)))
								$mixlist[] = $i;
					}
					include_once GAME_ROOT.'./include/game/itemmix.func.php';
					if (isset($itemselect))
						itemmix_rev($mixlist,$itemselect);
					else  itemmix_rev($mixlist);
				}
			} elseif($command == 'elementmix') {
				include_once GAME_ROOT.'./include/game/elementmix.func.php';
				$itmemax = $change_emax ? (int)$emixitmemax : 100;
				$itmer = $change_emr ? (int)$emixitmer : 55;
				elements_mix_prepare($emixlist,$emixnums,$itmemax,$itmer);
				$mode='command';
			} elseif($command == 'itemencase') {
				if(strpos($arbsk,'^')!==false && $arbs && $arbe){
					$ilist = array();
					for($i=1;$i<=6;$i++){
						if(isset(${'mitm'.$i}) && ${'mitm'.$i} == $i){
							$ilist[] = $i;
						}
					}
					item_encase($ilist);
				}else{
					$log.="<span class='red'>你身上没有背包，或是没有将背包装备上！<br>";
				}
			} elseif($command == 'iteminfo') {
				if(strpos($arbsk,'^')!==false && $arbs && $arbe){
					item_info();
				}else{
					$log.="<span class='red'>你身上没有背包，或是没有将背包装备上！<br>";
				}
			} elseif(strpos($command,'usebagitm') !==false) {
				if(strpos($arbsk,'^')!==false && $arbs && $arbe){
					$itemid = substr($command,10);
					item_out($itemid);
				}else{
					$log.="<span class='red'>你身上没有背包，或是没有将背包装备上！<br>";
				}
			} elseif(strpos($command,'changewep') !==false) {
				include_once GAME_ROOT.'./include/game/itemmain.func.php';
				change_subwep();
				$mode = 'command';
			}
		} elseif($mode == 'quest') {
			include_once GAME_ROOT.'./include/game/quest.func.php';
			if($command == 'quest_accept') {
				$qid = isset($questselect) ? $questselect : '';
				$accepted = quest_accept_offer($qid, $pdata);
				$mode = $accepted ? 'command' : 'quest';
			} elseif($command == 'quest_reject') {
				quest_reject_offer($pdata, true);
				$mode = 'command';
			} elseif($command == 'quest_cancel') {
				quest_reject_offer($pdata, false);
				$mode = 'command';
			} else {
				if(empty($clbpara['quest']['pending'])) $mode = 'command';
			}
		} elseif($mode == 'special') {
			include_once GAME_ROOT.'./include/game/special.func.php';
			if(strpos($command,'pose') === 0) {
				$cpose = substr($command,4,1);
				if(in_array($cpose,$apose)){
					$pose = $cpose;
					$log .= "基础姿态变为<span class=\"yellow\">$poseinfo[$pose]</span>。<br> ";
					$mode = 'command';
				}else{
					$log .= "<span class=\"yellow\">这个姿势太奇怪了！</span><br> ";
					$mode = 'command';
				}
			} elseif(strpos($command,'tac') === 0) {
				$ctac = substr($command,3,1);
				if(in_array($ctac,$atac)){
					$tactic = $ctac;
					$log .= "应战策略变为<span class=\"yellow\">$tacinfo[$tactic]</span>。<br> ";
					$mode = 'command';
				}else{
					$log .= "<span class=\"yellow\">这种策略太奇怪了！</span><br> ";
					$mode = 'command';
				}
			} elseif(strpos($command,'hor') === 0) {
				$chor = substr($command,3,1);
				if(isset($horizoninfo[$chor])){
					$horizon = $chor;
					$log .= "视界切换为<span class=\"yellow\">$horizoninfo[$chor]</span>。<br> ";
					# 切换视界后，丢失所有视野
					lost_searchmemory('all',$pdata);
					# 向页面发送刷新标记
					$log .= "<span id='HsUipfcGhU'></span>";
				}else{
					$log .= "<span class=\"yellow\">这种想法太奇怪了！</span><br> ";
				}
				$mode = 'command';
			} elseif(strpos($command,'inf') === 0) {
				$infpos = substr($command,3,1);
				chginf($infpos);
			} elseif(strpos($command,'chkp') === 0) {
				$itmn = substr($command,4,1);
				chkpoison($itmn);
			} elseif(strpos($command,'shop') === 0) {
				$shop = substr($command,4,2);
				shoplist($shop);
			} elseif(strpos($command,'clubsel') === 0) {
				# 后续更新中，将停用在游戏内直接选择称号的方式
				$clubchosen = (int)str_replace('clubsel','',$command);
				include_once GAME_ROOT.'./include/game/clubslct.func.php';
				$retval=selectclub($clubchosen);
				if ($retval==0)
					$log.="称号选择成功。<br>";
				else if ($retval==1)
					$log.="称号选择失败，称号一旦被选择便无法更改。<br>";
				else if ($retval==2)
					$log.="未选择称号。<br>";
				else  $log.="称号选择非法！<br>";
				$mode = 'command';
			}
		} elseif($mode == 'senditem') {
			include_once GAME_ROOT.'./include/game/battle.func.php';
			senditem();
		} elseif($mode == 'combat') {
			include_once GAME_ROOT.'./include/game/combat.func.php';
			combat(1,$command);
		} elseif($mode == 'revcombat'){
			chase_flag:
			include_once GAME_ROOT.'./include/game/revbattle.func.php';
			if(!isset($message)) $message = '';
			\revbattle\revbattle_prepare($command,$message);
		} elseif($mode == 'rest') {
			include_once GAME_ROOT.'./include/state.func.php';
			// 如果在休息状态下点击钓鱼按钮，则进入钓鱼状态
			if($command == 'fishing') {
				include_once GAME_ROOT.'./include/game/fishing.func.php';
				start_fishing($pdata);
				// 如果成功进入钓鱼状态，保持原有的休息状态
				if($mode == 'fishing') {
					// 不需要调用rest函数，因为钓鱼状态下会自动应用休息效果
				}
			} else {
				rest($command);
			}
		} elseif($mode == 'fishing') {
			include_once GAME_ROOT.'./include/game/fishing.func.php';
			fishing_command($command, $pdata);
			// 在钓鱼的同时也应用休息效果
			include_once GAME_ROOT.'./include/state.func.php';
			rest($command, $pdata);
//		} elseif($mode == 'chgpassword') {
//			include_once GAME_ROOT.'./include/game/special.func.php';
//			chgpassword($oldpswd,$newpswd,$newpswd2);
//		} elseif($mode == 'chgword') {
//			include_once GAME_ROOT.'./include/game/special.func.php';
//			chgword($newmotto,$newlastword,$newkillmsg);
		} elseif($mode == 'corpse') {
			if($command == 'fireseed_recruit' && $club == 22) {
				include_once GAME_ROOT.'./include/game/club22.func.php';
				$result = $db->query("SELECT * FROM {$tablepre}players WHERE pid='$bid' AND hp=0");
				if($db->num_rows($result) > 0) {
					$npc = $db->fetch_array($result);
					FireseedRecruit($npc);
				}
				$mode = 'command';
			} else {
				include_once GAME_ROOT.'./include/game/itemmain.func.php';
				getcorpse($command);
			}
		} elseif($mode == 'team') {
			include_once GAME_ROOT.'./include/game/team.func.php';
			if ($command=="teammake") teammake($nteamID,$nteamPass,(int)$ticon);
			if ($command=="teamjoin") teamjoin($nteamID,$nteamPass);
			if ($command=="teamquit") teamquit($nteamID,$nteamPass);
		} elseif($mode == 'shop') {
			if(in_array($pls,$shops) || !check_skill_unlock('c11_ebuy',$pdata)){
				if($command == 'shop') {
					$mode = 'sp_shop';
				} else {
					include_once GAME_ROOT.'./include/game/itemmain.func.php';
					itembuy($command,$shoptype,$buynum);
				}
			}else{
				$log .= '<span class="yellow">你所在的地区没有商店。</span><br />';
				$mode = 'command';
			}
		} elseif($mode == 'depot') {
			include_once GAME_ROOT.'./include/game/depot.func.php';
			if(in_array($pls,$depots))
			{
				$saveitem_list = depot_getlist($name,$type);
				switch($command)
				{
					case 'sp_depot_save':
						$mode = 'sp_depot_save';
						break;
					case 'sp_depot_load':
						$mode = 'sp_depot_load';
						break;
					case strpos($command,'saveitem')===0:
						$iid = substr($command,9);
						depot_save($iid);
						break;
					case strpos($command,'loaditem')===0:
						$lid = substr($command,9);
						depot_load($lid);
						break;
					default :
						$mode = 'sp_depot';
				}
			}
			else
			{
				$log .= '<span class="yellow">你所在的地区没有安全箱。</span><br />';
				$mode = 'command';
			}
		} elseif($mode == 'deathnote') {
			if($dnname){
				include_once GAME_ROOT.'./include/game/item2.func.php';
				deathnote($name,$item,$dnname,$dndeath,$dngender,$dnicon);
			} else {
				$log .= '嗯，暂时还不想杀人。<br>你合上了■DeathNote■。<br>';
				$mode = 'command';
			}
		}elseif($mode == 'oneonone') {
			if($dnname){
						include_once GAME_ROOT.'./include/game/special.func.php';
						oneonone($dnname,$name);
					} else {
						$log .= '约战取消。<br>';
						$mode = 'command';
					}
		} elseif ($mode == 'sp_skpts') {
			//include_once GAME_ROOT.'./include/game/clubskills.func.php';
			//upgradeclubskills($command);
			//calcskills($skarr);
			//$p12[1]=1; $p12[2]=2;
			$log .= "不存在该指令！<br>";
			$mode = 'command';
		} elseif ($mode == 'revskpts') {
			$sk = substr($command,9);
			if(isset($cskills[$sk])) {
				if(strpos($command,'upgskill_')!==false) {
					if(isset($cskills[$sk]['num_input'])){
						$nums = isset(${$command.'_nums'}) ? (int)${$command.'_nums'} : 1;
						upgclbskills($sk,$nums);
					}else{
						upgclbskills($sk);
					}
				} elseif(strpos($command,'swtskill_')!==false) {
					if(isset(${$sk.'upgpara'}) && isset($cskills[$sk]['choice']) && in_array(${$sk.'upgpara'},$cskills[$sk]['choice'])) {
						switchclbskills($sk,${$sk.'upgpara'});
					}
				} elseif(strpos($command,'actskill_')!==false) {
					# 其他特殊技能按钮
					include_once GAME_ROOT.'./include/game/revclubskills_extra.func.php';
					if($sk == 'c4_roar' || $sk == 'c4_sniper'){skill_c4_unlock($sk);}
					elseif($sk == 'c11_merc'){
						if(isset(${$sk.'mkey'}) && isset(${$sk.'fire'}) && ${$sk.'fire'} == ${$sk.'mkey'}){
							skill_merc_fire($sk,${$sk.'mkey'});
						} elseif(isset(${$sk.'mkey'}) && isset(${$sk.'chase'})){
							skill_merc_chase($sk,${$sk.'mkey'});
						} elseif(isset(${$sk.'mkey'}) && isset(${$sk.${$sk.'mkey'}.'moveto'})){
							skill_merc_move($sk,${$sk.'mkey'},${$sk.${$sk.'mkey'}.'moveto'});
						}
					}
				}
			}
			$mode = 'command';
		} elseif ($mode == 'sp_pbomb') {
			include_once GAME_ROOT.'./include/game/special.func.php';
			if ($command=="YES") press_bomb();
			$mode = 'command';
		} else {
			command_end_flag:
			$mode = 'command';
		}

		if($action == 'pacorpse' && $gamestate < 40){
//			if($state == 1 || $state == 2 || $state ==3){
//				$state = 0;
//			}
			$cid = $bid;
			if($cid){
				$result = $db->query("SELECT * FROM {$tablepre}players WHERE pid='$cid' AND hp=0");
				if($db->num_rows($result)>0){
					$edata = $db->fetch_array($result);
					include_once GAME_ROOT.'./include/game/battle.func.php';
					findcorpse($edata);
				}
			}
		}

		//指令执行完毕，更新冷却时间
		if($coldtimeon && isset($cmdcdtime)){
			$nowmtime = floor(getmicrotime()*1000);
			$cdsec = floor($nowmtime/1000);
			$cdmsec = fmod($nowmtime , 1000);
			$cdtime = $cmdcdtime;
			//$psdata = Array('pid' => $pid, 'cdsec' => $cdsec, 'cdmsec' => $cdmsec, 'cdtime' => $cdtime, 'cmd' => $mode);
			//set_pstate($psdata);
			$rmcdtime = $cmdcdtime;
		}
		//读取背包内道具
		if(strpos($arbsk,'^')!==false && $arbs && $arbe){
			include_once GAME_ROOT.'./include/game/itembag.func.php';
			$itemlist = item_arr();
		}
		$endtime = $now;
		$cmdnum ++;
	}
	ruleset_command_finished:
	//检查是否需要重生成播放器
	$bgm_player = init_bgm();
	if(!empty($bgm_player))
	{
		$gamedata['innerHTML']['ingamebgm'] = $bgm_player;
	}
	// RuleSet钩子：在保存玩家数据与决定弹窗前结算模式事件。
	// RuleSet hook: settle mode events before saving player data and choosing a dialog.
	if(function_exists('ruleset_command_end_hook')) {
		ruleset_command_end_hook($pdata, isset($command) ? $command : '');
	}
	//检查执行动作后是否有对话框产生
	//如果刚刚处理了对话选择，则不显示对话框
	$just_made_choice = strpos($command, 'dialogue_choice') === 0;

	if(!$just_made_choice && !empty($clbpara['dialogue']))
	{
		$opendialog = 'dialogue';
		$dialogue_id = $clbpara['dialogue'];
	}
	//显示指令执行结果
	$gamedata['innerHTML']['notice'] = ob_get_contents();
	if(($coldtimeon && $showcoldtimer && $rmcdtime) || isset($dizzy_times)){
		$gamedata['timer'] = isset($dizzy_times) ? $dizzy_times : $rmcdtime;
	}
	if($hp > 0 && $coldtimeon && $showcoldtimer && $rmcdtime){
		$log .= "行动冷却时间：<span id=\"timer\" class=\"yellow\">0.0</span>秒<br>";
	}
	player_save($pdata);
}
// RuleSet钩子：玩家数据持久化完成后释放指令级资源；死亡页面请求也需要释放。
// RuleSet hook: release command-scoped resources after persistence, including death-page requests.
if(function_exists('ruleset_command_post_save_hook')) {
	ruleset_command_post_save_hook($pdata, isset($command) ? $command : '');
}
init_profile();
if($hp <= 0) {
	$dtime = date("Y年m月d日H时i分s秒",$endtime);
	$kname='';
	if($bid) {
		$result = $db->query("SELECT name FROM {$tablepre}players WHERE pid='$bid'");
		if($db->num_rows($result)) { $kname = $db->result($result,0); }
	}
	ob_clean();
	include template('death');
	$gamedata['innerHTML']['cmd'] = ob_get_contents();
	$mode = 'death';
} elseif($cmd){
	$gamedata['innerHTML']['cmd'] = $cmd;
} elseif($itms0){
	ob_clean();
	include template('itemfind');
	$gamedata['innerHTML']['cmd'] = ob_get_contents();
} elseif($state == 1 || $state == 2 || $state ==3) {
	ob_clean();
	if($mode == 'fishing') {
		// 在钓鱼模式下显示钓鱼界面
		$fishing_count = count($clbpara['fishing']['caught_items']);
		include template('fishing');
	} else {
		include template('rest');
	}
	$gamedata['innerHTML']['cmd'] = ob_get_contents();
} elseif(!$cmd) {
	ob_clean();
	if($mode&&file_exists(GAME_ROOT.TPLDIR.'/'.$mode.'.htm')) {
		include template($mode);
	} else {
		include template('command');
	}
	$gamedata['innerHTML']['cmd'] = ob_get_contents();
	//$gamedata['cmd'] .= '<br><br><input type="button" id="submit" onClick="postCommand();return false;" value="提交">';
} else {
	$log .= '游戏流程故障，请联系管理员<br>';
	//$gamedata['innerHTML']['cmd'] = $cmd;
	//$gamedata['cmd'] .= '<br><br><input type="button" id="submit" onClick="postCommand();return false;" value="提交">';
}

//存在 $opendialog 时 尝试打开id为 $opendialog 值的悬浮窗口
if(isset($opendialog)){$log.="<span style=\"display:none\" id=\"open-dialog\">{$opendialog}</span>";}

if(isset($url)){$gamedata['url'] = $url;}
$gamedata['innerHTML']['pls'] = (!isset($plsinfo[$pls]) && isset($hplsinfo[$pgroup])) ? $hplsinfo[$pgroup][$pls] : $plsinfo[$pls];
$gamedata['innerHTML']['anum'] = $alivenum;
// 传递位置ID给JavaScript，用于更新背景图片
$gamedata['locationId'] = $pls;

ob_clean();
$main ? include template($main) : include template('profile');
$gamedata['innerHTML']['main'] = ob_get_contents();
// 添加调试信息，显示最终的 log 变量状态
$log .= "<!-- DEBUG: 最终的 log 变量长度: " . strlen($log) . " -->";

$gamedata['innerHTML']['log'] = $log;
if(isset($error)){$gamedata['innerHTML']['error'] = $error;}
$gamedata['clbpara'] = $clbpara;
$gamedata['value']['teamID'] = $teamID;
if($teamID){
	$gamedata['innerHTML']['chattype'] = "<select name=\"chattype\" value=\"2\"><option value=\"0\" selected>$chatinfo[0]<option value=\"1\" >$chatinfo[1]</select>";
}else{
	$gamedata['innerHTML']['chattype'] = "<select name=\"chattype\" value=\"2\"><option value=\"0\" selected>$chatinfo[0]</select>";
}
//foreach($gamedata as $k => $v){
//	$w .= "{ $k } => { $v };\n\r";
//}
//writeover('a.txt',$w);
ob_clean();
$jgamedata = compatible_json_encode($gamedata);
//$json = new Services_JSON();
//$jgamedata = $json->encode($gamedata);
//if(!strstr($_SERVER['HTTP_REFERER'], 'php')) {
if (isset($_GET['is_new'])) {
	include './api.php';
} else {
	echo $jgamedata;
}

ob_end_flush();
//$t_e=getmicrotime();
//putmicrotime($t_s,$t_e,'cmd_time');

?>
