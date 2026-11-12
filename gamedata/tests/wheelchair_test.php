<?php
if(!defined('IN_GAME')) exit('Access Denied');
if(!defined('GAME_ROOT')) define('GAME_ROOT',dirname(__DIR__,2).DIRECTORY_SEPARATOR);
if(!defined('CURSCRIPT')) define('CURSCRIPT','wheelchair_test');
require_once GAME_ROOT.'include/global.func.php';
require_once GAME_ROOT.'include/resources.func.php';
require_once GAME_ROOT.'include/game/revclubskills.func.php';
require_once GAME_ROOT.'include/game/fcg_skills.func.php';
require_once GAME_ROOT.'include/game.func.php';
require GAME_ROOT.'gamedata/cache/resources_1.php';
require GAME_ROOT.'gamedata/cache/gamecfg_1.php';
require GAME_ROOT.'gamedata/cache/combatcfg_1.php';
require GAME_ROOT.'gamedata/cache/clubskills_1.php';
require GAME_ROOT.'gamedata/cache/tooltip_1.php';
require GAME_ROOT.'gamedata/cache/titles_1.php';
require GAME_ROOT.'gamedata/cache/wheelchair.php';
require_once GAME_ROOT.'include/game/wheelchair.func.php';
require_once GAME_ROOT.'include/game/wheelchair_action.func.php';
require_once GAME_ROOT.'include/game/item.skillbook.php';
require_once GAME_ROOT.'include/game/item.weapon.php';
require_once GAME_ROOT.'include/game/item.recovery.php';
require_once GAME_ROOT.'include/game/depot.func.php';

// 隔离旧 itemuse 分派器加载的骰子／占卜 Web 入口；实际装备、补给、技能书处理器仍执行。
function itemuse($i,&$data) {
	$kind=$data['itmk'.$i];
	if(strpos($kind,'W')===0 || strpos($kind,'D')===0) item_weapon($i,$data);
	elseif($kind==='HH') item_recovery_health($i,$data);
	elseif($kind==='HS') item_recovery_stamina($i,$data);
	elseif($kind==='HB') item_recovery_both($i,$data);
	elseif(strpos($kind,'V')===0) item_skillbook($i,$data);
	else throw new RuntimeException('Unexpected item use in test: '.$kind);
}

