<?php
if(!defined('IN_GAME')) exit('Access Denied');

function fcg_field_rules($op)
{
	$rules=array(
		'heal_both_4_clean_freeze'=>'每回合末牌手与帮手恢复 4 生命，帮手解除冻结',
		'heal_both_6_def_1_clean_freeze'=>'每回合末牌手与帮手恢复 6 生命，牌手防御 +1，帮手解除冻结',
		'valhalla_2'=>'置入时牌手攻防 +2；帮手阵亡且未重生时牌手攻击 +2、白能量 +2；每回合末恢复 3 生命，帮手 +1 攻／+1 防',
		'valhalla_3'=>'置入时牌手攻防 +3；帮手阵亡且未重生时牌手攻击 +3、白能量 +3；每回合末恢复 5 生命，帮手 +2 攻／+1 防',
		'peep_room_1'=>'敌方每出一张非能量卡，己方当前最贵手牌减费 1；每回合末黄能量 +1',
		'peep_room_2'=>'敌方每出一张非能量卡，己方当前最贵手牌减费 1；每回合末黄能量 +2、抽 1 张牌',
		'viridian_1'=>'每回合末己方回血 +1、敌方回血 −1；绿产能至少 4 时，帮手 +2 攻／+1 防／+2 生命',
		'viridian_2'=>'每回合末己方回血 +1、敌方回血 −2；绿产能至少 4 时，帮手 +3 攻／+2 防／+3 生命',
		'digital_life_1'=>'每回合末五色能量 +1，对敌方牌手造成 4 点直伤',
		'digital_life_2'=>'每回合末五色能量 +1，对敌方牌手造成 6 点直伤',
		'nuke_24_8'=>'归零时敌方牌手与帮手各受 24 点直伤，己方牌手受到 8 点波及伤害',
		'nuke_32_12'=>'归零时敌方牌手与帮手各受 32 点直伤，己方牌手受到 12 点波及伤害',
		'stall_eight_zones_1'=>'驻留时双方直接攻击伤害减半（向上取整）；归零时造成双方总产能 +6 点直伤，并抽 2 张牌',
		'stall_eight_zones_2'=>'驻留时双方直接攻击伤害减半（向上取整）；归零时造成双方总产能 +12 点直伤，并抽 2 张牌',
		'mine_snare_10_3'=>'敌方召唤或协战时，对其帮手造成 10 点伤害，溢出伤及牌手，牌手防御 −3',
		'mine_snare_15_5'=>'敌方召唤或协战时，对其帮手造成 15 点伤害，溢出伤及牌手，牌手防御 −5',
		'booby_12_3'=>'敌方本回合第 2 张及以后的非能量出牌后，牌手受 12 点直伤，牌手与帮手防御 −3',
		'booby_16_5'=>'敌方本回合第 2 张及以后的非能量出牌后，牌手受 16 点直伤，牌手与帮手防御 −5',
		'emp_trap_3_2_4'=>'敌方打出原始费用至少 4 的牌后，夺取其最多颜色能量 3 点，冻结帮手 2 回合，本回合牌手攻击降低 4',
		'emp_trap_5_3_6'=>'敌方打出原始费用至少 4 的牌后，夺取其最多颜色能量 5 点，冻结帮手 3 回合，本回合牌手攻击降低 6',
		'poison_feast_3_10'=>'敌方打出含恢复生命、回血、能量或产能效果的非能量牌后，其回血 −3，并受 10 点直伤',
		'poison_feast_5_14'=>'敌方打出含恢复生命、回血、能量或产能效果的非能量牌后，其回血 −5，并受 14 点直伤',
		'mirror_backfire_1'=>'下一次至少 10 点的直接攻击伤害降为 1，将原伤害反打给攻击者',
		'mirror_backfire_2'=>'下一次至少 10 点的直接攻击伤害降为 1，将原伤害加 6 反打给攻击者'
	);
	return $rules[$op];
}

