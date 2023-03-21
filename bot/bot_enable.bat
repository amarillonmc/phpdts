@echo off
set BOT_CD=1
cd ..
:loop
php bot/revbotservice.php
timeout /t %BOT_CD%
goto loop
