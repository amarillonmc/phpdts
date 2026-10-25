<?php
define('CURSCRIPT','fcg');
require './include/common.inc.php';
require_once GAME_ROOT.'./include/fcg/account.php';
require_once GAME_ROOT.'./include/fcg/presentation.php';

if(!$cuser || !$cpass || !$udata || !hash_equals((string)$udata['password'],(string)$cpass)) {
	if($_SERVER['REQUEST_METHOD']==='POST') { ob_clean(); http_response_code(401); header('Content-Type: application/json; charset=utf-8'); echo fcg_json(array('error'=>'请先登录。')); exit; }
	gexit($_ERROR['no_login'],__FILE__,__LINE__);
}
$fcg_csrf=hash_hmac('sha256','fcg:'.$udata['uid'],(string)$cpass);

if($_SERVER['REQUEST_METHOD']==='POST') {
	header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store'); ob_clean();
	try {
		$raw=file_get_contents('php://input',false,null,0,32769);
		if(strlen($raw)>32768) throw new InvalidArgumentException('请求内容过长。');
		$input=json_decode($raw,true);
		if(!is_array($input) || !isset($input['csrf']) || !is_string($input['csrf']) || !hash_equals($fcg_csrf,$input['csrf'])) throw new InvalidArgumentException('页面校验已失效，请刷新。');
		fcg_ensure_storage();
		echo fcg_json(array('state'=>fcg_request($input,$now)));
	} catch(InvalidArgumentException $e) { http_response_code(409); echo fcg_json(array('error'=>$e->getMessage())); }
	catch(Throwable $e) { error_log('FCG: '.$e->getMessage()); http_response_code(503); echo fcg_json(array('error'=>'卡册暂时无法保存，请稍后重试。')); }
	exit;
}

fcg_ensure_storage();
$fcg_state=fcg_request(array('action'=>'status'),$now);
$fcg_shop_view=defined('FCG_SHOP_VIEW');
$fcg_boot=fcg_bootstrap($fcg_state,$fcg_csrf,$fcg_shop_view);
include template('fcg');
ob_end_flush();
