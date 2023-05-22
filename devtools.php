<?php
/*GPT:
这段代码是用于在使用 PHP 语言开发的网页中设置一些 HTTP 头部信息和输出缓冲设置。下面是对每一行代码的解释：

@ob_end_clean();

ob_end_clean() 函数用于清空输出缓冲区，并关闭输出缓冲。@ 符号用于抑制任何可能的错误或警告信息。
header('Content-Type: text/HTML; charset=utf-8');

这行代码设置响应头部，指定返回给浏览器的内容类型为 HTML，并指定字符集为 UTF-8。
header('Cache-Control: no-cache');

这行代码设置响应头部，告知浏览器不要对页面进行缓存。每次请求页面时都会向服务器发送请求，以获取最新的内容。
header('X-Accel-Buffering: no');

这行代码设置响应头部，关闭加速缓冲。加速缓冲是一种将响应内容在服务器端缓冲一段时间后再发送给客户端的技术。该行代码禁用了这种缓冲。
@ini_set('implicit_flush',1);

ini_set() 函数用于设置 PHP 配置选项的值。这行代码设置了 implicit_flush 选项为 1，启用了隐式刷新。隐式刷新表示在输出内容到浏览器之后立即将其发送给客户端，而不需要等待脚本执行完毕。
ob_implicit_flush(1);

ob_implicit_flush() 函数用于启用输出缓冲的隐式刷新。这行代码启用了隐式刷新功能。
set_time_limit(0);

set_time_limit() 函数用于设置脚本的最大执行时间。将参数设置为 0 表示不限制脚本的执行时间。
@ini_set('zlib.output_compression',0);

这行代码设置了 zlib.output_compression 选项为 0，禁用了输出内容的压缩。默认情况下，PHP 可能会对输出内容进行压缩以减少数据传输量，但该行代码禁用了这种压缩。
这些代码的目的是为了确保在输出内容到浏览器时能够实时显示，并禁用浏览器缓存和服务器端的输出缓冲。这对于实时显示动态内容或长时间运行的脚本非常有用。 */

define('CURSCRIPT', 'devtools');

require './include/common.inc.php';

if(!$udata) { gexit($_ERROR['no_login'],__file__,__line__); }
if($udata['password'] != $cpass) { gexit($_ERROR['wrong_pw'], __file__, __line__); }
elseif($udata['groupid'] < 9) { gexit($_ERROR['no_admin'], __file__, __line__); }

$action = !empty($_POST['action']) ? $_POST['action'] : $_GET['action'];

$exit = '<br><a href="devtools.php" style="text-decoration: none">
<span><font color="green">[返回]</font></span>'; 

if(empty($action))
{
	echo '临时开发工具列表：<br><br>

	<a href="devtools.php?action=print_itm" style="text-decoration: none">
		<span><font color="green">[生成所有道具名至 itmlist_'.$gamecfg.'.php]</font></span>
	</a>
	<br><br>
	<a href="devtools.php?action=print_titles" style="text-decoration: none">
		<span><font color="green">[生成所有头衔名至 titles_'.$gamecfg.'.php]</font></span>
	</a>
	<br><br>
	<a href="devtools.php?action=achrev_update" style="text-decoration: none">
		<span><font color="green">[更新所有用户的成就数据]</font></span>
	</a>
	<br><br>
	<a href="devtools.php?action=nicksrev_update" style="text-decoration: none">
		<span><font color="green">[更新所有用户的头衔数据]</font></span>
	</a>
	<br><br>';
}

ob_end_flush(); flush();

if (isset($action))
{
	ob_start();
	switch ($action) {
		case 'print_itm':
			include GAME_ROOT.'./include/devtools/printitm.func.php';
			print_itm_namelist();
			break;
		case 'print_titles':
			include GAME_ROOT.'./include/devtools/printtitles.func.php';
			print_titles_list();
			break;
		case 'achrev_update':
			include GAME_ROOT.'./include/devtools/achrevupdate.func.php';
			achrev_update();
			break;
		case 'nicksrev_update':
			include GAME_ROOT.'./include/devtools/achrevupdate.func.php';
			nicksrev_update();
			break;
		default:
			echo "无效的指令。".$exit;
			break;
	}
	ob_end_flush(); flush();
}


?>