function fcg_card_rules($card)
{
	$colors=array('W'=>'白','R'=>'红','Y'=>'黄','G'=>'绿','B'=>'蓝','ALL'=>'五色');
	$labels=array('atk'=>'攻击','def'=>'防御','gen_max'=>'最高颜色产能'); $out=array();
	foreach(fcg_tokens($card['ops']) as $op) {
		if(preg_match('/^(gamble|refactor)\((.*)\)$/',$op,$p)) {
			$sub=$card; $sub['ops']=$p[2];
			$out[]=($p[1]==='gamble'?'孤注：弃掉其他手牌中实际费用最低的一张（并列取最右，空手也满足），不扣回血，可触发重构；随后':'重构：主动弃掉或被孤注弃掉时，免除弃牌扣回血并免费').'：'.rtrim(fcg_card_rules($sub),'。'); continue;
		}
		if(preg_match('/^mastery\(([WRYGB]),\s*(\d+),\s*(.*)\)$/',$op,$p)) {
			$sub=$card; $sub['ops']=$p[3]; $out[]=$colors[$p[1]].'产能至少 '.$p[2].' 时：'.rtrim(fcg_card_rules($sub),'。'); continue;
		}
		if(preg_match('/^(combo|resonance)\(([^,]+),\s*(.*)\)$/',$op,$p)) {
			$sub=$card; $sub['ops']=$p[3];
			$out[]=($p[1]==='combo' ? '本回合第 '.$p[2].' 张及以后出牌时' : '上一张牌为'.($colors[$p[2]] ?? '任意').'色时').'：'.rtrim(fcg_card_rules($sub),'。'); continue;
		}
		if(preg_match('/^combo_(\d+)_(.*)$/',$op,$p)) {
			$sub=$card; $sub['ops']=preg_replace('/^pierce\(/','pierce_enemy(',$p[2]);
			$out[]='本回合第 '.$p[1].' 张及以后出牌时：'.rtrim(fcg_card_rules($sub),'。'); continue;
		}
		if(strpos($op,'scale_by(')===0) {
			$up=$card['upgraded'];
			$scale=array(27=>'按仍有能量的颜色数 N，攻击、防御各增加 '.($up?'3':'2').'N，生命恢复 '.($up?'6':'5').'N'.($up?'，抽 2 张牌':''),
				46=>'额外造成等于敌方攻击力的伤害',107=>'额外造成等于敌方攻击力的伤害',50=>'自身攻击与防御各增加敌方攻、防中的较大值',
				70=>'造成敌方总能量的 '.($up?'2/3（最高 24）':'1/2（最高 18）').' 点穿透伤害',
				75=>'造成双方总产能'.($up?'再加 8':'').'点伤害');
			$out[]=$scale[$card['pair']]; continue;
		}
		preg_match('/^(\w+)(?:\((.*)\))?$/',$op,$match);
		$k=$match[1]; $v=isset($match[2]) ? array_map('trim',explode(',',$match[2])) : array();
		$n=$v[0] ?? ''; $n2=$v[1] ?? ''; $n3=$v[2] ?? '';
		switch($k) {
			case 'field_domain': $out[]='场地：'.fcg_field_rules($n).'；占用己方唯一场地区，覆盖旧卡使其入堆'; break;
			case 'field_countdown': $out[]='倒数 '.$n.'：每回合末减 1；'.fcg_field_rules($n2).'；占用己方唯一场地区，覆盖旧卡使其入堆'; break;
			case 'trap_set': $out[]='伏击：暗牌盖放在己方唯一场地区，覆盖旧卡使其入堆；'.fcg_field_rules($n2).'，触发后退场'; break;
			case 'clear_enemy_field': $out[]='拆除敌方场地区且不触发伏击；成功拆除时黄能量 +'.$n.'、防御 +'.$n2; break;
			case 'surge_hand': $out[]='在手牌期间，每打出一张'.($card['pair']===134?'法术':'其他牌').'，本卡费用 −1（最低 0）'; break;
			case 'surge_match': $out[]='本局每已打出或弃掉一张牌，本卡费用 −1（最低 0）'; break;
			case 'surge_boost_hand': $out[]='当前全部手牌费用 −'.$n.'（最低 0）'; break;
			case 'summon_self_or_heal': $out[]='无帮手时免费召唤本卡；已有帮手时恢复牌手 '.$n.' 生命、防御 +4'; break;
			case 'upload_on_death': $out[]='阵亡且未重生时上传为数字生命场地：'.fcg_field_rules('digital_life_'.$n); break;
			case 'armor_press': $out[]='未冻结时，每回合末额外造成等于帮手当前防御的直伤'; break;
			case 'mastery_mon': $out[]=$colors[$n].'产能至少 '.$n2.' 时，登场帮手额外 +'.$n3.' 攻／+'.$v[3].' 防／+'.$v[4].' 生命'; break;
			case 'desperate_burst': $out[]='自身生命不高于 '.$n.' 时，额外造成 '.$n2.' 点穿透直伤'; break;
			case 'steal_valkyrie_soul': $out[]='夺取敌方帮手一半攻防（向下取整）给牌手，己方帮手复制其除协战、瞬身外的词条；敌方无帮手时改为夺能 '.($n==='2'?6:4).' 并造成 '.($n==='2'?14:10).' 点穿透直伤'; break;
			case 'gain_monster_stats': $out[]='帮手 +'.$n.' 攻／+'.$n2.' 防／+'.$n3.' 生命；无帮手时由牌手获得'; break;
			case 'mastery_deathnote': $out[]='登场时蓝产能至少 '.$n.'，无视潜伏击破敌方帮手，并对牌手造成 '.$n2.' 点直伤'; break;
			case 'miracle_box': $out[]='奇迹：五色能量 +'.($n==='2'?2:1).'，恢复 '.($n==='2'?14:10).' 生命；大成功额外五色能量 +1、穿透直伤 '.($n==='2'?20:14).'、抽 2 张牌；概率 50%，自身生命低于敌方或有出千光环时必定大成功'; break;
			case 'miracle_gem': $out[]='奇迹：攻防各 +'.$n.'；大成功改为各 +'.$n2.'，并使己方帮手 +4 攻／+4 防、获得一次重生；概率 50%，自身生命低于敌方或有出千光环时必定大成功'; break;
			case 'aura_cheat_miracle': $out[]='未冻结时奇迹必定大成功，每次奇迹额外抽 1 张牌、黄能量 +'.$n; break;
			case 'on_summon_jinx': $out[]='登场时敌方攻击 +'.$n.'，己方场地区置入倒数 1：归零使敌方攻击减半、受到 '.$n2.' 点直伤、回血 −'.$n3; break;
			case 'sql_inject': $out[]='本回合连击、余韵、极意、奇迹条件全部满足，囤积按 6 张手牌计算'; break;
			case 'hoard_slides': $out[]='登场时每张剩余手牌提供 '.$n.' 防御、'.$n2.' 生命；至少 4 张时白产能 +'.($card['upgraded']?2:1); break;
			case 'draw_safe': $out[]='抽 '.$n.' 张牌，满手时跳过且不扣回血（空牌库仍扣回血）'; break;
			case 'hoard_feast': $out[]='每张当前手牌恢复 '.$n.' 生命、绿能量 +'.$n2; break;
			case 'programmer_husband': $out[]='敌方牌手攻防中较高项设为 1（同值取攻击），帮手攻击设为 1'.($n==='2'?'，最多颜色能量设为 1':''); break;
			case 'copy_enemy_last_card': $out[]='复制敌方上一张公开打出的牌至手牌，费用 −'.$n.'（最低 0，满手跳过）'; break;
			case 'rewind_last_played': $out[]='将己方上一张非本卡的公开出牌复制回手牌，费用 −'.$n.'（最低 0，满手跳过）'; break;
			case 'on_summon_draw': $out[]='登场时抽 '.$n.' 张牌'; break;
			case 'add_gen': $out[]=($colors[$n] ?? $n).'产能 +'.$n2; break;
			case 'add_energy': $out[]=$n==='enemy_random' ? '敌方随机一种能量 +'.$n2 : ($colors[$n] ?? $n).'能量 +'.$n2; break;
			case 'gen': $out[]='每回合'.($colors[$n] ?? $n).'能量 '.((float)$n2>=0?'+':'').$n2; break;
			case 'gain_atk': $out[]='自身攻击 +'.$n2; break;
			case 'gain_def': $out[]='自身防御 +'.$n2; break;
			case 'gain_atk_def': $out[]='自身攻击 +'.$n2.'、防御 +'.$n3; break;
			case 'gain_hp': $out[]=($n==='enemy'?'敌方':'自身').'恢复 '.$n2.' 生命'; break;
			case 'lose_hp': $out[]=($n==='enemy'?'敌方':'自身').'失去 '.$n2.' 生命'; break;
			case 'add_regen': $out[]='每回合回血 +'.$n2; break;
			case 'add_poison': $out[]=($n==='self'?'自身':'敌方').'每回合回血 −'.$n2; break;
			case 'turn_bonus_dmg': $out[]='本回合自身攻击 +'.$n; break;
			case 'draw': $out[]='抽 '.$n.' 张牌'; break;
			case 'gain_monster_def': $out[]='己方帮手防御 +'.$n2; break;
			case 'gain_monster_hp': $out[]='己方帮手生命 +'.$n2; break;
			case 'lose_monster_hp': $out[]='敌方帮手失去 '.$n2.' 生命'; break;
			case 'freeze_enemy_monster': $out[]='冻结敌方帮手 '.$n.' 回合，期间不能攻击、成长、产能或发动光环'; break;
			case 'sunder_enemy': $out[]='敌方及其帮手防御 −'.$n; break;
			case 'pierce_enemy': $out[]='穿透伤害 '.$n.'（无视防御）'; break;
			case 'weaken_enemy': $out[]='敌方攻击 −'.$n.'、防御 −'.$n2; break;
			case 'steal_energy': $out[]='夺取敌方能量最多的颜色 '.$n.' 点'; break;
			case 'steal_stat': $out[]='夺取敌方'.($labels[$n] ?? $n).' '.$n2.' 点'; break;
			case 'lure': $out[]='护卫：替牌手承受攻击，溢出伤害仍伤及牌手'; break;
			case 'kill_monsters': $out[]='优先攻击敌方帮手，溢出伤害践踏牌手'; break;
			case 'growth': $out[]='未冻结的回合末成长 '.$n.' 攻／'.$n2.' 防／'.$n3.' 生命'; break;
			case 'pierce': $out[]='攻击忽略 '.$n.' 点防御'; break;
			case 'sunder': $out[]='攻击前使敌方及其帮手防御 −'.$n; break;
			case 'poison_enemy': $out[]='每回合使敌方回血 −'.$n; break;
			case 'rebirth_once': $out[]='被击破后满生命重生一次'; break;
			case 'rebirth_dmg_enemy': $out[]='被击破时对敌方造成 '.$n.' 伤害'; break;
			case 'on_summon_gain_hp': $out[]='登场时恢复牌手 '.$n.' 生命'; break;
			case 'on_summon_dmg_enemy': $out[]='登场时造成 '.$n.' 伤害'; break;
			case 'on_summon_steal_stat': $out[]='登场时夺取敌方'.($labels[$n] ?? $n).' '.$n2.' 点'; break;
			case 'set_thorns': $out[]='本回合反噬所受伤害 ×'.substr($n2,6).'（每击上限 '.substr($n3,4).'）'; break;
			case 'set_evade': $out[]='本回合下一次直接攻击伤害降为 1'.($n2==='2'?'，并将原伤害反打给攻击者':''); break;
			case 'cleanse_flip_self': $out[]='负回血转为正回血，清除破绽并解除己方帮手冻结'; break;
			case 'clean_self_poison': $out[]='清除自身负回血'; break;
			case 'clean_self_poison_to_enemy': $out[]='将自身负回血转移给敌方'; break;
			case 'invert_enemy_regen': $out[]='敌方回血正负反转'; break;
			case 'invert_or_burst_poison': $out[]='敌方仍为正回血时反转，否则追加 '.$n2.' 伤害'; break;
			case 'mirror_balance_stats': $out[]='双方攻击、防御分别同步至双方较高值'; break;
			case 'mirror_last_spell': $out[]='以己方视角重现上一张非镜像法术'; break;
			case 'mystery_upgraded_zero_cost': $out[]='随机获得一张本局零费升级手牌（不加入收藏）'; break;
			case 'code_fu_search': $out[]='检索牌库中费用最高的单位，加入手牌并变为零费'; break;
			case 'kill_enemy_monster': $out[]='击破敌方帮手'; break;
			case 'kill_monster_absorb_hp': $out[]='击破敌方帮手，吸收其剩余生命'; break;
			case 'steal_monster_atk_to_self': $out[]='将刚击破帮手的攻击力加入自身攻击'; break;
			case 'buff_or_summon_guardian': $out[]='己方帮手增加 '.substr($n,4).' 攻／'.substr($n2,4).' 防／'.substr($n3,3).' 生命，每回合攻防成长 '.substr($v[3],7).'；没有帮手则召唤护卫'; break;
			case 'escort': $out[]='已有帮手时，将本卡攻防血与词条并入原帮手，仍触发本卡登场效果'; break;
			case 'ephemeral': $out[]='受到正数攻击伤害或回合结束时自动退场，并触发亡语'; break;
			case 'stealth': $out[]='首次攻击前免疫法术击杀、法术扣血和敌方帮手索敌；首次攻击翻倍后现形'; break;
			case 'disarm_enemy': $out[]='清空敌方本回合临时攻击加成，本回合牌手攻击降低 '.$n; break;
			case 'disarm_attack': $out[]='未冻结时，每回合攻击前清空敌方临时攻击加成，牌手攻击降低 '.$n; break;
			case 'vuln_enemy': $out[]='敌方牌手下次承受攻击包时额外受伤 '.$n.'；空过不出牌的回合末清除'; break;
			case 'mark_vuln_on_hit': $out[]='帮手造成正数攻击伤害后，使敌方牌手下次受击额外受伤 '.$n; break;
			case 'gain_barrier': $out[]='获得 '.$n2.' 符盾，抵消后续伤害；符盾存在时己方攻击无视敌方护卫'; break;
			case 'on_summon_barrier': $out[]='登场时获得 '.$n.' 符盾，抵消后续伤害；符盾存在时己方攻击无视护卫'; break;
			case 'endure_lethal': $out[]='本回合受到致命伤害时至少保留 1 生命'; break;
			case 'desperate_draw': $out[]='自身生命不高于 '.$n.' 时抽 '.$n2.' 张牌'; break;
			case 'combo_attack_bonus': $out[]='本回合出牌不少于 '.$n.' 张时，帮手本次攻击 +'.$n2; break;
			case 'headshot': $out[]='敌方牌手生命不高于 '.$n.' 时，帮手攻击 +'.$n2; break;
			case 'promote': $out[]='未冻结在场满 2 回合或牌手生命不高于 45 时，永久增加 '.$n.' 攻／'.$n2.' 防／'.$n3.' 生命（仅一次）；进化后未冻结的回合末造成 '.$v[3].' 点直伤'; break;
			case 'enrage_atk': $out[]='帮手受到正数攻击伤害且存活时，永久增加 '.$n.' 攻击'; break;
			case 'aura_spell_zap': $out[]='在场且未冻结时，己方每出一张法术，对敌方牌手造成 '.$n.' 点直伤'; break;
			case 'aura_bug_draw': $out[]='在场且未冻结时，敌方每出一张非能量卡，己方'.($n==='0'?'抽 1 张牌':'获得 '.$n.' 黄能量'); break;
			case 'aura_artillery': $out[]='在场且未冻结时，己方牌手与帮手攻击越过敌方护卫直接命中牌手'; break;
			case 'aura_venom': $out[]='在场且未冻结时，己方牌手造成正数攻击伤害后，使敌方回血降低 '.$n; break;
			case 'aura_burn_monster_heal': $out[]='在场且未冻结时，打出的非协战帮手化为薪柴：牌手恢复该卡生命值，驻场帮手增加该卡攻击与 2 生命上限'; break;
			case 'burst_poison': $out[]='造成敌方负回血绝对值 ×'.$n.' 点直伤，不清除毒'; break;
			case 'clean_self_poison_on_death': $out[]='亡语：清除己方负回血'; break;
			case 'weak_to_red': $out[]='本帮手受到红色来源的伤害翻倍（攻击在扣除防御后翻倍；牌手攻击颜色取其上一张牌）'; break;
			case 'shuffle_stats': $out[]='将双方牌手的四个攻防数值随机重排，生命不变'; break;
			case 'on_summon_gain_def': $out[]='登场时牌手防御 +'.$n; break;
			case 'on_summon_eat_poison': $out[]='登场时清除己方负回血，恢复被清除数值 ×'.$n.' 的生命'; break;
			case 'on_summon_chaos_blessing': $out[]='登场时双方牌手各获得 '.$n.' 攻击，回血各降低 '.$n; break;
			case 'on_summon_flatten_def': $out[]='登场时将敌方牌手与驻场帮手防御设为 '.$n; break;
			case 'clone_monster_stats': $out[]='协战时额外复制原帮手 '.((float)$n*100).'% 攻防血与生命上限，各项向下取整'; break;
			default: throw new LogicException('Missing FCG rule description: '.$k);
		}
	}
	return implode('；',$out).'。';
}

