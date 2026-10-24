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
fcg_test(count($cards)===150,'150 independent card IDs');
foreach(fcg_colors() as $c) fcg_test(count(fcg_pack_pool(array('color'=>$c)))===30,'balanced color '.$c);
foreach(array(array(array('upgraded'=>false,'rare'=>false),57),array(array('upgraded'=>false),75),array(array('upgraded'=>true),75),array(array('rare'=>true),36)) as $case) fcg_test(count(fcg_pack_pool($case[0]))===$case[1],'pack population');
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
	fcg_validate_deck($o['deck'],array_fill(0,150,100));
	$m=fcg_new_match(fcg_starter_deck(),$o['id'],100);fcg_advance($m,100000);
	fcg_test($m['result']!==null,'offline match terminates '.$o['id']);
}
echo 'FCG: '.$checks." checks passed.\n";
