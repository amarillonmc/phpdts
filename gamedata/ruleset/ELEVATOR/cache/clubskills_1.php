<?php
if (!defined('IN_GAME')) exit('Access Denied');
require GAME_ROOT.'gamedata/cache/clubskills_1.php';
require_once __DIR__.'/../include/elevator.skills.config.php';
foreach (elevator_skill_definitions() as $elid=>$eldef) {
    $cskills['el_'.$elid]=array('name'=>$eldef[0],'tags'=>array('passive'),'desc'=>$eldef[1]);
}
unset($elid,$eldef);
