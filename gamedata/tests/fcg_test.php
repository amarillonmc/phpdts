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
$m=fcg_test_ready();$m['live']=true;$m['seen']=array(100,100);$m['sides'][0]['hand']=array(0);fcg_live_advance($m,120);
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
echo 'FCG: '.$checks." checks passed.\n";
