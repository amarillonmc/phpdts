<?php
if(!defined('IN_GAME')) exit('Define IN_GAME before running tests.');
if(!defined('GAME_ROOT')) define('GAME_ROOT',dirname(__DIR__,2).'/');
require_once GAME_ROOT.'include/fcg/account.php';
require_once GAME_ROOT.'include/fcg/presentation.php';
$checks=0;
function fcg_test($ok,$message) { global $checks; $checks++; if(!$ok) throw new RuntimeException($message); }
function fcg_test_throws($fn,$message) { try { $fn(); } catch(InvalidArgumentException $e) { fcg_test(true,$message); return; } fcg_test(false,$message); }
function fcg_test_match() { $m=fcg_new_match(fcg_starter_deck(),0,100); $m['ai_at']=PHP_INT_MAX; return $m; }

$cards=fcg_cards();
fcg_test(fcg_player_avatar(1,'m')==='img/m_1.gif','default account portrait');
fcg_test(fcg_player_avatar('n_1.gif','f')==='img/n_1.gif','custom portrait takes precedence over gender prefix');
fcg_test(fcg_player_avatar(1,'m','ACBRA_2009')==='./gamedata/ruleset/ACBRA_2009/img/m_1.gif','ruleset portrait takes precedence');
fcg_test(fcg_player_avatar(1,'m','FCG')==='img/m_1.gif','FCG without custom avatars falls back to account portrait');
fcg_test(fcg_player_avatar(null,null)===null && fcg_player_avatar('missing-avatar','m')===null,'missing player or image has no broken portrait URL');
// The profile editor must keep the site avatar list in modes without custom portraits.
require_once GAME_ROOT.'include/user.func.php';
$iconlimit=20;$icon=8;$groomid=1;$gtablepre='';
$db=new class {
	public $rule;
	function query($sql) { return $this->rule; }
	function num_rows($result) { return 1; }
	function fetch_array($result) { return array('gruleset'=>$result); }
};
foreach(array('FCG','ELEVATOR','ANOTHER','RAIDCAGEDBIRD','') as $rule) {
	$db->rule=$rule;
	if($rule!=='') fcg_test(get_ruleset_avatar_limits($rule)===false,'disabled custom avatars use default limits: '.$rule);
	foreach(array('m','f') as $gender) {
		$options=get_iconlist();
		fcg_test(count($options)===21 && strpos($options[0],'value=0')!==false && strpos($options[20],'value=20')!==false,'profile offers default range: '.$rule.' '.$gender);
		fcg_test(strpos($options[8],'selected')!==false,'profile keeps selected avatar: '.$rule.' '.$gender);
	}
}
$db->rule='ACBRA_2009';
foreach(array('m','f') as $gender) fcg_test(count(get_iconlist())===43,'enabled custom avatar range preserved: '.$gender);
$db->rule='FCG';$groomid=0;
fcg_test(count(get_iconlist())===21,'main-room profile still offers default avatar range');
unset($db,$iconlimit,$icon,$gender,$groomid,$gtablepre);
fcg_test(count($cards)===310,'310 independent card IDs');
// Keyword filters and the classroom share the card's own labels, including conditional effects.
fcg_test(fcg_token_keywords('resonance(W,draw(1))',$cards[156])===array('余韵','抽牌'),'resonance includes its conditional draw keyword');
fcg_test(fcg_token_keywords('combo_2_pierce(7)',$cards[172])===array('连击','穿透直伤'),'legacy combo uses spell damage keyword');
fcg_test(fcg_token_keywords('combo(3,draw(1))',$cards[173])===array('连击','抽牌'),'combo includes its conditional draw keyword');
fcg_test(fcg_token_keywords('aura_spell_zap(3)',$cards[166])===array('中继','光环'),'named aura included in aura filter');
fcg_test(fcg_token_keywords('aura_burn_monster_heal',$cards[198])===array('光环','传火'),'aura subtype included in reference');
fcg_test(fcg_token_keywords('on_summon_eat_poison(3)',$cards[214])===array('吞毒回血','登场'),'named summon effect included in summon filter');
fcg_test(fcg_token_keywords('gain_hp(enemy,5)',$cards[210])===array('恢复'),'healing keyword independent of target');
$glossary=fcg_card_glossary($cards[156]);
fcg_test($glossary[1]['keywords']===array('余韵','抽牌') && $glossary[1]['text']==='上一张牌为白色时：抽 1 张牌。','reference keeps condition and value with keywords');
foreach(fcg_colors() as $c) fcg_test(count(fcg_pack_pool(array('color'=>$c)))===62,'balanced color '.$c);
foreach(array(array(array('upgraded'=>false,'rare'=>false,'series'=>'vol1'),57),array(array('upgraded'=>false,'series'=>'vol1'),75),array(array('upgraded'=>true,'series'=>'vol1'),75),array(array('rare'=>true,'series'=>'vol1'),36),array(array('series'=>'vol2'),80),array(array('series'=>'vol2','rare'=>true),30)) as $case) fcg_test(count(fcg_pack_pool($case[0]))===$case[1],'pack population');
$catalog=require GAME_ROOT.'gamedata/fcg_shop.php';
foreach($catalog as $item) if(in_array($item[0],array(2,8),true)) {
	$pool=fcg_pack_pool($item[6]);
	fcg_test(count($pool)===109,'rebalanced shop pool keeps existing energy cards');
	foreach($pool as $id) fcg_test(!$cards[$id]['rare'] && $cards[$id]['upgraded']===($item[0]===8),'seed pack excludes rare and respects upgrade tier');
}
fcg_test_throws(function(){fcg_pack_pool(array('color'=>'purple'));},'invalid pool');
fcg_test_throws(function(){fcg_pack_pool(array('count'=>20));},'one card per pack');
$data=fcg_new_account();
fcg_test(fcg_validate_deck($data['deck'],$data['collection'])===$data['deck'],'starter playable');
fcg_test_throws(function()use($data){fcg_validate_deck(array_fill(0,25,149),$data['collection']);},'ownership checked');
fcg_test_throws(function(){fcg_validate_deck(array_merge(array_fill(0,8,24),array_fill(0,17,0)),array(24=>8,0=>17));},'seven card limit');
fcg_test(count(fcg_validate_deck(array_fill(0,25,0),array(0=>25)))===25,'generator exemption');
$data['collection'][24]=7; fcg_test(fcg_add_card($data,24)['overflow'] && $data['embers']===1 && $data['collection'][24]===7,'overflow stamps');
$data['collection'][0]=20; fcg_add_card($data,0); fcg_test($data['collection'][0]===21 && $data['embers']===1,'energy never overflows');

$m=fcg_test_match();
fcg_test(count($m['sides'][0]['hand'])===3 && $m['turn']===1 && $m['deadline']===110,'initial clock and hand');
$m['sides'][0]['hand']=array(0); fcg_play($m,0,0);
fcg_test($m['deadline']===111 && $m['sides'][0]['gen']['W']===2 && $m['sides'][0]['energy']['W']===0,'generator and timer');
fcg_advance($m,110);fcg_test($m['turn']===1,'deadline not premature');
fcg_advance($m,111);fcg_test($m['turn']===2 && $m['deadline']===116 && count($m['sides'][0]['hand'])===1,'turn draws once');
$s=fcg_side(array_fill(0,30,0));$s['hand']=array_fill(0,6,0);fcg_draw($s);fcg_test($s['regen']===-1,'full hand poison');
$s['hand']=array();$s['deck']=array();fcg_draw($s,2);fcg_test($s['regen']===-3,'empty deck poison');
fcg_energy($s,'B',1000);fcg_energy($s,'B',-1000,'gen');fcg_test($s['energy']['B']===99 && $s['gen']['B']===1,'resource bounds');

$m=fcg_test_match();$m['sides'][0]['atk']=7;$m['sides'][0]['monster']=array('id'=>24,'atk'=>9,'def'=>0,'hp'=>10,'maxhp'=>10,'frozen'=>0,'abilities'=>array());$m['sides'][1]['def']=10;
fcg_turn($m);fcg_test($m['sides'][1]['hp']===80,'player and monster independently subtract defense');
$a=fcg_side(array(0,0,0));$b=$a;$b['monster']=array('hp'=>5,'def'=>2,'abilities'=>array('lure'));fcg_packet($a,$b,12,0,false);
fcg_test($b['monster']['hp']===-5 && $b['hp']===75,'guardian trample overflow');
$m=fcg_test_match();foreach($m['sides'] as &$s){$s['hp']=5;$s['atk']=10;}unset($s);fcg_turn($m);fcg_test($m['result']==='draw','simultaneous lethal damage');
$m=fcg_test_match();$m['sides'][0]['hp']=5;$m['sides'][0]['regen']=10;$m['sides'][1]['atk']=10;fcg_turn($m);fcg_test($m['sides'][0]['hp']===5 && $m['result']===null,'simultaneous regen saves player');

foreach($cards as $c) {
	$m=fcg_test_match();foreach($m['sides'] as &$s){$s['energy']=array_fill_keys(fcg_colors(),99);$s['hp']=200;$s['atk']=6;$s['def']=4;}unset($s);
	$m['sides'][0]['hand']=array($c['id']);
	$m['sides'][1]['monster']=array('id'=>24,'atk'=>8,'def'=>2,'hp'=>12,'maxhp'=>12,'frozen'=>0,'abilities'=>array());
	$m['last_spell']=26;
	fcg_play($m,0,0);
	fcg_test($m['deadline']===111,'card play '.$c['id']);
	fcg_test(strpos(fcg_card_rules($c),'。')!==false,'readable card rule '.$c['id']);
	fcg_test(strpos(fcg_card_short_rules($c),'【')!==false && count(fcg_card_glossary($c))>0,'short rules and glossary '.$c['id']);
	if(!$m['result']) fcg_turn($m);
}
$m=fcg_test_match();$m['sides'][0]['energy']=array_fill_keys(fcg_colors(),99);$m['sides'][0]['hand']=array(26,114,115);
fcg_play($m,0,0);fcg_play($m,0,0);fcg_play($m,0,0);
fcg_test($m['sides'][0]['atk']==9 && $m['last_spell']===26,'mirrors keep last real spell without recursion');
$m=fcg_test_match();$m['sides'][0]['energy']['R']=99;$m['sides'][0]['hand']=array(147);
$m['sides'][1]['monster']=array('id'=>24,'atk'=>17,'def'=>0,'hp'=>5,'maxhp'=>5,'frozen'=>0,'abilities'=>array());fcg_play($m,0,0);
fcg_test($m['sides'][0]['atk']===17 && !$m['sides'][1]['monster'],'killed monster snapshot for steal');

$titles_list=array(9101=>'五色余烬',9106=>'具现的暗影');
$user=array('credits2'=>500,'nicksrev'=>'');$data=fcg_new_account();
$pack=array('item_id'=>1,'price_credits2'=>10,'enabled'=>1,'daily_limit'=>1,'total_limit'=>0,'reward_type'=>'fcg_pack','reward_payload'=>'{"upgraded":true}');
fcg_buy($data,$user,$pack,100);fcg_test($user['credits2']===490 && $cards[$data['last_reward']['card']]['upgraded'],'pack debit and grant');
fcg_test_throws(function()use(&$data,&$user,$pack){fcg_buy($data,$user,$pack,101);},'daily quota');
fcg_test($user['credits2']===490,'rejected purchase unchanged');
fcg_buy($data,$user,$pack,86500);fcg_test($user['credits2']===480,'daily reset');
$title=array_replace($pack,array('item_id'=>2,'daily_limit'=>0,'reward_type'=>'title','reward_payload'=>'{"title":9101}'));
fcg_buy($data,$user,$title,86500);fcg_test(fcg_title_owned($user,9101),'title current nicksrev format');
fcg_test_throws(function()use(&$data,&$user,$title){fcg_buy($data,$user,$title,86501);},'no duplicate title charge');
$unknown=array_replace($pack,array('item_id'=>99,'reward_type'=>'custom'));
fcg_test_throws(function()use(&$data,&$user,$unknown){fcg_buy($data,$user,$unknown,86501);},'unsupported delivery rejected');
$data['match']=fcg_test_match();$data['match']['result']='win';$before=array_sum($data['collection']);fcg_settle($data,$user);fcg_settle($data,$user);
fcg_test($data['played']===1 && $data['wins'][0]===1 && array_sum($data['collection'])===$before+1,'settlement exactly once');
fcg_test(in_array($data['match']['reward']['card'],$data['match']['drop_pool'],true),'drop from actual opponent deck');
$public=fcg_public_state($data,$user,true,100);fcg_test(!isset($public['match']['sides'][1]['hand']) && !isset($public['match']['drop_pool']),'opponent secrets hidden');