function wc_assert($ok,$label) {
	if(!$ok) { fwrite(STDERR,"FAIL: $label\n"); exit(1); }
	echo "PASS: $label\n";
}
class WheelchairResult {
	public $rows; public $pos=0;
	function __construct($rows=array()) { $this->rows=$rows; }
}
class WheelchairDb {
	public $queries=array(),$depot=array(),$inserted=array(),$shop=array(),$players=array(),$credits=0;
	function query($sql) {
		$this->queries[]=$sql;
		if(strpos($sql,'FROM test_itemdepot')!==false) return new WheelchairResult(array_values($this->depot));
		if(preg_match("/DELETE FROM test_itemdepot WHERE iid='(\d+)'/",$sql,$m)) unset($this->depot[(int)$m[1]]);
		if(strpos($sql,'FROM test_shopitem')!==false) {
			if(preg_match("/sid = '(\d+)'/",$sql,$m)) return new WheelchairResult(isset($this->shop[$m[1]]) ? array($this->shop[$m[1]]) : array());
			return new WheelchairResult(array_values($this->shop));
		}
		if(preg_match("/SET num = '(-?\d+)' WHERE sid = '(\d+)'/",$sql,$m)) $this->shop[$m[2]]['num']=(int)$m[1];
		if(preg_match('/SET credits2=credits2\+(\d+)/',$sql,$m)) $this->credits+=(int)$m[1];
		if(preg_match("/SELECT name,type,race,clbpara FROM test_players WHERE name=X'([a-f0-9]*)'/",$sql,$m)) { $name=hex2bin($m[1]); return new WheelchairResult(isset($this->players[$name]) ? array($this->players[$name]) : array()); }
		if(strpos($sql,'FROM test_chat')!==false) {
			$rows=array(); for($i=0;$i<4;$i++) $rows[]=array('time'=>1000-$i,'send'=>'小兵'.$i,'recv'=>16);
			return new WheelchairResult($rows);
		}
		if(strpos($sql,'FROM test_newsinfo')!==false) {
			$rows=array(); for($i=0;$i<4;$i++) $rows[]=array('time'=>1000-$i,'news'=>'death24','a'=>'小兵'.$i,'b'=>90,'c'=>'0|Hunter');
			return new WheelchairResult($rows);
		}
		return new WheelchairResult();
	}
	function fetch_array($result) { return $result->rows[$result->pos++] ?? false; }
	function num_rows($result) { return count($result->rows); }
	function result($result,$index=0) { $row=$result->rows[$index] ?? array(); return $row ? reset($row) : ''; }
	function array_insert($table,$row) { $this->inserted[]=array($table,$row); return true; }
	function insert_id() { return count($this->inserted); }
	function array_update($table,$row,$where) { return true; }
}
function wc_player() {
	$p=array('pid'=>1,'name'=>'Tester','nick'=>9301,'type'=>0,'club'=>3,'hp'=>400,'mhp'=>400,'sp'=>400,'msp'=>400,
		'att'=>100,'def'=>100,'lvl'=>1,'exp'=>0,'money'=>1000,'skillpoint'=>0,'killnum'=>0,'pls'=>5,'pgroup'=>0,
		'pose'=>1,'tactic'=>2,'horizon'=>0,'inf'=>'','rp'=>0,'rage'=>0,'teamID'=>'','action'=>'','bid'=>0,'state'=>0,
		'wp'=>0,'wk'=>0,'wc'=>50,'wg'=>0,'wd'=>0,'wf'=>0,'clbpara'=>array('skill'=>array(),'skillpara'=>array(),'BGMBrand'=>'','setitems'=>array()),'itmnumlimit'=>6);
	foreach(array('wep','wep2','arb','arh','ara','arf','art','itm0','itm1','itm2','itm3','itm4','itm5','itm6') as $slot) wheelchair_set_item($p,$slot,array());
	wheelchair_set_item($p,'wep',array('itm'=>'测试飞镖','itmk'=>'WC','itme'=>100,'itms'=>100));
	return $p;
}
function wc_bind($p) {
	$GLOBALS['pdata']=$p;
	foreach(array_keys($p) as $key) $GLOBALS[$key]=&$GLOBALS['pdata'][$key];
	$GLOBALS['log']=''; $GLOBALS['cmd']=''; $GLOBALS['mode']='command';
}
function wc_item($name,$kind,$effect=1,$count=1,$sk='',$para='') {
	return array('itm'=>$name,'itmk'=>$kind,'itme'=>$effect,'itms'=>$count,'itmsk'=>$sk,'itmpara'=>$para);
}
$db=new WheelchairDb(); $tablepre='test_'; $gtablepre='account_';
$now=1000; $gamecfg=1; $gamevars=array(); $current_ruleset_config=array('club_skills'=>2,'title_system'=>2);
$areanum=0; $areaadd=1; $arealist=range(0,100); $areatime=2000; $hack=0; $coldtimeon=0; $gamestate=20;
$log=''; $cmd=''; $mode='command';

