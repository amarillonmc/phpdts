<?php
if (!defined('IN_GAME')) exit('Access Denied');
// 报告、证据与找回都使用现有 quest 容器；本模式面板负责接取/提交。
$questcfg_global = array('max_active'=>20, 'assign_obbs'=>0);
$questcfg = array();
foreach (array('first'=>'第一页','chain'=>'互杀断链','drift'=>'世界线漂移','wrong'=>'杀错人','kind'=>'善意陷阱') as $key=>$title) {
    $questcfg['FL_report_'.$key] = array('title'=>'死亡报告册 · '.$title,'floor_board'=>true);
}
for ($i=1;$i<=5;$i++) $questcfg['FL_evidence_'.$i] = array('title'=>'门的证据 · '.$i,'floor_board'=>true);
$questcfg['FL_gate'] = array('title'=>'无月之影尽头的门','floor_board'=>true);
foreach (array('patrol','holo','collect','explore','escort','feed','track','disarm') as $key) $questcfg['FL_job_'.$key] = array('floor_board'=>true,'repeatable'=>true);
