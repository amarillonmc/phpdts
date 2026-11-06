<?php
if(!defined('IN_GAME')) exit('Access Denied');
define('GAME_ROOT',dirname(__DIR__,2).DIRECTORY_SEPARATOR);
error_reporting(E_ALL);
function c25_assert($ok,$label) {
	if(!$ok) throw new Exception('FAIL: '.$label);
	echo 'PASS: '.$label."\n";
}
function config($file,$version) { return GAME_ROOT.'gamedata/cache/'.$file.'_'.$version.'.php'; }
function get_itmpara($value) { return is_array($value) ? $value : (json_decode($value,true) ?: Array()); }
function get_clbpara($value) { return get_itmpara($value); }
function get_itmsk_array($value) { return str_split((string)$value); }
function reload_equip_items(&$data) { $GLOBALS['reloads']++; }
function fetch_playerdata_by_pid($pid) { return $GLOBALS['players'][$pid] ?? false; }
function player_save($data) { $GLOBALS['players'][$data['pid']]=$data; }
function logsave($pid,$now,$text,$kind) { $GLOBALS['notices'][]=$pid; }
class Club25Rows { public $rows; public function __construct($rows) { $this->rows=array_values($rows); } }
class Club25GroundDB {
	public $rows=Array('test_maptrap'=>Array(),'test_mapitem'=>Array());
	public $next=Array('test_maptrap'=>1,'test_mapitem'=>1);
	public $affected=0;
	public function array_insert($table,$row) {
		$field=$table==='test_maptrap' ? 'tid' : 'iid';
		$row[$field]=$this->next[$table]++;
		$this->rows[$table][$row[$field]]=$row;
	}
	public function query($sql) {
		if(preg_match('/^SELECT \* FROM (test_maptrap|test_mapitem) WHERE (.*)$/',$sql,$m)) {
			$rows=$this->rows[$m[1]];
			if(preg_match("/^(tid|iid)='([0-9]+)'$/",$m[2],$id)) $rows=isset($rows[$id[2]]) ? Array($rows[$id[2]]) : Array();
			elseif($m[2]!=="itms != '0'") throw new Exception($sql);
			return new Club25Rows($rows);
		}
		if(preg_match("/^DELETE FROM (test_maptrap|test_mapitem) WHERE (tid|iid)='([0-9]+)' AND pls='([0-9]+)'$/",$sql,$m)) {
			$this->affected=0;
			if(isset($this->rows[$m[1]][$m[3]]) && $this->rows[$m[1]][$m[3]]['pls']==$m[4]) {
				unset($this->rows[$m[1]][$m[3]]); $this->affected=1;
			}
			return true;
		}
		throw new Exception('Unexpected SQL: '.$sql);
	}
	public function fetch_array($result) { return array_shift($result->rows); }
	public function affected_rows() { return $this->affected; }
}
function c25_player($pid=1) {
	$data=Array('pid'=>$pid,'name'=>'猫'.$pid,'club'=>25,'type'=>0,'hp'=>500,'mhp'=>500,'sp'=>200,'msp'=>200,
		'att'=>100,'def'=>100,'wd'=>100,'exp'=>0,'lvl'=>5,'rage'=>50,'pls'=>2,'pgroup'=>0,'teamID'=>'',
		'action'=>'','bid'=>0,'clbpara'=>Array(),'wep'=>'枪','wepk'=>'WG','wepe'=>100,'weps'=>6,'wepsk'=>'p','weppara'=>'');
	for($i=0;$i<=6;$i++) c25_slot($data,$i,'','',0,0);
	return $data;
}
function c25_slot(&$data,$slot,$name,$kind,$effect,$count,$para='',$attr='') {
	foreach(Array('itm'=>$name,'itmk'=>$kind,'itme'=>$effect,'itms'=>$count,'itmsk'=>$attr,'itmpara'=>$para) as $key=>$val) $data[$key.$slot]=$val;
}
require GAME_ROOT.'include/game/club25.func.php';
$db=new Club25GroundDB; $tablepre='test_'; $nosta='∞'; $log=''; $now=1000; $baseexp=1000; $reloads=0; $notices=Array();
$plsinfo=Array(2=>'广场',3=>'学校'); $hplsinfo=Array();
$data=c25_player(); $players=Array(1=>$data,2=>c25_player(2));
c25_assert(club25_skill('feed',$data)===1 && $data['rage']===25 && $data['itmk1']==='HB' && $data['itme1']===70 && $data['itms1']===3,'投喂真实扣怒并产生70效三份补给');
c25_assert($data['exp']===0 && $data['wd']===100,'制作不产生经验或熟练');
for($i=2;$i<=6;$i++) c25_slot($data,$i,'占位','Y',1,1);
$before=$data; c25_assert(!club25_skill('feed',$data) && $data===$before,'背包满时不扣怒不覆盖物品');
$data=c25_player(); $data['rage']=24; $before=$data;
c25_assert(!club25_skill('feed',$data) && $data===$before,'怒气不足不能投喂');
$data=c25_player(); $data['action']='enemy'; $data['bid']=2; $before=$data;
c25_assert(!club25_skill('modify',$data,'wep') && $data===$before && club25_skill('board',$data)===1,'遭遇中不可加工，但可只读布场');
$data['action']='focus'; $before=$data;
c25_assert(!club25_skill('feed',$data) && $data===$before,'视野中的活人不能绕过遭遇限制');
$players[2]['hp']=0; c25_assert(club25_skill('feed',$data)===1,'尸体视野仍可使用技能');
$players[2]['hp']=500; $data=c25_player();
c25_assert(club25_skill('modify',$data,'wep')===1 && $data['weps']===3 && count($db->rows['test_maptrap'])===3,'改装扣三耐并落下一母两副雷');
$mine=$db->rows['test_maptrap'][1]; $child=$db->rows['test_maptrap'][2];
c25_assert($mine['itme']===180 && $child['itme']===90 && (int)$mine['itmsk']===1,'枪械按独立倍率改装，不继承武器毒属性');
c25_assert(get_itmpara($mine['itmpara'])['c25_mine']['bonus']===50 && get_itmpara($child['itmpara'])['c25_mine']['bonus']===25,'母副雷爆熟加成分别快照');
c25_assert(club25_skill('withdraw',$data,'t1')===1 && $data['itmk1']==='TN','当地回收保留可埋设形态');
$before=count($db->rows['test_maptrap']); $data['wd']=900;
club25_skill('deploy',$data,'1');
$last=end($db->rows['test_maptrap']);
c25_assert(count($db->rows['test_maptrap'])===$before+1 && get_itmpara($last['itmpara'])['c25_mine']['bonus']===50,'回收重布不再次复制或重算爆熟');
club25_skill('withdraw',$data,'t2'); $before=count($db->rows['test_maptrap']); club25_skill('deploy',$data,'1');
c25_assert(count($db->rows['test_maptrap'])===$before+1,'副雷回收重布不增殖');
$before=$data; c25_assert(!club25_skill('withdraw',$data,'t2') && $data===$before,'重复领取已删除地雷失败');
$data['weps']=$nosta; club25_skill('modify',$data,'wep');
c25_assert($data['weps']===0 && $data['weppara']==='' && $reloads===1,'无限耐久武器整体消耗并刷新装备');
$data=c25_player(); c25_slot($data,0,'发现的刀','WK',100,3,'','V'); $before=$data;
c25_assert(!club25_skill('modify',$data,'0') && $data===$before,'禁止改装绑定物品');
c25_slot($data,0,'发现的刀','WK',100,2); $before=$data;
c25_assert(!club25_skill('modify',$data,'0') && $data===$before,'耐久不足时不扣资源');
c25_slot($data,0,'发现的刀','WK',100,3); club25_skill('modify',$data,'0');
c25_assert($data['itms0']===0 && $data['clbpara']['c25_catstep']===1,'itm0可直接改装并获得一层猫步');
c25_slot($data,2,'无限陷阱','TN',20,$nosta); club25_skill('deploy',$data,'2');
c25_assert($data['itms2']===$nosta,'原有无限使用陷阱埋设后仍保留');
c25_slot($data,1,'雪糕','HB',70,3,Array('custom'=>'kept')); $c25_kind='poison';
club25_skill('hide',$data,'1'); club25_skill('trip',$data,'1'); $para=get_itmpara($data['itmpara1']);
c25_assert($data['sp']===160 && $para['custom']==='kept' && $para['c25_hide']['damage']===100 && $para['c25_trip']['mode']==='poison','捉迷藏与诡雷独立并存且保留原参数');
$before=$data; c25_assert(!club25_skill('hide',$data,'1') && $data===$before,'同一补给不能重复填充幻影或重复扣体力');
club25_skill('deploy',$data,'1'); $food=end($db->rows['test_mapitem']);
c25_assert($food['itms']===3 && $data['itms1']===0 && get_itmpara($food['itmpara'])['c25_ground_group']===0,'带机关补给整堆投放，不能拆出多个幻影');
$foodkey='i'.$food['iid']; $other=$data; $other['pid']=2;
c25_assert(!club25_ground_take($foodkey,$other,true),'不能撤回他人布置');
$other['pid']=1; $other['pls']=3; c25_assert(!club25_ground_take($foodkey,$other,true),'不能远程撤回');
$other['pls']=2; $other['pgroup']=9; c25_assert(!club25_ground_take($foodkey,$other,true),'同地点编号不同分组不能领取');
$target=c25_player(3); $target['club']=0; $before=count($db->rows['test_mapitem']);
c25_assert(!club25_net_take($data,$target,$foodkey) && count($db->rows['test_mapitem'])===$before,'玩家收网不能被强制喂食，现场物品保留');
$target['type']=1; $used=club25_net_take($data,$target,$foodkey); $rest=end($db->rows['test_mapitem']);
c25_assert($used['itms']===1 && $rest['itms']===2 && !isset(get_itmpara($rest['itmpara'])['c25_hide']) && isset(get_itmpara($rest['itmpara'])['c25_trip']),'NPC收网消耗一份，整堆幻影仅调用一次，诡雷仍保留');
c25_assert(!club25_net_take($data,$target,$foodkey),'重复请求不能再次消费旧地面编号');
$wild=Array('itm'=>'野生雷','itmk'=>'TO','itme'=>80,'itms'=>1,'itmsk'=>'0','itmpara'=>'','pls'=>2);
$db->array_insert('test_maptrap',$wild); $wild=end($db->rows['test_maptrap']); $key='t'.$wild['tid'];
$target['type']=0; $taken=club25_net_take($data,$target,$key);
c25_assert($taken && $taken['source']==='trap' && !isset($db->rows['test_maptrap'][$wild['tid']]),'收网允许消耗当地无主普通雷');
$wild['itmk']='TOt'; unset($wild['tid']); $db->array_insert('test_maptrap',$wild); $wild=end($db->rows['test_maptrap']);
c25_assert(!club25_net_take($data,$target,'t'.$wild['tid']),'特殊比例雷不能定点引爆');
$rows=club25_board($data); c25_assert(count($rows)>0 && count(array_filter($rows,function($row){return $row['owned'];}))>0,'布场读取真实地面记录');
$owner=c25_player(); $victim=c25_player(2); $victim['club']=0;
club25_effect_reward($victim,1,'trip',50,false,'诡雷',$owner);
c25_assert($owner['wd']===102 && $owner['exp']===1 && empty($owner['clbpara']['achvars']),'诡雷可成长但不能充当地雷成就');
club25_effect_reward($victim,1,'trap',50,false,'普通雷',$owner);
club25_effect_reward($victim,1,'food',50,false,'幻影',$owner);
c25_assert($owner['wd']===106 && !empty($owner['clbpara']['achvars']['c25_food_hit']) && !empty($owner['clbpara']['achvars']['c25_trap_hit']),'有效机关按正常命中经验加爆熟，双路线成就登记');
$before=$owner; club25_effect_reward($victim,1,'trap',0,false,'免伤雷',$owner);
c25_assert($owner===$before,'免伤不成长不登记');
$owner['teamID']=$victim['teamID']='同队'; $before=$owner; club25_effect_reward($victim,1,'food',50,false,'友军',$owner);
c25_assert($owner===$before,'队友受害不刷成长');
$owner['teamID']=$victim['teamID']=''; $victim['c25_net_active']=1; $before=$owner['wd'];
club25_effect_reward($victim,1,'trap',50,false,'收网',$owner);
c25_assert($owner['wd']===$before,'收网不重复奖励原来源玩家');
unset($victim['c25_net_active']); $players[1]=c25_player();
club25_effect_reward($victim,1,'food',10,false,'异步补给');
c25_assert($players[1]['wd']===102 && $players[1]['clbpara']['achvars']['c25_food_hit']===1,'非当前来源玩家成长和成就实际保存');
$players[1]['club']=0; $before=$players[1]['wd']; club25_effect_reward($victim,1,'trap',10,false,'传统雷');
c25_assert($players[1]['wd']===$before && $players[1]['clbpara']['achvars']['c25_trap_hit']===1,'普通社团的传统地雷也可计成就但不获额外熟练');
echo "All club25 core tests passed.\n";