// All opponent decks are legal; complete automated matches exercise the timer loop until an outcome.
foreach(fcg_opponents() as $o) {
	fcg_validate_deck($o['deck'],array_fill(0,310,100));
	$m=fcg_new_match(fcg_starter_deck(),$o['id'],100);fcg_advance($m,100000);
	fcg_test($m['result']!==null,'offline match terminates '.$o['id']);
}
// Phase 2: exercise outcomes, including blocked plays and turn boundaries.
function fcg_test_ready($hand=array()) {
	$m=fcg_test_match();
	foreach($m['sides'] as &$s) { $s['hp']=200; $s['energy']=array_fill_keys(fcg_colors(),99); $s['hand']=array(); } unset($s);
	$m['sides'][0]['hand']=$hand; return $m;
}
$m=fcg_test_ready(array(172));fcg_play($m,0,0);fcg_test($m['sides'][1]['hp']==193,'first card does not combo');
$m=fcg_test_ready(array(0,172,173));fcg_play($m,0,0);fcg_play($m,0,0);fcg_test($m['sides'][1]['hp']==186,'second card combo pierces');
$before=count($m['sides'][0]['deck']);fcg_play($m,0,0);fcg_test($m['sides'][1]['hp']==167 && count($m['sides'][0]['deck'])===$before-1,'third card combo damage and draw');
fcg_turn($m);fcg_test($m['sides'][0]['played_this_turn']===0 && $m['sides'][0]['last_color']==='R','combo resets, previous color survives turn');
$m=fcg_test_ready(array(156));fcg_play($m,0,0);fcg_test(count($m['sides'][0]['hand'])===0,'resonance does not use own color');
$m=fcg_test_ready(array(0,156));fcg_play($m,0,0);fcg_play($m,0,0);fcg_test(count($m['sides'][0]['hand'])===1,'resonance uses preceding card');
$m=fcg_test_ready(array(172));$m['sides'][0]['energy']['R']=0;fcg_test_throws(function()use(&$m){fcg_play($m,0,0);},'unaffordable card rejected');
fcg_test($m['sides'][0]['played_this_turn']===0 && $m['sides'][0]['last_color']===null,'rejected card does not advance combo');
$m=fcg_test_ready(array(166,168,169));$m['sides'][0]['hp']=100;fcg_play($m,0,0);$old=$m['sides'][0]['monster'];fcg_play($m,0,0);fcg_play($m,0,0);$unit=$m['sides'][0]['monster'];
fcg_test($unit['id']===166 && $unit['atk']==$old['atk']+$cards[168]['atk']+$cards[169]['atk'] && $unit['hp']===$old['hp']+$cards[168]['hp']+$cards[169]['hp'],'escort merges stats without replacing');
fcg_test($m['sides'][0]['hp']==105 && fcg_ability($unit,'lure')===1,'escort preserves abilities and triggers own entry');
$m['sides'][0]['energy']['Y']=0;fcg_turn($m);fcg_test($m['sides'][0]['energy']['Y']===5,'all merged production abilities resolve');
$m=fcg_test_ready(array(198,196));$m['sides'][0]['hp']=100;fcg_play($m,0,0);$old=$m['sides'][0]['monster'];fcg_play($m,0,0);
fcg_test($m['sides'][0]['hp']==100+$cards[196]['hp'] && $m['sides'][0]['monster']['id']===198 && $m['sides'][0]['monster']['maxhp']===$old['maxhp']+2,'burn converts unit without triggering its entry heal');
$m['sides'][0]['monster']['frozen']=1;$m['sides'][0]['hand']=array(196);fcg_test_throws(function()use(&$m){fcg_play($m,0,0);},'frozen burn aura cannot bypass occupied slot');
$m=fcg_test_ready(array(166,0,0));fcg_play($m,0,0);fcg_play($m,0,0);fcg_test($m['sides'][1]['hp']==197,'spell relay includes energy spells');
$m['sides'][0]['monster']['frozen']=1;fcg_play($m,0,0);fcg_test($m['sides'][1]['hp']==197,'frozen relay inactive');
foreach(array(208,209) as $bug) {
	$m=fcg_test_ready(array(0,172));fcg_summon($m,1,$cards[$bug]);$m['sides'][1]['energy']['Y']=0;
	fcg_play($m,0,0);fcg_test($m['sides'][1]['energy']['Y']===0 && count($m['sides'][1]['hand'])===0,'bug ignores energy card');
	fcg_play($m,0,0);fcg_test($bug===208?$m['sides'][1]['energy']['Y']==2:count($m['sides'][1]['hand'])===1,'bug reward uses zero as draw mode');
}
$m=fcg_test_ready();fcg_summon($m,1,$cards[150]);$hp=$m['sides'][1]['monster']['hp'];$m['sides'][0]['atk']=10;$m['sides'][0]['barrier']=1;fcg_turn($m);
fcg_test($m['sides'][1]['hp']==193 && $m['sides'][1]['monster']['hp']===$hp,'barrier bypasses guardian; enemy lock reduces attack');
$m=fcg_test_ready(array(170));fcg_play($m,0,0);$m['sides'][1]['hand']=array(146);fcg_play($m,1,0);
fcg_test($m['sides'][0]['monster']!==null,'stealth immune to targeted kill');$m['sides'][0]['played_this_turn']=3;fcg_turn($m);
fcg_test($m['sides'][1]['hp']==200-2*($cards[170]['atk']+8) && !$m['sides'][0]['monster']['stealth'],'combo and first stealth attack combine once');
$m=fcg_test_ready(array(200));$m['sides'][0]['regen']=-7;fcg_play($m,0,0);fcg_turn($m);
fcg_test(!$m['sides'][0]['monster'] && $m['sides'][0]['regen']===0 && $m['sides'][1]['hp']==194-$cards[200]['atk'],'ephemeral expires, damages and cleans poison');
$m=fcg_test_ready();fcg_summon($m,1,$cards[164]);$m['sides'][1]['monster']['hp']=99;fcg_packet($m['sides'][0],$m['sides'][1],20,0,false);fcg_finish($m);
fcg_test(!$m['sides'][1]['monster'] && $m['sides'][0]['hp']==191,'positive hit detonates ephemeral without excess fake trample');
$m=fcg_test_ready(array(152));$m['sides'][1]['atk']=10;$m['sides'][1]['bonus']=20;fcg_play($m,0,0);fcg_turn($m);
fcg_test($m['sides'][0]['hp']==196 && $m['sides'][1]['disarm']===0,'lock removes temporary attack and expires after reduced attack');
$m=fcg_test_ready();$m['sides'][1]['vuln']=6;$m['sides'][1]['def']=99;
fcg_packet($m['sides'][0],$m['sides'][1],5,0,false);fcg_packet($m['sides'][0],$m['sides'][1],5,0,false);
fcg_test($m['sides'][1]['hp']==194 && $m['sides'][1]['vuln']===0,'vulnerability consumed by one attack packet even against armor');
$m=fcg_test_ready();fcg_summon($m,1,$cards[204]);$m['sides'][1]['monster']['hp']=100;fcg_packet($m['sides'][0],$m['sides'][1],20,0,false,'R');
fcg_test($m['sides'][1]['monster']['hp']===100-2*(20-$cards[204]['def']),'red weakness doubles post armor damage');
$m=fcg_test_ready(array(176,202));$m['sides'][0]['hp']=5;fcg_play($m,0,0);fcg_hurt($m['sides'][0],7);fcg_test($m['sides'][0]['hp']===5 && $m['sides'][0]['barrier']==1,'barrier absorbs damage');
fcg_play($m,0,0);fcg_hurt($m['sides'][0],100);$m['sides'][0]['regen']=-10;fcg_turn($m);fcg_test($m['result']===null && $m['sides'][0]['hp']===1 && $m['sides'][0]['endure']===0,'endure covers hits and poison then expires');fcg_hurt($m['sides'][0],1);fcg_finish($m);fcg_test($m['result']==='loss','endure not permanent');
$m=fcg_test_ready();$m['sides'][0]['barrier']=8;$m['sides'][0]['regen']=-5;fcg_turn($m);
fcg_test($m['sides'][0]['hp']===200 && $m['sides'][0]['barrier']===3,'barrier absorbs negative regeneration damage');fcg_turn($m);
fcg_test($m['sides'][0]['hp']===198 && $m['sides'][0]['barrier']===0,'poison consumes remaining barrier before health');
$m=fcg_test_ready(array(162));fcg_play($m,0,0);fcg_turn($m);fcg_test(empty($m['sides'][0]['monster']['promoted']),'promotion waits two turns');fcg_turn($m);
$atk=$m['sides'][0]['monster']['atk'];fcg_test($atk==$cards[162]['atk']+4 && $m['sides'][0]['monster']['promoted'],'promotion adds stats once');fcg_turn($m);fcg_test($m['sides'][0]['monster']['atk']===$atk,'no repeated promotion stats');
$m=fcg_test_ready(array(162));$m['sides'][0]['hp']=40;fcg_play($m,0,0);$m['sides'][0]['monster']['frozen']=1;fcg_turn($m);fcg_test(empty($m['sides'][0]['monster']['promoted']),'frozen promotion waits');fcg_turn($m);fcg_test($m['sides'][0]['monster']['promoted'],'low health promotion');
$m=fcg_test_ready(array(182,218));fcg_play($m,0,0);$old=$m['sides'][0]['monster'];fcg_play($m,0,0);fcg_test($m['sides'][0]['monster']['atk']==$old['atk']+$cards[218]['atk']+(int)floor($old['atk']/2),'clone copies pre-escort stats');
fcg_packet($m['sides'][1],$m['sides'][0],$m['sides'][0]['monster']['def']+1,0,false);fcg_test($m['sides'][0]['monster']['atk']==$old['atk']+$cards[218]['atk']+(int)floor($old['atk']/2)+3,'enrage survives hit');
$m=fcg_test_ready(array(214));$m['sides'][0]['hp']=100;$m['sides'][0]['regen']=-4;fcg_play($m,0,0);fcg_test($m['sides'][0]['hp']==112 && $m['sides'][0]['regen']===0,'eat poison heals and cleans');
$m=fcg_test_ready(array(226,228));fcg_play($m,0,0);fcg_test($m['sides'][0]['atk']==2 && $m['sides'][1]['regen']==-2,'chaos blessing affects both');$m['sides'][0]['monster']=null;$m['sides'][1]['def']=20;fcg_play($m,0,0);fcg_test($m['sides'][1]['def']===2,'flatten defense on entry');
$m=fcg_test_ready(array(0,0,0,0,0,0));$m['live']=true;for($i=0;$i<6;$i++)fcg_play($m,0,0);fcg_test($m['side_deadlines']===array(114,110),'live plays extend only the actor clock, capped at four');fcg_turn($m,114);fcg_test($m['deadline']===119 && $m['side_extensions']===array(0,0),'new turn resets extension');
// Keep this timer assertion independent of random cards drawn before the AI takes over.
$m=fcg_test_ready();$m['live']=true;$m['seen']=array(100,100);$m['sides'][0]['hand']=array(0);$m['sides'][0]['deck']=$m['sides'][1]['deck']=array();fcg_live_advance($m,120);
fcg_test($m['sides'][0]['gen']['W']===2 && $m['deadline']>120,'offline player AI resumes at boundary');
$m=fcg_test_ready(array(0));fcg_test_throws(function()use(&$m){fcg_duel_action($m,0,array('action'=>'play','slot'=>0,'card'=>0,'turn'=>2),100);},'old turn cannot play a new slot');
fcg_duel_action($m,0,array('action'=>'sticker','sticker'=>1),100);fcg_test_throws(function()use(&$m){fcg_duel_action($m,0,array('action'=>'sticker','sticker'=>2),102);},'sticker cooldown');
fcg_duel_action($m,0,array('action'=>'sticker','sticker'=>2),103);fcg_test($m['stickers'][0]['id']===2,'sticker cooldown release');

