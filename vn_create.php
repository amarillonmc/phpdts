<?php
define('CURSCRIPT','vnworld');
require './include/common.inc.php';
require_once GAME_ROOT.'./include/vnworld/vncreation.func.php';

if(!$cuser || !$cpass || !$udata || $udata['password'] !== $cpass) gexit($_ERROR['no_login'],__FILE__,__LINE__);
if($udata['groupid'] <= 0) gexit($_ERROR['user_ban'],__FILE__,__LINE__);
vncreation_start_session();

$vcfg = vncreation_config();
vncreation_ensure_table();
$vkind = isset($_GET['kind']) && $_GET['kind'] === 'item' ? 'item' : 'npc';
$vtitle = $vkind === 'npc' ? '幻影造物' : '万象投影';
$vstatus = array('等待审核','已采纳','未采纳');
$vmessage = $_SESSION['vncreation_message'] ?? '';
unset($_SESSION['vncreation_message']);
$vid = 0;
$vrow = null;
$vinput = array();
$vquote = null;
$vresult = null;
try {
	$vid = vncreation_int($_GET['id'] ?? 0,0,2147483647);
	$vrow = $vid ? vncreation_get($vid) : null;
	if($vid && (!$vrow || $vrow['kind'] !== $vkind)) throw new InvalidArgumentException('投稿不存在。');
	$vinput = $vrow ? json_decode($vrow['content'],true) : array();
	if($_SERVER['REQUEST_METHOD'] === 'POST') {
		$vaction = $_POST['action'] ?? '';
		$vtoken = $_POST['token'] ?? '';
		$vstamp = vncreation_check_token($vtoken,$vkind.':'.$vid.':'.($vaction === 'reroll' ? 'reroll' : 'save'));
		if($vrow && (int)$vrow['uid'] !== (int)$udata['uid']) throw new InvalidArgumentException('只能编辑自己的投稿。');
		if($vaction === 'reroll') {
			$vresult = vncreation_save($udata,$vkind,$vid,$vaction,array('slot'=>$_POST['slot'] ?? ''),$vstamp,$vcfg);
		} else {
			$vinput = $_POST['data'] ?? array();
			if($vaction === 'quote') {
				$vdata = vncreation_normalize($vkind,$vinput,$vcfg);
				$vquote = vncreation_edit_cost($vkind,$vdata,$vrow ? json_decode($vrow['content'],true) : null,$vcfg);
			} else {
				$vresult = vncreation_save($udata,$vkind,$vid,$vaction,$vinput,$vstamp,$vcfg);
			}
		}
		if(isset($vresult)) {
			unset($_SESSION['vncreation_forms'][$vtoken]);
			$_SESSION['vncreation_message'] = '已保存并提交审核，本次消耗 '.$vresult['cost'].' 切糕，剩余 '.$vresult['balance'].' 切糕。';
			session_write_close();
			header('Location: vn_create.php?kind='.$vkind.'&id='.$vresult['id'],true,303);
			exit;
		}
	}
} catch(Exception $e) {
	$vmessage = $e->getMessage();
}
$vcanedit = $vcfg['enabled'] && (!$vid || ($vrow && (int)$vrow['uid'] === (int)$udata['uid']));
$vform = vncreation_form_data($vkind,$vinput,$vcfg);
$vlines = $vrow ? vncreation_describe($vkind,json_decode($vrow['content'],true),$vcfg) : array();
$vformtoken = $vcanedit ? vncreation_form_token($vkind.':'.$vid.':save',$vrow) : '';
$vrerolls = array();
if($vcanedit && $vrow && $vkind === 'npc') {
	$vstored = json_decode($vrow['content'],true);
	foreach($vstored['equip'] as $vslot=>$vpart) {
		if($vpart['tier'] > 1) $vrerolls[$vslot] = array('label'=>$vcfg['equip_slots'][$vslot], 'cost'=>$vpart['tier']*$vcfg['reroll_cost'], 'token'=>vncreation_form_token($vkind.':'.$vid.':reroll',$vrow));
	}
}
$vpage = isset($_GET['page']) && is_scalar($_GET['page']) ? max(0,min(100000,(int)$_GET['page'])) : 0;
$vprevpage = $vpage-1;
$vnextpage = $vpage+1;
$voffset = $vpage*20;
$vlist = array();
$vquery = $db->query("SELECT * FROM {$gtablepre}vncreation WHERE kind='$vkind' ORDER BY id DESC LIMIT $voffset,21");
while($vrecord = $db->fetch_array($vquery)) {
	$vrecord['data'] = json_decode($vrecord['content'],true);
	$vlist[] = $vrecord;
}
$vnext = count($vlist) > 20;
if($vnext) array_pop($vlist);
session_write_close();
include template('vn_creation');
