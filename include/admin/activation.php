<?php
if(!defined('IN_ADMIN')) exit('Access Denied');
if($mygroup < 6) exit($_ERROR['no_power']);
require_once GAME_ROOT.'./include/activation.func.php';
$reward_csrf = hash_hmac('sha256', 'admin-rewards:'.$udata['uid'], (string)$cpass);
$reward_form = account_reward_form_values($_POST);
$reward_boon_stats = account_boon_stats();
$reward_catalog = account_reward_catalog();
$generated_codes = $activation_rows = array();
$activation_seed = bin2hex(random_bytes(32));
try {
	activation_ensure_storage();
	$action = $_POST['activation_action'] ?? '';
	if($action !== '') {
		account_reward_post_check('admin-rewards', $_POST);
		if($action === 'generate') {
			$generated_codes = activation_generate($_POST);
			$cmd_info = '已生成 '.count($generated_codes).' 个激活码。相同种子、账户及序号会得到相同代码，重复提交不会额外生成。';
			adminlog('activation_generate', count($generated_codes), account_reward_html($_POST['bound_username'] ?? ''));
		} elseif($action === 'disable') {
			$id = account_reward_int($_POST['code_id'] ?? '', 1, 4294967295);
			$db->query("UPDATE {$gtablepre}activation_codes SET enabled=0 WHERE id=$id");
			$cmd_info = '激活码已停用，已经发送的邮件不受影响。';
			adminlog('activation_disable', $id);
		} else throw new InvalidArgumentException('未知的激活码操作。');
	}
} catch(Throwable $e) {
	$cmd_info = account_reward_html($e->getMessage());
	if(!($e instanceof InvalidArgumentException)) error_log('Activation admin: '.$e->getMessage());
}
$result = $db->query("SELECT c.*,u.username AS bound_username,(SELECT COUNT(*) FROM {$gtablepre}activation_uses r WHERE r.code_id=c.id) AS used_count FROM {$gtablepre}activation_codes c LEFT JOIN {$gtablepre}users u ON u.uid=c.bound_uid ORDER BY c.id DESC LIMIT 100", 'SILENT');
if($result) while($row = $db->fetch_array($result)) {
	$row['bound_name'] = $row['bound_uid'] ? account_reward_html($row['bound_username'] ?? ('已删除账户 #'.$row['bound_uid'])) : '不限账户';
	try { $row['reward_text'] = account_reward_html(implode('；', account_reward_describe(account_reward_decode($row['enclosure'])))); }
	catch(Throwable $e) { $row['reward_text'] = '附件配置无效'; }
	$activation_rows[] = $row;
}
include template('admin_activation');