$data=fcg_new_account();$user=array('credits2'=>1000,'nicksrev'=>'','roomid'=>'0');$pack['daily_limit']=0;$pack['reward_payload']='{"series":"vol2"}';
fcg_buy($data,$user,$pack,100,5);fcg_test($user['credits2']===955 && count($data['last_reward']['cards'])===5,'five pack discount');
fcg_buy($data,$user,$pack,100,10);fcg_test($user['credits2']===875 && count($data['last_reward']['cards'])===10,'ten pack discount');
$pack['daily_limit']=20;fcg_test_throws(function()use(&$data,&$user,$pack){fcg_buy($data,$user,$pack,100,10);},'batch quota cannot exceed remaining');
$pack['price_credits2']=0;fcg_buy($data,$user,$pack,100,'remaining');fcg_test(count($data['last_reward']['cards'])===5 && $data['purchases'][1]['daily']===20,'free remaining claimed in one batch');
$before=$data;fcg_test_throws(function()use(&$data,&$user,$pack){fcg_buy($data,$user,$pack,100,0);},'invalid quantity');fcg_test($data===$before,'rejected purchase unchanged');
$data=fcg_new_account();$permanent=$data['collection'];$key='0:1:100';
foreach(array(2,2,3,4,5) as $uid)fcg_pvp_reward($data,$user,$uid,array(150,151,0),100,$key);
fcg_test($data['embers']===6 && $data['pvp_wins']===5 && $data['collection']===$permanent && $data['temp_cards']['cards'][151]===5,'unique daily rewards and priority temporary upgraded rare');
$data['deck'][]=151;$data['decks'][0]['cards']=$data['deck'];fcg_test(count(fcg_validate_deck($data['deck'],fcg_collection($data)))===31,'temporary card available to deck');
fcg_expire_temp($data,$user,101,$key);fcg_test(count($data['deck'])===31,'room zero temporary card remains valid');
fcg_expire_temp($data,$user,86500,$key);fcg_test(empty($data['temp_cards']) && count($data['deck'])===30 && $data['collection']===$permanent,'next day prunes borrowed card without permanent loss');
fcg_pvp_reward($data,$user,2,array(151),86500,$key);fcg_test($data['embers']===8,'daily reward resets');fcg_expire_temp($data,$user,86501,'0:2:200');fcg_test(empty($data['temp_cards']),'new room instance expires borrowed card');
$data['pvp_wins']=9;fcg_pvp_reward($data,$user,2,array(151),86500,$key);fcg_test(fcg_title_owned($user,9121),'ten wins cosmetic title');$data['pvp_wins']=49;fcg_pvp_reward($data,$user,2,array(151),86500,$key);fcg_test(fcg_title_owned($user,9122),'fifty wins cosmetic title');
$data=fcg_new_account();$user['nicksrev']='';foreach(range(4,20) as $i)$data['wins'][$i]=1;fcg_settle($data,$user);fcg_test(!fcg_title_owned($user,9106),'new opponents do not substitute for initial seventeen');foreach(range(0,16) as $i)$data['wins'][$i]=1;fcg_settle($data,$user);fcg_test(fcg_title_owned($user,9106),'old seventeen achievement unchanged');
// Phase 3: HARD is a server-validated variant; normal decks and rewards remain available.
$opponents=fcg_opponents(); $npc_decks=fcg_npc_decks();
fcg_test(count($opponents)===26 && count($npc_decks['hard'])===20,'26 opponents and exactly 20 hard decks');
foreach(array_merge(range(5,20),range(22,25)) as $id) {
	$o=$opponents[$id]; $hard=fcg_new_match(fcg_starter_deck(),$id,100,true); $normal=fcg_new_match(fcg_starter_deck(),$id,100);
	fcg_test(count($npc_decks['hard'][$id])===32,'hard has 32 cards '.$id);
	fcg_validate_deck($npc_decks['hard'][$id],array_fill(0,310,100));
	$actual=array_merge($hard['sides'][1]['hand'],$hard['sides'][1]['deck']); sort($actual); $expected=$npc_decks['hard'][$id]; sort($expected);
	fcg_test($actual===$expected && $hard['sides'][1]['hp']===$o['hp']+25 && $o['hard_level']===$o['level']+2,'hard deck and stats '.$id);
	fcg_test($normal['drop_pool']===$o['deck'] && $normal['sides'][1]['hp']===$o['hp'],'normal unchanged '.$id);
	$signature=array_filter(array_unique($o['deck']),function($cid)use($cards){return !$cards[$cid]['unlimited'];});
	$drops=array_count_values($hard['drop_pool']);
	foreach($drops as $cid=>$n) fcg_test(!$cards[$cid]['unlimited'] && (in_array($cid,$signature,true) || (in_array($cid,$npc_decks['hard'][$id],true) && ($cards[$cid]['upgraded'] || $cards[$cid]['rare']))),'hard drop eligibility '.$id.':'.$cid);
	foreach($signature as $cid) fcg_test($drops[$cid]===($cards[$cid]['upgraded']?2:1),'signature weight '.$id.':'.$cid);
	$old_rate=count(array_filter($o['deck'],function($cid)use($signature){return in_array($cid,$signature,true);}))/count($o['deck']);
	$new_rate=array_sum(array_intersect_key($drops,array_flip($signature)))/count($hard['drop_pool']);
	fcg_test($new_rate>=$old_rate,'signature drops not diluted '.$id);
	fcg_advance($hard,100000); fcg_test($hard['result']!==null,'hard match terminates '.$id);
}
foreach(array(0,4,21,26) as $id) fcg_test_throws(function()use($id){fcg_new_match(fcg_starter_deck(),$id,100,true);},'unsupported hard opponent '.$id);
fcg_test_throws(function(){fcg_new_match(fcg_starter_deck(),5,100,'1');},'hard must be boolean');
$data=fcg_new_account(); $user=array('nicksrev'=>'','credits2'=>0);
fcg_test_throws(function()use(&$data,&$user){fcg_action($data,$user,array('action'=>'start','opponent'=>5,'hard'=>true),100,false);},'hard requires FCG room');
fcg_action($data,$user,array('action'=>'start','opponent'=>5,'hard'=>true),100,true);
fcg_test($data['match']['hard'],'request selects hard');
$data['match']['result']='win'; fcg_settle($data,$user); $once=$data; fcg_settle($data,$user);
fcg_test($data===$once && $data['hard_wins'][5]===1 && $data['wins'][5]===1 && $data['played']===1,'hard reward and wins exactly once');
$reloaded=fcg_load_account(fcg_json($data)); fcg_settle($reloaded,$user); fcg_test($reloaded['hard_wins'][5]===1,'hard win survives reload');
fcg_test(isset(fcg_public_state($data,$user,true,100)['hard_wins']->{5}),'hard wins public');
unset($data['hard_wins']); fcg_test(fcg_load_account(fcg_json($data))['hard_wins']===array(),'old accounts default hard record');
foreach(array(false,true) as $hard) {
	$m=fcg_new_match(fcg_starter_deck(),5,100,$hard); $m['sides'][1]['hand']=array(1,1,1,1); $m['sides'][1]['deck']=array();
	fcg_advance($m,103); fcg_test($m['sides'][1]['gen']['W']===($hard?4:2),'AI cadence '.($hard?'hard':'normal'));
}

function fcg_test_evo_deck($deck,$color) {
	global $cards;
	fcg_test(count($deck)===32,'adaptive size '.$color); $tiers=array_fill(0,4,0);
	foreach(array_count_values($deck) as $id=>$n) {
		fcg_test(isset($cards[$id]) && $cards[$id]['color']===$color,'adaptive same color '.$color.':'.$id);
		fcg_test($cards[$id]['unlimited'] || $n<=($cards[$id]['rare']?2:4),'adaptive copy cap '.$id);
		$tiers[fcg_evo_tier($cards[$id])]+=$n;
	}
	fcg_test($tiers[0]>=9 && $tiers[0]<=12 && $tiers[1]>=10 && $tiers[1]<=12 && $tiers[2]>=6 && $tiers[2]<=8 && $tiers[3]>=2 && $tiers[3]<=4,'adaptive curve '.$color);
	return $tiers;
}
function fcg_test_evo_match($hand=array()) {
	$m=fcg_new_match(fcg_starter_deck(),21,100); $m['ai_color']='W';
	foreach(array(0,1) as $side) { $m['sides'][$side]=fcg_side(array()); $m['sides'][$side]['hp']=200; $m['sides'][$side]['hand']=$side?array():$hand; }
	foreach($m['telemetry'] as &$t) { foreach($t as &$v) $v=is_array($v)?array():0; unset($v); } unset($t);
	return $m;
}
$evo=fcg_evo_initial_state();
$evo_curves=array('W'=>array(10,10,8,4),'R'=>array(10,12,6,4),'Y'=>array(10,12,6,4),'G'=>array(10,12,6,4),'B'=>array(10,12,6,4));
foreach(fcg_colors() as $color) fcg_test(fcg_test_evo_deck($evo['decks'][$color],$color)===$evo_curves[$color],'cold curve '.$color);
$m=fcg_new_match(fcg_starter_deck(),21,100,false,$evo); $chosen=$m['ai_color'];
$actual=array_merge($m['sides'][1]['hand'],$m['sides'][1]['deck']);sort($actual);$expected=$evo['decks'][$chosen];sort($expected);
fcg_test($actual===$expected && $m['sides'][1]['hp']===120 && $m['ai_gen']===1 && count($m['telemetry'])===2,'adaptive opening takes chosen generation');
fcg_test(strpos(implode('',$m['log']),'初始平衡构筑')!==false,'opening explains last changes');
$data=fcg_new_account();$data['match']=$m;$public=fcg_public_state($data,$user,true,100);
fcg_test(!isset($public['match']['telemetry'],$public['match']['sides'][1]['hand'],$public['match']['drop_pool']),'telemetry does not expose enemy hand');

$m=fcg_test_evo_match(array(33,33));fcg_turn($m);
fcg_test($m['telemetry'][0]['stalled_turns'][33]===1 && $m['telemetry'][0]['energy_starved']===1 && $m['telemetry'][0]['hand_starved']===0,'duplicates count as one stalled card-turn');
fcg_test($m['telemetry'][0]['seen_turns'][33]===2 && $m['telemetry'][1]['hand_starved']===1,'new turn observes retained hand and empty opponent');
$m=fcg_test_evo_match(array(95));$m['sides'][0]['free'][0]=true;fcg_turn($m);
fcg_test($m['telemetry'][0]['energy_starved']===0 && $m['telemetry'][0]['hand_starved']===1,'free search card not energy starved');
$m=fcg_test_evo_match(array(24));$m['sides'][0]['energy']['W']=99;fcg_summon($m,0,$cards[24]);fcg_turn($m);
fcg_test($m['telemetry'][0]['energy_starved']===0,'occupied monster slot not misreported as energy shortage');
$m=fcg_test_evo_match(array(33));fcg_test_throws(function()use(&$m){fcg_play($m,0,0);},'failed play not telemetry');
fcg_test(empty($m['telemetry'][0]['played']),'rejected play count zero');
$m['sides'][0]['energy']['R']=99;fcg_play($m,0,0);
fcg_test($m['telemetry'][0]['played'][33]===1 && $m['telemetry'][0]['seen_turns'][33]===1,'successful play seen once');
$m['sides'][0]['hand']=array(33);fcg_play($m,0,0);
fcg_test($m['telemetry'][0]['played'][33]===2 && $m['telemetry'][0]['seen_turns'][33]===1,'multiple plays one card-turn');
$m['turn']=3;$m['result']='win';$evo=fcg_evo_initial_state();fcg_evo_record_and_optimize($evo,$m);
fcg_test($evo['card_stats'][33]['plr_plays']===2 && $evo['card_stats'][33]['plr_played_games']===1 && $evo['card_stats'][33]['plr_played_wins']===1 && $evo['deck_stats']['W']['matches']===1,'red player card learned during white NPC match');
$m['turn']=2;$before=$evo;fcg_test(!fcg_evo_record_and_optimize($evo,$m) && $evo===$before,'short surrender ignored');
$m['turn']=3;$m['result']=null;fcg_test(!fcg_evo_record_and_optimize($evo,$m),'unfinished match ignored');
$m['result']='draw';fcg_evo_record_and_optimize($evo,$m);fcg_test($evo['card_stats'][33]['plr_played_games']===2 && $evo['card_stats'][33]['plr_played_wins']===1,'draw is sample without win credit');

$m=fcg_test_evo_match(array(1,191));$m['sides'][0]['energy']['B']=99;fcg_play($m,0,0);fcg_play($m,0,0);$m['turn']=3;$m['result']='win';$evo=fcg_evo_initial_state();fcg_evo_record_and_optimize($evo,$m);
fcg_test($evo['card_stats'][191]['plr_plays']===1 && empty($evo['card_stats'][191]['plr_played_games']),'triggered off-color resonance excluded from mono win evidence');
$without=$cards[191];$without['ops']='gain_hp(self, 16); clean_self_poison';fcg_test(fcg_card_prior_score($without)===fcg_card_prior_score($cards[191]),'off-color resonance omitted from prior');
$evo=fcg_evo_initial_state();$evo['card_stats'][33]=array('npc_played_games'=>8,'npc_played_wins'=>6,'plr_played_games'=>8,'plr_played_wins'=>5,'npc_seen'=>10,'npc_stalled'=>2,'plr_seen'=>4,'plr_stalled'=>2);
$expected=0.6*70+0.25*60+0.15*fcg_card_prior_score($cards[33])-3/12*35;
fcg_test(abs(fcg_evo_card_score($evo,33)-$expected)<0.00001,'smoothed 60/25/15 score and stall penalty');
$evo=fcg_evo_initial_state();$evo['card_stats'][24]=array('npc_played_games'=>100,'npc_played_wins'=>0,'npc_seen'=>100,'npc_stalled'=>100);
fcg_evolve_npc_deck($evo,'W');fcg_test(array_count_values($evo['decks']['W'])[24]===1,'two worst non-energy copies replaced in their fee band');
fcg_test(fcg_test_evo_deck($evo['decks']['W'],'W')===$evo_curves['W'],'slot replacement preserves curve');
$evo=fcg_evo_initial_state();$before=fcg_evo_card_score($evo,33);$evo['card_stats'][33]=array('plr_played_games'=>20,'plr_played_wins'=>20);
fcg_test(fcg_evo_card_score($evo,33)>$before,'player wins increase candidate score for its own color');