wc_bind(wc_player());
wheelchair_set_item($pdata,'itm1',wc_item('面包','HH',120,15));
wheelchair_set_item($pdata,'itm2',wc_item('矿泉水','HS',140,15));
wheelchair_set_item($pdata,'itm3',wc_item('原入场礼物','X',1,1,'',array('keep'=>'yes')));
$stored=wheelchair_entry($pdata);
wc_assert(count($stored)===3 && $stored[0]['itme']===180 && $stored[0]['itms']===20,'头衔保存原补给和随机物品，强化面包水');
wc_assert($stored[2]['itmpara']['keep']==='yes' && $stored[2]['itmpara']['wheelchair_free_depot']===1,'寄存保留物品完整参数');
for($i=1;$i<=6;$i++) wc_assert($pdata['itmk'.$i]==='VS' && $pdata['itmsk'.$i]==='v' && isset($tps_name[$pdata['itm'.$i]]),'技能书 '.$i.' 入包、绑定且有tooltip');
wc_assert(wheelchair_penalty_count($pdata)===0,'未读书不处罚');
wheelchair_command($pdata,'wc_hunt_set',array('wc_hunt_kind'=>'minion','wc_hunt_target'=>''));
wc_assert(empty($clbpara['wheelchair']['hunt']),'未学习技能不能用伪造指令开启辅助');
item_skillbook(6,$pdata);
wc_assert(wheelchair_has($pdata,'wc_review') && wheelchair_penalty_count($pdata)===0 && !$pdata['itms6'],'复盘阅读成功且无处罚');
for($i=1;$i<=5;$i++) {
	item_skillbook($i,$pdata);
	wc_assert(wheelchair_penalty_count($pdata)===$i && wheelchair_credit_gain(320,'Tester')===320/(1<<$i),'第 '.$i.' 本当场叠加收益折扣');
}
wc_assert(getclubskill('wc_grow',$clbpara)===0 && wheelchair_penalty_count($pdata)===5,'重复获得不重复惩罚');
wheelchair_command($pdata,'wc_toggle_wc_grow',array());
wc_assert(!wheelchair_has($pdata,'wc_grow') && wheelchair_penalty_count($pdata)===5,'暂停只停止辅助，不撤销折扣');
lostclubskill('wc_grow',$clbpara);
wc_assert(wheelchair_penalty_count($pdata)===5,'失去技能不能退还本局惩罚');
$reloaded=json_decode(json_encode($pdata),true); $reloaded['clbpara']=json_encode($pdata['clbpara']);
wc_assert(wheelchair_credit_gain(320,'Tester',$reloaded)===10,'保存再加载后折扣保持');
$db->players['Other']=array('clbpara'=>json_encode($clbpara));
wc_assert(wheelchair_credit_gain(320,'Other')===10 && wheelchair_credit_gain(320,'Nobody')===320,'按奖励接收者读取本局技能，不误罚其他人');
wheelchair_award_credits2('Tester',320);
wc_assert($db->credits===10 && wheelchair_credit_gain(1,'Tester')===0,'真实奖励SQL增加折后金额，允许取整为零');

wc_bind(wc_player());
$llm_stored=wheelchair_entry($pdata,true);
wc_assert(count($clbpara['skill'])===6 && wheelchair_penalty_count($pdata)===5 && !$llm_stored,'LLM入场直接学会六项，无多余技能书');
wc_assert(wheelchair_entry_notice($pdata) && !wheelchair_entry_notice($pdata),'入场说明仅消费一次');
$current_ruleset_config['club_skills']=0;
$disabled=wc_player(); wheelchair_entry($disabled,true);
wc_assert(empty($disabled['clbpara']['skill']) && !wheelchair_has($pdata,'wc_review'),'禁用社团技能的规则集不启用轮椅');
$current_ruleset_config['club_skills']=2;

wc_bind(wc_player()); wheelchair_entry($pdata);
$before=count($db->inserted); itemdrop('itm1',$pdata);
wc_assert(!$pdata['itms1'] && count($db->inserted)===$before && wheelchair_penalty_count($pdata)===0,'丢弃绑定技能书销毁，不落地、不处罚');
foreach($stored as $i=>$item) { $item['iid']=$i+1; $item['itmpara']=json_encode($item['itmpara']); $db->depot[$i+1]=$item; }
$money=0; $depots=array(5);
depot_load(0);
wc_assert($pdata['itm1']==='面包' && $money===0 && !isset(get_clbpara($pdata['itmpara1'])['wheelchair_free_depot']),'安全箱零元取出，入包并移除免费标记');
$count=count($db->depot);
depot_load(99);
wc_assert(count($db->depot)===$count,'非法安全箱索引不改变库存');
$db->depot=array(9=>array_merge(wc_item('普通补给','HH',120,1),array('iid'=>9)));
depot_load(0);
wc_assert(isset($db->depot[9]),'普通寄存仍要求保管费');

