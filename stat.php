<?php

define('CURSCRIPT', 'stat');

require './include/common.inc.php';

$k=(int)$_GET['lim'];

$result = $db->query("SELECT ip FROM {$tablepre}users WHERE lastgame>='$k'");

$cnt=0;

while ($dat = $db->fetch_array($result)) 
{
	$data=$dat['ip'];
	$i=strlen($data)-1;
	while ($i>=0 && $data[$i]!='.') $i--;
	if ($i<0) echo "something wrong..";
	$data=substr($data,0,$i);
	//echo $data."<br>";
	if (!isset($hash[$data])) { $hash[$data]=1; $cnt++; }
}

echo $cnt;

?>
