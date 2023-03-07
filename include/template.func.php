<?php


if(!defined('IN_GAME')) {
	exit('Access Denied');
}

function parse_template($file, $templateid, $tpldir) {
	global $language;

	$nest = 5;
	$tplfile = GAME_ROOT."./$tpldir/$file.htm";
	$objfile = GAME_ROOT."./gamedata/templates/{$templateid}_$file.tpl.php";

	if(!$fp = fopen($tplfile, 'r')) {
		gexit("Current template file './$tpldir/$file.htm' not found or have no access!");
	} elseif(!include_once language('templates', $templateid, $tpldir)) {
		gexit("<br>Current template pack do not have a necessary language file 'templates.lang.php' or have syntax error!");
	}

	$template = fread($fp, filesize($tplfile));
	fclose($fp);

	$var_regexp = "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";
	$const_regexp = "([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)";

	$template = preg_replace("/([\n\r]+)\t+/s", "\\1", $template);
	$template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);
	$template = preg_replace_callback("/\{lang\s+(.+?)\}/is", function ($matches){
		return languagevar($matches[1]);
	}, $template);
	$template = str_replace("{LF}", "<?=\"\\n\"?>", $template);

	$template = preg_replace("/\{(\\\$[a-zA-Z0-9_\[\]\'\"\$\.\x7f-\xff]+)\}/s", "<?=\\1?>", $template);
	$template = preg_replace_callback("/$var_regexp/s", function ($matches){
 		return addquote("<?={$matches[1]}?>");
	}, $template);
	$template = preg_replace_callback("/\<\?\=\<\?\=$var_regexp\?\>\?\>/s", function ($matches){
		return addquote("<?={$matches[1]}?>");
	}, $template);

	$template = "<? if(!defined('IN_GAME')) exit('Access Denied'); ?>\n$template";
	$template = preg_replace("/[\n\r\t]*\{template\s+([a-z0-9_]+)\}[\n\r\t]*/is", "\n<? include template('\\1'); ?>\n", $template);
	$template = preg_replace("/[\n\r\t]*\{template\s+(.+?)\}[\n\r\t]*/is", "\n<? include template(\\1); ?>\n", $template);
	$template = preg_replace_callback("/[\n\r\t]*\{eval\s+(.+?)\}[\n\r\t]*/is", function ($matches){
		return stripvtags("\n<? {$matches[1]} ?>\n",'');
	}, $template);
	$template = preg_replace_callback("/[\n\r\t]*\{echo\s+(.+?)\}[\n\r\t]*/is", function ($matches){
		return stripvtags("\n<? echo {$matches[1]}; ?>\n",'');
	}, $template);
	$template = preg_replace_callback("/[\n\r\t]*\{elseif\s+(.+?)\}[\n\r\t]*/is", function ($matches){
		return stripvtags("\n<? } elseif({$matches[1]}) { ?>\n",'');
	}, $template);
	$template = preg_replace("/[\n\r\t]*\{else\}[\n\r\t]*/is", "\n<? } else { ?>\n", $template);

	for($i = 0; $i < $nest; $i++) {
		$template = preg_replace_callback("/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\}[\n\r]*(.+?)[\n\r]*\{\/loop\}[\n\r\t]*/is", function ($matches){
			return stripvtags("\n<? if(is_array({$matches[1]})) { foreach({$matches[1]} as {$matches[2]}) { ?>","\n{$matches[3]}\n<? } } ?>\n");
		}, $template);
		$template = preg_replace_callback("/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}[\n\r\t]*(.+?)[\n\r\t]*\{\/loop\}[\n\r\t]*/is", function ($matches){
			return stripvtags("\n<? if(is_array({$matches[1]})) { foreach({$matches[1]} as {$matches[2]} => {$matches[3]}) { ?>","\n{$matches[4]}\n<? } } ?>\n");
		}, $template);
		$template = preg_replace_callback("/[\n\r\t]*\{if\s+(.+?)\}[\n\r]*(.+?)[\n\r]*\{\/if\}[\n\r\t]*/is", function ($matches){
			return stripvtags("\n<? if({$matches[1]}) { ?>","\n{$matches[2]}\n<? } ?>\n");
		}, $template);
	}

	$template = preg_replace("/\{$const_regexp\}/s", "<?=\\1?>", $template);
	$template = preg_replace("/ \?\>[\n\r]*\<\? /s", " ", $template);
	
	$template = preg_replace("/\<\?/s", "<?php", $template);
	$template = preg_replace("/\<\?php\=/s", "<?php echo ", $template);

	if(!$fp = fopen($objfile, 'w')) {
		gexit("Directory './gamedata/templates/' not found or have no access!");
	}

	$template = preg_replace_callback("/\"(http)?[\w\.\/:]+\?[^\"]+?&[^\"]+?\"/", function ($matches){
		return transamp($matches[0]);
	}, $template);
	flock($fp, 2);
	fwrite($fp, $template);
	fclose($fp);
}

function transamp($str) {
	$str = str_replace('&', '&amp;', $str);
	$str = str_replace('&amp;amp;', '&amp;', $str);
	$str = str_replace('\"', '"', $str);
	return $str;
}

function addquote($var) {
	return str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $var));
}

function languagevar($var) {
	if(isset($GLOBALS['language'][$var])) {
		return $GLOBALS['language'][$var];
	} else {
		return "!$var!";
	}
}

function stripvtags($expr, $statement) {
	$expr = str_replace("\\\"", "\"", preg_replace("/\<\?\=(\\\$.+?)\?\>/s", "\\1", $expr));
	$statement = str_replace("\\\"", "\"", $statement);
	return $expr.$statement;
}

?>