wc_bind(wc_player()); getclubskill('wc_prepare',$clbpara);
$hp=50; $sp=30;
wheelchair_set_item($pdata,'itm1',wc_item('面包','HH',180,3));
wheelchair_set_item($pdata,'itm2',wc_item('水','HS',180,3));
wheelchair_prepare($pdata);
wc_assert($hp>=280 && $sp>=140 && $pdata['itms1']<3,'整备通过真实道具使用补充HP/SP并扣库存');
wheelchair_set_item($pdata,'itm0',wc_item('更强飞镖','WC',500,100));
$original_weapon=$wep; wheelchair_replacement_power($pdata,wheelchair_item($pdata,'itm0'));
wc_assert($wep===$original_weapon,'鉴定复制解除引用，不能直接改变实际装备');
getclubskill('wc_appraise',$clbpara); $inventory_panel=wheelchair_panel($pdata);
wc_assert($wep===$original_weapon && strpos($inventory_panel,'更强飞镖：')!==false,'完整物品评分面板展示候选装备且不改变装备');
$old=wheelchair_power($pdata,true); wheelchair_handle_item($pdata);
wc_assert($wep==='更强飞镖' && wheelchair_power($pdata,true)>$old,'整备调用真实装备行为换装');
wheelchair_handle_item($pdata);
wc_assert(empty($itms0) && !empty($clbpara['smeo']),'旧装备正常丢弃并保留视野');
wheelchair_set_item($pdata,'itm0',wc_item('危险补给','PH50',999,5));
wheelchair_handle_item($pdata);
wc_assert(empty($itms0) && $hp>=280,'不把毒补给当作自动治疗');
$full=wheelchair_power($pdata,true); $hp=1;
wc_assert(wheelchair_power($pdata)<$full/10 && wheelchair_power($pdata,true)===$full,'低生命显著降低当前战力，不改变完好战力');
$hp=0; wc_assert(wheelchair_power($pdata)===0,'死亡战力为零');

