<?php
if(!defined('IN_GAME')) exit('Access Denied');

function fcg_card_rules($card)
{
	$colors=array('W'=>'白','R'=>'红','Y'=>'黄','G'=>'绿','B'=>'蓝','ALL'=>'五色');
	$labels=array('atk'=>'攻击','def'=>'防御','gen_max'=>'最高颜色产能'); $out=array();
	foreach(fcg_tokens($card['ops']) as $op) {
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
			case 'add_gen': $out[]=($colors[$n] ?? $n).'产能 +'.$n2; break;
			case 'add_energy': $out[]=$n==='enemy_random' ? '敌方随机一种能量 +'.$n2 : ($colors[$n] ?? $n).'能量 +'.$n2; break;
			case 'gen': $out[]='每回合'.($colors[$n] ?? $n).'能量 '.((float)$n2>=0?'+':'').$n2; break;
			case 'gain_atk': $out[]='自身攻击 +'.$n2; break;
			case 'gain_def': $out[]='自身防御 +'.$n2; break;
			case 'gain_atk_def': $out[]='自身攻击 +'.$n2.'、防御 +'.$n3; break;
			case 'gain_hp': $out[]=($n==='enemy'?'敌方':'自身').'恢复 '.$n2.' 生命'; break;
			case 'lose_hp': $out[]=($n==='enemy'?'敌方':'自身').'失去 '.$n2.' 生命'; break;
			case 'add_regen': $out[]='每回合回血 +'.$n2; break;
			case 'add_poison': $out[]='敌方每回合回血 −'.$n2; break;
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
		'aura_artillery'=>'⛩️光环：炮架','aura_burn_monster_heal'=>'⛩️光环：传火');
	if(isset($labels[$key])) return $labels[$key];
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

function fcg_card_glossary($card)
{
	$out=array();
	foreach(fcg_tokens($card['ops']) as $op) {
		$sub=$card; $sub['ops']=$op;
		$out[]=array('label'=>fcg_short_token($op,$card),'text'=>fcg_card_rules($sub));
	}
	return $out;
}

function fcg_bootstrap($state, $csrf, $shop)
{
	global $titles_list;
	$cards=fcg_cards();
	foreach($cards as &$card) {
		$card['rules']=fcg_card_rules($card); $card['short_rules']=fcg_card_short_rules($card); $card['glossary']=fcg_card_glossary($card);
		$card['escort']=in_array('escort',fcg_tokens($card['ops']),true); unset($card['ops']);
	} unset($card);
	$opponents=fcg_opponents(); foreach($opponents as &$o) unset($o['deck']); unset($o);
	return json_encode(array('state'=>$state,'csrf'=>$csrf,'shop'=>$shop,'cards'=>$cards,'opponents'=>array_values($opponents),
		'catalog'=>array_values(fcg_catalog()),'titles'=>(object)$titles_list,'stickers'=>(object)fcg_stickers()),JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR);
}
