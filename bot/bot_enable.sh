#!/bin/bash
BOT_CD=2
cd ..
while true; do 
    php bot/revbotservice.php
    sleep $BOT_CD
done
