<?php
$dir = dirname(__FILE__);



header("Content-Type: text/plain");

echo "/bin/find \"$dir\" -type d -exec /bin/chmod -c 0770 '{}' \\; \n";
passthru("/bin/find \"$dir\" -type d -exec /bin/chmod -c 0770 '{}' \\; 2>&1");

echo "\n\n";
echo "/bin/find \"$dir\" -type f -exec /bin/chmod -c 0660 '{}' \\; \n";
passthru("/bin/find  \"$dir\" -type f -exec /bin/chmod -c 0660 '{}' \\; 2>&1");

//var_dump($ret1,$ret2);

