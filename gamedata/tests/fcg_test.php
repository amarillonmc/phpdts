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
fcg_test(count($cards)===230,'230 independent card IDs');
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
foreach(fcg_colors() as $c) fcg_test(count(fcg_pack_pool(array('color'=>$c)))===46,'balanced color '.$c);
foreach(array(array(array('upgraded'=>false,'rare'=>false,'series'=>'vol1'),57),array(array('upgraded'=>false,'series'=>'vol1'),75),array(array('upgraded'=>true,'series'=>'vol1'),75),array(array('rare'=>true,'series'=>'vol1'),36),array(array('series'=>'vol2'),80),array(array('series'=>'vol2','rare'=>true),30)) as $case) fcg_test(count(fcg_pack_pool($case[0]))===$case[1],'pack population');
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
	fcg_validate_deck($o['deck'],array_fill(0,230,100));
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
$m=fcg_test_ready(array(0,0,0,0,0,0));$m['live']=true;for($i=0;$i<6;$i++)fcg_play($m,0,0);fcg_test($m['deadline']===114,'live extension cap shared by all plays');fcg_turn($m);fcg_test($m['deadline']===119 && $m['extension']===0,'new turn resets extension');
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
$data['deck'][]=151;fcg_test(count(fcg_validate_deck($data['deck'],fcg_collection($data)))===31,'temporary card available to deck');
fcg_expire_temp($data,$user,101,$key);fcg_test(count($data['deck'])===31,'room zero temporary card remains valid');
fcg_expire_temp($data,$user,86500,$key);fcg_test(empty($data['temp_cards']) && count($data['deck'])===30 && $data['collection']===$permanent,'next day prunes borrowed card without permanent loss');
fcg_pvp_reward($data,$user,2,array(151),86500,$key);fcg_test($data['embers']===8,'daily reward resets');fcg_expire_temp($data,$user,86501,'0:2:200');fcg_test(empty($data['temp_cards']),'new room instance expires borrowed card');
$data['pvp_wins']=9;fcg_pvp_reward($data,$user,2,array(151),86500,$key);fcg_test(fcg_title_owned($user,9121),'ten wins cosmetic title');$data['pvp_wins']=49;fcg_pvp_reward($data,$user,2,array(151),86500,$key);fcg_test(fcg_title_owned($user,9122),'fifty wins cosmetic title');
$data=fcg_new_account();$user['nicksrev']='';foreach(range(4,20) as $i)$data['wins'][$i]=1;fcg_settle($data,$user);fcg_test(!fcg_title_owned($user,9106),'new opponents do not substitute for initial seventeen');foreach(range(0,16) as $i)$data['wins'][$i]=1;fcg_settle($data,$user);fcg_test(fcg_title_owned($user,9106),'old seventeen achievement unchanged');
// Phase 3: HARD is a server-validated variant; normal decks and rewards remain available.
$opponents=fcg_opponents(); $npc_decks=fcg_npc_decks();
fcg_test(count($opponents)===22 && count($npc_decks['hard'])===16,'22 opponents and exactly 16 hard decks');
foreach(range(5,20) as $id) {
	$o=$opponents[$id]; $hard=fcg_new_match(fcg_starter_deck(),$id,100,true); $normal=fcg_new_match(fcg_starter_deck(),$id,100);
	fcg_test(count($npc_decks['hard'][$id])===32,'hard has 32 cards '.$id);
	fcg_validate_deck($npc_decks['hard'][$id],array_fill(0,230,100));
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
foreach(array(0,4,21,22) as $id) fcg_test_throws(function()use($id){fcg_new_match(fcg_starter_deck(),$id,100,true);},'unsupported hard opponent '.$id);
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
foreach(fcg_colors() as $color) fcg_test(fcg_test_evo_deck($evo['decks'][$color],$color)===array(10,12,6,4),'cold curve '.$color);
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
fcg_test(fcg_test_evo_deck($evo['decks']['W'],'W')===array(10,12,6,4),'slot replacement preserves curve');
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
	fcg_action($data,$user,array_merge(array('action'=>$action,'run'=>$data['run']['id'] ?? null,'layer'=>$data['run']['layer'] ?? null),$more),100,$in_room);
}
function fcg_test_finish_draft(&$data,&$user) {
	while($data['run']['stage']==='draft') {
		$pack=fcg_draft_pack($data['run']);
		fcg_test_draft_action($data,$user,'draft_pick',array('slot'=>0,'card'=>$pack[0],'pick'=>count($data['run']['picks'])));
	}
}
$data=fcg_new_account();$user=array('uid'=>1,'nicksrev'=>'','credits2'=>499);$before=$data;
fcg_test_throws(function()use(&$data,&$user){fcg_test_draft_action($data,$user,'draft_enter');},'entry requires 500 credits');
fcg_test($data===$before && $user['credits2']===499,'failed entry leaves account untouched');
$user['credits2']=1000;
fcg_test_throws(function()use(&$data,&$user){fcg_test_draft_action($data,$user,'draft_enter',array(),false);},'entry requires room');
$data['match']=fcg_test_match();
fcg_test_throws(function()use(&$data,&$user){fcg_test_draft_action($data,$user,'draft_enter');},'entry requires finished match');
$data['match']=null;fcg_test_draft_action($data,$user,'draft_enter');$data['run']['seed']=4729;
fcg_test($user['credits2']===500 && $data['run']['hp']===80 && $data['run']['stage']==='draft','entry starts at 80 HP and charges once');
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
fcg_test(count($data['run']['deck'])===32 && count($data['run']['picks'])===24 && $data['run']['stage']==='node','24 selections plus eight energy form first camp');
fcg_test($data['collection']===$permanent && $data['deck']===$ordinary_deck,'draft does not alter collection or regular deck');
$camp=$data;
foreach(array(array(array_fill(0,24,24),array('W'=>8)),array(array_merge(array_fill(0,18,30),array_fill(0,6,42)),array('R'=>4,'G'=>4)),array(array_merge(array_fill(0,12,30),array_fill(0,8,42),array_fill(0,4,48)),array('R'=>3,'G'=>3,'B'=>2))) as $case) {
	$base=fcg_draft_energy($case[0]);$colors=array();
	foreach($base as $id) {fcg_test($cards[$id]['unlimited'],'allocated energy is unlimited');$c=$cards[$id]['color'];$colors[$c]=($colors[$c] ?? 0)+1;}
	fcg_test(count($base)===8 && $colors===$case[1],'eight energy follow mono/dual/three-color split');
}
fcg_test(array_count_values(fcg_draft_energy(array_fill(0,24,24)))===array(1=>2,3=>2,0=>4),'mono gets upgraded pairs and ordinary generator filler');