function fcg_short_token($op, $card)
{
	$colors=array('W'=>'⚪','R'=>'🔴','Y'=>'🟡','G'=>'🟢','B'=>'🔵','ALL'=>'五色','ANY'=>'任意');
	if(preg_match('/^(gamble|refactor)\((.*)\)$/',$op,$p)) return ($p[1]==='gamble'?'🔥孤注':'♻️重构').'：'.implode(' · ',array_map(function($sub)use($card){return fcg_short_token($sub,$card);},fcg_tokens($p[2])));
	if(preg_match('/^mastery\(([WRYGB]),\s*(\d+),\s*(.*)\)$/',$op,$p)) return '🌟'.$colors[$p[1]].'极意 '.$p[2].'：'.implode(' · ',array_map(function($sub)use($card){return fcg_short_token($sub,$card);},fcg_tokens($p[3])));
	if(preg_match('/^(combo|resonance)\(([^,]+),\s*(.*)\)$/',$op,$p)) return ($p[1]==='combo'?'🔗连击 '.$p[2]:'🎨余韵 '.($colors[$p[2]] ?? $p[2])).'：'.fcg_short_token($p[3],$card);
	if(preg_match('/^combo_(\d+)_(.*)$/',$op,$p)) return '🔗连击 '.$p[1].'：'.fcg_short_token(preg_replace('/^pierce\(/','pierce_enemy(',$p[2]),$card);
	if(strpos($op,'scale_by(')===0) return '📐增幅';
	preg_match('/^(\w+)(?:\((.*)\))?$/',$op,$p); $key=$p[1];
	$v=isset($p[2])?array_map('trim',explode(',',$p[2])):array(); $n=$v[0] ?? ''; $n2=$v[1] ?? ''; $n3=$v[2] ?? '';
	$labels=array('lure'=>'🛡️护卫','kill_monsters'=>'🎯猎杀','rebirth_once'=>'🔥重生','escort'=>'🤝协战','ephemeral'=>'🧨瞬身','stealth'=>'🌫️潜伏',
		'mirror_last_spell'=>'🪞覆唱','code_fu_search'=>'🔍检索：最高费帮手零费','mystery_upgraded_zero_cost'=>'🎁衍生：零费升级牌',
		'cleanse_flip_self'=>'✨净化反转','clean_self_poison'=>'✨净毒','clean_self_poison_to_enemy'=>'☠️移毒','invert_enemy_regen'=>'☯️回血反转',
		'mirror_balance_stats'=>'⚖️攻防同步','kill_enemy_monster'=>'💀击破帮手','kill_monster_absorb_hp'=>'🩸吞噬帮手','steal_monster_atk_to_self'=>'🧲吸攻',
		'endure_lethal'=>'🔒锁血','weak_to_red'=>'🔥惧火','shuffle_stats'=>'🔀攻防乱序','clean_self_poison_on_death'=>'💀亡语：净毒',
		'aura_artillery'=>'⛩️光环：炮架','aura_burn_monster_heal'=>'⛩️光环：传火',
		'surge_hand'=>'⚡涌动','surge_match'=>'⚡全局涌动','clear_enemy_field'=>'🌪️拆场','armor_press'=>'🪨盾压',
		'miracle_box'=>'🎲奇迹：妙妙箱','miracle_gem'=>'🎲奇迹：赌玉','sql_inject'=>'💉注入：条件全满足',
		'programmer_husband'=>'🍎逻辑短路：设为1','aura_cheat_miracle'=>'⛩️光环：出千');
	if(isset($labels[$key])) return $labels[$key];
	if($key==='field_domain') return '🏛️场地：'.array('heal_both_4_clean_freeze'=>'圣所','heal_both_6_def_1_clean_freeze'=>'圣所','valhalla_2'=>'英灵','valhalla_3'=>'英灵','peep_room_1'=>'蹲站','peep_room_2'=>'蹲站','viridian_1'=>'常磐','viridian_2'=>'常磐','digital_life_1'=>'数字生命','digital_life_2'=>'数字生命')[$n];
	if($key==='field_countdown') return '🚨倒数 '.$n.'：'.(strpos($n2,'nuke_')===0?'核爆':'八禁');
	if($key==='trap_set') return '🪤伏击：'.array('on_enemy_summon'=>'阔剑','on_enemy_combo2'=>'诡雷','on_enemy_big_card'=>'脉冲','on_enemy_heal_or_energy'=>'毒饵','on_enemy_heavy_hit'=>'反噬')[$n];
	if($key==='mastery_mon') return '🌟'.$colors[$n].'极意 '.$n2.'：+'.$n3.'/+'.$v[3].'/+'.$v[4];
	if($key==='mastery_deathnote') return '🌟🔵极意 '.$n.'：抹杀·伤'.$n2;
	if($key==='on_summon_jinx') return '📣倒数 1：毒奶'.$n2;
	if($key==='hoard_slides') return '🐹囤积：防'.$n.'／血'.$n2;
	if($key==='hoard_feast') return '🐹囤积：血'.$n.'／绿能'.$n2;
	if($key==='gain_monster_stats') return '💪帮手强化 +'.$n.'/+'.$n2.'/+'.$n3;
	if($key==='desperate_burst') return '🔥绝境 ≤'.$n.'：穿伤'.$n2;
	$vol3=array('surge_boost_hand'=>'⚡全手减费','summon_self_or_heal'=>'♻️跃出／恢复','upload_on_death'=>'💀上传','steal_valkyrie_soul'=>'🧲偷魂','draw_safe'=>'🃏安全抽牌','copy_enemy_last_card'=>'🤖复刻减费','rewind_last_played'=>'⏪回收减费','on_summon_draw'=>'📣登场：抽牌');
	if(isset($vol3[$key])) return $vol3[$key].' '.$n;
	if($key==='growth') return '🌱成长 +'.$n.'/+'.$n2.'/+'.$n3;
	if($key==='gen' || $key==='add_gen' || $key==='add_energy') return ($key==='add_energy'?'⚡能量 ':'⚡产能 ').($colors[$n] ?? '敌方随机').' '.$n2;
	if($key==='gain_atk_def') return '⚔攻防 +'.$n2.'/+'.$n3;
	if($key==='set_thorns') return '🌵反噬 '.((float)substr($n2,6)*100).'% · 上限'.substr($n3,4);
	if($key==='set_evade') return $n2==='2'?'⚡偏斜反击':'💨偏斜';
	if($key==='steal_stat' || $key==='on_summon_steal_stat') return ($key==='steal_stat'?'🧲夺取 ':'📣登场：夺取 ').(array('atk'=>'攻','def'=>'防','energy'=>'能量','gen_max'=>'产能')[$n] ?? '').' '.$n2;
	if($key==='promote') return '👑过河 +'.$n.'/+'.$n2.'/+'.$n3.' · 轰炸'.$v[3];
	if($key==='headshot') return '🎯穿杨 ≤'.$n.'：+'.$n2;
	if($key==='desperate_draw') return '🔥绝境 ≤'.$n.'：抽'.$n2;
	if($key==='combo_attack_bonus') return '🔗连击 '.$n.'：攻+'.$n2;
	if($key==='aura_bug_draw') return '⛩️增殖：'.($n==='0'?'抽1':'黄+'.$n);
	if($key==='clone_monster_stats') return '🧬复制 '.((float)$n*100).'%';
	if($key==='buff_or_summon_guardian') return '🛡️召唤／强化 '.substr($n,4).'/'.substr($n2,4).'/'.substr($n3,3);
	$targeted=array('gain_atk'=>'⚔加攻','gain_def'=>'▣加防','gain_hp'=>'♥恢复','lose_hp'=>'💥直伤','add_regen'=>'💚回血','add_poison'=>'☠️剧毒','gain_monster_def'=>'▣帮手加防','gain_monster_hp'=>'♥帮手回血','lose_monster_hp'=>'💥伤帮手','gain_barrier'=>'🧿符盾','invert_or_burst_poison'=>'☠️反转／毒爆');
	if(isset($targeted[$key])) return $targeted[$key].($key==='gain_hp' && $n==='enemy'?'敌方':'').' '.$n2;
	$numbered=array('turn_bonus_dmg'=>'💥临时攻击','draw'=>'🃏抽牌','sunder'=>'🔨碎甲','sunder_enemy'=>'🔨碎甲','pierce'=>'💘穿透','pierce_enemy'=>'💘穿透直伤','freeze_enemy_monster'=>'❄️冻结','steal_energy'=>'🧲夺能','weaken_enemy'=>'⬇️弱化','poison_enemy'=>'☠️剧毒','rebirth_dmg_enemy'=>'💀亡语：伤','on_summon_gain_hp'=>'📣登场：回血','on_summon_dmg_enemy'=>'📣登场：伤','disarm_enemy'=>'🔒锁臂','disarm_attack'=>'🔒锁臂','vuln_enemy'=>'🔬破绽','mark_vuln_on_hit'=>'🔬命中破绽','on_summon_barrier'=>'📣登场：符盾','enrage_atk'=>'🧹逆鳞','aura_spell_zap'=>'⛩️中继','aura_venom'=>'⛩️淬毒','burst_poison'=>'☠️毒爆×','on_summon_gain_def'=>'📣登场：加防','on_summon_eat_poison'=>'📣吞毒回血×','on_summon_chaos_blessing'=>'📣四神赐福','on_summon_flatten_def'=>'📣二维化');
	if(isset($numbered[$key])) return $numbered[$key].' '.$n.($key==='weaken_enemy'?'/'.$n2:'');
	throw new LogicException('Missing short rule: '.$key);
}

