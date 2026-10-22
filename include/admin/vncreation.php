<?php
if(!defined('IN_ADMIN')) exit('Access Denied');
require_once GAME_ROOT.'./include/vnworld/vncreation.func.php';
vncreation_start_session();
vncreation_ensure_table();
$vcfg = vncreation_config();
$vstatus = array('等待审核','已采纳','未采纳');
$vkind = isset($_GET['kind']) && $_GET['kind'] === 'item' ? 'item' : 'npc';
$vfilter = isset($_GET['status']) && in_array($_GET['status'],array('0','1','2'),true) ? (int)$_GET['status'] : 0;
$vpage = isset($_GET['page']) && is_scalar($_GET['page']) ? max(0,min(100000,(int)$_GET['page'])) : 0;
$vprevpage = $vpage-1;
$vnextpage = $vpage+1;
$vmessage = $_SESSION['vncreation_admin_message'] ?? '';
unset($_SESSION['vncreation_admin_message']);
try {
	if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vaction'])) {
		$vaction = $_POST['vaction'];
		$vtoken = $_POST['token'] ?? '';
		if($vaction === 'count') {
			vncreation_check_token($vtoken,'admin:count');
			vncreation_set_count($_POST['npc_count'] ?? '',$vcfg);
			adminlog('幻想世界NPC每局投放数',(int)$_POST['npc_count']);
		} elseif($vaction === 'review') {
			$vid = vncreation_int($_POST['id'] ?? '',1,2147483647);
			$vstamp = vncreation_check_token($vtoken,'admin:'.$vid);
			vncreation_review($udata,$vid,$_POST['status'] ?? '',$_POST['note'] ?? '',$vstamp);
			adminlog('幻想世界审核',$vid,(int)$_POST['status']);
		} else {
			throw new InvalidArgumentException('操作无效。');
		}
		unset($_SESSION['vncreation_forms'][$vtoken]);
		$_SESSION['vncreation_admin_message'] = '已保存，从下一局大房间开始生效。';
		session_write_close();
		header('Location: admin.php?mode=vncreation&kind='.$vkind.'&status='.$vfilter.'&page='.$vpage,true,303);
		exit;
	}
} catch(Exception $e) {
	$vmessage = $e->getMessage();
}
$vcounttoken = vncreation_form_token('admin:count');
$voffset = $vpage*20;
$vlist = array();
$vquery = $db->query("SELECT * FROM {$gtablepre}vncreation WHERE kind='$vkind' AND status=$vfilter ORDER BY id DESC LIMIT $voffset,21");
while($vrow = $db->fetch_array($vquery)) $vlist[] = $vrow;
$vnext = count($vlist) > 20;
if($vnext) array_pop($vlist);
foreach($vlist as &$vrow) {
	$vrow['lines'] = vncreation_describe($vrow['kind'],json_decode($vrow['content'],true),$vcfg);
	$vrow['token'] = vncreation_form_token('admin:'.$vrow['id'],$vrow);
}
unset($vrow);
session_write_close();
include template('admin_vncreation');
