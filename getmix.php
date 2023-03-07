<?php

require './include/common.inc.php';

$mixfile = config('mixitem',$gamecfg);
include_once $mixfile;

$mixitem = array();
	foreach($mixinfo as $mix)
		if ($mix['result'][1]=='WP' || $mix['result'][1]=='WK' || $mix['result'][1]=='WG' || substr($mix['result'][1],0,2)=='WC' || $mix['result'][1]=='WD' || $mix['result'][1]=='WF' || $mix['result'][1]=='WJ')
		{
			echo $mix['result'][0]."\n";
			echo substr($mix['result'][1],0,2)."\n";
			echo $mix['result'][2]."\n";
			echo $mix['result'][3]."\n";
			echo $mix['result'][4]."\n";
		}

?>