function fcg_card_short_rules($card)
{
	$out=array();
	foreach(fcg_tokens($card['ops']) as $op) $out[]='【'.fcg_short_token($op,$card).'】';
	return preg_replace('/(\d+(?:\.\d+)?)/','<strong class="fcg-num">$1</strong>',fcg_html(implode(' ',$out)));
}

function fcg_token_keywords($op, $card)
{
	// 直接沿用卡面词名，去掉图标、数值和颜色参数，避免筛选另用一套命名。
	$short=fcg_short_token($op,$card);
	preg_match('/[\p{Han}／]+/u',$short,$label);
	$out=array($label[0]);
	if($out[0]==='恢复敌方') $out[0]='恢复';
	if(strpos($op,'aura_')===0) $out[]='光环';
	if(strpos($op,'on_summon_')===0) $out[]='登场';
	if(preg_match('/光环：([\p{Han}]+)/u',$short,$aura)) $out[]=$aura[1];
	if(preg_match('/^(gamble|refactor)\((.*)\)$/',$op,$p) || preg_match('/^(mastery)\([WRYGB],\s*\d+,\s*(.*)\)$/',$op,$p)) {
		foreach(fcg_tokens($p[2]) as $sub) $out=array_merge($out,fcg_token_keywords($sub,$card));
	}
	if(preg_match('/^(combo|resonance)\(([^,]+),\s*(.*)\)$/',$op,$condition)) $out=array_merge($out,fcg_token_keywords($condition[3],$card));
	elseif(preg_match('/^combo_(\d+)_(.*)$/',$op,$condition)) $out=array_merge($out,fcg_token_keywords(preg_replace('/^pierce\(/','pierce_enemy(',$condition[2]),$card));
	return array_values(array_unique($out));
}

