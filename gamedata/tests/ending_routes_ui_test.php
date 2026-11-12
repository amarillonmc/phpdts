<?php
if(PHP_SAPI !== 'cli') exit('CLI only');
if(!defined('IN_GAME')) define('IN_GAME',true);
if(!defined('GAME_ROOT')) define('GAME_ROOT',dirname(__DIR__,2).'/');
require GAME_ROOT.'include/template.func.php';
require GAME_ROOT.'include/game/ending_routes.func.php';
require GAME_ROOT.'include/game/severe_status.func.php';
require GAME_ROOT.'include/talent.func.php';
$ending_routes_v2=1; $club_skills=1; $current_ruleset_config=array();
$ui_id='ending_ui_'.getmypid(); $ui_compiled=array();
function language($file,$id=0,$dir='') { return GAME_ROOT.'templates/default/templates.lang.php'; }
function template($file,$id=0,$dir='') {
	global $ui_id,$ui_compiled;
	$dir=$dir ?: 'templates/default';
	$tag=$ui_id.'_'.(strpos($dir,'nouveau')!==false ? 'nouveau' : 'default');
	$path=GAME_ROOT.'gamedata/templates/'.$tag.'_'.$file.'.tpl.php';
	parse_template($file,$tag,$dir); $ui_compiled[$path]=true; return $path;
}
function ui_check($ok,$message) { if(!$ok) throw new RuntimeException($message); echo 'PASS: '.$message."\n"; }
function get_clbpara($value) { return is_array($value) ? $value : (json_decode((string)$value,true) ?: array()); }
function ui_render($file,$vars) { extract($vars,EXTR_SKIP); ob_start(); try { include template($file); return ob_get_contents(); } finally { ob_end_clean(); } }
try {
	foreach(array('command','profile','quest_sendo','ending_routes_status','ending_routes_story','ending_story','sp_shop','user_talent') as $file) token_get_all(file_get_contents(template($file)),TOKEN_PARSE);
	foreach(array('command','profile') as $file) token_get_all(file_get_contents(template($file,0,'templates/nouveau')),TOKEN_PARSE);
	ui_check(true,'默认与 nouveau 的十个相关模板通过真实编译器');
	$plsinfo=array(); foreach(range(0,34) as $i) $plsinfo[$i]='地点'.$i;
	$hplsinfo=$areainfo=$horizoninfo=$typeinfo=$dinfo=array(); ending_routes_setup();
	include GAME_ROOT.'gamedata/questcfg_1.php';
	$sendo_view=array_intersect_key($questcfg,array_flip(array('QD1','QD2','QD3','QD4','QD5','QD6','QD7')));
	$sendo_state=array('done'=>array('QD6'=>1),'gentle'=>array(),'batteries'=>1,'lantern_route'=>array(1,2,3,34),'lantern_step'=>1);
	$sendo_world=array('points'=>array(1,2,3,4,5,6,7),'bye'=>7);
	$pdata=array('clbpara'=>array(),'itm1'=>'备用护甲','itmk1'=>'DB','itms1'=>1);
	$hub=ui_render('quest_sendo',compact('sendo_view','sendo_state','sendo_world','pdata','plsinfo'));
	ui_check(substr_count($hub,'下轮重做')===7 && strpos($hub,'sendo_donate_1')!==false,'七项送行与捐装均使用实际 command 提交');
	$sendo_state['donate_confirm']=array('item'=>array('备用护甲')); $confirm=ui_render('quest_sendo',compact('sendo_view','sendo_state','sendo_world','pdata','plsinfo'));
	ui_check(strpos($confirm,'sendo_donate_confirm')!==false,'捐装预览显示独立确认操作');
	$talent_available=true; $talent_notice=''; $talent_data=array('earned_points'=>28,'bought_points'=>2,'alloc'=>array()); $talent_used=0; $talent_limit=80; $talent_free=30;
	$talent_user=array('credits2'=>1500); $talent_buy_price=100; $talent_reset_price=100; $talent_base='preview'; $talent_csrf='preview';
	$talent_trees=talent_catalog(); $raceinfo=array(0=>'人类',1=>'兽人',2=>'妖精',3=>'龙',4=>'鱼人',5=>'ＡＩ');
	$talents=ui_render('user_talent',get_defined_vars());
	ui_check(substr_count($talents,'value="learn"')===96 && substr_count($talents,'disabled')===80,'天赋树仅开放各树首节点，后续节点需先点亮前置');
	$now=1000; $pgroup=3; $horizon=2; $clbpara=array('dissociation_deadline'=>5500,'ex_lock'=>1,'curses'=>array('darkness'=>1)); $pdata['clbpara']=$clbpara;
	$status=ui_render('ending_routes_status',compact('now','pgroup','horizon','clbpara','pdata'));
	ui_check(strpos($status,'data-remaining="4500"')!==false && strpos($status,'视界已锁定')!==false,'湾城倒计时和 EX 锁定显示');
	$stories=''; foreach(array('jiejin_Zv'=>5,'jieli_phantom'=>5,'jieli_true'=>6) as $route=>$last) {
		$story=ui_render('ending_routes_story',array('gamevars'=>array('ending_subroute'=>$route),'state'=>6));
		ui_check(strpos($story,'class="shootings">'.$last.'</div>')!==false && substr_count($story,'alt="结局插画"')===2,$route.' 页码与两幅 CG');
		$stories.='<h2>'.$route.'</h2>'.$story;
	}
	if(in_array('--preview',$argv,true)) {
		$head='<!doctype html><html lang="zh-CN"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><base href="/"><title>四路线模板预览</title><link rel="stylesheet" href="gamedata/cache/style_20240120.css"><link rel="stylesheet" href="gamedata/cache/style_buttons.20180118.css"><style>body{margin:24px;text-align:left}main{max-width:1200px;margin:auto}button,input{cursor:pointer}h1{font-size:20px}.shootings{display:block!important;margin:20px 0;max-width:720px}#preview-command{padding:12px;background:#293844;color:white;position:sticky;top:0}nav a{margin-right:24px}</style><main><h1>四路线模板预览</h1><nav><a href="tmp/ending_routes_preview.html">送行与状态</a><a href="tmp/ending_routes_talents.html">天赋树</a><a href="tmp/ending_routes_story.html">结局演出</a></nav><hr>';
		$script='<script>function $(id){return document.getElementById(id)}function postCmd(){document.getElementById("preview-command").textContent="提交指令："+document.getElementById("command").value;}document.addEventListener("submit",function(e){e.preventDefault();});</script>';
		file_put_contents(GAME_ROOT.'tmp/ending_routes_preview.html',$head.'<div id="preview-command">等待指令</div>'.$status.'<form id="gamecmd">'.$hub.'</form>'.$script.'</main></html>');
		file_put_contents(GAME_ROOT.'tmp/ending_routes_talents.html',$head.$talents.$script.'</main></html>');
		file_put_contents(GAME_ROOT.'tmp/ending_routes_story.html',$head.$stories.'</main></html>');
	}
} finally { foreach(array_keys($ui_compiled) as $path) if(is_file($path)) unlink($path); }
echo "Ending route UI regression complete.\n";