wc_bind(wc_player()); getclubskill('wc_route',$clbpara);
$recipes=wheelchair_recipes(); $master=''; $ball='';
foreach($recipes as $id=>$r) {
	if($r['result'][0]==='《小黄的大师球》' && count($r['stuff'])===4) $master=$id;
	if($r['result'][0]==='《小黄的精灵球》') $ball=$id;
}
wc_assert($master!=='' && $ball!=='','读取真实多分支大师球配方');
wheelchair_route_pick($pdata,$master);
$names=wheelchair_plan_names(wheelchair_route_plan($pdata));
wc_assert(in_array('《小黄的草帽》',$names,true) && in_array('凤凰之羽',$names,true) && in_array('露琪亚之羽',$names,true) && in_array('《小黄的常磐之力》',$names,true),'递归展开草帽、两种羽毛、技能书的完整链');
$next=wheelchair_plan_next(wheelchair_route_plan($pdata),$pdata);
wc_assert(in_array($next['name'],array('《小黄的草帽》','《小黄的钓鱼竿》','《小黄的行军靴》'),true),'大师球路线先发展适配的中间武器，再筹钱完成后续链');
wc_assert(in_array(16,wheelchair_sources('《小黄的草帽》'),true),'素材地图来源复用当前规则配置');
wheelchair_route_pick($pdata,$ball);
wheelchair_set_item($pdata,'itm1',wc_item('《小黄的草帽》','DH',50,70));
wheelchair_set_item($pdata,'itm2',wc_item('《小黄的钓鱼竿》','DA',50,70));
wheelchair_set_item($pdata,'itm3',wc_item('《小黄的行军靴》','DF',50,70));
wc_assert(wheelchair_material($pdata,wheelchair_item($pdata,'itm1')),'目标素材不误丢');
wheelchair_route_step($pdata);
wc_assert(in_array('《小黄的精灵球》',$pdata,true) && $pdata['itms2']===0 && $pdata['itms3']===0,'通过真实合成函数消耗三件素材并产生精灵球');
wheelchair_route_step($pdata);
wc_assert(empty($clbpara['wheelchair']['recipe']),'达到产物后停止导航');
// 正在收集后续书本时，可把精灵球装备起来打钱，而不是永远留在包里。
wc_bind(wc_player()); getclubskill('wc_route',$clbpara); getclubskill('wc_prepare',$clbpara); wheelchair_route_pick($pdata,$master);
wheelchair_set_item($pdata,'itm1',wc_item('《小黄的精灵球》','WC',300,'∞'));
wheelchair_prepare($pdata);
wc_assert($wep==='《小黄的精灵球》' && !wheelchair_junk($pdata,wheelchair_item($pdata,'wep')),'缺后续素材时装备中间产物发育，同时保留素材保护');
// 已经齐全的装备素材逐件卸下后，整备不能反复穿回去阻塞合成。
wc_bind(wc_player()); getclubskill('wc_route',$clbpara); getclubskill('wc_prepare',$clbpara); wheelchair_route_pick($pdata,$ball);
wheelchair_set_item($pdata,'arh',wc_item('《小黄的草帽》','DH',50,70));
wheelchair_set_item($pdata,'ara',wc_item('《小黄的钓鱼竿》','DA',50,70));
wheelchair_set_item($pdata,'arf',wc_item('《小黄的行军靴》','DF',50,70));
for($i=0;$i<5 && !empty($clbpara['wheelchair']['recipe']);$i++) { wheelchair_prepare($pdata); wheelchair_route_step($pdata); }
wc_assert(empty($clbpara['wheelchair']['recipe']) && in_array('《小黄的精灵球》',$pdata,true),'穿戴中的素材可逐件卸下合成，不陷入整备重穿循环');
// 用配置中真实的叶子物品模拟正常探索收获，逐步调用真实拾取／合成，不直接创建中间产物。
wc_bind(wc_player()); getclubskill('wc_route',$clbpara); wheelchair_route_pick($pdata,$master);
$leaves=array();
foreach(openfile(config('mapitem',1)) as $line) {
	$r=explode(',',trim($line));
	if(count($r)>=8 && is_numeric($r[0])) $leaves[wheelchair_normal_name($r[3])]=wc_item($r[3],$r[4],$r[5],$r[6],$r[7]);
}
foreach(openfile(config('shopitem',1)) as $line) {
	$r=explode(',',trim($line));
	if(count($r)>=9 && (int)$r[0]>0) $leaves[wheelchair_normal_name($r[4])]=wc_item($r[4],$r[5],$r[6],$r[7],$r[8]);
}
$mixsteps=0;
for($step=0;$step<40 && !empty($clbpara['wheelchair']['recipe']);$step++) {
	$next=wheelchair_plan_next(wheelchair_route_plan($pdata),$pdata);
	if($next['kind']==='need') {
		wc_assert(isset($leaves[$next['name']]),'大师球叶子来源存在：'.$next['name']);
		wheelchair_set_item($pdata,'itm0',$leaves[$next['name']]);
		wheelchair_handle_item($pdata);
		wc_assert(empty($itms0),'素材可正常收纳：'.$next['name']);
	} else { wheelchair_route_step($pdata); $mixsteps++; }
}
wc_assert(empty($clbpara['wheelchair']['recipe']) && in_array('《小黄的大师球》',$pdata,true) && $mixsteps>=5,'完整双羽大师球链自动合成结束');
$reloaded=json_decode(json_encode($pdata),true);
wc_assert($reloaded['clbpara']['wheelchair']['target']==='《小黄的大师球》','导航状态可随玩家数据保存');
// 导航采购调用真实 itembuy，钱和店铺库存都会减少；买到的合成技能书不会自动阅读。
wc_bind(wc_player()); getclubskill('wc_route',$clbpara); wheelchair_route_pick($pdata,$master);
wheelchair_set_item($pdata,'itm1',wc_item('凤凰之羽','WK',20,200,'u'));
wheelchair_set_item($pdata,'itm2',wc_item('露琪亚之羽','WK',50,200,'i'));
wheelchair_set_item($pdata,'itm3',wc_item('《小黄的超级球》','WC',386,'∞'));
$db->shop=array(1=>array('sid'=>1,'kind'=>10,'num'=>10,'price'=>5500,'area'=>0,'item'=>'《小黄的常磐之力》','itmk'=>'VC','itme'=>300,'itms'=>1,'itmsk'=>'','itmpara'=>''));
$shops=array(5); $money=6000; $oldprof=$wc;
wheelchair_route_step($pdata);
wc_assert($money===500 && $db->shop[1]['num']===9 && $wc===$oldprof && in_array('《小黄的常磐之力》',$pdata,true),'采购扣钱扣库存，素材书留作合成');
$oldmoney=$money; $oldnum=$db->shop[1]['num']; wheelchair_route_step($pdata);
wc_assert($money===$oldmoney && $db->shop[1]['num']===$oldnum,'重试导航不重复买已经齐全的素材');
$db->shop=array();
$stock=array(); $budget=80;
$cycle=array('a'=>array('stuff'=>array('B'),'result'=>array('A')),'b'=>array('stuff'=>array('A'),'result'=>array('B')));
$node=wheelchair_plan_node('A',$stock,$cycle,array(),$budget);
wc_assert(wheelchair_plan_next($node)['kind']==='blocked','循环配方有界退出');
$cycle['c']=array('stuff'=>array('C'),'result'=>array('B'));
$stock=array(); $budget=80; $node=wheelchair_plan_node('A',$stock,$cycle,array(),$budget);
wc_assert(wheelchair_plan_next($node)['name']==='C','材料有循环时改用可行的其他公开配方');
// 同名材料需要不同的物理槽；手持槽可直接参与原生合成。
wc_bind(wc_player()); getclubskill('wc_route',$clbpara);
foreach($recipes as $id=>$r) if($r['result'][0]==='D.毒针') { wheelchair_route_pick($pdata,$id); break; }
wheelchair_set_item($pdata,'itm1',wc_item('毒针','WK',32,20,'p'));
wheelchair_set_item($pdata,'itm0',wc_item('毒针','WK',32,20,'p'));
wheelchair_search($pdata);
wc_assert(in_array('D.毒针',$pdata,true) && empty($itms0),'手持同名素材直接合成，避免合并后卡住');