// Every camp option is checked by the server, including protected energy and upgraded targets.
$data=$camp;$data['run']['deck']=array_merge(array_fill(0,24,24),array_fill(0,8,1));
fcg_test_throws(function()use(&$data,&$user){fcg_test_draft_action($data,$user,'draft_node',array('choice'=>'supply','layer'=>0));},'old layer choice rejected');
foreach(array('upgrade','remove') as $choice) fcg_test_throws(function()use(&$data,&$user,$choice){fcg_test_draft_action($data,$user,'draft_node',array('choice'=>$choice,'slot'=>24,'card'=>1));},'energy cannot be modified '.$choice);
fcg_test_draft_action($data,$user,'draft_node',array('choice'=>'upgrade','slot'=>0,'card'=>24));
fcg_test($data['run']['deck'][0]===25 && $data['run']['stage']==='battle' && $data['collection']===$permanent,'upgrade changes phantom card only');
$data['run']['stage']='node';$data['run']['node']=array('type'=>'camp');
fcg_test_throws(function()use(&$data,&$user){fcg_test_draft_action($data,$user,'draft_node',array('choice'=>'upgrade','slot'=>0,'card'=>25));},'already upgraded target rejected');
fcg_test_draft_action($data,$user,'draft_node',array('choice'=>'remove','slot'=>0,'card'=>25));
fcg_test(count($data['run']['deck'])===31 && !in_array(25,$data['run']['deck'],true),'removal changes one phantom copy');
$data=$camp;$data['run']['hp']=53;fcg_test_draft_action($data,$user,'draft_node',array('choice'=>'supply'));
fcg_test($data['run']['hp']===77,'supply heals 30 percent of maximum');
fcg_test_throws(function()use(&$data,&$user){fcg_test_draft_action($data,$user,'draft_battle',array('layer'=>0));},'old battle confirmation rejected');
fcg_test_throws(function()use(&$data,&$user){fcg_test_draft_action($data,$user,'draft_node',array('choice'=>'rest'));},'camp used only once');
fcg_test_draft_action($data,$user,'draft_battle');$first=$data['match']['opponent'];
fcg_test($first<=4 && $data['match']['sides'][0]['hp']===77 && $data['match']['sides'][1]['hp']===$opponents[$first]['hp'],'first layer starts with inherited HP');
fcg_test_throws(function()use(&$data,&$user){fcg_test_draft_action($data,$user,'draft_battle');},'cannot restart active layer');
$m=&$data['match'];$heal=$cards[26];$heal['ops']='gain_hp(self,100)';fcg_effect($m,0,$heal);
fcg_test($m['sides'][0]['hp']===80,'expedition healing capped at run maximum');
$m['sides'][0]['regen']=50;fcg_turn($m);fcg_test($m['sides'][0]['hp']===80,'expedition regeneration capped');
$m['sides'][0]['hp']=27;$m['sides'][0]['atk']=30;$m['result']='win';unset($m);
$before_collection=$data['collection'];$before_wins=$data['wins'];$before_embers=$data['embers'];fcg_settle($data,$user);$settled=$data;fcg_settle($data,$user);
fcg_test($data===$settled && $data['run']['wins']===1 && $data['run']['hp']===27 && $data['run']['node']['type']==='reinforce','layer settles once, carries remaining HP');
fcg_test($data['collection']===$before_collection && $data['wins']===$before_wins && $data['embers']===$before_embers && !$data['hard_wins'] && !fcg_title_owned($user,9106),'layer does not grant normal drops, records or title');
fcg_test(count($data['run']['node']['opts'])===3 && count(array_unique($data['run']['node']['opts']))===3 && !array_diff($data['run']['node']['opts'],$data['match']['drop_pool']),'three distinct reinforcements from actual opponent deck');
fcg_test_throws(function()use(&$data,&$user){fcg_test_draft_action($data,$user,'draft_node',array('choice'=>'supply'));},'cannot skip reinforcement');
fcg_test_draft_action($data,$user,'draft_node',array('choice'=>'reinforce','slot'=>0,'card'=>$data['run']['node']['opts'][0]));
fcg_test($data['run']['layer']===2 && count($data['run']['deck'])===33,'reinforcement appended before next camp');
fcg_test_draft_action($data,$user,'draft_node',array('choice'=>'rest'));
fcg_test($data['run']['hp']===39 && $data['embers']===$before_embers+1,'small rest heals 15 percent and one stamp');
fcg_test_draft_action($data,$user,'draft_battle');$second=$data['match']['opponent'];
fcg_test($second<=4 && $second!==$first && $data['match']['sides'][1]['hp']===$opponents[$second]['hp']+15 && $data['match']['sides'][0]['atk']===0,'next layer scales opponent HP, avoids repeat and resets combat stats');
$data['match']['sides'][0]['hp']=0;fcg_finish($data['match']);fcg_settle($data,$user);
fcg_test($data['run']['stage']==='over' && $data['run']['wins']===1 && $data['run']['hp']===0,'HP zero immediately ends run');