$evo=fcg_evo_initial_state();$m=fcg_test_evo_match(array(1));fcg_play($m,0,0);$m['turn']=3;$m['result']='loss';
for($i=0;$i<11;$i++) fcg_evo_record_and_optimize($evo,$m);
fcg_test($evo['gen']['W']===1 && $evo['deck_stats']['W']['matches']===11,'no early generation');
fcg_evo_record_and_optimize($evo,$m);
fcg_test($evo['gen']['W']===2 && $evo['deck_stats']['W']['matches']===0 && $evo['deck_stats']['W']['total_matches']===12 && abs($evo['card_stats'][1]['plr_plays']-10.2)<0.00001,'twelfth match advances and decays');
fcg_test($evo['gen']['R']===1 && $evo['decks']['R']===$npc_decks['adaptive']['R'],'other color not evolved');

foreach(fcg_colors() as $color) {
	$evo=fcg_evo_initial_state();
	// Repeated shortage and empty-hand generations must keep curve and copy limits bounded.
	foreach(array(true,true,true,false,false,false,false) as $starved) {
		$evo['deck_stats'][$color]['total_turns']=100;
		$evo['deck_stats'][$color]['energy_starved']=$starved?30:0;
		$evo['deck_stats'][$color]['hand_starved']=$starved?0:50;
		$before=$evo['decks'][$color];fcg_evolve_npc_deck($evo,$color);$tiers=fcg_test_evo_deck($evo['decks'][$color],$color);
		fcg_test(count(array_diff_assoc($before,$evo['decks'][$color]))<=3,'at most one curve change plus two replacements');
	}
	fcg_test($tiers[0]===9,'redundant energy lower bound '.$color);
}
$data=fcg_new_account();$data['match']=$m;$context=array('raw'=>'','state'=>fcg_evo_initial_state());fcg_settle($data,$user,100,'',$context);$once=$context;
fcg_settle($data,$user,100,'',$context);fcg_test($context===$once,'settlement learns exactly once in memory');

// Draft expedition: phantom cards, deterministic five-seat passing and account-owned progress.
function fcg_test_draft_action(&$data,&$user,$action,$more=array(),$in_room=true) {
	fcg_action($data,$user,array_merge(array('action'=>$action,'run'=>$data['run']['id'] ?? null,'layer'=>$data['run']['layer'] ?? null,'removed'=>$data['run']['node']['removed'] ?? null),$more),100,$in_room);
}
function fcg_test_finish_draft(&$data,&$user) {
	while($data['run']['stage']==='draft') {
		$pack=fcg_draft_pack($data['run']);
		fcg_test_draft_action($data,$user,'draft_pick',array('slot'=>0,'card'=>$pack[0],'pick'=>count($data['run']['picks'])));
	}
}
$tiers=fcg_draft_tiers();fcg_test($tiers['entry']===250,'expedition entry costs 250');
foreach(array(array(0,0,0,1),array(1,0,0,1),array(1,1,0,2),array(2,1,0,3),array(3,0,1,4),array(2,1,1,5)) as $wins=>$tier) {
	fcg_test(array_values($tiers[$wins])===$tier,'configured deck/upgrade/rare/stamp tier '.$wins);
}
$data=fcg_new_account();$user=array('uid'=>1,'nicksrev'=>'','credits2'=>249);$before=$data;
fcg_test_throws(function()use(&$data,&$user){fcg_test_draft_action($data,$user,'draft_enter');},'entry requires 250 credits');
fcg_test($data===$before && $user['credits2']===249,'failed entry leaves account untouched');
$boundary=$data;$payer=$user;$payer['credits2']=250;fcg_test_draft_action($boundary,$payer,'draft_enter');
fcg_test($payer['credits2']===0 && $boundary['run']['entry_cost']===250,'exactly 250 credits admits entry and remembers payment');
$user['credits2']=1000;
fcg_test_throws(function()use(&$data,&$user){fcg_test_draft_action($data,$user,'draft_enter',array(),false);},'entry requires room');
$data['match']=fcg_test_match();
fcg_test_throws(function()use(&$data,&$user){fcg_test_draft_action($data,$user,'draft_enter');},'entry requires finished match');
$data['match']=null;fcg_test_draft_action($data,$user,'draft_enter');$data['run']['seed']=4729;
fcg_test($user['credits2']===750 && $data['run']['hp']===80 && $data['run']['stage']==='draft','entry starts at 80 HP and charges once');
fcg_test_throws(function()use(&$data,&$user){fcg_test_draft_action($data,$user,'draft_enter');},'second entry rejected');
fcg_test_throws(function()use(&$data,&$user){fcg_action($data,$user,array('action'=>'start','opponent'=>5),100,true);},'normal match cannot replace expedition');
$social=array('match'=>null);$room=array('gruleset'=>'FCG');
foreach(array('mirror','invite','accept') as $action) fcg_test_throws(function()use(&$social,&$data,&$user,$room,$action){fcg_social_action($social,$data,$user,array('action'=>$action),$room,100);},'social cannot replace run '.$action);
$permanent=$data['collection'];$ordinary_deck=$data['deck'];$initial=$data;
mt_srand(4729,MT_RAND_MT19937);$packs=fcg_draft_deal();mt_srand(4729,MT_RAND_MT19937);fcg_test($packs===fcg_draft_deal(),'seeded 24-pack generation replays');mt_srand();
fcg_test(count($packs)===24,'three circles of eight packs');
foreach($packs as $pack) {
	fcg_test(count($pack)===5 && !$cards[$pack[0]]['unlimited'],'five cards including weighted slot');
	foreach(array_slice($pack,1) as $id) fcg_test(!$cards[$id]['unlimited'] && !$cards[$id]['upgraded'],'four unupgraded non-energy slots');
}
$lengths=array(5,1,2,3,4,5,1,2,5,4,3,2,1,5,4,3,5,1,2,3,4,5,1,2);
for($n=0;$n<24;$n++) {
	$public=fcg_public_state($data,$user,true,100)['run'];$pack=$public['pack'];
	fcg_test(!isset($public['seed'],$public['packs']) && count($pack)===$lengths[$n],'only current remainder exposed, alternating pass '.$n);
	$loaded=fcg_load_account(fcg_json($data));fcg_test(fcg_draft_pack($loaded['run'])===$pack,'reloaded bot circle is stable '.$n);
	$slot=$n%count($pack);$input=array('slot'=>$slot,'card'=>$pack[$slot],'pick'=>$n);
	if($n===0) {
		foreach(array(array('slot'=>5),array('slot'=>'0'),array('card'=>-1),array('pick'=>1),array('run'=>'stale')) as $bad) fcg_test_throws(function()use(&$data,&$user,$input,$bad){fcg_test_draft_action($data,$user,'draft_pick',array_merge($input,$bad));},'bad draft choice rejected');
		fcg_test_throws(function()use(&$data,&$user,$input){fcg_test_draft_action($data,$user,'draft_pick',$input,false);},'selection requires room');
	}
	fcg_test_draft_action($data,$user,'draft_pick',$input);
	fcg_test_throws(function()use(&$data,&$user,$input){fcg_test_draft_action($data,$user,'draft_pick',$input);},'same pack cannot be picked twice');
}
fcg_test(count($data['run']['deck'])===32 && count($data['run']['picks'])===24 && $data['run']['stage']==='battle','24 selections plus eight energy prepare first battle directly');
fcg_test($data['collection']===$permanent && $data['deck']===$ordinary_deck,'draft does not alter collection or regular deck');
$ready=$data;
foreach(array(array(array_fill(0,24,24),array('W'=>8)),array(array_merge(array_fill(0,18,30),array_fill(0,6,42)),array('R'=>4,'G'=>4)),array(array_merge(array_fill(0,12,30),array_fill(0,8,42),array_fill(0,4,48)),array('R'=>3,'G'=>3,'B'=>2))) as $case) {
	$base=fcg_draft_energy($case[0]);$colors=array();
	foreach($base as $id) {fcg_test($cards[$id]['unlimited'],'allocated energy is unlimited');$c=$cards[$id]['color'];$colors[$c]=($colors[$c] ?? 0)+1;}
	fcg_test(count($base)===8 && $colors===$case[1],'eight energy follow mono/dual/three-color split');
}
fcg_test(array_count_values(fcg_draft_energy(array_fill(0,24,24)))===array(1=>2,3=>2,0=>4),'mono gets upgraded pairs and ordinary generator filler');