wc_bind(wc_player()); getclubskill('wc_prepare',$clbpara);
$hp=10; $sp=10; $movesp=10000; $movehp=2;
wheelchair_search($pdata);
wc_assert(!empty($clbpara['wheelchair']['health_warning']) && strpos($log,'再动下去要出人命')===false,'准备失败第一次提醒，未搜索');
$log=''; wheelchair_search($pdata);
wc_assert(strpos($log,'再动下去要出人命')!==false,'第二次确实进入原生搜索检查，不永久拦截');
wc_bind(wc_player()); getclubskill('wc_prepare',$clbpara); $hp=10; $sp=10;
$clbpara['wheelchair']['health_warning']=1;
for($i=1;$i<=6;$i++) wheelchair_set_item($pdata,'itm'.$i,wc_item('绑定物'.$i,'Y',1,1,'v'));
wheelchair_set_item($pdata,'itm0',wc_item('保留物','Y',1,1));
wheelchair_search($pdata);
wc_assert($itms0===1 && !empty($clbpara['wheelchair']['held_warning']),'满包且无法整理时先保留手持物并提示');
$log=''; wheelchair_search($pdata);
wc_assert(empty($itms0) && !empty($clbpara['smeo']) && strpos($log,'再动下去要出人命')!==false,'第二次放下手持物、保留视野并执行搜索检查');
wc_assert(in_array(16,wheelchair_danger_places($pdata),true),'从公开遗言地点和击杀新闻识别密集击杀区域');
$areatime=$now+30; $arealist=array(0,5,8,16,20); $areaadd=1;
wc_assert(!in_array(5,wheelchair_safe_places($pdata),true),'避开即将成为禁区的地点');
$areatime=2000;

wc_bind(wc_player()); getclubskill('wc_grow',$clbpara); getclubskill('s_hp',$clbpara); $skillpoint=5;
wheelchair_grow($pdata);
wc_assert($skillpoint===4 && $mhp===403,'精进调用原生升级并扣点');
$skillpoint=2; wheelchair_grow($pdata);
wc_assert($skillpoint===2,'精进保留两点自愈储备');
wc_assert(wheelchair_hunt_match(array('type'=>90,'name'=>'兵'),array('kind'=>'minion','target'=>'90')) && !wheelchair_hunt_match(array('type'=>0,'name'=>'别人'),array('kind'=>'player','target'=>'目标')),'追猎只匹配所选种类或姓名');
getclubskill('wc_review',$clbpara);
for($i=0;$i<15;$i++) { $before=wheelchair_review_before($pdata); $hp--; $log.='燃烧生命探索<br>'; wheelchair_review_after($pdata,$before,'search'); }
wc_assert(count($clbpara['wheelchair']['review'])===12 && strpos(end($clbpara['wheelchair']['review']),'生命-1')!==false,'复盘保留十二步真实得失和原因');
$clbpara['wheelchair']['review'][]='<script>alert(1)</script>';
wc_assert(strpos(wheelchair_panel($pdata),'<script>')===false,'复盘输出转义');
echo "Wheelchair regression tests passed.\n";
