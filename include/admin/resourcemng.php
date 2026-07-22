<?php
if(!defined('IN_ADMIN')) {
	exit('Access Denied');
}

if(!isset($res_type)) $res_type = 'mapitem';
if(!isset($start)) $start = 0;
if(!isset($pagemode)) $pagemode = '';
if(!isset($keyword)) $keyword = '';
if(!isset($ruleset)) $ruleset = '__default__';
if(!isset($action)) $action = 'list';

if(isset($edit_id)) {
	$action = 'edit';
	$record_id = intval($edit_id);
}
if(isset($delete_id)) {
	$action = 'delete';
	$record_id = intval($delete_id);
}

$start = getstart($start,$pagemode);
$keyword = trim($keyword);
$res_type = in_array($res_type, array('mapitem','shopitem','stitem','stwep','npc')) ? $res_type : 'mapitem';
$ruleset = preg_match('/^[A-Za-z0-9_]+$/', $ruleset) ? $ruleset : '__default__';

$ruleset_list = resourcemng_get_rulesets($res_type);
if(!isset($ruleset_list[$ruleset])) $ruleset = '__default__';

$target_file = $ruleset_list[$ruleset]['file'];
$allow_upload = ($mygroup > 10);

if($action === 'download') {
	if(file_exists($target_file)) {
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.basename($target_file).'"');
		header('Content-Length: '.filesize($target_file));
		readfile($target_file);
		adminlog('resourcemng_download',$res_type,$ruleset,basename($target_file));
		exit;
	}
	$cmd_info = '下载失败：目标文件不存在。';
}

if($action === 'upload' && $allow_upload) {
	if(!empty($_FILES['cfgfile']['tmp_name']) && is_uploaded_file($_FILES['cfgfile']['tmp_name'])) {
		$uploaded = file_get_contents($_FILES['cfgfile']['tmp_name']);
		if($uploaded !== false && strlen($uploaded) > 0) {
			if(@file_put_contents($target_file, $uploaded) !== false) {
				$cmd_info = '上传覆盖成功。';
				adminlog('resourcemng_upload',$res_type,$ruleset,$_FILES['cfgfile']['name']);
			} else {
				$cmd_info = '上传失败：写入文件失败。';
			}
		} else {
			$cmd_info = '上传失败：文件内容为空或不可读。';
		}
	} else {
		$cmd_info = '上传失败：未检测到上传文件。';
	}
}

$resource = resourcemng_read_data($res_type, $target_file);
$records = $resource['records'];
$header = $resource['header'];
$columns = $resource['columns'];

if($action === 'save_record') {
	$id = isset($record_id) ? intval($record_id) : -1;
	if(isset($records[$id])) {
		$new_row = resourcemng_collect_row($res_type, $_POST, $records[$id]);
		$valid = resourcemng_validate_row($res_type, $new_row, $err);
		if($valid) {
			$records[$id] = $new_row;
			if(resourcemng_write_data($res_type, $target_file, $header, $records)) {
				$cmd_info = '记录已保存。';
				adminlog('resourcemng_edit',$res_type,$ruleset,$id);
			} else {
				$cmd_info = '保存失败：写入文件失败。';
			}
		} else {
			$cmd_info = '保存失败：'.$err;
		}
	} else {
		$cmd_info = '保存失败：记录不存在。';
	}
	$resource = resourcemng_read_data($res_type, $target_file);
	$records = $resource['records'];
}

if($action === 'delete') {
	$id = isset($record_id) ? intval($record_id) : -1;
	if(isset($records[$id])) {
		unset($records[$id]);
		$records = array_values($records);
		if(resourcemng_write_data($res_type, $target_file, $header, $records)) {
			$cmd_info = '记录已删除。';
			adminlog('resourcemng_delete',$res_type,$ruleset,$id);
		} else {
			$cmd_info = '删除失败：写入文件失败。';
		}
	}
	$resource = resourcemng_read_data($res_type, $target_file);
	$records = $resource['records'];
}

if($action === 'add') {
	$new_row = resourcemng_collect_row($res_type, $_POST, array());
	$valid = resourcemng_validate_row($res_type, $new_row, $err);
	if($valid) {
		if($res_type === 'npc' && isset($record_id) && intval($record_id) >= 0) $records[intval($record_id)] = $new_row;
		else $records[] = $new_row;
		if(resourcemng_write_data($res_type, $target_file, $header, $records)) {
			$cmd_info = '新记录已添加。';
			adminlog('resourcemng_add',$res_type,$ruleset,'new');
		} else {
			$cmd_info = '新增失败：写入文件失败。';
		}
	} else {
		$cmd_info = '新增失败：'.$err;
	}
	$resource = resourcemng_read_data($res_type, $target_file);
	$records = $resource['records'];
}

