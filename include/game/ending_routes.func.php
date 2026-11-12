<?php
if(!defined('IN_GAME')) exit('Access Denied');

// 普通房沿用大房间内容；专属规则集必须显式选择，不能因公共配置回退而注入。
function ending_routes_enabled()
{
	global $ending_routes_v2,$club_skills,$current_ruleset_config;
	if(empty($ending_routes_v2) || (isset($club_skills) && !$club_skills)) return false;
	if($current_ruleset_config && (empty($current_ruleset_config['ending_routes_v2']) || empty($current_ruleset_config['club_skills']))) return false;
	return true;
}

function ending_routes_setup()
{
	global $plsinfo,$hplsinfo,$areainfo,$horizoninfo,$typeinfo,$stateinfo,$dinfo;
	if(!ending_routes_enabled()) return;
	$plsinfo[34] = '代码洪流';
	$areainfo[34] = '光流在脚边交错，动物大排档的灯火映在奔流的代码里。';
	$hplsinfo[3] = array(120=>'中央旧城区',121=>'新金融核心区',122=>'西部山区',123=>'港口工业区',124=>'玄武数据公园',125=>'天青大桥',126=>'「下层」地下商业街',127=>'岚江口生态再造区',128=>'寂山书院',129=>'东洲岛');
	$hplsinfo[4] = array(134=>'英灵殿');
	$areainfo[134] = '长廊尽头，旧日的武神仍守着那道门。';
	foreach($hplsinfo[3] as $place=>$label) $areainfo[$place] = $label.'的灯光在投影中摇曳。时空锚定只会维持七十五分钟。';
	$horizoninfo[2] = '模式ＥＸ';
	foreach(array(34=>'红杀教头',35=>'善恶投影',36=>'动物团',37=>'动物团投影',38=>'送行事件',39=>'旧红杀教头',40=>'历史残响',41=>'终局守望者',42=>'蒸汽羊') as $key=>$value) $typeinfo[$key]=$value;
	$stateinfo[61] = '头部破坏致死';
	$stateinfo[62] = '湾城投影消散';
	$dinfo[61] = '世界在一瞬间倒转过来。你看见自己的身体倒下，看见自己的视野滚落在地。<br>最后的念头停在半空：啊……原来是我。';
	$dinfo[62] = '75 分钟的时空锚定归零。湾城的街道在噪波中剥落，你伸出指尖，已化作飞散的代码。<br>黑暗里有人叹息：「看来……你没能走到最后。」';
}

// 给物品时直接找空槽，满包则放脚下；不能覆盖待拾取物品或漏掉 para。
function ending_routes_give(&$data,$item)
{
	global $db,$tablepre,$log;
	$item += array('itme'=>1,'itms'=>1,'itmsk'=>'','itmpara'=>array());
	for($i=1;$i<=6;$i++) if(empty($data['itms'.$i])) {
		foreach(array('itm','itmk','itme','itms','itmsk','itmpara') as $key) $data[$key.$i]=$item[$key];
		return $i;
	}
	$item['pls']=(int)$data['pls'];
	$item['itmpara']=is_array($item['itmpara']) ? json_encode($item['itmpara'],JSON_UNESCAPED_UNICODE) : $item['itmpara'];
	$db->array_insert($tablepre.'mapitem',$item);
	$log .= '背包已满，<span class="yellow">'.$item['itm'].'</span>放在了脚下。<br>';
	return 0;
}

function ending_routes_npc_dead(&$victim,&$killer)
{
	global $db,$tablepre,$gamevars,$now;
	if(!ending_routes_enabled()) return;
	include_once GAME_ROOT.'./include/game/quest_sendo.func.php';
	include_once GAME_ROOT.'./include/game/dissociation.func.php';
	sendo_npc_dead($victim,$killer);
	$type=(int)$victim['type']; $pid=(int)$victim['pid'];
	if($type===34 && $victim['name']==='白凰的符灵幻象') return;
	if($type===34 && $victim['name']==='白凰') $db->query("DELETE FROM {$tablepre}players WHERE type=34 AND name='白凰的符灵幻象'");
	if($type===34 && empty($gamevars['jiaotou_final']) && !empty($gamevars['jiaotou_challenge'])) {
		$alive=$db->result($db->query("SELECT COUNT(*) FROM {$tablepre}players WHERE type=34 AND name<>'白凰的符灵幻象' AND hp>0 AND pid<>$pid"),0);
		if(!$alive) {
			$gamevars['jiaotou_final']=1;
			addnpc(19,0,1); addnpc(19,1,1);
			addnews($now,'jiaotou_final');
		}
	}
	dissociation_npc_dead($victim,$killer);
	save_gameinfo();
}

