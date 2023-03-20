#!/bin/bash
BOT_CD=0.5
cd ..
while true; do 
    php bot/revbotservice.php
    sleep $BOT_CD
done