// The first battle uses the drafted deck directly; there is no pre-battle camp.
$data=$ready;$data['run']['hp']=53;
fcg_test_throws(function()use(&$data,&$user){fcg_test_draft_action($data,$user,'draft_battle',array('layer'=>0));},'old battle confirmation rejected');
fcg_test_throws(function()use(&$data,&$user){fcg_test_draft_action($data,$user,'draft_node',array('choice'=>'supply'));},'camp no longer available');
fcg_test_draft_action($data,$user,'draft_battle');$first=$data['match']['opponent'];
fcg_test($first<=4 && $data['match']['sides'][0]['hp']===53 && $data['match']['sides'][1]['hp']===$opponents[$first]['hp'],'first layer starts with inherited HP');
fcg_test_throws(function()use(&$data,&$user){fcg_test_draft_action($data,$user,'draft_battle');},'cannot restart active layer');
$m=&$data['match'];$heal=$cards[26];$heal['ops']='gain_hp(self,100)';fcg_effect($m,0,$heal);
fcg_test($m['sides'][0]['hp']===80,'expedition healing capped at run maximum');
$m['sides'][0]['regen']=50;fcg_turn($m);fcg_test($m['sides'][0]['hp']===80,'expedition regeneration capped');
$m['sides'][0]['hp']=27;$m['sides'][0]['atk']=30;$m['result']='win';unset($m);
$before_collection=$data['collection'];$before_wins=$data['wins'];$before_embers=$data['embers'];fcg_settle($data,$user);$settled=$data;fcg_settle($data,$user);
fcg_test($data===$settled && $data['run']['wins']===1 && $data['run']['hp']===85 && $data['run']['mhp']===85 && $data['run']['node']['type']==='victory' && $data['run']['node']['removed']===0,'victory grows maximum, heals fully and offers one reward node exactly once');
fcg_test($data['collection']===$before_collection && $data['wins']===$before_wins && $data['embers']===$before_embers && !$data['hard_wins'] && !fcg_title_owned($user,9106),'layer does not grant normal drops, records or title');
fcg_test(count($data['run']['node']['opts'])===3 && count(array_unique($data['run']['node']['opts']))===3 && !array_diff($data['run']['node']['opts'],$data['match']['drop_pool']),'three distinct reinforcements from actual opponent deck');
foreach($data['run']['node']['opts'] as $id) fcg_test(!$cards[$id]['unlimited'],'reinforcement excludes energy');
fcg_test(fcg_load_account(fcg_json($data))['run']===$data['run'],'victory options survive reload without reroll');
$victory=$data;$victory['run']['deck']=array_merge(array_fill(0,24,24),array_fill(0,8,1));
foreach(array('camp','reinforce') as $type) {
	$legacy=$type==='camp'?$ready:$victory;
	$legacy['run']['stage']='node';$legacy['run']['hp']=40;
	$legacy['run']['node']=$type==='camp'?array('type'=>'camp','upgrades_left'=>1):array('type'=>'reinforce','opts'=>array(24,26,28));
	$legacy['run']['deck'][0]=25;$before=$legacy;
	fcg_action($legacy,$user,array('action'=>'status'),100,true);
	fcg_test($legacy['run']['deck']===$before['run']['deck'] && $legacy['run']['wins']===$before['run']['wins'] && $legacy['collection']===$before['collection'],'old node transition preserves earned progress '.$type);
	if($type==='camp') fcg_test($legacy['run']['stage']==='battle' && $legacy['run']['layer']===$before['run']['layer'] && $legacy['run']['hp']===40,'old camp prepares same layer without another reward');
	else fcg_test($legacy['run']['node']===array('type'=>'victory','removed'=>0,'opts'=>array(24,26,28)) && $legacy['run']['hp']===$legacy['run']['mhp'],'old reinforcement becomes full-health victory choice without reroll');
	$once=$legacy;$legacy=fcg_load_account(fcg_json($legacy));fcg_action($legacy,$user,array('action'=>'status'),100,true);
	fcg_test($legacy['run']===$once['run'],'old node transition is stable after saving and reloading '.$type);
}
foreach(array('supply','rest','reinforce','unknown') as $choice) fcg_test_throws(function()use(&$data,&$user,$choice){fcg_test_draft_action($data,$user,'draft_node',array('choice'=>$choice));},'invalid or removed camp action rejected '.$choice);
foreach(array('remove','upgrade','pick') as $choice) {
	$data=$victory;$card=$choice==='pick'?$data['run']['node']['opts'][0]:24;
	$input=array('choice'=>$choice,'slot'=>0,'card'=>$card,'removed'=>0);
	foreach(array(array('slot'=>-1),array('slot'=>99),array('slot'=>'0'),array('card'=>-1),array('card'=>(string)$card),array('layer'=>0),array('run'=>'stale')) as $bad) {
		fcg_test_throws(function()use(&$data,&$user,$input,$bad){fcg_test_draft_action($data,$user,'draft_node',array_merge($input,$bad));},'invalid victory target '.$choice);
		fcg_test($data===$victory,'invalid choice leaves run untouched '.$choice);
	}
	fcg_test_throws(function()use(&$data,&$user,$input){fcg_test_draft_action($data,$user,'draft_node',$input,false);},'victory choice requires room '.$choice);
}
foreach(array('remove','upgrade') as $choice) {
	$data=$victory;
	fcg_test_throws(function()use(&$data,&$user,$choice){fcg_test_draft_action($data,$user,'draft_node',array('choice'=>$choice,'slot'=>24,'card'=>1));},'energy protected '.$choice);
}
$data=$victory;$data['run']['deck'][0]=25;
fcg_test_throws(function()use(&$data,&$user){fcg_test_draft_action($data,$user,'draft_node',array('choice'=>'upgrade','slot'=>0,'card'=>25));},'already upgraded card rejected');
$data=$victory;$input=array('choice'=>'upgrade','slot'=>0,'card'=>24);
fcg_test_draft_action($data,$user,'draft_node',$input);$once=$data;
fcg_test($data['run']['deck'][0]===25 && $data['run']['stage']==='battle' && $data['run']['layer']===2 && $data['collection']===$permanent,'single upgrade completes reward and changes phantom only');
fcg_test_throws(function()use(&$data,&$user,$input){fcg_test_draft_action($data,$user,'draft_node',$input);},'upgrade cannot be repeated after advancement');
fcg_test($data===$once,'repeated upgrade has no effect');
$data=$victory;$input=array('choice'=>'remove','slot'=>0,'card'=>24,'removed'=>0);
foreach(array(null,'0',1,2) as $progress) fcg_test_throws(function()use(&$data,&$user,$input,$progress){fcg_test_draft_action($data,$user,'draft_node',array_merge($input,array('removed'=>$progress)));},'removal progress checked before mutation');
fcg_test_draft_action($data,$user,'draft_node',$input);$once=$data;
fcg_test(count($data['run']['deck'])===31 && $data['run']['node']['removed']===1 && $data['run']['stage']==='node' && $data['run']['layer']===1,'first removal persists at same reward node');
fcg_test_throws(function()use(&$data,&$user,$input){fcg_test_draft_action($data,$user,'draft_node',$input);},'same slot and identical next copy cannot be removed by replay');
fcg_test($data===$once,'duplicate removal has no effect');
foreach(array('upgrade','pick') as $choice) fcg_test_throws(function()use(&$data,&$user,$choice){fcg_test_draft_action($data,$user,'draft_node',array('choice'=>$choice,'slot'=>0,'card'=>$choice==='pick'?$data['run']['node']['opts'][0]:24));},'cannot switch reward after first removal '.$choice);
$data=fcg_load_account(fcg_json($data));
fcg_test($data['run']===$once['run'] && fcg_public_state($data,$user,true,100)['run']['node']['removed']===1,'removal progress survives reload and is exposed to client');
$input['removed']=1;fcg_test_draft_action($data,$user,'draft_node',$input);$once=$data;
fcg_test(count($data['run']['deck'])===30 && $data['run']['stage']==='battle' && $data['run']['layer']===2 && $data['collection']===$permanent,'second removal completes reward with exactly two cards removed');
fcg_test_throws(function()use(&$data,&$user,$input){fcg_test_draft_action($data,$user,'draft_node',$input);},'second removal replay rejected');
fcg_test($data===$once,'second removal replay cannot delete a third card');
$data=$victory;$data['run']['deck']=array(25,1);
fcg_test_throws(function()use(&$data,&$user){fcg_test_draft_action($data,$user,'draft_node',array('choice'=>'remove','slot'=>0,'card'=>25));},'cannot start two-card removal with one non-energy card');
$data=$victory;$input=array('choice'=>'pick','slot'=>0,'card'=>$data['run']['node']['opts'][0]);
fcg_test_draft_action($data,$user,'draft_node',$input);$once=$data;
fcg_test($data['run']['layer']===2 && $data['run']['stage']==='battle' && count($data['run']['deck'])===33 && end($data['run']['deck'])===$input['card'],'opponent card appended before next battle');
fcg_test_throws(function()use(&$data,&$user,$input){fcg_test_draft_action($data,$user,'draft_node',$input);},'pick replay after advancement rejected');
fcg_test($data===$once,'pick replay cannot add a second card');
fcg_test_draft_action($data,$user,'draft_battle');$second=$data['match']['opponent'];
fcg_test($second<=4 && $second!==$first && $data['match']['sides'][1]['hp']===$opponents[$second]['hp']+5 && $data['match']['sides'][0]['atk']===0 && $data['match']['sides'][0]['hp']===85,'next battle uses full HP, scales opponent and resets combat stats');
$data['match']['sides'][0]['hp']=0;fcg_finish($data['match']);fcg_settle($data,$user);
fcg_test($data['run']['stage']==='over' && $data['run']['wins']===1 && $data['run']['hp']===0 && $data['run']['mhp']===85,'loss ends run without maximum growth or healing');
foreach(array('remove','upgrade','pick') as $choice) {
	$data=$victory;$data['run']['wins']=5;$data['run']['layer']=5;
	$input=array('choice'=>$choice,'slot'=>0,'card'=>$choice==='pick'?$data['run']['node']['opts'][0]:24);
	fcg_test_draft_action($data,$user,'draft_node',$input);
	if($choice==='remove') {fcg_test($data['run']['stage']==='node','fifth victory still requires second removal');fcg_test_draft_action($data,$user,'draft_node',$input);}
	fcg_test($data['run']['stage']==='over' && $data['run']['node']===null && $data['run']['ending']==='win' && $data['run']['layer']===5,'every reward option completes fifth victory '.$choice);
}
// No selectable opponent cards: keep a valid upgrade/remove option, otherwise advance safely.
foreach(array(array(1,3),array(25,1),array(24,1),array(25,25,1)) as $deck) foreach(array(1,5) as $layer) {
	$data=$ready;$data['run']['deck']=$deck;$data['run']['layer']=$layer;$data['run']['wins']=$layer-1;
	fcg_test_draft_action($data,$user,'draft_battle');$data['match']['result']='win';$data['match']['drop_pool']=array(1,3);
	fcg_settle($data,$user);$usable=in_array(24,$deck,true)||count($deck)===3;
	fcg_test($data['run']['stage']===($usable?'node':($layer===5?'over':'battle')),'empty reward pool cannot strand expedition');
	if($usable) fcg_test($data['run']['node']['opts']===array(),'empty opponent option preserved for client hiding');
	$once=$data;fcg_settle($data,$user);fcg_test($data===$once,'empty-pool settlement still idempotent');
}

// Fifth victory still grants its phantom choice before final reward is drawn.
$data=$ready;
for($layer=1;$layer<=5;$layer++) {
	fcg_test_draft_action($data,$user,'draft_battle');$opp=$data['match']['opponent'];
	$range=$layer<=2?range(0,4):($layer===3?range(5,10):($layer===4?range(11,16):range(17,25)));
	fcg_test(in_array($opp,$range,true) && $data['match']['sides'][1]['hp']===$opponents[$opp]['hp']+5*($layer-1),'layer opponent band and HP '.$layer);
	$data['match']['result']='win';$data['match']['sides'][0]['hp']=40;fcg_settle($data,$user);
	fcg_test($data['run']['stage']==='node' && $data['run']['wins']===$layer,'victory offers choice including fifth layer');
	fcg_test($data['run']['mhp']===min(100,80+$layer*5) && $data['run']['hp']===$data['run']['mhp'],'victory health growth caps at 100 and fully heals each layer '.$layer);
	fcg_test_draft_action($data,$user,'draft_node',array('choice'=>'pick','slot'=>0,'card'=>$data['run']['node']['opts'][0]));
}
fcg_test($data['run']['stage']==='over' && $data['run']['ending']==='win' && count($data['run']['deck'])===37,'all five reinforcement cards included in final deck');
foreach(array('win','draw','loss','abandon') as $result) {
	$data=$ready;fcg_draft_prepare_battle($data['run']);fcg_test_draft_action($data,$user,'draft_battle');
	$data['match']['sides'][0]['hp']=80;
	if($result==='abandon') fcg_test_draft_action($data,$user,'draft_abandon');
	else { $data['match']['result']=$result;fcg_settle($data,$user); }
	fcg_test($data['run']['mhp']===($result==='win'?85:80) && $data['run']['hp']===($result==='win'?85:0),'healing caps at new maximum, only victory grows: '.$result);
}
foreach(array(0=>0,1=>1,2=>2,3=>3,4=>4,5=>4) as $wins=>$amount) {
	$data=$ready;$data['run']['stage']='over';$data['run']['wins']=$wins;$data['run']['deck']=array_merge(range(0,23),array(24,24,25,26,151));
	$before=$data['collection'];$embers=$data['embers'];fcg_test_draft_action($data,$user,'draft_reward',array(),false);$reward=$data['last_reward'];$once=$data;
	fcg_test($data['run']===null && $data['match']===null && count($reward['cards'])===$amount && $reward['embers']===max(1,$wins),'wins map to final reward '.$wins);
	$ids=array_column($reward['cards'],'card');fcg_test(count($ids)===count(array_unique($ids)) && !array_diff($ids,array(24,25,26,151)),'final pool deduplicated and excludes all unlimited');
	fcg_test($reward['total_cost']===250,'receipt records new entry cost');
	if($wins===2 || $wins===3) fcg_test($cards[$ids[0]]['upgraded'],'two and three wins guarantee an upgraded card from deck');
	if($wins>=4) fcg_test($ids[0]===151,'rare guarantee takes the deck rare before other slots');
	if($wins===5) fcg_test($ids[1]===25,'upgrade guarantee follows rare without reusing its card');
	fcg_test($data['embers']-$embers===max(1,$wins)+count(array_filter($reward['cards'],function($r){return $r['overflow'];})),'final stamps and overflow use existing rules');
	if(!$wins) fcg_test($data['collection']===$before,'zero wins means no permanent card');
	fcg_test_throws(function()use(&$data,&$user){fcg_test_draft_action($data,$user,'draft_reward');},'reward cannot be claimed twice');fcg_test($data===$once,'rejected repeat leaves receipt intact');
}
$data=$ready;$data['run']['stage']='over';$data['run']['wins']=5;$data['run']['deck']=array(24,25,26,151);foreach(array(24,25,26,151) as $id)$data['collection'][$id]=7;
fcg_test_draft_action($data,$user,'draft_reward');fcg_test($data['embers']===9 && $data['collection'][24]===7,'four full-stack rewards become four extra stamps');
// No matching deck cards: guarantee slots fall back globally, never to energy or ordinary cards.
for($i=0;$i<64;$i++) {
	$data=$ready;$data['run']['stage']='over';$data['run']['wins']=5;$data['run']['deck']=array_merge(range(0,23),array(24,24,26));
	fcg_test_draft_action($data,$user,'draft_reward');$receipt=$data['last_reward'];$ids=array_column($receipt['cards'],'card');
	fcg_test(count($ids)===4 && $cards[$ids[0]]['rare'] && !$cards[$ids[0]]['unlimited'],'rare fallback supplies non-energy rare');
	fcg_test($cards[$ids[1]]['upgraded'] && !$cards[$ids[1]]['rare'] && !$cards[$ids[1]]['unlimited'],'upgrade fallback supplies non-energy non-rare upgrade');
	$ordinary=array_slice($ids,2);sort($ordinary);fcg_test($ordinary===array(24,26),'ordinary slots still come from unique final deck cards');
	$loaded=fcg_load_account(fcg_json($data));
	fcg_test_throws(function()use(&$loaded,&$user){fcg_test_draft_action($loaded,$user,'draft_reward');},'fallback rewards cannot be rerolled after reload');
	fcg_test($loaded['last_reward']===$receipt && $loaded['collection']===$data['collection'],'fallback receipt and collection survive reload unchanged');
}
$data=$ready;$data['run']['stage']='over';$data['run']['wins']=5;$data['run']['deck']=array(151,24,26);
fcg_test_draft_action($data,$user,'draft_reward');$ids=array_column($data['last_reward']['cards'],'card');
fcg_test($ids[0]===151 && $cards[$ids[1]]['upgraded'] && !$cards[$ids[1]]['rare'],'consumed upgraded rare triggers separate upgrade fallback');
$data=$ready;$data['run']['stage']='over';$data['run']['wins']=2;$data['run']['deck']=array(151,24);
fcg_test_draft_action($data,$user,'draft_reward');fcg_test(array_column($data['last_reward']['cards'],'card')===array(151,24),'upgrade slot accepts an upgraded rare present in deck');
$data=$ready;$data['run']['stage']='over';$data['run']['wins']=5;$data['run']['deck']=array(24,26);$data['collection']=array_fill(0,count($cards),7);$before=$data['collection'];
fcg_test_draft_action($data,$user,'draft_reward');fcg_test($data['embers']===9 && $data['collection']===$before,'global fallback also respects full-stack overflow');
$data=$ready;$data['run']['stage']='over';$data['run']['wins']=0;unset($data['run']['entry_cost']);
fcg_test_draft_action($data,$user,'draft_reward');fcg_test($data['last_reward']['total_cost']===500,'pre-rebalance run receipt preserves its actual entry payment');
foreach(array('draft','node','battle') as $stage) {
	$data=$stage==='draft'?$initial:($stage==='node'?$victory:$ready);
	if($stage==='battle') {fcg_test_draft_action($data,$user,'draft_battle');}
	fcg_test_draft_action($data,$user,'draft_abandon',array(),false);$abandoned=$data;
	fcg_test($data['run']['stage']==='over' && $data['run']['ending']==='abandoned','can abandon outside room during '.$stage);
	fcg_settle($data,$user);fcg_test($data===$abandoned,'abandoned battle does not settle again');
	fcg_test_draft_action($data,$user,'draft_reward',array(),false);fcg_test($data['embers']===1 && count($data['last_reward']['cards'])===($stage==='node'?1:0),'abandonment keeps earned wins without extra stamps');
}
$data=$ready;fcg_test_draft_action($data,$user,'draft_battle');
fcg_test_draft_action($data,$user,'surrender',array('match'=>$data['match']['id']));fcg_settle($data,$user);
fcg_test($data['run']['stage']==='over' && $data['run']['hp']===0,'ordinary surrender ends whole expedition');
$normal=fcg_test_match();fcg_effect($normal,0,$heal);fcg_test($normal['sides'][0]['hp']==180,'ordinary healing keeps existing 200 cap');
$data=$ready;$user['nicksrev']='';fcg_test_draft_action($data,$user,'draft_battle');
$data['collection']=array_fill(0,150,1);fcg_settle($data,$user);fcg_test(fcg_title_owned($user,9106),'permanent collection title still checked during expedition');

