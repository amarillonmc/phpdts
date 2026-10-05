<?php
if(!defined('IN_GAME')) exit('Access Denied');

function club23_config()
{
	global $club23cfg;
	if(!isset($club23cfg)) $club23cfg = include config('club23cfg',1);
	return $club23cfg;
}

function club23_is_memory($data)
{
	return isset($data['wepk']) && $data['wepk'] === 'WFq' && !empty($data['weps']);
}

// 技能入口仍使用 revskpts，消耗在效果能够完成后才扣除。
function club23_skill($skill,&$data,$patch_mode='')
{
	global $log,$nosta;
	$cfg = club23_config();
	$data['clbpara'] = get_clbpara($data['clbpara']);
	if($data['hp'] <= 0 || $data['type'] || $data['club'] != 23 || check_skill_unlock($skill,$data))
	{
		$log .= '你现在无法使用这个称号技能。<br>';
		return 0;
	}
	if($skill == 'c23_trauma')
	{
		if(empty($data['weps']) || strpos($data['wepk'],'W') !== 0 || $data['wepk'] == 'WN' || club23_is_memory($data) || $data['wepe'] <= 0)
		{
			$log .= '请先装备一件尚未剥落、效果大于零的武器。<br>';
			return 0;
		}
		// 转化后只保留效果与有限耐久，原武器的属性和专用参数随外壳一起剥落。
		$data['wep'] = '剥落的记忆';
		$data['wepk'] = 'WFq';
		if($data['weps'] === $nosta) $data['weps'] = $cfg['memory_durability'];
		$data['wepsk'] = $data['weppara'] = '';
		$log .= '<span class="purple">你揭开了物件表面的一角。熟悉的形状剥落下来，里面留着一段没有来历的往事。</span><br>武器变成了<span class="yellow">剥落的记忆</span>。<br>';
		return 1;
	}
	if($skill != 'c23_shroud' && $skill != 'c23_patch') return 0;
	$steps = isset($data['clbpara']['skillpara']['c23_shroud']['steps']) ? (int)$data['clbpara']['skillpara']['c23_shroud']['steps'] : 0;
	if($steps < $cfg['shroud_steps'])
	{
		$log .= '黑岚里暂时没有可以拾起的东西。继续移动或探索，寻找下一块记忆。<br>';
		return 0;
	}
	if($skill == 'c23_shroud')
	{
		// 不占用 itm0，避免从发现物品/尸体界面发动时覆盖待拾取物品。
		for($slot=1;$slot<=6;$slot++) if(empty($data['itms'.$slot])) break;
		if($slot > 6)
		{
			$log .= '请先空出一个背包位置。黑岚中的记忆仍在那里。<br>';
			return 0;
		}
		$data['itm'.$slot] = '剥落的记忆';
		$data['itmk'.$slot] = 'WFq';
		$data['itme'.$slot] = $cfg['memory_effect'];
		$data['itms'.$slot] = $cfg['memory_durability'];
		$data['itmsk'.$slot] = $data['itmpara'.$slot] = '';
		$log .= '<span class="purple">你伸手探进那片暗色。有什么东西握住了你，又松开了。</span><br>你将<span class="yellow">剥落的记忆</span>放入背包。<br>';
	}
	elseif($patch_mode == 'weapon')
	{
		if(!club23_is_memory($data) || $data['weps'] === $nosta)
		{
			$log .= '请先装备一件可以补缀的剥落的记忆。<br>';
			return 0;
		}
		$data['weps'] += $cfg['patch_durability'];
		$log .= "这块记忆的颜色不太一样。暂时能用。<br>剥落的记忆增加<span class=\"yellow\">{$cfg['patch_durability']}</span>耐久。<br>";
	}
	elseif($patch_mode == 'health')
	{
		if($data['hp'] >= $data['mhp'])
		{
			$log .= '你目前不需要补缀自己的形体。<br>';
			return 0;
		}
		$heal = min($data['mhp']-$data['hp'],max(1,round($data['mhp']*$cfg['patch_heal_percent']/100)));
		$data['hp'] += $heal;
		$log .= "你用一段不属于自己的经历，补住了身上的缺口。<br>恢复<span class=\"lime\">{$heal}</span>点生命。<br>";
	}
	else
	{
		$log .= '请选择要补缀武器，还是自己的形体。<br>';
		return 0;
	}
	$data['clbpara']['skillpara']['c23_shroud']['steps'] = $steps-$cfg['shroud_steps'];
	return 1;
}

// 只有玩家之间的接触积累 S；换一个记忆持有者不会清除或另建一份进度。
function club23_contact(&$source,&$target,$vacancy=false)
{
	global $now,$log,$club23_contacts;
	if($source['type'] || $target['type'] || !club23_is_memory($source) || $source['hp'] <= 0 || $target['hp'] <= 0) return 0;
	$cfg = club23_config();
	$gain = max(1,(int)round($source['wepe']*$cfg['subliminal_per_effect']));
	$target['clbpara']['subliminal'] = (isset($target['clbpara']['subliminal']) ? $target['clbpara']['subliminal'] : 0)+$gain;
	if(!check_skill_unlock('c23_afterimage',$source))
		$target['clbpara']['subliminal_afterimage_until'] = $now+$cfg['afterimage_seconds'];
	// 请求内标记：探索过程中若遭遇记忆，结束探索时不再衰减本次新施加的 S。
	$club23_contacts[$target['pid']] = true;
	$source['club23_memory_hit'] = true;
	$lead = $vacancy ? '「空座」留下的缺口触及了' : '剥落的记忆触及了';
	$log .= "<span class=\"purple\">{$lead}{$target['nm']}。一段陌生的往事开始变得熟悉。</span><br>";
	$target['logsave'] = (isset($target['logsave']) ? $target['logsave'] : '')."<span class=\"purple\">你没有见过那条走廊，但你知道尽头的灯一直是坏的。</span><br>记忆侵入增加{$gain}，目前为{$target['clbpara']['subliminal']}。<br>";
	return $gain;
}