// Fifth victory still grants its phantom choice before final reward is drawn.
$data=$camp;
for($layer=1;$layer<=5;$layer++) {
	fcg_test_draft_action($data,$user,'draft_node',array('choice'=>'supply'));fcg_test_draft_action($data,$user,'draft_battle');$opp=$data['match']['opponent'];
	$range=$layer<=2?range(0,4):($layer===3?range(5,10):($layer===4?range(11,16):range(17,20)));
	fcg_test(in_array($opp,$range,true) && $data['match']['sides'][1]['hp']===$opponents[$opp]['hp']+15*($layer-1),'layer opponent band and HP '.$layer);
	$data['match']['result']='win';$data['match']['sides'][0]['hp']=40;fcg_settle($data,$user);
	fcg_test($data['run']['stage']==='node' && $data['run']['wins']===$layer,'victory offers choice including fifth layer');
	fcg_test_draft_action($data,$user,'draft_node',array('choice'=>'reinforce','slot'=>0,'card'=>$data['run']['node']['opts'][0]));
}
fcg_test($data['run']['stage']==='over' && $data['run']['ending']==='win' && count($data['run']['deck'])===37,'all five reinforcement cards included in final deck');
foreach(array(0=>0,1=>1,2=>1,3=>2,4=>2,5=>3) as $wins=>$amount) {
	$data=$camp;$data['run']['stage']='over';$data['run']['wins']=$wins;$data['run']['deck']=array(0,1,20,21,22,23,24,24,25,26,151);
	$before=$data['collection'];$embers=$data['embers'];fcg_test_draft_action($data,$user,'draft_reward',array(),false);$reward=$data['last_reward'];$once=$data;
	fcg_test($data['run']===null && $data['match']===null && count($reward['cards'])===$amount && $reward['embers']===max(1,$wins),'wins map to final reward '.$wins);
	$ids=array_column($reward['cards'],'card');fcg_test(count($ids)===count(array_unique($ids)) && !array_diff($ids,array(24,25,26,151)),'final pool deduplicated and excludes all unlimited');
	fcg_test($data['embers']-$embers===max(1,$wins)+count(array_filter($reward['cards'],function($r){return $r['overflow'];})),'final stamps and overflow use existing rules');
	if(!$wins) fcg_test($data['collection']===$before,'zero wins means no permanent card');
	fcg_test_throws(function()use(&$data,&$user){fcg_test_draft_action($data,$user,'draft_reward');},'reward cannot be claimed twice');fcg_test($data===$once,'rejected repeat leaves receipt intact');
}
$data=$camp;$data['run']['stage']='over';$data['run']['wins']=5;$data['run']['deck']=array(24,25,26);foreach(array(24,25,26) as $id)$data['collection'][$id]=7;
fcg_test_draft_action($data,$user,'draft_reward');fcg_test($data['embers']===8 && $data['collection'][24]===7,'three full-stack rewards become three extra stamps');
foreach(array('draft','node','battle') as $stage) {
	$data=$stage==='draft'?$initial:$camp;
	if($stage==='battle') {fcg_test_draft_action($data,$user,'draft_node',array('choice'=>'supply'));fcg_test_draft_action($data,$user,'draft_battle');}
	fcg_test_draft_action($data,$user,'draft_abandon',array(),false);$abandoned=$data;
	fcg_test($data['run']['stage']==='over' && $data['run']['ending']==='abandoned','can abandon outside room during '.$stage);
	fcg_settle($data,$user);fcg_test($data===$abandoned,'abandoned battle does not settle again');
	fcg_test_draft_action($data,$user,'draft_reward',array(),false);fcg_test($data['embers']===1 && !$data['last_reward']['cards'],'zero-win abandonment gives exactly one stamp');
}
$data=$camp;fcg_test_draft_action($data,$user,'draft_node',array('choice'=>'supply'));fcg_test_draft_action($data,$user,'draft_battle');
fcg_test_draft_action($data,$user,'surrender',array('match'=>$data['match']['id']));fcg_settle($data,$user);
fcg_test($data['run']['stage']==='over' && $data['run']['hp']===0,'ordinary surrender ends whole expedition');
$normal=fcg_test_match();fcg_effect($normal,0,$heal);fcg_test($normal['sides'][0]['hp']==180,'ordinary healing keeps existing 200 cap');
$data=$camp;$user['nicksrev']='';fcg_test_draft_action($data,$user,'draft_node',array('choice'=>'supply'));fcg_test_draft_action($data,$user,'draft_battle');
$data['collection']=array_fill(0,150,1);fcg_settle($data,$user);fcg_test(fcg_title_owned($user,9106),'permanent collection title still checked during expedition');

echo 'FCG: '.$checks." checks passed.\n";
