<?php
if(!defined('IN_GAME')) exit('Access Denied');

function fcg_card_rules($card)
{
	$colors=array('W'=>'白','R'=>'红','Y'=>'黄','G'=>'绿','B'=>'蓝','ALL'=>'五色');
	$labels=array('atk'=>'攻击','def'=>'防御','gen_max'=>'最高颜色产能'); $out=array();
	foreach(fcg_tokens($card['ops']) as $op) {
		if(strpos($op,'scale_by(')===0) {
			$up=$card['upgraded'];
			$scale=array(27=>'按仍有能量的颜色数 N，攻击、防御各增加 '.($up?'3':'2').'N，生命恢复 '.($up?'6':'5').'N'.($up?'，抽 2 张牌':''),
				46=>'额外造成等于敌方攻击力的伤害',50=>'自身攻击与防御各增加敌方攻、防中的较大值',
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
			case 'gain_hp': $out[]='恢复 '.$n2.' 生命'; break;
			case 'lose_hp': $out[]=($n==='enemy'?'敌方':'自身').'失去 '.$n2.' 生命'; break;
			case 'add_regen': $out[]='每回合回血 +'.$n2; break;
			case 'add_poison': $out[]='敌方每回合回血 −'.$n2; break;
			case 'turn_bonus_dmg': $out[]='本回合自身攻击 +'.$n; break;
			case 'draw': $out[]='抽 '.$n.' 张牌'; break;
			case 'gain_monster_def': $out[]='己方帮手防御 +'.$n2; break;
			case 'gain_monster_hp': $out[]='己方帮手生命 +'.$n2; break;
			case 'lose_monster_hp': $out[]='敌方帮手失去 '.$n2.' 生命'; break;
			case 'freeze_enemy_monster': $out[]='冻结敌方帮手 '.$n.' 回合'; break;
			case 'sunder_enemy': $out[]='敌方及其帮手防御 −'.$n; break;
			case 'pierce_enemy': $out[]='穿透伤害 '.$n.'（无视防御）'; break;
			case 'weaken_enemy': $out[]='敌方攻击 −'.$n.'、防御 −'.$n2; break;
			case 'steal_energy': $out[]='夺取敌方能量最多的颜色 '.$n.' 点'; break;
			case 'steal_stat': $out[]='夺取敌方'.($labels[$n] ?? $n).' '.$n2.' 点'; break;
			case 'lure': $out[]='护卫：替牌手承受攻击，溢出伤害仍伤及牌手'; break;
			case 'kill_monsters': $out[]='优先攻击敌方帮手，溢出伤害践踏牌手'; break;
			case 'growth': $out[]='每回合成长 '.$n.' 攻／'.$n2.' 防／'.$n3.' 生命'; break;
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
			case 'cleanse_flip_self': $out[]='负回血转为正回血，并解除己方帮手冻结'; break;
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
			default: throw new LogicException('Missing FCG rule description: '.$k);
		}
	}
	return implode('；',$out).'。';
}

function fcg_bootstrap($state, $csrf, $shop)
{
	global $titles_list;
	$cards=fcg_cards();
	foreach($cards as &$card) { $card['rules']=fcg_card_rules($card); unset($card['ops']); } unset($card);
	$opponents=fcg_opponents(); foreach($opponents as &$o) unset($o['deck']); unset($o);
	return json_encode(array('state'=>$state,'csrf'=>$csrf,'shop'=>$shop,'cards'=>$cards,'opponents'=>array_values($opponents),
		'catalog'=>array_values(fcg_catalog()),'titles'=>(object)$titles_list),JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR);
}
