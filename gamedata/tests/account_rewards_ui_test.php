<?php
// 为 JS 测试渲染实际模板；仅跳过与本次表单无关的页头、外观等子模板。
if(PHP_SAPI !== 'cli') exit('CLI only');
define('IN_GAME', true);
define('GAME_ROOT', dirname(__DIR__, 2).'/');
require GAME_ROOT.'include/template.func.php';
require GAME_ROOT.'include/messages.func.php';
set_error_handler(function($severity,$message,$file,$line){ throw new ErrorException($message,0,$severity,$file,$line); });
$ui_template_id = random_int(900000,999999);
$ui_theme = 'default'; $ui_compiled = array();
$ui_empty = tempnam(sys_get_temp_dir(), 'reward-ui-');
function language($file, $id=0, $dir='') { return GAME_ROOT.'templates/default/templates.lang.php'; }
function template($file, $id=0, $dir='') {
	global $ui_template_id,$ui_theme,$ui_empty,$ui_compiled;
	if(!in_array($file,array('user','message_page','messages','admin_activation','admin_reward_fields','admin_urlist','validover'),true)) return $ui_empty;
	$dir = file_exists(GAME_ROOT.'templates/'.$ui_theme.'/'.$file.'.htm') ? 'templates/'.$ui_theme : 'templates/default';
	$path = GAME_ROOT.'gamedata/templates/'.$ui_template_id.'_'.$file.'.tpl.php';
	parse_template($file,$ui_template_id,$dir); $ui_compiled[$path] = true;
	return $path;
}
function ui_render($file, $vars) { extract($vars); ob_start(); try { include template($file); return ob_get_contents(); } finally { ob_end_clean(); } }
try {
	$payload = array();
	$reward = account_reward_validate(array('credits'=>30,'credits2'=>5,'titles'=>array(1),'achievements'=>array(0=>1),'boon'=>array('name'=>'<测试赐福>','stats'=>array('att'=>10))));
	$raw = array(7=>array('timestamp'=>1800000000,'rd'=>0,'checked'=>0,'title'=>'测试邮件','content'=>'正文','enclosure'=>account_reward_encode($reward)));
	foreach(array('default','nouveau') as $theme) {
		$ui_theme = $theme; $messages = message_disp($raw);
		$payload[$theme]['messages'] = ui_render('message_page',array('mode'=>'show','info'=>array(),'message_csrf'=>'mail-csrf','messages'=>$messages));
		$deleted = $raw; $deleted[7]['dtimestamp'] = 1800000010;
		$payload[$theme]['deleted'] = ui_render('messages',array('mode'=>'showdel','info'=>array(),'message_rec_cost'=>100,'messages'=>message_disp($deleted)));
		$raw[7]['checked'] = 1;
		$payload[$theme]['claimed'] = ui_render('messages',array('mode'=>'check','info'=>array(),'messages'=>message_disp($raw)));
		$raw[7]['checked'] = 0;
		$payload[$theme]['user'] = ui_render('user',array('groomid'=>0,'activation_csrf'=>'code-csrf','activation_request'=>str_repeat('a',32),'activation_info'=>'','activation_queue'=>array('初行（攻击 +10）'),'show_sync_button'=>false,'show_reverse_migrate_button'=>false));
		$vars = array('reward_csrf'=>'admin-csrf','reward_form'=>account_reward_form_values(array('stitle'=>'标题 " <标签>')),'reward_boon_stats'=>account_boon_stats(),'reward_catalog'=>account_reward_catalog(),'activation_seed'=>str_repeat('b',64),'activation_custom'=>'','generated_codes'=>array(str_repeat('A',32),'新手福利','VIP777'),'activation_rows'=>array());
		$payload[$theme]['admin'] = ui_render('admin_activation',$vars);
		$payload[$theme]['admin_custom'] = ui_render('admin_activation',array_merge($vars,array('activation_custom'=>account_reward_html('新手福利'))));
		$payload[$theme]['admin_invalid'] = ui_render('admin_activation',array_merge($vars,array('activation_custom'=>account_reward_html('" onfocus="alert(1)'))));
		$vars += array('urcmd'=>'list','pagecmd'=>'check','urorder'=>'uid','urorder2'=>'DESC','start'=>0,'resultinfo'=>'测试','urgroup'=>array('封停','正常'),'ursex'=>array('0'=>'未定','m'=>'男','f'=>'女'),'mygroup'=>9,'show_reverse_migration'=>false,'titles_list'=>array(0=>'参展者',1=>'幻想'),'urdata'=>array(array('uid'=>1,'username'=>'测试玩家','groupid'=>1,'gender'=>'m','lastgame'=>0,'ip'=>'127.0.0.1','credits'=>0,'credits2'=>0,'icon'=>1,'motto'=>'','killmsg'=>'','lastword'=>'','nicksrev'=>array('nicks'=>array(0)))));
		$payload[$theme]['admin_users'] = ui_render('admin_urlist',$vars);
	}
	echo json_encode($payload,JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} finally {
	foreach(array_keys($ui_compiled) as $path) unlink($path);
	unlink($ui_empty);
}
