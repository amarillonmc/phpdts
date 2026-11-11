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
$verror = false;
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
	$verror = true;
	if(!$vrow) $vid = 0;
}
$vmode = isset($_GET['mode']) && is_scalar($_GET['mode']) ? (string)$_GET['mode'] : '';
$vpostmode = ($vmode === 'post' || $vmode === 'edit' || $vid > 0 || $_SERVER['REQUEST_METHOD'] === 'POST');
$vnotice = $vkind === 'npc' ? $vcfg['npc_notice'] : $vcfg['item_notice'];
$vtop_tips = $vkind === 'npc' ? $vcfg['npc_tips'] : $vcfg['item_tips'];
$vcanedit = $vcfg['enabled'] && (!$vid || ($vrow && (int)$vrow['uid'] === (int)$udata['uid']));
$vform = vncreation_form_data($vkind,$vinput,$vcfg);
$vold_data = $vrow ? json_decode($vrow['content'],true) : null;
$vlines = $vrow ? vncreation_describe($vkind,$vold_data,$vcfg) : array();
$vformtoken = ($vpostmode && $vcanedit) ? vncreation_form_token($vkind.':'.$vid.':save',$vrow) : '';
$vrerolls = array();
if($vpostmode && $vcanedit && $vrow && $vkind === 'npc') {
	foreach($vold_data['equip'] as $vslot=>$vpart) {
		if($vpart['tier'] > 1) $vrerolls[$vslot] = array('label'=>$vcfg['equip_slots'][$vslot], 'cost'=>$vpart['tier']*$vcfg['reroll_cost'], 'token'=>vncreation_form_token($vkind.':'.$vid.':reroll',$vrow));
	}
}
if($vrow) $vrow = vncreation_format_record($vrow,$vcfg);
$vinitial_quote = $vquote !== null ? $vquote : ($vrow ? 0 : ($vkind === 'npc' ? $vcfg['npc_levels'][$vform['level']]['cost'] : $vcfg['item_base']));
$vjs_cfg = json_encode(array(
	'kind' => $vkind,
	'max_sk' => $vcfg['max_sk'],
	'item_base' => $vcfg['item_base'],
	'bag_base' => $vcfg['bag_base'],
	'effect_cost' => $vcfg['effect_cost'],
	'durability_cost' => $vcfg['durability_cost'],
	'attribute_cost' => $vcfg['attribute_cost'],
	'infinite_cost' => $vcfg['infinite_cost'],
	'reroll_cost' => $vcfg['reroll_cost'],
	'npc_levels' => $vcfg['npc_levels'],
	'equip_levels' => $vcfg['equip_levels'],
	'equip_slots' => array_keys($vcfg['equip_slots']),
	'old' => $vold_data,
), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
$temp_item_namelist = array();
if($vpostmode && $vcanedit && $vcfg['name_assoc']) {
	$in_file = config('itmlist',$gamecfg);
	if(file_exists($in_file)) {
		include $in_file;
		if(isset($item_namelist) && is_array($item_namelist)) $temp_item_namelist = $item_namelist;
	}
}
$vpage = isset($_GET['page']) && is_scalar($_GET['page']) ? max(0,min(100000,(int)$_GET['page'])) : 0;
$vprevpage = $vpage-1;
$vnextpage = $vpage+1;
$voffset = $vpage*20;
$vlist = array();
if(!$vpostmode) {
	$vquery = $db->query("SELECT * FROM {$gtablepre}vncreation WHERE kind='$vkind' ORDER BY id DESC LIMIT $voffset,21");
	while($vrecord = $db->fetch_array($vquery)) {
		$vlist[] = vncreation_format_record($vrecord,$vcfg);
	}
}
$vnext = count($vlist) > 20;
if($vnext) array_pop($vlist);
session_write_close();
include template('vn_creation');