function fcg_card_glossary($card)
{
	$out=array();
	foreach(fcg_tokens($card['ops']) as $op) {
		$sub=$card; $sub['ops']=$op;
		$out[]=array('label'=>fcg_short_token($op,$card),'text'=>fcg_card_rules($sub),'keywords'=>fcg_token_keywords($op,$card));
	}
	return $out;
}

function fcg_load_bgm_config()
{
	require GAME_ROOT.'gamedata/ruleset/FCG/cache/audio_1.php';
	return array('tracks'=>array_values($bgm_Query),'force_loop'=>$Force_Loop,'events'=>$event_bgm_list);
}

function fcg_bootstrap($state, $csrf, $shop)
{
	global $titles_list;
	$cards=fcg_cards();
	foreach($cards as &$card) {
		$card['rules']=fcg_card_rules($card); $card['short_rules']=fcg_card_short_rules($card); $card['glossary']=fcg_card_glossary($card);
		$combo_min=null; $res_colors=array();
		foreach(fcg_tokens($card['ops']) as $op) {
			if(preg_match('/^combo\((\d+),/',$op,$p) || preg_match('/^combo_(\d+)_/',$op,$p)) {
				$n=(int)$p[1]; $combo_min=$combo_min===null ? $n : min($combo_min,$n);
			} elseif(preg_match('/^resonance\(([^,]+),/',$op,$p)) $res_colors[]=$p[1];
		}
		$card['combo_min']=$combo_min; $card['res_colors']=array_values(array_unique($res_colors));
		$card['escort']=in_array('escort',fcg_tokens($card['ops']),true);
		$card['refactor']=strpos($card['ops'],'refactor(')!==false;
		unset($card['ops']);
	} unset($card);
	$opponents=fcg_opponents(); foreach($opponents as &$o) unset($o['deck']); unset($o);
	return json_encode(array('state'=>$state,'csrf'=>$csrf,'shop'=>$shop,'cards'=>$cards,'opponents'=>array_values($opponents),
		'quiz_defs'=>fcg_quiz_definitions(),'starter_decks_meta'=>fcg_color_starter_decks(),'bgm'=>fcg_load_bgm_config(),
		'catalog'=>array_values(fcg_catalog()),'draft_tiers'=>fcg_draft_tiers(),'titles'=>(object)$titles_list,'stickers'=>(object)fcg_stickers()),JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR);
}