// Table UI: public piles survive reloads, without exposing either deck or the opponent's hand.
foreach(array('normal','draft','pvp') as $context) {
	$m=fcg_test_ready(array(0,26,24));
	if($context==='draft') $m['draft']=true;
	if($context==='pvp') { $m['pvp']=true; $m['live']=true; }
	fcg_test($m['sides'][0]['played_cards']===array(),'new pile empty: '.$context);
	fcg_play($m,0,0);fcg_play($m,0,0);fcg_play($m,0,0);
	fcg_test($m['sides'][0]['played_cards']===array(0,26) && $m['sides'][0]['monster']['id']===24,'spells in order, resident helper stays out: '.$context);
	$m['sides'][1]['hand']=array(146);fcg_play($m,1,0);
	fcg_test($m['sides'][0]['played_cards']===array(0,26,24) && $m['sides'][1]['played_cards']===array(146),'killed helper goes into owner pile: '.$context);
	fcg_finish($m);fcg_test($m['sides'][0]['played_cards']===array(0,26,24),'death recorded only once: '.$context);
	$data=fcg_new_account();$data['match']=json_decode(fcg_json($m),true);
	$public=fcg_public_state($data,array('credits2'=>0),true,100)['match'];
	fcg_test($public['sides'][0]['played_cards']===array(0,26,24) && $public['sides'][1]['played_cards']===array(146) && !isset($public['sides'][1]['hand']) && !isset($public['sides'][1]['free']) && !isset($public['sides'][0]['deck']) && !isset($public['sides'][1]['deck']),'only public cards survive serialization: '.$context);
	fcg_turn($m);fcg_test($m['sides'][0]['played_cards']===array() && $m['sides'][1]['played_cards']===array(),'new turn clears both piles: '.$context);
}
$m=fcg_test_ready(array(0));fcg_play($m,0,0);fcg_summon($m,1,$cards[150]);$m['sides'][1]['monster']['hp']=1;$m['sides'][0]['atk']=30;
fcg_turn($m);fcg_test($m['turn']===2 && $m['sides'][0]['played_cards']===array() && $m['sides'][1]['played_cards']===array(150),'end-turn combat death remains visible in next turn');
fcg_turn($m);fcg_test($m['sides'][1]['played_cards']===array(),'combat death clears on following turn');
$m=fcg_test_ready(array(200));fcg_play($m,0,0);fcg_turn($m);
fcg_test($m['sides'][0]['played_cards']===array(200),'ephemeral death remains in new turn pile');
$rebirth=current(array_filter($cards,function($c){return in_array('rebirth_once',fcg_tokens($c['ops']),true);}));
$m=fcg_test_ready();fcg_summon($m,0,$rebirth);$m['sides'][0]['monster']['hp']=0;fcg_finish($m);
fcg_test($m['sides'][0]['monster']!==null && $m['sides'][0]['played_cards']===array(),'rebirth does not enter pile');
$m['sides'][0]['monster']['hp']=0;fcg_finish($m);fcg_finish($m);
fcg_test($m['sides'][0]['played_cards']===array($rebirth['id']),'second death enters pile once');
$m=fcg_test_ready(array(198,196));fcg_play($m,0,0);fcg_play($m,0,0);
fcg_test($m['sides'][0]['played_cards']===array(),'burned fuel never enters pile');
$m=fcg_test_ready(array(172));$m['sides'][0]['energy']['R']=0;
fcg_test_throws(function()use(&$m){fcg_play($m,0,0);},'unaffordable play rejected before pile');
fcg_test($m['sides'][0]['played_cards']===array(),'failed play does not enter pile');
$m=fcg_test_ready(array(0));unset($m['sides'][0]['played_cards'],$m['sides'][1]['played_cards']);fcg_play($m,0,0);
fcg_test($m['sides'][0]['played_cards']===array(0),'existing saved match starts pile on next play');
fcg_turn($m);fcg_test($m['sides'][1]['played_cards']===array(),'existing match receives both piles at turn boundary');

// Vol.3: costs, conditional effects, field lifetime and hidden information.
fcg_test(count(fcg_pack_pool(array('series'=>'vol3')))===80 && count(fcg_pack_pool(array('series'=>'vol3','rare'=>true)))===26,'Vol.3 preserves all 13 authored rare pairs');
foreach(range(17,25) as $opponent) {
	$run=array('layer'=>5,'opponents'=>array_values(array_diff(range(17,25),array($opponent))));fcg_draft_prepare_battle($run);
	fcg_test($run['opponent']===$opponent,'every final-layer opponent remains eligible: '.$opponent);
}
foreach(fcg_colors() as $color) fcg_test(count(fcg_pack_pool(array('series'=>'vol3','color'=>$color)))===16,'Vol.3 color balance '.$color);
fcg_test(fcg_tokens('gamble(draw(1); mastery(R, 4, gain_atk(self, 2); draw(1))); refactor(draw(1))')===array('gamble(draw(1); mastery(R, 4, gain_atk(self, 2); draw(1)))','refactor(draw(1))'),'nested semicolons stay within their condition');
fcg_test(in_array('抽牌',fcg_token_keywords($cards[262]['ops'],$cards[262]),true),'nested keyword descriptions remain searchable');
$m=fcg_test_ready(array(260,266,254,0,24));fcg_play($m,0,3);
fcg_test(fcg_card_cost($m['sides'][0],0)===8 && fcg_card_cost($m['sides'][0],1)===9 && fcg_card_cost($m['sides'][0],2)===9,'energy spell reduces both hand surge cards and match surge');
fcg_play($m,0,3);
fcg_test(fcg_card_cost($m['sides'][0],0)===7 && fcg_card_cost($m['sides'][0],1)===9 && fcg_card_cost($m['sides'][0],2)===8,'monster only advances non-spell-only surge');
$m['sides'][0]['free'][1]=true;fcg_discard_slot($m,0,0);
fcg_test(fcg_card_cost($m['sides'][0],0)===0 && fcg_card_cost($m['sides'][0],1)===7,'free and surge slots shift together on discard');
$s=fcg_side(array());$s['hand']=array(254);$s['discarded_match']=40;fcg_test(fcg_card_cost($s,0)===0,'surge has zero floor');
$m=fcg_test_ready(array(260));$m['sides'][0]['surge'][0]=7;$m['sides'][0]['energy']['R']=2;fcg_play($m,0,0);
fcg_test($m['sides'][0]['energy']['R']===0 && $m['sides'][0]['monster']['id']===260,'actual reduced cost paid');
$m=fcg_test_ready(array(262));fcg_play($m,0,0);fcg_test(count($m['sides'][0]['hand'])===2 && $m['sides'][0]['energy']['Y']===96,'playing refactor card does not also discard it');
$m=fcg_test_ready(array(263));$m['sides'][0]['energy']['Y']=0;fcg_discard_slot($m,0,0);
fcg_test(count($m['sides'][0]['hand'])===2 && $m['sides'][0]['energy']['Y']===4 && $m['sides'][0]['regen']===0 && $m['sides'][0]['discarded_match']===1,'manual refactor free, no regen penalty');
$m=fcg_test_ready(array(257,0,258));$m['sides'][0]['surge'][2]=5;fcg_play($m,0,0);
fcg_test($m['sides'][0]['hand']===array(0) && $m['sides'][1]['hp']==192 && $m['sides'][0]['atk']==7 && $m['sides'][0]['barrier']==16 && $m['sides'][0]['endure']===1,'gamble chooses rightmost lowest actual cost and chains refactor');
$m=fcg_test_ready(array(257));fcg_play($m,0,0);fcg_test($m['sides'][0]['atk']==7 && $m['sides'][0]['regen']===0,'empty hand gamble succeeds');
$m=fcg_test_ready(array(264));fcg_discard_slot($m,0,0);fcg_test($m['sides'][0]['monster']['id']===264,'discarded mecha summons itself');
$m['sides'][0]['hp']=100;$m['sides'][0]['hand']=array(264);fcg_discard_slot($m,0,0);fcg_test($m['sides'][0]['hp']==110 && $m['sides'][0]['def']==4,'mecha refactor with resident helper heals instead of escort');
$m=fcg_test_ready(array(268));fcg_discard_slot($m,0,0);fcg_field_turn($m,0);
fcg_test($m['sides'][0]['field']['op']==='digital_life_1' && $m['sides'][1]['hp']==196,'digital refactor uploads a damaging domain');
$m=fcg_test_ready();fcg_summon($m,0,$cards[269]);$m['sides'][0]['monster']['abilities'][]='rebirth_once';$m['sides'][0]['monster']['hp']=0;fcg_finish($m);
fcg_test(!$m['sides'][0]['field'] && $m['sides'][0]['monster']['reborn'],'rebirth delays upload');
$m['sides'][0]['monster']['hp']=0;fcg_finish($m);fcg_test($m['sides'][0]['field']['op']==='digital_life_2','final death uploads');

