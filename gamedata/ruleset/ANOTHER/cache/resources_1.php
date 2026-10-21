<?php
if(!defined('IN_GAME')) exit('Access Denied');

// 复用通用物品字段的显示词典；场景、角色与剧情资源在本模式全部替换。
require GAME_ROOT.'./gamedata/cache/resources_1.php';
require_once dirname(__DIR__).'/include/another.config.php';
$another_resource_config=another_config();
$plsinfo=array(0=>'载入边界');
$areainfo=array(0=>'未完成载入的边界，无法进入。');
$xyinfo=array(0=>'--','hidden'=>'--');
foreach($another_resource_config['maps'] as $another_map_id=>$another_map) {
    $plsinfo[$another_map_id]=$another_map['name'];
    $areainfo[$another_map_id]=$another_map['description'];
    $xyinfo[$another_map_id]=str_pad((string)$another_map_id,2,'0',STR_PAD_LEFT);
}
$gstate=array(0=>'已结束',10=>'即将开始',20=>'异常对局进行中',30=>'异常对局进行中',40=>'区域收束',50=>'区域收束',60=>'区域收束');
$gwin=array(0=>'程序故障',1=>'无人优胜',2=>'幸存脱出',3=>'异常终止',4=>'无人参加',5=>'无人优胜',6=>'管理中止',7=>'异常终止');
$clubinfo=array(0=>'天赋构筑');
$typeinfo=array(0=>'被困参战者');
$npcchat=$killmsginfo=$lwinfo=array();
foreach($another_resource_config['npc_templates'] as $another_role=>$another_npc) {
    $typeinfo[$another_npc['type']]=$another_npc['name'];
    $killmsginfo[$another_npc['type']]='目标信号消失。';
    $lwinfo[$another_npc['type']]='重复任务……中断。';
}
$hidding_typelist=$sentinel_typelist=$deepzones=$depots=$hplsinfo=array();
$shops=array(1);
$hospitals=array(2,6,7);
$rswtharr=array(0);
$stateinfo[5]='幸存脱出';
$stateinfo[6]='异常终止';
$stateinfo[42]='异常终止';
$apose=$atac=array();
unset($another_resource_config,$another_map_id,$another_map,$another_role,$another_npc);