function ending_routes_summon(&$data,$itmn)
{
	global $gamevars,$log,$now;
	if(!ending_routes_enabled()) return false;
	$item=$data['itm'.$itmn];
	if(!in_array($item,array('挑战者之印','挑战者之印Ⅱ','红杀教头挑战状','【我想要领略真正的红杀之力】'),true)) return false;
	$key=in_array($item,array('红杀教头挑战状','【我想要领略真正的红杀之力】'),true) ? 'jiaotou_challenge' : 'ibis_challenge';
	if(!empty($gamevars[$key])) { $log.='挑战已经开始，请寻找战场上尚未击破的对手。<br>'; return true; }
	$gamevars[$key]=1;
	if($key==='jiaotou_challenge') {
		for($i=0;$i<8;$i++) {
			$ids=addnpc(34,$i,1);
			if($i===4 && !empty($ids[0])) {
				$shepherd=fetch_playerdata_by_pid($ids[0]);
				foreach(array(0,1) as $sheep) addnpc(42,$sheep,1,0,array('pls'=>(int)$shepherd['pls'],'pgroup'=>(int)$shepherd['pgroup']));
			}
		}
		addnews($now,'jiaotou_challenge',$data['name']);
		$log.='八位红杀教头接受了挑战。<br>';
	} else {
		for($i=3;$i<=5;$i++) addnpc(7,$i,1);
		addnews($now,'secphase',$data['name'],$data['nick']);
		$log.='HOROU、ORIHIME 与 SAYA 已进入战场。收集三人的社员ID卡吧！<br>';
	}
	destory_single_item($data,$itmn,1); save_gameinfo();
	return true;
}

function ending_routes_legacy_enter(&$data)
{
	global $log;
	if(!ending_routes_enabled() || $data['pgroup']!=0 || $data['pls']!=34 || $data['art']!=='Untainted Glory' || !$data['arts']) { $log.='这里没有向你开启的旧日通道。<br>'; return; }
	$data['pgroup']=4; $data['pls']=134;
	$data['bid']=0; $data['action']='';
	lost_searchmemory('all',$data);
	$log.='Untainted Glory 映亮了代码深处的门。你走进了隐藏的英灵殿。<br>';
}

function ending_routes_xharmer(&$data,$accept)
{
	if(!ending_routes_enabled() || empty($data['clbpara']['xharmer_event']) || !empty($data['clbpara']['xharmer_chosen'])) return;
	$data['clbpara']['xharmer_chosen']=1;
	if(!$accept) return;
	$char=(int)($data['clbpara']['randver1'] ?? 0)%3;
	$data['clbpara']['xharmer_platform']=array('char'=>$char);
	$npc=get_addnpcinfo(); $source=array_merge(get_npcinit(),$npc[7],$npc[7]['sub'][$char+3]);
	$source['hp']=$source['mhp']; $source['sp']=$source['msp'];
	foreach(array('wp','wk','wg','wc','wd','wf') as $field) if(empty($source[$field])) $source[$field]=$source['skill'];
	$skills=array(array('npc_overrainbow','npc_7colour'),array('npc_wrelease','npc_flying'),array('npc_mecstasy','npc_perfectspy'));
	ending_routes_give($data,array('itm'=>'便携式化身平台','itmk'=>'💝','itmpara'=>array('IsPlatformItem'=>1,'platformPlayerMode'=>'REPLAY','platformPlayerData'=>$source,'platformSkills'=>$skills[$char],'platformTransformTime'=>40+(int)($data['clbpara']['randver2'] ?? 0)%21,'platformHideDuration'=>1)));
}

function ending_routes_sanbu(&$data)
{
	global $now,$db,$tablepre,$gamevars,$aidata,$log;
	if(!ending_routes_enabled() || !$data || !empty($data['type']) || $data['pgroup']!=0 || $data['hp']<=0 || ($gamevars['sanbu_next'] ?? 0)>$now || rand(1,100)>20) return false;
	$res=$db->query("SELECT pid FROM {$tablepre}players WHERE type=34 AND hp>0");
	while($row=$db->fetch_array($res)) {
		$npc=fetch_playerdata_by_pid($row['pid']);
		if(($npc['clbpara']['er_sub'] ?? -1)!==5 || (int)$npc['horizon']!==(int)$data['horizon']) continue;
		$npc['pls']=$data['pls']; $npc['pgroup']=$data['pgroup'];
		$gamevars['sanbu_next']=$now+(int)erc_config('sanbu_interval',90);
		player_save($npc); save_gameinfo(); $aidata=$npc;
		$log.='提灯在身后忽然亮起。三步从你的影子里走了出来。<br>';
		return true;
	}
	return false;
}

function ending_routes_encounter_allowed($data,$enemy)
{
	if(!ending_routes_enabled()) return true;
	if((int)$data['pgroup']!==(int)$enemy['pgroup']) return false;
	$p=get_clbpara($enemy['clbpara']);
	if(!empty($p['sendo_captured'])) return false;
	if((int)$data['pgroup']===3 && (int)$enemy['type']>0 && (int)$data['horizon']!==(int)$enemy['horizon']) return false;
	return true;
}