$m=fcg_test_ready(array(230,232,234));fcg_play($m,0,0);fcg_test($m['sides'][0]['played_cards']===array(),'resident domain not duplicated in pile');
fcg_play($m,0,0);fcg_test($m['sides'][0]['played_cards']===array(230) && $m['sides'][0]['atk']==2,'replacement sends old field to pile and applies Valhalla entry');
fcg_summon($m,0,$cards[24]);$m['sides'][0]['monster']['hp']=0;$m['sides'][0]['energy']['W']=0;fcg_finish($m);
fcg_test($m['sides'][0]['atk']==4 && $m['sides'][0]['energy']['W']==2,'Valhalla death award once');
$m['sides'][0]['hp']=100;fcg_field_turn($m,0);fcg_test($m['sides'][0]['hp']==103,'Valhalla documented healing branch');
$m=fcg_test_ready(array(231));fcg_play($m,0,0);fcg_summon($m,0,$cards[24]);$m['sides'][0]['hp']=100;$m['sides'][0]['monster']['hp']=1;$m['sides'][0]['monster']['frozen']=3;fcg_field_turn($m,0);
fcg_test($m['sides'][0]['hp']==106 && $m['sides'][0]['def']==1 && $m['sides'][0]['monster']['hp']<=$m['sides'][0]['monster']['maxhp'] && $m['sides'][0]['monster']['frozen']===0,'sanctuary heals, caps helper health and cleans freeze');
$m=fcg_test_ready(array(243));fcg_play($m,0,0);$m['sides'][0]['hand']=array(260,0);$m['sides'][1]['hand']=array(0,26);fcg_play($m,1,0);
fcg_test(empty($m['sides'][0]['surge']),'peep ignores basic energy cards');fcg_play($m,1,0);fcg_test(fcg_card_cost($m['sides'][0],0)===8,'enemy spell discounts most expensive hand card');
$m=fcg_test_ready(array(245));fcg_play($m,0,0);fcg_summon($m,0,$cards[24]);$old=$m['sides'][0]['monster'];$m['sides'][0]['gen']['G']=4;fcg_field_turn($m,0);
fcg_test($m['sides'][0]['regen']===1 && $m['sides'][1]['regen']===-2 && $m['sides'][0]['monster']['atk']==$old['atk']+3,'forest poison and green mastery');
$m=fcg_test_ready(array(236));fcg_play($m,0,0);fcg_field_turn($m,0);$m=json_decode(fcg_json($m),true);fcg_field_turn($m,0);
fcg_test($m['sides'][0]['field']['turns']===1 && $m['sides'][1]['hp']===200,'countdown persists without early blast');fcg_field_turn($m,0);fcg_field_turn($m,0);
fcg_test($m['sides'][1]['hp']==176 && $m['sides'][0]['hp']==192 && $m['sides'][0]['played_cards']===array(236),'nuke resolves once and leaves field');
$m=fcg_test_ready(array(248));fcg_play($m,0,0);fcg_packet($m['sides'][1],$m['sides'][0],11,0,false);
fcg_test($m['sides'][0]['hp']==194,'eight zones halves direct attack rounding up');fcg_field_turn($m,0);fcg_field_turn($m,0);
fcg_test($m['sides'][1]['hp']==184 && count($m['sides'][0]['hand'])===2,'eight zones counts both total generation');
$m=fcg_test_ready(array(297));fcg_play($m,0,0);fcg_field_turn($m,0);
fcg_test($m['sides'][1]['atk']===1 && $m['sides'][1]['hp']==175 && $m['sides'][1]['regen']===-5,'jinx buffs then halves attack and poisons');

foreach(array(234,238,240,246,250) as $id) {
	$m=fcg_test_ready();$m['sides'][1]['hand']=array($id);fcg_play($m,1,0);
	$data=fcg_new_account();$data['match']=json_decode(fcg_json($m),true);$public=fcg_public_state($data,array('credits2'=>0),true,100)['match'];
	fcg_test($public['sides'][1]['field']===array('kind'=>'trap','hidden'=>true) && $public['sides'][1]['played_cards']===array() && !isset($public['last_card']) && $public['last_spell']===null && strpos(implode(' ',$public['log']),$cards[$id]['name'])===false,'trap identity hidden in field, log, piles and copy history: '.$id);
	fcg_test(!isset($public['sides'][1]['surge']),'enemy discounts not exposed');
}
$m=fcg_test_ready(array(234));fcg_play($m,0,0);fcg_summon($m,1,$cards[24]);
fcg_test(!$m['sides'][0]['field'] && $m['sides'][0]['played_cards']===array(234) && !$m['sides'][1]['monster'] && $m['last_card']===234,'mine reveals and kills summoned unit');
$m=fcg_test_ready(array(238));fcg_play($m,0,0);$m['sides'][1]['hand']=array(0,0,26);fcg_play($m,1,0);fcg_play($m,1,0);
fcg_test($m['sides'][0]['field']!==null,'combo trap ignores unlimited cards');fcg_play($m,1,0);fcg_test(!$m['sides'][0]['field'] && $m['sides'][1]['hp']==188,'combo trap triggers on subsequent non-energy card');
$m=fcg_test_ready(array(241));fcg_play($m,0,0);$m['sides'][1]['hand']=array(260);$m['sides'][1]['free'][0]=true;fcg_play($m,1,0);
fcg_test(!$m['sides'][0]['field'] && $m['sides'][1]['monster']['frozen']===3 && $m['sides'][1]['disarm']===6,'EMP uses printed cost even on free summon');
$m=fcg_test_ready(array(246));fcg_play($m,0,0);$m['sides'][1]['hand']=array(272);fcg_play($m,1,0);
fcg_test($m['sides'][1]['hp']==190 && $m['sides'][1]['regen']===-3,'poison feast detects nested energy gain');
$m=fcg_test_ready(array(251));fcg_play($m,0,0);fcg_packet($m['sides'][1],$m['sides'][0],9,0,false,null,false,$m,0);
fcg_test($m['sides'][0]['field']!==null,'mirror waits for heavy hit');fcg_packet($m['sides'][1],$m['sides'][0],20,0,false,null,false,$m,0);
fcg_test($m['sides'][0]['hp']==190 && $m['sides'][1]['hp']==174 && $m['sides'][0]['played_cards']===array(251),'heavy hit reflects original plus six and reduces to one');
$m=fcg_test_ready(array(253));$m['sides'][1]['hand']=array(234);fcg_play($m,1,0);$m['sides'][0]['energy']['Y']=10;fcg_play($m,0,0);
fcg_test(!$m['sides'][1]['field'] && $m['sides'][1]['played_cards']===array(234) && $m['sides'][0]['energy']['Y']===14 && $m['sides'][0]['def']==9,'clear field disarms without triggering and rewards');

$m=fcg_test_ready(array(277));$m['sides'][0]['gen']['R']=3;$m['sides'][0]['hp']=40;fcg_play($m,0,0);fcg_test($m['sides'][1]['hp']==182,'mastery fails below threshold');
$m=fcg_test_ready(array(277));$m['sides'][0]['gen']['R']=4;$m['sides'][0]['hp']=40;fcg_play($m,0,0);fcg_test($m['sides'][1]['hp']==154,'mastery and desperate nested effects');
$m=fcg_test_ready(array(275));$m['sides'][0]['gen']['W']=4;fcg_play($m,0,0);fcg_test($m['sides'][0]['monster']['def']===19 && $m['sides'][0]['monster']['hp']===26,'monster mastery bonuses');
$m['sides'][1]['def']=99;fcg_turn($m);fcg_test($m['sides'][1]['hp']==181,'armor press ignores defense');$m['sides'][0]['monster']['frozen']=2;fcg_turn($m);fcg_test($m['sides'][1]['hp']==181,'frozen armor press disabled');
$m=fcg_test_ready(array(287));$m['sides'][0]['gen']['B']=4;fcg_summon($m,1,$cards[170]);fcg_play($m,0,0);
fcg_test(!$m['sides'][1]['monster'] && $m['sides'][1]['hp']==171,'deathnote ignores stealth');
$m=fcg_test_ready(array(299,277,303));$m['sides'][0]['hp']=40;fcg_play($m,0,0);fcg_play($m,0,0);
fcg_test($m['sides'][1]['hp']==154,'SQL forces mastery below generation requirement');$m['sides'][0]['hand']=array(303);$m['sides'][0]['energy']['G']=10;fcg_play($m,0,0);
fcg_test($m['sides'][0]['hp']==64 && $m['sides'][0]['energy']['G']===19,'SQL hoard counts six after safe draw');fcg_turn($m);fcg_test($m['sides'][0]['sql_inject']===0,'SQL expires at turn end');
$m=fcg_test_ready(array(299,156,172));fcg_play($m,0,0);$m['sides'][0]['played_this_turn']=0;fcg_play($m,0,1);
fcg_test($m['sides'][1]['hp']==186,'SQL forces legacy combo');$n=count($m['sides'][0]['hand']);fcg_play($m,0,0);fcg_test(count($m['sides'][0]['hand'])===$n,'SQL forces off-color resonance');
$m=fcg_test_ready(array(290));$m['sides'][0]['hp']=100;$m['sides'][0]['energy']=array_fill_keys(fcg_colors(),10);fcg_play($m,0,0);
fcg_test($m['sides'][1]['hp']==186 && count($m['sides'][0]['hand'])===2 && $m['sides'][0]['energy']['W']===12,'behind on health guarantees miracle jackpot');
$m=fcg_test_ready(array(293,295));fcg_play($m,0,0);fcg_play($m,0,0);
fcg_test($m['sides'][0]['atk']==8 && $m['sides'][0]['monster']['atk']==10 && in_array('rebirth_once',$m['sides'][0]['monster']['abilities'],true),'cheat aura guarantees gem and grants helper rebirth');
$m=fcg_test_ready(array(302,0,0,0,0,0));$m['sides'][0]['hp']=100;fcg_play($m,0,0);
fcg_test(count($m['sides'][0]['hand'])===6 && $m['sides'][0]['regen']===0 && $m['sides'][0]['hp']==118,'safe draw at capacity avoids penalty and feeds hoard');
$m=fcg_test_ready(array(305));$m['sides'][1]['atk']=30;$m['sides'][1]['def']=40;fcg_summon($m,1,$cards[24]);fcg_play($m,0,0);
fcg_test($m['sides'][1]['def']===1 && $m['sides'][1]['atk']===30 && $m['sides'][1]['monster']['atk']===1 && min($m['sides'][1]['energy'])===1,'programmer rewrites highest stats and energy');
$m=fcg_test_ready(array(277,309));fcg_play($m,0,0);fcg_turn($m);fcg_play($m,0,0);
fcg_test(in_array(277,$m['sides'][0]['hand'],true) && fcg_card_cost($m['sides'][0],array_search(277,$m['sides'][0]['hand'],true))===4,'rewind persists own previous play across turn');
$m=fcg_test_ready(array(307));$m['sides'][1]['hand']=array(279,234);fcg_play($m,1,0);fcg_play($m,1,0);fcg_play($m,0,0);
fcg_test($m['sides'][0]['hand']===array(279) && fcg_card_cost($m['sides'][0],0)===1,'copy uses enemy last public card and cannot copy hidden trap');

$m=fcg_test_ready(array(281));$m['sides'][0]['gen']['Y']=4;fcg_summon($m,0,$cards[24]);fcg_summon($m,1,$cards[24]);
$m['sides'][1]['monster']['atk']=9;$m['sides'][1]['monster']['def']=7;$m['sides'][1]['monster']['abilities']=array('escort','ephemeral','growth(1,1,1)');fcg_play($m,0,0);
fcg_test($m['sides'][0]['atk']==4 && $m['sides'][0]['def']==3 && $m['sides'][1]['monster']['atk']==5 && $m['sides'][1]['monster']['def']==4,'mastery soul steal transfers half rounded down');
fcg_test(in_array('growth(1,1,1)',$m['sides'][0]['monster']['abilities'],true) && !in_array('ephemeral',$m['sides'][0]['monster']['abilities'],true) && !in_array('escort',$m['sides'][0]['monster']['abilities'],true),'soul copy excludes escort and ephemeral');
$m=fcg_test_ready();$s=&$m['sides'][0];$s['deck']=array();fcg_draw($s,2,true);fcg_test($s['regen']===-2,'safe draw still penalizes empty deck');unset($s);
$data=fcg_new_account();$data['match']=fcg_test_ready(array(263,24));$data['match']['sides'][0]['energy']['Y']=0;$user=array('credits2'=>0);
$input=array('action'=>'discard','match'=>$data['match']['id'],'slot'=>0,'card'=>263,'turn'=>1);
fcg_action($data,$user,$input,100,true);$data=fcg_load_account(fcg_json($data));$once=$data['match'];
fcg_test($once['sides'][0]['energy']['Y']===4 && $once['sides'][0]['regen']===0,'refactor request persists its free reward');
fcg_test_throws(function()use(&$data,&$user,$input){fcg_action($data,$user,$input,100,true);},'stale refactor slot rejected');
fcg_test($once===$data['match'],'stale discard cannot grant refactor twice');
foreach(array('play','discard') as $action) {
	$input=array('action'=>$action,'match'=>$data['match']['id'],'slot'=>0,'card'=>24,'turn'=>2);
	fcg_test_throws(function()use(&$data,&$user,$input){fcg_action($data,$user,$input,100,true);},'NPC request rejects changed turn: '.$action);
}
$m=fcg_test_ready(array(234));$m['uids']=array(11,12);$m['names']=array('甲','乙');$m['stickers']=array();$m['decks']=array(array(234),array(241));$m['sides'][1]['hand']=array(241);
fcg_duel_action($m,0,array('action'=>'play','slot'=>0,'card'=>234,'turn'=>1),100);fcg_duel_action($m,1,array('action'=>'play','slot'=>0,'card'=>241,'turn'=>1),100);
foreach(array(11,12) as $uid) {
	$public=fcg_social_public(array(),array('match'=>json_decode(fcg_json($m),true),'vars'=>array(),'mirrors'=>array()),fcg_new_account(),array('uid'=>$uid),null,100)['match'];
	fcg_test($public['sides'][0]['field']['id']===($uid===11?234:241) && $public['sides'][1]['field']===array('kind'=>'trap','hidden'=>true),'PvP owner sees own trap only: '.$uid);
	fcg_test(!isset($public['decks']) && !isset($public['sides'][1]['hand']) && !isset($public['sides'][1]['surge']) && $public['sides'][1]['played_cards']===array(),'PvP hidden trap survives serialization and perspective swap: '.$uid);
}