// 每次正式交战只在开头调用一次；此时还没有加入本次命中的 S。
// 使用不经称号/运气修正的 1d1000，严格小于 S 才触发。
function club23_death_damage(&$source,&$target)
{
	global $log;
	if($source['type'] || $target['type'] || !club23_is_memory($source) || $source['hp'] <= 0 || $target['hp'] <= 0 || empty($target['clbpara']['subliminal'])) return 0;
	if(mt_rand(1,1000) >= $target['clbpara']['subliminal']) return 0;
	$damage = $target['hp'];
	$source['final_damage'] = $damage;
	$log .= "<span class=\"purple\">有人叫了一个{$target['nm']}不认识的名字。{$target['nm']}答应了。</span><br><span class=\"red\">往事覆盖了眼前的景象，{$target['nm']}的形体崩散了！</span><br>";
	$target['logsave'] = (isset($target['logsave']) ? $target['logsave'] : '').'<span class="red">你答应了那个陌生的名字。记忆侵入使你的形体崩散。</span><br>';
	return $damage;
}

function club23_memory_attack(&$source,&$target,$active)
{
	global $log;
	$cfg = club23_config();
	$source['hitrate_times'] = mt_rand(1,100) <= $cfg['memory_hit_rate'] ? 1 : 0;
	$source['inf_times'] = $source['final_damage'] = $source['phy_damage'] = $source['ex_damage'] = 0;
	$log .= "{$source['nm']}将<span class=\"purple\">剥落的记忆</span>伸向{$target['nm']}。<br>";
	if($source['hitrate_times'])
	{
		$source['club23_landed'] = true;
		if(!club23_contact($source,$target)) $log .= '那幅景象没有找到可以停留的位置。<br>';
	}
	else $log .= '景象从身边掠过，没有留下痕迹。<br>';
	// 经验、怒气与武器耗损沿用现有结算；不调用常规伤害、属性和连击计算。
	expup_rev($source,$target,$active);
	rgup_rev($source,$target,$active);
	weapon_loss($source,1);
	$source['wf'] += 1;
	return 0;
}

function club23_adjust_damage($source,$target,$damage)
{
	global $log;
	if($damage <= 0) return $damage;
	$cfg = club23_config();
	if($source['club'] == 23)
	{
		$damage = round($damage*$cfg['ordinary_damage_percent']/100);
		$log .= "<span class=\"grey\">往事拖住了{$source['nm']}的手，常规武器伤害衰减。</span><br>";
	}
	if(club23_is_memory($target) && !check_skill_unlock('c23_scrape',$target))
	{
		$damage = round($damage*$cfg['scrape_damage_percent']/100);
		$log .= "<span class=\"purple\">「旧痕」让攻击穿过了{$target['nm']}身上不属于此刻的部分。</span><br>";
	}
	return $damage;
}

// 普通反击已经命中时不重复施加。射程、反击姿态不影响这次接触。
function club23_vacancy(&$attacker,&$defender)
{
	if($attacker['hp'] <= 0 || $defender['hp'] <= 0 || empty($attacker['club23_landed']) || !empty($defender['club23_memory_hit']) || check_skill_unlock('c23_vacancy',$defender)) return;
	if(club23_contact($defender,$attacker,true)) weapon_loss($defender,1);
}

function club23_move_search(&$data)
{
	global $now,$log,$club23_contacts;
	if($data['hp'] <= 0 || $data['type']) return;
	$cfg = club23_config();
	if($data['club'] == 23 && !check_skill_unlock('c23_shroud',$data))
	{
		if(!isset($data['clbpara']['skillpara']['c23_shroud']['steps'])) $data['clbpara']['skillpara']['c23_shroud']['steps'] = 0;
		$data['clbpara']['skillpara']['c23_shroud']['steps']++;
		if($data['clbpara']['skillpara']['c23_shroud']['steps'] % $cfg['shroud_steps'] == 0)
			$log .= '<span class="purple">黑岚里浮出了新的轮廓。可以领取记忆，或将它用于补缀。</span><br>';
	}
	if(empty($data['clbpara']['subliminal'])) return;
	if(empty($club23_contacts[$data['pid']]))
	{
		$decay = $cfg['decay'];
		if(!empty($data['clbpara']['subliminal_afterimage_until']) && $now < $data['clbpara']['subliminal_afterimage_until'])
			$decay = max(1,(int)round($decay*$cfg['afterimage_decay_percent']/100));
		else unset($data['clbpara']['subliminal_afterimage_until']);
		$data['clbpara']['subliminal'] = max(0,$data['clbpara']['subliminal']-$decay);
	}
	$s = $data['clbpara']['subliminal'];
	if(!$s)
	{
		unset($data['clbpara']['subliminal'],$data['clbpara']['subliminal_afterimage_until']);
		$log .= '<span class="lime">眼前的景物终于只属于此刻。记忆侵入消退了。</span><br>';
	}
	elseif($s >= $cfg['dialogue_threshold'] && empty($data['clbpara']['dialogue']) && empty($data['clbpara']['noskip_dialogue']) && mt_rand(1,100) <= min($cfg['dialogue_chance_max'],(int)floor($s/20)))
	{
		$data['clbpara']['dialogue'] = 'club23_intrusion';
	}
}
