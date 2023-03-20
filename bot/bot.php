<?php
require './include/common.inc.php';
require './bot/revbotservice.php';
while (true) {
    botservice();
    sleep($botcds);
}
?>