$edit_record = array();
if($action === 'edit' && isset($record_id)) {
	$id = intval($record_id);
	if(isset($records[$id])) $edit_record = $records[$id];
}

$edit_record_json = '';
if($res_type === 'npc' && !empty($edit_record)) {
	$edit_record_json = json_encode($edit_record, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
}

$filtered = array();
foreach($records as $i => $row) {
	$ok = true;
	if($keyword !== '') {
		$src = is_array($row) ? json_encode($row,JSON_UNESCAPED_UNICODE) : strval($row);
		if(stripos($src, $keyword) === false) $ok = false;
	}
	if($ok) {
		$row['_idx'] = $i;
		if($res_type === 'npc') {
			$row['_sub_count'] = (isset($row['sub']) && is_array($row['sub'])) ? count($row['sub']) : 0;
		}
		$filtered[] = $row;
	}
}
$total_count = count($filtered);
$paged_records = array_slice($filtered, $start, $showlimit);
$resultinfo = '第'.($total_count?($start+1):0).'条-第'.($start+count($paged_records)).'条 / 共'.$total_count.'条';

// 转义模板输出，表单提交时浏览器会还原实体 / Escape template output; browsers decode entities on submit
// keyword 已经由 common.inc.php 的 gstrfilter() 实体化；禁止重复编码导致搜索框内容漂移。
// keyword was entity-encoded by common.inc.php::gstrfilter(); prevent a second encoding pass here.
$keyword_html = htmlspecialchars($keyword, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
$paged_records_html = array();
foreach($paged_records as $row) {
	$html_row = $row;
	if($res_type !== 'npc') {
		foreach($columns as $ci => $column) {
			$html_row[$ci] = htmlspecialchars(isset($row[$ci]) ? strval($row[$ci]) : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
		}
	}
	$paged_records_html[] = $html_row;
}
$edit_record_html = array();
foreach($columns as $i => $column) {
	$edit_record_html[$i] = htmlspecialchars(isset($edit_record[$i]) ? strval($edit_record[$i]) : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
$edit_record_json_html = htmlspecialchars($edit_record_json, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

include template('admin_resourcemng');

function resourcemng_get_rulesets($res_type){
	$map = array(
		'mapitem' => 'mapitem_1.php',
		'shopitem' => 'shopitem_1.php',
		'stitem' => 'stitem_1.php',
		'stwep' => 'stwep_1.php',
		'npc' => 'npc_1.php',
	);
	$list = array();
	$list['__default__'] = array('name' => '默认配置', 'file' => GAME_ROOT.'./gamedata/cache/'.$map[$res_type]);
	$ruleset_root = GAME_ROOT.'./gamedata/ruleset';
	if(is_dir($ruleset_root)) {
		foreach(scandir($ruleset_root) as $id) {
			if($id === '.' || $id === '..') continue;
			if(!preg_match('/^[A-Za-z0-9_]+$/',$id)) continue;
			$file = $ruleset_root.'/'.$id.'/cache/'.$map[$res_type];
			if(file_exists($file)) {
				$list[$id] = array('name' => 'RuleSet: '.$id, 'file' => $file);
			}
		}
	}
	return $list;
}

function resourcemng_read_data($res_type, $file){
	if($res_type === 'npc') {
		if(!defined('IN_GAME')) define('IN_GAME', TRUE);
		$npcinit = array(); $npcinfo = array();
		include $file;
		return array('header' => '', 'records' => $npcinfo, 'columns' => array('key','value_json'), 'type' => 'npc');
	}
	$raw = file_get_contents($file);
	if($raw === false) $raw = '';
	$raw = str_replace("\r\n", "\n", $raw);
	$lines = explode("\n", $raw);
	$header = '';
	$data_lines = array();
	$in_guard = false;
	foreach($lines as $line) {
		$trim = trim($line);
		if($trim === '') continue;
		if(strpos($trim, '<?') === 0) {
			$header .= $line."\n";
			$in_guard = (strpos($trim, '?>') === false);
			continue;
		}
		if($in_guard) {
			$header .= $line."\n";
			if(strpos($trim, '?>') !== false) $in_guard = false;
			continue;
		}
		if(strpos($trim,'//') === 0) { $header .= $line."\n"; continue; }
		$data_lines[] = $line;
	}
	$records = array();
	$expected = count(resourcemng_columns($res_type));
	foreach($data_lines as $line) {
		$line = trim($line);
		// 配置行统一以逗号结尾，先去掉末尾分隔符，保留中间 JSON/文本中的逗号
		$line = preg_replace('/,\s*$/', '', $line);
		$row = explode(',', $line, $expected);
		$row = array_map('trim', $row);
		if(count($row) < $expected) $row = array_pad($row, $expected, '');
		$records[] = $row;
	}
	$columns = resourcemng_columns($res_type);
	return array('header' => $header, 'records' => $records, 'columns' => $columns, 'type' => 'csv');
}

function resourcemng_write_data($res_type, $file, $header, $records){
	if($res_type === 'npc') {
		$data = "<?php\n\n\tif(!defined('IN_GAME')) exit('Access Denied');\n\n";
		$data .= "\t\$npcinit = array\n\t(\n\t\t'name' => '',\t'pass' => 'bra', 'gd' => 'm',\t'icon' => 0,\t'club' => 0,\t\n\t\t'mhp' => 0,\t'msp' => 0,\t'att' => 0,\t'def' => 0,\t'pls' => 0,\t'lvl' => 0,\n\t\t'money' => 0,\t'inf' => '',\t'rage' => 0,\t'pose' => 0,\t'tactic' => 0,\t\n\t\t'killnum' => 0,\t'state' => 1,\t'teamID' => '',\t'teamPass' => '','bid' => 0,\n\t\t'horizon' => 0, 'clbpara' => Array(),\n\t\t'wp' => 0, 'wk' => 0, 'wc' => 0, 'wg' => 0, 'wd' => 0, 'wf' => 0, 'skills' => 0, 'rp' => 0,\n\t);\n";
		$data .= "\t\$npcinfo = ".var_export($records, true).";\n?>";
		return @file_put_contents($file, $data) !== false;
	}
	$out = rtrim($header)."\n";
	foreach($records as $row) {
		$out .= implode(',', $row).",\n";
	}
	return @file_put_contents($file, $out) !== false;
}

function resourcemng_columns($res_type){
	$map = array(
		'mapitem' => array('刷新禁区','地图编号','数量','名称','类别','效果值','耐久/次数','属性','itmpara'),
		'shopitem' => array('记录类型','分类/出现率','价格/参数','等级','名称','类别','效果值','耐久/次数','属性','itmpara'),
		'stitem' => array('名称','类别','效果值','耐久/次数','属性','itmpara'),
		'stwep' => array('名称','类别','效果值','耐久/次数','属性','itmpara'),
		'npc' => array('NPC类别','配置JSON'),
	);
	return $map[$res_type];
}

function resourcemng_collect_row($res_type, $input, $default){
	if($res_type === 'npc') {
		$key = isset($input['record_id']) ? intval($input['record_id']) : -1;
		$json = isset($input['npc_json']) ? trim($input['npc_json']) : '';
		$arr = $default;
		if($key >= 0) {
			$decoded = json_decode($json, true);
			if(!is_array($decoded)) $decoded = array();
			$arr = $decoded;
		}
		return $arr;
	}
	$row = $default;
	$size = count(resourcemng_columns($res_type));
	for($i=0;$i<$size;$i++) {
		$val = isset($input['col_'.$i]) ? trim($input['col_'.$i]) : '';
		$val = str_replace(array("\n","\r"), array('',''), $val);
		// 最后一列是 itmpara（JSON），必须保留半角逗号
		if($i === $size - 1) {
			$row[$i] = $val;
		} else {
			$row[$i] = str_replace(',', '，', $val);
		}
	}
	return $row;
}

function resourcemng_validate_row($res_type, $row, &$err){
	$err = '';
	if($res_type === 'npc') {
		if(!is_array($row)) { $err = 'NPC配置必须为对象数组'; return false; }
		return true;
	}
	if(empty($row) || trim($row[0]) === '') {
		$err = '首字段不能为空';
		return false;
	}
	// CSV 资源的最后一列为 itmpara，若填写则应为合法 JSON
	$last = count($row) - 1;
	if($last >= 0) {
		$itmpara = trim($row[$last]);
		if($itmpara !== '' && $itmpara !== 'null') {
			json_decode($itmpara, true);
			if(json_last_error() !== JSON_ERROR_NONE) {
				$err = 'itmpara 不是合法JSON：'.json_last_error_msg();
				return false;
			}
		}
	}
	return true;
}
?>
