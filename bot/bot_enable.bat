@echo off
set BOT_CD=0.5
cd ..
:loop
php bot/revbotservice.php
timeout /t %BOT_CD%
goto loop