// Bootstrap only needs a catalog read here; no live database or account is touched.
$db=new class {
	function query($sql) { return array(); }
	function fetch_array($result) { return false; }
};
$gtablepre='';$titles_list=array();
$boot=json_decode(fcg_bootstrap(array(),'test-csrf',false),true);
fcg_test($boot['cards'][172]['combo_min']===2 && $boot['cards'][173]['combo_min']===2,'combo summary picks lowest trigger including legacy syntax');
fcg_test($boot['cards'][156]['res_colors']===array('W'),'resonance summary exposes preceding color');
fcg_test($boot['cards'][0]['combo_min']===null && $boot['cards'][0]['res_colors']===array(),'ordinary card has no highlight condition');
foreach($boot['cards'] as $card) fcg_test(!isset($card['ops']) && array_key_exists('combo_min',$card) && is_array($card['res_colors']),'static summary without raw ops: '.$card['id']);
unset($db);

// Onboarding grants are permanent, bounded and shared by all saved decks.
$data=fcg_new_account(); $user=array('credits2'=>500,'nicksrev'=>'');
fcg_test(count(fcg_quiz_definitions()['questions'])===5 && count(fcg_color_starter_decks())===6,'five questions and six starter choices');
foreach(fcg_colors() as $color) {
	$deck=fcg_color_starter_decks()[$color]['cards'];
	fcg_test(count($deck)===30,'starter has thirty cards: '.$color);
	foreach($deck as $id) fcg_test($cards[$id]['color']===$color && $cards[$id]['series']!=='vol3','starter uses own color from Vol1/2');
	fcg_test(fcg_evaluate_quiz(array_fill(0,5,$color))['recommended']===$color,'uniform quiz color');
}
fcg_test(fcg_evaluate_quiz(fcg_colors())['recommended']==='ORIG' && fcg_evaluate_quiz(fcg_colors())['is_random'],'flat quiz uses instinct deck');
fcg_test(fcg_evaluate_quiz(array('W','R','W','G','R'))['recommended']==='R','Q5 breaks tied colors');
fcg_test(fcg_evaluate_quiz(array('W','R','R','W','G'))['recommended']==='W','non-tied Q5 falls back to first tied answer');
foreach(array(null,array('W'),array_fill(0,6,'W'),array('W','R',array(),'G','B')) as $answers) fcg_test_throws(function()use($answers){fcg_evaluate_quiz($answers);},'malformed quiz rejected');
fcg_action($data,$user,array('action'=>'quiz','answers'=>array()),100,false);
fcg_claim_starter_deck($data,$user,'ORIG');fcg_test(!$data['starter_claimed'] && $user['credits2']===500 && $data['quiz_result']==='ORIG','skipping and original preserve free grant');
fcg_claim_starter_deck($data,$user,'W');$before=$data;
fcg_test($data['starter_claimed'] && $user['credits2']===500 && fcg_validate_deck($data['deck'],$data['collection'])===$data['deck'],'free color delivered and playable');
fcg_claim_starter_deck($data,$user,'W');fcg_test($before===$data && $user['credits2']===500,'same starter never grants twice');
fcg_claim_starter_deck($data,$user,'ORIG');fcg_test($data['starter_claimed'],'original cannot reset consumed free grant');
foreach(array('R','Y','G','B') as $color) {fcg_claim_starter_deck($data,$user,$color);fcg_test(fcg_validate_deck($data['deck'],$data['collection'])===$data['deck'],'paid starter playable');}
fcg_test($user['credits2']===100 && count($data['owned_starters'])===6,'remaining four colors cost exactly 400');
$data=fcg_load_account(fcg_json($data));$before=$data;fcg_claim_starter_deck($data,$user,'B');fcg_test($data===$before && $user['credits2']===100,'reload preserves ownership');
$data=fcg_new_account();$user['credits2']=0;fcg_claim_starter_deck($data,$user,'W');$before=$data;
fcg_test_throws(function()use(&$data,&$user){fcg_claim_starter_deck($data,$user,'R');},'paid starter needs funds');fcg_test($data===$before && $user['credits2']===0,'failed grant unchanged');
fcg_test_throws(function()use(&$data,&$user){fcg_claim_starter_deck($data,$user,array('R'));},'starter key type checked');
$data['collection'][152]=7;$user['credits2']=100;fcg_claim_starter_deck($data,$user,'R');fcg_test($data['collection'][152]===7 && $data['embers']===4,'overflow uses existing stamps');

$legacy=fcg_new_account();unset($legacy['decks'],$legacy['deck_slots'],$legacy['active_deck'],$legacy['turn_mode'],$legacy['starter_claimed'],$legacy['owned_starters']);$legacy['deck']=array_fill(0,25,0);
$legacy=fcg_load_account(fcg_json($legacy));fcg_test($legacy['decks'][0]['cards']===$legacy['deck'] && !$legacy['starter_claimed'] && $legacy['turn_mode']==='instinct','legacy custom deck and free grant preserved');
$data=fcg_new_account();$user['credits2']=5000;
$slot_item=array('item_id'=>35,'enabled'=>1,'price_credits2'=>500,'daily_limit'=>0,'total_limit'=>9,'reward_type'=>'custom','reward_payload'=>array('kind'=>'deck_slot','count'=>1));
fcg_buy($data,$user,$slot_item,100);fcg_test($data['deck_slots']===2 && $data['decks'][1]['cards']===array() && $user['credits2']===4500,'slot unlock costs 500 and starts empty');
fcg_test_throws(function()use(&$data){fcg_deck_slot($data,array('slot'=>1,'op'=>'activate'));},'empty slot cannot become active');
fcg_deck_slot($data,array('slot'=>1,'op'=>'save','deck'=>array_reverse($data['deck'])));fcg_deck_slot($data,array('slot'=>1,'op'=>'rename','name'=>'另一副牌'));
$loaded=fcg_load_account(fcg_json($data));fcg_test($loaded['active_deck']===1 && $loaded['deck']===$loaded['decks'][1]['cards'] && $loaded['decks'][1]['name']==='另一副牌','slot activation and name persist');
fcg_deck_slot($data,array('slot'=>0,'op'=>'activate'));fcg_test($data['deck']===fcg_starter_deck(),'original slot can be restored');
for($i=2;$i<10;$i++) fcg_buy($data,$user,$slot_item,100);
$before=$data;$balance=$user['credits2'];fcg_test_throws(function()use(&$data,&$user,$slot_item){fcg_buy($data,$user,$slot_item,100);},'slot limit ten');fcg_test($data===$before && $user['credits2']===$balance,'slot limit does not debit');
fcg_test_throws(function()use(&$data){fcg_deck_slot($data,array('slot'=>10,'op'=>'rename','name'=>'bad'));},'unowned slot rejected');
fcg_test_throws(function()use(&$data){fcg_deck_slot($data,array('slot'=>0,'op'=>'rename','name'=>str_repeat('字',31)));},'rename length checked');
$data['temp_cards']=array('room'=>1,'key'=>'1:1:1','day'=>'1970-01-01','cards'=>array(151=>1));$data['decks'][1]['cards'][]=151;
fcg_expire_temp($data,array('roomid'=>2),86500,'2:1:1');fcg_test(!in_array(151,$data['decks'][1]['cards'],true) && $data['decks'][2]['cards']===array(),'expiry prunes inactive slots and preserves empty slots');

// Actual discounted costs and summon restrictions drive no-playable shortening.
$m=fcg_test_ready(array(0,0));$m['turn_modes'][0]='think';fcg_reset_turn_timers($m,100);
fcg_test($m['deadline']===280,'think turn starts with 180 seconds');
fcg_duel_action($m,0,array('action'=>'play','slot'=>0,'card'=>0,'turn'=>1),101);fcg_test($m['deadline']===280,'think play never extends deadline');
fcg_duel_action($m,0,array('action'=>'play','slot'=>0,'card'=>0,'turn'=>1),102);fcg_test($m['deadline']===107 && $m['no_playable_shortened'][0],'last playable card shortens to five seconds');
fcg_advance($m,106);fcg_test($m['turn']===1,'shortened timer waits');fcg_advance($m,107);fcg_test($m['turn']===2,'shortened timer settles');
$m=fcg_test_ready(array(238));$m['sides'][0]['energy']=array_fill_keys(fcg_colors(),0);$m['sides'][0]['surge'][0]=99;fcg_test(fcg_has_playable_card($m,0),'surge discounted card is playable');
$m['sides'][0]['surge']=array();fcg_test(!fcg_has_playable_card($m,0),'unaffordable hand is not playable');$m['sides'][0]['free'][0]=true;fcg_test(fcg_has_playable_card($m,0),'free card is playable');
$m=fcg_test_ready(array(24));$m['sides'][0]['monster']=array('id'=>24,'hp'=>10,'frozen'=>0,'abilities'=>array());fcg_test(!fcg_has_playable_card($m,0),'occupied monster slot blocks summon');
$m['turn_modes'][0]='think';fcg_reset_turn_timers($m,100);fcg_duel_action($m,0,array('action'=>'destroy','turn'=>1),101);fcg_test(fcg_has_playable_card($m,0) && $m['deadline']===105,'dismiss opens slot without resetting shortened timer');
$m=fcg_test_ready(array(0));$m['turn_modes'][0]='think';fcg_reset_turn_timers($m,100);fcg_duel_action($m,0,array('action'=>'discard','slot'=>0,'card'=>0,'turn'=>1),102);fcg_test($m['deadline']===107,'discard also shortens timer');
$m=fcg_test_ready(array(0));$m['turn_modes'][0]='think';$m['ai_at']=102;$m['sides'][1]['hand']=array(26,26);fcg_reset_turn_timers($m,100);
fcg_duel_action($m,0,array('action'=>'end_turn','turn'=>1),100);fcg_test($m['turn']===2 && $m['sides'][1]['atk']==6 && $m['sides'][0]['hp']==194,'early end completes AI before combat');
fcg_test_throws(function()use(&$m){fcg_duel_action($m,0,array('action'=>'end_turn','turn'=>1),100);},'stale end turn cannot skip next round');
fcg_test_throws(function()use(&$m){fcg_duel_action($m,0,array('action'=>'destroy','turn'=>1),100);},'stale destroy cannot affect next round');
$m=fcg_test_ready(array(0));$m['live']=true;$m['turn_modes']=array('instinct','think');$m['sides'][1]['hand']=array(0);$m['seen']=array(100,100);fcg_reset_turn_timers($m,100);
fcg_live_advance($m,110);fcg_test($m['turn']===1 && $m['turn_done']===array(true,false) && $m['deadline']===280,'live fast side waits for thinking side');
fcg_test_throws(function()use(&$m){fcg_duel_action($m,0,array('action'=>'play','slot'=>0,'card'=>0,'turn'=>1),111);},'finished player cannot play');
fcg_test_throws(function()use(&$m){fcg_duel_action($m,0,array('action'=>'discard','slot'=>0,'card'=>0,'turn'=>1),111);},'finished player cannot discard');
fcg_test_throws(function()use(&$m){fcg_duel_action($m,0,array('action'=>'destroy','turn'=>1),111);},'finished player cannot dismiss');
$view=$m;fcg_public_timers($view,1);fcg_test($view['turn_mode']==='think' && !$view['my_turn_done'] && $view['opp_turn_done'] && $view['deadline']===280,'second player sees own timer and readiness');
fcg_end_turn($m,1,112);fcg_test($m['turn']===2 && $m['turn_done']===array(false,false) && $m['side_deadlines']===array(117,292),'both done settle once and reset independent clocks');
$m=fcg_test_ready(array(0));fcg_set_turn_mode($m,0,'think',101);fcg_test($m['deadline']===280,'switch uses original round start');fcg_set_turn_mode($m,0,'instinct',102);fcg_set_turn_mode($m,0,'think',103);fcg_test($m['deadline']===280,'repeated switching cannot reset time');
$m=fcg_test_ready();unset($m['side_deadlines']);fcg_advance($m,100);fcg_test($m['side_deadlines']===array(110,110),'legacy in-flight match migrates without losing time');
$bgm=fcg_load_bgm_config();fcg_test(count($bgm['tracks'])===5 && count($bgm['events'])===3,'BGM scene scaffold loads');foreach($bgm['tracks'] as $track)fcg_test($track['url']==='','BGM starts silent');

echo 'FCG: '.$checks." checks passed.\n";
